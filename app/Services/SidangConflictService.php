<?php

namespace App\Services;

use App\Models\Sidang;
use App\Models\Dosen;
use App\Models\DosenPengujiRule;
use App\Models\Ruang;
use Carbon\Carbon;

class SidangConflictService
{
    /**
     * Parse jam string into start and end minutes from midnight.
     * Example: "08.00 - 09.00" -> ['start' => 480, 'end' => 540]
     */
    public static function parseJamRange(?string $jam): ?array
    {
        if (!$jam) {
            return null;
        }

        $cleaned = str_replace(['.', ':'], ':', trim($jam));
        $cleaned = preg_replace('/[–—]/u', '-', $cleaned);
        $parts = explode('-', $cleaned);

        if (count($parts) < 2) {
            return null;
        }

        $startMinutes = self::timeToMinutes(trim($parts[0]));
        $endMinutes   = self::timeToMinutes(trim($parts[1]));

        if ($startMinutes === null || $endMinutes === null) {
            return null;
        }

        return [
            'start' => $startMinutes,
            'end'   => $endMinutes,
        ];
    }

    private static function timeToMinutes(string $timeStr): ?int
    {
        $parts = explode(':', $timeStr);
        if (count($parts) < 2) {
            return null;
        }
        $h = (int) $parts[0];
        $m = (int) $parts[1];
        return ($h * 60) + $m;
    }

    /**
     * Check if two time ranges overlap
     */
    public static function isTimeOverlap(?string $jam1, ?string $jam2): bool
    {
        $range1 = self::parseJamRange($jam1);
        $range2 = self::parseJamRange($jam2);

        if (!$range1 || !$range2) {
            return trim((string)$jam1) === trim((string)$jam2);
        }

        return ($range1['start'] < $range2['end']) && ($range2['start'] < $range1['end']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BUSINESS RULES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validate structural/business rules for a single Sidang data array.
     *
     * Rules:
     *   1. Dosen Pembimbing Utama WAJIB menjadi Anggota Penguji 2.
     *   2. Dosen Pembimbing Pendamping TIDAK BOLEH menjadi penguji mana pun
     *      (Ketua Penguji, Penguji 1, atau Penguji 2).
     *
     * @param array $data  Validated field array (or model attributes)
     * @return array       List of human-readable error messages (empty = OK)
     */
    /**
     * Ketua Penguji, Penguji 1, dan Penguji 2 wajib 3 dosen berbeda — berlaku
     * untuk semua jenis tugas akhir. Tanpa ini, dosen yang sama bisa terpilih
     * di lebih dari satu peran penguji sekaligus (mis. menjadi Ketua Penguji
     * sekaligus Penguji 1 pada sidang yang sama) tanpa terdeteksi oleh aturan
     * lain, karena itu bukan "bentrok" dengan record lain melainkan
     * inkonsistensi di dalam satu record itu sendiri.
     */
    public static function checkDuplicatePengujiRoles(array $data): array
    {
        $errors = [];

        $pengujiRoles = array_filter([
            'Ketua Penguji'     => (int) ($data['ketua_penguji_id']     ?? 0),
            'Anggota Penguji 1' => (int) ($data['anggota_penguji_1_id'] ?? 0),
            'Anggota Penguji 2' => (int) ($data['anggota_penguji_2_id'] ?? 0),
        ]);

        $seenRoleByDosenId = [];
        foreach ($pengujiRoles as $role => $dosenId) {
            if (isset($seenRoleByDosenId[$dosenId])) {
                $nama = Dosen::find($dosenId)?->nama_dosen ?? 'Dosen';
                $errors[] = "Pelanggaran: {$nama} tidak boleh menempati lebih dari satu peran penguji ({$seenRoleByDosenId[$dosenId]} dan {$role}) pada sidang yang sama.";
            } else {
                $seenRoleByDosenId[$dosenId] = $role;
            }
        }

        return $errors;
    }

    public static function checkBusinessRules(array $data): array
    {
        $errors = self::checkDuplicatePengujiRoles($data);

        if (($data['jenis_tugas_akhir'] ?? '') === 'sempro') {
            return $errors;
        }

        $pembimbingUtamaId     = (int) ($data['dosen_pembimbing_utama_id']      ?? 0);
        $pembimbingPendampingId = (int) ($data['dosen_pembimbing_pendamping_id'] ?? 0);
        $ketuaPengujiId        = (int) ($data['ketua_penguji_id']                ?? 0);
        $penguji1Id            = (int) ($data['anggota_penguji_1_id']            ?? 0);
        $penguji2Id            = (int) ($data['anggota_penguji_2_id']            ?? 0);

        // ── Rule 1: Pembimbing Utama WAJIB menjadi Anggota Penguji 2 ──────────
        if ($pembimbingUtamaId && $penguji2Id) {
            if ($pembimbingUtamaId !== $penguji2Id) {
                $dosenUtama = Dosen::find($pembimbingUtamaId);
                $namaDosen  = $dosenUtama?->nama_dosen ?? 'Pembimbing Utama';
                $errors[] = "Aturan 1 – Pelanggaran: Dosen Pembimbing Utama ({$namaDosen}) WAJIB menjadi Anggota Penguji 2 untuk mahasiswa bimbingannya. Silakan isi Penguji 2 dengan dosen yang sama.";
            }
        } elseif ($pembimbingUtamaId && !$penguji2Id) {
            // Penguji 2 belum diisi, ingatkan
            $dosenUtama = Dosen::find($pembimbingUtamaId);
            $namaDosen  = $dosenUtama?->nama_dosen ?? 'Pembimbing Utama';
            $errors[] = "Aturan 1 – Peringatan: Anggota Penguji 2 harus diisi dengan Dosen Pembimbing Utama ({$namaDosen}).";
        }

        // ── Rule 2: Pembimbing Pendamping TIDAK BOLEH menjadi penguji ─────────
        if ($pembimbingPendampingId) {
            $dosenPendamping = Dosen::find($pembimbingPendampingId);
            $namaPendamping  = $dosenPendamping?->nama_dosen ?? 'Pembimbing Pendamping';

            if ($pembimbingPendampingId === $ketuaPengujiId) {
                $errors[] = "Aturan 2 – Pelanggaran: Dosen Pembimbing Pendamping ({$namaPendamping}) TIDAK BOLEH menjadi Ketua Penguji untuk mahasiswa bimbingannya.";
            }
            if ($pembimbingPendampingId === $penguji1Id) {
                $errors[] = "Aturan 2 – Pelanggaran: Dosen Pembimbing Pendamping ({$namaPendamping}) TIDAK BOLEH menjadi Anggota Penguji 1 untuk mahasiswa bimbingannya.";
            }
            if ($pembimbingPendampingId === $penguji2Id) {
                $errors[] = "Aturan 2 – Pelanggaran: Dosen Pembimbing Pendamping ({$namaPendamping}) TIDAK BOLEH menjadi Anggota Penguji 2 untuk mahasiswa bimbingannya.";
            }
        }

        return $errors;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KOMPOSISI DOSEN PENGUJI (master data rule, dikelola admin)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validasi Rule Komposisi Dosen Penguji (master data terpisah):
     *   1. Pasangan dosen yang ditandai "tidak boleh" TIDAK BOLEH menjadi
     *      Ketua Penguji dan Penguji 1 sekaligus pada sidang yang sama.
     *      (Penguji 2 — yang otomatis diisi Pembimbing Utama — tidak ikut
     *      diperiksa dalam aturan ini.)
     *   2. Anggota Penguji 1 TIDAK BOLEH memiliki jabatan fungsional lebih
     *      tinggi dari Ketua Penguji.
     *
     * @param array $data  Validated field array (atau atribut model)
     * @return array       Daftar pesan error (kosong = tidak ada pelanggaran)
     */
    public static function checkPengujiCompositionRules(array $data): array
    {
        $errors = [];

        if (($data['jenis_tugas_akhir'] ?? '') === 'sempro') {
            return $errors;
        }

        $ketuaPengujiId = (int) ($data['ketua_penguji_id'] ?? 0);
        $penguji1Id     = (int) ($data['anggota_penguji_1_id'] ?? 0);

        // ── Rule: pasangan Ketua Penguji <-> Penguji 1 yang dilarang ────────────
        if ($ketuaPengujiId && $penguji1Id && $ketuaPengujiId !== $penguji1Id) {
            $blocked = DosenPengujiRule::blockedPartnersFor($ketuaPengujiId);
            if (in_array($penguji1Id, $blocked)) {
                $dosens = Dosen::whereIn('id', [$ketuaPengujiId, $penguji1Id])->get()->keyBy('id');
                $namaKetua = $dosens[$ketuaPengujiId]->nama_dosen ?? 'Dosen';
                $namaP1 = $dosens[$penguji1Id]->nama_dosen ?? 'Dosen';
                $errors[] = "Komposisi Penguji – Pelanggaran: {$namaKetua} dan {$namaP1} tidak boleh menjadi Ketua Penguji dan Penguji 1 sekaligus (sesuai Rule Komposisi Dosen Penguji).";
            }
        }

        // ── Rule: Penguji 1 tidak boleh berjabatan fungsional lebih tinggi dari Ketua Penguji ──
        if ($ketuaPengujiId && $penguji1Id && $ketuaPengujiId !== $penguji1Id) {
            $ketua = Dosen::find($ketuaPengujiId);
            $p1 = Dosen::find($penguji1Id);
            if ($ketua && $p1 && $ketua->jabatan_rank > 0 && $p1->jabatan_rank > 0 && $p1->jabatan_rank > $ketua->jabatan_rank) {
                $errors[] = "Komposisi Penguji – Pelanggaran: Anggota Penguji 1 ({$p1->nama_dosen}, {$p1->jabatan_fungsional}) tidak boleh memiliki jabatan fungsional lebih tinggi dari Ketua Penguji ({$ketua->nama_dosen}, {$ketua->jabatan_fungsional}).";
            }
        }

        return $errors;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SCHEDULE CONFLICT DETECTION (single record, for Store/Update validation)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check schedule conflicts for a single Sidang data (for Store/Update validation).
     * Returns human-readable messages only about time/room/lecturer overlaps.
     */
    /**
     * Dosen berstatus "Tugas Belajar" tidak boleh menjadi Ketua Penguji /
     * Anggota Penguji 1 / Anggota Penguji 2 — berlaku untuk Sempro maupun
     * Sidang Skripsi karena keduanya memakai kolom penguji yang sama.
     */
    public static function checkTugasBelajarRule(array $data): array
    {
        $errors = [];

        $roles = array_filter([
            'Ketua Penguji'    => $data['ketua_penguji_id']     ?? null,
            'Penguji 1'        => $data['anggota_penguji_1_id'] ?? null,
            'Penguji 2'        => $data['anggota_penguji_2_id'] ?? null,
        ]);

        if (empty($roles)) {
            return $errors;
        }

        $dosens = Dosen::whereIn('id', array_unique(array_values($roles)))->get()->keyBy('id');

        foreach ($roles as $role => $dosenId) {
            $dosen = $dosens->get($dosenId);
            if ($dosen && $dosen->isTugasBelajar()) {
                $errors[] = "Dosen {$dosen->nama_dosen} sedang berstatus Tugas Belajar dan tidak bisa ditugaskan sebagai {$role}.";
            }
        }

        return $errors;
    }

    public static function checkConflicts(array $data, ?int $excludeId = null): array
    {
        $conflicts = array_merge(self::checkDuplicatePengujiRoles($data), self::checkTugasBelajarRule($data));

        $tanggal = $data['tanggal'] ?? null;
        $jam     = $data['jam']     ?? null;
        $ruangId = $data['ruang_id'] ?? null;

        if (!$tanggal || !$jam) {
            return $conflicts;
        }

        // Reject an inverted/zero-length jam range (jam mulai >= jam selesai)
        // up front — otherwise isTimeOverlap()'s overlap math on this range
        // silently never flags a real conflict against it, for as long as the
        // corrupted record exists.
        $jamRange = self::parseJamRange($jam);
        if ($jamRange && $jamRange['end'] <= $jamRange['start']) {
            $conflicts[] = "Rentang jam '{$jam}' tidak valid: jam mulai harus lebih awal dari jam selesai.";
            return array_unique($conflicts);
        }

        $tanggalYmd = ($tanggal instanceof Carbon)
            ? $tanggal->format('Y-m-d')
            : Carbon::parse($tanggal)->format('Y-m-d');

        $tglIndo = Carbon::parse($tanggalYmd)->locale('id')->isoFormat('D MMMM Y');

        $existing = Sidang::with(['ruang', 'pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'])
            ->whereDate('tanggal', $tanggalYmd)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get();

        $isSemproNew = ($data['jenis_tugas_akhir'] ?? '') === 'sempro';
        $newDosenRoles = array_filter([
            'Pembimbing Utama'      => $data['dosen_pembimbing_utama_id']      ?? null,
            'Pembimbing Pendamping' => $isSemproNew ? ($data['dosen_pembimbing_pendamping_id'] ?? null) : null,
            'Ketua Penguji'         => $data['ketua_penguji_id']               ?? null,
            'Penguji 1'             => $data['anggota_penguji_1_id']           ?? null,
            'Penguji 2'             => $data['anggota_penguji_2_id']           ?? null,
        ]);

        foreach ($existing as $item) {
            if (!self::isTimeOverlap($jam, $item->jam)) {
                continue;
            }

            // 1. Room Conflict
            if ($ruangId && (int) $item->ruang_id === (int) $ruangId) {
                $ruangNama = $item->ruang?->kode_ruangan ?? 'Ruangan';
                $conflicts[] = "Bentrok Ruangan '{$ruangNama}': Sudah dipakai ujian '{$item->nama_mahasiswa}' pada {$tglIndo} jam {$item->jam}.";
            }

            // 1b. Mahasiswa Conflict — cegah mahasiswa yang sama terjadwal di
            // dua ujian yang waktunya tumpang tindih (mis. sempro & sidang
            // skripsi, atau dua record duplikat, di jam yang sama).
            $newNim = trim((string) ($data['nim'] ?? ''));
            if ($newNim !== '' && trim((string) $item->nim) === $newNim) {
                $conflicts[] = "Bentrok Mahasiswa '{$item->nama_mahasiswa}' (NIM {$newNim}): Sudah terjadwal pada ujian lain di {$tglIndo} jam {$item->jam}.";
            }

            // 2. Dosen Conflicts (Pembimbing Pendamping only tests in Sempro)
            $isSemproItem = $item->jenis_tugas_akhir === 'sempro';
            $existingDosenRoles = array_filter([
                'Pembimbing Utama'      => $item->dosen_pembimbing_utama_id,
                'Pembimbing Pendamping' => $isSemproItem ? $item->dosen_pembimbing_pendamping_id : null,
                'Ketua Penguji'         => $item->ketua_penguji_id,
                'Penguji 1'             => $item->anggota_penguji_1_id,
                'Penguji 2'             => $item->anggota_penguji_2_id,
            ]);

            foreach ($newDosenRoles as $newRole => $newDosenId) {
                foreach ($existingDosenRoles as $existingRole => $existingDosenId) {
                    if ((int) $newDosenId === (int) $existingDosenId) {
                        $dosen     = Dosen::find($newDosenId);
                        $dosenNama = $dosen?->nama_dosen ?? 'Dosen';
                        $conflicts[] = "Bentrok Dosen '{$dosenNama}' (sebagai {$newRole}): Sedang bertugas sebagai {$existingRole} untuk ujian '{$item->nama_mahasiswa}' pada {$tglIndo} jam {$item->jam}.";
                    }
                }
            }
        }

        return array_unique($conflicts);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SEMPRO JADWAL TERPISAH (Pembimbing Utama & Pendamping tidak bisa
    // menyamakan jadwal — mahasiswa diuji di DUA sesi/tanggal berbeda)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Khusus Sempro dengan jadwal_terpisah=true: Pembimbing Utama (Ketua
     * Penguji) dan Pembimbing Pendamping (Anggota Penguji 1) menguji
     * mahasiswa yang sama di DUA tanggal/jam/ruang berbeda. checkConflicts()
     * biasa mengasumsikan satu tanggal/jam/ruang untuk seluruh dewan penguji,
     * jadi di sini dijalankan terpisah per sesi — masing-masing TANPA
     * menyertakan peran dosen dari sesi yang lain (dosen itu memang tidak
     * hadir di sesi tsb) — ditambah satu pengecekan manual terhadap sesi
     * PENDAMPING milik sidang lain yang juga berjadwal terpisah, satu-satunya
     * hal yang tidak terlihat oleh checkConflicts() karena query intinya
     * hanya melihat kolom `tanggal` utama, bukan `tanggal_pendamping`.
     *
     * @param array $data Harus memuat: nim, dosen_pembimbing_utama_id,
     *   ketua_penguji_id, tanggal, jam, ruang_id (sesi utama) DAN
     *   dosen_pembimbing_pendamping_id, anggota_penguji_1_id,
     *   tanggal_pendamping, jam_pendamping, ruang_id_pendamping (sesi kedua).
     */
    public static function checkSplitSemproConflicts(array $data, ?int $excludeId = null): array
    {
        $conflicts = [];

        $sesiUtama = [
            'nim'                            => $data['nim'] ?? null,
            'jenis_tugas_akhir'              => 'sempro',
            'tanggal'                        => $data['tanggal'] ?? null,
            'jam'                            => $data['jam'] ?? null,
            'ruang_id'                       => $data['ruang_id'] ?? null,
            'dosen_pembimbing_utama_id'      => $data['dosen_pembimbing_utama_id'] ?? null,
            'dosen_pembimbing_pendamping_id' => null,
            'ketua_penguji_id'               => $data['ketua_penguji_id'] ?? null,
            'anggota_penguji_1_id'           => null,
            'anggota_penguji_2_id'           => null,
        ];

        $sesiPendamping = [
            'nim'                            => $data['nim'] ?? null,
            'jenis_tugas_akhir'              => 'sempro',
            'tanggal'                        => $data['tanggal_pendamping'] ?? null,
            'jam'                            => $data['jam_pendamping'] ?? null,
            'ruang_id'                       => $data['ruang_id_pendamping'] ?? null,
            'dosen_pembimbing_utama_id'      => null,
            'dosen_pembimbing_pendamping_id' => $data['dosen_pembimbing_pendamping_id'] ?? null,
            'ketua_penguji_id'               => null,
            'anggota_penguji_1_id'           => $data['anggota_penguji_1_id'] ?? null,
            'anggota_penguji_2_id'           => null,
        ];

        foreach (self::checkConflicts($sesiUtama, $excludeId) as $msg) {
            $conflicts[] = "[Sesi Pembimbing Utama] {$msg}";
        }
        foreach (self::checkConflicts($sesiPendamping, $excludeId) as $msg) {
            $conflicts[] = "[Sesi Pembimbing Pendamping] {$msg}";
        }

        foreach ([
            ['sesi' => $sesiUtama,      'label' => 'Sesi Pembimbing Utama'],
            ['sesi' => $sesiPendamping, 'label' => 'Sesi Pembimbing Pendamping'],
        ] as $check) {
            $conflicts = array_merge(
                $conflicts,
                self::checkAgainstOtherPendampingSessions($check['sesi'], $check['label'], $excludeId)
            );
        }

        return array_unique($conflicts);
    }

    /**
     * Bagian kedua dari checkSplitSemproConflicts(): bandingkan satu sesi baru
     * terhadap sesi PENDAMPING milik sidang LAIN yang juga berjadwal terpisah
     * — satu-satunya kombinasi yang tidak tercakup oleh checkConflicts() biasa.
     */
    private static function checkAgainstOtherPendampingSessions(array $sesi, string $label, ?int $excludeId): array
    {
        if (!$sesi['tanggal'] || !$sesi['jam']) {
            return [];
        }

        $tanggalYmd = $sesi['tanggal'] instanceof Carbon
            ? $sesi['tanggal']->format('Y-m-d')
            : Carbon::parse($sesi['tanggal'])->format('Y-m-d');

        $others = Sidang::with('ruangPendamping')
            ->where('jadwal_terpisah', true)
            ->whereDate('tanggal_pendamping', $tanggalYmd)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get();

        if ($others->isEmpty()) {
            return [];
        }

        $tglIndo = Carbon::parse($tanggalYmd)->locale('id')->isoFormat('D MMMM Y');
        $conflicts = [];

        foreach ($others as $other) {
            if (!self::isTimeOverlap($sesi['jam'], $other->jam_pendamping)) {
                continue;
            }

            if ($sesi['ruang_id'] && (int) $other->ruang_id_pendamping === (int) $sesi['ruang_id']) {
                $ruangNama = $other->ruangPendamping?->kode_ruangan ?? 'Ruangan';
                $conflicts[] = "[{$label}] Bentrok Ruangan '{$ruangNama}': Sudah dipakai sesi Pembimbing Pendamping ujian '{$other->nama_mahasiswa}' pada {$tglIndo} jam {$other->jam_pendamping}.";
            }

            $newDosenRoles = array_filter([
                'Pembimbing Utama'      => $sesi['dosen_pembimbing_utama_id'] ?? null,
                'Pembimbing Pendamping' => $sesi['dosen_pembimbing_pendamping_id'] ?? null,
                'Ketua Penguji'         => $sesi['ketua_penguji_id'] ?? null,
                'Penguji 1'             => $sesi['anggota_penguji_1_id'] ?? null,
            ]);
            $otherDosenRoles = array_filter([
                'Pembimbing Pendamping' => $other->dosen_pembimbing_pendamping_id,
                'Penguji 1'             => $other->anggota_penguji_1_id,
            ]);

            foreach ($newDosenRoles as $newRole => $newDosenId) {
                foreach ($otherDosenRoles as $otherRole => $otherDosenId) {
                    if ((int) $newDosenId === (int) $otherDosenId) {
                        $dosenNama = Dosen::find($newDosenId)?->nama_dosen ?? 'Dosen';
                        $conflicts[] = "[{$label}] Bentrok Dosen '{$dosenNama}' (sebagai {$newRole}): Sedang bertugas sebagai {$otherRole} (sesi Pembimbing Pendamping) untuk ujian '{$other->nama_mahasiswa}' pada {$tglIndo} jam {$other->jam_pendamping}.";
                    }
                }
            }
        }

        return $conflicts;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETECT ALL CONFLICTS (full list, for table badges + edit modal)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Map all conflicts (schedule + business rules) in a collection of Sidangs for UI display.
     *
     * Returns: [sidang_id => ['schedule' => [...], 'rules' => [...]]]
     *   - 'schedule' : time/room/lecturer overlap messages
     *   - 'rules'    : business rule violation messages
     *
     * The table shows only "Jadwal Bentrok" badge (if schedule conflicts exist).
     * The edit modal shows both schedule conflicts and rule violations in detail.
     */
    public static function detectAllConflicts($sidangs): array
    {
        $conflictMap = [];
        $sidangList  = $sidangs->values();

        // ── Pass 1: Business Rule violations (per-record, no comparison needed) ──
        foreach ($sidangList as $s) {
            if ($s->jenis_tugas_akhir === 'sempro') continue;

            $rules = self::checkBusinessRules([
                'jenis_tugas_akhir'              => $s->jenis_tugas_akhir,
                'dosen_pembimbing_utama_id'      => $s->dosen_pembimbing_utama_id,
                'dosen_pembimbing_pendamping_id' => $s->dosen_pembimbing_pendamping_id,
                'ketua_penguji_id'               => $s->ketua_penguji_id,
                'anggota_penguji_1_id'           => $s->anggota_penguji_1_id,
                'anggota_penguji_2_id'           => $s->anggota_penguji_2_id,
            ]);

            if (!empty($rules)) {
                $conflictMap[$s->id]['rules'] = $rules;
            }
        }

        // ── Pass 2: Schedule overlaps (pairwise comparison, scoped per date) ──
        // Bucketing by tanggal first turns the comparison from O(n²) over the
        // whole dataset into O(n²) only within each day's (small) sidang count.
        $byDate = [];
        foreach ($sidangList as $s) {
            if (!$s->tanggal || !$s->jam) continue;
            $key = $s->tanggal instanceof Carbon
                ? $s->tanggal->format('Y-m-d')
                : Carbon::parse($s->tanggal)->format('Y-m-d');
            $byDate[$key][] = $s;
        }

        // Batch-load every dosen name referenced across all sidangs once, instead
        // of querying per conflicting pair inside the loop below.
        $dosenIds = [];
        foreach ($sidangList as $s) {
            foreach ([
                $s->dosen_pembimbing_utama_id,
                $s->dosen_pembimbing_pendamping_id,
                $s->ketua_penguji_id,
                $s->anggota_penguji_1_id,
                $s->anggota_penguji_2_id,
            ] as $id) {
                if ($id) $dosenIds[$id] = true;
            }
        }
        $dosenNames = empty($dosenIds)
            ? collect()
            : Dosen::withTrashed()->whereIn('id', array_keys($dosenIds))->pluck('nama_dosen', 'id');

        foreach ($byDate as $dateKey => $dayList) {
            $tglIndo = Carbon::parse($dateKey)->locale('id')->isoFormat('D MMMM Y');
            $dayCount = count($dayList);

            for ($i = 0; $i < $dayCount; $i++) {
                $a = $dayList[$i];

                for ($j = $i + 1; $j < $dayCount; $j++) {
                    $b = $dayList[$j];

                    if (!self::isTimeOverlap($a->jam, $b->jam)) continue;

                    // Room conflict
                    if ($a->ruang_id && $a->ruang_id == $b->ruang_id) {
                        $ruangKode = $a->ruang?->kode_ruangan ?? 'Ruang';
                        $conflictMap[$a->id]['schedule'][] = "Bentrok Ruangan '{$ruangKode}': Bersamaan dengan ujian '{$b->nama_mahasiswa}' ({$tglIndo}, {$b->jam})";
                        $conflictMap[$b->id]['schedule'][] = "Bentrok Ruangan '{$ruangKode}': Bersamaan dengan ujian '{$a->nama_mahasiswa}' ({$tglIndo}, {$a->jam})";
                    }

                    // Dosen conflicts (Pembimbing Pendamping only tests in Sempro)
                    $examinersA = array_filter([
                        'Pembimbing Utama'      => $a->dosen_pembimbing_utama_id,
                        'Pembimbing Pendamping' => $a->jenis_tugas_akhir === 'sempro' ? $a->dosen_pembimbing_pendamping_id : null,
                        'Ketua Penguji'         => $a->ketua_penguji_id,
                        'Penguji 1'             => $a->anggota_penguji_1_id,
                        'Penguji 2'             => $a->anggota_penguji_2_id,
                    ]);

                    $examinersB = array_filter([
                        'Pembimbing Utama'      => $b->dosen_pembimbing_utama_id,
                        'Pembimbing Pendamping' => $b->jenis_tugas_akhir === 'sempro' ? $b->dosen_pembimbing_pendamping_id : null,
                        'Ketua Penguji'         => $b->ketua_penguji_id,
                        'Penguji 1'             => $b->anggota_penguji_1_id,
                        'Penguji 2'             => $b->anggota_penguji_2_id,
                    ]);

                    foreach ($examinersA as $roleA => $dosenIdA) {
                        foreach ($examinersB as $roleB => $dosenIdB) {
                            if ((int) $dosenIdA === (int) $dosenIdB) {
                                $dosenNama = $dosenNames[$dosenIdA] ?? 'Dosen';
                                $conflictMap[$a->id]['schedule'][] = "Bentrok Dosen '{$dosenNama}' (sebagai {$roleA}): Sudah bertugas sebagai {$roleB} di ujian '{$b->nama_mahasiswa}' ({$tglIndo}, {$b->jam})";
                                $conflictMap[$b->id]['schedule'][] = "Bentrok Dosen '{$dosenNama}' (sebagai {$roleB}): Sudah bertugas sebagai {$roleA} di ujian '{$a->nama_mahasiswa}' ({$tglIndo}, {$a->jam})";
                            }
                        }
                    }
                }
            }
        }

        // Deduplicate each subarray
        foreach ($conflictMap as $id => $groups) {
            if (isset($groups['schedule'])) {
                $conflictMap[$id]['schedule'] = array_values(array_unique($groups['schedule']));
            }
            if (isset($groups['rules'])) {
                $conflictMap[$id]['rules'] = array_values(array_unique($groups['rules']));
            }
        }

        return $conflictMap;
    }
}

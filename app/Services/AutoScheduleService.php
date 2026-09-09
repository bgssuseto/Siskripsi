<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\KesediaanDosen;
use App\Models\Ruang;
use App\Models\Sidang;
use Carbon\Carbon;

class AutoScheduleService
{
    private const SKRIPSI_BUCKET = ['skripsi', 'sidang', 'jurnal'];

    // Jam operasional sidang: 09.00–17.00. Kesediaan dosen di luar rentang ini
    // (mis. submit mulai 08.00) tetap dipangkas agar usulan jadwal tidak keluar jam kerja.
    private const JAM_OPERASIONAL_MULAI = 9 * 60;  // 09.00
    private const JAM_OPERASIONAL_SELESAI = 17 * 60; // 17.00

    /**
     * Generate jadwal usulan untuk seluruh Sidang yang belum di-plot pada
     * periode + jenis (+ gelombang opsional) tertentu, berbasis irisan
     * kesediaan dosen yang terlibat dan slot ruang yang masih kosong.
     *
     * Aturan tambahan:
     *  - Jika Ketua Penguji / Penguji 1 belum diisi, dipilih otomatis dari
     *    dosen yang punya kesediaan pada slot terkait, diprioritaskan yang
     *    beban mengujinya masih di bawah jumlah mahasiswa yang ia luluskan
     *    sebagai Pembimbing Utama.
     *  - Ruang dipilih agar seminimal mungkin berpindah-pindah: ruang yang
     *    sudah dipakai dosen terkait / siapapun pada hari yang sama diprioritaskan.
     *
     * @param array|null $sidangIds  Jika diisi, batasi hanya ke Sidang dengan id ini
     *                                (dipakai untuk aksi "Jadwalkan Terpilih" / bulk action
     *                                dari daftar mahasiswa, bukan seluruh periode/gelombang).
     *
     * Return: ['proposals' => [...], 'unresolved' => [...]]
     */
    public static function generateProposals(int $periodeId, string $jenisBucket, ?int $gelombang, int $slotMinutes = 60, ?array $sidangIds = null): array
    {
        $jenisList = $jenisBucket === 'sempro' ? ['sempro'] : self::SKRIPSI_BUCKET;

        $unscheduled = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'])
            ->where('periode_id', $periodeId)
            ->whereIn('jenis_tugas_akhir', $jenisList)
            ->where('verifikasi_status', 'disetujui')
            ->whereNull('tanggal')
            ->when($gelombang, fn ($q) => $q->where('gelombang', $gelombang))
            ->when(!empty($sidangIds), fn ($q) => $q->whereIn('id', $sidangIds))
            ->orderBy('tanggal_pendaftaran')
            ->orderBy('id')
            ->get();

        if ($unscheduled->isEmpty()) {
            return ['proposals' => [], 'unresolved' => []];
        }

        $ruangs = Ruang::orderBy('kode_ruangan')->get()
            ->filter(fn ($r) => trim($r->kode_ruangan) !== 'Langsung Pemberkasan' && $r->isSiapDigunakan())
            ->values();

        $bebanDosen = self::calculateBebanDosen();

        // ── Busy maps, seeded dari seluruh Sidang yang SUDAH terjadwal (sistem-wide) ──
        $busyDosen = [];      // [dosen_id][Y-m-d] => [[start,end], ...]
        $busyRuang = [];      // [ruang_id][Y-m-d] => [[start,end], ...]
        $dosenRuangHariIni = []; // [dosen_id][Y-m-d] => [ruang_id => true, ...]

        Sidang::whereNotNull('tanggal')->whereNotNull('jam')->get()->each(function ($s) use (&$busyDosen, &$busyRuang, &$dosenRuangHariIni) {
            self::markBusyFromSidang($s, $busyDosen, $busyRuang, $dosenRuangHariIni);
        });

        // ── Kesediaan dosen: [dosen_id][Y-m-d] => [[start,end], ...] ──
        $kesediaanMap = [];
        KesediaanDosen::where('periode_id', $periodeId)->get()->each(function ($k) use (&$kesediaanMap) {
            if (!$k->tanggal || !$k->jam_mulai || !$k->jam_selesai) {
                return;
            }
            $tgl = $k->tanggal->format('Y-m-d');
            $start = self::timeStringToMinutes((string) $k->jam_mulai);
            $end = self::timeStringToMinutes((string) $k->jam_selesai);
            if ($start === null || $end === null || $end <= $start) {
                return;
            }

            // Pangkas ke jam operasional sidang (09.00–17.00) walaupun kesediaan dosen lebih lebar
            $start = max($start, self::JAM_OPERASIONAL_MULAI);
            $end = min($end, self::JAM_OPERASIONAL_SELESAI);
            if ($end <= $start) {
                return;
            }

            $kesediaanMap[$k->dosen_id][$tgl][] = [$start, $end];
        });

        $proposals = [];
        $unresolved = [];

        // Trio Ketua Penguji + Penguji 1 yang TERAKHIR berhasil dipasangkan secara
        // otomatis untuk setiap tanggal, dicoba dipakai ulang dulu sebelum memilih
        // pasangan baru — supaya dewan penguji yang sama menempel di satu tanggal
        // (dan, lewat preferensi pickRuang(), di satu ruang) sepanjang hari alih-alih
        // berganti-ganti tiap mahasiswa.
        $lastTrioPerTanggal = []; // [Y-m-d] => [ketuaId, p1Id]

        foreach ($unscheduled as $sidang) {
            $isSempro = $sidang->jenis_tugas_akhir === 'sempro';

            if (!$sidang->dosen_pembimbing_utama_id) {
                $unresolved[] = self::unresolvedEntry($sidang, 'Dosen Pembimbing Utama belum diisi.');
                continue;
            }

            $needsPengujiAssignment = !$isSempro && (!$sidang->ketua_penguji_id || !$sidang->anggota_penguji_1_id);

            if ($needsPengujiAssignment) {
                $result = self::scheduleWithAutoPenguji($sidang, $kesediaanMap, $busyDosen, $busyRuang, $dosenRuangHariIni, $ruangs, $slotMinutes, $bebanDosen, $lastTrioPerTanggal);
                if ($result) {
                    $proposals[] = $result;
                } else {
                    $unresolved[] = self::unresolvedEntry($sidang, 'Tidak ditemukan kombinasi Ketua/Penguji 1 & slot kosong yang cocok dari kesediaan yang ada.');
                }
                continue;
            }

            $requiredDosenIds = ['Pembimbing Utama' => $sidang->dosen_pembimbing_utama_id];

            if ($isSempro) {
                if ($sidang->dosen_pembimbing_pendamping_id) {
                    $requiredDosenIds['Pembimbing Pendamping'] = $sidang->dosen_pembimbing_pendamping_id;
                }
            } else {
                $requiredDosenIds['Ketua Penguji'] = $sidang->ketua_penguji_id;
                $requiredDosenIds['Penguji 1'] = $sidang->anggota_penguji_1_id;
                // anggota_penguji_2_id sudah otomatis = pembimbing utama (Sidang::booted())

                $compositionErrors = SidangConflictService::checkPengujiCompositionRules([
                    'jenis_tugas_akhir'     => $sidang->jenis_tugas_akhir,
                    'ketua_penguji_id'      => $sidang->ketua_penguji_id,
                    'anggota_penguji_1_id'  => $sidang->anggota_penguji_1_id,
                    'anggota_penguji_2_id'  => $sidang->anggota_penguji_2_id,
                ]);
                if (!empty($compositionErrors)) {
                    $unresolved[] = self::unresolvedEntry($sidang, 'Dewan penguji yang sudah diisi melanggar Rule Komposisi Dosen Penguji: ' . implode(' ', $compositionErrors));
                    continue;
                }
            }

            $uniqueDosenIds = array_unique(array_values($requiredDosenIds));

            $missingKesediaan = array_filter($uniqueDosenIds, fn ($id) => empty($kesediaanMap[$id]));
            if (!empty($missingKesediaan)) {
                $namaDosen = Dosen::whereIn('id', $missingKesediaan)->pluck('nama_dosen')->implode(', ');
                $unresolved[] = self::unresolvedEntry($sidang, "Menunggu kesediaan dosen: {$namaDosen}.");
                continue;
            }

            // Kandidat tanggal = irisan tanggal yang tersedia untuk SEMUA dosen terkait
            $tanggalCandidates = null;
            foreach ($uniqueDosenIds as $dosenId) {
                $tanggalDosen = array_keys($kesediaanMap[$dosenId]);
                $tanggalCandidates = $tanggalCandidates === null ? $tanggalDosen : array_intersect($tanggalCandidates, $tanggalDosen);
            }
            sort($tanggalCandidates);

            if (empty($tanggalCandidates)) {
                $unresolved[] = self::unresolvedEntry($sidang, 'Tidak ada tanggal kesediaan yang beririsan untuk seluruh dosen terkait.');
                continue;
            }

            $found = false;

            foreach ($tanggalCandidates as $tgl) {
                $freeWindows = null;
                foreach ($uniqueDosenIds as $dosenId) {
                    $windows = self::subtractBusy($kesediaanMap[$dosenId][$tgl], $busyDosen[$dosenId][$tgl] ?? []);
                    $freeWindows = $freeWindows === null ? $windows : self::intersectWindows($freeWindows, $windows);
                    if (empty($freeWindows)) {
                        break;
                    }
                }

                if (empty($freeWindows)) {
                    continue;
                }

                $slots = self::sliceWindowsIntoSlots($freeWindows, $slotMinutes);

                foreach ($slots as [$slotStart, $slotEnd]) {
                    $ruangId = self::pickRuang($ruangs, $tgl, $slotStart, $slotEnd, $busyRuang, $uniqueDosenIds, $dosenRuangHariIni);
                    if (!$ruangId) {
                        continue;
                    }

                    $ruang = $ruangs->firstWhere('id', $ruangId);
                    $jamStr = self::minutesToJamString($slotStart, $slotEnd);

                    $proposals[] = [
                        'sidang_id'            => $sidang->id,
                        'nama'                 => $sidang->nama_mahasiswa,
                        'nim'                  => $sidang->nim,
                        'jenis_label'          => $sidang->jenis_label,
                        'is_sempro'            => $isSempro,
                        'tanggal'              => $tgl,
                        'jam'                  => $jamStr,
                        'ruang_id'             => $ruang->id,
                        'ruang_nama'           => $ruang->kode_ruangan,
                        'dosen'                => $requiredDosenIds,
                        'beban'                => self::bebanForDosen($requiredDosenIds, $bebanDosen),
                        'ketua_penguji_id'     => $requiredDosenIds['Ketua Penguji'] ?? null,
                        'anggota_penguji_1_id' => $requiredDosenIds['Penguji 1'] ?? null,
                    ];

                    foreach ($uniqueDosenIds as $dosenId) {
                        $busyDosen[$dosenId][$tgl][] = [$slotStart, $slotEnd];
                        $dosenRuangHariIni[$dosenId][$tgl][$ruang->id] = true;
                    }
                    $busyRuang[$ruang->id][$tgl][] = [$slotStart, $slotEnd];

                    $found = true;
                    break 2;
                }
            }

            if (!$found) {
                $unresolved[] = self::unresolvedEntry($sidang, 'Tidak ditemukan irisan waktu & ruang kosong dari kesediaan yang ada.');
            }
        }

        return ['proposals' => $proposals, 'unresolved' => $unresolved];
    }

    /**
     * Hitung beban "meluluskan" (jumlah mahasiswa sudah sidang skripsi dengan
     * dosen sbg Pembimbing Utama) vs "menguji" (jumlah keikutsertaan sbg
     * Ketua/Penguji 1/Penguji 2 pada sidang yang sudah selesai) tiap dosen.
     * Skor lebih tinggi = lebih "berhutang" menguji, diprioritaskan lebih dulu.
     */
    public static function calculateBebanDosen(): array
    {
        $today = Carbon::now()->format('Y-m-d');

        $meluluskan = Sidang::whereIn('jenis_tugas_akhir', self::SKRIPSI_BUCKET)
            ->whereNotNull('tanggal')
            ->whereDate('tanggal', '<=', $today)
            ->whereNotNull('dosen_pembimbing_utama_id')
            ->selectRaw('dosen_pembimbing_utama_id as dosen_id, COUNT(*) as total')
            ->groupBy('dosen_pembimbing_utama_id')
            ->pluck('total', 'dosen_id');

        $menguji = [];
        foreach (['ketua_penguji_id', 'anggota_penguji_1_id', 'anggota_penguji_2_id'] as $col) {
            Sidang::whereIn('jenis_tugas_akhir', self::SKRIPSI_BUCKET)
                ->whereNotNull('tanggal')
                ->whereDate('tanggal', '<=', $today)
                ->whereNotNull($col)
                ->selectRaw("{$col} as dosen_id, COUNT(*) as total")
                ->groupBy($col)
                ->pluck('total', 'dosen_id')
                ->each(function ($total, $dosenId) use (&$menguji) {
                    $menguji[$dosenId] = ($menguji[$dosenId] ?? 0) + (int) $total;
                });
        }

        $beban = [];
        foreach (Dosen::pluck('id') as $id) {
            $lulus = (int) ($meluluskan[$id] ?? 0);
            $uji = (int) ($menguji[$id] ?? 0);
            $beban[$id] = [
                'meluluskan' => $lulus,
                'menguji'    => $uji,
                'skor'       => $lulus - $uji, // > 0 berarti "berhutang" menguji
            ];
        }

        return $beban;
    }

    private static function bebanForDosen(array $requiredDosenIds, array $bebanDosen): array
    {
        $out = [];
        foreach ($requiredDosenIds as $role => $dosenId) {
            $out[$role] = $bebanDosen[$dosenId] ?? ['meluluskan' => 0, 'menguji' => 0, 'skor' => 0];
        }
        return $out;
    }

    /**
     * Jalur khusus saat Ketua Penguji / Penguji 1 belum diisi manual: cari
     * tanggal+slot dari kesediaan Pembimbing Utama, lalu pilih 2 dosen lain
     * (bukan pembimbing utama/pendamping) yang tersedia di slot yang sama,
     * diprioritaskan yang skor "beban menguji"-nya paling tinggi.
     */
    private static function scheduleWithAutoPenguji(
        Sidang $sidang,
        array $kesediaanMap,
        array &$busyDosen,
        array &$busyRuang,
        array &$dosenRuangHariIni,
        $ruangs,
        int $slotMinutes,
        array $bebanDosen,
        array &$lastTrioPerTanggal
    ): ?array {
        $utamaId = $sidang->dosen_pembimbing_utama_id;
        if (empty($kesediaanMap[$utamaId])) {
            return null;
        }

        $excludeIds = array_filter([$utamaId, $sidang->dosen_pembimbing_pendamping_id]);
        $tanggalCandidates = array_keys($kesediaanMap[$utamaId]);
        sort($tanggalCandidates);

        foreach ($tanggalCandidates as $tgl) {
            $utamaFree = self::subtractBusy($kesediaanMap[$utamaId][$tgl], $busyDosen[$utamaId][$tgl] ?? []);
            if (empty($utamaFree)) {
                continue;
            }

            // Pool kandidat penguji: dosen lain yang punya kesediaan di tanggal ini
            $pool = [];
            foreach ($kesediaanMap as $dosenId => $tanggalMap) {
                if (in_array($dosenId, $excludeIds) || empty($tanggalMap[$tgl])) {
                    continue;
                }
                $free = self::subtractBusy($tanggalMap[$tgl], $busyDosen[$dosenId][$tgl] ?? []);
                if (empty($free)) {
                    continue;
                }
                $pool[$dosenId] = $free;
            }

            if (count($pool) < 2) {
                continue;
            }

            // Prioritaskan dosen dengan skor "beban menguji" tertinggi (paling berhutang)
            uksort($pool, function ($a, $b) use ($bebanDosen) {
                $scoreA = $bebanDosen[$a]['skor'] ?? 0;
                $scoreB = $bebanDosen[$b]['skor'] ?? 0;
                return $scoreB <=> $scoreA ?: $a <=> $b;
            });

            $slots = self::sliceWindowsIntoSlots($utamaFree, $slotMinutes);

            foreach ($slots as [$slotStart, $slotEnd]) {
                $kandidatTersedia = [];
                foreach ($pool as $dosenId => $free) {
                    if (self::slotFitsAnyWindow($slotStart, $slotEnd, $free)) {
                        $kandidatTersedia[] = $dosenId;
                    }
                }

                if (count($kandidatTersedia) < 2) {
                    continue;
                }

                // Coba pakai ulang dulu trio Ketua/Penguji 1 terakhir yang berhasil
                // dipasangkan di tanggal ini (kalau ada, masih tersedia di slot ini,
                // dan tidak melanggar Rule Komposisi terhadap pembimbing utama
                // mahasiswa ini) — supaya dewan penguji tidak berganti-ganti tiap
                // mahasiswa dan pickRuang() bisa menahan mereka di satu ruang.
                $pair = null;
                $reusedTrio = $lastTrioPerTanggal[$tgl] ?? null;
                if ($reusedTrio) {
                    [$reusedKetua, $reusedP1] = $reusedTrio;
                    $bothAvailable = in_array($reusedKetua, $kandidatTersedia) && in_array($reusedP1, $kandidatTersedia);
                    if ($bothAvailable) {
                        $errors = SidangConflictService::checkPengujiCompositionRules([
                            'jenis_tugas_akhir'    => 'skripsi',
                            'ketua_penguji_id'     => $reusedKetua,
                            'anggota_penguji_1_id' => $reusedP1,
                            'anggota_penguji_2_id' => $utamaId,
                        ]);
                        if (empty($errors)) {
                            $pair = [$reusedKetua, $reusedP1];
                        }
                    }
                }

                if (!$pair) {
                    $pair = self::pickValidPengujiPair($kandidatTersedia, $utamaId);
                }
                if (!$pair) {
                    continue;
                }
                [$ketuaId, $p1Id] = $pair;

                $dosenTerlibat = [$utamaId, $ketuaId, $p1Id];
                $ruangId = self::pickRuang($ruangs, $tgl, $slotStart, $slotEnd, $busyRuang, $dosenTerlibat, $dosenRuangHariIni);
                if (!$ruangId) {
                    continue;
                }

                $ruang = $ruangs->firstWhere('id', $ruangId);
                $jamStr = self::minutesToJamString($slotStart, $slotEnd);

                $requiredDosenIds = [
                    'Pembimbing Utama' => $utamaId,
                    'Ketua Penguji'    => $ketuaId,
                    'Penguji 1'        => $p1Id,
                ];

                foreach ($dosenTerlibat as $dosenId) {
                    $busyDosen[$dosenId][$tgl][] = [$slotStart, $slotEnd];
                    $dosenRuangHariIni[$dosenId][$tgl][$ruang->id] = true;
                }
                $busyRuang[$ruang->id][$tgl][] = [$slotStart, $slotEnd];
                $lastTrioPerTanggal[$tgl] = [$ketuaId, $p1Id];

                return [
                    'sidang_id'            => $sidang->id,
                    'nama'                 => $sidang->nama_mahasiswa,
                    'nim'                  => $sidang->nim,
                    'jenis_label'          => $sidang->jenis_label,
                    'is_sempro'            => false,
                    'tanggal'              => $tgl,
                    'jam'                  => $jamStr,
                    'ruang_id'             => $ruang->id,
                    'ruang_nama'           => $ruang->kode_ruangan,
                    'dosen'                => $requiredDosenIds,
                    'beban'                => self::bebanForDosen($requiredDosenIds, $bebanDosen),
                    'ketua_penguji_id'     => $ketuaId,
                    'anggota_penguji_1_id' => $p1Id,
                    'auto_penguji'         => true,
                ];
            }
        }

        return null;
    }

    /**
     * Dari daftar kandidat yang tersedia di slot yang sama (sudah terurut
     * prioritas beban menguji), cari pasangan Ketua Penguji + Penguji 1 yang
     * TIDAK melanggar Rule Komposisi Dosen Penguji (pasangan terlarang &
     * jenjang jabatan fungsional) — baik terhadap satu sama lain maupun
     * terhadap Pembimbing Utama (yang otomatis jadi Penguji 2).
     */
    private static function pickValidPengujiPair(array $kandidat, int $utamaId): ?array
    {
        $limit = min(count($kandidat), 12); // batasi kombinasi diperiksa agar tetap ringan

        for ($i = 0; $i < $limit; $i++) {
            for ($j = $i + 1; $j < $limit; $j++) {
                $a = $kandidat[$i];
                $b = $kandidat[$j];

                foreach ([[$a, $b], [$b, $a]] as [$ketuaId, $p1Id]) {
                    $errors = SidangConflictService::checkPengujiCompositionRules([
                        'jenis_tugas_akhir'    => 'skripsi',
                        'ketua_penguji_id'     => $ketuaId,
                        'anggota_penguji_1_id' => $p1Id,
                        'anggota_penguji_2_id' => $utamaId,
                    ]);
                    if (empty($errors)) {
                        return [$ketuaId, $p1Id];
                    }
                }
            }
        }

        return null;
    }

    private static function slotFitsAnyWindow(int $start, int $end, array $windows): bool
    {
        foreach ($windows as [$wStart, $wEnd]) {
            if ($start >= $wStart && $end <= $wEnd) {
                return true;
            }
        }
        return false;
    }

    /**
     * Pilih ruang untuk sebuah slot, mengutamakan ruang yang HARI ITU JUGA
     * sudah dipakai oleh salah satu dosen terkait (supaya dosen tidak
     * pindah-pindah ruang), lalu ruang yang sudah dipakai siapapun hari itu
     * (supaya jumlah ruang yang terpakai per hari tetap minim), baru
     * fallback ke ruang kosong berikutnya.
     */
    private static function pickRuang($ruangs, string $tgl, int $slotStart, int $slotEnd, array $busyRuang, array $preferredDosenIds, array $dosenRuangHariIni): ?int
    {
        $preferredRuangIds = [];
        foreach ($preferredDosenIds as $dosenId) {
            foreach (array_keys($dosenRuangHariIni[$dosenId][$tgl] ?? []) as $ruangId) {
                $preferredRuangIds[$ruangId] = true;
            }
        }

        $candidates = $ruangs->sortBy(function ($r) use ($preferredRuangIds, $busyRuang, $tgl) {
            if (isset($preferredRuangIds[$r->id])) {
                return 0;
            }
            if (!empty($busyRuang[$r->id][$tgl] ?? [])) {
                return 1;
            }
            return 2;
        });

        foreach ($candidates as $ruang) {
            $ruangBusy = $busyRuang[$ruang->id][$tgl] ?? [];
            if (!self::overlapsAny($slotStart, $slotEnd, $ruangBusy)) {
                return $ruang->id;
            }
        }

        return null;
    }

    private static function unresolvedEntry(Sidang $sidang, string $reason): array
    {
        return [
            'sidang_id'   => $sidang->id,
            'nama'        => $sidang->nama_mahasiswa,
            'nim'         => $sidang->nim,
            'jenis_label' => $sidang->jenis_label,
            'reason'      => $reason,
        ];
    }

    private static function markBusyFromSidang(Sidang $s, array &$busyDosen, array &$busyRuang, array &$dosenRuangHariIni): void
    {
        $range = SidangConflictService::parseJamRange($s->jam);
        if (!$range) {
            return;
        }

        $tgl = $s->tanggal instanceof Carbon ? $s->tanggal->format('Y-m-d') : Carbon::parse($s->tanggal)->format('Y-m-d');

        $isSempro = $s->jenis_tugas_akhir === 'sempro';
        $roleIds = array_filter([
            $s->dosen_pembimbing_utama_id,
            $isSempro ? $s->dosen_pembimbing_pendamping_id : null,
            $s->ketua_penguji_id,
            $s->anggota_penguji_1_id,
            $s->anggota_penguji_2_id,
        ]);

        foreach (array_unique($roleIds) as $dosenId) {
            $busyDosen[$dosenId][$tgl][] = [$range['start'], $range['end']];
            if ($s->ruang_id) {
                $dosenRuangHariIni[$dosenId][$tgl][$s->ruang_id] = true;
            }
        }

        if ($s->ruang_id) {
            $busyRuang[$s->ruang_id][$tgl][] = [$range['start'], $range['end']];
        }
    }

    private static function timeStringToMinutes(string $time): ?int
    {
        $time = trim($time);
        if ($time === '') {
            return null;
        }
        // Terima format "HH:mm[:ss]" atau "HH.mm"
        $normalized = str_replace('.', ':', $time);
        $parts = explode(':', $normalized);
        if (count($parts) < 2) {
            return null;
        }
        return ((int) $parts[0] * 60) + (int) $parts[1];
    }

    private static function minutesToJamString(int $start, int $end): string
    {
        $fmt = fn ($m) => sprintf('%02d.%02d', intdiv($m, 60), $m % 60);
        return $fmt($start) . ' - ' . $fmt($end);
    }

    /**
     * Kurangi window ketersediaan dengan rentang yang sudah busy, hasilkan
     * daftar sub-window yang benar-benar masih kosong.
     */
    private static function subtractBusy(array $windows, array $busyRanges): array
    {
        $result = $windows;
        foreach ($busyRanges as [$bStart, $bEnd]) {
            $next = [];
            foreach ($result as [$wStart, $wEnd]) {
                if ($bEnd <= $wStart || $bStart >= $wEnd) {
                    $next[] = [$wStart, $wEnd];
                    continue;
                }
                if ($bStart > $wStart) {
                    $next[] = [$wStart, min($bStart, $wEnd)];
                }
                if ($bEnd < $wEnd) {
                    $next[] = [max($bEnd, $wStart), $wEnd];
                }
            }
            $result = $next;
        }
        return $result;
    }

    private static function intersectWindows(array $a, array $b): array
    {
        $result = [];
        foreach ($a as [$aStart, $aEnd]) {
            foreach ($b as [$bStart, $bEnd]) {
                $start = max($aStart, $bStart);
                $end = min($aEnd, $bEnd);
                if ($end > $start) {
                    $result[] = [$start, $end];
                }
            }
        }
        return $result;
    }

    private static function sliceWindowsIntoSlots(array $windows, int $slotMinutes): array
    {
        $slots = [];
        usort($windows, fn ($a, $b) => $a[0] <=> $b[0]);
        foreach ($windows as [$start, $end]) {
            for ($s = $start; $s + $slotMinutes <= $end; $s += $slotMinutes) {
                $slots[] = [$s, $s + $slotMinutes];
            }
        }
        return $slots;
    }

    private static function overlapsAny(int $start, int $end, array $busyRanges): bool
    {
        foreach ($busyRanges as [$bStart, $bEnd]) {
            if ($start < $bEnd && $bStart < $end) {
                return true;
            }
        }
        return false;
    }
}

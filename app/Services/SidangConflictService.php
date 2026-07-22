<?php

namespace App\Services;

use App\Models\Sidang;
use App\Models\Dosen;
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
    public static function checkBusinessRules(array $data): array
    {
        $errors = [];

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
    // SCHEDULE CONFLICT DETECTION (single record, for Store/Update validation)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check schedule conflicts for a single Sidang data (for Store/Update validation).
     * Returns human-readable messages only about time/room/lecturer overlaps.
     */
    public static function checkConflicts(array $data, ?int $excludeId = null): array
    {
        $conflicts = [];

        $tanggal = $data['tanggal'] ?? null;
        $jam     = $data['jam']     ?? null;
        $ruangId = $data['ruang_id'] ?? null;

        if (!$tanggal || !$jam) {
            return $conflicts;
        }

        $tanggalYmd = ($tanggal instanceof Carbon)
            ? $tanggal->format('Y-m-d')
            : Carbon::parse($tanggal)->format('Y-m-d');

        $tglIndo = Carbon::parse($tanggalYmd)->locale('id')->isoFormat('D MMMM Y');

        $existing = Sidang::with(['ruang', 'pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'])
            ->whereDate('tanggal', $tanggalYmd)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get();

        $newDosenRoles = [];
        if (($data['jenis_tugas_akhir'] ?? '') !== 'sempro') {
            $newDosenRoles = array_filter([
                'Ketua Penguji'     => $data['ketua_penguji_id']    ?? null,
                'Penguji 1'         => $data['anggota_penguji_1_id'] ?? null,
                'Penguji 2'         => $data['anggota_penguji_2_id'] ?? null,
            ]);
        }

        foreach ($existing as $item) {
            if (!self::isTimeOverlap($jam, $item->jam)) {
                continue;
            }

            // 1. Room Conflict
            if ($ruangId && (int) $item->ruang_id === (int) $ruangId) {
                $ruangNama = $item->ruang?->kode_ruangan ?? 'Ruangan';
                $conflicts[] = "Bentrok Ruangan '{$ruangNama}': Sudah dipakai ujian '{$item->nama_mahasiswa}' pada {$tglIndo} jam {$item->jam}.";
            }

            // 2. Examiner conflicts (only if neither is sempro)
            if (($data['jenis_tugas_akhir'] ?? '') !== 'sempro' && $item->jenis_tugas_akhir !== 'sempro') {
                $existingExaminerRoles = array_filter([
                    'Ketua Penguji' => $item->ketua_penguji_id,
                    'Penguji 1'     => $item->anggota_penguji_1_id,
                    'Penguji 2'     => $item->anggota_penguji_2_id,
                ]);

                foreach ($newDosenRoles as $newRole => $newDosenId) {
                    foreach ($existingExaminerRoles as $existingRole => $existingDosenId) {
                        if ((int) $newDosenId === (int) $existingDosenId) {
                            $dosen     = Dosen::find($newDosenId);
                            $dosenNama = $dosen?->nama_dosen ?? 'Dosen';
                            $conflicts[] = "Bentrok Penguji '{$dosenNama}' (sebagai {$newRole}): Sedang bertugas sebagai {$existingRole} untuk ujian '{$item->nama_mahasiswa}' pada {$tglIndo} jam {$item->jam}.";
                        }
                    }
                }
            }
        }

        return array_unique($conflicts);
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
        $count       = count($sidangList);

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

        // ── Pass 2: Schedule overlaps (pairwise comparison) ───────────────────
        for ($i = 0; $i < $count; $i++) {
            $a = $sidangList[$i];
            if (!$a->tanggal || !$a->jam) continue;

            $aTanggal = $a->tanggal instanceof Carbon
                ? $a->tanggal->format('Y-m-d')
                : Carbon::parse($a->tanggal)->format('Y-m-d');

            $tglIndo = Carbon::parse($aTanggal)->locale('id')->isoFormat('D MMMM Y');

            for ($j = $i + 1; $j < $count; $j++) {
                $b = $sidangList[$j];
                if (!$b->tanggal || !$b->jam) continue;

                $bTanggal = $b->tanggal instanceof Carbon
                    ? $b->tanggal->format('Y-m-d')
                    : Carbon::parse($b->tanggal)->format('Y-m-d');

                if ($aTanggal !== $bTanggal)            continue;
                if (!self::isTimeOverlap($a->jam, $b->jam)) continue;

                // Room conflict
                if ($a->ruang_id && $a->ruang_id == $b->ruang_id) {
                    $ruangKode = $a->ruang?->kode_ruangan ?? 'Ruang';
                    $conflictMap[$a->id]['schedule'][] = "Bentrok Ruangan '{$ruangKode}': Bersamaan dengan ujian '{$b->nama_mahasiswa}' ({$tglIndo}, {$b->jam})";
                    $conflictMap[$b->id]['schedule'][] = "Bentrok Ruangan '{$ruangKode}': Bersamaan dengan ujian '{$a->nama_mahasiswa}' ({$tglIndo}, {$a->jam})";
                }

                // Examiner conflicts (only if neither is sempro)
                if ($a->jenis_tugas_akhir !== 'sempro' && $b->jenis_tugas_akhir !== 'sempro') {
                    $examinersA = array_filter([
                        'Ketua Penguji' => $a->ketua_penguji_id,
                        'Penguji 1'     => $a->anggota_penguji_1_id,
                        'Penguji 2'     => $a->anggota_penguji_2_id,
                    ]);

                    $examinersB = array_filter([
                        'Ketua Penguji' => $b->ketua_penguji_id,
                        'Penguji 1'     => $b->anggota_penguji_1_id,
                        'Penguji 2'     => $b->anggota_penguji_2_id,
                    ]);

                    foreach ($examinersA as $roleA => $dosenIdA) {
                        foreach ($examinersB as $roleB => $dosenIdB) {
                            if ((int) $dosenIdA === (int) $dosenIdB) {
                                $dosen     = Dosen::find($dosenIdA);
                                $dosenNama = $dosen?->nama_dosen ?? 'Dosen';
                                $conflictMap[$a->id]['schedule'][] = "Bentrok Penguji '{$dosenNama}' (sebagai {$roleA}): Sudah bertugas sebagai {$roleB} di ujian '{$b->nama_mahasiswa}' ({$tglIndo}, {$b->jam})";
                                $conflictMap[$b->id]['schedule'][] = "Bentrok Penguji '{$dosenNama}' (sebagai {$roleB}): Sudah bertugas sebagai {$roleA} di ujian '{$a->nama_mahasiswa}' ({$tglIndo}, {$a->jam})";
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

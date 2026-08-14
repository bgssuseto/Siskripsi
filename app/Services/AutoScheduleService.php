<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\KesediaanDosen;
use App\Models\Ruang;
use App\Models\Sidang;
use Carbon\Carbon;

class AutoScheduleService
{
    /**
     * Generate jadwal usulan untuk seluruh Sidang yang belum di-plot pada
     * periode + jenis (+ gelombang opsional) tertentu, berbasis irisan
     * kesediaan dosen yang terlibat dan slot ruang yang masih kosong.
     *
     * Return: ['proposals' => [...], 'unresolved' => [...]]
     */
    public static function generateProposals(int $periodeId, string $jenisBucket, ?int $gelombang, int $slotMinutes = 60): array
    {
        $jenisList = $jenisBucket === 'sempro' ? ['sempro'] : ['skripsi', 'jurnal', 'sidang'];

        $unscheduled = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'])
            ->where('periode_id', $periodeId)
            ->whereIn('jenis_tugas_akhir', $jenisList)
            ->where('verifikasi_status', 'disetujui')
            ->whereNull('tanggal')
            ->when($gelombang, fn ($q) => $q->where('gelombang', $gelombang))
            ->orderBy('tanggal_pendaftaran')
            ->orderBy('id')
            ->get();

        if ($unscheduled->isEmpty()) {
            return ['proposals' => [], 'unresolved' => []];
        }

        $ruangs = Ruang::orderBy('kode_ruangan')->get()
            ->filter(fn ($r) => trim($r->kode_ruangan) !== 'Langsung Pemberkasan')
            ->values();

        // ── Busy maps, seeded dari seluruh Sidang yang SUDAH terjadwal (sistem-wide) ──
        $busyDosen = [];   // [dosen_id][Y-m-d] => [[start,end], ...]
        $busyRuang = [];   // [ruang_id][Y-m-d] => [[start,end], ...]

        Sidang::whereNotNull('tanggal')->whereNotNull('jam')->get()->each(function ($s) use (&$busyDosen, &$busyRuang) {
            self::markBusyFromSidang($s, $busyDosen, $busyRuang);
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
            $kesediaanMap[$k->dosen_id][$tgl][] = [$start, $end];
        });

        $proposals = [];
        $unresolved = [];

        foreach ($unscheduled as $sidang) {
            $isSempro = $sidang->jenis_tugas_akhir === 'sempro';

            $requiredDosenIds = [];
            if ($sidang->dosen_pembimbing_utama_id) {
                $requiredDosenIds['Pembimbing Utama'] = $sidang->dosen_pembimbing_utama_id;
            } else {
                $unresolved[] = self::unresolvedEntry($sidang, 'Dosen Pembimbing Utama belum diisi.');
                continue;
            }

            if ($isSempro) {
                if ($sidang->dosen_pembimbing_pendamping_id) {
                    $requiredDosenIds['Pembimbing Pendamping'] = $sidang->dosen_pembimbing_pendamping_id;
                }
            } else {
                if (!$sidang->ketua_penguji_id || !$sidang->anggota_penguji_1_id) {
                    $unresolved[] = self::unresolvedEntry($sidang, 'Dewan Penguji (Ketua/Penguji 1) belum lengkap — lengkapi di Data Skripsi terlebih dahulu.');
                    continue;
                }
                $requiredDosenIds['Ketua Penguji'] = $sidang->ketua_penguji_id;
                $requiredDosenIds['Penguji 1'] = $sidang->anggota_penguji_1_id;
                // anggota_penguji_2_id sudah otomatis = pembimbing utama (Sidang::booted())
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
                // Irisan window kesediaan semua dosen pada tanggal ini, dikurangi slot yang sudah terisi
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
                    foreach ($ruangs as $ruang) {
                        $ruangBusy = $busyRuang[$ruang->id][$tgl] ?? [];
                        if (self::overlapsAny($slotStart, $slotEnd, $ruangBusy)) {
                            continue;
                        }

                        // Slot ditemukan — catat usulan & tandai busy untuk sisa proses
                        $jamStr = self::minutesToJamString($slotStart, $slotEnd);

                        $proposals[] = [
                            'sidang_id'   => $sidang->id,
                            'nama'        => $sidang->nama_mahasiswa,
                            'nim'         => $sidang->nim,
                            'jenis_label' => $sidang->jenis_label,
                            'tanggal'     => $tgl,
                            'jam'         => $jamStr,
                            'ruang_id'    => $ruang->id,
                            'ruang_nama'  => $ruang->kode_ruangan,
                            'dosen'       => $requiredDosenIds,
                        ];

                        foreach ($uniqueDosenIds as $dosenId) {
                            $busyDosen[$dosenId][$tgl][] = [$slotStart, $slotEnd];
                        }
                        $busyRuang[$ruang->id][$tgl][] = [$slotStart, $slotEnd];

                        $found = true;
                        break 3;
                    }
                }
            }

            if (!$found) {
                $unresolved[] = self::unresolvedEntry($sidang, 'Tidak ditemukan irisan waktu & ruang kosong dari kesediaan yang ada.');
            }
        }

        return ['proposals' => $proposals, 'unresolved' => $unresolved];
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

    private static function markBusyFromSidang(Sidang $s, array &$busyDosen, array &$busyRuang): void
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

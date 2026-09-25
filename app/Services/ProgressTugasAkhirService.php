<?php

namespace App\Services;

use App\Models\Sidang;
use Carbon\Carbon;

/**
 * Menyusun 5 tahap progress tugas akhir (Sempro / Skripsi) seorang mahasiswa
 * untuk dashboard:
 *
 *   1. Pendaftaran  2. Verifikasi Berkas  3. Join Grup WA
 *   4. Penjadwalan  5. Pelaksanaan Ujian
 *
 * Tiap tahap punya status independen (done / active / pending / failed) yang
 * diturunkan langsung dari data pendaftaran (Sidang) — bukan urutan linear kaku,
 * karena mis. koordinator bisa menjadwalkan sebelum mahasiswa sempat join grup WA.
 * Murni logika: tidak ada query selain lazy-load relasi periode/ruang.
 */
class ProgressTugasAkhirService
{
    public const TRACK_SEMPRO  = 'sempro';
    public const TRACK_SKRIPSI = 'skripsi';

    public const DONE    = 'done';
    public const ACTIVE  = 'active';
    public const PENDING = 'pending';
    public const FAILED  = 'failed';

    /**
     * @param  Sidang|null  $sidang  Pendaftaran terbaru mahasiswa pada track ini (null = belum mendaftar)
     * @param  string       $track   self::TRACK_SEMPRO | self::TRACK_SKRIPSI
     * @param  Carbon|null  $today   Hanya untuk test; default hari ini (Asia/Jakarta)
     *
     * @return array{
     *   track: string, registered: bool, sidang: ?Sidang, jenis_label: ?string,
     *   steps: array<int, array<string, mixed>>, done_count: int, total: int,
     *   percent: int, current_index: int, failed: bool, complete: bool
     * }
     */
    public static function build(?Sidang $sidang, string $track, ?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::now('Asia/Jakarta'))->copy()->startOfDay();

        $registered = $sidang !== null;
        $status     = $registered ? ($sidang->verifikasi_status ?: 'menunggu') : null;
        $verified   = $status === 'disetujui';

        // Jadwal hanya dianggap "ada" setelah berkas diterima dan plotting lengkap.
        $scheduled = $verified && $sidang->isPlotted();

        $daysUntilExam = null;
        if ($scheduled) {
            $examDate      = Carbon::parse($sidang->tanggal->format('Y-m-d'), 'Asia/Jakarta')->startOfDay();
            $daysUntilExam = (int) round(($examDate->getTimestamp() - $today->getTimestamp()) / 86400);
        }
        $examPassed = $daysUntilExam !== null && $daysUntilExam < 0;

        $steps = [
            self::stepPendaftaran($sidang, $registered),
            self::stepVerifikasi($sidang, $registered, $status),
            self::stepJoinWa($sidang, $track, $verified, $examPassed),
            self::stepPenjadwalan($verified, $scheduled),
            self::stepUjian($sidang, $scheduled, $daysUntilExam),
        ];

        $doneCount = count(array_filter($steps, fn ($s) => $s['status'] === self::DONE));
        $complete  = $steps[4]['status'] === self::DONE;
        $failed    = (bool) array_filter($steps, fn ($s) => $s['status'] === self::FAILED);

        // "Tahap saat ini" = tahap aktif/gagal pertama; kalau tidak ada (mis. semua selesai,
        // atau ujian lewat tanpa join WA), tahap terakhir yang sudah selesai.
        $currentIndex = 0;
        foreach ($steps as $i => $step) {
            if (in_array($step['status'], [self::ACTIVE, self::FAILED], true)) {
                $currentIndex = $i;
                break;
            }
            if ($step['status'] === self::DONE) {
                $currentIndex = $i;
            }
        }

        return [
            'track'         => $track,
            'registered'    => $registered,
            'sidang'        => $sidang,
            'jenis_label'   => $sidang?->jenis_label,
            'steps'         => $steps,
            'done_count'    => $doneCount,
            'total'         => count($steps),
            // Ujian sudah dilaksanakan = perjalanan selesai, walau ada tahap lain (mis. join WA) yang terlewat.
            'percent'       => $complete ? 100 : (int) round($doneCount / count($steps) * 100),
            'current_index' => $currentIndex,
            'failed'        => $failed,
            'complete'      => $complete,
        ];
    }

    private static function step(string $key, string $label, string $status, string $detail, array $extra = []): array
    {
        return array_merge([
            'key'        => $key,
            'label'      => $label,
            'status'     => $status,
            'detail'     => $detail,
            'note'       => null,
            'badge'      => null,
            'badge_tone' => null,   // success | danger | warning | info | neutral
            'cta'        => null,   // daftar | revisi | join_wa
        ], $extra);
    }

    private static function stepPendaftaran(?Sidang $sidang, bool $registered): array
    {
        if (!$registered) {
            return self::step('pendaftaran', 'Pendaftaran', self::ACTIVE, 'Belum mendaftar', ['cta' => 'daftar']);
        }

        $detail = $sidang->tanggal_pendaftaran
            ? 'Terdaftar ' . $sidang->tanggal_pendaftaran->copy()->locale('id')->translatedFormat('d M Y')
            : 'Terdaftar';

        return self::step('pendaftaran', 'Pendaftaran', self::DONE, $detail);
    }

    private static function stepVerifikasi(?Sidang $sidang, bool $registered, ?string $status): array
    {
        if (!$registered) {
            return self::step('verifikasi', 'Verifikasi Berkas', self::PENDING, 'Menunggu pendaftaran');
        }

        return match ($status) {
            'disetujui' => self::step('verifikasi', 'Verifikasi Berkas', self::DONE, 'Berkas diterima', [
                'badge' => 'Diterima', 'badge_tone' => 'success',
            ]),
            'ditolak' => self::step('verifikasi', 'Verifikasi Berkas', self::FAILED, 'Berkas ditolak', [
                'note'       => $sidang->verifikasi_komentar ? 'Catatan: ' . $sidang->verifikasi_komentar : 'Silakan revisi dan kirim ulang.',
                'badge'      => 'Ditolak', 'badge_tone' => 'danger',
                'cta'        => 'revisi',
            ]),
            default => self::step('verifikasi', 'Verifikasi Berkas', self::ACTIVE, 'Menunggu verifikasi koordinator'),
        };
    }

    private static function stepJoinWa(?Sidang $sidang, string $track, bool $verified, bool $examPassed): array
    {
        $label = 'Join Grup WA';

        if (!$verified) {
            return self::step('join_wa', $label, self::PENDING, 'Menunggu berkas diterima');
        }

        if ($sidang->wa_joined_at) {
            return self::step('join_wa', $label, self::DONE,
                'Bergabung ' . $sidang->wa_joined_at->copy()->locale('id')->translatedFormat('d M Y'));
        }

        if ($examPassed) {
            return self::step('join_wa', $label, self::PENDING, 'Tidak tercatat bergabung');
        }

        $periode = $sidang->periode;
        $link    = $track === self::TRACK_SEMPRO ? $periode?->link_grup_wa_sempro : $periode?->link_grup_wa_skripsi;

        if (!$link) {
            return self::step('join_wa', $label, self::PENDING, 'Link grup belum tersedia', [
                'note' => 'Koordinator belum mengatur link grup WhatsApp.',
            ]);
        }

        return self::step('join_wa', $label, self::ACTIVE, 'Klik tombol untuk bergabung', ['cta' => 'join_wa']);
    }

    private static function stepPenjadwalan(bool $verified, bool $scheduled): array
    {
        $label = 'Penjadwalan';

        if (!$verified) {
            return self::step('penjadwalan', $label, self::PENDING, 'Menunggu berkas diterima');
        }

        if (!$scheduled) {
            return self::step('penjadwalan', $label, self::ACTIVE, 'Sedang dijadwalkan', [
                'note' => 'Koordinator sedang menyusun jadwal ujian Anda.',
            ]);
        }

        return self::step('penjadwalan', $label, self::DONE, 'Sudah terjadwal', [
            'badge' => 'Terjadwal', 'badge_tone' => 'success',
        ]);
    }

    private static function stepUjian(?Sidang $sidang, bool $scheduled, ?int $daysUntilExam): array
    {
        $label = 'Pelaksanaan Ujian';

        if (!$scheduled) {
            return self::step('ujian', $label, self::PENDING, 'Menunggu jadwal');
        }

        $tanggal = $sidang->tanggal->copy()->locale('id')->translatedFormat('l, d M Y');
        $meta    = array_filter([
            $sidang->jam ? 'Pukul ' . $sidang->jam : null,
            $sidang->ruang?->kode_ruangan ? 'Ruang ' . $sidang->ruang->kode_ruangan : null,
        ]);
        $note = $meta ? implode(' • ', $meta) : null;

        if ($daysUntilExam > 0) {
            return self::step('ujian', $label, self::ACTIVE, $tanggal, [
                'note'  => $note,
                'badge' => $daysUntilExam === 1 ? 'Besok' : 'H-' . $daysUntilExam,
                'badge_tone' => 'info',
            ]);
        }

        if ($daysUntilExam === 0) {
            return self::step('ujian', $label, self::ACTIVE, $tanggal, [
                'note'  => $note,
                'badge' => 'Hari ini', 'badge_tone' => 'warning',
            ]);
        }

        [$badge, $tone] = match ($sidang->status_ujian) {
            'lulus'       => ['Lulus', 'success'],
            'tidak_lulus' => ['Tidak lulus', 'danger'],
            default       => ['Selesai', 'neutral'],
        };

        return self::step('ujian', $label, self::DONE, $tanggal, [
            'note'  => $note,
            'badge' => $badge, 'badge_tone' => $tone,
        ]);
    }
}

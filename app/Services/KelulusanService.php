<?php

namespace App\Services;

use App\Models\Sidang;
use App\Models\User;

class KelulusanService
{
    /**
     * Re-check a student's graduation status by NIM and sync it onto their
     * User account. A student is "lulus" once they have at least one 'lulus'
     * result on a sempro record AND at least one 'lulus' result on a
     * skripsi-track record. Called after every hasil-ujian update.
     */
    public static function syncStatus(?string $nim): void
    {
        if (empty($nim)) {
            return;
        }

        $lulusSempro = Sidang::where('nim', $nim)
            ->where('jenis_tugas_akhir', 'sempro')
            ->where('status_ujian', 'lulus')
            ->exists();

        $lulusSkripsi = Sidang::where('nim', $nim)
            ->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)
            ->where('status_ujian', 'lulus')
            ->exists();

        $isLulus = $lulusSempro && $lulusSkripsi;

        $user = User::where('nim', $nim)->first();
        if (!$user) {
            return;
        }

        $newStatus = $isLulus ? 'lulus' : null;
        if ($user->status_kelulusan !== $newStatus) {
            $user->status_kelulusan = $newStatus;
            $user->save();
        }
    }

    /**
     * Whether a student (by NIM) has a 'tidak_lulus' (remidi) result for the
     * given jenis bucket ('sempro' or skripsi-track) with no registration yet
     * in the given active periode — meaning they need to be re-registered by
     * an admin/koordinator rather than self-registering.
     */
    public static function needsCoordinatorForRemidi(string $nim, array $jenisBucket, ?int $activePeriodeId): bool
    {
        if (!$activePeriodeId) {
            return false;
        }

        $hasRemidi = Sidang::where('nim', $nim)
            ->whereIn('jenis_tugas_akhir', $jenisBucket)
            ->where('status_ujian', 'tidak_lulus')
            ->exists();

        if (!$hasRemidi) {
            return false;
        }

        $hasCurrentPeriodeRecord = Sidang::where('nim', $nim)
            ->whereIn('jenis_tugas_akhir', $jenisBucket)
            ->where('periode_id', $activePeriodeId)
            ->exists();

        return !$hasCurrentPeriodeRecord;
    }
}

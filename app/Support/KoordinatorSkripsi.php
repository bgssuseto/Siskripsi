<?php

namespace App\Support;

use App\Models\Dosen;
use App\Models\User;

/**
 * Koordinator Skripsi penandatangan undangan: dosen yang akunnya memegang role
 * koordinator — baik sebagai role utama maupun role tambahan.
 */
class KoordinatorSkripsi
{
    public static function dosen(): ?Dosen
    {
        $user = User::query()
            ->whereNotNull('dosen_id')
            ->where(function ($q) {
                $q->where('role', User::ROLE_KOORDINATOR)
                  ->orWhereHas('additionalRoles', fn ($r) => $r->where('role', User::ROLE_KOORDINATOR));
            })
            ->whereHas('dosen')
            // Role utama koordinator didahulukan, lalu akun terlama.
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [User::ROLE_KOORDINATOR])
            ->orderBy('id')
            ->with('dosen')
            ->first();

        return $user?->dosen;
    }
}

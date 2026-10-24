<?php

namespace App\Support;

use App\Models\Sidang;

/**
 * Daftar peran seorang dosen pada satu sidang, dengan istilah yang benar:
 * Sempro tidak punya Ketua Penguji — dewan pengujinya adalah pembimbing
 * mahasiswa itu sendiri (Anggota Penguji 1 & 2), jadi cukup ditampilkan
 * sebagai Pembimbing Utama/Pendamping.
 */
class PeranDosen
{
    /** @return list<string> */
    public static function untuk(Sidang $s, int $dosenId): array
    {
        $peran = [];
        if ((int) $s->dosen_pembimbing_utama_id === $dosenId) $peran[] = 'Pembimbing Utama';
        if ((int) $s->dosen_pembimbing_pendamping_id === $dosenId) $peran[] = 'Pembimbing Pendamping';

        if ($s->jenis_tugas_akhir === 'sempro') {
            // Penguji sempro = pembimbing; tampilkan peran pengujinya hanya bila bukan pembimbing.
            if (!$peran && ($p = $s->peranPengujiDosen($dosenId))) $peran[] = $p;
            return $peran;
        }

        if ((int) $s->ketua_penguji_id === $dosenId) $peran[] = 'Ketua Penguji';
        if ((int) $s->anggota_penguji_1_id === $dosenId) $peran[] = 'Anggota Penguji 1';
        if ((int) $s->anggota_penguji_2_id === $dosenId) $peran[] = 'Anggota Penguji 2';

        return $peran;
    }
}

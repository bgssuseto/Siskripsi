<?php

namespace App\Support;

use Carbon\Carbon;

class TanggalIndo
{
    /**
     * Format tanggal lengkap bahasa Indonesia untuk export Excel / mail merge,
     * mis. "Jum'at, 10 Juni 2026". Mengembalikan $kosong jika tanggal null.
     */
    public static function lengkap($tanggal, string $kosong = '-'): string
    {
        if (empty($tanggal)) {
            return $kosong;
        }

        $date = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal);

        return str_replace('Jumat', "Jum'at", $date->locale('id')->translatedFormat('l, j F Y'));
    }
}

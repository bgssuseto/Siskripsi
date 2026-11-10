<?php

namespace App\Support;

/**
 * Membersihkan teks hasil salin dari WhatsApp, kontak HP, Excel atau Word.
 * Teks seperti itu sering membawa karakter tak terlihat: tanda arah teks
 * (U+202A/U+202C), zero-width, spasi NBSP, serta tanda kutip/strip "keriting".
 * Karakter ini mengotori data, dan kalau ikut terkirim di form, bisa membuat
 * kiriman ditolak firewall server.
 */
class TeksBersih
{
    public static function teks(?string $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{2069}\x{FEFF}\x{00AD}]/u', '', $nilai) ?? $nilai;
        $nilai = preg_replace('/[\x{00A0}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]/u', ' ', $nilai) ?? $nilai;
        $nilai = preg_replace('/[\x{2010}-\x{2015}\x{2212}]/u', '-', $nilai) ?? $nilai;
        $nilai = preg_replace('/[\x{2018}\x{2019}]/u', "'", $nilai) ?? $nilai;
        $nilai = preg_replace('/[\x{201C}\x{201D}]/u', '"', $nilai) ?? $nilai;
        $nilai = trim(preg_replace('/\s+/u', ' ', $nilai) ?? $nilai);

        return $nilai === '' ? null : $nilai;
    }

    /** Nomor HP/WA: hanya angka, dengan "+" di depan bila ada. */
    public static function nomorHp(?string $nilai): ?string
    {
        $nilai = self::teks($nilai);
        if ($nilai === null) {
            return null;
        }

        $angka = preg_replace('/\D/', '', $nilai);
        if ($angka === '') {
            return null;
        }

        return (str_starts_with($nilai, '+') ? '+' : '') . $angka;
    }
}

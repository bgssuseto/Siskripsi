<?php

namespace App\Support;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Kirim email lewat antrian bila penjadwal server aktif (halaman tidak menunggu
 * SMTP, gagal dicoba ulang otomatis); bila penjadwal belum aktif, kirim langsung
 * supaya email tidak tertahan di antrian yang tidak pernah diproses.
 */
class KirimEmail
{
    /** @return 'antri'|'terkirim' */
    public static function ke(string $alamat, Mailable $email): string
    {
        if (config('queue.default') !== 'sync' && StatusSistem::penjadwalAktif()) {
            Mail::to($alamat)->queue($email);
            return 'antri';
        }

        Mail::to($alamat)->send($email);
        return 'terkirim';
    }
}

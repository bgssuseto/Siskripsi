<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Lokasi berkas pribadi mahasiswa (persyaratan & bukti pembayaran).
 *
 * Berkas baru disimpan di storage/app/private/berkas/... — di luar folder public,
 * jadi tidak bisa dibuka lewat URL apa pun (tidak bergantung .htaccess/jenis web
 * server). Satu-satunya jalan membukanya adalah route /dokumen yang memeriksa hak
 * akses. Nilai di database: "berkas/<folder>/<nama>.pdf".
 *
 * Berkas lama masih tercatat "uploads/<folder>/<nama>.pdf" (di public/) dan tetap
 * terbaca; perintah `php artisan berkas:pindah-privat` memindahkannya.
 */
class BerkasMahasiswa
{
    public const AWALAN_PRIVAT = 'berkas/';
    public const AWALAN_LAMA = 'uploads/';

    /** Simpan unggahan dengan nama acak; kembalikan path untuk database. */
    public static function simpan(UploadedFile $file, string $folder, string $awalanNama): string
    {
        $relatif = self::AWALAN_PRIVAT . $folder . '/' . $awalanNama . '_' . Str::random(40) . '.pdf';
        $tujuan = self::lokasi($relatif);
        if (!is_dir(dirname($tujuan))) {
            @mkdir(dirname($tujuan), 0775, true);
        }
        $file->move(dirname($tujuan), basename($tujuan));

        return $relatif;
    }

    /** Path absolut di disk dari nilai database (format baru maupun lama). */
    public static function lokasi(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        $path = ltrim(str_replace('\\', '/', $path), '/');
        // Tolak path yang mencoba keluar folder (mis. "../../.env").
        if (str_contains($path, '..')) {
            return null;
        }

        return str_starts_with($path, self::AWALAN_PRIVAT)
            ? storage_path('app/private/' . $path)
            : public_path($path);
    }

    public static function ada(?string $path): bool
    {
        $lokasi = self::lokasi($path);
        return $lokasi !== null && is_file($lokasi);
    }

    public static function hapus(?string $path): void
    {
        $lokasi = self::lokasi($path);
        if ($lokasi && is_file($lokasi)) {
            @unlink($lokasi);
        }
    }

    /**
     * Pindahkan satu berkas lama (public/uploads/...) ke penyimpanan privat.
     * Mengembalikan path baru, atau null bila bukan berkas lama / tidak ada di disk.
     */
    public static function pindahkanKePrivat(?string $path): ?string
    {
        if (!$path || !str_starts_with(ltrim($path, '/'), self::AWALAN_LAMA)) {
            return null;
        }
        $asal = self::lokasi($path);
        if (!$asal || !is_file($asal)) {
            return null;
        }

        // uploads/persyaratan/x.pdf → berkas/persyaratan/<acak>.pdf (nama lama bisa ditebak).
        $folder = basename(dirname($asal));
        $baru = self::AWALAN_PRIVAT . $folder . '/' . pathinfo($asal, PATHINFO_FILENAME) . '_' . Str::random(16) . '.' . (pathinfo($asal, PATHINFO_EXTENSION) ?: 'pdf');
        $tujuan = self::lokasi($baru);
        if (!is_dir(dirname($tujuan))) {
            @mkdir(dirname($tujuan), 0775, true);
        }
        if (!@rename($asal, $tujuan)) {
            if (!@copy($asal, $tujuan)) {
                return null;
            }
            @unlink($asal);
        }

        return $baru;
    }
}

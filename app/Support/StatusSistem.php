<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Kesehatan sistem untuk Super Admin (tanpa perlu akses server):
 * - penjadwal (cron `schedule:run`) benar-benar berjalan,
 * - backup database otomatis tidak terlalu lama,
 * - APP_DEBUG tidak menyala di server publik.
 */
class StatusSistem
{
    public const KUNCI_DETAK = 'sistem:penjadwal-detak';
    public const DIR_BACKUP = 'backups';

    /** Dipanggil penjadwal tiap menit. */
    public static function detak(): void
    {
        Cache::forever(self::KUNCI_DETAK, now()->timestamp);
    }

    public static function detakTerakhir(): ?Carbon
    {
        $ts = Cache::get(self::KUNCI_DETAK);
        return $ts ? Carbon::createFromTimestamp($ts) : null;
    }

    /** Penjadwal dianggap aktif bila berdetak dalam 10 menit terakhir. */
    public static function penjadwalAktif(): bool
    {
        $t = self::detakTerakhir();
        return $t !== null && $t->gt(now()->subMinutes(10));
    }

    public static function backupTerakhir(): ?Carbon
    {
        $disk = Storage::disk('local');
        $terbaru = collect($disk->exists(self::DIR_BACKUP) ? $disk->files(self::DIR_BACKUP) : [])
            ->filter(fn ($p) => str_ends_with($p, '.sql'))
            ->map(fn ($p) => $disk->lastModified($p))
            ->max();

        return $terbaru ? Carbon::createFromTimestamp($terbaru) : null;
    }

    /** Debug menyala di luar komputer pengembang = detail error terlihat pengunjung. */
    public static function debugBerbahaya(): bool
    {
        if (!config('app.debug')) {
            return false;
        }
        $host = request()?->getHost() ?? '';
        return !app()->environment('local', 'testing')
            || !in_array($host, ['localhost', '127.0.0.1', '::1'], true) && !str_ends_with($host, '.test');
    }

    /**
     * Daftar peringatan yang perlu ditindaklanjuti: [judul, keterangan, tingkat(rose|amber)].
     *
     * @return array<int, array{0:string,1:string,2:string}>
     */
    public static function peringatan(): array
    {
        $daftar = [];

        if (self::debugBerbahaya()) {
            $daftar[] = ['Mode debug menyala', 'Ubah APP_DEBUG=false di file .env server, lalu jalankan php artisan config:clear. Saat debug menyala, detail error (termasuk konfigurasi) bisa terlihat pengunjung.', 'rose'];
        }

        if (!self::penjadwalAktif()) {
            $t = self::detakTerakhir();
            $daftar[] = ['Penjadwal otomatis tidak berjalan', ($t ? 'Terakhir berjalan ' . $t->locale('id')->diffForHumans() . '. ' : 'Belum pernah berjalan. ')
                . 'Backup harian, pengiriman email, pengingat ujian, dan pemindahan berkas tidak berjalan. Tambahkan Cron Job tiap menit di hPanel: php ' . base_path('artisan') . ' schedule:run', 'amber'];
        }

        try {
            $gagal = \Illuminate\Support\Facades\DB::table('failed_jobs')->where('failed_at', '>=', now()->subDays(7))->count();
        } catch (\Throwable) {
            $gagal = 0;
        }
        if ($gagal > 0) {
            $daftar[] = ["{$gagal} email gagal terkirim (7 hari terakhir)", 'Server email menolak atau tidak merespons setelah 3 kali percobaan. Periksa pengaturan MAIL_* di .env server, lalu kirim ulang undangan yang bersangkutan.', 'amber'];
        }

        $backup = self::backupTerakhir();
        if (!$backup || $backup->lt(now()->subDays(2))) {
            $daftar[] = ['Backup database sudah lama', $backup
                ? 'Backup terakhir ' . $backup->locale('id')->diffForHumans() . '. Buat backup manual di menu Backup bila penjadwal belum aktif.'
                : 'Belum ada backup sama sekali. Buat backup manual di menu Backup.', 'amber'];
        }

        return $daftar;
    }
}

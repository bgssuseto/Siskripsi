<?php

use App\Services\KelulusanService;
use App\Services\NotifikasiService;
use App\Support\StatusSistem;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tugas terjadwal
|--------------------------------------------------------------------------
| Butuh satu Cron Job tiap menit di server (hPanel → Cron Jobs):
|     php /path/ke/aplikasi/artisan schedule:run
|
| Semua tugas memakai Schedule::call() + Artisan::call() (berjalan di proses PHP
| yang sama), BUKAN Schedule::command() — hosting seperti Hostinger mematikan
| proc_open, sehingga Schedule::command() (yang membuka proses baru) gagal diam-diam.
*/

// Semua tugas di bawah hanya didaftarkan bila PENJADWAL_AKTIF=true di .env
// (lihat config/app.php → penjadwal_aktif).
if (config('app.penjadwal_aktif')) {
    // Detak: tanda penjadwal hidup (ditampilkan di dashboard Super Admin).
    Schedule::call(fn () => StatusSistem::detak())
        ->name('detak-penjadwal')->everyMinute();

    // Proses antrian (email undangan) — berhenti sendiri bila antrian kosong.
    Schedule::call(fn () => Artisan::call('queue:work', ['--stop-when-empty' => true, '--max-time' => 50, '--tries' => 3]))
        ->name('proses-antrian')->everyMinute()->withoutOverlapping(5);

    // Email pemberitahuan verifikasi & jadwal yang sudah "tenang" 15 menit.
    Schedule::call(fn () => NotifikasiService::prosesTertunda())
        ->name('notifikasi-tertunda')->everyFiveMinutes()->withoutOverlapping(10);

    // Pengingat H-1 ke mahasiswa & dosen.
    Schedule::call(fn () => NotifikasiService::kirimPengingatBesok())
        ->name('pengingat-h1')->dailyAt('07:00')->withoutOverlapping(30);

    // Hasil ujian yang sudah lewat & belum diisi → Lulus (juga dicek saat halaman jadwal dibuka).
    Schedule::call(fn () => KelulusanService::autoFinalizePastExams())
        ->name('finalisasi-kelulusan')->everyThirtyMinutes()->withoutOverlapping(10);

    // Backup database harian (14 backup otomatis terakhir disimpan).
    Schedule::call(fn () => Artisan::call('backup:harian'))
        ->name('backup-harian')->dailyAt('01:30')->withoutOverlapping(60);

    // Pindahkan berkas pribadi mahasiswa lama ke penyimpanan privat.
    Schedule::call(fn () => Artisan::call('berkas:pindah-privat'))
        ->name('berkas-pindah-privat')->dailyAt('02:00')->withoutOverlapping(60);

    // Hapus berkas persyaratan > 1 tahun (data pendaftarannya tetap utuh).
    Schedule::call(fn () => Artisan::call('app:cleanup-old-persyaratan-files'))
        ->name('bersihkan-berkas-lama')->dailyAt('02:30')->withoutOverlapping(60);
}

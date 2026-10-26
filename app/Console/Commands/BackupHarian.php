<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use App\Support\StatusSistem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Backup database otomatis (dijadwalkan harian). Memakai DatabaseBackupService
 * yang murni PHP — aman di hosting yang mematikan proc_open/mysqldump.
 * Hanya backup otomatis (backup_otomatis_*.sql) yang dirotasi; backup manual tidak disentuh.
 */
class BackupHarian extends Command
{
    protected $signature = 'backup:harian {--simpan=14 : Jumlah backup otomatis terbaru yang disimpan}';

    protected $description = 'Buat backup database otomatis dan hapus backup otomatis lama';

    public function handle(DatabaseBackupService $backup): int
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory(StatusSistem::DIR_BACKUP);

        $nama = 'backup_otomatis_' . now()->format('Ymd_His') . '.sql';
        $path = $disk->path(StatusSistem::DIR_BACKUP . '/' . $nama);
        @set_time_limit(300);

        try {
            $backup->dumpToFile($path);
        } catch (\Throwable $e) {
            @unlink($path);
            report($e);
            $this->error('Backup gagal: ' . $e->getMessage());
            return self::FAILURE;
        }

        $simpan = max(1, (int) $this->option('simpan'));
        collect($disk->files(StatusSistem::DIR_BACKUP))
            ->filter(fn ($p) => str_starts_with(basename($p), 'backup_otomatis_') && str_ends_with($p, '.sql'))
            ->sortDesc()
            ->slice($simpan)
            ->each(fn ($p) => $disk->delete($p));

        $this->info("Backup dibuat: {$nama}");
        return self::SUCCESS;
    }
}

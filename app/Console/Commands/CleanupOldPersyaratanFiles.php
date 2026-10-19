<?php

namespace App\Console\Commands;

use App\Models\Sidang;
use Illuminate\Console\Command;

class CleanupOldPersyaratanFiles extends Command
{
    /**
     * Every year's worth of uploaded PDF persyaratan files adds up on the
     * server's disk. Once a registration is a year old the file has long
     * served its purpose (verification already happened), so only the
     * physical .pdf is removed here — the Sidang record itself, and every
     * other field on it, is left completely untouched.
     */
    protected $signature = 'app:cleanup-old-persyaratan-files';

    protected $description = 'Delete uploaded persyaratan PDF files older than 1 year (keeps the student data record intact)';

    public function handle(): int
    {
        $cutoff = now()->subYear();

        $sidangs = Sidang::whereNotNull('file_persyaratan')
            ->where('created_at', '<', $cutoff)
            ->get();

        $deleted = 0;

        foreach ($sidangs as $sidang) {
            $path = public_path($sidang->file_persyaratan);

            if (file_exists($path)) {
                @unlink($path);
            }

            $sidang->update(['file_persyaratan' => null]);
            $deleted++;
        }

        $this->info("Selesai: {$deleted} file persyaratan lebih dari 1 tahun berhasil dihapus (data mahasiswa tetap utuh).");

        return self::SUCCESS;
    }
}

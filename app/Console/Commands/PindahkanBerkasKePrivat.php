<?php

namespace App\Console\Commands;

use App\Models\BebasProdi;
use App\Models\Sidang;
use App\Support\BerkasMahasiswa;
use Illuminate\Console\Command;

/**
 * Memindahkan berkas pribadi mahasiswa lama dari public/uploads/... ke
 * storage/app/private/berkas/... (tidak bisa dibuka lewat URL) dan memperbarui
 * path-nya di database. Aman dijalankan berulang — berkas yang sudah privat dilewati.
 * Ikut dijadwalkan harian (routes/console.php).
 */
class PindahkanBerkasKePrivat extends Command
{
    protected $signature = 'berkas:pindah-privat';

    protected $description = 'Pindahkan berkas persyaratan & bukti pembayaran mahasiswa lama ke penyimpanan privat';

    public function handle(): int
    {
        $dipindah = 0;
        $hilang = 0;

        Sidang::query()
            ->where(fn ($q) => $q->where('file_persyaratan', 'like', BerkasMahasiswa::AWALAN_LAMA . '%')
                ->orWhere('bukti_pembayaran', 'like', BerkasMahasiswa::AWALAN_LAMA . '%'))
            ->chunkById(100, function ($sidangs) use (&$dipindah, &$hilang) {
                foreach ($sidangs as $sidang) {
                    $ubah = [];
                    foreach (['file_persyaratan', 'bukti_pembayaran'] as $kolom) {
                        $lama = $sidang->{$kolom};
                        if (!$lama || !str_starts_with($lama, BerkasMahasiswa::AWALAN_LAMA)) {
                            continue;
                        }
                        $baru = BerkasMahasiswa::pindahkanKePrivat($lama);
                        if ($baru) {
                            $ubah[$kolom] = $baru;
                            $dipindah++;
                        } elseif (!BerkasMahasiswa::ada($lama)) {
                            $hilang++;
                        }
                    }
                    if ($ubah) {
                        // Tanpa memicu event model (hanya path berkas yang berubah).
                        Sidang::whereKey($sidang->id)->update($ubah);
                    }
                }
            });

        $this->info("Selesai: {$dipindah} berkas dipindah ke penyimpanan privat."
            . ($hilang ? " {$hilang} berkas tercatat tapi tidak ditemukan di disk." : ''));

        return self::SUCCESS;
    }
}

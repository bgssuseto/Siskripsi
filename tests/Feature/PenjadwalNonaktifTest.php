<?php

namespace Tests\Feature;

use App\Services\NotifikasiService;
use App\Support\StatusSistem;
use Tests\TestCase;

class PenjadwalNonaktifTest extends TestCase
{
    public function test_penjadwal_nonaktif_tidak_memunculkan_peringatan_dan_notifikasi_tidak_ditampung(): void
    {
        config(['app.penjadwal_aktif' => false]);

        $judul = array_column(StatusSistem::peringatan(), 0);
        $this->assertNotContains('Penjadwal otomatis tidak berjalan', $judul);
        $this->assertFalse(StatusSistem::penjadwalAktif());
        $this->assertFalse(NotifikasiService::aktif());
    }

    public function test_penjadwal_dinyalakan_tanpa_cron_tetap_diperingatkan(): void
    {
        config(['app.penjadwal_aktif' => true]);
        \Illuminate\Support\Facades\Cache::forget(StatusSistem::KUNCI_DETAK);

        $this->assertContains('Penjadwal otomatis tidak berjalan', array_column(StatusSistem::peringatan(), 0));
    }
}

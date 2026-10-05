<?php

namespace Tests\Unit;

use App\Models\PendaftaranPeriode;
use Tests\TestCase;

class PendaftaranPeriodeTest extends TestCase
{
    public function test_gelombang_tepat_1_bulan_tidak_dianggap_terlalu_panjang(): void
    {
        $wave = new PendaftaranPeriode(['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-01-31']);
        $this->assertFalse($wave->isTooLongForKesediaanPicker());
    }

    public function test_gelombang_lebih_dari_1_bulan_dianggap_terlalu_panjang(): void
    {
        $wave = new PendaftaranPeriode(['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-03-15']);
        $this->assertTrue($wave->isTooLongForKesediaanPicker());
    }

    public function test_gelombang_tanpa_tanggal_tidak_dianggap_terlalu_panjang(): void
    {
        $wave = new PendaftaranPeriode(['tanggal_mulai' => null, 'tanggal_selesai' => null]);
        $this->assertFalse($wave->isTooLongForKesediaanPicker());
    }
}

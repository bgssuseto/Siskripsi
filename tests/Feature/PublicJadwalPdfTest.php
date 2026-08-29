<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use App\Models\Sidang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicJadwalPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_dosen_jadwal_page_returns_successful_response(): void
    {
        $dosen = Dosen::create([
            'nama_dosen' => 'Test Dosen PDF',
            'nidn' => '1234567890',
        ]);

        $periode = Periode::create([
            'nama_periode' => 'Semester Genap 2025/2026',
            'aktif' => true,
        ]);

        $wave = PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'jenis' => 'skripsi',
            'gelombang' => 1,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-01-31',
        ]);

        $response = $this->get(route('public.dosen.jadwal', [
            'token' => $dosen->public_token,
            'wave_id' => $wave->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Test Dosen PDF');
        $response->assertSee('Gelombang 1');
        $response->assertSee('Download PDF');
    }

    public function test_public_dosen_jadwal_pdf_download(): void
    {
        $dosen = Dosen::create([
            'nama_dosen' => 'Test Dosen PDF Download',
            'nidn' => '9876543210',
        ]);

        $periode = Periode::create([
            'nama_periode' => 'Semester Genap 2025/2026',
            'aktif' => true,
        ]);

        $wave = PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'jenis' => 'skripsi',
            'gelombang' => 2,
            'tanggal_mulai' => '2026-02-01',
            'tanggal_selesai' => '2026-02-28',
        ]);

        $response = $this->get(route('public.dosen.jadwal.pdf', [
            'token' => $dosen->public_token,
            'wave_id' => $wave->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_public_jadwal_dosen_penguji_page_and_pdf(): void
    {
        $dosen = Dosen::create([
            'nama_dosen' => 'Test Examiner Search',
            'nidn' => '1122334455',
        ]);

        $periode = Periode::create([
            'nama_periode' => 'Semester Genap 2025/2026',
            'aktif' => true,
        ]);

        $wave = PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-01-31',
        ]);

        $response = $this->get(route('public.jadwal-dosen-penguji', [
            'dosen_id' => $dosen->id,
            'wave_id' => $wave->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Test Examiner Search');
        $response->assertSee('Download PDF');

        $pdfResponse = $this->get(route('public.jadwal-dosen-penguji.pdf', [
            'dosen_id' => $dosen->id,
            'wave_id' => $wave->id,
        ]));

        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }
}

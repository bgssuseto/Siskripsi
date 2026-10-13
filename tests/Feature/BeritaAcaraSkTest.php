<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BeritaAcaraSkTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Periode $periode;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $this->admin = User::create([
            'name' => 'Admin BA', 'email' => 'admin-ba-' . uniqid() . '@test.id',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $this->periode = Periode::create(['nama_periode' => 'Semester Gasal Uji ' . uniqid(), 'aktif' => false]);
        PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 1,
            'tanggal_mulai' => '2026-09-01', 'tanggal_selesai' => '2026-09-30',
        ]);
        PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 2,
            'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-31',
        ]);

        $utama = Dosen::create(['nama_dosen' => 'Dosen Utama BA', 'nidn' => 'BAU' . uniqid()]);
        $pend  = Dosen::create(['nama_dosen' => 'Dosen Pendamping BA', 'nidn' => 'BAP' . uniqid()]);

        foreach (['2026-09-05', '2026-09-06', '2026-10-05'] as $i => $tgl) {
            Sidang::create([
                'nim' => "BANIM{$i}" . substr(uniqid(), -6), 'nama_mahasiswa' => "Mhs BA {$i}", 'judul_skripsi' => 'Judul',
                'jenis_tugas_akhir' => 'sempro', 'verifikasi_status' => 'disetujui',
                'periode_id' => $this->periode->id, 'tanggal_pendaftaran' => $tgl,
                'dosen_pembimbing_utama_id' => $utama->id, 'dosen_pembimbing_pendamping_id' => $pend->id,
                'tanggal' => '2026-10-16', 'jam' => sprintf('%02d.00 - %02d.30', 8 + $i, 8 + $i),
            ]);
        }
    }

    public function test_berita_acara_sempro_filter_by_periode_and_gelombang(): void
    {
        $this->actingAs($this->admin)
            ->get(route('administrasi.berita-acara.index', ['jenis' => 'sempro', 'periode_id' => $this->periode->id, 'gelombang' => 1]))
            ->assertOk()
            ->assertSee('Gelombang 1 (1 Sep – 30 Sep 2026)')
            ->assertSee('Gelombang 2')
            ->assertSee('BANIM0')
            ->assertSee('BANIM1')
            ->assertDontSee('BANIM2');
    }

    public function test_cetak_semua_dan_zip_berita_acara_sempro_berhasil(): void
    {
        $params = ['jenis' => 'sempro', 'periode_id' => $this->periode->id, 'gelombang' => 1];

        $res = $this->actingAs($this->admin)->get(route('administrasi.berita-acara.mass-pdf', $params));
        $res->assertOk();
        $this->assertStringStartsWith('%PDF', file_get_contents($res->getFile()->getPathname()));
        $this->assertStringContainsString('Gelombang_1', $res->headers->get('content-disposition'));

        // Unduh kedua kali diambil dari cache — hasilnya identik.
        $lagi = $this->actingAs($this->admin)->get(route('administrasi.berita-acara.mass-pdf', $params));
        $lagi->assertOk();
        $this->assertSame($res->getFile()->getPathname(), $lagi->getFile()->getPathname());

        $this->actingAs($this->admin)->get(route('administrasi.berita-acara.mass-preview', $params))->assertOk();

        // Pratinjau / Cetak Semua: halaman HTML siap cetak (instan, tanpa DomPDF).
        $this->actingAs($this->admin)
            ->get(route('administrasi.berita-acara.mass-print', $params + ['print' => 1]))
            ->assertOk()
            ->assertSee('BERITA ACARA SEMINAR PROPOSAL SKRIPSI')
            ->assertSee('window.print()', false)
            ->assertSee(route('administrasi.berita-acara.mass-pdf', $params));
        $this->actingAs($this->admin)->get(route('administrasi.berita-acara.zip', $params))->assertOk();
    }

    public function test_sk_hanya_menghitung_ujian_skripsi_bukan_sempro(): void
    {
        $routes = ['administrasi.sk.export-penguji-pdf', 'administrasi.sk.export-pembimbing-pdf',
                   'administrasi.sk.export-penguji', 'administrasi.sk.export-pembimbing'];

        // Periode ini hanya berisi sempro → SK tidak boleh mencetak apa pun.
        foreach ($routes as $route) {
            $this->actingAs($this->admin)
                ->from(route('administrasi.sk.index'))
                ->get(route($route, ['periode_id' => $this->periode->id, 'jenis_tugas_akhir' => 'sempro']))
                ->assertRedirect()
                ->assertSessionHas('warning');
        }

        // Tambah satu ujian skripsi → SK tercetak.
        PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'skripsi', 'gelombang' => 1,
            'tanggal_mulai' => '2026-09-01', 'tanggal_selesai' => '2026-09-30',
        ]);
        $ketua = Dosen::create(['nama_dosen' => 'Ketua Skripsi BA', 'nidn' => 'BAK' . uniqid()]);
        $sempro = Sidang::where('periode_id', $this->periode->id)->first();
        Sidang::create([
            'nim' => 'BASKR' . substr(uniqid(), -6), 'nama_mahasiswa' => 'Mhs Skripsi', 'judul_skripsi' => 'Judul',
            'jenis_tugas_akhir' => 'sidang', 'verifikasi_status' => 'disetujui',
            'periode_id' => $this->periode->id, 'tanggal_pendaftaran' => '2026-09-10',
            'dosen_pembimbing_utama_id' => $sempro->dosen_pembimbing_utama_id,
            'ketua_penguji_id' => $ketua->id, 'anggota_penguji_1_id' => $sempro->dosen_pembimbing_pendamping_id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('administrasi.sk.index', ['periode_id' => $this->periode->id]))
            ->assertOk()
            ->assertSee('Hanya ujian skripsi', false);

        foreach (['administrasi.sk.export-penguji-pdf', 'administrasi.sk.export-pembimbing-pdf'] as $route) {
            $res = $this->actingAs($this->admin)->get(route($route, [
                'periode_id' => $this->periode->id, 'gelombang' => 1, 'jenis_tugas_akhir' => 'sempro',
            ]));
            $res->assertOk();
            $this->assertStringStartsWith('%PDF', $res->getContent());
            $this->assertStringContainsString('Pengajuan_SK_', $res->headers->get('content-disposition'));
        }

        foreach (['administrasi.sk.export-penguji', 'administrasi.sk.export-pembimbing'] as $route) {
            $this->actingAs($this->admin)
                ->get(route($route, ['periode_id' => $this->periode->id]))
                ->assertOk();
        }
    }
}

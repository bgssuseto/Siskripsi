<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DosenUndanganTest extends TestCase
{
    use DatabaseTransactions;

    private User $userDosen;
    private Dosen $dosen;
    private Periode $aktif;
    private Periode $lama;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        Periode::query()->update(['aktif' => false]);
        $this->lama  = Periode::create(['nama_periode' => 'Genap Lama ' . uniqid(), 'aktif' => false]);
        $this->aktif = Periode::create(['nama_periode' => 'Ganjil Aktif ' . uniqid(), 'aktif' => true]);

        PendaftaranPeriode::create(['periode_id' => $this->aktif->id, 'jenis' => 'skripsi', 'gelombang' => 1,
            'tanggal_mulai' => now()->subDays(60)->toDateString(), 'tanggal_selesai' => now()->subDays(50)->toDateString()]);
        PendaftaranPeriode::create(['periode_id' => $this->aktif->id, 'jenis' => 'skripsi', 'gelombang' => 2,
            'tanggal_mulai' => now()->subDays(10)->toDateString(), 'tanggal_selesai' => now()->subDays(2)->toDateString()]);

        $this->dosen = Dosen::create(['nama_dosen' => 'Dosen Undangan Uji', 'nidn' => 'DU' . uniqid()]);
        $this->userDosen = User::create(['name' => 'Dosen Undangan Uji', 'email' => 'du-' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => User::ROLE_DOSEN, 'dosen_id' => $this->dosen->id]);
        $lain = Dosen::create(['nama_dosen' => 'Dosen Lain', 'nidn' => 'DL' . uniqid()]);

        PendaftaranPeriode::create(['periode_id' => $this->lama->id, 'jenis' => 'skripsi', 'gelombang' => 3,
            'tanggal_mulai' => now()->subDays(260)->toDateString(), 'tanggal_selesai' => now()->subDays(250)->toDateString()]);

        // Gelombang ditentukan otomatis dari tanggal pendaftaran (lihat model Sidang).
        $daftar = [1 => now()->subDays(55), 2 => now()->subDays(5), 3 => now()->subDays(255)];
        $buat = fn ($periode, $gel, $tgl, $nama) => Sidang::create([
            'nim' => 'UND' . substr(uniqid(), -7), 'nama_mahasiswa' => $nama, 'judul_skripsi' => 'Judul',
            'jenis_tugas_akhir' => 'skripsi', 'verifikasi_status' => 'disetujui', 'periode_id' => $periode->id,
            'gelombang' => $gel, 'tanggal_pendaftaran' => $daftar[$gel]->toDateString(),
            'dosen_pembimbing_utama_id' => $lain->id, 'ketua_penguji_id' => $this->dosen->id,
            'tanggal' => $tgl, 'jam' => '09.00 - 10.00',
        ]);
        $buat($this->aktif, 1, now()->subDays(40)->toDateString(), 'Mhs Gel Satu');
        $buat($this->aktif, 2, now()->addDays(5)->toDateString(), 'Mhs Gel Dua');
        $buat($this->lama, 3, now()->subDays(200)->toDateString(), 'Mhs Semester Lama');
    }

    public function test_undangan_gelombang_berjalan_dan_arsip_per_semester(): void
    {
        $res = $this->actingAs($this->userDosen)->get(route('dosen.undangan.index'))->assertOk();

        $berjalan = $res->viewData('berjalan');
        $this->assertCount(1, $berjalan);
        $this->assertSame(2, (int) $berjalan->first()['gelombang']);

        $arsip = $res->viewData('arsip');
        $this->assertCount(2, $arsip); // gelombang 1 periode aktif + semester lama
        $res->assertSee($this->lama->nama_periode)->assertSee('Unduh Semua (ZIP)');
    }

    public function test_dosen_bisa_mengunduh_undangan_dan_zip_semester_miliknya(): void
    {
        $this->actingAs($this->userDosen);

        $this->get(route('dosen.undangan.unduh', [$this->aktif, 'skripsi', '2']))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('dosen.undangan.lihat', [$this->lama, 'skripsi', '3']))
            ->assertOk()->assertSee('MHS SEMESTER LAMA');
        $this->get(route('dosen.undangan.zip', $this->aktif))->assertOk();

        // Gelombang yang tidak memuat dosen ini tidak bisa dibuka.
        $this->get(route('dosen.undangan.unduh', [$this->aktif, 'sempro', '1']))->assertNotFound();
    }

    public function test_dosen_lain_tidak_bisa_membuka_undangan_orang_lain(): void
    {
        $dosen2 = Dosen::create(['nama_dosen' => 'Dosen Kedua', 'nidn' => 'DK' . uniqid()]);
        $user2 = User::create(['name' => 'Dosen Kedua', 'email' => 'dk-' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => User::ROLE_DOSEN, 'dosen_id' => $dosen2->id]);

        $this->actingAs($user2)->get(route('dosen.undangan.unduh', [$this->aktif, 'skripsi', '2']))->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardAdminTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Periode $periode;
    private Dosen $dosen;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $this->admin = User::create([
            'name' => 'Admin Dashboard', 'email' => 'dash-' . uniqid() . '@test.id',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $this->periode = Periode::create(['nama_periode' => 'Periode Dash ' . uniqid(), 'aktif' => false]);
        $this->dosen = Dosen::create(['nama_dosen' => 'Dosen Beban Dash, S.Kom', 'nidn' => 'DSH' . substr(uniqid(), -7)]);
    }

    private function sidang(array $attr): Sidang
    {
        return Sidang::create($attr + [
            'nim' => 'DSH' . substr(uniqid(), -7), 'nama_mahasiswa' => 'Mhs Dash', 'judul_skripsi' => 'Judul',
            'jenis_tugas_akhir' => 'sempro', 'verifikasi_status' => 'menunggu', 'jalur_ta' => 'sidang',
            'dosen_pembimbing_utama_id' => $this->dosen->id, 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => '2026-09-01',
        ]);
    }

    public function test_dashboard_menampilkan_tindakan_agenda_dan_beban_sesuai_filter_periode(): void
    {
        $ruang = Ruang::create(['kode_ruangan' => 'DSH' . substr(uniqid(), -5), 'nama_ruangan' => 'R', 'status' => Ruang::STATUS_SIAP]);
        $this->sidang(['nama_mahasiswa' => 'Mhs Menunggu Dash']);
        $this->sidang(['nama_mahasiswa' => 'Mhs Agenda Dash', 'verifikasi_status' => 'disetujui',
            'tanggal' => now()->addDay()->format('Y-m-d'), 'jam' => '08.00 - 09.00', 'ruang_id' => $ruang->id]);

        // Data periode lain tidak ikut tampil.
        $lain = Periode::create(['nama_periode' => 'Periode Lain ' . uniqid(), 'aktif' => false]);
        $this->sidang(['nama_mahasiswa' => 'Mhs Periode Lain', 'periode_id' => $lain->id]);

        $res = $this->actingAs($this->admin)->get(route('dashboard', ['periode_id' => $this->periode->id]));
        $res
            ->assertOk()
            ->assertSee('Perlu Tindakan')
            ->assertSee('Verifikasi berkas Sempro')
            ->assertSee('Agenda 7 Hari ke Depan')
            ->assertSee('MHS AGENDA DASH')
            ->assertSee('Besok')
            ->assertSee('Ruang ' . $ruang->kode_ruangan)
            ->assertSee('MHS MENUNGGU DASH')
            ->assertSee('Dosen Beban Dash, S.Kom')
            ->assertSee('Alur Progres');

        // Lonceng notifikasi di header memang lintas periode; isi dashboard tidak.
        $this->assertSame(2, $res->viewData('kpi')['pendaftar']);
        $this->assertFalse($res->viewData('terbaru')->contains('nama_mahasiswa', 'MHS PERIODE LAIN'));
    }

    public function test_grafik_lulusan_hanya_menghitung_yang_lulus(): void
    {
        $tahun = 2011;
        $this->sidang(['jenis_tugas_akhir' => 'skripsi', 'verifikasi_status' => 'disetujui', 'tanggal' => "$tahun-03-01", 'status_ujian' => 'lulus']);
        $this->sidang(['jenis_tugas_akhir' => 'skripsi', 'verifikasi_status' => 'disetujui', 'tanggal' => "$tahun-03-02", 'status_ujian' => 'tidak_lulus']);

        $res = $this->actingAs($this->admin)->get(route('dashboard', ['periode_id' => 'semua']))->assertOk();
        $lulusan = $res->viewData('lulusanPerTahun')->firstWhere('tahun', $tahun);
        $this->assertSame(1, (int) $lulusan->total);
    }

    public function test_dosen_dan_mahasiswa_diarahkan_ke_dashboard_masing_masing(): void
    {
        $dosenUser = User::create([
            'name' => 'Dosen Dash', 'email' => 'dash-dosen-' . uniqid() . '@test.id',
            'password' => bcrypt('password'), 'role' => User::ROLE_DOSEN, 'dosen_id' => $this->dosen->id,
        ]);
        $this->actingAs($dosenUser)->get(route('dashboard'))->assertRedirect(route('dosen.dashboard'));
    }
}

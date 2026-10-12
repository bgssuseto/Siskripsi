<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EditTanggalPendaftaranTest extends TestCase
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
            'name' => 'Admin Test', 'email' => 'admin-tgl-' . uniqid() . '@test.id',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $this->periode = Periode::firstOrCreate(['nama_periode' => 'Semester Gasal 2025/2026'], ['aktif' => true]);
        $this->dosen = Dosen::create(['nama_dosen' => 'Dosen Tgl Test', 'nidn' => 'TGL' . uniqid()]);
    }

    private function makeSidang(string $jenis): Sidang
    {
        return Sidang::create([
            'nim' => 'TGL' . uniqid(), 'nama_mahasiswa' => 'Mhs Tgl', 'judul_skripsi' => 'Judul',
            'jenis_tugas_akhir' => $jenis, 'verifikasi_status' => 'menunggu',
            'dosen_pembimbing_utama_id' => $this->dosen->id, 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => '2026-09-01', 'jalur_ta' => 'sidang',
        ]);
    }

    public function test_admin_can_change_tanggal_pendaftaran_from_data_sempro(): void
    {
        $sidang = $this->makeSidang('sempro');

        $this->actingAs($this->admin)
            ->put(route('master.sempro.update', $sidang), [
                'nim' => $sidang->nim, 'nama_mahasiswa' => $sidang->nama_mahasiswa,
                'judul_skripsi' => $sidang->judul_skripsi, 'jalur_ta' => 'sidang',
                'periode_id' => $this->periode->id, 'tanggal_pendaftaran' => '2026-10-05',
                'dosen_pembimbing_utama_id' => $this->dosen->id, 'dosen_pembimbing_pendamping_id' => '',
                'jenis_tugas_akhir' => 'sempro',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('2026-10-05', $sidang->fresh()->tanggal_pendaftaran->format('Y-m-d'));
    }

    public function test_change_tanggal_pendaftaran_for_scheduled_sempro_with_pendamping(): void
    {
        $pendamping = Dosen::create(['nama_dosen' => 'Pendamping Tgl', 'nidn' => 'TGLP' . uniqid()]);
        $ruang = \App\Models\Ruang::create(['kode_ruangan' => 'TGL' . uniqid(), 'nama_ruangan' => 'R', 'status' => 'siap_digunakan']);
        $sidang = $this->makeSidang('sempro');
        $sidang->update(['dosen_pembimbing_pendamping_id' => $pendamping->id, 'tanggal' => '2026-12-01', 'jam' => '08.00 - 09.00', 'ruang_id' => $ruang->id]);

        // Payload modal Edit Data di Data Sempro
        $this->actingAs($this->admin)
            ->put(route('master.sempro.update', $sidang), [
                'nim' => $sidang->nim, 'nama_mahasiswa' => $sidang->nama_mahasiswa,
                'judul_skripsi' => $sidang->judul_skripsi, 'jalur_ta' => 'sidang',
                'periode_id' => $this->periode->id, 'tanggal_pendaftaran' => '2026-10-05',
                'dosen_pembimbing_utama_id' => $this->dosen->id, 'dosen_pembimbing_pendamping_id' => $pendamping->id,
                'jenis_tugas_akhir' => 'sempro',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        // Payload modal Edit Data di Jadwal Sempro
        $this->actingAs($this->admin)
            ->put(route('master.sempro.update', $sidang), [
                'nim' => $sidang->nim, 'nama_mahasiswa' => $sidang->nama_mahasiswa,
                'judul_skripsi' => $sidang->judul_skripsi,
                'periode_id' => $this->periode->id, 'tanggal_pendaftaran' => '2026-10-06',
                'tanggal' => '2026-12-01', 'jam_mulai' => '08.00', 'jam_selesai' => '09.00', 'ruang_id' => $ruang->id,
                'keterangan' => '',
                'dosen_pembimbing_utama_id' => $this->dosen->id, 'dosen_pembimbing_pendamping_id' => $pendamping->id,
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame('2026-10-06', $sidang->fresh()->tanggal_pendaftaran->format('Y-m-d'));
    }

    public function test_admin_can_change_tanggal_pendaftaran_from_pendaftaran_page_and_gelombang_follows(): void
    {
        \App\Models\PendaftaranPeriode::where('periode_id', $this->periode->id)->where('jenis', 'sempro')->delete();
        \App\Models\PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 2,
            'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-31',
        ]);
        $sidang = $this->makeSidang('sempro');

        $this->actingAs($this->admin)->get(route('pendaftaran.sempro'))
            ->assertOk()->assertSee('Ubah Tanggal Pendaftaran');
        $this->actingAs($this->admin)->get(route('pendaftaran.skripsi'))->assertOk();

        $this->actingAs($this->admin)
            ->patch(route('pendaftaran.tanggal-pendaftaran', $sidang), ['tanggal_pendaftaran' => '2026-10-10'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $sidang->fresh();
        $this->assertSame('2026-10-10', $fresh->tanggal_pendaftaran->format('Y-m-d'));
        $this->assertSame(2, (int) $fresh->gelombang);

        $this->actingAs($this->admin)
            ->patch(route('pendaftaran.tanggal-pendaftaran', $sidang), ['tanggal_pendaftaran' => ''], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_admin_store_accepts_custom_tanggal_pendaftaran(): void
    {
        $nim = 'TGLS' . uniqid();
        $this->actingAs($this->admin)
            ->post(route('pendaftaran.admin-store'), [
                'nim' => $nim, 'nama_mahasiswa' => 'Manual', 'jenis_tugas_akhir' => 'sempro',
                'jenis_ta_pilihan' => 'sidang', 'judul_skripsi' => 'J',
                'dosen_pembimbing_utama_id' => $this->dosen->id, 'periode_id' => $this->periode->id,
                'tanggal_pendaftaran' => '2026-08-17',
            ])->assertSessionHas('success');

        $this->assertSame('2026-08-17', Sidang::where('nim', $nim)->first()->tanggal_pendaftaran->format('Y-m-d'));
    }

    public function test_duplicate_nim_in_same_periode_returns_clear_422_instead_of_server_error(): void
    {
        $lain = Periode::create(['nama_periode' => 'Periode Lain ' . uniqid(), 'aktif' => false]);
        $a = $this->makeSidang('sempro');
        $b = $this->makeSidang('sempro');
        $b->update(['nim' => $a->nim, 'periode_id' => $lain->id]);

        $this->actingAs($this->admin)
            ->put(route('master.sempro.update', $b), [
                'nim' => $a->nim, 'nama_mahasiswa' => 'X', 'judul_skripsi' => 'J', 'jalur_ta' => 'sidang',
                'periode_id' => $this->periode->id, 'tanggal_pendaftaran' => '2026-10-05',
                'dosen_pembimbing_utama_id' => $this->dosen->id,
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}

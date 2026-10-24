<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Kepemilikan data mahasiswa harus dikunci lewat NIM dan tidak bisa diambil
 * alih dengan mengganti NIM/nama sendiri.
 */
class KeamananDataMahasiswaTest extends TestCase
{
    use DatabaseTransactions;

    private Sidang $sidangKorban;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $periode = Periode::create(['nama_periode' => 'Periode Keamanan ' . uniqid(), 'aktif' => false]);
        $dosen = Dosen::create(['nama_dosen' => 'Dosen Keamanan', 'nidn' => 'KM' . uniqid()]);

        $this->sidangKorban = Sidang::create([
            'nim' => 'KORBAN' . substr(uniqid(), -5), 'nama_mahasiswa' => 'Siti Korban', 'judul_skripsi' => 'Judul Rahasia',
            'jenis_tugas_akhir' => 'sempro', 'verifikasi_status' => 'menunggu', 'periode_id' => $periode->id,
            'tanggal_pendaftaran' => now()->toDateString(), 'dosen_pembimbing_utama_id' => $dosen->id,
            'file_persyaratan' => 'uploads/persyaratan/tidak-ada.pdf',
        ]);
    }

    private function mahasiswa(?string $nim, string $nama = 'Penyerang'): User
    {
        return User::create(['name' => $nama, 'email' => 'm' . uniqid() . '@test.id', 'password' => bcrypt('x'),
            'role' => User::ROLE_MAHASISWA, 'nim' => $nim]);
    }

    public function test_mahasiswa_tidak_bisa_mengganti_nim_sendiri(): void
    {
        $user = $this->mahasiswa('NIMASLI' . substr(uniqid(), -5));

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email, 'nim' => $this->sidangKorban->nim,
        ]);

        $this->assertNotSame($this->sidangKorban->nim, $user->fresh()->nim);
    }

    public function test_akun_tanpa_nim_tidak_mendapat_data_lewat_kesamaan_nama(): void
    {
        $user = $this->mahasiswa(null, 'SITI KORBAN');

        $this->actingAs($user)->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertDontSee('JUDUL RAHASIA');
    }

    public function test_mahasiswa_lain_tidak_bisa_membuka_berkas_dan_koordinator_tambahan_bisa(): void
    {
        $this->actingAs($this->mahasiswa('LAIN' . substr(uniqid(), -5)))
            ->get(route('dokumen.show', [$this->sidangKorban, 'persyaratan']))
            ->assertForbidden();

        // Dosen yang diberi role koordinator sebagai role tambahan → lolos cek akses
        // (404 karena berkas contoh memang tidak ada di disk, bukan 403).
        $dosenKoor = User::create(['name' => 'Dosen Koor', 'email' => 'dk' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_DOSEN]);
        UserRole::create(['user_id' => $dosenKoor->id, 'role' => User::ROLE_KOORDINATOR]);
        $this->actingAs($dosenKoor)
            ->get(route('dokumen.show', [$this->sidangKorban, 'persyaratan']))
            ->assertNotFound();
    }

    public function test_admin_mengoreksi_nim_memindahkan_data_pendaftaran(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'a' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $pemilik = $this->mahasiswa($this->sidangKorban->nim, 'SITI KORBAN');
        $nimBenar = 'BENAR' . substr(uniqid(), -5);

        $this->actingAs($admin)->putJson(route('users.update', $pemilik), [
            'name' => $pemilik->name, 'email' => $pemilik->email, 'role' => User::ROLE_MAHASISWA, 'nim' => $nimBenar,
        ])->assertOk();

        $this->assertSame($nimBenar, $pemilik->fresh()->nim);
        $this->assertSame($nimBenar, $this->sidangKorban->fresh()->nim);
    }
}

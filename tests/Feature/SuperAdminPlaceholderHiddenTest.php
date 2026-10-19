<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Dosen placeholder "Super Administrator" (NIDN 0000000000) tidak boleh
 * muncul di SATU PUN dropdown pilihan dosen di seluruh sistem (pembimbing,
 * penguji, kesediaan, filter admin/publik) — lihat Dosen::scopeExcludingSuperAdminPlaceholder().
 */
class SuperAdminPlaceholderHiddenTest extends TestCase
{
    use DatabaseTransactions;

    private Dosen $superAdminDosen;
    private Dosen $dosenAsli;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $this->superAdminDosen = Dosen::firstOrCreate(
            ['nidn' => Dosen::NIDN_SUPER_ADMIN_PLACEHOLDER],
            ['nama_dosen' => 'Super Administrator']
        );
        $this->dosenAsli = Dosen::create(['nidn' => '0099999901', 'nama_dosen' => 'Dr. Dosen Asli Dua']);

        $this->superAdmin = User::create([
            'name' => 'Admin Uji Hide', 'email' => 'admin-hide-everywhere@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);

        Periode::query()->update(['aktif' => false]);
        Periode::create(['nama_periode' => 'Periode Uji Hide Super Admin', 'aktif' => true]);
    }

    public function test_dropdown_dosen_di_asisten_plotting_tidak_menampilkan_super_administrator(): void
    {
        // $dosens dikirim ke view terpisah dari tabel usulan (yang hanya tampil
        // setelah "Buat Usulan Jadwal" di-generate) -- cek lewat viewData supaya
        // tidak perlu menyiapkan satu sidang+kesediaan+ruang lengkap segala.
        $response = $this->actingAs($this->superAdmin)->get(route('jadwal.auto-plot.index'))->assertOk();
        $dosens = $response->viewData('dosens');

        $this->assertTrue($dosens->contains('nama_dosen', 'Dr. Dosen Asli Dua'));
        $this->assertFalse($dosens->contains('nama_dosen', 'Super Administrator'));
    }

    public function test_halaman_kelola_user_tidak_menampilkan_super_administrator_di_pilihan_dosen(): void
    {
        $this->actingAs($this->superAdmin)->get(route('users.index'))
            ->assertOk()
            ->assertSee('Dr. Dosen Asli Dua')
            ->assertDontSee('Super Administrator');
    }

    public function test_halaman_rule_komposisi_dosen_penguji_tidak_menampilkan_super_administrator(): void
    {
        $this->actingAs($this->superAdmin)->get(route('master.dosen-penguji-rule.index'))
            ->assertOk()
            ->assertSee('Dr. Dosen Asli Dua')
            ->assertDontSee('Super Administrator');
    }

    public function test_halaman_publik_jadwal_dosen_penguji_tidak_menampilkan_super_administrator(): void
    {
        $this->get(route('public.jadwal-dosen-penguji'))
            ->assertOk()
            ->assertSee('Dr. Dosen Asli Dua')
            ->assertDontSee('Super Administrator');
    }

    public function test_halaman_kelola_sempro_tidak_menampilkan_super_administrator_di_pilihan_pembimbing(): void
    {
        $this->actingAs($this->superAdmin)->get(route('master.sempro.index'))
            ->assertOk()
            ->assertSee('Dr. Dosen Asli Dua')
            ->assertDontSee('Super Administrator');
    }

    public function test_halaman_kelola_skripsi_tidak_menampilkan_super_administrator_di_pilihan_pembimbing(): void
    {
        $this->actingAs($this->superAdmin)->get(route('master.skripsi.index'))
            ->assertOk()
            ->assertSee('Dr. Dosen Asli Dua')
            ->assertDontSee('Super Administrator');
    }
}

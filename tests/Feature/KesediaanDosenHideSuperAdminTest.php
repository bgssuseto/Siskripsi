<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Dosen placeholder "Super Administrator" (NIDN 0000000000, dibuat otomatis
 * di UserController::destroy() sebagai penampung data saat dosen asli
 * dihapus) bukan dosen sungguhan -- tidak boleh muncul di pilihan dosen pada
 * fitur kesediaan menguji (admin maupun link publik).
 */
class KesediaanDosenHideSuperAdminTest extends TestCase
{
    use DatabaseTransactions;

    private Dosen $superAdminDosen;
    private Dosen $dosenAsli;
    private Periode $periode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminDosen = Dosen::firstOrCreate(
            ['nidn' => Dosen::NIDN_SUPER_ADMIN_PLACEHOLDER],
            ['nama_dosen' => 'Super Administrator']
        );
        $this->dosenAsli = Dosen::create(['nidn' => '0088888801', 'nama_dosen' => 'Dr. Dosen Asli']);

        Periode::query()->update(['aktif' => false]);
        $this->periode = Periode::create([
            'nama_periode' => 'Periode Uji Sembunyikan Super Admin', 'aktif' => true,
            'show_form_kesediaan' => true, 'lock_form_kesediaan' => false,
        ]);
    }

    public function test_halaman_admin_kesediaan_dosen_tidak_menampilkan_super_administrator(): void
    {
        $superAdmin = User::create([
            'name' => 'Admin Uji', 'email' => 'admin-hide-super@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($superAdmin)->get(route('master.kesediaan-dosen.index'))
            ->assertOk()
            ->assertSee('Dr. Dosen Asli')
            ->assertDontSee('Super Administrator');
    }

    public function test_form_kesediaan_publik_tidak_menampilkan_super_administrator(): void
    {
        $token = $this->periode->ensureKesediaanPublicToken();

        $this->get(route('public.kesediaan.show', $token))
            ->assertOk()
            ->assertSee('Dr. Dosen Asli')
            ->assertDontSee('Super Administrator');
    }

    public function test_submit_kesediaan_publik_atas_nama_super_administrator_ditolak(): void
    {
        $token = $this->periode->ensureKesediaanPublicToken();
        $wave = \App\Models\PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 1,
            'tanggal_mulai' => now()->format('Y-m-d'), 'tanggal_selesai' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $this->post(route('public.kesediaan.store', $token), [
            'dosen_id' => $this->superAdminDosen->id,
            'wave_id'  => $wave->id,
            'slots'    => [['tanggal' => now()->addDays(2)->format('Y-m-d')]],
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('kesediaan_dosens', ['dosen_id' => $this->superAdminDosen->id]);
    }
}

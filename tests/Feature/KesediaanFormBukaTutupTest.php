<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KesediaanDosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Form kesediaan menguji dosen (baik lewat portal login maupun link publik
 * tanpa login) harus bisa dibuka/ditutup murni lewat toggle di menu Kesediaan
 * Dosen (show_form_kesediaan / lock_form_kesediaan) -- TIDAK lagi ikut
 * tertutup otomatis hanya karena gelombang pendaftaran (PendaftaranPeriode)
 * sedang di luar jendela tanggalnya.
 */
class KesediaanFormBukaTutupTest extends TestCase
{
    use DatabaseTransactions;

    private Periode $periode;
    private Dosen $dosen;

    protected function setUp(): void
    {
        parent::setUp();

        Periode::query()->update(['aktif' => false]);
        $this->periode = Periode::create([
            'nama_periode' => 'Periode Uji Kesediaan', 'aktif' => true,
            'show_form_kesediaan' => true, 'lock_form_kesediaan' => false,
        ]);
        $this->dosen = Dosen::create(['nidn' => '0077777701', 'nama_dosen' => 'Dr. Uji Kesediaan']);
    }

    private function gelombangSudahLewat(): PendaftaranPeriode
    {
        return PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 1,
            'tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-01-31', // sudah lewat
        ]);
    }

    // ── Portal dosen (login) ────────────────────────────────────────────────

    public function test_dosen_tetap_bisa_mengisi_kesediaan_walau_gelombang_sudah_lewat(): void
    {
        $wave = $this->gelombangSudahLewat();
        $dosenUser = User::create([
            'name' => $this->dosen->nama_dosen, 'email' => 'dosen-kesediaan@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_DOSEN, 'dosen_id' => $this->dosen->id,
        ]);

        $this->actingAs($dosenUser)->post(route('dosen.kesediaan.store'), [
            'wave_id' => $wave->id,
            'slots' => [['tanggal' => now()->addDays(5)->format('Y-m-d'), 'keterangan' => 'Siap menguji']],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('kesediaan_dosens', ['dosen_id' => $this->dosen->id, 'wave_id' => $wave->id]);
    }

    public function test_dosen_bisa_mengisi_kesediaan_tanpa_memilih_gelombang_sama_sekali(): void
    {
        // Periode belum punya gelombang sama sekali -- wave_id harus benar-benar opsional.
        $dosenUser = User::create([
            'name' => $this->dosen->nama_dosen, 'email' => 'dosen-kesediaan2@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_DOSEN, 'dosen_id' => $this->dosen->id,
        ]);

        $this->actingAs($dosenUser)->post(route('dosen.kesediaan.store'), [
            'slots' => [['tanggal' => now()->addDays(5)->format('Y-m-d'), 'keterangan' => 'Siap menguji']],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('kesediaan_dosens', ['dosen_id' => $this->dosen->id, 'wave_id' => null]);
    }

    public function test_dashboard_dosen_menampilkan_semua_gelombang_termasuk_yang_sudah_lewat(): void
    {
        $wave = $this->gelombangSudahLewat();
        $dosenUser = User::create([
            'name' => $this->dosen->nama_dosen, 'email' => 'dosen-kesediaan3@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_DOSEN, 'dosen_id' => $this->dosen->id,
        ]);

        $this->actingAs($dosenUser)->get(route('dosen.dashboard'))
            ->assertOk()
            ->assertSee('Gelombang 1 - Sempro', false)
            ->assertDontSee('name="wave_id" required', false);
    }

    public function test_form_tetap_ditolak_server_side_saat_periode_dikunci_koordinator(): void
    {
        $this->gelombangSudahLewat();
        $this->periode->update(['lock_form_kesediaan' => true]);
        $dosenUser = User::create([
            'name' => $this->dosen->nama_dosen, 'email' => 'dosen-kesediaan4@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_DOSEN, 'dosen_id' => $this->dosen->id,
        ]);

        $this->actingAs($dosenUser)->post(route('dosen.kesediaan.store'), [
            'slots' => [['tanggal' => now()->addDays(5)->format('Y-m-d')]],
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('kesediaan_dosens', ['dosen_id' => $this->dosen->id]);
    }

    // ── Link publik (tanpa login) ───────────────────────────────────────────

    public function test_link_publik_tetap_aktif_walau_gelombang_sudah_lewat(): void
    {
        $this->gelombangSudahLewat();

        $this->assertTrue($this->periode->isKesediaanPublicLinkActive());

        $token = $this->periode->ensureKesediaanPublicToken();
        $this->get(route('public.kesediaan.show', $token))
            ->assertOk()
            ->assertSee('Form Kesediaan Menguji')
            ->assertDontSee('Link Sedang Tidak Aktif');
    }

    public function test_link_publik_bisa_dipakai_submit_untuk_gelombang_yang_sudah_lewat(): void
    {
        $wave = $this->gelombangSudahLewat();
        $this->dosen->update(['can_fill_kesediaan' => true]);
        $token = $this->periode->ensureKesediaanPublicToken();

        $this->post(route('public.kesediaan.store', $token), [
            'dosen_id' => $this->dosen->id,
            'wave_id'  => $wave->id,
            'slots'    => [['tanggal' => now()->addDays(5)->format('Y-m-d'), 'keterangan' => 'Siap']],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('kesediaan_dosens', ['dosen_id' => $this->dosen->id, 'wave_id' => $wave->id]);
    }

    public function test_link_publik_nonaktif_saat_ditutup_admin_meski_gelombang_sedang_buka(): void
    {
        // Gelombang SEDANG berjalan (hari ini ada di dalam rentangnya), tapi
        // admin menutup form lewat menu Kesediaan Dosen -- harus tetap nonaktif.
        PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 2,
            'tanggal_mulai' => now()->subDay()->format('Y-m-d'), 'tanggal_selesai' => now()->addDays(7)->format('Y-m-d'),
        ]);
        $this->periode->update(['show_form_kesediaan' => false]);

        $this->assertFalse($this->periode->isKesediaanPublicLinkActive());

        $token = $this->periode->ensureKesediaanPublicToken();
        $this->get(route('public.kesediaan.show', $token))
            ->assertOk()
            ->assertSee('Link Sedang Tidak Aktif');
    }

    public function test_link_publik_nonaktif_saat_dikunci_admin(): void
    {
        $this->periode->update(['lock_form_kesediaan' => true]);
        $this->assertFalse($this->periode->isKesediaanPublicLinkActive());
    }
}

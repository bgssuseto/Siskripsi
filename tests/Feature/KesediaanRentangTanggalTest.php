<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Admin/koordinator membatasi rentang tanggal yang boleh dipilih dosen saat
 * mengisi kesediaan menguji (dashboard dosen & link publik).
 */
class KesediaanRentangTanggalTest extends TestCase
{
    use DatabaseTransactions;

    private Periode $periode;
    private Dosen $dosen;
    private PendaftaranPeriode $wave;
    private string $mulai;
    private string $selesai;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        Periode::query()->update(['aktif' => false]);
        $this->periode = Periode::create([
            'nama_periode' => 'Periode Rentang ' . uniqid(), 'aktif' => true,
            'show_form_kesediaan' => true, 'lock_form_kesediaan' => false,
        ]);
        $this->dosen = Dosen::create(['nidn' => 'RNG' . substr(uniqid(), -7), 'nama_dosen' => 'Dr. Uji Rentang', 'can_fill_kesediaan' => true]);
        $this->wave = PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 1,
            'tanggal_mulai' => now()->format('Y-m-d'), 'tanggal_selesai' => now()->addDays(10)->format('Y-m-d'),
        ]);
        $this->mulai = now()->addDays(7)->format('Y-m-d');
        $this->selesai = now()->addDays(14)->format('Y-m-d');
    }

    private function aturRentang(User $oleh, ?string $mulai, ?string $selesai)
    {
        return $this->actingAs($oleh)->post(route('master.kesediaan-dosen.settings'), [
            'show_form_kesediaan' => 1, 'lock_form_kesediaan' => 0,
            'kesediaan_tanggal_mulai' => $mulai, 'kesediaan_tanggal_selesai' => $selesai,
        ]);
    }

    public function test_admin_dan_koordinator_bisa_mengatur_rentang(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $koor = User::factory()->create(['role' => User::ROLE_KOORDINATOR]);

        $this->aturRentang($koor, $this->mulai, $this->selesai)->assertSessionHas('success');
        $p = $this->periode->fresh();
        $this->assertSame($this->mulai, $p->kesediaan_tanggal_mulai->format('Y-m-d'));
        $this->assertSame($this->selesai, $p->kesediaan_tanggal_selesai->format('Y-m-d'));

        // Halaman pengaturan menampilkan rentang yang berlaku.
        $this->actingAs($admin)->get(route('master.kesediaan-dosen.index'))
            ->assertOk()->assertSee('Rentang Tanggal Kesediaan')->assertSee('Berlaku:');

        // Dikosongkan = tanpa batas.
        $this->aturRentang($admin, '', '')->assertSessionHas('success');
        $this->assertNull($this->periode->fresh()->kesediaan_tanggal_mulai);
        $this->assertNull($this->periode->fresh()->kesediaan_tanggal_selesai);
    }

    public function test_tanggal_selesai_tidak_boleh_sebelum_mulai(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->aturRentang($admin, $this->selesai, $this->mulai)->assertSessionHasErrors('kesediaan_tanggal_selesai');
        $this->assertNull($this->periode->fresh()->kesediaan_tanggal_mulai);
    }

    public function test_pengaturan_tanpa_field_rentang_tidak_menghapus_rentang(): void
    {
        $this->periode->update(['kesediaan_tanggal_mulai' => $this->mulai, 'kesediaan_tanggal_selesai' => $this->selesai]);
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)->post(route('master.kesediaan-dosen.settings'), ['show_form_kesediaan' => 0, 'lock_form_kesediaan' => 1]);

        $this->assertSame($this->mulai, $this->periode->fresh()->kesediaan_tanggal_mulai->format('Y-m-d'));
    }

    public function test_dosen_hanya_bisa_mengisi_tanggal_di_dalam_rentang(): void
    {
        $this->periode->update(['kesediaan_tanggal_mulai' => $this->mulai, 'kesediaan_tanggal_selesai' => $this->selesai]);
        $user = User::create(['name' => $this->dosen->nama_dosen, 'email' => 'rentang' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => User::ROLE_DOSEN, 'dosen_id' => $this->dosen->id]);

        // Di luar rentang → ditolak, tidak ada yang tersimpan.
        $this->actingAs($user)->post(route('dosen.kesediaan.store'), [
            'wave_id' => $this->wave->id,
            'slots' => [
                ['tanggal' => now()->addDays(8)->format('Y-m-d'), 'keterangan' => ''],
                ['tanggal' => now()->addDays(30)->format('Y-m-d'), 'keterangan' => ''],
            ],
        ])->assertSessionHas('error', fn ($pesan) => str_contains($pesan, 'di luar rentang'));
        $this->assertDatabaseMissing('kesediaan_dosens', ['dosen_id' => $this->dosen->id]);

        // Di dalam rentang → tersimpan.
        $this->actingAs($user)->post(route('dosen.kesediaan.store'), [
            'wave_id' => $this->wave->id,
            'slots' => [['tanggal' => now()->addDays(8)->format('Y-m-d'), 'keterangan' => '']],
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('kesediaan_dosens', ['dosen_id' => $this->dosen->id]);
    }

    public function test_link_publik_menolak_tanggal_di_luar_rentang_dan_tanpa_label_gelombang(): void
    {
        $this->periode->update(['kesediaan_tanggal_mulai' => $this->mulai, 'kesediaan_tanggal_selesai' => $this->selesai]);
        $token = $this->periode->ensureKesediaanPublicToken();

        // Halaman publik: label "Gelombang 1 · Sempro — tgl s/d tgl" di header sudah tidak ada,
        // dan kalender membawa batas rentang.
        $this->get(route('public.kesediaan.show', $token))
            ->assertOk()
            ->assertDontSee('Gelombang 1 · Sempro —', false)
            ->assertSee('Tanggal yang bisa dipilih')
            ->assertSee($this->mulai)
            ->assertSee($this->selesai);

        $this->post(route('public.kesediaan.store', $token), [
            'dosen_id' => $this->dosen->id, 'wave_id' => $this->wave->id,
            'slots' => [['tanggal' => now()->addDays(2)->format('Y-m-d'), 'keterangan' => '']],
        ])->assertSessionHas('error');
        $this->assertDatabaseMissing('kesediaan_dosens', ['dosen_id' => $this->dosen->id]);

        $this->post(route('public.kesediaan.store', $token), [
            'dosen_id' => $this->dosen->id, 'wave_id' => $this->wave->id,
            'slots' => [['tanggal' => $this->selesai, 'keterangan' => '']],
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('kesediaan_dosens', ['dosen_id' => $this->dosen->id]);
    }

    public function test_tanpa_rentang_semua_tanggal_mendatang_boleh(): void
    {
        $this->assertSame([], $this->periode->tanggalDiLuarRentangKesediaan([now()->addYear()->format('Y-m-d')]));
        $this->assertNull($this->periode->labelRentangKesediaan());
    }
}

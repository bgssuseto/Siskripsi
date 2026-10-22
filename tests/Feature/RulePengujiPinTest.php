<?php

namespace Tests\Feature;

use App\Http\Middleware\PinRulePenguji;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RulePengujiPinTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin PIN', 'email' => 'admin-pin@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);
        RateLimiter::clear('pin-rule-penguji:' . $this->admin->id . '|127.0.0.1');
    }

    public function test_halaman_terkunci_menampilkan_form_pin_tanpa_data_rule(): void
    {
        Dosen::create(['nidn' => '0011223344', 'nama_dosen' => 'Dosen Rahasia Sekali']);

        $this->actingAs($this->admin)->get(route('master.dosen-penguji-rule.index'))
            ->assertOk()
            ->assertSee('Halaman Rahasia')
            ->assertDontSee('Dosen Rahasia Sekali');
    }

    public function test_pin_salah_ditolak_dan_pin_benar_membuka_halaman(): void
    {
        $this->actingAs($this->admin)->postJson(route('master.dosen-penguji-rule.buka'), ['pin' => '111111'])
            ->assertStatus(422)->assertJsonPath('success', false);

        $this->postJson(route('master.dosen-penguji-rule.buka'), ['pin' => '030400'])
            ->assertOk()->assertJsonPath('success', true);

        $this->get(route('master.dosen-penguji-rule.index'))
            ->assertOk()
            ->assertDontSee('Halaman Rahasia')
            ->assertSee('Rule Komposisi Dosen Penguji');
    }

    public function test_simpan_rule_ditolak_saat_terkunci(): void
    {
        $dosen = Dosen::create(['nidn' => '0011223355', 'nama_dosen' => 'Dosen Uji Kunci']);

        $this->actingAs($this->admin)->post(route('master.dosen-penguji-rule.store'), ['dosen_id' => $dosen->id])
            ->assertRedirect(route('master.dosen-penguji-rule.index'));

        $this->assertDatabaseMissing('dosen_penguji_rules', ['dosen_id' => $dosen->id]);
    }

    public function test_terlalu_banyak_pin_salah_dikunci_sementara(): void
    {
        $this->actingAs($this->admin);
        foreach (range(1, 5) as $_) {
            $this->postJson(route('master.dosen-penguji-rule.buka'), ['pin' => '000000'])->assertStatus(422);
        }

        // PIN benar pun ditolak selama masa jeda.
        $this->postJson(route('master.dosen-penguji-rule.buka'), ['pin' => '030400'])->assertStatus(429);
    }

    public function test_kunci_kembali_menghapus_akses(): void
    {
        $this->actingAs($this->admin)
            ->withSession([PinRulePenguji::SESSION_KEY => now()->addMinutes(5)->timestamp])
            ->post(route('master.dosen-penguji-rule.kunci'))
            ->assertRedirect(route('master.dosen-penguji-rule.index'));

        $this->get(route('master.dosen-penguji-rule.index'))->assertSee('Halaman Rahasia');
    }

    public function test_pindah_ke_halaman_lain_langsung_mengunci_kembali(): void
    {
        $this->actingAs($this->admin)->postJson(route('master.dosen-penguji-rule.buka'), ['pin' => '030400'])->assertOk();
        $this->get(route('master.dosen-penguji-rule.index'))->assertDontSee('Halaman Rahasia');

        // Permintaan AJAX dari halaman itu sendiri tidak mengunci.
        $this->postJson(route('master.dosen-penguji-rule.aktif'))->assertNoContent();
        $this->get(route('master.dosen-penguji-rule.index'))->assertDontSee('Halaman Rahasia');

        $this->get(route('dashboard'));
        $this->get(route('master.dosen-penguji-rule.index'))->assertSee('Halaman Rahasia');
    }
}

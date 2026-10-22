<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KesediaanDosen;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class KesediaanResetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_hapus_semua_di_tabel_mengikuti_filter_periode_dan_tanpa_filter_menghapus_semua(): void
    {
        $admin = User::create(['name' => 'Admin Reset', 'email' => 'reset-' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $dosen = Dosen::create(['nama_dosen' => 'Dosen Reset', 'nidn' => 'RST' . uniqid()]);
        $p1 = Periode::create(['nama_periode' => 'Reset A ' . uniqid(), 'aktif' => false]);
        $p2 = Periode::create(['nama_periode' => 'Reset B ' . uniqid(), 'aktif' => false]);
        foreach ([$p1, $p2] as $p) {
            KesediaanDosen::create(['dosen_id' => $dosen->id, 'periode_id' => $p->id, 'tanggal' => now()->addDays(3)->format('Y-m-d'), 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        }

        $this->actingAs($admin)->get(route('master.kesediaan-dosen.index', ['periode_id' => $p1->id]))
            ->assertOk()->assertSee('Reset Kesediaan (sesuai filter)');

        $this->actingAs($admin)->delete(route('master.kesediaan-dosen.destroy-all'), ['periode_id' => $p1->id])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(0, KesediaanDosen::where('periode_id', $p1->id)->count());
        $this->assertSame(1, KesediaanDosen::where('periode_id', $p2->id)->count());

        $this->actingAs($admin)->delete(route('master.kesediaan-dosen.destroy-all'))->assertSessionHas('success');
        $this->assertSame(0, KesediaanDosen::count());
    }

    public function test_reset_kesediaan_per_gelombang_tidak_menghapus_gelombang_lain(): void
    {
        $admin = User::create(['name' => 'Admin Reset Gel', 'email' => 'resetg-' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $dosen = Dosen::create(['nama_dosen' => 'Dosen Reset Gel', 'nidn' => 'RSG' . uniqid()]);
        $p = Periode::create(['nama_periode' => 'Reset Gel ' . uniqid(), 'aktif' => false]);
        $g1 = \App\Models\PendaftaranPeriode::create(['periode_id' => $p->id, 'jenis' => 'skripsi', 'gelombang' => 1, 'tanggal_mulai' => '2026-09-01', 'tanggal_selesai' => '2026-09-30']);
        $g2 = \App\Models\PendaftaranPeriode::create(['periode_id' => $p->id, 'jenis' => 'skripsi', 'gelombang' => 2, 'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-31']);
        foreach ([$g1, $g2] as $g) {
            KesediaanDosen::create(['dosen_id' => $dosen->id, 'periode_id' => $p->id, 'wave_id' => $g->id, 'tanggal' => now()->addDays(3)->format('Y-m-d'), 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        }

        $this->actingAs($admin)->get(route('master.kesediaan-dosen.index', ['periode_id' => $p->id, 'wave_id' => $g1->id]))
            ->assertOk()
            ->assertSee('Reset Kesediaan (sesuai filter)')
            ->assertSee('gelombang 1 (Skripsi)');

        $this->actingAs($admin)->delete(route('master.kesediaan-dosen.destroy-all'), ['periode_id' => $p->id, 'wave_id' => $g1->id])
            ->assertRedirect(route('master.kesediaan-dosen.index', ['periode_id' => $p->id, 'wave_id' => $g1->id]))
            ->assertSessionHas('success');

        $this->assertSame(0, KesediaanDosen::where('wave_id', $g1->id)->count());
        $this->assertSame(1, KesediaanDosen::where('wave_id', $g2->id)->count());
    }
}

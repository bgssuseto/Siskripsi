<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Dosen;
use App\Models\Periode;
use App\Models\KesediaanDosen;
use App\Models\PendaftaranPeriode;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class KesediaanDosenTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dosen_can_submit_availability_and_super_admin_can_view_it(): void
    {
        // 1. Create Super Admin & Dosen
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $dosenObj = Dosen::create(['nidn' => '9988776655', 'nama_dosen' => 'Dr. Budi Dosen, M.Kom']);
        $dosenUser = User::factory()->create(['role' => User::ROLE_DOSEN, 'dosen_id' => $dosenObj->id]);

        Periode::query()->update(['aktif' => false]);
        $periode = Periode::create(['nama_periode' => 'Semester Gasal 2026/2027', 'aktif' => true]);

        $wave = PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'gelombang' => 1,
            'jenis' => 'sempro',
            'aktif' => true,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        // 2. Dosen submits availability slots
        $response = $this->actingAs($dosenUser)->post(route('dosen.kesediaan.store'), [
            'wave_id' => $wave->id,
            'slots' => [
                [
                    'tanggal' => '2026-08-10',
                    'keterangan' => 'Siap menguji luring',
                ],
                [
                    'tanggal' => '2026-08-11',
                    'keterangan' => 'Siap menguji online',
                ],
            ]
        ]);

        $response->assertRedirect();
        $this->assertEquals(2, KesediaanDosen::where('dosen_id', $dosenObj->id)->count());
        $this->assertDatabaseHas('kesediaan_dosens', [
            'dosen_id' => $dosenObj->id,
            'tanggal' => '2026-08-10',
            'jam_mulai' => '',
            'jam_selesai' => '',
        ]);

        // 3. Super Admin accesses Kesediaan Dosen page
        $adminResponse = $this->actingAs($superAdmin)->get(route('master.kesediaan-dosen.index'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Dr. Budi Dosen, M.Kom');
        $adminResponse->assertSee('Senin, 10 Agustus 2026');
    }

    public function test_koordinator_can_view_availability_and_both_admin_roles_can_update_settings(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $koordinator = User::factory()->create(['role' => User::ROLE_KOORDINATOR]);
        $dosenObj = Dosen::create(['nidn' => '1122334455', 'nama_dosen' => 'Dr. Cici Dosen, M.Kom'])->refresh();
        Periode::query()->update(['aktif' => false]);
        $periode = Periode::create(['nama_periode' => 'Semester Genap 2026/2027', 'aktif' => true]);

        $wave = PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'gelombang' => 1,
            'jenis' => 'skripsi',
            'aktif' => true,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);
        
        KesediaanDosen::create([
            'dosen_id' => $dosenObj->id,
            'periode_id' => $periode->id,
            'wave_id' => $wave->id,
            'tanggal' => '2026-08-12',
            'jam_mulai' => '',
            'jam_selesai' => '',
        ]);

        // 1. Koordinator can access Kesediaan Dosen page
        $koorResponse = $this->actingAs($koordinator)->get(route('master.kesediaan-dosen.index'));
        $koorResponse->assertStatus(200);
        $koorResponse->assertSee('Dr. Cici Dosen, M.Kom');
        $koorResponse->assertSee('Rabu, 12 Agustus 2026');

        // 2. Koordinator updates settings (close form, lock form)
        $koorPostResponse = $this->actingAs($koordinator)->post(route('master.kesediaan-dosen.settings'), [
            'show_form_kesediaan' => 0,
            'lock_form_kesediaan' => 1,
        ]);
        $koorPostResponse->assertRedirect();
        
        $periode->refresh();
        $this->assertFalse($periode->show_form_kesediaan);
        $this->assertTrue($periode->lock_form_kesediaan);

        // 3. Super Admin updates settings (open form, unlock form)
        $adminPostResponse = $this->actingAs($superAdmin)->post(route('master.kesediaan-dosen.settings'), [
            'show_form_kesediaan' => 1,
            'lock_form_kesediaan' => 0,
        ]);
        $adminPostResponse->assertRedirect();
        
        $periode->refresh();
        $this->assertTrue($periode->show_form_kesediaan);
        $this->assertFalse($periode->lock_form_kesediaan);

        // 4. Koordinator can toggle individual Dosen form access
        $this->assertTrue($dosenObj->can_fill_kesediaan);
        
        $toggleResponse = $this->actingAs($koordinator)->post(route('master.kesediaan-dosen.toggle-access', $dosenObj->id));
        $toggleResponse->assertRedirect();
        
        $dosenObj->refresh();
        $this->assertFalse($dosenObj->can_fill_kesediaan);

        // Toggle back using Super Admin
        $toggleResponse2 = $this->actingAs($superAdmin)->post(route('master.kesediaan-dosen.toggle-access', $dosenObj->id));
        $toggleResponse2->assertRedirect();
        
        $dosenObj->refresh();
        $this->assertTrue($dosenObj->can_fill_kesediaan);
    }
}

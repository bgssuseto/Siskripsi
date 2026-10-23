<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProfilKalenderTest extends TestCase
{
    use DatabaseTransactions;

    private Dosen $dosen;
    private User $userDosen;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $this->dosen = Dosen::create([
            'nama_dosen' => 'Dosen Profil, S.Kom., M.Kom', 'nidn' => 'PRF' . substr(uniqid(), -7),
            'jabatan_fungsional' => 'Lektor', 'kepakaran' => 'Data Mining',
        ]);
        $this->userDosen = User::create([
            'name' => $this->dosen->nama_dosen, 'email' => 'profil-dosen-' . uniqid() . '@test.id',
            'password' => bcrypt('password'), 'role' => User::ROLE_DOSEN, 'dosen_id' => $this->dosen->id,
        ]);
    }

    public function test_profil_dosen_menampilkan_dan_menyimpan_data_master_dosen(): void
    {
        $this->actingAs($this->userDosen)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Data Master Dosen')
            ->assertSee('Lektor')
            ->assertSee($this->dosen->nidn)
            ->assertSee('Ringkasan Keterlibatan');

        $nidnBaru = 'PRN' . substr(uniqid(), -7);
        $emailBaru = 'baru-' . uniqid() . '@test.id';
        $this->actingAs($this->userDosen)->patch(route('profile.update'), [
            'name' => 'Dr. Dosen Profil, S.Kom., M.Kom', 'email' => $emailBaru, 'no_hp' => '081234567890',
            'nidn' => $nidnBaru, 'kepakaran' => 'Machine Learning',
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('success');

        $d = $this->dosen->fresh();
        $this->assertSame('Dr. Dosen Profil, S.Kom., M.Kom', $d->nama_dosen);
        $this->assertSame($nidnBaru, $d->nidn);
        $this->assertSame($emailBaru, $d->email);
        $this->assertSame('081234567890', $d->no_wa);
        $this->assertSame('Machine Learning', $d->kepakaran);
        $this->assertSame('Lektor', $d->jabatan_fungsional); // tidak bisa diubah dari profil
    }

    public function test_nidn_dosen_lain_ditolak(): void
    {
        $lain = Dosen::create(['nama_dosen' => 'Dosen Lain', 'nidn' => 'PRL' . substr(uniqid(), -7)]);

        $this->actingAs($this->userDosen)->patch(route('profile.update'), [
            'name' => $this->dosen->nama_dosen, 'email' => $this->userDosen->email, 'nidn' => $lain->nidn,
        ])->assertSessionHasErrors('nidn');

        $this->assertNotSame($lain->nidn, $this->dosen->fresh()->nidn);
    }

    public function test_profil_mahasiswa_dan_admin_tampil(): void
    {
        $mhs = User::create(['name' => 'MHS PROFIL', 'email' => 'mhs-prf-' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_MAHASISWA, 'nim' => 'PRM' . substr(uniqid(), -6)]);
        $this->actingAs($mhs)->get(route('profile.edit'))->assertOk()
            ->assertSee('Status Tugas Akhir')->assertSee('Belum mendaftar');

        $admin = User::create(['name' => 'Admin Profil', 'email' => 'adm-prf-' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $this->actingAs($admin)->get(route('profile.edit'))->assertOk()->assertSee('Hak Akses');
    }

    public function test_kalender_dosen_memakai_bingkai_baru_dan_event_berjam(): void
    {
        $periode = Periode::create(['nama_periode' => 'Periode Kalender ' . uniqid(), 'aktif' => true]);
        $ruang = Ruang::create(['kode_ruangan' => 'KAL' . substr(uniqid(), -5), 'nama_ruangan' => 'R', 'status' => 'siap_digunakan']);
        Sidang::create([
            'nim' => 'KAL' . substr(uniqid(), -6), 'nama_mahasiswa' => 'Mhs Kalender', 'judul_skripsi' => 'J',
            'jenis_tugas_akhir' => 'jurnal', 'verifikasi_status' => 'disetujui', 'periode_id' => $periode->id,
            'dosen_pembimbing_utama_id' => $this->dosen->id, 'ketua_penguji_id' => $this->dosen->id,
            'tanggal' => now()->addDays(5)->format('Y-m-d'), 'jam' => '09.00 - 10.00', 'ruang_id' => $ruang->id,
        ]);

        $res = $this->actingAs($this->userDosen)->get(route('dosen.kalender'));
        $res->assertOk()->assertSee('Perbesar')->assertSee('cal-frame', false);

        $events = $res->viewData('calendarEvents')->values();
        $this->assertStringContainsString('T09:00:00', $events->first()['start']);
        $this->assertStringContainsString('T10:00:00', $events->first()['end']);
        $this->assertSame('Sidang Jurnal', $events->first()['extendedProps']['jenis']);
    }
}

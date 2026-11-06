<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Berkas yang ditolak / masih menunggu verifikasi tidak boleh masuk menu
 * Data Sempro/Skripsi maupun Penjadwalan Sempro/Skripsi, dan tidak bisa dijadwalkan.
 */
class BerkasDitolakTidakTerjadwalTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Periode $periode;
    private Dosen $dosen;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();
        $this->admin = User::create(['name' => 'Admin Tolak', 'email' => 'tolak' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $this->periode = Periode::create(['nama_periode' => 'Periode Tolak ' . uniqid(), 'aktif' => false]);
        $this->dosen = Dosen::create(['nama_dosen' => 'Dosen Tolak', 'nidn' => 'TLK' . substr(uniqid(), -7)]);
    }

    private function sidang(string $jenis, string $status, string $nama): Sidang
    {
        return Sidang::create([
            'nim' => 'TLK' . substr(uniqid(), -7), 'nama_mahasiswa' => $nama, 'judul_skripsi' => 'Judul',
            'jenis_tugas_akhir' => $jenis, 'verifikasi_status' => $status, 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => now()->toDateString(), 'dosen_pembimbing_utama_id' => $this->dosen->id,
        ]);
    }

    public function test_berkas_ditolak_dan_menunggu_tidak_tampil_di_menu_data_dan_jadwal(): void
    {
        foreach ([['sempro', ['master.sempro.index', 'jadwal-sempro.index']], ['sidang', ['master.skripsi.index', 'jadwal-ujian.index']]] as [$jenis, $routes]) {
            $tgl = now()->addDays(5)->toDateString();
            $ok = $this->sidang($jenis, 'disetujui', 'Mhs Disetujui');
            $tolak = $this->sidang($jenis, 'ditolak', 'Mhs Ditolak');
            $tunggu = $this->sidang($jenis, 'menunggu', 'Mhs Menunggu');
            // Sudah punya jadwal lalu ditolak → juga tidak boleh muncul di kalender.
            $tolak->update(['tanggal' => $tgl, 'jam' => '08.00 - 09.00']);

            foreach ($routes as $route) {
                $this->actingAs($this->admin)
                    ->get(route($route, ['periode_id' => $this->periode->id]))
                    ->assertOk()
                    ->assertViewHas('sidangs', fn ($p) => collect($p->items())->pluck('id')->all() === [$ok->id])
                    ->assertViewHas('calendarEvents', fn ($ev) => !collect($ev)->pluck('id')->intersect([$tolak->id, $tunggu->id])->count());
            }
        }
    }

    public function test_berkas_ditolak_tidak_bisa_dijadwalkan(): void
    {
        $ruang = Ruang::create(['kode_ruangan' => 'TLK' . substr(uniqid(), -4), 'nama_ruangan' => 'R', 'status' => Ruang::STATUS_SIAP]);
        $tgl = now()->addDays(3)->toDateString();

        $sempro = $this->sidang('sempro', 'ditolak', 'Mhs Sempro Ditolak');
        $this->actingAs($this->admin)
            ->postJson(route('jadwal.sempro.jadwalkan', $sempro), ['tanggal' => $tgl, 'jam' => '08.00 - 09.00', 'ruang_id' => $ruang->id])
            ->assertStatus(422)->assertJsonPath('message', Sidang::PESAN_BELUM_TERVERIFIKASI);
        $this->assertNull($sempro->fresh()->tanggal);

        $skripsi = $this->sidang('sidang', 'ditolak', 'Mhs Skripsi Ditolak');
        $this->actingAs($this->admin)
            ->postJson(route('jadwal.skripsi.jadwalkan', $skripsi), ['tanggal' => $tgl, 'jam' => '08.00 - 09.00', 'ruang_id' => $ruang->id,
                'ketua_penguji_id' => $this->dosen->id, 'anggota_penguji_1_id' => $this->dosen->id, 'anggota_penguji_2_id' => $this->dosen->id])
            ->assertStatus(422)->assertJsonPath('message', Sidang::PESAN_BELUM_TERVERIFIKASI);
        $this->assertNull($skripsi->fresh()->tanggal);

        $this->actingAs($this->admin)
            ->postJson(route('jadwal.sempro.bulk-jadwalkan'), ['ids' => [$sempro->id], 'tanggal' => $tgl,
                'jam_mulai' => '08.00', 'durasi_menit' => 60, 'ruang_id' => $ruang->id])
            ->assertOk()->assertJsonCount(0, 'berhasil')->assertJsonCount(1, 'gagal');
        $this->assertNull($sempro->fresh()->tanggal);
    }
}

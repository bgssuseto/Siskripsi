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

    public function test_menolak_berkas_mengosongkan_jadwal_dan_slot_tidak_dianggap_bentrok(): void
    {
        $ruang = Ruang::create(['kode_ruangan' => 'TLK' . substr(uniqid(), -4), 'nama_ruangan' => 'R', 'status' => Ruang::STATUS_SIAP]);
        $tgl = now()->addDays(4)->toDateString();
        $jadwal = ['tanggal' => $tgl, 'jam' => '08.00 - 09.00', 'ruang_id' => $ruang->id];

        $a = $this->sidang('sempro', 'disetujui', 'Mhs A');
        $a->update($jadwal);
        $b = $this->sidang('sempro', 'disetujui', 'Mhs B');
        $b->update(['jam' => '09.00 - 10.00'] + $jadwal);
        $sudahUjian = $this->sidang('sempro', 'disetujui', 'Mhs Sudah Ujian');
        $sudahUjian->update(['tanggal' => now()->subDays(3)->toDateString(), 'jam' => '10.00 - 11.00', 'status_ujian' => 'lulus']);

        $this->actingAs($this->admin)
            ->postJson(route('pendaftaran.verifikasi', $a), ['verifikasi_status' => 'ditolak', 'verifikasi_komentar' => 'Berkas kurang'])
            ->assertOk();
        $a->refresh();
        $this->assertNull($a->tanggal);
        $this->assertNull($a->ruang_id);

        // Bulk: jadwal B dikosongkan, yang sudah punya hasil ujian tidak disentuh.
        $this->actingAs($this->admin)
            ->postJson(route('pendaftaran.bulk-verifikasi'), ['ids' => [$b->id, $sudahUjian->id], 'verifikasi_status' => 'ditolak', 'verifikasi_komentar' => 'x'])
            ->assertOk();
        $this->assertNull($b->fresh()->tanggal);
        $this->assertNotNull($sudahUjian->fresh()->tanggal);

        // Data lama: pendaftaran ditolak yang masih memegang jadwal tidak dihitung bentrok.
        $lama = $this->sidang('sempro', 'ditolak', 'Mhs Ditolak Lama');
        $lama->update($jadwal);
        $c = $this->sidang('sempro', 'disetujui', 'Mhs C');
        $hasil = \App\Services\SidangConflictService::checkConflicts(
            $jadwal + ['jenis_tugas_akhir' => 'sempro', 'dosen_pembimbing_utama_id' => $this->dosen->id], $c->id);
        $this->assertSame([], $hasil);

        // Pembanding: pendaftaran disetujui di jam yang sama (ruang lain, dosen sama) tetap bentrok.
        $ruang2 = Ruang::create(['kode_ruangan' => 'TLK' . substr(uniqid(), -4), 'nama_ruangan' => 'R2', 'status' => Ruang::STATUS_SIAP]);
        $this->sidang('sempro', 'disetujui', 'Mhs D')->update(['ruang_id' => $ruang2->id] + $jadwal);
        $this->assertNotEmpty(\App\Services\SidangConflictService::checkConflicts(
            $jadwal + ['jenis_tugas_akhir' => 'sempro', 'dosen_pembimbing_utama_id' => $this->dosen->id], $c->id));
    }

    public function test_migrasi_membersihkan_jadwal_lama_milik_berkas_ditolak(): void
    {
        $ruang = Ruang::create(['kode_ruangan' => 'TLK' . substr(uniqid(), -4), 'nama_ruangan' => 'R', 'status' => Ruang::STATUS_SIAP]);
        $ditolak = $this->sidang('sempro', 'ditolak', 'Mhs Lama Ditolak');
        $ditolak->update(['tanggal' => now()->addDays(6)->toDateString(), 'jam' => '13.00 - 14.00', 'ruang_id' => $ruang->id]);
        $sah = $this->sidang('sempro', 'disetujui', 'Mhs Lama Sah');
        $sah->update(['tanggal' => now()->addDays(6)->toDateString(), 'jam' => '14.00 - 15.00', 'ruang_id' => $ruang->id]);

        $migrasi = require database_path('migrations/2026_11_13_000000_kosongkan_jadwal_pendaftaran_ditolak.php');
        $migrasi->up();

        $this->assertNull($ditolak->fresh()->tanggal);
        $this->assertNull($ditolak->fresh()->ruang_id);
        $this->assertNotNull($sah->fresh()->tanggal);
        $this->assertDatabaseHas('activity_logs', ['subject_id' => $ditolak->id, 'action' => 'reset_jadwal']);
    }
}

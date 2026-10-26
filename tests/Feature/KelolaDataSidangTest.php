<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Models\User;
use App\Support\TanggalIndo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** Aksi bersama Sempro & Skripsi (trait KelolaDataSidang). */
class KelolaDataSidangTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Periode $periode;
    private Dosen $dosen;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();
        $this->admin = User::create(['name' => 'Admin Kelola', 'email' => 'kelola' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $this->periode = Periode::create(['nama_periode' => 'Periode Kelola ' . uniqid(), 'aktif' => false]);
        $this->dosen = Dosen::create(['nama_dosen' => 'Dosen Kelola', 'nidn' => 'KLA' . substr(uniqid(), -7)]);
    }

    private function sidang(string $jenis, array $attr = []): Sidang
    {
        return Sidang::create($attr + [
            'nim' => 'KLA' . substr(uniqid(), -7), 'nama_mahasiswa' => 'Mhs Kelola', 'judul_skripsi' => 'Judul',
            'jenis_tugas_akhir' => $jenis, 'verifikasi_status' => 'disetujui', 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => now()->toDateString(), 'dosen_pembimbing_utama_id' => $this->dosen->id,
        ]);
    }

    public function test_hapus_terpilih_hanya_menghapus_jenis_halamannya(): void
    {
        $sempro = $this->sidang('sempro');
        $skripsi = $this->sidang('sidang');

        // Dari halaman Sempro, id skripsi ikut dikirim → tidak boleh terhapus.
        $this->actingAs($this->admin)
            ->deleteJson(route('master.sempro.bulk-destroy'), ['ids' => [$sempro->id, $skripsi->id]])
            ->assertOk()->assertJsonPath('message', '🗑️ 1 data sempro berhasil dihapus.');

        $this->assertNull(Sidang::find($sempro->id));
        $this->assertNotNull(Sidang::find($skripsi->id));

        $this->actingAs($this->admin)
            ->deleteJson(route('master.skripsi.bulk-destroy'), ['ids' => [$skripsi->id]])
            ->assertOk()->assertJsonPath('message', '🗑️ 1 data skripsi/jurnal berhasil dihapus.');
        $this->assertNull(Sidang::find($skripsi->id));
    }

    public function test_geser_jadwal_ditolak_bila_bentrok_ruang_dan_dicatat_bila_aman(): void
    {
        $ruang = Ruang::create(['kode_ruangan' => 'KLA' . substr(uniqid(), -4), 'nama_ruangan' => 'R', 'status' => Ruang::STATUS_SIAP]);
        $tgl = now()->addDays(3)->toDateString();
        $this->sidang('sempro', ['tanggal' => $tgl, 'jam' => '08.00 - 09.00', 'ruang_id' => $ruang->id]);
        $geser = $this->sidang('sempro', ['tanggal' => $tgl, 'jam' => '10.00 - 11.00', 'ruang_id' => $ruang->id,
            'dosen_pembimbing_utama_id' => Dosen::create(['nama_dosen' => 'Dosen Lain', 'nidn' => 'KLB' . substr(uniqid(), -7)])->id]);

        $this->actingAs($this->admin)
            ->patchJson(route('jadwal.sempro.reschedule', $geser), ['tanggal' => $tgl, 'jam' => '08.30 - 09.30'])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame('10.00 - 11.00', $geser->fresh()->jam);

        $this->actingAs($this->admin)
            ->patchJson(route('jadwal.sempro.reschedule', $geser), ['tanggal' => $tgl, 'jam' => '13.00 - 14.00'])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame('13.00 - 14.00', $geser->fresh()->jam);
    }

    public function test_form_impor_tampil(): void
    {
        $this->actingAs($this->admin)->get(route('master.sempro.import.form'))->assertOk();
        $this->actingAs($this->admin)->get(route('master.skripsi.import.form'))->assertOk();
    }

    public function test_pembaca_tanggal_excel(): void
    {
        $this->assertSame('2026-06-10', TanggalIndo::dariExcel('Rabu, 10 Juni 2026'));
        $this->assertSame('2026-07-03', TanggalIndo::dariExcel('3 Juli 2026'));
        $this->assertSame('2026-06-10', TanggalIndo::dariExcel('2026-06-10'));
        $this->assertSame('2026-06-25', TanggalIndo::dariExcel('25/06/2026'));
        $this->assertSame('2026-01-01', TanggalIndo::dariExcel(46023)); // nomor seri Excel
        $this->assertNull(TanggalIndo::dariExcel(''));
    }
}

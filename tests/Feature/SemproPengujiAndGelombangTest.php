<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\Sidang;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Dua aturan baru pada Sidang (lihat App\Models\Sidang):
 *  1. Dewan penguji Sempro SELALU = pembimbing mahasiswa sendiri (tidak ada
 *     penguji eksternal untuk sempro).
 *  2. Kolom `gelombang` bisa dihitung ulang retroaktif lewat
 *     recomputeGelombangForPeriode() — dipakai saat gelombang (Master
 *     Gelombang) dibuat/diedit SETELAH mahasiswa sudah terdaftar.
 */
class SemproPengujiAndGelombangTest extends TestCase
{
    use DatabaseTransactions;

    private Periode $periode;
    private Dosen $utama;
    private Dosen $pendamping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periode = Periode::create(['nama_periode' => 'Periode Uji Penguji Sempro', 'aktif' => false]);
        $this->utama = Dosen::firstOrCreate(['nidn' => '0011111111'], ['nama_dosen' => 'Dr. Utama Uji']);
        $this->pendamping = Dosen::firstOrCreate(['nidn' => '0022222222'], ['nama_dosen' => 'Dr. Pendamping Uji']);
    }

    private function daftarSempro(array $attrs = []): Sidang
    {
        return Sidang::create(array_merge([
            'nim' => '2024PENGUJI01', 'nama_mahasiswa' => 'Mahasiswa Uji Penguji',
            'judul_skripsi' => 'Judul Uji Penguji Sempro',
            'dosen_pembimbing_utama_id' => $this->utama->id,
            'dosen_pembimbing_pendamping_id' => $this->pendamping->id,
            'jenis_tugas_akhir' => 'sempro', 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => now()->format('Y-m-d'), 'verifikasi_status' => 'disetujui',
        ], $attrs));
    }

    public function test_dewan_penguji_sempro_otomatis_mengikuti_pembimbing(): void
    {
        $sidang = $this->daftarSempro();

        $this->assertSame($this->utama->id, $sidang->ketua_penguji_id);
        $this->assertSame($this->pendamping->id, $sidang->anggota_penguji_1_id);
        $this->assertNull($sidang->anggota_penguji_2_id);
    }

    public function test_dewan_penguji_sempro_tanpa_pembimbing_pendamping(): void
    {
        $sidang = $this->daftarSempro(['dosen_pembimbing_pendamping_id' => null]);

        $this->assertSame($this->utama->id, $sidang->ketua_penguji_id);
        $this->assertNull($sidang->anggota_penguji_1_id);
    }

    public function test_dewan_penguji_sempro_ikut_berubah_saat_pembimbing_direvisi(): void
    {
        $sidang = $this->daftarSempro();
        $penggantiUtama = Dosen::firstOrCreate(['nidn' => '0033333333'], ['nama_dosen' => 'Dr. Pengganti Uji']);

        $sidang->update(['dosen_pembimbing_utama_id' => $penggantiUtama->id]);

        // Penguji lama TIDAK boleh nyangkut ke pembimbing sebelumnya.
        $this->assertSame($penggantiUtama->id, $sidang->fresh()->ketua_penguji_id);
    }

    public function test_input_manual_ketua_penguji_pada_skripsi_tetap_dihormati(): void
    {
        // Regresi: aturan baru ini HANYA untuk sempro — ketua_penguji_id/anggota_penguji_1_id
        // skripsi tetap harus bisa diisi manual/oleh Asisten Plotting dengan dosen lain
        // (bukan ikut auto-mirror ke pembimbing seperti sempro).
        $penguji1 = Dosen::firstOrCreate(['nidn' => '0044444444'], ['nama_dosen' => 'Dr. Penguji Eksternal']);
        $penguji2 = Dosen::firstOrCreate(['nidn' => '0055555555'], ['nama_dosen' => 'Dr. Penguji Eksternal Dua']);

        $sidang = Sidang::create([
            'nim' => '2024PENGUJI02', 'nama_mahasiswa' => 'Mahasiswa Uji Skripsi',
            'judul_skripsi' => 'Judul Uji Penguji Skripsi',
            'dosen_pembimbing_utama_id' => $this->utama->id,
            'jenis_tugas_akhir' => 'sidang', 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => now()->format('Y-m-d'), 'verifikasi_status' => 'disetujui',
            'ketua_penguji_id' => $penguji1->id, 'anggota_penguji_1_id' => $penguji2->id,
        ]);

        $this->assertSame($penguji1->id, $sidang->ketua_penguji_id);
        $this->assertSame($penguji2->id, $sidang->anggota_penguji_1_id);
        // anggota_penguji_2 tetap default ke pembimbing utama seperti sebelumnya.
        $this->assertSame($this->utama->id, $sidang->anggota_penguji_2_id);
    }

    public function test_recompute_gelombang_menandai_ulang_mahasiswa_yang_daftar_sebelum_gelombang_dibuat(): void
    {
        $sidang = $this->daftarSempro(['tanggal_pendaftaran' => '2026-10-05']);
        $this->assertNull($sidang->gelombang); // belum ada gelombang saat mendaftar

        PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 1,
            'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-10',
        ]);

        // computeGelombang() saat create Sidang tidak retroaktif — tanpa recompute,
        // seharusnya tetap null di sini.
        $this->assertNull($sidang->fresh()->gelombang);

        Sidang::recomputeGelombangForPeriode($this->periode->id, 'sempro');

        $this->assertSame(1, $sidang->fresh()->gelombang);
    }

    public function test_recompute_gelombang_menghapus_tag_saat_gelombang_dihapus(): void
    {
        $wave = PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 2,
            'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-10',
        ]);
        $sidang = $this->daftarSempro(['tanggal_pendaftaran' => '2026-10-05']);
        $this->assertSame(2, $sidang->gelombang);

        $wave->delete();
        Sidang::recomputeGelombangForPeriode($this->periode->id, 'sempro');

        $this->assertNull($sidang->fresh()->gelombang);
    }

    public function test_membuat_gelombang_lewat_periode_controller_langsung_menandai_mahasiswa_lama(): void
    {
        $superAdmin = \App\Models\User::create([
            'name' => 'Admin Uji', 'email' => 'admin-uji-gelombang@example.test',
            'password' => bcrypt('password'), 'role' => \App\Models\User::ROLE_SUPER_ADMIN,
        ]);
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $sidang = $this->daftarSempro(['tanggal_pendaftaran' => '2026-11-05']);
        $this->assertNull($sidang->gelombang);

        $this->actingAs($superAdmin)->post(route('master.pendaftaran-periode.store'), [
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 3,
            'tanggal_mulai' => '2026-11-01', 'tanggal_selesai' => '2026-11-10',
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, $sidang->fresh()->gelombang);
    }
}

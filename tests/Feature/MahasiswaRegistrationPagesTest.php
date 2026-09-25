<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Halaman pendaftaran Sempro & Skripsi mahasiswa benar-benar di-render (bukan sekadar
 * `php -l`), supaya variabel yang dipakai sebelum didefinisikan di Blade langsung ketahuan.
 */
class MahasiswaRegistrationPagesTest extends TestCase
{
    use DatabaseTransactions;

    private User $student;
    private Periode $periode;
    private Dosen $dosen;

    protected function setUp(): void
    {
        parent::setUp();

        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $this->student = User::create([
            'name' => 'Mahasiswa Halaman', 'email' => 'mhs-halaman@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_MAHASISWA, 'nim' => '2024PAGE01',
        ]);

        // Satu-satunya periode aktif + gelombang sempro & skripsi yang sedang terbuka,
        // supaya modal pendaftaran (hanya tampil saat gelombang terbuka) ikut ter-render.
        Periode::query()->update(['aktif' => false]);
        $this->periode = Periode::create(['nama_periode' => 'Periode Uji Halaman', 'aktif' => true]);
        foreach (['sempro', 'skripsi'] as $jenis) {
            PendaftaranPeriode::create([
                'periode_id' => $this->periode->id, 'jenis' => $jenis, 'gelombang' => 1,
                'tanggal_mulai' => now()->subDay()->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(7)->format('Y-m-d'),
            ]);
        }

        $this->dosen = Dosen::firstOrCreate(['nidn' => '0012058501'], ['nama_dosen' => 'Arief Susanto, ST., M.Kom']);
    }

    private function register(string $jenis, array $attrs = []): Sidang
    {
        return Sidang::create(array_merge([
            'nim' => $this->student->nim, 'nama_mahasiswa' => $this->student->name,
            'judul_skripsi' => 'Judul Uji Halaman', 'dosen_pembimbing_utama_id' => $this->dosen->id,
            'jenis_tugas_akhir' => $jenis, 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => now()->format('Y-m-d'), 'verifikasi_status' => 'disetujui',
        ], $attrs));
    }

    public function test_halaman_skripsi_tampil_untuk_mahasiswa_yang_belum_mendaftar(): void
    {
        // Regresi: x-data pembungkus memakai $mySidang sebelum blok @php mendefinisikannya.
        $this->actingAs($this->student)
            ->get(route('mahasiswa.skripsi.index'))
            ->assertOk()
            ->assertSee("jenisTaSelected: ''", false);
    }

    public function test_halaman_skripsi_menampilkan_form_jurnal_dan_info_loa_saat_modal_terbuka(): void
    {
        $this->register('sempro'); // modal skripsi hanya tampil bila sudah pernah daftar sempro

        $this->actingAs($this->student)
            ->get(route('mahasiswa.skripsi.index'))
            ->assertOk()
            ->assertSee('Kategori Jurnal')
            ->assertSee('Link Jurnal')
            ->assertSee('Sinta 1')
            ->assertSee('Seminar Internasional')
            ->assertSee('LOA (Letter of Acceptance)');
    }

    public function test_halaman_skripsi_memilih_ulang_jalur_dan_kategori_jurnal_dari_pendaftaran_sebelumnya(): void
    {
        $this->register('sempro');
        $this->register('jurnal', [
            'verifikasi_status' => 'ditolak',
            'kategori_jurnal'   => 'Q2',
            'link_jurnal'       => 'https://jurnal.example/artikel-saya',
        ]);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.skripsi.index'))
            ->assertOk()
            ->assertSee("jenisTaSelected: 'jurnal'", false)
            ->assertSee('<option value="Q2" selected>Q2</option>', false)
            ->assertSee('https://jurnal.example/artikel-saya', false);
    }

    public function test_transkrip_wajib_dari_baak_ditegaskan_di_halaman_skripsi_dan_sempro(): void
    {
        $wajibBaak = '<strong>WAJIB</strong> yang dikeluarkan oleh <strong>BAAK</strong>';

        $this->actingAs($this->student)
            ->get(route('mahasiswa.skripsi.index'))
            ->assertOk()
            ->assertSee('Scan transkrip nilai ' . $wajibBaak, false);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.sempro.index'))
            ->assertOk()
            ->assertSee('Transkrip Nilai Lengkap')
            ->assertSee('Transkrip Nilai lengkap ' . $wajibBaak, false)
            ->assertDontSee('Transkip');
    }

    public function test_halaman_sempro_tampil_dengan_pendaftaran_sebelumnya(): void
    {
        $this->register('sempro', ['verifikasi_status' => 'ditolak', 'verifikasi_komentar' => 'PDF tidak terbaca']);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.sempro.index'))
            ->assertOk()
            ->assertSee('Revisi Pendaftaran Sempro');
    }
}

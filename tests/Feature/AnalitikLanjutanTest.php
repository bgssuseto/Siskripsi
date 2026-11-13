<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use App\Services\AnalitikLanjutanService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AnalitikLanjutanTest extends TestCase
{
    use DatabaseTransactions;

    private Periode $periode;
    private Dosen $dosen;
    private Dosen $lain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->periode = Periode::create(['nama_periode' => 'Periode Analitik ' . uniqid(), 'aktif' => false]);
        $this->dosen = Dosen::create(['nama_dosen' => 'Dosen Analitik', 'nidn' => 'ANL' . substr(uniqid(), -7)]);
        $this->lain = Dosen::create(['nama_dosen' => 'Dosen Analitik Lain', 'nidn' => 'ANX' . substr(uniqid(), -7)]);
    }

    private function sidang(string $nim, array $atribut): Sidang
    {
        $s = Sidang::create($atribut + [
            'nim' => $nim, 'nama_mahasiswa' => 'Mhs ' . $nim, 'judul_skripsi' => 'Judul',
            'jenis_tugas_akhir' => 'sempro', 'verifikasi_status' => 'disetujui', 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => now()->subDays(40)->toDateString(),
        ]);
        if (isset($atribut['created_at'])) {
            $s->forceFill(['created_at' => $atribut['created_at']])->saveQuietly();
        }
        return $s;
    }

    private function nim(string $awal = '2099'): string
    {
        return $awal . substr(uniqid(), -6);
    }

    private function nimDi(array $macet, string $kategori): array
    {
        return $macet[$kategori]['mahasiswa']->pluck('nim')->all();
    }

    public function test_waktu_tempuh_lama_studi_dan_mahasiswa_macet(): void
    {
        $sebelum = (new AnalitikLanjutanService())->waktuTempuh();

        // Lulus Sempro lalu lulus Sidang 120 hari kemudian; angkatan 2021 → ±5 tahun.
        $lulus = $this->nim('2021');
        $tglSidang = now()->subDays(10);
        $this->sidang($lulus, ['tanggal' => $tglSidang->copy()->subDays(120)->toDateString(), 'status_ujian' => 'lulus']);
        $this->sidang($lulus, ['jenis_tugas_akhir' => 'sidang', 'tanggal' => $tglSidang->toDateString(), 'status_ujian' => 'lulus']);

        $sesudah = (new AnalitikLanjutanService())->waktuTempuh();
        $this->assertSame($sebelum['tempuh']['jumlah'] + 1, $sesudah['tempuh']['jumlah']);
        $this->assertSame($sebelum['lama_studi']['jumlah'] + 1, $sesudah['lama_studi']['jumlah']);

        // Kategori macet
        $menunggu = $this->nim();
        $this->sidang($menunggu, ['verifikasi_status' => 'menunggu', 'created_at' => now()->subDays(5)]);
        $menungguBaru = $this->nim();
        $this->sidang($menungguBaru, ['verifikasi_status' => 'menunggu', 'created_at' => now()->subDay()]);
        $belumJadwal = $this->nim();
        $this->sidang($belumJadwal, ['verifikasi_tanggal' => now()->subDays(20)]);
        $ditolak = $this->nim();
        $this->sidang($ditolak, ['verifikasi_status' => 'ditolak', 'verifikasi_tanggal' => now()->subDays(20)]);
        $tidakLulus = $this->nim();
        $this->sidang($tidakLulus, ['tanggal' => now()->subDays(40)->toDateString(), 'status_ujian' => 'tidak_lulus']);
        $semproLama = $this->nim();
        $this->sidang($semproLama, ['tanggal' => now()->subDays(200)->toDateString(), 'status_ujian' => 'lulus']);
        // Lulus sempro lalu sudah daftar skripsi → tidak macet di kategori sempro.
        $sudahLanjut = $this->nim();
        $this->sidang($sudahLanjut, ['tanggal' => now()->subDays(200)->toDateString(), 'status_ujian' => 'lulus']);
        $this->sidang($sudahLanjut, ['jenis_tugas_akhir' => 'sidang', 'tanggal' => now()->addDays(3)->toDateString()]);

        $macet = (new AnalitikLanjutanService())->macet();
        $this->assertContains($menunggu, $this->nimDi($macet, 'menunggu'));
        $this->assertNotContains($menungguBaru, $this->nimDi($macet, 'menunggu'));
        $this->assertContains($belumJadwal, $this->nimDi($macet, 'belum_jadwal'));
        $this->assertContains($ditolak, $this->nimDi($macet, 'ditolak'));
        $this->assertContains($tidakLulus, $this->nimDi($macet, 'tidak_lulus'));
        $this->assertContains($semproLama, $this->nimDi($macet, 'sempro_ke_sidang'));
        $semuaMacet = collect($macet)->flatMap(fn ($k) => $k['mahasiswa']->pluck('nim'))->all();
        $this->assertNotContains($sudahLanjut, $semuaMacet);
        $this->assertNotContains($lulus, $semuaMacet);
    }

    public function test_beban_dosen_per_semester_dan_halaman_analitik(): void
    {
        // Bimbing 1 (utama) + menguji 1 skripsi orang lain; menguji mahasiswa bimbingan sendiri tidak dihitung dobel.
        $this->sidang($this->nim(), ['jenis_tugas_akhir' => 'sidang', 'dosen_pembimbing_utama_id' => $this->dosen->id, 'anggota_penguji_2_id' => $this->dosen->id]);
        $this->sidang($this->nim(), ['jenis_tugas_akhir' => 'sidang', 'dosen_pembimbing_utama_id' => $this->lain->id, 'ketua_penguji_id' => $this->dosen->id]);
        // Pendaftaran ditolak tidak dihitung.
        $this->sidang($this->nim(), ['jenis_tugas_akhir' => 'sidang', 'verifikasi_status' => 'ditolak', 'ketua_penguji_id' => $this->dosen->id]);

        $beban = (new AnalitikLanjutanService())->bebanPerSemester();
        $this->assertTrue($beban['periodes']->contains('id', $this->periode->id));
        $baris = $beban['baris']->firstWhere('id', $this->dosen->id);
        $this->assertSame(['bimbing' => 1, 'uji' => 1, 'total' => 2], $baris['per_periode'][$this->periode->id]);

        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();
        $admin = User::create(['name' => 'Admin Analitik', 'email' => 'anl' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $this->actingAs($admin)->get(route('administrasi.analitik.index'))
            ->assertOk()
            ->assertSee('Analitik Lanjutan')
            ->assertSee('Mahasiswa Perlu Perhatian')
            ->assertSee('Beban Dosen per Semester')
            ->assertSee('Dosen Analitik');
    }
}

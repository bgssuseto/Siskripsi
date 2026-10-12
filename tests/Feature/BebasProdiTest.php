<?php

namespace Tests\Feature;

use App\Models\BebasProdi;
use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BebasProdiTest extends TestCase
{
    use DatabaseTransactions;

    private User $student;
    private User $admin;
    private Periode $periode;
    private Dosen $dosen;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $this->student = User::create([
            'name' => 'OESAMA HAKIM ADSANDI', 'email' => 'mhs-bp@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_MAHASISWA, 'nim' => '2024BP0001',
        ]);
        $this->admin = User::create([
            'name' => 'Admin BP', 'email' => 'admin-bp@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $this->periode = Periode::create(['nama_periode' => 'Periode Uji BP', 'aktif' => false]);
        $this->dosen = Dosen::firstOrCreate(['nidn' => '0012058501'], ['nama_dosen' => 'Arief Susanto, ST., M.Kom']);
    }

    private function sidang(string $jenis, ?string $statusUjian): Sidang
    {
        return Sidang::create([
            'nim' => $this->student->nim, 'nama_mahasiswa' => $this->student->name,
            'judul_skripsi' => 'Judul Uji BP', 'dosen_pembimbing_utama_id' => $this->dosen->id,
            'jenis_tugas_akhir' => $jenis, 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => now()->format('Y-m-d'), 'verifikasi_status' => 'disetujui',
            'status_ujian' => $statusUjian,
        ]);
    }

    private function luluskan(string $jenisSkripsi = 'sidang'): void
    {
        $this->sidang('sempro', 'lulus');
        $this->sidang($jenisSkripsi, 'lulus');
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'link_poster' => 'https://drive.google.com/file/d/poster',
            'link_linkedin' => 'https://www.linkedin.com/in/oesama',
            'follow_instagram' => '1',
            'follow_linkedin' => '1',
            'file_berkas' => UploadedFile::fake()->create('berkas.pdf', 500, 'application/pdf'),
        ], $extra);
    }

    public function test_halaman_terkunci_bila_belum_lulus_sempro_dan_skripsi(): void
    {
        $this->sidang('sempro', 'lulus');

        $this->actingAs($this->student)->get(route('mahasiswa.bebas-prodi.index'))
            ->assertOk()
            ->assertSee('belum terbuka')
            ->assertDontSee('Formulir Pengajuan Bebas Prodi');

        $this->actingAs($this->student)->post(route('mahasiswa.bebas-prodi.store'), $this->payload())
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('bebas_prodis', ['nim' => $this->student->nim]);
    }

    public function test_nim_bypass_bisa_mengajukan_tanpa_lulus(): void
    {
        config(['bebas_prodi.bypass_nims' => [$this->student->nim]]);

        $this->actingAs($this->student)->get(route('mahasiswa.bebas-prodi.index'))
            ->assertOk()->assertSee('Akun testing')->assertSee('Formulir Pengajuan Bebas Prodi');

        $this->actingAs($this->student)->post(route('mahasiswa.bebas-prodi.store'), $this->payload())
            ->assertRedirect(route('mahasiswa.bebas-prodi.index'));
        $this->assertDatabaseHas('bebas_prodis', ['nim' => $this->student->nim, 'jalur' => 'sidang']);
    }

    public function test_link_poster_wajib(): void
    {
        $this->luluskan();

        $this->actingAs($this->student)->post(route('mahasiswa.bebas-prodi.store'), $this->payload(['link_poster' => '']))
            ->assertSessionHasErrors('link_poster');
    }

    public function test_menu_bebas_prodi_muncul_di_sidebar_mahasiswa(): void
    {
        $this->actingAs($this->student)->get(route('mahasiswa.bebas-prodi.index'))
            ->assertOk()
            ->assertSee(route('mahasiswa.bebas-prodi.index'), false);
    }

    public function test_mahasiswa_lulus_bisa_mengajukan_dan_file_lebih_4mb_ditolak(): void
    {
        $this->luluskan();

        $this->actingAs($this->student)->get(route('mahasiswa.bebas-prodi.index'))
            ->assertOk()->assertSee('Formulir Pengajuan Bebas Prodi');

        $this->actingAs($this->student)->post(route('mahasiswa.bebas-prodi.store'), $this->payload([
            'file_berkas' => UploadedFile::fake()->create('besar.pdf', 5000, 'application/pdf'),
        ]))->assertSessionHasErrors('file_berkas');

        $this->actingAs($this->student)->post(route('mahasiswa.bebas-prodi.store'), $this->payload())
            ->assertRedirect(route('mahasiswa.bebas-prodi.index'));

        $p = BebasProdi::where('nim', $this->student->nim)->firstOrFail();
        $this->assertSame('menunggu', $p->status);
        $this->assertSame('sidang', $p->jalur);
        Storage::disk('local')->assertExists($p->file_berkas);
    }

    public function test_jalur_jurnal_wajib_link_jurnal(): void
    {
        $this->luluskan('jurnal');

        $this->actingAs($this->student)->post(route('mahasiswa.bebas-prodi.store'), $this->payload())
            ->assertSessionHasErrors('link_jurnal');
    }

    public function test_admin_setujui_menerbitkan_nomor_mulai_1268_dan_surat_bisa_dicetak(): void
    {
        $this->luluskan();
        $this->actingAs($this->student)->post(route('mahasiswa.bebas-prodi.store'), $this->payload());
        $p = BebasProdi::where('nim', $this->student->nim)->firstOrFail();

        $this->actingAs($this->student)->get(route('mahasiswa.bebas-prodi.cetak'))->assertNotFound();

        $this->actingAs($this->admin)->get(route('bebas-prodi.index'))->assertOk()->assertSee($this->student->nim);

        $this->actingAs($this->admin)->post(route('bebas-prodi.verifikasi', $p), [
            'status' => 'disetujui', 'tanggal_surat' => '2026-09-22',
        ])->assertSessionHas('success');

        $p->refresh();
        $expected = max(1268, (int) BebasProdi::where('id', '!=', $p->id)->max('nomor_surat') + 1);
        $this->assertSame($expected, $p->nomor_surat);
        $this->assertSame('disetujui', $p->status);

        $res = $this->actingAs($this->student)->get(route('mahasiswa.bebas-prodi.cetak'));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));

        $html = view('bebas-prodi.surat', [
            'p' => $p, 'nama' => 'Oesama Hakim Adsandi', 'tanggal' => '22 September 2026',
            'kaprodi' => config('bebas_prodi.kaprodi_nama'), 'kopBase64' => '', 'ttdBase64' => '',
        ])->render();
        $this->assertStringContainsString('NO:TI/BP/' . $expected, $html);
        $this->assertStringContainsString('Bebas Peminjaman Perangkat Lab TI', $html);
    }

    public function test_ditolak_bisa_revisi_dan_mahasiswa_lain_tidak_bisa_buka_berkas(): void
    {
        $this->luluskan();
        $this->actingAs($this->student)->post(route('mahasiswa.bebas-prodi.store'), $this->payload());
        $p = BebasProdi::where('nim', $this->student->nim)->firstOrFail();

        $this->actingAs($this->admin)->post(route('bebas-prodi.verifikasi', $p), ['status' => 'ditolak'])
            ->assertSessionHasErrors('komentar');
        $this->actingAs($this->admin)->post(route('bebas-prodi.verifikasi', $p), ['status' => 'ditolak', 'komentar' => 'Pengesahan belum TTD'])
            ->assertSessionHas('success');

        $this->actingAs($this->student)->post(route('mahasiswa.bebas-prodi.store'), $this->payload(['file_berkas' => null]))
            ->assertRedirect();
        $this->assertSame('menunggu', $p->fresh()->status);

        $other = User::create([
            'name' => 'Lain', 'email' => 'lain-bp@example.test', 'password' => bcrypt('x'),
            'role' => User::ROLE_MAHASISWA, 'nim' => '2024BP0002',
        ]);
        $this->actingAs($other)->get(route('bebas-prodi.berkas', $p))->assertForbidden();
        $this->actingAs($this->student)->get(route('bebas-prodi.berkas', $p))->assertOk();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use App\Services\SkPdfService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DosenSkTest extends TestCase
{
    use DatabaseTransactions;

    private Periode $periode;
    private Dosen $andi;
    private Dosen $budi;
    private User $userAndi;
    private User $userBudi;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $this->periode = Periode::create(['nama_periode' => 'Semester Gasal SK Dosen ' . uniqid(), 'aktif' => false]);
        $this->andi = Dosen::create(['nama_dosen' => 'Andi SK Dosen', 'nidn' => 'SKA' . uniqid()]);
        $this->budi = Dosen::create(['nama_dosen' => 'Budi SK Dosen', 'nidn' => 'SKB' . uniqid()]);

        $buatUser = fn (Dosen $d) => User::create([
            'name' => $d->nama_dosen, 'email' => 'sk-' . uniqid() . '@test.id', 'password' => bcrypt('password'),
            'role' => User::ROLE_DOSEN, 'dosen_id' => $d->id,
        ]);
        $this->userAndi = $buatUser($this->andi);
        $this->userBudi = $buatUser($this->budi);

        // Skripsi: Andi pembimbing utama + ketua penguji, Budi anggota penguji 1.
        Sidang::create([
            'nim' => 'SKD' . substr(uniqid(), -6), 'nama_mahasiswa' => 'Mhs Skripsi', 'judul_skripsi' => 'J',
            'jenis_tugas_akhir' => 'sidang', 'verifikasi_status' => 'disetujui', 'periode_id' => $this->periode->id,
            'dosen_pembimbing_utama_id' => $this->andi->id,
            'ketua_penguji_id' => $this->andi->id, 'anggota_penguji_1_id' => $this->budi->id,
        ]);
        // Sempro tidak ikut dihitung: Budi pembimbing utama sempro.
        Sidang::create([
            'nim' => 'SKS' . substr(uniqid(), -6), 'nama_mahasiswa' => 'Mhs Sempro', 'judul_skripsi' => 'J',
            'jenis_tugas_akhir' => 'sempro', 'verifikasi_status' => 'disetujui', 'periode_id' => $this->periode->id,
            'dosen_pembimbing_utama_id' => $this->budi->id,
        ]);
    }

    public function test_dosen_melihat_menu_sk_per_periode_dan_mengunduh_sk_pembimbing_dan_penguji(): void
    {
        $this->actingAs($this->userAndi)->get(route('dosen.sk.index'))
            ->assertOk()
            ->assertSee($this->periode->nama_periode)
            ->assertSee('Tanpa Gelombang') // data uji tanpa kolom gelombang
            ->assertSee('Unduh SK Penguji (PDF)')
            ->assertSee(route('dosen.sk.index'), false); // menu SK tampil di sidebar

        foreach (['pembimbing', 'penguji'] as $mode) {
            $res = $this->actingAs($this->userAndi)->get(route('dosen.sk.download', [$this->periode, $mode]));
            $res->assertOk();
            $this->assertStringStartsWith('%PDF', $res->getContent());
            $this->assertStringContainsString('SK_' . ucfirst($mode), $res->headers->get('content-disposition'));
        }
    }

    public function test_pdf_dosen_hanya_berisi_bagian_dosen_itu_dan_sempro_tidak_dihitung_untuk_penguji(): void
    {
        $sidangs = SkPdfService::sidangs(['periode_id' => $this->periode->id]);

        $semua = SkPdfService::dosenRincian($sidangs, 'penguji');
        $this->assertSame(['Andi SK Dosen', 'Budi SK Dosen'], $semua->pluck('nama_dosen')->all());

        $budi = SkPdfService::dosenRincian($sidangs, 'penguji', $this->budi->id);
        $this->assertSame(['Budi SK Dosen'], $budi->pluck('nama_dosen')->all());
        $this->assertSame([0, 1], $budi->first()['jumlah']);
    }

    public function test_sk_pembimbing_dihitung_sejak_sempro_disetujui(): void
    {
        // Budi hanya membimbing di tahap Sempro (disetujui) → tetap punya SK Pembimbing.
        $bimbingan = SkPdfService::sidangs(['periode_id' => $this->periode->id], 'pembimbing');
        $budi = SkPdfService::dosenRincian($bimbingan, 'pembimbing', $this->budi->id);
        $this->assertSame([1, 0], $budi->first()['jumlah']);

        $this->actingAs($this->userBudi)->get(route('dosen.sk.index'))
            ->assertOk()
            ->assertSee('1 Pembimbing Utama')
            ->assertSee('1 mahasiswa masih tahap Sempro');

        $res = $this->actingAs($this->userBudi)->get(route('dosen.sk.download', [$this->periode, 'pembimbing']));
        $res->assertOk();
        $this->assertStringStartsWith('%PDF', $res->getContent());

        // Sempro yang belum disetujui tidak dihitung; mahasiswa yang sudah lanjut skripsi tidak dobel.
        $nim = 'SKX' . substr(uniqid(), -6);
        Sidang::create(['nim' => 'SKP' . substr(uniqid(), -6), 'nama_mahasiswa' => 'Mhs Pending', 'judul_skripsi' => 'J',
            'jenis_tugas_akhir' => 'sempro', 'verifikasi_status' => 'menunggu', 'periode_id' => $this->periode->id, 'dosen_pembimbing_utama_id' => $this->budi->id]);
        foreach (['sempro', 'sidang'] as $jenis) {
            Sidang::create(['nim' => $nim, 'nama_mahasiswa' => 'Mhs Lanjut', 'judul_skripsi' => 'J', 'jenis_tugas_akhir' => $jenis,
                'verifikasi_status' => 'disetujui', 'periode_id' => $this->periode->id, 'dosen_pembimbing_utama_id' => $this->budi->id]);
        }
        $bimbingan = SkPdfService::pembimbingSidangs(['periode_id' => $this->periode->id])
            ->filter(fn ($s) => (int) $s->dosen_pembimbing_utama_id === $this->budi->id);
        $this->assertCount(2, $bimbingan);
        $this->assertSame('sidang', $bimbingan->firstWhere('nim', $nim)->jenis_tugas_akhir);

        // Admin SK: kartu SK Pembimbing ikut menghitung bimbingan tahap Sempro.
        $admin = User::create(['name' => 'Admin SK', 'email' => 'admsk-' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $this->actingAs($admin)->get(route('administrasi.sk.index', ['periode_id' => $this->periode->id]))
            ->assertOk()->assertSee('Mahasiswa Bimbingan')->assertSee('masih tahap Sempro');
    }

    public function test_mode_tidak_dikenal_404_dan_mahasiswa_tidak_bisa_akses(): void
    {
        $this->actingAs($this->userAndi)->get('/dosen/sk/' . $this->periode->getRouteKey() . '/lainnya')->assertNotFound();

        $mhs = User::create(['name' => 'Mhs', 'email' => 'mhs-sk-' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_MAHASISWA]);
        $this->actingAs($mhs)->get(route('dosen.sk.index'))->assertForbidden();
    }

    public function test_sk_pembimbing_per_gelombang_tanpa_menunggu_gelombang_terakhir(): void
    {
        // Gelombang 1 sudah ada mahasiswanya, gelombang 3 belum — SK gelombang 1 tetap bisa diunduh.
        foreach ([1, 1, 2] as $i => $g) {
            Sidang::create([
                'nim' => 'SKG' . $i . substr(uniqid(), -5), 'nama_mahasiswa' => "Mhs G{$g}-{$i}", 'judul_skripsi' => 'J',
                'jenis_tugas_akhir' => 'sidang', 'verifikasi_status' => 'disetujui', 'periode_id' => $this->periode->id,
                'gelombang' => $g, 'tanggal_pendaftaran' => now()->format('Y-m-d'),
                'dosen_pembimbing_utama_id' => $this->budi->id,
            ]);
        }
        // Hook model bisa menghitung ulang gelombang dari tanggal — paksa nilai uji.
        Sidang::where('nama_mahasiswa', 'like', 'MHS G1-%')->update(['gelombang' => 1]);
        Sidang::where('nama_mahasiswa', 'like', 'MHS G2-%')->update(['gelombang' => 2]);

        $this->actingAs($this->userBudi)->get(route('dosen.sk.index'))
            ->assertOk()
            ->assertSee('Gelombang 1')
            ->assertSee('Gelombang 2')
            ->assertSee('2 Utama · 0 Pendamping')
            ->assertSee('?gelombang=1', false);

        $g1 = SkPdfService::dosenRincian(SkPdfService::sidangs(['periode_id' => $this->periode->id, 'gelombang' => '1']), 'pembimbing', $this->budi->id);
        $this->assertSame([2, 0], $g1->first()['jumlah']);
        $this->assertSame(['MHS G1-0', 'MHS G1-1'], collect($g1->first()['mahasiswa'])->pluck('nama')->sort()->values()->all()); // nama disimpan kapital

        $res = $this->actingAs($this->userBudi)->get(route('dosen.sk.download', [$this->periode, 'pembimbing']) . '?gelombang=1');
        $res->assertOk();
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->assertStringContainsString('Gelombang_1', $res->headers->get('content-disposition'));

        // Gelombang tanpa bimbingan → kembali dengan peringatan; nilai gelombang aneh → 404.
        $this->actingAs($this->userBudi)->from(route('dosen.sk.index'))
            ->get(route('dosen.sk.download', [$this->periode, 'pembimbing']) . '?gelombang=3')
            ->assertRedirect(route('dosen.sk.index'))->assertSessionHas('warning');
        $this->actingAs($this->userBudi)->get(route('dosen.sk.download', [$this->periode, 'pembimbing']) . '?gelombang=x1')->assertNotFound();
    }

    public function test_sk_pembimbing_tanpa_gelombang(): void
    {
        $tanpa = SkPdfService::dosenRincian(SkPdfService::sidangs(['periode_id' => $this->periode->id, 'gelombang' => 'tanpa']), 'pembimbing', $this->andi->id);
        $this->assertSame([1, 0], $tanpa->first()['jumlah']);

        $this->actingAs($this->userAndi)->get(route('dosen.sk.download', [$this->periode, 'pembimbing']) . '?gelombang=tanpa')->assertOk();
    }

    public function test_periode_berjalan_sk_pembimbing_sementara_dan_sk_penguji_menunggu_final(): void
    {
        $aktif = Periode::create(['nama_periode' => 'Semester Berjalan SK ' . uniqid(), 'aktif' => true]);
        // Gelombang skripsi terakhir masih terbuka → periode belum final.
        \App\Models\PendaftaranPeriode::create([
            'periode_id' => $aktif->id, 'jenis' => 'skripsi', 'gelombang' => 3,
            'tanggal_mulai' => now()->subDay()->format('Y-m-d'), 'tanggal_selesai' => now()->addDays(5)->format('Y-m-d'),
        ]);
        Sidang::create([
            'nim' => 'SKA' . substr(uniqid(), -6), 'nama_mahasiswa' => 'Mhs Berjalan', 'judul_skripsi' => 'J',
            'jenis_tugas_akhir' => 'sidang', 'verifikasi_status' => 'disetujui', 'periode_id' => $aktif->id,
            'dosen_pembimbing_utama_id' => $this->andi->id, 'ketua_penguji_id' => $this->andi->id,
        ]);

        $this->assertFalse(SkPdfService::periodeFinal($aktif));
        $this->assertTrue(SkPdfService::periodeFinal($this->periode)); // periode tidak aktif = final

        $this->actingAs($this->userAndi)->get(route('dosen.sk.index'))
            ->assertOk()
            ->assertSee('SK Sementara')
            ->assertSee('SK sementara, belum final.')
            ->assertSee('SK Penguji tersedia setelah gelombang terakhir');

        $res = $this->actingAs($this->userAndi)->get(route('dosen.sk.download', [$aktif, 'pembimbing']));
        $res->assertOk();
        $this->assertStringContainsString('SEMENTARA', $res->headers->get('content-disposition'));

        $this->actingAs($this->userAndi)->from(route('dosen.sk.index'))
            ->get(route('dosen.sk.download', [$aktif, 'penguji']))
            ->assertRedirect(route('dosen.sk.index'))
            ->assertSessionHas('warning');

        // Periode lama (final): SK Pembimbing tanpa tanda SEMENTARA.
        $final = $this->actingAs($this->userAndi)->get(route('dosen.sk.download', [$this->periode, 'pembimbing']));
        $this->assertStringNotContainsString('SEMENTARA', $final->headers->get('content-disposition'));
    }
}

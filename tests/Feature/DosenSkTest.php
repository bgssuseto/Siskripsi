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
            ->assertSee('Unduh SK Pembimbing (PDF)')
            ->assertSee('Unduh SK Penguji (PDF)')
            ->assertSee(route('dosen.sk.index'), false); // menu SK tampil di sidebar

        foreach (['pembimbing', 'penguji'] as $mode) {
            $res = $this->actingAs($this->userAndi)->get(route('dosen.sk.download', [$this->periode, $mode]));
            $res->assertOk();
            $this->assertStringStartsWith('%PDF', $res->getContent());
            $this->assertStringContainsString('SK_' . ucfirst($mode), $res->headers->get('content-disposition'));
        }
    }

    public function test_pdf_dosen_hanya_berisi_bagian_dosen_itu_dan_sempro_tidak_dihitung(): void
    {
        $sidangs = SkPdfService::sidangs(['periode_id' => $this->periode->id]);

        $semua = SkPdfService::dosenRincian($sidangs, 'penguji');
        $this->assertSame(['Andi SK Dosen', 'Budi SK Dosen'], $semua->pluck('nama_dosen')->all());

        $budi = SkPdfService::dosenRincian($sidangs, 'penguji', $this->budi->id);
        $this->assertSame(['Budi SK Dosen'], $budi->pluck('nama_dosen')->all());
        $this->assertSame([0, 1], $budi->first()['jumlah']);

        // Budi hanya pembimbing sempro → tidak punya SK Pembimbing.
        $this->assertTrue(SkPdfService::dosenRincian($sidangs, 'pembimbing', $this->budi->id)->isEmpty());

        $this->actingAs($this->userBudi)
            ->from(route('dosen.sk.index'))
            ->get(route('dosen.sk.download', [$this->periode, 'pembimbing']))
            ->assertRedirect(route('dosen.sk.index'))
            ->assertSessionHas('warning');

        $this->actingAs($this->userBudi)->get(route('dosen.sk.index'))
            ->assertOk()
            ->assertSee('Tidak ada bimbingan ujian skripsi pada periode ini.');
    }

    public function test_mode_tidak_dikenal_404_dan_mahasiswa_tidak_bisa_akses(): void
    {
        $this->actingAs($this->userAndi)->get('/dosen/sk/' . $this->periode->getRouteKey() . '/lainnya')->assertNotFound();

        $mhs = User::create(['name' => 'Mhs', 'email' => 'mhs-sk-' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_MAHASISWA]);
        $this->actingAs($mhs)->get(route('dosen.sk.index'))->assertForbidden();
    }
}

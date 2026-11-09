<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\PengumpulanProposal;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PengumpulanProposalTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $mahasiswa;
    private Sidang $sempro;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();
        $this->admin = User::create(['name' => 'Admin Proposal', 'email' => 'prop' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $nim = 'PRP' . substr(uniqid(), -7);
        $this->mahasiswa = User::create(['name' => 'Mhs Proposal', 'email' => 'mhsprop' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => 'mahasiswa', 'nim' => $nim]);
        $periode = Periode::create(['nama_periode' => 'Periode Proposal ' . uniqid(), 'aktif' => false]);
        $dosen = Dosen::create(['nama_dosen' => 'Dosen Proposal, M.Kom', 'nidn' => 'PRP' . substr(uniqid(), -7)]);
        $this->sempro = Sidang::create([
            'nim' => $nim, 'nama_mahasiswa' => 'Mhs Proposal', 'judul_skripsi' => 'Judul Proposal',
            'jenis_tugas_akhir' => 'sempro', 'verifikasi_status' => 'disetujui', 'periode_id' => $periode->id,
            'tanggal_pendaftaran' => now()->toDateString(), 'dosen_pembimbing_utama_id' => $dosen->id,
        ]);
    }

    public function test_terima_proposal_menerbitkan_nomor_dan_mahasiswa_bisa_cetak(): void
    {
        $this->actingAs($this->mahasiswa)->get(route('mahasiswa.proposal.cetak', $this->sempro))->assertNotFound();

        $berikutnya = PengumpulanProposal::nomorBerikutnya();
        $this->actingAs($this->admin)
            ->postJson(route('master.sempro.proposal.verifikasi', $this->sempro), ['status' => 'diterima', 'tanggal_surat' => '2020-01-01'])
            ->assertOk()->assertJsonPath('success', true);

        $proposal = $this->sempro->fresh()->pengumpulanProposal;
        $this->assertTrue($proposal->isDiterima());
        $this->assertSame($berikutnya, $proposal->nomor_surat);
        // Tanggal surat selalu hari ini (tanggal kiriman form diabaikan).
        $hariIni = now()->timezone('Asia/Jakarta');
        $this->assertSame($hariIni->toDateString(), $proposal->tanggal_surat->toDateString());
        $this->assertSame("{$berikutnya}/PROPOSAL.TI.UMK/{$hariIni->format('n')}/{$hariIni->format('Y')}", $proposal->nomor_surat_lengkap);

        $this->actingAs($this->mahasiswa)->get(route('mahasiswa.sempro.index'))
            ->assertOk()->assertSee('Cetak Surat Keterangan');
        $this->actingAs($this->mahasiswa)->get(route('mahasiswa.proposal.cetak', $this->sempro))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        // QR mengarah ke halaman verifikasi publik yang menyatakan surat sah.
        $this->get(route('verifikasi.proposal', $proposal->kode_verifikasi))->assertOk()->assertSee('DOKUMEN ASLI');

        // Diterima ulang setelah dibatalkan: nomor & kode QR tetap sama.
        $kode = $proposal->kode_verifikasi;
        $this->actingAs($this->admin)->postJson(route('master.sempro.proposal.verifikasi', $this->sempro), ['status' => 'belum'])->assertOk();
        $this->get(route('verifikasi.proposal', $kode))->assertOk()->assertSee('SURAT TIDAK BERLAKU');
        $this->actingAs($this->admin)->postJson(route('master.sempro.proposal.verifikasi', $this->sempro), ['status' => 'diterima'])->assertOk();
        $this->assertSame($berikutnya, $proposal->fresh()->nomor_surat);
        $this->assertSame($kode, $proposal->fresh()->kode_verifikasi);
    }

    public function test_tolak_wajib_catatan_dan_catatan_tampil_ke_mahasiswa(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('master.sempro.proposal.verifikasi', $this->sempro), ['status' => 'ditolak'])
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson(route('master.sempro.proposal.verifikasi', $this->sempro), ['status' => 'ditolak', 'catatan' => 'Bab 3 belum lengkap'])
            ->assertOk();

        $this->actingAs($this->mahasiswa)->get(route('mahasiswa.sempro.index'))
            ->assertOk()->assertSee('Bab 3 belum lengkap')->assertDontSee('Cetak Surat Keterangan');
        $this->actingAs($this->mahasiswa)->get(route('mahasiswa.proposal.cetak', $this->sempro))->assertNotFound();
    }

    public function test_mahasiswa_lain_tidak_bisa_cetak_dan_data_sempro_tampil(): void
    {
        $this->actingAs($this->admin)->postJson(route('master.sempro.proposal.verifikasi', $this->sempro), ['status' => 'diterima'])->assertOk();

        $lain = User::create(['name' => 'Mhs Lain', 'email' => 'lain' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => 'mahasiswa', 'nim' => 'LAIN' . substr(uniqid(), -6)]);
        $this->actingAs($lain)->get(route('mahasiswa.proposal.cetak', $this->sempro))->assertForbidden();

        $this->actingAs($this->admin)->get(route('master.sempro.index', ['periode_id' => $this->sempro->periode_id]))
            ->assertOk()->assertSee('PROPOSAL.TI.UMK');
        $this->actingAs($this->admin)->get(route('master.sempro.proposal.cetak', $this->sempro))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_pendaftaran_belum_disetujui_tidak_bisa_diproses(): void
    {
        $this->sempro->update(['verifikasi_status' => 'ditolak']);

        $this->actingAs($this->admin)
            ->postJson(route('master.sempro.proposal.verifikasi', $this->sempro), ['status' => 'diterima'])
            ->assertStatus(422);
        $this->assertNull($this->sempro->fresh()->pengumpulanProposal);
    }
}

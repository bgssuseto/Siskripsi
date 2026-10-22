<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\TandaTanganUndangan;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UndanganTtdDanPencarianTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Periode $periode;
    private Dosen $penguji;
    private Dosen $koordinator;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $this->admin = User::create([
            'name' => 'Admin TTD', 'email' => 'admin-ttd-' . uniqid() . '@test.id',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $this->periode = Periode::create(['nama_periode' => 'Semester Uji TTD ' . uniqid(), 'aktif' => false]);

        // Koordinator ditunjuk lewat role tambahan pada akun dosen.
        $this->koordinator = Dosen::create(['nama_dosen' => 'Dr. Koordinator Uji', 'nidn' => 'KO' . uniqid()]);
        $userKoor = User::create([
            'name' => 'Koor', 'email' => 'koor-' . uniqid() . '@test.id', 'password' => bcrypt('x'),
            'role' => User::ROLE_DOSEN, 'dosen_id' => $this->koordinator->id,
        ]);
        UserRole::create(['user_id' => $userKoor->id, 'role' => User::ROLE_KOORDINATOR]);

        $this->penguji = Dosen::create(['nama_dosen' => 'Dosen Penguji TTD', 'nidn' => 'PG' . uniqid()]);
        $lain = Dosen::create(['nama_dosen' => 'Dosen Lain TTD', 'nidn' => 'LN' . uniqid()]);

        foreach ([['Mahasiswa Satu Cari', 'Sistem Pakar Padi'], ['Mahasiswa Dua', 'Aplikasi Kasir']] as $i => [$nama, $judul]) {
            Sidang::create([
                'nim' => "TTD{$i}" . substr(uniqid(), -6), 'nama_mahasiswa' => $nama, 'judul_skripsi' => $judul,
                'jenis_tugas_akhir' => 'skripsi', 'verifikasi_status' => 'disetujui',
                'periode_id' => $this->periode->id, 'tanggal_pendaftaran' => '2026-10-01',
                'dosen_pembimbing_utama_id' => $lain->id,
                'ketua_penguji_id' => $this->penguji->id,
                'tanggal' => '2026-11-0' . ($i + 2), 'jam' => '09.00 - 10.00',
            ]);
        }
    }

    public function test_undangan_bertanda_tangan_qr_koordinator_dan_bisa_diverifikasi(): void
    {
        $param = ['dosen' => $this->penguji, 'jenis' => 'skripsi', 'periode_id' => $this->periode->id];

        $this->actingAs($this->admin)->get(route('administrasi.undangan.preview', $param))
            ->assertOk()
            ->assertSee('Dr. Koordinator Uji')
            ->assertSee('Ditandatangani secara elektronik');

        // Cetak ulang undangan yang sama memakai QR yang sama.
        $this->get(route('administrasi.undangan.preview', $param))->assertOk();
        $ttd = TandaTanganUndangan::where('dosen_id', $this->penguji->id)->sole();
        $this->assertSame(2, $ttd->jumlah_mahasiswa);
        $this->assertSame($this->koordinator->id, $ttd->penandatangan_id);

        $this->get(route('administrasi.undangan.pdf', $param))->assertOk();

        auth()->logout();
        $this->get(route('verifikasi.undangan', $ttd->kode))
            ->assertOk()
            ->assertSee('UNDANGAN ASLI')
            ->assertSee('Dr. Koordinator Uji')
            ->assertSee('MAHASISWA SATU CARI');

        $this->get(route('verifikasi.undangan', 'kode-palsu'))->assertOk()->assertSee('DOKUMEN TIDAK TERDAFTAR');
    }

    public function test_pencarian_berita_acara_berdasarkan_judul_dan_nama_dosen(): void
    {
        $base = ['jenis' => 'skripsi', 'periode_id' => $this->periode->id];

        $this->actingAs($this->admin)->get(route('administrasi.berita-acara.index', $base + ['q' => 'pakar padi']))
            ->assertOk()->assertSee('MAHASISWA SATU CARI')->assertDontSee('MAHASISWA DUA');

        $this->get(route('administrasi.berita-acara.index', $base + ['q' => 'Penguji TTD']))
            ->assertOk()->assertSee('MAHASISWA SATU CARI')->assertSee('MAHASISWA DUA');

        $this->get(route('administrasi.berita-acara.index', $base + ['q' => 'tidak-ada-xyz']))
            ->assertOk()->assertSee('Tidak ada yang cocok');
    }
}

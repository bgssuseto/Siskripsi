<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MahasiswaProgressTest extends TestCase
{
    use DatabaseTransactions;

    private const WA_LINK = 'https://chat.whatsapp.com/progress-test';

    private User $student;
    private Periode $periode;
    private Dosen $dosen;

    protected function setUp(): void
    {
        parent::setUp();

        // Layout membaca menu sidebar dari DB (sama seperti test feature lain).
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $this->student = User::create([
            'name'     => 'Mahasiswa Progress',
            'email'    => 'mhs-progress@example.test',
            'password' => bcrypt('password'),
            'role'     => User::ROLE_MAHASISWA,
            'nim'      => '2024TEST01',
        ]);

        $this->periode = Periode::create([
            'nama_periode'        => 'Periode Uji Progress',
            'aktif'               => false,
            'link_grup_wa_sempro' => self::WA_LINK,
        ]);

        $this->dosen = Dosen::firstOrCreate(
            ['nidn' => '0012058501'],
            ['nama_dosen' => 'Arief Susanto, ST., M.Kom']
        );
    }

    private function registerSempro(array $attrs = []): Sidang
    {
        return Sidang::create(array_merge([
            'nim'                       => $this->student->nim,
            'nama_mahasiswa'            => $this->student->name,
            'judul_skripsi'             => 'Judul Uji Progress',
            'dosen_pembimbing_utama_id' => $this->dosen->id,
            'jenis_tugas_akhir'         => 'sempro',
            'periode_id'                => $this->periode->id,
            'tanggal_pendaftaran'       => now()->format('Y-m-d'),
            'verifikasi_status'         => 'disetujui',
        ], $attrs));
    }

    public function test_dashboard_menampilkan_progress_kosong_untuk_mahasiswa_belum_mendaftar(): void
    {
        $this->actingAs($this->student)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Progress Seminar Proposal (Sempro)')
            ->assertSee('Progress Sidang Skripsi')
            ->assertSee('Belum mendaftar')
            ->assertSee('Daftar sekarang');
    }

    public function test_dashboard_menampilkan_tahap_join_wa_dan_penjadwalan_setelah_berkas_diterima(): void
    {
        $sidang = $this->registerSempro();

        $this->actingAs($this->student)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Berkas diterima')
            ->assertSee('Klik tombol untuk bergabung')
            ->assertSee(route('mahasiswa.sidang.join-wa', $sidang), false)
            ->assertSee('Sedang dijadwalkan');
    }

    public function test_dashboard_menampilkan_berkas_ditolak_beserta_catatan(): void
    {
        $this->registerSempro(['verifikasi_status' => 'ditolak', 'verifikasi_komentar' => 'PDF tidak terbaca']);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Berkas ditolak')
            ->assertSee('PDF tidak terbaca')
            ->assertSee('Revisi pendaftaran');
    }

    public function test_dashboard_menampilkan_jadwal_ujian_saat_sudah_terjadwal(): void
    {
        $ruang = \App\Models\Ruang::create(['kode_ruangan' => 'RT-99', 'nama_ruangan' => 'Ruang Uji']);
        $this->registerSempro([
            'tanggal'  => now()->addDays(3)->format('Y-m-d'),
            'jam'      => '09.00 - 10.00',
            'ruang_id' => $ruang->id,
        ]);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Sudah terjadwal')
            ->assertSee('H-3')
            ->assertSee('Pukul 09.00 - 10.00')
            ->assertSee('Ruang RT-99');
    }

    public function test_klik_join_wa_mencatat_klik_pertama_lalu_redirect_ke_grup(): void
    {
        $sidang = $this->registerSempro();
        $this->assertNull($sidang->fresh()->wa_joined_at);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.sidang.join-wa', $sidang))
            ->assertRedirect(self::WA_LINK);

        $this->assertNotNull($sidang->fresh()->wa_joined_at);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Bergabung ')
            ->assertDontSee('Klik tombol untuk bergabung');
    }

    public function test_klik_join_wa_berikutnya_tidak_menimpa_waktu_klik_pertama(): void
    {
        $sidang = $this->registerSempro();
        \DB::table('sidangs')->where('id', $sidang->id)->update(['wa_joined_at' => '2026-01-02 03:04:05']);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.sidang.join-wa', $sidang))
            ->assertRedirect(self::WA_LINK);

        $this->assertSame('2026-01-02 03:04:05', $sidang->fresh()->wa_joined_at->format('Y-m-d H:i:s'));
    }

    public function test_mahasiswa_lain_tidak_bisa_memakai_route_join_wa_milik_orang_lain(): void
    {
        $sidang = $this->registerSempro();
        $other = User::create([
            'name' => 'Mahasiswa Lain', 'email' => 'mhs-lain@example.test', 'password' => bcrypt('password'),
            'role' => User::ROLE_MAHASISWA, 'nim' => '2024TEST02',
        ]);

        $this->actingAs($other)
            ->get(route('mahasiswa.sidang.join-wa', $sidang))
            ->assertForbidden();

        $this->assertNull($sidang->fresh()->wa_joined_at);
    }

    public function test_join_wa_ditolak_selama_berkas_belum_diterima(): void
    {
        $sidang = $this->registerSempro(['verifikasi_status' => 'menunggu']);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.sidang.join-wa', $sidang))
            ->assertRedirect(route('mahasiswa.dashboard'))
            ->assertSessionHas('error');

        $this->assertNull($sidang->fresh()->wa_joined_at);
    }

    public function test_join_wa_tanpa_link_grup_kembali_ke_dashboard_dengan_pesan(): void
    {
        $this->periode->update(['link_grup_wa_sempro' => null]);
        $sidang = $this->registerSempro();

        $this->actingAs($this->student)
            ->get(route('mahasiswa.sidang.join-wa', $sidang))
            ->assertRedirect(route('mahasiswa.dashboard'))
            ->assertSessionHas('error');

        $this->assertNull($sidang->fresh()->wa_joined_at);
    }
}

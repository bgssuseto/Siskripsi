<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KesediaanDosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Uji end-to-end Asisten Plotting (Auto-Schedule): tanggal/ruang diambil dari
 * irisan kesediaan dosen & ruang yang siap digunakan, dan untuk Sempro, dewan
 * penguji yang diterapkan adalah pembimbing mahasiswa sendiri.
 */
class AutoSchedulePlottingTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;
    private Periode $periode;
    private Dosen $utama;
    private Dosen $pendamping;
    private Ruang $ruangSiap;

    protected function setUp(): void
    {
        parent::setUp();

        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();

        $this->superAdmin = User::create([
            'name' => 'Admin Uji Plotting', 'email' => 'admin-uji-plotting@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->periode = Periode::create(['nama_periode' => 'Periode Uji Plotting', 'aktif' => true]);
        $this->utama = Dosen::firstOrCreate(['nidn' => '0066666601'], ['nama_dosen' => 'Dr. Utama Plotting']);
        $this->pendamping = Dosen::firstOrCreate(['nidn' => '0066666602'], ['nama_dosen' => 'Dr. Pendamping Plotting']);

        $this->ruangSiap = Ruang::create([
            'kode_ruangan' => 'RPLOT-1', 'nama_ruangan' => 'Ruang Uji Plotting',
            'status' => Ruang::STATUS_SIAP,
        ]);

        // Ruang tidak siap tidak boleh pernah dipilih Asisten Plotting.
        Ruang::create([
            'kode_ruangan' => 'RPLOT-2', 'nama_ruangan' => 'Ruang Belum Siap',
            'status' => Ruang::STATUS_BELUM_SIAP,
        ]);

        foreach ([$this->utama, $this->pendamping] as $dosen) {
            KesediaanDosen::create([
                'dosen_id' => $dosen->id, 'periode_id' => $this->periode->id,
                'tanggal' => now()->addDays(3)->format('Y-m-d'),
                'jam_mulai' => '09:00', 'jam_selesai' => '11:00',
            ]);
        }
    }

    private function daftarSempro(): Sidang
    {
        return Sidang::create([
            'nim' => '2024PLOT01', 'nama_mahasiswa' => 'Mahasiswa Uji Plotting',
            'judul_skripsi' => 'Judul Uji Plotting',
            'dosen_pembimbing_utama_id' => $this->utama->id,
            'dosen_pembimbing_pendamping_id' => $this->pendamping->id,
            'jenis_tugas_akhir' => 'sempro', 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => now()->format('Y-m-d'), 'verifikasi_status' => 'disetujui',
        ]);
    }

    public function test_usulan_jadwal_sempro_memakai_irisan_kesediaan_dosen_dan_ruang_siap(): void
    {
        $sidang = $this->daftarSempro();

        $response = $this->actingAs($this->superAdmin)->get(route('jadwal.auto-plot.index', [
            'generate' => 1, 'periode_id' => $this->periode->id, 'jenis' => 'sempro',
        ]));

        $response->assertOk();
        $proposals = $response->viewData('proposals');

        $this->assertCount(1, $proposals);
        $this->assertSame($sidang->id, $proposals[0]['sidang_id']);
        $this->assertSame($this->ruangSiap->id, $proposals[0]['ruang_id']);
        $this->assertSame(now()->addDays(3)->format('Y-m-d'), $proposals[0]['tanggal']);
    }

    public function test_menerapkan_usulan_sempro_menetapkan_dewan_penguji_pembimbing_sendiri(): void
    {
        $sidang = $this->daftarSempro();

        $tanggal = now()->addDays(3)->format('Y-m-d');
        $this->actingAs($this->superAdmin)->post(route('jadwal.auto-plot.apply'), [
            'selected'  => [$sidang->id],
            'proposals' => [
                $sidang->id => [
                    'tanggal' => $tanggal, 'jam_mulai' => '09.00', 'jam_selesai' => '10.00',
                    'ruang_id' => $this->ruangSiap->id,
                ],
            ],
        ])->assertSessionHas('success');

        $fresh = $sidang->fresh();
        $this->assertSame($tanggal, $fresh->tanggal->format('Y-m-d'));
        $this->assertSame($this->ruangSiap->id, $fresh->ruang_id);
        // Dewan penguji sempro = pembimbing sendiri, walau form apply() sendiri
        // tidak pernah mengirim field ketua_penguji_id/anggota_penguji_1_id untuk
        // sempro — disinkronkan otomatis oleh Sidang::syncPengujiFromPembimbing().
        $this->assertSame($this->utama->id, $fresh->ketua_penguji_id);
        $this->assertSame($this->pendamping->id, $fresh->anggota_penguji_1_id);
        $this->assertTrue($fresh->isPlotted());
    }

    public function test_ruang_belum_siap_tidak_pernah_diusulkan(): void
    {
        $this->daftarSempro();

        $response = $this->actingAs($this->superAdmin)->get(route('jadwal.auto-plot.index', [
            'generate' => 1, 'periode_id' => $this->periode->id, 'jenis' => 'sempro',
        ]));

        $proposals = $response->viewData('proposals');
        $ruangBelumSiap = Ruang::where('kode_ruangan', 'RPLOT-2')->first();

        $this->assertCount(1, $proposals);
        $this->assertNotSame($ruangBelumSiap->id, $proposals[0]['ruang_id']);
    }

    public function test_halaman_mengirim_data_semua_gelombang_untuk_dropdown_reaktif_tanpa_reload(): void
    {
        PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 1,
            'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-10',
        ]);
        PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'skripsi', 'gelombang' => 2,
            'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-10',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('jadwal.auto-plot.index'));

        $response->assertOk();
        $allWaves = $response->viewData('allWaves');

        $this->assertCount(2, $allWaves);
        $this->assertTrue($allWaves->contains(fn ($w) => $w['jenis'] === 'sempro' && $w['gelombang'] === 1));
        $this->assertTrue($allWaves->contains(fn ($w) => $w['jenis'] === 'skripsi' && $w['gelombang'] === 2));
        // Data ini yang dipakai Alpine di browser (lihat x-data pada blade) untuk
        // mengisi ulang dropdown Gelombang tanpa reload saat Jenis/Periode diganti.
        // Js::from() membungkusnya sebagai JSON.parse('...') dengan tanda kutip
        // di-escape (") supaya aman disisipkan ke dalam atribut x-data="...".
        $response->assertSee('allWaves: JSON.parse(', false);
        $response->assertSee('\\u0022jenis\\u0022:\\u0022sempro\\u0022', false);
    }

    public function test_halaman_tetap_tampil_normal_tanpa_gelombang_sama_sekali(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('jadwal.auto-plot.index'))
            ->assertOk()
            ->assertSee('Semua Gelombang');
    }

    public function test_gelombang_baru_langsung_terdeteksi_saat_generate_usulan(): void
    {
        // Mahasiswa mendaftar SEBELUM gelombang-nya dibuat di Master Gelombang —
        // Asisten Plotting harus tetap menemukannya saat filter Gelombang dipakai
        // (lihat AutoScheduleController::index() -> recomputeGelombangForPeriode()).
        $sidang = $this->daftarSempro();
        $this->assertNull($sidang->gelombang);

        \App\Models\PendaftaranPeriode::create([
            'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 5,
            'tanggal_mulai' => now()->format('Y-m-d'), 'tanggal_selesai' => now()->addDay()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('jadwal.auto-plot.index', [
            'generate' => 1, 'periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 5,
        ]));

        $proposals = $response->viewData('proposals');
        $this->assertCount(1, $proposals);
        $this->assertSame($sidang->id, $proposals[0]['sidang_id']);
        $this->assertSame(5, $sidang->fresh()->gelombang);
    }

    /** Tambah kesediaan kedua dosen di tanggal tertentu. */
    private function kesediaanKeduaDosen(string $tanggal): void
    {
        foreach ([$this->utama, $this->pendamping] as $dosen) {
            KesediaanDosen::create([
                'dosen_id' => $dosen->id, 'periode_id' => $this->periode->id,
                'tanggal' => $tanggal, 'jam_mulai' => '09:00', 'jam_selesai' => '11:00',
            ]);
        }
    }

    private function generateSempro()
    {
        return $this->actingAs($this->superAdmin)->get(route('jadwal.auto-plot.index', [
            'generate' => 1, 'periode_id' => $this->periode->id, 'jenis' => 'sempro',
        ]));
    }

    public function test_usulan_tidak_pernah_memakai_tanggal_yang_sudah_lewat_atau_hari_ini(): void
    {
        KesediaanDosen::where('periode_id', $this->periode->id)->delete();
        $this->kesediaanKeduaDosen(now()->subDays(2)->format('Y-m-d'));
        $this->kesediaanKeduaDosen(now()->format('Y-m-d'));
        $this->daftarSempro();

        $response = $this->generateSempro();

        $this->assertCount(0, $response->viewData('proposals'));
        $unresolved = $response->viewData('unresolved');
        $this->assertCount(1, $unresolved);
        $this->assertStringContainsString('sudah lewat / di luar rentang', $unresolved[0]['reason']);
    }

    public function test_usulan_dibatasi_rentang_kesediaan_periode(): void
    {
        KesediaanDosen::where('periode_id', $this->periode->id)->delete();
        $this->periode->update([
            'kesediaan_tanggal_mulai'   => now()->addDays(5)->format('Y-m-d'),
            'kesediaan_tanggal_selesai' => now()->addDays(10)->format('Y-m-d'),
        ]);
        // Lebih awal dari rentang (besok), terlalu jauh ke depan (+20), dan satu di dalam rentang (+7).
        $this->kesediaanKeduaDosen(now()->addDay()->format('Y-m-d'));
        $this->kesediaanKeduaDosen(now()->addDays(20)->format('Y-m-d'));
        $this->kesediaanKeduaDosen(now()->addDays(7)->format('Y-m-d'));
        $this->daftarSempro();

        $response = $this->generateSempro();
        $proposals = $response->viewData('proposals');

        $this->assertCount(1, $proposals);
        $this->assertSame(now()->addDays(7)->format('Y-m-d'), $proposals[0]['tanggal']);
        $this->assertSame(now()->addDays(5)->format('Y-m-d'), $response->viewData('rentang')['min']);
        $this->assertSame(now()->addDays(10)->format('Y-m-d'), $response->viewData('rentang')['max']);
    }

    public function test_menerapkan_usulan_menolak_tanggal_di_luar_rentang(): void
    {
        $this->periode->update([
            'kesediaan_tanggal_mulai'   => now()->addDays(2)->format('Y-m-d'),
            'kesediaan_tanggal_selesai' => now()->addDays(4)->format('Y-m-d'),
        ]);
        $sidang = $this->daftarSempro();

        foreach ([now()->subDay(), now()->addDays(30)] as $tgl) {
            $this->actingAs($this->superAdmin)->post(route('jadwal.auto-plot.apply'), [
                'selected'  => [$sidang->id],
                'proposals' => [$sidang->id => [
                    'tanggal' => $tgl->format('Y-m-d'), 'jam_mulai' => '09.00', 'jam_selesai' => '10.00',
                    'ruang_id' => $this->ruangSiap->id,
                ]],
            ])->assertSessionHas('warning');

            $this->assertNull($sidang->fresh()->tanggal);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Smoke test seluruh halaman/unduhan menu Administrasi (dulu satu
 * AdministrasiController, kini dipecah per domain): tidak boleh error 500.
 */
class AdministrasiSmokeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_semua_halaman_dan_unduhan_administrasi_tidak_error(): void
    {
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();
        $admin = User::create(['name' => 'Admin Smoke', 'email' => 'smoke' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $periode = Periode::create(['nama_periode' => 'Periode Smoke ' . uniqid(), 'aktif' => false]);
        $d1 = Dosen::create(['nama_dosen' => 'Dosen Smoke Satu', 'nidn' => 'SMK' . substr(uniqid(), -7), 'alias' => 'smk' . substr(uniqid(), -5)]);
        $d2 = Dosen::create(['nama_dosen' => 'Dosen Smoke Dua', 'nidn' => 'SMD' . substr(uniqid(), -7)]);
        $d3 = Dosen::create(['nama_dosen' => 'Dosen Smoke Tiga', 'nidn' => 'SMT' . substr(uniqid(), -7)]);
        $ruang = Ruang::create(['kode_ruangan' => 'SM' . substr(uniqid(), -4), 'nama_ruangan' => 'Ruang Smoke', 'status' => Ruang::STATUS_SIAP]);
        $tgl = now()->addDays(5)->toDateString();

        $dasar = ['periode_id' => $periode->id, 'verifikasi_status' => 'disetujui', 'tanggal_pendaftaran' => now()->toDateString(),
            'tanggal' => $tgl, 'ruang_id' => $ruang->id, 'judul_skripsi' => 'Judul Smoke', 'gelombang' => 1];
        $skripsi = Sidang::create($dasar + ['nim' => 'SMK' . substr(uniqid(), -7), 'nama_mahasiswa' => 'Mhs Skripsi', 'jenis_tugas_akhir' => 'sidang',
            'jam' => '08.00 - 09.00', 'dosen_pembimbing_utama_id' => $d1->id, 'ketua_penguji_id' => $d2->id, 'anggota_penguji_1_id' => $d3->id]);
        $sempro = Sidang::create($dasar + ['nim' => 'SMS' . substr(uniqid(), -7), 'nama_mahasiswa' => 'Mhs Sempro', 'jenis_tugas_akhir' => 'sempro',
            'jam' => '10.00 - 11.00', 'dosen_pembimbing_utama_id' => $d1->id, 'dosen_pembimbing_pendamping_id' => $d2->id]);

        $q = ['periode_id' => $periode->id];
        $halaman = [
            route('administrasi.undangan.index', $q),
            route('administrasi.undangan.preview', [$d1] + $q + ['jenis' => 'skripsi']),
            route('administrasi.undangan.pdf', [$d1] + $q + ['jenis' => 'skripsi']),
            route('administrasi.undangan.docx', [$d1] + $q + ['jenis' => 'sempro']),
            route('administrasi.undangan.excel', [$d1] + $q + ['jenis' => 'sempro']),
            route('administrasi.undangan.mass-excel', $q + ['jenis' => 'skripsi']),
            route('administrasi.undangan.zip', $q + ['jenis' => 'skripsi']),
            route('administrasi.undangan.mass-docx', $q + ['jenis' => 'sempro']),
            route('administrasi.undangan.rekap-dosen-penguji', $q),
            route('administrasi.rekap-pembimbing.index', $q),
            route('administrasi.berita-acara.index', $q),
            route('administrasi.berita-acara.pdf', $skripsi),
            route('administrasi.berita-acara.preview', $sempro),
            route('administrasi.berita-acara.mass-pdf', $q + ['jenis' => 'skripsi']),
            route('administrasi.berita-acara.mass-preview', $q + ['jenis' => 'sempro']),
            route('administrasi.berita-acara.zip', $q + ['jenis' => 'skripsi']),
            route('administrasi.sk.index', $q),
            route('administrasi.sk.export-pembimbing', $q),
            route('administrasi.sk.export-penguji', $q),
            route('administrasi.sk.export-pembimbing-pdf', $q),
            route('administrasi.sk.export-penguji-pdf', $q),
        ];
        foreach ($halaman as $url) {
            $status = $this->actingAs($admin)->get($url)->getStatusCode();
            $this->assertContains($status, [200, 302], "GET {$url} → HTTP {$status}");
        }

        auth()->logout();
        $publik = [
            route('public.dosen.jadwal', ['token' => $d1->public_token, 'p' => $periode->id]),
            route('public.dosen.jadwal.pdf', ['token' => $d1->public_token, 'p' => $periode->id]),
            route('public.dosen.jadwal.alias', ['alias' => $d1->alias]),
            route('public.dosen.jadwal.alias.pdf', ['alias' => $d1->alias]),
            route('public.jadwal-dosen-penguji', ['p' => $periode->id]),
            route('public.jadwal-dosen-penguji.pdf', ['dosen_id' => $d2->id, 'periode_id' => $periode->id]),
        ];
        foreach ($publik as $url) {
            $res = $this->get($url);
            $this->assertSame(200, $res->getStatusCode(), "GET {$url} → HTTP {$res->getStatusCode()}");
        }
        $this->get(route('administrasi.undangan.index'))->assertRedirect(); // tetap wajib login
    }
}

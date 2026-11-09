<?php

namespace Tests\Feature;

use App\Mail\PemberitahuanMail;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use App\Models\Sidang;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PengingatGelombangTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.penjadwal_aktif' => true, 'app.notifikasi_email' => true]);
        Mail::fake();
    }

    private function buatPeriodeAktif(): Periode
    {
        return Periode::create(['nama_periode' => 'Ganjil 2026/2027', 'aktif' => true]);
    }

    public function test_pengingat_dikirim_ke_mahasiswa_yang_belum_daftar_saat_gelombang_buka(): void
    {
        $periode = $this->buatPeriodeAktif();
        PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
            'tanggal_mulai' => now()->timezone('Asia/Jakarta')->startOfDay(),
            'tanggal_selesai' => now()->timezone('Asia/Jakarta')->addDays(14),
        ]);

        $belumDaftar = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'nim' => '2026001']);

        NotifikasiService::kirimPengingatGelombang();

        Mail::assertSent(PemberitahuanMail::class, fn ($mail) => $mail->hasTo($belumDaftar->email));
    }

    public function test_mahasiswa_yang_sudah_daftar_tidak_dapat_pengingat_belum_daftar(): void
    {
        $periode = $this->buatPeriodeAktif();
        PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
            'tanggal_mulai' => now()->timezone('Asia/Jakarta')->startOfDay(),
            'tanggal_selesai' => now()->timezone('Asia/Jakarta')->addDays(14),
        ]);

        $sudahDaftar = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'nim' => '2026002']);
        Sidang::create([
            'nim' => '2026002',
            'nama_mahasiswa' => 'Mahasiswa Dua',
            'judul_skripsi' => 'Judul Contoh',
            'periode_id' => $periode->id,
            'jenis_tugas_akhir' => 'sempro',
            'tanggal_pendaftaran' => now(),
            'verifikasi_status' => 'disetujui',
        ]);

        NotifikasiService::kirimPengingatGelombang();

        Mail::assertNotSent(PemberitahuanMail::class, fn ($mail) => $mail->hasTo($sudahDaftar->email));
    }

    public function test_pengingat_berkas_belum_lengkap_dikirim_h3_sebelum_gelombang_tutup(): void
    {
        $periode = $this->buatPeriodeAktif();
        $mulai = now()->timezone('Asia/Jakarta')->subDays(4)->startOfDay();
        $selesai = now()->timezone('Asia/Jakarta')->addDays(3)->startOfDay();

        PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
        ]);

        $menunggu = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'nim' => '2026003']);
        Sidang::create([
            'nim' => '2026003',
            'nama_mahasiswa' => 'Mahasiswa Tiga',
            'judul_skripsi' => 'Judul Contoh',
            'periode_id' => $periode->id,
            'jenis_tugas_akhir' => 'sempro',
            'tanggal_pendaftaran' => $mulai->copy()->addDay(),
            'gelombang' => 1,
            'verifikasi_status' => 'menunggu',
        ]);

        NotifikasiService::kirimPengingatGelombang();

        Mail::assertSent(PemberitahuanMail::class, fn ($mail) => $mail->hasTo($menunggu->email));
    }

    public function test_pengingat_tidak_dikirim_dua_kali_pada_hari_yang_sama(): void
    {
        $periode = $this->buatPeriodeAktif();
        PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
            'tanggal_mulai' => now()->timezone('Asia/Jakarta')->startOfDay(),
            'tanggal_selesai' => now()->timezone('Asia/Jakarta')->addDays(14),
        ]);
        User::factory()->create(['role' => User::ROLE_MAHASISWA, 'nim' => '2026004']);

        $pertama = NotifikasiService::kirimPengingatGelombang();
        $kedua = NotifikasiService::kirimPengingatGelombang();

        $this->assertGreaterThan(0, $pertama);
        $this->assertSame(0, $kedua);
    }

    public function test_tidak_mengirim_apa_pun_ketika_penjadwal_nonaktif(): void
    {
        config(['app.penjadwal_aktif' => false]);

        $periode = $this->buatPeriodeAktif();
        PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
            'tanggal_mulai' => now()->timezone('Asia/Jakarta')->startOfDay(),
            'tanggal_selesai' => now()->timezone('Asia/Jakarta')->addDays(14),
        ]);
        User::factory()->create(['role' => User::ROLE_MAHASISWA, 'nim' => '2026005']);

        $jumlah = NotifikasiService::kirimPengingatGelombang();

        $this->assertSame(0, $jumlah);
        Mail::assertNothingSent();
    }
}

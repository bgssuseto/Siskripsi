<?php

namespace Tests\Feature;

use App\Mail\PemberitahuanMail;
use App\Mail\UndanganDosenMail;
use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Models\User;
use App\Services\KelulusanService;
use App\Services\NotifikasiService;
use App\Support\BerkasMahasiswa;
use App\Support\KirimEmail;
use App\Support\StatusSistem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Berkas privat, finalisasi kelulusan berbasis jam, notifikasi email,
 * antrian email, dan status sistem.
 */
class KeandalanSistemTest extends TestCase
{
    use DatabaseTransactions;

    private Periode $periode;
    private Dosen $dosen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->periode = Periode::create(['nama_periode' => 'Periode Andal ' . uniqid(), 'aktif' => false]);
        $this->dosen = Dosen::create(['nama_dosen' => 'Dr. Penguji Andal, M.Kom', 'nidn' => 'AND' . substr(uniqid(), -7), 'email' => 'penguji' . uniqid() . '@test.id']);
    }

    private function sidang(array $attr = []): Sidang
    {
        return Sidang::create($attr + [
            'nim' => 'AND' . substr(uniqid(), -7), 'nama_mahasiswa' => 'Mhs Andal', 'judul_skripsi' => 'Judul',
            'jenis_tugas_akhir' => 'sempro', 'verifikasi_status' => 'menunggu', 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => now()->toDateString(), 'dosen_pembimbing_utama_id' => $this->dosen->id,
        ]);
    }

    private function akunMahasiswa(Sidang $s): User
    {
        return User::create(['name' => $s->nama_mahasiswa, 'email' => 'mhs' . uniqid() . '@test.id', 'password' => bcrypt('x'),
            'role' => User::ROLE_MAHASISWA, 'nim' => $s->nim]);
    }

    // ── Berkas privat ──

    public function test_berkas_lama_dipindah_ke_penyimpanan_privat_dan_tetap_bisa_dibuka_pemilik(): void
    {
        $nama = 'uji_' . uniqid() . '.pdf';
        @mkdir(public_path('uploads/persyaratan'), 0775, true);
        file_put_contents(public_path('uploads/persyaratan/' . $nama), '%PDF-1.4 uji');
        $s = $this->sidang(['file_persyaratan' => 'uploads/persyaratan/' . $nama]);

        Artisan::call('berkas:pindah-privat');

        $baru = $s->fresh()->file_persyaratan;
        $this->assertStringStartsWith('berkas/persyaratan/', $baru);
        $this->assertFileDoesNotExist(public_path('uploads/persyaratan/' . $nama));
        $this->assertFileExists(storage_path('app/private/' . $baru));

        $this->actingAs($this->akunMahasiswa($s))
            ->get(route('dokumen.show', [$s, 'persyaratan']))
            ->assertOk();

        BerkasMahasiswa::hapus($baru);
    }

    public function test_path_berkas_tidak_bisa_keluar_folder(): void
    {
        $this->assertNull(BerkasMahasiswa::lokasi('berkas/../../.env'));
        $this->assertNull(BerkasMahasiswa::lokasi('uploads/../../.env'));
    }

    // ── Finalisasi kelulusan ──

    public function test_ujian_hari_ini_baru_lulus_setelah_jam_selesai(): void
    {
        $this->travelTo(now()->setTime(10, 0));
        $pagi = $this->sidang(['tanggal' => now()->toDateString(), 'jam' => '08.00 - 09.00', 'verifikasi_status' => 'disetujui']);
        $siang = $this->sidang(['tanggal' => now()->toDateString(), 'jam' => '13.00 - 14.00', 'verifikasi_status' => 'disetujui']);
        $kemarin = $this->sidang(['tanggal' => now()->subDay()->toDateString(), 'jam' => '13.00 - 14.00', 'verifikasi_status' => 'disetujui']);

        KelulusanService::autoFinalizePastExams();

        $this->assertSame('lulus', $pagi->fresh()->status_ujian);
        $this->assertNull($siang->fresh()->status_ujian, 'Ujian pukul 13.00 belum boleh Lulus pukul 10.00.');
        $this->assertSame('lulus', $kemarin->fresh()->status_ujian);
    }

    // ── Notifikasi ──

    public function test_verifikasi_dikirim_setelah_jeda_dan_tidak_dobel(): void
    {
        Mail::fake();
        $s = $this->sidang();
        $this->akunMahasiswa($s);

        $s->update(['verifikasi_status' => 'ditolak', 'verifikasi_komentar' => 'Transkrip belum ada']);

        // Belum lewat jeda → belum terkirim.
        $this->assertSame(0, NotifikasiService::prosesTertunda());
        Mail::assertNothingSent();

        $this->travel(NotifikasiService::JEDA_MENIT + 1)->minutes();
        $this->assertSame(1, NotifikasiService::prosesTertunda());
        Mail::assertSent(PemberitahuanMail::class, fn ($m) => str_contains($m->subjek, 'Perlu Diperbaiki')
            && str_contains(json_encode($m->rincian), 'Transkrip belum ada'));

        // Diproses lagi tanpa perubahan → tidak dikirim ulang.
        $this->assertSame(0, NotifikasiService::prosesTertunda(0));
    }

    public function test_jadwal_yang_digeser_berkali_kali_hanya_dikirim_sekali_dengan_jadwal_akhir(): void
    {
        Mail::fake();
        $ruang = Ruang::create(['kode_ruangan' => 'AND' . substr(uniqid(), -4), 'nama_ruangan' => 'Lab', 'status' => Ruang::STATUS_SIAP]);
        $s = $this->sidang(['verifikasi_status' => 'disetujui']);
        $this->akunMahasiswa($s);

        $s->update(['tanggal' => now()->addDays(5)->toDateString(), 'jam' => '08.00 - 09.00', 'ruang_id' => $ruang->id]);
        $s->update(['jam' => '10.00 - 11.00']);
        $s->update(['jam' => '13.00 - 14.00']);

        $this->travel(NotifikasiService::JEDA_MENIT + 1)->minutes();
        $this->assertSame(1, NotifikasiService::prosesTertunda());
        Mail::assertSent(PemberitahuanMail::class, 1);
        Mail::assertSent(PemberitahuanMail::class, fn ($m) => str_contains(json_encode($m->rincian), '13.00 - 14.00'));
    }

    public function test_pengingat_h1_ke_mahasiswa_dan_dosen_sekali_saja(): void
    {
        Mail::fake();
        $s = $this->sidang([
            'verifikasi_status' => 'disetujui', 'jenis_tugas_akhir' => 'sidang',
            'tanggal' => now()->addDay()->toDateString(), 'jam' => '09.00 - 10.00',
            'ketua_penguji_id' => $this->dosen->id,
        ]);
        $mhs = $this->akunMahasiswa($s);

        $terkirim = NotifikasiService::kirimPengingatBesok();

        $this->assertGreaterThanOrEqual(2, $terkirim);
        Mail::assertSent(PemberitahuanMail::class, fn ($m) => $m->hasTo($mhs->email));
        Mail::assertSent(PemberitahuanMail::class, fn ($m) => $m->hasTo($this->dosen->email) && str_contains($m->subjek, 'besok'));
        $this->assertSame(0, NotifikasiService::kirimPengingatBesok(), 'Pengingat tidak boleh terkirim dua kali.');
    }

    public function test_email_pemberitahuan_dapat_dirender(): void
    {
        $html = (new PemberitahuanMail('Subjek Uji', 'Halo Uji,', ['Paragraf <b>aman</b>'],
            [['Jam' => '08.00 - 09.00 WIB', 'Ruang' => 'R1']], 'Buka Sistem', 'https://contoh.test/x'))->render();

        $this->assertStringContainsString('Halo Uji,', $html);
        $this->assertStringContainsString('08.00 - 09.00 WIB', $html);
        $this->assertStringContainsString('https://contoh.test/x', $html);
        $this->assertStringNotContainsString('<b>aman</b>', $html, 'Isi email harus di-escape.');
    }

    public function test_notifikasi_bisa_dimatikan(): void
    {
        Mail::fake();
        config(['app.notifikasi_email' => false]);
        $s = $this->sidang();
        $this->akunMahasiswa($s);
        $s->update(['verifikasi_status' => 'disetujui']);

        $this->travel(NotifikasiService::JEDA_MENIT + 1)->minutes();
        NotifikasiService::prosesTertunda();
        Mail::assertNothingSent();
    }

    // ── Antrian email & status sistem ──

    public function test_email_diantrikan_hanya_bila_penjadwal_aktif(): void
    {
        Mail::fake();
        config(['queue.default' => 'database']);
        $mail = new UndanganDosenMail($this->dosen, 'sempro', 'Periode Uji', 1, "%PDF\x00\xff biner", 'u.pdf');

        $this->assertSame('terkirim', KirimEmail::ke('a@test.id', $mail));
        Mail::assertSent(UndanganDosenMail::class);

        StatusSistem::detak();
        $this->assertSame('antri', KirimEmail::ke('a@test.id', $mail));
        Mail::assertQueued(UndanganDosenMail::class);

        // PDF biner aman diserialisasi ke payload antrian (JSON).
        $this->assertNotFalse(json_encode(serialize($mail)));
    }

    public function test_peringatan_status_sistem(): void
    {
        $judul = fn () => array_column(StatusSistem::peringatan(), 0);

        $this->assertContains('Penjadwal otomatis tidak berjalan', $judul());
        StatusSistem::detak();
        $this->assertNotContains('Penjadwal otomatis tidak berjalan', $judul());
    }

    public function test_peringatan_sistem_hanya_untuk_super_admin(): void
    {
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();
        $admin = User::create(['name' => 'Admin Sistem', 'email' => 'sys' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $koor = User::create(['name' => 'Koor Sistem', 'email' => 'koor' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_KOORDINATOR]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertSee('Kesehatan sistem')->assertSee('Penjadwal otomatis tidak berjalan');
        $this->actingAs($koor)->get(route('dashboard'))->assertOk()->assertDontSee('Kesehatan sistem');
    }

    public function test_semua_tugas_terjadwal_terdaftar_tanpa_proses_terpisah(): void
    {
        $nama = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($e) => $e->description)->all();

        foreach (['detak-penjadwal', 'proses-antrian', 'notifikasi-tertunda', 'pengingat-h1', 'finalisasi-kelulusan', 'backup-harian', 'berkas-pindah-privat', 'bersihkan-berkas-lama'] as $tugas) {
            $this->assertContains($tugas, $nama);
        }
        // Tidak ada Schedule::command() (butuh proc_open — dimatikan di hosting).
        foreach (app(\Illuminate\Console\Scheduling\Schedule::class)->events() as $e) {
            $this->assertInstanceOf(\Illuminate\Console\Scheduling\CallbackEvent::class, $e);
        }
    }
}

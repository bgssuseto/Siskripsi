<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use App\Models\UserRole;
use App\Services\FcmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

/**
 * Saat mahasiswa mendaftar Sempro/Skripsi, super admin & koordinator
 * (termasuk dosen yang diberi peran koordinator tambahan) dapat push.
 */
class NotifikasiPendaftaranAdminTest extends TestCase
{
    use RefreshDatabase;

    private array $terkirim = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.notifikasi_email' => true]);

        $fcm = Mockery::mock(FcmNotificationService::class);
        $fcm->shouldReceive('sendToUser')->andReturnUsing(function ($user, $title, $body, $data) {
            $this->terkirim[] = compact('user', 'title', 'body', 'data');
        });
        $this->app->instance(FcmNotificationService::class, $fcm);
    }

    private function user(string $role, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' ' . uniqid(),
            'email' => uniqid($role) . '@test.id',
            'password' => bcrypt('x'),
            'role' => $role,
        ], $extra));
    }

    private function daftar(User $mahasiswa, string $jenis): void
    {
        $periode = Periode::create(['nama_periode' => 'Ganjil 2026/2027', 'aktif' => true]);
        PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'jenis' => $jenis,
            'gelombang' => 1,
            'tanggal_mulai' => now()->subDay()->format('Y-m-d'),
            'tanggal_selesai' => now()->addDays(3)->format('Y-m-d'),
        ]);
        $dosen = Dosen::create(['nidn' => '0011223344', 'nama_dosen' => 'Dosen Pembimbing']);

        $this->actingAs($mahasiswa)->post(route('mahasiswa.daftar.store'), [
            'nim' => $mahasiswa->nim,
            'jenis_tugas_akhir' => $jenis,
            'judul_skripsi' => 'Sistem Pengingat',
            'dosen_pembimbing_utama_id' => $dosen->id,
            'no_wa_aktif' => '081234567890',
            'jenis_ta_pilihan' => 'sidang',
            'file_persyaratan' => UploadedFile::fake()->create('syarat.pdf', 100, 'application/pdf'),
        ])->assertSessionHas('success');

        $s = Sidang::where('nim', $mahasiswa->nim)->first();
        \App\Support\BerkasMahasiswa::hapus($s->file_persyaratan);
    }

    public function test_pendaftaran_sempro_mengirim_push_ke_super_admin_dan_koordinator(): void
    {
        $admin = $this->user(User::ROLE_SUPER_ADMIN);
        $koordinator = $this->user(User::ROLE_KOORDINATOR);
        $dosenKoordinator = $this->user(User::ROLE_DOSEN);
        UserRole::create(['user_id' => $dosenKoordinator->id, 'role' => User::ROLE_KOORDINATOR]);
        $dosenBiasa = $this->user(User::ROLE_DOSEN);
        $mahasiswa = $this->user(User::ROLE_MAHASISWA, ['nim' => '202651001', 'name' => 'Budi']);

        $this->daftar($mahasiswa, 'sempro');

        $penerima = collect($this->terkirim)->pluck('user.id')->sort()->values()->all();
        $this->assertSame(
            collect([$admin->id, $koordinator->id, $dosenKoordinator->id])->sort()->values()->all(),
            $penerima
        );
        $this->assertNotContains($dosenBiasa->id, $penerima);
        $this->assertNotContains($mahasiswa->id, $penerima);

        $push = $this->terkirim[0];
        $this->assertSame('Pendaftaran Baru Seminar Proposal', $push['title']);
        $this->assertStringContainsStringIgnoringCase('Budi (202651001)', $push['body']);
        $this->assertSame(route('pendaftaran.sempro'), $push['data']['url']);
    }

    public function test_pendaftaran_skripsi_membuka_halaman_pendaftaran_skripsi(): void
    {
        $this->user(User::ROLE_KOORDINATOR);
        $mahasiswa = $this->user(User::ROLE_MAHASISWA, ['nim' => '202651002']);

        $this->daftar($mahasiswa, 'skripsi');

        $this->assertCount(1, $this->terkirim);
        $this->assertSame('Pendaftaran Baru Sidang Skripsi', $this->terkirim[0]['title']);
        $this->assertSame(route('pendaftaran.skripsi'), $this->terkirim[0]['data']['url']);
    }

    public function test_tidak_mengirim_saat_notifikasi_dimatikan(): void
    {
        config(['app.notifikasi_email' => false]);
        $this->user(User::ROLE_SUPER_ADMIN);
        $mahasiswa = $this->user(User::ROLE_MAHASISWA, ['nim' => '202651003']);

        $this->daftar($mahasiswa, 'sempro');

        $this->assertSame([], $this->terkirim);
    }
}

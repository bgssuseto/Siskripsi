<?php

namespace Tests\Feature;

use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use App\Models\User;
use App\Services\FcmNotificationService;
use App\Services\NotifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * Setiap push membawa 'url' supaya aplikasi Android langsung membuka halaman
 * yang relevan saat notifikasi diketuk, bukan sekadar beranda.
 */
class PushNotificationUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.penjadwal_aktif' => true, 'app.notifikasi_email' => true]);
        Mail::fake();
    }

    public function test_pengingat_gelombang_sempro_membawa_url_halaman_pendaftaran_sempro(): void
    {
        $periode = Periode::create(['nama_periode' => 'Ganjil 2026/2027', 'aktif' => true]);
        PendaftaranPeriode::create([
            'periode_id' => $periode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
            'tanggal_mulai' => now()->timezone('Asia/Jakarta')->startOfDay(),
            'tanggal_selesai' => now()->timezone('Asia/Jakarta')->addDays(14),
        ]);
        $mahasiswa = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'nim' => '2026101']);

        $captured = [];
        $fcm = Mockery::mock(FcmNotificationService::class);
        $fcm->shouldReceive('sendToUser')->andReturnUsing(function ($user, $title, $body, $data) use (&$captured) {
            $captured[] = [$user->id, $data];
        });
        $this->app->instance(FcmNotificationService::class, $fcm);

        NotifikasiService::kirimPengingatGelombang();

        $this->assertNotEmpty($captured);
        [$userId, $data] = $captured[0];
        $this->assertSame($mahasiswa->id, $userId);
        $this->assertSame(route('mahasiswa.sempro.index'), $data['url']);
        foreach ($data as $value) {
            $this->assertIsString($value, 'Nilai data FCM wajib string');
        }
    }
}

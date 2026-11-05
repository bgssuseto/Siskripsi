<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Form kesediaan PUBLIK harus tetap bisa dikirim dari HP/WhatsApp: tujuan form
 * relatif (http/https & domain ikut halaman), halaman tidak di-cache, tidak
 * bergantung token CSRF/sesi, dan pesan sukses tetap tampil tanpa sesi.
 */
class KesediaanPublikKirimTest extends TestCase
{
    use DatabaseTransactions;

    private Periode $periode;
    private Dosen $dosen;
    private PendaftaranPeriode $wave;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Periode::query()->update(['aktif' => false]);
        $this->periode = Periode::create(['nama_periode' => 'Periode Publik ' . uniqid(), 'aktif' => true,
            'show_form_kesediaan' => true, 'lock_form_kesediaan' => false]);
        $this->dosen = Dosen::create(['nidn' => 'PUB' . substr(uniqid(), -7), 'nama_dosen' => 'Dr. Uji Publik', 'can_fill_kesediaan' => true]);
        $this->wave = PendaftaranPeriode::create(['periode_id' => $this->periode->id, 'jenis' => 'sempro', 'gelombang' => 1,
            'tanggal_mulai' => now()->format('Y-m-d'), 'tanggal_selesai' => now()->addDays(10)->format('Y-m-d')]);
        $this->token = $this->periode->ensureKesediaanPublicToken();
    }

    public function test_halaman_form_tidak_dicache_dan_tujuan_form_relatif(): void
    {
        $res = $this->get(route('public.kesediaan.show', $this->token))->assertOk();

        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $this->assertSame('no-cache', $res->headers->get('X-LiteSpeed-Cache-Control'));
        $res->assertSee('action="/kesediaan-publik/' . $this->token . '"', false);
        $res->assertDontSee('action="http', false);
    }

    public function test_kirim_publik_tidak_memerlukan_token_csrf(): void
    {
        $csrf = new class(app(), app('encrypter')) extends ValidateCsrfToken {
            public function dikecualikan(Request $r): bool { return $this->inExceptArray($r); }
        };

        $this->assertTrue($csrf->dikecualikan(Request::create('/kesediaan-publik/' . $this->token, 'POST')));
        // Form lain (login, admin) tetap dilindungi CSRF.
        $this->assertFalse($csrf->dikecualikan(Request::create('/login', 'POST')));
        $this->assertFalse($csrf->dikecualikan(Request::create('/master/kesediaan-dosen/settings', 'POST')));
    }

    public function test_setelah_simpan_kembali_ke_alamat_relatif_dan_pesan_tampil_tanpa_sesi(): void
    {
        $res = $this->post(route('public.kesediaan.store', $this->token), [
            'dosen_id' => $this->dosen->id, 'wave_id' => $this->wave->id,
            'slots' => [
                ['tanggal' => now()->addDays(3)->format('Y-m-d'), 'keterangan' => ''],
                ['tanggal' => now()->addDays(4)->format('Y-m-d'), 'keterangan' => ''],
            ],
        ]);

        $res->assertRedirect('/kesediaan-publik/' . $this->token . '?tersimpan=2');
        $this->assertDatabaseCount('kesediaan_dosens', 2);

        // Walau sesi (pesan flash) hilang, penanda di URL tetap menampilkan pesan sukses.
        $this->flushSession();
        $this->get('/kesediaan-publik/' . $this->token . '?tersimpan=2')
            ->assertOk()->assertSee('2 tanggal kesediaan menguji Anda berhasil disimpan');
    }
}

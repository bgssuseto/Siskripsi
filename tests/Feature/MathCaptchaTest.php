<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MathCaptchaTest extends TestCase
{
    use DatabaseTransactions;

    private function jawaban(string $form): int
    {
        $this->get(route('captcha.image', $form))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');

        return session("captcha.{$form}")['answer'];
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Captcha User', 'email' => 'captcha-' . uniqid() . '@test.id',
            'password' => bcrypt('rahasia123'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function test_gambar_captcha_berupa_svg_dan_soal_tidak_ada_di_html_halaman(): void
    {
        $res = $this->get(route('captcha.image', 'login'));
        $res->assertOk();
        $this->assertStringStartsWith('<svg', $res->getContent());
        $this->assertIsInt(session('captcha.login')['answer']);

        $this->get(route('login'))->assertOk()
            ->assertSee('Verifikasi Keamanan')
            ->assertSee(route('captcha.image', 'login'), false)
            ->assertDontSee((string) session('captcha.login')['answer'] . ' = ?');

        $this->get('/captcha/lainnya')->assertNotFound();
    }

    public function test_login_ditolak_tanpa_atau_dengan_jawaban_salah_dan_berhasil_dengan_jawaban_benar(): void
    {
        $user = $this->user();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertSessionHasErrors('captcha_input');
        $this->assertGuest();

        $benar = $this->jawaban('login');
        $this->post(route('login'), ['email' => $user->email, 'password' => 'rahasia123', 'captcha_input' => $benar + 1])
            ->assertSessionHasErrors('captcha_input');
        $this->assertGuest();

        $benar = $this->jawaban('login');
        $this->post(route('login'), ['email' => $user->email, 'password' => 'rahasia123', 'captcha_input' => $benar]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_jawaban_sekali_pakai_tidak_bisa_dipakai_ulang(): void
    {
        $user = $this->user();
        $benar = $this->jawaban('login');

        // Percobaan pertama salah password — soal tetap hangus.
        $this->post(route('login'), ['email' => $user->email, 'password' => 'salah', 'captcha_input' => $benar]);
        $this->assertGuest();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'rahasia123', 'captcha_input' => $benar])
            ->assertSessionHasErrors('captcha_input');
        $this->assertGuest();
    }

    public function test_daftar_memakai_captcha_gambar(): void
    {
        $data = [
            'name' => 'mahasiswa captcha', 'nim' => 'CAP' . substr(uniqid(), -6),
            'email' => 'daftar-' . uniqid() . '@test.id',
            'password' => 'Rahasia123!', 'password_confirmation' => 'Rahasia123!',
        ];

        $this->get(route('register'))->assertOk()->assertSee(route('captcha.image', 'register'), false);

        $this->post(route('register'), $data + ['captcha_input' => 999999])->assertSessionHasErrors('captcha_input');
        $this->assertDatabaseMissing('users', ['email' => $data['email']]);

        // Jawaban captcha login tidak berlaku untuk form daftar.
        $loginAnswer = $this->jawaban('login');
        $this->post(route('register'), $data + ['captcha_input' => $loginAnswer])->assertSessionHasErrors('captcha_input');

        $benar = $this->jawaban('register');
        $this->post(route('register'), $data + ['captcha_input' => $benar])->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['email' => $data['email'], 'name' => 'MAHASISWA CAPTCHA']);
    }
}

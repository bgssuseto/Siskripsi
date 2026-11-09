<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AndroidAppRememberLoginTest extends TestCase
{
    use RefreshDatabase;

    private function hasRememberCookie($response): bool
    {
        return collect($response->headers->getCookies())
            ->contains(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_'));
    }

    public function test_android_app_login_sets_remember_cookie_even_without_checkbox(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->withSession(['captcha.login' => ['answer' => 7, 'expires' => now()->addMinutes(10)->timestamp]])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 SkripsiAndroidApp/1.0',
            ])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha_input' => '7',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(
            $this->hasRememberCookie($response),
            'Login dari Android app harus selalu set cookie remember-me, walau checkbox tidak dicentang.'
        );
    }

    public function test_regular_browser_login_without_checkbox_does_not_set_remember_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->withSession(['captcha.login' => ['answer' => 9, 'expires' => now()->addMinutes(10)->timestamp]])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
            ])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'captcha_input' => '9',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertFalse(
            $this->hasRememberCookie($response),
            'Login browser biasa tanpa centang remember tidak boleh set cookie remember-me.'
        );
    }
}

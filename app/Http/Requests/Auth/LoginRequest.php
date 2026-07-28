<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Dosen;
use App\Models\User;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginInput = trim($this->input('email'));
        $authenticated = false;

        // Coba login via email
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $authenticated = Auth::attempt([
                'email'    => $loginInput,
                'password' => $this->input('password'),
            ], $this->boolean('remember'));
        } else {
            // Coba login via NIM (untuk Mahasiswa)
            $authenticated = Auth::attempt([
                'nim'      => $loginInput,
                'password' => $this->input('password'),
            ], $this->boolean('remember'));

            // Jika gagal via NIM, coba via NIDN (untuk Dosen)
            // NIDN disimpan di tabel dosens, bukan users, jadi perlu lookup dulu
            if (!$authenticated) {
                $dosen = Dosen::where('nidn', $loginInput)->first();
                if ($dosen) {
                    $dosenUser = User::where('dosen_id', $dosen->id)->first();
                    if ($dosenUser) {
                        $authenticated = Auth::attempt([
                            'email'    => $dosenUser->email,
                            'password' => $this->input('password'),
                        ], $this->boolean('remember'));
                    }
                }
            }
        }

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Kredensial yang Anda masukkan tidak valid. Silakan periksa kembali dan coba lagi.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.',
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}

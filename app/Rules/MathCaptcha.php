<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MathCaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $expected = session('captcha_answer');

        if ($expected === null) {
            $fail('Sesi verifikasi captcha telah kedaluwarsa. Silakan muat ulang halaman.');
            return;
        }

        if ((int) $value !== (int) $expected) {
            $fail('Jawaban captcha tidak sesuai. Silakan coba lagi.');
        }
    }
}

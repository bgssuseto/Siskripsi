<?php

namespace App\Rules;

use App\Support\MathCaptcha as Captcha;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validasi jawaban CAPTCHA hitungan untuk form tertentu ('login' / 'register').
 * Soal langsung hangus setelah dicek (lihat App\Support\MathCaptcha::check()).
 */
class MathCaptcha implements ValidationRule
{
    public function __construct(private string $form = 'register')
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($pesan = Captcha::check($this->form, $value)) {
            $fail($pesan);
        }
    }
}

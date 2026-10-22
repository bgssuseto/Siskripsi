<?php

namespace App\Http\Controllers;

use App\Support\MathCaptcha;
use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    /**
     * Gambar soal CAPTCHA baru untuk form login / daftar. Setiap permintaan
     * (termasuk tombol muat ulang) membuat soal baru dan mengganti jawaban lama.
     */
    public function image(string $form): Response
    {
        $svg = MathCaptcha::svg(MathCaptcha::generate($form));

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }
}

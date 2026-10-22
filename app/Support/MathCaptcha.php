<?php

namespace App\Support;

/**
 * CAPTCHA hitungan sederhana untuk form login & daftar.
 *
 * Soal (mis. "3 × 8") dibuat di server dan HANYA ditampilkan sebagai gambar SVG
 * yang diacak (rotasi, warna, garis & titik pengacau) — jawabannya disimpan di
 * session per form, tidak pernah dikirim ke browser. Setiap soal sekali pakai:
 * dicek benar/salah, langsung dihapus, sehingga tidak bisa ditebak berulang.
 */
class MathCaptcha
{
    public const FORMS = ['login', 'register'];

    private static function key(string $form): string
    {
        return "captcha.{$form}";
    }

    /** Buat soal baru untuk form ini, simpan jawabannya, kembalikan teks soalnya. */
    public static function generate(string $form): string
    {
        $op = ['+', '-', '×'][random_int(0, 2)];
        [$a, $b] = $op === '×'
            ? [random_int(2, 9), random_int(2, 9)]
            : [random_int(1, 20), random_int(1, 20)];

        if ($op === '-' && $b > $a) {
            [$a, $b] = [$b, $a];
        }

        $answer = match ($op) {
            '+' => $a + $b,
            '-' => $a - $b,
            '×' => $a * $b,
        };

        session([self::key($form) => ['answer' => $answer, 'expires' => now()->addMinutes(10)->timestamp]]);

        return "{$a} {$op} {$b} = ?";
    }

    /**
     * Cek jawaban lalu hapus soalnya (sekali pakai).
     *
     * @return string|null pesan kesalahan, atau null bila benar
     */
    public static function check(string $form, mixed $value): ?string
    {
        $data = session()->pull(self::key($form));

        if (!$data || ($data['expires'] ?? 0) < now()->timestamp) {
            return 'Kode verifikasi kedaluwarsa. Silakan muat ulang soal dan coba lagi.';
        }

        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^-?\d+$/', $value) || (int) $value !== (int) $data['answer']) {
            return 'Hasil hitungan verifikasi keamanan salah. Silakan coba lagi.';
        }

        return null;
    }

    /** Gambar SVG soal dengan pengacau visual. */
    public static function svg(string $question): string
    {
        $w = 150;
        $h = 52;
        $rand = fn (int $min, int $max) => random_int($min, $max);
        $warna = ['#1e3a8a', '#065f46', '#7c2d12', '#581c87', '#0f766e', '#9f1239', '#334155'];
        $noise = ['#93c5fd', '#fca5a5', '#86efac', '#fcd34d', '#c4b5fd', '#f9a8d4'];

        $out = '';
        // Latar
        $out .= "<rect width=\"{$w}\" height=\"{$h}\" rx=\"10\" fill=\"#eef2ff\"/>";

        // Titik-titik pengacau
        for ($i = 0; $i < 40; $i++) {
            $out .= sprintf('<circle cx="%d" cy="%d" r="%.1f" fill="%s" opacity="0.7"/>',
                $rand(0, $w), $rand(0, $h), $rand(5, 18) / 10, $noise[array_rand($noise)]);
        }

        // Garis pengacau di belakang teks
        for ($i = 0; $i < 6; $i++) {
            $out .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="%.1f" opacity="0.8"/>',
                $rand(0, 40), $rand(0, $h), $rand($w - 40, $w), $rand(0, $h), $noise[array_rand($noise)], $rand(8, 18) / 10);
        }

        // Karakter soal satu per satu, masing-masing diputar & digeser acak
        $chars = preg_split('//u', str_replace(' ', '', $question), -1, PREG_SPLIT_NO_EMPTY);
        $x = 12;
        foreach ($chars as $c) {
            $y = $rand(32, 40);
            $rot = $rand(-25, 25);
            $size = $c === '?' ? $rand(22, 26) : $rand(24, 30);
            $color = $c === '?' ? '#b91c1c' : $warna[array_rand($warna)];
            $out .= sprintf(
                '<text x="%d" y="%d" font-family="Georgia, \'Times New Roman\', serif" font-size="%d" font-weight="bold" fill="%s" transform="rotate(%d %d %d)">%s</text>',
                $x, $y, $size, $color, $rot, $x + 7, $y - 9, htmlspecialchars($c, ENT_XML1)
            );
            $x += ctype_digit($c) ? $rand(15, 18) : $rand(18, 22);
        }

        // Garis kurva di atas teks
        for ($i = 0; $i < 2; $i++) {
            $out .= sprintf('<path d="M0 %d Q %d %d %d %d" stroke="%s" stroke-width="1.4" fill="none" opacity="0.75"/>',
                $rand(10, $h - 10), $rand(40, 110), $rand(0, $h), $w, $rand(10, $h - 10), $noise[array_rand($noise)]);
        }

        return "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$w}\" height=\"{$h}\" viewBox=\"0 0 {$w} {$h}\" role=\"img\" aria-label=\"Soal verifikasi keamanan\">{$out}</svg>";
    }
}

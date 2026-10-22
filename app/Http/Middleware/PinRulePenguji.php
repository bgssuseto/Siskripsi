<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman Rule Komposisi Dosen Penguji bersifat rahasia: setelah login, user
 * masih harus memasukkan PIN. Akses terbuka selama sesi aktif di halaman ini
 * (kunci otomatis setelah beberapa menit tanpa aktivitas).
 */
class PinRulePenguji
{
    public const SESSION_KEY = 'pin_rule_penguji_sampai';

    public static function terbuka(Request $request): bool
    {
        return (int) $request->session()->get(self::SESSION_KEY, 0) > now()->timestamp;
    }

    public static function perpanjang(Request $request): void
    {
        $menit = max(1, (int) config('rahasia.rule_penguji.berlaku_menit', 15));
        $request->session()->put(self::SESSION_KEY, now()->addMinutes($menit)->timestamp);
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (self::terbuka($request)) {
            self::perpanjang($request);
            return $next($request);
        }

        $request->session()->forget(self::SESSION_KEY);

        if ($request->isMethod('GET') && !$request->expectsJson()) {
            return response()->view('master.dosen-penguji-rule.pin', [], 200)
                ->header('Cache-Control', 'no-store, private');
        }

        $pesan = 'Halaman Rule Komposisi Penguji terkunci. Masukkan PIN terlebih dahulu.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $pesan, 'terkunci' => true], 423);
        }

        return redirect()->route('master.dosen-penguji-rule.index')->with('error', $pesan);
    }
}

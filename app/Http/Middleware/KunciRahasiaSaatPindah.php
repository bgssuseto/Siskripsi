<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman rahasia (Rule Komposisi Penguji) langsung terkunci lagi begitu user
 * berpindah ke halaman lain — PIN harus dimasukkan ulang saat kembali.
 */
class KunciRahasiaSaatPindah
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()
            && $request->session()->has(PinRulePenguji::SESSION_KEY)
            && $request->isMethod('GET')
            && !$request->ajax()
            && !$request->expectsJson()
            && !str_starts_with((string) $request->route()?->getName(), 'master.dosen-penguji-rule.')) {
            $request->session()->forget(PinRulePenguji::SESSION_KEY);
        }

        return $next($request);
    }
}

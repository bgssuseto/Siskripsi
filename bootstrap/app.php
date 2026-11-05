<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\KunciRahasiaSaatPindah::class,
        ]);
        // Form kesediaan PUBLIK (tanpa login) — siapa pun yang memegang link memang
        // boleh mengisi, jadi token CSRF tidak menambah keamanan di sini, tapi justru
        // membuat kiriman gagal (419) bila sesi dosen habis karena halaman dibiarkan
        // lama terbuka, atau browser bawaan WhatsApp/HP tidak menyimpan cookie sesi.
        // Penyalahgunaan tetap dibatasi throttle:20,1 di route-nya.
        $middleware->validateCsrfTokens(except: [
            'kesediaan-publik/*',
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'menu.permission' => \App\Http\Middleware\CheckMenuPermission::class,
            'pin.rule-penguji' => \App\Http\Middleware\PinRulePenguji::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // This app has no separate `api/*` route group — every AJAX call (file
        // uploads, form submits, etc.) goes through normal `web` routes and
        // relies on Laravel's default JSON rendering for validation/other
        // exceptions when the request sends `Accept: application/json`.
        // Restricting this to `api/*` (the previous override) silently broke
        // every `$request->validate()` failure on AJAX forms app-wide: instead
        // of a 422 JSON response with `errors`, it returned a 302 redirect,
        // which `fetch().json()` can't parse — so users only ever saw a
        // generic "failed to connect" message, never the real validation error.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, \Throwable $e) => $request->expectsJson(),
        );
    })->create();

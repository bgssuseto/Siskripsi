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
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'menu.permission' => \App\Http\Middleware\CheckMenuPermission::class,
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

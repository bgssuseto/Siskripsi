<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal Skripsi TI' }} - Portal Skripsi Program Studi Teknik Informatika</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('images/favicon-48.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon-192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon-180.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#3251d4">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 relative overflow-hidden" style="background: linear-gradient(135deg, #4361ee 0%, #3251d4 45%, #192355 100%);">
        <!-- Decorative circles -->
        <div class="absolute top-20 left-20 w-64 h-64 rounded-full blur-3xl" style="background: rgba(255,255,255,0.08);"></div>
        <div class="absolute bottom-20 right-20 w-96 h-96 rounded-full blur-3xl" style="background: rgba(107,127,243,0.20);"></div>
        <div class="absolute top-1/2 left-1/3 w-48 h-48 rounded-full blur-2xl" style="background: rgba(139,156,247,0.15);"></div>

        <!-- Centered Card -->
        <div class="relative z-10 w-full max-w-md bg-white rounded-3xl shadow-2xl p-7 sm:p-9">
            <!-- Logo Lockup -->
            <div class="flex items-center justify-center gap-3 mb-7">
                <div class="w-11 h-11 shrink-0 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center p-1.5">
                    <img src="{{ asset('images/logo-ti-umk-icon.png') }}" alt="TI-UMK" class="w-full h-full object-contain">
                </div>
                <img src="{{ asset('images/logo-ti-umk-text.png') }}" alt="TI-UMK" class="h-6 w-auto object-contain">
            </div>

            {{ $slot }}
        </div>

        <p class="relative z-10 mt-6 text-xs text-center" style="color: #b5c1fb;">
            &copy; {{ date('Y') }} Portal Skripsi TI — Program Studi Teknik Informatika. All rights reserved.
        </p>
    </div>

    @stack('scripts')
</body>
</html>

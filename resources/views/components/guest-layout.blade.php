<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Skripsi TI' }} - Skripsi TI</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- Left Side - Branding -->
        <div class="hidden lg:flex lg:w-1/2 p-12 flex-col justify-between relative overflow-hidden" style="background: linear-gradient(135deg, #4361ee 0%, #3251d4 40%, #192355 100%);">
            <!-- Decorative circles -->
            <div class="absolute top-20 left-20 w-64 h-64 rounded-full blur-3xl" style="background: rgba(255,255,255,0.08);"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 rounded-full blur-3xl" style="background: rgba(107,127,243,0.20);"></div>
            <div class="absolute top-1/2 left-1/3 w-48 h-48 rounded-full blur-2xl" style="background: rgba(139,156,247,0.15);"></div>

            <!-- Logo & Title -->
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white tracking-tight">Skripsi TI</h1>
                </div>
                <p class="text-lg" style="color: #b5c1fb;">Sistem Informasi Tugas Akhir</p>
            </div>

            <!-- Feature List -->
            <div class="relative z-10 space-y-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-medium mb-1">Manajemen Mudah</h3>
                        <p class="text-indigo-200 text-sm">Kelola skripsi dan tugas akhir dengan mudah</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-medium mb-1">Aman & Terpercaya</h3>
                        <p class="text-indigo-200 text-sm">Data Anda terjaga dengan sistem keamanan tinggi</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-medium mb-1">Cepat & Responsif</h3>
                        <p class="text-indigo-200 text-sm">Akses dari mana saja dengan performa optimal</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative z-10">
                <p class="text-sm" style="color: #b5c1fb;">© 2026 Skripsi TI. All rights reserved.</p>
            </div>
        </div>

        <!-- Right Side - Content -->
        <div class="flex-1 flex items-center justify-center p-6 lg:p-12 bg-gray-50">
            <!-- Mobile Logo -->
            <div class="lg:hidden absolute top-6 left-6 flex items-center gap-2">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #4361ee;">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-900">Skripsi TI</span>
            </div>

            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

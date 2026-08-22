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
    <div class="min-h-screen flex flex-col lg:flex-row bg-white">

        <!-- Left Panel: Branding + Illustration -->
        <div class="hidden lg:flex lg:w-1/2 bg-slate-50 flex-col p-10 xl:p-14 relative overflow-hidden">
            <!-- Logo Lockup -->
            <div class="flex items-center gap-3 relative z-10">
                <div class="w-11 h-11 shrink-0 bg-white border border-slate-200 rounded-xl flex items-center justify-center p-1.5 shadow-sm">
                    <img src="{{ asset('images/logo-ti-umk-icon.png') }}" alt="TI-UMK" class="w-full h-full object-contain">
                </div>
                <div>
                    <p class="font-extrabold text-slate-800 text-sm leading-tight">TI &ndash; UMK</p>
                    <p class="text-[11px] text-slate-500">Portal Skripsi</p>
                </div>
            </div>

            <!-- Illustration -->
            <div class="flex-1 flex items-center justify-center relative z-10">
                <svg viewBox="0 0 420 360" class="w-full max-w-md" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- floating decorative shapes -->
                    <rect x="28" y="40" width="18" height="18" rx="4" fill="#22d3ee" opacity="0.5" transform="rotate(15 37 49)"/>
                    <rect x="368" y="70" width="14" height="14" rx="3" fill="#fbbf24" opacity="0.6" transform="rotate(-10 375 77)"/>
                    <circle cx="52" cy="290" r="9" fill="#4361ee" opacity="0.35"/>
                    <circle cx="392" cy="270" r="7" fill="#22d3ee" opacity="0.45"/>

                    <!-- back document card -->
                    <rect x="70" y="70" width="200" height="250" rx="18" fill="#e0e7ff" transform="rotate(-6 170 195)"/>

                    <!-- main device / screen -->
                    <rect x="120" y="55" width="220" height="255" rx="20" fill="#ffffff" stroke="#c7d2fe" stroke-width="2"/>
                    <rect x="120" y="55" width="220" height="46" rx="20" fill="#4361ee"/>
                    <rect x="120" y="81" width="220" height="20" fill="#4361ee"/>
                    <circle cx="145" cy="78" r="6" fill="#ffffff" opacity="0.85"/>
                    <rect x="163" y="73" width="80" height="9" rx="4.5" fill="#ffffff" opacity="0.85"/>

                    <!-- body lines -->
                    <rect x="140" y="122" width="180" height="10" rx="5" fill="#e2e8f0"/>
                    <rect x="140" y="144" width="150" height="10" rx="5" fill="#e2e8f0"/>
                    <rect x="140" y="166" width="165" height="10" rx="5" fill="#e2e8f0"/>

                    <!-- highlighted "approved" row -->
                    <rect x="140" y="196" width="180" height="34" rx="10" fill="#eef2ff"/>
                    <circle cx="158" cy="213" r="10" fill="#10b981"/>
                    <path d="M153 213l3.5 3.5L164 209" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="178" y="208" width="120" height="9" rx="4.5" fill="#c7d2fe"/>

                    <rect x="140" y="248" width="120" height="10" rx="5" fill="#e2e8f0"/>
                    <rect x="140" y="270" width="150" height="10" rx="5" fill="#e2e8f0"/>

                    <!-- graduation cap badge -->
                    <circle cx="322" cy="266" r="34" fill="#ffffff" stroke="#e2e8f0" stroke-width="2"/>
                    <path d="M300 260l22-9 22 9-22 9-22-9z" fill="#192355"/>
                    <path d="M308 264v10c0 4 6.5 7 14 7s14-3 14-7v-10" stroke="#192355" stroke-width="2.4" stroke-linecap="round"/>
                    <circle cx="340" cy="262" r="2.2" fill="#fbbf24"/>

                    <!-- big checkmark shield, top-right of device -->
                    <circle cx="328" cy="88" r="26" fill="#fbbf24"/>
                    <path d="M317 88l7 7 13-14" stroke="#ffffff" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Footer -->
            <p class="text-xs text-slate-400 relative z-10">
                &copy; {{ date('Y') }} Portal Skripsi TI &mdash; Program Studi Teknik Informatika, Universitas Muria Kudus.
            </p>
        </div>

        <!-- Right Panel: Form -->
        <div class="flex-1 flex flex-col items-center justify-center p-6 sm:p-10 bg-white">
            <!-- Mobile Logo -->
            <div class="lg:hidden flex flex-col items-center gap-2 mb-8">
                <div class="w-14 h-14 shrink-0 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center p-2">
                    <img src="{{ asset('images/logo-ti-umk-icon.png') }}" alt="TI-UMK" class="w-full h-full object-contain">
                </div>
                <img src="{{ asset('images/logo-ti-umk-text.png') }}" alt="TI-UMK" class="h-6 w-auto object-contain">
            </div>

            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

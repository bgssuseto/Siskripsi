<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
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

    <!-- Dark Mode Initializer Script (Default: Dark Mode) -->
    <script>
        if (localStorage.getItem('theme') !== 'light') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased"
      x-data="{ darkMode: localStorage.getItem('theme') !== 'light' }">
    <div class="min-h-screen flex flex-col lg:flex-row bg-white dark:bg-slate-950 transition-colors duration-200">

        <!-- Left Panel: Branding + Illustration -->
        <div class="hidden lg:flex lg:w-1/2 bg-slate-50 dark:bg-slate-900 flex-col p-10 xl:p-14 relative overflow-hidden transition-colors duration-200">
            <!-- Logo Lockup -->
            <div class="flex items-center gap-3.5 relative z-10">
                <div class="w-16 h-16 shrink-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl flex items-center justify-center p-2 shadow-sm">
                    <img src="{{ asset('images/logo-ti-umk-icon.png') }}" alt="TI-UMK" class="w-full h-full object-contain">
                </div>
                <div>
                    <p class="font-extrabold text-slate-800 dark:text-slate-100 text-xl leading-tight">TI &ndash; UMK</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Portal Skripsi</p>
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
                    <rect x="70" y="70" width="200" height="250" rx="18" class="fill-indigo-100 dark:fill-slate-800" transform="rotate(-6 170 195)"/>

                    <!-- main device / screen -->
                    <rect x="120" y="55" width="220" height="255" rx="20" class="fill-white dark:fill-slate-900" stroke="#c7d2fe" stroke-width="2"/>
                    <rect x="120" y="55" width="220" height="46" rx="20" fill="#4361ee"/>
                    <rect x="120" y="81" width="220" height="20" fill="#4361ee"/>
                    <circle cx="145" cy="78" r="6" fill="#ffffff" opacity="0.85"/>
                    <rect x="163" y="73" width="80" height="9" rx="4.5" fill="#ffffff" opacity="0.85"/>

                    <!-- body lines -->
                    <rect x="140" y="122" width="180" height="10" rx="5" class="fill-slate-200 dark:fill-slate-700"/>
                    <rect x="140" y="144" width="150" height="10" rx="5" class="fill-slate-200 dark:fill-slate-700"/>
                    <rect x="140" y="166" width="165" height="10" rx="5" class="fill-slate-200 dark:fill-slate-700"/>

                    <!-- highlighted "approved" row -->
                    <rect x="140" y="196" width="180" height="34" rx="10" class="fill-indigo-50 dark:fill-indigo-950/50"/>
                    <circle cx="158" cy="213" r="10" fill="#10b981"/>
                    <path d="M153 213l3.5 3.5L164 209" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="178" y="208" width="120" height="9" rx="4.5" fill="#c7d2fe"/>

                    <rect x="140" y="248" width="120" height="10" rx="5" class="fill-slate-200 dark:fill-slate-700"/>
                    <rect x="140" y="270" width="150" height="10" rx="5" class="fill-slate-200 dark:fill-slate-700"/>

                    <!-- graduation cap badge -->
                    <circle cx="322" cy="266" r="34" class="fill-white dark:fill-slate-900" stroke="#e2e8f0" stroke-width="2"/>
                    <path d="M300 260l22-9 22 9-22 9-22-9z" fill="#192355"/>
                    <path d="M308 264v10c0 4 6.5 7 14 7s14-3 14-7v-10" stroke="#192355" stroke-width="2.4" stroke-linecap="round"/>
                    <circle cx="340" cy="262" r="2.2" fill="#fbbf24"/>

                    <!-- big checkmark shield, top-right of device -->
                    <circle cx="328" cy="88" r="26" fill="#fbbf24"/>
                    <path d="M317 88l7 7 13-14" stroke="#ffffff" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Footer -->
            <p class="text-xs text-slate-400 dark:text-slate-500 relative z-10">
                &copy; {{ date('Y') }} Portal Skripsi TI &mdash; Program Studi Teknik Informatika, Universitas Muria Kudus.
            </p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 relative z-10 mt-1">
                Made with <span class="text-rose-500">&hearts;</span> by Tim Pengembang TI UMK
            </p>
        </div>

        <!-- Right Panel: Form -->
        <div class="flex-1 flex flex-col items-center justify-center p-6 sm:p-10 bg-white dark:bg-slate-950 relative transition-colors duration-200">

            <!-- Dark Mode Toggle -->
            <button type="button"
                    @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light'); if(darkMode) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); }"
                    class="absolute top-5 right-5 sm:top-6 sm:right-6 flex items-center justify-center w-11 h-11 rounded-2xl text-slate-600 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-all border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm z-20"
                    aria-label="Ganti mode tampilan">
                <template x-if="!darkMode">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </template>
                <template x-if="darkMode">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </template>
            </button>

            <!-- Mobile Logo -->
            <div class="lg:hidden flex flex-col items-center gap-2.5 mb-8">
                <div class="w-20 h-20 shrink-0 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl flex items-center justify-center p-2.5">
                    <img src="{{ asset('images/logo-ti-umk-icon.png') }}" alt="TI-UMK" class="w-full h-full object-contain">
                </div>
                <img x-show="!darkMode" src="{{ asset('images/logo-ti-umk-text.png') }}" alt="TI-UMK" class="h-8 w-auto object-contain">
                <img x-show="darkMode" src="{{ asset('images/logo-ti-umk-text-white.png') }}" alt="TI-UMK" class="h-8 w-auto object-contain">
                <p class="text-sm text-slate-500 dark:text-slate-400">Portal Skripsi</p>
            </div>

            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

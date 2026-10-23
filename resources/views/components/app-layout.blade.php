<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} - Sistem Informasi Skripsi TI</title>

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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Dark Mode Initializer Script (Default: Dark Mode) -->
    <script>
        if (localStorage.getItem('theme') !== 'light') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* Sidebar transition */
        .sidebar-expanded { width: 260px; }
        .sidebar-collapsed { width: 72px; }

        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

        /* Smooth submenu */
        [x-cloak] { display: none !important; }

        /* Global Dark Mode Styling */
        html.dark body {
            background-color: #0b0f19 !important;
            color: #f8fafc !important;
        }

        html.dark header {
            background-color: #111827 !important;
            border-color: #1e293b !important;
            color: #f8fafc !important;
        }

        html.dark .bg-white {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }

        html.dark .bg-slate-50, html.dark .bg-slate-50\/40, html.dark .bg-slate-50\/50, html.dark .bg-slate-50\/60, html.dark .bg-slate-50\/80 {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
            border-color: #1e293b !important;
        }

        html.dark .bg-slate-100 {
            background-color: #334155 !important;
            color: #f1f5f9 !important;
        }

        html.dark .text-slate-900, html.dark .text-slate-800 {
            color: #f8fafc !important;
        }

        html.dark .text-slate-700, html.dark .text-slate-600 {
            color: #cbd5e1 !important;
        }

        html.dark .text-slate-500, html.dark .text-slate-400 {
            color: #94a3b8 !important;
        }

        html.dark .border-slate-200, html.dark .border-slate-200\/80, html.dark .border-slate-200\/90, html.dark .border-slate-100 {
            border-color: #334155 !important;
        }

        html.dark input, html.dark select, html.dark textarea {
            background-color: #0f172a !important;
            color: #f8fafc !important;
            border-color: #475569 !important;
        }

        html.dark input::placeholder, html.dark textarea::placeholder {
            color: #64748b !important;
        }

        html.dark tr:hover {
            background-color: #334155 !important;
        }

        /* High-Contrast Badges & Text adjustments for Dark Mode */
        html.dark .bg-amber-50, html.dark .bg-amber-100 {
            background-color: rgba(245, 158, 11, 0.18) !important;
            color: #fcd34d !important;
            border-color: rgba(245, 158, 11, 0.35) !important;
        }

        html.dark .bg-emerald-50, html.dark .bg-emerald-100 {
            background-color: rgba(16, 185, 129, 0.18) !important;
            color: #6ee7b7 !important;
            border-color: rgba(16, 185, 129, 0.35) !important;
        }

        html.dark .bg-rose-50, html.dark .bg-rose-100 {
            background-color: rgba(244, 63, 94, 0.18) !important;
            color: #fda4af !important;
            border-color: rgba(244, 63, 94, 0.35) !important;
        }

        html.dark .bg-indigo-50, html.dark .bg-indigo-100 {
            background-color: rgba(67, 97, 238, 0.18) !important;
            color: #8b9cf7 !important;
            border-color: rgba(67, 97, 238, 0.35) !important;
        }

        html.dark .bg-purple-50, html.dark .bg-purple-100 {
            background-color: rgba(168, 85, 247, 0.18) !important;
            color: #d8b4fe !important;
            border-color: rgba(168, 85, 247, 0.35) !important;
        }

        /* Extended Dark Mode Safety-Net — additional color families */
        html.dark .bg-gray-50, html.dark .bg-gray-50\/40, html.dark .bg-gray-50\/50, html.dark .bg-gray-50\/60, html.dark .bg-gray-50\/80 {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
            border-color: #1e293b !important;
        }

        html.dark .bg-gray-100 {
            background-color: #334155 !important;
            color: #f1f5f9 !important;
        }

        html.dark .text-gray-900, html.dark .text-gray-800 {
            color: #f8fafc !important;
        }

        html.dark .text-gray-700, html.dark .text-gray-600 {
            color: #cbd5e1 !important;
        }

        html.dark .text-gray-500, html.dark .text-gray-400 {
            color: #94a3b8 !important;
        }

        html.dark .border-gray-200, html.dark .border-gray-200\/80, html.dark .border-gray-200\/90, html.dark .border-gray-100, html.dark .border-gray-300 {
            border-color: #334155 !important;
        }

        html.dark .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
        html.dark .divide-slate-200 > :not([hidden]) ~ :not([hidden]),
        html.dark .divide-gray-100 > :not([hidden]) ~ :not([hidden]),
        html.dark .divide-gray-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: #334155 !important;
        }

        html.dark .bg-violet-50, html.dark .bg-violet-100 {
            background-color: rgba(139, 92, 246, 0.18) !important;
            color: #c4b5fd !important;
            border-color: rgba(139, 92, 246, 0.35) !important;
        }

        html.dark .bg-blue-50, html.dark .bg-blue-100 {
            background-color: rgba(59, 130, 246, 0.18) !important;
            color: #93c5fd !important;
            border-color: rgba(59, 130, 246, 0.35) !important;
        }

        html.dark .bg-red-50, html.dark .bg-red-100 {
            background-color: rgba(239, 68, 68, 0.18) !important;
            color: #fca5a5 !important;
            border-color: rgba(239, 68, 68, 0.35) !important;
        }

        html.dark .bg-green-50, html.dark .bg-green-100 {
            background-color: rgba(34, 197, 94, 0.18) !important;
            color: #86efac !important;
            border-color: rgba(34, 197, 94, 0.35) !important;
        }

        html.dark .bg-teal-50, html.dark .bg-teal-100 {
            background-color: rgba(20, 184, 166, 0.18) !important;
            color: #5eead4 !important;
            border-color: rgba(20, 184, 166, 0.35) !important;
        }

        html.dark .bg-cyan-50, html.dark .bg-cyan-100 {
            background-color: rgba(6, 182, 212, 0.18) !important;
            color: #67e8f9 !important;
            border-color: rgba(6, 182, 212, 0.35) !important;
        }

        html.dark .bg-sky-50, html.dark .bg-sky-100 {
            background-color: rgba(14, 165, 233, 0.18) !important;
            color: #7dd3fc !important;
            border-color: rgba(14, 165, 233, 0.35) !important;
        }

        html.dark .bg-orange-50, html.dark .bg-orange-100 {
            background-color: rgba(249, 115, 22, 0.18) !important;
            color: #fdba74 !important;
            border-color: rgba(249, 115, 22, 0.35) !important;
        }

        html.dark .bg-yellow-50, html.dark .bg-yellow-100 {
            background-color: rgba(234, 179, 8, 0.18) !important;
            color: #fde047 !important;
            border-color: rgba(234, 179, 8, 0.35) !important;
        }

        html.dark .text-violet-600, html.dark .text-violet-700, html.dark .text-violet-800 { color: #c4b5fd !important; }
        html.dark .text-blue-600, html.dark .text-blue-700, html.dark .text-blue-800 { color: #93c5fd !important; }
        html.dark .text-red-600, html.dark .text-red-700, html.dark .text-red-800 { color: #fca5a5 !important; }
        html.dark .text-green-600, html.dark .text-green-700, html.dark .text-green-800 { color: #86efac !important; }
        html.dark .text-teal-600, html.dark .text-teal-700, html.dark .text-teal-800 { color: #5eead4 !important; }
        html.dark .text-cyan-600, html.dark .text-cyan-700, html.dark .text-cyan-800 { color: #67e8f9 !important; }
        html.dark .text-sky-600, html.dark .text-sky-700, html.dark .text-sky-800 { color: #7dd3fc !important; }
        html.dark .text-orange-600, html.dark .text-orange-700, html.dark .text-orange-800 { color: #fdba74 !important; }
        html.dark .text-yellow-600, html.dark .text-yellow-700, html.dark .text-yellow-800 { color: #fde047 !important; }

        html.dark .border-violet-200, html.dark .border-violet-300 { border-color: rgba(139, 92, 246, 0.35) !important; }
        html.dark .border-blue-200, html.dark .border-blue-300 { border-color: rgba(59, 130, 246, 0.35) !important; }
        html.dark .border-red-200, html.dark .border-red-300 { border-color: rgba(239, 68, 68, 0.35) !important; }
        html.dark .border-green-200, html.dark .border-green-300 { border-color: rgba(34, 197, 94, 0.35) !important; }
        html.dark .border-teal-200, html.dark .border-teal-300 { border-color: rgba(20, 184, 166, 0.35) !important; }
        html.dark .border-cyan-200, html.dark .border-cyan-300 { border-color: rgba(6, 182, 212, 0.35) !important; }
        html.dark .border-sky-200, html.dark .border-sky-300 { border-color: rgba(14, 165, 233, 0.35) !important; }
        html.dark .border-orange-200, html.dark .border-orange-300 { border-color: rgba(249, 115, 22, 0.35) !important; }
        html.dark .border-yellow-200, html.dark .border-yellow-300 { border-color: rgba(234, 179, 8, 0.35) !important; }

        html.dark .hover\:bg-gray-50:hover, html.dark .hover\:bg-slate-50:hover {
            background-color: #1e293b !important;
        }

        /* ===================== LIGHT-MODE SIDEBAR ===================== */
        /* The sidebar is built dark-first (flat dark fill + white text). Give it
           an explicit light theme when the app is NOT in dark mode, instead of leaving it
           stuck looking "dark" regardless of the toggle. */
        html:not(.dark) #app-sidebar {
            background: #ffffff !important;
            border-right: 1px solid #e2e8f0;
        }

        html:not(.dark) #app-sidebar .border-white\/\[0\.07\],
        html:not(.dark) #app-sidebar .border-white\/\[0\.05\] {
            border-color: #e2e8f0 !important;
        }

        html:not(.dark) #app-sidebar .text-slate-400 { color: #64748b !important; }
        html:not(.dark) #app-sidebar .text-slate-500 { color: #475569 !important; }
        html:not(.dark) #app-sidebar .text-slate-200 { color: #334155 !important; }

        html:not(.dark) #app-sidebar .hover\:bg-white\/\[0\.06\]:hover,
        html:not(.dark) #app-sidebar .bg-white\/\[0\.08\] {
            background-color: #eef1fe !important;
        }

        /* Base: any inherited/explicit white text turns dark on a light sidebar... */
        html:not(.dark) #app-sidebar .text-white { color: #1e293b !important; }
        html:not(.dark) #app-sidebar .hover\:text-white:hover { color: #1e293b !important; }

        /* ...except where it sits on a strong/solid colored pill (active nav item),
           where white text stays legible on any background theme. */
        html:not(.dark) #app-sidebar .bg-indigo-600.text-white,
        html:not(.dark) #app-sidebar .bg-indigo-600 .text-white,
        html:not(.dark) #app-sidebar .bg-indigo-600\/70.text-white,
        html:not(.dark) #app-sidebar .bg-indigo-600\/70 .text-white,
        html:not(.dark) #app-sidebar .bg-indigo-600\/80.text-white,
        html:not(.dark) #app-sidebar .bg-indigo-600\/80 .text-white,
        html:not(.dark) #app-sidebar .bg-blue-600\/70.text-white,
        html:not(.dark) #app-sidebar .bg-blue-600\/70 .text-white,
        html:not(.dark) #app-sidebar .bg-amber-600\/70.text-white,
        html:not(.dark) #app-sidebar .bg-amber-600\/70 .text-white,
        html:not(.dark) #app-sidebar .bg-violet-600\/80.text-white,
        html:not(.dark) #app-sidebar .bg-violet-600\/80 .text-white {
            color: #ffffff !important;
        }

        /* Translucent "active group / active child" pills paired with light-tinted text
           need both sides re-balanced for a light background (dark text on a soft tint). */
        html:not(.dark) #app-sidebar .bg-indigo-600\/30 { background-color: #e0e7ff !important; }
        html:not(.dark) #app-sidebar .text-indigo-300 { color: #3730a3 !important; }
        html:not(.dark) #app-sidebar .bg-amber-600\/20 { background-color: #fef3c7 !important; }
        html:not(.dark) #app-sidebar .text-amber-300 { color: #92400e !important; }
        html:not(.dark) #app-sidebar .bg-blue-600\/20 { background-color: #dbeafe !important; }
        html:not(.dark) #app-sidebar .text-blue-300 { color: #1d4ed8 !important; }

        html:not(.dark) #app-sidebar .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(15, 23, 42, 0.15) !important;
        }
        html:not(.dark) #app-sidebar .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(15, 23, 42, 0.25) !important;
        }

        html.dark .shadow, html.dark .shadow-sm, html.dark .shadow-md, html.dark .shadow-lg, html.dark .shadow-xl {
            --tw-shadow-color: rgba(0, 0, 0, 0.4);
        }

        html.dark .nim-pill {
            background-color: rgba(14, 165, 233, 0.16) !important;
            color: #7dd3fc !important;
        }

        html.dark .judul-text {
            color: #f1f5f9 !important;
        }

        /* Highlight Nama & Judul in Crisp Bright White on Row Hover in Dark Mode */
        html.dark tr:hover td .text-slate-900,
        html.dark tr:hover td .text-slate-800,
        html.dark tr:hover td .text-slate-700,
        html.dark tr:hover td .judul-text,
        html.dark tr:hover td div.font-extrabold,
        html.dark tr:hover td p.font-bold,
        html.dark tr:hover td h3,
        html.dark tr:hover td h4 {
            color: #ffffff !important;
            transition: color 0.15s ease;
        }

        html.dark td, html.dark th {
            border-color: #334155 !important;
        }

        /* Stat Infographics High Contrast Rules */
        .stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; }
        .stat-value { color: #0f172a !important; font-weight: 800 !important; }
        .stat-label { color: #475569 !important; font-weight: 600 !important; }
        html.dark .stat-card { background-color: #1e293b !important; border-color: #334155 !important; }
        html.dark .stat-value { color: #f8fafc !important; }
        html.dark .stat-label { color: #cbd5e1 !important; }

        /* Tema FullCalendar: resources/css/calendar.css */
    </style>
</head>
<body class="h-full antialiased text-slate-800 bg-slate-100 dark:bg-slate-950 dark:text-slate-100 transition-colors duration-200"
      x-data="{
          darkMode: localStorage.getItem('theme') !== 'light',
          sidebarOpen: true,
          mobileOpen: false,
          dataMasterOpen: {{ request()->routeIs('master.dosen.*') || request()->routeIs('master.ruang.*') || request()->routeIs('master.periode.*') || request()->routeIs('master.dosen-penguji-rule.*') ? 'true' : 'false' }},
          manajemenOpen: {{ request()->routeIs('users.*') || request()->routeIs('admin.menus.*') || request()->routeIs('backup.*') ? 'true' : 'false' }},
          pendaftaranNavOpen: {{ request()->routeIs('pendaftaran.*') ? 'true' : 'false' }},
          dataOpen: {{ request()->routeIs('master.skripsi.*') || request()->routeIs('master.sempro.*') ? 'true' : 'false' }},
          penjadwalanOpen: {{ request()->routeIs('jadwal-ujian.*') || request()->routeIs('jadwal-sempro.*') ? 'true' : 'false' }},
          administrasiOpen: {{ request()->routeIs('administrasi.*') ? 'true' : 'false' }},
          profileDropdown: false
      }"
      @keydown.escape.window="mobileOpen = false; profileDropdown = false">

    <div class="flex h-full">

        <!-- ===================== OVERLAY MOBILE ===================== -->
        <div x-show="mobileOpen"
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileOpen = false"
             class="fixed inset-0 z-40 bg-black/50 backdrop-blur-[2px] lg:hidden"
             x-cloak></div>

        <!-- ===================== SIDEBAR ===================== -->
        <aside
            id="app-sidebar"
            class="fixed inset-y-0 left-0 z-50 flex flex-col bg-slate-900 text-white transition-all duration-300 ease-in-out border-r border-slate-800 lg:relative lg:z-auto lg:translate-x-0"
            :class="{
                'translate-x-0': mobileOpen,
                '-translate-x-full': !mobileOpen,
                'sidebar-expanded': sidebarOpen,
                'sidebar-collapsed': !sidebarOpen
            }">

            <!-- Brand / Logo -->
            <div class="flex items-center h-16 px-4 border-b border-white/[0.07] shrink-0">
                <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 min-w-0">
                    <div class="relative w-10 h-10 shrink-0">
                        <span aria-hidden="true" class="absolute -inset-0.5 rounded-[14px] bg-gradient-to-br from-indigo-500 via-violet-500 to-sky-400 opacity-60 blur-[6px] transition-opacity group-hover:opacity-90"></span>
                        <div class="relative w-10 h-10 rounded-xl flex items-center justify-center p-1.5 ring-1 ring-indigo-100 shadow-md shadow-indigo-500/10 transition-transform group-hover:scale-105" style="background-color:#fff">
                            <img src="{{ asset('images/logo-ti-umk-icon.png') }}" alt="TI-UMK" class="w-full h-full object-contain">
                        </div>
                    </div>
                    <div class="overflow-hidden transition-all duration-300" :class="sidebarOpen ? 'w-40 opacity-100' : 'w-0 opacity-0'">
                        <img x-show="darkMode" src="{{ asset('images/logo-ti-umk-text-white.png') }}" alt="TI-UMK" class="h-[18px] w-auto object-contain">
                        <img x-show="!darkMode" src="{{ asset('images/logo-ti-umk-text.png') }}" alt="TI-UMK" class="h-[18px] w-auto object-contain">
                        <p class="mt-1.5 flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-1.5 h-1.5 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 shadow-[0_0_0_3px_rgba(99,102,241,0.15)]"></span>
                            <span class="text-[9.5px] font-extrabold uppercase tracking-[0.2em] bg-gradient-to-r from-indigo-500 to-violet-500 bg-clip-text text-transparent">Portal Akademik</span>
                        </p>
                    </div>
                </a>

                <!-- Close button (mobile) -->
                <button @click="mobileOpen = false" class="lg:hidden ml-auto p-1 text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- ── Scrollable Nav Items ── -->
            <nav class="flex-1 py-5 px-3 overflow-y-auto sidebar-scroll space-y-0.5">

                <!-- Section: MAIN -->
                @if(!Auth::user()->hasRole('dosen'))
                <div class="mb-2">
                    <p class="px-3 text-[9px] font-extrabold uppercase tracking-[0.15em] text-slate-500 mb-1 transition-all duration-300"
                       :class="sidebarOpen ? 'opacity-100' : 'opacity-0'">Menu Utama</p>

                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                       class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                              {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                       title="Dashboard">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Dashboard</span>
                        @if(!request()->routeIs('dashboard'))
                        <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                        @endif
                    </a>
                </div>
                @endif

                    <!-- Section: MAHASISWA -->
                    @if(Auth::user()->hasRole('mahasiswa'))
                    <div class="mb-2 pt-3">
                        <p class="px-3 text-[9px] font-extrabold uppercase tracking-[0.15em] text-slate-500 mb-1 transition-all duration-300"
                           :class="sidebarOpen ? 'opacity-100' : 'opacity-0'">Mahasiswa</p>

                        @php
                            $mahasiswaMenus = Auth::user()->getAccessibleMenus();
                            $parentMenus = $mahasiswaMenus->whereNull('parent_id')->filter(function ($m) {
                                return strtolower($m->name) !== 'dashboard' && !str_contains(strtolower($m->name), 'dashboard');
                            });
                        @endphp

                        @foreach($parentMenus as $parent)
                            @if($parent->is_active)
                                @php
                                    $children = $mahasiswaMenus->where('parent_id', $parent->id)->where('is_active', true);
                                @endphp

                                @if($children->isEmpty())
                                    @php
                                        $routeName = $parent->route;
                                        $targetUrl = ($routeName && Route::has($routeName)) ? route($routeName) : '#';
                                        $isActive = $routeName ? (request()->routeIs($routeName . '*') || ($routeName === 'mahasiswa.sempro.index' && request()->routeIs('mahasiswa.pendaftaran.*'))) : false;
                                    @endphp
                                    <a href="{{ $targetUrl }}"
                                       class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                              {{ $isActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                       title="{{ $parent->name }}">
                                        <svg class="w-5 h-5 shrink-0 {{ $isActive ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if(str_contains(strtolower($parent->name), 'dashboard'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                            @elseif(str_contains(strtolower($parent->name), 'pendaftaran') || str_contains(strtolower($parent->name), 'daftar'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            @elseif(str_contains(strtolower($parent->name), 'jadwal') || str_contains(strtolower($parent->name), 'penjadwalan'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            @elseif(str_contains(strtolower($parent->name), 'bebas'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            @endif
                                        </svg>
                                        <span class="truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">{{ $parent->name }}</span>
                                        @if(!$isActive)
                                        <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                        @endif
                                    </a>
                                @else
                                    @php
                                        $childRoutes = $children->pluck('route')->filter()->toArray();
                                        $isActive = false;
                                        foreach($childRoutes as $cr) {
                                            if(request()->routeIs($cr . '*')) {
                                                $isActive = true;
                                            }
                                        }
                                    @endphp
                                    <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                                        <button @click="open = !open; if(!sidebarOpen) sidebarOpen = true"
                                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                                       {{ $isActive ? 'bg-white/[0.08] text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                                title="{{ $parent->name }}">
                                            <svg class="w-5 h-5 shrink-0 {{ $isActive ? 'text-indigo-400' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if(str_contains(strtolower($parent->name), 'pendaftaran') || str_contains(strtolower($parent->name), 'daftar'))
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                @elseif(str_contains(strtolower($parent->name), 'jadwal') || str_contains(strtolower($parent->name), 'penjadwalan'))
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                                @endif
                                            </svg>
                                            <span class="flex-1 text-left truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">{{ $parent->name }}</span>
                                            <!-- Chevron -->
                                            <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                                 :class="{ 'rotate-180': open, 'opacity-0': !sidebarOpen }"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                            @if(!$isActive)
                                            <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                            @endif
                                        </button>

                                        <!-- Submenu -->
                                        <div x-show="open && sidebarOpen"
                                             x-collapse.duration.250ms
                                             class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                                             x-cloak>
                                            
                                            @foreach($children as $child)
                                                @php
                                                    $childRoute = $child->route;
                                                    $childUrl = ($childRoute && Route::has($childRoute)) ? route($childRoute) : '#';
                                                    $childActive = $childRoute ? request()->routeIs($childRoute . '*') : false;
                                                @endphp
                                                <a href="{{ $childUrl }}"
                                                   class="nav-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200 group relative
                                                          {{ $childActive ? 'bg-indigo-600/30 text-indigo-300 font-bold border border-indigo-500/30' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                                   title="{{ $child->name }}">
                                                    <span class="w-1.5 h-1.5 rounded-full {{ $childActive ? 'bg-indigo-400' : 'bg-slate-600 group-hover:bg-indigo-400' }} transition-colors"></span>
                                                    <span class="truncate">{{ $child->name }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </div>
                    @endif

                    <!-- Section: DOSEN -->
                    @if(Auth::user()->hasRole('dosen'))
                    <div class="mb-2 pt-3">
                        <p class="px-3 text-[9px] font-extrabold uppercase tracking-[0.15em] text-slate-500 mb-1 transition-all duration-300"
                           :class="sidebarOpen ? 'opacity-100' : 'opacity-0'">Dosen</p>

                        @php
                            $dosenMenus = Auth::user()->getAccessibleMenus();
                            $parentMenus = $dosenMenus->whereNull('parent_id');
                        @endphp

                        @foreach($parentMenus as $parent)
                            @if($parent->is_active)
                                @php
                                    $children = $dosenMenus->where('parent_id', $parent->id)->where('is_active', true);
                                @endphp

                                @if($children->isEmpty())
                                    @php
                                        $routeName = $parent->route;
                                    @endphp
                                    @if(!$routeName)
                                        @continue
                                    @endif
                                    @php
                                        $targetUrl = Route::has($routeName) ? route($routeName) : '#';
                                        $isActive = request()->routeIs($routeName . '*');
                                    @endphp
                                    <a href="{{ $targetUrl }}"
                                       class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                              {{ $isActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                       title="{{ $parent->name }}">
                                        <svg class="w-5 h-5 shrink-0 {{ $isActive ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($parent->icon === 'home' || str_contains(strtolower($parent->name), 'dashboard'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                            @elseif($parent->icon === 'clock' || str_contains(strtolower($parent->name), 'kalender'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            @elseif($parent->icon === 'user' || str_contains(strtolower($parent->name), 'profil'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            @elseif($parent->icon === 'mail' || str_contains(strtolower($parent->name), 'undangan'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            @elseif($parent->icon === 'badge')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            @endif
                                        </svg>
                                        <span class="truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">{{ $parent->name }}</span>
                                        @if(!$isActive)
                                        <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                        @endif
                                    </a>
                                @else
                                    @php
                                        $stateName = strtolower(str_replace(' ', '', $parent->name)) . 'Open';
                                        $childRoutes = $children->pluck('route')->filter()->toArray();
                                        $isActive = false;
                                        foreach($childRoutes as $cr) {
                                            if(request()->routeIs($cr . '*')) {
                                                $isActive = true;
                                            }
                                        }
                                    @endphp
                                    <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                                        <button @click="open = !open; if(!sidebarOpen) sidebarOpen = true"
                                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                                       {{ $isActive ? 'bg-white/[0.08] text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                                title="{{ $parent->name }}">
                                            <svg class="w-5 h-5 shrink-0 {{ $isActive ? 'text-indigo-400' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if(str_contains(strtolower($parent->name), 'jadwal'))
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                                @endif
                                            </svg>
                                            <span class="flex-1 text-left truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">{{ $parent->name }}</span>
                                            <!-- Chevron -->
                                            <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                                 :class="{ 'rotate-180': open, 'opacity-0': !sidebarOpen }"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                            @if(!$isActive)
                                            <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                            @endif
                                        </button>

                                        <!-- Submenu -->
                                        <div x-show="open && sidebarOpen"
                                             x-collapse.duration.250ms
                                             class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                                             x-cloak>
                                            
                                            @foreach($children as $child)
                                                @php
                                                    $childRoute = $child->route;
                                                    $childUrl = ($childRoute && Route::has($childRoute)) ? route($childRoute) : '#';
                                                    $childActive = $childRoute ? request()->routeIs($childRoute . '*') : false;
                                                @endphp
                                                <a href="{{ $childUrl }}"
                                                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                                          {{ $childActive ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                                    <svg class="w-4 h-4 shrink-0 {{ $childActive ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        @if(str_contains(strtolower($child->name), 'proposal') || str_contains(strtolower($child->name), 'sempro'))
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6"/>
                                                        @elseif(str_contains(strtolower($child->name), 'skripsi') || str_contains(strtolower($child->name), 'sidang'))
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        @else
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                        @endif
                                                    </svg>
                                                    {{ $child->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </div>
                    @endif

                @if(Auth::user()->isSuperAdmin())
                <!-- Section: ADMINISTRASI (Super Admin only) -->
                <div class="mb-2 pt-3">
                    <p class="px-3 text-[9px] font-extrabold uppercase tracking-[0.15em] text-slate-500 mb-1 transition-all duration-300"
                       :class="sidebarOpen ? 'opacity-100' : 'opacity-0'">Administrasi</p>

                    <!-- MANAJEMEN (with submenu: Manajemen Menu, Manajemen User) -->
                    <div x-data>
                        <button @click="manajemenOpen = !manajemenOpen; if(!sidebarOpen) sidebarOpen = true"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                       {{ request()->routeIs('users.*') || request()->routeIs('admin.menus.*') || request()->routeIs('backup.*') ? 'bg-white/[0.08] text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                title="Manajemen">
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('users.*') || request()->routeIs('admin.menus.*') || request()->routeIs('backup.*') ? 'text-indigo-400' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="flex-1 text-left truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Manajemen</span>
                            <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                 :class="{ 'rotate-180': manajemenOpen, 'opacity-0': !sidebarOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            @if(!request()->routeIs('users.*') && !request()->routeIs('admin.menus.*') && !request()->routeIs('backup.*'))
                            <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            @endif
                        </button>
                        <div x-show="manajemenOpen && sidebarOpen"
                             x-collapse.duration.250ms
                             class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                             x-cloak>
                            <a href="{{ route('admin.menus.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('admin.menus.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.menus.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Manajemen Menu
                            </a>
                            <a href="{{ route('users.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('users.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('users.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                Manajemen User
                            </a>
                            <a href="{{ route('backup.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('backup.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('backup.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                </svg>
                                Backup &amp; Restore
                            </a>
                        </div>
                    </div>

                    <!-- DATA MASTER (with submenu) -->
                    <div x-data>
                        <button @click="dataMasterOpen = !dataMasterOpen"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                       {{ request()->routeIs('master.dosen.*') || request()->routeIs('master.ruang.*') || request()->routeIs('master.periode.*') ? 'bg-white/[0.08] text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                title="Data Master">
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('master.dosen.*') || request()->routeIs('master.ruang.*') || request()->routeIs('master.periode.*') ? 'text-indigo-400' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                            </svg>
                            <span class="flex-1 text-left truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Data Master</span>
                            <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                 :class="{ 'rotate-180': dataMasterOpen, 'opacity-0': !sidebarOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            @if(!request()->routeIs('master.dosen.*') && !request()->routeIs('master.ruang.*') && !request()->routeIs('master.periode.*') && !request()->routeIs('master.dosen-penguji-rule.*'))
                            <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            @endif
                        </button>
                        <div x-show="dataMasterOpen && sidebarOpen"
                             x-collapse.duration.250ms
                             class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                             x-cloak>
                            <a href="{{ route('master.dosen.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('master.dosen.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('master.dosen.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Master Dosen
                            </a>
                            <a href="{{ route('master.ruang.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('master.ruang.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('master.ruang.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                Master Ruang
                            </a>
                            <a href="{{ route('master.periode.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('master.periode.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('master.periode.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Master Periode
                            </a>
                            <a href="{{ route('master.dosen-penguji-rule.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('master.dosen-penguji-rule.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('master.dosen-penguji-rule.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Rule Komposisi Penguji
                            </a>
                        </div>
                    </div>

                    <!-- PENDAFTARAN (with submenu) -->
                    <div>
                        <button @click="pendaftaranNavOpen = !pendaftaranNavOpen; if(!sidebarOpen) sidebarOpen = true"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                       {{ request()->routeIs('pendaftaran.*') ? 'bg-white/[0.08] text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                title="Pendaftaran">
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('pendaftaran.*') ? 'text-indigo-400' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="flex-1 text-left truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Pendaftaran</span>
                            <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                 :class="{ 'rotate-180': pendaftaranNavOpen, 'opacity-0': !sidebarOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            @if(!request()->routeIs('pendaftaran.*'))
                            <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            @endif
                        </button>
                        <div x-show="pendaftaranNavOpen && sidebarOpen"
                             x-collapse.duration.250ms
                             class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                             x-cloak>
                            <a href="{{ route('pendaftaran.sempro') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('pendaftaran.sempro') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('pendaftaran.sempro') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Sempro
                            </a>
                            <a href="{{ route('pendaftaran.skripsi') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('pendaftaran.skripsi') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('pendaftaran.skripsi') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                Skripsi
                            </a>
                        </div>
                    </div>

                    <!-- BEBAS PRODI -->
                    <a href="{{ route('bebas-prodi.index') }}"
                       class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                              {{ request()->routeIs('bebas-prodi.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                       title="Bebas Prodi">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('bebas-prodi.*') ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        <span class="truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Bebas Prodi</span>
                        @if(!request()->routeIs('bebas-prodi.*'))
                        <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                        @endif
                    </a>

                    <!-- DATA (with submenu) -->
                    <div>
                        <button @click="dataOpen = !dataOpen; if(!sidebarOpen) sidebarOpen = true"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                       {{ request()->routeIs('master.skripsi.*') || request()->routeIs('master.sempro.*') ? 'bg-white/[0.08] text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                title="Data">
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('master.skripsi.*') || request()->routeIs('master.sempro.*') ? 'text-indigo-400' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="flex-1 text-left truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Data</span>
                            <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                 :class="{ 'rotate-180': dataOpen, 'opacity-0': !sidebarOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            @if(!request()->routeIs('master.skripsi.*') && !request()->routeIs('master.sempro.*'))
                            <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            @endif
                        </button>
                        <div x-show="dataOpen && sidebarOpen"
                             x-collapse.duration.250ms
                             class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                             x-cloak>
                            <a href="{{ route('master.skripsi.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('master.skripsi.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('master.skripsi.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                Data Skripsi
                            </a>
                            <a href="{{ route('master.sempro.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('master.sempro.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('master.sempro.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Data Sempro
                            </a>
                        </div>
                    </div>

                    <!-- PENJADWALAN (with submenu) -->
                    <div>
                        <button @click="penjadwalanOpen = !penjadwalanOpen; if(!sidebarOpen) sidebarOpen = true"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                       {{ request()->routeIs('jadwal-ujian.*') || request()->routeIs('jadwal-sempro.*') ? 'bg-white/[0.08] text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                title="Penjadwalan">
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('jadwal-ujian.*') || request()->routeIs('jadwal-sempro.*') ? 'text-violet-400' : 'text-slate-400 group-hover:text-violet-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="flex-1 text-left truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Penjadwalan</span>
                            <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                 :class="{ 'rotate-180': penjadwalanOpen, 'opacity-0': !sidebarOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            @if(!request()->routeIs('jadwal-ujian.*') && !request()->routeIs('jadwal-sempro.*'))
                            <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-violet-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            @endif
                        </button>
                        <div x-show="penjadwalanOpen && sidebarOpen"
                             x-collapse.duration.250ms
                             class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                             x-cloak>
                            <a href="{{ route('jadwal-ujian.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('jadwal-ujian.*') ? 'bg-violet-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('jadwal-ujian.*') ? 'text-white' : 'text-slate-500 group-hover:text-violet-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                Jadwal Sidang Skripsi
                            </a>
                            <a href="{{ route('jadwal-sempro.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('jadwal-sempro.*') ? 'bg-violet-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('jadwal-sempro.*') ? 'text-white' : 'text-slate-500 group-hover:text-violet-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Jadwal Sempro
                            </a>
                            <a href="{{ route('master.kesediaan-dosen.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('master.kesediaan-dosen.index') ? 'bg-violet-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('master.kesediaan-dosen.index') ? 'text-white' : 'text-slate-500 group-hover:text-violet-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                                Kesediaan Dosen
                            </a>
                            <a href="{{ route('jadwal.auto-plot.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('jadwal.auto-plot.*') ? 'bg-violet-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('jadwal.auto-plot.*') ? 'text-white' : 'text-slate-500 group-hover:text-violet-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Asisten Plotting Otomatis
                            </a>
                        </div>
                    </div>

                    <!-- Administrasi (Dropdown) -->
                    <div class="space-y-0.5">
                        <button @click="administrasiOpen = !administrasiOpen; if(!sidebarOpen) sidebarOpen = true"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                       {{ request()->routeIs('administrasi.*') ? 'bg-indigo-600/30 text-indigo-300 font-semibold' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                title="Administrasi">
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('administrasi.*') ? 'text-indigo-400' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="flex-1 text-left truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Administrasi</span>
                            <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                 :class="{ 'rotate-180': administrasiOpen, 'opacity-0': !sidebarOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            @if(!request()->routeIs('administrasi.*'))
                            <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            @endif
                        </button>
                        <div x-show="administrasiOpen && sidebarOpen"
                             x-collapse.duration.250ms
                             class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                             x-cloak>

                            {{-- ── Sub-group: Skripsi ── --}}
                            @php $isSkripsiAdminActive = request()->routeIs('administrasi.*') && request()->get('jenis') === 'skripsi'; @endphp
                            <div x-data="{ open: {{ $isSkripsiAdminActive ? 'true' : 'false' }} }">
                                <button @click="open = !open"
                                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                               {{ $isSkripsiAdminActive ? 'bg-blue-600/20 text-blue-300' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                    <svg class="w-4 h-4 shrink-0 {{ $isSkripsiAdminActive ? 'text-blue-400' : 'text-slate-500 group-hover:text-blue-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span class="flex-1 text-left">Skripsi</span>
                                    <svg class="w-3.5 h-3.5 text-slate-500 transition-transform duration-200 shrink-0"
                                         :class="{ 'rotate-180': open }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open"
                                     x-collapse.duration.250ms
                                     class="mt-0.5 ml-3 pl-3 border-l border-white/[0.05] space-y-0.5"
                                     x-cloak>
                                    @php $jenisSkripsi = request()->get('jenis') === 'skripsi'; @endphp
                                    <a href="{{ route('administrasi.undangan.index', ['jenis' => 'skripsi']) }}"
                                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 group
                                              {{ request()->routeIs('administrasi.undangan.*') && $jenisSkripsi ? 'bg-blue-600/70 text-white' : 'text-slate-500 hover:bg-white/[0.06] hover:text-white' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('administrasi.undangan.*') && $jenisSkripsi ? 'bg-blue-300' : 'bg-slate-600 group-hover:bg-blue-400' }} transition-colors"></span>
                                        Undangan
                                    </a>
                                    <a href="{{ route('administrasi.rekap-pembimbing.index', ['jenis' => 'skripsi']) }}"
                                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 group
                                              {{ request()->routeIs('administrasi.rekap-pembimbing.*') && $jenisSkripsi ? 'bg-blue-600/70 text-white' : 'text-slate-500 hover:bg-white/[0.06] hover:text-white' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('administrasi.rekap-pembimbing.*') && $jenisSkripsi ? 'bg-blue-300' : 'bg-slate-600 group-hover:bg-blue-400' }} transition-colors"></span>
                                        Rekap Pembimbing
                                    </a>
                                    <a href="{{ route('administrasi.berita-acara.index', ['jenis' => 'skripsi']) }}"
                                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 group
                                              {{ request()->routeIs('administrasi.berita-acara.*') && $jenisSkripsi ? 'bg-blue-600/70 text-white' : 'text-slate-500 hover:bg-white/[0.06] hover:text-white' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('administrasi.berita-acara.*') && $jenisSkripsi ? 'bg-blue-300' : 'bg-slate-600 group-hover:bg-blue-400' }} transition-colors"></span>
                                        Berita Acara
                                    </a>
                                </div>
                            </div>

                            {{-- ── Sub-group: Sempro ── --}}
                            @php $isSemproAdminActive = request()->routeIs('administrasi.*') && request()->get('jenis') === 'sempro'; @endphp
                            <div x-data="{ open: {{ $isSemproAdminActive ? 'true' : 'false' }} }">
                                <button @click="open = !open"
                                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                               {{ $isSemproAdminActive ? 'bg-amber-600/20 text-amber-300' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                    <svg class="w-4 h-4 shrink-0 {{ $isSemproAdminActive ? 'text-amber-400' : 'text-slate-500 group-hover:text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="flex-1 text-left">Sempro</span>
                                    <svg class="w-3.5 h-3.5 text-slate-500 transition-transform duration-200 shrink-0"
                                         :class="{ 'rotate-180': open }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open"
                                     x-collapse.duration.250ms
                                     class="mt-0.5 ml-3 pl-3 border-l border-white/[0.05] space-y-0.5"
                                     x-cloak>
                                    @php $jenisSempro = request()->get('jenis') === 'sempro'; @endphp
                                    <a href="{{ route('administrasi.undangan.index', ['jenis' => 'sempro']) }}"
                                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 group
                                              {{ request()->routeIs('administrasi.undangan.*') && $jenisSempro ? 'bg-amber-600/70 text-white' : 'text-slate-500 hover:bg-white/[0.06] hover:text-white' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('administrasi.undangan.*') && $jenisSempro ? 'bg-amber-300' : 'bg-slate-600 group-hover:bg-amber-400' }} transition-colors"></span>
                                        Undangan
                                    </a>
                                    <a href="{{ route('administrasi.rekap-pembimbing.index', ['jenis' => 'sempro']) }}"
                                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 group
                                              {{ request()->routeIs('administrasi.rekap-pembimbing.*') && $jenisSempro ? 'bg-amber-600/70 text-white' : 'text-slate-500 hover:bg-white/[0.06] hover:text-white' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('administrasi.rekap-pembimbing.*') && $jenisSempro ? 'bg-amber-300' : 'bg-slate-600 group-hover:bg-amber-400' }} transition-colors"></span>
                                        Rekap Pembimbing
                                    </a>
                                    <a href="{{ route('administrasi.berita-acara.index', ['jenis' => 'sempro']) }}"
                                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 group
                                              {{ request()->routeIs('administrasi.berita-acara.*') && $jenisSempro ? 'bg-amber-600/70 text-white' : 'text-slate-500 hover:bg-white/[0.06] hover:text-white' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('administrasi.berita-acara.*') && $jenisSempro ? 'bg-amber-300' : 'bg-slate-600 group-hover:bg-amber-400' }} transition-colors"></span>
                                        Berita Acara
                                    </a>
                                </div>
                            </div>

                            {{-- ── Surat Keputusan (SK) — satu menu tersendiri; SK hanya untuk ujian skripsi ── --}}
                            <a href="{{ route('administrasi.sk.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('administrasi.sk.*') ? 'bg-indigo-600/70 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('administrasi.sk.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                <span class="flex-1 text-left">Surat Keputusan (SK)</span>
                            </a>

                            {{-- ── Dashboard Analitik ── --}}
                            <a href="{{ route('administrasi.analitik.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('administrasi.analitik.*') ? 'bg-indigo-600/70 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('administrasi.analitik.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <span class="flex-1 text-left">Dashboard Analitik</span>
                            </a>

                            {{-- ── Riwayat Aktivitas (Audit Log) ── --}}
                            <a href="{{ route('administrasi.audit-log.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('administrasi.audit-log.*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('administrasi.audit-log.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="flex-1 text-left">Riwayat Aktivitas</span>
                            </a>

                        </div>
                    </div>
                </div>
                @endif

                @if(Auth::user()->isKoordinator())
                <!-- Section: KOORDINATOR - Dynamic menus from DB -->
                @php
                    $koordinatorMenus = Auth::user()->getAccessibleMenus();
                    $koordinatorParents = $koordinatorMenus->whereNull('parent_id');
                @endphp
                @if($koordinatorParents->isNotEmpty())
                <div class="mb-2 pt-3">
                    <p class="px-3 text-[9px] font-extrabold uppercase tracking-[0.15em] text-slate-500 mb-1 transition-all duration-300"
                       :class="sidebarOpen ? 'opacity-100' : 'opacity-0'">Koordinator</p>

                    @foreach($koordinatorParents as $kParent)
                        @if($kParent->is_active)
                            @php
                                $kChildren = $koordinatorMenus->where('parent_id', $kParent->id)->where('is_active', true);
                                $kChildRoutes = $kChildren->pluck('route')->filter()->toArray();
                                $kParentActive = false;
                                foreach($kChildRoutes as $kcr) {
                                    if(request()->routeIs($kcr . '*')) { $kParentActive = true; }
                                }
                                if(!$kChildren->count() && $kParent->route) {
                                    $kParentActive = request()->routeIs($kParent->route . '*');
                                }
                            @endphp

                            @if($kChildren->isEmpty())
                                @php
                                    $kUrl = ($kParent->route && Route::has($kParent->route)) ? route($kParent->route) : '#';
                                @endphp
                                <a href="{{ $kUrl }}"
                                   class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                          {{ $kParentActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                   title="{{ $kParent->name }}">
                                    <svg class="w-5 h-5 shrink-0 {{ $kParentActive ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    <span class="truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">{{ $kParent->name }}</span>
                                    @if(!$kParentActive)
                                    <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                    @endif
                                </a>
                            @else
                                <div x-data="{ open: {{ $kParentActive ? 'true' : 'false' }} }">
                                    <button @click="open = !open; if(!sidebarOpen) sidebarOpen = true"
                                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                                   {{ $kParentActive ? 'bg-white/[0.08] text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                            title="{{ $kParent->name }}">
                                        <svg class="w-5 h-5 shrink-0 {{ $kParentActive ? 'text-indigo-400' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if(str_contains(strtolower($kParent->name), 'data'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            @elseif(str_contains(strtolower($kParent->name), 'jadwal') || str_contains(strtolower($kParent->name), 'penjadwalan'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                            @endif
                                        </svg>
                                        <span class="flex-1 text-left truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">{{ $kParent->name }}</span>
                                        <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                             :class="{ 'rotate-180': open, 'opacity-0': !sidebarOpen }"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                        @if(!$kParentActive)
                                        <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                        @endif
                                    </button>
                                    <div x-show="open && sidebarOpen"
                                         x-collapse.duration.250ms
                                         class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                                         x-cloak>
                                        @foreach($kChildren as $kChild)
                                            @php
                                                $kChildUrl = ($kChild->route && Route::has($kChild->route)) ? route($kChild->route) : '#';
                                                $kChildActive = $kChild->route ? request()->routeIs($kChild->route . '*') : false;
                                            @endphp
                                            <a href="{{ $kChildUrl }}"
                                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                                      {{ $kChildActive ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                                <svg class="w-4 h-4 shrink-0 {{ $kChildActive ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    @if(str_contains(strtolower($kChild->name), 'skripsi'))
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                                    @elseif(str_contains(strtolower($kChild->name), 'sempro'))
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    @elseif(str_contains(strtolower($kChild->name), 'jadwal'))
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    @endif
                                                </svg>
                                                {{ $kChild->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach
                </div>
                @endif
                @endif


            </nav>

            <!-- ── Sidebar Footer: Profile + Logout ── -->
            <div class="border-t border-white/[0.07] p-3 space-y-1 shrink-0">

                <!-- Profile link -->
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:bg-white/[0.06] hover:text-white transition-all duration-200 group"
                   title="{{ Auth::user()->name }}">
                    <div class="w-7 h-7 shrink-0 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-bold flex items-center justify-center text-xs shadow-md">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="overflow-hidden transition-all duration-300 min-w-0" :class="sidebarOpen ? 'w-auto opacity-100' : 'w-0 opacity-0'">
                        <p class="text-xs font-bold text-slate-200 truncate whitespace-nowrap">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] text-slate-500 truncate whitespace-nowrap">{{ Auth::user()->role_label }}</p>
                    </div>
                </a>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-500/10 hover:text-rose-400 transition-all duration-200 group"
                            title="Keluar">
                        <svg class="w-5 h-5 shrink-0 text-slate-500 group-hover:text-rose-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- ===================== MAIN AREA ===================== -->
        <div class="flex-1 flex flex-col min-w-0 min-h-screen overflow-auto">

            <!-- ── TOP NAVBAR ── -->
            <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-700 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 shadow-sm shrink-0">

                <div class="flex items-center gap-3 min-w-0">
                    <!-- Burger toggle (desktop: collapse sidebar) -->
                    <button @click="sidebarOpen = !sidebarOpen"
                            class="hidden lg:flex items-center justify-center w-9 h-9 rounded-xl text-slate-500 dark:text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 dark:hover:text-indigo-400 transition-colors"
                            title="Toggle Sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  :d="sidebarOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'"/>
                        </svg>
                    </button>

                    <!-- Burger toggle (mobile: open drawer) -->
                    <button @click="mobileOpen = true"
                            class="lg:hidden flex items-center justify-center w-9 h-9 rounded-xl text-slate-500 dark:text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition-colors"
                            title="Buka Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Page breadcrumb title -->
                    <div class="flex items-center gap-2 text-sm min-w-0">
                        <span class="text-slate-400 dark:text-slate-500 font-medium hidden sm:block shrink-0">Skripsi TI</span>
                        <span class="text-slate-300 dark:text-slate-600 hidden sm:block shrink-0">/</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100 truncate">{{ $header ?? $title ?? 'Dashboard' }}</span>
                    </div>
                </div>

                <!-- Right: Profile & Actions -->
                @php
                    $upcomingCount = 0;
                    $upcomingSidangs = collect();
                    if (Auth::check()) {
                        $tomorrow = now()->timezone('Asia/Jakarta')->addDay()->format('Y-m-d');
                        if (Auth::user()->isDosen() && Auth::user()->dosen) {
                            $dosenId = Auth::user()->dosen->id;
                            
                            $upcomingSidangs = \App\Models\Sidang::with(['ruang', 'pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'])
                                ->whereDate('tanggal', $tomorrow)
                                ->where(function ($q) use ($dosenId) {
                                    $q->where(function ($sq) use ($dosenId) {
                                        $sq->where('jenis_tugas_akhir', 'sempro')
                                           ->where(function ($inner) use ($dosenId) {
                                               $inner->where('dosen_pembimbing_utama_id', $dosenId)
                                                     ->orWhere('dosen_pembimbing_pendamping_id', $dosenId)
                                                     ->orWhere('ketua_penguji_id', $dosenId)
                                                     ->orWhere('anggota_penguji_1_id', $dosenId)
                                                     ->orWhere('anggota_penguji_2_id', $dosenId);
                                           });
                                    })->orWhere(function ($sq) use ($dosenId) {
                                        $sq->whereIn('jenis_tugas_akhir', ['skripsi', 'jurnal', 'sidang'])
                                           ->where(function ($inner) use ($dosenId) {
                                               $inner->where('dosen_pembimbing_utama_id', $dosenId)
                                                     ->orWhere('ketua_penguji_id', $dosenId)
                                                     ->orWhere('anggota_penguji_1_id', $dosenId)
                                                     ->orWhere('anggota_penguji_2_id', $dosenId);
                                           });
                                    });
                                })
                                ->get();
                            $upcomingCount = $upcomingSidangs->count();
                        } elseif (Auth::user()->isMahasiswa()) {
                            $nim = Auth::user()->nim ?? null;
                            $name = Auth::user()->name;

                            $upcomingSidangs = \App\Models\Sidang::with(['ruang', 'pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'])
                                ->whereDate('tanggal', $tomorrow)
                                ->where(function ($q) use ($nim, $name) {
                                    if ($nim) {
                                        $q->where('nim', $nim);
                                    } else {
                                        $q->where('nama_mahasiswa', $name);
                                    }
                                })
                                ->get();
                            $upcomingCount = $upcomingSidangs->count();
                        }
                    }

                    // Admin/koordinator notifications: new pending registrations & schedule
                    // conflicts. Both are derived live from current DB state — once a
                    // registration is verified or a conflict is resolved by rescheduling,
                    // it naturally drops out of these lists on the very next page load,
                    // with no separate "mark as read"/dismiss bookkeeping needed.
                    $adminPendingRegs = collect();
                    $adminConflicts = collect();
                    $adminNotifCount = 0;
                    if (Auth::check() && (Auth::user()->isSuperAdmin() || Auth::user()->isKoordinator())) {
                        $adminPendingRegs = \App\Models\Sidang::where('verifikasi_status', 'menunggu')
                            ->orderByDesc('tanggal_pendaftaran')
                            ->limit(10)
                            ->get();

                        $scheduledSidangs = \App\Models\Sidang::with(['ruang'])->whereNotNull('tanggal')->get();
                        $conflictMapHeader = \App\Services\SidangConflictService::detectAllConflicts($scheduledSidangs);
                        $conflictIds = collect($conflictMapHeader)->filter(fn ($c) => !empty($c['schedule']))->keys();
                        $adminConflicts = $scheduledSidangs->whereIn('id', $conflictIds)->values();

                        $adminNotifCount = $adminPendingRegs->count() + $adminConflicts->count();
                    }
                @endphp

                <div class="flex items-center gap-2 sm:gap-3 relative shrink-0" x-data="{ open: false }" @click.away="open = false">

                    <!-- Theme Toggle Switch Button (Light / Dark Mode) -->
                    <button type="button" 
                            @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light'); if(darkMode) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); } window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: darkMode } }));"
                            class="flex items-center justify-center w-10 h-10 rounded-2xl text-slate-600 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-all border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-2xs group shrink-0"
                            title="Ganti Mode Terang / Gelap">
                        <template x-if="!darkMode">
                            <span class="text-base" title="Aktifkan Mode Gelap"><x-icon name="moon" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></span>
                        </template>
                        <template x-if="darkMode">
                            <span class="text-base" title="Aktifkan Mode Terang"><x-icon name="sun" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></span>
                        </template>
                    </button>

                    <!-- Notification Bell Dropdown -->
                    @if (Auth::user()->isDosen() || Auth::user()->isMahasiswa())
                    <div class="relative" x-data="{ notifOpen: false }" @click.away="notifOpen = false">
                        <button @click="notifOpen = !notifOpen" 
                                class="relative flex items-center justify-center w-10 h-10 rounded-2xl text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/80 transition-all duration-200 shadow-2xs border border-slate-200/80 bg-white group"
                                title="Notifikasi Ujian H-1">
                            <svg class="w-6 h-6 text-slate-500 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if ($upcomingCount > 0)
                            <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-extrabold text-white ring-2 ring-white animate-bounce">
                                {{ $upcomingCount }}
                            </span>
                            @endif
                        </button>

                        <!-- Notification Dropdown -->
                        <div x-show="notifOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                             class="fixed inset-x-3 top-16 sm:absolute sm:inset-x-auto sm:top-13 sm:right-0 w-auto sm:w-[440px] bg-white border border-slate-200 rounded-3xl shadow-2xl shadow-slate-900/20 overflow-hidden z-50"
                             x-cloak>
                            <div class="px-5 py-3.5 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300 flex items-center justify-center"><x-icon name="bell" class="w-4 h-4" /></span>
                                    <span class="font-extrabold text-xs tracking-wider uppercase">Notifikasi Ujian H-1</span>
                                </div>
                                @if ($upcomingCount > 0)
                                <span class="bg-indigo-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                                    {{ $upcomingCount }} Baru
                                </span>
                                @endif
                            </div>
                            
                            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 bg-white">
                                @forelse ($upcomingSidangs as $us)
                                    <div class="p-4 hover:bg-slate-50/80 transition-colors">
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <span class="inline-flex px-2 py-0.5 text-[9px] font-bold rounded bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-200 dark:border-indigo-500/30">
                                                {{ $us->jenis_tugas_akhir === 'sempro' ? 'Sempro' : 'Sidang Skripsi' }}
                                            </span>
                                            <span class="text-[10px] font-extrabold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-100">
                                                Besok, {{ $us->jam }} WIB
                                            </span>
                                        </div>
                                        
                                        @if(Auth::user()->isDosen())
                                            <p class="text-xs font-bold text-slate-800 leading-snug">{{ $us->nama_mahasiswa }}</p>
                                            <p class="text-[10px] text-slate-400 font-mono">NIM: {{ $us->nim }}</p>
                                        @else
                                            <p class="text-xs font-bold text-slate-800 leading-snug">Jadwal Ujian Anda</p>
                                            <p class="text-[10px] text-slate-400 font-mono">NIM: {{ $us->nim }}</p>
                                        @endif

                                        <p class="text-[11px] text-slate-600 mt-1.5 font-medium leading-relaxed italic line-clamp-2">"{{ $us->judul_skripsi }}"</p>
                                        
                                        <div class="mt-2.5 pt-2 border-t border-slate-50 flex flex-wrap gap-x-3 gap-y-1.5 text-[10px] text-slate-500 font-medium">
                                            <div class="flex items-center gap-1">
                                                <span><x-icon name="map-pin" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Ruang:</span>
                                                <strong class="text-slate-700">{{ $us->ruang->kode_ruangan ?? '-' }}</strong>
                                            </div>
                                            
                                            @if(Auth::user()->isDosen() && isset($dosenId))
                                                @php
                                                    $peran = [];
                                                    $isSemproUs = $us->jenis_tugas_akhir === 'sempro';
                                                    if ($us->dosen_pembimbing_utama_id == $dosenId) $peran[] = 'Dosbing Utama';
                                                    if ($us->dosen_pembimbing_pendamping_id == $dosenId) $peran[] = 'Dosbing Pendamping';
                                                    // Untuk Sempro, ketua_penguji_id/anggota_penguji_1_id/2_id SELALU
                                                    // mencerminkan pembimbing (lihat Sidang::syncPengujiFromPembimbing())
                                                    // — sudah tercakup 2 baris di atas, dilewati di sini supaya tidak dobel.
                                                    if (!$isSemproUs && $us->ketua_penguji_id == $dosenId) $peran[] = 'Ketua Penguji';
                                                    if (!$isSemproUs && $us->anggota_penguji_1_id == $dosenId) $peran[] = 'Anggota Penguji 1';
                                                    if (!$isSemproUs && $us->anggota_penguji_2_id == $dosenId) $peran[] = 'Anggota Penguji 2';
                                                @endphp
                                                <div class="flex items-center gap-1">
                                                    <span><x-icon name="user" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Peran:</span>
                                                    <strong class="text-slate-700">{{ implode(', ', $peran) ?: 'Penguji' }}</strong>
                                                </div>
                                            @else
                                                <div class="flex items-center gap-1">
                                                    <span><x-icon name="user" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Ketua Penguji:</span>
                                                    <strong class="text-slate-700">{{ $us->ketuaPenguji ? $us->ketuaPenguji->nama_dosen : '-' }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 py-8 text-center text-slate-400">
                                        <div class="w-10 h-10 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-2.5">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-500">Tidak ada jadwal ujian esok hari.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Admin/Koordinator Notification Bell: pendaftaran baru & bentrok jadwal -->
                    @if (Auth::user()->isSuperAdmin() || Auth::user()->isKoordinator())
                    <div class="relative" x-data="{ notifOpen: false }" @click.away="notifOpen = false">
                        <button @click="notifOpen = !notifOpen"
                                class="relative flex items-center justify-center w-10 h-10 rounded-2xl text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50/80 dark:hover:bg-slate-800 transition-all duration-200 shadow-2xs border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-900 group"
                                title="Notifikasi Pendaftaran & Bentrok Jadwal">
                            <svg class="w-6 h-6 text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if ($adminNotifCount > 0)
                            <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-extrabold text-white ring-2 ring-white dark:ring-slate-900 animate-bounce">
                                {{ $adminNotifCount > 99 ? '99+' : $adminNotifCount }}
                            </span>
                            @endif
                        </button>

                        <div x-show="notifOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                             class="fixed inset-x-3 top-16 sm:absolute sm:inset-x-auto sm:top-13 sm:right-0 w-auto sm:w-[440px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-3xl shadow-2xl shadow-slate-900/20 overflow-hidden z-50"
                             x-cloak>
                            <div class="px-5 py-3.5 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300 flex items-center justify-center"><x-icon name="bell" class="w-4 h-4" /></span>
                                    <span class="font-extrabold text-xs tracking-wider uppercase">Notifikasi</span>
                                </div>
                                @if ($adminNotifCount > 0)
                                <span class="bg-indigo-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                                    {{ $adminNotifCount }} Perlu Diproses
                                </span>
                                @endif
                            </div>

                            <div class="max-h-96 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                                @forelse ($adminPendingRegs as $pr)
                                    <a href="{{ route($pr->jenis_tugas_akhir === 'sempro' ? 'pendaftaran.sempro' : 'pendaftaran.skripsi', ['verifikasi_status' => 'menunggu']) }}"
                                       class="block p-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors">
                                        <div class="flex items-center justify-between gap-2 mb-1.5">
                                            <span class="inline-flex px-2 py-0.5 text-[9px] font-bold rounded bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                <x-icon name="clock" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Pendaftaran Baru &mdash; {{ $pr->jenis_tugas_akhir === 'sempro' ? 'Sempro' : 'Skripsi' }}
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100 leading-snug">{{ $pr->nama_mahasiswa }}</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">NIM: {{ $pr->nim }}</p>
                                    </a>
                                @empty
                                @endforelse

                                @forelse ($adminConflicts as $cf)
                                    <a href="{{ route($cf->jenis_tugas_akhir === 'sempro' ? 'jadwal-sempro.index' : 'jadwal-ujian.index') }}"
                                       class="block p-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors">
                                        <div class="flex items-center justify-between gap-2 mb-1.5">
                                            <span class="inline-flex px-2 py-0.5 text-[9px] font-bold rounded bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                                <x-icon name="warning" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Jadwal Bentrok
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100 leading-snug">{{ $cf->nama_mahasiswa }}</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">NIM: {{ $cf->nim }} &middot; {{ $cf->tanggal?->locale('id')->isoFormat('D MMM Y') }}, {{ $cf->jam }}</p>
                                    </a>
                                @empty
                                @endforelse

                                @if ($adminNotifCount === 0)
                                    <div class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">
                                        <div class="w-10 h-10 bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-full flex items-center justify-center mx-auto mb-2.5">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Tidak ada yang perlu diproses saat ini.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Profile Dropdown Trigger -->
                    <button @click="open = !open"
                            class="flex items-center gap-2.5 pl-1 pr-3 py-1 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-100 leading-tight">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">{{ Auth::user()->role_label }}</p>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 hidden sm:block" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                         class="absolute top-14 right-0 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xl shadow-slate-200/60 dark:shadow-slate-900/80 overflow-hidden z-50"
                         x-cloak>

                        <!-- Profile Card -->
                        <div class="p-4 bg-gradient-to-br from-indigo-50 to-violet-50 dark:from-indigo-950/60 dark:to-violet-950/60 border-b border-slate-100 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-white font-extrabold flex items-center justify-center text-base shadow-md">
                                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-sm text-slate-900 dark:text-slate-100 truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ Auth::user()->email }}</p>
                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ Auth::user()->role_badge_class }}">
                                        {{ Auth::user()->role_label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown Links -->
                        <div class="p-2 bg-white dark:bg-slate-900">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium transition-colors group">
                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Profil Saya
                            </a>
                        </div>

                        <!-- Divider + Logout -->
                        <div class="border-t border-slate-100 dark:border-slate-700 p-2 bg-white dark:bg-slate-900">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 font-semibold transition-colors group">
                                    <svg class="w-4 h-4 text-rose-500 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Keluar dari Sistem
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </header>

            <!-- ── PAGE CONTENT ── -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">

                <!-- Dynamic Notification Wrapper -->
                <div x-data="{ 
                    show: false, 
                    message: '', 
                    type: 'success',
                    init() {
                        @if (session('success'))
                            this.showNotification('{{ session('success') }}', 'success');
                        @endif
                        @if (session('error'))
                            this.showNotification('{{ session('error') }}', 'error');
                        @endif
                    },
                    showNotification(msg, t) {
                        this.message = msg;
                        this.type = t;
                        this.show = true;
                        // Auto hide after 4 seconds
                        setTimeout(() => {
                            this.show = false;
                        }, 4000);
                    }
                }"
                @notify.window="showNotification($event.detail.message, $event.detail.type)"
                x-show="show" 
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                :class="{
                    'bg-emerald-50 border-emerald-200 text-emerald-800': type === 'success',
                    'bg-rose-50 border-rose-200 text-rose-800': type === 'error'
                }"
                class="mb-5 flex items-center justify-between p-4 border rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3">
                        <!-- Icon Success -->
                        <div x-show="type === 'success'" class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <!-- Icon Error -->
                        <div x-show="type === 'error'" class="w-8 h-8 rounded-lg bg-rose-500 text-white flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold" x-text="message"></p>
                    </div>
                    <button @click="show = false" 
                            :class="{
                                'text-emerald-500 hover:text-emerald-700 hover:bg-emerald-100': type === 'success',
                                'text-rose-500 hover:text-rose-700 hover:bg-rose-100': type === 'error'
                            }"
                            class="p-1 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Validation Errors -->
                @if ($errors->any())
                <div x-data="{ show: true }" x-show="show" x-cloak class="mb-5 p-4 bg-rose-50 border border-rose-200 rounded-2xl shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-500 text-white flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-rose-800 mb-1">Terdapat kesalahan input:</p>
                            <ul class="list-disc pl-4 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                <li class="text-xs text-rose-700">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif

                <div id="page-content">
                    {{ $slot }}
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-slate-200/60 px-6 py-3 shrink-0">
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                    <span>© {{ date('Y') }} Sistem Informasi Skripsi TI — Teknik Informatika</span>
                    <span class="font-semibold text-slate-300">v1.0.0</span>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

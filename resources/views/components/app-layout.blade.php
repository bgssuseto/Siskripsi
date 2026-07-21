<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} - Sistem Informasi Skripsi TI</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

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
    </style>
</head>
<body class="h-full antialiased text-slate-800 bg-slate-100"
      x-data="{
          sidebarOpen: true,
          mobileOpen: false,
          dataMasterOpen: {{ request()->routeIs('master.*') ? 'true' : 'false' }},
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
            class="fixed inset-y-0 left-0 z-50 flex flex-col bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 text-white transition-all duration-300 ease-in-out shadow-2xl lg:relative lg:z-auto lg:translate-x-0"
            :class="{
                'translate-x-0': mobileOpen,
                '-translate-x-full': !mobileOpen,
                'sidebar-expanded': sidebarOpen,
                'sidebar-collapsed': !sidebarOpen
            }">

            <!-- Brand / Logo -->
            <div class="flex items-center h-16 px-4 border-b border-white/[0.07] shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 shrink-0 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div class="overflow-hidden transition-all duration-300" :class="sidebarOpen ? 'w-36 opacity-100' : 'w-0 opacity-0'">
                        <p class="font-extrabold text-sm leading-tight text-white whitespace-nowrap">Skripsi TI</p>
                        <p class="text-[10px] text-indigo-400 font-semibold uppercase tracking-widest whitespace-nowrap">Portal Akademik</p>
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

                    <!-- Section: MAHASISWA -->
                    @if(Auth::user()->hasRole('mahasiswa'))
                    <div class="mb-2 pt-3">
                        <p class="px-3 text-[9px] font-extrabold uppercase tracking-[0.15em] text-slate-500 mb-1 transition-all duration-300"
                           :class="sidebarOpen ? 'opacity-100' : 'opacity-0'">Mahasiswa</p>

                        @php
                            $mahasiswaMenus = Auth::user()->getAccessibleMenus();
                        @endphp

                        @foreach($mahasiswaMenus as $m)
                            @if($m->is_active)
                            @php
                                $routeName = $m->route;
                                $targetUrl = ($routeName && Route::has($routeName)) ? route($routeName) : '#';
                                $isActive = $routeName ? (request()->routeIs($routeName . '*') || ($routeName === 'mahasiswa.sempro.index' && request()->routeIs('mahasiswa.pendaftaran.*'))) : false;
                            @endphp
                            <a href="{{ $targetUrl }}"
                               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                      {{ $isActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                               title="{{ $m->name }}">
                                <svg class="w-5 h-5 shrink-0 {{ $isActive ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if(str_contains(strtolower($m->name), 'sempro'))
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6"/>
                                    @elseif(str_contains(strtolower($m->name), 'skripsi'))
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    @elseif(str_contains(strtolower($m->name), 'jadwal'))
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    @endif
                                </svg>
                                <span class="truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">{{ $m->name }}</span>
                                @if(!$isActive)
                                <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                @endif
                            </a>
                            @endif
                        @endforeach
                    </div>
                    @endif

                @if(Auth::user()->hasRole(['super_admin', 'koordinator']))
                <!-- Section: ADMINISTRASI -->
                <div class="mb-2 pt-3">
                    <p class="px-3 text-[9px] font-extrabold uppercase tracking-[0.15em] text-slate-500 mb-1 transition-all duration-300"
                       :class="sidebarOpen ? 'opacity-100' : 'opacity-0'">Administrasi</p>

                    <!-- Manajemen User -->
                    <a href="{{ route('users.index') }}"
                       class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                              {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                       title="Manajemen User">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('users.*') ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span class="truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Manajemen User</span>
                        @if(request()->routeIs('users.*'))
                        @else
                        <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                        @endif
                    </a>

                    <!-- Manajemen Menu (Super Admin & Koordinator) -->
                    <a href="{{ route('admin.menus.index') }}"
                       class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                              {{ request()->routeIs('admin.menus.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                       title="Manajemen Menu">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.menus.*') ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Manajemen Menu</span>
                        @if(!request()->routeIs('admin.menus.*'))
                        <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                        @endif
                    </a>

                    <!-- DATA MASTER (with submenu) -->
                    <div x-data>
                        <button @click="dataMasterOpen = !dataMasterOpen"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                                       {{ request()->routeIs('master.*') ? 'bg-white/[0.08] text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                                title="Data Master">
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('master.*') ? 'text-indigo-400' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                            </svg>
                            <span class="flex-1 text-left truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Data Master</span>
                            <!-- Chevron -->
                            <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                 :class="{ 'rotate-180': dataMasterOpen, 'opacity-0': !sidebarOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            @if(!request()->routeIs('master.*'))
                            <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            @endif
                        </button>

                        <!-- Submenu -->
                        <div x-show="dataMasterOpen && sidebarOpen"
                             x-transition:enter="transition-all duration-200 ease-out"
                             x-transition:enter-start="opacity-0 -translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition-all duration-150 ease-in"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-2"
                             class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                             x-cloak>

                            <!-- Master Dosen -->
                            <a href="{{ route('master.dosen.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('master.dosen.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('master.dosen.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Master Dosen
                            </a>

                            <!-- Master Ruang -->
                            <a href="{{ route('master.ruang.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('master.ruang.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('master.ruang.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                Master Ruang
                            </a>

                            <!-- Master Periode -->
                            <a href="{{ route('master.periode.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('master.periode.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('master.periode.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Master Periode
                            </a>
                        </div>
                    </div>

                    <!-- Jadwal Ujian / Sidang -->
                    <a href="{{ route('sidang.index') }}"
                       class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative
                              {{ request()->routeIs('sidang.*') || request()->routeIs('jadwal-ujian.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}"
                       title="Jadwal Sidang & Ujian">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('sidang.*') || request()->routeIs('jadwal-ujian.*') ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="truncate transition-all duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">Jadwal Sidang</span>
                        @if(!request()->routeIs('sidang.*') && !request()->routeIs('jadwal-ujian.*'))
                        <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                        @endif
                    </a>

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
                            <!-- Chevron -->
                            <svg class="w-4 h-4 text-slate-500 transition-transform duration-300 shrink-0"
                                 :class="{ 'rotate-180': administrasiOpen, 'opacity-0': !sidebarOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            @if(!request()->routeIs('administrasi.*'))
                            <span class="absolute left-1 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            @endif
                        </button>

                        <!-- Submenu -->
                        <div x-show="administrasiOpen && sidebarOpen"
                             x-transition:enter="transition-all duration-200 ease-out"
                             x-transition:enter-start="opacity-0 -translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition-all duration-150 ease-in"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-2"
                             class="mt-1 ml-4 pl-4 border-l border-white/[0.07] space-y-0.5"
                             x-cloak>

                            <!-- Undangan -->
                            <a href="{{ route('administrasi.undangan.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('administrasi.undangan.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('administrasi.undangan.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Undangan
                            </a>

                            <!-- Berita Acara -->
                            <a href="{{ route('administrasi.berita-acara.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('administrasi.berita-acara.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('administrasi.berita-acara.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Berita Acara
                            </a>

                            <!-- Surat Keputusan (SK) -->
                            <a href="{{ route('administrasi.sk.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200 group
                                      {{ request()->routeIs('administrasi.sk.*') ? 'bg-indigo-600/80 text-white' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('administrasi.sk.*') ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Surat Keputusan (SK)
                            </a>
                        </div>
                    </div>
                </div>
                @endif

            </nav>

            <!-- ── Sidebar Footer: Profile + Logout ── -->
            <div class="border-t border-white/[0.07] p-3 space-y-1 shrink-0">

                <!-- Profile link -->
                <a href="#"
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
            <header class="h-16 bg-white border-b border-slate-200/80 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 shadow-sm shrink-0">

                <div class="flex items-center gap-3">
                    <!-- Burger toggle (desktop: collapse sidebar) -->
                    <button @click="sidebarOpen = !sidebarOpen"
                            class="hidden lg:flex items-center justify-center w-9 h-9 rounded-xl text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                            title="Toggle Sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  :d="sidebarOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'"/>
                        </svg>
                    </button>

                    <!-- Burger toggle (mobile: open drawer) -->
                    <button @click="mobileOpen = true"
                            class="lg:hidden flex items-center justify-center w-9 h-9 rounded-xl text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                            title="Buka Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Page breadcrumb title -->
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-slate-400 font-medium hidden sm:block">Skripsi TI</span>
                        <span class="text-slate-300 hidden sm:block">/</span>
                        <span class="font-bold text-slate-800">{{ $header ?? $title ?? 'Dashboard' }}</span>
                    </div>
                </div>

                <!-- Right: Profile & Actions -->
                <div class="flex items-center gap-2 sm:gap-3" x-data="{ open: false }" @click.away="open = false">

                    <!-- Standalone Quick Logout Button in Top Right Header -->
                    <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                        @csrf
                        <button type="submit" 
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 text-xs font-semibold border border-rose-200/80 transition-colors shadow-sm"
                                title="Keluar dari Sistem">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>

                    <!-- Profile Dropdown Trigger -->
                    <button @click="open = !open"
                            class="flex items-center gap-2.5 pl-1 pr-3 py-1 rounded-xl hover:bg-slate-100 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <p class="text-xs font-bold text-slate-800 leading-tight">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] text-slate-500 leading-tight">{{ Auth::user()->role_label }}</p>
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
                         class="absolute top-14 right-4 w-64 bg-white border border-slate-200 rounded-2xl shadow-xl shadow-slate-200/60 overflow-hidden z-50"
                         x-cloak>

                        <!-- Profile Card -->
                        <div class="p-4 bg-gradient-to-br from-indigo-50 to-violet-50 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-white font-extrabold flex items-center justify-center text-base shadow-md">
                                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-sm text-slate-900 truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ Auth::user()->role_badge_class }}">
                                        {{ Auth::user()->role_label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown Links -->
                        <div class="p-2">
                            <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 font-medium transition-colors group">
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Profil Saya
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 font-medium transition-colors group">
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Pengaturan
                            </a>
                        </div>

                        <!-- Divider + Logout -->
                        <div class="border-t border-slate-100 p-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-rose-600 hover:bg-rose-50 font-semibold transition-colors group">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                <!-- Flash: Success -->
                @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-cloak
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mb-5 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold">{{ session('success') }}</p>
                    </div>
                    <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 p-1 rounded-lg hover:bg-emerald-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                @endif

                <!-- Flash: Error -->
                @if (session('error'))
                <div x-data="{ show: true }" x-show="show" x-cloak
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mb-5 flex items-center justify-between p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-500 text-white flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold">{{ session('error') }}</p>
                    </div>
                    <button @click="show = false" class="text-rose-500 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                @endif

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

                {{ $slot }}
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

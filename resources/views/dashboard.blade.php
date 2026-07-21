<x-app-layout title="Dashboard Utama">
    <x-slot:header>
        Dashboard
    </x-slot:header>

    <!-- Welcome Header Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-900 via-slate-900 to-purple-950 p-6 sm:p-8 text-white shadow-xl mb-8">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold text-indigo-200 border border-white/10 mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Selamat Datang di Sistem Informasi Skripsi TI
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Halo, {{ Auth::user()->name }}!</h1>
                <p class="text-slate-300 text-sm mt-1 max-w-xl">
                    Anda masuk sebagai <span class="font-bold text-white uppercase">{{ Auth::user()->role_label }}</span>. Kelola tugas akhir, jadwal bimbingan, dan manajemen akun dari panel ini.
                </p>
            </div>
            
            <div class="shrink-0 flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-extrabold border shadow-lg {{ Auth::user()->role_badge_class }}">
                    Role: {{ Auth::user()->role_label }}
                </span>
                @if(Auth::user()->hasRole(['super_admin', 'koordinator']))
                <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-all">
                    Kelola User →
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Skripsi</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1">0</p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Sedang Berjalan</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1">0</p>
                </div>
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Selesai</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1">0</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-900">Aktivitas Terbaru</h2>
        </div>
        <div class="p-6">
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h3 class="text-slate-900 font-bold mb-1">Belum Ada Aktivitas</h3>
                <p class="text-slate-500 text-sm">Aktivitas skripsi dan pengajuan akan ditampilkan di sini.</p>
            </div>
        </div>
    </div>
</x-app-layout>

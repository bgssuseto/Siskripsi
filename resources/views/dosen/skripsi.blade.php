<x-app-layout title="Jadwal Sidang Skripsi">
    <x-slot:header>
        Jadwal Sidang Skripsi Dosen
    </x-slot:header>

    <!-- Back Navigation -->
    <div class="mb-4">
        <a href="{{ route('dosen.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    <!-- Page Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Jadwal Sidang Skripsi</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Daftar sidang skripsi mahasiswa di mana Anda bertindak sebagai pembimbing atau penguji.</p>
    </div>

    <!-- Filters & Search Toolbar -->
    <form method="GET" action="{{ route('dosen.jadwal.skripsi') }}" class="flex flex-wrap items-center gap-3 bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm mb-6">
        <!-- Search bar -->
        <div class="relative flex-1 min-w-[240px]">
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Cari mahasiswa atau judul..." 
                   class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 focus:border-purple-500 focus:ring-purple-500 bg-slate-50/50 dark:bg-slate-950 focus:bg-white text-slate-900 dark:text-slate-100 transition-all font-semibold">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Filter Tanggal -->
        <div class="relative min-w-[160px]">
            <input type="date" 
                   name="tanggal" 
                   value="{{ request('tanggal') }}"
                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 focus:border-purple-500 focus:ring-purple-500 text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-950 font-semibold">
        </div>

        <!-- Filter Status -->
        <div class="relative min-w-[180px]">
            <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 focus:border-purple-500 focus:ring-purple-500 text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-950 font-semibold cursor-pointer">
                <option value="">Semua Status</option>
                <option value="terjadwal" {{ request('status') === 'terjadwal' ? 'selected' : '' }}>Terjadwal & Belum Ujian</option>
                <option value="proses" {{ request('status') === 'proses' ? 'selected' : '' }}>Proses Ujian</option>
                <option value="sudah" {{ request('status') === 'sudah' ? 'selected' : '' }}>Sudah Ujian</option>
                <option value="belum_plotting" {{ request('status') === 'belum_plotting' ? 'selected' : '' }}>Belum Plotting</option>
            </select>
        </div>

        <!-- Buttons -->
        <div class="flex items-center gap-2">
            <button type="submit" style="background-color: #9333ea; color: #ffffff;" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm cursor-pointer border border-purple-500">
                Filter
            </button>
            @if(request()->hasAny(['search','tanggal','status']))
                <a href="{{ route('dosen.jadwal.skripsi') }}" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition-all">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Table content with High-Contrast Header & Dark Mode Support -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-[11px] font-extrabold uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                        <th class="py-4 px-6 w-32">JADWAL</th>
                        <th class="py-4 px-6 w-52">MAHASISWA</th>
                        <th class="py-4 px-6">JUDUL SKRIPSI</th>
                        <th class="py-4 px-6 w-32">RUANGAN</th>
                        <th class="py-4 px-6 w-36">STATUS</th>
                        <th class="py-4 px-6 w-44">PERAN ANDA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm font-medium">
                    @if($dosen)
                        @forelse($schedules as $s)
                            @php
                                $roles = [];
                                if($s->dosen_pembimbing_utama_id === $dosen->id) $roles[] = 'Pembimbing Utama';
                                if($s->dosen_pembimbing_pendamping_id === $dosen->id) $roles[] = 'Pembimbing Pendamping';
                                if($s->ketua_penguji_id === $dosen->id) $roles[] = 'Ketua Penguji';
                                if($s->anggota_penguji_1_id === $dosen->id) $roles[] = 'Anggota Penguji 1';
                                if($s->anggota_penguji_2_id === $dosen->id) $roles[] = 'Anggota Penguji 2';
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors">
                                <!-- Jadwal -->
                                <td class="py-4 px-6">
                                    <div class="text-slate-900 dark:text-slate-100 font-bold text-xs sm:text-sm">
                                        {{ $s->tanggal ? $s->tanggal->locale('id')->translatedFormat('l, d M Y') : 'Belum Terjadwal' }}
                                    </div>
                                    @if($s->tanggal)
                                        <div class="text-purple-600 dark:text-purple-400 text-xs mt-0.5 font-semibold">
                                            {{ $s->jam ?? '-' }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Mahasiswa -->
                                <td class="py-4 px-6">
                                    <div class="text-slate-900 dark:text-slate-100 font-extrabold leading-snug">{{ $s->nama_mahasiswa }}</div>
                                    <div class="text-xs text-purple-600 dark:text-purple-400 font-mono mt-0.5 font-bold">NIM: {{ $s->nim }}</div>
                                </td>

                                <!-- Judul -->
                                <td class="py-4 px-6">
                                    <p class="text-slate-800 dark:text-slate-200 text-xs font-semibold leading-relaxed max-w-lg line-clamp-2">
                                        "{{ $s->judul_skripsi }}"
                                    </p>
                                </td>

                                <!-- Ruangan -->
                                <td class="py-4 px-6">
                                    @if($s->ruang)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold rounded-xl">
                                            {{ $s->ruang->kode_ruangan }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 text-xs font-semibold">-</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="py-4 px-6">
                                    {!! $s->getJadwalStatusHtml() !!}
                                </td>

                                <!-- Peran Anda -->
                                <td class="py-4 px-6">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($roles as $r)
                                            <span class="px-2.5 py-1 text-[10px] font-extrabold rounded-lg uppercase tracking-wider
                                                {{ str_contains($r, 'Pembimbing') ? 'bg-emerald-100 dark:bg-emerald-950/80 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-purple-100 dark:bg-purple-950/80 border border-purple-300 dark:border-purple-800 text-purple-800 dark:text-purple-300' }}">
                                                {{ $r }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-16 text-center text-slate-500 dark:text-slate-400">
                                    <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3.5">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <h3 class="font-extrabold text-slate-800 dark:text-slate-200 text-base">Tidak Ada Jadwal Sidang Skripsi</h3>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-sm mx-auto">
                                        Tidak ditemukan jadwal Sidang Skripsi yang melibatkan Anda saat ini.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    @else
                        <tr>
                            <td colspan="6" class="py-16 text-center text-slate-500 dark:text-slate-400">
                                <h3 class="font-extrabold text-slate-800 dark:text-slate-200 text-base">Akun Belum Terhubung</h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Hubungkan akun Anda dengan Master Dosen untuk melihat jadwal.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

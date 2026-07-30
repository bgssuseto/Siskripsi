<x-app-layout title="Dashboard Dosen">
    <x-slot:header>
        Dashboard
    </x-slot:header>

    <div x-data="kesediaanModalHandler()">
        @if(!$stats['is_linked'])
            <!-- Alert Profile Not Linked -->
            <div class="relative overflow-hidden rounded-3xl bg-amber-500/10 border border-amber-500/20 p-6 text-amber-800 dark:text-amber-300 shadow-sm mb-8">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm sm:text-base text-amber-950 dark:text-amber-200">Akun Belum Terhubung dengan Data Dosen</h4>
                        <p class="text-xs sm:text-sm mt-0.5 text-amber-900/80 dark:text-amber-300/80 leading-relaxed">
                            Akun pengguna Anda belum dihubungkan dengan data profil Master Dosen oleh Super Admin. Anda tidak akan melihat jadwal ujian atau bimbingan hingga profil Anda terhubung. Silakan hubungi Super Admin untuk penyesuaian data.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Welcome Hero Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-indigo-950 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-8 text-white shadow-2xl mb-8 border border-slate-800">
            <!-- Decorative background accents -->
            <div class="absolute -right-10 -top-10 w-56 h-56 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute right-32 -bottom-10 w-44 h-44 bg-purple-500/10 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11.5px] font-bold text-indigo-200 border border-white/10 mb-3.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Selamat Datang di Sistem Informasi Skripsi TI
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Halo, {{ Auth::user()->name }}!</h1>
                    <p class="text-indigo-200/90 text-xs sm:text-sm mt-2 max-w-2xl leading-relaxed">
                        Anda masuk sebagai <strong class="font-extrabold text-white">DOSEN</strong>. Kelola tugas akhir, jadwal bimbingan, dan ketersediaan menguji dari panel ini.
                    </p>
                </div>
                
                <div class="shrink-0">
                    <span class="px-4 py-2 rounded-2xl bg-white/10 backdrop-blur-md text-white font-extrabold text-xs sm:text-sm shadow-md border border-white/20">
                        Role: Dosen
                    </span>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 rounded-2xl text-sm font-semibold flex items-center justify-between shadow-xs">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">✕</button>
            </div>
        @endif

        @if($isRegistrationClosed)
            <!-- Registration Closed Alert Banner -->
            <div class="mb-8 p-5 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-500/20 rounded-3xl text-slate-700 dark:text-slate-300 shadow-xs flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-md shadow-amber-500/20">
                    🔔
                </div>
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 text-[10px] font-bold">
                        Pendaftaran Ujian Ditutup
                    </div>
                    <h4 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-slate-100">Pendaftaran Ujian Sempro & Skripsi Telah Ditutup!</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Periode pendaftaran mahasiswa telah berakhir. Silakan isikan slot ketersediaan menguji Anda pada panel form kesediaan di bawah agar koordinator dapat melakukan plotting jadwal ujian.
                    </p>
                </div>
            </div>
        @endif

        @if($showFormKesediaan)
        <!-- Card Section: Form & Status Kesediaan Menguji Dosen -->
        <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-slate-200/80 dark:border-slate-700 p-6 shadow-xs mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-700 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-700">
                        📝
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Form Kesediaan Menguji Sempro & Skripsi</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Isi ketersediaan hari, tanggal, dan rentang jam Anda untuk mempermudah plotting jadwal ujian.</p>
                    </div>
                </div>

                @if($isLockedKesediaan)
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 text-xs font-extrabold border border-amber-200 dark:border-amber-700">
                        🔒 Batas Pengisian 6 Hari Terlampaui (Terkunci)
                    </span>
                @else
                    <button type="button" @click="openModal()" 
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 dark:hover:bg-indigo-800/60 text-indigo-700 dark:text-indigo-300 text-xs font-extrabold rounded-xl border border-indigo-200 dark:border-indigo-700 transition-all cursor-pointer">
                        <span>+ Tambah / Atur Slot Kesediaan</span>
                    </button>
                @endif
            </div>

            @if($existingKesediaan->count() > 0)
                <div class="space-y-2">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Slot Ketersediaan Menguji Anda Saat Ini ({{ $existingKesediaan->count() }} Slot):</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($existingKesediaan as $slot)
                            <div class="bg-slate-50 dark:bg-slate-700/50 border border-slate-200/90 dark:border-slate-600 hover:border-indigo-300 dark:hover:border-indigo-500 rounded-2xl p-4 transition-all flex items-center justify-between shadow-xs">
                                <div>
                                    <div class="font-extrabold text-slate-800 dark:text-slate-200 text-xs sm:text-sm">📅 {{ \Carbon\Carbon::parse($slot->tanggal)->translatedFormat('l, d F Y') }}</div>
                                    @if($slot->keterangan)<div class="text-slate-500 dark:text-slate-400 italic text-[11px] mt-1">💬 {{ $slot->keterangan }}</div>@endif
                                    @if($slot->wave)
                                        <div class="mt-1"><span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-700 px-2 py-0.5 rounded-lg">Gel. {{ $slot->wave->gelombang }} ({{ ucfirst($slot->wave->jenis) }})</span></div>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('dosen.kesediaan.destroy', $slot->id) }}" onsubmit="return confirm('Hapus slot ketersediaan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 hover:bg-rose-100 dark:hover:bg-rose-800/50 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold text-xs transition-colors cursor-pointer" title="Hapus Slot">
                                        ✕
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-slate-50/70 dark:bg-slate-700/30 border border-dashed border-slate-200 dark:border-slate-600 rounded-2xl p-6 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Anda belum mengisikan slot ketersediaan menguji. Klik tombol <strong class="text-indigo-600 dark:text-indigo-400">"+ Tambah / Atur Slot Kesediaan"</strong> untuk memasukkan jadwal ketersediaan Anda.</p>
                </div>
            @endif
        </div>
        @else
            @if($activePeriode && $activePeriode->show_form_kesediaan && $dosen && !$dosen->can_fill_kesediaan)
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-300 rounded-3xl p-5 mb-8 flex items-start gap-4 shadow-xs">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-md shadow-amber-500/20">
                        🔒
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-900 dark:text-slate-100">Akses Pengisian Dinonaktifkan</h4>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                            Akses pengisian form kesediaan menguji Anda saat ini dinonaktifkan oleh Koordinator/Administrator. Silakan hubungi Koordinator Program Studi untuk membuka akses Anda.
                        </p>
                    </div>
                </div>
            @endif
        @endif

        <!-- 3 Stat Cards (Real Database Counts for Logged-In Lecturer) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- MAHASISWA BIMBINGAN Stat Card -->
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-700 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">MAHASISWA BIMBINGAN</p>
                        <p class="text-3xl font-extrabold text-slate-900 dark:text-slate-100 mt-2">{{ $stats['total_jurnal'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pembimbing Utama & Pendamping</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-2xl flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- MENGUJI SEMPRO Stat Card -->
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-700 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">MENGUJI SEMPRO</p>
                        <p class="text-3xl font-extrabold text-slate-900 dark:text-slate-100 mt-2">{{ $stats['total_sempro'] }}</p>
                        <p class="text-xs text-amber-600 font-semibold mt-1">Seminar Proposal Active</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center shrink-0 border border-amber-100 dark:border-amber-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- MENGUJI SKRIPSI Stat Card -->
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-700 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">MENGUJI SKRIPSI</p>
                        <p class="text-3xl font-extrabold text-slate-900 dark:text-slate-100 mt-2">{{ $stats['total_skripsi'] }}</p>
                        <p class="text-xs text-purple-600 font-semibold mt-1">Sidang Skripsi / Tugas Akhir</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-2xl flex items-center justify-center shrink-0 border border-purple-100 dark:border-purple-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aktivitas Ujian & Bimbingan Terbaru -->
        <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden mb-8">
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Jadwal Ujian & Bimbingan Mahasiswa</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar jadwal sidang skripsi dan sempro terhubung dengan Anda</p>
                </div>
                <span class="px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-xs rounded-xl">
                    {{ count($stats['recent_schedules']) }} Sesi Terjadwal
                </span>
            </div>

            @if(count($stats['recent_schedules']) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/70 dark:bg-slate-700/50 text-slate-400 dark:text-slate-500 text-[10px] font-extrabold uppercase tracking-wider border-b border-slate-100 dark:border-slate-700">
                                <th class="py-4 px-6 w-40">TANGGAL & WAKTU</th>
                                <th class="py-4 px-6">MAHASISWA</th>
                                <th class="py-4 px-6">JUDUL TUGAS AKHIR</th>
                                <th class="py-4 px-6 w-32">RUANGAN</th>
                                <th class="py-4 px-6 w-36 text-center">PERAN DOSEN</th>
                                <th class="py-4 px-6 w-28 text-center">JENIS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-sm font-medium">
                            @foreach($stats['recent_schedules'] as $s)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="py-4 px-6">
                                        <div class="text-slate-900 dark:text-slate-100 font-bold text-xs sm:text-sm">
                                            {{ $s->tanggal ? \Carbon\Carbon::parse($s->tanggal)->translatedFormat('l, d/m/Y') : 'Belum diplotting' }}
                                        </div>
                                        <div class="mt-1.5">
                                            @if($s->jam)
                                                <span class="inline-block bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-800 font-extrabold text-xs">{{ $s->jam }} WIB</span>
                                            @else
                                                <span class="text-slate-400 text-xs font-semibold">-</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-slate-900 dark:text-slate-100">{{ $s->nama_mahasiswa }}</div>
                                        <div class="text-xs text-slate-400 dark:text-slate-500 font-mono">{{ $s->nim }}</div>
                                    </td>
                                    <td class="py-4 px-6 max-w-xs">
                                        <p class="text-xs text-slate-700 dark:text-slate-300 font-medium line-clamp-2">{{ $s->judul_skripsi }}</p>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-600">
                                            {{ $s->ruang ? $s->ruang->kode_ruangan : '-' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        @php
                                            $dosenId = $dosen ? $dosen->id : 0;
                                            $roles = [];
                                            if ($s->dosen_pembimbing_utama_id == $dosenId) $roles[] = ['name' => 'Pembimbing Utama', 'class' => 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700'];
                                            if ($s->dosen_pembimbing_pendamping_id == $dosenId) $roles[] = ['name' => 'Pembimbing Pendamping', 'class' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700'];
                                            if ($s->ketua_penguji_id == $dosenId) $roles[] = ['name' => 'Ketua Penguji', 'class' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-700'];
                                            if ($s->anggota_penguji_1_id == $dosenId) $roles[] = ['name' => 'Penguji 1', 'class' => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700'];
                                            if ($s->anggota_penguji_2_id == $dosenId) $roles[] = ['name' => 'Penguji 2', 'class' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-700'];
                                        @endphp
                                        <div class="flex flex-wrap gap-1.5 justify-center items-center">
                                            @forelse($roles as $r)
                                                <span class="inline-flex items-center px-2.5 py-0.5 text-[11px] font-bold rounded-lg border whitespace-nowrap {{ $r['class'] }}">
                                                    {{ $r['name'] }}
                                                </span>
                                            @empty
                                                <span class="inline-flex items-center px-2.5 py-0.5 text-[11px] font-bold rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 whitespace-nowrap">
                                                    Penguji / Dosbing
                                                </span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="px-2.5 py-1 text-xs font-extrabold rounded-full {{ $s->jenis_tugas_akhir == 'sempro' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300' }}">
                                            {{ strtoupper($s->jenis_tugas_akhir) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State Box -->
                <div class="py-16 px-6 text-center flex flex-col items-center justify-center">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-200">Belum Ada Aktivitas</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-sm">Aktivitas skripsi dan pengajuan akan ditampilkan di sini.</p>
                </div>
            @endif
        </div>

        <!-- Modal Form Kesediaan Menguji Sempro & Skripsi -->
        <div x-show="isOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-transition.opacity>
            <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-2xl w-full p-6 text-slate-800 dark:text-slate-200 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col border border-slate-200 dark:border-slate-700" @click.away="closeModal()">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700 shrink-0">
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">Form Kesediaan Menguji Ujian</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Silakan tentukan hari, tanggal, dan rentang jam ketersediaan Anda untuk Sempro dan Skripsi.</p>
                    </div>
                    <button type="button" @click="closeModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600 flex items-center justify-center font-bold cursor-pointer">✕</button>
                </div>

                {{-- Modal Body Form --}}
                <form method="POST" action="{{ route('dosen.kesediaan.store') }}" class="flex-1 overflow-y-auto py-4 space-y-4">
                    @csrf

                    {{-- Nama Dosen (Readonly) --}}
                    <div class="bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-2xl p-4 flex items-center justify-between">
                        <div>
                            <label class="text-[10px] font-extrabold uppercase text-slate-400 dark:text-slate-500 tracking-wider">Nama Dosen Penguji</label>
                            <div class="font-extrabold text-slate-800 dark:text-slate-200 text-sm">{{ Auth::user()->name }}</div>
                        </div>
                        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-700 px-3 py-1 rounded-xl">NIDN: {{ $dosen->nidn ?? '-' }}</span>
                    </div>

                    @if($allWaves->count() > 0)
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Gelombang Ujian (Opsional)</label>
                            <select name="wave_id" required class="w-full text-xs p-2.5 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 cursor-pointer">
                                @foreach($allWaves as $w)
                                    <option value="{{ $w->id }}">Gelombang {{ $w->gelombang }} - {{ ucfirst($w->jenis) }} ({{ $w->tanggal_mulai ? $w->tanggal_mulai->format('d/m/Y') : '' }} s/d {{ $w->tanggal_selesai ? $w->tanggal_selesai->format('d/m/Y') : '' }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Slot List --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Rentang Hari & Waktu Ketersediaan</label>
                            <button type="button" @click="addSlot()" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 border border-indigo-200 dark:border-indigo-700 px-3 py-1.5 rounded-xl transition-colors cursor-pointer">
                                + Tambah Slot Jam
                            </button>
                        </div>

                        <template x-for="(slot, index) in slots" :key="index">
                            <div class="bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-2xl p-4 mb-3 space-y-3 relative">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/40 px-2.5 py-0.5 rounded-lg" x-text="'Slot ' + (index + 1)"></span>
                                    <button type="button" x-show="slots.length > 1" @click="removeSlot(index)" class="text-rose-500 hover:text-rose-700 text-xs font-bold cursor-pointer">
                                        Hapus Slot
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Tanggal *</label>
                                        <input type="date" :name="'slots['+index+'][tanggal]'" x-model="slot.tanggal" required class="w-full text-xs p-2.5 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Catatan / Keterangan (Opsional)</label>
                                        <input type="text" :name="'slots['+index+'][keterangan]'" x-model="slot.keterangan" placeholder="Contoh: Ketersediaan menguji Sempro & Skripsi secara luring/online" class="w-full text-xs p-2.5 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-700 shrink-0">
                        <button type="button" @click="closeModal()" class="px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 text-xs font-extrabold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md shadow-indigo-600/20 cursor-pointer">Simpan Kesediaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function kesediaanModalHandler() {
            return {
                isOpen: false,
                slots: [
                    { tanggal: '{{ now()->format('Y-m-d') }}', keterangan: '' }
                ],
                openModal() {
                    this.isOpen = true;
                },
                closeModal() {
                    this.isOpen = false;
                },
                addSlot() {
                    this.slots.push({ tanggal: '{{ now()->format('Y-m-d') }}', keterangan: '' });
                },
                removeSlot(index) {
                    if (this.slots.length > 1) {
                        this.slots.splice(index, 1);
                    }
                }
            }
        }
    </script>
</x-app-layout>

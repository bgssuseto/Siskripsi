<x-app-layout title="Administrasi - Surat Keputusan (SK)">
    <x-slot:header>Administrasi Surat Keputusan (SK)</x-slot:header>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Hero Banner Header -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-8 text-white shadow-xl border border-slate-800">
            <div class="absolute -right-10 -top-10 w-56 h-56 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute right-32 -bottom-10 w-44 h-44 bg-purple-500/10 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11.5px] font-bold text-indigo-200 border border-white/10 mb-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Modul Administrasi Surat Keputusan (SK)
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Generate SK Dekan (Excel Output)</h1>
                    <p class="text-indigo-200/90 text-xs sm:text-sm mt-1.5 max-w-2xl leading-relaxed">
                        Cetak dan unduh Surat Keputusan (SK) Dekan untuk Penetapan Dosen Pembimbing Skripsi & Tim Dosen Penguji Ujian (Ketua, Anggota 1, & Anggota 2) dalam format Excel (.xlsx).
                    </p>
                </div>

                <div class="shrink-0 flex items-center gap-3">
                    <div class="px-4 py-3 bg-white/10 backdrop-blur-md rounded-2xl border border-white/15 text-center shadow-md">
                        <p class="text-[10px] text-indigo-300 font-bold uppercase tracking-wider">Periode Aktif</p>
                        <p class="text-sm font-extrabold text-white mt-0.5">{{ $selectedPeriode->nama_periode ?? 'Belum Ditentukan' }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if(session('warning'))
            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-300 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
                <span>⚠️ {{ session('warning') }}</span>
                <button onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-800">✕</button>
            </div>
        @endif

        <!-- Filter Card -->
        <div class="bg-white dark:bg-slate-800/80 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-700 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mb-4 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                Filter Data SK Berdasarkan Periode & Tanggal
            </h3>

            <form method="GET" action="{{ route('administrasi.sk.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Periode Akademik</label>
                    <select name="periode_id" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 text-xs font-bold border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                        @foreach($periodes as $p)
                            <option value="{{ $p->id }}" {{ $selectedPeriodeId == $p->id ? 'selected' : '' }}>
                                {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Tanggal Pendaftaran Mulai</label>
                    <input type="date" name="tanggal_pendaftaran_mulai" value="{{ request('tanggal_pendaftaran_mulai') }}" class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Tanggal Pendaftaran Selesai</label>
                    <input type="date" name="tanggal_pendaftaran_selesai" value="{{ request('tanggal_pendaftaran_selesai') }}" class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition-all cursor-pointer">
                        Terapkan Filter
                    </button>
                    @if(request()->anyFilled(['tanggal_pendaftaran_mulai', 'tanggal_pendaftaran_selesai']))
                        <a href="{{ route('administrasi.sk.index', ['periode_id' => $selectedPeriodeId]) }}" class="px-3.5 py-2.5 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 font-bold text-xs rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- 2 Main SK Cards (Excel Export Action Cards) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Card 1: SK Pembimbing Excel -->
            <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-indigo-100 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-indigo-50 dark:bg-indigo-950/30 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>

                <div class="relative z-10 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center text-xl shrink-0 border border-indigo-100 dark:border-indigo-700">
                            👨‍🏫
                        </div>
                        <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-[10px] font-extrabold uppercase rounded-full border border-indigo-200 dark:border-indigo-700">
                            Output Excel (.xlsx)
                        </span>
                    </div>

                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">1. SK Dekan - Dosen Pembimbing Skripsi</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            Generate file Excel (.xlsx) Surat Keputusan penetapan <strong>Pembimbing Utama</strong> dan <strong>Pembimbing Pendamping</strong> bagi seluruh mahasiswa terdaftar pada periode terpilih. Dilengkapi sheet Rekapitulasi Jumlah Bimbingan per Dosen.
                        </p>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-600 grid grid-cols-2 gap-3 text-center">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Total Mahasiswa</p>
                            <p class="text-xl font-extrabold text-slate-800 dark:text-slate-200 mt-0.5">{{ $totalSidangs }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Dosen Pembimbing</p>
                            <p class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $dosenPembimbingIds->count() }} Orang</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 pt-6 mt-6 border-t border-slate-100 dark:border-slate-700">
                    <a href="{{ route('administrasi.sk.export-pembimbing', request()->all()) }}" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-extrabold text-xs rounded-2xl shadow-lg shadow-indigo-600/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Download SK Pembimbing (.xlsx)</span>
                    </a>
                </div>
            </div>

            <!-- Card 2: SK Penguji Excel -->
            <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-purple-100 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-purple-50 dark:bg-purple-950/30 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>

                <div class="relative z-10 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 font-bold flex items-center justify-center text-xl shrink-0 border border-purple-100 dark:border-purple-700">
                            🎓
                        </div>
                        <span class="px-3 py-1 bg-purple-50 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 text-[10px] font-extrabold uppercase rounded-full border border-purple-200 dark:border-purple-700">
                            Output Excel (.xlsx)
                        </span>
                    </div>

                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">2. SK Dekan - Tim Dosen Penguji Ujian</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            Generate file Excel (.xlsx) Surat Keputusan penetapan tim penguji: <strong>Ketua Penguji</strong>, <strong>Anggota Penguji 1</strong>, dan <strong>Anggota Penguji 2</strong>. Dilengkapi sheet Rekapitulasi Beban Penguji per Dosen.
                        </p>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-600 grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Sempro</p>
                            <p class="text-lg font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">{{ $totalSempro }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Skripsi</p>
                            <p class="text-lg font-extrabold text-purple-600 dark:text-purple-400 mt-0.5">{{ $totalSkripsi }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Dosen Penguji</p>
                            <p class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $dosenPengujiIds->count() }} Orang</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 pt-6 mt-6 border-t border-slate-100 dark:border-slate-700">
                    <a href="{{ route('administrasi.sk.export-penguji', request()->all()) }}" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white font-extrabold text-xs rounded-2xl shadow-lg shadow-purple-600/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Download SK Tim Penguji (.xlsx)</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

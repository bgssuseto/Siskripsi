<x-app-layout title="Administrasi - Dashboard Analitik">
    <x-slot:header>Dashboard Analitik</x-slot:header>

    <div class="space-y-6">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 tracking-tight flex items-center gap-2.5">
                    <span class="p-2 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </span>
                    Dashboard Analitik Koordinator/Kaprodi
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Rekap beban bimbingan &amp; menguji dosen, keterpakaian ruang, dan timeline sidang dalam satu tampilan level program studi.
                </p>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form method="GET" action="{{ route('administrasi.analitik.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Periode Akademik</label>
                    <select name="periode_id" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">-- Semua Periode --</option>
                        @foreach($periodes as $p)
                        <option value="{{ $p->id }}" {{ (string) $selectedPeriodeId === (string) $p->id ? 'selected' : '' }}>
                            {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Jenis</label>
                    <select name="jenis" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="" {{ $jenis ? '' : 'selected' }}>-- Skripsi &amp; Sempro --</option>
                        <option value="skripsi" {{ $jenis === 'skripsi' ? 'selected' : '' }}>Sidang Skripsi</option>
                        <option value="sempro" {{ $jenis === 'sempro' ? 'selected' : '' }}>Seminar Proposal (Sempro)</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-md transition-all">
                        Filter
                    </button>
                    @if(request()->anyFilled(['periode_id', 'jenis']))
                    <a href="{{ route('administrasi.analitik.index') }}"
                       class="px-3.5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium text-sm rounded-xl transition-all flex items-center justify-center" title="Reset Filter">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sidang Skripsi</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-1.5">{{ $totalSkripsi }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Seminar Proposal</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-1.5">{{ $totalSempro }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sudah Terjadwal</p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1.5">{{ $totalTerjadwal }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Belum Terjadwal</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1.5">{{ $totalBelumJadwal }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Beban Dosen -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Beban Bimbingan &amp; Menguji Dosen</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Diranking dari beban tertinggi (bimbingan + menguji digabung)</p>
                </div>
                <div class="max-h-[420px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($bebanDosen as $row)
                    <div class="px-5 py-3">
                        <div class="flex items-center justify-between gap-3 mb-1.5">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">{{ $row['dosen']->nama_dosen }}</span>
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 shrink-0">{{ $row['total'] }} total</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-500 dark:bg-indigo-400 rounded-full" style="width: {{ round($row['total'] / $maxBeban * 100) }}%"></div>
                        </div>
                        <div class="flex items-center gap-3 mt-1.5 text-[11px] text-slate-400 dark:text-slate-500">
                            <span>Pembimbing Utama: <strong class="text-slate-600 dark:text-slate-300">{{ $row['bimbing_utama'] }}</strong></span>
                            <span>Pendamping: <strong class="text-slate-600 dark:text-slate-300">{{ $row['bimbing_pendamping'] }}</strong></span>
                            <span>Menguji: <strong class="text-slate-600 dark:text-slate-300">{{ $row['menguji'] }}</strong></span>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-10 text-center text-slate-400 dark:text-slate-500 text-sm">Belum ada data untuk filter ini.</div>
                    @endforelse
                </div>
            </div>

            <!-- Keterpakaian Ruang -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Keterpakaian Ruang</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Nama & kode ruang, jumlah sidang, klik untuk lihat hari/tanggal dipakai</p>
                </div>
                <div class="max-h-[460px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($keterpakaianRuang as $row)
                    <div class="px-5 py-3" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-3 text-left cursor-pointer">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">{{ $row['ruang']->nama_ruangan ?? $row['ruang']->kode_ruangan }}</span>
                                    <span class="text-[10px] font-mono font-bold text-indigo-600 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-500/10 px-1.5 py-0.5 rounded shrink-0">{{ $row['ruang']->kode_ruangan }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $row['jadwal']->count() }} hari terpakai</div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $row['jumlah'] }} sidang</span>
                                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>
                        <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mt-2">
                            <div class="h-full bg-indigo-500 dark:bg-indigo-400 rounded-full" style="width: {{ round($row['jumlah'] / $maxRuang * 100) }}%"></div>
                        </div>
                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="mt-2.5 flex flex-wrap gap-1.5">
                            @foreach($row['jadwal'] as $j)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10.5px] font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                📅 {{ $j['tanggal_label'] }} <span class="text-slate-300 dark:text-slate-600">·</span> <span class="text-indigo-600 dark:text-indigo-300">{{ $j['jumlah'] }} sidang</span>
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-10 text-center text-slate-400 dark:text-slate-500 text-sm">Belum ada data untuk filter ini.</div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Timeline Sidang -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Timeline Sidang Terjadwal</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Sebaran jumlah sidang per tanggal &mdash; arahkan kursor ke batang untuk detail</p>
            </div>
            <div class="p-5">
                @if($timeline->isEmpty())
                <div class="py-10 text-center text-slate-400 dark:text-slate-500 text-sm">Belum ada sidang terjadwal untuk filter ini.</div>
                @else
                <div class="overflow-x-auto pb-1">
                    <div class="flex items-end gap-2 min-w-max border-b border-slate-200 dark:border-slate-700" style="height: 180px;">
                        @foreach($timeline as $tanggal => $jumlah)
                        @php $tglObj = \Carbon\Carbon::parse($tanggal); @endphp
                        <div class="flex flex-col items-center justify-end h-full group relative" style="width: 30px;">
                            <!-- Tooltip -->
                            <div class="absolute bottom-full mb-2 hidden group-hover:flex flex-col items-center z-10 pointer-events-none">
                                <div class="bg-slate-900 dark:bg-slate-700 text-white text-[10.5px] font-semibold px-2.5 py-1.5 rounded-lg whitespace-nowrap shadow-lg text-center">
                                    {{ $tglObj->locale('id')->translatedFormat('l, d F Y') }}<br>
                                    <span class="text-indigo-300 font-bold">{{ $jumlah }} sidang</span>
                                </div>
                                <div class="w-2 h-2 bg-slate-900 dark:bg-slate-700 rotate-45 -mt-1"></div>
                            </div>

                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">{{ $jumlah }}</span>
                            <div class="w-full bg-indigo-500 dark:bg-indigo-400 group-hover:bg-indigo-600 dark:group-hover:bg-indigo-300 rounded-t-[4px] transition-colors duration-150"
                                 style="height: {{ max(4, round($jumlah / $maxTimeline * 128)) }}px"></div>
                        </div>
                        @endforeach
                    </div>
                    <div class="flex items-start gap-2 min-w-max mt-1.5">
                        @foreach($timeline as $tanggal => $jumlah)
                        <div class="flex justify-center" style="width: 30px;">
                            <span class="text-[9px] text-slate-400 dark:text-slate-500 whitespace-nowrap" style="writing-mode: vertical-rl; transform: rotate(180deg);">{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d M') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>

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
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Jumlah sidang terjadwal per ruang</p>
                </div>
                <div class="max-h-[420px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($keterpakaianRuang as $row)
                    <div class="px-5 py-3">
                        <div class="flex items-center justify-between gap-3 mb-1.5">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">{{ $row['ruang']->nama_ruangan ?? $row['ruang']->kode_ruangan }}</span>
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 shrink-0">{{ $row['jumlah'] }} sidang</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-500 dark:bg-amber-400 rounded-full" style="width: {{ round($row['jumlah'] / $maxRuang * 100) }}%"></div>
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
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Sebaran jumlah sidang per tanggal</p>
            </div>
            <div class="p-5">
                @if($timeline->isEmpty())
                <div class="py-10 text-center text-slate-400 dark:text-slate-500 text-sm">Belum ada sidang terjadwal untuk filter ini.</div>
                @else
                <div class="overflow-x-auto">
                    <div class="flex items-end gap-1.5 min-w-max" style="height: 160px;">
                        @foreach($timeline as $tanggal => $jumlah)
                        <div class="flex flex-col items-center justify-end h-full group relative" style="width: 26px;">
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1">{{ $jumlah }}</span>
                            <div class="w-full bg-emerald-500 dark:bg-emerald-400 rounded-t-md" style="height: {{ max(4, round($jumlah / $maxTimeline * 110)) }}px" title="{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d M Y') }}: {{ $jumlah }} sidang"></div>
                            <span class="text-[9px] text-slate-400 dark:text-slate-500 mt-1.5 whitespace-nowrap" style="writing-mode: vertical-rl; transform: rotate(180deg);">{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d M') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>

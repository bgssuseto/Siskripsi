<x-app-layout title="Administrasi - Surat Keputusan (SK)">
    <x-slot:header>Administrasi Surat Keputusan (SK)</x-slot:header>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Hero Banner Header -->
        <x-page-header title="Generate SK Dekan (Excel Output)" subtitle="Cetak dan unduh Surat Keputusan (SK) Dekan untuk Penetapan Dosen Pembimbing Skripsi & Tim Dosen Penguji Ujian Skripsi. SK Pembimbing dihitung sejak Sempro disetujui; SK Penguji hanya dari ujian skripsi (sidang & jurnal)." icon="badge-check" accent="indigo">
            <form method="GET" action="{{ route('administrasi.sk.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-2">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Filter</span>
                    <select name="periode_id" aria-label="Periode akademik" onchange="this.form.gelombang.value=''; this.form.submit()"
                            class="h-10 min-w-[210px] px-3 rounded-xl text-sm font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 cursor-pointer">
                        @foreach($periodes as $p)
                            <option value="{{ $p->id }}" @selected($selectedPeriodeId == $p->id)>{{ $p->nama_periode }}{{ $p->aktif ? ' (Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                    <select name="gelombang" aria-label="Gelombang" onchange="this.form.submit()"
                            class="h-10 px-3 rounded-xl text-sm font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 cursor-pointer">
                        <option value="">Semua Gelombang</option>
                        @foreach($gelombangOptions as $g)
                            <option value="{{ $g['value'] }}" @selected((string) $selectedGelombang === $g['value'])>{{ $g['label'] }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="ui-btn-secondary">Terapkan</button></noscript>
                </form>
        </x-page-header>

        @if(session('warning'))
            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-300 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
                <span><x-icon name="warning" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ session('warning') }}</span>
                <button onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-800"><x-icon name="x" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></button>
            </div>
        @endif

        @if($statusFinal && $selectedPeriode)
            <x-sk-status-final :status="$statusFinal" :periode="$selectedPeriode" />
        @endif

        <!-- 2 Main SK Cards (Excel Export Action Cards) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Card 1: SK Pembimbing Excel -->
            <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-indigo-100 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-indigo-50 dark:bg-indigo-950/30 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>

                <div class="relative z-10 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-700">
                            <x-icon name="user" class="w-5 h-5" />
                        </div>
                        <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-[10px] font-extrabold uppercase rounded-full border border-indigo-200 dark:border-indigo-700">
                            Output Excel (.xlsx)
                        </span>
                    </div>

                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">1. SK Dekan - Dosen Pembimbing Skripsi</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            Generate file Excel (.xlsx) Surat Keputusan penetapan <strong>Pembimbing Utama</strong> dan <strong>Pembimbing Pendamping</strong> bagi mahasiswa bimbingan pada periode terpilih — dihitung sejak pendaftaran <strong>Sempro disetujui</strong> hingga ujian skripsi (sidang &amp; jurnal), satu baris per mahasiswa. Dilengkapi sheet Rekapitulasi Jumlah Bimbingan per Dosen.
                        </p>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-600 grid grid-cols-2 gap-3 text-center">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Mahasiswa Bimbingan</p>
                            <p class="text-xl font-extrabold text-slate-800 dark:text-slate-200 mt-0.5">{{ $totalBimbingan }}</p>
                            @if($totalBimbinganSempro > 0)
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ $totalBimbinganSempro }} masih tahap Sempro</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Dosen Pembimbing</p>
                            <p class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $dosenPembimbingIds->count() }} Orang</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 pt-6 mt-6 border-t border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row gap-2.5">
                    <a href="{{ route('administrasi.sk.export-pembimbing', request()->except('jenis')) }}" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-extrabold text-xs rounded-2xl shadow-lg shadow-indigo-600/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Excel (.xlsx)</span>
                    </a>
                    <a href="{{ route('administrasi.sk.export-pembimbing-pdf', request()->except('jenis')) }}" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 text-indigo-700 dark:text-indigo-300 font-extrabold text-xs rounded-2xl border-2 border-indigo-200 dark:border-indigo-800 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>PDF</span>
                    </a>
                </div>
            </div>

            <!-- Card 2: SK Penguji Excel -->
            <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-purple-100 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-purple-50 dark:bg-purple-950/30 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>

                <div class="relative z-10 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 font-bold flex items-center justify-center shrink-0 border border-purple-100 dark:border-purple-700">
                            <x-icon name="academic" class="w-5 h-5" />
                        </div>
                        <span class="px-3 py-1 bg-purple-50 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 text-[10px] font-extrabold uppercase rounded-full border border-purple-200 dark:border-purple-700">
                            Output Excel (.xlsx)
                        </span>
                    </div>

                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">2. SK Dekan - Tim Dosen Penguji Ujian</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            Generate file Excel (.xlsx) Surat Keputusan penetapan tim penguji: <strong>Ketua Penguji</strong>, <strong>Anggota Penguji 1</strong>, dan <strong>Anggota Penguji 2</strong>. Hanya ujian skripsi (sidang &amp; jurnal) — sempro tidak dihitung. Dilengkapi sheet Rekapitulasi Beban Penguji per Dosen.
                        </p>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-600 grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Sidang</p>
                            <p class="text-lg font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">{{ $totalSidangReguler }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Jurnal</p>
                            <p class="text-lg font-extrabold text-purple-600 dark:text-purple-400 mt-0.5">{{ $totalJurnal }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Dosen Penguji</p>
                            <p class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $dosenPengujiIds->count() }} Orang</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 pt-6 mt-6 border-t border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row gap-2.5">
                    <a href="{{ route('administrasi.sk.export-penguji', request()->except('jenis')) }}" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white font-extrabold text-xs rounded-2xl shadow-lg shadow-purple-600/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Excel (.xlsx)</span>
                    </a>
                    <a href="{{ route('administrasi.sk.export-penguji-pdf', request()->except('jenis')) }}" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 text-purple-700 dark:text-purple-300 font-extrabold text-xs rounded-2xl border-2 border-purple-200 dark:border-purple-800 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>PDF</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- Infografis / Statistik -->
        <div class="bg-white dark:bg-slate-800/80 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-700 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mb-1 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                Infografis Beban Bimbingan &amp; Penguji
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">Mengikuti filter periode &amp; gelombang di atas — hanya ujian skripsi.</p>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Proporsi Jalur Tugas Akhir</p>
                    <div style="height: 220px;">
                        <canvas id="chartJalur"></canvas>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Top Dosen Pembimbing (Beban Bimbingan)</p>
                    <div style="height: 220px;">
                        <canvas id="chartPembimbing"></canvas>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Top Dosen Penguji (Beban Menguji)</p>
                    <div style="height: 220px;">
                        <canvas id="chartPenguji"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#f8fafc' : '#1e293b';
            const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.06)';

            new Chart(document.getElementById('chartJalur').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Sidang Skripsi', 'Jurnal'],
                    datasets: [{
                        data: [{{ $totalSidangReguler }}, {{ $totalJurnal }}],
                        backgroundColor: ['#f59e0b', '#7c3aed'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor, font: { weight: 'bold', size: 10 } } }
                    }
                }
            });

            new Chart(document.getElementById('chartPembimbing').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($pembimbingChart->pluck('nama')) !!},
                    datasets: [{
                        label: 'Jumlah Bimbingan',
                        data: {!! json_encode($pembimbingChart->pluck('total')) !!},
                        backgroundColor: '#4361ee',
                        borderRadius: 6,
                        maxBarThickness: 22,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: textColor, stepSize: 1 }, grid: { color: gridColor } },
                        y: { ticks: { color: textColor, font: { size: 9.5 } }, grid: { display: false } }
                    }
                }
            });

            new Chart(document.getElementById('chartPenguji').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($pengujiChart->pluck('nama')) !!},
                    datasets: [{
                        label: 'Jumlah Menguji',
                        data: {!! json_encode($pengujiChart->pluck('total')) !!},
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                        maxBarThickness: 22,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: textColor, stepSize: 1 }, grid: { color: gridColor } },
                        y: { ticks: { color: textColor, font: { size: 9.5 } }, grid: { display: false } }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>

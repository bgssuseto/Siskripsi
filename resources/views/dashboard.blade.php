<x-app-layout title="Dashboard Utama">
    <x-slot:header>
        Dashboard Overview
    </x-slot:header>

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <!-- Welcome Header Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-900 via-slate-900 to-purple-950 p-6 sm:p-8 text-white shadow-xl mb-8 border border-indigo-900/50">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold text-indigo-200 border border-white/10 mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Sistem Informasi Skripsi & Sempro Teknik Informatika
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Selamat Datang, {{ Auth::user()->name }}! 👋</h1>
                <p class="text-slate-300 text-sm mt-1 max-w-xl">
                    Anda masuk sebagai <span class="font-bold text-white uppercase">{{ Auth::user()->role_label }}</span>. Berikut adalah ringkasan data pendaftaran, verifikasi, dan trend kelulusan terkini.
                </p>
            </div>
            
            <div class="shrink-0 flex items-center gap-3 flex-wrap">
                <span class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-extrabold border shadow-lg {{ Auth::user()->role_badge_class }}">
                    Role: {{ Auth::user()->role_label }}
                </span>
                @if(Auth::user()->hasRole(['super_admin', 'koordinator']))
                <a href="{{ route('pendaftaran.sempro') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-all">
                    🛡️ Verifikasi Pendaftaran →
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Stats Cards (REAL DATA) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Card 1: Total Pendaftar --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Pendaftar</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">{{ $totalMahasiswa }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-semibold">Mahasiswa Terdata</p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 rounded-2xl flex items-center justify-center font-bold text-xl border border-indigo-100 dark:border-indigo-900 shrink-0">
                    🎓
                </div>
            </div>
        </div>

        {{-- Card 2: Skripsi Reguler vs Artikel Jurnal --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Skripsi vs Jurnal</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">{{ $skripsiRegulerCount }} <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">/ {{ $artikelJurnalCount }} Jurnal</span></p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-semibold">Sidang Reguler / Artikel Jurnal</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 dark:bg-purple-950/80 text-purple-600 dark:text-purple-400 rounded-2xl flex items-center justify-center font-bold text-xl border border-purple-100 dark:border-purple-900 shrink-0">
                    📄
                </div>
            </div>
        </div>

        {{-- Card 3: Plotting Jadwal --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status Plotting</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">{{ $totalTerjadwal }}</p>
                    <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-1 font-bold">⚠️ {{ $totalBelumPlotting }} Belum Plotting</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center font-bold text-xl border border-emerald-100 dark:border-emerald-900 shrink-0">
                    📅
                </div>
            </div>
        </div>

        {{-- Card 4: Status Verifikasi --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Verifikasi Berkas</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">{{ $verifikasiDisetujui }}</p>
                    <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-1 font-bold">⏳ {{ $verifikasiMenunggu }} Menunggu</p>
                </div>
                <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/80 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center font-bold text-xl border border-amber-100 dark:border-amber-900 shrink-0">
                    🛡️
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Chart 1: Bar Chart Status Verifikasi --}}
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Status Verifikasi Pendaftaran</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Ringkasan berkas disetujui, menunggu, & ditolak</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-300 rounded-lg border border-indigo-200 dark:border-indigo-800">
                    📊 Bar
                </span>
            </div>
            <div class="h-64 relative">
                <canvas id="verifikasiChart"></canvas>
            </div>
        </div>

        {{-- Chart 2: Doughnut Chart Proporsi Jalur Skripsi Reguler vs Artikel Jurnal --}}
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-3">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Proporsi Jalur Tugas Akhir</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Perbandingan Sidang Skripsi vs Artikel Jurnal</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 bg-purple-50 dark:bg-purple-950 text-purple-600 dark:text-purple-300 rounded-lg border border-purple-200 dark:border-purple-800">
                    🍩 Jalur
                </span>
            </div>

            {{-- Summary Badges --}}
            <div class="flex items-center justify-around gap-2 mb-3 bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 text-[11px]">
                <div class="text-center">
                    <span class="block font-semibold text-slate-500 dark:text-slate-400 text-[10px]">Sidang Reguler</span>
                    <strong class="text-indigo-600 dark:text-indigo-400 text-xs font-extrabold">{{ $skripsiRegulerCount }} Mhs</strong>
                </div>
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700"></div>
                <div class="text-center">
                    <span class="block font-semibold text-slate-500 dark:text-slate-400 text-[10px]">Artikel Jurnal</span>
                    <strong class="text-emerald-600 dark:text-emerald-400 text-xs font-extrabold">{{ $artikelJurnalCount }} Mhs</strong>
                </div>
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700"></div>
                <div class="text-center">
                    <span class="block font-semibold text-slate-500 dark:text-slate-400 text-[10px]">Sempro</span>
                    <strong class="text-purple-600 dark:text-purple-400 text-xs font-extrabold">{{ $semproCount }} Mhs</strong>
                </div>
            </div>

            <div class="h-52 relative flex items-center justify-center">
                <canvas id="jenisChart"></canvas>
            </div>
        </div>

        {{-- Chart 3: Line Chart Lulusan Tiap Tahun --}}
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Grafik Line Lulusan Tiap Tahun</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Trend total mahasiswa lulus & selesai per tahun</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-300 rounded-lg border border-emerald-200 dark:border-emerald-800">
                    📈 Line Chart
                </span>
            </div>
            <div class="h-64 relative">
                <canvas id="lineLulusanChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Pendaftaran & Activity Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Pendaftaran & Aktivitas Terbaru</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar pendaftaran seminar proposal & skripsi mahasiswa terkini</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pendaftaran.sempro') }}" class="px-3 py-1.5 text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl transition-all">
                    Verifikasi Sempro
                </a>
                <a href="{{ route('pendaftaran.skripsi') }}" class="px-3 py-1.5 text-xs font-bold bg-indigo-50 dark:bg-indigo-950/80 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 rounded-xl border border-indigo-200 dark:border-indigo-800 transition-all">
                    Verifikasi Skripsi
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 font-extrabold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wide">
                    <tr>
                        <th class="py-3.5 px-4">Tgl Daftar</th>
                        <th class="py-3.5 px-4">NIM</th>
                        <th class="py-3.5 px-4">Nama Mahasiswa</th>
                        <th class="py-3.5 px-4">Jenis</th>
                        <th class="py-3.5 px-4">Dosbing Utama</th>
                        <th class="py-3.5 px-4 text-center">Verifikasi</th>
                        <th class="py-3.5 px-4 text-center">Plotting Jadwal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @forelse($recentActivities as $item)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ $item->tanggal_pendaftaran ? $item->tanggal_pendaftaran->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2 py-0.5 rounded-md font-semibold text-[11px]">
                                    {{ $item->nim }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-slate-100 min-w-[160px]">
                                {{ $item->nama_mahasiswa }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wide {{ $item->jenis_tugas_akhir === 'sempro' ? 'bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800' : 'bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' }}">
                                    {{ $item->jenis_label }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300 min-w-[150px]">
                                {{ $item->pembimbingUtama ? $item->pembimbingUtama->nama_dosen : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                {!! $item->verifikasi_status_html !!}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                {!! $item->getJadwalStatusHtml() !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400 dark:text-slate-500 font-semibold">
                                Belum ada data pendaftaran terbaru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart.js Script Initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#f8fafc' : '#1e293b';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.06)';

            // 1. Chart Status Verifikasi (Bar)
            const ctxVerifikasi = document.getElementById('verifikasiChart').getContext('2d');
            new Chart(ctxVerifikasi, {
                type: 'bar',
                data: {
                    labels: ['Menunggu', 'Disetujui', 'Ditolak'],
                    datasets: [{
                        label: 'Jumlah Pendaftaran',
                        data: [{{ $verifikasiCounts['Menunggu'] }}, {{ $verifikasiCounts['Disetujui'] }}, {{ $verifikasiCounts['Ditolak'] }}],
                        backgroundColor: ['#f59e0b', '#10b981', '#f43f5e'],
                        borderRadius: 8,
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            ticks: { color: textColor, font: { weight: 'bold' } },
                            grid: { display: false }
                        },
                        y: {
                            ticks: { color: textColor, stepSize: 1 },
                            grid: { color: gridColor }
                        }
                    }
                }
            });

            // 2. Chart Proporsi Jalur Tugas Akhir (Doughnut)
            const ctxJenis = document.getElementById('jenisChart').getContext('2d');
            new Chart(ctxJenis, {
                type: 'doughnut',
                data: {
                    labels: ['Sidang Skripsi (Reguler)', 'Artikel Jurnal', 'Seminar Proposal'],
                    datasets: [{
                        data: [{{ $skripsiRegulerCount }}, {{ $artikelJurnalCount }}, {{ $semproCount }}],
                        backgroundColor: ['#4361ee', '#10b981', '#a855f7'],
                        borderWidth: 2,
                        borderColor: isDark ? '#0f172a' : '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: textColor, font: { weight: 'bold', size: 10 } }
                        }
                    },
                    cutout: '65%'
                }
            });

            // 3. Line Chart Lulusan Tiap Tahun
            const ctxLine = document.getElementById('lineLulusanChart').getContext('2d');
            const tahunLabels = {!! json_encode($yearlyGraduates->pluck('tahun')->map(fn($y) => 'Tahun ' . $y)->toArray()) !!};
            const yearlyTotalData = {!! json_encode($yearlyGraduates->pluck('total')->toArray()) !!};

            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: tahunLabels,
                    datasets: [{
                        label: 'Total Lulusan',
                        data: yearlyTotalData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.15)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: isDark ? '#0f172a' : '#ffffff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Total Lulusan: ' + context.raw + ' Mahasiswa';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: textColor, font: { weight: 'bold', size: 11 } },
                            grid: { display: false }
                        },
                        y: {
                            ticks: { color: textColor, stepSize: 1 },
                            grid: { color: gridColor },
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>

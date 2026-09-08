<x-app-layout title="Dashboard Utama">
    <x-slot:header>
        Dashboard Overview
    </x-slot:header>

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <!-- Welcome Header Banner -->
    <section class="relative overflow-hidden rounded-2xl bg-surface-container-lowest p-6 sm:p-8 shadow-sm mb-8">
        <div class="absolute -right-16 -top-16 w-80 h-80 rounded-full bg-primary-subtle opacity-70 pointer-events-none blur-3xl"></div>
        <div class="absolute right-40 -bottom-20 w-64 h-64 rounded-full bg-accent-gold-light/40 pointer-events-none blur-2xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex flex-col max-w-2xl">
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-subtle text-primary text-xs font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                        Sistem Informasi Skripsi &amp; Sempro Teknik Informatika
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-on-surface tracking-tight">Selamat Datang, {{ Auth::user()->name }}</h1>
                <p class="mt-2 text-sm text-on-surface-variant leading-relaxed">
                    Anda masuk sebagai <span class="font-semibold text-on-surface">{{ Auth::user()->role_label }}</span>. Berikut ringkasan data pendaftaran, verifikasi, dan trend kelulusan terkini.
                </p>
            </div>
            <div class="shrink-0 flex items-center gap-3 flex-wrap">
                <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-extrabold border shadow-sm {{ Auth::user()->role_badge_class }}">
                    Role: {{ Auth::user()->role_label }}
                </span>
                @if(Auth::user()->hasRole(['super_admin', 'koordinator']))
                <a href="{{ route('pendaftaran.sempro') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-primary-container hover:bg-primary-dark text-on-primary text-xs font-bold shadow-md hover:shadow-lg transition-all transform active:scale-95">
                    <span class="material-symbols-outlined text-base">verified</span>
                    Verifikasi Pendaftaran
                </a>
                @endif
            </div>
        </div>
    </section>

    <!-- Main Stats Cards (REAL DATA) -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Card 1: Total Pendaftar --}}
        <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-sm flex flex-col justify-between group hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div class="w-12 h-12 rounded-xl bg-primary-subtle text-primary flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-2xl">school</span>
                </div>
            </div>
            <div class="mt-5">
                <div class="text-2xl sm:text-3xl font-extrabold text-on-surface leading-none tracking-tight">{{ $totalMahasiswa }}</div>
                <div class="text-sm font-semibold text-on-surface mt-1.5">Total Pendaftar</div>
                <p class="text-xs text-on-surface-variant mt-0.5">Mahasiswa terdata skripsi &amp; sempro</p>
            </div>
        </div>

        {{-- Card 2: Skripsi Reguler vs Artikel Jurnal --}}
        <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-sm flex flex-col justify-between group hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div class="w-12 h-12 rounded-xl bg-accent-gold-light text-tertiary flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-2xl">description</span>
                </div>
            </div>
            <div class="mt-5">
                <div class="text-2xl sm:text-3xl font-extrabold text-on-surface leading-none tracking-tight">{{ $skripsiRegulerCount }} <span class="text-sm font-bold text-secondary">/ {{ $artikelJurnalCount }}</span></div>
                <div class="text-sm font-semibold text-on-surface mt-1.5">Skripsi vs Jurnal</div>
                <p class="text-xs text-on-surface-variant mt-0.5">Sidang reguler / artikel jurnal</p>
            </div>
        </div>

        {{-- Card 3: Plotting Jadwal --}}
        <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-sm flex flex-col justify-between group hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div class="w-12 h-12 rounded-xl bg-surface-container text-primary flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-2xl">event_available</span>
                </div>
            </div>
            <div class="mt-5">
                <div class="text-2xl sm:text-3xl font-extrabold text-on-surface leading-none tracking-tight">{{ $totalTerjadwal }}</div>
                <div class="text-sm font-semibold text-on-surface mt-1.5">Status Plotting</div>
                <p class="text-xs text-tertiary font-semibold mt-0.5">{{ $totalBelumPlotting }} belum plotting</p>
            </div>
        </div>

        {{-- Card 4: Status Verifikasi --}}
        <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-sm flex flex-col justify-between group hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div class="w-12 h-12 rounded-xl bg-accent-cyan-subtle text-secondary flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-2xl">verified_user</span>
                </div>
            </div>
            <div class="mt-5">
                <div class="text-2xl sm:text-3xl font-extrabold text-on-surface leading-none tracking-tight">{{ $verifikasiDisetujui }}</div>
                <div class="text-sm font-semibold text-on-surface mt-1.5">Verifikasi Berkas Disetujui</div>
                <p class="text-xs text-tertiary font-semibold mt-0.5">{{ $verifikasiMenunggu }} menunggu</p>
            </div>
        </div>
    </section>

    <!-- Charts Section -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-stretch">
        {{-- Chart 1: Bar Chart Status Verifikasi --}}
        <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-sm flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-border-subtle mb-4">
                <div>
                    <h3 class="text-sm font-extrabold text-on-surface">Status Verifikasi Pendaftaran</h3>
                    <p class="text-xs text-on-surface-variant">Ringkasan berkas disetujui, menunggu, & ditolak</p>
                </div>
                <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 bg-primary-subtle text-primary rounded-full">
                    <span class="material-symbols-outlined text-sm">bar_chart</span>
                </span>
            </div>
            <div class="flex-1 min-h-[220px] relative">
                <canvas id="verifikasiChart"></canvas>
            </div>
        </div>

        {{-- Chart 2: Grouped Bar Chart Proporsi Jalur — Sempro vs Skripsi, tiap tahap dipecah Reguler vs Jurnal --}}
        <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-sm flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-border-subtle mb-3">
                <div>
                    <h3 class="text-sm font-extrabold text-on-surface">Proporsi Jalur Tugas Akhir</h3>
                    <p class="text-xs text-on-surface-variant">Sempro vs Skripsi, masing-masing Reguler vs Jurnal</p>
                </div>
                <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 bg-accent-gold-light text-tertiary rounded-full">
                    <span class="material-symbols-outlined text-sm">route</span>
                </span>
            </div>

            {{-- Summary Badges --}}
            <div class="grid grid-cols-2 gap-2 mb-3 bg-surface-container-low p-2.5 rounded-xl text-[11px]">
                <div class="text-center">
                    <span class="block font-semibold text-on-surface-variant text-[10px]">Sempro Reguler</span>
                    <strong class="text-primary text-xs font-extrabold">{{ $semproRegulerCount }} Mhs</strong>
                </div>
                <div class="text-center">
                    <span class="block font-semibold text-on-surface-variant text-[10px]">Sempro Jurnal</span>
                    <strong class="text-secondary text-xs font-extrabold">{{ $semproJurnalCount }} Mhs</strong>
                </div>
                <div class="text-center">
                    <span class="block font-semibold text-on-surface-variant text-[10px]">Skripsi Reguler</span>
                    <strong class="text-primary text-xs font-extrabold">{{ $skripsiRegulerCount }} Mhs</strong>
                </div>
                <div class="text-center">
                    <span class="block font-semibold text-on-surface-variant text-[10px]">Skripsi Jurnal</span>
                    <strong class="text-secondary text-xs font-extrabold">{{ $artikelJurnalCount }} Mhs</strong>
                </div>
            </div>
            @if($semproBelumJalurCount > 0)
            <p class="text-[10px] text-tertiary font-semibold mb-2">{{ $semproBelumJalurCount }} data Sempro lama belum tercatat jalurnya.</p>
            @endif

            <div class="flex-1 min-h-[160px] relative">
                <canvas id="jenisChart"></canvas>
            </div>
        </div>

        {{-- Chart 3: Line Chart Lulusan Tiap Tahun --}}
        <div class="rounded-2xl bg-surface-container-lowest p-6 shadow-sm flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-border-subtle mb-4">
                <div>
                    <h3 class="text-sm font-extrabold text-on-surface">Grafik Line Lulusan Tiap Tahun</h3>
                    <p class="text-xs text-on-surface-variant">Trend total mahasiswa lulus & selesai per tahun</p>
                </div>
                <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 bg-accent-cyan-subtle text-secondary rounded-full">
                    <span class="material-symbols-outlined text-sm">show_chart</span>
                </span>
            </div>
            <div class="flex-1 min-h-[220px] relative">
                <canvas id="lineLulusanChart"></canvas>
            </div>
        </div>
    </section>

    <!-- Recent Pendaftaran & Activity Table -->
    <div class="rounded-2xl bg-surface-container-lowest shadow-sm overflow-hidden">
        <div class="p-6 border-b border-border-subtle flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-base font-extrabold text-on-surface">Pendaftaran & Aktivitas Terbaru</h2>
                <p class="text-xs text-on-surface-variant mt-0.5">Daftar pendaftaran seminar proposal & skripsi mahasiswa terkini</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pendaftaran.sempro') }}" class="px-3.5 py-1.5 text-xs font-bold bg-surface-container-low hover:bg-surface-container text-on-surface rounded-full transition-all">
                    Verifikasi Sempro
                </a>
                <a href="{{ route('pendaftaran.skripsi') }}" class="px-3.5 py-1.5 text-xs font-bold bg-primary-subtle hover:bg-primary-100 text-primary rounded-full transition-all">
                    Verifikasi Skripsi
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low/70 text-on-surface-variant font-bold uppercase tracking-wider">
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
                <tbody class="divide-y divide-border-subtle font-medium text-on-surface">
                    @forelse($recentActivities as $item)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
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
            let isDark = document.documentElement.classList.contains('dark');
            let textColor = isDark ? '#f8fafc' : '#1e293b';
            let gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.06)';
            const dashboardCharts = [];

            // 1. Chart Status Verifikasi (Bar)
            const ctxVerifikasi = document.getElementById('verifikasiChart').getContext('2d');
            dashboardCharts.push(new Chart(ctxVerifikasi, {
                type: 'bar',
                data: {
                    labels: ['Menunggu', 'Disetujui', 'Ditolak'],
                    datasets: [{
                        label: 'Jumlah Pendaftaran',
                        data: [{{ $verifikasiCounts['Menunggu'] }}, {{ $verifikasiCounts['Disetujui'] }}, {{ $verifikasiCounts['Ditolak'] }}],
                        backgroundColor: ['#f59e0b', '#10b981', '#f43f5e'],
                        borderRadius: 8,
                        borderWidth: 0,
                        maxBarThickness: 56,
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
            }));

            // 2. Chart Proporsi Jalur Tugas Akhir (Doughnut)
            const ctxJenis = document.getElementById('jenisChart').getContext('2d');
            dashboardCharts.push(new Chart(ctxJenis, {
                type: 'bar',
                data: {
                    labels: ['Sempro', 'Skripsi'],
                    datasets: [
                        {
                            label: 'Reguler',
                            data: [{{ $semproRegulerCount }}, {{ $skripsiRegulerCount }}],
                            backgroundColor: '#4361ee',
                            borderRadius: 6,
                        },
                        {
                            label: 'Jurnal',
                            data: [{{ $semproJurnalCount }}, {{ $artikelJurnalCount }}],
                            backgroundColor: '#10b981',
                            borderRadius: 6,
                        },
                        {
                            label: 'Belum Ditentukan',
                            data: [{{ $semproBelumJalurCount }}, 0],
                            backgroundColor: '#94a3b8',
                            borderRadius: 6,
                        }
                    ]
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
                    scales: {
                        x: {
                            ticks: { color: textColor, font: { weight: 'bold' } },
                            grid: { display: false }
                        },
                        y: {
                            ticks: { color: textColor, stepSize: 1 },
                            grid: { color: gridColor },
                            beginAtZero: true
                        }
                    }
                }
            }));

            // 3. Line Chart Lulusan Tiap Tahun
            const ctxLine = document.getElementById('lineLulusanChart').getContext('2d');
            const tahunLabels = {!! json_encode($yearlyGraduates->pluck('tahun')->map(fn($y) => 'Tahun ' . $y)->toArray()) !!};
            const yearlyTotalData = {!! json_encode($yearlyGraduates->pluck('total')->toArray()) !!};

            dashboardCharts.push(new Chart(ctxLine, {
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
            }));

            // Redraw semua chart dengan warna yang sesuai saat tema terang/gelap di-toggle,
            // karena Chart.js hanya membaca warna sekali saat instance dibuat.
            window.addEventListener('theme-changed', function (e) {
                isDark = !!e.detail.dark;
                textColor = isDark ? '#f8fafc' : '#1e293b';
                gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.06)';
                const borderContrast = isDark ? '#0f172a' : '#ffffff';

                dashboardCharts.forEach(function (chart) {
                    if (chart.options.scales?.x?.ticks) chart.options.scales.x.ticks.color = textColor;
                    if (chart.options.scales?.y?.ticks) chart.options.scales.y.ticks.color = textColor;
                    if (chart.options.scales?.y?.grid) chart.options.scales.y.grid.color = gridColor;
                    if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = textColor;
                    if (chart.config.type === 'line') chart.data.datasets[0].pointBorderColor = borderContrast;
                    chart.update();
                });
            });
        });
    </script>
</x-app-layout>

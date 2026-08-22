@props(['sidang' => 0, 'jurnal' => 0, 'total' => null, 'chartId' => 'jalurChart-' . uniqid()])

@php
    $total = $total ?? ($sidang + $jurnal);
@endphp

<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Infografis Jalur Tugas Akhir</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Sebaran mahasiswa berdasarkan jalur yang diambil</p>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-300 rounded-lg border border-indigo-200 dark:border-indigo-800">
            Total {{ $total }} Mhs
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
        <div class="sm:col-span-1 flex flex-col gap-3">
            <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Jalur Sidang</span>
                </div>
                <span class="text-sm font-extrabold text-indigo-700 dark:text-indigo-300">{{ $sidang }}</span>
            </div>
            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Jalur Jurnal</span>
                </div>
                <span class="text-sm font-extrabold text-emerald-700 dark:text-emerald-300">{{ $jurnal }}</span>
            </div>
        </div>
        <div class="sm:col-span-2 h-40 relative">
            <canvas id="{{ $chartId }}"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        function renderChart() {
            var el = document.getElementById('{{ $chartId }}');
            if (!el || typeof Chart === 'undefined') return;
            var isDark = document.documentElement.classList.contains('dark');
            new Chart(el.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Jalur Sidang', 'Jalur Jurnal'],
                    datasets: [{
                        data: [{{ $sidang }}, {{ $jurnal }}],
                        backgroundColor: ['#6366f1', '#10b981'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true }
                    }
                }
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderChart);
        } else {
            renderChart();
        }
    })();
</script>
@endpush

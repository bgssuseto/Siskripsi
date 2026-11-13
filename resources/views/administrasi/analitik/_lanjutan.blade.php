{{-- Analitik lanjutan (lintas periode) — data dari App\Services\AnalitikLanjutanService --}}
@php
    $kartu = 'bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm';
    $warnaMacet = [
        'amber'  => ['badge' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30', 'angka' => 'text-amber-600 dark:text-amber-300'],
        'sky'    => ['badge' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30', 'angka' => 'text-sky-600 dark:text-sky-300'],
        'rose'   => ['badge' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30', 'angka' => 'text-rose-600 dark:text-rose-300'],
        'violet' => ['badge' => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30', 'angka' => 'text-violet-600 dark:text-violet-300'],
    ];
    $tempuh = $waktuTempuh['tempuh'];
    $studi = $waktuTempuh['lama_studi'];
    $tunggu = $waktuTempuh['tunggu'];
    $totalMacet = collect($macet)->sum(fn ($k) => $k['mahasiswa']->count());
@endphp

<div class="pt-2">
    <h2 class="text-base font-extrabold text-slate-800 dark:text-slate-100">Analitik Lanjutan</h2>
    <p class="text-xs text-slate-500 dark:text-slate-400">Dihitung dari seluruh data (lintas periode), tidak terpengaruh filter di atas.</p>
</div>

{{-- ── Waktu tempuh & lama studi ── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    @foreach([
        ['judul' => 'Waktu Tempuh Sempro → Sidang', 'sub' => 'Dari lulus Sempro sampai lulus Sidang Skripsi', 'data' => $tempuh, 'rata' => $tempuh['rata_bulan'], 'median' => $tempuh['median_bulan'], 'satuan' => 'bulan'],
        ['judul' => 'Lama Studi (perkiraan)', 'sub' => 'Dari tahun angkatan di NIM (masuk September) sampai lulus Sidang', 'data' => $studi, 'rata' => $studi['rata_tahun'], 'median' => $studi['median_tahun'], 'satuan' => 'tahun'],
    ] as $blok)
        <div class="{{ $kartu }} p-5">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $blok['judul'] }}</h3>
            <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ $blok['sub'] }}</p>
            @if($blok['data']['jumlah'])
                <div class="flex items-end gap-6 mt-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rata-rata</p>
                        <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($blok['rata'], 1, ',', '.') }} <span class="text-sm font-semibold text-slate-500">{{ $blok['satuan'] }}</span></p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Median</p>
                        <p class="text-lg font-bold text-slate-700 dark:text-slate-200">{{ number_format($blok['median'], 1, ',', '.') }} <span class="text-xs font-semibold text-slate-500">{{ $blok['satuan'] }}</span></p>
                    </div>
                </div>
                <div class="mt-4 space-y-2">
                    @foreach($blok['data']['distribusi'] as $d)
                        <div>
                            <div class="flex justify-between text-[11px] text-slate-500 dark:text-slate-400"><span>{{ $d['label'] }}</span><span><b class="text-slate-700 dark:text-slate-200">{{ $d['jumlah'] }}</b> mhs ({{ $d['persen'] }}%)</span></div>
                            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div class="h-full bg-indigo-500 dark:bg-indigo-400 rounded-full" style="width: {{ $d['persen'] }}%"></div></div>
                        </div>
                    @endforeach
                </div>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-3">Dari {{ $blok['data']['jumlah'] }} mahasiswa lulus.</p>
            @else
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-6 text-center">Belum ada data kelulusan.</p>
            @endif
        </div>
    @endforeach

    <div class="{{ $kartu }} p-5">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Waktu Tunggu Daftar → Ujian</h3>
        <p class="text-[11px] text-slate-400 dark:text-slate-500">Ujian 12 bulan terakhir</p>
        <div class="mt-4 space-y-4">
            @foreach(['sempro' => 'Seminar Proposal', 'skripsi' => 'Sidang Skripsi'] as $k => $label)
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $label }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ $tunggu[$k]['jumlah'] }} ujian @if($tunggu[$k]['median'] !== null)· median {{ $tunggu[$k]['median'] }} hari @endif</p>
                    </div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $tunggu[$k]['rata'] ?? '–' }} <span class="text-xs font-semibold text-slate-500">hari</span></p>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Mahasiswa macet ── --}}
<div class="{{ $kartu }} overflow-hidden" x-data="{ buka: null }">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Mahasiswa Perlu Perhatian</h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Mahasiswa yang tertahan terlalu lama di satu tahap. Klik kategori untuk melihat daftarnya.</p>
        </div>
        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 shrink-0">{{ $totalMacet }} mahasiswa</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-5 divide-x divide-y md:divide-y-0 divide-slate-100 dark:divide-slate-800">
        @foreach($macet as $kunci => $k)
            <button type="button" @click="buka = buka === '{{ $kunci }}' ? null : '{{ $kunci }}'"
                    :class="buka === '{{ $kunci }}' ? 'bg-slate-50 dark:bg-slate-800/60' : ''"
                    class="text-left p-4 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                <p class="text-2xl font-bold {{ $warnaMacet[$k['warna']]['angka'] }}">{{ $k['mahasiswa']->count() }}</p>
                <p class="text-xs font-bold text-slate-700 dark:text-slate-200 mt-1">{{ $k['judul'] }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ $k['keterangan'] }}</p>
            </button>
        @endforeach
    </div>
    @foreach($macet as $kunci => $k)
        <div x-show="buka === '{{ $kunci }}'" x-cloak class="border-t border-slate-100 dark:border-slate-800">
            @if($k['mahasiswa']->isEmpty())
                <p class="px-5 py-6 text-center text-sm text-slate-400 dark:text-slate-500">Tidak ada mahasiswa di kategori ini.</p>
            @else
                <div class="max-h-[360px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 sticky top-0">
                            <tr><th class="text-left px-5 py-2">Mahasiswa</th><th class="text-left px-3 py-2">Tahap</th><th class="text-left px-3 py-2">Sejak</th><th class="text-right px-5 py-2">Lama</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($k['mahasiswa'] as $m)
                                <tr>
                                    <td class="px-5 py-2"><span class="font-semibold text-slate-700 dark:text-slate-200">{{ $m['nama'] }}</span> <span class="text-[11px] font-mono text-slate-400">{{ $m['nim'] }}</span></td>
                                    <td class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">{{ $m['tahap'] }}</td>
                                    <td class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $m['sejak']->locale('id')->translatedFormat('j M Y') }}</td>
                                    <td class="px-5 py-2 text-right"><span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-bold ring-1 ring-inset {{ $warnaMacet[$k['warna']]['badge'] }}">{{ $m['hari'] }} hari</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
</div>

{{-- ── Beban dosen per semester ── --}}
<div class="{{ $kartu }} overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Beban Dosen per Semester</h3>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $bebanSemester['periodes']->count() }} periode terakhir · <b>B</b> = membimbing (utama/pendamping), <b>U</b> = menguji (di luar mahasiswa bimbingannya sendiri)</p>
    </div>
    @if($bebanSemester['baris']->isEmpty())
        <p class="px-5 py-10 text-center text-sm text-slate-400 dark:text-slate-500">Belum ada data.</p>
    @else
        @php $maxSel = max(1, $bebanSemester['baris']->flatMap(fn ($r) => $r['per_periode']->pluck('total'))->max()); @endphp
        <div class="overflow-x-auto max-h-[480px] overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 sticky top-0 z-10">
                    <tr>
                        <th class="text-left px-5 py-2 min-w-[200px]">Dosen</th>
                        @foreach($bebanSemester['periodes'] as $p)
                            <th class="text-center px-3 py-2 whitespace-nowrap">{{ $p->nama_periode }}</th>
                        @endforeach
                        <th class="text-right px-5 py-2">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($bebanSemester['baris'] as $r)
                        <tr>
                            <td class="px-5 py-2 font-semibold text-slate-700 dark:text-slate-200">{{ $r['nama'] }}</td>
                            @foreach($bebanSemester['periodes'] as $p)
                                @php $sel = $r['per_periode'][$p->id]; $alpha = $sel['total'] ? round(0.08 + 0.42 * $sel['total'] / $maxSel, 2) : 0; @endphp
                                <td class="px-3 py-2 text-center whitespace-nowrap" @if($alpha) style="background: rgba(99,102,241,{{ $alpha }});" @endif>
                                    @if($sel['total'])
                                        <span class="font-bold text-slate-800 dark:text-slate-100">{{ $sel['total'] }}</span>
                                        <span class="block text-[10px] text-slate-600 dark:text-slate-300">B {{ $sel['bimbing'] }} · U {{ $sel['uji'] }}</span>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600">–</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-5 py-2 text-right font-bold text-slate-800 dark:text-slate-100">{{ $r['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

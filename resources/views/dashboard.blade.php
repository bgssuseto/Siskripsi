<x-app-layout title="Dashboard Utama">
    <x-slot:header>
        Dashboard Overview
    </x-slot:header>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    @php
        $user = Auth::user();
        $q = array_filter(['periode_id' => $selectedPeriode?->id, 'gelombang' => $selectedGelombang], fn ($v) => $v !== null);
        $cakupan = $selectedPeriode
            ? $selectedPeriode->nama_periode . ($selectedGelombang !== null ? ' · Gelombang ' . $selectedGelombang : ' · semua gelombang')
            : 'Semua periode';
        $jumlahTindakan = $tindakan->sum('jumlah');
        $subjudul = $tindakan->isNotEmpty()
            ? 'Ada ' . $tindakan->count() . ' hal yang perlu ditindaklanjuti. Data ditampilkan untuk ' . $cakupan . '.'
            : 'Semua beres untuk ' . $cakupan . '. Tidak ada berkas atau jadwal yang menunggu tindakan.';

        $warnaTindakan = [
            'amber'  => 'bg-amber-50 text-amber-600 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30',
            'sky'    => 'bg-sky-50 text-sky-600 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30',
            'rose'   => 'bg-rose-50 text-rose-600 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30',
            'violet' => 'bg-violet-50 text-violet-600 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30',
            'slate'  => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
        ];
        $jenisBadge = fn ($s) => match ($s->jenis_tugas_akhir) {
            'sempro' => ['Sempro', 'cal-badge-sempro'],
            'jurnal' => ['Jurnal', 'cal-badge-jurnal'],
            default  => ['Skripsi', 'cal-badge-skripsi'],
        };
        $namaPendek = fn ($d) => $d ? trim(explode(',', $d->nama_dosen)[0]) : null;
    @endphp

    {{-- 1. Kartu sambutan --}}
    <x-sambutan :nama="$user->name" :peran="$user->role_label" :subjudul="$subjudul"
                :agenda="$agendaSambutan" kosong="Belum ada ujian yang dijadwalkan dalam waktu dekat.">
        <x-slot:aksi>
            @if($bisa('pendaftaran.sempro'))
                <a href="{{ route('pendaftaran.sempro', $q) }}" class="ui-btn-primary"><x-icon name="shield" class="w-4 h-4" /> Verifikasi</a>
            @endif
            @if($bisa('jadwal-sempro.index'))
                <a href="{{ route('jadwal-sempro.index', $q) }}" class="ui-btn-secondary"><x-icon name="calendar" class="w-4 h-4" /> Penjadwalan Sempro</a>
            @endif
            @if($bisa('jadwal-ujian.index'))
                <a href="{{ route('jadwal-ujian.index', $q) }}" class="ui-btn-secondary"><x-icon name="academic" class="w-4 h-4" /> Penjadwalan Skripsi</a>
            @endif
            @if($bisa('administrasi.undangan.index'))
                <a href="{{ route('administrasi.undangan.index') }}" class="ui-btn-secondary"><x-icon name="printer" class="w-4 h-4" /> Cetak</a>
            @endif
        </x-slot:aksi>
    </x-sambutan>

    {{-- 2. Filter periode & gelombang --}}
    <form method="GET" action="{{ route('dashboard') }}"
          class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:flex-row sm:items-end dark:border-slate-800 dark:bg-slate-900">
        <div class="min-w-0 sm:w-64">
            <label for="f-periode" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Periode</label>
            <select id="f-periode" name="periode_id" onchange="this.form.gelombang && (this.form.gelombang.value=''); this.form.submit()"
                    class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                <option value="semua" @selected($periodeValue === 'semua')>Semua periode</option>
                @foreach($periodes as $p)
                    <option value="{{ $p->id }}" @selected($periodeValue === $p->id)>{{ $p->nama_periode }}{{ $p->aktif ? ' (aktif)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-0 sm:w-48">
            <label for="f-gelombang" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Gelombang</label>
            <select id="f-gelombang" name="gelombang" onchange="this.form.submit()" @disabled(!$selectedPeriode)
                    class="w-full rounded-xl border-slate-300 bg-white text-sm disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                <option value="">Semua gelombang</option>
                @foreach($gelombangOptions as $g)
                    <option value="{{ $g }}" @selected($selectedGelombang === $g)>Gelombang {{ $g }}</option>
                @endforeach
            </select>
        </div>
        <noscript><button type="submit" class="ui-btn-primary">Terapkan</button></noscript>
        <p class="text-xs text-slate-500 sm:ml-auto sm:pb-2.5 dark:text-slate-400">
            Menampilkan: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $cakupan }}</span>
        </p>
    </form>

    {{-- KPI --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        @php
            $kartu = [
                ['Pendaftar', $kpi['pendaftar'], 'mahasiswa', $kpi['sempro'] . ' sempro · ' . $kpi['skripsi'] . ' skripsi/jurnal', 'academic', 'text-indigo-600 bg-indigo-50 dark:bg-indigo-500/10 dark:text-indigo-300'],
                ['Berkas disetujui', $kpi['disetujui'], 'berkas', $kpi['menunggu'] . ' menunggu · ' . $kpi['ditolak'] . ' ditolak', 'shield', 'text-amber-600 bg-amber-50 dark:bg-amber-500/10 dark:text-amber-300'],
                ['Terjadwal', $kpi['terjadwal'], 'ujian', $kpi['belum_terjadwal'] . ' disetujui belum dijadwalkan', 'calendar', 'text-sky-600 bg-sky-50 dark:bg-sky-500/10 dark:text-sky-300'],
                ['Lulus skripsi/jurnal', $kpi['lulus_skripsi'], 'mahasiswa', $kpi['tidak_lulus'] . ' ujian tidak lulus', 'badge-check', 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10 dark:text-emerald-300'],
            ];
        @endphp
        @foreach($kartu as [$judul, $nilai, $satuan, $ket, $ikon, $warna])
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $judul }}</p>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $warna }}"><x-icon name="{{ $ikon }}" class="h-4.5 w-4.5" /></span>
                </div>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $nilai }} <span class="text-sm font-medium text-slate-400">{{ $satuan }}</span></p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $ket }}</p>
            </div>
        @endforeach
    </div>

    {{-- 3 & 4. Perlu tindakan + Agenda 7 hari --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-5">
        <section class="flex flex-col rounded-2xl border border-slate-200/80 bg-white shadow-sm lg:col-span-2 dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Perlu Tindakan</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Klik untuk langsung menuju halamannya</p>
                </div>
                @if($jumlahTindakan)
                    <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">{{ $jumlahTindakan }}</span>
                @endif
            </header>
            @if($tindakan->isEmpty())
                <div class="flex flex-1 flex-col items-center justify-center gap-2 px-5 py-10 text-center">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"><x-icon name="check-circle" class="h-5 w-5" /></span>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Tidak ada yang perlu ditindaklanjuti</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Berkas, jadwal, dan kesediaan dosen sudah beres.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($tindakan as $t)
                        <li>
                            @php $tag = $t['url'] ? 'a' : 'div'; @endphp
                            <{{ $tag }} @if($t['url']) href="{{ $t['url'] }}" @endif
                                class="group flex items-center gap-3 px-5 py-3 {{ $t['url'] ? 'transition hover:bg-slate-50 dark:hover:bg-slate-800/50' : '' }}">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset {{ $warnaTindakan[$t['warna']] }}"><x-icon name="{{ $t['ikon'] }}" class="h-4 w-4" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $t['judul'] }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $t['ket'] }}</span>
                                </span>
                                <span class="text-lg font-bold tabular-nums text-slate-900 dark:text-white">{{ $t['jumlah'] }}</span>
                                @if($t['url'])<x-icon name="arrow-right" class="h-4 w-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-indigo-500" />@endif
                            </{{ $tag }}>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="flex flex-col rounded-2xl border border-slate-200/80 bg-white shadow-sm lg:col-span-3 dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Agenda 7 Hari ke Depan</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $hariIni->locale('id')->translatedFormat('j M') }} – {{ $hariIni->copy()->addDays(6)->locale('id')->translatedFormat('j M Y') }} · {{ $agenda->count() }} ujian</p>
                </div>
                @if($bisa('jadwal.semua.index'))
                    <a href="{{ route('jadwal.semua.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Semua jadwal</a>
                @endif
            </header>
            @if($agendaPerTanggal->isEmpty())
                <div class="flex flex-1 flex-col items-center justify-center gap-2 px-5 py-10 text-center">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><x-icon name="calendar" class="h-5 w-5" /></span>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Belum ada ujian minggu ini</p>
                </div>
            @else
                <div class="max-h-[26rem] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                    @foreach($agendaPerTanggal as $tgl => $items)
                        @php
                            $c = \Carbon\Carbon::parse($tgl)->locale('id');
                            $selisih = (int) $hariIni->diffInDays($c, false);
                            $label = $selisih === 0 ? 'Hari ini' : ($selisih === 1 ? 'Besok' : $c->translatedFormat('l'));
                        @endphp
                        <div class="sticky top-0 z-[1] flex items-center gap-2 bg-slate-50/95 px-5 py-1.5 text-[11px] font-bold uppercase tracking-wider backdrop-blur dark:bg-slate-800/95 {{ $selisih === 0 ? 'text-rose-600 dark:text-rose-300' : 'text-slate-500 dark:text-slate-400' }}">
                            {{ $label }} <span class="font-medium normal-case tracking-normal text-slate-400">{{ $c->translatedFormat('j F Y') }}</span>
                        </div>
                        @foreach($items as $s)
                            @php
                                [$jl, $jk] = $jenisBadge($s);
                                $penguji = collect([$s->ketuaPenguji, $s->anggotaPenguji1, $s->anggotaPenguji2])->filter()->map($namaPendek)->unique()->implode(', ');
                            @endphp
                            <div class="flex gap-4 px-5 py-3">
                                <div class="w-16 shrink-0 pt-0.5 text-xs font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ $s->jam ?: '—' }}</div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="cal-badge cal-badge-xs {{ $jk }}">{{ $jl }}</span>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $s->nama_mahasiswa }}</p>
                                        <span class="font-mono text-[11px] text-slate-400">{{ $s->nim }}</span>
                                    </div>
                                    <p class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        <span class="inline-flex items-center gap-1"><x-icon name="map-pin" class="h-3.5 w-3.5" /> {{ $s->ruang?->kode_ruangan ? 'Ruang ' . $s->ruang->kode_ruangan : 'Ruang belum diatur' }}</span>
                                        @if($penguji)<span class="inline-flex min-w-0 items-center gap-1"><x-icon name="users" class="h-3.5 w-3.5 shrink-0" /> <span class="truncate">{{ $penguji }}</span></span>@endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
                @if($agenda->count() > 12)
                    <p class="border-t border-slate-100 px-5 py-2.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">+{{ $agenda->count() - 12 }} ujian lainnya minggu ini.</p>
                @endif
            @endif
        </section>
    </div>

    {{-- 6. Alur progres --}}
    <section class="mb-6 rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Alur Progres</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Jumlah pendaftaran di tiap tahap, dari mendaftar sampai lulus</p>
        </header>
        <div class="grid grid-cols-1 gap-6 p-5 md:grid-cols-2">
            @foreach($alur as $a)
                @php
                    $awal = max(1, $a['tahap'][0][1]);
                    $warnaBar = $a['key'] === 'sempro' ? 'bg-violet-500' : 'bg-indigo-500';
                @endphp
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $a['label'] }}</p>
                    <ol class="space-y-2.5">
                        @foreach($a['tahap'] as $i => [$nama, $jml])
                            @php $pct = round($jml / $awal * 100); @endphp
                            <li>
                                <div class="mb-1 flex items-baseline justify-between gap-2 text-xs">
                                    <span class="font-medium text-slate-700 dark:text-slate-200"><span class="mr-1 text-slate-400">{{ $i + 1 }}.</span>{{ $nama }}</span>
                                    <span class="tabular-nums text-slate-500 dark:text-slate-400"><span class="font-bold text-slate-900 dark:text-white">{{ $jml }}</span>@if($i > 0) · {{ $pct }}%@endif</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                    <div class="h-full rounded-full {{ $warnaBar }}" style="width: {{ $a['tahap'][0][1] ? $pct : 0 }}%; opacity: {{ 1 - $i * 0.12 }}"></div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 5. Beban dosen + grafik --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-5">
        <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm lg:col-span-3 dark:border-slate-800 dark:bg-slate-900" x-data="{ semua: false }">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Beban Dosen</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Membimbing & menguji · rata-rata {{ $rataBeban }} per dosen</p>
                </div>
                <div class="flex items-center gap-3 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-indigo-500"></span> Membimbing</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-400"></span> Menguji</span>
                </div>
            </header>
            @if($bebanDosen->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada dosen yang ditugaskan pada data ini.</p>
            @else
                <ul class="space-y-2.5 px-5 py-4">
                    @foreach($bebanDosen as $i => $d)
                        <li @if($i >= 10) x-show="semua" x-cloak @endif class="grid grid-cols-[minmax(0,11rem)_1fr_2rem] items-center gap-3 text-xs sm:grid-cols-[minmax(0,14rem)_1fr_2rem]">
                            <span class="truncate font-medium text-slate-700 dark:text-slate-200" title="{{ $d['nama'] }}">{{ $d['nama'] }}</span>
                            <span class="relative flex h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" title="{{ $d['bimbing'] }} membimbing, {{ $d['menguji'] }} menguji">
                                <span class="h-full bg-indigo-500" style="width: {{ $d['bimbing'] / $maxBeban * 100 }}%"></span>
                                <span class="h-full bg-amber-400" style="width: {{ $d['menguji'] / $maxBeban * 100 }}%"></span>
                                <span aria-hidden="true" class="absolute inset-y-0 w-px bg-slate-500/60 dark:bg-slate-300/60" style="left: {{ min(100, $rataBeban / $maxBeban * 100) }}%"></span>
                            </span>
                            <span class="text-right font-bold tabular-nums {{ $d['total'] > $rataBeban * 1.5 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">{{ $d['total'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-5 py-2.5 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    <span>Garis tipis = rata-rata. Angka merah = di atas 1,5× rata-rata.</span>
                    @if($bebanDosen->count() > 10)
                        <button type="button" @click="semua = !semua" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                x-text="semua ? 'Ringkas' : 'Tampilkan semua ({{ $bebanDosen->count() }})'">Tampilkan semua ({{ $bebanDosen->count() }})</button>
                    @endif
                </div>
            @endif
        </section>

        <div class="grid grid-cols-1 gap-6 lg:col-span-2">
            <section class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Jalur Tugas Akhir</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Reguler vs jurnal pada data terpilih</p>
                @if($jalur['sempro_belum'] > 0)
                    <p class="mt-2 text-[11px] font-medium text-amber-600 dark:text-amber-400">{{ $jalur['sempro_belum'] }} data sempro lama belum tercatat jalurnya.</p>
                @endif
                <div class="relative mt-3 min-h-[180px] flex-1"><canvas id="jalurChart"></canvas></div>
            </section>
            <section class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Lulusan per Tahun</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Mahasiswa lulus sidang skripsi/jurnal, semua periode</p>
                <div class="relative mt-3 min-h-[180px] flex-1"><canvas id="lulusanChart"></canvas></div>
            </section>
        </div>
    </div>

    {{-- Pendaftaran terbaru --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Pendaftaran Terbaru</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">6 pendaftaran terakhir pada data terpilih</p>
        </header>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-600 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-3">Tgl Daftar</th>
                        <th class="px-4 py-3">Mahasiswa</th>
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Pembimbing Utama</th>
                        <th class="px-4 py-3 text-center">Verifikasi</th>
                        <th class="px-4 py-3 text-center">Jadwal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-800 dark:text-slate-200">
                    @forelse($terbaru as $item)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $item->tanggal_pendaftaran ? $item->tanggal_pendaftaran->format('d/m/Y') : '-' }}</td>
                            <td class="min-w-[180px] px-4 py-3">
                                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $item->nama_mahasiswa }}</p>
                                <p class="font-mono text-[11px] text-slate-400">{{ $item->nim }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">@php [$jl, $jk] = $jenisBadge($item); @endphp<span class="cal-badge cal-badge-xs {{ $jk }}">{{ $item->jenis_label }}</span></td>
                            <td class="min-w-[150px] px-4 py-3 text-slate-600 dark:text-slate-300">{{ $item->pembimbingUtama?->nama_dosen ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">{!! $item->verifikasi_status_html !!}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">{!! $item->getJadwalStatusHtml() !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center font-medium text-slate-400 dark:text-slate-500">Belum ada pendaftaran pada data ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.Chart) return;
            const warna = (dark) => ({ text: dark ? '#cbd5e1' : '#475569', grid: dark ? 'rgba(255,255,255,0.08)' : 'rgba(15,23,42,0.06)', bg: dark ? '#0f172a' : '#ffffff' });
            let w = warna(document.documentElement.classList.contains('dark'));
            const sumbu = () => ({
                x: { ticks: { color: w.text, font: { weight: '600' } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: w.text, precision: 0 }, grid: { color: w.grid } },
            });

            const jalur = new Chart(document.getElementById('jalurChart'), {
                type: 'bar',
                data: {
                    labels: ['Sempro', 'Skripsi'],
                    datasets: [
                        { label: 'Reguler', data: [{{ $jalur['sempro_reguler'] }}, {{ $jalur['skripsi_reguler'] }}], backgroundColor: '#6366f1', borderRadius: 6, maxBarThickness: 40 },
                        { label: 'Jurnal', data: [{{ $jalur['sempro_jurnal'] }}, {{ $jalur['skripsi_jurnal'] }}], backgroundColor: '#10b981', borderRadius: 6, maxBarThickness: 40 },
                        @if($jalur['sempro_belum'] > 0)
                        { label: 'Belum ditentukan', data: [{{ $jalur['sempro_belum'] }}, 0], backgroundColor: '#94a3b8', borderRadius: 6, maxBarThickness: 40 },
                        @endif
                    ],
                },
                options: { responsive: true, maintainAspectRatio: false, scales: sumbu(),
                    plugins: { legend: { position: 'bottom', labels: { color: w.text, boxWidth: 10, font: { size: 11 } } } } },
            });

            const lulusan = new Chart(document.getElementById('lulusanChart'), {
                type: 'line',
                data: {
                    labels: @json($lulusanPerTahun->pluck('tahun')->map(fn ($t) => (string) $t)),
                    datasets: [{
                        label: 'Lulusan', data: @json($lulusanPerTahun->pluck('total')),
                        borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.12)', fill: true, tension: 0.35,
                        borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#10b981', pointBorderColor: w.bg, pointBorderWidth: 2,
                    }],
                },
                options: { responsive: true, maintainAspectRatio: false, scales: sumbu(),
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.raw + ' mahasiswa lulus' } } } },
            });

            // Chart.js membaca warna sekali saat dibuat; perbarui saat tema berganti.
            window.addEventListener('theme-changed', function (e) {
                w = warna(!!e.detail.dark);
                [jalur, lulusan].forEach(function (c) {
                    c.options.scales = sumbu();
                    if (c.options.plugins.legend.labels) c.options.plugins.legend.labels.color = w.text;
                    if (c.config.type === 'line') c.data.datasets[0].pointBorderColor = w.bg;
                    c.update();
                });
            });
        });
    </script>
</x-app-layout>

@props([
    'nama',
    'peran'     => null,   // label kecil, mis. "Dosen" / "Mahasiswa"
    'subjudul'  => null,
    'agenda'    => null,   // ['jenis' => 'Sempro', 'judul' => ..., 'sub' => ..., 'tanggal' => Carbon, 'jam' => ..., 'ruang' => ..., 'url' => ...]
    'kosong'    => 'Belum ada agenda ujian terdekat.',
])
@php
    // Kartu sambutan dashboard dosen & mahasiswa: kartu netral dengan aksen tipis,
    // avatar, salam sesuai waktu, tombol akses cepat (slot $aksi), dan panel agenda
    // ujian terdekat lengkap dengan hitung mundur.
    $sekarang = now()->timezone('Asia/Jakarta')->locale('id');
    $jam = (int) $sekarang->format('G');
    $salam = match (true) {
        $jam < 11 => 'Selamat pagi',
        $jam < 15 => 'Selamat siang',
        $jam < 18 => 'Selamat sore',
        default   => 'Selamat malam',
    };
    $namaDepan = trim(preg_replace('/^((dr|prof|ir|drs|dra)\.?\s+)+/i', '', explode(',', $nama)[0]));
    $inisial = collect(preg_split('/\s+/', $namaDepan))->filter()->take(2)
        ->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('');
    $foto = auth()->user()?->foto_profil;

    $hitung = null;
    if ($agenda && !empty($agenda['tanggal'])) {
        $selisih = (int) $sekarang->copy()->startOfDay()->diffInDays(\Carbon\Carbon::parse($agenda['tanggal'])->startOfDay(), false);
        $hitung = match (true) {
            $selisih <= 0  => ['Hari ini', 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30'],
            $selisih === 1 => ['Besok', 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30'],
            $selisih <= 7  => ['H-' . $selisih, 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30'],
            default        => ['H-' . $selisih, 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30'],
        };
    }
    $jenisKelas = null;
    if ($agenda && !empty($agenda['jenis'])) {
        $j = strtolower($agenda['jenis']);
        $jenisKelas = str_contains($j, 'proposal') || str_contains($j, 'sempro') ? 'cal-badge-sempro'
            : (str_contains($j, 'jurnal') ? 'cal-badge-jurnal' : 'cal-badge-skripsi');
    }
@endphp
<section {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 mb-6']) }}>
    {{-- aksen: garis tipis di atas, cahaya lembut & pola titik samar di kanan --}}
    <span aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-violet-500 to-sky-400"></span>
    <span aria-hidden="true" class="pointer-events-none absolute -top-28 -right-20 h-72 w-72 rounded-full bg-indigo-400/15 blur-3xl dark:bg-indigo-500/15"></span>
    <span aria-hidden="true" class="pointer-events-none absolute inset-0 text-slate-300 dark:text-slate-700"
          style="background-image: radial-gradient(currentColor 1px, transparent 1px); background-size: 18px 18px; -webkit-mask-image: linear-gradient(to left, rgba(0,0,0,.55), transparent 55%); mask-image: linear-gradient(to left, rgba(0,0,0,.55), transparent 55%);"></span>

    <div class="relative grid grid-cols-1 gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_23rem] lg:items-center lg:gap-10">
        <div class="min-w-0">
            <div class="flex items-center gap-4">
                @if($foto)
                    <img src="{{ asset($foto) }}" alt="" class="h-12 w-12 sm:h-14 sm:w-14 shrink-0 rounded-2xl object-cover ring-4 ring-indigo-50 dark:ring-indigo-500/10">
                @else
                    <span class="flex h-12 w-12 sm:h-14 sm:w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-base sm:text-lg font-bold text-white shadow-md shadow-indigo-500/20 ring-4 ring-indigo-50 dark:ring-indigo-500/10">{{ $inisial ?: '?' }}</span>
                @endif
                <div class="min-w-0">
                    <p class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-1.5"><x-icon name="calendar" class="h-3.5 w-3.5" /> {{ $sekarang->translatedFormat('l, j F Y') }}</span>
                        @if($peran)
                            <span aria-hidden="true" class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                            <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $peran }}</span>
                        @endif
                    </p>
                    <h1 class="mt-1 text-xl font-bold leading-tight tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                        {{ $salam }}, <span class="text-indigo-600 dark:text-indigo-400">{{ $namaDepan }}</span>
                    </h1>
                </div>
            </div>

            @if($subjudul)
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $subjudul }}</p>
            @endif

            @isset($aksi)
                <div class="mt-5 flex flex-wrap gap-2">{{ $aksi }}</div>
            @endisset
        </div>

        {{-- Panel agenda terdekat --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50/80 dark:border-slate-700/70 dark:bg-slate-800/40">
            <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-2.5 dark:border-slate-700/70">
                <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <x-icon name="clock" class="h-3.5 w-3.5" /> Agenda terdekat
                </p>
                @if($hitung)
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 ring-inset {{ $hitung[1] }}">{{ $hitung[0] }}</span>
                @endif
            </div>

            @if($agenda)
                <div class="flex items-start gap-3.5 p-4">
                    @if(!empty($agenda['tanggal']))
                        @php $tgl = \Carbon\Carbon::parse($agenda['tanggal'])->locale('id'); @endphp
                        <div class="w-14 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-white text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <div class="bg-rose-500 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{{ $tgl->translatedFormat('M') }}</div>
                            <div class="pt-1 text-xl font-bold leading-tight text-slate-900 dark:text-white">{{ $tgl->format('j') }}</div>
                            <div class="pb-1 text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ $tgl->translatedFormat('D') }}</div>
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        @if($jenisKelas)
                            <span class="cal-badge cal-badge-xs {{ $jenisKelas }}">{{ $agenda['jenis'] }}</span>
                        @endif
                        <p class="mt-1.5 break-words text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100">{{ $agenda['judul'] }}</p>
                        @if(!empty($agenda['sub']))
                            <p class="mt-0.5 line-clamp-2 text-xs leading-snug text-slate-500 dark:text-slate-400">{{ $agenda['sub'] }}</p>
                        @endif
                        <p class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs font-medium text-slate-600 dark:text-slate-300">
                            @if(!empty($agenda['jam']))<span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5 text-slate-400" /> {{ $agenda['jam'] }}</span>@endif
                            @if(!empty($agenda['ruang']))<span class="inline-flex items-center gap-1"><x-icon name="map-pin" class="h-3.5 w-3.5 text-slate-400" /> Ruang {{ $agenda['ruang'] }}</span>@endif
                        </p>
                    </div>
                </div>
                @if(!empty($agenda['url']))
                    <a href="{{ $agenda['url'] }}" class="flex items-center justify-between border-t border-slate-200 px-4 py-2.5 text-xs font-semibold text-indigo-600 transition hover:bg-white dark:border-slate-700/70 dark:text-indigo-400 dark:hover:bg-slate-800">
                        Lihat detail jadwal <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                    </a>
                @endif
            @else
                <div class="flex items-center gap-3 p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-500 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-indigo-400 dark:ring-slate-700"><x-icon name="calendar" class="h-5 w-5" /></span>
                    <p class="text-sm leading-snug text-slate-600 dark:text-slate-400">{{ $kosong }}</p>
                </div>
            @endif
        </div>
    </div>
</section>

@props([
    'nama',
    'peran'     => null,   // label kecil, mis. "Dosen" / "Mahasiswa"
    'subjudul'  => null,
    'agenda'    => null,   // ['jenis' => 'Sempro', 'judul' => ..., 'sub' => ..., 'tanggal' => Carbon, 'jam' => ..., 'ruang' => ..., 'url' => ...]
    'kosong'    => 'Belum ada agenda ujian terdekat.',
])
@php
    // Kartu sambutan dashboard dosen & mahasiswa: salam sesuai waktu, tanggal hari ini,
    // agenda ujian terdekat (dengan hitung mundur), dan tombol akses cepat (slot $aksi).
    $sekarang = now()->timezone('Asia/Jakarta')->locale('id');
    $jam = (int) $sekarang->format('G');
    $salam = match (true) {
        $jam < 11 => 'Selamat pagi',
        $jam < 15 => 'Selamat siang',
        $jam < 18 => 'Selamat sore',
        default   => 'Selamat malam',
    };
    $namaDepan = trim(preg_replace('/^(dr\.?|prof\.?|ir\.?)\s+/i', '', explode(',', $nama)[0]));

    $hitung = null;
    if ($agenda && !empty($agenda['tanggal'])) {
        $selisih = (int) $sekarang->copy()->startOfDay()->diffInDays(\Carbon\Carbon::parse($agenda['tanggal'])->startOfDay(), false);
        $hitung = match (true) {
            $selisih <= 0 => ['Hari ini', 'bg-rose-500'],
            $selisih === 1 => ['Besok', 'bg-amber-500'],
            default => ['H-' . $selisih, $selisih <= 7 ? 'bg-amber-500' : 'bg-emerald-500'],
        };
    }
@endphp
<section {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-3xl text-white shadow-xl shadow-indigo-900/10 mb-6']) }}
         style="background: linear-gradient(120deg, #4338ca 0%, #6d28d9 55%, #7c3aed 100%);">
    {{-- ornamen lembut --}}
    <span aria-hidden="true" class="pointer-events-none absolute -top-24 -right-16 w-72 h-72 rounded-full" style="background: radial-gradient(circle, rgba(255,255,255,.18), transparent 65%);"></span>
    <span aria-hidden="true" class="pointer-events-none absolute -bottom-28 left-1/3 w-80 h-80 rounded-full" style="background: radial-gradient(circle, rgba(56,189,248,.22), transparent 65%);"></span>
    <svg aria-hidden="true" class="pointer-events-none absolute inset-0 w-full h-full opacity-[.07]"><defs><pattern id="titik-sambutan" width="22" height="22" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.4" fill="#fff"/></pattern></defs><rect width="100%" height="100%" fill="url(#titik-sambutan)"/></svg>

    <div class="relative p-5 sm:p-7 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_22rem] gap-5 lg:gap-8 items-center">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full" style="background: rgba(255,255,255,.14);">
                    <x-icon name="calendar" class="w-3.5 h-3.5" /> {{ $sekarang->translatedFormat('l, j F Y') }}
                </span>
                @if($peran)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full" style="background: rgba(255,255,255,.14);">{{ $peran }}</span>
                @endif
            </div>
            <h1 class="mt-3 text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight">
                {{ $salam }}, <span class="whitespace-nowrap">{{ $namaDepan }}</span> 👋
            </h1>
            @if($subjudul)
                <p class="mt-1.5 text-sm leading-relaxed max-w-2xl" style="color: rgba(238,242,255,.85);">{{ $subjudul }}</p>
            @endif

            @isset($aksi)
                <div class="mt-5 flex flex-wrap gap-2">{{ $aksi }}</div>
            @endisset
        </div>

        {{-- Agenda terdekat --}}
        <div class="rounded-2xl p-4 sm:p-5 ring-1 backdrop-blur-sm" style="background: rgba(255,255,255,.12); --tw-ring-color: rgba(255,255,255,.2);">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.14em]" style="color: rgba(224,231,255,.9);">Agenda terdekat</p>
                @if($hitung)
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold text-white {{ $hitung[1] }}">{{ $hitung[0] }}</span>
                @endif
            </div>
            @if($agenda)
                <div class="mt-3 flex items-start gap-3">
                    @if(!empty($agenda['tanggal']))
                        @php $tgl = \Carbon\Carbon::parse($agenda['tanggal'])->locale('id'); @endphp
                        <div class="w-14 shrink-0 rounded-xl text-center overflow-hidden shadow-md" style="background:#fff;">
                            <div class="text-[10px] font-extrabold uppercase py-0.5 text-white bg-rose-500">{{ $tgl->translatedFormat('M') }}</div>
                            <div class="text-xl font-extrabold leading-tight pt-1" style="color:#1e1b4b;">{{ $tgl->format('j') }}</div>
                            <div class="text-[10px] font-bold pb-1" style="color:#64748b;">{{ $tgl->translatedFormat('D') }}</div>
                        </div>
                    @endif
                    <div class="min-w-0">
                        @if(!empty($agenda['jenis']))
                            <span class="inline-block px-1.5 py-px rounded text-[10px] font-extrabold uppercase tracking-wide" style="background: rgba(255,255,255,.18);">{{ $agenda['jenis'] }}</span>
                        @endif
                        <p class="mt-1 text-sm font-extrabold leading-snug break-words">{{ $agenda['judul'] }}</p>
                        @if(!empty($agenda['sub']))
                            <p class="text-[11px] leading-snug line-clamp-2" style="color: rgba(224,231,255,.8);">{{ $agenda['sub'] }}</p>
                        @endif
                        <p class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-[11px] font-semibold" style="color: rgba(238,242,255,.92);">
                            @if(!empty($agenda['jam']))<span class="inline-flex items-center gap-1"><x-icon name="clock" class="w-3.5 h-3.5" /> {{ $agenda['jam'] }}</span>@endif
                            @if(!empty($agenda['ruang']))<span class="inline-flex items-center gap-1"><x-icon name="map-pin" class="w-3.5 h-3.5" /> {{ $agenda['ruang'] }}</span>@endif
                        </p>
                    </div>
                </div>
                @if(!empty($agenda['url']))
                    <a href="{{ $agenda['url'] }}" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-white/90 hover:text-white">
                        Lihat detail <x-icon name="arrow-right" class="w-3.5 h-3.5" />
                    </a>
                @endif
            @else
                <div class="mt-3 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(255,255,255,.16);"><x-icon name="sparkles" class="w-5 h-5" /></span>
                    <p class="text-sm leading-snug" style="color: rgba(238,242,255,.9);">{{ $kosong }}</p>
                </div>
            @endif
        </div>
    </div>
</section>

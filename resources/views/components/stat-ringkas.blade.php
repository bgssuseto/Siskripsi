{{--
    Kartu ringkasan kecil (ikon + angka + label) — gaya yang sama dengan
    infografis menu Penjadwalan. Dipakai di Penjadwalan, Data, & Pendaftaran.

    <x-stat-ringkas icon="clock" warna="amber" :nilai="$n" label="Belum Plotting" />
    Tambahkan href (dan :aktif) bila kartu juga berfungsi sebagai filter.
--}}
@props([
    'icon',
    'label',
    'nilai',
    'warna' => 'slate',
    'href' => null,
    'aktif' => false,
    'kecil' => false, // nilai berupa teks (mis. nama periode), bukan angka
    'netral' => false, // angka berwarna gelap (untuk kartu "Total")
])

@php
    // Kelas ditulis lengkap (bukan dirangkai) supaya tetap terbaca Tailwind.
    $gaya = [
        'slate'   => ['ikon' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',              'angka' => 'text-slate-900 dark:text-slate-100',     'aktif' => 'border-indigo-500 ring-2 ring-indigo-500/20',  'hover' => 'hover:border-indigo-300'],
        'violet'  => ['ikon' => 'bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400',        'angka' => 'text-violet-700 dark:text-violet-300',   'aktif' => 'border-indigo-500 ring-2 ring-indigo-500/20',  'hover' => 'hover:border-indigo-300'],
        'blue'    => ['ikon' => 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400',                'angka' => 'text-blue-700 dark:text-blue-300',       'aktif' => 'border-indigo-500 ring-2 ring-indigo-500/20',  'hover' => 'hover:border-indigo-300'],
        'indigo'  => ['ikon' => 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400',        'angka' => 'text-indigo-700 dark:text-indigo-300',   'aktif' => 'border-indigo-500 ring-2 ring-indigo-500/20',  'hover' => 'hover:border-indigo-300'],
        'teal'    => ['ikon' => 'bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400',                'angka' => 'text-teal-700 dark:text-teal-300',       'aktif' => 'border-teal-500 ring-2 ring-teal-500/20',      'hover' => 'hover:border-teal-300'],
        'emerald' => ['ikon' => 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400',    'angka' => 'text-emerald-700 dark:text-emerald-300', 'aktif' => 'border-emerald-500 ring-2 ring-emerald-500/20', 'hover' => 'hover:border-emerald-300'],
        'amber'   => ['ikon' => 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400',            'angka' => 'text-amber-700 dark:text-amber-300',     'aktif' => 'border-amber-500 ring-2 ring-amber-500/20',    'hover' => 'hover:border-amber-300'],
        'rose'    => ['ikon' => 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400',                'angka' => 'text-rose-700 dark:text-rose-300',       'aktif' => 'border-rose-500 ring-2 ring-rose-500/20',      'hover' => 'hover:border-rose-300'],
    ][$warna] ?? null;

    $gaya ??= ['ikon' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400', 'angka' => 'text-slate-900 dark:text-slate-100', 'aktif' => 'border-indigo-500 ring-2 ring-indigo-500/20', 'hover' => 'hover:border-indigo-300'];

    $tag = $href ? 'a' : 'div';
    $kelas = 'bg-white dark:bg-slate-900 rounded-xl border shadow-sm p-3.5 flex items-center gap-2.5 transition-colors '
        . ($aktif ? $gaya['aktif'] : 'border-slate-200/80 dark:border-slate-800')
        . ($href && !$aktif ? ' ' . $gaya['hover'] : '');
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @if($aktif) aria-current="true" @endif @endif {{ $attributes->merge(['class' => $kelas]) }}>
    <div class="w-9 h-9 rounded-lg {{ $gaya['ikon'] }} flex items-center justify-center shrink-0 relative">
        <x-icon :name="$icon" class="w-4 h-4" />
        {{ $slot }}
    </div>
    <div class="min-w-0">
        @if($kecil)
            <p class="text-sm font-extrabold text-slate-900 dark:text-slate-100 leading-none truncate">{{ $nilai }}</p>
        @else
            <p class="text-lg font-extrabold {{ $netral ? 'text-slate-900 dark:text-slate-100' : $gaya['angka'] }} leading-none">{{ $nilai }}</p>
        @endif
        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-bold mt-0.5 truncate">{{ $label }}</p>
    </div>
</{{ $tag }}>

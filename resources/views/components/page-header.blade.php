@props([
    'title',
    'subtitle' => null,
    'icon'     => null,
    'accent'   => 'indigo', // indigo | violet | emerald | teal | sky | amber | rose | slate
])
@php
    // Header halaman yang seragam di seluruh sistem: kartu warna solid sesuai
    // aksen halaman, ikon dalam tile warna solid, judul + subjudul, aksi di
    // kanan (slot $actions), dan baris tambahan opsional di bawahnya (slot
    // default). Kelas ditulis literal supaya terdeteksi Tailwind.
    $tema = [
        'indigo'  => ['bg' => 'bg-indigo-50 dark:bg-indigo-500/10', 'border' => 'border-indigo-100 dark:border-indigo-500/20', 'tile' => 'bg-indigo-600'],
        'violet'  => ['bg' => 'bg-violet-50 dark:bg-violet-500/10', 'border' => 'border-violet-100 dark:border-violet-500/20', 'tile' => 'bg-violet-600'],
        'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-500/10', 'border' => 'border-emerald-100 dark:border-emerald-500/20', 'tile' => 'bg-emerald-600'],
        'teal'    => ['bg' => 'bg-teal-50 dark:bg-teal-500/10', 'border' => 'border-teal-100 dark:border-teal-500/20', 'tile' => 'bg-teal-600'],
        'sky'     => ['bg' => 'bg-sky-50 dark:bg-sky-500/10', 'border' => 'border-sky-100 dark:border-sky-500/20', 'tile' => 'bg-sky-600'],
        'amber'   => ['bg' => 'bg-amber-50 dark:bg-amber-500/10', 'border' => 'border-amber-100 dark:border-amber-500/20', 'tile' => 'bg-amber-500'],
        'rose'    => ['bg' => 'bg-rose-50 dark:bg-rose-500/10', 'border' => 'border-rose-100 dark:border-rose-500/20', 'tile' => 'bg-rose-600'],
        'slate'   => ['bg' => 'bg-slate-100 dark:bg-slate-700/30', 'border' => 'border-slate-200 dark:border-slate-700', 'tile' => 'bg-slate-700'],
    ][$accent] ?? null;
    $tema ??= [
        'bg' => 'bg-indigo-50 dark:bg-indigo-500/10', 'border' => 'border-indigo-100 dark:border-indigo-500/20', 'tile' => 'bg-indigo-600',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="relative overflow-hidden rounded-3xl border shadow-sm {{ $tema['bg'] }} {{ $tema['border'] }}">
        <div class="relative px-5 py-5 sm:px-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3.5 min-w-0">
                @if($icon)
                    <span class="w-12 h-12 shrink-0 rounded-2xl text-white shadow-sm flex items-center justify-center {{ $tema['tile'] }}">
                        <x-icon :name="$icon" class="w-6 h-6" />
                    </span>
                @endif
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100 leading-tight">{{ $title }}</h1>
                    @isset($description)
                        {{-- Slot description: subjudul yang memuat HTML (mis. <strong>) --}}
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400 leading-relaxed max-w-3xl [&_strong]:text-slate-800 dark:[&_strong]:text-slate-200">{{ $description }}</p>
                    @elseif($subtitle)
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400 leading-relaxed max-w-3xl">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2 sm:shrink-0">{{ $actions }}</div>
            @endisset
        </div>

        @if(trim($slot) !== '')
            <div class="relative px-5 pb-5 sm:px-6">{{ $slot }}</div>
        @endif
    </div>
</div>

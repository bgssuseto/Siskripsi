@props([
    'title',
    'subtitle' => null,
    'icon'     => null,
    'accent'   => 'indigo', // indigo | violet | emerald | teal | sky | amber | rose | slate
])
@php
    // Header halaman yang seragam di seluruh sistem (menggantikan banner gradient gelap):
    // ikon dalam tile berwarna lembut + judul + subjudul, aksi di kanan (slot $actions),
    // dan baris tambahan opsional di bawahnya (slot default, mis. badge/info singkat).
    // Kelas ditulis literal supaya terdeteksi Tailwind.
    $tile = [
        'indigo'  => 'bg-indigo-50 text-indigo-600 ring-indigo-100 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/20',
        'violet'  => 'bg-violet-50 text-violet-600 ring-violet-100 dark:bg-violet-500/15 dark:text-violet-300 dark:ring-violet-500/20',
        'emerald' => 'bg-emerald-50 text-emerald-600 ring-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/20',
        'teal'    => 'bg-teal-50 text-teal-600 ring-teal-100 dark:bg-teal-500/15 dark:text-teal-300 dark:ring-teal-500/20',
        'sky'     => 'bg-sky-50 text-sky-600 ring-sky-100 dark:bg-sky-500/15 dark:text-sky-300 dark:ring-sky-500/20',
        'amber'   => 'bg-amber-50 text-amber-600 ring-amber-100 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/20',
        'rose'    => 'bg-rose-50 text-rose-600 ring-rose-100 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-500/20',
        'slate'   => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
    ][$accent] ?? '';
@endphp
<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3.5 min-w-0">
            @if($icon)
                <span class="w-11 h-11 shrink-0 rounded-2xl ring-1 ring-inset flex items-center justify-center {{ $tile }}">
                    <x-icon :name="$icon" class="w-5 h-5" />
                </span>
            @endif
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100 leading-tight">{{ $title }}</h1>
                @isset($description)
                    {{-- Slot description: subjudul yang memuat HTML (mis. <strong>) --}}
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 leading-relaxed max-w-3xl [&_strong]:text-slate-700 dark:[&_strong]:text-slate-200">{{ $description }}</p>
                @elseif($subtitle)
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 leading-relaxed max-w-3xl">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2 sm:shrink-0">{{ $actions }}</div>
        @endisset
    </div>
    @if(trim($slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>

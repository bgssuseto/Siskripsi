@props([
    'title',
    'subtitle' => null,
    'icon'     => null,
    'accent'   => 'indigo', // indigo | violet | emerald | teal | sky | amber | rose | slate
])
@php
    // Header halaman yang seragam di seluruh sistem: kartu dengan sapuan warna lembut
    // sesuai aksen halaman, ikon dalam tile gradient, judul + subjudul, aksi di kanan
    // (slot $actions), dan baris tambahan opsional di bawahnya (slot default).
    // Kelas ditulis literal supaya terdeteksi Tailwind.
    $tema = [
        'indigo'  => ['wash' => 'from-indigo-50 via-white to-white dark:from-indigo-500/15 dark:via-slate-900/60 dark:to-slate-900/60', 'border' => 'border-indigo-100 dark:border-indigo-500/20', 'glow' => 'rgba(99,102,241,.16)', 'tile' => 'from-indigo-500 to-violet-600 shadow-indigo-500/30'],
        'violet'  => ['wash' => 'from-violet-50 via-white to-white dark:from-violet-500/15 dark:via-slate-900/60 dark:to-slate-900/60', 'border' => 'border-violet-100 dark:border-violet-500/20', 'glow' => 'rgba(139,92,246,.16)', 'tile' => 'from-violet-500 to-fuchsia-600 shadow-violet-500/30'],
        'emerald' => ['wash' => 'from-emerald-50 via-white to-white dark:from-emerald-500/15 dark:via-slate-900/60 dark:to-slate-900/60', 'border' => 'border-emerald-100 dark:border-emerald-500/20', 'glow' => 'rgba(16,185,129,.16)', 'tile' => 'from-emerald-500 to-teal-600 shadow-emerald-500/30'],
        'teal'    => ['wash' => 'from-teal-50 via-white to-white dark:from-teal-500/15 dark:via-slate-900/60 dark:to-slate-900/60', 'border' => 'border-teal-100 dark:border-teal-500/20', 'glow' => 'rgba(20,184,166,.16)', 'tile' => 'from-teal-500 to-cyan-600 shadow-teal-500/30'],
        'sky'     => ['wash' => 'from-sky-50 via-white to-white dark:from-sky-500/15 dark:via-slate-900/60 dark:to-slate-900/60', 'border' => 'border-sky-100 dark:border-sky-500/20', 'glow' => 'rgba(14,165,233,.16)', 'tile' => 'from-sky-500 to-blue-600 shadow-sky-500/30'],
        'amber'   => ['wash' => 'from-amber-50 via-white to-white dark:from-amber-500/15 dark:via-slate-900/60 dark:to-slate-900/60', 'border' => 'border-amber-100 dark:border-amber-500/20', 'glow' => 'rgba(245,158,11,.18)', 'tile' => 'from-amber-400 to-orange-500 shadow-amber-500/30'],
        'rose'    => ['wash' => 'from-rose-50 via-white to-white dark:from-rose-500/15 dark:via-slate-900/60 dark:to-slate-900/60', 'border' => 'border-rose-100 dark:border-rose-500/20', 'glow' => 'rgba(244,63,94,.14)', 'tile' => 'from-rose-500 to-pink-600 shadow-rose-500/30'],
        'slate'   => ['wash' => 'from-slate-100 via-white to-white dark:from-slate-700/30 dark:via-slate-900/60 dark:to-slate-900/60', 'border' => 'border-slate-200 dark:border-slate-700', 'glow' => 'rgba(100,116,139,.14)', 'tile' => 'from-slate-600 to-slate-800 shadow-slate-500/30'],
    ][$accent] ?? null;
    $tema ??= [
        'wash' => 'from-indigo-50 via-white to-white dark:from-indigo-500/15 dark:via-slate-900/60 dark:to-slate-900/60', 'border' => 'border-indigo-100 dark:border-indigo-500/20',
        'glow' => 'rgba(99,102,241,.16)', 'tile' => 'from-indigo-500 to-violet-600 shadow-indigo-500/30',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="relative overflow-hidden rounded-3xl border bg-gradient-to-r shadow-sm {{ $tema['wash'] }} {{ $tema['border'] }}">
        <span aria-hidden="true" class="pointer-events-none absolute inset-0" style="background: radial-gradient(420px 220px at 100% 0%, {{ $tema['glow'] }}, transparent 70%);"></span>

        <div class="relative px-5 py-5 sm:px-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3.5 min-w-0">
                @if($icon)
                    <span class="w-12 h-12 shrink-0 rounded-2xl bg-gradient-to-br text-white shadow-lg flex items-center justify-center {{ $tema['tile'] }}">
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

@props([
    'title' => 'Kalender Jadwal',
    'subtitle' => null,
    'legend' => ['sempro', 'skripsi', 'jurnal'],
    'conflict' => false,
])

{{--
    Bingkai kalender: judul, legenda badge, dan tombol "Perbesar" (layar penuh,
    tutup dengan Esc). Kalender di dalamnya didaftarkan lewat CalendarKit.register()
    supaya tingginya menyesuaikan saat diperbesar. Gaya: resources/css/calendar.css.
--}}
@php
    $legendLabels = ['sempro' => 'Seminar Proposal', 'skripsi' => 'Sidang Skripsi', 'jurnal' => 'Sidang Jurnal'];
@endphp
<div {{ $attributes->merge(['class' => 'cal-frame']) }}
     x-data="{
        expanded: false,
        toggle() {
            this.expanded = !this.expanded;
            document.documentElement.classList.toggle('cal-lock', this.expanded);
            this.$nextTick(() => window.dispatchEvent(new CustomEvent('calendar-frame:toggle', { detail: { frame: this.$root, expanded: this.expanded } })));
        }
     }"
     :class="expanded && 'cal-expanded'"
     @keydown.escape.window="expanded && toggle()">
    <div class="cal-frame-head">
        <div class="min-w-0">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span class="inline-flex w-7 h-7 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-500/15 text-indigo-600 dark:text-indigo-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
                {{ $title }}
            </h3>
            @if($subtitle)
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @foreach($legend as $key)
                <span class="cal-badge cal-badge-{{ $key }}">{{ $legendLabels[$key] ?? ucfirst($key) }}</span>
            @endforeach
            @if($conflict)
                <span class="cal-badge cal-badge-conflict">Bentrok</span>
            @endif
            <button type="button" class="cal-frame-btn" @click="toggle()" :title="expanded ? 'Kecilkan (Esc)' : 'Perbesar kalender'">
                <svg x-show="!expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"/></svg>
                <svg x-show="expanded" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5"/></svg>
                <span x-text="expanded ? 'Kecilkan' : 'Perbesar'">Perbesar</span>
            </button>
        </div>
    </div>
    <div class="cal-frame-body">
        {{ $slot }}
    </div>
</div>

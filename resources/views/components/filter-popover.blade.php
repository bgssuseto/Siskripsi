@props(['label' => 'Filter Lanjutan', 'submitVia' => null, 'active' => false])

<div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false" x-cloak>
    <button type="button" @click="open = !open"
            class="relative inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl border transition-all
                   {{ $active ? 'bg-indigo-50 dark:bg-indigo-500/10 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        {{ $label }}
        @if($active)
            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-rose-500 border-2 border-white dark:border-slate-950"></span>
        @endif
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute left-0 sm:left-auto sm:right-0 z-30 mt-2 w-72 max-w-[calc(100vw-2rem)] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xl p-4 space-y-3">
        {{ $slot }}
        <div class="flex justify-end gap-2 pt-1 border-t border-slate-100 dark:border-slate-800 mt-1">
            @if($submitVia)
                <button type="button" @click="{{ $submitVia }}($event.target.closest('form')); open = false"
                        class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-all">Terapkan</button>
            @else
                <button type="submit"
                        class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-all">Terapkan</button>
            @endif
        </div>
    </div>
</div>

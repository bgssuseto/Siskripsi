@props(['label' => 'Filter Lanjutan', 'submitVia' => null, 'active' => false])

{{--
    Panel "Filter Lanjutan" untuk datatable. Field (div berisi label + input) ditata dalam
    grid 1 / 2 / 3 kolom (HP / tablet / desktop) supaya panel melebar, bukan memanjang ke bawah.
--}}
<div class="relative inline-block"
     x-data="{
        open: false,
        geser: 0,
        // Geser panel ke kiri bila melewati tepi kanan layar (sisakan 16px di kedua sisi).
        posisikan() {
            this.geser = 0;
            this.$nextTick(() => {
                const tombol = this.$refs.tombol.getBoundingClientRect();
                const lebar = this.$refs.panel.offsetWidth;
                const vw = document.documentElement.clientWidth;
                let kiri = 0;
                if (tombol.left + lebar > vw - 16) kiri = vw - 16 - lebar - tombol.left;
                if (tombol.left + kiri < 16) kiri = 16 - tombol.left;
                this.geser = Math.round(kiri);
            });
        }
     }"
     @click.outside="open = false" @keydown.escape.window="open = false" @resize.window="open && posisikan()" x-cloak>
    <button type="button" x-ref="tombol" @click="open = !open; if (open) posisikan()" :aria-expanded="open.toString()"
            class="relative inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl border transition-all
                   {{ $active ? 'bg-indigo-50 dark:bg-indigo-500/10 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
        <x-icon name="filter" class="w-4 h-4" />
        {{ $label }}
        <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        @if($active)
            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-rose-500 border-2 border-white dark:border-slate-950"></span>
        @endif
    </button>

    {{-- Animasi buka/tutup ditangani smooth.js (elemen melayang: fade + geser halus). --}}
    <div x-show="open" x-ref="panel" :style="`left: ${geser}px`"
         class="absolute left-0 z-30 mt-2 w-[calc(100vw-2rem)] sm:w-[36rem] lg:w-[48rem] max-w-[calc(100vw-2rem)] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xl shadow-slate-900/10 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
            <p class="flex items-center gap-2 text-xs font-extrabold text-slate-800 dark:text-slate-100">
                <span class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-500/15 text-indigo-600 dark:text-indigo-300 flex items-center justify-center"><x-icon name="filter" class="w-3.5 h-3.5" /></span>
                {{ $label }}
            </p>
            <button type="button" @click="open = false" class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="Tutup">
                <x-icon name="x" class="w-4 h-4" />
            </button>
        </div>

        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-3 items-start [&>div]:min-w-0">
            {{ $slot }}
        </div>

        <div class="flex justify-end gap-2 px-4 py-3 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="open = false" class="ui-btn-secondary">Batal</button>
            @if($submitVia)
                <button type="button" @click="{{ $submitVia }}($event.target.closest('form')); open = false" class="ui-btn-primary">Terapkan</button>
            @else
                <button type="submit" class="ui-btn-primary">Terapkan</button>
            @endif
        </div>
    </div>
</div>

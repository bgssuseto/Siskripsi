@props(['terjadwal' => 0, 'belumPlotting' => 0])

<div class="grid grid-cols-2 gap-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5 flex items-center gap-3">
        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <x-icon name="check-circle" class="w-5 h-5" />
        </div>
        <div class="min-w-0">
            <p class="text-xl sm:text-2xl font-extrabold text-emerald-700 dark:text-emerald-300 leading-none">{{ $terjadwal }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1 truncate">Sudah Terjadwal</p>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5 flex items-center gap-3">
        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
            <x-icon name="clock" class="w-5 h-5" />
        </div>
        <div class="min-w-0">
            <p class="text-xl sm:text-2xl font-extrabold text-amber-700 dark:text-amber-300 leading-none">{{ $belumPlotting }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1 truncate">Belum Plotting</p>
        </div>
    </div>
</div>

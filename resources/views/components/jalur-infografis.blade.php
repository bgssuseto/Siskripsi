@props(['sidang' => 0, 'jurnal' => 0, 'total' => null])

@php
    $total = $total ?? ($sidang + $jurnal);
@endphp

<div class="grid grid-cols-3 gap-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5 flex items-center gap-3">
        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center text-lg shrink-0">
            👥
        </div>
        <div class="min-w-0">
            <p class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-slate-100 leading-none">{{ $total }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1 truncate">Total Mahasiswa</p>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5 flex items-center gap-3">
        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg shrink-0">
            🎓
        </div>
        <div class="min-w-0">
            <p class="text-xl sm:text-2xl font-extrabold text-indigo-700 dark:text-indigo-300 leading-none">{{ $sidang }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1 truncate">Jalur Sidang</p>
        </div>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5 flex items-center gap-3">
        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg shrink-0">
            📰
        </div>
        <div class="min-w-0">
            <p class="text-xl sm:text-2xl font-extrabold text-emerald-700 dark:text-emerald-300 leading-none">{{ $jurnal }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1 truncate">Jalur Jurnal</p>
        </div>
    </div>
</div>

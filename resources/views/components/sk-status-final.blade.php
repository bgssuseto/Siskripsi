@props(['status', 'periode'])
@php
    // Panel status SK satu periode: final / sementara + progres gelombang sidang skripsi.
    // $status dari \App\Services\SkPdfService::statusFinal(). Kelas ditulis literal (Tailwind).
    $final = $status['final'];
    $chip = function (array $g) {
        if ($g['terlaksana']) {
            return ['Terlaksana', 'check-circle', 'bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-500/15 dark:text-teal-200 dark:ring-teal-500/30'];
        }
        if ($g['ada'] && $g['ditutup']) {
            return ['Sidang berjalan', 'clock', 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-500/30'];
        }
        if ($g['ada']) {
            return ['Pendaftaran dibuka', 'clock', 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/15 dark:text-sky-200 dark:ring-sky-500/30'];
        }
        return ['Belum dibuka', 'lock', 'bg-slate-50 text-slate-500 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700'];
    };
@endphp
<div {{ $attributes->merge(['class' => 'rounded-2xl p-4 ring-1 ring-inset ' . ($final
        ? 'bg-teal-50/60 ring-teal-200 dark:bg-teal-500/10 dark:ring-teal-500/30'
        : 'bg-amber-50/60 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/30')]) }}>
    <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-6">
        <div class="flex items-start gap-3 min-w-0 lg:max-w-md">
            <span class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center text-white shadow-sm {{ $final ? 'bg-gradient-to-br from-teal-500 to-emerald-600' : 'bg-gradient-to-br from-amber-400 to-orange-500' }}">
                <x-icon :name="$final ? 'badge-check' : 'clock'" class="w-5 h-5" />
            </span>
            <div class="min-w-0">
                <p class="text-sm font-extrabold {{ $final ? 'text-teal-900 dark:text-teal-100' : 'text-amber-900 dark:text-amber-100' }}">
                    {{ $final ? 'SK Final' : 'SK Sementara' }} — {{ $periode->nama_periode }}
                </p>
                <p class="text-xs leading-relaxed {{ $final ? 'text-teal-800/80 dark:text-teal-200/80' : 'text-amber-800/90 dark:text-amber-200/80' }}">
                    @if($final && $status['semester_berganti'])
                        Semester sudah berganti — SK periode ini final (total seluruh gelombang).
                    @elseif($final)
                        Sidang skripsi gelombang 1–{{ count($status['gelombang']) }} sudah terlaksana semua. SK Pembimbing &amp; SK Penguji final (total gelombang 1–{{ count($status['gelombang']) }}).
                    @else
                        SK final = total gelombang 1, 2, dan 3, terbit otomatis setelah sidang skripsi gelombang {{ \App\Services\SkPdfService::GELOMBANG_TERAKHIR }} selesai. Sebelum itu hanya SK Pembimbing sementara yang tersedia.
                    @endif
                </p>
            </div>
        </div>

        <ul class="flex flex-wrap gap-2 lg:ml-auto">
            @foreach($status['gelombang'] as $g)
                @php [$label, $ikon, $warna] = $chip($g); @endphp
                <li class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-[11px] font-bold ring-1 ring-inset {{ $warna }}">
                    <x-icon :name="$ikon" class="w-3.5 h-3.5" />
                    <span>Gelombang {{ $g['no'] }}</span>
                    <span class="font-semibold opacity-80">· {{ $label }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>

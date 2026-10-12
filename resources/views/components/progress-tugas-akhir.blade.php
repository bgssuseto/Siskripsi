@props(['progress'])

@php
    $isSempro = $progress['track'] === 'sempro';
    $steps    = $progress['steps'];
    $current  = $steps[$progress['current_index']];
    $title    = $isSempro ? 'Progress Seminar Proposal (Sempro)' : 'Progress Sidang Skripsi';

    // Kelas ditulis literal (bukan interpolasi) supaya terdeteksi Tailwind.
    $accent = $isSempro
        ? [
            'header' => 'from-indigo-600 via-violet-600 to-indigo-700',
            'glow'   => 'bg-violet-400/30',
            'active' => 'bg-indigo-600 text-white ring-indigo-200 dark:ring-indigo-900/70',
            'ping'   => 'bg-indigo-400',
            'card'   => 'bg-indigo-50/80 dark:bg-indigo-500/10 border-indigo-200 dark:border-indigo-500/30',
            'text'   => 'text-indigo-600 dark:text-indigo-300',
            'bar'    => 'from-indigo-500 to-violet-500',
            'cta'    => 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-600/30',
        ]
        : [
            'header' => 'from-purple-600 via-fuchsia-600 to-purple-700',
            'glow'   => 'bg-fuchsia-400/30',
            'active' => 'bg-purple-600 text-white ring-purple-200 dark:ring-purple-900/70',
            'ping'   => 'bg-purple-400',
            'card'   => 'bg-purple-50/80 dark:bg-purple-500/10 border-purple-200 dark:border-purple-500/30',
            'text'   => 'text-purple-600 dark:text-purple-300',
            'bar'    => 'from-purple-500 to-fuchsia-500',
            'cta'    => 'bg-purple-600 hover:bg-purple-700 shadow-purple-600/30',
        ];

    $tones = [
        'success' => 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        'danger'  => 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        'warning' => 'bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'info'    => 'bg-sky-100 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800',
        'neutral' => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-600',
    ];

    if ($progress['failed']) {
        $headline = ['Perlu revisi', 'Berkas perlu direvisi'];
    } elseif ($progress['complete']) {
        $headline = ['Selesai', 'Seluruh tahap telah dilalui'];
    } elseif (!$progress['registered']) {
        // Tahap "Pendaftaran" sendiri sudah dinamis (lihat ProgressTugasAkhirService):
        // "Sedang Buka" (gelombang buka, tombol Daftar tersedia di bawah) atau
        // "Belum dibuka Pendaftaran". Badge & subjudul atas mengikutinya.
        $waveOpen = $current['status'] === 'active';
        $headline = $waveOpen
            ? [$current['detail'], 'Pendaftaran sedang dibuka — klik "Daftar sekarang" di bawah.']
            : [$current['detail'], 'Cek kembali saat gelombang pendaftaran dibuka.'];
    } else {
        $headline = ['Tahap ' . ($progress['current_index'] + 1) . ' dari ' . $progress['total'], 'Saat ini: ' . $current['label']];
    }

    $headlineDot = $progress['failed'] ? 'bg-rose-300' : ($progress['complete'] ? 'bg-emerald-300' : 'bg-white');
    $barColor    = $progress['failed'] ? 'from-rose-500 to-rose-400' : ($progress['complete'] ? 'from-emerald-500 to-teal-400' : $accent['bar']);

    // Cincin persentase (SVG): keliling lingkaran r=26.
    $circumference = 2 * M_PI * 26;
    $dashOffset    = $circumference * (1 - $progress['percent'] / 100);
@endphp

<section aria-label="{{ $title }}" class="relative overflow-hidden bg-white dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-700/80 rounded-3xl shadow-sm">

    {{-- ── Header bergradien ── --}}
    <div class="relative overflow-hidden bg-gradient-to-br {{ $accent['header'] }} px-5 py-5 sm:px-6 text-white">
        <div aria-hidden="true" class="absolute -right-10 -top-12 w-40 h-40 rounded-full blur-2xl {{ $accent['glow'] }}"></div>
        <div aria-hidden="true" class="absolute right-24 -bottom-16 w-32 h-32 rounded-full bg-white/10 blur-2xl"></div>

        <div class="relative flex items-center gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-10 h-10 shrink-0 rounded-2xl bg-white/15 border border-white/25 backdrop-blur flex items-center justify-center text-lg shadow-inner">
                        {{ $isSempro ? '📝' : '🎓' }}
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-extrabold tracking-tight leading-snug">{{ $title }}</h3>
                        @if(!$isSempro && $progress['registered'] && $progress['jenis_label'])
                            <span class="inline-flex mt-0.5 px-2 py-0.5 rounded-full bg-white/15 border border-white/25 text-[10px] font-extrabold">{{ $progress['jenis_label'] }}</span>
                        @endif
                    </div>
                </div>

                <div class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-black/15 border border-white/20 text-[11px] font-extrabold">
                    <span class="w-1.5 h-1.5 rounded-full {{ $headlineDot }} {{ !$progress['complete'] && !$progress['failed'] ? 'animate-pulse' : '' }}"></span>
                    {{ $headline[0] }}
                </div>
                <p class="mt-1.5 text-[11.5px] text-white/80 leading-snug">{{ $headline[1] }}</p>
            </div>

            {{-- Cincin persentase --}}
            <div class="relative shrink-0 w-[68px] h-[68px] sm:w-20 sm:h-20">
                <svg viewBox="0 0 64 64" class="w-full h-full -rotate-90" aria-hidden="true">
                    <circle cx="32" cy="32" r="26" fill="none" stroke="currentColor" stroke-width="6" class="text-white/20"/>
                    <circle cx="32" cy="32" r="26" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"
                            class="{{ $progress['failed'] ? 'text-rose-200' : ($progress['complete'] ? 'text-emerald-200' : 'text-white') }} transition-all duration-1000"
                            stroke-dasharray="{{ round($circumference, 2) }}" stroke-dashoffset="{{ round($dashOffset, 2) }}"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center leading-none">
                    <span class="text-base sm:text-lg font-black">{{ $progress['percent'] }}%</span>
                    <span class="text-[9px] font-bold text-white/75 mt-0.5">{{ $progress['done_count'] }}/{{ $progress['total'] }}</span>
                </div>
            </div>
        </div>

        {{-- Bar progress tipis di bawah header --}}
        <div class="relative mt-4 h-1.5 rounded-full bg-white/20 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress['percent'] }}" aria-label="Progress">
            <div class="h-full rounded-full bg-white/90 transition-all duration-1000" style="width: {{ $progress['percent'] }}%"></div>
        </div>
    </div>

    {{-- ── Stepper: timeline vertikal di mobile, horizontal mulai lg ── --}}
    <div class="px-4 py-5 sm:px-6 sm:py-6">
        <ol class="relative grid grid-cols-1 gap-y-3 lg:grid-cols-5 lg:gap-x-3 lg:gap-y-0">
            @foreach($steps as $step)
                @php
                    $st = $step['status'];
                    $isFocus = in_array($st, ['active', 'failed'], true);
                    $circle = match ($st) {
                        'done'   => 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-md shadow-emerald-500/30',
                        'active' => 'ring-4 shadow-lg ' . $accent['active'],
                        'failed' => 'bg-rose-500 text-white ring-4 ring-rose-200 dark:ring-rose-900/60 shadow-lg shadow-rose-500/30',
                        default  => 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700',
                    };
                    $labelColor = $st === 'pending' ? 'text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-100';
                    $detailColor = match ($st) {
                        'done'   => 'text-emerald-600 dark:text-emerald-400',
                        'active' => $accent['text'],
                        'failed' => 'text-rose-600 dark:text-rose-400',
                        default  => 'text-slate-400 dark:text-slate-500',
                    };
                    // Tahap yang sedang berjalan / bermasalah ditampilkan sebagai kartu
                    // yang disorot supaya langsung terlihat apa yang harus dilakukan.
                    $box = match (true) {
                        $st === 'failed' => 'bg-rose-50/80 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/30',
                        $isFocus         => $accent['card'],
                        default          => 'border-transparent',
                    };
                    $connector = $st === 'done' ? 'bg-gradient-to-b lg:bg-gradient-to-r from-emerald-400 to-emerald-300 dark:from-emerald-500/80 dark:to-emerald-600/60' : 'bg-slate-200 dark:bg-slate-700';
                @endphp
                <li class="relative" @if($isFocus) aria-current="step" @endif>

                    {{-- Garis penghubung ke tahap berikutnya --}}
                    @unless($loop->last)
                        <span aria-hidden="true" class="absolute left-[28px] top-12 -bottom-3 w-0.5 rounded-full lg:left-[calc(50%_+_22px)] lg:right-[calc(-50%_+_10px)] lg:top-[28px] lg:bottom-auto lg:h-0.5 lg:w-auto {{ $connector }}"></span>
                    @endunless

                    <div class="relative flex items-start gap-3.5 rounded-2xl border p-3 transition-all lg:flex-col lg:items-center lg:text-center lg:gap-2.5 lg:h-full {{ $box }}">
                        <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-extrabold {{ $circle }}">
                            @if($st === 'active')
                                <span aria-hidden="true" class="absolute inset-0 rounded-full animate-ping opacity-40 {{ $accent['ping'] }}"></span>
                            @endif
                            @if($st === 'done')
                                <svg class="relative w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @elseif($st === 'failed')
                                <svg class="relative w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            @else
                                <span class="relative">{{ $loop->iteration }}</span>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1 lg:flex-none lg:w-full">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 lg:justify-center">
                                <p class="text-[13px] lg:text-xs font-extrabold {{ $labelColor }}">{{ $step['label'] }}</p>
                                @if($step['badge'])
                                    <span class="inline-flex px-2 py-0.5 rounded-full border text-[10px] font-extrabold {{ $tones[$step['badge_tone']] ?? $tones['neutral'] }}">{{ $step['badge'] }}</span>
                                @endif
                            </div>
                            <p class="text-[11.5px] lg:text-[11px] font-semibold mt-0.5 {{ $detailColor }}">{{ $step['detail'] }}</p>

                            @if($step['note'])
                                <p class="text-[11px] lg:text-[10.5px] mt-1.5 text-slate-500 dark:text-slate-400 leading-snug break-words">
                                    @if($step['key'] === 'ujian')
                                        {{-- "Pukul … • Ruang …": tiap bagian satu baris supaya tidak terpotong di tengah kode ruang (mis. R-201) --}}
                                        @foreach(explode(' • ', $step['note']) as $part)
                                            <span class="block">{{ $part }}</span>
                                        @endforeach
                                    @else
                                        {{ $step['note'] }}
                                    @endif
                                </p>
                            @endif

                            @if($step['cta'])
                                @php
                                    $registrationUrl = $isSempro ? route('mahasiswa.sempro.index') : route('mahasiswa.skripsi.index');
                                    [$ctaUrl, $ctaLabel, $ctaColor, $ctaExternal] = match ($step['cta']) {
                                        'daftar'  => [$registrationUrl, 'Daftar sekarang →', $accent['cta'], false],
                                        'revisi'  => [$registrationUrl, 'Revisi pendaftaran →', 'bg-rose-600 hover:bg-rose-700 shadow-rose-600/30', false],
                                        'join_wa' => [route('mahasiswa.sidang.join-wa', $progress['sidang']), '💬 Join Grup WA', 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/30', true],
                                    };
                                @endphp
                                <a href="{{ $ctaUrl }}" @if($ctaExternal) target="_blank" rel="noopener noreferrer" @endif
                                   class="mt-2.5 flex w-full sm:inline-flex sm:w-auto items-center justify-center px-4 py-2.5 sm:py-2 rounded-xl text-xs font-extrabold text-white shadow-lg transition-all active:scale-[.98] whitespace-nowrap {{ $ctaColor }}">
                                    {{ $ctaLabel }}
                                </a>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

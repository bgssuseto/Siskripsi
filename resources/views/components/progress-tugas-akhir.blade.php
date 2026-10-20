@props(['progress'])

@php
    $isSempro = $progress['track'] === 'sempro';
    $steps    = $progress['steps'];
    $current  = $steps[$progress['current_index']];
    $title    = $isSempro ? 'Progress Seminar Proposal (Sempro)' : 'Progress Sidang Skripsi';

    // Kelas ditulis literal (bukan interpolasi) supaya terdeteksi Tailwind.
    // Desain netral & profesional: kartu putih, aksen warna hanya pada strip
    // atas, ikon, cincin/bar progress, dan tahap aktif. Sempro = indigo,
    // Skripsi = violet.
    $accent = $isSempro
        ? [
            'strip'  => 'bg-indigo-500',
            'tile'   => 'bg-indigo-50 text-indigo-600 ring-1 ring-inset ring-indigo-100 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/20',
            'ring'   => 'text-indigo-600 dark:text-indigo-400',
            'bar'    => 'bg-indigo-600 dark:bg-indigo-500',
            'pill'   => 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-200 dark:ring-indigo-500/30',
            'dot'    => 'bg-indigo-500',
            'active' => 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-300 ring-2 ring-indigo-600 dark:ring-indigo-400 shadow-[0_0_0_5px_rgba(99,102,241,0.14)]',
            'ping'   => 'bg-indigo-400',
            'card'   => 'bg-indigo-50/60 dark:bg-indigo-500/10 border-indigo-100 dark:border-indigo-500/20',
            'text'   => 'text-indigo-600 dark:text-indigo-300',
            'cta'    => 'bg-indigo-600 hover:bg-indigo-700',
        ]
        : [
            'strip'  => 'bg-violet-500',
            'tile'   => 'bg-violet-50 text-violet-600 ring-1 ring-inset ring-violet-100 dark:bg-violet-500/15 dark:text-violet-300 dark:ring-violet-500/20',
            'ring'   => 'text-violet-600 dark:text-violet-400',
            'bar'    => 'bg-violet-600 dark:bg-violet-500',
            'pill'   => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/15 dark:text-violet-200 dark:ring-violet-500/30',
            'dot'    => 'bg-violet-500',
            'active' => 'bg-white dark:bg-slate-900 text-violet-600 dark:text-violet-300 ring-2 ring-violet-600 dark:ring-violet-400 shadow-[0_0_0_5px_rgba(139,92,246,0.14)]',
            'ping'   => 'bg-violet-400',
            'card'   => 'bg-violet-50/60 dark:bg-violet-500/10 border-violet-100 dark:border-violet-500/20',
            'text'   => 'text-violet-600 dark:text-violet-300',
            'cta'    => 'bg-violet-600 hover:bg-violet-700',
        ];

    $tones = [
        'success' => 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        'danger'  => 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        'warning' => 'bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'info'    => 'bg-sky-100 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800',
        'neutral' => 'bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-200 border-indigo-200 dark:border-indigo-500/30',
    ];

    $kelulusan = collect($steps)->firstWhere('key', 'kelulusan');

    if (($kelulusan['status'] ?? null) === 'failed') {
        $headline = ['Tidak Lulus', 'Hasil ujian dinyatakan tidak lulus — silakan hubungi koordinator.'];
    } elseif ($progress['failed']) {
        $headline = ['Perlu revisi', 'Berkas perlu direvisi'];
    } elseif ($progress['complete']) {
        $headline = ['Lulus', 'Selamat! Ujian Anda dinyatakan lulus.'];
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

    // Pill status di header mengikuti keadaan: gagal (rose), selesai (emerald), atau aksen track.
    [$headlinePill, $headlineDot] = match (true) {
        (bool) $progress['failed']   => ['bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-200 dark:ring-rose-500/30', 'bg-rose-500'],
        (bool) $progress['complete'] => ['bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-200 dark:ring-emerald-500/30', 'bg-emerald-500'],
        default                      => [$accent['pill'], $accent['dot']],
    };
    $ringColor = $progress['failed'] ? 'text-rose-500' : ($progress['complete'] ? 'text-emerald-500' : $accent['ring']);
    $barColor  = $progress['failed'] ? 'bg-rose-500' : ($progress['complete'] ? 'bg-emerald-500' : $accent['bar']);

    // Cincin persentase (SVG): keliling lingkaran r=26.
    $circumference = 2 * M_PI * 26;
    $dashOffset    = $circumference * (1 - $progress['percent'] / 100);
@endphp

<section aria-label="{{ $title }}" class="relative overflow-hidden bg-white dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-700/80 rounded-3xl shadow-sm">
    <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 {{ $accent['strip'] }}"></span>

    {{-- ── Header ── --}}
    <div class="px-5 pt-6 pb-5 sm:px-6">
        <div class="flex items-start gap-4">
            <span class="w-11 h-11 shrink-0 rounded-2xl flex items-center justify-center {{ $accent['tile'] }}">
                @if($isSempro)
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0118.5 16.5C16.5 18 14.36 19 12 20.055 9.64 19 7.5 18 5.5 16.5a12.083 12.083 0 01.34-5.922L12 14z"/></svg>
                @endif
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <h3 class="text-[15px] sm:text-base font-extrabold tracking-tight text-slate-900 dark:text-slate-100 leading-snug">{{ $title }}</h3>
                    @if(!$isSempro && $progress['registered'] && $progress['jenis_label'])
                        <span class="inline-flex px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-200 text-[10px] font-bold">{{ $progress['jenis_label'] }}</span>
                    @endif
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full ring-1 ring-inset text-[11px] font-bold {{ $headlinePill }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $headlineDot }} {{ !$progress['complete'] && !$progress['failed'] ? 'animate-pulse' : '' }}"></span>
                        {{ $headline[0] }}
                    </span>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-snug">{{ $headline[1] }}</p>
                </div>
            </div>

            {{-- Cincin persentase --}}
            <div class="relative shrink-0 w-14 h-14 sm:w-16 sm:h-16">
                <svg viewBox="0 0 64 64" class="w-full h-full -rotate-90" aria-hidden="true">
                    <circle cx="32" cy="32" r="26" fill="none" stroke="currentColor" stroke-width="6" class="text-slate-100 dark:text-slate-800"/>
                    <circle cx="32" cy="32" r="26" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"
                            class="{{ $ringColor }} transition-all duration-1000"
                            stroke-dasharray="{{ round($circumference, 2) }}" stroke-dashoffset="{{ round($dashOffset, 2) }}"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center leading-none">
                    <span class="{{ $progress['percent'] >= 100 ? 'text-xs sm:text-[13px]' : 'text-sm sm:text-[15px]' }} font-black tracking-tight text-slate-900 dark:text-slate-100">{{ $progress['percent'] }}%</span>
                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 mt-0.5">{{ $progress['done_count'] }}/{{ $progress['total'] }}</span>
                </div>
            </div>
        </div>

        {{-- Bar progress --}}
        <div class="mt-5 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress['percent'] }}" aria-label="Progress">
            <div class="h-full rounded-full {{ $barColor }} transition-all duration-1000" style="width: {{ $progress['percent'] }}%"></div>
        </div>
    </div>

    {{-- ── Stepper: timeline vertikal di mobile, horizontal mulai lg ── --}}
    <div class="px-4 pb-5 pt-4 sm:px-6 sm:pb-6 border-t border-slate-100 dark:border-slate-800">
        <ol class="relative grid grid-cols-1 gap-y-3 lg:grid-cols-6 lg:gap-x-3 lg:gap-y-0">
            @foreach($steps as $step)
                @php
                    $st = $step['status'];
                    $isFocus = in_array($st, ['active', 'failed'], true);
                    $circle = match ($st) {
                        'done'   => 'bg-emerald-500 text-white shadow-sm',
                        'active' => $accent['active'],
                        'failed' => 'bg-rose-500 text-white shadow-[0_0_0_5px_rgba(244,63,94,0.14)]',
                        default  => 'bg-white dark:bg-slate-900 text-slate-400 dark:text-slate-500 ring-1 ring-slate-200 dark:ring-slate-700',
                    };
                    $labelColor = $st === 'pending' ? 'text-slate-500 dark:text-slate-400' : 'text-slate-900 dark:text-slate-100';
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
                    $connector = $st === 'done' ? 'bg-emerald-300 dark:bg-emerald-600/60' : 'bg-slate-200 dark:bg-slate-700';
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
                                        'revisi'  => [$registrationUrl, 'Revisi pendaftaran →', 'bg-rose-600 hover:bg-rose-700', false],
                                        'join_wa' => [route('mahasiswa.sidang.join-wa', $progress['sidang']), '💬 Join Grup WA', 'bg-emerald-600 hover:bg-emerald-700', true],
                                    };
                                @endphp
                                <a href="{{ $ctaUrl }}" @if($ctaExternal) target="_blank" rel="noopener noreferrer" @endif
                                   class="mt-3 flex w-full sm:inline-flex sm:w-auto items-center justify-center px-4 py-2.5 sm:py-2 rounded-xl text-xs font-bold text-white shadow-sm transition-all active:scale-[.98] whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-400 {{ $ctaColor }}">
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

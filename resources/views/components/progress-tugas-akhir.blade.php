@props(['progress'])

@php
    $isSempro = $progress['track'] === 'sempro';
    $steps    = $progress['steps'];
    $current  = $steps[$progress['current_index']];
    $title    = $isSempro ? 'Progress Seminar Proposal (Sempro)' : 'Progress Sidang Skripsi';

    // Kelas ditulis literal (bukan interpolasi) supaya terdeteksi Tailwind.
    $accent = $isSempro
        ? [
            'icon'   => 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800',
            'active' => 'bg-indigo-600 ring-indigo-200 dark:ring-indigo-900/60',
            'text'   => 'text-indigo-600 dark:text-indigo-400',
            'bar'    => 'bg-indigo-500',
            'cta'    => 'bg-indigo-600 hover:bg-indigo-700',
        ]
        : [
            'icon'   => 'bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-800',
            'active' => 'bg-purple-600 ring-purple-200 dark:ring-purple-900/60',
            'text'   => 'text-purple-600 dark:text-purple-400',
            'bar'    => 'bg-purple-500',
            'cta'    => 'bg-purple-600 hover:bg-purple-700',
        ];

    $tones = [
        'success' => 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        'danger'  => 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        'warning' => 'bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'info'    => 'bg-sky-100 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800',
        'neutral' => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-600',
    ];

    if ($progress['failed']) {
        $headline = ['Perlu revisi', $tones['danger'], 'Berkas perlu direvisi'];
    } elseif ($progress['complete']) {
        $headline = ['Selesai', $tones['success'], 'Seluruh tahap telah dilalui'];
    } elseif (!$progress['registered']) {
        $headline = ['Belum mendaftar', $tones['neutral'], 'Belum ada pendaftaran'];
    } else {
        $headline = ['Tahap ' . ($progress['current_index'] + 1) . ' dari ' . $progress['total'], $tones['info'], 'Saat ini: ' . $current['label']];
    }

    $barColor = $progress['failed'] ? 'bg-rose-500' : ($progress['complete'] ? 'bg-emerald-500' : $accent['bar']);
@endphp

<section aria-label="{{ $title }}" class="bg-white dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 rounded-2xl shadow-sm p-5 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 shrink-0 rounded-xl border flex items-center justify-center text-lg {{ $accent['icon'] }}">
                {{ $isSempro ? '📝' : '🎓' }}
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ $title }}</h3>
                    @if(!$isSempro && $progress['registered'] && $progress['jenis_label'])
                        <span class="px-2 py-0.5 rounded-full border text-[10px] font-extrabold {{ $tones['neutral'] }}">{{ $progress['jenis_label'] }}</span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $headline[2] }}</p>
            </div>
        </div>
        <span class="px-3 py-1 rounded-full border text-[11px] font-extrabold whitespace-nowrap {{ $headline[1] }}">{{ $headline[0] }}</span>
    </div>

    {{-- Progress bar --}}
    <div class="mb-6">
        <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 dark:text-slate-500 mb-1">
            <span>Progress</span>
            <span>{{ $progress['percent'] }}%</span>
        </div>
        <div class="h-1.5 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-700 {{ $barColor }}" style="width: {{ $progress['percent'] }}%"></div>
        </div>
    </div>

    {{-- Stepper: vertikal di layar kecil, horizontal mulai lg --}}
    <ol class="grid grid-cols-1 gap-y-5 lg:grid-cols-5 lg:gap-x-2">
        @foreach($steps as $step)
            @php
                $st = $step['status'];
                $circle = match ($st) {
                    'done'   => 'bg-emerald-500 text-white',
                    'active' => 'text-white ring-4 ' . $accent['active'],
                    'failed' => 'bg-rose-500 text-white ring-4 ring-rose-200 dark:ring-rose-900/50',
                    default  => 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500',
                };
                $labelColor = $st === 'pending' ? 'text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-100';
                $detailColor = match ($st) {
                    'done'   => 'text-emerald-600 dark:text-emerald-400',
                    'active' => $accent['text'],
                    'failed' => 'text-rose-600 dark:text-rose-400',
                    default  => 'text-slate-400 dark:text-slate-500',
                };
            @endphp
            <li class="relative flex items-start gap-3 lg:flex-col lg:items-center lg:gap-2 lg:text-center" @if($st === 'active' || $st === 'failed') aria-current="step" @endif>

                {{-- Garis penghubung ke tahap berikutnya --}}
                @unless($loop->last)
                    <span aria-hidden="true" class="absolute left-[15px] top-8 -bottom-5 w-0.5 lg:left-1/2 lg:top-[15px] lg:bottom-auto lg:h-0.5 lg:w-full {{ $st === 'done' ? 'bg-emerald-400 dark:bg-emerald-500/70' : 'bg-slate-200 dark:bg-slate-700' }}"></span>
                @endunless

                <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-extrabold {{ $circle }}">
                    @if($st === 'done')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @elseif($st === 'failed')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </span>

                <div class="min-w-0 pb-1 lg:pb-0 lg:px-1">
                    <p class="text-xs font-extrabold {{ $labelColor }}">{{ $step['label'] }}</p>
                    <p class="text-[11px] font-semibold mt-0.5 {{ $detailColor }}">{{ $step['detail'] }}</p>

                    @if($step['badge'])
                        <span class="inline-flex mt-1.5 px-2 py-0.5 rounded-full border text-[10px] font-extrabold {{ $tones[$step['badge_tone']] ?? $tones['neutral'] }}">{{ $step['badge'] }}</span>
                    @endif

                    @if($step['note'])
                        <p class="text-[10.5px] mt-1 text-slate-500 dark:text-slate-400 leading-snug break-words">
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
                           class="mt-2 inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] font-extrabold text-white shadow-sm transition-all whitespace-nowrap {{ $ctaColor }}">
                            {{ $ctaLabel }}
                        </a>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</section>

<x-app-layout title="Undangan Menguji">
    <x-slot:header>Undangan Menguji</x-slot:header>

    @php
        $infoJenis = [
            'sempro'  => ['label' => 'Seminar Proposal', 'singkat' => 'Sempro', 'chip' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30', 'ikon' => 'document', 'tile' => 'amber', 'garis' => 'from-amber-400 to-orange-500'],
            'skripsi' => ['label' => 'Sidang Skripsi', 'singkat' => 'Skripsi', 'chip' => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30', 'ikon' => 'academic', 'tile' => 'violet', 'garis' => 'from-violet-500 to-fuchsia-500'],
        ];
        $rentang = function ($it) {
            if (!$it['mulai']) return 'Jadwal belum ditentukan';
            $a = $it['mulai']->locale('id'); $b = $it['selesai']->locale('id');
            return $a->isSameDay($b) ? $a->translatedFormat('l, j F Y') : $a->translatedFormat('j M') . ' – ' . $b->translatedFormat('j M Y');
        };
        $labelGel = fn ($it) => $it['gelombang'] === null ? 'Tanpa gelombang' : 'Gelombang ' . $it['gelombang'];

        // Ringkasan atas
        $hariIni = now()->timezone('Asia/Jakarta')->startOfDay();
        $ujianMendatang = $berjalan->flatMap(fn ($it) => $it['sidangs'])
            ->filter(fn ($s) => $s->tanggal && \Carbon\Carbon::parse($s->tanggal)->gte($hariIni))
            ->sortBy(fn ($s) => \Carbon\Carbon::parse($s->tanggal)->format('Ymd') . ($s->jam ?? ''));
        $berikutnya = $ujianMendatang->first();
        $jumlahArsip = $arsip->sum(fn ($g) => $g['items']->count());
    @endphp

    <div x-data="{ tab: '{{ $berjalan->isEmpty() && $arsip->isNotEmpty() ? 'arsip' : 'berjalan' }}', jenis: '', semester: '' }" class="space-y-6">

        <x-page-header title="Undangan Menguji" icon="mail" accent="indigo">
            <x-slot:description>Undangan menjadi dewan penguji untuk Anda, lengkap dengan rincian jadwal per mahasiswa. Undangan gelombang yang sedang berjalan tampil di <strong>Berjalan</strong>, lalu otomatis pindah ke <strong>Arsip</strong> setelah gelombangnya lewat.</x-slot:description>
        </x-page-header>

        @if(!$dosen)
            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-sm text-amber-800 dark:text-amber-200">
                Akun Anda belum terhubung dengan data Master Dosen, sehingga undangan belum dapat ditampilkan. Hubungi Super Admin.
            </div>
        @else
            {{-- Ringkasan --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-700 p-4 shadow-sm flex items-center gap-3">
                    <span class="stat-icon blue w-11 h-11 rounded-xl flex items-center justify-center shrink-0"><x-icon name="users" class="w-5 h-5" /></span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ujian akan datang</p>
                        <p class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 leading-tight">{{ $ujianMendatang->count() }} <span class="text-xs font-semibold text-slate-400">mahasiswa</span></p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-700 p-4 shadow-sm flex items-center gap-3">
                    <span class="stat-icon green w-11 h-11 rounded-xl flex items-center justify-center shrink-0"><x-icon name="calendar" class="w-5 h-5" /></span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ujian berikutnya</p>
                        @if($berikutnya)
                            <p class="text-sm font-extrabold text-slate-900 dark:text-slate-100 truncate">{{ \Carbon\Carbon::parse($berikutnya->tanggal)->locale('id')->translatedFormat('l, j M') }}{{ $berikutnya->jam ? ' · ' . \Illuminate\Support\Str::before($berikutnya->jam, ' ') : '' }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $berikutnya->nama_mahasiswa }} · {{ $berikutnya->ruang?->kode_ruangan ?? 'Ruang belum ditentukan' }}</p>
                        @else
                            <p class="text-sm font-bold text-slate-400">Tidak ada jadwal mendatang</p>
                        @endif
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-700 p-4 shadow-sm flex items-center gap-3">
                    <span class="stat-icon violet w-11 h-11 rounded-xl flex items-center justify-center shrink-0"><x-icon name="folder" class="w-5 h-5" /></span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Arsip</p>
                        <p class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 leading-tight">{{ $jumlahArsip }} <span class="text-xs font-semibold text-slate-400">undangan · {{ $arsip->count() }} semester</span></p>
                    </div>
                </div>
            </div>

            {{-- Tab & filter --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="inline-flex p-1 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                    @foreach(['berjalan' => ['Berjalan', 'lightning', $berjalan->count()], 'arsip' => ['Arsip', 'folder', $jumlahArsip]] as $k => [$lbl, $ik, $n])
                        <button type="button" @click="tab = '{{ $k }}'" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all"
                                :class="tab === '{{ $k }}' ? 'bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                            <x-icon :name="$ik" class="w-4 h-4" /> {{ $lbl }}
                            <span class="min-w-[1.25rem] h-5 px-1 rounded-full text-[10px] flex items-center justify-center" :class="tab === '{{ $k }}' ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">{{ $n }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="inline-flex p-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-[11px] font-bold self-start sm:self-auto">
                    @foreach(['' => 'Semua', 'sempro' => 'Sempro', 'skripsi' => 'Skripsi'] as $k => $v)
                        <button type="button" @click="jenis = '{{ $k }}'" class="px-3 py-1.5 rounded-lg transition-colors"
                                :class="jenis === '{{ $k }}' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'">{{ $v }}</button>
                    @endforeach
                </div>
            </div>

            {{-- ═════════ BERJALAN ═════════ --}}
            <section x-show="tab === 'berjalan'" class="space-y-5">
                @forelse($berjalan as $it)
                    @php $j = $infoJenis[$it['jenis']]; @endphp
                    <article x-show="!jenis || jenis === '{{ $it['jenis'] }}'" x-data="{ rinci: {{ $it['jumlah'] <= 8 ? 'true' : 'false' }} }"
                             class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm">
                        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $j['garis'] }}"></span>

                        <header class="p-5 sm:p-6 flex flex-col lg:flex-row lg:items-center gap-4">
                            <div class="flex items-start gap-4 min-w-0 flex-1">
                                <span class="stat-icon {{ $j['tile'] }} w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"><x-icon :name="$j['ikon']" class="w-6 h-6" /></span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide ring-1 ring-inset {{ $j['chip'] }}">{{ $j['label'] }}</span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Berjalan</span>
                                    </div>
                                    <h2 class="mt-1.5 text-lg font-extrabold text-slate-900 dark:text-slate-100 leading-snug">{{ $labelGel($it) }} <span class="font-semibold text-slate-400">· {{ $it['periode']?->nama_periode }}</span></h2>
                                    <p class="mt-0.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                        <span class="inline-flex items-center gap-1"><x-icon name="users" class="w-3.5 h-3.5" /> {{ $it['jumlah'] }} mahasiswa</span>
                                        <span class="inline-flex items-center gap-1"><x-icon name="calendar" class="w-3.5 h-3.5" /> {{ $rentang($it) }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 lg:shrink-0">
                                <button type="button" @click="rinci = !rinci" class="ui-btn-secondary">
                                    <svg class="w-4 h-4 transition-transform" :class="rinci && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    <span x-text="rinci ? 'Sembunyikan rincian' : 'Lihat rincian'"></span>
                                </button>
                                <a href="{{ route('dosen.undangan.lihat', [$it['periode'], $it['jenis'], $it['gelombang_kunci']]) }}" target="_blank" class="ui-btn-secondary"><x-icon name="printer" class="w-4 h-4" /> Cetak</a>
                                <a href="{{ route('dosen.undangan.unduh', [$it['periode'], $it['jenis'], $it['gelombang_kunci']]) }}" class="ui-btn-primary"><x-icon name="download" class="w-4 h-4" /> Unduh PDF</a>
                            </div>
                        </header>

                        <div x-show="rinci" class="px-5 sm:px-6 pb-6 pt-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/40">
                            @include('dosen.partials._rincian-undangan', ['sidangs' => $it['sidangs'], 'dosen' => $dosen])
                        </div>
                    </article>
                @empty
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center">
                        <span class="mx-auto w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 flex items-center justify-center"><x-icon name="inbox" class="w-7 h-7" /></span>
                        <h3 class="mt-4 font-extrabold text-slate-800 dark:text-slate-100">Belum ada undangan untuk gelombang berjalan</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">Undangan akan muncul setelah koordinator menjadwalkan Anda sebagai penguji. Undangan sebelumnya tersimpan di <button type="button" @click="tab = 'arsip'" class="font-bold text-indigo-600 dark:text-indigo-300 hover:underline">Arsip</button>.</p>
                    </div>
                @endforelse
            </section>

            {{-- ═════════ ARSIP ═════════ --}}
            <section x-show="tab === 'arsip'" x-cloak class="space-y-4">
                @if($arsip->isEmpty())
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center">
                        <span class="mx-auto w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center"><x-icon name="folder" class="w-7 h-7" /></span>
                        <h3 class="mt-4 font-extrabold text-slate-800 dark:text-slate-100">Arsip masih kosong</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Undangan dari gelombang dan semester yang sudah lewat akan tersimpan di sini.</p>
                    </div>
                @else
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Semester</span>
                        <select x-model="semester" class="px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-700 dark:text-slate-200">
                            <option value="">Semua semester</option>
                            @foreach($arsip as $g)
                                <option value="{{ $g['periode']->id }}">{{ $g['periode']->nama_periode }}</option>
                            @endforeach
                        </select>
                    </div>

                    @foreach($arsip as $g)
                        <div x-show="!semester || semester === '{{ $g['periode']->id }}'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 bg-slate-50/70 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-800">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 flex items-center justify-center shrink-0"><x-icon name="folder" class="w-5 h-5" /></span>
                                    <div class="min-w-0">
                                        <h3 class="font-extrabold text-slate-900 dark:text-slate-100 truncate">{{ $g['periode']->nama_periode }}</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $g['items']->count() }} undangan · {{ $g['total'] }} mahasiswa diuji</p>
                                    </div>
                                </div>
                                <a href="{{ route('dosen.undangan.zip', $g['periode']) }}" class="ui-btn-secondary self-start sm:self-center"><x-icon name="download" class="w-4 h-4" /> Unduh Semua (ZIP)</a>
                            </div>
                            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($g['items'] as $it)
                                    @php $j = $infoJenis[$it['jenis']]; @endphp
                                    <li x-show="!jenis || jenis === '{{ $it['jenis'] }}'" x-data="{ rinci: false }">
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-3.5">
                                            <button type="button" @click="rinci = !rinci" class="flex items-center gap-3 min-w-0 flex-1 text-left group">
                                                <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-transform shrink-0" :class="rinci && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide ring-1 ring-inset shrink-0 {{ $j['chip'] }}">{{ $j['singkat'] }}</span>
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-bold text-slate-900 dark:text-slate-100 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">{{ $labelGel($it) }}</span>
                                                    <span class="block text-xs text-slate-500 dark:text-slate-400 truncate">{{ $rentang($it) }} · {{ $it['jumlah'] }} mahasiswa</span>
                                                </span>
                                            </button>
                                            <div class="flex gap-2 shrink-0 pl-7 sm:pl-0">
                                                <a href="{{ route('dosen.undangan.lihat', [$it['periode'], $it['jenis'], $it['gelombang_kunci']]) }}" target="_blank" class="ui-btn-secondary"><x-icon name="printer" class="w-4 h-4" /> Cetak</a>
                                                <a href="{{ route('dosen.undangan.unduh', [$it['periode'], $it['jenis'], $it['gelombang_kunci']]) }}" class="ui-btn-primary"><x-icon name="download" class="w-4 h-4" /> PDF</a>
                                            </div>
                                        </div>
                                        <div x-show="rinci" x-cloak class="px-5 pb-5 pt-1">
                                            <div class="rounded-2xl bg-slate-50/70 dark:bg-slate-800/30 p-4 sm:p-5">
                                                @include('dosen.partials._rincian-undangan', ['sidangs' => $it['sidangs'], 'dosen' => $dosen])
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @endif
            </section>
        @endif
    </div>
</x-app-layout>

<x-app-layout title="Undangan Menguji">
    <x-slot:header>Undangan Menguji</x-slot:header>

    @php
        $infoJenis = [
            'sempro'  => ['Seminar Proposal', 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30', 'amber', 'document'],
            'skripsi' => ['Sidang Skripsi', 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30', 'violet', 'academic'],
        ];
        $rentang = function ($it) {
            if (!$it['mulai']) return 'Jadwal belum ditentukan';
            $a = $it['mulai']->locale('id'); $b = $it['selesai']->locale('id');
            return $a->isSameDay($b) ? $a->translatedFormat('l, j F Y') : $a->translatedFormat('j M') . ' – ' . $b->translatedFormat('j M Y');
        };
        $labelGel = fn ($it) => $it['gelombang'] === null ? 'Tanpa gelombang' : 'Gelombang ' . $it['gelombang'];
    @endphp

    <div x-data="{ tab: '{{ $berjalan->isEmpty() && $arsip->isNotEmpty() ? 'arsip' : 'berjalan' }}', jenis: '', semester: '' }" class="space-y-6">

        <x-page-header title="Undangan Menguji" icon="mail" accent="indigo">
            <x-slot:description>Undangan menjadi dewan penguji Seminar Proposal & Sidang Skripsi untuk Anda. Undangan gelombang yang sedang berjalan tampil di sini, dan otomatis masuk <strong>Arsip</strong> setelah gelombangnya lewat — tetap bisa diunduh kapan saja per semester.</x-slot:description>
        </x-page-header>

        @if(!$dosen)
            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-sm text-amber-800 dark:text-amber-200">
                Akun Anda belum terhubung dengan data Master Dosen, sehingga undangan belum dapat ditampilkan. Hubungi Super Admin.
            </div>
        @else
            {{-- Tab --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="inline-flex p-1 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                    <button type="button" @click="tab = 'berjalan'" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all"
                            :class="tab === 'berjalan' ? 'bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                        <x-icon name="lightning" class="w-4 h-4" /> Berjalan
                        <span class="min-w-[1.25rem] h-5 px-1 rounded-full text-[10px] flex items-center justify-center" :class="tab === 'berjalan' ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">{{ $berjalan->count() }}</span>
                    </button>
                    <button type="button" @click="tab = 'arsip'" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all"
                            :class="tab === 'arsip' ? 'bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                        <x-icon name="folder" class="w-4 h-4" /> Arsip
                        <span class="min-w-[1.25rem] h-5 px-1 rounded-full text-[10px] flex items-center justify-center" :class="tab === 'arsip' ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">{{ $arsip->sum(fn ($g) => $g['items']->count()) }}</span>
                    </button>
                </div>

                <div class="inline-flex p-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-[11px] font-bold">
                    @foreach(['' => 'Semua', 'sempro' => 'Sempro', 'skripsi' => 'Skripsi'] as $k => $v)
                        <button type="button" @click="jenis = '{{ $k }}'" class="px-3 py-1.5 rounded-lg transition-colors"
                                :class="jenis === '{{ $k }}' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'">{{ $v }}</button>
                    @endforeach
                </div>
            </div>

            {{-- ═════════ BERJALAN ═════════ --}}
            <section x-show="tab === 'berjalan'">
                @if($berjalan->isEmpty())
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center">
                        <span class="mx-auto w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 flex items-center justify-center"><x-icon name="inbox" class="w-7 h-7" /></span>
                        <h3 class="mt-4 font-extrabold text-slate-800 dark:text-slate-100">Belum ada undangan untuk gelombang berjalan</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">Undangan akan muncul di sini setelah koordinator menjadwalkan Anda sebagai penguji. Undangan sebelumnya ada di tab <button type="button" @click="tab = 'arsip'" class="font-bold text-indigo-600 dark:text-indigo-300 hover:underline">Arsip</button>.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($berjalan as $it)
                            @php [$jl, $jcls, $jwarna, $jikon] = $infoJenis[$it['jenis']]; @endphp
                            <article x-show="!jenis || jenis === '{{ $it['jenis'] }}'"
                                     class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                                <span class="absolute inset-x-0 top-0 h-1 {{ $it['jenis'] === 'sempro' ? 'bg-gradient-to-r from-amber-400 to-orange-500' : 'bg-gradient-to-r from-violet-500 to-fuchsia-500' }}"></span>
                                <div class="p-5">
                                    <div class="flex items-start gap-3">
                                        <span class="stat-icon {{ $jwarna }} w-11 h-11 rounded-xl flex items-center justify-center shrink-0"><x-icon :name="$jikon" class="w-5 h-5" /></span>
                                        <div class="min-w-0 flex-1">
                                            <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide ring-1 ring-inset {{ $jcls }}">{{ $jl }}</span>
                                            <h3 class="mt-1 font-extrabold text-slate-900 dark:text-slate-100 leading-snug">{{ $labelGel($it) }}</h3>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $it['periode']?->nama_periode }}</p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30 shrink-0">Berjalan</span>
                                    </div>
                                    <dl class="mt-4 grid grid-cols-2 gap-2 text-xs">
                                        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3 py-2">
                                            <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Mahasiswa</dt>
                                            <dd class="font-extrabold text-slate-800 dark:text-slate-100">{{ $it['jumlah'] }} orang</dd>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3 py-2 min-w-0">
                                            <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Jadwal</dt>
                                            <dd class="font-extrabold text-slate-800 dark:text-slate-100 truncate" title="{{ $rentang($it) }}">{{ $rentang($it) }}</dd>
                                        </div>
                                    </dl>
                                    <div class="mt-4 flex gap-2">
                                        <a href="{{ route('dosen.undangan.lihat', [$it['periode'], $it['jenis'], $it['gelombang_kunci']]) }}" target="_blank" class="ui-btn-secondary flex-1"><x-icon name="eye" class="w-4 h-4" /> Lihat</a>
                                        <a href="{{ route('dosen.undangan.unduh', [$it['periode'], $it['jenis'], $it['gelombang_kunci']]) }}" class="ui-btn-primary flex-1"><x-icon name="download" class="w-4 h-4" /> Unduh PDF</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
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
                        <div x-show="!semester || semester === '{{ $g['periode']->id }}'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
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
                                    @php [$jl, $jcls] = $infoJenis[$it['jenis']]; @endphp
                                    <li x-show="!jenis || jenis === '{{ $it['jenis'] }}'" class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-3.5 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                            <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide ring-1 ring-inset shrink-0 {{ $jcls }}">{{ $it['jenis'] === 'sempro' ? 'Sempro' : 'Skripsi' }}</span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $labelGel($it) }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $rentang($it) }} · {{ $it['jumlah'] }} mahasiswa</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 shrink-0">
                                            <a href="{{ route('dosen.undangan.lihat', [$it['periode'], $it['jenis'], $it['gelombang_kunci']]) }}" target="_blank" class="ui-btn-secondary"><x-icon name="eye" class="w-4 h-4" /> Lihat</a>
                                            <a href="{{ route('dosen.undangan.unduh', [$it['periode'], $it['jenis'], $it['gelombang_kunci']]) }}" class="ui-btn-primary"><x-icon name="download" class="w-4 h-4" /> PDF</a>
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

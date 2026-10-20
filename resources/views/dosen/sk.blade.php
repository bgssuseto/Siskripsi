<x-app-layout title="SK Pembimbing & Penguji">
    <x-slot:header>
        SK Pembimbing &amp; Penguji
    </x-slot:header>

    <!-- Back Navigation -->
    <div class="mb-4">
        <a href="{{ route('dosen.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    <x-page-header title="SK Pembimbing & Penguji" icon="badge-check" accent="indigo">
            <x-slot:description>SK Pembimbing dihitung sejak Sempro mahasiswa bimbingan Anda disetujui dan bisa diunduh <strong>sementara</strong> per gelombang. SK <strong>final</strong> (total gelombang 1–3) terbit setelah sidang skripsi gelombang 3 selesai — termasuk SK Penguji.</x-slot:description>
            @if($dosen && $rows->isNotEmpty())
                <x-slot:actions>
                    <form method="GET" action="{{ route('dosen.sk.index') }}" class="flex items-center gap-2">
                        <label for="pilih-periode" class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Periode</label>
                        <select id="pilih-periode" name="periode" onchange="this.form.submit()"
                                class="h-10 min-w-[220px] px-3 rounded-xl text-sm font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 cursor-pointer">
                            @foreach($rows as $opt)
                                <option value="{{ $opt->id }}" @selected($row && $row['periode']->id === $opt->id)>{{ $opt->nama_periode }}{{ $opt->aktif ? ' (Aktif)' : '' }}</option>
                            @endforeach
                        </select>
                        <noscript><button class="ui-btn-secondary">Tampilkan</button></noscript>
                    </form>
                </x-slot:actions>
            @endif
        </x-page-header>

    @if(!$dosen)
        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-sm text-amber-800 dark:text-amber-300">
            Akun Anda belum terhubung dengan data dosen. Silakan hubungi admin.
        </div>
    @elseif($rows->isEmpty() || !$row)
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center">
            <p class="text-3xl mb-2"><x-icon name="document" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></p>
            <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Belum ada SK</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Anda belum tercatat sebagai pembimbing atau penguji ujian skripsi pada periode mana pun.</p>
        </div>
    @else
        <div class="space-y-4">
                @php
                    $p = $row['periode'];
                    $totalPembimbing = $row['utama'] + $row['pendamping'];
                    $totalPenguji = $row['ketua'] + $row['anggota'];
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100">{{ $p->nama_periode }}</h3>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            @if($p->aktif)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">● Periode Aktif</span>
                            @endif
                            @if($row['final'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-teal-50 dark:bg-teal-500/15 text-teal-700 dark:text-teal-200 ring-1 ring-inset ring-teal-200 dark:ring-teal-500/30"><x-icon name="badge-check" class="w-3 h-3" /> SK Final</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-200 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/30"><x-icon name="clock" class="w-3 h-3" /> SK Sementara</span>
                            @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- SK Pembimbing --}}
                        <div class="rounded-2xl border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50/60 dark:bg-indigo-500/10 p-4 flex flex-col gap-3">
                            <div>
                                <p class="text-sm font-extrabold text-indigo-900 dark:text-indigo-200">SK Pembimbing</p>
                                <p class="text-xs text-indigo-700/80 dark:text-indigo-300/80 mt-0.5">
                                    {{ $row['utama'] }} Pembimbing Utama · {{ $row['pendamping'] }} Pembimbing Pendamping
                                </p>
                                @if($row['sempro'] > 0)
                                    <p class="text-[11px] text-indigo-600/80 dark:text-indigo-300/80 mt-0.5">{{ $row['sempro'] }} mahasiswa masih tahap Sempro</p>
                                @endif
                            </div>
                            @if($totalPembimbing > 0)
                                @unless($row['final'])
                                    <div class="flex gap-2 rounded-xl bg-amber-50 dark:bg-amber-500/10 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/30 px-3 py-2.5 text-[11px] leading-relaxed text-amber-800 dark:text-amber-200">
                                        <x-icon name="warning" class="w-4 h-4 shrink-0 mt-px" />
                                        <span><strong>SK sementara, belum final.</strong> Sidang gelombang 1–3 {{ $p->nama_periode }} belum selesai semua, jadi daftar bimbingan masih bisa bertambah. PDF diberi tanda <em>SEMENTARA</em>; unduh ulang SK final setelah semester berakhir.</span>
                                    </div>
                                @endunless
                                <ul class="space-y-2">
                                    @foreach($row['per_gelombang'] as $g)
                                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white dark:bg-slate-900/60 ring-1 ring-inset ring-indigo-100 dark:ring-indigo-500/20 px-3 py-2.5">
                                            <div class="min-w-0">
                                                <p class="text-xs font-extrabold text-slate-800 dark:text-slate-100">{{ $g['gelombang'] === 'tanpa' ? 'Tanpa Gelombang' : 'Gelombang ' . $g['gelombang'] }}</p>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $g['utama'] }} Utama · {{ $g['pendamping'] }} Pendamping</p>
                                            </div>
                                            <a href="{{ route('dosen.sk.download', [$p, 'pembimbing']) }}?gelombang={{ $g['gelombang'] }}"
                                               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-extrabold shadow-sm transition-all">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12a2 2 0 002-2V8l-6-6H6a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                {{ $row['final'] ? 'Unduh PDF' : 'Unduh (Sementara)' }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                @if($row['per_gelombang']->count() > 1)
                                    <a href="{{ route('dosen.sk.download', [$p, 'pembimbing']) }}" class="text-[11px] font-bold text-indigo-700 dark:text-indigo-300 hover:underline">Unduh SK Pembimbing seluruh periode →</a>
                                @endif
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500 italic">Tidak ada mahasiswa bimbingan pada periode ini.</span>
                            @endif
                        </div>

                        {{-- SK Penguji --}}
                        <div class="rounded-2xl border border-purple-200 dark:border-purple-500/30 bg-purple-50/60 dark:bg-purple-500/10 p-4 flex flex-col gap-3">
                            <div>
                                <p class="text-sm font-extrabold text-purple-900 dark:text-purple-200">SK Penguji</p>
                                <p class="text-xs text-purple-700/80 dark:text-purple-300/80 mt-0.5">
                                    {{ $row['ketua'] }} Ketua Penguji · {{ $row['anggota'] }} Anggota Penguji
                                </p>
                            </div>
                            @if($totalPenguji > 0 && !$row['final'])
                                <div class="flex gap-2 rounded-xl bg-white/70 dark:bg-slate-900/40 ring-1 ring-inset ring-purple-200 dark:ring-purple-500/30 px-3 py-2.5 text-[11px] leading-relaxed text-purple-800 dark:text-purple-200">
                                    <x-icon name="lock" class="w-4 h-4 shrink-0 mt-px" />
                                    <span>SK Penguji final (total gelombang 1–3) tersedia setelah sidang skripsi gelombang 3 {{ $p->nama_periode }} selesai. SK sementara hanya untuk SK Pembimbing.</span>
                                </div>
                            @elseif($totalPenguji > 0)
                                <a href="{{ route('dosen.sk.download', [$p, 'penguji']) }}"
                                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-extrabold shadow-md transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12a2 2 0 002-2V8l-6-6H6a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    Unduh SK Penguji (PDF)
                                </a>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500 italic">Tidak menguji ujian skripsi pada periode ini.</span>
                            @endif
                        </div>
                    </div>
                </div>
        </div>
    @endif
</x-app-layout>

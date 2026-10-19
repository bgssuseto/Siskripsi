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

    <!-- Page Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">SK Pembimbing &amp; Penguji</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            SK Pembimbing terbit sejak mahasiswa bimbingan Anda disetujui mendaftar Sempro (tanpa menunggu periode sidang skripsi); SK Penguji dihitung dari ujian skripsi (sidang &amp; jurnal). Dokumen berisi surat permohonan SK beserta rekap dan rincian mahasiswa Anda.
        </p>
    </div>

    @if(!$dosen)
        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-sm text-amber-800 dark:text-amber-300">
            Akun Anda belum terhubung dengan data dosen. Silakan hubungi admin.
        </div>
    @elseif($rows->isEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center">
            <p class="text-3xl mb-2">📄</p>
            <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Belum ada SK</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Anda belum tercatat sebagai pembimbing atau penguji ujian skripsi pada periode mana pun.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($rows as $row)
                @php
                    $p = $row['periode'];
                    $totalPembimbing = $row['utama'] + $row['pendamping'];
                    $totalPenguji = $row['ketua'] + $row['anggota'];
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100">{{ $p->nama_periode }}</h3>
                            @if($p->aktif)
                                <span class="inline-flex mt-1 items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">● Periode Aktif</span>
                            @endif
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
                            </div>
                            @if($totalPembimbing > 0)
                                <a href="{{ route('dosen.sk.download', [$p, 'pembimbing']) }}"
                                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold shadow-md transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12a2 2 0 002-2V8l-6-6H6a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    Unduh SK Pembimbing (PDF)
                                </a>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500 italic">Tidak ada mahasiswa bimbingan baru pada periode ini.</span>
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
                            @if($totalPenguji > 0)
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
            @endforeach
        </div>
    @endif
</x-app-layout>

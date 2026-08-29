@php
    $isSempro = $s->jenis_tugas_akhir === 'sempro';
    $roles = [];
    if($s->dosen_pembimbing_utama_id === $dosen->id) $roles[] = 'Pembimbing Utama';
    if($isSempro && $s->dosen_pembimbing_pendamping_id === $dosen->id) $roles[] = 'Pembimbing Pendamping';
    if($s->ketua_penguji_id === $dosen->id) $roles[] = 'Ketua Penguji';
    if($s->anggota_penguji_1_id === $dosen->id) $roles[] = 'Anggota Penguji 1';
    if($s->anggota_penguji_2_id === $dosen->id) $roles[] = 'Anggota Penguji 2';
@endphp
<!-- Timeline Item -->
<div class="relative">
    <!-- Dot marker -->
    <div class="absolute -left-[31px] top-1.5 w-4 h-4 rounded-full border-4 border-white shadow-sm transition-transform duration-300 hover:scale-125
        {{ $isSempro ? 'bg-blue-600' : 'bg-emerald-600' }}"></div>

    <div class="bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 rounded-2xl transition-all duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-3.5">
            <div class="flex items-center gap-3">
                <span class="inline-flex px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-wider
                    {{ $isSempro ? 'bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800' : 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800' }}">
                    {{ $isSempro ? 'Sempro' : 'Sidang Skripsi' }}
                </span>
                {!! $s->getJadwalStatusHtml() !!}
                <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="inline-block bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800 px-2 py-0.5 rounded font-extrabold">{{ $s->jam ?? '-' }}</span>
                </div>
            </div>
            <div class="text-xs font-bold text-slate-600 dark:text-slate-300">
                {{ $s->tanggal ? $s->tanggal->translatedFormat('l, d F Y') : '-' }}
            </div>
        </div>

        <h4 class="font-extrabold text-slate-900 dark:text-slate-100 text-base leading-snug">{{ $s->nama_mahasiswa }} <span class="text-slate-400 font-mono text-xs font-normal">({{ $s->nim }})</span></h4>
        <p class="text-xs text-slate-650 dark:text-slate-400 mt-1.5 leading-relaxed font-semibold">{{ $s->judul_skripsi }}</p>

        <div class="mt-4 pt-4 border-t border-slate-200/60 dark:border-slate-700/60 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-start gap-2">
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-0.5 shrink-0">Peran:</span>
                <div class="flex flex-wrap gap-1">
                    @php
                        $roleColors = [
                            'Pembimbing Utama'      => 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800',
                            'Pembimbing Pendamping' => 'bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800',
                            'Ketua Penguji'         => 'bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-800',
                            'Anggota Penguji 1'     => 'bg-purple-100 dark:bg-purple-900/60 text-purple-800 dark:text-purple-200 border border-purple-200 dark:border-purple-800',
                            'Anggota Penguji 2'     => 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800',
                        ];
                    @endphp
                    @foreach($roles as $r)
                        <span class="inline-block px-2 py-0.5 text-[9px] font-extrabold rounded-md {{ $roleColors[$r] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600' }}">
                            {{ $r }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Ruang: {{ $s->ruang->kode_ruangan ?? '-' }}
            </div>
        </div>
    </div>
</div>

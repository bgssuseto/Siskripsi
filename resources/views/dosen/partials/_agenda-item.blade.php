@php
    $isSempro = $s->jenis_tugas_akhir === 'sempro';
    $roles = [];
    if($s->dosen_pembimbing_utama_id === $dosen->id) $roles[] = 'Pembimbing Utama';
    if($isSempro && $s->dosen_pembimbing_pendamping_id === $dosen->id) $roles[] = 'Pembimbing Pendamping';
    // Untuk Sempro, ketua_penguji_id/anggota_penguji_1_id/2_id SELALU mencerminkan
    // pembimbing (lihat Sidang::syncPengujiFromPembimbing()) — sudah tercakup 2
    // baris di atas, dilewati di sini supaya tidak dobel.
    if(!$isSempro && $s->ketua_penguji_id === $dosen->id) $roles[] = 'Ketua Penguji';
    if(!$isSempro && $s->anggota_penguji_1_id === $dosen->id) $roles[] = 'Anggota Penguji 1';
    if(!$isSempro && $s->anggota_penguji_2_id === $dosen->id) $roles[] = 'Anggota Penguji 2';

    $roleColors = [
        'Pembimbing Utama'      => 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-200 dark:ring-indigo-500/30',
        'Pembimbing Pendamping' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/15 dark:text-sky-200 dark:ring-sky-500/30',
        'Ketua Penguji'         => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-500/30',
        'Anggota Penguji 1'     => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/15 dark:text-violet-200 dark:ring-violet-500/30',
        'Anggota Penguji 2'     => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/15 dark:text-violet-200 dark:ring-violet-500/30',
    ];

    // Status ringkas (satu badge) — detail tanggal/jam/ruang ditampilkan sekali di bawah.
    $hariIni = now()->timezone('Asia/Jakarta')->format('Y-m-d');
    [$statusLabel, $statusCls] = match (true) {
        !$s->tanggal                               => ['Belum Dijadwalkan', 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-200 dark:ring-rose-500/30'],
        $s->isExamEnded()                          => ['Selesai', 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-700/60 dark:text-slate-300 dark:ring-slate-600'],
        $s->tanggal->format('Y-m-d') === $hariIni  => ['Hari Ini', 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/15 dark:text-blue-200 dark:ring-blue-500/30'],
        default                                    => ['Akan Datang', 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-200 dark:ring-emerald-500/30'],
    };
@endphp
<!-- Timeline Item -->
<div class="relative">
    <!-- Dot marker (garis timeline hanya tampil mulai layar sm) -->
    <div class="hidden sm:block absolute -left-[31px] top-5 w-4 h-4 rounded-full border-4 border-white dark:border-slate-800 shadow-sm
        {{ $isSempro ? 'bg-blue-600' : 'bg-emerald-600' }}"></div>

    <article class="relative overflow-hidden bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
        <span class="absolute inset-y-0 left-0 w-1 {{ $isSempro ? 'bg-blue-600' : 'bg-emerald-600' }}"></span>

        <div class="p-4 sm:p-5 pl-5 sm:pl-6 space-y-3">
            <!-- Jenis & status -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide ring-1 ring-inset
                    {{ $isSempro ? 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/15 dark:text-blue-200 dark:ring-blue-500/30' : 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-200 dark:ring-emerald-500/30' }}">
                    {{ $isSempro ? 'Sempro' : 'Sidang Skripsi' }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold ring-1 ring-inset {{ $statusCls }}">{{ $statusLabel }}</span>
            </div>

            <!-- Mahasiswa -->
            <div class="min-w-0">
                <h4 class="font-extrabold text-slate-900 dark:text-slate-100 text-[15px] leading-snug break-words">{{ $s->nama_mahasiswa }}</h4>
                <p class="mt-0.5 text-[11px] font-mono font-semibold text-slate-500 dark:text-slate-400">{{ $s->nim }}</p>
                <p class="mt-1.5 text-xs text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-3">{{ $s->judul_skripsi }}</p>
            </div>

            <!-- Waktu & tempat -->
            @if($s->jadwal_terpisah && $s->tanggal)
                <div class="rounded-xl bg-slate-50 dark:bg-slate-900/50 p-2.5">{!! $s->getJadwalStatusHtml() !!}</div>
            @else
                <dl class="grid grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] sm:grid-cols-[minmax(0,1.6fr)_minmax(0,1.2fr)_minmax(0,0.8fr)] gap-1.5 sm:gap-2 text-xs">
                    @foreach([
                        ['Tanggal', $s->tanggal ? $s->tanggal->locale('id')->translatedFormat('l, j F Y') : 'Belum ditentukan', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['Waktu',   $s->jam ? $s->jam . ' WIB' : '-', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['Ruang',   $s->ruang->kode_ruangan ?? '-', 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                    ] as [$label, $nilai, $ikon])
                        <div class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900/50 px-2.5 py-2 min-w-0 {{ $loop->first ? 'col-span-2 sm:col-span-1' : '' }}">
                            <svg class="w-4 h-4 shrink-0 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $ikon }}"/></svg>
                            <div class="min-w-0">
                                <dt class="text-[9px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $label }}</dt>
                                <dd class="font-bold text-slate-800 dark:text-slate-200 leading-snug break-words">{{ $nilai }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            @endif

            <!-- Peran -->
            @if($roles)
                <div class="flex flex-wrap items-center gap-1.5 pt-3 border-t border-slate-100 dark:border-slate-700/60">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mr-0.5">Peran Anda</span>
                    @foreach($roles as $r)
                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold ring-1 ring-inset {{ $roleColors[$r] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $r }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </article>
</div>

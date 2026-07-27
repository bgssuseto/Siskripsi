<x-app-layout title="Jadwal Sidang & Ujian">
<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    
    <!-- Welcome Header Banner -->
    <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 rounded-3xl text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4 border border-slate-800">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-semibold border border-indigo-400/30 mb-2">
                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span> Role: Mahasiswa
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">
                {{ ($type ?? 'sempro') === 'sempro' ? 'Jadwal Seminar Proposal (Sempro)' : 'Jadwal Sidang Skripsi' }}
            </h1>
            <p class="text-xs text-indigo-200 mt-1">Pantau informasi waktu, ruang ujian, dosen pembimbing, dan dewan penguji Anda.</p>
        </div>
    </div>

    <!-- Main Schedule Section -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">
                    {{ ($type ?? 'sempro') === 'sempro' ? 'Agenda Seminar Proposal' : 'Agenda Sidang Skripsi' }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Sesi ujian aktif untuk periode akademik saat ini</p>
            </div>
            <span class="px-3.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold text-xs rounded-2xl border border-slate-200 dark:border-slate-700">
                {{ $sidangs->count() }} Jadwal Terdaftar
            </span>
        </div>

        @if($sidangs->isEmpty())
            <div class="text-center py-14 bg-slate-50/60 dark:bg-slate-950/40 rounded-3xl border border-dashed border-slate-200 dark:border-slate-800">
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-3 text-xl">
                    📅
                </div>
                <p class="text-sm font-extrabold text-slate-700 dark:text-slate-200">Belum Ada Agenda Ujian</p>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    {{ ($type ?? 'sempro') === 'sempro' 
                        ? 'Jadwal Seminar Proposal Anda belum ditentukan atau belum diajukan.' 
                        : 'Jadwal Sidang Skripsi Anda belum diplotting oleh koordinator.' }}
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6">
                @foreach($sidangs as $s)
                    @php
                        $isPlotted = $s->tanggal && $s->ruang_id && $s->ketua_penguji_id;
                    @endphp
                    <div class="p-6 sm:p-7 rounded-3xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-indigo-300 dark:hover:border-indigo-800 hover:shadow-lg transition-all duration-300 space-y-5 relative overflow-hidden group">
                        
                        <!-- Header Bar Inside Card -->
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2.5">
                                <span class="px-3.5 py-1 text-xs font-extrabold rounded-xl uppercase tracking-wider {{ $s->jenis_tugas_akhir === 'sempro' ? 'bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'bg-purple-100 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800' }}">
                                    {{ $s->jenis_label }}
                                </span>
                                
                                @if($isPlotted)
                                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1 text-xs font-extrabold rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                        ✓ Terjadwal & Plotting Selesai
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1 text-xs font-extrabold rounded-xl bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800 shadow-2xs">
                                        ⏳ Belum di-Plotting
                                    </span>
                                @endif
                            </div>

                            <div class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                Periode: <strong class="text-slate-800 dark:text-slate-200">{{ $s->periode->nama_periode ?? '-' }}</strong>
                            </div>
                        </div>

                        <!-- Judul Proposal / Skripsi -->
                        <div>
                            <p class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider mb-1">Judul Tugas Akhir / Skripsi</p>
                            <h3 class="font-extrabold text-slate-900 dark:text-slate-100 text-base leading-relaxed group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                "{{ $s->judul_skripsi }}"
                            </h3>
                        </div>

                        <!-- Grid Info Pelaksanaan & Ruangan (2 Box Columns) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Box 1: Tanggal & Waktu -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
                                <div class="flex items-center gap-2 text-xs font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                    <span class="text-base">📅</span> Jadwal Pelaksanaan Ujian
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">Tanggal Ujian:</span>
                                    @if($s->tanggal)
                                        <strong class="text-slate-900 dark:text-slate-100 font-extrabold">{{ $s->tanggal->format('d M Y') }}</strong>
                                    @else
                                        <span class="px-2.5 py-0.5 bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 text-[11px] font-extrabold rounded-lg border border-amber-300 dark:border-amber-800">Belum di-Plotting</span>
                                    @endif
                                </div>
                                <div class="flex justify-between items-center text-xs pt-1">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">Waktu / Jam:</span>
                                    @if($s->jam)
                                        <strong class="text-indigo-600 dark:text-indigo-400 font-extrabold">{{ $s->jam }} WIB</strong>
                                    @else
                                        <span class="px-2.5 py-0.5 bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 text-[11px] font-extrabold rounded-lg border border-amber-300 dark:border-amber-800">Belum di-Plotting</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Box 2: Ruangan Ujian -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
                                <div class="flex items-center gap-2 text-xs font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                    <span class="text-base">📍</span> Lokasi & Ruang Ujian
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">Ruang Sidang:</span>
                                    @if($s->ruang)
                                        <strong class="text-slate-900 dark:text-slate-100 font-extrabold">{{ $s->ruang->nama_ruangan }} ({{ $s->ruang->kode_ruangan }})</strong>
                                    @else
                                        <span class="px-2.5 py-0.5 bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 text-[11px] font-extrabold rounded-lg border border-amber-300 dark:border-amber-800">Belum di-Plotting</span>
                                    @endif
                                </div>
                                <div class="flex justify-between items-center text-xs pt-1">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">Status Verifikasi Berkas:</span>
                                    @if(($s->verifikasi_status ?? 'menunggu') === 'disetujui')
                                        <span class="px-2.5 py-0.5 bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 text-[11px] font-extrabold rounded-lg border border-emerald-300 dark:border-emerald-800">Terverifikasi</span>
                                    @elseif(($s->verifikasi_status ?? '') === 'ditolak')
                                        <span class="px-2.5 py-0.5 bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 text-[11px] font-extrabold rounded-lg border border-rose-300 dark:border-rose-800">Ditolak</span>
                                    @else
                                        <span class="px-2.5 py-0.5 bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 text-[11px] font-extrabold rounded-lg border border-amber-300 dark:border-amber-800">Belum Diverifikasi</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Grid Tim Dosen Pembimbing & Penguji -->
                        <div class="grid grid-cols-1 {{ ($s->jenis_tugas_akhir !== 'sempro' && ($type ?? 'sempro') !== 'sempro') ? 'sm:grid-cols-2' : '' }} gap-4 pt-1">
                            <!-- Box 1: Dosen Pembimbing (Untuk Sempro sekaligus Penguji) -->
                            <div class="p-4 bg-indigo-50/40 dark:bg-indigo-950/30 rounded-2xl border border-indigo-100 dark:border-indigo-900 space-y-2">
                                <p class="text-xs font-extrabold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>👨‍🏫</span> {{ $s->jenis_tugas_akhir === 'sempro' ? 'Tim Dosen Pembimbing & Penguji Sempro' : 'Tim Dosen Pembimbing' }}
                                </p>
                                <div class="space-y-1 text-xs">
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-slate-500 dark:text-slate-400 font-medium shrink-0">Pembimbing 1:</span>
                                        <strong class="text-slate-900 dark:text-slate-100 font-bold text-right">{{ $s->pembimbingUtama->nama_dosen ?? '-' }}</strong>
                                    </div>
                                    <div class="flex items-start justify-between gap-2 pt-1">
                                        <span class="text-slate-500 dark:text-slate-400 font-medium shrink-0">Pembimbing 2:</span>
                                        @if($s->pembimbingPendamping)
                                            <strong class="text-slate-900 dark:text-slate-100 font-bold text-right">{{ $s->pembimbingPendamping->nama_dosen }}</strong>
                                        @else
                                            <span class="text-slate-400 italic text-[11px]">Tanpa Pembimbing 2</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($s->jenis_tugas_akhir !== 'sempro' && ($type ?? 'sempro') !== 'sempro')
                            <!-- Box 2: Tim Penguji (Hanya untuk Sidang Skripsi) -->
                            <div class="p-4 bg-purple-50/40 dark:bg-purple-950/30 rounded-2xl border border-purple-100 dark:border-purple-900 space-y-2">
                                <p class="text-xs font-extrabold text-purple-900 dark:text-purple-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>👨‍⚖️</span> Dewan Penguji Ujian
                                </p>
                                <div class="space-y-1.5 text-xs">
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-slate-500 dark:text-slate-400 font-medium shrink-0">Ketua Penguji:</span>
                                        @if($s->ketuaPenguji)
                                            <strong class="text-slate-900 dark:text-slate-100 font-bold text-right">{{ $s->ketuaPenguji->nama_dosen }}</strong>
                                        @else
                                            <span class="px-2.5 py-0.5 bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 text-[10px] font-extrabold rounded-md border border-amber-300 dark:border-amber-800">Belum di-Plotting</span>
                                        @endif
                                    </div>
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-slate-500 dark:text-slate-400 font-medium shrink-0">Penguji 1:</span>
                                        @if($s->anggotaPenguji1)
                                            <strong class="text-slate-900 dark:text-slate-100 font-bold text-right">{{ $s->anggotaPenguji1->nama_dosen }}</strong>
                                        @else
                                            <span class="px-2.5 py-0.5 bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 text-[10px] font-extrabold rounded-md border border-amber-300 dark:border-amber-800">Belum di-Plotting</span>
                                        @endif
                                    </div>
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-slate-500 dark:text-slate-400 font-medium shrink-0">Penguji 2:</span>
                                        @if($s->anggotaPenguji2)
                                            <strong class="text-slate-900 dark:text-slate-100 font-bold text-right">{{ $s->anggotaPenguji2->nama_dosen }}</strong>
                                        @else
                                            <span class="px-2.5 py-0.5 bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 text-[10px] font-extrabold rounded-md border border-amber-300 dark:border-amber-800">Belum di-Plotting</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Section khusus: Riwayat Sempro Terdahulu (Hanya muncul jika di tab Skripsi & Mahasiswa memiliki data Sempro) -->
    @if(($type ?? '') === 'skripsi' && isset($semproHistory) && $semproHistory->isNotEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📜</span>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Riwayat Seminar Proposal (Sempro) Terdahulu</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Arsip riwayat pelaksanaan Seminar Proposal Anda</p>
                    </div>
                </div>
                <span class="px-3.5 py-1.5 bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-extrabold text-xs rounded-2xl border border-amber-200 dark:border-amber-800">
                    {{ $semproHistory->count() }} Sesi
                </span>
            </div>

            <div class="space-y-2">
                @foreach($semproHistory as $sh)
                    <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:border-indigo-300 dark:hover:border-indigo-800 transition-all">
                        <div class="space-y-1.5 max-w-xl">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-extrabold rounded-lg bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800 shadow-2xs">
                                ✓ Sempro Lulus / Terdata
                            </span>
                            <h4 class="font-extrabold text-slate-900 dark:text-slate-100 text-xs sm:text-sm leading-snug">"{{ $sh->judul_skripsi }}"</h4>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 font-medium">Dosbing: <strong class="text-slate-800 dark:text-slate-200 font-bold">{{ $sh->pembimbingUtama->nama_dosen ?? '-' }}</strong></p>
                        </div>
                        <div class="text-left md:text-right shrink-0 text-xs space-y-1">
                            <p class="font-extrabold text-slate-900 dark:text-slate-100 text-xs">{{ $sh->tanggal ? $sh->tanggal->format('d M Y') : 'Status: Selesai' }}</p>
                            <div>
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-800 font-extrabold text-[11px] rounded-xl shadow-2xs">
                                    📍 Ruang: {{ $sh->ruang ? $sh->ruang->nama_ruangan : 'Belum di-Plotting' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
</x-app-layout>

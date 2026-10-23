<x-app-layout title="Dashboard Mahasiswa">
<div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">
    
    <!-- Sambutan + ujian terdekat -->
    @php
        $hariIni = now()->timezone('Asia/Jakarta')->startOfDay();
        $ujianBerikut = $sidangs
            ->filter(fn ($x) => $x->tanggal && \Carbon\Carbon::parse($x->tanggal)->gte($hariIni))
            ->sortBy(fn ($x) => \Carbon\Carbon::parse($x->tanggal)->format('Ymd') . ($x->jam ?? ''))
            ->first();
        $agendaSambutan = $ujianBerikut ? [
            'jenis'   => $ujianBerikut->jenis_tugas_akhir === 'sempro' ? 'Seminar Proposal' : ($ujianBerikut->jenis_tugas_akhir === 'jurnal' ? 'Sidang Jurnal' : 'Sidang Skripsi'),
            'judul'   => 'Ujian ' . ($ujianBerikut->jenis_tugas_akhir === 'sempro' ? 'Seminar Proposal' : 'Sidang') . ' Anda',
            'sub'     => $ujianBerikut->judul_skripsi,
            'tanggal' => $ujianBerikut->tanggal,
            'jam'     => $ujianBerikut->jam ? $ujianBerikut->jam . ' WIB' : null,
            'ruang'   => $ujianBerikut->ruang?->kode_ruangan,
            'url'     => Route::has('mahasiswa.jadwal.' . ($ujianBerikut->jenis_tugas_akhir === 'sempro' ? 'sempro' : 'skripsi'))
                ? route('mahasiswa.jadwal.' . ($ujianBerikut->jenis_tugas_akhir === 'sempro' ? 'sempro' : 'skripsi')) : null,
        ] : null;
    @endphp
    <x-sambutan :nama="$user->name" :peran="'Mahasiswa' . ($user->nim ? ' · ' . $user->nim : '')"
                subjudul="Pantau progres tugas akhir Anda dari pendaftaran sampai kelulusan. Satu langkah lagi menuju toga!"
                :agenda="$agendaSambutan" kosong="Belum ada jadwal ujian. Lengkapi pendaftaran Anda dan pantau progresnya di bawah.">
        <x-slot:aksi>
            @if(Route::has('mahasiswa.sempro.index'))<a href="{{ route('mahasiswa.sempro.index') }}" class="ui-btn-primary"><x-icon name="document-add" class="w-4 h-4" /> Pendaftaran Sempro</a>@endif
            @if(Route::has('mahasiswa.skripsi.index'))<a href="{{ route('mahasiswa.skripsi.index') }}" class="ui-btn-secondary"><x-icon name="academic" class="w-4 h-4" /> Pendaftaran Skripsi</a>@endif
            @if(Route::has('mahasiswa.jadwal.sempro'))<a href="{{ route('mahasiswa.jadwal.sempro') }}" class="ui-btn-secondary"><x-icon name="calendar" class="w-4 h-4" /> Jadwal Ujian</a>@endif
            @if(Route::has('mahasiswa.bebas-prodi.index'))<a href="{{ route('mahasiswa.bebas-prodi.index') }}" class="ui-btn-secondary"><x-icon name="badge-check" class="w-4 h-4" /> Bebas Prodi</a>@endif
        </x-slot:aksi>
    </x-sambutan>



    {{-- ── Registration Countdown Banner ── --}}
    @if($registrationWaves->isNotEmpty())
    <div class="grid grid-cols-1 {{ $registrationWaves->count() > 1 ? 'sm:grid-cols-2' : '' }} gap-3">
        @foreach($registrationWaves as $wave)
        @php
            $isSempro  = $wave['jenis'] === 'sempro';
            $label     = $isSempro ? 'Pendaftaran Sempro' : 'Pendaftaran Skripsi';
            $routeName = $isSempro ? 'mahasiswa.sempro.index' : 'mahasiswa.skripsi.index';

            $totalDays = max(1, (int) $wave['tanggal_mulai']->diffInDays($wave['tanggal_selesai']));
            $usedDays  = $wave['is_open'] ? max(0, $totalDays - $wave['days_until_close']) : 0;
            $barPct    = $wave['is_open'] ? min(100, round($usedDays / $totalDays * 100)) : 0;

            if ($wave['is_closing_soon']) {
                $borderColor = 'border-l-rose-500';
                $numColor    = 'text-rose-600 dark:text-rose-400';
                $numBg       = 'bg-rose-50 dark:bg-rose-950/40';
                $badgeColor  = 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300';
                $barColor    = 'bg-rose-500';
                $barTrack    = 'bg-rose-100 dark:bg-rose-900/40';
                $iconBg      = 'bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400';
                $badgeLabel  = '⚡ Segera Tutup';
                $statusMsg   = 'Tutup dalam';
                $days        = $wave['days_until_close'];
                $ctaColor    = 'bg-rose-600 hover:bg-rose-700 text-white';
                $ctaLabel    = 'Daftar Sekarang';
                $subMsg      = 'Tutup: ' . $wave['tanggal_selesai']->translatedFormat('d M Y') . ' — Segera lengkapi berkas!';
            } elseif ($wave['is_open']) {
                $borderColor = 'border-l-emerald-500';
                $numColor    = 'text-emerald-600 dark:text-emerald-400';
                $numBg       = 'bg-emerald-50 dark:bg-emerald-950/40';
                $badgeColor  = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
                $barColor    = 'bg-emerald-500';
                $barTrack    = 'bg-emerald-100 dark:bg-emerald-900/40';
                $iconBg      = 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400';
                $badgeLabel  = '● Sedang Dibuka';
                $statusMsg   = 'Sisa pendaftaran';
                $days        = $wave['days_until_close'];
                $ctaColor    = 'bg-emerald-600 hover:bg-emerald-700 text-white';
                $ctaLabel    = 'Daftar Sekarang';
                $subMsg      = $wave['tanggal_mulai']->format('d M') . ' – ' . $wave['tanggal_selesai']->format('d M Y');
            } else {
                $borderColor = 'border-l-indigo-500';
                $numColor    = 'text-indigo-600 dark:text-indigo-400';
                $numBg       = 'bg-indigo-50 dark:bg-indigo-950/40';
                $badgeColor  = 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300';
                $barColor    = 'bg-indigo-400';
                $barTrack    = 'bg-indigo-100 dark:bg-indigo-900/40';
                $iconBg      = 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400';
                $badgeLabel  = '🗓 Segera Dibuka';
                $statusMsg   = 'Dibuka dalam';
                $days        = $wave['days_until_open'];
                $ctaColor    = 'bg-indigo-600 hover:bg-indigo-700 text-white';
                $ctaLabel    = 'Lihat Info';
                $subMsg      = 'Mulai ' . $wave['tanggal_mulai']->translatedFormat('l, d M Y');
            }
        @endphp

        <div class="bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 border-l-4 {{ $borderColor }} rounded-2xl shadow-sm px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5">
            <div class="flex items-center gap-4 sm:gap-5 flex-1 min-w-0">
                {{-- Angka countdown --}}
                <div class="{{ $numBg }} rounded-xl px-4 py-3 text-center shrink-0 min-w-[72px]">
                    <p class="text-3xl font-black leading-none {{ $numColor }}">{{ $days }}</p>
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-0.5 uppercase tracking-wider">hari</p>
                </div>

                {{-- Info tengah --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full {{ $badgeColor }} tracking-wide">
                            {{ $badgeLabel }}
                        </span>
                        <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">
                            Gelombang {{ $wave['gelombang'] }}
                        </span>
                    </div>
                    <p class="text-sm font-extrabold text-slate-800 dark:text-slate-100 leading-snug">{{ $label }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">{{ $statusMsg }} · {{ $subMsg }}</p>

                    @if($wave['is_open'])
                    <div class="mt-2">
                        <div class="h-1.5 rounded-full {{ $barTrack }} overflow-hidden">
                            <div class="h-full rounded-full {{ $barColor }} transition-all duration-700" style="width: {{ $barPct }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- CTA --}}
            <a href="{{ route($routeName) }}"
               class="shrink-0 w-full sm:w-auto text-center px-4 py-2 rounded-xl text-xs font-extrabold shadow-sm transition-all whitespace-nowrap {{ $ctaColor }}">
                {{ $ctaLabel }} →
            </a>
        </div>
        @endforeach
    </div>
    @endif


    {{-- Graduation Celebration Banner --}}

    @if($user->status_kelulusan === 'lulus')
        <div class="relative overflow-hidden rounded-3xl p-7 sm:p-9 text-center shadow-xl bg-emerald-600 dark:bg-emerald-700">
            <!-- Confetti particles -->
            <div class="confetti-layer" aria-hidden="true">
                @foreach(['#fbbf24','#f472b6','#34d399','#60a5fa','#f87171','#a78bfa','#fbbf24','#34d399','#60a5fa','#f472b6','#fbbf24','#a78bfa'] as $i => $color)
                    <span class="confetti-piece" style="left: {{ (($i + 1) * 8) - 2 }}%; background: {{ $color }}; animation-delay: {{ $i * 0.35 }}s;"></span>
                @endforeach
            </div>

            <div class="relative z-10">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/15 border border-white/25 mb-4 grad-cap-float">
                    <x-icon name="academic" class="w-7 h-7" />
                </div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-amber-300 mb-2">Selamat &amp; Sukses</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mb-3">
                    Selamat, {{ $user->name }}! Anda Telah Dinyatakan <span class="text-amber-300">LULUS</span> 
                </h2>
                <p class="max-w-2xl mx-auto text-sm sm:text-[15px] text-emerald-50 leading-relaxed">
                    Perjalanan panjang menyusun proposal, penelitian, hingga mempertahankannya di meja sidang kini telah usai.
                    Terima kasih telah menjadi bagian dari keluarga besar <strong class="text-white">Program Studi Teknik Informatika, Universitas Muria Kudus</strong>.
                    Semoga ilmu dan pengalaman yang telah diperoleh menjadi bekal berharga untuk melangkah lebih jauh.
                    Sukses selalu untuk langkah berikutnya — kami bangga pernah menjadi bagian dari perjalananmu. <x-icon name="academic" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" />
                </p>
            </div>
        </div>

        <style>
            .confetti-layer {
                position: absolute;
                inset: 0;
                pointer-events: none;
                overflow: hidden;
            }
            .confetti-piece {
                position: absolute;
                top: -12px;
                width: 8px;
                height: 8px;
                border-radius: 2px;
                opacity: 0.9;
                animation: confetti-fall 4.5s linear infinite;
            }
            @keyframes confetti-fall {
                0%   { transform: translateY(0) rotate(0deg); opacity: 0.9; }
                85%  { opacity: 0.9; }
                100% { transform: translateY(220px) rotate(360deg); opacity: 0; }
            }
            .grad-cap-float {
                animation: grad-cap-float 2.6s ease-in-out infinite;
            }
            @keyframes grad-cap-float {
                0%, 100% { transform: translateY(0) rotate(-4deg); }
                50%      { transform: translateY(-8px) rotate(4deg); }
            }
            @media (prefers-reduced-motion: reduce) {
                .confetti-piece, .grad-cap-float { animation: none !important; }
            }
        </style>
    @endif

    {{-- Progress pendaftaran Sempro & Skripsi (5 tahap per track) --}}
    <div class="grid grid-cols-1 gap-5 sm:gap-6">
        <x-progress-tugas-akhir :progress="$progressSempro" />
        <x-progress-tugas-akhir :progress="$progressSkripsi" />
    </div>

    <!-- Informasi & Tata Tertib Persiapan Sidang -->
    <x-info-persiapan-sidang />

    <!-- Status Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Active Period Card -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-2">
            <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Periode Akademik Aktif</p>
            @if($activePeriode)
                <div class="flex items-center gap-2 mt-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                    <p class="text-base font-extrabold text-slate-800 dark:text-slate-100">{{ $activePeriode->nama_periode }}</p>
                </div>
                <p class="text-xs text-indigo-600 dark:text-indigo-400 font-extrabold mt-1">Status: Periode Akademik Berjalan</p>
            @else
                <p class="text-xs text-slate-400 dark:text-slate-500 italic">Belum ada periode aktif.</p>
            @endif
        </div>

        <!-- Sidang Skripsi Card -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-2">
            <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status Sidang Skripsi</p>
            @if($sidangSkripsi)
                <div class="flex flex-wrap items-center justify-between gap-2">
                    @php
                        $statusText = 'Belum Diverifikasi';
                        $statusClass = 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-250 dark:border-amber-700/50';
                        if (($sidangSkripsi->verifikasi_status ?? 'menunggu') === 'disetujui') {
                            $statusText = 'Terverifikasi Koordinator';
                            $statusClass = 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-250 dark:border-emerald-700/50';
                        } elseif (($sidangSkripsi->verifikasi_status ?? 'menunggu') === 'ditolak') {
                            $statusText = 'Ditolak';
                            $statusClass = 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-250 dark:border-rose-700/50';
                        }
                    @endphp
                    <span class="px-2.5 py-1 {{ $statusClass }} text-[10px] font-extrabold rounded-full">
                        {{ $statusText }}
                    </span>
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ optional($sidangSkripsi->tanggal)->translatedFormat('l, d/m/Y') ?? '-' }}</span>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-300 truncate font-semibold" title="{{ $sidangSkripsi->judul_skripsi }}">{{ $sidangSkripsi->judul_skripsi }}</p>
                @if(($sidangSkripsi->verifikasi_status ?? 'menunggu') === 'ditolak' && $sidangSkripsi->verifikasi_komentar)
                    <p class="text-[10px] text-rose-500 dark:text-rose-400 font-medium">Catatan: {{ $sidangSkripsi->verifikasi_komentar }}</p>
                @endif
            @else
                <p class="text-xs text-slate-400 dark:text-slate-500 italic">Belum ada pendaftaran sidang skripsi.</p>
            @endif
        </div>

        <!-- Sidang Jurnal Card -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-700 shadow-sm space-y-2">
            <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status Sidang Jurnal</p>
            @if($sidangJurnal)
                <div class="flex flex-wrap items-center justify-between gap-2">
                    @php
                        $statusText = 'Belum Diverifikasi';
                        $statusClass = 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-250 dark:border-amber-700/50';
                        if (($sidangJurnal->verifikasi_status ?? 'menunggu') === 'disetujui') {
                            $statusText = 'Terverifikasi Koordinator';
                            $statusClass = 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-250 dark:border-emerald-700/50';
                        } elseif (($sidangJurnal->verifikasi_status ?? 'menunggu') === 'ditolak') {
                            $statusText = 'Ditolak';
                            $statusClass = 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-250 dark:border-rose-700/50';
                        }
                    @endphp
                    <span class="px-2.5 py-1 {{ $statusClass }} text-[10px] font-extrabold rounded-full">
                        {{ $statusText }}
                    </span>
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ optional($sidangJurnal->tanggal)->translatedFormat('l, d/m/Y') ?? '-' }}</span>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-300 truncate font-semibold" title="{{ $sidangJurnal->judul_skripsi }}">{{ $sidangJurnal->judul_skripsi }}</p>
                @if(($sidangJurnal->verifikasi_status ?? 'menunggu') === 'ditolak' && $sidangJurnal->verifikasi_komentar)
                    <p class="text-[10px] text-rose-500 dark:text-rose-400 font-medium">Catatan: {{ $sidangJurnal->verifikasi_komentar }}</p>
                @endif
            @else
                <p class="text-xs text-slate-400 dark:text-slate-500 italic">Belum ada pendaftaran sidang jurnal.</p>
            @endif
        </div>
    </div>

    <!-- Recent Sidang List -->
    <div class="bg-white dark:bg-slate-800/80 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-4 sm:p-6">
        <h2 class="text-base sm:text-lg font-extrabold text-slate-800 dark:text-slate-100 mb-4">Riwayat Sidang &amp; Tugas Akhir</h2>
        @if($sidangs->isEmpty())
            <div class="text-center py-8 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                <p class="text-sm text-slate-500 dark:text-slate-400 italic">Belum ada data riwayat sidang.</p>
            </div>
        @else
            {{-- Mobile: kartu --}}
            <div class="space-y-3 md:hidden">
                @foreach($sidangs as $i => $s)
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900/40 p-3.5">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ $s->jenis_tugas_akhir }}</span>
                            {!! $s->getVerifikasiStatusHtml() !!}
                        </div>
                        <p class="text-[13px] font-bold text-slate-800 dark:text-slate-100 leading-snug line-clamp-2">{{ $s->judul_skripsi }}</p>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                            <span><x-icon name="calendar" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ optional($s->tanggal)->format('d M Y') ?? 'Belum dijadwalkan' }}</span>
                            <span><x-icon name="building" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ $s->ruang->kode_ruangan ?? $s->ruang->nama ?? '-' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop: tabel --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                            <th class="py-3 px-4 font-bold">#</th>
                            <th class="py-3 px-4 font-bold">Jenis</th>
                            <th class="py-3 px-4 font-bold">Judul</th>
                            <th class="py-3 px-4 font-bold">Tanggal</th>
                            <th class="py-3 px-4 font-bold">Ruang</th>
                            <th class="py-3 px-4 font-bold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-sm font-medium">
                        @foreach($sidangs as $i => $s)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-900/40">
                                <td class="py-3 px-4 text-slate-400 text-xs">{{ $i + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-100 capitalize">{{ $s->jenis_tugas_akhir }}</td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300 max-w-xs truncate" title="{{ $s->judul_skripsi }}">{{ $s->judul_skripsi }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ optional($s->tanggal)->format('d M Y') ?? '-' }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $s->ruang->kode_ruangan ?? $s->ruang->nama ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    {!! $s->getVerifikasiStatusHtml() !!}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
</x-app-layout>

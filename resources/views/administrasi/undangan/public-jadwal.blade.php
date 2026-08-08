<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Menguji - {{ $dosen->nama_dosen }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen p-4 sm:p-6 flex flex-col justify-between">
    <div class="max-w-4xl mx-auto w-full space-y-6">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 p-6 rounded-3xl border border-indigo-500/30 shadow-2xl relative overflow-hidden">
            <div class="absolute -top-12 -right-12 w-44 h-44 bg-indigo-500/10 rounded-full blur-2xl"></div>
            
            <div class="flex items-center gap-3 mb-3">
                <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 text-xs font-bold rounded-full border border-indigo-400/30">
                    Jadwal Menguji Publik
                </span>
                @if($activePeriode)
                    <span class="text-xs text-slate-400">Periode: {{ $activePeriode->nama_periode }}</span>
                @endif
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                {{ $dosen->nama_dosen }}
            </h1>
            <p class="text-xs text-indigo-200 mt-1 font-mono">NIDN: {{ $dosen->nidn ?? '-' }}</p>
        </div>

        @if($mySidangs->isNotEmpty())
        {{-- ══ RINGKASAN PER HARI ══ --}}
        @php
            // Group sidangs by date
            $groupedByDate = $mySidangs->filter(fn($s) => $s->tanggal)->groupBy(fn($s) => $s->tanggal->format('Y-m-d'))->sortKeys();
        @endphp
        <div class="bg-slate-800/80 rounded-3xl p-5 sm:p-6 border border-slate-700/80 shadow-lg space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-700/60">
                <div class="w-9 h-9 rounded-2xl bg-amber-500/20 text-amber-300 flex items-center justify-center text-lg shrink-0">
                    📊
                </div>
                <div>
                    <h2 class="text-sm font-extrabold text-white uppercase tracking-wider">Ringkasan Jadwal Per Hari</h2>
                    <p class="text-[11px] text-slate-400">Total {{ $mySidangs->count() }} mahasiswa diuji dalam {{ $groupedByDate->count() }} hari</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($groupedByDate as $dateKey => $sidangsInDay)
                    @php
                        $dateCarbon = \Carbon\Carbon::parse($dateKey)->locale('id');
                        $hariNama = $dateCarbon->isoFormat('dddd, D MMMM Y');
                        $totalMhs = $sidangsInDay->count();

                        // Parse jam values to find earliest start and latest end
                        $jamValues = $sidangsInDay->pluck('jam')->filter()->values();
                        $jamMulai = '-';
                        $jamSelesai = '-';
                        if ($jamValues->isNotEmpty()) {
                            $starts = [];
                            $ends = [];
                            foreach ($jamValues as $j) {
                                // Format: "08.00 - 09.00" or "08:00 - 09:00"
                                $parts = preg_split('/\s*[-–]\s*/', $j);
                                if (count($parts) >= 1) $starts[] = trim($parts[0]);
                                if (count($parts) >= 2) $ends[] = trim($parts[1]);
                            }
                            sort($starts);
                            rsort($ends);
                            $jamMulai = $starts[0] ?? '-';
                            $jamSelesai = $ends[0] ?? '-';
                        }
                    @endphp
                    <div class="bg-slate-900/80 rounded-2xl p-4 border border-slate-700/50 space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-extrabold text-white">📅 {{ $hariNama }}</p>
                            <span class="px-2.5 py-0.5 bg-indigo-500/20 text-indigo-300 text-[11px] font-extrabold rounded-full border border-indigo-500/30">
                                {{ $totalMhs }} Mahasiswa
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-[11px]">
                            <div class="flex items-center gap-1.5">
                                <span class="text-emerald-400 font-bold">▶</span>
                                <span class="text-slate-400">Mulai:</span>
                                <span class="font-extrabold text-emerald-400">{{ $jamMulai }} WIB</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-rose-400 font-bold">■</span>
                                <span class="text-slate-400">Selesai:</span>
                                <span class="font-extrabold text-rose-400">{{ $jamSelesai }} WIB</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Schedule List -->
        <div class="space-y-4">
            <div class="flex items-center justify-between px-2">
                <h2 class="text-sm font-extrabold text-slate-300 uppercase tracking-wider">Daftar Jadwal Menguji / Membimbing</h2>
                <span class="text-xs text-slate-400 font-bold bg-slate-800 px-3 py-1 rounded-full border border-slate-700">
                    {{ $mySidangs->count() }} Sidang
                </span>
            </div>

            @if($mySidangs->isEmpty())
                <div class="bg-slate-800/60 rounded-3xl p-10 border border-slate-700/60 text-center space-y-2">
                    <span class="text-4xl">📅</span>
                    <h3 class="text-base font-bold text-slate-200">Belum Ada Jadwal</h3>
                    <p class="text-xs text-slate-400">Tidak ada jadwal ujian/sidang yang terplotting untuk Anda saat ini.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($mySidangs as $s)
                        @php
                            $role = '-';
                            $roleBadgeClass = 'bg-slate-800 text-slate-300 border-slate-700';
                            if ($s->ketua_penguji_id == $dosen->id) {
                                $role = 'Ketua Penguji';
                                $roleBadgeClass = 'bg-amber-500/20 text-amber-300 border-amber-500/30';
                            } elseif ($s->anggota_penguji_1_id == $dosen->id) {
                                $role = 'Penguji 1';
                                $roleBadgeClass = 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30';
                            } elseif ($s->anggota_penguji_2_id == $dosen->id) {
                                $role = 'Penguji 2';
                                $roleBadgeClass = 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30';
                            } elseif ($s->dosen_pembimbing_utama_id == $dosen->id) {
                                $role = 'Pembimbing Utama';
                                $roleBadgeClass = 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30';
                            } elseif ($s->dosen_pembimbing_pendamping_id == $dosen->id) {
                                $role = 'Pembimbing Pendamping';
                                $roleBadgeClass = 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30';
                            }
                        @endphp
                        <div class="bg-slate-800/80 rounded-3xl p-5 sm:p-6 border border-slate-700/80 shadow-lg space-y-4 hover:border-indigo-500/50 transition-all">
                            <!-- Top Info -->
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-700/60 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 text-xs font-extrabold rounded-xl border {{ $roleBadgeClass }}">
                                        {{ $role }}
                                    </span>
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-xl bg-slate-700/60 text-slate-300 border border-slate-600/50 uppercase">
                                        {{ $s->jenis_tugas_akhir }}
                                    </span>
                                </div>
                                <div class="text-xs font-mono text-slate-400">
                                    NIM: {{ $s->nim }}
                                </div>
                            </div>

                            <!-- Student & Title -->
                            <div>
                                <h3 class="text-base font-extrabold text-white">
                                    {{ $s->nama_mahasiswa }}
                                </h3>
                                <p class="text-xs text-slate-300 mt-1 italic leading-relaxed">
                                    "{{ $s->judul_skripsi }}"
                                </p>
                            </div>

                            <!-- Time & Room Details -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                <div class="bg-slate-900/80 p-3 rounded-2xl border border-slate-700/50 flex items-center gap-3">
                                    <span class="text-xl">📅</span>
                                    <div>
                                        <p class="text-[10px] text-slate-400 uppercase font-bold">Hari & Waktu</p>
                                        <p class="text-xs font-extrabold text-white">
                                            {{ $s->tanggal ? $s->tanggal->locale('id')->isoFormat('dddd, D MMMM Y') : 'Belum di-plotting' }}
                                        </p>
                                        <p class="text-xs font-bold text-indigo-400">
                                            {{ $s->jam ?? '-' }} WIB
                                        </p>
                                    </div>
                                </div>

                                <div class="bg-slate-900/80 p-3 rounded-2xl border border-slate-700/50 flex items-center gap-3">
                                    <span class="text-xl">📍</span>
                                    <div>
                                        <p class="text-[10px] text-slate-400 uppercase font-bold">Ruangan Ujian</p>
                                        <p class="text-xs font-extrabold text-emerald-400">
                                            {{ $s->ruang ? $s->ruang->kode_ruangan . ' (' . $s->ruang->nama_ruangan . ')' : 'Belum ditentukan' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <footer class="text-center text-xs text-slate-500 mt-8 py-4">
        &copy; {{ date('Y') }} Sistem Informasi Skripsi TI - Universitas Muria Kudus
    </footer>
</body>
</html>

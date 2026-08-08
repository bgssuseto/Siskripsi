<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Dosen Penguji - Publik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen p-4 sm:p-6 flex flex-col justify-between">
    <div class="max-w-4xl mx-auto w-full space-y-6">
        
        <!-- Header Portal -->
        <div class="bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 p-6 rounded-3xl border border-indigo-500/30 shadow-2xl relative overflow-hidden">
            <div class="absolute -top-12 -right-12 w-44 h-44 bg-indigo-500/10 rounded-full blur-2xl"></div>
            
            <div class="flex items-center gap-3 mb-3">
                <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 text-xs font-bold rounded-full border border-indigo-400/30 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Pencarian Publik
                </span>
                <span class="text-xs text-slate-400">Jadwal Menguji Dosen</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                Daftar Ujian & Sidang Dosen Penguji
            </h1>
            <p class="text-xs text-indigo-200 mt-1">Cari dan lihat jadwal menguji dosen berdasarkan nama dosen, gelombang/periode, dan jenis sidang.</p>
        </div>

        <!-- Filter Form Card -->
        <div class="bg-slate-800/90 rounded-3xl p-5 sm:p-6 border border-slate-700/80 shadow-xl space-y-4">
            <h2 class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter Pencarian
            </h2>

            <form method="GET" action="{{ route('public.jadwal-dosen-penguji') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Dropdown Nama Dosen -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-300">
                        Nama Dosen Penguji <span class="text-rose-400">*</span>
                    </label>
                    <select name="dosen_id" onchange="this.form.submit()" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-slate-900 text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">-- Pilih Dosen Penguji --</option>
                        @foreach($dosens as $d)
                            <option value="{{ $d->id }}" {{ (string)$selectedDosenId === (string)$d->id ? 'selected' : '' }}>
                                {{ $d->nama_dosen }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dropdown Gelombang / Periode (Tidak Wajib) -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-300">
                        Gelombang / Periode <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <select name="periode_id" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-slate-900 text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Semua Gelombang / Periode</option>
                        @foreach($periodes as $p)
                            <option value="{{ $p->id }}" {{ (string)$selectedPeriodeId === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dropdown Jenis Sidang -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-300">
                        Jenis Sidang <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <select name="jenis" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-slate-900 text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="" {{ empty($selectedJenis) ? 'selected' : '' }}>Semua Jenis Sidang</option>
                        <option value="sempro" {{ $selectedJenis === 'sempro' ? 'selected' : '' }}>Seminar Proposal (Sempro)</option>
                        <option value="skripsi" {{ $selectedJenis === 'skripsi' ? 'selected' : '' }}>Sidang Skripsi / Jurnal</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Filter Info / Status Banner -->
        @if($selectedDosen)
            <div class="bg-indigo-950/60 border border-indigo-500/30 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-0.5">
                    <p class="text-xs text-indigo-300 font-bold">Menampilkan Jadwal Menguji Untuk:</p>
                    <h3 class="text-base font-extrabold text-white">{{ $selectedDosen->nama_dosen }}</h3>
                    <p class="text-[11px] font-mono text-slate-400">NIDN: {{ $selectedDosen->nidn ?? '-' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1.5 bg-indigo-500/20 text-indigo-300 text-xs font-extrabold rounded-xl border border-indigo-500/40">
                        {{ $mySidangs->count() }} Ujian Ditemukan
                    </span>
                    @if($selectedDosen->public_token)
                        <a href="{{ $selectedDosen->public_url }}" target="_blank" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Link Publik Langsung
                        </a>
                    @endif
                </div>
            </div>

            <!-- Result Content -->
            @if($mySidangs->isNotEmpty())
                @php
                    $groupedByDate = $mySidangs->filter(fn($s) => $s->tanggal)->groupBy(fn($s) => $s->tanggal->format('Y-m-d'))->sortKeys();
                    $unplottedSidangs = $mySidangs->filter(fn($s) => !$s->tanggal);
                @endphp

                <!-- Ringkasan Per Hari -->
                <div class="bg-slate-800/80 rounded-3xl p-5 sm:p-6 border border-slate-700/80 shadow-lg space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-slate-700/60">
                        <div class="w-9 h-9 rounded-2xl bg-amber-500/20 text-amber-300 flex items-center justify-center text-lg shrink-0">
                            📊
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-white uppercase tracking-wider">Ringkasan Hari Menguji</h2>
                            <p class="text-[11px] text-slate-400">Total {{ $mySidangs->count() }} mahasiswa diuji dalam {{ $groupedByDate->count() }} hari</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($groupedByDate as $dateKey => $sidangsInDay)
                            @php
                                $dateCarbon = \Carbon\Carbon::parse($dateKey)->locale('id');
                                $hariNama = $dateCarbon->isoFormat('dddd, D MMMM Y');
                                $totalMhs = $sidangsInDay->count();

                                $jamValues = $sidangsInDay->pluck('jam')->filter()->values();
                                $jamMulai = '-';
                                $jamSelesai = '-';
                                if ($jamValues->isNotEmpty()) {
                                    $starts = [];
                                    $ends = [];
                                    foreach ($jamValues as $j) {
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

                <!-- Detail Jadwal per Hari -->
                <div class="space-y-4">
                    @foreach($groupedByDate as $dateKey => $sidangsInDay)
                        @php
                            $dateCarbon = \Carbon\Carbon::parse($dateKey)->locale('id');
                            $hariNama = $dateCarbon->isoFormat('dddd, D MMMM Y');
                        @endphp
                        <div x-data="{ open: true }" class="bg-slate-800/60 rounded-3xl border border-slate-700/80 overflow-hidden shadow-lg">
                            <button @click="open = !open" type="button" class="w-full px-5 py-4 bg-slate-800 hover:bg-slate-750 flex items-center justify-between transition-colors text-left border-b border-slate-700/50">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg">📅</span>
                                    <div>
                                        <h3 class="text-sm font-extrabold text-white">{{ $hariNama }}</h3>
                                        <p class="text-[11px] text-slate-400">{{ $sidangsInDay->count() }} Mahasiswa Diuji</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-slate-400 transform transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <div x-show="open" class="p-4 sm:p-5 space-y-4 bg-slate-900/40">
                                @foreach($sidangsInDay as $s)
                                    @php
                                        $role = '-';
                                        $roleBadgeClass = 'bg-slate-800 text-slate-300 border-slate-700';
                                        if ($s->ketua_penguji_id == $selectedDosen->id) {
                                            $role = 'Ketua Penguji';
                                            $roleBadgeClass = 'bg-amber-500/20 text-amber-300 border-amber-500/30';
                                        } elseif ($s->anggota_penguji_1_id == $selectedDosen->id) {
                                            $role = 'Penguji 1';
                                            $roleBadgeClass = 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30';
                                        } elseif ($s->anggota_penguji_2_id == $selectedDosen->id) {
                                            $role = 'Penguji 2';
                                            $roleBadgeClass = 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30';
                                        }
                                    @endphp
                                    <div class="bg-slate-800/90 rounded-2xl p-5 border border-slate-700/80 shadow-md space-y-4 hover:border-indigo-500/50 transition-all">
                                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-700/60 pb-3">
                                            <div class="flex items-center gap-2">
                                                <span class="px-3 py-1 text-xs font-extrabold rounded-xl border {{ $roleBadgeClass }}">
                                                    Peran: {{ $role }}
                                                </span>
                                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-xl bg-slate-700/60 text-slate-300 border border-slate-600/50 uppercase">
                                                    {{ $s->jenis_tugas_akhir }}
                                                </span>
                                            </div>
                                            <div class="text-xs font-mono text-slate-400">
                                                NIM: <strong class="text-slate-200">{{ $s->nim }}</strong>
                                            </div>
                                        </div>

                                        <div>
                                            <h4 class="text-base font-extrabold text-white">
                                                {{ $s->nama_mahasiswa }}
                                            </h4>
                                            <p class="text-xs text-slate-300 mt-1 italic leading-relaxed">
                                                "{{ $s->judul_skripsi }}"
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-700/50 flex items-center gap-3">
                                                <span class="text-xl">⏰</span>
                                                <div>
                                                    <p class="text-[10px] text-slate-400 uppercase font-bold">Waktu Ujian</p>
                                                    <p class="text-xs font-extrabold text-indigo-400">
                                                        {{ $s->jam ?? '-' }} WIB
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-700/50 flex items-center gap-3">
                                                <span class="text-xl">📍</span>
                                                <div>
                                                    <p class="text-[10px] text-slate-400 uppercase font-bold">Ruangan Ujian</p>
                                                    <p class="text-xs font-extrabold text-emerald-400">
                                                        {{ $s->ruang ? $s->ruang->kode_ruangan . ' (' . $s->ruang->nama_ruangan . ')' : 'Belum ditentukan' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-700/40 space-y-2">
                                            <p class="text-[10.5px] font-extrabold text-indigo-300 uppercase tracking-wider flex items-center gap-1.5">
                                                <span>👥</span> Tim Dewan Penguji
                                            </p>
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="text-amber-400 font-bold text-[10px] uppercase">Ketua Penguji:</span>
                                                    <span class="text-slate-200 font-medium">{{ $s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : '-' }}</span>
                                                </div>
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="text-indigo-400 font-bold text-[10px] uppercase">Anggota Penguji 1:</span>
                                                    <span class="text-slate-200 font-medium">{{ $s->anggotaPenguji1 ? $s->anggotaPenguji1->nama_dosen : '-' }}</span>
                                                </div>
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="text-indigo-400 font-bold text-[10px] uppercase">Anggota Penguji 2:</span>
                                                    <span class="text-slate-200 font-medium">{{ $s->anggotaPenguji2 ? $s->anggotaPenguji2->nama_dosen : '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if(isset($unplottedSidangs) && $unplottedSidangs->isNotEmpty())
                        <div x-data="{ open: true }" class="bg-slate-800/60 rounded-3xl border border-slate-700/80 overflow-hidden shadow-lg">
                            <button @click="open = !open" type="button" class="w-full px-5 py-4 bg-slate-800 hover:bg-slate-750 flex items-center justify-between transition-colors text-left border-b border-slate-700/50">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg">⏳</span>
                                    <div>
                                        <h3 class="text-sm font-extrabold text-amber-400">Belum Memiliki Tanggal (Belum Plotting)</h3>
                                        <p class="text-[11px] text-slate-400">{{ $unplottedSidangs->count() }} Mahasiswa</p>
                                    </div>
                                </div>
                            </button>

                            <div x-show="open" class="p-4 sm:p-5 space-y-4 bg-slate-900/40">
                                @foreach($unplottedSidangs as $s)
                                    <div class="bg-slate-800/90 rounded-2xl p-5 border border-slate-700/80 shadow-md space-y-3">
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="px-2.5 py-0.5 rounded bg-amber-500/20 text-amber-300 font-bold">Belum Di-Plotting</span>
                                            <span class="text-slate-400 font-mono">NIM: {{ $s->nim }}</span>
                                        </div>
                                        <h4 class="text-sm font-bold text-white">{{ $s->nama_mahasiswa }}</h4>
                                        <p class="text-xs text-slate-300 italic">"{{ $s->judul_skripsi }}"</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-slate-800/60 rounded-3xl p-10 border border-slate-700/60 text-center space-y-2">
                    <span class="text-4xl">🔍</span>
                    <h3 class="text-base font-bold text-slate-200">Tidak Ada Jadwal Menguji</h3>
                    <p class="text-xs text-slate-400">Dosen yang Anda pilih tidak memiliki jadwal menguji pada filter gelombang/periode atau jenis sidang ini.</p>
                </div>
            @endif
        @else
            <!-- Placeholder state before choosing a lecturer -->
            <div class="bg-slate-800/40 rounded-3xl p-12 border border-slate-700/40 text-center space-y-3">
                <div class="w-16 h-16 bg-indigo-500/10 text-indigo-400 rounded-full flex items-center justify-center mx-auto text-2xl">
                    👨‍🏫
                </div>
                <h3 class="text-base font-bold text-slate-200">Silakan Pilih Nama Dosen Penguji</h3>
                <p class="text-xs text-slate-400 max-w-md mx-auto">
                    Gunakan dropdown di atas untuk memilih nama dosen yang ingin Anda lihat daftar jadwal mengujinya. Anda juga dapat menyaring berdasarkan gelombang atau jenis ujian.
                </p>
            </div>
        @endif

    </div>

    <footer class="text-center text-xs text-slate-500 mt-8 py-4">
        &copy; {{ date('Y') }} Sistem Informasi Skripsi TI - Universitas Muria Kudus
    </footer>
</body>
</html>

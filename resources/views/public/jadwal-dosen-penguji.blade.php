<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light dark">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Dosen Penguji - Publik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen p-4 sm:p-6 flex flex-col justify-between dark:text-slate-200 dark:bg-slate-950">
    <div class="max-w-4xl mx-auto w-full space-y-6">
        
        <!-- Header Portal -->
        <div class="bg-white p-6 rounded-3xl border border-indigo-200 shadow-sm relative overflow-hidden dark:bg-slate-800/80 dark:border-indigo-500/30">
            
            <div class="flex items-center gap-3 mb-3">
                <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-xs font-bold rounded-full border border-indigo-200 flex items-center gap-1.5 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-500/30">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Pencarian Publik
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400">Jadwal Menguji Dosen</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight dark:text-slate-100">
                Daftar Ujian & Sidang Dosen Penguji
            </h1>
            <p class="text-xs text-indigo-700 mt-1 dark:text-indigo-300">Cari dan lihat jadwal menguji dosen berdasarkan nama dosen, gelombang/periode, dan jenis sidang.</p>
        </div>

        <!-- Filter Form Card -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-4 dark:bg-slate-800/80 dark:border-slate-700">
            <h2 class="text-xs font-extrabold text-indigo-600 uppercase tracking-wider flex items-center gap-2 dark:text-indigo-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter Pencarian
            </h2>

            <form method="GET" action="{{ route('public.jadwal-dosen-penguji') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Dropdown Nama Dosen -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">
                        Nama Dosen Penguji <span class="text-rose-600 dark:text-rose-400">*</span>
                    </label>
                    <select name="dosen_id" onchange="this.form.submit()" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100">
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
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">
                        Gelombang / Periode <span class="text-slate-500 font-normal dark:text-slate-400">(Opsional)</span>
                    </label>
                    <select name="periode_id" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100">
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
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">
                        Jenis Sidang <span class="text-slate-500 font-normal dark:text-slate-400">(Opsional)</span>
                    </label>
                    <select name="jenis" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100">
                        <option value="" {{ empty($selectedJenis) ? 'selected' : '' }}>Semua Jenis Sidang</option>
                        <option value="sempro" {{ $selectedJenis === 'sempro' ? 'selected' : '' }}>Seminar Proposal (Sempro)</option>
                        <option value="skripsi" {{ $selectedJenis === 'skripsi' ? 'selected' : '' }}>Sidang Skripsi / Jurnal</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Filter Info / Status Banner -->
        @if($selectedDosen)
            <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 dark:bg-indigo-500/15 dark:border-indigo-500/30">
                <div class="space-y-0.5">
                    <p class="text-xs text-indigo-600 font-bold dark:text-indigo-300">Menampilkan Jadwal Menguji Untuk:</p>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100">{{ $selectedDosen->nama_dosen }}</h3>
                    <p class="text-[11px] font-mono text-slate-500 dark:text-slate-400">NIDN: {{ $selectedDosen->nidn ?? '-' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-3 py-1.5 bg-indigo-50 text-indigo-600 text-xs font-extrabold rounded-xl border border-indigo-500/40 dark:bg-indigo-500/15 dark:text-indigo-300">
                        {{ $mySidangs->count() }} Ujian Ditemukan
                    </span>
                    <a href="{{ route('public.jadwal-dosen-penguji.pdf', request()->all()) }}" target="_blank" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all inline-flex items-center gap-1.5 shadow-md">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download PDF
                    </a>
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
                @endphp

                <!-- Ringkasan Per Hari -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-lg space-y-4 dark:bg-slate-800/80 dark:border-slate-700">
                    <div class="flex items-center gap-3 pb-3 border-b border-slate-200 dark:border-slate-700">
                        <div class="w-9 h-9 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-lg shrink-0 dark:bg-amber-500/15 dark:text-amber-300">
                            <x-icon name="chart" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" />
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider dark:text-slate-100">Ringkasan Hari Menguji</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Total {{ $mySidangs->count() }} mahasiswa diuji dalam {{ $groupedByDate->count() }} hari</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($groupedByDate as $dateKey => $sidangsInDay)
                            @php
                                $dateCarbon = \Carbon\Carbon::parse($dateKey)->locale('id');
                                $hariNama = $dateCarbon->isoFormat('dddd, D MMMM Y');
                                $totalMhs = $sidangsInDay->count();

                                $ruangList = $sidangsInDay->map(fn($s) => $s->ruang ? $s->ruang->kode_ruangan : null)->filter()->unique()->values();
                                $ruangText = $ruangList->isNotEmpty() ? $ruangList->join(', ') : 'Belum ditentukan';

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
                            <div class="bg-white rounded-2xl p-4 border border-slate-200 space-y-2 dark:bg-slate-800/80 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-extrabold text-slate-900 dark:text-slate-100"><x-icon name="calendar" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ $hariNama }}</p>
                                    <span class="px-2.5 py-0.5 bg-indigo-50 text-indigo-600 text-[11px] font-extrabold rounded-full border border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-500/30">
                                        {{ $totalMhs }} Mahasiswa
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-amber-600 font-bold dark:text-amber-400"><x-icon name="map-pin" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></span>
                                        <span class="text-slate-500 dark:text-slate-400">Ruangan:</span>
                                        <span class="font-extrabold text-amber-700 dark:text-amber-300">{{ $ruangText }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-emerald-600 font-bold dark:text-emerald-400">▶</span>
                                        <span class="text-slate-500 dark:text-slate-400">Mulai:</span>
                                        <span class="font-extrabold text-emerald-600 dark:text-emerald-400">{{ $jamMulai }} WIB</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-rose-600 font-bold dark:text-rose-400">■</span>
                                        <span class="text-slate-500 dark:text-slate-400">Selesai:</span>
                                        <span class="font-extrabold text-rose-600 dark:text-rose-400">{{ $jamSelesai }} WIB</span>
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
                        <div x-data="{ open: true }" class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-lg dark:bg-slate-800/80 dark:border-slate-700">
                            <button @click="open = !open" type="button" class="w-full px-5 py-4 bg-white hover:bg-slate-750 flex items-center justify-between transition-colors text-left border-b border-slate-200 dark:bg-slate-800/80 dark:border-slate-700">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg"><x-icon name="calendar" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></span>
                                    <div>
                                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ $hariNama }}</h3>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $sidangsInDay->count() }} Mahasiswa Diuji</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-slate-500 transform transition-transform duration-200 dark:text-slate-400" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <div x-show="open" class="p-4 sm:p-5 space-y-4 bg-slate-50 dark:bg-slate-900/60">
                                @foreach($sidangsInDay as $s)
                                    @php
                                        $role = '-';
                                        $roleBadgeClass = 'bg-white text-slate-600 border-slate-200 dark:bg-slate-800/80 dark:text-slate-400 dark:border-slate-700';
                                        if ($s->ketua_penguji_id == $selectedDosen->id) {
                                            $role = 'Ketua Penguji';
                                            $roleBadgeClass = 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/30';
                                        } elseif ($s->anggota_penguji_1_id == $selectedDosen->id) {
                                            $role = 'Penguji 1';
                                            $roleBadgeClass = 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-500/30';
                                        } elseif ($s->anggota_penguji_2_id == $selectedDosen->id) {
                                            $role = 'Penguji 2';
                                            $roleBadgeClass = 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-500/30';
                                        }
                                    @endphp
                                    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-md space-y-4 hover:border-indigo-500/50 transition-all dark:bg-slate-800/80 dark:border-slate-700">
                                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3 dark:border-slate-700">
                                            <div class="flex items-center gap-2">
                                                <span class="px-3 py-1 text-xs font-extrabold rounded-xl border {{ $roleBadgeClass }}">
                                                    Peran: {{ $role }}
                                                </span>
                                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase dark:bg-indigo-500/15 dark:text-indigo-200 dark:border-indigo-500/30">
                                                    {{ $s->jenis_tugas_akhir }}
                                                </span>
                                            </div>
                                            <div class="text-xs font-mono text-slate-500 dark:text-slate-400">
                                                NIM: <strong class="text-slate-700 dark:text-slate-300">{{ $s->nim }}</strong>
                                            </div>
                                        </div>

                                        <div>
                                            <h4 class="text-base font-extrabold text-slate-900 dark:text-slate-100">
                                                {{ $s->nama_mahasiswa }}
                                            </h4>
                                            <p class="text-xs text-slate-600 mt-1 italic leading-relaxed dark:text-slate-400">
                                                "{{ $s->judul_skripsi }}"
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div class="bg-white p-3 rounded-xl border border-slate-200 flex items-center gap-3 dark:bg-slate-800/80 dark:border-slate-700">
                                                <span class="text-xl"><x-icon name="clock" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></span>
                                                <div>
                                                    <p class="text-[10px] text-slate-500 uppercase font-bold dark:text-slate-400">Waktu Ujian</p>
                                                    <p class="text-xs font-extrabold text-indigo-600 dark:text-indigo-300">
                                                        {{ $s->jam ?? '-' }} WIB
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="bg-white p-3 rounded-xl border border-slate-200 flex items-center gap-3 dark:bg-slate-800/80 dark:border-slate-700">
                                                <span class="text-xl"><x-icon name="map-pin" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></span>
                                                <div>
                                                    <p class="text-[10px] text-slate-500 uppercase font-bold dark:text-slate-400">Ruangan Ujian</p>
                                                    <p class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400">
                                                        {{ $s->ruang ? $s->ruang->kode_ruangan . ' (' . $s->ruang->nama_ruangan . ')' : 'Belum ditentukan' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 dark:bg-slate-900/60 dark:border-slate-700">
                                            <p class="text-[10.5px] font-extrabold text-indigo-600 uppercase tracking-wider flex items-center gap-1.5 dark:text-indigo-300">
                                                <span><x-icon name="users" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></span> Tim Dewan Penguji
                                            </p>
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="text-amber-600 font-bold text-[10px] uppercase dark:text-amber-400">Ketua Penguji:</span>
                                                    <span class="text-slate-700 font-medium dark:text-slate-300">{{ $s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : '-' }}</span>
                                                </div>
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="text-indigo-600 font-bold text-[10px] uppercase dark:text-indigo-300">Anggota Penguji 1:</span>
                                                    <span class="text-slate-700 font-medium dark:text-slate-300">{{ $s->anggotaPenguji1 ? $s->anggotaPenguji1->nama_dosen : '-' }}</span>
                                                </div>
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="text-indigo-600 font-bold text-[10px] uppercase dark:text-indigo-300">Anggota Penguji 2:</span>
                                                    <span class="text-slate-700 font-medium dark:text-slate-300">{{ $s->anggotaPenguji2 ? $s->anggotaPenguji2->nama_dosen : '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                </div>
            @else
                <div class="bg-white rounded-3xl p-10 border border-slate-200 text-center space-y-2 dark:bg-slate-800/80 dark:border-slate-700">
                    <span class="text-4xl"><x-icon name="search" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></span>
                    <h3 class="text-base font-bold text-slate-700 dark:text-slate-300">Tidak Ada Jadwal Menguji</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Dosen yang Anda pilih tidak memiliki jadwal menguji pada filter gelombang/periode atau jenis sidang ini.</p>
                </div>
            @endif
        @else
            <!-- Placeholder state before choosing a lecturer -->
            <div class="bg-slate-50 rounded-3xl p-12 border border-slate-200 text-center space-y-3 dark:bg-slate-900/60 dark:border-slate-700">
                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto text-2xl dark:bg-indigo-500/15 dark:text-indigo-300">
                    <x-icon name="user" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" />
                </div>
                <h3 class="text-base font-bold text-slate-700 dark:text-slate-300">Silakan Pilih Nama Dosen Penguji</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto dark:text-slate-400">
                    Gunakan dropdown di atas untuk memilih nama dosen yang ingin Anda lihat daftar jadwal mengujinya. Anda juga dapat menyaring berdasarkan gelombang atau jenis ujian.
                </p>
            </div>
        @endif

    </div>

    <footer class="text-center text-xs text-slate-500 mt-8 py-4 dark:text-slate-400">
        &copy; {{ date('Y') }} Sistem Informasi Skripsi TI - Universitas Muria Kudus
    </footer>
</body>
</html>

{{--
    Rincian jadwal satu undangan (setara tampilan link publik dosen):
    rekap sesi per hari & ruang, lalu linimasa per hari berisi mahasiswa,
    jam, ruang, peran Anda, dan susunan dewan penguji.
    Variabel: $sidangs (Collection<Sidang>), $dosen (Dosen)
--}}
@php
    $hariIni = now()->timezone('Asia/Jakarta')->startOfDay();
    $pecahJam = function (?string $jam) {
        if (!$jam) return [null, null];
        $bagian = preg_split('/\s*[-–]\s*/', trim($jam));
        return [$bagian[0] ?? null, $bagian[1] ?? null];
    };
    $perHari = $sidangs->groupBy(fn ($s) => $s->tanggal ? \Carbon\Carbon::parse($s->tanggal)->toDateString() : 'belum');

    // Rekap sesi: gabungkan per hari + ruang (jam mulai paling awal s/d selesai paling akhir).
    $rekap = $sidangs->filter(fn ($s) => $s->tanggal)
        ->groupBy(fn ($s) => \Carbon\Carbon::parse($s->tanggal)->toDateString() . '|' . ($s->ruang?->kode_ruangan ?? '-'))
        ->map(function ($g) use ($pecahJam) {
            $jam = $g->map(fn ($s) => $pecahJam($s->jam));
            return [
                'tanggal' => \Carbon\Carbon::parse($g->first()->tanggal)->locale('id'),
                'ruang'   => $g->first()->ruang?->kode_ruangan ?? 'Belum ditentukan',
                'mulai'   => $jam->pluck(0)->filter()->sort()->first(),
                'selesai' => $jam->pluck(1)->filter()->sort()->last(),
                'jumlah'  => $g->count(),
            ];
        })->values();

    $warnaPeran = [
        'Ketua Penguji'     => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30',
        'Anggota Penguji 1' => 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/30',
        'Anggota Penguji 2' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30',
    ];
@endphp

<div class="space-y-5">
    {{-- Rekap sesi per hari & ruang --}}
    @if($rekap->isNotEmpty())
        <div>
            <p class="mb-2 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rekap hari & ruang</p>
            <div class="flex flex-wrap gap-2">
                @foreach($rekap as $r)
                    <div class="w-full sm:w-auto inline-flex items-center gap-2.5 pl-1.5 pr-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-inset ring-slate-200 dark:ring-slate-700">
                        <span class="w-9 rounded-lg overflow-hidden text-center shrink-0 bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-700">
                            <span class="block text-[8px] font-extrabold uppercase text-white bg-rose-500 leading-[13px]">{{ $r['tanggal']->translatedFormat('M') }}</span>
                            <span class="block text-sm font-extrabold leading-5 text-slate-800 dark:text-slate-100">{{ $r['tanggal']->format('j') }}</span>
                        </span>
                        <span class="text-xs leading-tight">
                            <span class="block font-bold text-slate-800 dark:text-slate-100">{{ $r['tanggal']->translatedFormat('l') }} · {{ $r['ruang'] }}</span>
                            <span class="block text-slate-500 dark:text-slate-400">{{ $r['mulai'] ?? '—' }}{{ $r['selesai'] ? ' – ' . $r['selesai'] : '' }} WIB · {{ $r['jumlah'] }} mhs</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Linimasa per hari --}}
    <div class="space-y-4">
        @foreach($perHari as $kunci => $daftar)
            @php
                $tgl = $kunci === 'belum' ? null : \Carbon\Carbon::parse($kunci)->locale('id');
                $selisih = $tgl ? (int) $hariIni->diffInDays($tgl->copy()->startOfDay(), false) : null;
                [$labelHari, $clsHari] = match (true) {
                    $tgl === null   => ['Belum dijadwalkan', 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'],
                    $selisih < 0    => ['Selesai', 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'],
                    $selisih === 0  => ['Hari ini', 'bg-rose-500 text-white'],
                    $selisih === 1  => ['Besok', 'bg-amber-500 text-white'],
                    default         => ['H-' . $selisih, 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'],
                };
            @endphp
            <div>
                <div class="flex items-start gap-2 mb-2">
                    <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $selisih !== null && $selisih >= 0 ? 'bg-indigo-500' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                    <div class="min-w-0 flex flex-wrap items-center gap-x-2 gap-y-1">
                        <p class="text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ $tgl ? $tgl->translatedFormat('l, j F Y') : 'Tanggal belum ditentukan' }}</p>
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <span class="px-1.5 py-px rounded-md text-[10px] font-extrabold {{ $clsHari }}">{{ $labelHari }}</span>
                            <span class="text-[11px] text-slate-400">{{ $daftar->count() }} mahasiswa</span>
                        </span>
                    </div>
                </div>

                <ul class="ml-[3px] pl-3 sm:pl-4 border-l-2 border-dashed border-slate-200 dark:border-slate-700 space-y-2.5">
                    @foreach($daftar as $s)
                        @php
                            [$mulai, $selesai] = $pecahJam($s->jam);
                            $peran = $s->peranPengujiDosen($dosen->id);
                            $dewan = $s->dewanPengujiUndangan();
                        @endphp
                        <li class="rounded-2xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-700 hover:ring-indigo-300 dark:hover:ring-indigo-500/50 transition-all p-3.5 sm:p-4">
                            <div class="flex flex-col sm:flex-row gap-2.5 sm:gap-4">
                                {{-- Jam & ruang: baris di HP, kolom di layar lebar --}}
                                <div class="flex flex-wrap sm:flex-nowrap sm:flex-col items-center gap-x-2 gap-y-1.5 sm:gap-0 sm:w-16 shrink-0 sm:text-center min-w-0">
                                    <p class="text-sm font-extrabold text-indigo-700 dark:text-indigo-300 leading-tight whitespace-nowrap">{{ $mulai ?? '—' }}<span class="sm:hidden font-semibold text-slate-400">{{ $selesai ? ' – ' . $selesai : '' }}</span></p>
                                    @if($selesai)<p class="hidden sm:block text-[11px] text-slate-400 whitespace-nowrap">s/d {{ $selesai }}</p>@endif
                                    <p class="sm:mt-1.5 inline-flex items-center gap-0.5 px-1.5 py-px rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 whitespace-nowrap">
                                        <x-icon name="map-pin" class="w-3 h-3" />{{ $s->ruang?->kode_ruangan ?? '—' }}
                                    </p>
                                    @if($peran)
                                        <span class="sm:hidden inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-extrabold ring-1 ring-inset whitespace-nowrap {{ $warnaPeran[$peran] ?? $warnaPeran['Anggota Penguji 1'] }}">
                                            <x-icon name="user" class="w-3 h-3" /> {{ $peran }}
                                        </span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-extrabold text-slate-900 dark:text-slate-100 leading-snug break-words">{{ $s->nama_mahasiswa }}</p>
                                            <p class="text-[11px] font-mono font-semibold text-slate-500 dark:text-slate-400">{{ $s->nim }}</p>
                                        </div>
                                        @if($peran)
                                            <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-extrabold ring-1 ring-inset shrink-0 whitespace-nowrap {{ $warnaPeran[$peran] ?? $warnaPeran['Anggota Penguji 1'] }}">
                                                <x-icon name="user" class="w-3 h-3" /> {{ $peran }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1.5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed line-clamp-2 break-words" title="{{ $s->judul_skripsi }}">{{ $s->judul_skripsi }}</p>
                                    {{-- Dewan penguji: tiap baris "Peran — Nama" agar tidak bertumpuk di layar kecil --}}
                                    <ul class="mt-2.5 grid grid-cols-1 {{ count($dewan) === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }} gap-1.5">
                                        @foreach($dewan as $label => $nama)
                                            @php $diriSendiri = $peran !== null && $label === $peran; @endphp
                                            <li class="min-w-0 px-2.5 py-1.5 rounded-lg text-[11px] leading-snug {{ $diriSendiri ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-700 ring-1 ring-inset ring-slate-200 dark:bg-slate-800/60 dark:text-slate-200 dark:ring-slate-700' }}">
                                                <span class="block text-[9px] font-extrabold uppercase tracking-wider {{ $diriSendiri ? 'text-indigo-100' : 'text-slate-400' }}">{{ $label }}</span>
                                                <span class="block font-semibold break-words">{{ $diriSendiri ? 'Anda' : ($nama ?? '—') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>

<x-app-layout title="Dashboard Dosen">
    <x-slot:header>
        Dashboard
    </x-slot:header>

    <div x-data="kesediaanModalHandler()">
        @if(!$stats['is_linked'])
            <!-- Alert Profile Not Linked -->
            <div class="relative overflow-hidden rounded-3xl bg-amber-500/10 border border-amber-500/20 p-6 text-amber-800 dark:text-amber-300 shadow-sm mb-8">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm sm:text-base text-amber-950 dark:text-amber-200">Akun Belum Terhubung dengan Data Dosen</h4>
                        <p class="text-xs sm:text-sm mt-0.5 text-amber-900/80 dark:text-amber-300/80 leading-relaxed">
                            Akun pengguna Anda belum dihubungkan dengan data profil Master Dosen oleh Super Admin. Anda tidak akan melihat jadwal ujian atau bimbingan hingga profil Anda terhubung. Silakan hubungi Super Admin untuk penyesuaian data.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Sambutan + agenda terdekat -->
        @php
            $hariIni = now()->timezone('Asia/Jakarta')->startOfDay();
            $agendaBerikut = collect($stats['recent_schedules'])
                ->filter(fn ($x) => $x->tanggal && \Carbon\Carbon::parse($x->tanggal)->gte($hariIni))
                ->sortBy(fn ($x) => \Carbon\Carbon::parse($x->tanggal)->format('Ymd') . ($x->jam ?? ''))
                ->first();
            $agendaSambutan = $agendaBerikut ? [
                'jenis'   => $agendaBerikut->jenis_tugas_akhir === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi',
                'judul'   => $agendaBerikut->nama_mahasiswa,
                'sub'     => $agendaBerikut->judul_skripsi,
                'tanggal' => $agendaBerikut->tanggal,
                'jam'     => $agendaBerikut->jam ? $agendaBerikut->jam . ' WIB' : null,
                'ruang'   => $agendaBerikut->ruang?->kode_ruangan,
                'url'     => route($agendaBerikut->jenis_tugas_akhir === 'sempro' ? 'dosen.jadwal.sempro' : 'dosen.jadwal.skripsi'),
            ] : null;
            $jumlahMingguIni = collect($stats['recent_schedules'])->filter(fn ($x) => $x->tanggal && \Carbon\Carbon::parse($x->tanggal)->between($hariIni, $hariIni->copy()->addDays(6)->endOfDay()))->count();
        @endphp
        <x-sambutan :nama="Auth::user()->name" peran="Dosen"
                    :subjudul="$jumlahMingguIni ? 'Anda memiliki ' . $jumlahMingguIni . ' jadwal ujian dalam 7 hari ke depan. Semangat menguji!' : 'Pantau jadwal menguji, mahasiswa bimbingan, dan isi kesediaan menguji Anda dari sini.'"
                    :agenda="$agendaSambutan" kosong="Belum ada jadwal ujian yang akan datang. Waktunya rehat sejenak.">
            <x-slot:aksi>
                @if($showFormKesediaan && !$isLockedKesediaan)
                    <button type="button" @click="openModal()" class="ui-btn-primary"><x-icon name="plus" class="w-4 h-4" /> Isi Kesediaan</button>
                @endif
                @if(Route::has('dosen.undangan.index'))<a href="{{ route('dosen.undangan.index') }}" class="ui-btn-secondary"><x-icon name="mail" class="w-4 h-4" /> Undangan</a>@endif
                @if(Route::has('dosen.jadwal.sempro'))<a href="{{ route('dosen.jadwal.sempro') }}" class="ui-btn-secondary"><x-icon name="document" class="w-4 h-4" /> Jadwal Sempro</a>@endif
                @if(Route::has('dosen.jadwal.skripsi'))<a href="{{ route('dosen.jadwal.skripsi') }}" class="ui-btn-secondary"><x-icon name="academic" class="w-4 h-4" /> Jadwal Skripsi</a>@endif
                @if(Route::has('dosen.kalender'))<a href="{{ route('dosen.kalender') }}" class="ui-btn-secondary"><x-icon name="calendar" class="w-4 h-4" /> Kalender</a>@endif
                @if(Route::has('dosen.sk.index'))<a href="{{ route('dosen.sk.index') }}" class="ui-btn-secondary"><x-icon name="badge-check" class="w-4 h-4" /> SK</a>@endif
            </x-slot:aksi>
        </x-sambutan>

        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 rounded-2xl text-sm font-semibold flex items-center justify-between shadow-xs">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800"><x-icon name="x" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></button>
            </div>
        @endif

        @if($isRegistrationClosed)
            <!-- Pendaftaran ditutup: pengingat ringkas -->
            <div class="mb-6 px-4 py-3 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0"><x-icon name="bell" class="w-4 h-4" /></span>
                <p class="text-xs sm:text-sm text-amber-900 dark:text-amber-200 leading-snug">
                    <strong>Pendaftaran ujian sudah ditutup.</strong> Isi slot kesediaan menguji Anda agar koordinator dapat segera menyusun jadwal.
                </p>
            </div>
        @endif

        @if($showFormKesediaan)
        <!-- Card Section: Form & Status Kesediaan Menguji Dosen -->
        <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-slate-200/80 dark:border-slate-700 p-6 shadow-xs mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-700 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-700">
                        <x-icon name="pencil" class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Form Kesediaan Menguji Sempro & Skripsi</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Isi ketersediaan hari, tanggal, dan rentang jam Anda untuk mempermudah plotting jadwal ujian.</p>
                    </div>
                </div>

                @if($isLockedKesediaan)
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 text-xs font-extrabold border border-amber-200 dark:border-amber-700">
                        <x-icon name="lock" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Batas Pengisian 6 Hari Terlampaui (Terkunci)
                    </span>
                @else
                    <button type="button" @click="openModal()" 
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 dark:hover:bg-indigo-800/60 text-indigo-700 dark:text-indigo-300 text-xs font-extrabold rounded-xl border border-indigo-200 dark:border-indigo-700 transition-all cursor-pointer">
                        <span>+ Tambah / Atur Slot Kesediaan</span>
                    </button>
                @endif
            </div>

            @if($existingKesediaan->count() > 0)
                <div class="space-y-2">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Slot Ketersediaan Menguji Anda Saat Ini ({{ $existingKesediaan->count() }} Slot):</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($existingKesediaan as $slot)
                            <div class="bg-slate-50 dark:bg-slate-700/50 border border-slate-200/90 dark:border-slate-600 hover:border-indigo-300 dark:hover:border-indigo-500 rounded-2xl p-4 transition-all flex items-center justify-between shadow-xs">
                                <div>
                                    <div class="font-extrabold text-slate-800 dark:text-slate-200 text-xs sm:text-sm"><x-icon name="calendar" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ \Carbon\Carbon::parse($slot->tanggal)->translatedFormat('l, d F Y') }}</div>
                                    @if($slot->keterangan)<div class="text-slate-500 dark:text-slate-400 italic text-[11px] mt-1"><x-icon name="chat" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ $slot->keterangan }}</div>@endif
                                    @if($slot->wave)
                                        <div class="mt-1"><span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-700 px-2 py-0.5 rounded-lg">Gel. {{ $slot->wave->gelombang }} ({{ ucfirst($slot->wave->jenis) }})</span></div>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('dosen.kesediaan.destroy', $slot->hash_id) }}" onsubmit="return confirm('Hapus slot ketersediaan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 hover:bg-rose-100 dark:hover:bg-rose-800/50 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold text-xs transition-colors cursor-pointer" title="Hapus Slot">
                                        <x-icon name="x" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" />
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-slate-50/70 dark:bg-slate-700/30 border border-dashed border-slate-200 dark:border-slate-600 rounded-2xl p-6 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Anda belum mengisikan slot ketersediaan menguji. Klik tombol <strong class="text-indigo-600 dark:text-indigo-400">"+ Tambah / Atur Slot Kesediaan"</strong> untuk memasukkan jadwal ketersediaan Anda.</p>
                </div>
            @endif
        </div>
        @else
            @if($activePeriode && $activePeriode->show_form_kesediaan && $dosen && !$dosen->can_fill_kesediaan)
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-300 rounded-3xl p-5 mb-8 flex items-start gap-4 shadow-xs">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold shrink-0 shadow-md shadow-amber-500/20">
                        <x-icon name="lock" class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-900 dark:text-slate-100">Akses Pengisian Dinonaktifkan</h4>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                            Akses pengisian form kesediaan menguji Anda saat ini dinonaktifkan oleh Koordinator/Administrator. Silakan hubungi Koordinator Program Studi untuk membuka akses Anda.
                        </p>
                    </div>
                </div>
            @endif
        @endif

        <!-- Ringkasan -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            @foreach([
                ['Mahasiswa Bimbingan', $stats['total_jurnal'], 'Pembimbing utama & pendamping', 'users', 'blue'],
                ['Menguji Sempro', $stats['total_sempro'], 'Seminar proposal', 'document', 'amber'],
                ['Menguji Skripsi', $stats['total_skripsi'], 'Sidang skripsi / jurnal', 'academic', 'violet'],
            ] as [$judul, $nilai, $ket, $ikon, $warna])
                <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-700 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all flex items-center gap-4">
                    <span class="stat-icon {{ $warna }} w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"><x-icon :name="$ikon" class="w-6 h-6" /></span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $judul }}</p>
                        <p class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 leading-tight">{{ $nilai }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $ket }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Infografis Statistik Bimbingan -->
        <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm p-6 mb-8">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-700 mb-5">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Statistik Peran Bimbingan</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Rincian mahasiswa yang Anda bimbing berdasarkan peran.</p>
                </div>
            </div>

            @php
                $bTotal = $stats['bimbingan_total'] ?? 0;
                $bUtama = $stats['bimbingan_utama'] ?? 0;
                $bPendamping = $stats['bimbingan_pendamping'] ?? 0;
                $utamaPct = $bTotal > 0 ? round($bUtama / $bTotal * 100) : 0;
                $pendampingPct = $bTotal > 0 ? round($bPendamping / $bTotal * 100) : 0;
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
                <div class="rounded-2xl p-4 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-800">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Pembimbing Utama</p>
                    <p class="text-2xl font-extrabold text-indigo-900 dark:text-indigo-200 mt-1.5">{{ $bUtama }} <span class="text-xs font-semibold text-indigo-500 dark:text-indigo-400">Mahasiswa</span></p>
                </div>
                <div class="rounded-2xl p-4 bg-sky-50 dark:bg-sky-500/10 border border-sky-100 dark:border-sky-800">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-sky-600 dark:text-sky-300">Pembimbing Pendamping</p>
                    <p class="text-2xl font-extrabold text-sky-900 dark:text-sky-200 mt-1.5">{{ $bPendamping }} <span class="text-xs font-semibold text-sky-500 dark:text-sky-400">Mahasiswa</span></p>
                </div>
                <div class="rounded-2xl p-4 bg-slate-50 dark:bg-slate-700/40 border border-slate-200 dark:border-slate-700">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Bimbingan</p>
                    <p class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 mt-1.5">{{ $bTotal }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Mahasiswa</span></p>
                </div>
            </div>

            @if($bTotal > 0)
            <div>
                <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1.5">
                    <span>Proporsi Peran</span>
                    <span>{{ $utamaPct }}% Utama &middot; {{ $pendampingPct }}% Pendamping</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden flex">
                    <div class="h-full bg-indigo-500" style="width: {{ $utamaPct }}%" title="Pembimbing Utama: {{ $bUtama }}"></div>
                    <div class="h-full bg-sky-400" style="width: {{ $pendampingPct }}%" title="Pembimbing Pendamping: {{ $bPendamping }}"></div>
                </div>
            </div>
            @else
            <p class="text-xs text-slate-400 dark:text-slate-500 italic">Belum ada mahasiswa bimbingan pada data saat ini.</p>
            @endif
        </div>

        <!-- Aktivitas Ujian & Bimbingan Terbaru -->
        <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden mb-8">
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Jadwal Ujian & Bimbingan Mahasiswa</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar jadwal sidang skripsi dan sempro terhubung dengan Anda</p>
                </div>
                <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-200 font-bold text-xs rounded-xl">
                    {{ count($stats['recent_schedules']) }} Sesi Terjadwal
                </span>
            </div>

            @if(count($stats['recent_schedules']) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/70 dark:bg-slate-700/50 text-slate-400 dark:text-slate-500 text-[10px] font-extrabold uppercase tracking-wider border-b border-slate-100 dark:border-slate-700">
                                <th class="py-4 px-6 w-48">TANGGAL & WAKTU</th>
                                <th class="py-4 px-6">MAHASISWA</th>
                                <th class="py-4 px-6">JUDUL TUGAS AKHIR</th>
                                <th class="py-4 px-6 w-32">RUANGAN</th>
                                <th class="py-4 px-6 w-36 text-center">PERAN DOSEN</th>
                                <th class="py-4 px-6 w-28 text-center">JENIS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-sm font-medium">
                            @foreach($stats['recent_schedules'] as $s)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="py-4 px-6">
                                        @if($s->tanggal)
                                            @php $tg = \Carbon\Carbon::parse($s->tanggal)->locale('id'); @endphp
                                            <div class="flex items-center gap-3">
                                                <div class="w-11 shrink-0 rounded-xl overflow-hidden text-center border border-slate-200 dark:border-slate-600">
                                                    <div class="text-[9px] font-extrabold uppercase py-px text-white {{ $s->jenis_tugas_akhir === 'sempro' ? 'bg-amber-500' : 'bg-violet-500' }}">{{ $tg->translatedFormat('M') }}</div>
                                                    <div class="text-base font-extrabold leading-tight py-0.5 text-slate-900 dark:text-slate-100">{{ $tg->format('j') }}</div>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-100 whitespace-nowrap">{{ $tg->translatedFormat('l') }}</div>
                                                    <div class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-300 whitespace-nowrap">{{ $s->jam ? $s->jam . ' WIB' : 'Jam belum ditentukan' }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-xs font-semibold text-slate-400">Belum diplotting</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 min-w-[12rem]">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 text-[13px]">{{ $s->nama_mahasiswa }}</div>
                                        <div class="text-xs text-slate-400 dark:text-slate-500 font-mono">{{ $s->nim }}</div>
                                    </td>
                                    <td class="py-4 px-6 max-w-xs">
                                        <p class="text-xs text-slate-700 dark:text-slate-300 font-medium line-clamp-2">{{ $s->judul_skripsi }}</p>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-200 text-xs font-bold rounded-lg border border-indigo-200 dark:border-indigo-500/30">
                                            {{ $s->ruang ? $s->ruang->kode_ruangan : '-' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        @php
                                            $dosenId = $dosen ? $dosen->id : 0;
                                            $isSemproRow = $s->jenis_tugas_akhir === 'sempro';
                                            $roles = [];
                                            if ($s->dosen_pembimbing_utama_id == $dosenId) $roles[] = ['name' => 'Pembimbing Utama', 'class' => 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700'];
                                            if ($s->dosen_pembimbing_pendamping_id == $dosenId) $roles[] = ['name' => 'Pembimbing Pendamping', 'class' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700'];
                                            // Untuk Sempro, ketua_penguji_id/anggota_penguji_1_id/2_id SELALU mencerminkan
                                            // pembimbing (Sidang::syncPengujiFromPembimbing()) — sudah tercakup 2 baris di
                                            // atas, dilewati di sini supaya tidak dobel.
                                            if (!$isSemproRow && $s->ketua_penguji_id == $dosenId) $roles[] = ['name' => 'Ketua Penguji', 'class' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-700'];
                                            if (!$isSemproRow && $s->anggota_penguji_1_id == $dosenId) $roles[] = ['name' => 'Anggota Penguji 1', 'class' => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700'];
                                            if (!$isSemproRow && $s->anggota_penguji_2_id == $dosenId) $roles[] = ['name' => 'Anggota Penguji 2', 'class' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-700'];
                                        @endphp
                                        <div class="flex flex-wrap gap-1.5 justify-center items-center">
                                            @forelse($roles as $r)
                                                <span class="inline-flex items-center px-2.5 py-0.5 text-[11px] font-bold rounded-lg border whitespace-nowrap {{ $r['class'] }}">
                                                    {{ $r['name'] }}
                                                </span>
                                            @empty
                                                <span class="inline-flex items-center px-2.5 py-0.5 text-[11px] font-bold rounded-lg bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-500/30 whitespace-nowrap">
                                                    Penguji / Dosbing
                                                </span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="px-2.5 py-1 text-xs font-extrabold rounded-full {{ $s->jenis_tugas_akhir == 'sempro' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300' }}">
                                            {{ strtoupper($s->jenis_tugas_akhir) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State Box -->
                <div class="py-16 px-6 text-center flex flex-col items-center justify-center">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-200">Belum Ada Aktivitas</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-sm">Aktivitas skripsi dan pengajuan akan ditampilkan di sini.</p>
                </div>
            @endif
        </div>

        <!-- Modal Form Kesediaan Menguji Sempro & Skripsi -->
        <div x-show="isOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-transition.opacity>
            <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-2xl w-full p-6 text-slate-800 dark:text-slate-200 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col border border-slate-200 dark:border-slate-700" @click.away="closeModal()">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700 shrink-0">
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">Form Kesediaan Menguji Ujian</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Silakan tentukan hari, tanggal, dan rentang jam ketersediaan Anda untuk Sempro dan Skripsi.</p>
                        @if($activePeriode)
                            <span class="inline-flex items-center gap-1 mt-1.5 text-[11px] font-bold px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700">
                                <x-icon name="calendar" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ $activePeriode->nama_periode }}
                            </span>
                        @endif
                    </div>
                    <button type="button" @click="closeModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600 flex items-center justify-center font-bold cursor-pointer"><x-icon name="x" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></button>
                </div>

                {{-- Modal Body Form --}}
                <form method="POST" action="{{ route('dosen.kesediaan.store') }}" class="flex-1 overflow-y-auto py-4 space-y-4">
                    @csrf

                    {{-- Nama Dosen (Readonly) --}}
                    <div class="bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-2xl p-4 flex items-center justify-between">
                        <div>
                            <label class="text-[10px] font-extrabold uppercase text-slate-400 dark:text-slate-500 tracking-wider">Nama Dosen Penguji</label>
                            <div class="font-extrabold text-slate-800 dark:text-slate-200 text-sm">{{ Auth::user()->name }}</div>
                        </div>
                        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-700 px-3 py-1 rounded-xl">NIDN: {{ $dosen->nidn ?? '-' }}</span>
                    </div>

                    @if($allWaves->count() > 0)
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Gelombang Ujian *</label>
                            <select name="wave_id" required class="w-full text-xs p-2.5 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 cursor-pointer">
                                <option value="">-- Pilih Gelombang --</option>
                                @foreach($allWaves as $w)
                                    <option value="{{ $w->id }}">Gelombang {{ $w->gelombang }} - {{ ucfirst($w->jenis) }} ({{ $w->tanggal_mulai ? $w->tanggal_mulai->format('d/m/Y') : '' }} s/d {{ $w->tanggal_selesai ? $w->tanggal_selesai->format('d/m/Y') : '' }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Kalender multi-pilih tanggal --}}
                        <x-kesediaan-date-picker
                            :existing="$existingKesediaan->map(fn ($k) => \Carbon\Carbon::parse($k->tanggal)->format('Y-m-d'))->unique()->values()->all()" />
                    @else
                        {{-- Kesediaan WAJIB ditautkan ke gelombang + periode semester aktif —
                             tanpa gelombang yang tersedia, form tidak bisa disubmit sama sekali. --}}
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-300 rounded-2xl p-4 text-xs leading-relaxed">
                            Belum ada Gelombang yang tersedia untuk periode <strong>{{ $activePeriode->nama_periode ?? '-' }}</strong> saat ini. Hubungi Koordinator untuk menambahkan gelombang di menu Master Gelombang terlebih dahulu.
                        </div>
                    @endif

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-700 shrink-0">
                        <button type="button" @click="closeModal()" class="px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl cursor-pointer">Batal</button>
                        @if($allWaves->count() > 0)
                            <button type="submit" class="px-5 py-2.5 text-xs font-extrabold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md shadow-indigo-600/20 cursor-pointer">Simpan Kesediaan</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function kesediaanModalHandler() {
            return {
                isOpen: false,
                openModal() {
                    this.isOpen = true;
                },
                closeModal() {
                    this.isOpen = false;
                }
            }
        }
    </script>
</x-app-layout>

<x-app-layout title="Profil Saya">
@php
    $isDosen = $user->isDosen();
    $isMahasiswa = $user->isMahasiswa();
    $inisial = collect(preg_split('/\s+/', trim($user->name ?? 'U')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    $roleTheme = match (true) {
        $user->isSuperAdmin()  => ['from-violet-600 to-indigo-600', 'Mengelola seluruh data, penjadwalan, administrasi, dan hak akses sistem.'],
        $user->isKoordinator() => ['from-sky-600 to-indigo-600', 'Mengelola pendaftaran, penjadwalan, dan administrasi sesuai menu yang diberikan.'],
        $isDosen               => ['from-amber-500 to-orange-600', 'Membimbing dan menguji Seminar Proposal serta Sidang Skripsi.'],
        default                => ['from-emerald-500 to-teal-600', 'Mendaftar dan mengikuti Seminar Proposal serta Sidang Skripsi.'],
    };

    $input = 'w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 transition';
    $label = 'block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5';
    $card  = 'bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm';

    $statusTa = function ($s) {
        if (!$s) return ['Belum mendaftar', 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'];
        return match (true) {
            $s->status_ujian === 'lulus'        => ['Lulus', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
            $s->status_ujian === 'tidak_lulus'  => ['Tidak Lulus', 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'],
            $s->verifikasi_status === 'ditolak' => ['Berkas ditolak', 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'],
            $s->verifikasi_status !== 'disetujui' => ['Menunggu verifikasi', 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
            (bool) $s->tanggal                  => ['Terjadwal ' . $s->tanggal->locale('id')->translatedFormat('j M Y'), 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'],
            default                             => ['Menunggu jadwal', 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300'],
        };
    };
@endphp

<div class="max-w-6xl mx-auto space-y-6"
     x-data="{
        preview: '{{ $user->foto_profil ? asset($user->foto_profil) : '' }}',
        pilihFoto(e) { const f = e.target.files[0]; if (f) { this.preview = URL.createObjectURL(f); this.fotoBaru = f.name; } },
        fotoBaru: '',
     }">

    {{-- ═══════════════ Kartu identitas ═══════════════ --}}
    <section class="{{ $card }} overflow-hidden">
        <div class="h-28 sm:h-32 bg-gradient-to-r {{ $roleTheme[0] }} relative">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, #fff 0 2px, transparent 3px), radial-gradient(circle at 70% 60%, #fff 0 1.5px, transparent 2.5px); background-size: 46px 46px, 64px 64px;"></div>
        </div>
        <div class="px-5 sm:px-8 pb-6">
            <div class="relative flex flex-col sm:flex-row sm:items-end gap-4 sm:gap-6">
                <div class="relative shrink-0 self-start -mt-12 sm:-mt-14">
                    <template x-if="preview">
                        <img :src="preview" alt="{{ $user->name }}" class="w-24 h-24 sm:w-28 sm:h-28 rounded-3xl object-cover ring-4 ring-white dark:ring-slate-900 shadow-lg bg-white">
                    </template>
                    <template x-if="!preview">
                        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-3xl ring-4 ring-white dark:ring-slate-900 shadow-lg bg-gradient-to-br {{ $roleTheme[0] }} text-white flex items-center justify-center text-3xl font-black tracking-tight">{{ $inisial }}</div>
                    </template>
                    <label for="foto_profil" title="Ganti foto profil"
                           class="absolute -bottom-1 -right-1 w-9 h-9 rounded-xl bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-md ring-1 ring-slate-200 dark:ring-slate-700 flex items-center justify-center cursor-pointer hover:text-indigo-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </label>
                </div>

                <div class="min-w-0 flex-1 sm:pt-4">
                    <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-50 break-words">{{ $user->name }}</h1>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400 break-all">{{ $user->email }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold text-white bg-gradient-to-r {{ $roleTheme[0] }} shadow-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-white/90"></span>{{ $user->role_label }}
                        </span>
                        @if($isMahasiswa && $user->nim)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200">NIM <span class="ml-1.5 font-mono">{{ $user->nim }}</span></span>
                        @endif
                        @if($isDosen && $dosen)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200">NIDN <span class="ml-1.5 font-mono">{{ $dosen->nidn }}</span></span>
                            @if($dosen->jabatan_fungsional)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/30">{{ $dosen->jabatan_fungsional }}</span>
                            @endif
                        @endif
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Akun aktif</span>
                    </div>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">{{ $roleTheme[1] }}</p>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 text-sm font-semibold text-emerald-800 dark:text-emerald-300" x-data="{ show: true }" x-show="show">
            <x-icon name="check-circle" class="w-5 h-5 shrink-0" />
            <span class="flex-1">{{ session('success') }}</span>
            <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-900 dark:hover:text-emerald-100"><x-icon name="x" class="w-4 h-4" /></button>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        {{-- ═══════════════ Panel ringkasan per role ═══════════════ --}}
        <aside class="order-2 lg:order-1 space-y-6 lg:sticky lg:top-6">
            @if($isDosen)
                @if($dosen)
                    <div class="{{ $card }} p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Data Master Dosen</h2>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-300"><span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>Tersinkron</span>
                        </div>
                        <dl class="space-y-3 text-sm">
                            @foreach([
                                'Jabatan Fungsional' => $dosen->jabatan_fungsional ?: '—',
                                'Status Kepegawaian' => \App\Models\Dosen::STATUS_KEPEGAWAIAN_LABELS[$dosen->status_kepegawaian] ?? ($dosen->status_kepegawaian ?: '—'),
                                'Kepakaran'          => $dosen->kepakaran ?: '—',
                                'Alias / Inisial'    => $dosen->alias ?: '—',
                            ] as $k => $v)
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400 shrink-0">{{ $k }}</dt>
                                    <dd class="text-xs font-bold text-slate-800 dark:text-slate-100 text-right break-words">{{ $v }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <p class="mt-4 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">Nama, NIDN, email, no. WhatsApp, dan kepakaran yang Anda ubah di sini langsung tersimpan ke Master Dosen. Jabatan fungsional & status kepegawaian diatur oleh admin.</p>
                    </div>

                    @if($dosenStat)
                        <div class="{{ $card }} p-5 sm:p-6">
                            <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mb-4">Ringkasan Keterlibatan</h2>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach([
                                    ['Bimbingan Sempro', $dosenStat['bimbingan_sempro'], 'text-indigo-600 dark:text-indigo-300'],
                                    ['Bimbingan Skripsi', $dosenStat['bimbingan_skripsi'], 'text-emerald-600 dark:text-emerald-300'],
                                    ['Menguji Skripsi', $dosenStat['menguji'], 'text-violet-600 dark:text-violet-300'],
                                    ['Jadwal Mendatang', $dosenStat['akan_datang'], 'text-amber-600 dark:text-amber-300'],
                                ] as [$k, $v, $c])
                                    <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                        <p class="text-2xl font-black {{ $c }} leading-none">{{ $v }}</p>
                                        <p class="mt-1.5 text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ $k }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                @if(Route::has('dosen.kalender'))<a href="{{ route('dosen.kalender') }}" class="text-center px-3 py-2 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-500/25 transition">Kalender</a>@endif
                                @if(Route::has('dosen.riwayat'))<a href="{{ route('dosen.riwayat') }}" class="text-center px-3 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition">Riwayat Menguji</a>@endif
                            </div>
                        </div>
                    @endif
                @else
                    <div class="{{ $card }} p-5 sm:p-6 border-amber-200 dark:border-amber-500/30">
                        <h2 class="text-sm font-extrabold text-amber-800 dark:text-amber-300">Belum terhubung ke Master Dosen</h2>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-300">Akun ini belum ditautkan ke data Master Dosen, sehingga jadwal, SK, dan riwayat menguji belum dapat ditampilkan. Silakan hubungi admin untuk menautkan akun Anda.</p>
                    </div>
                @endif
            @elseif($isMahasiswa)
                <div class="{{ $card }} p-5 sm:p-6">
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mb-4">Status Tugas Akhir</h2>
                    <div class="space-y-3">
                        @foreach(['sempro' => 'Seminar Proposal', 'skripsi' => 'Sidang Skripsi'] as $key => $judul)
                            @php $s = $tugasAkhir[$key] ?? null; [$lbl, $cls] = $statusTa($s); @endphp
                            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 p-3.5">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-extrabold text-slate-800 dark:text-slate-100">{{ $judul }}</p>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $cls }}">{{ $lbl }}</span>
                                </div>
                                @if($s)
                                    <p class="mt-1.5 text-[11px] text-slate-500 dark:text-slate-400 leading-snug line-clamp-2">{{ $s->judul_skripsi }}</p>
                                    <p class="mt-1 text-[11px] font-semibold text-slate-600 dark:text-slate-300">Pembimbing: {{ $s->pembimbingUtama->nama_dosen ?? '—' }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-2 rounded-2xl bg-slate-50 dark:bg-slate-800/60 px-3.5 py-3">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Status kelulusan</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold {{ $user->status_kelulusan === 'lulus' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' }}">{{ $user->status_kelulusan === 'lulus' ? 'Lulus' : 'Aktif' }}</span>
                    </div>
                    <a href="{{ route('mahasiswa.dashboard') }}" class="mt-4 block text-center px-3 py-2 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-500/25 transition">Lihat progress lengkap</a>
                </div>
            @else
                <div class="{{ $card }} p-5 sm:p-6">
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mb-3">Hak Akses</h2>
                    <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">{{ $roleTheme[1] }}</p>
                    @if($user->isKoordinator())
                        <p class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">Menu yang dapat Anda buka diatur oleh Super Admin melalui Manajemen Menu.</p>
                    @endif
                </div>
            @endif

            <div class="{{ $card }} p-5 sm:p-6">
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mb-3">Informasi Akun</h2>
                <dl class="space-y-2.5 text-xs">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500 dark:text-slate-400 font-semibold">Terdaftar sejak</dt><dd class="font-bold text-slate-800 dark:text-slate-100">{{ optional($user->created_at)->locale('id')->translatedFormat('j F Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500 dark:text-slate-400 font-semibold">Terakhir diperbarui</dt><dd class="font-bold text-slate-800 dark:text-slate-100">{{ optional($user->updated_at)->locale('id')->diffForHumans() ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500 dark:text-slate-400 font-semibold">No. WhatsApp</dt><dd class="font-bold text-slate-800 dark:text-slate-100">{{ $user->no_hp ?: '—' }}</dd></div>
                </dl>
            </div>
        </aside>

        {{-- ═══════════════ Form ═══════════════ --}}
        <div class="order-1 lg:order-2 lg:col-span-2 space-y-6">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="{{ $card }}">
                @csrf
                @method('PATCH')
                <input type="file" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pilihFoto($event)">

                <div class="px-5 sm:px-7 py-5 border-b border-slate-100 dark:border-slate-800 flex items-start gap-3">
                    <span class="w-10 h-10 shrink-0 rounded-2xl bg-indigo-50 dark:bg-indigo-500/15 text-indigo-600 dark:text-indigo-300 flex items-center justify-center"><x-icon name="user" class="w-5 h-5" /></span>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">{{ $isDosen ? 'Data Dosen' : 'Informasi Pribadi' }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $isDosen ? 'Perubahan disimpan ke akun dan Master Dosen sekaligus.' : 'Perbarui nama, kontak, dan foto profil Anda.' }}</p>
                    </div>
                </div>

                <div class="px-5 sm:px-7 py-6 space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-dashed border-slate-300 dark:border-slate-700">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Foto profil</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400" x-text="fotoBaru ? 'Dipilih: ' + fotoBaru + ' — simpan untuk menerapkan.' : 'JPG, PNG, atau WEBP, maksimal 2 MB.'">JPG, PNG, atau WEBP, maksimal 2 MB.</p>
                        </div>
                        <label for="foto_profil" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition">
                            <x-icon name="folder" class="w-4 h-4" /> Pilih foto
                        </label>
                    </div>
                    @error('foto_profil')<p class="-mt-3 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label for="name" class="{{ $label }}">{{ $isDosen ? 'Nama lengkap & gelar' : 'Nama lengkap' }} <span class="text-rose-500">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="{{ $input }}" placeholder="{{ $isDosen ? 'Contoh: Dr. Nama Dosen, S.Kom., M.Kom' : '' }}">
                            @if($isMahasiswa)<p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Disimpan dalam huruf kapital, sama seperti data pendaftaran.</p>@endif
                            @error('name')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        @if($isMahasiswa)
                            <div>
                                <label for="nim" class="{{ $label }}">NIM</label>
                                <input type="text" id="nim" name="nim" value="{{ old('nim', $user->nim) }}" class="{{ $input }} font-mono" placeholder="202251xxx">
                                @error('nim')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        @elseif($isDosen && $dosen)
                            <div>
                                <label for="nidn" class="{{ $label }}">NIDN</label>
                                <input type="text" id="nidn" name="nidn" value="{{ old('nidn', $dosen->nidn) }}" class="{{ $input }} font-mono" placeholder="0012345678">
                                @error('nidn')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        @endif

                        <div>
                            <label for="email" class="{{ $label }}">Email <span class="text-rose-500">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="{{ $input }}">
                            @error('email')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="no_hp" class="{{ $label }}">No. WhatsApp</label>
                            <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" inputmode="tel" class="{{ $input }}" placeholder="08123456789">
                            @error('no_hp')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        @if($isDosen && $dosen)
                            <div class="sm:col-span-2">
                                <label for="kepakaran" class="{{ $label }}">Bidang kepakaran</label>
                                <input type="text" id="kepakaran" name="kepakaran" value="{{ old('kepakaran', $dosen->kepakaran) }}" class="{{ $input }}" placeholder="Contoh: Data Mining, Sistem Informasi">
                                @error('kepakaran')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    </div>
                </div>

                <div class="px-5 sm:px-7 py-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm shadow-indigo-600/30 transition">
                        <x-icon name="check-circle" class="w-4 h-4" /> Simpan perubahan
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('password.update') }}" class="{{ $card }}"
                  x-data="{
                    pw: '',
                    get skor() { let s = 0; if (this.pw.length >= 8) s++; if (/[A-Z]/.test(this.pw) && /[a-z]/.test(this.pw)) s++; if (/\d/.test(this.pw)) s++; if (/[^A-Za-z0-9]/.test(this.pw)) s++; return s; },
                    get teks() { return ['Terlalu lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'][this.skor]; },
                    lihat: false,
                  }">
                @csrf
                @method('PUT')
                <div class="px-5 sm:px-7 py-5 border-b border-slate-100 dark:border-slate-800 flex items-start gap-3">
                    <span class="w-10 h-10 shrink-0 rounded-2xl bg-amber-50 dark:bg-amber-500/15 text-amber-600 dark:text-amber-300 flex items-center justify-center"><x-icon name="lock" class="w-5 h-5" /></span>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Keamanan Akun</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Gunakan minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, angka, dan simbol.</p>
                    </div>
                </div>

                <div class="px-5 sm:px-7 py-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label for="current_password" class="{{ $label }}">Kata sandi saat ini</label>
                        <input :type="lihat ? 'text' : 'password'" id="current_password" name="current_password" required autocomplete="current-password" class="{{ $input }}">
                        @error('current_password', 'updatePassword')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="{{ $label }}">Kata sandi baru</label>
                        <input :type="lihat ? 'text' : 'password'" id="password" name="password" x-model="pw" required autocomplete="new-password" class="{{ $input }}">
                        <div class="mt-2 flex gap-1" x-show="pw.length" x-cloak>
                            <template x-for="i in 4"><span class="h-1.5 flex-1 rounded-full transition-colors" :class="i <= skor ? (skor < 2 ? 'bg-rose-500' : (skor < 3 ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-slate-200 dark:bg-slate-700'"></span></template>
                        </div>
                        <p class="mt-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400" x-show="pw.length" x-cloak x-text="'Kekuatan: ' + teks"></p>
                        @error('password', 'updatePassword')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="{{ $label }}">Konfirmasi kata sandi baru</label>
                        <input :type="lihat ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="{{ $input }}">
                        @error('password_confirmation', 'updatePassword')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-5 sm:px-7 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" x-model="lihat" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600"> Tampilkan kata sandi
                    </label>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 dark:bg-amber-600 dark:hover:bg-amber-500 shadow-sm transition">
                        <x-icon name="lock" class="w-4 h-4" /> Perbarui kata sandi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>

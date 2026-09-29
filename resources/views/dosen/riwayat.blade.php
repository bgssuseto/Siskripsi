<x-app-layout title="Riwayat Menguji">
    <x-slot:header>
        Riwayat Menguji
    </x-slot:header>

    <!-- Back Navigation -->
    <div class="mb-4">
        <a href="{{ route('dosen.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    <!-- Page Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Riwayat Menguji</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Rekap seluruh sidang skripsi & seminar proposal yang telah Anda uji/bimbing, lintas tahun ajaran.</p>
    </div>

    @if($dosen)
        <!-- Rekap Summary -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4">
                <p class="text-xl font-extrabold text-slate-900 dark:text-slate-100 leading-none">{{ $rekap['total'] }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1">Total Riwayat</p>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4">
                <p class="text-xl font-extrabold text-blue-600 dark:text-blue-400 leading-none">{{ $rekap['sempro'] }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1">Seminar Proposal</p>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4">
                <p class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-none">{{ $rekap['skripsi'] }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1">Sidang Skripsi</p>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4">
                <p class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-none">{{ $rekap['lulus'] }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1">🎓 Lulus</p>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4">
                <p class="text-xl font-extrabold text-rose-600 dark:text-rose-400 leading-none">{{ $rekap['tidak_lulus'] }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1">✕ Tidak Lulus</p>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4">
                <p class="text-xl font-extrabold text-slate-400 dark:text-slate-500 leading-none">{{ $rekap['belum'] }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold mt-1">Belum Diisi</p>
            </div>
        </div>
    @endif

    <!-- Filters & Search Toolbar -->
    <form method="GET" action="{{ route('dosen.riwayat') }}" class="flex flex-wrap items-end gap-3 bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm mb-6">
        <div class="relative flex-1 max-w-xs min-w-[200px]">
            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Cari</label>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari mahasiswa, NIM, atau judul..."
                   class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 focus:border-purple-500 focus:ring-purple-500 bg-slate-50/50 dark:bg-slate-950 focus:bg-white text-slate-900 dark:text-slate-100 transition-all font-semibold">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 mt-[22px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <div class="min-w-[200px]">
            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Tahun Ajaran</label>
            <select name="periode_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 focus:border-purple-500 focus:ring-purple-500 text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-950 font-semibold cursor-pointer">
                <option value="">-- Semua Tahun Ajaran --</option>
                @foreach($periodes as $p)
                    <option value="{{ $p->id }}" {{ request('periode_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->nama_periode }}{{ $p->aktif ? ' (Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="min-w-[160px]">
            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Jenis</label>
            <select name="jenis" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 focus:border-purple-500 focus:ring-purple-500 text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-950 font-semibold cursor-pointer">
                <option value="">Semua Jenis</option>
                <option value="sempro" {{ request('jenis') === 'sempro' ? 'selected' : '' }}>Seminar Proposal</option>
                <option value="skripsi" {{ request('jenis') === 'skripsi' ? 'selected' : '' }}>Sidang Skripsi</option>
            </select>
        </div>

        <div class="min-w-[140px]">
            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Gelombang</label>
            <select name="gelombang" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 focus:border-purple-500 focus:ring-purple-500 text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-950 font-semibold cursor-pointer">
                <option value="">Semua Gelombang</option>
                @foreach($gelombangOptions as $g)
                    <option value="{{ $g }}" {{ (string) request('gelombang') === (string) $g ? 'selected' : '' }}>Gelombang {{ $g }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm cursor-pointer border border-purple-500">
                Filter
            </button>
            @if(request()->hasAny(['search','periode_id','jenis','gelombang']))
                <a href="{{ route('dosen.riwayat') }}" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition-all">
                    Reset
                </a>
            @endif
            <a href="{{ route('dosen.riwayat.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm border border-emerald-500 inline-flex items-center gap-1.5">
                📥 Unduh Rekap
            </a>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[960px]">
                <thead>
                    <tr class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-[11px] font-extrabold uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                        <th class="py-4 px-6 w-32">JADWAL</th>
                        <th class="py-4 px-6 w-52">MAHASISWA</th>
                        <th class="py-4 px-6">JUDUL</th>
                        <th class="py-4 px-6 w-36">TAHUN AJARAN</th>
                        <th class="py-4 px-6 w-28">GELOMBANG</th>
                        <th class="py-4 px-6 w-44">PERAN ANDA</th>
                        <th class="py-4 px-6 w-36">HASIL UJIAN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm font-medium">
                    @if($dosen)
                        @forelse($schedules as $s)
                            @php
                                $roles = [];
                                if($s->dosen_pembimbing_utama_id === $dosen->id) $roles[] = 'Pembimbing Utama';
                                if($s->dosen_pembimbing_pendamping_id === $dosen->id) $roles[] = 'Pembimbing Pendamping';
                                if($s->ketua_penguji_id === $dosen->id) $roles[] = 'Ketua Penguji';
                                if($s->anggota_penguji_1_id === $dosen->id) $roles[] = 'Anggota Penguji 1';
                                if($s->anggota_penguji_2_id === $dosen->id) $roles[] = 'Anggota Penguji 2';
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="text-slate-900 dark:text-slate-100 font-bold text-xs sm:text-sm">
                                        {{ $s->tanggal->locale('id')->translatedFormat('l, d M Y') }}
                                    </div>
                                    <div class="bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200 text-xs mt-1 px-2 py-0.5 rounded font-extrabold inline-block">
                                        {{ $s->jam ?? '-' }}
                                    </div>
                                </td>

                                <td class="py-4 px-6">
                                    <div class="text-slate-900 dark:text-slate-100 font-extrabold leading-snug">{{ $s->nama_mahasiswa }}</div>
                                    <div class="bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200 text-xs mt-1 px-2 py-0.5 rounded font-mono font-extrabold inline-block">NIM: {{ $s->nim }}</div>
                                    <div class="mt-1">
                                        <span class="inline-flex whitespace-nowrap px-2 py-0.5 rounded-lg text-[10px] font-extrabold {{ $s->jenis_tugas_akhir === 'sempro' ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' }}">
                                            {{ $s->jenis_tugas_akhir === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-4 px-6">
                                    <p class="text-slate-800 dark:text-slate-200 text-xs font-semibold leading-relaxed max-w-lg line-clamp-2">
                                        "{{ $s->judul_skripsi }}"
                                    </p>
                                </td>

                                <td class="py-4 px-6">
                                    <span class="text-xs text-slate-600 dark:text-slate-300 font-semibold">{{ $s->periode->nama_periode ?? '-' }}</span>
                                </td>

                                <td class="py-4 px-6">
                                    @if($s->gelombang)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl">
                                            Gel. {{ $s->gelombang }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 text-xs">-</span>
                                    @endif
                                </td>

                                <td class="py-4 px-6">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($roles as $r)
                                            <span class="px-2.5 py-1 text-[10px] font-extrabold rounded-lg uppercase tracking-wider
                                                {{ str_contains($r, 'Pembimbing') ? 'bg-emerald-100 dark:bg-emerald-950/80 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-purple-100 dark:bg-purple-950/80 border border-purple-300 dark:border-purple-800 text-purple-800 dark:text-purple-300' }}">
                                                {{ $r }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="py-4 px-6">
                                    {!! $s->getHasilUjianHtml() !!}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center text-slate-500 dark:text-slate-400">
                                    <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3.5">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <h3 class="font-extrabold text-slate-800 dark:text-slate-200 text-base">Belum Ada Riwayat Menguji</h3>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-sm mx-auto">
                                        Riwayat akan muncul di sini setelah ada sidang/sempro yang Anda uji atau bimbing dan sudah terlaksana.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    @else
                        <tr>
                            <td colspan="7" class="py-16 text-center text-slate-500 dark:text-slate-400">
                                <h3 class="font-extrabold text-slate-800 dark:text-slate-200 text-base">Akun Belum Terhubung</h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Hubungkan akun Anda dengan Master Dosen untuk melihat riwayat menguji.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

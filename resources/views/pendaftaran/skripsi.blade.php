<x-app-layout title="Verifikasi Pendaftaran Sidang Skripsi">
<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6" 
     x-data="{ 
         verifikasiModal: false, 
         previewModal: false,
         deleteModal: false,
         previewUrl: '',
         previewTitle: '',
         selectedSidang: null, 
         verifikasiStatus: 'disetujui', 
         verifikasiKomentar: '' 
     }">
    
    <!-- Hero Header Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 p-6 sm:p-7 text-white shadow-xl border border-slate-800">
        <div class="absolute -right-10 -top-10 w-52 h-52 bg-purple-500/10 rounded-full blur-3xl"></div>
        <div class="absolute right-32 -bottom-10 w-40 h-40 bg-pink-500/10 rounded-full blur-2xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-[11px] font-extrabold border border-purple-400/30 mb-2.5 shadow-inner">
                    <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span>
                    Portal Verifikasi Pendaftaran
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Pendaftaran Sidang Skripsi</h1>
                <p class="text-xs text-purple-200/90 mt-1 max-w-2xl leading-relaxed">
                    Periksa kelayakan pendaftaran, validasi bukti pembayaran, dan berikan persetujuan untuk meneruskan mahasiswa ke Master Skripsi.
                </p>
            </div>
            
            <div class="shrink-0 flex items-center gap-3">
                <div class="px-4 py-2.5 bg-white/10 backdrop-blur-md rounded-2xl border border-white/15 text-center shadow-md">
                    <p class="text-[10px] text-purple-300 font-bold uppercase tracking-wider">Total Pendaftaran</p>
                    <p class="text-lg font-extrabold text-white mt-0.5">{{ $counts['total'] }} Berkas</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-base">✅</span>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-sm font-bold">✕</button>
        </div>
    @endif

    <!-- Stat Infographics Cards (4 Interactive Columns) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Card 1: Semua Pendaftaran -->
        <a href="{{ route('pendaftaran.skripsi') }}" 
           class="group bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border transition-all duration-300 shadow-xs hover:shadow-md relative overflow-hidden {{ !request('verifikasi_status') ? 'border-purple-600 ring-2 ring-purple-500/20 bg-purple-50/10' : 'border-slate-200/80 dark:border-slate-800 hover:border-purple-300' }}">
            <div class="flex items-center justify-between">
                <p class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">SEMUA BERKAS</p>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 font-bold flex items-center justify-center text-sm sm:text-base border border-purple-100 dark:border-purple-900 group-hover:scale-110 transition-transform">
                    🎓
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 mt-2">{{ $counts['total'] }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">Total pendaftaran skripsi</p>
        </a>

        <!-- Card 2: Menunggu Verifikasi -->
        <a href="{{ route('pendaftaran.skripsi', ['verifikasi_status' => 'menunggu']) }}" 
           class="group bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border transition-all duration-300 shadow-xs hover:shadow-md relative overflow-hidden {{ request('verifikasi_status') === 'menunggu' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/10' : 'border-slate-200/80 dark:border-slate-800 hover:border-amber-300' }}">
            <div class="flex items-center justify-between">
                <p class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-amber-600 dark:text-amber-400">MENUNGGU</p>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 font-bold flex items-center justify-center text-sm sm:text-base border border-amber-100 dark:border-amber-900 group-hover:scale-110 transition-transform relative">
                    ⏳
                    @if($counts['menunggu'] > 0)
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-amber-500 rounded-full animate-ping"></span>
                    @endif
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-amber-700 dark:text-amber-400 mt-2">{{ $counts['menunggu'] }}</p>
            <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold mt-0.5">Perlu verifikasi segera</p>
        </a>

        <!-- Card 3: Disetujui -->
        <a href="{{ route('pendaftaran.skripsi', ['verifikasi_status' => 'disetujui']) }}" 
           class="group bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border transition-all duration-300 shadow-xs hover:shadow-md relative overflow-hidden {{ request('verifikasi_status') === 'disetujui' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/10' : 'border-slate-200/80 dark:border-slate-800 hover:border-emerald-300' }}">
            <div class="flex items-center justify-between">
                <p class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">DISETUJUI</p>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 font-bold flex items-center justify-center text-sm sm:text-base border border-emerald-100 dark:border-emerald-900 group-hover:scale-110 transition-transform">
                    ✓
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-emerald-700 dark:text-emerald-400 mt-2">{{ $counts['disetujui'] }}</p>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">Masuk ke Master Skripsi</p>
        </a>

        <!-- Card 4: Ditolak -->
        <a href="{{ route('pendaftaran.skripsi', ['verifikasi_status' => 'ditolak']) }}" 
           class="group bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border transition-all duration-300 shadow-xs hover:shadow-md relative overflow-hidden {{ request('verifikasi_status') === 'ditolak' ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/10' : 'border-slate-200/80 dark:border-slate-800 hover:border-rose-300' }}">
            <div class="flex items-center justify-between">
                <p class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-rose-600 dark:text-rose-400">DITOLAK</p>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 font-bold flex items-center justify-center text-sm sm:text-base border border-rose-100 dark:border-rose-900 group-hover:scale-110 transition-transform">
                    ✕
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-rose-700 dark:text-rose-400 mt-2">{{ $counts['ditolak'] }}</p>
            <p class="text-[11px] text-rose-600 dark:text-rose-400 font-medium mt-0.5">Perlu perbaikan berkas</p>
        </a>
    </div>

    <!-- Main Data Table Container -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <!-- Filter Header Bar -->
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40">
            <form method="GET" action="{{ route('pendaftaran.skripsi') }}" class="flex flex-wrap items-center gap-3">
                
                <!-- Search Query Input -->
                <div class="relative flex-1 min-w-[260px]">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari NIM, Nama Mahasiswa, atau Judul Skripsi..." 
                           class="w-full pl-10 pr-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-semibold focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-all">
                </div>

                <!-- Filter Status Dropdown -->
                <div class="min-w-[200px]">
                    <select name="verifikasi_status" onchange="this.form.submit()" 
                            class="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 focus:outline-none focus:border-purple-600 cursor-pointer shadow-2xs">
                        <option value="">-- Semua Status Verifikasi --</option>
                        <option value="menunggu" {{ request('verifikasi_status') === 'menunggu' ? 'selected' : '' }}>⏳ Menunggu Verifikasi</option>
                        <option value="disetujui" {{ request('verifikasi_status') === 'disetujui' ? 'selected' : '' }}>✓ Disetujui</option>
                        <option value="ditolak" {{ request('verifikasi_status') === 'ditolak' ? 'selected' : '' }}>✕ Ditolak</option>
                    </select>
                </div>

                <!-- Filter Periode Dropdown -->
                <div class="min-w-[200px]">
                    <select name="periode_id" onchange="this.form.submit()" 
                            class="w-full px-3 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 focus:outline-none focus:border-purple-600 cursor-pointer shadow-2xs">
                        <option value="">-- Semua Periode --</option>
                        @foreach($periodes as $p)
                            <option value="{{ $p->id }}" {{ (request('periode_id', $activePeriode?->id) == $p->id) ? 'selected' : '' }}>
                                {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons with Explicit High-Contrast Colors -->
                <div class="flex items-center gap-2">
                    <button type="submit" 
                            style="background-color: #9333ea; color: #ffffff;" 
                            class="px-5 py-2.5 bg-purple-600 text-white font-extrabold text-xs rounded-2xl transition-all shadow-md hover:bg-purple-700 border border-purple-500 cursor-pointer flex items-center justify-center gap-1.5">
                        🔍 Cari & Filter
                    </button>
                    
                    <a href="{{ route('pendaftaran.skripsi.export', request()->query()) }}" 
                       style="background-color: #059669; color: #ffffff;" 
                       class="px-4 py-2.5 bg-emerald-600 text-white font-extrabold text-xs rounded-2xl transition-all shadow-md hover:bg-emerald-700 border border-emerald-500 cursor-pointer flex items-center justify-center gap-1.5">
                        📊 Export Excel
                    </a>

                    @if(request('search') || request('verifikasi_status') || request('periode_id'))
                        <a href="{{ route('pendaftaran.skripsi') }}" 
                           style="background-color: #475569; color: #ffffff;"
                           class="px-4 py-2.5 bg-slate-600 text-white font-extrabold text-xs rounded-2xl hover:bg-slate-700 transition-colors border border-slate-500 cursor-pointer flex items-center justify-center gap-1">
                            ✕ Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table Container with Dedicated DOSEN PEMBIMBING Column -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1100px]">
                <thead>
                    <tr class="bg-slate-100/70 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 text-[10px] font-extrabold uppercase tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                        <th class="py-3.5 px-4 w-44">MAHASISWA</th>
                        <th class="py-3.5 px-3 w-28">PRODI</th>
                        <th class="py-3.5 px-4 min-w-[200px]">JUDUL SKRIPSI</th>
                        <th class="py-3.5 px-4 w-48">DOSEN PEMBIMBING</th>
                        <th class="py-3.5 px-3 w-36">PERIODE & TANGGAL</th>
                        <th class="py-3.5 px-3 w-36 text-center">BERKAS / PERSYARATAN</th>
                        <th class="py-3.5 px-3 w-36 text-center">STATUS VERIFIKASI</th>
                        <th class="py-3.5 px-4 w-40 text-center">TINDAKAN AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs font-medium">
                    @forelse($sidangs as $s)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                            <!-- Mahasiswa -->
                            <td class="py-3.5 px-4">
                                <div class="font-extrabold text-slate-900 dark:text-slate-100 text-xs leading-snug">{{ $s->nama_mahasiswa }}</div>
                                <div class="text-[10.5px] text-purple-600 dark:text-purple-400 font-mono font-bold mt-0.5">NIM: {{ $s->nim }}</div>
                                @if($s->no_wa_aktif)
                                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-mono font-semibold mt-0.5">WA: {{ $s->no_wa_aktif }}</div>
                                @endif
                            </td>

                            <!-- Prodi -->
                            <td class="py-3.5 px-3">
                                <span class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[10.5px] border border-slate-200 dark:border-slate-700 inline-block">
                                    Teknik Informatika
                                </span>
                            </td>

                            <!-- Judul Skripsi -->
                            <td class="py-3.5 px-4">
                                <p class="text-slate-800 dark:text-slate-200 font-bold leading-relaxed line-clamp-2" title="{{ $s->judul_skripsi }}">
                                    "{{ $s->judul_skripsi }}"
                                </p>
                            </td>

                            <!-- Dedicated Column: Dosen Pembimbing -->
                            <td class="py-3.5 px-4">
                                <div class="font-extrabold text-slate-800 dark:text-slate-200 text-xs leading-snug">
                                    {{ $s->pembimbingUtama->nama_dosen ?? '-' }}
                                </div>
                                <div class="text-[10px] text-purple-600 dark:text-purple-400 font-semibold mt-0.5">
                                    Pembimbing Utama
                                </div>
                                @if($s->pembimbingPendamping)
                                    <div class="text-[10.5px] font-bold text-slate-700 dark:text-slate-300 mt-1">
                                        {{ $s->pembimbingPendamping->nama_dosen }}
                                    </div>
                                    <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">
                                        Pembimbing Pendamping
                                    </div>
                                @endif
                            </td>

                            <!-- Gelombang & Tanggal -->
                            <td class="py-3.5 px-3">
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">
                                    {{ $s->periode->nama_periode ?? '-' }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-medium mt-0.5">
                                    Tgl Daftar: {{ $s->tanggal_pendaftaran ? \Carbon\Carbon::parse($s->tanggal_pendaftaran)->locale('id')->translatedFormat('l, d/m/Y') : '-' }}
                                </div>
                            </td>

                            <!-- File Persyaratan Pop-Up & Download Trigger -->
                            <td class="py-3.5 px-3 text-center">
                                @if($s->file_persyaratan)
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <button type="button"
                                                @click="previewUrl = '{{ asset($s->file_persyaratan) }}'; previewTitle = 'Berkas Persyaratan - {{ addslashes($s->nama_mahasiswa) }}'; previewModal = true"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/50 hover:bg-purple-100 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-900 text-[11px] font-extrabold transition-all shadow-2xs whitespace-nowrap cursor-pointer">
                                            👁️ Preview
                                        </button>
                                        <a href="{{ asset($s->file_persyaratan) }}" download
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900 text-[11px] font-extrabold transition-all shadow-2xs whitespace-nowrap">
                                            📥 Download
                                        </a>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[10.5px]">Belum diunggah</span>
                                @endif
                            </td>

                            <!-- Status Verifikasi (Badge Pill Prominent) -->
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                @if(($s->verifikasi_status ?? 'menunggu') === 'disetujui')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 shadow-2xs">
                                        ✓ Disetujui
                                    </span>
                                @elseif(($s->verifikasi_status ?? '') === 'ditolak')
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-extrabold px-3 py-1 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800 shadow-2xs">
                                            ✕ Ditolak
                                        </span>
                                        @if($s->verifikasi_komentar)
                                            <span class="text-[9.5px] text-slate-500 dark:text-slate-400 font-semibold max-w-[140px] truncate" title="{{ $s->verifikasi_komentar }}">
                                                Catatan: {{ $s->verifikasi_komentar }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800 shadow-2xs animate-pulse">
                                        ⏳ Menunggu
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi Buttons -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap space-x-1.5">
                                <button type="button" 
                                        @click="selectedSidang = {{ json_encode($s) }}; verifikasiStatus = '{{ $s->verifikasi_status ?? 'disetujui' }}'; verifikasiKomentar = '{{ addslashes($s->verifikasi_komentar ?? '') }}'; verifikasiModal = true" 
                                        style="background-color: #9333ea; color: #ffffff;"
                                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-purple-600 text-white font-extrabold text-xs transition-all shadow-md shadow-purple-600/30 hover:bg-purple-700 border border-purple-500 whitespace-nowrap cursor-pointer">
                                    <span>⚡ Verifikasi</span>
                                </button>
                                <button type="button" 
                                        @click="selectedSidang = {{ json_encode($s) }}; deleteModal = true" 
                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 font-extrabold text-xs transition-all border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900/60 whitespace-nowrap cursor-pointer"
                                        title="Hapus pendaftaran agar mahasiswa dapat daftar ulang">
                                    <span>🗑️ Hapus</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-14 px-6 text-center bg-slate-50/40 dark:bg-slate-900/40">
                                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-2 text-xl">
                                    🎓
                                </div>
                                <h3 class="text-xs font-extrabold text-slate-800 dark:text-slate-200">Tidak Ada Data Pendaftaran Sidang Skripsi</h3>
                                <p class="text-[11px] text-slate-400 mt-1 max-w-sm mx-auto">Data pendaftaran yang diajukan mahasiswa akan ditampilkan di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($sidangs->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40">
                {{ $sidangs->links() }}
            </div>
        @endif
    </div>

    <!-- POP-UP MODAL PREVIEW BUKTI PEMBAYARAN -->
    <div x-show="previewModal" x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-md overflow-y-auto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div @click.away="previewModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full p-6 text-slate-800 dark:text-slate-100 shadow-2xl overflow-hidden relative border border-slate-100 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 font-bold flex items-center justify-center text-sm border border-purple-100 dark:border-purple-900">
                        🖼️
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100" x-text="previewTitle"></h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Pratinjau Berkas Bukti Pembayaran Pendaftaran</p>
                    </div>
                </div>
                <button type="button" @click="previewModal = false" class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center font-bold text-xs transition-colors">✕</button>
            </div>

            <!-- Preview Content -->
            <div class="bg-slate-950 rounded-2xl p-2 flex items-center justify-center min-h-[300px] max-h-[500px] overflow-auto border border-slate-800">
                <template x-if="previewUrl">
                    <img :src="previewUrl" alt="Bukti Pembayaran" class="max-w-full max-h-[460px] rounded-xl object-contain shadow-lg">
                </template>
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <a :href="previewUrl" download target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-950/50 hover:bg-purple-100 text-purple-700 dark:text-purple-300 font-extrabold text-xs rounded-xl border border-purple-200 dark:border-purple-900 transition-all">
                    📥 Unduh File Asli
                </a>
                <button type="button" @click="previewModal = false" class="px-5 py-2 bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white text-xs font-extrabold rounded-xl transition-all">
                    Tutup Pratinjau
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL VERIFIKASI PENDAFTARAN (KAMPUS & COMPACT DESIGN) -->
    <div x-show="verifikasiModal" x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div @click.away="verifikasiModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-4 sm:p-5 text-slate-800 dark:text-slate-100 shadow-2xl overflow-hidden relative border border-slate-100 dark:border-slate-800 space-y-3 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 font-bold flex items-center justify-center text-sm border border-purple-100 dark:border-purple-900">
                        ⚡
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Verifikasi Sidang Skripsi</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Validasi kelayakan pendaftaran & berkas</p>
                    </div>
                </div>
                <button type="button" @click="verifikasiModal = false" class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center font-bold text-xs transition-colors">✕</button>
            </div>

            <!-- Detail Data Mahasiswa Ringkas -->
            <template x-if="selectedSidang">
                <div class="p-3 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-2 text-xs">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-extrabold text-slate-900 dark:text-slate-100 text-xs" x-text="selectedSidang.nama_mahasiswa"></h4>
                            <p class="text-[10px] font-mono font-bold text-purple-600 dark:text-purple-400" x-text="'NIM: ' + selectedSidang.nim"></p>
                        </div>
                        <span class="px-2 py-0.5 rounded-lg bg-purple-100 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300 text-[9.5px] font-bold border border-purple-200 dark:border-purple-800">Teknik Informatika</span>
                    </div>

                    <div class="pt-1.5 border-t border-slate-200/60 dark:border-slate-800">
                        <p class="text-[9.5px] font-bold uppercase tracking-wider text-slate-400">Judul Skripsi</p>
                        <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px] italic mt-0.5 line-clamp-2" x-text="'&quot;' + selectedSidang.judul_skripsi + '&quot;'"></p>
                    </div>

                    <!-- Compact PDF Document Preview & Download -->
                    <div class="pt-1.5 border-t border-slate-200/60 dark:border-slate-800 flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Berkas Persyaratan (PDF):</span>
                        <template x-if="selectedSidang.file_persyaratan">
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                         @click="previewUrl = '{{ asset('') }}' + selectedSidang.file_persyaratan; previewTitle = 'Berkas Persyaratan - ' + selectedSidang.nama_mahasiswa; previewModal = true"
                                         class="inline-flex items-center gap-1 text-[10.5px] text-purple-600 dark:text-purple-400 font-extrabold hover:underline">
                                    👁️ Preview PDF
                                </button>
                                <a :href="'{{ asset('') }}' + selectedSidang.file_persyaratan" download
                                   class="inline-flex items-center gap-1 text-[10.5px] text-emerald-600 dark:text-emerald-400 font-extrabold hover:underline">
                                    📥 Download
                                </a>
                            </div>
                        </template>
                        <template x-if="!selectedSidang.file_persyaratan">
                            <span class="text-[10.5px] text-slate-400 italic">Belum diunggah</span>
                        </template>
                    </div>
                </div>
            </template>

            <form :action="'{{ url('/pendaftaran') }}/' + (selectedSidang ? selectedSidang.id : '') + '/verifikasi'" method="POST" class="space-y-3">
                @csrf
                
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Keputusan Verifikasi <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <label class="flex items-center justify-center gap-1.5 p-2.5 rounded-2xl border cursor-pointer transition-all text-xs"
                               :class="verifikasiStatus === 'disetujui' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-extrabold ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium'">
                            <input type="radio" name="verifikasi_status" value="disetujui" x-model="verifikasiStatus" class="sr-only">
                            <span>✓ Setujui</span>
                        </label>
                        <label class="flex items-center justify-center gap-2 p-2.5 rounded-2xl border cursor-pointer transition-all text-xs"
                               :class="verifikasiStatus === 'ditolak' ? 'border-rose-500 bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 font-extrabold ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium'">
                            <input type="radio" name="verifikasi_status" value="ditolak" x-model="verifikasiStatus" class="sr-only">
                            <span>✕ Tolak</span>
                        </label>
                    </div>
                </div>

                <div x-show="verifikasiStatus === 'ditolak'">
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Catatan Alasan Penolakan <span class="text-rose-500">*</span></label>
                    <textarea name="verifikasi_komentar" x-model="verifikasiKomentar" rows="2" 
                              placeholder="Tuliskan catatan alasan penolakan (misal: Bukti pembayaran kurang jelas)..."
                              class="w-full p-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-medium focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100"></textarea>
                </div>

                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                    <button type="button" @click="verifikasiModal = false" class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-2xl transition-all">
                        Batal
                    </button>
                    <button type="submit" 
                            style="background-color: #9333ea; color: #ffffff;"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-extrabold rounded-2xl transition-all shadow-md border border-purple-500 cursor-pointer">
                        Simpan Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CONFIRM DELETE PENDAFTARAN SKRIPSI -->
    <div x-show="deleteModal" x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div @click.away="deleteModal = false" class="bg-white dark:bg-slate-900 rounded-3xl w-full max-w-md shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden text-left my-auto relative z-50">
            <div class="p-6 text-center space-y-4">
                <div class="w-14 h-14 rounded-2xl bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400 flex items-center justify-center text-2xl mx-auto shadow-inner">
                    🗑️
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Hapus Pendaftaran Skripsi?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        Data pendaftaran mahasiswa <strong class="text-slate-800 dark:text-slate-200" x-text="selectedSidang?.nama_mahasiswa"></strong> (<span x-text="selectedSidang?.nim"></span>) akan dihapus secara permanen.
                    </p>
                    <p class="text-[11px] text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 p-2.5 rounded-xl border border-amber-200 dark:border-amber-800/60 font-semibold mt-3">
                        💡 Setelah dihapus, mahasiswa ini dapat melakukan pendaftaran ulang Sidang Skripsi.
                    </p>
                </div>

                <form :action="'{{ url('/pendaftaran') }}/' + (selectedSidang ? selectedSidang.id : '')" method="POST" class="flex gap-3 pt-2">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteModal = false" class="flex-1 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-extrabold text-xs transition-all">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs transition-all shadow-md shadow-rose-600/30">
                        Ya, Hapus Data
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>


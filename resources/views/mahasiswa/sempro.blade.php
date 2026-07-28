<x-app-layout title="Daftar Sempro">
<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6" x-data="{ regModal: false }">
    <!-- Hero Banner Header -->
    <div class="bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 p-6 rounded-2xl text-white shadow-xl flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold mb-1">Pendaftaran Seminar Proposal (Sempro)</h1>
            <p class="text-xs text-indigo-200">Kelola riwayat dan pengajuan Seminar Proposal Anda dari halaman ini.</p>
        </div>
        <span class="px-3.5 py-1.5 bg-indigo-500/30 border border-indigo-400/30 text-indigo-200 text-xs font-bold rounded-xl shrink-0">
            Sempro Mahasiswa
        </span>
    </div>

@php
    $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
    $activeWave = null;
    if ($activePeriode) {
        $activeWave = \App\Models\PendaftaranPeriode::where('periode_id', $activePeriode->id)
            ->where('jenis', 'sempro')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->first();
    }
    $mySidang = $sidangs->where('jenis_tugas_akhir', 'sempro')->first();
@endphp

    <!-- Top Grid: Status Cards & Card Syarat & Ketentuan Pendaftaran -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Side: Status Periode & Gelombang Cards -->
        <div class="lg:col-span-1 space-y-6 h-fit">
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm p-5 border border-slate-200/80 dark:border-slate-700">
                <h2 class="text-[11px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Periode Akademik Aktif</h2>
                @if($activePeriode)
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                        <p class="font-extrabold text-slate-800 dark:text-slate-200 text-sm">{{ $activePeriode->nama_periode }}</p>
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic">Belum ada periode aktif.</p>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm p-5 border border-slate-200/80 dark:border-slate-700">
                <div>
                    <h2 class="text-[11px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Status Gelombang Sempro</h2>
                    @if($activeWave)
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                            <p class="font-extrabold text-slate-800 dark:text-slate-200 text-sm">Terbuka (Gelombang {{ $activeWave->gelombang }})</p>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Rentang: {{ $activeWave->tanggal_mulai ? $activeWave->tanggal_mulai->format('d M Y') : '-' }} s/d {{ $activeWave->tanggal_selesai ? $activeWave->tanggal_selesai->format('d M Y') : '-' }}</p>
                    @else
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <p class="font-extrabold text-slate-800 dark:text-slate-200 text-sm">Ditutup</p>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Tidak ada gelombang pendaftaran yang aktif saat ini.</p>
                    @endif
                </div>

                @if($activeWave)
                    <div class="mt-4">
                        @if(!$mySidang)
                            <button @click="regModal = true" class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:shadow-lg shadow-indigo-600/20 flex items-center justify-center gap-2 cursor-pointer">
                                <span>📝</span> Ajukan Pendaftaran Sempro
                            </button>
                        @elseif($mySidang->verifikasi_status === 'ditolak')
                            <button @click="regModal = true" class="w-full px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:shadow-lg shadow-rose-600/20 flex items-center justify-center gap-2 cursor-pointer">
                                <span>✏️</span> Revisi Pendaftaran Sempro
                            </button>
                        @else
                            <div class="bg-indigo-100 dark:bg-indigo-900/60 border border-indigo-300 dark:border-indigo-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white font-extrabold flex items-center gap-2">
                                <span>ℹ️</span> Pendaftaran Anda telah terkirim dan sedang diproses.
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Card Syarat & Ketentuan Pendaftaran Sempro -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm p-6 border border-slate-200/80 dark:border-slate-700 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-700 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-base font-bold shrink-0 border border-indigo-200 dark:border-indigo-800">
                        📋
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Syarat & Ketentuan Pendaftaran Seminar Proposal</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Pastikan seluruh dokumen dan kriteria berikut terpenuhi sebelum mendaftar</p>
                    </div>
                </div>

                <ol class="text-xs text-slate-700 dark:text-slate-300 space-y-2 list-decimal pl-4 leading-relaxed font-medium">
                    <li>Telah menyelesaikan minimal <strong>100 SKS</strong> dengan IPK memenuhi syarat.</li>
                    <li>Telah menempuh dan lulus mata kuliah <strong>Metodologi Penelitian</strong>.</li>
                    <li>Memiliki topik/judul proposal tugas akhir yang disetujui Dosen Pembimbing Utama.</li>
                    <li>Memilih Dosen Pembimbing Utama yang telah terdaftar resmi dalam sistem.</li>
                    <li>Melunasi administrasi pendaftaran Seminar Proposal sebesar <strong>Rp 200.000</strong>.</li>
                    <li>Mengunggah seluruh berkas persyaratan gabungan dalam <strong>1 file format PDF (maksimal 4 MB)</strong>.</li>
                </ol>
            </div>

            <!-- Box Informasi Pembayaran BSI -->
            <div class="mt-4 p-3.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800/80 flex items-center justify-between text-xs text-indigo-900 dark:text-indigo-200">
                <div class="flex items-center gap-2">
                    <span class="text-base">💳</span>
                    <div>
                        <p class="font-extrabold">Pembayaran Administrasi (Bank BSI)</p>
                        <p class="text-[11px] text-indigo-700 dark:text-indigo-300">No. Rek: <strong class="font-mono font-bold">7318709593</strong> (A.n. Ahmad Abdul Chamid / Alvin R)</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-indigo-600 text-white font-extrabold text-[10px] rounded-lg shadow-2xs shrink-0">
                    Rp 200.000
                </span>
            </div>
        </div>

    </div>

    {{-- Modal Pendaftaran Seminar Proposal (Proporsional & Compact) --}}
    @if($activeWave)
    <div x-show="regModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto" 
         x-cloak 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div @click.away="regModal = false" class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden text-left my-auto transform transition-all relative z-50">
            
            {{-- Modal Header --}}
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between bg-slate-50/80 dark:bg-slate-900/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                        📝
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Form Pendaftaran Seminar Proposal</h3>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Isi formulir dengan data yang lengkap dan valid</p>
                    </div>
                </div>
                <button @click="regModal = false" class="w-7 h-7 rounded-full bg-slate-200/70 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 flex items-center justify-center text-xs font-bold transition-all">✕</button>
            </div>

            {{-- Compact Form Body --}}
            <form id="form-daftar-sempro" method="POST" action="{{ route('mahasiswa.daftar.store') }}" enctype="multipart/form-data" class="p-5 space-y-3.5">
                @csrf
                <input type="hidden" name="jenis_tugas_akhir" value="sempro">
                
                {{-- NIM & Nama (Readonly) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">NIM Mahasiswa <span class="text-rose-500">*</span></label>
                        <input type="text" name="nim" value="{{ $user->nim ?? '' }}" readonly class="w-full px-3 py-1.5 bg-slate-100 dark:bg-slate-700/80 border border-slate-200 dark:border-slate-600 rounded-lg text-xs text-slate-600 dark:text-slate-300 font-semibold cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Nama Mahasiswa</label>
                        <input type="text" value="{{ $user->name }}" readonly class="w-full px-3 py-1.5 bg-slate-100 dark:bg-slate-700/80 border border-slate-200 dark:border-slate-600 rounded-lg text-xs text-slate-600 dark:text-slate-300 font-medium cursor-not-allowed">
                    </div>
                </div>

                {{-- Judul Proposal --}}
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Judul Proposal Tugas Akhir <span class="text-rose-500">*</span></label>
                    <textarea name="judul_skripsi" required rows="2" placeholder="Masukkan judul proposal tugas akhir Anda..." class="w-full px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-500 transition-all text-slate-800 dark:text-slate-200 font-medium leading-relaxed bg-white dark:bg-slate-700 placeholder-slate-400">{{ $mySidang->judul_skripsi ?? '' }}</textarea>
                </div>

                {{-- Jenis TA --}}
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Jenis Tugas Akhir <span class="text-rose-500">*</span></label>
                    <select name="jenis_ta_pilihan" required class="w-full px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-500 transition-all text-slate-800 dark:text-slate-200 font-semibold cursor-pointer bg-white dark:bg-slate-700">
                        <option value="">-- Pilih Jenis Tugas Akhir --</option>
                        <option value="sidang" {{ ($mySidang && $mySidang->jenis_tugas_akhir == 'sidang') ? 'selected' : '' }}>Sidang Skripsi</option>
                        <option value="jurnal" {{ ($mySidang && $mySidang->jenis_tugas_akhir == 'jurnal') ? 'selected' : '' }}>Jurnal / Artikel</option>
                    </select>
                </div>

                {{-- Dosbing Utama & Pendamping --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Dosbing Utama <span class="text-rose-500">*</span></label>
                        <select name="dosen_pembimbing_utama_id" required class="w-full px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-500 transition-all text-slate-800 dark:text-slate-200 font-semibold cursor-pointer bg-white dark:bg-slate-700">
                            <option value="">-- Pilih Dosbing Utama --</option>
                            @foreach($dosens as $d)
                                <option value="{{ $d->id }}" {{ ($mySidang && $mySidang->dosen_pembimbing_utama_id == $d->id) ? 'selected' : '' }}>{{ $d->nama_dosen }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Dosbing Pendamping</label>
                        <select name="dosen_pembimbing_pendamping_id" class="w-full px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-500 transition-all text-slate-800 dark:text-slate-200 font-semibold cursor-pointer bg-white dark:bg-slate-700">
                            <option value="">-- Tidak Ada --</option>
                            @foreach($dosens as $d)
                                <option value="{{ $d->id }}" {{ ($mySidang && $mySidang->dosen_pembimbing_pendamping_id == $d->id) ? 'selected' : '' }}>{{ $d->nama_dosen }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- No WA & Tanggal Pendaftaran --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">No. WhatsApp Aktif <span class="text-rose-500">*</span></label>
                        <input type="text" name="no_wa_aktif" value="{{ $mySidang->no_wa_aktif ?? '' }}" required placeholder="Contoh: 0812xxxxxxxx" class="w-full px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-500 transition-all text-slate-800 dark:text-slate-200 font-semibold bg-white dark:bg-slate-700 placeholder-slate-400">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Tanggal Pendaftaran</label>
                        <input type="text" value="{{ now()->timezone('Asia/Jakarta')->format('d M Y') }}" readonly class="w-full px-3 py-1.5 bg-slate-100 dark:bg-slate-700/80 border border-slate-200 dark:border-slate-600 rounded-lg text-xs text-slate-600 dark:text-slate-300 font-semibold cursor-not-allowed">
                    </div>
                </div>

                {{-- File Upload PDF Only --}}
                <div x-data="{
                        isDragging: false,
                        fileName: '',
                        fileSize: '',
                        fileError: '',
                        existingFile: '{{ ($mySidang && $mySidang->file_persyaratan) ? asset($mySidang->file_persyaratan) : '' }}',
                        handleFileSelect(e) {
                            const file = e.target.files[0] || (e.dataTransfer ? e.dataTransfer.files[0] : null);
                            if (!file) return;
                            if (file.type !== 'application/pdf') {
                                this.fileError = 'Hanya file PDF yang diterima!';
                                this.$refs.fileInputSempro.value = '';
                                return;
                            }
                            if (file.size > 4 * 1024 * 1024) {
                                this.fileError = 'Ukuran file maksimal 4 MB!';
                                this.$refs.fileInputSempro.value = '';
                                return;
                            }
                            this.fileError = '';
                            this.fileName = file.name;
                            this.fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                        },
                        clearFile() {
                            this.fileName = ''; this.fileSize = ''; this.fileError = '';
                            if (this.$refs.fileInputSempro) this.$refs.fileInputSempro.value = '';
                        }
                     }">
                    <label class="block text-[10px] font-extrabold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        File Persyaratan (PDF) <span class="text-rose-500">*</span>
                        <span class="text-[9px] text-slate-400 font-normal normal-case ml-1">(PDF Only, Maks 4 MB)</span>
                    </label>
                    
                    <div class="relative border border-dashed rounded-lg p-2.5 text-center transition-all duration-200 cursor-pointer"
                         :class="isDragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30' : (fileName ? 'border-emerald-400 bg-emerald-50/20 dark:bg-emerald-950/20' : 'border-slate-300 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-700/30 hover:border-indigo-500 hover:bg-indigo-50/20')"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; if ($event.dataTransfer.files.length) { $refs.fileInputSempro.files = $event.dataTransfer.files; handleFileSelect($event); }">
                        
                        <input type="file" 
                                name="file_persyaratan" 
                                x-ref="fileInputSempro"
                                @change="handleFileSelect($event)"
                                accept="application/pdf"
                                {{ ($mySidang && $mySidang->file_persyaratan) ? '' : 'required' }} 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                        <template x-if="!fileName">
                            <div class="flex items-center justify-center gap-2 py-0.5">
                                <div class="w-6 h-6 rounded bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs shrink-0 font-bold border border-indigo-200 dark:border-indigo-800">
                                    📄
                                </div>
                                <div class="text-left">
                                    <p class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Pilih berkas PDF persyaratan</p>
                                    <p class="text-[9px] text-slate-400 font-medium">Klik atau drop file PDF di sini (maks 4 MB)</p>
                                </div>
                            </div>
                        </template>

                        <template x-if="fileName">
                            <div class="flex items-center justify-between p-1 bg-white dark:bg-slate-700 border border-emerald-200 dark:border-emerald-700 rounded shadow-2xs z-20 relative">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-5 h-5 rounded bg-rose-600 text-white font-bold flex items-center justify-center text-[8px] uppercase shrink-0">
                                        PDF
                                    </div>
                                    <p class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate max-w-[180px] text-left" x-text="fileName"></p>
                                    <span class="text-[9px] text-slate-400 font-medium shrink-0" x-text="fileSize"></span>
                                </div>
                                <button type="button" @click.stop="clearFile()" class="p-0.5 text-slate-400 hover:text-rose-500 rounded transition-colors z-20" title="Batal">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                    <div x-show="fileError" class="mt-1 text-[10px] font-bold text-rose-600" x-text="fileError"></div>

                    <template x-if="!fileName && existingFile">
                        <div class="mt-1.5 flex items-center justify-between px-2 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold">
                            <span>📄 File Persyaratan Terunggah:</span>
                            <a :href="existingFile" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline font-extrabold" @click.stop>
                                Preview File ↗
                            </a>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="regModal = false" class="px-3.5 py-1.5 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-xs rounded-xl transition-all">Batal</button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md shadow-indigo-600/20 flex items-center gap-1.5 cursor-pointer">
                        <span>🚀</span> Kirim Pendaftaran
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- History Table -->
    <div class="bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-6">
        <h2 class="text-base font-extrabold text-slate-800 dark:text-slate-200 mb-4">Riwayat Pendaftaran Sempro</h2>
        @if($sidangs->isEmpty())
            <div class="text-center py-8 bg-slate-50 dark:bg-slate-700/30 rounded-xl border border-dashed border-slate-200 dark:border-slate-600">
                <p class="text-xs text-slate-500 dark:text-slate-400 italic">Belum ada data pendaftaran Seminar Proposal.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                            <th class="py-3 px-4 font-bold">#</th>
                            <th class="py-3 px-4 font-bold">NIM / Nama</th>
                            <th class="py-3 px-4 font-bold">Judul Sempro</th>
                            <th class="py-3 px-4 font-bold">No. WA</th>
                            <th class="py-3 px-4 font-bold">Tgl. Daftar</th>
                            <th class="py-3 px-4 font-bold">Ruang</th>
                            <th class="py-3 px-4 font-bold text-center">Status</th>
                            <th class="py-3 px-4 font-bold text-center">File Persyaratan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-xs font-medium">
                        @foreach($sidangs as $i => $s)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30">
                                <td class="py-3 px-4 text-slate-400 dark:text-slate-500 text-xs">{{ $i + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-200">{{ $s->nim }}<br><span class="text-[11px] text-slate-500 dark:text-slate-400 font-normal">{{ $s->nama_mahasiswa }}</span></td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300 max-w-xs truncate" title="{{ $s->judul_skripsi }}">{{ $s->judul_skripsi }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400 text-xs font-mono">{{ $s->no_wa_aktif ?? '-' }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400 font-semibold">{{ $s->tanggal_pendaftaran ? \Carbon\Carbon::parse($s->tanggal_pendaftaran)->locale('id')->translatedFormat('l, d/m/Y') : '-' }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $s->ruang->kode_ruangan ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    {!! $s->getVerifikasiStatusHtml() !!}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($s->file_persyaratan)
                                        <a href="{{ asset($s->file_persyaratan) }}" target="_blank" class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold rounded-lg border border-indigo-200 dark:border-indigo-700 transition-all flex items-center gap-1 shadow-2xs justify-center">
                                            <span>📄</span> Preview
                                        </a>
                                    @elseif($s->bukti_pembayaran)
                                        <a href="{{ asset($s->bukti_pembayaran) }}" target="_blank" class="px-2.5 py-1 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 text-slate-600 dark:text-slate-300 text-[11px] font-bold rounded-lg border border-slate-200 dark:border-slate-600 transition-all flex items-center gap-1 shadow-2xs justify-center">
                                            <span>📄</span> Lihat Bukti
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400 font-normal italic">Belum diupload</span>
                                    @endif
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

@push('scripts')
<script>
    const CSRF_MAHASISWA = '{{ csrf_token() }}';
    const TOAST_ICONS  = { success:'✅', error:'❌', warning:'⚠️', info:'ℹ️' };
    const TOAST_TITLES = { success:'Berhasil!', error:'Gagal!', warning:'Peringatan', info:'Informasi' };

    function showToastMhs(type, message) {
        const c = document.getElementById('toast-container');
        if (!c) { alert(message); return; }
        const t = document.createElement('div');
        t.className = `toast toast-${type}`;
        t.innerHTML = `<span class="toast-icon">${TOAST_ICONS[type]||'ℹ️'}</span><div class="toast-body"><div class="toast-title">${TOAST_TITLES[type]}</div><div class="toast-msg">${message}</div></div><button class="toast-close" onclick="this.parentElement.remove()">✕</button>`;
        c.appendChild(t);
        setTimeout(() => t.remove(), 5000);
    }

    @if(session('success')) showToastMhs('success', @json(session('success'))); @endif
    @if(session('error'))   showToastMhs('error',   @json(session('error')));   @endif
    @if(session('warning')) showToastMhs('warning', @json(session('warning'))); @endif

    // Pendaftaran Sempro (AJAX)
    const formDaftar = document.getElementById('form-daftar-sempro');
    if (formDaftar) {
        formDaftar.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'), orig = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '⏳ Mengirim…';
            try {
                const res = await fetch(this.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF_MAHASISWA, 'Accept': 'application/json' }, body: new FormData(this) });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToastMhs('success', data.message || 'Pendaftaran berhasil dikirim!');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToastMhs(res.status === 422 ? 'warning' : 'error', data.message || 'Terjadi kesalahan.');
                }
            } catch(err) { showToastMhs('error', 'Gagal terhubung ke server.'); }
            finally { btn.disabled = false; btn.innerHTML = orig; }
        });
    }
</script>
@endpush

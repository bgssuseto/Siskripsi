<x-app-layout title="Daftar Skripsi">
<div class="max-w-7xl mx-auto p-6 space-y-6" x-data="{ regModal: false }">
    <div class="bg-gradient-to-r from-purple-900 via-slate-900 to-indigo-950 p-6 rounded-2xl text-white shadow-xl flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold mb-1">Pendaftaran Sidang Skripsi</h1>
            <p class="text-xs text-purple-200">Kelola riwayat dan pendaftaran Sidang Skripsi Tugas Akhir Anda.</p>
        </div>
        <span class="px-3.5 py-1.5 bg-purple-500/30 border border-purple-400/30 text-purple-200 text-xs font-bold rounded-xl">
            Sidang Skripsi
        </span>
    </div>

@php
    $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
    $activeWave = null;
    if ($activePeriode) {
        $activeWave = \App\Models\PendaftaranPeriode::where('periode_id', $activePeriode->id)
            ->where('jenis', 'skripsi')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->first();
    }
    $mySidang = $sidangs->first();
@endphp

    <!-- Top Grid: Status Cards & Card Syarat & Ketentuan Pendaftaran -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Side: Status Periode & Gelombang Cards -->
        <div class="lg:col-span-1 space-y-6 flex flex-col justify-between">
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

            <div class="bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm p-5 border border-slate-200/80 dark:border-slate-700 flex-1 flex flex-col justify-between">
                <div>
                    <h2 class="text-[11px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Status Gelombang Skripsi</h2>
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
                        @if(!$hasSempro)
                            <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl p-3.5 text-xs text-amber-800 dark:text-amber-300 font-semibold flex items-start gap-2.5">
                                <span class="text-base shrink-0">⚠️</span>
                                <div>
                                    <p class="font-extrabold">Belum Mendaftar Sempro</p>
                                    <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5 leading-relaxed">
                                        Anda belum mendaftar Seminar Proposal (Sempro). Pendaftaran Sidang Skripsi hanya dapat dilakukan setelah Anda mendaftar Sempro.
                                    </p>
                                    <a href="{{ route('mahasiswa.sempro.index') }}" class="inline-flex items-center gap-1 mt-2 text-[11px] font-extrabold text-amber-900 dark:text-amber-200 hover:underline">
                                        <span>👉</span> Ke Halaman Pendaftaran Sempro ↗
                                    </a>
                                </div>
                            </div>
                        @elseif(!$mySidang)
                            <button @click="regModal = true" style="background-color: #9333ea; color: #ffffff;" class="w-full px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md hover:shadow-lg shadow-purple-600/20 flex items-center justify-center gap-2 cursor-pointer border border-purple-500">
                                <span>📝</span> Ajukan Pendaftaran Sidang Skripsi
                            </button>
                        @elseif($mySidang->verifikasi_status === 'ditolak')
                            <button @click="regModal = true" style="background-color: #e11d48; color: #ffffff;" class="w-full px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md hover:shadow-lg shadow-rose-600/20 flex items-center justify-center gap-2 cursor-pointer border border-rose-500">
                                <span>✏️</span> Revisi Pendaftaran Skripsi
                            </button>
                        @else
                            <div class="bg-purple-50/60 dark:bg-purple-950/40 border border-purple-200/80 dark:border-purple-800 rounded-xl p-3 text-xs text-purple-800 dark:text-purple-300 font-semibold flex items-center gap-2">
                                <span>ℹ️</span> Pendaftaran Anda telah terkirim dan sedang diproses.
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Card Syarat & Ketentuan Pendaftaran Skripsi -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm p-6 border border-slate-200/80 dark:border-slate-700 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-700 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-base font-bold shrink-0 border border-purple-200 dark:border-purple-800">
                        📋
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Syarat & Ketentuan Sidang Skripsi</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Pastikan seluruh kriteria & dokumen berikut terpenuhi secara lengkap:</p>
                    </div>
                </div>

                <ol class="text-xs text-slate-700 dark:text-slate-300 space-y-2 list-decimal pl-4 leading-relaxed font-medium columns-1 sm:columns-2 gap-x-6">
                    <li>Scan surat pendaftaran skripsi bertandatangan kaprodi.</li>
                    <li>Scan transkrip nilai resmi dari BAAK.</li>
                    <li>Tidak terdapat nilai <strong>E</strong>.</li>
                    <li>Nilai <strong>D</strong> maksimal 14 SKS.</li>
                    <li>Nilai Metodologi Penelitian minimal <strong>BC</strong>.</li>
                    <li>Nilai Pancasila minimal <strong>BC</strong>.</li>
                    <li>Nilai Kewarganegaraan minimal <strong>BC</strong>.</li>
                    <li>Nilai Bahasa Indonesia minimal <strong>C</strong>.</li>
                    <li>Nilai Pendidikan Agama minimal <strong>C</strong>.</li>
                    <li>Scan KRS semester berjalan.</li>
                    <li>Total SKS minimal <strong>138 SKS</strong>.</li>
                    <li>Surat pengumpulan proposal.</li>
                    <li>Hasil Turnitin maksimal <strong>25%</strong>.</li>
                    <li>Halaman persetujuan yang telah ditandatangani.</li>
                    <li>Lampiran ACC pada buku bimbingan.</li>
                </ol>
                <p class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">Semua dokumen digabung dalam <strong>1 file PDF (maks. 4 MB)</strong>. Format nama file: <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded text-purple-700 dark:text-purple-300 font-mono">NIM_NAMA_SKRIPSI.pdf</code></p>
            </div>

            <!-- Box Informasi Pembayaran BSI -->
            <div class="mt-4 p-3.5 rounded-xl bg-purple-50/70 dark:bg-purple-950/40 border border-purple-100 dark:border-purple-800/80 flex items-center justify-between text-xs text-purple-900 dark:text-purple-200">
                <div class="flex items-center gap-2">
                    <span class="text-base">💳</span>
                    <div>
                        <p class="font-extrabold">Pembayaran Administrasi (Bank BSI)</p>
                        <p class="text-[11px] text-purple-700 dark:text-purple-300">No. Rek: <strong class="font-mono font-bold">7318709593</strong> (A.n. Ahmad Abdul Chamid / Alvin R)</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-purple-600 text-white font-extrabold text-[10px] rounded-lg shadow-2xs shrink-0">
                    Rp 200.000
                </span>
            </div>
        </div>

    </div>

    {{-- Modal Pendaftaran Sidang Skripsi --}}
    @if($activeWave && $hasSempro)
    <div x-show="regModal" 
         class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto" 
         x-cloak 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div @click.away="regModal = false" class="bg-white dark:bg-slate-800 rounded-3xl w-full max-w-xl shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden text-left my-6 transform transition-all relative z-50">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between bg-slate-50/80 dark:bg-slate-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center text-base font-bold shadow-sm">
                        🎓
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Form Pendaftaran Sidang Skripsi</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Lengkapi formulir pendaftaran dengan data yang valid</p>
                    </div>
                </div>
                <button @click="regModal = false" class="w-8 h-8 rounded-full bg-slate-200/70 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-600 flex items-center justify-center text-xs font-bold transition-all">✕</button>
            </div>

            <form id="form-daftar-skripsi" method="POST" action="{{ route('mahasiswa.daftar.store') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="jenis_tugas_akhir" value="skripsi">
                
                {{-- NIM & Nama --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">NIM Mahasiswa <span class="text-rose-500">*</span></label>
                        <input type="text" name="nim" value="{{ $user->nim ?? '' }}" readonly class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-600 dark:text-slate-300 font-semibold cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Mahasiswa</label>
                        <input type="text" value="{{ $user->name }}" readonly class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-600 dark:text-slate-300 font-medium cursor-not-allowed">
                    </div>
                </div>

                {{-- Judul Skripsi --}}
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Judul Tugas Akhir / Skripsi <span class="text-rose-500">*</span></label>
                    <textarea name="judul_skripsi" required rows="3" placeholder="Masukkan judul skripsi Anda..." class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all text-slate-800 dark:text-slate-200 font-medium leading-relaxed bg-white dark:bg-slate-700">{{ $mySidang->judul_skripsi ?? ($semproRecord->judul_skripsi ?? '') }}</textarea>
                </div>

                {{-- Jenis TA --}}
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Jenis Tugas Akhir <span class="text-rose-500">*</span></label>
                    <select name="jenis_ta_pilihan" required class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all text-slate-800 dark:text-slate-200 font-semibold cursor-pointer bg-white dark:bg-slate-700">
                        <option value="">-- Pilih Jenis Tugas Akhir --</option>
                        <option value="sidang" {{ (($mySidang->jenis_tugas_akhir ?? '') == 'sidang') ? 'selected' : 'selected' }}>Sidang Skripsi</option>
                        <option value="jurnal" {{ (($mySidang->jenis_tugas_akhir ?? '') == 'jurnal') ? 'selected' : '' }}>Jurnal</option>
                    </select>
                </div>

                {{-- Dosbing Utama & Pendamping --}}
                @php
                    $defaultUtamaId = $mySidang->dosen_pembimbing_utama_id ?? ($semproRecord->dosen_pembimbing_utama_id ?? null);
                    $defaultPendampingId = $mySidang->dosen_pembimbing_pendamping_id ?? ($semproRecord->dosen_pembimbing_pendamping_id ?? null);
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Dosbing Utama <span class="text-rose-500">*</span></label>
                        <select name="dosen_pembimbing_utama_id" required class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all text-slate-800 dark:text-slate-200 font-semibold cursor-pointer bg-white dark:bg-slate-700">
                            <option value="">-- Pilih Dosen Pembimbing Utama --</option>
                            @foreach($dosens as $d)
                                <option value="{{ $d->id }}" {{ ($defaultUtamaId == $d->id) ? 'selected' : '' }}>{{ $d->nama_dosen }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Dosbing Pendamping</label>
                        <select name="dosen_pembimbing_pendamping_id" class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all text-slate-800 dark:text-slate-200 font-semibold cursor-pointer bg-white dark:bg-slate-700">
                            <option value="">-- Tidak Ada --</option>
                            @foreach($dosens as $d)
                                <option value="{{ $d->id }}" {{ ($defaultPendampingId == $d->id) ? 'selected' : '' }}>{{ $d->nama_dosen }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- No WA & Tanggal Pendaftaran --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">No. WhatsApp Aktif <span class="text-rose-500">*</span></label>
                        <input type="text" name="no_wa_aktif" value="{{ $mySidang->no_wa_aktif ?? '' }}" required placeholder="Contoh: 0812xxxxxxxx" class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all text-slate-800 dark:text-slate-200 font-semibold bg-white dark:bg-slate-700 placeholder-slate-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Tanggal Pendaftaran</label>
                        <input type="text" value="{{ now()->timezone('Asia/Jakarta')->format('d M Y') }}" readonly class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-600 dark:text-slate-300 font-semibold cursor-not-allowed">
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
                                this.$refs.fileInputSkripsi.value = '';
                                return;
                            }
                            if (file.size > 4 * 1024 * 1024) {
                                this.fileError = 'Ukuran file maksimal 4 MB!';
                                this.$refs.fileInputSkripsi.value = '';
                                return;
                            }
                            this.fileError = '';
                            this.fileName = file.name;
                            this.fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                        },
                        clearFile() {
                            this.fileName = ''; this.fileSize = ''; this.fileError = '';
                            if (this.$refs.fileInputSkripsi) this.$refs.fileInputSkripsi.value = '';
                        }
                     }">
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                        File Persyaratan (PDF) <span class="text-rose-500">*</span>
                        <span class="text-[9px] text-slate-400 font-normal normal-case ml-1">(Hanya PDF, Maks 4 MB)</span>
                    </label>
                    
                    <div class="relative border border-dashed rounded-xl p-3 text-center transition-all duration-200 cursor-pointer"
                         :class="isDragging ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : (fileName ? 'border-emerald-400 bg-emerald-50/20 dark:bg-emerald-900/10' : 'border-slate-300 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-700/30 hover:border-purple-500 hover:bg-purple-50/20')"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; if ($event.dataTransfer.files.length) { $refs.fileInputSkripsi.files = $event.dataTransfer.files; handleFileSelect($event); }">
                        
                        <input type="file" 
                                name="file_persyaratan" 
                                x-ref="fileInputSkripsi"
                                @change="handleFileSelect($event)"
                                accept="application/pdf"
                                {{ ($mySidang && $mySidang->file_persyaratan) ? '' : 'required' }} 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                        <template x-if="!fileName">
                            <div class="flex items-center justify-center gap-2 py-1">
                                <div class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center text-sm shrink-0 border border-purple-100 dark:border-purple-700">
                                    📄
                                </div>
                                <div class="text-left">
                                    <p class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Pilih berkas PDF persyaratan</p>
                                    <p class="text-[9px] text-slate-400 font-medium">Klik atau drop berkas PDF di sini (maks. 4 MB)</p>
                                </div>
                            </div>
                        </template>

                        <template x-if="fileName">
                            <div class="flex items-center justify-between p-1 bg-white dark:bg-slate-700 border border-emerald-200 dark:border-emerald-700 rounded-lg shadow-xs z-20 relative">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-6 h-6 rounded bg-red-600 text-white font-bold flex items-center justify-center text-[9px] uppercase shrink-0">
                                        PDF
                                    </div>
                                    <p class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate max-w-[200px] text-left" x-text="fileName"></p>
                                    <span class="text-[9px] text-slate-400 font-medium shrink-0" x-text="fileSize"></span>
                                </div>
                                <button type="button" @click.stop="clearFile()" class="p-1 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded transition-colors z-20" title="Batal">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                    <div x-show="fileError" class="mt-1 text-[10px] font-bold text-rose-600" x-text="fileError"></div>

                    <template x-if="!fileName && existingFile">
                        <div class="mt-1.5 flex items-center gap-1.5 px-2 py-1 rounded-lg bg-purple-50 dark:bg-purple-900/30 border border-purple-100 dark:border-purple-700 text-purple-700 dark:text-purple-300 text-[10px] font-bold">
                            <span>📄 File Terunggah:</span>
                            <a :href="existingFile" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline font-extrabold" @click.stop>
                                Preview File ↗
                            </a>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="regModal = false" class="px-3 py-1.5 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-[11px] rounded-lg transition-all">Batal</button>
                    <button type="submit"
                            style="background-color: #9333ea; color: #ffffff; border: none;"
                            class="px-5 py-2 font-extrabold text-[12px] rounded-lg transition-all shadow-md flex items-center gap-1.5 hover:opacity-90 active:scale-95 cursor-pointer">
                        <span>🚀</span> Kirim Pendaftaran
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- History Table -->
    <div class="bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-6">
        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-4">Riwayat Sidang Skripsi</h2>
        @if($sidangs->isEmpty())
            <div class="text-center py-8 bg-slate-50 dark:bg-slate-700/30 rounded-xl border border-dashed border-slate-200 dark:border-slate-600">
                <p class="text-sm text-slate-500 dark:text-slate-400 italic">Belum ada data pendaftaran Sidang Skripsi.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                            <th class="py-3 px-4 font-bold">#</th>
                            <th class="py-3 px-4 font-bold">Judul Skripsi</th>
                            <th class="py-3 px-4 font-bold">Jenis TA</th>
                            <th class="py-3 px-4 font-bold">Dosbing Utama</th>
                            <th class="py-3 px-4 font-bold">No. WA</th>
                            <th class="py-3 px-4 font-bold">Tgl. Daftar</th>
                            <th class="py-3 px-4 font-bold">Ruang</th>
                            <th class="py-3 px-4 font-bold text-center">Status</th>
                            <th class="py-3 px-4 font-bold text-center">File</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-sm font-medium">
                        @foreach($sidangs as $i => $s)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30">
                                <td class="py-3 px-4 text-slate-400 dark:text-slate-500 text-xs">{{ $i + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-200 max-w-xs truncate" title="{{ $s->judul_skripsi }}">{{ $s->judul_skripsi }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg {{ $s->jenis_tugas_akhir === 'jurnal' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' }}">
                                        {{ $s->jenis_tugas_akhir === 'jurnal' ? 'Jurnal' : 'Sidang Skripsi' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $s->pembimbingUtama->nama_dosen ?? '-' }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400 text-xs font-mono">{{ $s->no_wa_aktif ?? '-' }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400 font-semibold">{{ $s->tanggal_pendaftaran ? \Carbon\Carbon::parse($s->tanggal_pendaftaran)->locale('id')->translatedFormat('l, d/m/Y') : '-' }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $s->ruang->kode_ruangan ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    {!! $s->getVerifikasiStatusHtml() !!}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($s->file_persyaratan)
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a href="{{ asset($s->file_persyaratan) }}" target="_blank" class="px-2.5 py-1 bg-purple-50 dark:bg-purple-900/30 hover:bg-purple-100 text-purple-700 dark:text-purple-300 text-[11px] font-bold rounded-lg border border-purple-200 dark:border-purple-700 transition-all flex items-center gap-1 shadow-sm">
                                                <span>👁️</span> Preview
                                            </a>
                                            <a href="{{ asset($s->file_persyaratan) }}" download class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-900/30 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 text-[11px] font-bold rounded-lg border border-emerald-200 dark:border-emerald-700 transition-all flex items-center gap-1 shadow-sm">
                                                <span>📥</span>
                                            </a>
                                        </div>
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
    const CSRF_MHS_SKRIPSI = '{{ csrf_token() }}';
    const TOAST_ICONS_S  = { success:'✅', error:'❌', warning:'⚠️', info:'ℹ️' };
    const TOAST_TITLES_S = { success:'Berhasil!', error:'Gagal!', warning:'Peringatan', info:'Informasi' };

    function showToastSkripsi(type, message) {
        const c = document.getElementById('toast-container');
        if (!c) { alert(message); return; }
        const t = document.createElement('div');
        t.className = `toast toast-${type}`;
        t.innerHTML = `<span class="toast-icon">${TOAST_ICONS_S[type]||'ℹ️'}</span><div class="toast-body"><div class="toast-title">${TOAST_TITLES_S[type]}</div><div class="toast-msg">${message}</div></div><button class="toast-close" onclick="this.parentElement.remove()">✕</button>`;
        c.appendChild(t);
        setTimeout(() => t.remove(), 5000);
    }

    @if(session('success')) showToastSkripsi('success', @json(session('success'))); @endif
    @if(session('error'))   showToastSkripsi('error',   @json(session('error')));   @endif
    @if(session('warning')) showToastSkripsi('warning', @json(session('warning'))); @endif

    // Pendaftaran Skripsi (AJAX)
    const formDaftarSkripsi = document.getElementById('form-daftar-skripsi');
    if (formDaftarSkripsi) {
        formDaftarSkripsi.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'), orig = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '⏳ Mengirim…';
            try {
                const res = await fetch(this.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF_MHS_SKRIPSI, 'Accept': 'application/json' }, body: new FormData(this) });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToastSkripsi('success', data.message || 'Pendaftaran berhasil dikirim!');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToastSkripsi(res.status === 422 ? 'warning' : 'error', data.message || 'Terjadi kesalahan.');
                }
            } catch(err) { showToastSkripsi('error', 'Gagal terhubung ke server.'); }
            finally { btn.disabled = false; btn.innerHTML = orig; }
        });
    }
</script>
@endpush

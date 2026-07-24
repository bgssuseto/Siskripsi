<x-app-layout title="Daftar Skripsi">
<div class="max-w-7xl mx-auto p-6 space-y-6" x-data="{ regModal: false, editBuktiModal: false, editBuktiAction: '', editBuktiUrl: '' }">
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

    <!-- Active Period & Registration Wave Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200/80">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Periode Akademik Aktif</h2>
            @if($activePeriode)
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                    <p class="font-extrabold text-slate-800">{{ $activePeriode->nama_periode }}</p>
                </div>
            @else
                <p class="text-sm text-slate-400 italic">Belum ada periode aktif.</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200/80 flex flex-col justify-between">
            <div>
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status Pendaftaran Sidang Skripsi</h2>
                @if($activeWave)
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                        <p class="font-extrabold text-slate-800">Terbuka (Gelombang {{ $activeWave->gelombang }})</p>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Rentang: {{ $activeWave->tanggal_mulai->translatedFormat('d M Y') }} s/d {{ $activeWave->tanggal_selesai->translatedFormat('d M Y') }}</p>
                @else
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                        <p class="font-extrabold text-slate-800">Ditutup</p>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Tidak ada gelombang pendaftaran yang aktif saat ini.</p>
                @endif
            </div>

            @if($activeWave)
                <div class="mt-4">
                    @if(!$mySidang)
                        <button @click="regModal = true" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:shadow-lg shadow-indigo-600/10">
                            Ajukan Pendaftaran Sidang Skripsi
                        </button>
                    @elseif($mySidang->verifikasi_status === 'ditolak')
                        <button @click="regModal = true" class="w-full sm:w-auto px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:shadow-lg shadow-rose-600/10">
                            Revisi Pendaftaran Sidang Skripsi
                        </button>
                    @else
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-500 font-semibold flex items-center gap-2">
                            <span>ℹ️</span> Anda sudah mengajukan pendaftaran untuk periode ini.
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- True Screen-Centered Pop-Up Modal Pendaftaran Sidang Skripsi --}}
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
        <div @click.away="regModal = false" class="bg-white rounded-2xl w-full max-w-[400px] shadow-2xl border border-slate-100 overflow-hidden text-left my-auto transform transition-all relative z-50">
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center text-sm font-bold shadow-sm">
                        🎓
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Form Pendaftaran Sidang Skripsi</h3>
                        <p class="text-[11px] text-slate-500">Lengkapi formulir dengan data yang valid</p>
                    </div>
                </div>
                <button @click="regModal = false" class="w-6 h-6 rounded-full bg-slate-200/70 text-slate-500 hover:text-slate-800 hover:bg-slate-300 flex items-center justify-center text-xs font-bold transition-all">✕</button>
                       <form id="form-daftar-skripsi" method="POST" action="{{ route('mahasiswa.daftar.store') }}" enctype="multipart/form-data" class="p-4 space-y-3">
                @csrf
                <input type="hidden" name="jenis_tugas_akhir" value="skripsi">
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">NIM Mahasiswa <span class="text-rose-500">*</span></label>
                        <input type="text" name="nim" value="{{ $mySidang->nim ?? $user->nim }}" required placeholder="NIM..." class="w-full px-2.5 py-1 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all text-slate-800 font-semibold bg-slate-50/50">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Mahasiswa</label>
                        <input type="text" value="{{ $user->name }}" readonly class="w-full px-2.5 py-1 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-500 font-medium cursor-not-allowed">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Judul Tugas Akhir / Skripsi <span class="text-rose-500">*</span></label>
                    <textarea name="judul_skripsi" required rows="2" placeholder="Masukkan judul skripsi Anda..." class="w-full px-2.5 py-1 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all text-slate-800 font-medium leading-relaxed">{{ $mySidang->judul_skripsi ?? '' }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dosbing Utama <span class="text-rose-500">*</span></label>
                        <select name="dosen_pembimbing_utama_id" required class="w-full px-2.5 py-1 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all text-slate-800 font-semibold cursor-pointer bg-white">
                            <option value="">-- Pilih --</option>
                            @foreach($dosens as $d)
                                <option value="{{ $d->id }}" {{ ($mySidang && $mySidang->dosen_pembimbing_utama_id == $d->id) ? 'selected' : '' }}>{{ $d->nama_dosen }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dosbing Pendamping</label>
                        <select name="dosen_pembimbing_pendamping_id" class="w-full px-2.5 py-1 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all text-slate-800 font-semibold cursor-pointer bg-white">
                            <option value="">-- Tidak Ada --</option>
                            @foreach($dosens as $d)
                                <option value="{{ $d->id }}" {{ ($mySidang && $mySidang->dosen_pembimbing_pendamping_id == $d->id) ? 'selected' : '' }}>{{ $d->nama_dosen }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Compact File Upload dropzone -->
                <div x-data="{
                        isDragging: false,
                        fileName: '',
                        fileSize: '',
                        fileType: '',
                        existingFile: '{{ ($mySidang && $mySidang->bukti_pembayaran) ? asset($mySidang->bukti_pembayaran) : '' }}',
                        handleFileSelect(e) {
                            const file = e.target.files[0] || (e.dataTransfer ? e.dataTransfer.files[0] : null);
                            if (file) {
                                this.fileName = file.name;
                                this.fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                                this.fileType = file.name.split('.').pop().toUpperCase();
                            }
                        },
                        clearFile() {
                            this.fileName = '';
                            this.fileSize = '';
                            this.fileType = '';
                            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                        }
                     }">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                        Bukti Pembayaran <span class="text-rose-500">*</span>
                        <span class="text-[9px] text-slate-400 font-normal normal-case ml-1">(PDF/JPG, Maks 2MB)</span>
                    </label>
                    
                    <div class="relative border border-dashed rounded-xl p-2.5 text-center transition-all duration-200 cursor-pointer bg-slate-50/50 hover:bg-purple-50/20"
                         :class="isDragging ? 'border-purple-500 bg-purple-50/50 shadow-inner' : (fileName ? 'border-emerald-400 bg-emerald-50/20' : 'border-slate-300 hover:border-purple-500')"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; if ($event.dataTransfer.files.length) { $refs.fileInput.files = $event.dataTransfer.files; handleFileSelect($event); }">
                        
                        <input type="file" 
                                name="bukti_pembayaran" 
                                x-ref="fileInput"
                                @change="handleFileSelect($event)"
                                {{ ($mySidang && $mySidang->bukti_pembayaran) ? '' : 'required' }} 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                        <!-- Compact Horizontal Info -->
                        <template x-if="!fileName">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs shrink-0 border border-purple-100">
                                    📁
                                </div>
                                <div class="text-left">
                                    <p class="text-[11px] font-bold text-slate-700">Pilih berkas pembayaran</p>
                                    <p class="text-[9px] text-slate-400 font-medium">Klik atau drop berkas PDF/JPG di sini</p>
                                </div>
                            </div>
                        </template>

                        <!-- Selected File View -->
                        <template x-if="fileName">
                            <div class="flex items-center justify-between p-1 bg-white border border-emerald-200 rounded-lg shadow-xs z-20 relative">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-6 h-6 rounded bg-emerald-500 text-white font-bold flex items-center justify-center text-[9px] uppercase shrink-0">
                                        <span x-text="fileType"></span>
                                    </div>
                                    <p class="text-[11px] font-bold text-slate-800 truncate max-w-[180px] text-left" x-text="fileName"></p>
                                </div>
                                <button type="button" @click.stop="clearFile()" class="p-1 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded transition-colors z-20" title="Batal">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Existing file indicator -->
                    <template x-if="!fileName && existingFile">
                        <div class="mt-1 flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-purple-50 border border-purple-100 text-purple-700 text-[10px] font-bold">
                            <span>📄 Berkas Terunggah:</span>
                            <a :href="existingFile" target="_blank" class="text-purple-600 hover:underline font-extrabold" @click.stop>
                                Lihat Berkas ↗
                            </a>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="regModal = false" class="px-3 py-1.5 border border-slate-300 hover:bg-slate-50 text-slate-600 font-bold text-[11px] rounded-lg transition-all">Batal</button>
                    <button type="submit" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-extrabold text-[11px] rounded-lg transition-all shadow-sm flex items-center gap-1">
                        <span>🚀</span> Kirim Pendaftaran
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- History Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Riwayat Sidang Skripsi</h2>
        @if($sidangs->isEmpty())
            <div class="text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <p class="text-sm text-slate-500 italic">Belum ada data pendaftaran Sidang Skripsi.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4 font-bold">#</th>
                            <th class="py-3 px-4 font-bold">Judul Skripsi</th>
                            <th class="py-3 px-4 font-bold">Dosbing Utama</th>
                            <th class="py-3 px-4 font-bold">Tanggal</th>
                            <th class="py-3 px-4 font-bold">Ruang</th>
                            <th class="py-3 px-4 font-bold text-center">Status</th>
                            <th class="py-3 px-4 font-bold text-center">Bukti Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium">
                        @foreach($sidangs as $i => $s)
                            <tr class="hover:bg-slate-50/80">
                                <td class="py-3 px-4 text-slate-400 text-xs">{{ $i + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800 max-w-xs truncate" title="{{ $s->judul_skripsi }}">{{ $s->judul_skripsi }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $s->pembimbingUtama->nama_dosen ?? '-' }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $s->tanggal ? $s->tanggal->format('d M Y') : '-' }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $s->ruang->kode_ruangan ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    {!! $s->getVerifikasiStatusHtml() !!}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($s->bukti_pembayaran)
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ asset($s->bukti_pembayaran) }}" target="_blank" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-bold rounded-lg border border-indigo-200 transition-all flex items-center gap-1 shadow-sm">
                                                <span>📄</span> Preview
                                            </a>
                                            <button @click="editBuktiModal = true; editBuktiAction = '{{ route('mahasiswa.sidang.update-bukti', $s->id) }}'; editBuktiUrl = '{{ asset($s->bukti_pembayaran) }}'" class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 text-[11px] font-bold rounded-lg border border-amber-200 transition-all flex items-center gap-1 shadow-sm">
                                                <span>✏️</span> Edit
                                            </button>
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

    {{-- Modal Edit Bukti Pembayaran --}}
    <div x-show="editBuktiModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-md" x-cloak>
        <div @click.away="editBuktiModal = false" class="bg-white rounded-3xl w-full max-w-[400px] shadow-2xl border border-slate-100 overflow-hidden text-left my-8 transform transition-all">
            <div class="px-7 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold shadow-md shadow-amber-500/20">
                        ✏️
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Edit / Upload Ulang Bukti Pembayaran</h3>
                        <p class="text-xs text-slate-500">Unggah berkas bukti pembayaran terbaru Anda</p>
                    </div>
                </div>
                <button @click="editBuktiModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition-all">✕</button>
            </div>
            <form id="form-edit-bukti-skripsi" method="POST" :action="editBuktiAction" enctype="multipart/form-data" class="p-7 space-y-5">
                @csrf
                <div x-data="{
                        isDragging: false,
                        fileName: '',
                        fileSize: '',
                        fileType: '',
                        handleFileSelect(e) {
                            const file = e.target.files[0] || (e.dataTransfer ? e.dataTransfer.files[0] : null);
                            if (file) {
                                this.fileName = file.name;
                                this.fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                                this.fileType = file.name.split('.').pop().toUpperCase();
                            }
                        },
                        clearFile() {
                            this.fileName = '';
                            this.fileSize = '';
                            this.fileType = '';
                            if (this.$refs.fileInputEdit) this.$refs.fileInputEdit.value = '';
                        }
                     }">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Pilih File Bukti Pembayaran Baru <span class="text-rose-500">*</span>
                        <span class="text-[10px] text-slate-400 font-normal ml-1">(PDF, PNG, JPG, JPEG. Maks: 2MB)</span>
                    </label>

                    <div class="relative border-2 border-dashed rounded-3xl p-6 text-center transition-all duration-200 cursor-pointer group overflow-hidden"
                         :class="isDragging ? 'border-amber-500 bg-amber-50/80 shadow-inner scale-[0.99]' : (fileName ? 'border-emerald-400 bg-emerald-50/40' : 'border-slate-200 hover:border-amber-400 hover:bg-amber-50/30')"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; if ($event.dataTransfer.files.length) { $refs.fileInputEdit.files = $event.dataTransfer.files; handleFileSelect($event); }">
                        
                        <input type="file" 
                               name="bukti_pembayaran" 
                               x-ref="fileInputEdit"
                               @change="handleFileSelect($event)"
                               required
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                        <!-- Initial Empty State -->
                        <template x-if="!fileName">
                            <div class="space-y-3 py-2">
                                <div class="w-12 h-12 rounded-2xl bg-amber-100/70 text-amber-600 flex items-center justify-center mx-auto group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700">
                                        <span class="text-amber-600 underline">Klik untuk memilih berkas baru</span> atau seret file ke sini
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">Format: PDF, PNG, JPG, JPEG (Maks. 2MB)</p>
                                </div>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-semibold mt-2 z-20 relative">
                                    <span>📄 File Terpasang:</span>
                                    <a :href="editBuktiUrl" target="_blank" class="text-amber-600 hover:underline font-bold" @click.stop>Pratinjau File Lama ↗</a>
                                </div>
                            </div>
                        </template>

                        <!-- Selected File State -->
                        <template x-if="fileName">
                            <div class="flex items-center justify-between p-3.5 bg-white border border-emerald-200 rounded-2xl shadow-sm z-20 relative">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white font-extrabold flex items-center justify-center text-xs uppercase shadow-md shadow-emerald-500/20">
                                        <span x-text="fileType"></span>
                                    </div>
                                    <div class="text-left">
                                        <p class="text-xs font-bold text-slate-800 truncate max-w-xs" x-text="fileName"></p>
                                        <p class="text-[10px] text-emerald-600 font-bold" x-text="'Berkas baru (' + fileSize + ')'"></p>
                                    </div>
                                </div>
                                <button type="button" @click.stop="clearFile()" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-colors z-20">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-100">
                    <button type="button" @click="editBuktiModal = false" class="px-5 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-2xl transition-all">Batal</button>
                    <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-2xl transition-all hover:shadow-xl shadow-amber-600/20 flex items-center gap-2">
                        <span>💾</span> Simpan Bukti Pembayaran
                    </button>
                </div>
            </form>
        </div>
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

    // ── Pendaftaran Skripsi (AJAX) ───────────────────────────────────────
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

    // ── Edit Bukti Pembayaran (AJAX) ──────────────────────────────────
    const formEditBuktiSkripsi = document.getElementById('form-edit-bukti-skripsi');
    if (formEditBuktiSkripsi) {
        formEditBuktiSkripsi.addEventListener('submit', async function(e) {
            e.preventDefault();
            const action = this.getAttribute('action') || this.action;
            const btn = this.querySelector('[type=submit]'), orig = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '⏳ Menyimpan…';
            try {
                const res = await fetch(action, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF_MHS_SKRIPSI, 'Accept': 'application/json' }, body: new FormData(this) });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToastSkripsi('success', data.message || 'Bukti pembayaran berhasil diperbarui!');
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

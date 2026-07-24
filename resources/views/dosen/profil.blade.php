<x-app-layout title="Profil Akademik Dosen">
    <x-slot:header>
        Profil Akademik Dosen
    </x-slot:header>

    <!-- Back Navigation -->
    <div class="mb-4">
        <a href="{{ route('dosen.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    @if(!$dosen)
        <div class="bg-white rounded-3xl border border-slate-200 p-8 text-center text-slate-500 shadow-sm">
            <h3 class="font-extrabold text-slate-800 text-base">Akun Belum Terhubung</h3>
            <p class="text-xs text-slate-400 mt-1 font-semibold">Hubungkan akun Anda dengan Master Dosen untuk melihat profil akademik lengkap.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Side: Profile Card -->
            <div class="space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 text-center shadow-sm relative overflow-hidden">
                    <!-- Subtle background decoration -->
                    <div class="absolute -top-12 -left-12 w-24 h-24 bg-indigo-50 rounded-full blur-xl"></div>
                    
                    <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 text-white font-extrabold flex items-center justify-center text-3xl shadow-lg shadow-indigo-500/20 mx-auto mb-4 relative z-10">
                        {{ strtoupper(substr($dosen->nama_dosen, 0, 1)) }}
                    </div>

                    <h3 class="font-extrabold text-slate-900 text-lg leading-tight">{{ $dosen->nama_dosen }}</h3>
                    <p class="text-xs text-slate-400 font-mono mt-1">NIDN: {{ $dosen->nidn }}</p>

                    <div class="mt-4 flex justify-center">
                        <span class="px-3 py-1 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-150 uppercase tracking-wider">
                            {{ $dosen->status_dosen ?? 'Dosen Tetap' }}
                        </span>
                    </div>

                    <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Bimbingan</p>
                            <p class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $stats['total_sempro'] + $stats['total_skripsi'] }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Akun</p>
                            <p class="text-xs font-bold text-emerald-600 mt-1 flex items-center justify-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Aktif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Keahlian Card -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                    <h4 class="font-extrabold text-slate-950 text-xs uppercase tracking-wider mb-3">Bidang Keahlian</h4>
                    <div class="flex flex-wrap gap-1.5">
                        @if($dosen->bidang_keahlian)
                            @foreach(explode(',', $dosen->bidang_keahlian) as $bk)
                                <span class="px-2.5 py-1 bg-slate-100 border border-slate-200/80 text-slate-700 text-xs font-bold rounded-lg">{{ trim($bk) }}</span>
                            @endforeach
                        @else
                            <span class="text-xs text-slate-400">-</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Side: Details Tab/Card -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Academic Identity -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
                    <h3 class="font-extrabold text-slate-900 text-base mb-6 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                        Identitas Akademik
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <!-- NIDN -->
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">NIDN</span>
                            <span class="font-bold text-slate-800 mt-1 block">{{ $dosen->nidn }}</span>
                        </div>

                        <!-- Nama Lengkap -->
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Lengkap (Gelar)</span>
                            <span class="font-bold text-slate-800 mt-1 block">{{ $dosen->nama_dosen }}</span>
                        </div>

                        <!-- Email -->
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Alamat Email</span>
                            <span class="font-bold text-slate-800 mt-1 block">{{ Auth::user()->email }}</span>
                        </div>

                        <!-- Jabatan Fungsional -->
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jabatan Fungsional</span>
                            <span class="font-bold text-slate-800 mt-1 block">{{ $dosen->jabatan_fungsional ?? 'Asisten Ahli' }}</span>
                        </div>

                        <!-- Status Dosen -->
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Dosen</span>
                            <span class="font-bold text-slate-800 mt-1 block">{{ $dosen->status_dosen ?? 'Dosen Tetap' }}</span>
                        </div>

                        <!-- Nomor Telepon -->
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nomor Telepon</span>
                            <span class="font-bold text-slate-800 mt-1 block">{{ $dosen->no_telp ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Academic Statistics Summary -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
                    <h3 class="font-extrabold text-slate-900 text-base mb-6 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        Keterlibatan Tugas Akhir
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sebagai Pembimbing</span>
                            <span class="text-xl font-extrabold text-slate-900 mt-1.5 block">{{ $stats['total_sempro'] }} Mahasiswa</span>
                        </div>
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sebagai Penguji</span>
                            <span class="text-xl font-extrabold text-slate-900 mt-1.5 block">{{ $stats['total_skripsi'] }} Mahasiswa</span>
                        </div>
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Publikasi Terkait</span>
                            <span class="text-xl font-extrabold text-slate-900 mt-1.5 block">{{ $stats['total_jurnal'] }} Artikel</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>

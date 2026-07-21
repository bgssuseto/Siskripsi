<x-app-layout title="Administrasi - Berita Acara">
    <x-slot:header>Administrasi Berita Acara</x-slot:header>

    <div class="space-y-6">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight flex items-center gap-2.5">
                    <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                    Generate Berita Acara Mahasiswa
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Generate berita acara sidang skripsi dan jurnal, baik per mahasiswa secara individual maupun cetak massal berdasarkan rentang tanggal pendaftaran.
                </p>
            </div>

            @if($sidangs->count() > 0)
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Preview All Button -->
                <a href="{{ route('administrasi.berita-acara.mass-preview', request()->all()) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm rounded-xl border border-slate-300 transition-all duration-200">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Pratinjau Semua
                </a>

                <!-- Print/Download All Button -->
                <a href="{{ route('administrasi.berita-acara.mass-pdf', request()->all()) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak Semua
                </a>

                <!-- Download ZIP Button -->
                <a href="{{ route('administrasi.berita-acara.zip', request()->all()) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Unduh Semua (ZIP)
                </a>
            </div>
            @endif
        </div>

        <!-- Filter Range Tanggal Pendaftaran & Periode -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <form method="GET" action="{{ route('administrasi.berita-acara.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                
                <!-- Periode -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Periode Akademik</label>
                    <select name="periode_id" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">-- Semua Periode --</option>
                        @foreach($periodes as $p)
                        <option value="{{ $p->id }}" {{ $selectedPeriodeId == $p->id ? 'selected' : '' }}>
                            {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Range Tanggal Pendaftaran Mulai -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Pendaftaran Dari Tanggal</label>
                    <input type="date" name="tanggal_pendaftaran_mulai" value="{{ $tglMulai }}"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>

                <!-- Range Tanggal Pendaftaran Selesai -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Pendaftaran Sampai Tanggal</label>
                    <input type="date" name="tanggal_pendaftaran_selesai" value="{{ $tglSelesai }}"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>

                <!-- Submit Filter -->
                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-md transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter Data
                    </button>

                    @if($tglMulai || $tglSelesai || $selectedPeriodeId)
                    <a href="{{ route('administrasi.berita-acara.index') }}"
                       class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium text-sm rounded-xl transition-all flex items-center justify-center" title="Reset Filter">
                        Reset
                    </a>
                    @endif
                </div>

            </form>
        </div>

        <!-- Tabel Daftar Mahasiswa & Berita Acara -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Daftar Sidang Mahasiswa</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menampilkan {{ $sidangs->count() }} data pelaksanaan ujian sidang mahasiswa pada gelombang pendaftaran yang dipilih.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/80 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4 text-center w-12">No</th>
                            <th class="py-3.5 px-4 w-32">NIM</th>
                            <th class="py-3.5 px-4 w-48">Nama Mahasiswa</th>
                            <th class="py-3.5 px-4">Judul Skripsi</th>
                            <th class="py-3.5 px-4 text-center w-36">Tgl Pendaftaran</th>
                            <th class="py-3.5 px-4 text-center w-28">Jenis</th>
                            <th class="py-3.5 px-4 text-center w-52">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($sidangs as $idx => $item)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $idx + 1 }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-1 bg-slate-100 text-slate-700 font-mono text-xs rounded-md font-semibold">
                                    {{ $item->nim }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800">
                                {{ $item->nama_mahasiswa }}
                            </td>
                            <td class="py-3.5 px-4">
                                <p class="text-slate-600 text-xs font-medium line-clamp-2" title="{{ $item->judul_skripsi }}">
                                    {{ $item->judul_skripsi }}
                                </p>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($item->tanggal_pendaftaran)
                                    <span class="text-xs text-slate-600 font-medium">
                                        {{ $item->tanggal_pendaftaran->translatedFormat('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $item->jenis_tugas_akhir === 'sidang' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">
                                    {{ $item->jenis_tugas_akhir === 'sidang' ? 'Sidang' : 'Jurnal' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center flex items-center justify-center gap-1.5">
                                <!-- Preview Button -->
                                <a href="{{ route('administrasi.berita-acara.preview', $item->id) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition-all border border-slate-200" title="Pratinjau PDF">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Pratinjau
                                </a>

                                <!-- Download PDF Button -->
                                <a href="{{ route('administrasi.berita-acara.pdf', $item->id) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium text-xs rounded-lg transition-all border border-indigo-200" title="Unduh PDF">
                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Unduh PDF
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="max-w-xs mx-auto text-center">
                                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="font-semibold text-slate-600">Belum ada data sidang</p>
                                    <p class="text-xs text-slate-400 mt-1">Gunakan filter range tanggal pendaftaran di atas untuk menampilkan mahasiswa yang terdaftar sidang.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</x-app-layout>

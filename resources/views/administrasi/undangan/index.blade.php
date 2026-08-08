<x-app-layout title="Administrasi - Undangan Sidang">
    <x-slot:header>Administrasi Undangan Sidang</x-slot:header>

    <div class="space-y-6">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight flex items-center gap-2.5">
                    <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    Generate Undangan Sidang Dosen
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Filter berdasarkan rentang tanggal pendaftaran gelombang sidang untuk membuat dokumen undangan menguji bagi setiap dosen.
                </p>
            </div>

            @if($dosenList->count() > 0)
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('administrasi.undangan.mass-excel', request()->all()) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Excel Semua Dosen
                </a>
                <a href="{{ route('administrasi.undangan.rekap-dosen-penguji', request()->all()) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-medium text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Rekap Dosen Penguji
                </a>
                <a href="{{ route('administrasi.undangan.zip', request()->all()) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Unduh Semua PDF (ZIP)
                </a>
            </div>
            @endif
        </div>

        <!-- Filter Range Tanggal Pendaftaran & Periode -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <form method="GET" action="{{ route('administrasi.undangan.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                
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

                <!-- Jenis Undangan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Jenis Undangan</label>
                    <select name="jenis" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="sempro" {{ $jenisUndangan === 'sempro' ? 'selected' : '' }}>Seminar Proposal (Sempro)</option>
                        <option value="skripsi" {{ $jenisUndangan === 'skripsi' ? 'selected' : '' }}>Sidang Skripsi</option>
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

                    @if($tglMulai || $tglSelesai || $selectedPeriodeId || request('jenis'))
                    <a href="{{ route('administrasi.undangan.index') }}"
                       class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium text-sm rounded-xl transition-all flex items-center justify-center" title="Reset Filter">
                        Reset
                    </a>
                    @endif
                </div>

            </form>
        </div>

        <!-- Tabel Daftar Dosen Penguji -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Daftar Dosen Penguji</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menampilkan {{ $dosenList->count() }} dosen penguji (Ketua Penguji, Penguji 1, dan Penguji 2) pada gelombang pendaftaran ini.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/80 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4 text-center w-12">No</th>
                            <th class="py-3.5 px-4">Nama Dosen & Gelar</th>
                            <th class="py-3.5 px-4">NIDN</th>
                            <th class="py-3.5 px-4 text-center">Total Uji (Mahasiswa)</th>
                            <th class="py-3.5 px-4 text-center">Total Sesi Jadwal</th>
                            <th class="py-3.5 px-4 text-center w-48">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($dosenList as $idx => $item)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $idx + 1 }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800">
                                {{ $item['dosen']->nama_dosen }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 font-mono text-xs">
                                {{ $item['dosen']->nidn ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700">
                                    {{ $item['total_uji'] }} Mahasiswa
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ $item['total_sesi'] }} Sesi Sektor
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('administrasi.undangan.preview', array_merge(['dosen' => $item['dosen']->id], request()->all())) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-xs rounded-lg transition-all border border-amber-200" title="Cetak / Preview Undangan">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Cetak Undangan
                                    </a>
                                    <a href="{{ route('administrasi.undangan.pdf', array_merge(['dosen' => $item['dosen']->id], request()->all())) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium text-xs rounded-lg transition-all border border-indigo-200" title="Unduh PDF">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        PDF
                                    </a>
                                     <a href="{{ route('public.dosen.jadwal', ['dosen' => $item['dosen']->id]) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 font-bold text-xs rounded-lg transition-all border border-sky-200" title="Buka Link Jadwal Tanpa Login">
                                         <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                         </svg>
                                         Link Publik
                                     </a>
                                    <a href="{{ route('administrasi.undangan.excel', array_merge(['dosen' => $item['dosen']->id], request()->all())) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-medium text-xs rounded-lg transition-all border border-emerald-200" title="Export Excel">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Excel
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="max-w-xs mx-auto text-center">
                                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="font-semibold text-slate-600">Belum ada data penguji</p>
                                    <p class="text-xs text-slate-400 mt-1">Gunakan filter range tanggal pendaftaran di atas untuk menampilkan dosen yang bertugas menguji.</p>
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

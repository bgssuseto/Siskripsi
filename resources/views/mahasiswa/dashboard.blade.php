<x-app-layout title="Dashboard Mahasiswa">
<div class="max-w-7xl mx-auto p-6 space-y-6">
    
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 rounded-2xl text-white shadow-xl flex justify-between items-center">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-semibold border border-emerald-500/30 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Role: Mahasiswa
            </span>
            <h1 class="text-2xl font-extrabold tracking-tight">Halo, {{ $user->name }}!</h1>
            <p class="text-xs text-slate-300 mt-1">Selamat datang di Sistem Informasi Skripsi TI. Kelola tugas akhir, pendaftaran, dan jadwal sidang Anda.</p>
        </div>
        <div class="hidden sm:flex w-12 h-12 bg-indigo-500/20 border border-indigo-400/30 rounded-2xl items-center justify-center">
            <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
            </svg>
        </div>
    </div>

    <!-- Status Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Active Period Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm space-y-2">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Periode Akademik Aktif</p>
            @if($activePeriode)
                <div class="flex items-center gap-2 mt-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                    <p class="text-base font-extrabold text-slate-800">{{ $activePeriode->nama_periode }}</p>
                </div>
                <p class="text-xs text-indigo-600 font-semibold mt-1">Status: Periode Akademik Berjalan</p>
            @else
                <p class="text-xs text-slate-400 italic">Belum ada periode aktif.</p>
            @endif
        </div>

        <!-- Sidang Skripsi Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm space-y-2">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status Sidang Skripsi</p>
            @if($sidangSkripsi)
                <div class="flex items-center justify-between">
                    @php
                        $statusText = 'Belum Diverifikasi';
                        $statusClass = 'bg-amber-100 text-amber-700 border border-amber-250';
                        if (($sidangSkripsi->verifikasi_status ?? 'menunggu') === 'disetujui') {
                            $statusText = 'Terverifikasi Koordinator';
                            $statusClass = 'bg-emerald-100 text-emerald-700 border border-emerald-250';
                        } elseif (($sidangSkripsi->verifikasi_status ?? 'menunggu') === 'ditolak') {
                            $statusText = 'Ditolak';
                            $statusClass = 'bg-rose-100 text-rose-700 border border-rose-250';
                        }
                    @endphp
                    <span class="px-2.5 py-1 {{ $statusClass }} text-xs font-bold rounded-full">
                        {{ $statusText }}
                    </span>
                    <span class="text-xs text-slate-400">{{ optional($sidangSkripsi->tanggal)->format('d M Y') ?? '-' }}</span>
                </div>
                <p class="text-xs text-slate-600 truncate" title="{{ $sidangSkripsi->judul_skripsi }}">{{ $sidangSkripsi->judul_skripsi }}</p>
                @if(($sidangSkripsi->verifikasi_status ?? 'menunggu') === 'ditolak' && $sidangSkripsi->verifikasi_komentar)
                    <p class="text-[10px] text-rose-500 font-medium">Catatan: {{ $sidangSkripsi->verifikasi_komentar }}</p>
                @endif
            @else
                <p class="text-xs text-slate-400 italic">Belum ada pendaftaran sidang skripsi.</p>
            @endif
        </div>

        <!-- Sidang Jurnal Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm space-y-2">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status Sidang Jurnal</p>
            @if($sidangJurnal)
                <div class="flex items-center justify-between">
                    @php
                        $statusText = 'Belum Diverifikasi';
                        $statusClass = 'bg-amber-100 text-amber-700 border border-amber-250';
                        if (($sidangJurnal->verifikasi_status ?? 'menunggu') === 'disetujui') {
                            $statusText = 'Terverifikasi Koordinator';
                            $statusClass = 'bg-emerald-100 text-emerald-700 border border-emerald-250';
                        } elseif (($sidangJurnal->verifikasi_status ?? 'menunggu') === 'ditolak') {
                            $statusText = 'Ditolak';
                            $statusClass = 'bg-rose-100 text-rose-700 border border-rose-250';
                        }
                    @endphp
                    <span class="px-2.5 py-1 {{ $statusClass }} text-xs font-bold rounded-full">
                        {{ $statusText }}
                    </span>
                    <span class="text-xs text-slate-400">{{ optional($sidangJurnal->tanggal)->format('d M Y') ?? '-' }}</span>
                </div>
                <p class="text-xs text-slate-600 truncate" title="{{ $sidangJurnal->judul_skripsi }}">{{ $sidangJurnal->judul_skripsi }}</p>
                @if(($sidangJurnal->verifikasi_status ?? 'menunggu') === 'ditolak' && $sidangJurnal->verifikasi_komentar)
                    <p class="text-[10px] text-rose-500 font-medium">Catatan: {{ $sidangJurnal->verifikasi_komentar }}</p>
                @endif
            @else
                <p class="text-xs text-slate-400 italic">Belum ada pendaftaran sidang jurnal.</p>
            @endif
        </div>
    </div>

    <!-- Recent Sidang List -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Riwayat Sidang & Tugas Akhir</h2>
        @if($sidangs->isEmpty())
            <div class="text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <p class="text-sm text-slate-500 italic">Belum ada data riwayat sidang.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4 font-bold">#</th>
                            <th class="py-3 px-4 font-bold">Jenis</th>
                            <th class="py-3 px-4 font-bold">Judul</th>
                            <th class="py-3 px-4 font-bold">Tanggal</th>
                            <th class="py-3 px-4 font-bold">Ruang</th>
                            <th class="py-3 px-4 font-bold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium">
                        @foreach($sidangs as $i => $s)
                            <tr class="hover:bg-slate-50/80">
                                <td class="py-3 px-4 text-slate-400 text-xs">{{ $i + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800 capitalize">{{ $s->jenis_tugas_akhir }}</td>
                                <td class="py-3 px-4 text-slate-700 max-w-xs truncate" title="{{ $s->judul_skripsi }}">{{ $s->judul_skripsi }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ optional($s->tanggal)->format('d M Y') ?? '-' }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $s->ruang->kode_ruangan ?? $s->ruang->nama ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    {!! $s->getVerifikasiStatusHtml() !!}
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

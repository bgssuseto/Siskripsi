<x-app-layout title="Daftar Sempro">
<div class="max-w-7xl mx-auto p-6 space-y-6">
    <div class="bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 p-6 rounded-2xl text-white shadow-xl flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold mb-1">Pendaftaran Seminar Proposal (Sempro)</h1>
            <p class="text-xs text-indigo-200">Kelola riwayat dan pengajuan Seminar Proposal Anda dari halaman ini.</p>
        </div>
        <span class="px-3.5 py-1.5 bg-indigo-500/30 border border-indigo-400/30 text-indigo-200 text-xs font-bold rounded-xl">
            Sempro Mahasiswa
        </span>
    </div>

    <!-- Active Period Card -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200/80">
        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-2">Periode Akademik Aktif</h2>
        @if($activePeriode)
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                <p class="font-extrabold text-slate-800">{{ $activePeriode->nama_periode }}</p>
            </div>
        @else
            <p class="text-sm text-slate-400 italic">Belum ada periode aktif.</p>
        @endif
    </div>

    <!-- History Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Riwayat Pendaftaran Sempro</h2>
        @if($sidangs->isEmpty())
            <div class="text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <p class="text-sm text-slate-500 italic">Belum ada data pendaftaran Seminar Proposal.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4 font-bold">#</th>
                            <th class="py-3 px-4 font-bold">NIM / Nama</th>
                            <th class="py-3 px-4 font-bold">Judul Sempro</th>
                            <th class="py-3 px-4 font-bold">Tanggal</th>
                            <th class="py-3 px-4 font-bold">Ruang</th>
                            <th class="py-3 px-4 font-bold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium">
                        @foreach($sidangs as $i => $s)
                            <tr class="hover:bg-slate-50/80">
                                <td class="py-3 px-4 text-slate-400 text-xs">{{ $i + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800">{{ $s->nim }}<br><span class="text-xs text-slate-500 font-normal">{{ $s->nama_mahasiswa }}</span></td>
                                <td class="py-3 px-4 text-slate-700 max-w-xs truncate" title="{{ $s->judul_skripsi }}">{{ $s->judul_skripsi }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $s->tanggal ? $s->tanggal->format('d M Y') : '-' }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $s->ruang->kode_ruangan ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $s->status == 'Lulus' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $s->status ?? 'Terdaftar' }}
                                    </span>
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

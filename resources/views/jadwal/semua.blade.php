<x-app-layout title="Semua Jadwal">
<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-3xl bg-slate-900 dark:bg-slate-950 p-6 sm:p-7 text-white shadow-xl border border-slate-800">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-[11px] font-extrabold border border-indigo-400/30 mb-2.5">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    Khusus Super Admin
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Semua Jadwal</h1>
                <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    Seluruh jadwal Seminar Proposal dan Sidang Skripsi/Jurnal yang telah diplotting, digabung dalam satu tampilan.
                    Saring berdasarkan hari, penguji/pembimbing, atau ruang.
                </p>
            </div>
            <div class="flex gap-3 shrink-0">
                <div class="px-4 py-2.5 bg-white/10 backdrop-blur-md rounded-2xl border border-white/15 text-center shadow-md">
                    <p class="text-[10px] text-indigo-300 font-bold uppercase tracking-wider">Sempro</p>
                    <p class="text-lg font-extrabold text-white mt-0.5">{{ $totalSempro }}</p>
                </div>
                <div class="px-4 py-2.5 bg-white/10 backdrop-blur-md rounded-2xl border border-white/15 text-center shadow-md">
                    <p class="text-[10px] text-indigo-300 font-bold uppercase tracking-wider">Skripsi/Jurnal</p>
                    <p class="text-lg font-extrabold text-white mt-0.5">{{ $totalSkripsi }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5">
        <form method="GET" action="{{ route('jadwal.semua.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[240px]">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NIM, nama, atau judul…"
                       class="w-full pl-10 pr-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-semibold focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-all">
            </div>

            <x-filter-popover :active="request()->hasAny(['hari','ruang_id','penguji_id'])">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Hari</label>
                    <select name="hari" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-950">
                        <option value="">-- Semua Hari --</option>
                        @foreach($hariOptions as $val => $label)
                            <option value="{{ $val }}" {{ (string) request('hari') === (string) $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Penguji / Pembimbing</label>
                    <select name="penguji_id" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-950">
                        <option value="">-- Semua Dosen --</option>
                        @foreach($dosens as $d)
                            <option value="{{ $d->id }}" {{ (string) request('penguji_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->nama_dosen }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Ruang</label>
                    <select name="ruang_id" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-950">
                        <option value="">-- Semua Ruang --</option>
                        @foreach($ruangs as $r)
                            <option value="{{ $r->id }}" {{ (string) request('ruang_id') === (string) $r->id ? 'selected' : '' }}>{{ $r->kode_ruangan }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filter-popover>

            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white font-extrabold text-xs rounded-2xl transition-all shadow-md hover:bg-indigo-700 border border-indigo-500 cursor-pointer">
                🔍 Cari &amp; Filter
            </button>
            @if(request()->hasAny(['search','hari','ruang_id','penguji_id']))
                <a href="{{ route('jadwal.semua.index') }}" class="px-4 py-2.5 bg-slate-600 text-white font-extrabold text-xs rounded-2xl hover:bg-slate-700 transition-colors border border-slate-500">✕ Reset</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-100/70 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 text-[10px] font-extrabold uppercase tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                        <th class="py-3.5 px-4 w-44">Mahasiswa</th>
                        <th class="py-3.5 px-3 w-24">Jenis</th>
                        <th class="py-3.5 px-3 w-40">Hari, Tanggal</th>
                        <th class="py-3.5 px-3 w-24">Jam</th>
                        <th class="py-3.5 px-3 w-20">Ruang</th>
                        <th class="py-3.5 px-4 w-56">Dosen Terkait</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs font-medium">
                    @forelse($sidangs as $s)
                        @php
                            $jenisLabel = $s->jenis_tugas_akhir === 'sempro' ? 'Sempro' : (in_array($s->jenis_tugas_akhir, ['skripsi','sidang']) ? 'Sidang Skripsi' : 'Jurnal');
                            $jenisColor = $s->jenis_tugas_akhir === 'sempro' ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300';
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-extrabold text-slate-900 dark:text-slate-100">{{ $s->nama_mahasiswa }}</div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">NIM: {{ $s->nim }}</div>
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="inline-flex whitespace-nowrap px-2.5 py-1 rounded-lg text-[10px] font-extrabold {{ $jenisColor }}">{{ $jenisLabel }}</span>
                            </td>
                            <td class="py-3.5 px-3 text-slate-700 dark:text-slate-300">
                                {{ $s->tanggal?->locale('id')->isoFormat('dddd, D MMM Y') }}
                            </td>
                            <td class="py-3.5 px-3 font-mono text-slate-700 dark:text-slate-300">{{ $s->jam ?? '-' }}</td>
                            <td class="py-3.5 px-3">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono text-[10.5px]">{{ $s->ruang->kode_ruangan ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400">
                                @if($s->jenis_tugas_akhir === 'sempro')
                                    @php
                                        $namaDosen = array_filter([
                                            $s->pembimbingUtama->nama_dosen ?? null,
                                            $s->pembimbingPendamping->nama_dosen ?? null,
                                        ]);
                                    @endphp
                                    <div><span class="text-slate-400">Pembimbing:</span> {{ $namaDosen ? implode(', ', $namaDosen) : '-' }}</div>
                                @else
                                    @php
                                        $namaDosen = array_filter([
                                            $s->ketuaPenguji->nama_dosen ?? null,
                                            $s->anggotaPenguji1->nama_dosen ?? null,
                                            $s->anggotaPenguji2->nama_dosen ?? null,
                                        ]);
                                    @endphp
                                    <div><span class="text-slate-400">Penguji:</span> {{ $namaDosen ? implode(', ', $namaDosen) : '-' }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-14 px-6 text-center bg-slate-50/40 dark:bg-slate-900/40">
                                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-2 text-xl">📅</div>
                                <h3 class="text-xs font-extrabold text-slate-800 dark:text-slate-200">Tidak Ada Jadwal Ditemukan</h3>
                                <p class="text-[11px] text-slate-400 mt-1">Coba ubah atau reset filter yang digunakan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sidangs->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40">
                {{ $sidangs->links() }}
            </div>
        @endif
    </div>
</div>
</x-app-layout>

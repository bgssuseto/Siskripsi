<x-app-layout title="Jadwal Sidang">
<div class="max-w-7xl mx-auto p-6 space-y-6">
    <div class="bg-gradient-to-r from-blue-900 via-slate-900 to-indigo-950 p-6 rounded-2xl text-white shadow-xl flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold mb-1">Jadwal Sidang & Ujian</h1>
            <p class="text-xs text-blue-200">Lihat jadwal pelaksanaan sidang, alokasi ruangan, dan jajaran penguji Anda.</p>
        </div>
        <span class="px-3.5 py-1.5 bg-blue-500/30 border border-blue-400/30 text-blue-200 text-xs font-bold rounded-xl">
            Jadwal Saya
        </span>
    </div>

    <!-- Schedule Cards -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Agenda Sidang Anda</h2>
        @if($sidangs->isEmpty())
            <div class="text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <p class="text-sm text-slate-500 italic">Belum ada agenda jadwal sidang yang terjadwal.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($sidangs as $s)
                    <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition-all space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-full uppercase">
                                {{ $s->jenis_tugas_akhir }}
                            </span>
                            <span class="text-xs font-bold text-slate-500">
                                Ruang: {{ $s->ruang->kode_ruangan ?? 'TBA' }}
                            </span>
                        </div>

                        <h3 class="font-extrabold text-slate-800 text-base leading-snug">{{ $s->judul_skripsi }}</h3>

                        <div class="grid grid-cols-2 gap-2 text-xs text-slate-600 pt-2 border-t border-slate-200/60">
                            <div>
                                <p class="text-slate-400 font-semibold">Tanggal:</p>
                                <p class="font-bold text-slate-800">{{ $s->tanggal ? $s->tanggal->format('d M Y') : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-slate-400 font-semibold">Waktu / Jam:</p>
                                <p class="font-bold text-slate-800">{{ $s->jam ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-200/60 text-xs text-slate-600 space-y-1">
                            <p><span class="font-bold text-slate-700">Pembimbing:</span> {{ $s->pembimbingUtama->nama_dosen ?? '-' }}</p>
                            <p><span class="font-bold text-slate-700">Ketua Penguji:</span> {{ $s->ketuaPenguji->nama_dosen ?? '-' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
</x-app-layout>

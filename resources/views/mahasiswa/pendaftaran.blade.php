<x-app-layout title="Daftar Sempro & Skripsi">
<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-3xl font-bold text-indigo-800 mb-6">Daftar Sempro & Skripsi</h1>

    <div class="bg-white rounded-xl shadow-lg p-5 border border-gray-100 mb-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Periode Aktif</h2>
        @if($activePeriode)
            <p class="text-gray-600">{{ $activePeriode->nama }} ({{ $activePeriode->tahun }})</p>
            <p class="text-sm text-gray-500">Mulai: {{ $activePeriode->tanggal_mulai->format('d M Y') }}</p>
            <p class="text-sm text-gray-500">Selesai: {{ $activePeriode->tanggal_selesai->format('d M Y') }}</p>
        @else
            <p class="text-gray-500 italic">Tidak ada periode aktif.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-lg p-5 border border-gray-100">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Riwayat Pendaftaran Anda</h2>
        @if($sidangs->isEmpty())
            <p class="text-gray-500 italic">Belum ada pendaftaran.</p>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ruang</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($sidangs as $i => $s)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $i + 1 }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 capitalize">{{ $s->jenis_tugas_akhir }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $s->tanggal->format('d M Y') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $s->ruang->nama ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm font-medium {{ $s->status == 'Lulus' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $s->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
</x-app-layout>

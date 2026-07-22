<x-app-layout title="Administrasi - Surat Keputusan (SK)">
    <x-slot:header>Administrasi Surat Keputusan (SK)</x-slot:header>

    <div class="space-y-6">
        <div class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-sm text-center max-w-2xl mx-auto my-12">
            <div class="w-16 h-16 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-slate-800">Modul Surat Keputusan (SK)</h2>
            <p class="text-slate-500 text-sm mt-2">
                Halaman pencetakan Surat Keputusan (SK) Dekan untuk Penugasan Dosen Pembimbing dan Tim Dosen Penguji Ujian Sidang Skripsi.
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <a href="{{ route('master.skripsi.index') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl transition-all shadow-md">
                    Lihat Data Skripsi
                </a>
            </div>
        </div>
    </div>
</x-app-layout>

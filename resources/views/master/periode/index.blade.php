<x-app-layout title="Master Periode">
    <x-slot:header>Master Periode</x-slot:header>

    <div x-data="{ 
        createModal: false, 
        editModal: false, 
        deleteModal: false,
        editPeriode: { id: null, nama_periode: '', aktif: false }
    }">
        {{-- Flash message success --}}
        @if (session('success'))
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        {{-- Flash message error --}}
        @if (session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl flex items-center gap-3">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Header & Add button --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Master Periode Akademik</h2>
                <p class="text-sm text-slate-500 mt-1">Kelola data periode semester aktif untuk jadwal sidang skripsi.</p>
            </div>
            <button @click="createModal = true" class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm px-4 py-2.5 rounded-xl transition-all duration-200 hover:shadow-lg hover:shadow-indigo-600/20 focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Tambah Periode
            </button>
        </div>

        {{-- Search & stats bar --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between gap-4">
                <form method="GET" action="{{ route('master.periode.index') }}" class="w-full flex gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama periode..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all placeholder-slate-400 text-slate-800">
                    </div>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm px-4 py-2 rounded-xl transition-all">Filter</button>
                    @if (request()->filled('search'))
                        <a href="{{ route('master.periode.index') }}" class="inline-flex items-center justify-center border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm px-4 py-2 rounded-xl transition-all">Reset</a>
                    @endif
                </form>
            </div>
            
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Periode Aktif</p>
                    <p class="text-sm font-bold text-slate-800 mt-1">
                        {{ \App\Models\Periode::where('aktif', true)->first()->nama_periode ?? 'Belum ada yang aktif' }}
                    </p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    ✓
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-4 px-6">No</th>
                            <th class="py-4 px-6">Nama Periode</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        @forelse ($periodes as $index => $item)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-6 font-medium text-slate-400">{{ $periodes->firstItem() + $index }}</td>
                                <td class="py-4 px-6 font-semibold text-slate-900">{{ $item->nama_periode }}</td>
                                <td class="py-4 px-6">
                                    @if ($item->aktif)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-50 text-slate-500 border border-slate-200">
                                            Tidak Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right space-x-2">
                                    @if (!$item->aktif)
                                        <form method="POST" action="{{ route('master.periode.active', $item->id) }}" class="inline-block">
                                            @csrf
                                            <button type="submit" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg transition-all">Set Aktif</button>
                                        </form>
                                    @endif
                                    <button @click="
                                        editPeriode.id = {{ $item->id }};
                                        editPeriode.nama_periode = '{{ addslashes($item->nama_periode) }}';
                                        editPeriode.aktif = {{ $item->aktif ? 'true' : 'false' }};
                                        editModal = true;
                                    " class="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-50 hover:bg-slate-100 px-2.5 py-1 rounded-lg transition-all">
                                        Edit
                                    </button>
                                    <button @click="
                                        editPeriode.id = {{ $item->id }};
                                        editPeriode.nama_periode = '{{ addslashes($item->nama_periode) }}';
                                        deleteModal = true;
                                    " class="text-xs font-bold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-lg transition-all">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 00-2 2z"></path>
                                    </svg>
                                    <p class="font-medium text-slate-500">Belum ada data periode</p>
                                    <p class="text-xs text-slate-400 mt-1">Silakan tambahkan data periode baru.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($periodes->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $periodes->links() }}
                </div>
            @endif
        </div>

        {{-- Modal Tambah --}}
        <div x-show="createModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-cloak>
            <div @click.away="createModal = false" class="bg-white rounded-2xl w-full max-w-md shadow-2xl border border-slate-100 overflow-hidden transform transition-all duration-300">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="text-base font-bold text-slate-900">Tambah Periode Akademik</h3>
                    <button @click="createModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        ✕
                    </button>
                </div>
                <form method="POST" action="{{ route('master.periode.store') }}" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Periode <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_periode" required placeholder="Contoh: Semester Genap 2026/2027" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-800">
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <input type="checkbox" name="aktif" value="1" id="create-aktif" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500/20">
                        <label for="create-aktif" class="text-sm font-semibold text-slate-700 select-none cursor-pointer">Jadikan Periode Aktif</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="createModal = false" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm rounded-xl transition-all">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl transition-all hover:shadow-lg hover:shadow-indigo-600/20">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Edit --}}
        <div x-show="editModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-cloak>
            <div @click.away="editModal = false" class="bg-white rounded-2xl w-full max-w-md shadow-2xl border border-slate-100 overflow-hidden transform transition-all duration-300">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="text-base font-bold text-slate-900">Edit Periode Akademik</h3>
                    <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        ✕
                    </button>
                </div>
                <form method="POST" :action="`{{ route('master.periode.index') }}/${editPeriode.id}`" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Periode <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_periode" x-model="editPeriode.nama_periode" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-800">
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <input type="checkbox" name="aktif" value="1" id="edit-aktif" x-model="editPeriode.aktif" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500/20">
                        <label for="edit-aktif" class="text-sm font-semibold text-slate-700 select-none cursor-pointer">Jadikan Periode Aktif</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="editModal = false" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm rounded-xl transition-all">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl transition-all hover:shadow-lg hover:shadow-indigo-600/20">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Hapus --}}
        <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-cloak>
            <div @click.away="deleteModal = false" class="bg-white rounded-2xl w-full max-w-sm shadow-2xl border border-slate-100 overflow-hidden transform transition-all duration-300">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="text-base font-bold text-rose-600">Hapus Periode</h3>
                    <button @click="deleteModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        ✕
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-sm text-slate-600">Apakah Anda yakin ingin menghapus periode <strong class="text-slate-900" x-text="editPeriode.nama_periode"></strong>?</p>
                    <p class="text-xs text-rose-500 mt-2 font-semibold">⚠️ Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" @click="deleteModal = false" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm rounded-xl transition-all">Batal</button>
                    <form method="POST" :action="`{{ route('master.periode.index') }}/${editPeriode.id}`" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm rounded-xl transition-all">Ya, Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout title="Master Ruang">
    <x-slot:header>Master Ruang</x-slot:header>

    <div x-data="{ 
        createModal: false, 
        editModal: false, 
        deleteModal: false,
        editRuang: { id: null, kode_ruangan: '', nama_ruangan: '' },
        deleteRuang: { id: null, nama_ruangan: '' },
        errors: {},
        isLoading: false,
        openEdit(ruang) {
            this.editRuang = { ...ruang };
            this.errors = {};
            this.editModal = true;
        },
        openDelete(ruang) {
            this.deleteRuang = { ...ruang };
            this.deleteModal = true;
        },
        async navigate(url) {
            window.history.pushState(null, '', url);
            await refreshComponent(['#table-container', '#filter-container']);
        },
        submitSearch(form) {
            const url = new URL(form.action || window.location.href);
            const formData = new FormData(form);
            url.searchParams.delete('search');
            url.searchParams.delete('page');
            for (const [key, value] of formData.entries()) {
                if (value) {
                    url.searchParams.set(key, value);
                }
            }
            this.navigate(url.toString());
        },
        async submitCreate(e) {
            this.isLoading = true;
            this.errors = {};
            const form = e.target;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new FormData(form)
                });
                const result = await response.json();
                if (response.ok) {
                    this.createModal = false;
                    form.reset();
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    await refreshComponent(['#table-container', '#filter-container']);
                } else {
                    if (response.status === 422) {
                        this.errors = result.errors || {};
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Terjadi kesalahan.', type: 'error' } }));
                    }
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                this.isLoading = false;
            }
        },
        async submitEdit(e) {
            this.isLoading = true;
            this.errors = {};
            const form = e.target;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new FormData(form)
                });
                const result = await response.json();
                if (response.ok) {
                    this.editModal = false;
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    await refreshComponent(['#table-container', '#filter-container']);
                } else {
                    if (response.status === 422) {
                        this.errors = result.errors || {};
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Terjadi kesalahan.', type: 'error' } }));
                    }
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                this.isLoading = false;
            }
        },
        async submitDelete(e) {
            this.isLoading = true;
            const form = e.target;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new FormData(form)
                });
                const result = await response.json();
                if (response.ok) {
                    this.deleteModal = false;
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    await refreshComponent(['#table-container', '#filter-container']);
                } else {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Gagal menghapus ruangan.', type: 'error' } }));
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                this.isLoading = false;
            }
        }
    }">

        <!-- Page Title & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Master Ruang</h2>
                <p class="text-sm text-slate-500 mt-1">Kelola data Kode Ruangan dan Nama Ruangan ujian skripsi.</p>
            </div>
            <button @click="createModal = true" 
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shadow-lg shadow-indigo-600/25 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Ruangan
            </button>
        </div>

        <!-- Search & Filter Bar -->
        <div id="filter-container" class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700 p-4 mb-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <form method="GET" action="{{ route('master.ruang.index') }}" @submit.prevent="submitSearch($event.currentTarget)" class="w-full sm:w-80 relative">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       @input.debounce.500ms="submitSearch($event.target.form)"
                       placeholder="Cari Kode atau Nama Ruangan..." 
                       class="w-full pl-10 pr-4 py-2 rounded-xl text-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </form>

            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                Total: <span class="font-extrabold text-slate-900 dark:text-slate-100">{{ $ruangs->total() }}</span> Ruangan
            </div>
        </div>

        <!-- Table Card -->
        <div id="table-container" @click="if ($event.target.closest('a')) { const link = $event.target.closest('a'); if (link.href && !link.hasAttribute('download') && !link.getAttribute('href').startsWith('#') && link.target !== '_blank') { $event.preventDefault(); navigate(link.href); } }" class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200/80 dark:border-slate-700 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">No</th>
                            <th class="px-6 py-4">Kode Ruangan</th>
                            <th class="px-6 py-4">Nama Ruangan</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($ruangs as $index => $ruang)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-400 dark:text-slate-500">
                                {{ $ruangs->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $ruang->kode_ruangan }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900 dark:text-slate-100">
                                {{ $ruang->nama_ruangan }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEdit({{ json_encode($ruang) }})" 
                                            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Edit Ruangan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="openDelete({{ json_encode($ruang) }})" 
                                            class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Hapus Ruangan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                Belum ada data ruangan. Klik tombol <b>Tambah Ruangan</b> untuk memasukkan data baru.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($ruangs->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $ruangs->links() }}
            </div>
            @endif
        </div>

        <!-- ================= MODAL TAMBAH RUANGAN ================= -->
        <div x-show="createModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="createModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <div class="inline-block relative z-10 w-full max-w-md my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">Tambah Ruangan Baru</h3>
                        <button @click="createModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('master.ruang.store') }}" @submit.prevent="submitCreate($event)" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Kode Ruangan <span class="text-rose-500">*</span></label>
                            <input type="text" name="kode_ruangan" required placeholder="Contoh: R-101" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <template x-if="errors.kode_ruangan">
                                <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.kode_ruangan[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Nama Ruangan <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama_ruangan" required placeholder="Contoh: Ruang Ujian Skripsi Lt. 2" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <template x-if="errors.nama_ruangan">
                                <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.nama_ruangan[0]"></p>
                            </template>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                            <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                            <button type="submit" :disabled="isLoading" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/30 disabled:opacity-50">
                                <span x-show="!isLoading">Simpan Data</span>
                                <span x-show="isLoading">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL EDIT RUANGAN ================= -->
        <div x-show="editModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="editModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <div class="inline-block relative z-10 w-full max-w-md my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">Edit Data Ruangan</h3>
                        <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form :action="'/master/ruang/' + editRuang.id" method="POST" @submit.prevent="submitEdit($event)" class="p-6 space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Kode Ruangan <span class="text-rose-500">*</span></label>
                            <input type="text" name="kode_ruangan" x-model="editRuang.kode_ruangan" required 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <template x-if="errors.kode_ruangan">
                                <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.kode_ruangan[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Ruangan <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama_ruangan" x-model="editRuang.nama_ruangan" required 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <template x-if="errors.nama_ruangan">
                                <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.nama_ruangan[0]"></p>
                            </template>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                            <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                            <button type="submit" :disabled="isLoading" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/30 disabled:opacity-50">
                                <span x-show="!isLoading">Simpan Perubahan</span>
                                <span x-show="isLoading">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL HAPUS RUANGAN ================= -->
        <div x-show="deleteModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="deleteModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <div class="inline-block relative z-10 w-full max-w-sm my-8 overflow-hidden text-center align-middle transition-all transform bg-white rounded-2xl shadow-xl p-6">
                    <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>

                    <h3 class="text-base font-bold text-slate-900 mb-1">Hapus Data Ruangan?</h3>
                    <p class="text-xs text-slate-500 mb-6">
                        Apakah Anda yakin ingin menghapus ruangan <span class="font-bold text-slate-800" x-text="deleteRuang.nama_ruangan"></span>?
                    </p>

                    <form :action="'/master/ruang/' + deleteRuang.id" method="POST" @submit.prevent="submitDelete($event)" class="flex items-center justify-center gap-3">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="deleteModal = false" class="w-1/2 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" :disabled="isLoading" class="w-1/2 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold shadow-md shadow-rose-600/30 disabled:opacity-50">
                            <span x-show="!isLoading">Hapus</span>
                            <span x-show="isLoading">Menghapus...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>

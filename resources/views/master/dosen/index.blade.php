<x-app-layout title="Master Dosen">
    <x-slot:header>Master Dosen</x-slot:header>

    <div x-data="{ 
        createModal: false, 
        editModal: false, 
        deleteModal: false,
        importModal: false,
        editDosen: { id: null, nidn: '', nama_dosen: '', no_wa: '' },
        deleteDosen: { id: null, nama_dosen: '' },
        errors: {},
        isLoading: false,
        openEdit(dosen) {
            this.editDosen = { ...dosen };
            this.errors = {};
            this.editModal = true;
        },
        openDelete(dosen) {
            this.deleteDosen = { ...dosen };
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
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Gagal menghapus dosen.', type: 'error' } }));
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                this.isLoading = false;
            }
        },
        async submitImport(e) {
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
                    this.importModal = false;
                    form.reset();
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    await refreshComponent(['#table-container', '#filter-container']);
                } else {
                    if (response.status === 422) {
                        this.errors = result.errors || {};
                    }
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Gagal meng-import data.', type: 'error' } }));
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
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Master Dosen</h2>
                <p class="text-sm text-slate-500 mt-1">Kelola data NIDN dan Nama Dosen pembimbing/penguji skripsi.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="importModal = true" 
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-lg shadow-emerald-600/25 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import Excel
                </button>
                <button @click="createModal = true" 
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shadow-lg shadow-indigo-600/25 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Dosen
                </button>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <div id="filter-container" class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700 p-4 mb-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <form method="GET" action="{{ route('master.dosen.index') }}" @submit.prevent="submitSearch($event.currentTarget)" class="w-full sm:w-80 relative">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       @input.debounce.500ms="submitSearch($event.target.form)"
                       placeholder="Cari NIDN atau Nama Dosen..." 
                       class="w-full pl-10 pr-4 py-2 rounded-xl text-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </form>

            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                Total: <span class="font-extrabold text-slate-900 dark:text-slate-100">{{ $dosens->total() }}</span> Dosen
            </div>
        </div>

        <!-- Table Card -->
        <div id="table-container" @click="if ($event.target.closest('a')) { const link = $event.target.closest('a'); if (link.href && !link.hasAttribute('download') && !link.getAttribute('href').startsWith('#') && link.target !== '_blank') { $event.preventDefault(); navigate(link.href); } }" class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200/80 dark:border-slate-700 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">No</th>
                            <th class="px-6 py-4">NIDN</th>
                            <th class="px-6 py-4">Nama Dosen</th>
                            <th class="px-6 py-4">No. WhatsApp</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($dosens as $index => $dosen)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-400 dark:text-slate-500">
                                {{ $dosens->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono font-extrabold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/40 px-2.5 py-1 rounded-lg border border-indigo-200 dark:border-indigo-800">{{ $dosen->nidn }}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900 dark:text-slate-100">
                                {{ $dosen->nama_dosen }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-600 dark:text-slate-300">
                                {{ $dosen->no_wa ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEdit({{ json_encode($dosen) }})" 
                                            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Edit Dosen">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="openDelete({{ json_encode($dosen) }})" 
                                            class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Hapus Dosen">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                Belum ada data dosen. Klik tombol <b>Tambah Dosen</b> untuk memasukkan data baru.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($dosens->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $dosens->links() }}
            </div>
            @endif
        </div>

        <!-- ================= MODAL TAMBAH DOSEN ================= -->
        <div x-show="createModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="createModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <div class="inline-block relative z-10 w-full max-w-md my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">Tambah Dosen Baru</h3>
                        <button @click="createModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('master.dosen.store') }}" @submit.prevent="submitCreate($event)" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">NIDN <span class="text-rose-500">*</span></label>
                            <input type="text" name="nidn" required placeholder="Contoh: 0012058501" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <template x-if="errors.nidn">
                                <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.nidn[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Nama Dosen <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama_dosen" required placeholder="Contoh: Dr. Eng. Budi Santoso, M.T." 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <template x-if="errors.nama_dosen">
                                <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.nama_dosen[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">No. WhatsApp (Opsional)</label>
                            <input type="text" name="no_wa" placeholder="Contoh: 081234567890" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
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

        <!-- ================= MODAL EDIT DOSEN ================= -->
        <div x-show="editModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="editModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <div class="inline-block relative z-10 w-full max-w-md my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">Edit Data Dosen</h3>
                        <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form :action="'/master/dosen/' + editDosen.id" method="POST" @submit.prevent="submitEdit($event)" class="p-6 space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">NIDN <span class="text-rose-500">*</span></label>
                            <input type="text" name="nidn" x-model="editDosen.nidn" required 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <template x-if="errors.nidn">
                                <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.nidn[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Dosen <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama_dosen" x-model="editDosen.nama_dosen" required 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <template x-if="errors.nama_dosen">
                                <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.nama_dosen[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">No. WhatsApp (Opsional)</label>
                            <input type="text" name="no_wa" x-model="editDosen.no_wa" placeholder="Contoh: 081234567890" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
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

        <!-- ================= MODAL HAPUS DOSEN ================= -->
        <div x-show="deleteModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="deleteModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <div class="inline-block relative z-10 w-full max-w-sm my-8 overflow-hidden text-center align-middle transition-all transform bg-white rounded-2xl shadow-xl p-6">
                    <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>

                    <h3 class="text-base font-bold text-slate-900 mb-1">Hapus Data Dosen?</h3>
                    <p class="text-xs text-slate-500 mb-6">
                        Apakah Anda yakin ingin menghapus data dosen <span class="font-bold text-slate-800" x-text="deleteDosen.nama_dosen"></span>?
                    </p>

                    <form :action="'/master/dosen/' + deleteDosen.id" method="POST" @submit.prevent="submitDelete($event)" class="flex items-center justify-center gap-3">
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
        <!-- ================= MODAL IMPORT DOSEN ================= -->
        <div x-show="importModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="importModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <div class="inline-block relative z-10 w-full max-w-md my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">Import Data Dosen</h3>
                        <button @click="importModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('master.dosen.import') }}" enctype="multipart/form-data" @submit.prevent="submitImport($event)" class="p-6 space-y-4">
                        @csrf
                        <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 rounded-xl p-4 text-xs text-indigo-900 dark:text-indigo-200 space-y-2">
                            <p class="font-bold flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Petunjuk Header File Excel:
                            </p>
                            <ul class="list-disc list-inside space-y-1 pl-1">
                                <li>Header wajib memuat: <b>Nama & Gelar</b> (atau Nama Dosen), <b>NIDN</b>, dan <b>No WhatsApp</b>.</li>
                                <li>Format file yang didukung: <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code>.</li>
                            </ul>
                            <div class="pt-1">
                                <a href="{{ route('master.dosen.template') }}" class="inline-flex items-center gap-1 font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 underline">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Download Template Excel
                                </a>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Pilih File Excel <span class="text-rose-500">*</span></label>
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" required 
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                            <template x-if="errors.file">
                                <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.file[0]"></p>
                            </template>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
                            <button type="button" @click="importModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Batal</button>
                            <button type="submit" :disabled="isLoading" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-md shadow-emerald-600/30 disabled:opacity-50">
                                <span x-show="!isLoading">Import Data</span>
                                <span x-show="isLoading">Meng-import...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>

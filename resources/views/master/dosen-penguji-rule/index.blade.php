<x-app-layout title="Rule Komposisi Dosen Penguji">
    <x-slot:header>Rule Komposisi Dosen Penguji</x-slot:header>

    <div x-data="{
        createModal: false,
        editModal: false,
        deleteModal: false,
        editRule: { id: null, dosen_id: null, dosen_nama: '', boleh: [], tidak_boleh: [], keterangan: '' },
        deleteRule: { id: null, dosen_nama: '' },
        openEdit(rule) {
            this.editRule = {
                id: rule.id,
                dosen_id: rule.dosen_id,
                dosen_nama: rule.dosen?.nama_dosen ?? '',
                boleh: (rule.boleh_dosen_ids || []).map(String),
                tidak_boleh: (rule.tidak_boleh_dosen_ids || []).map(String),
                keterangan: rule.keterangan ?? '',
            };
            this.editModal = true;
        },
        openDelete(rule) {
            this.deleteRule = { id: rule.id, dosen_nama: rule.dosen?.nama_dosen ?? '' };
            this.deleteModal = true;
        },
    }">

        <!-- Page Title & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Rule Komposisi Dosen Penguji</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Atur pasangan dosen yang boleh/tidak boleh satu tim dewan penguji. Dipakai sebagai validasi wajib saat plotting jadwal (manual maupun otomatis).</p>
            </div>
            <div>
                <button @click="createModal = true"
                        {{ $dosenBelumAdaRule->isEmpty() ? 'disabled' : '' }}
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shadow-lg shadow-indigo-600/25 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Rule
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold flex items-center justify-between shadow-xs">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">✕</button>
            </div>
        @endif

        <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 rounded-2xl p-4 mb-6 text-xs text-indigo-900 dark:text-indigo-200 space-y-1.5">
            <p class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Cara Kerja Rule Ini
            </p>
            <ul class="list-disc list-inside space-y-1 pl-1">
                <li><b>Tidak Boleh</b> = larangan keras (berlaku dua arah). Jika dua dosen di daftar ini dipasangkan sebagai <b>Ketua Penguji dan Penguji 1</b> pada sidang yang sama, sistem akan menolak penyimpanan jadwal. Penguji 2 (otomatis diisi Pembimbing Utama) tidak diperiksa aturan ini.</li>
                <li><b>Boleh</b> = daftar pasangan yang direkomendasikan/didahulukan, sifatnya catatan saja dan tidak membatasi pasangan lain yang tidak disebutkan di sini.</li>
                <li>Selain itu, sistem juga otomatis menolak jika <b>Anggota Penguji 1</b> memiliki jabatan fungsional lebih tinggi dari <b>Ketua Penguji</b>.</li>
            </ul>
        </div>

        <!-- Table Card -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200/80 dark:border-slate-700 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Dosen</th>
                            <th class="px-6 py-4">Boleh Berpasangan Dengan</th>
                            <th class="px-6 py-4">Tidak Boleh Berpasangan Dengan</th>
                            <th class="px-6 py-4">Keterangan</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($rules as $rule)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors align-top">
                            <td class="px-6 py-4 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                {{ $rule->dosen->nama_dosen ?? '-' }}
                                @if($rule->dosen?->jabatan_fungsional)
                                    <div class="mt-1"><span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-300">{{ $rule->dosen->jabatan_fungsional }}</span></div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse($rule->boleh_dosen_ids ?? [] as $id)
                                        <span class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-[11px] font-semibold">{{ $dosens->firstWhere('id', $id)->nama_dosen ?? '-' }}</span>
                                    @empty
                                        <span class="text-slate-400">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse($rule->tidak_boleh_dosen_ids ?? [] as $id)
                                        <span class="px-2 py-0.5 rounded-md bg-rose-50 dark:bg-rose-500/10 border border-rose-100 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-[11px] font-semibold">{{ $dosens->firstWhere('id', $id)->nama_dosen ?? '-' }}</span>
                                    @empty
                                        <span class="text-slate-400">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400 max-w-xs">
                                {{ $rule->keterangan ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEdit({{ json_encode($rule) }})"
                                            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit Rule">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="openDelete({{ json_encode($rule) }})"
                                            class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Rule">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                Belum ada rule komposisi dosen penguji. Klik tombol <b>Tambah Rule</b> untuk memasukkan aturan baru.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= MODAL TAMBAH RULE ================= -->
        <div x-show="createModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="createModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <div class="inline-block relative z-10 w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">Tambah Rule Komposisi Penguji</h3>
                        <button @click="createModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('master.dosen-penguji-rule.store') }}" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Dosen <span class="text-rose-500">*</span></label>
                            <select name="dosen_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none cursor-pointer">
                                <option value="">-- Pilih Dosen --</option>
                                @foreach($dosenBelumAdaRule as $d)
                                    <option value="{{ $d->id }}">{{ $d->nama_dosen }}{{ $d->jabatan_fungsional ? ' — ' . $d->jabatan_fungsional : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-emerald-600 dark:text-emerald-400 mb-1.5">Boleh Berpasangan Dengan</label>
                                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-3 max-h-56 overflow-y-auto space-y-1.5">
                                    @foreach($dosens as $d)
                                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                            <input type="checkbox" name="boleh_dosen_ids[]" value="{{ $d->id }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            {{ $d->nama_dosen }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-rose-600 dark:text-rose-400 mb-1.5">Tidak Boleh Berpasangan Dengan</label>
                                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-3 max-h-56 overflow-y-auto space-y-1.5">
                                    @foreach($dosens as $d)
                                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                            <input type="checkbox" name="tidak_boleh_dosen_ids[]" value="{{ $d->id }}" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                            {{ $d->nama_dosen }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Keterangan (Opsional)</label>
                            <textarea name="keterangan" rows="2" placeholder="Catatan alasan aturan ini dibuat..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
                            <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/30">Simpan Rule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL EDIT RULE ================= -->
        <div x-show="editModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="editModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <div class="inline-block relative z-10 w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">Edit Rule: <span x-text="editRule.dosen_nama"></span></h3>
                        <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form :action="'/master/dosen-penguji-rule/' + editRule.id" method="POST" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-emerald-600 dark:text-emerald-400 mb-1.5">Boleh Berpasangan Dengan</label>
                                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-3 max-h-56 overflow-y-auto space-y-1.5">
                                    @foreach($dosens as $d)
                                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                            <input type="checkbox" name="boleh_dosen_ids[]" value="{{ $d->id }}" x-model="editRule.boleh" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            {{ $d->nama_dosen }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-rose-600 dark:text-rose-400 mb-1.5">Tidak Boleh Berpasangan Dengan</label>
                                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-3 max-h-56 overflow-y-auto space-y-1.5">
                                    @foreach($dosens as $d)
                                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                            <input type="checkbox" name="tidak_boleh_dosen_ids[]" value="{{ $d->id }}" x-model="editRule.tidak_boleh" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                            {{ $d->nama_dosen }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Keterangan (Opsional)</label>
                            <textarea name="keterangan" x-model="editRule.keterangan" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
                            <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/30">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL HAPUS RULE ================= -->
        <div x-show="deleteModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="deleteModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <div class="inline-block relative z-10 w-full max-w-sm my-8 overflow-hidden text-center align-middle transition-all transform bg-white rounded-2xl shadow-xl p-6">
                    <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Hapus Rule Ini?</h3>
                    <p class="text-xs text-slate-500 mb-6">
                        Apakah Anda yakin ingin menghapus rule komposisi penguji untuk <span class="font-bold text-slate-800" x-text="deleteRule.dosen_nama"></span>?
                    </p>
                    <form :action="'/master/dosen-penguji-rule/' + deleteRule.id" method="POST" class="flex items-center justify-center gap-3">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="deleteModal = false" class="w-1/2 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="w-1/2 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold shadow-md shadow-rose-600/30">Hapus</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>

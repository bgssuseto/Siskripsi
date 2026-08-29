<x-app-layout title="Data Kesediaan Dosen">
    <x-slot:header>Kesediaan Dosen Menguji</x-slot:header>

    <div class="space-y-6">

        {{-- Header Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" x-data="{ importModal: false }">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Data Kesediaan Dosen Menguji</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Informasi ketersediaan hari, tanggal, dan rentang jam dosen untuk penjadwalan ujian sempro dan skripsi.</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="importModal = true"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-lg shadow-emerald-600/25 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import Excel Kesediaan
                </button>
                <form action="{{ route('master.kesediaan-dosen.destroy-all') }}" method="POST"
                      onsubmit="return confirm('⚠️ KONFIRMASI HAPUS SEMUA DATA:\nApakah Anda yakin ingin menghapus SELURUH data kesediaan dosen (semua dosen, semua periode/gelombang)?\n\nTindakan ini tidak dapat dibatalkan!');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm shadow-lg shadow-rose-600/25 transition-all">
                        🗑️ Hapus Semua
                    </button>
                </form>
            </div>

            {{-- ================= MODAL IMPORT KESEDIAAN ================= --}}
            <div x-show="importModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div @click="importModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                    <div class="inline-block relative z-10 w-full max-w-md my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700">
                        <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                            <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">Import Kesediaan dari Excel</h3>
                            <button @click="importModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('master.kesediaan-dosen.import') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                            @csrf
                            <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 rounded-xl p-4 text-xs text-indigo-900 dark:text-indigo-200 space-y-2">
                                <p class="font-bold flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Format File Excel yang Didukung:
                                </p>
                                <ul class="list-disc list-inside space-y-1 pl-1">
                                    <li>Export jawaban Google Form "Kesediaan Menguji" dengan kolom <b>Nama</b> dan <b>Hari/Tanggal yang dipilih</b> (checkbox, boleh lebih dari satu).</li>
                                    <li>Nama dosen dicocokkan otomatis ke Master Dosen (termasuk variasi gelar/ejaan).</li>
                                    <li>Karena formulir hanya mencatat tanggal, jam diasumsikan <b>08:00–17:00</b> — bisa diedit manual sesudahnya bila perlu.</li>
                                </ul>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Periode Akademik <span class="text-rose-500">*</span></label>
                                <select name="periode_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none cursor-pointer">
                                    @foreach($periodes as $p)
                                        <option value="{{ $p->id }}" {{ ($activePeriode && $activePeriode->id === $p->id) ? 'selected' : '' }}>{{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Gelombang (Opsional)</label>
                                <select name="wave_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none cursor-pointer">
                                    <option value="">-- Tidak Spesifik Gelombang --</option>
                                    @foreach($waves as $w)
                                        <option value="{{ $w->id }}">{{ $w->periode->nama_periode ?? '' }} — Gel. {{ $w->gelombang }} ({{ ucfirst($w->jenis) }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Pilih File Excel <span class="text-rose-500">*</span></label>
                                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                            </div>

                            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
                                <button type="button" @click="importModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Batal</button>
                                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-md shadow-emerald-600/30">Import Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold flex items-center justify-between shadow-xs">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">✕</button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm font-semibold flex items-center justify-between shadow-xs">
                <span>{{ session('error') }}</span>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">✕</button>
            </div>
        @endif

        {{-- Pengaturan Form Kesediaan Card --}}
        @if($activePeriode)
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700 p-6 shadow-sm mb-6">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-700 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-lg border border-indigo-100 dark:border-indigo-700 shadow-xs shrink-0">
                        ⚙️
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Pengaturan Akses Form Kesediaan Dosen</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Konfigurasi visibilitas dan status penguncian form kesediaan menguji untuk dosen pada periode aktif: <strong class="text-indigo-600 dark:text-indigo-400">{{ $activePeriode->nama_periode }}</strong></p>
                    </div>
                </div>

                <form method="POST" action="{{ route('master.kesediaan-dosen.settings') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wide">Status Akses Form Dosen</label>
                            <select name="show_form_kesediaan" class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-semibold focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 dark:text-slate-200 transition-all cursor-pointer">
                                <option value="1" {{ $activePeriode->show_form_kesediaan ? 'selected' : '' }}>🟢 Buka (Form Tampil di Dashboard Dosen)</option>
                                <option value="0" {{ !$activePeriode->show_form_kesediaan ? 'selected' : '' }}>🔴 Tutup (Form Disembunyikan)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wide">Status Penguncian Input</label>
                            <select name="lock_form_kesediaan" class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-semibold focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 dark:text-slate-200 transition-all cursor-pointer">
                                <option value="0" {{ !$activePeriode->lock_form_kesediaan ? 'selected' : '' }}>🔓 Buka Kunci (Dosen Bisa Tambah/Hapus Slot)</option>
                                <option value="1" {{ $activePeriode->lock_form_kesediaan ? 'selected' : '' }}>🔒 Kunci Form (Input Dinonaktifkan/Terkunci)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-md transition-all cursor-pointer hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <span>💾</span> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 text-xs font-bold shadow-xs mb-6 flex items-center gap-3">
                <span class="text-lg">⚠️</span>
                <span>Tidak ada periode akademik aktif saat ini. Silakan aktifkan salah satu periode akademik di menu Master Periode terlebih dahulu untuk dapat mengatur akses form kesediaan dosen.</span>
            </div>
        @endif

        {{-- Hak Akses Pengisian Dosen Section --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700 p-6 shadow-sm mb-6">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-700 mb-5">
                <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 flex items-center justify-center font-bold text-lg border border-violet-100 dark:border-violet-700 shadow-xs shrink-0">
                    🔑
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Manajemen Hak Akses Pengisian Dosen</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Aktifkan atau nonaktifkan hak pengisian form kesediaan menguji secara individu untuk setiap dosen.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($dosens as $d)
                    <div class="bg-slate-50/50 dark:bg-slate-700/40 hover:bg-slate-50 dark:hover:bg-slate-700/60 border border-slate-200/80 dark:border-slate-600 rounded-2xl p-4 transition-all duration-200 flex items-center justify-between gap-4 shadow-xs">
                        <div class="min-w-0">
                            <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm leading-snug truncate" title="{{ $d->nama_dosen }}">{{ $d->nama_dosen }}</h4>
                            <span class="inline-block mt-1 px-2 py-0.5 rounded bg-slate-200/60 dark:bg-slate-600 text-slate-600 dark:text-slate-300 font-mono text-[10px]">NIDN: {{ $d->nidn }}</span>
                            <div class="mt-2">
                                @if($d->can_fill_kesediaan)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        🟢 Akses Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200">
                                        🔴 Dibatasi
                                    </span>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('master.kesediaan-dosen.toggle-access', $d->hash_id) }}" class="shrink-0">
                            @csrf
                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" class="sr-only peer" {{ $d->can_fill_kesediaan ? 'checked' : '' }} onchange="this.form.submit()">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Toolbar / Filters --}}
        <form method="GET" action="{{ route('master.kesediaan-dosen.index') }}" class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700 p-4 mb-6 shadow-sm flex flex-wrap items-center gap-4">
            <div class="relative flex-1 max-w-sm min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama dosen, NIP, keterangan…" class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <x-filter-popover :active="request()->hasAny(['dosen_id','periode_id','per_page'])">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Tampilkan</label>
                    <select name="per_page" class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-200 cursor-pointer">
                        <option value="5" {{ request('per_page') == 5 ? 'selected' : '' }}>5 per halaman</option>
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 per halaman</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 per halaman</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Dosen</label>
                    <select name="dosen_id" class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-200 cursor-pointer">
                        <option value="">Semua Dosen</option>
                        @foreach ($dosens as $d)
                            <option value="{{ $d->id }}" {{ request('dosen_id') == $d->id ? 'selected' : '' }}>
                                {{ $d->nama_dosen }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Periode</label>
                    <select name="periode_id" class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-200 cursor-pointer">
                        <option value="">Semua Periode</option>
                        @foreach ($periodes as $p)
                            <option value="{{ $p->id }}" {{ (request('periode_id', $activePeriode->id ?? null) == $p->id) ? 'selected' : '' }}>
                                {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </x-filter-popover>

            <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl shadow-xs transition-colors cursor-pointer">Filter</button>
            @if(request()->hasAny(['search','dosen_id','periode_id','per_page']))
                <a href="{{ route('master.kesediaan-dosen.index') }}" class="px-4 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-sm rounded-xl transition-colors">✕ Reset</a>
            @endif
        </form>

        {{-- Table Card --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-700/60 border-b border-slate-200/80 dark:border-slate-600 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-center" style="width: 50px;">No</th>
                            <th class="px-6 py-4">Nama Dosen & NIP</th>
                            <th class="px-6 py-4">Ketersediaan Hari & Tanggal</th>
                            <th class="px-6 py-4">Gelombang / Periode</th>
                            <th class="px-6 py-4">Keterangan</th>
                            <th class="px-6 py-4 text-right" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($kesediaans as $item)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors">
                                <td class="px-6 py-4 text-center font-bold text-slate-400 dark:text-slate-500">
                                    {{ ($kesediaans->currentPage() - 1) * $kesediaans->perPage() + $loop->iteration }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $item['dosen']->nama_dosen ?? '-' }}</div>
                                    @if($item['dosen'] && $item['dosen']->nidn)
                                        <div class="mt-0.5"><span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-600 text-slate-600 dark:text-slate-300 font-mono text-[10px]">NIDN: {{ $item['dosen']->nidn }}</span></div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5 max-w-md">
                                        @foreach($item['dates'] as $date)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-100 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 text-xs font-bold whitespace-nowrap">
                                                📅 {{ \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('l, d F Y') }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($item['wave'])
                                        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/40 px-2.5 py-1 rounded-md border border-indigo-200 dark:border-indigo-700 inline-block">
                                            Gel. {{ $item['wave']->gelombang }} ({{ ucfirst($item['wave']->jenis) }})
                                        </span>
                                    @elseif($item['periode'])
                                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded-md">
                                            {{ $item['periode']->nama_periode }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs font-medium text-slate-700 dark:text-slate-300">
                                    {{ implode(', ', $item['keterangans']) ?: '—' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('master.kesediaan-dosen.destroy-group') }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh data kesediaan dosen untuk gelombang ini?')">
                                        @csrf
                                        <input type="hidden" name="dosen_id" value="{{ $item['dosen_id'] }}">
                                        <input type="hidden" name="wave_id" value="{{ $item['wave_id'] }}">
                                        <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 transition-colors cursor-pointer" title="Hapus Semua">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-12 text-slate-400 dark:text-slate-500">
                                    <div class="text-3xl mb-2">📝</div>
                                    <div class="font-bold text-slate-600 dark:text-slate-400">Belum Ada Data Kesediaan Dosen</div>
                                    <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">Dosen akan mengisi kesediaan menguji melalui dashboard portal dosen.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($kesediaans->hasPages())
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/30 border-t border-slate-100 dark:border-slate-700">
                    {{ $kesediaans->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

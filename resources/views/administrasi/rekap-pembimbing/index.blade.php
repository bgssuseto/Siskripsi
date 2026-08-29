<x-app-layout title="Administrasi - Rekap Dosen Pembimbing">
    <x-slot:header>Rekap Dosen Pembimbing</x-slot:header>

    <div class="space-y-6">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 tracking-tight flex items-center gap-2.5">
                    <span class="p-2 bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </span>
                    Rekap Dosen Pembimbing
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Jumlah mahasiswa bimbingan per dosen, dipisah antara Pembimbing Utama dan Pembimbing Pendamping.
                </p>
            </div>

            @if($dosenRows->count() > 0)
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('administrasi.sk.export-pembimbing', array_merge(request()->except('jenis'), ['jenis_tugas_akhir' => $jenisTa])) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Excel
                </a>
            </div>
            @endif
        </div>

        <!-- Filter -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form method="GET" action="{{ route('administrasi.rekap-pembimbing.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                <!-- Periode -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Periode Akademik</label>
                    <select name="periode_id" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all">
                        <option value="">-- Semua Periode --</option>
                        @foreach($periodes as $p)
                        <option value="{{ $p->id }}" {{ $selectedPeriodeId == $p->id ? 'selected' : '' }}>
                            {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Jenis TA -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Jenis TA</label>
                    <select name="jenis" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all">
                        <option value="">-- Semua Jenis --</option>
                        <option value="sempro" {{ $jenisTa === 'sempro' ? 'selected' : '' }}>Seminar Proposal (Sempro)</option>
                        <option value="skripsi" {{ $jenisTa === 'skripsi' ? 'selected' : '' }}>Sidang Skripsi</option>
                    </select>
                </div>

                <!-- Gelombang -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Gelombang</label>
                    <select name="gelombang" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all">
                        <option value="">-- Semua Gelombang --</option>
                        @foreach($gelombangOptions ?? [] as $g)
                        <option value="{{ $g }}" {{ (string) $selectedGelombang === (string) $g ? 'selected' : '' }}>
                            Gelombang {{ $g }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit -->
                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-medium text-sm rounded-xl shadow-md transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter Data
                    </button>

                    @if($selectedPeriodeId || $jenisTa || $selectedGelombang)
                    <a href="{{ route('administrasi.rekap-pembimbing.index') }}"
                       class="px-3.5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium text-sm rounded-xl transition-all flex items-center justify-center" title="Reset Filter">
                        Reset
                    </a>
                    @endif
                </div>

            </form>
        </div>

        <!-- Tabel Rekap Pembimbing -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">

            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-slate-100 text-base">Daftar Dosen Pembimbing</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Menampilkan {{ $dosenRows->count() }} dosen dengan mahasiswa bimbingan pada filter ini.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200/80 dark:border-slate-700 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4 text-center w-12">No</th>
                            <th class="py-3.5 px-4">Nama Dosen & Gelar</th>
                            <th class="py-3.5 px-4">NIDN</th>
                            <th class="py-3.5 px-4 text-center">Pembimbing Utama</th>
                            <th class="py-3.5 px-4 text-center">Pembimbing Pendamping</th>
                            <th class="py-3.5 px-4 text-center">Total Bimbingan</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                        @forelse($dosenRows as $idx => $row)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400 dark:text-slate-500">
                                {{ $idx + 1 }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800 dark:text-slate-100">
                                {{ $row['dosen']->nama_dosen }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 font-mono text-xs">
                                {{ $row['dosen']->nidn ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300">
                                    {{ $row['utama'] }} Mahasiswa
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-300">
                                    {{ $row['pendamping'] }} Mahasiswa
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200">
                                    {{ $row['total'] }} Mahasiswa
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <button type="button"
                                        onclick='openDetailPembimbing(@json($row["dosen"]->nama_dosen), @json($row["mahasiswa"]))'
                                        class="inline-flex items-center justify-center p-2 bg-teal-50 hover:bg-teal-100 dark:bg-teal-500/10 dark:hover:bg-teal-500/20 text-teal-700 dark:text-teal-300 rounded-lg transition-all border border-teal-200 dark:border-teal-800"
                                        title="Detail Bimbingan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="max-w-xs mx-auto text-center">
                                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <p class="font-semibold text-slate-600 dark:text-slate-300">Belum ada data bimbingan</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Gunakan filter di atas untuk menampilkan dosen dengan mahasiswa bimbingan.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- ============ MODAL DETAIL MAHASISWA BIMBINGAN ============ -->
    <div id="modal-detail-pembimbing" class="fixed inset-0 z-50 overflow-y-auto" style="display:none;" onclick="if (event.target === this) closeDetailPembimbing()">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-200" id="detail-pembimbing-backdrop"></div>
            <div class="relative z-10 w-full max-w-4xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-all duration-200" id="detail-pembimbing-panel">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 shrink-0 rounded-xl bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-300 border border-teal-100 dark:border-teal-900 flex items-center justify-center text-lg">
                            🎓
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 dark:text-slate-100 text-base">Mahasiswa Bimbingan</h3>
                            <p id="detail-pembimbing-dosen" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span id="detail-pembimbing-count" class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-100 dark:border-teal-900"></span>
                        <button type="button" onclick="closeDetailPembimbing()" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">✕</button>
                    </div>
                </div>
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="w-full text-left border-collapse text-sm table-fixed">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200/80 dark:border-slate-700 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider sticky top-0">
                                <th class="py-2.5 px-4 text-center w-10">No</th>
                                <th class="py-2.5 px-4 w-[13%]">NIM</th>
                                <th class="py-2.5 px-4 w-[20%]">Nama Mahasiswa</th>
                                <th class="py-2.5 px-4">Judul Tugas Akhir</th>
                                <th class="py-2.5 px-4 w-[15%]">Peran</th>
                            </tr>
                        </thead>
                        <tbody id="detail-pembimbing-body" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-slate-100 dark:border-slate-800 flex justify-end bg-slate-50/60 dark:bg-slate-800/40">
                    <button type="button" onclick="closeDetailPembimbing()" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium text-xs rounded-xl transition-all">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function openDetailPembimbing(namaDosen, mahasiswa) {
            document.getElementById('detail-pembimbing-dosen').textContent = namaDosen;
            const count = (mahasiswa || []).length;
            const countEl = document.getElementById('detail-pembimbing-count');
            countEl.textContent = count + ' Mahasiswa';
            const body = document.getElementById('detail-pembimbing-body');
            body.innerHTML = '';

            if (!mahasiswa || mahasiswa.length === 0) {
                body.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-slate-400 text-xs italic">Tidak ada data mahasiswa bimbingan.</td></tr>';
            } else {
                mahasiswa.forEach((m, idx) => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50/80 dark:hover:bg-slate-800/60';
                    const peranBadgeClass = m.peran === 'Pembimbing Utama'
                        ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                        : 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-300';
                    tr.innerHTML = `
                        <td class="py-2.5 px-4 text-center text-slate-400 dark:text-slate-500 align-top">${idx + 1}</td>
                        <td class="py-2.5 px-4 font-mono text-xs text-slate-600 dark:text-slate-300 align-top break-words">${escapeHtml(m.nim) || '-'}</td>
                        <td class="py-2.5 px-4 font-semibold text-slate-800 dark:text-slate-100 align-top"><span class="line-clamp-2" title="${escapeHtml(m.nama) || ''}">${escapeHtml(m.nama) || '-'}</span></td>
                        <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300 align-top"><span class="line-clamp-2" title="${escapeHtml(m.judul) || ''}">${escapeHtml(m.judul) || '-'}</span></td>
                        <td class="py-2.5 px-4 align-top"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold ${peranBadgeClass}">${escapeHtml(m.peran)}</span></td>
                    `;
                    body.appendChild(tr);
                });
            }

            const backdrop = document.getElementById('detail-pembimbing-backdrop');
            const panel = document.getElementById('detail-pembimbing-panel');
            backdrop.classList.add('opacity-0');
            panel.classList.add('opacity-0', 'scale-95');
            document.getElementById('modal-detail-pembimbing').style.display = 'block';
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                panel.classList.remove('opacity-0', 'scale-95');
            });
        }

        function closeDetailPembimbing() {
            document.getElementById('modal-detail-pembimbing').style.display = 'none';
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDetailPembimbing();
        });
    </script>
</x-app-layout>

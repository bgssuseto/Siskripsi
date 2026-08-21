<x-app-layout title="Jadwal Sempro">
    <x-slot:header>Jadwal Sempro</x-slot:header>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <style>
        .sidang-page { font-family: 'Inter', 'Segoe UI', sans-serif; }

        .fc { font-family: 'Inter', sans-serif !important; }
        .fc-col-header-cell-cushion { font-size: .8rem; font-weight: 800; text-transform: uppercase; text-decoration: none !important; }
        .fc-event { border-radius: 6px !important; padding: .1rem .3rem !important; cursor: pointer !important; font-size: .75rem !important; font-weight: 600 !important; }
        .fc-header-toolbar { margin-bottom: 1.25rem !important; }
        .fc-button-primary { background-color: #8b5cf6 !important; border-color: #8b5cf6 !important; border-radius: 8px !important; font-size: .85rem !important; font-weight: 600 !important; }
        .fc-button-primary:hover { background-color: #7c3aed !important; border-color: #7c3aed !important; }
        .fc-button-active { background-color: #6d28d9 !important; border-color: #6d28d9 !important; }
        .fc-daygrid-day-number { font-size: .85rem !important; font-weight: 800 !important; text-decoration: none !important; }

        .view-toggle {
            display: inline-flex; background: #e2e8f0; border-radius: 12px; padding: .25rem; gap: .25rem;
        }
        .view-toggle-btn {
            padding: .5rem 1rem; border-radius: 10px; font-size: .85rem; font-weight: 700;
            cursor: pointer; transition: all .2s; border: none; background: transparent; color: #475569;
        }
        .view-toggle-btn.active {
            background: #fff; color: #8b5cf6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }

        .stat-card {
            background: #fff; border-radius: 16px; padding: 1.25rem 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #f1f5f9;
        }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; flex-shrink: 0;
        }
        .stat-icon.purple { background: #f3e8ff; }
        .stat-icon.green  { background: #f0fdf4; }
        .stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1; }
        .stat-label { font-size: .8rem; font-weight: 600; margin-top: .2rem; }

        .toolbar {
            background: #fff; border-radius: 14px; padding: 1rem;
            display: flex; flex-wrap: wrap; gap: .75rem; align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,.07); border: 1px solid #f1f5f9;
        }
        .toolbar-search { position: relative; flex: 1; min-width: 200px; }
        .toolbar-search svg {
            position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
            color: #94a3b8; pointer-events: none;
        }
        .toolbar-search input {
            width: 100%; padding: .55rem .75rem .55rem 2.25rem;
            border: 1px solid #e2e8f0; border-radius: 10px; font-size: .875rem;
            transition: border-color .15s, box-shadow .15s; color: #1e293b;
        }
        .toolbar-search input:focus {
            outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,.12);
        }
        .filter-select {
            padding: .55rem .85rem; border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: .875rem; color: #1e293b; background: #fff; cursor: pointer;
        }

        .btn {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .55rem 1rem; border-radius: 10px; font-size: .875rem;
            font-weight: 600; cursor: pointer; transition: all .15s; border: none; text-decoration: none;
        }
        .btn-primary { background: #8b5cf6; color: #fff; }
        .btn-primary:hover { background: #7c3aed; box-shadow: 0 4px 12px rgba(139,92,246,.35); }
        .btn-success { background: #10b981; color: #fff; }
        .btn-success:hover { background: #059669; box-shadow: 0 4px 12px rgba(16,185,129,.35); }
        .btn-outline { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: .3rem .65rem; font-size: .78rem; border-radius: 7px; }

        .table-card {
            background: #fff; border-radius: 16px; overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #f1f5f9;
        }
        .table-scroll { overflow-x: auto; }
        table.sidang-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
        table.sidang-table thead th {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #e2e8f0; font-weight: 600; padding: .75rem .85rem;
            text-align: left; font-size: .78rem; letter-spacing: .03em; text-transform: uppercase;
        }
        table.sidang-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
        table.sidang-table tbody tr:hover { background: #f8fafc; }
        table.sidang-table tbody tr.conflict-schedule { background: #fef2f2 !important; }
        table.sidang-table td { padding: .75rem .85rem; vertical-align: top; color: #334155; }

        /* Dark Mode Overrides for Penjadwalan Sempro */
        html.dark .sidang-page h1 { color: #f8fafc !important; }
        html.dark .sidang-page p { color: #cbd5e1 !important; }
        html.dark .view-toggle { background: #334155 !important; }
        html.dark .view-toggle-btn { color: #94a3b8 !important; }
        html.dark .view-toggle-btn.active { background: #1e293b !important; color: #a78bfa !important; }
        html.dark .stat-card, html.dark .toolbar, html.dark .table-card {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }
        html.dark .stat-value { color: #f8fafc !important; }
        html.dark .stat-label { color: #94a3b8 !important; }
        html.dark table.sidang-table tbody tr {
            border-bottom-color: #334155 !important;
        }
        html.dark table.sidang-table tbody tr:hover {
            background-color: #334155 !important;
        }
        html.dark table.sidang-table tbody tr.conflict-schedule {
            background-color: rgba(159, 18, 57, 0.3) !important;
        }
        html.dark table.sidang-table td {
            color: #e2e8f0 !important;
        }
        html.dark .schedule-hari {
            color: #f8fafc !important;
        }
        html.dark .schedule-jam {
            color: #a5b4fc !important;
        }
        html.dark .schedule-ruang {
            background-color: rgba(147, 51, 234, 0.25) !important;
            color: #c084fc !important;
        }
        html.dark .filter-select, html.dark .toolbar-search input {
            background-color: #0f172a !important;
            color: #f8fafc !important;
            border-color: #475569 !important;
        }
        html.dark .modal-box {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border: 1px solid #334155 !important;
        }
        html.dark .modal-header, html.dark .modal-footer {
            border-color: #334155 !important;
            background-color: #0f172a !important;
        }
        html.dark .modal-title { color: #f8fafc !important; }
        html.dark .form-control {
            background-color: #0f172a !important;
            color: #f8fafc !important;
            border-color: #475569 !important;
        }
        html.dark .form-group label { color: #cbd5e1 !important; }
        html.dark .form-section-title { color: #94a3b8 !important; border-bottom-color: #334155 !important; }
        html.dark .nim-pill { background-color: #334155 !important; color: #cbd5e1 !important; }
        html.dark .dosen-chip { background-color: #334155 !important; border-color: #475569 !important; color: #cbd5e1 !important; }
        html.dark .dosen-chip.utama { background-color: rgba(147, 51, 234, 0.25) !important; border-color: rgba(147, 51, 234, 0.5) !important; color: #c084fc !important; }

        .badge { display: inline-flex; align-items: center; padding: .25rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
        .badge-sempro  { background: #f3e8ff; color: #6b21a8; }

        .nim-pill { font-family: 'Courier New', monospace; background: #f1f5f9; color: #475569; padding: .15rem .5rem; border-radius: 6px; font-size: .78rem; font-weight: 600; }
        .judul-text { font-size: .8rem; color: #1e293b; font-weight: 500; line-height: 1.4; max-width: 260px; }
        .dosen-chip { display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 7px; padding: .15rem .5rem; font-size: .75rem; color: #475569; margin: .1rem 0; }
        .dosen-chip.utama { background: #f3e8ff; border-color: #d8b4fe; color: #6b21a8; font-weight: 600; }

        .schedule-cell { min-width: 140px; }
        .schedule-hari { font-weight: 700; color: #1e293b; font-size: .8rem; }
        .schedule-jam  { font-size: .75rem; color: #64748b; margin-top: .1rem; }
        .schedule-ruang { display: inline-flex; align-items: center; gap: .25rem; background: #f3e8ff; color: #6b21a8; font-weight: 700; padding: .15rem .5rem; border-radius: 6px; font-size: .75rem; }

        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem; }
        .modal-box { background: #fff; border-radius: 20px; width: 100%; max-width: 680px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.2); }
        .modal-box.modal-sm { max-width: 460px; }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
        .modal-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
        .modal-close { width: 32px; height: 32px; border: none; background: #f1f5f9; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; }
        .modal-body { padding: 1.25rem 1.5rem; }
        .modal-alert {
            display: flex; align-items: flex-start; gap: .6rem;
            background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
            border-radius: 12px; padding: .75rem .9rem; margin-bottom: 1rem;
            font-size: .8rem; font-weight: 600; line-height: 1.4;
        }
        html.dark .modal-alert {
            background: rgba(244, 63, 94, 0.12) !important;
            border-color: rgba(244, 63, 94, 0.35) !important;
            color: #fda4af !important;
        }
        .modal-footer { display: flex; gap: .75rem; justify-content: flex-end; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }
        .form-group { display: flex; flex-direction: column; gap: .35rem; }
        .form-group label { font-size: .78rem; font-weight: 600; color: #374151; }
        .form-control { padding: .55rem .75rem; border: 1px solid #e2e8f0; border-radius: 10px; font-size: .875rem; color: #1e293b; background: #fff; width: 100%; }
        .form-control:focus { outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,.12); }
        textarea.form-control { resize: vertical; min-height: 70px; }
        .form-section { margin-top: 1.1rem; }
        .form-section-title { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: .6rem; padding-bottom: .35rem; border-bottom: 1px solid #f1f5f9; }

        .pagination-wrap { padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; background: #fff; }
        .calendar-container { background: #fff; border-radius: 16px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #f1f5f9; }

        @media (max-width: 640px) { .form-grid-2 { grid-template-columns: 1fr; } }
    </style>

    <div class="sidang-page space-y-6" x-data="{
        currentView: 'table',
        selectedIds: [],
        goToAutoPlot() {
            if (this.selectedIds.length === 0) return;
            const params = new URLSearchParams();
            params.set('generate', '1');
            params.set('periode_id', '{{ request('periode_id', $activePeriode->id ?? '') }}');
            params.set('jenis', 'sempro');
            this.selectedIds.forEach(id => params.append('ids[]', id));
            window.location.href = '{{ route('jadwal.auto-plot.index') }}?' + params.toString();
        },
        openBulkManual() {
            if (this.selectedIds.length === 0) return;
            document.getElementById('bulk-jadwalkan-result').innerHTML = '';
            hideModalAlert('form-bulk-jadwalkan-alert');
            openModal('modal-bulk-jadwalkan');
        },
        async submitBulkJadwalkan() {
            const tanggal = document.getElementById('bulk-tanggal').value;
            const jamMulai = document.getElementById('bulk-jam-mulai').value;
            const durasi = document.getElementById('bulk-durasi').value;
            const ruangId = document.getElementById('bulk-ruang').value;

            if (!tanggal || !jamMulai || !durasi || !ruangId) {
                showModalAlert('form-bulk-jadwalkan-alert', 'form-bulk-jadwalkan-alert-text', 'Semua field wajib diisi.');
                return;
            }

            const resultBox = document.getElementById('bulk-jadwalkan-result');
            resultBox.innerHTML = '⏳ Memproses...';
            hideModalAlert('form-bulk-jadwalkan-alert');

            try {
                const response = await fetch('{{ route('jadwal.sempro.bulk-jadwalkan') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ ids: this.selectedIds, tanggal, jam_mulai: jamMulai, durasi_menit: durasi, ruang_id: ruangId })
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    let html = `<div class='text-emerald-700 font-bold mb-1'>✅ ${result.berhasil.length} mahasiswa berhasil dijadwalkan.</div>`;
                    if (result.gagal && result.gagal.length > 0) {
                        html += `<div class='text-rose-600 font-bold mb-1 mt-2'>⚠️ ${result.gagal.length} gagal:</div><ul class='list-disc pl-4 space-y-0.5 text-slate-600'>`;
                        result.gagal.forEach(g => { html += `<li><strong>${g.nama}</strong>: ${g.alasan}</li>`; });
                        html += '</ul>';
                    }
                    resultBox.innerHTML = html;
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    setTimeout(() => location.reload(), (result.gagal && result.gagal.length > 0) ? 3500 : 1200);
                } else {
                    resultBox.innerHTML = '';
                    showModalAlert('form-bulk-jadwalkan-alert', 'form-bulk-jadwalkan-alert-text', result.message || 'Terjadi kesalahan.');
                }
            } catch (err) {
                console.error(err);
                resultBox.innerHTML = '';
                showModalAlert('form-bulk-jadwalkan-alert', 'form-bulk-jadwalkan-alert-text', 'Gagal terhubung ke server.');
            }
        },
    }">

        {{-- Top Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Jadwal Sempro</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Penjadwalan seminar proposal mahasiswa.</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="view-toggle">
                    <button class="view-toggle-btn" :class="{ 'active': currentView === 'table' }" @click="currentView = 'table'">
                        📋 Tabel
                    </button>
                    <button class="view-toggle-btn" :class="{ 'active': currentView === 'calendar' }" @click="currentView = 'calendar'; $nextTick(() => initCalendar())">
                        📅 Kalender
                    </button>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="stat-card">
                <div class="stat-icon purple">📋</div>
                <div>
                    <div class="stat-value">{{ $totalSempro }}</div>
                    <div class="stat-label">Total Mahasiswa Sempro</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📅</div>
                <div>
                    <div class="stat-value">{{ $activePeriode ? $activePeriode->nama_periode : '—' }}</div>
                    <div class="stat-label">Periode Aktif</div>
                </div>
            </div>
        </div>

        {{-- Toolbar / Filter --}}
        <form method="GET" action="{{ route('jadwal-sempro.index') }}" class="toolbar">
            <div class="toolbar-search">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NIM, nama, judul proposal…">
            </div>

            <select name="per_page" class="filter-select" onchange="this.form.submit()">
                <option value="5" {{ request('per_page', 5) == 5 ? 'selected' : '' }}>Tampilkan 5 data</option>
                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>Tampilkan 10 data</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>Tampilkan 25 data</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>Tampilkan 100 data</option>
            </select>

            <select name="status" class="filter-select">
                <option value="">Semua Status Plotting</option>
                <option value="belum" {{ request('status') == 'belum' ? 'selected' : '' }}>Belum Plotting</option>
                <option value="sudah" {{ request('status') == 'sudah' ? 'selected' : '' }}>Sudah Dijadwal</option>
            </select>

            <select name="periode_id" class="filter-select">
                @foreach ($periodes as $p)
                    <option value="{{ $p->id }}" {{ (request('periode_id', $activePeriode->id ?? null) == $p->id) ? 'selected' : '' }}>
                        {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>

            <select name="gelombang" class="filter-select">
                <option value="">Semua Gelombang</option>
                @foreach ($gelombangOptions ?? [] as $g)
                    <option value="{{ $g }}" {{ (string) request('gelombang') === (string) $g ? 'selected' : '' }}>Gelombang {{ $g }}</option>
                @endforeach
            </select>

            <x-filter-popover :active="request()->hasAny(['dosen_pembimbing_id'])">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Dosen Pembimbing</label>
                    <select name="dosen_pembimbing_id" class="filter-select w-full">
                        <option value="">-- Semua Dosen --</option>
                        @foreach ($dosens as $d)
                            <option value="{{ $d->id }}" {{ (string) request('dosen_pembimbing_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->nama_dosen }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filter-popover>

            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->hasAny(['search','status','periode_id','gelombang','dosen_pembimbing_id']))
                <a href="{{ route('jadwal-sempro.index') }}" class="btn btn-outline">✕ Reset</a>
            @endif
        </form>

        {{-- TAMPILAN TABEL --}}
        <div x-show="currentView === 'table'" class="space-y-4">
            {{-- Bulk Action Bar --}}
            <div x-show="selectedIds.length > 0" x-cloak class="flex items-center justify-between gap-3 bg-violet-50 border border-violet-200 rounded-2xl p-3.5">
                <span class="text-xs font-bold text-violet-800" x-text="selectedIds.length + ' mahasiswa dipilih'"></span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="goToAutoPlot()" class="btn btn-primary btn-sm">📅 Jadwalkan Terpilih (Otomatis)</button>
                    <button type="button" @click="openBulkManual()" class="btn btn-primary btn-sm">🗓️ Plot Manual (Massal)</button>
                    <button type="button" @click="selectedIds = []" class="btn btn-outline btn-sm">Batalkan Pilihan</button>
                </div>
            </div>

            <div id="table-container" class="table-card">
                <div class="table-scroll">
                    <table class="sidang-table">
                        <thead>
                            <tr>
                                <th style="width:32px; text-align:center;">
                                    <input type="checkbox" @click="selectedIds = $event.target.checked ? {{ Js::from($sidangs->where('tanggal', null)->pluck('id')->map(fn($id) => (string) $id)->values()) }} : []">
                                </th>
                                <th style="width:42px; text-align:center;">No</th>
                                <th style="width:90px;">Tgl Daftar</th>
                                <th>NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Status</th>
                                <th>Periode</th>
                                <th>Dosbing Utama</th>
                                <th>Dosbing Pendamping</th>
                                <th>Jadwal Ujian</th>
                                <th>Ruangan</th>
                                <th style="width:130px; text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sidangs as $item)
                                @php
                                    $hasConflict = isset($conflictMap[$item->id]) && !empty($conflictMap[$item->id]['schedule']);
                                @endphp
                                <tr class="{{ $hasConflict ? 'conflict-schedule' : '' }}">
                                    <td style="text-align:center; vertical-align: middle;">
                                        @if(empty($item->tanggal))
                                            <input type="checkbox" x-model="selectedIds" value="{{ $item->id }}">
                                        @endif
                                    </td>
                                    <td style="text-align:center; color:#475569; vertical-align: middle;">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="font-bold text-xs">{{ ($sidangs->currentPage() - 1) * $sidangs->perPage() + $loop->iteration }}</span>
                                            @if($hasConflict)
                                                <span class="text-red-600 text-xs" title="Bentrok Jadwal">⚠️</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($item->tanggal_pendaftaran)
                                            <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-1 rounded-md whitespace-nowrap">
                                                {{ $item->tanggal_pendaftaran->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span style="color:#cbd5e1;">—</span>
                                        @endif
                                    </td>
                                    <td><span class="nim-pill">{{ $item->nim }}</span></td>
                                        <td style="font-weight:600; color:#1e293b; min-width:160px;">
                                            <div class="text-slate-900 dark:text-slate-100 font-extrabold">{{ $item->nama_mahasiswa }}</div>
                                            @if($hasConflict)
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800 shadow-2xs">
                                                        ⚠️ Jadwal Bentrok
                                                    </span>
                                                </div>
                                                <div class="mt-1 space-y-0.5">
                                                    @foreach($conflictMap[$item->id]['schedule'] as $msg)
                                                        <div class="text-[10.5px] leading-snug text-rose-800 dark:text-rose-300 font-bold pl-1">• {{ $msg }}</div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    <td>
                                        {!! $item->getJadwalStatusHtml() !!}
                                    </td>
                                    <td><span class="text-xs font-semibold text-slate-500">{{ $item->periode ? $item->periode->nama_periode : '—' }}</span></td>
                                    <td><span class="dosen-chip utama">{{ $item->pembimbingUtama ? $item->pembimbingUtama->nama_dosen : '—' }}</span></td>
                                    <td><span class="dosen-chip">{{ $item->pembimbingPendamping ? $item->pembimbingPendamping->nama_dosen : '—' }}</span></td>
                                    <td class="schedule-cell">
                                        @if($item->tanggal)
                                            <div class="schedule-hari">{{ $item->tanggal->locale('id')->translatedFormat('l, d F Y') }}</div>
                                            <div class="schedule-jam">{{ $item->jam ?: '—' }}</div>
                                        @else
                                            <span style="color:#cbd5e1;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->ruang)
                                            <span class="schedule-ruang">
                                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke="currentColor" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                {{ $item->ruang->kode_ruangan }}
                                            </span>
                                        @else
                                            <span style="color:#cbd5e1;">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right space-x-1 whitespace-nowrap">
                                        <button class="btn btn-primary btn-sm" title="{{ empty($item->tanggal) ? 'Jadwalkan' : 'Edit Jadwal' }}"
                                            onclick="openJadwalkanSempro('{{ $item->hash_id }}', '{{ addslashes($item->nama_mahasiswa) }}', '{{ $item->nim }}', '{{ $item->tanggal ? $item->tanggal->format('Y-m-d') : '' }}', '{{ $item->jam ?? '' }}', '{{ $item->ruang_id ?? '' }}')">
                                            📅
                                        </button>
                                        <button class="btn btn-outline btn-sm" title="Edit Data"
                                            onclick="openEdit('{{ $item->hash_id }}', {{ json_encode([
                                                'id' => $item->id,
                                                'nim' => $item->nim,
                                                'nama_mahasiswa' => $item->nama_mahasiswa,
                                                'judul_skripsi' => $item->judul_skripsi,
                                                'dosen_pembimbing_utama_id' => $item->dosen_pembimbing_utama_id,
                                                'dosen_pembimbing_pendamping_id' => $item->dosen_pembimbing_pendamping_id,
                                                'ruang_id' => $item->ruang_id,
                                                'periode_id' => $item->periode_id,
                                                'tanggal' => $item->tanggal ? $item->tanggal->format('Y-m-d') : '',
                                                'tanggal_pendaftaran' => $item->tanggal_pendaftaran ? $item->tanggal_pendaftaran->format('Y-m-d') : '',
                                                'jam' => $item->jam,
                                                'conflict_schedule' => $conflictMap[$item->id]['schedule'] ?? [],
                                            ]) }})">
                                            ✏️
                                        </button>
                                        <button class="btn btn-danger btn-sm" title="Hapus"
                                            onclick="openDelete('{{ $item->hash_id }}', '{{ addslashes($item->nama_mahasiswa) }}')">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="py-12 text-center text-slate-400">
                                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        <h3>Belum ada data sempro</h3>
                                        <p style="font-size:.85rem;">Import Excel di menu Data Sempro terlebih dahulu.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($sidangs->hasPages())
                    <div class="pagination-wrap">
                        {{ $sidangs->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- TAMPILAN KALENDER --}}
        <div x-show="currentView === 'calendar'" id="calendar-container" class="calendar-container" x-cloak>
            <div id="calendar-view" data-events="{{ json_encode($calendarEvents->values()) }}"></div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: PLOTTING JADWAL SEMPRO                                    --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="modal-jadwalkan" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-jadwalkan')">
        <div class="modal-box modal-sm">
            <div class="modal-header">
                <span class="modal-title">📅 Plotting Jadwal Sempro</span>
                <button class="modal-close" onclick="closeModal('modal-jadwalkan')">✕</button>
            </div>
            <form method="POST" id="form-jadwalkan" action="">
                @csrf
                <div class="modal-body">
                    <div id="form-jadwalkan-alert" class="modal-alert" style="display:none;">
                        <span>⚠️</span>
                        <span id="form-jadwalkan-alert-text"></span>
                    </div>
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl mb-4 text-xs">
                        <div class="font-bold text-slate-800 text-sm" id="jadwalkan-mhs-nama"></div>
                        <div class="text-slate-500 font-semibold mt-0.5" id="jadwalkan-mhs-nim"></div>
                    </div>

                    {{-- Info Box Kesediaan Menguji Dosen: awalnya tampilkan semua slot, otomatis
                         difilter ke dosen yang siap pada tanggal terpilih begitu tanggal diklik --}}
                    @if(isset($kesediaanDosens) && $kesediaanDosens->count() > 0)
                        <div class="mb-4 bg-emerald-50/80 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-700 rounded-2xl p-3.5 text-xs">
                            <div class="flex items-center justify-between font-extrabold text-emerald-900 dark:text-emerald-300 mb-2">
                                <span class="flex items-center gap-1.5">
                                    <span>📝</span> Dosen Siap Menguji
                                </span>
                                <span class="bg-emerald-200 dark:bg-emerald-500/20 text-emerald-900 dark:text-emerald-300 text-[10px] px-2 py-0.5 rounded-full font-extrabold" id="kesediaan-info-count">
                                    {{ $kesediaanDosens->count() }} Slot Terdaftar
                                </span>
                            </div>
                            <div class="max-h-32 overflow-y-auto space-y-1.5 pr-1" id="kesediaan-info-list">
                                @foreach($kesediaanDosens as $kd)
                                    <div class="flex items-center justify-between bg-white border border-emerald-100 rounded-xl p-2 text-[11px]">
                                        <div>
                                            <strong class="text-slate-800">{{ $kd->dosen->nama_dosen ?? 'Dosen' }}</strong>
                                            <span class="text-indigo-700 font-bold ml-1">({{ \Carbon\Carbon::parse($kd->tanggal)->locale('id')->translatedFormat('l, d F Y') }})</span>
                                        </div>
                                        <div class="font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                            ⏰ {{ $kd->jam_mulai }} - {{ $kd->jam_selesai }} WIB
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- NOTE: must be raw json_encode (not Js::from), since the JS below reads
                         this tag's textContent and runs it through JSON.parse() itself; Js::from()
                         wraps the value as `JSON.parse('...')`, which is a JS *expression* meant to
                         be executed inline, not JSON text — using it here left this tag's content
                         un-parseable and silently broke the kesediaan-menguji sync. --}}
                    <script type="application/json" id="kesediaan-data">{!! json_encode(($kesediaanDosens ?? collect())->map(fn($kd) => [
                        'dosen_id'   => $kd->dosen_id,
                        'nama_dosen' => $kd->dosen->nama_dosen ?? 'Dosen',
                        'tanggal'    => optional($kd->tanggal)->format('Y-m-d'),
                        'mulai'      => $kd->jam_mulai,
                        'selesai'    => $kd->jam_selesai,
                    ])->values(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

                    <div class="form-section mt-0">
                        <div class="form-section-title">Waktu & Tempat Sempro</div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal *</label>
                                <input type="date" name="tanggal" id="jadwalkan-tanggal" class="form-control text-xs p-2.5" required onchange="refreshKesediaanInfo()">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai *</label>
                                <select name="jam_mulai" id="jadwalkan-jam-mulai" class="form-control text-xs p-2.5 bg-white cursor-pointer" required>
                                    @foreach(['07.00', '07.30', '08.00', '08.30', '09.00', '09.30', '10.00', '10.30', '11.00', '11.30', '12.00', '12.30', '13.00', '13.30', '14.00', '14.30', '15.00', '15.30', '16.00', '16.30', '17.00', '17.30', '18.00'] as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai *</label>
                                <select name="jam_selesai" id="jadwalkan-jam-selesai" class="form-control text-xs p-2.5 bg-white cursor-pointer" required>
                                    @foreach(['07.00', '07.30', '08.00', '08.30', '09.00', '09.30', '10.00', '10.30', '11.00', '11.30', '12.00', '12.30', '13.00', '13.30', '14.00', '14.30', '15.00', '15.30', '16.00', '16.30', '17.00', '17.30', '18.00'] as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Keterangan (Opsional)</label>
                            <input type="text" name="keterangan" id="jadwalkan-keterangan" placeholder="Catatan tambahan..." class="form-control text-xs p-2.5">
                        </div>
                        <div class="form-group mt-3">
                            <label>Ruangan Sidang <span style="color:red">*</span></label>
                            <select name="ruang_id" id="jadwalkan-ruang" class="form-control" required>
                                <option value="">-- Pilih Ruangan --</option>
                                @foreach ($ruangs as $r)
                                    <option value="{{ $r->id }}">{{ $r->kode_ruangan }} ({{ $r->nama_ruangan }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-jadwalkan')">Batal</button>
                    <button type="submit" class="btn btn-primary">💾 Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: PLOT MANUAL MASSAL (bulk-schedule beberapa mahasiswa) --}}
    <div id="modal-bulk-jadwalkan" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-bulk-jadwalkan')">
        <div class="modal-box modal-sm">
            <div class="modal-header">
                <span class="modal-title">🗓️ Plot Manual Massal</span>
                <button class="modal-close" onclick="closeModal('modal-bulk-jadwalkan')">✕</button>
            </div>
            <div class="modal-body">
                <div id="form-bulk-jadwalkan-alert" class="modal-alert" style="display:none;">
                    <span>⚠️</span>
                    <span id="form-bulk-jadwalkan-alert-text"></span>
                </div>
                <p class="text-xs text-slate-500 mb-4"><strong x-text="selectedIds.length"></strong> mahasiswa terpilih akan dijadwalkan berurutan pada hari &amp; ruangan yang sama — sistem otomatis memberi slot jam berbeda per mahasiswa (mulai dari jam yang dipilih, berurutan sesuai durasi per sesi) supaya tidak bentrok ruangan.</p>

                <div class="form-section mt-0">
                    <div class="form-section-title">Waktu &amp; Tempat Sempro</div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal *</label>
                            <input type="date" id="bulk-tanggal" class="form-control text-xs p-2.5" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai (Sesi 1) *</label>
                            <select id="bulk-jam-mulai" class="form-control text-xs p-2.5 bg-white cursor-pointer" required>
                                @foreach(['07.00', '07.30', '08.00', '08.30', '09.00', '09.30', '10.00', '10.30', '11.00', '11.30', '12.00', '12.30', '13.00', '13.30', '14.00', '14.30', '15.00', '15.30', '16.00', '16.30', '17.00'] as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Durasi per Sesi *</label>
                            <select id="bulk-durasi" class="form-control text-xs p-2.5 bg-white cursor-pointer" required>
                                <option value="30">30 menit</option>
                                <option value="60" selected>60 menit</option>
                                <option value="90">90 menit</option>
                                <option value="120">120 menit</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label>Ruangan Sidang <span style="color:red">*</span></label>
                        <select id="bulk-ruang" class="form-control" required>
                            <option value="">-- Pilih Ruangan --</option>
                            @foreach ($ruangs as $r)
                                <option value="{{ $r->id }}">{{ $r->kode_ruangan }} ({{ $r->nama_ruangan }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="bulk-jadwalkan-result" class="mt-2 text-xs"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-bulk-jadwalkan')">Batal</button>
                <button type="button" class="btn btn-primary" @click="submitBulkJadwalkan()">💾 Terapkan ke Semua Terpilih</button>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-edit')">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title">✏️ Edit Data Sempro</span>
                <button class="modal-close" onclick="closeModal('modal-edit')">✕</button>
            </div>
            <form method="POST" id="form-edit" action="">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div id="form-edit-alert" class="modal-alert" style="display:none;">
                        <span>⚠️</span>
                        <span id="form-edit-alert-text"></span>
                    </div>
                    <div class="form-section">
                        <div class="form-section-title">Identitas Mahasiswa</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>NIM <span style="color:red">*</span></label>
                                <input type="text" name="nim" id="edit-nim" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Periode Akademik <span style="color:red">*</span></label>
                                <select name="periode_id" id="edit-periode" class="form-control" required>
                                    @foreach ($periodes as $p)
                                        <option value="{{ $p->id }}">{{ $p->nama_periode }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="jenis_tugas_akhir" value="sempro">
                        <div class="form-group mt-3">
                            <label>Nama Mahasiswa <span style="color:red">*</span></label>
                            <input type="text" name="nama_mahasiswa" id="edit-nama" class="form-control" required>
                        </div>
                        <div class="form-group mt-3">
                            <label>Judul Proposal <span style="color:red">*</span></label>
                            <textarea name="judul_skripsi" id="edit-judul" class="form-control" required></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Tim Dosen</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Dosbing Utama <span style="color:red">*</span></label>
                                <select name="dosen_pembimbing_utama_id" id="edit-dosbing-utama" class="form-control" required>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Dosbing Pendamping</label>
                                <select name="dosen_pembimbing_pendamping_id" id="edit-dosbing-pendamping" class="form-control">
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Jadwal & Ruangan</div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal *</label>
                                <input type="date" name="tanggal" id="edit-tanggal" class="form-control text-xs p-2.5">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai *</label>
                                <select name="jam_mulai" id="edit-jam-mulai" class="form-control text-xs p-2.5 bg-white cursor-pointer">
                                    @foreach(['07.00', '07.30', '08.00', '08.30', '09.00', '09.30', '10.00', '10.30', '11.00', '11.30', '12.00', '12.30', '13.00', '13.30', '14.00', '14.30', '15.00', '15.30', '16.00', '16.30', '17.00', '17.30', '18.00'] as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai *</label>
                                <select name="jam_selesai" id="edit-jam-selesai" class="form-control text-xs p-2.5 bg-white cursor-pointer">
                                    @foreach(['07.00', '07.30', '08.00', '08.30', '09.00', '09.30', '10.00', '10.30', '11.00', '11.30', '12.00', '12.30', '13.00', '13.30', '14.00', '14.30', '15.00', '15.30', '16.00', '16.30', '17.00', '17.30', '18.00'] as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Keterangan (Opsional)</label>
                            <input type="text" name="keterangan" id="edit-keterangan" placeholder="Catatan tambahan..." class="form-control text-xs p-2.5">
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Ruangan</label>
                                <select name="ruang_id" id="edit-ruangan" class="form-control">
                                    <option value="">-- Pilih Ruangan --</option>
                                    @foreach ($ruangs as $r)
                                        <option value="{{ $r->id }}">{{ $r->kode_ruangan }} ({{ $r->nama_ruangan }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Pendaftaran</label>
                                <input type="date" name="tanggal_pendaftaran" id="edit-tanggal-pendaftaran" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-edit')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL HAPUS --}}
    <div id="modal-hapus" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-hapus')">
        <div class="modal-box modal-sm text-center">
            <div class="modal-body pt-6">
                <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3 text-xl">⚠️</div>
                <h3 class="text-base font-bold text-slate-800">Hapus Data Sempro?</h3>
                <p class="text-xs text-slate-500 mt-1">Anda yakin ingin menghapus data sempro <strong id="hapus-nama"></strong>?</p>
            </div>
            <form method="POST" id="form-hapus">
                @csrf @method('DELETE')
                <div class="modal-footer justify-center bg-slate-50">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-hapus')">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let calendar = null;

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            document.body.style.overflow = 'hidden';
            const alertBox = document.getElementById(id.replace('modal-', 'form-') + '-alert');
            if (alertBox) alertBox.style.display = 'none';
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = '';
        }
        function closeOnOverlay(e, id) {
            if (e.target === document.getElementById(id)) closeModal(id);
        }

        function showModalAlert(alertId, textId, message) {
            const box = document.getElementById(alertId);
            const text = document.getElementById(textId);
            if (box && text) {
                text.textContent = message;
                box.style.display = 'flex';
                box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        function hideModalAlert(alertId) {
            const box = document.getElementById(alertId);
            if (box) box.style.display = 'none';
        }

        function parseJamRange(jamStr) {
            if (!jamStr) return { mulai: '08.00', selesai: '12.00' };
            const parts = jamStr.split('-');
            if (parts.length === 2) {
                let m = parts[0].trim().replace(':', '.');
                let s = parts[1].trim().replace(':', '.');
                if (m.indexOf('.') === 1) m = '0' + m;
                if (s.indexOf('.') === 1) s = '0' + s;
                return { mulai: m, selesai: s };
            }
            return { mulai: '08.00', selesai: '12.00' };
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        const INDO_DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const INDO_MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        function formatTanggalIndo(ymd) {
            const parts = String(ymd || '').split('-');
            if (parts.length !== 3) return ymd || '';
            const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
            if (isNaN(d.getTime())) return ymd;
            return `${INDO_DAYS[d.getDay()]}, ${d.getDate()} ${INDO_MONTHS[d.getMonth()]} ${d.getFullYear()}`;
        }

        // Shows the "Dosen Siap Menguji" info box. With no tanggal picked yet it
        // shows every kesediaan slot (all dates); once a tanggal is picked it
        // narrows down to just that date's dosen.
        function refreshKesediaanInfo() {
            const tanggal = document.getElementById('jadwalkan-tanggal').value;
            const list = document.getElementById('kesediaan-info-list');
            const countBadge = document.getElementById('kesediaan-info-count');
            const dataEl = document.getElementById('kesediaan-data');
            if (!list || !dataEl) return;

            let kesediaan = [];
            try { kesediaan = JSON.parse(dataEl.textContent || '[]'); } catch (e) { kesediaan = []; }

            if (!tanggal) {
                if (countBadge) countBadge.textContent = `${kesediaan.length} Slot Terdaftar`;
                if (kesediaan.length === 0) {
                    list.innerHTML = '<div class="text-slate-500 italic px-1 py-2">Belum ada dosen yang mengisi data kesediaan menguji.</div>';
                    return;
                }
                list.innerHTML = kesediaan.map(kd => `
                    <div class="flex items-center justify-between bg-white border border-emerald-100 rounded-xl p-2 text-[11px]">
                        <div>
                            <strong class="text-slate-800">${escapeHtml(kd.nama_dosen)}</strong>
                            <span class="text-indigo-700 font-bold ml-1">(${formatTanggalIndo(kd.tanggal)})</span>
                        </div>
                        <div class="font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                            ⏰ ${escapeHtml(kd.mulai)} - ${escapeHtml(kd.selesai)} WIB
                        </div>
                    </div>`).join('');
                return;
            }

            const entries = kesediaan.filter(k => k.tanggal === tanggal);
            if (countBadge) countBadge.textContent = `${entries.length} Dosen Siap`;

            if (entries.length === 0) {
                list.innerHTML = '<div class="text-slate-500 italic px-1 py-2">Belum ada dosen yang mengisi kesediaan pada tanggal ini.</div>';
                return;
            }

            list.innerHTML = entries.map(kd => `
                <div class="flex items-center justify-between bg-white border border-emerald-100 rounded-xl p-2 text-[11px]">
                    <div><strong class="text-slate-800">${escapeHtml(kd.nama_dosen)}</strong></div>
                    <div class="font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                        ⏰ ${escapeHtml(kd.mulai)} - ${escapeHtml(kd.selesai)} WIB
                    </div>
                </div>`).join('');
        }

        function openJadwalkanSempro(hashId, nama, nim, tgl, jam, ruangId) {
            const form = document.getElementById('form-jadwalkan');
            form.action = '/jadwal/sempro/' + hashId + '/jadwalkan';
            document.getElementById('jadwalkan-mhs-nama').textContent = nama;
            document.getElementById('jadwalkan-mhs-nim').textContent = 'NIM: ' + nim;
            document.getElementById('jadwalkan-tanggal').value = tgl || '';
            const parsed = parseJamRange(jam);
            if (document.getElementById('jadwalkan-jam-mulai')) document.getElementById('jadwalkan-jam-mulai').value = parsed.mulai;
            if (document.getElementById('jadwalkan-jam-selesai')) document.getElementById('jadwalkan-jam-selesai').value = parsed.selesai;
            document.getElementById('jadwalkan-ruang').value = ruangId || '';
            refreshKesediaanInfo();
            openModal('modal-jadwalkan');
        }

        function openEdit(hashId, data) {
            document.getElementById('form-edit').action = '/master/sempro/' + hashId;
            document.getElementById('edit-nim').value                             = data.nim || '';
            document.getElementById('edit-nama').value                            = data.nama_mahasiswa || '';
            document.getElementById('edit-judul').value                           = data.judul_skripsi || '';
            document.getElementById('edit-dosbing-utama').value                   = data.dosen_pembimbing_utama_id || '';
            document.getElementById('edit-dosbing-pendamping').value              = data.dosen_pembimbing_pendamping_id || '';
            document.getElementById('edit-ruangan').value                         = data.ruang_id || '';
            document.getElementById('edit-periode').value                         = data.periode_id || '';
            document.getElementById('edit-tanggal').value                         = data.tanggal || '';
            document.getElementById('edit-tanggal-pendaftaran').value             = data.tanggal_pendaftaran || '';
            const parsed = parseJamRange(data.jam);
            if (document.getElementById('edit-jam-mulai')) document.getElementById('edit-jam-mulai').value = parsed.mulai;
            if (document.getElementById('edit-jam-selesai')) document.getElementById('edit-jam-selesai').value = parsed.selesai;
            openModal('modal-edit');
        }

        function openDelete(hashId, nama) {
            document.getElementById('hapus-nama').textContent = nama;
            document.getElementById('form-hapus').action = '/master/sempro/' + hashId;
            openModal('modal-hapus');
        }

        function initCalendar() {
            if (calendar) {
                calendar.render();
                return;
            }
            const calendarEl = document.getElementById('calendar-view');
            if (!calendarEl) return;
            const eventsData = JSON.parse(calendarEl.getAttribute('data-events') || '[]');
            const firstDate = (eventsData.length > 0 && eventsData[0].start) ? eventsData[0].start.split('T')[0] : null;
            const isMobileScreen = window.innerWidth < 640;

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: isMobileScreen ? 'listWeek' : 'dayGridMonth',
                initialDate: firstDate || undefined,
                locale: 'id',
                headerToolbar: isMobileScreen
                    ? { left: 'prev,next', center: 'title', right: 'today' }
                    : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
                footerToolbar: isMobileScreen
                    ? { right: 'dayGridMonth,listWeek,timeGridDay' }
                    : false,
                slotMinTime: '07:00:00',
                slotMaxTime: '17:00:00',
                slotDuration: '00:30:00',
                slotLabelInterval: '00:30:00',
                slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                allDaySlot: false,
                editable: true,
                eventStartEditable: true,
                eventDurationEditable: false,
                events: eventsData,
                eventContent: function(arg) {
                    const props = arg.event.extendedProps || {};
                    const jam = props.jam && props.jam !== '-' ? props.jam : '';
                    const ruang = props.ruang && props.ruang !== 'TBA' ? props.ruang : '';
                    const meta = [jam, ruang].filter(Boolean).join(' · ');
                    const wrap = document.createElement('div');
                    wrap.style.cssText = 'overflow:hidden; line-height:1.25; padding:1px 3px; width:100%;';
                    if (meta) {
                        const metaLine = document.createElement('div');
                        metaLine.style.cssText = 'font-size:9px; font-weight:800; opacity:.9; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;';
                        metaLine.textContent = meta;
                        wrap.appendChild(metaLine);
                    }
                    const titleLine = document.createElement('div');
                    titleLine.style.cssText = 'font-size:10px; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;';
                    titleLine.textContent = arg.event.title;
                    wrap.appendChild(titleLine);
                    return { domNodes: [wrap] };
                },
                eventDrop: function(info) {
                    const start = info.event.start;
                    const y = start.getFullYear();
                    const mo = String(start.getMonth() + 1).padStart(2, '0');
                    const d = String(start.getDate()).padStart(2, '0');
                    const tanggal = `${y}-${mo}-${d}`;

                    let jam = info.event.extendedProps.jam;
                    if (info.event.end) {
                        const sh = String(start.getHours()).padStart(2, '0');
                        const sm = String(start.getMinutes()).padStart(2, '0');
                        const end = info.event.end;
                        const eh = String(end.getHours()).padStart(2, '0');
                        const em = String(end.getMinutes()).padStart(2, '0');
                        jam = `${sh}.${sm} - ${eh}.${em}`;
                    }

                    fetch(`/jadwal/sempro/${info.event.extendedProps.hash_id}/reschedule`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ tanggal, jam })
                    })
                    .then(res => res.json().then(data => ({ ok: res.ok, data })))
                    .then(({ ok, data }) => {
                        if (!ok) {
                            info.revert();
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message || 'Gagal memindahkan jadwal.', type: 'error' } }));
                        } else {
                            info.event.setExtendedProp('jam', jam);
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message, type: 'success' } }));
                        }
                    })
                    .catch(() => {
                        info.revert();
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Gagal terhubung ke server.', type: 'error' } }));
                    });
                },
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    openJadwalkanSempro(
                        props.hash_id,
                        props.mahasiswa,
                        props.nim,
                        info.event.startStr ? info.event.startStr.split('T')[0] : '',
                        props.jam,
                        props.ruang_id
                    );
                },
            });
            calendar.render();
        }

        // ── Shared AJAX helper ───────────────────────────────────────────────
        async function submitAjaxForm(formId, modalId, onSuccess) {
            const form = document.getElementById(formId);
            if (!form) return;
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const submitBtn = this.querySelector('button[type="submit"]');
                const origText  = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '⏳ Menyimpan…'; }
                this.querySelectorAll('.error-feedback').forEach(el => el.remove());
                const alertBox = document.getElementById(modalId.replace('modal-', 'form-') + '-alert');
                const alertText = document.getElementById(modalId.replace('modal-', 'form-') + '-alert-text');
                if (alertBox) alertBox.style.display = 'none';
                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: new FormData(this)
                    });
                    const result = await response.json();
                    if (response.ok) {
                        closeModal(modalId);
                        if (formId === 'form-tambah') this.reset();
                        // Show brief success toast then reload
                        const toastDiv = document.createElement('div');
                        toastDiv.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;background:#10b981;color:#fff;padding:.75rem 1.25rem;border-radius:.75rem;font-size:.875rem;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,.15);';
                        toastDiv.textContent = result.message || '✅ Berhasil!';
                        document.body.appendChild(toastDiv);
                        setTimeout(() => { toastDiv.remove(); location.reload(); }, 1000);
                        if (typeof onSuccess === 'function') onSuccess();
                    } else {
                        if (response.status === 422 && result.errors) {
                            Object.keys(result.errors).forEach(key => {
                                const input = form.querySelector(`[name="${key}"]`);
                                if (input) {
                                    const errEl = document.createElement('p');
                                    errEl.className = 'error-feedback text-xs text-rose-600 mt-1 font-semibold';
                                    errEl.textContent = result.errors[key][0];
                                    input.parentNode.appendChild(errEl);
                                }
                            });
                        } else if (alertBox && alertText) {
                            alertText.textContent = result.message || 'Terjadi kesalahan.';
                            alertBox.style.display = 'flex';
                            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        } else {
                            const toastDiv = document.createElement('div');
                            toastDiv.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;background:#ef4444;color:#fff;padding:.75rem 1.25rem;border-radius:.75rem;font-size:.875rem;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,.15);';
                            toastDiv.textContent = result.message || '❌ Terjadi kesalahan.';
                            document.body.appendChild(toastDiv);
                            setTimeout(() => toastDiv.remove(), 4000);
                        }
                    }
                } catch (err) {
                    console.error(err);
                    if (alertBox && alertText) {
                        alertText.textContent = 'Gagal terhubung ke server. Silakan coba lagi.';
                        alertBox.style.display = 'flex';
                    } else {
                        alert('Gagal terhubung ke server. Silakan coba lagi.');
                    }
                } finally {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
                }
            });
        }

        submitAjaxForm('form-tambah',    'modal-tambah');
        submitAjaxForm('form-edit',      'modal-edit');
        submitAjaxForm('form-hapus',     'modal-hapus');
        submitAjaxForm('form-jadwalkan', 'modal-jadwalkan');

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ['modal-jadwalkan','modal-edit','modal-hapus','modal-tambah'].forEach(closeModal);
            }
        });
    </script>
</x-app-layout>

<x-app-layout title="Jadwal Sidang">
    <x-slot:header>Jadwal Sidang</x-slot:header>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  STYLES                                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <style>
        .sidang-page { font-family: 'Inter', 'Segoe UI', sans-serif; }

        /* ── Toggle Views ── */
        .view-toggle {
            display: inline-flex;
            background: #e2e8f0;
            border-radius: 12px;
            padding: .25rem;
            gap: .25rem;
        }
        .view-toggle-btn {
            padding: .5rem 1rem;
            border-radius: 10px;
            font-size: .85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            border: none;
            background: transparent;
            color: #475569;
        }
        .view-toggle-btn.active {
            background: #fff;
            color: #3251d4;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }

        /* ── Stats cards ── */
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
        .stat-icon.blue  { background: #eff6ff; }
        .stat-icon.green { background: #f0fdf4; }
        .stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1; }
        .stat-label { font-size: .8rem; font-weight: 600; margin-top: .2rem; }

        /* ── Toolbar ── */
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
            outline: none; border-color: #4361ee; box-shadow: 0 0 0 3px rgba(99,102,241,.12);
        }
        .filter-select {
            padding: .55rem .85rem; border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: .875rem; color: #1e293b; background: #fff; cursor: pointer;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .55rem 1rem; border-radius: 10px; font-size: .875rem;
            font-weight: 600; cursor: pointer; transition: all .15s; border: none;
        }
        .btn-primary { background: #4361ee; color: #fff; }
        .btn-primary:hover { background: #3251d4; box-shadow: 0 4px 12px rgba(99,102,241,.35); }
        .btn-success { background: #10b981; color: #fff; }
        .btn-success:hover { background: #059669; box-shadow: 0 4px 12px rgba(16,185,129,.35); }
        .btn-outline { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: .3rem .65rem; font-size: .78rem; border-radius: 7px; }

        /* ── Table design ── */
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
        table.sidang-table tbody tr.conflict-schedule { background-color: #fee2e2 !important; }
        table.sidang-table tbody tr.conflict-schedule:hover { background-color: #fecaca !important; }
        table.sidang-table tbody tr.conflict-rule { background-color: #ffedd5 !important; }
        table.sidang-table tbody tr.conflict-rule:hover { background-color: #fed7aa !important; }
        table.sidang-table td { padding: .7rem .85rem; vertical-align: top; color: #334155; }

        /* Dark Mode Overrides for Penjadwalan Skripsi */
        html.dark .sidang-page h1 { color: #f8fafc !important; }
        html.dark .sidang-page p { color: #cbd5e1 !important; }
        html.dark .view-toggle { background: #334155 !important; }
        html.dark .view-toggle-btn { color: #94a3b8 !important; }
        html.dark .view-toggle-btn.active { background: #1e293b !important; color: #818cf8 !important; }
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
        html.dark table.sidang-table tbody tr.conflict-schedule:hover {
            background-color: rgba(159, 18, 57, 0.45) !important;
        }
        html.dark table.sidang-table tbody tr.conflict-rule {
            background-color: rgba(180, 83, 9, 0.3) !important;
        }
        html.dark table.sidang-table tbody tr.conflict-rule:hover {
            background-color: rgba(180, 83, 9, 0.45) !important;
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
            background-color: rgba(192, 38, 211, 0.2) !important;
            border-color: rgba(192, 38, 211, 0.4) !important;
            color: #f0abfc !important;
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
        html.dark .dosen-chip.utama { background-color: rgba(29, 78, 216, 0.25) !important; border-color: rgba(29, 78, 216, 0.5) !important; color: #93c5fd !important; }
        html.dark .dosen-chip.ketua { background-color: rgba(180, 83, 9, 0.25) !important; border-color: rgba(180, 83, 9, 0.5) !important; color: #fde68a !important; }

        /* Badges */
        .badge { display: inline-flex; align-items: center; padding: .22rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 700; }
        .badge-sidang { background: #dbeafe; color: #1d4ed8; }
        .badge-jurnal  { background: #dcfce7; color: #15803d; }
        .nim-pill { font-family: 'Courier New', monospace; background: #f1f5f9; color: #475569; padding: .15rem .5rem; border-radius: 6px; font-size: .78rem; font-weight: 600; }
        .judul-text { font-size: .8rem; color: #1e293b; font-weight: 500; line-height: 1.4; max-width: 300px; }

        .dosen-chip {
            display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 7px; padding: .1rem .45rem; font-size: .75rem; color: #475569; margin: .1rem 0;
        }
        .dosen-chip.utama { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; font-weight: 600; }
        .dosen-chip.ketua { background: #fef3c7; border-color: #fde68a; color: #92400e; font-weight: 600; }

        .schedule-cell { min-width: 130px; }
        .schedule-hari { font-weight: 700; color: #1e293b; font-size: .78rem; }
        .schedule-jam  { color: #4361ee; font-size: .75rem; font-weight: 600; margin-top: .1rem; }
        .schedule-ruang {
            display: inline-flex; align-items: center; gap: .3rem;
            background: #fdf4ff; border: 1px solid #e9d5ff; color: #7e22ce;
            font-size: .72rem; font-weight: 700; padding: .15rem .5rem; border-radius: 6px; margin-top: .25rem;
        }

        /* ── Modals ── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.45); backdrop-filter: blur(2px);
            display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;
        }
        .modal-box {
            background: #fff; border-radius: 20px; width: 100%; max-width: 680px; max-height: 90vh;
            overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.2);
        }
        .modal-box.modal-sm { max-width: 440px; }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
        .modal-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
        .modal-close {
            width: 32px; height: 32px; border: none; background: #f1f5f9; border-radius: 8px;
            cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b;
        }
        .modal-body { padding: 1.25rem 1.5rem; }
        .modal-footer { display: flex; gap: .75rem; justify-content: flex-end; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; }

        /* Form fields */
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }
        .form-group { display: flex; flex-direction: column; gap: .35rem; }
        .form-group label { font-size: .78rem; font-weight: 600; color: #374151; }
        .form-control {
            padding: .55rem .75rem; border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: .875rem; color: #1e293b; background: #fff; width: 100%;
        }
        .form-control:focus { outline: none; border-color: #4361ee; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
        textarea.form-control { resize: vertical; min-height: 70px; }
        .form-section { margin-top: 1.1rem; }
        .form-section-title {
            font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
            color: #94a3b8; margin-bottom: .6rem; padding-bottom: .35rem; border-bottom: 1px solid #f1f5f9;
        }

        .alert { border-radius: 12px; padding: .85rem 1.1rem; display: flex; gap: .75rem; align-items: flex-start; font-size: .875rem; margin-bottom: 1rem; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d !important; }
        .alert-error   { background: #fef2f2 !important; border: 1px solid #fecaca !important; color: #dc2626 !important; }
        .alert-error strong, .alert-error em, .alert-error li { color: #dc2626 !important; }
        .alert-error svg { stroke: #dc2626 !important; }
        html.dark .alert-error { background: rgba(220,38,38,0.12) !important; border: 1px solid rgba(220,38,38,0.3) !important; color: #fca5a5 !important; }
        html.dark .alert-error strong, html.dark .alert-error em, html.dark .alert-error li { color: #fca5a5 !important; }
        html.dark .alert-error svg { stroke: #fca5a5 !important; }
        html.dark .alert-error p { color: #fca5a5 !important; opacity: 0.85; }

        /* ── Calendar container ── */
        .calendar-container {
            background: #fff; border-radius: 20px; padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #f1f5f9;
        }
        
        /* Customizing FullCalendar */
        .fc { font-family: 'Inter', sans-serif !important; }
        .fc-col-header-cell-cushion { font-size: .8rem; font-weight: 700; color: #475569; text-transform: uppercase; }
        .fc-event { border-radius: 6px !important; padding: .1rem .3rem !important; cursor: pointer !important; font-size: .75rem !important; font-weight: 600 !important; }
        .fc-header-toolbar { margin-bottom: 1.25rem !important; }
        .fc-button-primary {
            background-color: #4361ee !important; border-color: #4361ee !important; border-radius: 8px !important;
            font-size: .85rem !important; font-weight: 600 !important;
        }
        .fc-button-primary:hover { background-color: #3251d4 !important; border-color: #3251d4 !important; }
        .fc-button-active { background-color: #2a43b0 !important; border-color: #2a43b0 !important; }
        .fc-daygrid-day-number { font-size: .82rem !important; font-weight: 700 !important; color: #334155 !important; }

        @media (max-width: 640px) { .form-grid-2 { grid-template-columns: 1fr; } }
    </style>

    {{-- FullCalendar v6 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <div class="sidang-page" x-data="{ 
        currentView: 'table',
        async navigate(url) {
            window.history.pushState(null, '', url);
            await refreshComponent(['#table-container', '#filter-container', '#stats-container', '#calendar-container', '#alerts-container']);
            if (this.currentView === 'calendar') {
                initCalendar();
            }
        },
        submitSearch(form) {
            const url = new URL(form.action || window.location.href);
            const formData = new FormData(form);
            url.searchParams.delete('search');
            url.searchParams.delete('periode_id');
            url.searchParams.delete('jenis');
            url.searchParams.delete('tanggal');
            url.searchParams.delete('page');
            for (const [key, value] of formData.entries()) {
                if (value) {
                    url.searchParams.set(key, value);
                }
            }
            this.navigate(url.toString());
        }
    }">

        <div id="alerts-container">
            @php
                $scheduleConflictCount = collect($conflictMap ?? [])->filter(fn($v) => !empty($v['schedule']))->count();
                $ruleViolationCount    = collect($conflictMap ?? [])->filter(fn($v) => !empty($v['rules']))->count();
            @endphp
            @if($scheduleConflictCount > 0 || $ruleViolationCount > 0)
                <div class="alert alert-error">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <div>
                        <strong>Perhatian: Terdeteksi Masalah Jadwal Skripsi</strong>
                        <ul class="text-xs mt-1 list-disc list-inside space-y-0.5">
                            @if($scheduleConflictCount > 0)
                                <li>⚠️ <strong>{{ $scheduleConflictCount }}</strong> skripsi memiliki <em>bentrok jadwal</em> (ruangan atau penguji bersamaan). Ditandai badge merah di tabel.</li>
                            @endif
                            @if($ruleViolationCount > 0)
                                <li>❌ <strong>{{ $ruleViolationCount }}</strong> skripsi memiliki <em>pelanggaran aturan</em> (Pembimbing Utama bukan Penguji 2, atau Pembimbing Pendamping menguji). Ditandai badge oranye di tabel.</li>
                            @endif
                        </ul>
                        <p class="text-xs mt-1.5 opacity-75">Klik ✏️ pada baris tersebut untuk melihat detail dan memperbaiki.</p>
                        <div class="mt-2">
                            <a href="{{ route('jadwal-ujian.export-bentrok') }}" class="btn btn-danger btn-sm">
                                📥 Download Data Jadwal Bentrok (Excel)
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>


        {{-- Page header --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Jadwal Sidang Skripsi</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Plotting, kelola jadwal, dan visualisasikan sidang skripsi mahasiswa.</p>
            </div>
            
            <div class="flex items-center gap-3 flex-wrap">
                @if(($scheduleConflictCount ?? 0) > 0 || ($ruleViolationCount ?? 0) > 0)
                    <a href="{{ route('jadwal-ujian.export-bentrok') }}" class="btn btn-danger btn-sm">
                        ⚠️ Export Jadwal Bentrok
                    </a>
                @endif
                {{-- View Toggle --}}
                <div class="view-toggle">
                    <button @click="currentView = 'table'" :class="currentView === 'table' ? 'active' : ''" class="view-toggle-btn">
                        📋 Tabel
                    </button>
                    <button @click="currentView = 'calendar'; $nextTick(() => initCalendar())" :class="currentView === 'calendar' ? 'active' : ''" class="view-toggle-btn">
                        📅 Kalender
                    </button>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div id="stats-container" class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div>
                    <div class="stat-value">{{ $totalSkripsi + $totalJurnal }}</div>
                    <div class="stat-label">Total Mahasiswa</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">🎓</div>
                <div>
                    <div class="stat-value">{{ $totalSkripsi }}</div>
                    <div class="stat-label">Skripsi</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📰</div>
                <div>
                    <div class="stat-value">{{ $totalJurnal }}</div>
                    <div class="stat-label">Jurnal / Artikel</div>
                </div>
            </div>
        </div>

        {{-- ── TAMPILAN TABEL ── --}}
        <div x-show="currentView === 'table'" class="space-y-6">
            {{-- Toolbar / Filters --}}
            <form method="GET" action="{{ route('jadwal-ujian.index') }}" id="filter-container" @submit.prevent="submitSearch($event.currentTarget)" class="toolbar">
                <div class="toolbar-search">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" @input.debounce.500ms="submitSearch($event.target.form)" placeholder="Cari NIM, nama, judul…">
                </div>
                
                <select name="per_page" class="filter-select" @change="submitSearch($event.target.form)">
                    <option value="5" {{ request('per_page', 5) == 5 ? 'selected' : '' }}>Tampilkan 5 data</option>
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>Tampilkan 10 data</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>Tampilkan 25 data</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>Tampilkan 100 data</option>
                </select>

                <select name="status" class="filter-select" @change="submitSearch($event.target.form)">
                    <option value="">Semua Status</option>
                    <option value="belum" {{ request('status')=='belum'?'selected':'' }}>Belum Dijadwalkan</option>
                    <option value="sudah" {{ request('status')=='sudah'?'selected':'' }}>Sudah Dijadwal</option>
                </select>

                <select name="jenis" class="filter-select" @change="submitSearch($event.target.form)">
                    <option value="">Semua Jenis</option>
                    <option value="skripsi" {{ request('jenis')=='skripsi'?'selected':'' }}>Skripsi</option>
                    <option value="jurnal" {{ request('jenis')=='jurnal'?'selected':'' }}>Jurnal</option>
                </select>

                <select name="periode_id" class="filter-select" @change="submitSearch($event.target.form)">
                    @foreach ($periodes as $p)
                        <option value="{{ $p->id }}" {{ (request('periode_id', $activePeriode->id ?? null) == $p->id) ? 'selected' : '' }}>
                            {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>

                <select name="tanggal" class="filter-select" @change="submitSearch($event.target.form)">
                    <option value="">Semua Tanggal</option>
                    @foreach ($daftarTanggal as $t)
                        <option value="{{ $t->format('Y-m-d') }}" {{ request('tanggal')==$t->format('Y-m-d')?'selected':'' }}>
                            {{ $t->translatedFormat('d M Y') }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search','jenis','status','tanggal','periode_id']))
                    <a href="{{ route('jadwal-ujian.index') }}" @click.prevent="navigate($event.currentTarget.href)" class="btn btn-outline">✕ Reset</a>
                @endif
            </form>

            {{-- Table --}}
            <div id="table-container" @click="if ($event.target.closest('a')) { const link = $event.target.closest('a'); if (link.href && !link.hasAttribute('download') && !link.getAttribute('href').startsWith('#') && link.target !== '_blank') { $event.preventDefault(); navigate(link.href); } }" class="table-card">
                <div class="table-scroll">
                    <table class="sidang-table">
                        <thead>
                            <tr>
                                <th style="width:42px; text-align:center;">No</th>
                                <th style="width:90px;">Tgl Daftar</th>
                                <th>NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Status</th>
                                <th>Periode</th>
                                <th>Dosbing Utama</th>
                                <th>Dosbing Pendamping</th>
                                <th>Ketua Penguji</th>
                                <th>Penguji 1</th>
                                <th>Penguji 2</th>
                                <th>Jadwal Sidang</th>
                                <th>Ruangan</th>
                                <th>Jenis</th>
                                <th style="width:130px; text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sidangs as $item)
                                @php
                                    $hasConflict      = isset($conflictMap[$item->id]);
                                    $hasSchedule      = $hasConflict && !empty($conflictMap[$item->id]['schedule']);
                                    $hasRuleViolation = $hasConflict && !empty($conflictMap[$item->id]['rules']);
                                @endphp
                                <tr class="{{ $hasSchedule ? 'conflict-schedule' : ($hasRuleViolation ? 'conflict-rule' : '') }}">
                                    <td style="text-align:center; color:#475569; vertical-align: middle;">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="font-bold text-xs">{{ ($sidangs->currentPage() - 1) * $sidangs->perPage() + $loop->iteration }}</span>
                                            @if($hasSchedule)
                                                <span class="text-red-600 text-xs" title="Bentrok Jadwal">⚠️</span>
                                            @elseif($hasRuleViolation)
                                                <span class="text-orange-500 text-xs" title="Pelanggaran Aturan">❌</span>
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
                                        @if($hasSchedule)
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
                                        @if($hasRuleViolation)
                                            <div class="mt-1">
                                                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800 shadow-2xs">
                                                    ❌ Pelanggaran Aturan
                                                </span>
                                            </div>
                                            <div class="mt-1 space-y-0.5">
                                                @foreach($conflictMap[$item->id]['rules'] as $msg)
                                                    <div class="text-[10.5px] leading-snug text-amber-800 dark:text-amber-300 font-bold pl-1">• {{ $msg }}</div>
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
                                    <td><span class="dosen-chip ketua {{ $hasSchedule && in_array($item->ketua_penguji_id, array_column(array_filter(($conflictMap[$item->id]['schedule'] ?? []), fn($m) => str_contains($m,'Ketua')), 0)) ? 'ring-1 ring-red-400' : '' }}">{{ $item->ketuaPenguji ? $item->ketuaPenguji->nama_dosen : '—' }}</span></td>
                                    <td><span class="dosen-chip">{{ $item->anggotaPenguji1 ? $item->anggotaPenguji1->nama_dosen : '—' }}</span></td>
                                    <td><span class="dosen-chip">{{ $item->anggotaPenguji2 ? $item->anggotaPenguji2->nama_dosen : '—' }}</span></td>
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
                                    <td>
                                        <span class="badge {{ $item->jenis_tugas_akhir === 'skripsi' ? 'badge-sidang' : 'badge-jurnal' }}">
                                            {{ $item->jenis_tugas_akhir === 'skripsi' ? 'Skripsi' : 'Jurnal' }}
                                        </span>
                                    </td>
                                    <td class="text-right space-x-1 whitespace-nowrap">
                                        <button class="btn btn-primary btn-sm"
                                            onclick="openJadwalkan({{ $item->id }}, '{{ addslashes($item->nama_mahasiswa) }}', '{{ $item->nim }}', '{{ $item->tanggal ? $item->tanggal->format('Y-m-d') : '' }}', '{{ $item->jam ?? '' }}', '{{ $item->ruang_id ?? '' }}', '{{ $item->ketua_penguji_id ?? '' }}', '{{ $item->anggota_penguji_1_id ?? '' }}', '{{ $item->anggota_penguji_2_id ?? '' }}')">
                                            📅 {{ empty($item->tanggal) ? 'Jadwalkan' : 'Edit Jadwal' }}
                                        </button>
                                        <button class="btn btn-outline btn-sm"
                                            onclick="openEdit({{ $item->id }}, {{ json_encode([
                                                'id' => $item->id,
                                                'nim' => $item->nim,
                                                'nama_mahasiswa' => $item->nama_mahasiswa,
                                                'judul_skripsi' => $item->judul_skripsi,
                                                'dosen_pembimbing_utama_id' => $item->dosen_pembimbing_utama_id,
                                                'dosen_pembimbing_pendamping_id' => $item->dosen_pembimbing_pendamping_id,
                                                'ketua_penguji_id' => $item->ketua_penguji_id,
                                                'anggota_penguji_1_id' => $item->anggota_penguji_1_id,
                                                'anggota_penguji_2_id' => $item->anggota_penguji_2_id,
                                                'ruang_id' => $item->ruang_id,
                                                'periode_id' => $item->periode_id,
                                                'tanggal' => $item->tanggal ? $item->tanggal->format('Y-m-d') : '',
                                                'tanggal_pendaftaran' => $item->tanggal_pendaftaran ? $item->tanggal_pendaftaran->format('Y-m-d') : '',
                                                'jam' => $item->jam,
                                                'jenis_tugas_akhir' => $item->jenis_tugas_akhir,
                                                'conflict_schedule' => $conflictMap[$item->id]['schedule'] ?? [],
                                                'conflict_rules'    => $conflictMap[$item->id]['rules'] ?? [],
                                            ]) }})">
                                            ✏️
                                        </button>
                                        <button class="btn btn-danger btn-sm"
                                            onclick="openDelete({{ $item->id }}, '{{ addslashes($item->nama_mahasiswa) }}')">
                                            🗑
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="py-12 text-center text-slate-400">
                                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        <h3>Belum ada data skripsi</h3>
                                        <p style="font-size:.85rem;">Import Excel atau tambah data secara manual.</p>
                                        <div class="flex gap-3 justify-center mt-4">
                                            <a href="{{ route('master.skripsi.import.form') }}" class="btn btn-success">Import Excel</a>
                                            <button onclick="openModal('modal-tambah')" class="btn btn-primary">Tambah Manual</button>
                                        </div>
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

        {{-- ── TAMPILAN KALENDER ── --}}
        <div x-show="currentView === 'calendar'" id="calendar-container" class="calendar-container" x-cloak>
            <div id="calendar-view" data-events="{{ json_encode($calendarEvents->values()) }}"></div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: TAMBAH DATA                                              --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="modal-tambah" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-tambah')">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title">➕ Tambah Data Skripsi</span>
                <button class="modal-close" onclick="closeModal('modal-tambah')">✕</button>
            </div>
            <form method="POST" action="{{ route('master.skripsi.store') }}" id="form-tambah">
                @csrf
                <div class="modal-body">
                    {{-- Identitas mahasiswa --}}
                    <div class="form-section">
                        <div class="form-section-title">Identitas Mahasiswa</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>NIM <span style="color:red">*</span></label>
                                <input type="text" name="nim" class="form-control" required placeholder="202251xxx">
                            </div>
                            <div class="form-group">
                                <label>Jenis <span style="color:red">*</span></label>
                                <select name="jenis_tugas_akhir" class="form-control" required>
                                    <option value="skripsi">Skripsi</option>
                                    <option value="jurnal">Jurnal / Artikel</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <label>Nama Mahasiswa <span style="color:red">*</span></label>
                            <input type="text" name="nama_mahasiswa" class="form-control" required placeholder="Nama lengkap mahasiswa">
                        </div>
                        <div class="form-group mt-3">
                            <label>Judul Skripsi <span style="color:red">*</span></label>
                            <textarea name="judul_skripsi" class="form-control" required placeholder="Judul skripsi/tugas akhir"></textarea>
                        </div>
                        <div class="form-group mt-3">
                            <label>Periode Akademik <span style="color:red">*</span></label>
                            <select name="periode_id" class="form-control" required>
                                @foreach ($periodes as $p)
                                    <option value="{{ $p->id }}" {{ $p->aktif ? 'selected' : '' }}>{{ $p->nama_periode }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Dosen --}}
                    <div class="form-section">
                        <div class="form-section-title">Tim Dosen</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Dosbing Utama <span style="color:red">*</span></label>
                                <select name="dosen_pembimbing_utama_id" class="form-control" required>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Dosbing Pendamping</label>
                                <select name="dosen_pembimbing_pendamping_id" class="form-control">
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ketua Penguji <span style="color:red">*</span></label>
                                <select name="ketua_penguji_id" class="form-control" required>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Penguji 1 <span style="color:red">*</span></label>
                                <select name="anggota_penguji_1_id" class="form-control" required>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Penguji 2</label>
                                <select name="anggota_penguji_2_id" class="form-control">
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Jadwal --}}
                    <div class="form-section">
                        <div class="form-section-title">Jadwal & Ruangan</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Tanggal Sidang</label>
                                <input type="date" name="tanggal" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Jam</label>
                                <input type="text" name="jam" class="form-control" placeholder="Contoh: 08.00 - 09.00">
                            </div>
                            <div class="form-group">
                                <label>Ruangan</label>
                                <select name="ruang_id" class="form-control">
                                    <option value="">-- Pilih Ruangan --</option>
                                    @foreach ($ruangs as $r)
                                        <option value="{{ $r->id }}">{{ $r->kode_ruangan }} ({{ $r->nama_ruangan }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Pendaftaran</label>
                                <input type="date" name="tanggal_pendaftaran" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-tambah')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: EDIT DATA                                                --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="modal-edit" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-edit')">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title">✏️ Edit Data Skripsi</span>
                <button class="modal-close" onclick="closeModal('modal-edit')">✕</button>
            </div>
            <form method="POST" id="form-edit" action="">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    {{-- Dynamic Conflict Warning Box: 2 sections --}}
                    <div id="edit-conflict-schedule-box"
                         class="mb-3 rounded-xl border border-red-200 bg-red-50 p-3.5"
                         style="display:none;">
                        <div class="flex items-start gap-2">
                            <span class="text-base">⚠️</span>
                            <div class="flex-1">
                                <strong class="block text-xs font-bold uppercase tracking-wider text-red-700 mb-1.5">Bentrok Jadwal</strong>
                                <ul id="edit-conflict-schedule-list" class="list-disc list-inside space-y-1 text-xs font-medium text-red-800"></ul>
                                <p class="mt-2 text-[11px] italic text-red-600">* Sesuaikan Jam, Ruangan, atau Penguji agar bentrok hilang.</p>
                            </div>
                        </div>
                    </div>
                    <div id="edit-conflict-rules-box"
                         class="mb-3 rounded-xl border border-orange-200 bg-orange-50 p-3.5"
                         style="display:none;">
                        <div class="flex items-start gap-2">
                            <span class="text-base">❌</span>
                            <div class="flex-1">
                                <strong class="block text-xs font-bold uppercase tracking-wider text-orange-700 mb-1.5">Pelanggaran Aturan Skripsi</strong>
                                <ul id="edit-conflict-rules-list" class="list-disc list-inside space-y-1 text-xs font-medium text-orange-800"></ul>
                                <p class="mt-2 text-[11px] italic text-orange-600">* Aturan: (1) Pembimbing Utama wajib menjadi Penguji 2. (2) Pembimbing Pendamping tidak boleh menguji.</p>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <div class="form-section-title">Identitas Mahasiswa</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>NIM <span style="color:red">*</span></label>
                                <input type="text" name="nim" id="edit-nim" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Jenis <span style="color:red">*</span></label>
                                <select name="jenis_tugas_akhir" id="edit-jenis" class="form-control" required>
                                    <option value="skripsi">Skripsi</option>
                                    <option value="jurnal">Jurnal / Artikel</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <label>Nama Mahasiswa <span style="color:red">*</span></label>
                            <input type="text" name="nama_mahasiswa" id="edit-nama" class="form-control" required>
                        </div>
                        <div class="form-group mt-3">
                            <label>Judul Skripsi <span style="color:red">*</span></label>
                            <textarea name="judul_skripsi" id="edit-judul" class="form-control" required></textarea>
                        </div>
                        <div class="form-group mt-3">
                            <label>Periode Akademik <span style="color:red">*</span></label>
                            <select name="periode_id" id="edit-periode" class="form-control" required>
                                @foreach ($periodes as $p)
                                    <option value="{{ $p->id }}">{{ $p->nama_periode }}</option>
                                @endforeach
                            </select>
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
                            <div class="form-group">
                                <label>Ketua Penguji <span style="color:red">*</span></label>
                                <select name="ketua_penguji_id" id="edit-ketua" class="form-control" required>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Penguji 1 <span style="color:red">*</span></label>
                                <select name="anggota_penguji_1_id" id="edit-penguji1" class="form-control" required>
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Penguji 2</label>
                                <select name="anggota_penguji_2_id" id="edit-penguji2" class="form-control">
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

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: HAPUS                                                     --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="modal-hapus" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-hapus')">
        <div class="modal-box modal-sm">
            <div class="modal-header">
                <span class="modal-title text-rose-600 font-bold">🗑 Hapus Data</span>
                <button class="modal-close" onclick="closeModal('modal-hapus')">✕</button>
            </div>
            <div class="modal-body">
                <p>Hapus data skripsi untuk mahasiswa <strong id="hapus-nama">?</strong></p>
                <p class="text-xs text-rose-500 font-semibold mt-2">⚠ Tindakan ini tidak bisa dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <form id="form-hapus" method="POST" action="">
                    @csrf @method('DELETE')
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-hapus')">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: DETAIL JADWAL KALENDER                                     --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="modal-detail" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-detail')">
        <div class="modal-box">
            <div class="modal-header bg-slate-50 border-b border-slate-100">
                <span class="modal-title font-bold">📄 Detail Jadwal Skripsi</span>
                <button class="modal-close" onclick="closeModal('modal-detail')">✕</button>
            </div>
            <div class="modal-body space-y-4">
                <div id="detail-conflict-box" class="rounded-xl border border-red-200 bg-red-50 p-3.5" style="display:none;">
                    <div class="flex items-start gap-2">
                        <span class="text-base">⚠️</span>
                        <div class="flex-1">
                            <strong class="block text-xs font-bold uppercase tracking-wider text-red-700 mb-1">Masalah Jadwal / Pelanggaran Aturan</strong>
                            <div id="detail-conflict-text" class="text-xs font-semibold text-red-800 leading-snug"></div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Jenis Ujian</span>
                    <span class="col-span-2"><span id="detail-jenis" class="badge"></span></span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">NIM / Nama</span>
                    <span class="col-span-2 text-slate-800 font-semibold"><span id="detail-nim" class="nim-pill"></span> <span id="detail-nama" class="ml-1"></span></span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Judul Skripsi</span>
                    <span class="col-span-2 text-slate-700 text-sm leading-relaxed" id="detail-judul"></span>
                </div>
                
                <hr class="border-slate-100">
                
                <div class="grid grid-cols-3 gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pembimbing</span>
                    <div class="col-span-2 space-y-1">
                        <div><span class="text-xs font-semibold text-slate-400">Utama:</span> <span id="detail-dosbing" class="text-slate-800 font-medium"></span></div>
                        <div><span class="text-xs font-semibold text-slate-400">Pendamping:</span> <span id="detail-dosbing-p" class="text-slate-800 font-medium"></span></div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Penguji</span>
                    <div class="col-span-2 space-y-1">
                        <div><span class="text-xs font-semibold text-slate-400">Ketua:</span> <span id="detail-ketua" class="text-slate-800 font-medium"></span></div>
                        <div><span class="text-xs font-semibold text-slate-400">Anggota 1:</span> <span id="detail-penguji1" class="text-slate-800 font-medium"></span></div>
                        <div><span class="text-xs font-semibold text-slate-400">Anggota 2:</span> <span id="detail-penguji2" class="text-slate-800 font-medium"></span></div>
                    </div>
                </div>
                
                <hr class="border-slate-100">

                <div class="grid grid-cols-3 gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Jadwal & Ruang</span>
                    <div class="col-span-2 space-y-1">
                        <div class="text-slate-800 font-semibold" id="detail-jadwal"></div>
                        <div><span class="schedule-ruang" id="detail-ruang"></span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-slate-50 border-t border-slate-100">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-detail')">Tutup</button>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: PLOTTING JADWAL SIDANG                                     --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="modal-jadwalkan" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-jadwalkan')">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title">📅 Plotting Jadwal & Penguji Sidang</span>
                <button class="modal-close" onclick="closeModal('modal-jadwalkan')">✕</button>
            </div>
            <form method="POST" id="form-jadwalkan" action="">
                @csrf
                <div class="modal-body">
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl mb-4 text-xs">
                        <div class="font-bold text-slate-800 text-sm" id="jadwalkan-mhs-nama"></div>
                        <div class="text-slate-500 font-semibold mt-0.5" id="jadwalkan-mhs-nim"></div>
                    </div>

                    {{-- Info Box Kesediaan Menguji Dosen --}}
                    @if(isset($kesediaanDosens) && $kesediaanDosens->count() > 0)
                        <div class="mb-4 bg-emerald-50/80 border border-emerald-200 rounded-2xl p-3.5 text-xs">
                            <div class="flex items-center justify-between font-extrabold text-emerald-900 mb-2">
                                <span class="flex items-center gap-1.5">
                                    <span>📝</span> Data Ketersediaan Menguji Dosen (Sinkron)
                                </span>
                                <span class="bg-emerald-200 text-emerald-900 text-[10px] px-2 py-0.5 rounded-full font-extrabold">
                                    {{ $kesediaanDosens->count() }} Slot Terdaftar
                                </span>
                            </div>
                            <div class="max-h-32 overflow-y-auto space-y-1.5 pr-1">
                                @foreach($kesediaanDosens as $kd)
                                    <div class="flex items-center justify-between bg-white border border-emerald-100 rounded-xl p-2 text-[11px]">
                                        <div>
                                            <strong class="text-slate-800">{{ $kd->dosen->nama_dosen ?? 'Dosen' }}</strong>
                                            <span class="text-purple-700 font-bold ml-1">({{ \Carbon\Carbon::parse($kd->tanggal)->locale('id')->translatedFormat('l, d F Y') }})</span>
                                        </div>
                                        <div class="font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                            ⏰ {{ $kd->jam_mulai }} - {{ $kd->jam_selesai }} WIB
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="form-section mt-0">
                        <div class="form-section-title">Waktu & Tempat Sidang</div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal *</label>
                                <input type="date" name="tanggal" id="jadwalkan-tanggal" class="form-control text-xs p-2.5" required>
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

                    <div class="form-section">
                        <div class="form-section-title">Plotting Tim Penguji</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Ketua Penguji</label>
                                <select name="ketua_penguji_id" id="jadwalkan-ketua" class="form-control">
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Penguji 1</label>
                                <select name="anggota_penguji_1_id" id="jadwalkan-penguji1" class="form-control">
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Penguji 2</label>
                                <select name="anggota_penguji_2_id" id="jadwalkan-penguji2" class="form-control">
                                    <option value="">-- Pilih Dosen --</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
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

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- SCRIPTS                                                          --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <script>
        let calendar = null;

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = '';
        }
        function closeOnOverlay(e, id) {
            if (e.target === document.getElementById(id)) closeModal(id);
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

        function openJadwalkan(id, nama, nim, tgl, jam, ruangId, ketuaId, p1Id, p2Id) {
            const form = document.getElementById('form-jadwalkan');
            form.action = '/jadwal/skripsi/' + id + '/jadwalkan';
            document.getElementById('jadwalkan-mhs-nama').textContent = nama;
            document.getElementById('jadwalkan-mhs-nim').textContent = 'NIM: ' + nim;
            document.getElementById('jadwalkan-tanggal').value = tgl || '';
            const parsed = parseJamRange(jam);
            if (document.getElementById('jadwalkan-jam-mulai')) document.getElementById('jadwalkan-jam-mulai').value = parsed.mulai;
            if (document.getElementById('jadwalkan-jam-selesai')) document.getElementById('jadwalkan-jam-selesai').value = parsed.selesai;
            document.getElementById('jadwalkan-ruang').value = ruangId || '';
            document.getElementById('jadwalkan-ketua').value = ketuaId || '';
            document.getElementById('jadwalkan-penguji1').value = p1Id || '';
            document.getElementById('jadwalkan-penguji2').value = p2Id || '';
            openModal('modal-jadwalkan');
        }


        function openEdit(id, data) {
            document.getElementById('form-edit').action = '/master/skripsi/' + id;
            document.getElementById('edit-nim').value                             = data.nim || '';
            document.getElementById('edit-nama').value                            = data.nama_mahasiswa || '';
            document.getElementById('edit-judul').value                           = data.judul_skripsi || '';
            document.getElementById('edit-dosbing-utama').value                   = data.dosen_pembimbing_utama_id || '';
            document.getElementById('edit-dosbing-pendamping').value              = data.dosen_pembimbing_pendamping_id || '';
            document.getElementById('edit-ketua').value                           = data.ketua_penguji_id || '';
            document.getElementById('edit-penguji1').value                        = data.anggota_penguji_1_id || '';
            document.getElementById('edit-penguji2').value                        = data.anggota_penguji_2_id || '';
            document.getElementById('edit-ruangan').value                         = data.ruang_id || '';
            document.getElementById('edit-periode').value                         = data.periode_id || '';
            document.getElementById('edit-tanggal').value                         = data.tanggal || '';
            document.getElementById('edit-tanggal-pendaftaran').value             = data.tanggal_pendaftaran || '';
            const parsed = parseJamRange(data.jam);
            if (document.getElementById('edit-jam-mulai')) document.getElementById('edit-jam-mulai').value = parsed.mulai;
            if (document.getElementById('edit-jam-selesai')) document.getElementById('edit-jam-selesai').value = parsed.selesai;
            document.getElementById('edit-jenis').value                           = data.jenis_tugas_akhir || 'skripsi';

            // ── Section 1: Schedule Conflicts (red box) ──
            const scheduleBox  = document.getElementById('edit-conflict-schedule-box');
            const scheduleList = document.getElementById('edit-conflict-schedule-list');
            if (scheduleList && scheduleBox) {
                scheduleList.innerHTML = '';
                const sched = data.conflict_schedule || [];
                if (sched.length > 0) {
                    sched.forEach(msg => {
                        const li = document.createElement('li');
                        li.textContent = msg;
                        scheduleList.appendChild(li);
                    });
                    scheduleBox.style.display = 'block';
                } else {
                    scheduleBox.style.display = 'none';
                }
            }

            // ── Section 2: Business Rule Violations (orange box) ──
            const rulesBox  = document.getElementById('edit-conflict-rules-box');
            const rulesList = document.getElementById('edit-conflict-rules-list');
            if (rulesList && rulesBox) {
                rulesList.innerHTML = '';
                const rules = data.conflict_rules || [];
                if (rules.length > 0) {
                    rules.forEach(msg => {
                        const li = document.createElement('li');
                        li.textContent = msg;
                        rulesList.appendChild(li);
                    });
                    rulesBox.style.display = 'block';
                } else {
                    rulesBox.style.display = 'none';
                }
            }

            openModal('modal-edit');
        }

        function openDelete(id, nama) {
            document.getElementById('hapus-nama').textContent = nama;
            document.getElementById('form-hapus').action = '/master/skripsi/' + id;
            openModal('modal-hapus');
        }

        // Initialize FullCalendar
        function initCalendar() {
            if (calendar) {
                calendar.render();
                return;
            }
            
            const calendarEl = document.getElementById('calendar-view');
            if (!calendarEl) return;
            
            if (calendar) {
                calendar.destroy();
            }
            
            const eventsData = JSON.parse(calendarEl.getAttribute('data-events') || '[]');
            const firstDate = (eventsData.length > 0 && eventsData[0].start) ? eventsData[0].start.split('T')[0] : null;

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                initialDate: firstDate || undefined,
                locale: 'id',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: eventsData,
                eventDidMount: function(info) {
                    if (info.event.backgroundColor) {
                        info.el.style.setProperty('background-color', info.event.backgroundColor, 'important');
                        info.el.style.setProperty('border-color', info.event.borderColor || info.event.backgroundColor, 'important');
                        info.el.style.setProperty('color', '#ffffff', 'important');
                    }
                },
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    
                    document.getElementById('detail-nim').textContent = props.nim;
                    document.getElementById('detail-nama').textContent = props.mahasiswa;
                    document.getElementById('detail-judul').textContent = props.judul;
                    document.getElementById('detail-dosbing').textContent = props.dosbing;
                    document.getElementById('detail-dosbing-p').textContent = props.penguji_1; // using mapping representation
                    document.getElementById('detail-ketua').textContent = props.ketua_penguji;
                    document.getElementById('detail-penguji1').textContent = props.penguji_1;
                    document.getElementById('detail-penguji2').textContent = props.penguji_2;
                    
                    const dateStr = info.event.start.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                    document.getElementById('detail-jadwal').textContent = dateStr + ' | ' + props.jam;
                    document.getElementById('detail-ruang').textContent = '📍 Ruangan: ' + props.ruang;
                    
                    const jenisBadge = document.getElementById('detail-jenis');
                    jenisBadge.textContent = props.jenis;
                    jenisBadge.className = 'badge ' + (info.event.backgroundColor === '#4361ee' ? 'badge-sidang' : 'badge-jurnal');

                    const conflictBox = document.getElementById('detail-conflict-box');
                    const conflictText = document.getElementById('detail-conflict-text');
                    if (props.conflict_notes) {
                        conflictText.textContent = props.conflict_notes;
                        conflictBox.style.display = 'block';
                    } else {
                        conflictBox.style.display = 'none';
                    }

                    openModal('modal-detail');
                }
            });
            calendar.render();
        }

        // Intercept Form Submissions
        document.getElementById('form-tambah')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            
            form.querySelectorAll('.error-feedback').forEach(el => el.remove());
            
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
                    closeModal('modal-tambah');
                    form.reset();
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    setTimeout(() => location.reload(), 800);
                } else {
                    if (response.status === 422) {
                        if (result.errors) {
                            Object.keys(result.errors).forEach(key => {
                                const input = form.querySelector(`[name="${key}"]`);
                                if (input) {
                                    const errEl = document.createElement('p');
                                    errEl.className = 'error-feedback text-xs text-rose-600 mt-1 font-semibold';
                                    errEl.textContent = result.errors[key][0];
                                    input.parentNode.appendChild(errEl);
                                }
                            });
                        } else {
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Terjadi kesalahan.', type: 'error' } }));
                        }
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Terjadi kesalahan.', type: 'error' } }));
                    }
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        document.getElementById('form-edit')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            
            form.querySelectorAll('.error-feedback').forEach(el => el.remove());
            
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
                    closeModal('modal-edit');
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    setTimeout(() => location.reload(), 800);
                } else {
                    if (response.status === 422) {
                        if (result.errors) {
                            Object.keys(result.errors).forEach(key => {
                                const input = form.querySelector(`[name="${key}"]`);
                                if (input) {
                                    const errEl = document.createElement('p');
                                    errEl.className = 'error-feedback text-xs text-rose-600 mt-1 font-semibold';
                                    errEl.textContent = result.errors[key][0];
                                    input.parentNode.appendChild(errEl);
                                }
                            });
                        } else {
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Terjadi kesalahan.', type: 'error' } }));
                        }
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Terjadi kesalahan.', type: 'error' } }));
                    }
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        document.getElementById('form-hapus')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            
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
                    closeModal('modal-hapus');
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    setTimeout(() => location.reload(), 800);
                } else {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Gagal menghapus.', type: 'error' } }));
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        document.getElementById('form-jadwalkan')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const origText  = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '⏳ Menyimpan…'; }
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: new FormData(form)
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    closeModal('modal-jadwalkan');
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || '✅ Jadwal berhasil disimpan!', type: 'success' } }));
                    setTimeout(() => location.reload(), 800);
                } else {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Terjadi kesalahan saat menyimpan jadwal.', type: response.status === 422 ? 'warning' : 'error' } }));
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Gagal terhubung ke server.', type: 'error' } }));
            } finally {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
            }
        });

        // Escape key close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ['modal-tambah','modal-edit','modal-hapus','modal-detail','modal-jadwalkan'].forEach(closeModal);
            }
        });
    </script>
</x-app-layout>

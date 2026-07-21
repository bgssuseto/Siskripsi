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
            color: #4f46e5;
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
        .stat-value { font-size: 1.75rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .stat-label { font-size: .8rem; color: #64748b; margin-top: .2rem; }

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
            outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12);
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
        .btn-primary { background: #6366f1; color: #fff; }
        .btn-primary:hover { background: #4f46e5; box-shadow: 0 4px 12px rgba(99,102,241,.35); }
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
        .schedule-jam  { color: #6366f1; font-size: .75rem; font-weight: 600; margin-top: .1rem; }
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
        .form-control:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
        textarea.form-control { resize: vertical; min-height: 70px; }
        .form-section { margin-top: 1.1rem; }
        .form-section-title {
            font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
            color: #94a3b8; margin-bottom: .6rem; padding-bottom: .35rem; border-bottom: 1px solid #f1f5f9;
        }

        .alert { border-radius: 12px; padding: .85rem 1.1rem; display: flex; gap: .75rem; align-items: flex-start; font-size: .875rem; margin-bottom: 1rem; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }

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
            background-color: #6366f1 !important; border-color: #6366f1 !important; border-radius: 8px !important;
            font-size: .85rem !important; font-weight: 600 !important;
        }
        .fc-button-primary:hover { background-color: #4f46e5 !important; border-color: #4f46e5 !important; }
        .fc-button-active { background-color: #4338ca !important; border-color: #4338ca !important; }
        .fc-daygrid-day-number { font-size: .82rem !important; font-weight: 700 !important; color: #334155 !important; }

        @media (max-width: 640px) { .form-grid-2 { grid-template-columns: 1fr; } }
    </style>

    {{-- FullCalendar v6 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <div class="sidang-page" x-data="{ currentView: 'table' }">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success" x-data x-init="setTimeout(()=>$el.remove(),5000)">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('warning'))
            <div class="alert alert-error" style="background:#fff7ed; border-color:#fed7aa; color:#c2410c;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <div><strong>⚠️ Bentrok Jadwal</strong><p class="text-xs mt-1">{{ session('warning') }}</p></div>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-error" style="background:#fff1f2; border-color:#fecaca; color:#b91c1c;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div><strong>❌ Pelanggaran Aturan Sidang</strong><p class="text-xs mt-1">{{ session('error') }}</p></div>
            </div>
        @endif
        @php
            $scheduleConflictCount = collect($conflictMap ?? [])->filter(fn($v) => !empty($v['schedule']))->count();
            $ruleViolationCount    = collect($conflictMap ?? [])->filter(fn($v) => !empty($v['rules']))->count();
        @endphp
        @if($scheduleConflictCount > 0 || $ruleViolationCount > 0)
            <div class="alert alert-error">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <div>
                    <strong>Perhatian: Terdeteksi Masalah Jadwal Sidang</strong>
                    <ul class="text-xs mt-1 list-disc list-inside space-y-0.5">
                        @if($scheduleConflictCount > 0)
                            <li>⚠️ <strong>{{ $scheduleConflictCount }}</strong> sidang memiliki <em>bentrok jadwal</em> (ruangan atau penguji bersamaan). Ditandai badge merah di tabel.</li>
                        @endif
                        @if($ruleViolationCount > 0)
                            <li>❌ <strong>{{ $ruleViolationCount }}</strong> sidang memiliki <em>pelanggaran aturan</em> (Pembimbing Utama bukan Penguji 2, atau Pembimbing Pendamping menguji). Ditandai badge oranye di tabel.</li>
                        @endif
                    </ul>
                    <p class="text-xs mt-1.5 opacity-75">Klik ✏️ pada baris tersebut untuk melihat detail dan memperbaiki.</p>
                </div>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <ul class="list-disc list-inside ml-2">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        @endif
        @if (session('import_errors'))
            <div class="alert alert-error">
                <div>
                    <b>Beberapa baris gagal diimpor:</b>
                    <ul class="list-disc list-inside ml-2">@foreach (session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            </div>
        @endif


        {{-- Page header --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Jadwal Sidang Skripsi</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola, import, dan visualisasikan jadwal ujian sidang mahasiswa.</p>
            </div>
            
            <div class="flex items-center gap-3 flex-wrap">
                {{-- View Toggle --}}
                <div class="view-toggle">
                    <button @click="currentView = 'table'" :class="currentView === 'table' ? 'active' : ''" class="view-toggle-btn">
                        📋 Tabel
                    </button>
                    <button @click="currentView = 'calendar'; $nextTick(() => initCalendar())" :class="currentView === 'calendar' ? 'active' : ''" class="view-toggle-btn">
                        📅 Kalender
                    </button>
                </div>

                <a href="{{ route('sidang.import.form') }}" class="btn btn-success">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import Excel
                </a>
                <button onclick="openModal('modal-tambah')" class="btn btn-primary">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Data
                </button>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div>
                    <div class="stat-value">{{ $totalSidang + $totalJurnal }}</div>
                    <div class="stat-label">Total Mahasiswa</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">🎓</div>
                <div>
                    <div class="stat-value">{{ $totalSidang }}</div>
                    <div class="stat-label">Sidang Skripsi</div>
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
            <form method="GET" action="{{ route('sidang.index') }}" class="toolbar">
                <div class="toolbar-search">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NIM, nama, judul…">
                </div>
                
                <select name="periode_id" class="filter-select" onchange="this.form.submit()">
                    @foreach ($periodes as $p)
                        <option value="{{ $p->id }}" {{ (request('periode_id', $activePeriode->id ?? null) == $p->id) ? 'selected' : '' }}>
                            {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>

                <select name="jenis" class="filter-select" onchange="this.form.submit()">
                    <option value="">Semua Jenis</option>
                    <option value="sidang" {{ request('jenis')=='sidang'?'selected':'' }}>Sidang Skripsi</option>
                    <option value="jurnal" {{ request('jenis')=='jurnal'?'selected':'' }}>Jurnal</option>
                </select>

                <select name="tanggal" class="filter-select" onchange="this.form.submit()">
                    <option value="">Semua Tanggal</option>
                    @foreach ($daftarTanggal as $t)
                        <option value="{{ $t->format('Y-m-d') }}" {{ request('tanggal')==$t->format('Y-m-d')?'selected':'' }}>
                            {{ $t->translatedFormat('d M Y') }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search','jenis','tanggal','periode_id']))
                    <a href="{{ route('sidang.index') }}" class="btn btn-outline">✕ Reset</a>
                @endif
            </form>

            {{-- Table --}}
            <div class="table-card">
                <div class="table-scroll">
                    <table class="sidang-table">
                        <thead>
                            <tr>
                                <th style="width:42px; text-align:center;">No</th>
                                <th style="width:90px;">Tgl Daftar</th>
                                <th>NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Judul Skripsi</th>
                                <th>Periode</th>
                                <th>Dosbing Utama</th>
                                <th>Dosbing Pendamping</th>
                                <th>Ketua Penguji</th>
                                <th>Penguji 1</th>
                                <th>Penguji 2</th>
                                <th>Jadwal Sidang</th>
                                <th>Ruangan</th>
                                <th>Jenis</th>
                                <th style="width:85px; text-align:right;">Aksi</th>
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
                                    {{-- Tgl Daftar (dipindah ke setelah No) --}}
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
                                        <div>{{ $item->nama_mahasiswa }}</div>
                                        @if($hasSchedule)
                                            <div class="mt-1">
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700 border border-red-200">
                                                    ⚠️ Jadwal Bentrok
                                                </span>
                                            </div>
                                            <div class="mt-1 space-y-0.5">
                                                @foreach($conflictMap[$item->id]['schedule'] as $msg)
                                                    <div class="text-[10px] leading-snug text-red-700 pl-1">• {{ $msg }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($hasRuleViolation)
                                            <div class="mt-1">
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 border border-orange-200">
                                                    ❌ Pelanggaran Aturan
                                                </span>
                                            </div>
                                            <div class="mt-1 space-y-0.5">
                                                @foreach($conflictMap[$item->id]['rules'] as $msg)
                                                    <div class="text-[10px] leading-snug text-orange-700 pl-1">• {{ $msg }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td><p class="judul-text">{{ $item->judul_skripsi }}</p></td>
                                    <td><span class="text-xs font-semibold text-slate-500">{{ $item->periode ? $item->periode->nama_periode : '—' }}</span></td>
                                    <td><span class="dosen-chip utama">{{ $item->pembimbingUtama ? $item->pembimbingUtama->nama_dosen : '—' }}</span></td>
                                    <td><span class="dosen-chip">{{ $item->pembimbingPendamping ? $item->pembimbingPendamping->nama_dosen : '—' }}</span></td>
                                    <td><span class="dosen-chip ketua {{ $hasSchedule && in_array($item->ketua_penguji_id, array_column(array_filter(($conflictMap[$item->id]['schedule'] ?? []), fn($m) => str_contains($m,'Ketua')), 0)) ? 'ring-1 ring-red-400' : '' }}">{{ $item->ketuaPenguji ? $item->ketuaPenguji->nama_dosen : '—' }}</span></td>
                                    <td><span class="dosen-chip">{{ $item->anggotaPenguji1 ? $item->anggotaPenguji1->nama_dosen : '—' }}</span></td>
                                    <td><span class="dosen-chip">{{ $item->anggotaPenguji2 ? $item->anggotaPenguji2->nama_dosen : '—' }}</span></td>
                                    <td class="schedule-cell">
                                        @if($item->tanggal)
                                            <div class="schedule-hari">{{ $item->tanggal->translatedFormat('d F Y') }}</div>
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
                                    {{-- Kolom Tgl Daftar dihapus dari sini (dipindah ke depan) --}}
                                    <td>
                                        <span class="badge {{ $item->jenis_tugas_akhir === 'sidang' ? 'badge-sidang' : 'badge-jurnal' }}">
                                            {{ $item->jenis_tugas_akhir === 'sidang' ? 'Sidang' : 'Jurnal' }}
                                        </span>
                                    </td>
                                    <td class="text-right space-x-1 whitespace-nowrap">
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
                                        <h3>Belum ada data sidang</h3>
                                        <p style="font-size:.85rem;">Import Excel atau tambah data secara manual.</p>
                                        <div class="flex gap-3 justify-center mt-4">
                                            <a href="{{ route('sidang.import.form') }}" class="btn btn-success">Import Excel</a>
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
        <div x-show="currentView === 'calendar'" class="calendar-container" x-cloak>
            <div id="calendar-view"></div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: TAMBAH DATA                                              --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="modal-tambah" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-tambah')">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title">➕ Tambah Data Sidang</span>
                <button class="modal-close" onclick="closeModal('modal-tambah')">✕</button>
            </div>
            <form method="POST" action="{{ route('sidang.store') }}">
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
                                    <option value="sidang">Sidang Skripsi</option>
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
                <span class="modal-title">✏️ Edit Data Sidang</span>
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
                                <strong class="block text-xs font-bold uppercase tracking-wider text-orange-700 mb-1.5">Pelanggaran Aturan Sidang</strong>
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
                                    <option value="sidang">Sidang Skripsi</option>
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
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Tanggal Sidang</label>
                                <input type="date" name="tanggal" id="edit-tanggal" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Jam</label>
                                <input type="text" name="jam" id="edit-jam" class="form-control">
                            </div>
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
                <p>Hapus data sidang untuk mahasiswa <strong id="hapus-nama">?</strong></p>
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
                <span class="modal-title font-bold">📄 Detail Jadwal Sidang</span>
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

        function openEdit(id, data) {
            document.getElementById('form-edit').action = '/sidang/' + id;
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
            document.getElementById('edit-jam').value                             = data.jam || '';
            document.getElementById('edit-jenis').value                           = data.jenis_tugas_akhir || 'sidang';

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
            document.getElementById('form-hapus').action = '/sidang/' + id;
            openModal('modal-hapus');
        }

        // Initialize FullCalendar
        function initCalendar() {
            if (calendar) {
                calendar.render();
                return;
            }
            
            const calendarEl = document.getElementById('calendar-view');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: {!! json_encode($calendarEvents->values()) !!},
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
                    jenisBadge.className = 'badge ' + (info.event.backgroundColor === '#6366f1' ? 'badge-sidang' : 'badge-jurnal');

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

        // Escape key close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ['modal-tambah','modal-edit','modal-hapus','modal-detail'].forEach(closeModal);
            }
        });
    </script>
</x-app-layout>

<x-app-layout title="Jadwal Sempro">
    <x-slot:header>Jadwal Sempro</x-slot:header>

    <style>
        .sidang-page { font-family: 'Inter', 'Segoe UI', sans-serif; }

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
        .stat-value { font-size: 1.75rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .stat-label { font-size: .8rem; color: #64748b; margin-top: .2rem; }

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
        table.sidang-table tbody tr.conflict-schedule { background: #fff1f2 !important; }
        table.sidang-table td { padding: .75rem .85rem; vertical-align: top; color: #334155; }

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

    <div class="sidang-page space-y-6" x-data="{ currentView: 'table' }">

        {{-- Top Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Jadwal Sempro</h1>
                <p class="text-sm text-slate-500 mt-1">Penjadwalan seminar proposal mahasiswa.</p>
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

            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->hasAny(['search','status','periode_id']))
                <a href="{{ route('jadwal-sempro.index') }}" class="btn btn-outline">✕ Reset</a>
            @endif
        </form>

        {{-- TAMPILAN TABEL --}}
        <div x-show="currentView === 'table'">
            <div id="table-container" class="table-card">
                <div class="table-scroll">
                    <table class="sidang-table">
                        <thead>
                            <tr>
                                <th style="width:42px; text-align:center;">No</th>
                                <th style="width:90px;">Tgl Daftar</th>
                                <th>NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Judul Proposal</th>
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
                                        <div>{{ $item->nama_mahasiswa }}</div>
                                        @if($hasConflict)
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
                                    </td>
                                    <td><p class="judul-text">{{ $item->judul_skripsi }}</p></td>
                                    <td>
                                        @if(empty($item->tanggal))
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200 whitespace-nowrap">
                                                ● Belum Plotting
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                                                ✓ Sudah Dijadwal
                                            </span>
                                        @endif
                                    </td>
                                    <td><span class="text-xs font-semibold text-slate-500">{{ $item->periode ? $item->periode->nama_periode : '—' }}</span></td>
                                    <td><span class="dosen-chip utama">{{ $item->pembimbingUtama ? $item->pembimbingUtama->nama_dosen : '—' }}</span></td>
                                    <td><span class="dosen-chip">{{ $item->pembimbingPendamping ? $item->pembimbingPendamping->nama_dosen : '—' }}</span></td>
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
                                    <td class="text-right space-x-1 whitespace-nowrap">
                                        <button class="btn btn-primary btn-sm"
                                            onclick="openJadwalkanSempro({{ $item->id }}, '{{ addslashes($item->nama_mahasiswa) }}', '{{ $item->nim }}', '{{ $item->tanggal ? $item->tanggal->format('Y-m-d') : '' }}', '{{ $item->jam ?? '' }}', '{{ $item->ruang_id ?? '' }}')">
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
                                                'ruang_id' => $item->ruang_id,
                                                'periode_id' => $item->periode_id,
                                                'tanggal' => $item->tanggal ? $item->tanggal->format('Y-m-d') : '',
                                                'tanggal_pendaftaran' => $item->tanggal_pendaftaran ? $item->tanggal_pendaftaran->format('Y-m-d') : '',
                                                'jam' => $item->jam,
                                                'conflict_schedule' => $conflictMap[$item->id]['schedule'] ?? [],
                                            ]) }})">
                                            ✏️
                                        </button>
                                        <button class="btn btn-danger btn-sm"
                                            onclick="openDelete({{ $item->id }}, '{{ addslashes($item->nama_mahasiswa) }}')">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="py-12 text-center text-slate-400">
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
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl mb-4 text-xs">
                        <div class="font-bold text-slate-800 text-sm" id="jadwalkan-mhs-nama"></div>
                        <div class="text-slate-500 font-semibold mt-0.5" id="jadwalkan-mhs-nim"></div>
                    </div>

                    <div class="form-section mt-0">
                        <div class="form-section-title">Waktu & Tempat Sempro</div>
                        <div class="form-group">
                            <label>Tanggal Ujian Sempro <span style="color:red">*</span></label>
                            <input type="date" name="tanggal" id="jadwalkan-tanggal" class="form-control" required>
                        </div>
                        <div class="form-group mt-3">
                            <label>Jam Sempro <span style="color:red">*</span></label>
                            <input type="text" name="jam" id="jadwalkan-jam" class="form-control" placeholder="Contoh: 08.00 - 09.30" required>
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
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Tanggal Ujian</label>
                                <input type="date" name="tanggal" id="edit-tanggal" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Jam</label>
                                <input type="text" name="jam" id="edit-jam" class="form-control" placeholder="Contoh: 08.00 - 09.00">
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
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = '';
        }
        function closeOnOverlay(e, id) {
            if (e.target === document.getElementById(id)) closeModal(id);
        }

        function openJadwalkanSempro(id, nama, nim, tgl, jam, ruangId) {
            const form = document.getElementById('form-jadwalkan');
            form.action = '/jadwal/sempro/' + id + '/jadwalkan';
            document.getElementById('jadwalkan-mhs-nama').textContent = nama;
            document.getElementById('jadwalkan-mhs-nim').textContent = 'NIM: ' + nim;
            document.getElementById('jadwalkan-tanggal').value = tgl || '';
            document.getElementById('jadwalkan-jam').value = jam || '';
            document.getElementById('jadwalkan-ruang').value = ruangId || '';
            openModal('modal-jadwalkan');
        }

        function openEdit(id, data) {
            document.getElementById('form-edit').action = '/master/sempro/' + id;
            document.getElementById('edit-nim').value                             = data.nim || '';
            document.getElementById('edit-nama').value                            = data.nama_mahasiswa || '';
            document.getElementById('edit-judul').value                           = data.judul_skripsi || '';
            document.getElementById('edit-dosbing-utama').value                   = data.dosen_pembimbing_utama_id || '';
            document.getElementById('edit-dosbing-pendamping').value              = data.dosen_pembimbing_pendamping_id || '';
            document.getElementById('edit-ruangan').value                         = data.ruang_id || '';
            document.getElementById('edit-periode').value                         = data.periode_id || '';
            document.getElementById('edit-tanggal').value                         = data.tanggal || '';
            document.getElementById('edit-tanggal-pendaftaran').value             = data.tanggal_pendaftaran || '';
            document.getElementById('edit-jam').value                             = data.jam || '';
            openModal('modal-edit');
        }

        function openDelete(id, nama) {
            document.getElementById('hapus-nama').textContent = nama;
            document.getElementById('form-hapus').action = '/master/sempro/' + id;
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
            });
            calendar.render();
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ['modal-jadwalkan','modal-edit','modal-hapus'].forEach(closeModal);
            }
        });
    </script>
</x-app-layout>

<x-app-layout title="Data Sempro">
    <x-slot:header>Data Sempro</x-slot:header>

    <style>
        .data-page { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }

        /* Stats */
        .stat-card {
            background: #fff; border-radius: 16px; padding: 1.25rem 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #f1f5f9;
        }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
        .stat-icon.blue  { background: #eff6ff; }
        .stat-icon.green { background: #f0fdf4; }
        .stat-value { font-size: 1.75rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .stat-label { font-size: .8rem; color: #64748b; margin-top: .2rem; }

        /* Toolbar */
        .toolbar {
            background: #fff; border-radius: 14px; padding: 1rem;
            display: flex; flex-wrap: wrap; gap: .75rem; align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,.07); border: 1px solid #f1f5f9;
        }
        .toolbar-search { position: relative; flex: 1; min-width: 200px; }
        .toolbar-search svg { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
        .toolbar-search input {
            width: 100%; padding: .55rem .75rem .55rem 2.25rem;
            border: 1px solid #e2e8f0; border-radius: 10px; font-size: .875rem; color: #1e293b;
            transition: border-color .15s, box-shadow .15s;
        }
        .toolbar-search input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
        .filter-select {
            padding: .55rem .85rem; border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: .875rem; color: #1e293b; background: #fff; cursor: pointer;
        }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1rem; border-radius: 10px; font-size: .875rem; font-weight: 600; cursor: pointer; transition: all .15s; border: none; }
        .btn-primary { background: #6366f1; color: #fff; }
        .btn-primary:hover { background: #4f46e5; box-shadow: 0 4px 12px rgba(99,102,241,.35); }
        .btn-success { background: #10b981; color: #fff; }
        .btn-success:hover { background: #059669; box-shadow: 0 4px 12px rgba(16,185,129,.35); }
        .btn-outline { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: .3rem .65rem; font-size: .78rem; border-radius: 7px; }

        /* Table */
        .table-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #f1f5f9; }
        .table-scroll { overflow-x: auto; }
        table.data-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
        table.data-table thead th {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #e2e8f0; font-weight: 600; padding: .75rem .85rem;
            text-align: left; font-size: .78rem; letter-spacing: .03em; text-transform: uppercase;
        }
        table.data-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
        table.data-table tbody tr:hover { background: #f8fafc; }
        table.data-table td { padding: .7rem .85rem; vertical-align: top; color: #334155; }

        /* Badges & Chips */
        .nim-pill { font-family: 'Courier New', monospace; background: #f1f5f9; color: #475569; padding: .15rem .5rem; border-radius: 6px; font-size: .78rem; font-weight: 600; }
        .judul-text { font-size: .8rem; color: #1e293b; font-weight: 500; line-height: 1.4; max-width: 280px; }
        .dosen-chip { display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 7px; padding: .1rem .45rem; font-size: .75rem; color: #475569; margin: .1rem 0; }
        .dosen-chip.utama { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; font-weight: 600; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem; }
        .modal-box { background: #fff; border-radius: 20px; width: 100%; max-width: 680px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.2); }
        .modal-box.modal-sm { max-width: 440px; }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
        .modal-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
        .modal-close { width: 32px; height: 32px; border: none; background: #f1f5f9; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; }
        .modal-body { padding: 1.25rem 1.5rem; }
        .modal-footer { display: flex; gap: .75rem; justify-content: flex-end; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; }

        /* Form */
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }
        .form-group { display: flex; flex-direction: column; gap: .35rem; }
        .form-group label { font-size: .78rem; font-weight: 600; color: #374151; }
        .form-control { padding: .55rem .75rem; border: 1px solid #e2e8f0; border-radius: 10px; font-size: .875rem; color: #1e293b; background: #fff; width: 100%; }
        .form-control:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
        textarea.form-control { resize: vertical; min-height: 70px; }
        .form-section { margin-top: 1.1rem; }
        .form-section-title { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: .6rem; padding-bottom: .35rem; border-bottom: 1px solid #f1f5f9; }

        /* Pagination */
        .pagination-wrap { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; background: #fff; }
        .pagination-info { font-size: .82rem; color: #64748b; }
        .pagination-links { display: flex; gap: .35rem; flex-wrap: wrap; }
        .page-btn { padding: .35rem .7rem; border-radius: 8px; font-size: .8rem; font-weight: 600; text-decoration: none; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; cursor: pointer; transition: all .15s; }
        .page-btn:hover { background: #e2e8f0; }
        .page-btn.active { background: #6366f1; color: #fff; border-color: #6366f1; }
        .page-btn.disabled { opacity: .4; pointer-events: none; }

        /* Dark Mode Overrides */
        html.dark .stat-card { background: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
        html.dark .stat-value { color: #f8fafc; }
        html.dark .stat-label { color: #94a3b8; }
        html.dark .stat-icon.blue  { background: rgba(59,130,246,.15); }
        html.dark .stat-icon.green { background: rgba(34,197,94,.15); }

        html.dark .toolbar { background: #1e293b; border-color: #334155; }
        html.dark .toolbar-search input,
        html.dark .filter-select { background: #0f172a; border-color: #334155; color: #f8fafc; }
        html.dark .toolbar-search input:focus,
        html.dark .filter-select:focus { border-color: #818cf8; }

        html.dark .btn-outline { background: #1e293b; color: #94a3b8; border-color: #334155; }
        html.dark .btn-outline:hover { background: #334155; color: #f8fafc; }

        html.dark .table-card { background: #1e293b; border-color: #334155; }
        html.dark table.data-table tbody tr { border-bottom-color: #334155; }
        html.dark table.data-table tbody tr:hover { background: #0f172a; }
        html.dark table.data-table td { color: #cbd5e1; }
        html.dark .judul-text { color: #f8fafc; }
        html.dark .nim-pill { background: #0f172a; color: #94a3b8; }
        html.dark .dosen-chip { background: #0f172a; border-color: #334155; color: #cbd5e1; }
        html.dark .dosen-chip.utama { background: rgba(29,78,216,.2); border-color: rgba(191,219,254,.2); color: #93c5fd; }

        html.dark .modal-box { background: #1e293b; border: 1px solid #334155; }
        html.dark .modal-header { border-bottom-color: #334155; background: #0f172a; }
        html.dark .modal-title { color: #f8fafc; }
        html.dark .modal-close { background: #334155; color: #94a3b8; }
        html.dark .modal-footer { border-top-color: #334155; background: #0f172a; }

        html.dark .form-group label { color: #cbd5e1; }
        html.dark .form-control { background: #0f172a; border-color: #334155; color: #f8fafc; }
        html.dark .form-control:focus { border-color: #818cf8; }
        html.dark .form-section-title { color: #64748b; border-bottom-color: #334155; }

        html.dark .pagination-wrap { background: #1e293b; border-top-color: #334155; }
        html.dark .pagination-info { color: #94a3b8; }
        html.dark .page-btn { background: #0f172a; border-color: #334155; color: #94a3b8; }
        html.dark .page-btn:hover { background: #334155; color: #f8fafc; }
        html.dark .page-btn.active { background: #6366f1; color: #fff; border-color: #6366f1; }

        @media (max-width: 640px) { .form-grid-2 { grid-template-columns: 1fr; } }
    </style>

    <div class="data-page space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Data Sempro</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola data mahasiswa seminar proposal (sempro).</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('master.sempro.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 border border-emerald-500 cursor-pointer">
                    📊 Export Excel
                </a>
                <a href="{{ route('master.sempro.import.form') }}" class="btn btn-success">
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
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
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
        <form method="GET" action="{{ route('master.sempro.index') }}" class="toolbar">
            <div class="toolbar-search">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NIM, nama, judul…">
            </div>

            <select name="per_page" class="filter-select" onchange="this.form.submit()">
                <option value="5" {{ request('per_page', 5) == 5 ? 'selected' : '' }}>Tampilkan 5 data</option>
                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>Tampilkan 10 data</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>Tampilkan 25 data</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>Tampilkan 100 data</option>
            </select>

            <select name="status" class="filter-select">
                <option value="">Semua Status</option>
                <option value="belum" {{ request('status') == 'belum' ? 'selected' : '' }}>Belum Dijadwalkan</option>
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
            @if(request()->hasAny(['search','status','periode_id', 'per_page']))
                <a href="{{ route('master.sempro.index') }}" class="btn btn-outline">✕ Reset</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="table-card">
            <div class="table-scroll">
                <table class="data-table" id="sempro-table">
                    <thead>
                        <tr>
                            <th style="width:42px; text-align:center;">No</th>
                            <th style="width:90px;">Tgl Daftar</th>
                            <th>NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Status Ujian</th>
                            <th>Verifikasi</th>
                            <th>Periode</th>
                            <th>Dosbing Utama</th>
                            <th>Dosbing Pendamping</th>
                            <th style="width:160px; text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sidangs as $item)
                            <tr id="row-sempro-{{ $item->id }}">
                                <td style="text-align:center; color:#475569; font-weight:700; font-size:.78rem;">
                                    {{ ($sidangs->currentPage() - 1) * $sidangs->perPage() + $loop->iteration }}
                                </td>
                                <td>
                                    @if($item->tanggal_pendaftaran)
                                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded-md whitespace-nowrap">
                                            {{ $item->tanggal_pendaftaran->locale('id')->translatedFormat('l, d/m/Y') }}
                                        </span>
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="nim-pill">{{ $item->nim }}</span>
                                    @if($item->file_persyaratan)
                                        <div class="mt-1">
                                            <a href="{{ asset($item->file_persyaratan) }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold">
                                                <span>📄</span> Berkas (PDF)
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td class="font-bold text-slate-800 dark:text-slate-100" style="min-width:150px;">{{ $item->nama_mahasiswa }}</td>
                                <td>
                                    {!! $item->getJadwalStatusHtml() !!}
                                </td>
                                <td>
                                    @php
                                        $verifStatus = $item->verifikasi_status ?? 'menunggu';
                                        $bgClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                        $labelTxt = 'Menunggu';
                                        if ($verifStatus === 'disetujui') {
                                            $bgClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                            $labelTxt = 'Disetujui';
                                        } elseif ($verifStatus === 'ditolak') {
                                            $bgClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                            $labelTxt = 'Ditolak';
                                        }
                                    @endphp
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $bgClass }}">
                                            {{ $labelTxt }}
                                        </span>
                                        @if($verifStatus === 'ditolak' && $item->verifikasi_komentar)
                                            <span class="text-[10px] text-slate-500 font-normal max-w-[150px] truncate" title="{{ $item->verifikasi_komentar }}">Catatan: {{ $item->verifikasi_komentar }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td><span class="text-xs font-semibold text-slate-500">{{ $item->periode ? $item->periode->nama_periode : '—' }}</span></td>
                                <td><span class="dosen-chip utama">{{ $item->pembimbingUtama ? $item->pembimbingUtama->nama_dosen : '—' }}</span></td>
                                <td><span class="dosen-chip">{{ $item->pembimbingPendamping ? $item->pembimbingPendamping->nama_dosen : '—' }}</span></td>
                                <td class="text-right space-x-1 whitespace-nowrap">
                                    <button class="btn btn-outline btn-sm"
                                        onclick="openEdit({{ $item->id }}, {{ json_encode([
                                            'id'                             => $item->id,
                                            'nim'                            => $item->nim,
                                            'nama_mahasiswa'                 => $item->nama_mahasiswa,
                                            'judul_skripsi'                  => $item->judul_skripsi,
                                            'periode_id'                     => $item->periode_id,
                                            'tanggal_pendaftaran'            => $item->tanggal_pendaftaran?->format('Y-m-d'),
                                            'dosen_pembimbing_utama_id'      => $item->dosen_pembimbing_utama_id,
                                            'dosen_pembimbing_pendamping_id' => $item->dosen_pembimbing_pendamping_id,
                                        ]) }})">
                                        ✏️
                                    </button>
                                    <button class="btn btn-danger btn-sm"
                                        onclick="confirmHapus({{ $item->id }}, {{ json_encode($item->nama_mahasiswa) }})">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center; padding:3rem; color:#94a3b8;">
                                    <div style="font-size:2.5rem; margin-bottom:.5rem;">📂</div>
                                    <div style="font-weight:700; color:#64748b;">Belum ada data sempro</div>
                                    <div style="font-size:.82rem; margin-top:.25rem;">Import Excel atau tambah data secara manual.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($sidangs->hasPages())
            <div class="pagination-wrap" id="sempro-pagination">
                <div class="pagination-info">
                    Menampilkan {{ $sidangs->firstItem() }}–{{ $sidangs->lastItem() }} dari {{ $sidangs->total() }} data
                </div>
                <div class="pagination-links">
                    @if($sidangs->onFirstPage())
                        <span class="page-btn disabled">‹</span>
                    @else
                        <a href="{{ $sidangs->previousPageUrl() }}" class="page-btn">‹</a>
                    @endif

                    @foreach($sidangs->getUrlRange(max(1, $sidangs->currentPage()-2), min($sidangs->lastPage(), $sidangs->currentPage()+2)) as $page => $url)
                        <a href="{{ $url }}" class="page-btn {{ $page == $sidangs->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                    @endforeach

                    @if($sidangs->hasMorePages())
                        <a href="{{ $sidangs->nextPageUrl() }}" class="page-btn">›</a>
                    @else
                        <span class="page-btn disabled">›</span>
                    @endif
                </div>
            </div>
            @endif
        </div>

    </div>{{-- /data-page --}}

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: TAMBAH DATA                                              --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div id="modal-tambah" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-tambah')">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title">➕ Tambah Data Sempro</span>
                <button class="modal-close" onclick="closeModal('modal-tambah')">✕</button>
            </div>
            <form method="POST" action="{{ route('master.sempro.store') }}">
                @csrf
                <input type="hidden" name="jenis_tugas_akhir" value="sempro">
                <div class="modal-body">
                    {{-- Identitas --}}
                    <div class="form-section">
                        <div class="form-section-title">Identitas Mahasiswa</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>NIM <span style="color:red">*</span></label>
                                <input type="text" name="nim" class="form-control" required placeholder="202251xxx">
                            </div>
                            <div class="form-group">
                                <label>Periode Akademik <span style="color:red">*</span></label>
                                <select name="periode_id" class="form-control" required>
                                    @foreach ($periodes as $p)
                                        <option value="{{ $p->id }}" {{ $p->aktif ? 'selected' : '' }}>{{ $p->nama_periode }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <label>Nama Mahasiswa <span style="color:red">*</span></label>
                            <input type="text" name="nama_mahasiswa" class="form-control" required placeholder="Nama lengkap mahasiswa">
                        </div>
                        <div class="form-group mt-3">
                            <label>Judul Skripsi <span style="color:red">*</span></label>
                            <textarea name="judul_skripsi" class="form-control" required placeholder="Judul skripsi / tugas akhir"></textarea>
                        </div>
                        <div class="form-group mt-3">
                            <label>Tanggal Pendaftaran</label>
                            <input type="date" name="tanggal_pendaftaran" class="form-control">
                        </div>
                    </div>

                    {{-- Dosen --}}
                    <div class="form-section">
                        <div class="form-section-title">Dosen Pembimbing</div>
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
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-tambah')" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary">💾 Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: EDIT DATA                                                --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div id="modal-edit" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-edit')">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title">✏️ Edit Data Sempro</span>
                <button class="modal-close" onclick="closeModal('modal-edit')">✕</button>
            </div>
            <form method="POST" id="form-edit" action="">
                @csrf @method('PUT')
                <input type="hidden" name="jenis_tugas_akhir" value="sempro">
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
                        <div class="form-group mt-3">
                            <label>Nama Mahasiswa <span style="color:red">*</span></label>
                            <input type="text" name="nama_mahasiswa" id="edit-nama" class="form-control" required>
                        </div>
                        <div class="form-group mt-3">
                            <label>Judul Skripsi <span style="color:red">*</span></label>
                            <textarea name="judul_skripsi" id="edit-judul" class="form-control" required></textarea>
                        </div>
                        <div class="form-group mt-3">
                            <label>Tanggal Pendaftaran</label>
                            <input type="date" name="tanggal_pendaftaran" id="edit-tgl-daftar" class="form-control">
                        </div>
                    </div>
                    <div class="form-section">
                        <div class="form-section-title">Dosen Pembimbing</div>
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
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-edit')" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: KONFIRMASI HAPUS                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div id="modal-hapus" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-hapus')">
        <div class="modal-box modal-sm">
            <div class="modal-header">
                <span class="modal-title">🗑️ Konfirmasi Hapus</span>
                <button class="modal-close" onclick="closeModal('modal-hapus')">✕</button>
            </div>
            <div class="modal-body" style="text-align:center; padding: 2rem 1.5rem;">
                <div style="font-size:3rem; margin-bottom:1rem;">⚠️</div>
                <p style="font-weight:700; color:#0f172a; font-size:1rem; margin-bottom:.5rem;">Hapus data ini?</p>
                <p id="hapus-detail" style="font-size:.85rem; color:#64748b; margin-bottom:0;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('modal-hapus')" class="btn btn-outline">Batal</button>
                <button type="button" id="btn-konfirmasi-hapus" class="btn btn-danger">🗑️ Ya, Hapus</button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: VERIFIKASI PENDAFTARAN                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div id="modal-verifikasi" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-verifikasi')">
        <div class="modal-box modal-sm">
            <div class="modal-header">
                <span class="modal-title" id="verif-title">🛡️ Verifikasi Pendaftaran</span>
                <button class="modal-close" onclick="closeModal('modal-verifikasi')">✕</button>
            </div>
            <form id="verif-form" method="POST" action="">
                @csrf
                <div class="modal-body space-y-4">
                    {{-- Student Registration Details Panel --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.85rem; margin-bottom: 1rem; font-size: 0.8rem; line-height: 1.5; color: #334155; text-align: left;">
                        <div style="margin-bottom: 0.4rem;"><strong>NIM:</strong> <span id="verif-detail-nim"></span></div>
                        <div style="margin-bottom: 0.4rem;"><strong>Nama:</strong> <span id="verif-detail-nama"></span></div>
                        <div style="margin-bottom: 0.4rem;"><strong>Judul:</strong> <span id="verif-detail-judul" style="font-style: italic;"></span></div>
                        <div style="margin-bottom: 0.4rem;"><strong>Pembimbing Utama:</strong> <span id="verif-detail-dosbing-utama"></span></div>
                        <div style="margin-bottom: 0.4rem;"><strong>Pembimbing Pendamping:</strong> <span id="verif-detail-dosbing-pendamping"></span></div>
                        <div style="margin-top: 0.6rem; padding-top: 0.6rem; border-top: 1px solid #e2e8f0;" id="verif-detail-bukti-container">
                            <strong>Bukti Pembayaran:</strong> 
                            <a id="verif-detail-bukti" href="#" target="_blank" style="color: #4f46e5; font-weight: bold; text-decoration: underline;">Lihat Bukti</a>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status Verifikasi</label>
                        <select name="verifikasi_status" id="verif-status" onchange="toggleKomentarField()" class="form-control">
                            <option value="menunggu">Menunggu Verifikasi</option>
                            <option value="disetujui">Setujui (Terverifikasi Koordinator)</option>
                            <option value="ditolak">Tolak Pendaftaran</option>
                        </select>
                    </div>

                    <div class="form-group" id="komentar-group" style="display:none; margin-top: 1rem;">
                        <label>Catatan / Komentar Penolakan</label>
                        <textarea name="verifikasi_komentar" id="verif-komentar" placeholder="Sebutkan alasan penolakan persyaratan..." class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-verifikasi')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Verifikasi</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast Notification System --}}
    <style>
        #toast-container { position:fixed; bottom:1.5rem; right:1.5rem; z-index:99999; display:flex; flex-direction:column; gap:.65rem; pointer-events:none; }
        .toast { display:flex; align-items:flex-start; gap:.85rem; background:#fff; border-radius:14px; box-shadow:0 8px 30px rgba(0,0,0,.14); padding:.9rem 1.1rem; min-width:300px; max-width:400px; border-left:4px solid #6366f1; pointer-events:all; animation:toastIn .35s cubic-bezier(.34,1.56,.64,1); position:relative; }
        .toast.toast-success { border-color:#10b981; }
        .toast.toast-error   { border-color:#ef4444; }
        .toast.toast-warning { border-color:#f59e0b; }
        .toast.toast-out { animation:toastOut .3s ease forwards; }
        @keyframes toastIn { from{opacity:0;transform:translateX(2rem) scale(.92)} to{opacity:1;transform:translateX(0) scale(1)} }
        @keyframes toastOut { from{opacity:1;transform:translateX(0) scale(1)} to{opacity:0;transform:translateX(2rem) scale(.9)} }
        .toast-icon { font-size:1.4rem; flex-shrink:0; }
        .toast-body { flex:1; }
        .toast-title { font-weight:700; font-size:.88rem; color:#0f172a; }
        .toast-msg   { font-size:.8rem; color:#64748b; margin-top:.15rem; }
        .toast-close { background:none; border:none; color:#94a3b8; cursor:pointer; font-size:1.1rem; }
    </style>
    <div id="toast-container"></div>

    @push('scripts')
    <script>
        const TOAST_ICONS  = { success:'✅', error:'❌', warning:'⚠️', info:'ℹ️' };
        const TOAST_TITLES = { success:'Berhasil!', error:'Gagal!', warning:'Peringatan', info:'Informasi' };

        function showToast(type, message, duration=4000) {
            const c = document.getElementById('toast-container');
            const t = document.createElement('div');
            t.className = `toast toast-${type}`;
            t.innerHTML = `<span class="toast-icon">${TOAST_ICONS[type]||'ℹ️'}</span><div class="toast-body"><div class="toast-title">${TOAST_TITLES[type]||type}</div><div class="toast-msg">${message}</div></div><button class="toast-close" onclick="dismissToast(this.parentElement)">✕</button>`;
            c.appendChild(t);
            setTimeout(() => dismissToast(t), duration);
        }
        function dismissToast(el) {
            if (!el || el.classList.contains('toast-out')) return;
            el.classList.add('toast-out');
            setTimeout(() => el.remove(), 300);
        }

        @if(session('success')) showToast('success', @json(session('success'))); @endif
        @if(session('error'))   showToast('error',   @json(session('error')));   @endif
        @if(session('warning')) showToast('warning', @json(session('warning'))); @endif

        function openModal(id)  { document.getElementById(id).style.display='flex'; document.body.style.overflow='hidden'; }
        function closeModal(id) { document.getElementById(id).style.display='none'; document.body.style.overflow=''; }
        function closeOnOverlay(e,id) { if(e.target===document.getElementById(id)) closeModal(id); }

        // ── AJAX Helpers ──────────────────────────────────────────────────────
        const CSRF = '{{ csrf_token() }}';

        async function refreshTableContent() {
            try {
                const res = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                const doc = (new DOMParser()).parseFromString(html, 'text/html');
                const newTbody = doc.querySelector('#sempro-table tbody');
                const newPag   = doc.querySelector('#sempro-pagination');
                if (newTbody) document.querySelector('#sempro-table tbody').innerHTML = newTbody.innerHTML;
                if (newPag)   document.getElementById('sempro-pagination').innerHTML = newPag.innerHTML;
            } catch(e) { location.reload(); }
        }

        // ── Tambah (AJAX) ─────────────────────────────────────────────────────
        document.getElementById('form-tambah').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn=this.querySelector('[type=submit]'), orig=btn.innerHTML;
            btn.disabled=true; btn.innerHTML='⏳ Menyimpan…';
            try {
                const res = await fetch(this.action, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:new FormData(this) });
                const data = await res.json();
                if (res.ok && data.success) { closeModal('modal-tambah'); this.reset(); showToast('success', data.message||'Data berhasil ditambahkan!'); await refreshTableContent(); }
                else showToast(res.status===422?'warning':'error', data.message||'Terjadi kesalahan.');
            } catch(err) { showToast('error','Gagal terhubung ke server.'); }
            finally { btn.disabled=false; btn.innerHTML=orig; }
        });

        // ── Edit Modal Setup ──────────────────────────────────────────────────
        function openEdit(id, data) {
            document.getElementById('form-edit').action = '{{ url("master/sempro") }}/' + id;
            document.getElementById('edit-nim').value   = data.nim || '';
            document.getElementById('edit-nama').value  = data.nama_mahasiswa || '';
            document.getElementById('edit-judul').value = data.judul_skripsi || '';
            document.getElementById('edit-tgl-daftar').value = data.tanggal_pendaftaran || '';
            setSelect('edit-periode',            data.periode_id);
            setSelect('edit-dosbing-utama',      data.dosen_pembimbing_utama_id);
            setSelect('edit-dosbing-pendamping', data.dosen_pembimbing_pendamping_id);
            openModal('modal-edit');
        }
        function setSelect(id,val) { const el=document.getElementById(id); if(el) el.value=val||''; }

        // ── Edit (AJAX) ───────────────────────────────────────────────────────
        document.getElementById('form-edit').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn=this.querySelector('[type=submit]'), orig=btn.innerHTML;
            btn.disabled=true; btn.innerHTML='⏳ Menyimpan…';
            const fd=new FormData(this); fd.append('_method','PUT');
            try {
                const res = await fetch(this.action, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:fd });
                const data = await res.json();
                if (res.ok && data.success) { closeModal('modal-edit'); showToast('success', data.message||'Data berhasil diperbarui!'); await refreshTableContent(); }
                else showToast(res.status===422?'warning':'error', data.message||'Terjadi kesalahan.');
            } catch(err) { showToast('error','Gagal terhubung ke server.'); }
            finally { btn.disabled=false; btn.innerHTML=orig; }
        });

        // ── Delete Confirmation ───────────────────────────────────────────────
        let _deleteUrl = null, _deleteRow = null;
        function confirmHapus(id, nama) {
            _deleteUrl = '{{ url("master/sempro") }}/' + id;
            _deleteRow = document.getElementById('row-sempro-' + id);
            document.getElementById('hapus-detail').textContent = 'Data "' + nama + '" akan dihapus secara permanen.';
            openModal('modal-hapus');
        }
        document.getElementById('btn-konfirmasi-hapus').addEventListener('click', async function() {
            if (!_deleteUrl) return;
            const btn=this; btn.disabled=true; btn.innerHTML='⏳ Menghapus…';
            try {
                const res = await fetch(_deleteUrl, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded'}, body:'_method=DELETE' });
                const data = await res.json();
                closeModal('modal-hapus');
                if (res.ok && data.success) {
                    if (_deleteRow) { _deleteRow.style.transition='opacity .3s'; _deleteRow.style.opacity='0'; setTimeout(()=>{ _deleteRow.remove(); refreshTableContent(); },300); }
                    showToast('success', data.message||'Data berhasil dihapus!');
                } else showToast('error', data.message||'Gagal menghapus data.');
            } catch(err) { closeModal('modal-hapus'); showToast('error','Gagal terhubung ke server.'); }
            finally { btn.disabled=false; btn.innerHTML='🗑️ Ya, Hapus'; _deleteUrl=null; _deleteRow=null; }
        });

        // ── Verifikasi Modal Setup ────────────────────────────────────────────
        function openVerifikasi(id, status, komentar, nama, nim, judul, dosbingUtama, dosbingPendamping, buktiBayarUrl) {
            document.getElementById('verif-title').innerText = '🛡️ Verifikasi: ' + nama;
            document.getElementById('verif-form').action = '{{ url("master/sidang") }}/' + id + '/verifikasi';
            document.getElementById('verif-status').value = status;
            document.getElementById('verif-komentar').value = komentar;

            // Populate student details
            document.getElementById('verif-detail-nim').innerText = nim;
            document.getElementById('verif-detail-nama').innerText = nama;
            document.getElementById('verif-detail-judul').innerText = judul;
            document.getElementById('verif-detail-dosbing-utama').innerText = dosbingUtama;
            document.getElementById('verif-detail-dosbing-pendamping').innerText = dosbingPendamping;

            const buktiContainer = document.getElementById('verif-detail-bukti-container');
            const buktiLink = document.getElementById('verif-detail-bukti');
            if (buktiBayarUrl) {
                buktiContainer.style.display = 'block';
                buktiLink.href = buktiBayarUrl;
            } else {
                buktiContainer.style.display = 'none';
            }
            
            toggleKomentarField();
            openModal('modal-verifikasi');
        }
        
        function toggleKomentarField() {
            const status = document.getElementById('verif-status').value;
            const commentGroup = document.getElementById('komentar-group');
            if (status === 'ditolak') {
                commentGroup.style.display = 'block';
                document.getElementById('verif-komentar').required = true;
            } else {
                commentGroup.style.display = 'none';
                document.getElementById('verif-komentar').required = false;
            }
        }

        // ── Verifikasi Form (AJAX) ────────────────────────────────────────────
        document.getElementById('verif-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'), orig = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '⏳ Memproses…';
            const fd = new FormData(this);
            try {
                const res = await fetch(this.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd });
                const data = await res.json();
                if (res.ok && data.success) {
                    closeModal('modal-verifikasi');
                    showToast('success', data.message || 'Verifikasi berhasil!');
                    await refreshTableContent();
                } else {
                    showToast(res.status === 422 ? 'warning' : 'error', data.message || 'Terjadi kesalahan.');
                }
            } catch(err) { showToast('error', 'Gagal terhubung ke server.'); }
            finally { btn.disabled = false; btn.innerHTML = orig; }
        });
    </script>
    @endpush

</x-app-layout>


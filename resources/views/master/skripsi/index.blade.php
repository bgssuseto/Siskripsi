<x-app-layout title="Data Skripsi">
    <x-slot:header>Data Skripsi</x-slot:header>

    <style>
        .data-page { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }

        /* Stats */
        .stat-card {
            background: #fff; border-radius: 16px; padding: 1.25rem 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #f1f5f9;
        }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
        .stat-icon.blue   { background: #eff6ff; }
        .stat-icon.violet { background: #f5f3ff; }
        .stat-icon.green  { background: #f0fdf4; }
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

        /* Badges */
        .badge { display: inline-flex; align-items: center; padding: .22rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 700; }
        .badge-skripsi { background: #dbeafe; color: #1d4ed8; }
        .badge-jurnal  { background: #dcfce7; color: #15803d; }
        .nim-pill { font-family: 'Courier New', monospace; background: #f1f5f9; color: #475569; padding: .15rem .5rem; border-radius: 6px; font-size: .78rem; font-weight: 600; }
        .judul-text { font-size: .8rem; color: #1e293b; font-weight: 500; line-height: 1.4; max-width: 280px; }
        .dosen-chip { display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 7px; padding: .1rem .45rem; font-size: .75rem; color: #475569; margin: .1rem 0; }
        .dosen-chip.utama { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; font-weight: 600; }
        .dosen-chip.ketua { background: #fef3c7; border-color: #fde68a; color: #92400e; font-weight: 600; }

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

        @media (max-width: 640px) { .form-grid-2 { grid-template-columns: 1fr; } }
    </style>

    <div class="data-page space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Data Skripsi</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola data mahasiswa sidang skripsi dan jurnal.</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('master.skripsi.import.form') }}" class="btn btn-success">
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
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div>
                    <div class="stat-value">{{ $totalSkripsi }}</div>
                    <div class="stat-label">Total Skripsi</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon violet">📰</div>
                <div>
                    <div class="stat-value">{{ $totalJurnal }}</div>
                    <div class="stat-label">Total Jurnal/Artikel</div>
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
        <form method="GET" action="{{ route('master.skripsi.index') }}" class="toolbar">
            <div class="toolbar-search">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NIM, nama, judul…">
            </div>

            <select name="jenis" class="filter-select">
                <option value="">Semua Jenis</option>
                <option value="skripsi" {{ request('jenis') == 'skripsi' ? 'selected' : '' }}>Skripsi</option>
                <option value="jurnal" {{ request('jenis') == 'jurnal' ? 'selected' : '' }}>Jurnal / Artikel</option>
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
            @if(request()->hasAny(['search','jenis','status','periode_id']))
                <a href="{{ route('master.skripsi.index') }}" class="btn btn-outline">✕ Reset</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="table-card">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:42px; text-align:center;">No</th>
                            <th style="width:90px;">Tgl Daftar</th>
                            <th>NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Judul Skripsi / TA</th>
                            <th>Status</th>
                            <th>Periode</th>
                            <th>Dosbing Utama</th>
                            <th>Dosbing Pendamping</th>
                            <th>Ketua Penguji</th>
                            <th>Penguji 1</th>
                            <th>Penguji 2</th>
                            <th>Jenis</th>
                            <th style="width:85px; text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sidangs as $item)
                            <tr>
                                <td style="text-align:center; color:#475569; font-weight:700; font-size:.78rem;">
                                    {{ ($sidangs->currentPage() - 1) * $sidangs->perPage() + $loop->iteration }}
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
                                <td style="font-weight:600; color:#1e293b; min-width:150px;">{{ $item->nama_mahasiswa }}</td>
                                <td><p class="judul-text">{{ $item->judul_skripsi }}</p></td>
                                <td>
                                    @if(empty($item->tanggal))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200 whitespace-nowrap">
                                            ● Belum Dijadwalkan
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
                                <td><span class="dosen-chip ketua">{{ $item->ketuaPenguji ? $item->ketuaPenguji->nama_dosen : '—' }}</span></td>
                                <td><span class="dosen-chip">{{ $item->anggotaPenguji1 ? $item->anggotaPenguji1->nama_dosen : '—' }}</span></td>
                                <td><span class="dosen-chip">{{ $item->anggotaPenguji2 ? $item->anggotaPenguji2->nama_dosen : '—' }}</span></td>
                                <td>
                                    @if($item->jenis_tugas_akhir === 'skripsi')
                                        <span class="badge badge-skripsi">Skripsi</span>
                                    @else
                                        <span class="badge badge-jurnal">Jurnal</span>
                                    @endif
                                </td>
                                <td class="text-right space-x-1 whitespace-nowrap">
                                    <button class="btn btn-outline btn-sm"
                                        onclick="openEdit({{ $item->id }}, {{ json_encode([
                                            'id'                             => $item->id,
                                            'nim'                            => $item->nim,
                                            'nama_mahasiswa'                 => $item->nama_mahasiswa,
                                            'judul_skripsi'                  => $item->judul_skripsi,
                                            'jenis_tugas_akhir'              => $item->jenis_tugas_akhir,
                                            'periode_id'                     => $item->periode_id,
                                            'tanggal_pendaftaran'            => $item->tanggal_pendaftaran?->format('Y-m-d'),
                                            'dosen_pembimbing_utama_id'      => $item->dosen_pembimbing_utama_id,
                                            'dosen_pembimbing_pendamping_id' => $item->dosen_pembimbing_pendamping_id,
                                            'ketua_penguji_id'               => $item->ketua_penguji_id,
                                            'anggota_penguji_1_id'           => $item->anggota_penguji_1_id,
                                            'anggota_penguji_2_id'           => $item->anggota_penguji_2_id,
                                        ]) }})">
                                        ✏️ Edit
                                    </button>
                                    <form method="POST" action="{{ route('master.skripsi.destroy', $item->id) }}" style="display:inline;"
                                          onsubmit="return confirm('Hapus data ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" style="text-align:center; padding:3rem; color:#94a3b8;">
                                    <div style="font-size:2.5rem; margin-bottom:.5rem;">📂</div>
                                    <div style="font-weight:700; color:#64748b;">Belum ada data skripsi</div>
                                    <div style="font-size:.82rem; margin-top:.25rem;">Import Excel atau tambah data secara manual.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($sidangs->hasPages())
            <div class="pagination-wrap">
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
                <span class="modal-title">➕ Tambah Data Skripsi</span>
                <button class="modal-close" onclick="closeModal('modal-tambah')">✕</button>
            </div>
            <form method="POST" action="{{ route('master.skripsi.store') }}" id="form-tambah">
                @csrf
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
                            <label>Judul Skripsi / TA <span style="color:red">*</span></label>
                            <textarea name="judul_skripsi" class="form-control" required placeholder="Judul skripsi / tugas akhir"></textarea>
                        </div>
                        <div class="form-grid-2 mt-3">
                            <div class="form-group">
                                <label>Periode Akademik <span style="color:red">*</span></label>
                                <select name="periode_id" class="form-control" required>
                                    @foreach ($periodes as $p)
                                        <option value="{{ $p->id }}" {{ $p->aktif ? 'selected' : '' }}>{{ $p->nama_periode }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Pendaftaran</label>
                                <input type="date" name="tanggal_pendaftaran" class="form-control">
                            </div>
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
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-tambah')" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary">💾 Simpan</button>
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
                <span class="modal-title">✏️ Edit Data Skripsi</span>
                <button class="modal-close" onclick="closeModal('modal-edit')">✕</button>
            </div>
            <form method="POST" id="form-edit">
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
                            <label>Judul Skripsi / TA <span style="color:red">*</span></label>
                            <textarea name="judul_skripsi" id="edit-judul" class="form-control" required></textarea>
                        </div>
                        <div class="form-grid-2 mt-3">
                            <div class="form-group">
                                <label>Periode Akademik <span style="color:red">*</span></label>
                                <select name="periode_id" id="edit-periode" class="form-control" required>
                                    @foreach ($periodes as $p)
                                        <option value="{{ $p->id }}">{{ $p->nama_periode }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Pendaftaran</label>
                                <input type="date" name="tanggal_pendaftaran" id="edit-tgl-daftar" class="form-control">
                            </div>
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
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-edit')" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        function closeOnOverlay(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }

        function openEdit(id, data) {
            const base = '{{ url("master/skripsi") }}/' + id;
            document.getElementById('form-edit').action = base;
            document.getElementById('edit-nim').value   = data.nim || '';
            document.getElementById('edit-nama').value  = data.nama_mahasiswa || '';
            document.getElementById('edit-judul').value = data.judul_skripsi || '';
            document.getElementById('edit-jenis').value = data.jenis_tugas_akhir || 'skripsi';
            document.getElementById('edit-tgl-daftar').value = data.tanggal_pendaftaran || '';
            setSelect('edit-periode',           data.periode_id);
            setSelect('edit-dosbing-utama',     data.dosen_pembimbing_utama_id);
            setSelect('edit-dosbing-pendamping',data.dosen_pembimbing_pendamping_id);
            setSelect('edit-ketua',             data.ketua_penguji_id);
            setSelect('edit-penguji1',          data.anggota_penguji_1_id);
            setSelect('edit-penguji2',          data.anggota_penguji_2_id);
            openModal('modal-edit');
        }

        function setSelect(id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        }
    </script>
    @endpush

</x-app-layout>

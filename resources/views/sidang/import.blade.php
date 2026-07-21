<x-app-layout title="Import Excel Jadwal Sidang">
    <x-slot:header>Import Excel</x-slot:header>

    <style>
        .import-page { max-width: 720px; margin: 0 auto; }

        .import-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            border: 1px solid #f1f5f9;
            overflow: hidden;
        }

        .import-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 2rem 2rem 1.5rem;
            color: #fff;
        }
        .import-header h1 {
            font-size: 1.4rem; font-weight: 800; margin: 0 0 .4rem;
        }
        .import-header p { font-size: .875rem; opacity: .85; margin: 0; }

        .import-body { padding: 1.75rem 2rem; }
        .import-footer {
            padding: 1rem 2rem;
            border-top: 1px solid #f1f5f9;
            display: flex; gap: .75rem; justify-content: flex-end;
        }

        /* Drop zone */
        .drop-zone {
            border: 2px dashed #c7d2fe;
            border-radius: 16px;
            background: #f5f3ff;
            padding: 2.5rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }
        .drop-zone:hover, .drop-zone.drag-over {
            border-color: #6366f1;
            background: #eef2ff;
        }
        .drop-zone input[type=file] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .drop-zone-icon { font-size: 2.5rem; margin-bottom: .75rem; display: block; }
        .drop-zone-title { font-weight: 700; color: #4338ca; font-size: 1rem; }
        .drop-zone-hint  { font-size: .8rem; color: #7c3aed; margin-top: .3rem; }
        .file-name-display {
            margin-top: .75rem; font-size: .82rem; color: #6366f1;
            font-weight: 600; display: none;
        }

        /* Info box */
        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 1rem 1.2rem;
            margin-top: 1.5rem;
        }
        .info-box h3 {
            font-size: .85rem; font-weight: 700; color: #0369a1;
            margin: 0 0 .65rem;
        }
        .info-box ul {
            list-style: none; padding: 0; margin: 0;
            display: flex; flex-direction: column; gap: .35rem;
        }
        .info-box li {
            font-size: .8rem; color: #0c4a6e;
            display: flex; align-items: flex-start; gap: .5rem;
        }
        .info-box li::before { content: '✓'; color: #0ea5e9; font-weight: 700; flex-shrink: 0; }

        /* Column table */
        .col-table {
            width: 100%; border-collapse: collapse;
            font-size: .78rem; margin-top: .65rem;
        }
        .col-table th {
            background: #e0f2fe; color: #0369a1;
            padding: .4rem .75rem; text-align: left;
            font-weight: 700; border-bottom: 1px solid #bae6fd;
        }
        .col-table td {
            padding: .35rem .75rem; color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .col-table tr:last-child td { border-bottom: none; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .6rem 1.2rem; border-radius: 10px; font-size: .875rem;
            font-weight: 600; cursor: pointer; transition: all .15s;
            border: none; text-decoration: none;
        }
        .btn-primary { background: #6366f1; color: #fff; }
        .btn-primary:hover { background: #4f46e5; box-shadow: 0 4px 14px rgba(99,102,241,.4); }
        .btn-outline { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
        .btn-outline:hover { background: #f8fafc; }

        /* Alert */
        .alert { border-radius: 12px; padding: .85rem 1.1rem; margin-bottom: 1rem; font-size: .875rem; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
    </style>

    <div class="import-page">
        {{-- Breadcrumb --}}
        <div style="margin-bottom:1rem;">
            <a href="{{ route('sidang.index') }}" style="color:#6366f1;font-size:.875rem;text-decoration:none;">
                ← Kembali ke Jadwal Sidang
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                <b>Gagal import:</b>
                <ul style="margin:.4rem 0 0 1rem;">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="import-card">
            {{-- Header --}}
            <div class="import-header">
                <h1>📥 Import Jadwal Sidang dari Excel</h1>
                <p>Upload file Excel (.xlsx) sesuai format yang sudah ditentukan. Data akan diproses secara otomatis.</p>
            </div>

            {{-- Body --}}
            <form method="POST" action="{{ route('sidang.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="import-body">

                    {{-- Drop Zone --}}
                    <div class="drop-zone" id="drop-zone">
                        <input type="file" name="excel_file" id="file-input" accept=".xlsx,.xls" required
                               onchange="updateFileName(this)">
                        <span class="drop-zone-icon">📊</span>
                        <div class="drop-zone-title">Klik atau seret file Excel ke sini</div>
                        <div class="drop-zone-hint">Format yang didukung: .xlsx, .xls</div>
                        <div class="file-name-display" id="file-name-display"></div>
                    </div>

                    {{-- Info sheet format --}}
                    <div class="info-box" style="margin-top:1.5rem;">
                        <h3>📋 Format Excel yang Diperlukan</h3>
                        <ul>
                            <li>File Excel harus memiliki 2 sheet: <strong>"Sidang"</strong> dan <strong>"Jurnal"</strong></li>
                            <li>Baris pertama adalah header, data dimulai dari baris ke-2</li>
                            <li>Kolom harus sesuai urutan di bawah ini</li>
                            <li>Data yang sudah ada (berdasarkan NIM) akan diperbarui secara otomatis</li>
                            <li>Kolom boleh kosong kecuali NIM, Nama, dan Judul Skripsi</li>
                        </ul>

                        <table class="col-table" style="margin-top:.85rem;">
                            <thead>
                                <tr>
                                    <th style="width:20%;">Dengan Kolom No</th>
                                    <th style="width:20%;">Tanpa Kolom No</th>
                                    <th>Nama Header</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>A</td><td>—</td><td>No</td><td>Opsional (diabaikan & tidak diimpor)</td></tr>
                                <tr><td>B</td><td>A</td><td>NIM</td><td>⚠ Wajib, unik per sheet</td></tr>
                                <tr><td>C</td><td>B</td><td>Nama</td><td>⚠ Wajib, huruf kapital</td></tr>
                                <tr><td>D</td><td>C</td><td>Judul Skripsi</td><td>⚠ Wajib</td></tr>
                                <tr><td>E</td><td>D</td><td>Dosbing Utama</td><td>Nama dosen pembimbing utama</td></tr>
                                <tr><td>F</td><td>E</td><td>Dosbing Pendamping</td><td>Opsional</td></tr>
                                <tr><td>G</td><td>F</td><td>Ketua Penguji</td><td>—</td></tr>
                                <tr><td>H</td><td>G</td><td>Penguji 1</td><td>—</td></tr>
                                <tr><td>I</td><td>H</td><td>Penguji 2</td><td>Opsional</td></tr>
                                <tr><td>J</td><td>I</td><td>Hari</td><td>Contoh: <em>Rabu, 15 Juli 2026</em></td></tr>
                                <tr><td>K</td><td>J</td><td>Jam</td><td>Contoh: <em>08.00 - 09.00</em></td></tr>
                                <tr><td>L</td><td>K</td><td>Ruangan</td><td>Contoh: <em>J.5.05</em></td></tr>
                                <tr><td>M</td><td>L</td><td>Tgl Daftar</td><td>Contoh: <em>Senin, 13 Juli 2026</em></td></tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Warning --}}
                    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:.85rem 1rem;margin-top:1rem;font-size:.82rem;color:#92400e;">
                        ⚠ <strong>Perhatian:</strong> Import akan memperbarui data yang sudah ada berdasarkan NIM + jenis (Sidang/Jurnal).
                        Data baru akan ditambahkan otomatis.
                    </div>
                </div>

                <div class="import-footer">
                    <a href="{{ route('sidang.index') }}" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Proses Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const display = document.getElementById('file-name-display');
            if (input.files.length > 0) {
                display.textContent = '✅ File: ' + input.files[0].name;
                display.style.display = 'block';
            } else {
                display.style.display = 'none';
            }
        }

        // Drag & drop visual feedback
        const zone = document.getElementById('drop-zone');
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', ()  => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', e => {
            e.preventDefault(); zone.classList.remove('drag-over');
            const input = document.getElementById('file-input');
            input.files = e.dataTransfer.files;
            updateFileName(input);
        });
    </script>
</x-app-layout>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Undangan Sidang Skripsi - {{ $dosen->nama_dosen }}</title>
    <style>
        /* ===================== PAGE ===================== */
        @page {
            margin-top: 15mm;
            margin-right: 15mm;
            margin-bottom: 15mm;
            margin-left: 15mm;
            size: a4 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.35;
        }

        /* ===================== HEADER ===================== */
        table.doc-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table.doc-header td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .brand-badge {
            display: inline-block;
            width: 36px;
            height: 36px;
            background-color: #4361ee;
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            border-radius: 8px;
            padding-top: 8px;
        }

        .brand-name {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.15;
        }

        .brand-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .doc-meta {
            text-align: right;
            font-size: 9px;
            color: #64748b;
            line-height: 1.7;
            white-space: nowrap;
        }

        .doc-meta strong {
            color: #1e293b;
            font-weight: bold;
        }

        .header-rule {
            border: none;
            border-top: 1.5px solid #4361ee;
            margin: 0 0 14px 0;
        }

        /* ===================== SECTION HEADING ===================== */
        .section-heading {
            font-size: 10px;
            font-weight: bold;
            color: #4361ee;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .section-rule {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: -3px 0 10px 0;
        }

        .section-block {
            margin-bottom: 14px;
        }

        /* ===================== DATA GRID (DOSEN) — 4 kolom sejajar ===================== */
        table.data-grid {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        table.data-grid td {
            border: none;
            padding: 0 12px 0 0;
            font-size: 10px;
            vertical-align: top;
            width: 25%;
        }

        table.data-grid td:last-child {
            padding-right: 0;
        }

        .grid-label {
            display: block;
            font-size: 9px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .grid-value {
            display: block;
            font-weight: bold;
            font-size: 10px;
            color: #0f172a;
        }

        .grid-value.accent {
            color: #4361ee;
        }

        /* ===================== TABEL UMUM ===================== */
        table.data-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }

        table.data-table thead th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: left;
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 6px;
            border-bottom: 1px solid #cbd5e1;
        }

        table.data-table tbody td {
            padding: 6px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            word-break: normal;
            word-wrap: break-word;
        }

        table.data-table tbody tr:last-child td {
            border-bottom: none;
        }

        table.data-table th.center, table.data-table td.center {
            text-align: center;
        }

        table.data-table td strong {
            color: #0f172a;
            font-weight: bold;
        }

        /* ===================== TABEL: REKAP HARI & RUANG ===================== */
        table.rekap-table th.col-jam, table.rekap-table td.col-jam,
        table.rekap-table th.col-ruang, table.rekap-table td.col-ruang {
            text-align: center;
        }

        /* ===================== FOOTER ===================== */
        .doc-footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- ============ HEADER ============ -->
    <table class="doc-header">
        <tr>
            <td style="width: 44px;">
                <div class="brand-badge">TI</div>
            </td>
            <td>
                <div class="brand-name">Sistem Informasi Tugas Akhir</div>
                <div class="brand-sub">Rekap Undangan {{ ($jenisUndangan ?? 'sempro') === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }} &mdash; Program Studi Teknik Informatika, Universitas Muria Kudus</div>
            </td>
            <td class="doc-meta" style="width: 210px;">
                Dicetak: <strong>{{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMM Y, HH:mm') }} WIB</strong><br>
                Periode: <strong>{{ strtoupper($namaPeriode) }}</strong>
            </td>
        </tr>
    </table>
    <hr class="header-rule">

    <!-- ============ DATA DOSEN ============ -->
    <div class="section-block">
        <div class="section-heading">Data Dosen Penguji / Pembimbing</div>
        <hr class="section-rule">
        <table class="data-grid">
            <tr>
                <td>
                    <span class="grid-label">Nama Lengkap</span>
                    <span class="grid-value">{{ $dosen->nama_dosen }}</span>
                </td>
                <td>
                    <span class="grid-label">NIDN</span>
                    <span class="grid-value">{{ $dosen->nidn ?? '-' }}</span>
                </td>
                <td>
                    <span class="grid-label">Jenis Kegiatan</span>
                    <span class="grid-value">{{ ($jenisUndangan ?? 'sempro') === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }}</span>
                </td>
                <td>
                    <span class="grid-label">Total Jumlah Uji</span>
                    <span class="grid-value accent">{{ $totalUji }} Mahasiswa</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- ============ TABEL 1: Rekap Hari & Ruang ============ -->
    <div class="section-block">
        <div class="section-heading">Rekap Hari &amp; Ruang</div>
        <hr class="section-rule">
        <table class="data-table rekap-table">
            <thead>
                <tr>
                    <th class="center" style="width: 5%;">No</th>
                    <th style="width: 35%;">Nama Dosen</th>
                    <th style="width: 30%;">Hari</th>
                    <th class="col-ruang" style="width: 15%;">Ruang</th>
                    <th class="col-jam" style="width: 15%;">Jam</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rekapSesi as $idx => $sesi)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td>{{ $dosen->nama_dosen }}</td>
                    <td>{{ $sesi['hari_tanggal'] }}</td>
                    <td class="col-ruang">{{ $sesi['ruang'] }}</td>
                    <td class="col-jam">{{ $sesi['jam'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="center" style="font-style: italic; color: #94a3b8;">Tidak ada sesi jadwal ujian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ============ TABEL 2: Daftar Mahasiswa Yang Diuji ============ -->
    <div class="section-block">
        <div class="section-heading">Daftar Mahasiswa yang Diuji</div>
        <hr class="section-rule">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="center" style="width: 3%;">No</th>
                    <th style="width: 14%;">Nama</th>
                    <th style="width: 19%;">Ketua Penguji</th>
                    <th style="width: 17%;">Penguji 1</th>
                    <th style="width: 17%;">Penguji 2</th>
                    <th style="width: 16%;">Hari</th>
                    <th class="center" style="width: 6%;">Jam</th>
                    <th class="center" style="width: 8%;">Ruang</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sidangs as $idx => $s)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $s->nama_mahasiswa }}</strong></td>
                    <td>{{ $s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : ($s->pembimbingUtama ? $s->pembimbingUtama->nama_dosen : '-') }}</td>
                    <td>{{ $s->anggotaPenguji1 ? $s->anggotaPenguji1->nama_dosen : ($s->pembimbingPendamping ? $s->pembimbingPendamping->nama_dosen : '-') }}</td>
                    <td>{{ $s->anggotaPenguji2 ? $s->anggotaPenguji2->nama_dosen : '-' }}</td>
                    <td>
                        @if($s->tanggal)
                            {{ \Carbon\Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="center">{{ $s->jam ?? '-' }}</td>
                    <td class="center">{{ $s->ruang ? $s->ruang->kode_ruangan : '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="center" style="font-style: italic; color: #94a3b8;">Tidak ada data mahasiswa.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ============ FOOTER ============ -->
    <div class="doc-footer">
        Dokumen ini dihasilkan otomatis oleh Sistem Informasi Tugas Akhir &mdash; Program Studi Teknik Informatika, Universitas Muria Kudus.
    </div>

</body>
</html>

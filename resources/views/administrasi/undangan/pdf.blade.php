<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Undangan Sidang Skripsi - {{ $dosen->nama_dosen }}</title>
    <style>
        @page {
            margin: 10mm 15mm 10mm 15mm;
            size: a4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.4;
        }

        /* ===================== HEADER ===================== */
        table.doc-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.doc-header td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .brand-badge {
            display: inline-block;
            width: 34px;
            height: 34px;
            background-color: #4361ee;
            color: #ffffff;
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            border-radius: 8px;
            padding-top: 7px;
        }

        .brand-name {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
        }

        .brand-sub {
            font-size: 8.5pt;
            color: #64748b;
        }

        .doc-meta {
            text-align: right;
            font-size: 8pt;
            color: #64748b;
            line-height: 1.6;
            white-space: nowrap;
        }

        .doc-meta strong {
            color: #1e293b;
        }

        .header-rule {
            border: none;
            border-top: 2.5px solid #4361ee;
            margin: 0 0 16px 0;
        }

        /* ===================== SECTION HEADING ===================== */
        .section-heading {
            font-size: 10pt;
            font-weight: bold;
            color: #4361ee;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
        }

        .section-rule {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: -4px 0 12px 0;
        }

        /* ===================== DATA GRID (DOSEN) ===================== */
        table.data-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        table.data-grid td {
            border: none;
            padding: 3px 10px 3px 0;
            font-size: 9.5pt;
            vertical-align: top;
            width: 25%;
        }

        .grid-label {
            display: block;
            font-size: 8pt;
            color: #64748b;
            margin-bottom: 1px;
        }

        .grid-value {
            font-weight: bold;
            color: #0f172a;
        }

        .grid-value.accent {
            color: #4361ee;
        }

        /* ===================== TABEL ===================== */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 8.3pt;
        }

        table.data-table th {
            font-weight: bold;
            text-align: left;
            color: #64748b;
            font-size: 7.3pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 0 5px 6px 5px;
            border-bottom: 1.5px solid #cbd5e1;
        }

        table.data-table td {
            padding: 6px 5px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        table.data-table th.center, table.data-table td.center {
            text-align: center;
        }

        table.data-table td strong {
            color: #0f172a;
        }

        /* ===================== FOOTER ===================== */
        .doc-footer {
            margin-top: 6px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 7.8pt;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- ============ HEADER ============ -->
    <table class="doc-header">
        <tr>
            <td style="width: 40px;">
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
    <div class="section-heading">Data Dosen Penguji / Pembimbing</div>
    <hr class="section-rule">
    <table class="data-grid">
        <tr>
            <td style="width: 40%;">
                <span class="grid-label">Nama Lengkap</span>
                <span class="grid-value">{{ $dosen->nama_dosen }}</span>
            </td>
            <td style="width: 20%;">
                <span class="grid-label">NIDN</span>
                <span class="grid-value">{{ $dosen->nidn ?? '-' }}</span>
            </td>
            <td style="width: 20%;">
                <span class="grid-label">Jenis Kegiatan</span>
                <span class="grid-value">{{ ($jenisUndangan ?? 'sempro') === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }}</span>
            </td>
            <td style="width: 20%;">
                <span class="grid-label">Total Jumlah Uji</span>
                <span class="grid-value accent">{{ $totalUji }} Mahasiswa</span>
            </td>
        </tr>
    </table>

    <!-- ============ TABEL 1: Rekap Hari & Ruang ============ -->
    <div class="section-heading">Rekap Hari &amp; Ruang</div>
    <hr class="section-rule">
    <table class="data-table">
        <thead>
            <tr>
                <th class="center" style="width: 5%;">No</th>
                <th style="width: 35%;">Nama Dosen</th>
                <th style="width: 30%;">Hari</th>
                <th style="width: 15%;">Ruang</th>
                <th style="width: 15%;">Jam</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekapSesi as $idx => $sesi)
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td>{{ $dosen->nama_dosen }}</td>
                <td>{{ $sesi['hari_tanggal'] }}</td>
                <td>{{ $sesi['ruang'] }}</td>
                <td>{{ $sesi['jam'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="center" style="font-style: italic; color: #94a3b8;">Tidak ada sesi jadwal ujian.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- ============ TABEL 2: Daftar Mahasiswa Yang Diuji ============ -->
    <div class="section-heading">Daftar Mahasiswa yang Diuji</div>
    <hr class="section-rule">
    <table class="data-table">
        <thead>
            <tr>
                <th class="center" style="width: 4%;">No</th>
                <th style="width: 15%;">Nama</th>
                <th style="width: 17%;">Ketua Penguji</th>
                <th style="width: 17%;">Penguji 1</th>
                <th style="width: 17%;">Penguji 2</th>
                <th style="width: 16%;">Hari</th>
                <th style="width: 9%;">Jam</th>
                <th style="width: 5%;">Ruang</th>
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
                <td>{{ $s->jam ?? '-' }}</td>
                <td>{{ $s->ruang ? $s->ruang->kode_ruangan : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="center" style="font-style: italic; color: #94a3b8;">Tidak ada data mahasiswa.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- ============ FOOTER ============ -->
    <div class="doc-footer">
        Dokumen ini dihasilkan otomatis oleh Sistem Informasi Tugas Akhir &mdash; Program Studi Teknik Informatika, Universitas Muria Kudus.
    </div>

</body>
</html>

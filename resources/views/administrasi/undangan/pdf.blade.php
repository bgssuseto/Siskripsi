<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Undangan Sidang Skripsi - {{ $dosen->nama_dosen }}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
            size: a4 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.3;
        }

        /* ===================== JUDUL ===================== */
        .doc-title-block {
            text-align: center;
            margin-bottom: 12px;
        }

        .doc-title-block h2 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* ===================== TABEL ===================== */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9.5pt;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: middle;
        }

        table.data-table th {
            font-weight: bold;
            text-align: left;
            background-color: #f2f2f2;
        }

        table.data-table td.center, table.data-table th.center {
            text-align: center;
        }

        /* ===================== SUMMARY ===================== */
        .summary-text {
            font-size: 10.5pt;
            font-weight: bold;
            margin: 10px 0 12px 0;
        }
    </style>
</head>
<body>

    <!-- ============ JUDUL DOKUMEN ============ -->
    <div class="doc-title-block">
        <h2>REKAP HARI DAN RUANG {{ ($jenisUndangan ?? 'sempro') === 'sempro' ? 'SEMINAR PROPOSAL' : 'SIDANG SKRIPSI' }} {{ strtoupper($namaPeriode) }}</h2>
    </div>

    <!-- ============ TABEL 1: Rekap Hari & Ruang ============ -->
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
                <td colspan="5" class="center" style="font-style: italic;">Tidak ada sesi jadwal ujian.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- ============ TOTAL JUMLAH UJI ============ -->
    <p class="summary-text">Total Jumlah Uji : {{ $totalUji }} Mahasiswa</p>

    <!-- ============ TABEL 2: Daftar Mahasiswa Yang Diuji ============ -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="center" style="width: 4%;">No</th>
                <th style="width: 20%;">Nama</th>
                <th style="width: 20%;">Ketua Penguji</th>
                <th style="width: 20%;">Penguji 1</th>
                <th style="width: 20%;">Penguji2</th>
                <th style="width: 16%;">Hari</th>
                <th style="width: 10%;">Jam</th>
                <th style="width: 8%;">Ruang</th>
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
                <td colspan="8" class="center" style="font-style: italic;">Tidak ada data mahasiswa.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>

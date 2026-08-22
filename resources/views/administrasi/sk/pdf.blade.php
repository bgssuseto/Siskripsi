<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rangkuman SK {{ $mode === 'pembimbing' ? 'Pembimbing' : 'Penguji' }} - {{ $namaPeriode }}</title>
    <style>
        @page {
            margin-top: 12mm;
            margin-right: 16mm;
            margin-bottom: 14mm;
            margin-left: 16mm;
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
            line-height: 1.4;
        }

        .kop-surat { width: 100%; display: block; }
        .kop-rule-thick { border: none; border-top: 2.5px solid #1e3a8a; margin: 6px 0 1.5px 0; }
        .kop-rule-thin { border: none; border-top: 1px solid #1e3a8a; margin: 0 0 12px 0; }

        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .doc-meta {
            text-align: center;
            font-size: 9.5px;
            color: #64748b;
            margin-bottom: 14px;
        }

        .section-heading {
            font-size: 10.5px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
        }

        .section-block { margin-bottom: 14px; }

        table.data-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: 1px solid #94a3b8;
            font-size: 9.5px;
        }

        table.data-table thead th {
            background-color: #1e3a8a;
            font-weight: bold;
            text-align: left;
            color: #ffffff;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 6px;
        }

        table.data-table tbody td {
            padding: 5px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            word-wrap: break-word;
        }

        table.data-table tbody tr:nth-child(even) { background-color: #f8fafc; }
        table.data-table tbody tr:last-child td { border-bottom: none; }
        table.data-table th.center, table.data-table td.center { text-align: center; }
        table.data-table td strong { color: #0f172a; font-weight: bold; }
        table.data-table tfoot td {
            padding: 6px 6px;
            font-weight: bold;
            background-color: #eef2ff;
            border-top: 1.5px solid #1e3a8a;
        }

        .doc-footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
            font-size: 8.5px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    @if($kopBase64)
        <img src="{{ $kopBase64 }}" class="kop-surat">
    @else
        <div style="text-align:center; font-weight:bold; font-size:14px; color:#1e3a8a;">UNIVERSITAS MURIA KUDUS</div>
        <div style="text-align:center; font-weight:bold; font-size:12px; color:#1e3a8a;">FAKULTAS TEKNIK — PROGRAM STUDI TEKNIK INFORMATIKA</div>
    @endif
    <hr class="kop-rule-thick">
    <hr class="kop-rule-thin">

    <div class="doc-title">
        Rangkuman Surat Keputusan (SK) Dekan &mdash; {{ $mode === 'pembimbing' ? 'Dosen Pembimbing Tugas Akhir' : 'Tim Dosen Penguji Ujian' }}
    </div>
    <div class="doc-meta">
        Periode Akademik: <strong>{{ strtoupper($namaPeriode) }}</strong> &nbsp;&bull;&nbsp;
        Digenerate: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB
    </div>

    <!-- ============ SECTION I: REKAPITULASI PER DOSEN ============ -->
    <div class="section-block">
        <div class="section-heading">
            I. Rekapitulasi Jumlah {{ $mode === 'pembimbing' ? 'Bimbingan' : 'Menguji' }} per Dosen
        </div>
        <table class="data-table">
            @if($mode === 'pembimbing')
                <thead>
                    <tr>
                        <th class="center" style="width: 4%;">No</th>
                        <th style="width: 14%;">NIDN</th>
                        <th style="width: 38%;">Nama Dosen Pembimbing</th>
                        <th class="center" style="width: 16%;">Pembimbing Utama</th>
                        <th class="center" style="width: 16%;">Pembimbing Pendamping</th>
                        <th class="center" style="width: 12%;">Total Bimbingan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dosenRekap as $idx => $row)
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td>{{ $row['nidn'] }}</td>
                        <td><strong>{{ $row['nama_dosen'] }}</strong></td>
                        <td class="center">{{ $row['utama'] }}</td>
                        <td class="center">{{ $row['pendamping'] }}</td>
                        <td class="center"><strong>{{ $row['total'] }}</strong></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="center" style="font-style: italic; color: #94a3b8;">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Total</td>
                        <td class="center">{{ $dosenRekap->sum('utama') }}</td>
                        <td class="center">{{ $dosenRekap->sum('pendamping') }}</td>
                        <td class="center">{{ $dosenRekap->sum('total') }}</td>
                    </tr>
                </tfoot>
            @else
                <thead>
                    <tr>
                        <th class="center" style="width: 4%;">No</th>
                        <th style="width: 13%;">NIDN</th>
                        <th style="width: 33%;">Nama Dosen Penguji</th>
                        <th class="center" style="width: 14%;">Ketua Penguji</th>
                        <th class="center" style="width: 14%;">Anggota Penguji 1</th>
                        <th class="center" style="width: 14%;">Anggota Penguji 2</th>
                        <th class="center" style="width: 8%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dosenRekap as $idx => $row)
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td>{{ $row['nidn'] }}</td>
                        <td><strong>{{ $row['nama_dosen'] }}</strong></td>
                        <td class="center">{{ $row['ketua'] }}</td>
                        <td class="center">{{ $row['anggota1'] }}</td>
                        <td class="center">{{ $row['anggota2'] }}</td>
                        <td class="center"><strong>{{ $row['total'] }}</strong></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="center" style="font-style: italic; color: #94a3b8;">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Total</td>
                        <td class="center">{{ $dosenRekap->sum('ketua') }}</td>
                        <td class="center">{{ $dosenRekap->sum('anggota1') }}</td>
                        <td class="center">{{ $dosenRekap->sum('anggota2') }}</td>
                        <td class="center">{{ $dosenRekap->sum('total') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <!-- ============ SECTION II: MASTER LIST ============ -->
    <div class="section-block">
        <div class="section-heading">
            II. Daftar Seluruh {{ $mode === 'pembimbing' ? 'Mahasiswa & Dosen Pembimbing' : 'Agenda Ujian & Tim Penguji' }}
        </div>
        <table class="data-table">
            @if($mode === 'pembimbing')
                <thead>
                    <tr>
                        <th class="center" style="width: 3%;">No</th>
                        <th style="width: 9%;">NIM</th>
                        <th style="width: 16%;">Nama Mahasiswa</th>
                        <th style="width: 27%;">Judul</th>
                        <th style="width: 9%;">Jenis TA</th>
                        <th style="width: 17%;">Pembimbing Utama</th>
                        <th style="width: 17%;">Pembimbing Pendamping</th>
                        <th class="center" style="width: 2%;">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sidangs as $idx => $s)
                        @php
                            $jenisStr = 'Seminar Proposal';
                            if (in_array($s->jenis_tugas_akhir, ['sidang', 'skripsi'])) $jenisStr = 'Sidang Skripsi';
                            elseif ($s->jenis_tugas_akhir === 'jurnal') $jenisStr = 'Jurnal';
                        @endphp
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td>{{ $s->nim }}</td>
                            <td><strong>{{ $s->nama_mahasiswa }}</strong></td>
                            <td>{{ $s->judul_skripsi }}</td>
                            <td>{{ $jenisStr }}</td>
                            <td>{{ $s->pembimbingUtama->nama_dosen ?? '-' }}</td>
                            <td>{{ $s->pembimbingPendamping->nama_dosen ?? '-' }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            @else
                <thead>
                    <tr>
                        <th class="center" style="width: 3%;">No</th>
                        <th style="width: 8%;">NIM</th>
                        <th style="width: 14%;">Nama Mahasiswa</th>
                        <th style="width: 8%;">Jenis</th>
                        <th style="width: 15%;">Ketua Penguji</th>
                        <th style="width: 14%;">Penguji 1</th>
                        <th style="width: 14%;">Penguji 2</th>
                        <th style="width: 13%;">Hari, Tanggal</th>
                        <th class="center" style="width: 6%;">Jam</th>
                        <th class="center" style="width: 5%;">Ruang</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sidangs as $idx => $s)
                        @php
                            $jenisStr = 'Sempro';
                            if (in_array($s->jenis_tugas_akhir, ['sidang', 'skripsi'])) $jenisStr = 'Skripsi';
                            elseif ($s->jenis_tugas_akhir === 'jurnal') $jenisStr = 'Jurnal';
                        @endphp
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td>{{ $s->nim }}</td>
                            <td><strong>{{ $s->nama_mahasiswa }}</strong></td>
                            <td>{{ $jenisStr }}</td>
                            <td>{{ $s->ketuaPenguji->nama_dosen ?? ($s->jenis_tugas_akhir === 'sempro' ? ($s->pembimbingUtama->nama_dosen ?? '-') : '-') }}</td>
                            <td>{{ $s->anggotaPenguji1->nama_dosen ?? ($s->jenis_tugas_akhir === 'sempro' ? ($s->pembimbingPendamping->nama_dosen ?? '-') : '-') }}</td>
                            <td>{{ $s->anggotaPenguji2->nama_dosen ?? '-' }}</td>
                            <td>{{ $s->tanggal ? \Carbon\Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMM Y') : 'Belum diplotting' }}</td>
                            <td class="center">{{ $s->jam ?? '-' }}</td>
                            <td class="center">{{ $s->ruang->kode_ruangan ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            @endif
        </table>
    </div>

    <div class="doc-footer">
        Dokumen ini dihasilkan otomatis oleh Sistem Informasi Tugas Akhir &mdash; Program Studi Teknik Informatika, Universitas Muria Kudus.
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Dosen Penguji - {{ $dosen ? $dosen->nama_dosen : 'Dosen Penguji' }}</title>
    <style>
        @page {
            margin-top: 1.5cm;
            margin-right: 1.5cm;
            margin-bottom: 1cm;
            margin-left: 2cm;
            size: a4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #111;
            line-height: 1.3;
        }

        .kop-header {
            text-align: center;
            margin-bottom: 10px;
        }

        .kop-header img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .doc-title-block {
            text-align: center;
            margin-bottom: 10px;
        }

        .doc-title-block h2 {
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-title-block p {
            font-size: 8.5pt;
            color: #333;
            margin-top: 2px;
            font-weight: bold;
        }

        .info-box {
            margin-bottom: 10px;
            font-size: 8.5pt;
            background-color: #fafafa;
            border: 1px solid #ccc;
            padding: 6px 10px;
            border-radius: 4px;
        }

        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-box td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .info-box td.label {
            font-weight: bold;
            width: 24%;
            color: #333;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8pt;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #333;
            padding: 4px 5px;
            vertical-align: middle;
        }

        table.data-table th {
            font-weight: bold;
            text-align: center;
            background-color: #e5e7eb;
            text-transform: uppercase;
            font-size: 7.5pt;
        }

        table.data-table td.center {
            text-align: center;
        }

        .summary-title {
            font-size: 9pt;
            font-weight: bold;
            margin: 8px 0 4px 0;
            text-transform: uppercase;
            border-bottom: 1.5px solid #333;
            padding-bottom: 2px;
        }

        .role-badge {
            font-weight: bold;
            color: #1e40af;
        }
    </style>
</head>
<body>

    <!-- ============ KOP SURAT ============ -->
    <div class="kop-header">
        @if(!empty($kopBase64))
            <img src="{{ $kopBase64 }}" alt="Kop Surat">
        @endif
    </div>

    <!-- ============ JUDUL DOKUMEN ============ -->
    <div class="doc-title-block">
        <h2>REKAP DOSEN PENGUJI {{ strtoupper($jenisTitle ?? 'SEMINAR PROPOSAL / SIDANG SKRIPSI') }}</h2>
        @if(!empty($gelombangTitle))
            <p>{{ strtoupper($gelombangTitle) }}</p>
        @elseif(!empty($periodeTitle))
            <p>{{ strtoupper($periodeTitle) }}</p>
        @endif
    </div>

    <!-- ============ INFORMASI DOSEN / GELOMBANG ============ -->
    @if($dosen)
    <div class="info-box">
        <table>
            <tr>
                <td class="label">Nama Dosen</td>
                <td>: <strong>{{ $dosen->nama_dosen }}</strong></td>
                <td class="label">NIDN / NIP</td>
                <td>: {{ $dosen->nidn ?? '-' }}</td>
            </tr>
            @if(!empty($gelombangTitle))
            <tr>
                <td class="label">Gelombang Pendaftaran</td>
                <td colspan="3">: <strong>{{ $gelombangTitle }}</strong></td>
            </tr>
            @elseif(!empty($periodeTitle))
            <tr>
                <td class="label">Periode Akademik</td>
                <td colspan="3">: {{ $periodeTitle }}</td>
            </tr>
            @endif
            @if($tglMulai || $tglSelesai)
            <tr>
                <td class="label">Rentang Pendaftaran</td>
                <td colspan="3">: 
                    {{ $tglMulai ? \Carbon\Carbon::parse($tglMulai)->locale('id')->isoFormat('D MMMM Y') : 'Awal' }} 
                    s.d. 
                    {{ $tglSelesai ? \Carbon\Carbon::parse($tglSelesai)->locale('id')->isoFormat('D MMMM Y') : 'Sekarang' }}
                </td>
            </tr>
            @endif
            <tr>
                <td class="label">Total Mahasiswa Diuji</td>
                <td colspan="3">: <strong>{{ $sidangs->count() }} Mahasiswa</strong></td>
            </tr>
        </table>
    </div>
    @endif

    <!-- ============ TABEL 1: Ringkasan Hari & Ruang Menguji ============ -->
    @if(!empty($rekapSesi) && count($rekapSesi) > 0)
    <div class="summary-title">Ringkasan Hari &amp; Ruang Menguji</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Hari &amp; Tanggal</th>
                <th style="width: 25%;">Ruang Ujian</th>
                <th style="width: 20%;">Jam Ujian</th>
                <th style="width: 15%;">Jumlah Mahasiswa</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rekapSesi as $idx => $sesi)
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td>{{ $sesi['hari_tanggal'] }}</td>
                <td class="center">{{ $sesi['ruang'] }}</td>
                <td class="center">{{ $sesi['jam'] }}</td>
                <td class="center">{{ $sesi['jumlah'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- ============ TABEL 2: Rekap Dosen Penguji (Format Sheet Rekap Dosen Penguji) ============ -->
    <div class="summary-title">Daftar Rekap Dosen Penguji (Total: {{ $sidangs->count() }} Mahasiswa)</div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">NIM</th>
                <th style="width: 17%;">Nama Mahasiswa</th>
                <th style="width: 11%;">Peran</th>
                <th style="width: 14%;">Ketua Penguji</th>
                <th style="width: 14%;">Penguji 1</th>
                <th style="width: 14%;">Penguji 2</th>
                <th style="width: 10%;">Hari</th>
                <th style="width: 6%;">Jam</th>
                <th style="width: 0%;">Ruang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sidangs as $idx => $s)
                @php
                    $role = '-';
                    if ($dosen) {
                        if ($s->ketua_penguji_id == $dosen->id) {
                            $role = 'Ketua Penguji';
                        } elseif ($s->anggota_penguji_1_id == $dosen->id) {
                            $role = 'Penguji 1';
                        } elseif ($s->anggota_penguji_2_id == $dosen->id) {
                            $role = 'Penguji 2';
                        }
                    }
                    $ketuaNama = $s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : ($s->pembimbingUtama ? $s->pembimbingUtama->nama_dosen : '-');
                    $penguji1Nama = $s->anggotaPenguji1 ? $s->anggotaPenguji1->nama_dosen : ($s->pembimbingPendamping ? $s->pembimbingPendamping->nama_dosen : '-');
                    $penguji2Nama = $s->anggotaPenguji2 ? $s->anggotaPenguji2->nama_dosen : '-';
                @endphp
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td class="center">{{ $s->nim }}</td>
                <td><strong>{{ $s->nama_mahasiswa }}</strong></td>
                <td class="center role-badge">{{ $role }}</td>
                <td>{{ $ketuaNama }}</td>
                <td>{{ $penguji1Nama }}</td>
                <td>{{ $penguji2Nama }}</td>
                <td class="center">
                    @if($s->tanggal)
                        {{ \Carbon\Carbon::parse($s->tanggal)->locale('id')->isoFormat('D/MM/Y') }}
                    @else
                        <span style="color:#d97706;">Belum Plot</span>
                    @endif
                </td>
                <td class="center">{{ $s->jam ?? '-' }}</td>
                <td class="center">{{ $s->ruang ? $s->ruang->kode_ruangan : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="center" style="font-style: italic;">Tidak ada data mahasiswa diuji pada gelombang ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>

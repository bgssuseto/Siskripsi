<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Hari dan Ruang Sidang Skripsi - {{ $dosen->nama_dosen }}</title>
    <style>
        @page {
            margin: 25.4mm 25.4mm 25.4mm 25.4mm;
            size: a4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
            color: #000;
            line-height: 1.3;
        }

        /* ── KOP SURAT ── */
        .kop-wrapper {
            width: 100%;
            margin-bottom: 0;
        }
        .kop-wrapper img {
            width: 100%;
            height: auto;
            display: block;
        }
        .kop-divider-thick {
            border: none;
            border-top: 3px solid #000;
            margin: 4px 0 0;
        }
        .kop-divider-thin {
            border: none;
            border-top: 1px solid #000;
            margin: 2px 0 14px;
        }

        /* ── JUDUL DOKUMEN ── */
        .doc-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.3px;
        }

        /* ── TABEL REKAP (Tabel 1) ── */
        table.tbl-rekap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            font-size: 10pt;
        }

        table.tbl-rekap th {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            font-weight: bold;
            background-color: #fff;
            vertical-align: middle;
        }

        table.tbl-rekap td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }

        table.tbl-rekap td.center {
            text-align: center;
        }

        /* ── TOTAL SUMMARY ── */
        .total-summary {
            font-size: 10.5pt;
            font-weight: bold;
            margin: 6px 0 12px 0;
        }

        /* ── TABEL DETAIL (Tabel 2) ── */
        table.tbl-detail {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        table.tbl-detail th {
            border: 1px solid #000;
            padding: 4px 5px;
            text-align: center;
            font-weight: bold;
            background-color: #fff;
            vertical-align: middle;
        }

        table.tbl-detail td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: top;
        }

        table.tbl-detail td.center {
            text-align: center;
            vertical-align: middle;
        }

        table.tbl-detail td.nama-col {
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    @php
        $kopPath = public_path('images/kop_surat.png');
        $kopBase64 = file_exists($kopPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($kopPath))
            : null;
    @endphp

    {{-- ============ KOP SURAT ============ --}}
    @if($kopBase64)
        <div class="kop-wrapper">
            <img src="{{ $kopBase64 }}" alt="Kop Surat UMK">
        </div>
        <hr class="kop-divider-thick">
        <hr class="kop-divider-thin">
    @endif

    {{-- ============ JUDUL DOKUMEN ============ --}}
    <div class="doc-title">
        REKAP HARI DAN RUANG SIDANG SKRIPSI {{ strtoupper($namaPeriode ?? '') }}
    </div>

    {{-- ============ TABEL 1: Rekap Hari & Ruang ============ --}}
    <table class="tbl-rekap">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 33%;">Nama Dosen</th>
                <th style="width: 30%;">Hari</th>
                <th style="width: 13%;">Ruang</th>
                <th style="width: 19%;">Jam</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekapPerHariRuang as $idx => $item)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td>{{ $dosen->nama_dosen }}</td>
                    <td>{{ $item['hari_tanggal'] }}</td>
                    <td class="center">{{ $item['ruang'] }}</td>
                    <td class="center">{{ $item['jam'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center" style="font-style: italic; padding: 8px;">
                        Tidak ada jadwal ujian yang terplotting.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ============ TOTAL JUMLAH MAHASISWA ============ --}}
    <div class="total-summary">
        Total Jumlah Uji : {{ $totalMahasiswa }} Mahasiswa
    </div>

    {{-- ============ TABEL 2: Detail Mahasiswa Ujian ============ --}}
    <table class="tbl-detail">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 16%;">Nama</th>
                <th style="width: 17%;">Ketua Penguji</th>
                <th style="width: 17%;">Penguji 1</th>
                <th style="width: 17%;">Penguji2</th>
                <th style="width: 14%;">Hari</th>
                <th style="width: 9%;">Jam</th>
                <th style="width: 6%;">Ruang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detailSidangs as $idx => $s)
                @php
                    $hariNama = $s->tanggal
                        ? \Carbon\Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y')
                        : '-';
                    $ruangKode = $s->ruang ? $s->ruang->kode_ruangan : '-';
                @endphp
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td class="nama-col">{{ strtoupper($s->nama_mahasiswa) }}</td>
                    <td>{{ $s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : '-' }}</td>
                    <td>{{ $s->anggotaPenguji1 ? $s->anggotaPenguji1->nama_dosen : '-' }}</td>
                    <td>{{ $s->anggotaPenguji2 ? $s->anggotaPenguji2->nama_dosen : '-' }}</td>
                    <td>{{ $hariNama }}</td>
                    <td class="center">{{ $s->jam ?? '-' }}</td>
                    <td class="center">{{ $ruangKode }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center" style="font-style: italic; padding: 8px;">
                        Tidak ada data ujian yang terplotting.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>

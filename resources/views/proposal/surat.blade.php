<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Pengumpulan Proposal - {{ $s->nim }}</title>
    <style>
        @page { margin: 14mm 18mm 15mm 16mm; size: a4 portrait; }

        body {
            font-family: 'helvetica', sans-serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.45;
            margin: 0;
        }

        .kop { border-collapse: collapse; margin-left: 2mm; }
        .kop td { padding: 0; vertical-align: middle; }
        .kop .logo img { width: 30mm; height: auto; display: block; }
        .kop .teks { padding-left: 6mm; font-family: 'times', serif; font-size: 12pt; line-height: 1.35; }
        .kop .teks b { font-size: 12.5pt; }

        .judul { text-align: center; margin-top: 10mm; }
        .judul .t { font-family: 'times', serif; font-weight: bold; text-decoration: underline; font-size: 12.5pt; }

        .isi { margin-top: 9mm; }
        .isi p { margin: 0; }

        .data { border-collapse: collapse; margin-top: 1mm; }
        .data td { padding: 0; vertical-align: top; }
        .data .l { width: 34mm; }
        .data .c { width: 8mm; }
        .data .v { width: 70mm; }
        .data .judul-v { padding: 1mm 0; }

        .ttd { margin: 10mm 0 0 106mm; white-space: nowrap; }
        .ttd p { margin: 0; }
        .ttd .qr { width: 26mm; height: 26mm; margin: 2mm 0; display: block; }
        .ttd .ruang-kosong { height: 26mm; }

        .catatan-tte {
            position: fixed; bottom: 0; left: 0; right: 0;
            border-top: 1px solid #999; padding-top: 5px;
            font-size: 8pt; color: #444; font-style: italic; line-height: 1.35;
        }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td class="logo">@if($logoBase64)<img src="{{ $logoBase64 }}" alt="UMK">@endif</td>
            <td class="teks">
                <b>PROGRAM STUDI TEKNIK INFORMATIKA</b><br>
                <b>FAKULTAS TEKNIK</b><br>
                <b>UNIVERSITAS MURIA KUDUS</b><br>
                Kampus UMK Gondangmanis PO.BOX 53 Bae<br>
                Telp (0291) 443844 Fax. (0291) 4230860
            </td>
        </tr>
    </table>

    <div class="judul">
        <div class="t">SURAT KETERANGAN</div>
        <div>{{ $p->nomor_surat_lengkap }}</div>
    </div>

    <div class="isi">
        <p>Berdasarkan data skripsi Teknik Informatika Universitas Muria Kudus, menerangkan<br>bahwa :</p>

        <table class="data">
            <tr><td class="l">Nama</td><td class="c">:</td><td class="v">{{ $s->nama_mahasiswa }}</td></tr>
            <tr><td class="l">NIM</td><td class="c">:</td><td class="v">{{ $s->nim }}</td></tr>
            <tr><td class="l" style="vertical-align: middle;">Judul</td><td class="c" style="vertical-align: middle;">:</td><td class="v judul-v">{{ $s->judul_skripsi }}</td></tr>
            <tr><td class="l">Pembimbing 1</td><td class="c">:</td><td class="v">{{ $s->pembimbingUtama?->nama_dosen ?? '-' }}</td></tr>
            <tr><td class="l">Pembimbing 2</td><td class="c">:</td><td class="v">{{ $s->pembimbingPendamping?->nama_dosen ?? '-' }}</td></tr>
        </table>

        <p style="margin-top: 7mm;">Mahasiswa tersebut dinyatakan sudah menyerahkan Proposal Skripsi kepada Koordinator Skripsi.</p>
        <p>Demikian surat keterangan ini dikeluarkan agar dapat digunakan sebagaimana mestinya.</p>

        <div class="ttd">
            <p>Kudus, &nbsp;&nbsp;&nbsp;&nbsp;{{ $tanggal }}</p>
            <p>Koordinator Skripsi</p>
            @if($qr)
                <img class="qr" src="{{ $qr }}" alt="QR Tanda Tangan Elektronik">
            @else
                <div class="ruang-kosong"></div>
            @endif
            <p>{{ $p->nama_penandatangan }}</p>
            @if($p->nidn_penandatangan)<p>NIDN : {{ $p->nidn_penandatangan }}</p>@endif
        </div>
    </div>

    @if($qr)
    <div class="catatan-tte">
        Dokumen ini telah ditandatangani secara elektronik oleh Koordinator Skripsi Program Studi Teknik Informatika Universitas Muria Kudus.
        Keaslian dan keabsahan surat dapat diverifikasi dengan memindai QR Code di atas.
    </div>
    @endif
</body>
</html>

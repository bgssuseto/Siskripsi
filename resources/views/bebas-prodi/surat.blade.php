<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Bebas Prodi - {{ $p->nim }}</title>
    <style>
        @page { margin: 12mm 18mm 15mm 18mm; size: a4 portrait; }

        body {
            font-family: 'times', serif;
            font-size: 12pt;
            color: #000;
            line-height: 1.55;
            margin: 0;
        }

        .kop img { width: 82%; height: auto; display: block; margin-left: -4mm; }

        .judul { text-align: center; margin-top: 18px; }
        .judul .t { font-weight: bold; text-decoration: underline; }

        .isi { margin: 26px 0 0 4mm; }
        .isi p { margin: 0; }

        .data { border-collapse: collapse; margin: 0; }
        .data td { padding: 0; vertical-align: top; }
        .data .l { width: 28mm; }
        .data .c { width: 6mm; text-align: center; }

        .ceklis { border-collapse: collapse; margin: 18px 0 18px 22mm; }
        .ceklis td { padding: 1px 0; vertical-align: middle; }
        .box { width: 14px; height: 14px; display: block; }
        .ceklis .txt { padding-left: 9mm; }

        .ttd { margin: 18px 0 0 96mm; white-space: nowrap; }
        .ttd p { margin: 0; }
        .ttd .ruang { height: 70px; position: relative; }
        .ttd .ruang img { height: 70px; width: auto; }
    </style>
</head>
<body>
@php
    // Kotak centang abu-abu ala Google Sheets (SVG — glyph ✓ tidak tersedia di font Times dompdf)
    $checkbox = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14"><rect width="14" height="14" rx="2" fill="#6b6b6b"/><path d="M3.2 7.2l2.6 2.6 5-5.2" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>');
@endphp
    <div class="kop">
        @if($kopBase64)<img src="{{ $kopBase64 }}" alt="Kop Surat">@endif
    </div>

    <div class="judul">
        <div class="t">SURAT KETERANGAN BEBAS PRODI</div>
        <div>NO:{{ $p->nomor_surat_lengkap }}</div>
    </div>

    <div class="isi">
        <p>Berdasarkan data pada Program Studi Teknik Informatika Universitas Muria Kudus,<br>menerangkan dibawah ini:</p>

        <table class="data">
            <tr><td class="l">Nama</td><td class="c">:</td><td>{{ $nama }}</td></tr>
            <tr><td class="l">Nim</td><td class="c">:</td><td>{{ $p->nim }}</td></tr>
            <tr><td class="l">Program Studi</td><td class="c">:</td><td>Teknik Informatika</td></tr>
            <tr><td class="l">Fakultas</td><td class="c">:</td><td>Teknik</td></tr>
        </table>

        <p style="margin-top: 20px;">Terhitung pada tanggal {{ $tanggal }} dinyatakan tidak memiliki tanggungan administrasi<br>pada Prodi Teknik Informatika Universitas Muria Kudus.</p>

        <table class="ceklis">
            @foreach(\App\Models\BebasProdi::POIN_SURAT as $poin)
                <tr><td><img class="box" src="{{ $checkbox }}" alt=""></td><td class="txt">{{ $poin }}</td></tr>
            @endforeach
        </table>

        <p>Demikian surat keterangan ini dikeluarkan untuk dipergunakan sebaiknya.</p>

        <div class="ttd">
            <p>Kudus, {{ $tanggal }}</p>
            <p>Ka. Prodi Teknik Informatika</p>
            <div class="ruang">@if($ttdBase64)<img src="{{ $ttdBase64 }}" alt="TTD">@endif</div>
            <p>{{ $kaprodi }}</p>
        </div>
    </div>
</body>
</html>

{{--
    Permohonan SK Pembimbing / Penguji — mengikuti format "PENGAJUAN SK PENGUJI GANJIL2526.docx":
      1. Surat permohonan ke Dekan (Nomor, Lampiran, Hal, isi, tanda tangan Ka. Prodi)
      2. LAMPIRAN 1 — rekap jumlah per dosen
      3. Rincian per dosen (a, b, c, …), satu dosen per halaman
    Kop surat diulang di setiap halaman.

    Variabel: $mode ('pembimbing'|'penguji'), $namaPeriode, $kegiatan, $dosenRincian,
              $tanggalSurat, $kaprodi, $kopBase64, $sementara (opsional: SK sementara, belum final)
--}}
@php
    $isPenguji  = $mode === 'penguji';
    $peranLabel = $isPenguji ? 'Penguji' : 'Pembimbing';
    $kolom      = $isPenguji ? ['Ketua Penguji', 'Anggota Penguji'] : ['Pembimbing Utama', 'Pembimbing Pendamping'];
    $jumlahLampiran = 1 + count($dosenRincian);
    $periodeTeks = \Illuminate\Support\Str::startsWith(strtolower($namaPeriode), 'semester') ? $namaPeriode : 'Periode ' . $namaPeriode;
    $huruf = fn (int $i) => $i < 26 ? chr(97 + $i) : chr(97 + intdiv($i, 26) - 1) . chr(97 + $i % 26);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Permohonan SK {{ $peranLabel }} - {{ $namaPeriode }}</title>
    <style>
        @page { margin: 50mm 20mm 18mm 25mm; size: a4 portrait; }
        body * { margin: 0; padding: 0; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #000; line-height: 1.5; }

        .kop { position: fixed; top: -47mm; left: -25mm; width: 150mm; }
        .kop img { width: 150mm; height: auto; }

        .page-break { page-break-before: always; }

        .meta td { padding: 1px 0; vertical-align: top; }
        .meta .label { width: 28mm; }
        .p { text-align: justify; line-height: 1.85; }
        .ttd { margin-left: 82mm; white-space: nowrap; }

        .judul-lampiran { font-weight: bold; margin-bottom: 14px; }
        .sub { margin: 0 0 12px 8mm; font-family: 'Helvetica', Arial, sans-serif; font-size: 11pt; }

        table.rekap { width: 100%; border-collapse: collapse; }
        table.rekap th, table.rekap td { border: 1px solid #000; }
        table.rekap th { font-weight: bold; text-align: center; vertical-align: middle; padding: 10px 6px; line-height: 1.15; }
        table.rekap td { padding: 2px 6px; font-family: 'Helvetica', Arial, sans-serif; font-size: 10pt; line-height: 1.25; }
        table.rekap td.n { text-align: right; }

        .dosen-nama { margin: 0 0 2px 4mm; font-size: 11.5pt; }
        table.rincian { width: 92%; margin-left: 8%; border-collapse: collapse; font-size: 11.5pt; }
        table.rincian th, table.rincian td { border: 1px solid #000; padding: 1px 6px; line-height: 1.15; }
        table.rincian th { font-weight: bold; text-align: center; }
        table.rincian td.no { text-align: center; font-family: 'Helvetica', Arial, sans-serif; font-size: 9.5pt; width: 6%; }
        table.rincian td.nim { text-align: right; width: 16%; }
        table.rincian td.nama { width: 44%; }
        table.rincian td.peran { width: 34%; font-size: 10.5pt; white-space: nowrap; }
        table.rincian tr { page-break-inside: avoid; }

        /* SK SEMENTARA: watermark diagonal di setiap halaman + kotak catatan */
        .watermark { position: fixed; top: 95mm; left: -10mm; width: 190mm; text-align: center;
                     font-family: 'Helvetica', Arial, sans-serif; font-size: 78pt; font-weight: bold;
                     color: #dc2626; opacity: 0.11; transform: rotate(-35deg); letter-spacing: 6px; }
        .catatan-sementara { border: 1.5px solid #dc2626; background: #fef2f2; color: #991b1b; padding: 7px 10px;
                             margin-bottom: 12px; font-family: 'Helvetica', Arial, sans-serif; font-size: 9.5pt; line-height: 1.4; }
        .catatan-kaki { position: fixed; bottom: -12mm; left: 0; right: 0; text-align: center;
                        font-family: 'Helvetica', Arial, sans-serif; font-size: 8pt; color: #b91c1c; }
    </style>
</head>
<body>
    @if(!empty($kopBase64))
        <div class="kop"><img src="{{ $kopBase64 }}" alt="Kop Surat"></div>
    @endif

    @if(!empty($sementara))
        <div class="watermark">SEMENTARA</div>
        <div class="catatan-kaki">SK SEMENTARA — belum final, data dapat bertambah hingga gelombang terakhir {{ $periodeTeks }} selesai.</div>
        <div class="catatan-sementara">
            <strong>CATATAN: SK SEMENTARA (BELUM FINAL).</strong>
            Dokumen ini diterbitkan sebelum seluruh gelombang sidang {{ $periodeTeks }} selesai, sehingga daftar
            mahasiswa bimbingan masih dapat bertambah atau berubah. Gunakan hanya sebagai rujukan sementara —
            SK final dapat diunduh kembali setelah gelombang terakhir selesai / semester berakhir.
        </div>
    @endif

    {{-- ═══════════════ SURAT PERMOHONAN ═══════════════ --}}
    <table class="meta" style="width: 100%;">
        <tr>
            <td class="label">Nomor</td>
            <td>:</td>
            <td style="text-align: right;">Kudus, {{ $tanggalSurat }}</td>
        </tr>
        <tr><td class="label">Lampiran</td><td colspan="2">: {{ $jumlahLampiran }} lembar</td></tr>
        <tr><td class="label">Hal</td><td colspan="2">: Permohonan SK <strong>{{ $peranLabel }}</strong> {{ $kegiatan['hal'] }} Mahasiswa</td></tr>
    </table>

    <div style="margin-top: 22px;">
        Kepada Yth.<br>
        Dekan Fakultas Teknik<br>
        Universitas Muria Kudus<br>
        Di Tempat
    </div>

    <p class="p" style="margin-top: 22px;">
        Sehubungan dengan dilaksanakannya kegiatan {{ $kegiatan['nama'] }} Kepada Mahasiswa pada {{ $periodeTeks }}
        di Program Studi Teknik Informatika, bersama surat ini kami mengajukan permohonan agar dibuatkan SK untuk
        {{ $peranLabel }} {{ $kegiatan['nama'] }}. Adapun daftar nama dosen, nama mahasiswa dan jumlah
        {{ $isPenguji ? 'mahasiswa yang diuji' : 'bimbingan' }} terlampir.
    </p>

    <p class="p" style="margin-top: 16px;">Demikian surat ini kami sampaikan, atas perhatian dan kerjasamanya diucapkan terimakasih.</p>

    <div class="ttd" style="margin-top: 46px;">
        Mengetahui,<br>
        Ka. Prodi Teknik Informatika
        <div style="height: 80px;"></div>
        {{ $kaprodi ?? '..........................................' }}
    </div>

    {{-- ═══════════════ LAMPIRAN 1: REKAP ═══════════════ --}}
    <div class="page-break"></div>
    <div class="judul-lampiran">LAMPIRAN 1</div>
    <div class="sub">1.&nbsp;&nbsp; Jumlah dosen {{ strtolower($peranLabel) }} {{ strtolower($kegiatan['nama']) }} {{ $periodeTeks }}</div>

    <table class="rekap">
        <tr>
            <th style="width: 50%;">{{ $peranLabel }}</th>
            <th style="width: 19%;">{{ $kolom[0] }}</th>
            <th style="width: 19%;">{{ $kolom[1] }}</th>
            <th style="width: 12%;">Total</th>
        </tr>
        @foreach($dosenRincian as $d)
            <tr>
                <td>{{ $d['nama_dosen'] }}</td>
                <td class="n">{{ $d['jumlah'][0] }}</td>
                <td class="n">{{ $d['jumlah'][1] }}</td>
                <td class="n">{{ $d['jumlah'][0] + $d['jumlah'][1] }}</td>
            </tr>
        @endforeach
    </table>

    {{-- ═══════════════ RINCIAN PER DOSEN ═══════════════ --}}
    @foreach($dosenRincian as $i => $d)
        <div class="page-break"></div>
        @if($loop->first)
            <div class="sub" style="margin-bottom: 18px;">2.&nbsp;&nbsp; Rincian {{ strtolower($peranLabel) }}:</div>
        @endif

        <div class="dosen-nama">{{ $huruf($i) }})&nbsp;&nbsp; {{ $d['nama_dosen'] }}</div>
        <table class="rincian">
            <thead>
                <tr><th>No</th><th>NIS/NIM</th><th>Nama Lengkap</th><th>{{ $peranLabel }}</th></tr>
            </thead>
            <tbody>
                @foreach($d['mahasiswa'] as $m)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td class="nim">{{ $m['nim'] }}</td>
                        <td class="nama">{{ strtoupper($m['nama']) }}</td>
                        <td class="peran">{{ $m['peran'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Sidang - {{ count($sidangs) === 1 ? $sidangs->first()->nama_mahasiswa : 'Massal' }}</title>
    <style>
        @page {
            margin: 15mm 20mm 15mm 25mm;
            size: a4 portrait;
        }

        body * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.4;
        }

        /* ── KOP SURAT ── */
        .kop-wrapper {
            width: 100%;
            text-align: center;
            margin-bottom: 0;
        }
        .kop-wrapper img {
            width: 100%;
            height: auto;
            display: block;
        }
        .kop-divider-thick { border: none; border-top: 3px solid #000; margin: 4px 0 0; }
        .kop-divider-thin  { border: none; border-top: 1px solid #000; margin: 2px 0 10px; }

        /* ── TITLES ── */
        .doc-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0 14px;
            letter-spacing: 0.5px;
        }

        /* ── FIELD ROWS ── */
        .field-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 11pt;
        }
        .field-table td {
            vertical-align: top;
            padding: 3px 0;
            border: none;
        }
        .field-label { width: 38%; font-weight: normal; }
        .field-colon { width: 3%; text-align: center; }
        .field-value { width: 59%; }

        /* ── RESULT PARAGRAPH ── */
        .result-para {
            margin: 12px 0 10px;
            text-align: justify;
            line-height: 1.5;
        }

        /* ── SIGNATURE TABLE ── */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .sign-table td {
            text-align: center;
            vertical-align: bottom;
            padding: 6px 4px;
            border: none;
            font-size: 11pt;
        }
        .sign-line {
            margin-top: 50px;
            border-top: 1px solid #000;
            width: 85%;
            margin-left: auto;
            margin-right: auto;
        }
        .sign-name {
            margin-top: 4px;
            font-size: 10.5pt;
        }

        /* ── PENILAIAN TABLE ── */
        .penilaian-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 14px 0 12px;
        }
        .penilaian-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 11pt;
        }
        .penilaian-table th,
        .penilaian-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            vertical-align: middle;
        }
        .penilaian-table th {
            text-align: center;
            font-weight: bold;
            background: #f8f9fa;
        }
        .penilaian-table td:nth-child(2) { text-align: center; }
        .penilaian-footer {
            text-align: right;
            margin-top: 15px;
            font-size: 11pt;
        }

        /* ── REVISI TABLE ── */
        .revisi-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 14px 0 12px;
        }
        .revisi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 11pt;
        }
        .revisi-table th,
        .revisi-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }
        .revisi-table th {
            text-align: center;
            font-weight: bold;
            background: #f8f9fa;
            width: 10%;
        }
        .revisi-table td.catatan { min-height: 55px; height: 65px; }

        /* ── BERITA ACARA SEMPRO (format BA SEMPRO.docx) ── */
        .ba-kop { width: 100%; border-collapse: collapse; }
        .ba-kop td { border: none; vertical-align: middle; }
        .ba-kop-logo { width: 18%; text-align: center; }
        .ba-kop-logo img { width: 62px; height: auto; }
        .ba-kop-text { width: 64%; text-align: center; font-size: 13pt; font-weight: bold; line-height: 1.15; }
        .ba-kop-line { border-top: 2.5px solid #4f81bd; margin-top: 6px; }
        .ba-title { text-align: center; font-size: 11pt; font-weight: bold; text-transform: uppercase; margin-bottom: 18px; }
        .ba-body { line-height: 1.75; }
        .ba-justify { text-align: justify; }
        .ba-fields { width: 100%; border-collapse: collapse; line-height: 1.75; }
        .ba-fields td { border: none; vertical-align: top; padding: 0; }
        .ba-fields-tight { line-height: 1.2; margin-bottom: 14px; }
        .ba-f-label { width: 34%; }
        .ba-f-colon { width: 3%; }
        .ba-keputusan { text-align: center; font-size: 15pt; font-weight: bold; margin: 30px 0 46px; }
        .ba-sign { width: 100%; border-collapse: collapse; }
        .ba-sign td { border: none; text-align: center; vertical-align: top; padding: 0 6px; }
        .ba-sign-name { padding-top: 78px !important; }
        .ba-nilai, .ba-revisi { width: 100%; border-collapse: collapse; }
        .ba-nilai th, .ba-nilai td, .ba-revisi th, .ba-revisi td { border: 1px solid #000; text-align: center; vertical-align: middle; }
        .ba-nilai th, .ba-nilai td { padding: 4px 8px; height: 22px; }
        .ba-revisi th { padding: 4px 8px; }
        .ba-revisi-body td { height: 600px; }
        .ba-revisi-sign { text-align: center; margin-top: 14px; }
        .ba-bold { font-weight: bold; }

        .ba-nilai-sidang td.kiri { text-align: left; }
        .ba-nilai-sidang th, .ba-nilai-sidang td { height: 20px; padding: 3px 8px; }

        /* ── PAGE BREAK ── */
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo_umk.jpeg');
        $logoBase64 = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : '';
    @endphp

    @foreach($sidangs as $sidang)
        @if($sidang->jenis_tugas_akhir === 'sempro')
            @include('administrasi.berita-acara._sempro', ['sidang' => $sidang, 'logoBase64' => $logoBase64])
        @else
            {{-- Sidang skripsi & sidang jurnal: format "Berita Acara Skripsi New.docx". --}}
            @include('administrasi.berita-acara._sidang', ['sidang' => $sidang, 'logoBase64' => $logoBase64])
        @endif

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>

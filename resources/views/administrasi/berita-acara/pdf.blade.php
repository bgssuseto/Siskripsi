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

        * { margin: 0; padding: 0; box-sizing: border-box; }

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

        /* ── PAGE BREAK ── */
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

    @foreach($sidangs as $sidang)
        @php
            $isJurnalOrSempro = ($sidang->jenis_tugas_akhir === 'jurnal');
            $jenisLabel = $sidang->jenis_label;
            $jenisUpper = strtoupper($jenisLabel);

            $hari  = $sidang->tanggal ? \Carbon\Carbon::parse($sidang->tanggal)->locale('id')->isoFormat('dddd') : '___________';
            $tanggalIndo = $sidang->tanggal ? \Carbon\Carbon::parse($sidang->tanggal)->locale('id')->isoFormat('D MMMM Y') : '___________';
            $jamTeks   = $sidang->jam ?? '___________';
            $ruangTeks = $sidang->ruang?->kode_ruangan ?? '___________';

            $nama         = $sidang->nama_mahasiswa;
            $nim          = $sidang->nim;
            $judul        = $sidang->judul_skripsi;
            $dosbingUtama = $sidang->pembimbingUtama?->nama_dosen;
            $dosbingPend  = $sidang->pembimbingPendamping?->nama_dosen;
            $ketuaPenguji = $sidang->ketuaPenguji?->nama_dosen;
            $penguji1     = $sidang->anggotaPenguji1?->nama_dosen;
            $penguji2     = $sidang->anggotaPenguji2?->nama_dosen;

            // Determine evaluators dynamically
            $evaluators = [];
            if ($ketuaPenguji || $penguji1) {
                if ($ketuaPenguji) $evaluators[] = ['role' => 'Ketua Penguji', 'name' => $ketuaPenguji];
                if ($penguji1)     $evaluators[] = ['role' => 'Anggota Penguji 1', 'name' => $penguji1];
                if ($penguji2)     $evaluators[] = ['role' => 'Anggota Penguji 2', 'name' => $penguji2];
            } else {
                if ($dosbingUtama) $evaluators[] = ['role' => 'Pembimbing Utama', 'name' => $dosbingUtama];
                if ($dosbingPend)  $evaluators[] = ['role' => 'Pembimbing Pendamping', 'name' => $dosbingPend];
            }
        @endphp

        {{-- ════════════════════════════════════════════════════════════════
             HALAMAN 1: BERITA ACARA
        ════════════════════════════════════════════════════════════════ --}}
        <div class="kop-wrapper">
            @if(!empty($kopBase64))
                <img src="{{ $kopBase64 }}" alt="Kop Surat">
            @else
                <div style="text-align:center; font-size:13pt; font-weight:bold; text-transform:uppercase;">UNIVERSITAS MURIA KUDUS</div>
                <div style="text-align:center; font-size:11pt;">FAKULTAS TEKNIK — PROGRAM STUDI TEKNIK INFORMATIKA</div>
            @endif
        </div>
        <hr class="kop-divider-thick">
        <hr class="kop-divider-thin">

        <div class="doc-title">BERITA ACARA {{ $isJurnalOrSempro ? 'SEMINAR PROPOSAL SKRIPSI' : 'SIDANG SKRIPSI' }}</div>

        <p style="margin-bottom:12px; line-height:1.6;">
            Pada hari ini <strong>{{ $hari }}</strong>, {{ $tanggalIndo }}
            Jam <strong>{{ $jamTeks }}</strong>
            di <strong>{{ $ruangTeks }}</strong>
            telah dilaksanakan {{ $isJurnalOrSempro ? 'Seminar Proposal Skripsi' : 'Sidang Skripsi' }}.
        </p>

        <table class="field-table">
            <tr><td class="field-label">Nama</td><td class="field-colon">:</td><td class="field-value"><strong>{{ strtoupper($nama) }}</strong></td></tr>
            <tr><td class="field-label">NIM</td><td class="field-colon">:</td><td class="field-value">{{ $nim }}</td></tr>
            <tr><td class="field-label">Judul</td><td class="field-colon">:</td><td class="field-value">{{ $judul }}</td></tr>
            <tr><td class="field-label">Pembimbing Utama</td><td class="field-colon">:</td><td class="field-value">{{ $dosbingUtama ?? '___________' }}</td></tr>
            @if($dosbingPend)
            <tr><td class="field-label">Pembimbing Pendamping</td><td class="field-colon">:</td><td class="field-value">{{ $dosbingPend }}</td></tr>
            @endif
            @if($ketuaPenguji)
            <tr><td class="field-label">Ketua Penguji</td><td class="field-colon">:</td><td class="field-value">{{ $ketuaPenguji }}</td></tr>
            @endif
            @if($penguji1)
            <tr><td class="field-label">Anggota Penguji 1</td><td class="field-colon">:</td><td class="field-value">{{ $penguji1 }}</td></tr>
            @endif
            @if($penguji2)
            <tr><td class="field-label">Anggota Penguji 2</td><td class="field-colon">:</td><td class="field-value">{{ $penguji2 }}</td></tr>
            @endif
        </table>

        <p class="result-para">
            Berdasarkan hasil {{ $isJurnalOrSempro ? 'seminar proposal' : 'pengujian pada Sidang Skripsi' }} yang telah dipaparkan, maka {{ $isJurnalOrSempro ? 'proposal skripsi' : 'mahasiswa' }} ini dinyatakan:
        </p>

        <p style="text-align:center; font-size:12pt; font-weight:bold; border: 1px solid #000; padding: 8px; margin-bottom:14px; letter-spacing:2px;">
            {{ $isJurnalOrSempro ? 'DITERIMA  /  DITOLAK' : 'LULUS  /  TIDAK LULUS' }}
        </p>

        {{-- Signatures Table --}}
        <table class="sign-table">
            <tr>
                @foreach($evaluators as $ev)
                <td style="width: {{ count($evaluators) > 0 ? (100 / count($evaluators)) : 50 }}%;">
                    {{ $ev['role'] }}
                    <div class="sign-line"></div>
                    <div class="sign-name">({{ $ev['name'] }})</div>
                </td>
                @endforeach
            </tr>
        </table>

        {{-- ════════════════════════════════════════════════════════════════
             PENILAIAN PAGES (DYNAMIC)
        ════════════════════════════════════════════════════════════════ --}}
        @foreach($evaluators as $ev)
            <div class="page-break"></div>

            <div class="penilaian-title">PENILAIAN {{ $isJurnalOrSempro ? 'SEMINAR PROPOSAL' : 'SIDANG SKRIPSI' }}</div>
            <table class="field-table">
                <tr><td class="field-label">Nama</td><td class="field-colon">:</td><td class="field-value"><strong>{{ strtoupper($nama) }}</strong></td></tr>
                <tr><td class="field-label">NIM</td><td class="field-colon">:</td><td class="field-value">{{ $nim }}</td></tr>
                <tr><td class="field-label">Judul</td><td class="field-colon">:</td><td class="field-value">{{ $judul }}</td></tr>
                <tr><td class="field-label">{{ $ev['role'] }}</td><td class="field-colon">:</td><td class="field-value">{{ $ev['name'] }}</td></tr>
            </table>

            <table class="penilaian-table">
                <thead>
                    <tr><th>PENILAIAN</th><th style="width:25%;">PROSENTASE</th><th style="width:25%;">NILAI</th></tr>
                </thead>
                <tbody>
                    @if($isJurnalOrSempro)
                        <tr><td>Sikap</td><td>20 %</td><td></td></tr>
                        <tr><td>Presentasi</td><td>30 %</td><td></td></tr>
                        <tr><td>Penguasaan Teori</td><td>50 %</td><td></td></tr>
                    @else
                        <tr><td>Sikap</td><td>10 %</td><td></td></tr>
                        <tr><td>Presentasi</td><td>10 %</td><td></td></tr>
                        <tr><td>Penguasaan Teori</td><td>40 %</td><td></td></tr>
                        <tr><td>Penguasaan Program</td><td>40 %</td><td></td></tr>
                    @endif
                    <tr><td colspan="2" style="font-weight:bold; text-align:right;">JUMLAH</td><td></td></tr>
                </tbody>
            </table>

            <p style="margin-top:8px; font-weight:bold;">KETERANGAN: {{ $isJurnalOrSempro ? 'DITERIMA / DITOLAK' : 'LULUS / TIDAK LULUS' }}</p>
            
            <div class="penilaian-footer">
                <p>Kudus, {{ $tanggalIndo }}</p>
                <p style="margin-top:55px;">{{ $ev['name'] }}</p>
            </div>
        @endforeach

        {{-- ════════════════════════════════════════════════════════════════
             LEMBAR REVISI PAGES (DYNAMIC)
        ════════════════════════════════════════════════════════════════ --}}
        @foreach($evaluators as $ev)
            <div class="page-break"></div>

            <div class="revisi-title">LEMBAR REVISI {{ $isJurnalOrSempro ? 'SEMINAR PROPOSAL SKRIPSI' : 'SIDANG SKRIPSI' }}</div>
            <table class="field-table">
                <tr><td class="field-label">Nama</td><td class="field-colon">:</td><td class="field-value"><strong>{{ strtoupper($nama) }}</strong></td></tr>
                <tr><td class="field-label">NIM</td><td class="field-colon">:</td><td class="field-value">{{ $nim }}</td></tr>
                <tr><td class="field-label">Judul</td><td class="field-colon">:</td><td class="field-value">{{ $judul }}</td></tr>
            </table>
            <p style="font-weight:bold; margin-bottom:6px; text-transform:uppercase;">{{ $ev['role'] }}</p>
            <table class="revisi-table">
                <thead><tr><th>No</th><th style="width:90%;">CATATAN</th></tr></thead>
                <tbody>
                    @for($i=1; $i<=6; $i++)
                    <tr><td style="text-align:center;">{{ $i }}</td><td class="catatan"></td></tr>
                    @endfor
                </tbody>
            </table>
            <p style="text-align:right; margin-top:6px;">Kudus, {{ $tanggalIndo }}</p>
            <table class="sign-table">
                <tr>
                    <td style="width:60%;"></td>
                    <td style="width:40%;">{{ $ev['role'] }}<div class="sign-line"></div><div class="sign-name">({{ $ev['name'] }})</div></td>
                </tr>
            </table>
        @endforeach

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>

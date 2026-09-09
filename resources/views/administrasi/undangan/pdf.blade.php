<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Undangan {{ ($jenisUndangan ?? 'sempro') === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }} - {{ $dosen->nama_dosen }}</title>
    <style>
        @page {
            margin-top: 12mm;
            margin-right: 16mm;
            margin-bottom: 14mm;
            margin-left: 16mm;
            size: a4 landscape;
        }

        body * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            color: #1e293b;
            line-height: 1.35;
        }

        /* ===================== KOP SURAT (LOGO UMK + LOGO TI) ===================== */
        table.kop-surat {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        table.kop-surat td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .kop-surat .kop-logo-umk {
            height: 46px;
        }

        .kop-surat .kop-logo-ti {
            height: 38px;
        }

        .kop-surat .kop-logo-right {
            text-align: right;
        }

        .kop-surat .kop-univ {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .kop-surat .kop-prodi {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin-top: 1px;
        }

        .kop-surat .kop-alamat {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 2px;
        }

        .kop-rule-thick {
            border: none;
            border-top: 2.5px solid #1e3a8a;
            margin: 5px 0 1.5px 0;
        }

        .kop-rule-thin {
            border: none;
            border-top: 1px solid #1e3a8a;
            margin: 0 0 6px 0;
        }

        /* ===================== LETTER META ===================== */
        .letter-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .letter-meta td {
            border: none;
            padding: 0;
            vertical-align: top;
            font-size: 10.5px;
        }

        .letter-meta .field-label {
            width: 62px;
            display: inline-block;
        }

        .letter-date {
            text-align: right;
        }

        .perihal-value {
            font-weight: bold;
        }

        /* ===================== BODY TEXT ===================== */
        .letter-body p {
            margin-bottom: 6px;
            text-align: justify;
        }

        .letter-intro {
            margin-bottom: 6px;
        }

        /* ===================== SUMMARY STRIP ===================== */
        table.summary-strip {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
        }

        table.summary-strip td {
            padding: 4px 12px;
            font-size: 10px;
            border: none;
            border-right: 1px solid #e2e8f0;
        }

        table.summary-strip td:last-child {
            border-right: none;
        }

        .strip-label {
            display: block;
            color: #64748b;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }

        .strip-value {
            display: block;
            font-weight: bold;
            color: #0f172a;
            font-size: 10.5px;
        }

        .strip-value.accent {
            color: #1e3a8a;
        }

        /* ===================== SECTION HEADING ===================== */
        .section-heading {
            font-size: 10.5px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .section-block {
            margin-bottom: 6px;
        }

        /* ===================== TABEL UMUM ===================== */
        table.data-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: 1px solid #94a3b8;
            font-size: 10px;
        }

        table.data-table thead th {
            background-color: #1e3a8a;
            font-weight: bold;
            text-align: left;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 4px 6px;
        }

        table.data-table tbody td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            word-wrap: break-word;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        table.data-table tbody tr:last-child td {
            border-bottom: none;
        }

        table.data-table th.center, table.data-table td.center {
            text-align: center;
        }

        table.data-table td strong {
            color: #0f172a;
            font-weight: bold;
        }

        /* ===================== SIGNATURE BLOCK ===================== */
        table.signature-block {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.signature-block td {
            border: none;
            padding: 0;
            vertical-align: top;
            font-size: 10.5px;
        }

        .signature-col {
            width: 260px;
            text-align: center;
        }

        .signature-space {
            height: 26px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* ===================== FOOTER ===================== */
        .doc-footer {
            margin-top: 5px;
            padding-top: 3px;
            border-top: 1px solid #e2e8f0;
            font-size: 8.5px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- ============ KOP SURAT (LOGO UMK KIRI, LOGO TI KANAN) ============ -->
    <table class="kop-surat">
        <tr>
            <td style="width: 50%;">
                @if(!empty($kopBase64))
                    <img class="kop-logo-umk" src="{{ $kopBase64 }}" alt="Kop Surat UMK">
                @else
                    <div class="kop-univ">Universitas Muria Kudus</div>
                    <div class="kop-prodi">Fakultas Teknik &mdash; Program Studi Teknik Informatika</div>
                    <div class="kop-alamat">Jln. Gondangmanis Bae Kudus PO BOX 53, Telp: 0291-438229, Fax: 0291-437198 &mdash; www.umk.ac.id &mdash; ti@umk.ac.id</div>
                @endif
            </td>
            <td class="kop-logo-right" style="width: 50%;">
                @if(!empty($logoTiBase64))
                    <img class="kop-logo-ti" src="{{ $logoTiBase64 }}" alt="Logo Teknik Informatika UMK">
                @endif
            </td>
        </tr>
    </table>
    <hr class="kop-rule-thick">
    <hr class="kop-rule-thin">

    <!-- ============ LETTER META ============ -->
    <table class="letter-meta">
        <tr>
            <td style="width: 60%;">
                <span class="field-label">Perihal</span>: <span class="perihal-value">Undangan Menjadi Dewan Penguji {{ ($jenisUndangan ?? 'sempro') === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }}</span><br>
                <span class="field-label">Lampiran</span>: Jadwal Pelaksanaan ({{ $totalUji }} Mahasiswa)
            </td>
            <td class="letter-date">
                Kudus, {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}
            </td>
        </tr>
    </table>

    <div class="letter-body">
        <p style="margin-bottom: 8px;">
            Kepada Yth.<br>
            Bapak/Ibu <strong>{{ $dosen->nama_dosen }}</strong><br>
            Dosen Program Studi Teknik Informatika<br>
            Universitas Muria Kudus<br>
            <em>di Tempat</em>
        </p>

        <p class="letter-intro">
            Dengan hormat, sehubungan dengan akan dilaksanakannya kegiatan <strong>{{ ($jenisUndangan ?? 'sempro') === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }}</strong>
            mahasiswa Program Studi Teknik Informatika, Fakultas Teknik, Universitas Muria Kudus pada <strong>{{ strtoupper($namaPeriode) }}</strong>,
            kami mengundang Bapak/Ibu untuk berkenan hadir sebagai Dewan Penguji sesuai dengan jadwal yang telah kami susun sebagaimana terlampir pada surat ini.
        </p>
    </div>

    <!-- ============ SUMMARY STRIP ============ -->
    <table class="summary-strip">
        <tr>
            <td style="width: 30%;">
                <span class="strip-label">Nama Dosen</span>
                <span class="strip-value">{{ $dosen->nama_dosen }}</span>
            </td>
            <td style="width: 18%;">
                <span class="strip-label">NIDN</span>
                <span class="strip-value">{{ $dosen->nidn ?? '-' }}</span>
            </td>
            <td style="width: 26%;">
                <span class="strip-label">Periode Akademik</span>
                <span class="strip-value">{{ $namaPeriode }}</span>
            </td>
            <td style="width: 26%;">
                <span class="strip-label">Total Mahasiswa Diuji</span>
                <span class="strip-value accent">{{ $totalUji }} Mahasiswa</span>
            </td>
        </tr>
    </table>

    <!-- ============ TABEL 1: Rekap Hari & Ruang ============ -->
    <div class="section-block">
        <div class="section-heading">Rekap Hari &amp; Ruang</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="center" style="width: 5%;">No</th>
                    <th style="width: 35%;">Nama Dosen</th>
                    <th style="width: 30%;">Hari, Tanggal</th>
                    <th class="center" style="width: 15%;">Ruang</th>
                    <th class="center" style="width: 15%;">Jam</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rekapSesi as $idx => $sesi)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td>{{ $dosen->nama_dosen }}</td>
                    <td>{{ $sesi['hari_tanggal'] }}</td>
                    <td class="center">{{ $sesi['ruang'] }}</td>
                    <td class="center">{{ $sesi['jam'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="center" style="font-style: italic; color: #94a3b8;">Tidak ada sesi jadwal ujian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ============ TABEL 2: Daftar Mahasiswa Yang Diuji ============ -->
    <div class="section-block">
        <div class="section-heading">Daftar Mahasiswa yang Diuji</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="center" style="width: 3%;">No</th>
                    <th style="width: 15%;">Nama</th>
                    <th style="width: 18%;">Ketua Penguji</th>
                    <th style="width: 16%;">Penguji 1</th>
                    <th style="width: 16%;">Penguji 2</th>
                    <th style="width: 16%;">Hari, Tanggal</th>
                    <th class="center" style="width: 8%;">Jam</th>
                    <th class="center" style="width: 8%;">Ruang</th>
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
                    <td class="center">{{ $s->jam ?? '-' }}</td>
                    <td class="center">{{ $s->ruang ? $s->ruang->kode_ruangan : '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="center" style="font-style: italic; color: #94a3b8;">Tidak ada data mahasiswa.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="letter-body">
        <p style="margin-top: 6px;">
            Demikian surat undangan ini kami sampaikan. Atas perhatian dan kesediaan Bapak/Ibu untuk hadir dan meluangkan waktu,
            kami ucapkan terima kasih.
        </p>
    </div>

    <!-- ============ SIGNATURE BLOCK ============ -->
    <table class="signature-block">
        <tr>
            <td></td>
            <td class="signature-col">
                Mengetahui,<br>
                Koordinator Skripsi/Tugas Akhir<br>
                Program Studi Teknik Informatika
                <div class="signature-space"></div>
                <div class="signature-name">{{ $koordinator->nama_dosen ?? '.....................................' }}</div>
                <div>NIDN. {{ $koordinator->nidn ?? '.....................................' }}</div>
            </td>
        </tr>
    </table>

    <!-- ============ FOOTER ============ -->
    <div class="doc-footer">
        Dokumen ini dihasilkan otomatis oleh Sistem Informasi Tugas Akhir &mdash; Program Studi Teknik Informatika, Universitas Muria Kudus. Dicetak: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB.
    </div>

</body>
</html>

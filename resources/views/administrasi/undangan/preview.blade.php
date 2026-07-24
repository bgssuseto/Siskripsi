<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Undangan {{ ($jenisUndangan ?? 'sempro') === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }} - {{ $dosen->nama_dosen }}</title>
  <style>
    @page {
      size: A4;
      margin: 2cm;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Calibri', 'Segoe UI', Arial, sans-serif;
      color: #1a1a1a;
      max-width: 780px;
      margin: 20px auto;
      padding: 40px 50px;
      background: #fff;
      line-height: 1.6;
      font-size: 15px;
    }

    .kop {
      text-align: center;
      margin-bottom: 8px;
    }

    .kop img {
      max-width: 100%;
      height: auto;
    }

    .garis {
      border: none;
      border-top: 3px solid #1a1a1a;
      border-bottom: 1px solid #1a1a1a;
      height: 4px;
      margin: 0 0 28px 0;
    }

    table.info {
      border-collapse: collapse;
      width: 100%;
      margin-bottom: 24px;
    }

    table.info td {
      padding: 2px 6px 2px 0;
      vertical-align: top;
    }

    .isi p {
      margin: 0 0 14px 0;
      text-align: justify;
    }

    .editable {
      background: #fff8d6;
      border-bottom: 1px dashed #c9a227;
      padding: 0 2px;
    }

    .detail-sidang {
      width: 100%;
      margin: 16px 0 20px 0;
      border-collapse: collapse;
    }

    .detail-sidang th,
    .detail-sidang td {
      border: 1px solid #1a1a1a;
      padding: 6px 10px;
      vertical-align: top;
      font-size: 14px;
    }

    .detail-sidang th {
      background-color: #f2f2f2;
      font-weight: bold;
      text-align: center;
    }

    .ttd {
      margin-top: 48px;
      display: flex;
      justify-content: flex-end;
    }

    .ttd-block {
      text-align: center;
      min-width: 240px;
      float: right;
    }

    .ttd-nama {
      margin-top: 70px;
      font-weight: bold;
      text-decoration: underline;
    }

    .ttd-nip {
      font-size: 14px;
    }

    .catatan {
      margin-top: 40px;
      font-size: 12.5px;
      color: #555;
      border-top: 1px dashed #ccc;
      padding-top: 12px;
      clear: both;
    }

    .btn-print-bar {
      position: fixed;
      top: 15px;
      right: 15px;
      background: #ffffff;
      padding: 10px 16px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.15);
      z-index: 9999;
      display: flex;
      gap: 10px;
    }

    .btn-print {
      background: #4f46e5;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      font-size: 13px;
    }

    .btn-close {
      background: #64748b;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      font-size: 13px;
    }

    @media print {
      body {
        margin: 0;
        padding: 20px;
        max-width: 100%;
      }

      .editable {
        background: none;
        border-bottom: none;
      }

      .catatan, .btn-print-bar {
        display: none !important;
      }
    }
  </style>
</head>

<body>

  <div class="btn-print-bar">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak Undangan</button>
    <button onclick="window.close()" class="btn-close">Tutup</button>
  </div>

  <div class="kop">
    @if(!empty($kopBase64))
      <img src="{{ $kopBase64 }}" alt="Kop Surat">
    @else
      <div style="font-size: 16px; font-weight: bold; text-transform: uppercase;">UNIVERSITAS MURIA KUDUS</div>
      <div style="font-size: 14px; font-weight: bold; text-transform: uppercase;">FAKULTAS TEKNIK - PROGRAM STUDI TEKNIK INFORMATIKA</div>
      <div style="font-size: 11px; color: #555;">Kampus UMK Gondangmanis Kudus. Telp (0291) 438229</div>
    @endif
  </div>
  <div class="garis"></div>

  <table class="info">
    <tr>
      <td style="width: 90px;">Nomor</td>
      <td>: <span class="editable">045/UN13.1/TI/{{ date('Y') }}</span></td>
    </tr>
    <tr>
      <td>Lampiran</td>
      <td>: <span class="editable">1 (satu) Berkas</span></td>
    </tr>
    <tr>
      <td>Hal</td>
      <td>: <strong>Undangan {{ ($jenisUndangan ?? 'sempro') === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }}</strong></td>
    </tr>
  </table>

  <p>Kepada Yth.<br>
    <strong>{{ $dosen->nama_dosen }}</strong><br>
    Dosen Penguji / Pembimbing<br>
    di Tempat
  </p>

  <div class="isi">
    <p>Dengan hormat,</p>
    <p>Sehubungan dengan pelaksanaan kegiatan {{ ($jenisUndangan ?? 'sempro') === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }} Mahasiswa Program Studi Teknik Informatika Periode {{ $namaPeriode }}, kami mengharapkan kesediaan Bapak/Ibu untuk menjadi Penguji/Pembimbing pada kegiatan tersebut yang akan dilaksanakan pada:</p>

    <!-- Tabel Rekap Sesi Ujian -->
    <table class="detail-sidang">
      <thead>
        <tr>
          <th style="width: 5%;">No</th>
          <th style="width: 25%;">Hari / Tanggal</th>
          <th style="width: 15%;">Jam</th>
          <th style="width: 15%;">Ruangan</th>
          <th>Mahasiswa & Judul</th>
        </tr>
      </thead>
      <tbody>
        @forelse($sidangs as $idx => $s)
        <tr>
          <td style="text-align: center;">{{ $idx + 1 }}</td>
          <td>{{ $s->tanggal ? \Carbon\Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') : '-' }}</td>
          <td style="text-align: center;">{{ $s->jam ?? '-' }} WIB</td>
          <td style="text-align: center;">{{ $s->ruang ? $s->ruang->kode_ruangan : '-' }}</td>
          <td>
            <strong>{{ $s->nama_mahasiswa }}</strong> ({{ $s->nim }})<br>
            <span style="font-size: 13px; color: #444;">"{{ $s->judul_skripsi }}"</span>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" style="text-align: center; font-style: italic;">Tidak ada sesi jadwal ujian.</td>
        </tr>
        @endforelse
      </tbody>
    </table>

    <p>Demikian surat undangan ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>
  </div>

  <div class="ttd">
    <div class="ttd-block">
      <p>Kudus, <span class="editable">{{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}</span><br>
        Ketua Program Studi Teknik Informatika,</p>
      <p class="ttd-nama"><span class="editable">Dr. Koordinator Skripsi, M.Kom</span></p>
      <p class="ttd-nip">NIP. <span class="editable">19800101 200501 1 001</span></p>
    </div>
  </div>

  <div class="catatan">
    <strong>Catatan:</strong> Teks berlatar kuning (<span style="background:#fff8d6;">seperti ini</span>) dapat disesuaikan kembali secara langsung sebelum dicetak.
  </div>

</body>
</html>

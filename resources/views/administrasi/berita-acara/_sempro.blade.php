{{--
    Berita Acara Seminar Proposal — meniru format "BA SEMPRO.docx":
      Hal. 1  Berita Acara (kop logo + teks, garis biru, keputusan Diterima / Ditolak)
      Hal. 2  Penilaian Seminar Proposal (Pembimbing Utama & Pendamping dalam satu halaman)
      Hal. 3  Lembar Revisi — Pembimbing Utama
      Hal. 4  Lembar Revisi — Pembimbing Pendamping (jika ada)
    Penguji sempro = pembimbing mahasiswa itu sendiri.
--}}
@php
    $kosong = '___________';
    $tglLengkap = fn ($tgl) => $tgl ? \App\Support\TanggalIndo::lengkap($tgl) : $kosong;

    $nama  = strtoupper($sidang->nama_mahasiswa);
    $nim   = $sidang->nim;
    $judul = $sidang->judul_skripsi;
    $utama = $sidang->pembimbingUtama?->nama_dosen;
    $pend  = $sidang->pembimbingPendamping?->nama_dosen;

    $sesiUtama = [
        'tanggal' => $tglLengkap($sidang->tanggal),
        'jam'     => $sidang->jam ? $sidang->jam . ' WIB' : $kosong,
        'ruang'   => $sidang->ruang?->kode_ruangan ?? $kosong,
    ];
    // Jadwal terpisah: sesi Pembimbing Pendamping punya tanggal/jam/ruang sendiri.
    $terpisah = $sidang->isJadwalTerpisah() && $pend;
    $sesiPend = $terpisah
        ? [
            'tanggal' => $tglLengkap($sidang->tanggal_pendamping),
            'jam'     => $sidang->jam_pendamping ? $sidang->jam_pendamping . ' WIB' : $kosong,
            'ruang'   => $sidang->ruangPendamping?->kode_ruangan ?? $kosong,
        ]
        : $sesiUtama;

    $evaluators = array_values(array_filter([
        $utama ? ['role' => 'Pembimbing Utama', 'name' => $utama, 'sesi' => $sesiUtama] : null,
        $pend  ? ['role' => 'Pembimbing Pendamping', 'name' => $pend, 'sesi' => $sesiPend] : null,
    ]));
@endphp

{{-- ═══════════════ HALAMAN 1: BERITA ACARA ═══════════════ --}}
<table class="ba-kop">
    <tr>
        <td class="ba-kop-logo">
            @if(!empty($logoBase64))
                <img src="{{ $logoBase64 }}" alt="Logo UMK">
            @endif
        </td>
        <td class="ba-kop-text">
            FAKULTAS TEKNIK<br>
            PROGRAM STUDI TEKNIK INFORMATIKA<br>
            UNIVERSITAS MURIA KUDUS<br>
            Jl. Gondang Manis Kudus
        </td>
        <td class="ba-kop-logo"></td>
    </tr>
</table>
<div class="ba-kop-line"></div>

<div class="ba-title" style="margin-top: 22px;">BERITA ACARA SEMINAR PROPOSAL SKRIPSI</div>

<div class="ba-body">
    <p class="ba-justify">
        @if($terpisah)
            Pada hari {{ $sesiUtama['tanggal'] }} Pukul {{ $sesiUtama['jam'] }} di {{ $sesiUtama['ruang'] }} (Pembimbing Utama)
            dan hari {{ $sesiPend['tanggal'] }} Pukul {{ $sesiPend['jam'] }} di {{ $sesiPend['ruang'] }} (Pembimbing Pendamping)
            telah dilaksanakan Seminar Proposal Skripsi dengan:
        @else
            Pada hari {{ $sesiUtama['tanggal'] }} Pukul {{ $sesiUtama['jam'] }} di {{ $sesiUtama['ruang'] }} telah dilaksanakan Seminar Proposal Skripsi dengan:
        @endif
    </p>

    <table class="ba-fields">
        <tr><td class="ba-f-label">Nama</td><td class="ba-f-colon">:</td><td>{{ $nama }}</td></tr>
        <tr><td class="ba-f-label">NIM</td><td class="ba-f-colon">:</td><td>{{ $nim }}</td></tr>
        <tr><td class="ba-f-label">Judul</td><td class="ba-f-colon">:</td><td>{{ $judul }}</td></tr>
        <tr><td class="ba-f-label">Pembimbing Utama</td><td class="ba-f-colon">:</td><td>{{ $utama ?? $kosong }}</td></tr>
        @if($pend)
            <tr><td class="ba-f-label">Pembimbing Pendamping</td><td class="ba-f-colon">:</td><td>{{ $pend }}</td></tr>
        @endif
    </table>

    <p class="ba-justify">Berdasarkan hasil seminar proposal yang telah dipaparkan, maka proposal skripsi ini dinyatakan:</p>
</div>

<div class="ba-keputusan">Diterima / Ditolak</div>

<table class="ba-sign">
    <tr>
        @foreach($evaluators as $ev)
            <td style="width: {{ 100 / max(count($evaluators), 1) }}%;">{{ strtoupper($ev['role']) }}</td>
        @endforeach
    </tr>
    <tr>
        @foreach($evaluators as $ev)
            <td class="ba-sign-name">{{ $ev['name'] }}</td>
        @endforeach
    </tr>
</table>

{{-- ═══════════════ HALAMAN 2: PENILAIAN (semua pembimbing dalam satu halaman) ═══════════════ --}}
<div class="page-break"></div>
@foreach($evaluators as $ev)
    <div class="ba-title" @if(!$loop->first) style="margin-top: 42px;" @endif>PENILAIAN SEMINAR PROPOSAL</div>

    <table class="ba-fields ba-fields-tight">
        <tr><td class="ba-f-label">Nama</td><td class="ba-f-colon">:</td><td>{{ $nama }}</td></tr>
        <tr><td class="ba-f-label">NIM</td><td class="ba-f-colon">:</td><td>{{ $nim }}</td></tr>
        <tr><td class="ba-f-label">Judul</td><td class="ba-f-colon">:</td><td>{{ $judul }}</td></tr>
        <tr><td class="ba-f-label">{{ $ev['role'] }}</td><td class="ba-f-colon">:</td><td>{{ $ev['name'] }}</td></tr>
    </table>

    <table class="ba-nilai">
        <tr>
            <th style="width: 34%;">PENILAIAN</th>
            <th style="width: 31%;">PROSENTASE</th>
            <th style="width: 35%;">NILAI</th>
        </tr>
        <tr><td>Sikap</td><td>20 %</td><td></td></tr>
        <tr><td>Presentasi</td><td>30 %</td><td></td></tr>
        <tr><td>Penguasaan Teori</td><td>50 %</td><td></td></tr>
        <tr><td colspan="2">JUMLAH</td><td></td></tr>
    </table>
@endforeach

{{-- ═══════════════ HALAMAN 3 & 4: LEMBAR REVISI per pembimbing ═══════════════ --}}
@foreach($evaluators as $ev)
    <div class="page-break"></div>

    <div class="ba-title">LEMBAR REVISI SEMINAR PROPOSAL SKRIPSI</div>

    <table class="ba-fields ba-fields-tight">
        <tr><td class="ba-f-label">Nama</td><td class="ba-f-colon">:</td><td>{{ $nama }}</td></tr>
        <tr><td class="ba-f-label">NIM</td><td class="ba-f-colon">:</td><td>{{ $nim }}</td></tr>
        <tr><td class="ba-f-label">Judul</td><td class="ba-f-colon">:</td><td>{{ $judul }}</td></tr>
    </table>

    <table class="ba-revisi">
        <tr>
            <th style="width: 30%;">{{ strtoupper($ev['role']) }}</th>
            <th style="width: 70%;">CATATAN</th>
        </tr>
        <tr class="ba-revisi-body"><td></td><td></td></tr>
    </table>

    <div class="ba-revisi-sign">
        <p>Kudus, {{ $ev['sesi']['tanggal'] }}</p>
        <p class="ba-bold" style="margin-top: 6px;">{{ $ev['role'] }}</p>
        <p class="ba-bold" style="margin-top: 70px;">({{ $ev['name'] }})</p>
    </div>
@endforeach

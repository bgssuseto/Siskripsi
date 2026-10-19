{{--
    Berita Acara Sidang Skripsi / Sidang Jurnal — meniru "Berita Acara Skripsi New.docx".
    Keduanya satu format; bedanya hanya istilah SKRIPSI / JURNAL sesuai jalur mahasiswa.
      Hal. 1    Berita Acara (data, keputusan Lulus / Tidak Lulus, tanda tangan 3 penguji)
      Hal. 2    Penilaian Sidang — Ketua, Anggota 1, Anggota 2 dalam satu halaman
      Hal. 3-5  Lembar Revisi per penguji
--}}
@php
    $kosong  = '___________';
    $istilah = $sidang->jenis_tugas_akhir === 'jurnal' ? 'JURNAL' : 'SKRIPSI';

    $nama   = mb_strtoupper($sidang->nama_mahasiswa);
    $nim    = $sidang->nim;
    $judul  = mb_strtoupper($sidang->judul_skripsi);
    $tanggal = $sidang->tanggal ? \App\Support\TanggalIndo::lengkap($sidang->tanggal) : $kosong;
    $jam     = $sidang->jam ?: $kosong;
    $ruang   = $sidang->ruang?->kode_ruangan ?? $kosong;

    $peran = [
        'Pembimbing Utama'      => $sidang->pembimbingUtama?->nama_dosen,
        'Pembimbing Pendamping' => $sidang->pembimbingPendamping?->nama_dosen,
        'Ketua Penguji'         => $sidang->ketuaPenguji?->nama_dosen,
        'Anggota Penguji 1'     => $sidang->anggotaPenguji1?->nama_dosen,
        'Anggota Penguji 2'     => $sidang->anggotaPenguji2?->nama_dosen,
    ];

    // Urutan penguji seperti di dokumen: Ketua, Anggota 1, Anggota 2.
    $penguji = array_values(array_filter([
        $peran['Ketua Penguji'] ? ['role' => 'Ketua Penguji', 'name' => $peran['Ketua Penguji']] : null,
        $peran['Anggota Penguji 1'] ? ['role' => 'Anggota Penguji 1', 'name' => $peran['Anggota Penguji 1']] : null,
        $peran['Anggota Penguji 2'] ? ['role' => 'Anggota Penguji 2', 'name' => $peran['Anggota Penguji 2']] : null,
    ]));
    $ketua   = collect($penguji)->firstWhere('role', 'Ketua Penguji');
    $anggota = collect($penguji)->where('role', '!=', 'Ketua Penguji')->values();
@endphp

{{-- ═══════════════ HALAMAN 1: BERITA ACARA ═══════════════ --}}
@include('administrasi.berita-acara._kop')

<div class="ba-title" style="margin-top: 22px;">BERITA ACARA SIDANG {{ $istilah }}</div>

<div class="ba-body">
    <p>Pada hari ini {{ $tanggal }} Jam {{ $jam }} di Ruang {{ $ruang }} telah dilaksanakan Sidang {{ $istilah }}.</p>

    <table class="ba-fields">
        <tr><td class="ba-f-label">Nama</td><td class="ba-f-colon">:</td><td>{{ $nama }}</td></tr>
        <tr><td class="ba-f-label">Nim</td><td class="ba-f-colon">:</td><td>{{ $nim }}</td></tr>
        <tr><td class="ba-f-label">Judul</td><td class="ba-f-colon">:</td><td class="ba-justify">{{ $judul }}</td></tr>
        @foreach($peran as $label => $dosen)
            @if($dosen || in_array($label, ['Pembimbing Utama', 'Ketua Penguji'], true))
                <tr><td class="ba-f-label">{{ $label }}</td><td class="ba-f-colon">:</td><td>{{ $dosen ?? $kosong }}</td></tr>
            @endif
        @endforeach
    </table>

    <p>Berdasarkan hasil pengujian pada Sidang {{ $istilah }}, maka mahasiswa dengan:</p>
    <p style="text-align: center;">NIM <strong>{{ $nim }}</strong> atas nama <strong>{{ $nama }}</strong> dinyatakan</p>
</div>

<div class="ba-keputusan" style="margin: 6px 0 30px;">Lulus / Tidak Lulus</div>

@if($anggota->isNotEmpty())
    <table class="ba-sign">
        <tr>
            @foreach($anggota as $ev)
                <td class="ba-bold" style="width: {{ 100 / $anggota->count() }}%;">{{ $ev['role'] }}</td>
            @endforeach
        </tr>
        <tr>
            @foreach($anggota as $ev)
                <td class="ba-sign-name ba-bold">({{ $ev['name'] }})</td>
            @endforeach
        </tr>
    </table>
@endif

@if($ketua)
    <table class="ba-sign" style="margin-top: 26px;">
        <tr><td class="ba-bold">Ketua Penguji</td></tr>
        <tr><td class="ba-sign-name ba-bold">({{ $ketua['name'] }})</td></tr>
    </table>
@endif

{{-- ═══════════════ HALAMAN 2: PENILAIAN (semua penguji dalam satu halaman) ═══════════════ --}}
<div class="page-break"></div>
@foreach($penguji as $ev)
    <div class="ba-title" @if(!$loop->first) style="margin-top: 10px;" @endif>PENILAIAN SIDANG {{ $istilah }}</div>

    <table class="ba-fields ba-fields-tight" style="margin-bottom: 2px;">
        <tr><td class="ba-f-label">Nama</td><td class="ba-f-colon">:</td><td>{{ $nama }}</td></tr>
        <tr><td class="ba-f-label">NIM</td><td class="ba-f-colon">:</td><td>{{ $nim }}</td></tr>
        <tr><td class="ba-f-label">Judul</td><td class="ba-f-colon">:</td><td class="ba-justify">{{ $judul }}</td></tr>
        <tr><td class="ba-f-label">{{ $ev['role'] }}</td><td class="ba-f-colon">:</td><td>{{ $ev['name'] }}</td></tr>
    </table>

    <table class="ba-nilai ba-nilai-sidang">
        <tr>
            <th style="width: 36%;">PENILAIAN</th>
            <th style="width: 30%;">PROSENTASE</th>
            <th style="width: 34%;">NILAI</th>
        </tr>
        <tr><td class="kiri">Sikap</td><td>10 %</td><td></td></tr>
        <tr><td class="kiri">Presentasi</td><td>10 %</td><td></td></tr>
        <tr><td class="kiri">Penguasaan Teori</td><td>40 %</td><td></td></tr>
        <tr><td class="kiri">Penguasaan Program</td><td>40 %</td><td></td></tr>
        <tr><td colspan="2">JUMLAH</td><td></td></tr>
        <tr><td colspan="2">KETERANGAN</td><td>LULUS / TIDAK LULUS</td></tr>
    </table>
@endforeach

{{-- ═══════════════ HALAMAN 3-5: LEMBAR REVISI per penguji ═══════════════ --}}
@foreach($penguji as $ev)
    <div class="page-break"></div>

    <div class="ba-title" style="font-size: 12pt;">LEMBAR REVISI SIDANG {{ $istilah }}</div>

    <table class="ba-fields" style="margin-bottom: 4px;">
        <tr><td class="ba-f-label">Nama</td><td class="ba-f-colon">:</td><td>{{ $nama }}</td></tr>
        <tr><td class="ba-f-label">NIM</td><td class="ba-f-colon">:</td><td>{{ $nim }}</td></tr>
        <tr><td class="ba-f-label">Judul</td><td class="ba-f-colon">:</td><td class="ba-justify">{{ $judul }}</td></tr>
    </table>

    <table class="ba-revisi">
        <tr>
            <td rowspan="2" style="width: 36%;">{{ mb_strtoupper($ev['role']) }}</td>
            <td style="width: 64%; padding: 3px 8px;">CATATAN</td>
        </tr>
        <tr class="ba-revisi-body"><td></td></tr>
    </table>

    <div class="ba-revisi-sign" style="line-height: 1.3;">
        <p>Kudus, {{ $tanggal }}</p>
        <p>{{ $ev['role'] }}</p>
        <p class="ba-bold" style="margin-top: 70px;">({{ $ev['name'] }})</p>
    </div>
@endforeach

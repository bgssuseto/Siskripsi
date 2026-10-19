{{-- Kop berita acara (format BA SEMPRO.docx / Berita Acara Skripsi New.docx): logo UMK + teks, garis biru. --}}
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

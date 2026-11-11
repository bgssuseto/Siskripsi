<?php

namespace App\Services;

use App\Models\BebasProdi;
use App\Models\Kaprodi;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF Surat Keterangan Bebas Prodi. Tanpa tanda tangan digital (QR) — surat
 * ditandatangani basah oleh Kaprodi. Halaman verifikasi publik tetap ada untuk
 * surat lama yang sudah terlanjur dicetak dengan QR.
 * Dipakai halaman mahasiswa, admin, dan verifikasi.
 */
class SuratBebasProdi
{
    public static function stream(BebasProdi $bebasProdi): Response
    {
        // Surat lama (sebelum ada data kaprodi) jatuh ke kaprodi yang menjabat pada tanggal surat.
        $kaprodi = $bebasProdi->kaprodi ?? Kaprodi::untukTanggal($bebasProdi->tanggal_surat);

        $kopPath = public_path('images/kop_surat.png');

        return Pdf::loadView('bebas-prodi.surat', [
            'p'         => $bebasProdi,
            'nama'      => mb_convert_case(mb_strtolower($bebasProdi->nama_mahasiswa), MB_CASE_TITLE, 'UTF-8'),
            'tanggal'   => $bebasProdi->tanggal_surat->locale('id')->translatedFormat('j F Y'),
            'kaprodi'   => $kaprodi?->nama ?? '',
            'kopBase64' => is_file($kopPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($kopPath)) : '',
        ])
            ->setPaper('a4', 'portrait')
            ->stream('Surat_Bebas_Prodi_' . $bebasProdi->nim . '.pdf');
    }
}

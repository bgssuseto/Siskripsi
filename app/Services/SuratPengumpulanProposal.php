<?php

namespace App\Services;

use App\Models\PengumpulanProposal;
use App\Support\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF Surat Keterangan Pengumpulan Proposal Skripsi, ditandatangani Koordinator
 * Skripsi lewat QR (menuju halaman verifikasi publik). Dipakai halaman
 * mahasiswa, admin/koordinator, dan halaman verifikasi.
 */
class SuratPengumpulanProposal
{
    public static function stream(PengumpulanProposal $proposal): Response
    {
        $sidang = $proposal->sidang()->with(['pembimbingUtama', 'pembimbingPendamping'])->firstOrFail();

        $logoPath = public_path('images/logo-umk.png');
        // Satu gambar PNG (QR + logo UMK di tengah); jatuh ke SVG polos bila GD tidak ada.
        $qr = $proposal->url_verifikasi
            ? (QrCode::pngDenganLogo($proposal->url_verifikasi, public_path('images/logo-umk-qr.png'))
                ?? QrCode::svgDataUri($proposal->url_verifikasi, 8, 'M'))
            : '';

        return Pdf::loadView('proposal.surat', [
            'p'          => $proposal,
            's'          => $sidang,
            'tanggal'    => $proposal->tanggal_surat->locale('id')->translatedFormat('j-M-Y'),
            'qr'         => $qr,
            'logoBase64' => is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '',
        ])
            ->setPaper('a4', 'portrait')
            ->stream('Surat_Pengumpulan_Proposal_' . $sidang->nim . '.pdf');
    }
}

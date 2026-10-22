<?php

namespace App\Http\Controllers;

use App\Models\BebasProdi;
use App\Models\TandaTanganUndangan;
use App\Services\SuratBebasProdi;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman publik (tanpa login) yang dibuka dari QR tanda tangan elektronik
 * pada surat — menyatakan apakah surat asli & masih berlaku.
 */
class VerifikasiDokumenController extends Controller
{
    public function bebasProdi(string $kode): View
    {
        $surat = BebasProdi::with('kaprodi')->where('kode_verifikasi', $kode)->first();

        return view('public.verifikasi-bebas-prodi', [
            'surat' => $surat,
            'sah'   => $surat && $surat->status === BebasProdi::STATUS_DISETUJUI,
            'kode'  => $kode,
        ]);
    }

    public function bebasProdiSurat(string $kode): Response
    {
        $surat = BebasProdi::where('kode_verifikasi', $kode)->first();
        abort_unless($surat && $surat->status === BebasProdi::STATUS_DISETUJUI, 404);

        return SuratBebasProdi::stream($surat);
    }

    /** Tujuan QR tanda tangan Koordinator Skripsi pada undangan menguji. */
    public function undangan(string $kode): View
    {
        $ttd = TandaTanganUndangan::with('dosen')->where('kode', $kode)->first();

        return view('public.verifikasi-undangan', ['ttd' => $ttd]);
    }
}

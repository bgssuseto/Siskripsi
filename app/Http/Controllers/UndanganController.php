<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use App\Models\Dosen;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use Carbon\Carbon;
use App\Services\SidangConflictService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use App\Support\PdfGabung;
use Illuminate\Support\Facades\Crypt;

/**
 * Undangan menguji untuk dosen (PDF/DOCX/Excel, satuan & massal, kirim email).
 * (Dipecah dari AdministrasiController.)
 */
class UndanganController extends Controller
{
    /**
     * Halaman Dashboard Generate Undangan Sidang
     */
    public function undanganIndex(Request $request): View
    {
        $periodes = Periode::orderBy('id', 'desc')->get();

        $selectedPeriodeId = $request->get('periode_id');
        if (!$selectedPeriodeId) {
            $activePeriode = Periode::where('aktif', true)->first();
            $selectedPeriodeId = $activePeriode ? $activePeriode->id : ($periodes->first()?->id);
        }

        $selectedPeriode = Periode::find($selectedPeriodeId);

        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');
        $jenisUndangan = $request->get('jenis', 'sempro');
        $selectedGelombang = $request->get('gelombang');

        // Query sidangs for examiners
        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ]);

        if ($jenisUndangan === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } else {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        if ($selectedPeriodeId) {
            $query->where('periode_id', $selectedPeriodeId);
        }

        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        // Options for the Gelombang filter dropdown, scoped to the selected periode + jenis
        $jenisBucket = $jenisUndangan === 'sempro' ? 'sempro' : 'skripsi';
        $gelombangOptions = $selectedPeriodeId
            ? PendaftaranPeriode::where('periode_id', $selectedPeriodeId)
                ->where('jenis', $jenisBucket)
                ->orderBy('gelombang')
                ->pluck('gelombang')
            : collect();

        $sidangs = $query->get();

        // Find all unique Dosen IDs acting as examiners
        $dosenExaminerIds = collect();

        foreach ($sidangs as $s) {
            if ($s->ketua_penguji_id) $dosenExaminerIds->push($s->ketua_penguji_id);
            if ($s->anggota_penguji_1_id) $dosenExaminerIds->push($s->anggota_penguji_1_id);
            if ($s->anggota_penguji_2_id) $dosenExaminerIds->push($s->anggota_penguji_2_id);
        }

        $dosenExaminerIds = $dosenExaminerIds->unique();

        // Build list of lecturers with stats
        $dosenList = Dosen::whereIn('id', $dosenExaminerIds)
            ->excludingSuperAdminPlaceholder()
            ->orderBy('nama_dosen', 'asc')
            ->get()
            ->map(function ($dosen) use ($sidangs) {
                $mySidangs = $sidangs->filter(function ($s) use ($dosen) {
                    return $s->ketua_penguji_id == $dosen->id ||
                           $s->anggota_penguji_1_id == $dosen->id ||
                           $s->anggota_penguji_2_id == $dosen->id;
                });

                $sessions = $this->buildSimplifiedSessions($mySidangs);

                return [
                    'dosen'         => $dosen,
                    'total_uji'     => $mySidangs->count(),
                    'total_sesi'    => count($sessions),
                    'sidangs_count' => $mySidangs->count(),
                ];
            });

        return view('administrasi.undangan.index', compact(
            'periodes', 'selectedPeriode', 'selectedPeriodeId',
            'tglMulai', 'tglSelesai', 'dosenList', 'sidangs', 'jenisUndangan',
            'selectedGelombang', 'gelombangOptions'
        ));
    }

    /**
     * Build the full data context for one dosen's Undangan letter — periode,
     * matching Sidang list, rekap sesi, koordinator penandatangan, dan logo
     * kop surat (base64) — dari filter query params yang sama. Dipakai
     * bersama oleh previewUndanganHtml() (versi "Cetak"/HTML) dan
     * buildUndanganPdfForDosen() (versi export PDF) supaya KEDUANYA selalu
     * menampilkan isi & kop surat yang identik.
     */
    public function buildUndanganContext(Request $request, Dosen $dosen): array
    {
        $periodeId = $request->get('periode_id');
        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');

        $periode = $periodeId ? Periode::find($periodeId) : Periode::where('aktif', true)->first();
        $namaPeriode = $periode ? $periode->nama_periode : 'Periode ' . date('Y');

        $jenisUndangan = $request->get('jenis', 'sempro');

        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ])
        ->where(function ($q) use ($dosen) {
            $q->where('ketua_penguji_id', $dosen->id)
              ->orWhere('anggota_penguji_1_id', $dosen->id)
              ->orWhere('anggota_penguji_2_id', $dosen->id);
        });

        if ($jenisUndangan === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } else {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        if ($periode) {
            $query->where('periode_id', $periode->id);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $selectedGelombang = $request->get('gelombang');
        if ($selectedGelombang === 'tanpa') {
            // Undangan untuk ujian yang tidak terikat gelombang (portal dosen).
            $query->whereNull('gelombang');
            $selectedGelombang = null;
        } elseif ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        $mySidangs = $query->orderBy('tanggal', 'asc')
                           ->orderBy('jam', 'asc')
                           ->get();

        // Rekap sesi (menggabungkan slot waktu berurutan per hari & ruang)
        $rekapSesi = $this->buildSimplifiedSessions($mySidangs);

        $koordinator = \App\Support\KoordinatorSkripsi::dosen();

        return [
            'dosen'         => $dosen,
            'namaPeriode'   => $namaPeriode,
            'rekapSesi'     => $rekapSesi,
            'sidangs'       => $mySidangs,
            'totalUji'      => $mySidangs->count(),
            'jenisUndangan' => $jenisUndangan,
            'koordinator'   => $koordinator,
            'kopBase64'     => $this->imageToBase64(public_path('images/kop_surat.png')),
        ] + $this->tandaTanganUndangan($dosen, $koordinator, $jenisUndangan, $periode?->id, $namaPeriode, $selectedGelombang, $mySidangs);
    }

    /**
     * QR tanda tangan elektronik Koordinator Skripsi untuk satu undangan.
     * Kosong bila belum ada dosen ber-role koordinator atau tidak ada jadwal
     * (blok tanda tangan lalu tampil seperti biasa tanpa QR).
     *
     * @return array{ttdUndangan: ?\App\Models\TandaTanganUndangan, qrTtd: ?string}
     */
    private function tandaTanganUndangan(Dosen $dosen, ?Dosen $koordinator, string $jenis, ?int $periodeId, string $namaPeriode, $gelombang, $sidangs): array
    {
        $kosong = ['ttdUndangan' => null, 'qrTtd' => null];
        if (!$koordinator || $sidangs->isEmpty()) {
            return $kosong;
        }

        try {
            $ttd = \App\Models\TandaTanganUndangan::untuk(
                $dosen, $koordinator, $jenis, $periodeId, $namaPeriode,
                ($gelombang === null || $gelombang === '') ? null : (string) $gelombang,
                $sidangs
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Tabel belum dimigrasi — undangan tetap bisa dicetak tanpa QR.
            report($e);
            return $kosong;
        }

        // Satu gambar PNG (QR + logo UMK di tengah) — tampil sama di halaman Cetak & PDF.
        $qrPng = \App\Support\QrCode::pngDenganLogo($ttd->url_verifikasi, public_path('images/logo-umk-qr.png'));

        return ['ttdUndangan' => $ttd, 'qrTtd' => $qrPng ?? \App\Support\QrCode::svgDataUri($ttd->url_verifikasi, 8, 'M')];
    }

    /**
     * Load an image file from disk and encode it as a data: URI, or an empty
     * string when the file doesn't exist — used for images embedded into the
     * Undangan kop surat (works both for the browser preview and dompdf).
     */
    private function imageToBase64(string $path): string
    {
        if (!file_exists($path)) {
            return '';
        }
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    /**
     * Preview HTML Undangan untuk Cetak / Tampilan Web 1 Dosen
     */
    public function previewUndanganHtml(Request $request, Dosen $dosen): View
    {
        return view('administrasi.undangan.preview', $this->buildUndanganContext($request, $dosen));
    }

    /**
     * Build the Undangan PDF (dompdf instance) plus its supporting context for
     * one dosen, given the same filter query params used across preview/pdf/
     * docx/email export. Shared by generateUndanganPdf() and the "kirim email"
     * flow so both stay in sync with a single source of truth.
     */
    public function buildUndanganPdfForDosen(Request $request, Dosen $dosen): array
    {
        $context = $this->buildUndanganContext($request, $dosen);

        $pdf = Pdf::loadView('administrasi.undangan.pdf', $context);
        $pdf->setPaper('a4', 'landscape');

        return [
            'pdf'           => $pdf,
            'namaPeriode'   => $context['namaPeriode'],
            'jenisUndangan' => $context['jenisUndangan'],
            'totalUji'      => $context['totalUji'],
        ];
    }

    /**
     * Generate & Download Undangan PDF untuk 1 Dosen
     */
    public function generateUndanganPdf(Request $request, Dosen $dosen): Response
    {
        $built = $this->buildUndanganPdfForDosen($request, $dosen);

        $invType = $built['jenisUndangan'] === 'sempro' ? 'Sempro' : 'Sidang_Skripsi';
        $filename = "Undangan_{$invType}_{$dosen->nama_dosen}.pdf";

        return $built['pdf']->download($filename);
    }

    /**
     * Send the Undangan PDF for 1 dosen directly to their registered email,
     * with an auto-generated subject and message body — an alternative to the
     * manual "download then attach to your own email" flow.
     */
    public function sendUndanganEmail(Request $request, Dosen $dosen)
    {
        if (empty($dosen->email)) {
            $message = "Dosen {$dosen->nama_dosen} belum memiliki alamat email. Silakan lengkapi email dosen ini di Master Dosen terlebih dahulu.";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        $built = $this->buildUndanganPdfForDosen($request, $dosen);

        $invType = $built['jenisUndangan'] === 'sempro' ? 'Sempro' : 'Sidang_Skripsi';
        $filename = "Undangan_{$invType}_{$dosen->nama_dosen}.pdf";

        try {
            $cara = \App\Support\KirimEmail::ke($dosen->email, new \App\Mail\UndanganDosenMail(
                $dosen,
                $built['jenisUndangan'],
                $built['namaPeriode'],
                $built['totalUji'],
                $built['pdf']->output(),
                $filename,
            ));
        } catch (\Throwable $e) {
            report($e);
            $message = "Email ke {$dosen->email} gagal dikirim: server email tidak merespons. Coba lagi beberapa saat lagi.";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 502);
            }
            return back()->with('error', $message);
        }

        $message = $cara === 'antri'
            ? "Undangan untuk {$dosen->nama_dosen} masuk antrian dan akan terkirim ke {$dosen->email} dalam 1–2 menit."
            : "Undangan berhasil dikirim ke email {$dosen->email} ({$dosen->nama_dosen}).";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    /**
     * Generate & Download Undangan Docx (.docx) untuk 1 Dosen matching sample template
     */
    public function generateUndanganDocx(Request $request, Dosen $dosen)
    {
        $periodeId = $request->get('periode_id');
        $tglMulai  = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');

        $periode = $periodeId ? Periode::find($periodeId) : Periode::where('aktif', true)->first();
        $namaPeriode = $periode ? $periode->nama_periode : 'JULI ' . date('Y');

        $jenisUndangan = $request->get('jenis', 'sempro');

        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ])
        ->where(function ($q) use ($dosen) {
            $q->where('ketua_penguji_id', $dosen->id)
              ->orWhere('anggota_penguji_1_id', $dosen->id)
              ->orWhere('anggota_penguji_2_id', $dosen->id);
        });

        if ($jenisUndangan === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } else {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        if ($periode) {
            $query->where('periode_id', $periode->id);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $selectedGelombang = $request->get('gelombang');
        if ($selectedGelombang === 'tanpa') {
            // Undangan untuk ujian yang tidak terikat gelombang (portal dosen).
            $query->whereNull('gelombang');
            $selectedGelombang = null;
        } elseif ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        $mySidangs = $query->orderBy('tanggal', 'asc')
                           ->orderBy('jam', 'asc')
                           ->get();

        if ($mySidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada jadwal menguji untuk dosen ini.');
        }

        $phpWord = $this->buildUndanganDocxForDosen($dosen, $mySidangs, $namaPeriode, $jenisUndangan);

        $filename = "{$dosen->nama_dosen}.docx";
        // uniqid() prefix on the on-disk temp path (not the downloaded filename)
        // so two concurrent exports never overwrite/truncate each other's file
        // while one is still streaming to its own requester.
        $tempPath = storage_path('app/public/' . uniqid('undangan_', true) . '_' . preg_replace('/[^\w\s\.,-]/', '_', $filename));

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Helper: Build a PhpWord document (Rekap Undangan) for a single Dosen.
     * Reused by both single-dosen DOCX download and mass DOCX ZIP export.
     */
    private function buildUndanganDocxForDosen(Dosen $dosen, $mySidangs, string $namaPeriode, string $jenisUndangan = 'sempro'): \PhpOffice\PhpWord\PhpWord
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection([
            'orientation'  => 'landscape',
            'marginTop'    => 1134,
            'marginBottom' => 1134,
            'marginLeft'   => 1134,
            'marginRight'  => 1134,
        ]);

        // Kop Surat (University Letterhead) — sized to the image's real aspect ratio
        // so it doesn't render squashed/distorted in the generated document.
        $kopPath = public_path('images/kop_surat.png');
        if (file_exists($kopPath)) {
            $kopWidth = 480;
            $kopHeight = $kopWidth;
            $kopDimensions = @getimagesize($kopPath);
            if ($kopDimensions && $kopDimensions[0] > 0 && $kopDimensions[1] > 0) {
                $kopHeight = (int) round($kopWidth * ($kopDimensions[1] / $kopDimensions[0]));
            }

            $section->addImage($kopPath, [
                'width'         => $kopWidth,
                'height'        => $kopHeight,
                'align'         => 'center',
                'wrappingStyle' => 'inline',
            ]);
            $section->addTextBreak(1);
        } else {
            $section->addText('UNIVERSITAS MURIA KUDUS', [
                'name' => 'Calibri', 'size' => 14, 'bold' => true,
            ], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $section->addText('FAKULTAS TEKNIK - PROGRAM STUDI TEKNIK INFORMATIKA', [
                'name' => 'Calibri', 'size' => 12, 'bold' => true,
            ], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 120]);
        }

        // Title Header
        $titleText = "REKAP HARI DAN RUANG SIDANG " . ($jenisUndangan === 'sempro' ? 'SEMPRO' : 'SKRIPSI') . " " . strtoupper($namaPeriode);
        $section->addText($titleText, [
            'name' => 'Calibri', 'size' => 12, 'bold' => true
        ], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 180]);

        // Table 1: Rekap Hari & Ruang
        $tableStyle = [
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 60,
        ];
        $phpWord->addTableStyle('RekapTable', $tableStyle);
        $table1 = $section->addTable('RekapTable');

        $table1->addRow();
        $table1->addCell(800)->addText('No', ['bold' => true, 'size' => 10]);
        $table1->addCell(3500)->addText('Nama Dosen', ['bold' => true, 'size' => 10]);
        $table1->addCell(3000)->addText('Hari', ['bold' => true, 'size' => 10]);
        $table1->addCell(1500)->addText('Ruang', ['bold' => true, 'size' => 10]);
        $table1->addCell(2000)->addText('Jam', ['bold' => true, 'size' => 10]);

        $rekapSesi = $this->buildSimplifiedSessions($mySidangs);
        foreach ($rekapSesi as $idx => $sesi) {
            $table1->addRow();
            $table1->addCell(800)->addText(($idx + 1), ['size' => 10]);
            $table1->addCell(3500)->addText($dosen->nama_dosen, ['size' => 10]);
            $table1->addCell(3000)->addText($sesi['hari_tanggal'], ['size' => 10]);
            $table1->addCell(1500)->addText($sesi['ruang'], ['size' => 10]);
            $table1->addCell(2000)->addText($sesi['jam'], ['size' => 10]);
        }

        // Summary Line
        $section->addText("Total Jumlah Uji : " . $mySidangs->count() . " Mahasiswa", [
            'name' => 'Calibri', 'size' => 11, 'bold' => true
        ], ['spaceBefore' => 140, 'spaceAfter' => 140]);

        // Table 2: Daftar Mahasiswa Yang Diuji
        $phpWord->addTableStyle('MahasiswaTable', $tableStyle);
        $table2 = $section->addTable('MahasiswaTable');

        $table2->addRow();
        $table2->addCell(600)->addText('No', ['bold' => true, 'size' => 9]);
        $table2->addCell(1500)->addText('NIM', ['bold' => true, 'size' => 9]);
        $table2->addCell(2500)->addText('Nama', ['bold' => true, 'size' => 9]);
        // Sempro: dewan penguji = pembimbing mahasiswa sendiri, tanpa ketua penguji
        if ($jenisUndangan !== 'sempro') {
            $table2->addCell(2500)->addText('Ketua Penguji', ['bold' => true, 'size' => 9]);
        }
        $table2->addCell(2500)->addText('Anggota Penguji 1', ['bold' => true, 'size' => 9]);
        $table2->addCell(2500)->addText('Anggota Penguji 2', ['bold' => true, 'size' => 9]);
        $table2->addCell(2200)->addText('Hari', ['bold' => true, 'size' => 9]);
        $table2->addCell(1500)->addText('Jam', ['bold' => true, 'size' => 9]);
        $table2->addCell(1200)->addText('Ruang', ['bold' => true, 'size' => 9]);

        foreach ($mySidangs as $idx => $s) {
            $tglStr = $s->tanggal ? Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') : '-';
            $ruangKode = $s->ruang ? $s->ruang->kode_ruangan : '-';

            $table2->addRow();
            $table2->addCell(600)->addText(($idx + 1), ['size' => 9]);
            $table2->addCell(1500)->addText($s->nim ?? '-', ['size' => 9]);
            $table2->addCell(2500)->addText($s->nama_mahasiswa, ['size' => 9]);
            if ($jenisUndangan === 'sempro') {
                $table2->addCell(2500)->addText($s->pembimbingUtama->nama_dosen ?? ($s->ketuaPenguji->nama_dosen ?? '-'), ['size' => 9]);
                $table2->addCell(2500)->addText($s->pembimbingPendamping->nama_dosen ?? ($s->anggotaPenguji1->nama_dosen ?? '-'), ['size' => 9]);
            } else {
                $table2->addCell(2500)->addText($s->ketuaPenguji->nama_dosen ?? '-', ['size' => 9]);
                $table2->addCell(2500)->addText($s->anggotaPenguji1->nama_dosen ?? '-', ['size' => 9]);
                $table2->addCell(2500)->addText($s->anggotaPenguji2->nama_dosen ?? '-', ['size' => 9]);
            }
            $table2->addCell(2200)->addText($tglStr, ['size' => 9]);
            $table2->addCell(1500)->addText($s->jam ?? '-', ['size' => 9]);
            $table2->addCell(1200)->addText($ruangKode, ['size' => 9]);
        }

        return $phpWord;
    }

    /**
     * Mass Download Undangan DOCX (ZIP) untuk semua Dosen Penguji
     */
    public function generateUndanganMassDocxZip(Request $request): BinaryFileResponse|RedirectResponse
    {
        $periodeId = $request->get('periode_id');
        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');
        $jenisUndangan = $request->get('jenis', 'sempro');
        $selectedGelombang = $request->get('gelombang');

        $periode = $periodeId ? Periode::find($periodeId) : Periode::where('aktif', true)->first();
        $namaPeriode = $periode ? $periode->nama_periode : 'Periode ' . date('Y');

        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ]);

        if ($jenisUndangan === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } else {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        if ($periode) {
            $query->where('periode_id', $periode->id);
        }

        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $sidangs = $query->get();

        $dosenExaminerIds = collect();
        foreach ($sidangs as $s) {
            if ($s->ketua_penguji_id) $dosenExaminerIds->push($s->ketua_penguji_id);
            if ($s->anggota_penguji_1_id) $dosenExaminerIds->push($s->anggota_penguji_1_id);
            if ($s->anggota_penguji_2_id) $dosenExaminerIds->push($s->anggota_penguji_2_id);
        }

        $dosens = Dosen::whereIn('id', $dosenExaminerIds->unique())->excludingSuperAdminPlaceholder()->get();

        if ($dosens->isEmpty()) {
            return back()->with('warning', 'Tidak ada dosen penguji pada filter pendaftaran yang dipilih.');
        }

        // uniqid() on both the zip path and its scratch directory so two
        // concurrent mass-exports never share files — the old shared
        // tmp_undangan_docx/ dir let one request's final cleanup (unlink all
        // *.docx) delete another still-in-progress request's files.
        $requestToken = uniqid('', true);
        $invType = $jenisUndangan === 'sempro' ? 'Sempro' : 'Sidang_Skripsi';
        $zipFilename = "Undangan_DOCX_{$invType}_" . str_replace(['/', ' '], '_', $namaPeriode) . ".zip";
        $tempZipPath = storage_path('app/public/' . $requestToken . '_' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        $tempDocxDir = storage_path('app/public/tmp_undangan_docx_' . $requestToken);
        if (!is_dir($tempDocxDir)) {
            mkdir($tempDocxDir, 0777, true);
        }

        foreach ($dosens as $dosen) {
            $mySidangs = $sidangs->filter(function ($s) use ($dosen) {
                return $s->ketua_penguji_id == $dosen->id ||
                       $s->anggota_penguji_1_id == $dosen->id ||
                       $s->anggota_penguji_2_id == $dosen->id;
            })->sortBy('tanggal')->values();

            if ($mySidangs->isEmpty()) continue;

            $phpWord = $this->buildUndanganDocxForDosen($dosen, $mySidangs, $namaPeriode, $jenisUndangan);

            $safeName = preg_replace('/[^\w\s\.,-]/', '_', $dosen->nama_dosen);
            $tempDocxPath = $tempDocxDir . '/' . $safeName . '.docx';

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempDocxPath);

            $zip->addFile($tempDocxPath, "{$safeName}.docx");
        }

        $zip->close();

        // Clean up this request's own scratch docx files/dir after they've
        // been added to the zip.
        foreach (glob($tempDocxDir . '/*.docx') as $f) {
            @unlink($f);
        }
        @rmdir($tempDocxDir);

        return response()->download($tempZipPath, $zipFilename)->deleteFileAfterSend(true);
    }

    /**
     * Mass Download Undangan PDF (ZIP) untuk semua Dosen Penguji
     */
    public function generateUndanganZip(Request $request): BinaryFileResponse|RedirectResponse
    {
        $periodeId = $request->get('periode_id');
        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');
        $jenisUndangan = $request->get('jenis', 'sempro');

        $periode = $periodeId ? Periode::find($periodeId) : Periode::where('aktif', true)->first();
        $namaPeriode = $periode ? $periode->nama_periode : 'Periode ' . date('Y');

        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ]);

        if ($jenisUndangan === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } else {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        if ($periode) {
            $query->where('periode_id', $periode->id);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        $selectedGelombang = $request->get('gelombang');
        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $sidangs = $query->get();

        $dosenExaminerIds = collect();
        foreach ($sidangs as $s) {
            if ($s->ketua_penguji_id) $dosenExaminerIds->push($s->ketua_penguji_id);
            if ($s->anggota_penguji_1_id) $dosenExaminerIds->push($s->anggota_penguji_1_id);
            if ($s->anggota_penguji_2_id) $dosenExaminerIds->push($s->anggota_penguji_2_id);
        }

        $dosens = Dosen::whereIn('id', $dosenExaminerIds->unique())->excludingSuperAdminPlaceholder()->get();

        if ($dosens->isEmpty()) {
            return back()->with('warning', 'Tidak ada dosen penguji pada filter pendaftaran yang dipilih.');
        }

        $koordinator = \App\Support\KoordinatorSkripsi::dosen();

        $invType = $jenisUndangan === 'sempro' ? 'Sempro' : 'Sidang_Skripsi';
        $zipFilename = "Undangan_{$invType}_" . str_replace(['/', ' '], '_', $namaPeriode) . ".zip";
        // uniqid() prefix on the on-disk temp path only — the download itself
        // keeps the clean $zipFilename — so two concurrent exports never
        // overwrite/truncate each other's zip.
        $tempZipPath = storage_path('app/public/' . uniqid('', true) . '_' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        $kopPath = public_path('images/kop_surat.png');
        $kopBase64 = '';
        if (file_exists($kopPath)) {
            $type = pathinfo($kopPath, PATHINFO_EXTENSION);
            $data = file_get_contents($kopPath);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        foreach ($dosens as $dosen) {
            $mySidangs = $sidangs->filter(function ($s) use ($dosen) {
                return $s->ketua_penguji_id == $dosen->id ||
                       $s->anggota_penguji_1_id == $dosen->id ||
                       $s->anggota_penguji_2_id == $dosen->id;
            })->sortBy('tanggal')->values();

            if ($mySidangs->isEmpty()) continue;

            $rekapSesi = $this->buildSimplifiedSessions($mySidangs);

            $pdf = Pdf::loadView('administrasi.undangan.pdf', [
                'dosen'         => $dosen,
                'namaPeriode'   => $namaPeriode,
                'rekapSesi'     => $rekapSesi,
                'sidangs'       => $mySidangs,
                'kopBase64'     => $kopBase64,
                'totalUji'      => $mySidangs->count(),
                'jenisUndangan' => $jenisUndangan,
                'koordinator'   => $koordinator,
            ] + $this->tandaTanganUndangan($dosen, $koordinator, $jenisUndangan, $periode?->id, $namaPeriode, $selectedGelombang, $mySidangs));

            $pdf->setPaper('a4', 'landscape');

            $pdfContent = $pdf->output();
            $safeName = preg_replace('/[^\w\s\.,-]/', '_', $dosen->nama_dosen);
            $zip->addFromString("{$safeName}.pdf", $pdfContent);
        }

        $zip->close();

        return response()->download($tempZipPath, $zipFilename)->deleteFileAfterSend(true);
    }

    /**
     * Export single lecturer invitation schedule to Excel (.xlsx)
     */
    public function generateUndanganExcel(Request $request, Dosen $dosen)
    {
        $selectedPeriodeId = $request->get('periode_id');
        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');
        $jenisUndangan = $request->get('jenis', 'sempro');

        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ]);

        if ($selectedPeriodeId) {
            $query->where('periode_id', $selectedPeriodeId);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $selectedGelombang = $request->get('gelombang');
        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        if ($jenisUndangan === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } else {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        $query->where(function ($q) use ($dosen) {
            $q->where('ketua_penguji_id', $dosen->id)
              ->orWhere('anggota_penguji_1_id', $dosen->id)
              ->orWhere('anggota_penguji_2_id', $dosen->id);
        });

        $mySidangs = $query->orderBy('tanggal')->get();

        if ($mySidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada jadwal menguji untuk dosen ini pada filter yang dipilih.');
        }

        $namaPeriode = 'Semua Periode';
        if ($selectedPeriodeId) {
            $p = Periode::find($selectedPeriodeId);
            if ($p) $namaPeriode = $p->nama_periode;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Undangan Menguji');

        $this->buildUndanganExcelForDosen($sheet, $dosen, $mySidangs, $namaPeriode, $jenisUndangan);

        $writer = new Xlsx($spreadsheet);
        $safeName = preg_replace('/[^\w\s\.,-]/', '_', $dosen->nama_dosen);
        $invType = $jenisUndangan === 'sempro' ? 'Sempro' : 'Menguji';
        $fileName = "Undangan_{$invType}_{$safeName}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export all filtered lecturer invitation schedules to a multi-sheet Excel (.xlsx) file
     */
    public function generateUndanganMassExcel(Request $request)
    {
        $selectedPeriodeId = $request->get('periode_id');
        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');

        $jenisUndangan = $request->get('jenis', 'sempro');

        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ]);

        if ($jenisUndangan === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } else {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        if ($selectedPeriodeId) {
            $query->where('periode_id', $selectedPeriodeId);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $selectedGelombang = $request->get('gelombang');
        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        $sidangs = $query->get();

        $dosenExaminerIds = collect();
        foreach ($sidangs as $s) {
            if ($s->ketua_penguji_id) $dosenExaminerIds->push($s->ketua_penguji_id);
            if ($s->anggota_penguji_1_id) $dosenExaminerIds->push($s->anggota_penguji_1_id);
            if ($s->anggota_penguji_2_id) $dosenExaminerIds->push($s->anggota_penguji_2_id);
        }

        $dosens = Dosen::whereIn('id', $dosenExaminerIds->unique())->excludingSuperAdminPlaceholder()->orderBy('nama_dosen')->get();

        if ($dosens->isEmpty()) {
            return back()->with('warning', 'Tidak ada dosen penguji pada filter pendaftaran yang dipilih.');
        }

        $namaPeriode = 'Semua Periode';
        if ($selectedPeriodeId) {
            $p = Periode::find($selectedPeriodeId);
            if ($p) $namaPeriode = $p->nama_periode;
        }

        $spreadsheet = new Spreadsheet();
        $sheetIndex = 0;

        foreach ($dosens as $dosen) {
            $mySidangs = $sidangs->filter(function ($s) use ($dosen) {
                return $s->ketua_penguji_id == $dosen->id ||
                       $s->anggota_penguji_1_id == $dosen->id ||
                       $s->anggota_penguji_2_id == $dosen->id;
            })->sortBy('tanggal')->values();

            if ($mySidangs->isEmpty()) continue;

            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            // Max 31 chars for Excel sheet title
            $cleanTitle = preg_replace('/[^\w\s]/', '', $dosen->nama_dosen);
            $sheetTitle = mb_substr($cleanTitle, 0, 28);
            $sheet->setTitle($sheetTitle ?: 'Dosen ' . ($sheetIndex + 1));

            $this->buildUndanganExcelForDosen($sheet, $dosen, $mySidangs, $namaPeriode, $jenisUndangan);
            $sheetIndex++;
        }

        if ($sheetIndex === 0) {
            return back()->with('warning', 'Tidak ada data penguji yang dapat diexport.');
        }

        $writer = new Xlsx($spreadsheet);
        $invType = $jenisUndangan === 'sempro' ? 'Sempro' : 'Sidang_Skripsi';
        $fileName = "Undangan_Menguji_Semua_Dosen_{$invType}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Helper to write structured Undangan data into a PhpSpreadsheet Worksheet
     */
    private function buildUndanganExcelForDosen($sheet, Dosen $dosen, $mySidangs, string $namaPeriode, string $jenisUndangan = 'sempro'): void
    {
        // ── Title Header Block ──
        $sheet->setCellValue('A1', 'UNIVERSITAS MURIA KUDUS - FAKULTAS TEKNIK');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'PROGRAM STUDI TEKNIK INFORMATIKA');
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $titleText = $jenisUndangan === 'sempro' ? 'JADWAL UNDANGAN MENGUJI SEMINAR PROPOSAL' : 'JADWAL UNDANGAN MENGUJI SIDANG SKRIPSI / JURNAL';
        $sheet->setCellValue('A3', $titleText);
        $sheet->mergeCells('A3:I3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ── Metadata Block ──
        $sheet->setCellValue('A5', 'Nama Dosen Penguji:');
        $sheet->setCellValue('C5', $dosen->nama_dosen);
        $sheet->getStyle('A5')->getFont()->setBold(true);
        $sheet->getStyle('C5')->getFont()->setBold(true);

        $sheet->setCellValue('A6', 'NIDN:');
        $sheet->setCellValue('C6', $dosen->nidn ?? '-');
        $sheet->getStyle('A6')->getFont()->setBold(true);

        $sheet->setCellValue('A7', 'Periode / Gelombang:');
        $sheet->setCellValue('C7', $namaPeriode);
        $sheet->getStyle('A7')->getFont()->setBold(true);

        // ── Table Header Row ──
        // Sempro: Anggota Penguji 1 & 2 saja. Skripsi: Ketua Penguji + Anggota Penguji 1 & 2.
        $labelDewan = Sidang::labelDewanPengujiUndangan($jenisUndangan);
        $headers = array_merge(['NIM', 'Nama Mahasiswa', 'Peran'], $labelDewan, ['Hari', 'Jam', 'Ruangan']);
        $startRow = 9;
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));

        foreach ($headers as $colIdx => $headerText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}{$startRow}", $headerText);
        }

        $headerRange = "A9:{$lastCol}9";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // ── Populate Data Rows ──
        $currentRow = 10;

        foreach ($mySidangs as $s) {
            $tglStr = $s->tanggal ? Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') : '-';
            $ruangKode = $s->ruang ? $s->ruang->kode_ruangan : '-';
            $dewan = $s->dewanPengujiUndangan();

            $baris = array_merge(
                [$s->nim, $s->nama_mahasiswa, $s->peranPengujiDosen($dosen->id) ?? '-'],
                array_map(fn ($label) => $dewan[$label] ?? '-', $labelDewan),
                [$tglStr, $s->jam ?? '-', $ruangKode]
            );

            foreach ($baris as $colIdx => $nilai) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->setCellValue("{$colLetter}{$currentRow}", $nilai ?? '-');
            }

            $currentRow++;
        }

        // Apply Borders
        $lastRow = max($startRow, $currentRow - 1);
        $sheet->getStyle("A9:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Alignment
        $sheet->getStyle("A10:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B10:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E10:{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Auto width columns
        foreach (range(1, count($headers)) as $colIdx) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    /**
     * Helper to group and simplify sessions per Day and Room.
     * Merges repetitive time slots on the same day & room into a single span (e.g. 08.00 – 13.00)
     */
    private function buildSimplifiedSessions($mySidangs): array
    {
        $grouped = [];

        foreach ($mySidangs as $s) {
            $tglStr = $s->tanggal ? Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') : '-';
            $ruangKode = $s->ruang ? $s->ruang->kode_ruangan : '-';
            $key = "{$tglStr}||{$ruangKode}";

            $grouped[$key][] = $s->jam;
        }

        $result = [];
        foreach ($grouped as $key => $jams) {
            list($hariTanggal, $ruang) = explode('||', $key);

            // Calculate min start and max end minutes
            $minStart = null;
            $maxEnd = null;
            $startStr = '';
            $endStr = '';

            foreach ($jams as $j) {
                $parsed = SidangConflictService::parseJamRange($j);
                if ($parsed) {
                    if ($minStart === null || $parsed['start'] < $minStart) {
                        $minStart = $parsed['start'];
                        $parts = explode('-', str_replace(['.', ':'], ':', $j));
                        $startStr = trim($parts[0] ?? '');
                    }
                    if ($maxEnd === null || $parsed['end'] > $maxEnd) {
                        $maxEnd = $parsed['end'];
                        $parts = explode('-', str_replace(['.', ':'], ':', $j));
                        $endStr = trim($parts[1] ?? '');
                    }
                }
            }

            if ($startStr && $endStr) {
                // Ensure dot format like 08.00 - 13.00
                $startFormatted = str_replace(':', '.', $startStr);
                $endFormatted = str_replace(':', '.', $endStr);
                $jamMerged = "{$startFormatted} – {$endFormatted}";
            } else {
                $jamMerged = implode(', ', array_unique($jams));
            }

            $result[] = [
                'hari_tanggal' => $hariTanggal,
                'ruang'        => $ruang,
                'jam'          => $jamMerged,
            ];
        }

        return $result;
    }
}

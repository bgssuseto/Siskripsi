<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use App\Models\Dosen;
use App\Models\Periode;
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

class AdministrasiController extends Controller
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

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $sidangs = $query->get();

        // Find all unique Dosen IDs acting as examiners
        $dosenExaminerIds = collect();

        foreach ($sidangs as $s) {
            if ($s->ketua_penguji_id) $dosenExaminerIds->push($s->ketua_penguji_id);
            if ($s->anggota_penguji_1_id) $dosenExaminerIds->push($s->anggota_penguji_1_id);
            if ($s->anggota_penguji_2_id) $dosenExaminerIds->push($s->anggota_penguji_2_id);
            if ($s->dosen_pembimbing_utama_id) $dosenExaminerIds->push($s->dosen_pembimbing_utama_id);
            if ($s->dosen_pembimbing_pendamping_id) $dosenExaminerIds->push($s->dosen_pembimbing_pendamping_id);
        }

        $dosenExaminerIds = $dosenExaminerIds->unique();

        // Build list of lecturers with stats
        $dosenList = Dosen::whereIn('id', $dosenExaminerIds)
            ->orderBy('nama_dosen', 'asc')
            ->get()
            ->map(function ($dosen) use ($sidangs) {
                $mySidangs = $sidangs->filter(function ($s) use ($dosen) {
                    return $s->ketua_penguji_id == $dosen->id ||
                           $s->anggota_penguji_1_id == $dosen->id ||
                           $s->anggota_penguji_2_id == $dosen->id ||
                           $s->dosen_pembimbing_utama_id == $dosen->id ||
                           $s->dosen_pembimbing_pendamping_id == $dosen->id;
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
            'tglMulai', 'tglSelesai', 'dosenList', 'sidangs', 'jenisUndangan'
        ));
    }

    /**
     * Preview HTML Undangan untuk Cetak / Tampilan Web 1 Dosen
     */
    public function previewUndanganHtml(Request $request, Dosen $dosen): View
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
              ->orWhere('anggota_penguji_2_id', $dosen->id)
              ->orWhere('dosen_pembimbing_utama_id', $dosen->id)
              ->orWhere('dosen_pembimbing_pendamping_id', $dosen->id);
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

        $mySidangs = $query->orderBy('tanggal', 'asc')
                           ->orderBy('jam', 'asc')
                           ->get();

        $rekapSesi = $this->buildSimplifiedSessions($mySidangs);

        $kopPath = public_path('images/kop_surat.png');
        $kopBase64 = '';
        if (file_exists($kopPath)) {
            $type = pathinfo($kopPath, PATHINFO_EXTENSION);
            $data = file_get_contents($kopPath);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        return view('administrasi.undangan.preview', [
            'dosen'         => $dosen,
            'namaPeriode'   => $namaPeriode,
            'rekapSesi'     => $rekapSesi,
            'sidangs'       => $mySidangs,
            'kopBase64'     => $kopBase64,
            'totalUji'      => $mySidangs->count(),
            'jenisUndangan' => $jenisUndangan,
        ]);
    }

    /**
     * Generate & Download Undangan PDF untuk 1 Dosen
     */
    public function generateUndanganPdf(Request $request, Dosen $dosen): Response
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
              ->orWhere('anggota_penguji_2_id', $dosen->id)
              ->orWhere('dosen_pembimbing_utama_id', $dosen->id)
              ->orWhere('dosen_pembimbing_pendamping_id', $dosen->id);
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

        $mySidangs = $query->orderBy('tanggal', 'asc')
                           ->orderBy('jam', 'asc')
                           ->get();

        // Build simplified rekap sessions (merging consecutive time slots per day & room)
        $rekapSesi = $this->buildSimplifiedSessions($mySidangs);

        // Prepare Kop Surat Image (Base64)
        $kopPath = public_path('images/kop_surat.png');
        $kopBase64 = '';
        if (file_exists($kopPath)) {
            $type = pathinfo($kopPath, PATHINFO_EXTENSION);
            $data = file_get_contents($kopPath);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdf = Pdf::loadView('administrasi.undangan.pdf', [
            'dosen'         => $dosen,
            'namaPeriode'   => $namaPeriode,
            'rekapSesi'     => $rekapSesi,
            'sidangs'       => $mySidangs,
            'kopBase64'     => $kopBase64,
            'totalUji'      => $mySidangs->count(),
            'jenisUndangan' => $jenisUndangan,
        ]);

        $pdf->setPaper('a4', 'landscape');

        $invType = $jenisUndangan === 'sempro' ? 'Sempro' : 'Sidang_Skripsi';
        $filename = "Undangan_{$invType}_{$dosen->nama_dosen}.pdf";

        return $pdf->download($filename);
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
              ->orWhere('anggota_penguji_2_id', $dosen->id)
              ->orWhere('dosen_pembimbing_utama_id', $dosen->id)
              ->orWhere('dosen_pembimbing_pendamping_id', $dosen->id);
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

        $mySidangs = $query->orderBy('tanggal', 'asc')
                           ->orderBy('jam', 'asc')
                           ->get();

        if ($mySidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada jadwal menguji untuk dosen ini.');
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection([
            'orientation'  => 'landscape',
            'marginTop'    => 1134,
            'marginBottom' => 1134,
            'marginLeft'   => 1134,
            'marginRight'  => 1134,
        ]);

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
        $table2->addCell(2500)->addText('Nama', ['bold' => true, 'size' => 9]);
        $table2->addCell(2500)->addText('Ketua Penguji', ['bold' => true, 'size' => 9]);
        $table2->addCell(2500)->addText('Penguji 1', ['bold' => true, 'size' => 9]);
        $table2->addCell(2500)->addText('Penguji 2', ['bold' => true, 'size' => 9]);
        $table2->addCell(2200)->addText('Hari', ['bold' => true, 'size' => 9]);
        $table2->addCell(1500)->addText('Jam', ['bold' => true, 'size' => 9]);
        $table2->addCell(1200)->addText('Ruang', ['bold' => true, 'size' => 9]);

        foreach ($mySidangs as $idx => $s) {
            $tglStr = $s->tanggal ? Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') : '-';
            $ruangKode = $s->ruang ? $s->ruang->kode_ruangan : '-';

            $table2->addRow();
            $table2->addCell(600)->addText(($idx + 1), ['size' => 9]);
            $table2->addCell(2500)->addText($s->nama_mahasiswa, ['size' => 9]);
            $table2->addCell(2500)->addText($s->ketuaPenguji->nama_dosen ?? '-', ['size' => 9]);
            $table2->addCell(2500)->addText($s->anggotaPenguji1->nama_dosen ?? '-', ['size' => 9]);
            $table2->addCell(2500)->addText($s->anggotaPenguji2->nama_dosen ?? '-', ['size' => 9]);
            $table2->addCell(2200)->addText($tglStr, ['size' => 9]);
            $table2->addCell(1500)->addText($s->jam ?? '-', ['size' => 9]);
            $table2->addCell(1200)->addText($ruangKode, ['size' => 9]);
        }

        $filename = "{$dosen->nama_dosen}.docx";
        $tempPath = storage_path('app/public/' . preg_replace('/[^\w\s\.,-]/', '_', $filename));

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
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

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $sidangs = $query->get();

        $dosenExaminerIds = collect();
        foreach ($sidangs as $s) {
            if ($s->ketua_penguji_id) $dosenExaminerIds->push($s->ketua_penguji_id);
            if ($s->anggota_penguji_1_id) $dosenExaminerIds->push($s->anggota_penguji_1_id);
            if ($s->anggota_penguji_2_id) $dosenExaminerIds->push($s->anggota_penguji_2_id);
            if ($s->dosen_pembimbing_utama_id) $dosenExaminerIds->push($s->dosen_pembimbing_utama_id);
            if ($s->dosen_pembimbing_pendamping_id) $dosenExaminerIds->push($s->dosen_pembimbing_pendamping_id);
        }

        $dosens = Dosen::whereIn('id', $dosenExaminerIds->unique())->get();

        if ($dosens->isEmpty()) {
            return back()->with('warning', 'Tidak ada dosen penguji pada filter pendaftaran yang dipilih.');
        }

        $invType = $jenisUndangan === 'sempro' ? 'Sempro' : 'Sidang_Skripsi';
        $zipFilename = "Undangan_{$invType}_" . str_replace(['/', ' '], '_', $namaPeriode) . ".zip";
        $tempZipPath = storage_path('app/public/' . $zipFilename);

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
                       $s->anggota_penguji_2_id == $dosen->id ||
                       $s->dosen_pembimbing_utama_id == $dosen->id ||
                       $s->dosen_pembimbing_pendamping_id == $dosen->id;
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
            ]);

            $pdf->setPaper('a4', 'landscape');

            $pdfContent = $pdf->output();
            $safeName = preg_replace('/[^\w\s\.,-]/', '_', $dosen->nama_dosen);
            $zip->addFromString("{$safeName}.pdf", $pdfContent);
        }

        $zip->close();

        return response()->download($tempZipPath)->deleteFileAfterSend(true);
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

        if ($jenisUndangan === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } else {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        $query->where(function ($q) use ($dosen) {
            $q->where('ketua_penguji_id', $dosen->id)
              ->orWhere('anggota_penguji_1_id', $dosen->id)
              ->orWhere('anggota_penguji_2_id', $dosen->id)
              ->orWhere('dosen_pembimbing_utama_id', $dosen->id)
              ->orWhere('dosen_pembimbing_pendamping_id', $dosen->id);
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

        $sidangs = $query->get();

        $dosenExaminerIds = collect();
        foreach ($sidangs as $s) {
            if ($s->ketua_penguji_id) $dosenExaminerIds->push($s->ketua_penguji_id);
            if ($s->anggota_penguji_1_id) $dosenExaminerIds->push($s->anggota_penguji_1_id);
            if ($s->anggota_penguji_2_id) $dosenExaminerIds->push($s->anggota_penguji_2_id);
            if ($s->dosen_pembimbing_utama_id) $dosenExaminerIds->push($s->dosen_pembimbing_utama_id);
            if ($s->dosen_pembimbing_pendamping_id) $dosenExaminerIds->push($s->dosen_pembimbing_pendamping_id);
        }

        $dosens = Dosen::whereIn('id', $dosenExaminerIds->unique())->orderBy('nama_dosen')->get();

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
                       $s->anggota_penguji_2_id == $dosen->id ||
                       $s->dosen_pembimbing_utama_id == $dosen->id ||
                       $s->dosen_pembimbing_pendamping_id == $dosen->id;
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
     * Export Rekap Dosen Penguji – multi-sheet Excel (.xlsx)
     * Format: 1 sheet per dosen, columns: NIM | Nama Mahasiswa | Peran | Ketua Penguji | Penguji 1 | Penguji 2 | Hari | Jam | Ruangan
     */
    public function generateRekapDosenPengujiExcel(Request $request)
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

        $sidangs = $query->orderBy('tanggal')->orderBy('jam')->get();

        // Collect all dosen IDs that appear as penguji
        $dosenExaminerIds = collect();
        foreach ($sidangs as $s) {
            if ($s->ketua_penguji_id) $dosenExaminerIds->push($s->ketua_penguji_id);
            if ($s->anggota_penguji_1_id) $dosenExaminerIds->push($s->anggota_penguji_1_id);
            if ($s->anggota_penguji_2_id) $dosenExaminerIds->push($s->anggota_penguji_2_id);
        }

        $dosens = Dosen::whereIn('id', $dosenExaminerIds->unique())->orderBy('nama_dosen')->get();

        if ($dosens->isEmpty()) {
            return back()->with('warning', 'Tidak ada data dosen penguji yang dapat diexport.');
        }

        $spreadsheet = new Spreadsheet();
        $sheetIndex = 0;

        foreach ($dosens as $dosen) {
            // Filter sidangs where this dosen is ketua/penguji1/penguji2
            $mySidangs = $sidangs->filter(function ($s) use ($dosen) {
                return $s->ketua_penguji_id == $dosen->id ||
                       $s->anggota_penguji_1_id == $dosen->id ||
                       $s->anggota_penguji_2_id == $dosen->id;
            })->values();

            if ($mySidangs->isEmpty()) continue;

            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            // Sheet title (max 31 chars for Excel)
            $cleanTitle = preg_replace('/[^\w\s,.]/', '', $dosen->nama_dosen);
            $sheetTitle = mb_substr($cleanTitle, 0, 31);
            $sheet->setTitle($sheetTitle ?: 'Dosen ' . ($sheetIndex + 1));

            // ── Header Row ──
            $headers = ['NIM', 'Nama Mahasiswa', 'Peran', 'Ketua Penguji', 'Penguji 1', 'Penguji 2', 'Hari', 'Jam', 'Ruangan'];
            foreach ($headers as $colIdx => $headerText) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->setCellValue("{$colLetter}1", $headerText);
            }

            $headerRange = 'A1:I1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // ── Data Rows ──
            $currentRow = 2;
            foreach ($mySidangs as $s) {
                // Determine role of this dosen in this sidang
                $role = '-';
                if ($s->ketua_penguji_id == $dosen->id) {
                    $role = 'Ketua Penguji';
                } elseif ($s->anggota_penguji_1_id == $dosen->id) {
                    $role = 'Penguji 1';
                } elseif ($s->anggota_penguji_2_id == $dosen->id) {
                    $role = 'Penguji 2';
                }

                $ketuaNama = $s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : '-';
                $penguji1Nama = $s->anggotaPenguji1 ? $s->anggotaPenguji1->nama_dosen : '-';
                $penguji2Nama = $s->anggotaPenguji2 ? $s->anggotaPenguji2->nama_dosen : '-';

                $tglStr = $s->tanggal
                    ? Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y')
                    : '-';

                $ruangKode = $s->ruang ? $s->ruang->kode_ruangan : '-';

                $sheet->setCellValue("A{$currentRow}", $s->nim);
                $sheet->setCellValue("B{$currentRow}", $s->nama_mahasiswa);
                $sheet->setCellValue("C{$currentRow}", $role);
                $sheet->setCellValue("D{$currentRow}", $ketuaNama);
                $sheet->setCellValue("E{$currentRow}", $penguji1Nama);
                $sheet->setCellValue("F{$currentRow}", $penguji2Nama);
                $sheet->setCellValue("G{$currentRow}", $tglStr);
                $sheet->setCellValue("H{$currentRow}", $s->jam ?? '-');
                $sheet->setCellValue("I{$currentRow}", $ruangKode);

                $currentRow++;
            }

            // Auto-size columns
            foreach (range(1, 9) as $colIdx) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            $sheetIndex++;
        }

        if ($sheetIndex === 0) {
            return back()->with('warning', 'Tidak ada data dosen penguji yang dapat diexport.');
        }

        $writer = new Xlsx($spreadsheet);

        // Build filename with month/year from filter or current date
        $bulanTahun = Carbon::now()->locale('id')->isoFormat('MMMM_Y');
        if ($tglMulai) {
            $bulanTahun = Carbon::parse($tglMulai)->locale('id')->isoFormat('MMMM_Y');
        }
        $fileName = "Rekap_Dosen_Penguji_{$bulanTahun}.xlsx";

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
        $headers = ['NIM', 'Nama Mahasiswa', 'Peran', 'Ketua Penguji', 'Penguji 1', 'Penguji 2', 'Hari', 'Jam', 'Ruangan'];
        $startRow = 9;

        foreach ($headers as $colIdx => $headerText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}{$startRow}", $headerText);
        }

        $headerRange = "A9:I9";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // ── Populate Data Rows ──
        $currentRow = 10;

        foreach ($mySidangs as $s) {
            $role = '-';
            if ($s->ketua_penguji_id == $dosen->id) {
                $role = 'Ketua Penguji';
            } elseif ($s->anggota_penguji_1_id == $dosen->id) {
                $role = 'Penguji 1';
            } elseif ($s->anggota_penguji_2_id == $dosen->id) {
                $role = 'Penguji 2';
            } elseif ($s->dosen_pembimbing_utama_id == $dosen->id) {
                $role = 'Pembimbing Utama';
            } elseif ($s->dosen_pembimbing_pendamping_id == $dosen->id) {
                $role = 'Pembimbing Pendamping';
            }

            $ketuaNama = $s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : '-';
            $penguji1Nama = $s->anggotaPenguji1 ? $s->anggotaPenguji1->nama_dosen : '-';
            $penguji2Nama = $s->anggotaPenguji2 ? $s->anggotaPenguji2->nama_dosen : '-';

            $tglStr = $s->tanggal ? Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') : '-';
            $ruangKode = $s->ruang ? $s->ruang->kode_ruangan : '-';

            $sheet->setCellValue("A{$currentRow}", $s->nim);
            $sheet->setCellValue("B{$currentRow}", $s->nama_mahasiswa);
            $sheet->setCellValue("C{$currentRow}", $role);
            $sheet->setCellValue("D{$currentRow}", $ketuaNama);
            $sheet->setCellValue("E{$currentRow}", $penguji1Nama);
            $sheet->setCellValue("F{$currentRow}", $penguji2Nama);
            $sheet->setCellValue("G{$currentRow}", $tglStr);
            $sheet->setCellValue("H{$currentRow}", $s->jam ?? '-');
            $sheet->setCellValue("I{$currentRow}", $ruangKode);

            $currentRow++;
        }

        // Apply Borders
        $lastRow = max($startRow, $currentRow - 1);
        $dataRange = "A9:I{$lastRow}";
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Alignment
        $sheet->getStyle("A10:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B10:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E10:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Auto width columns
        foreach (range(1, 9) as $colIdx) {
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

    /**
     * Halaman Dashboard Berita Acara Sidang
     */
    public function beritaAcaraIndex(Request $request): View
    {
        $periodes = Periode::orderBy('id', 'desc')->get();

        $selectedPeriodeId = $request->get('periode_id');
        if (!$selectedPeriodeId) {
            $activePeriode = Periode::where('aktif', true)->first();
            $selectedPeriodeId = $activePeriode ? $activePeriode->id : ($periodes->first()?->id);
        }

        $selectedPeriode = Periode::find($selectedPeriodeId);

        $tglMulai  = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');
        $jenis     = $request->get('jenis'); // 'skripsi' | 'sempro' | null

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

        // Filter berdasarkan jenis tugas akhir
        if ($jenis === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } elseif ($jenis === 'skripsi') {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        $sidangs = $query->orderBy('tanggal', 'asc')
                         ->orderBy('jam', 'asc')
                         ->orderBy('nama_mahasiswa', 'asc')
                         ->get();

        return view('administrasi.berita-acara.index', compact(
            'periodes', 'selectedPeriode', 'selectedPeriodeId',
            'tglMulai', 'tglSelesai', 'jenis', 'sidangs'
        ));
    }

    /**
     * Generate & Download Berita Acara PDF untuk 1 Mahasiswa
     */
    public function generateBeritaAcaraPdf(Request $request, Sidang $sidang): Response
    {
        $kopPath = public_path('images/kop_surat.png');
        $kopBase64 = '';
        if (file_exists($kopPath)) {
            $type = pathinfo($kopPath, PATHINFO_EXTENSION);
            $data = file_get_contents($kopPath);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdf = Pdf::loadView('administrasi.berita-acara.pdf', [
            'sidangs'   => collect([$sidang]),
            'kopBase64' => $kopBase64,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = "Berita Acara - {$sidang->nim} - {$sidang->nama_mahasiswa}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Preview Berita Acara PDF untuk 1 Mahasiswa di Browser
     */
    public function previewBeritaAcaraPdf(Request $request, Sidang $sidang): Response
    {
        $kopPath = public_path('images/kop_surat.png');
        $kopBase64 = '';
        if (file_exists($kopPath)) {
            $type = pathinfo($kopPath, PATHINFO_EXTENSION);
            $data = file_get_contents($kopPath);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdf = Pdf::loadView('administrasi.berita-acara.pdf', [
            'sidangs'   => collect([$sidang]),
            'kopBase64' => $kopBase64,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("Berita Acara - {$sidang->nama_mahasiswa}.pdf");
    }

    /**
     * Generate & Download Berita Acara PDF Massal (Combined PDF)
     */
    public function generateBeritaAcaraMassPdf(Request $request): Response|RedirectResponse
    {
        $periodeId = $request->get('periode_id');
        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');

        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ]);

        if ($periodeId) {
            $query->where('periode_id', $periodeId);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $sidangs = $query->orderBy('tanggal', 'asc')
                         ->orderBy('jam', 'asc')
                         ->orderBy('nama_mahasiswa', 'asc')
                         ->get();

        if ($sidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada data sidang mahasiswa pada filter yang dipilih.');
        }

        $kopPath = public_path('images/kop_surat.png');
        $kopBase64 = '';
        if (file_exists($kopPath)) {
            $type = pathinfo($kopPath, PATHINFO_EXTENSION);
            $data = file_get_contents($kopPath);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdf = Pdf::loadView('administrasi.berita-acara.pdf', [
            'sidangs'   => $sidangs,
            'kopBase64' => $kopBase64,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("Berita_Acara_Sidang_Massal.pdf");
    }

    /**
     * Preview Berita Acara PDF Massal (Combined PDF) di Browser
     */
    public function previewBeritaAcaraMassPdf(Request $request): Response|RedirectResponse
    {
        $periodeId = $request->get('periode_id');
        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');

        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ]);

        if ($periodeId) {
            $query->where('periode_id', $periodeId);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $sidangs = $query->orderBy('tanggal', 'asc')
                         ->orderBy('jam', 'asc')
                         ->orderBy('nama_mahasiswa', 'asc')
                         ->get();

        if ($sidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada data sidang mahasiswa pada filter yang dipilih.');
        }

        $kopPath = public_path('images/kop_surat.png');
        $kopBase64 = '';
        if (file_exists($kopPath)) {
            $type = pathinfo($kopPath, PATHINFO_EXTENSION);
            $data = file_get_contents($kopPath);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdf = Pdf::loadView('administrasi.berita-acara.pdf', [
            'sidangs'   => $sidangs,
            'kopBase64' => $kopBase64,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("Berita_Acara_Sidang_Massal.pdf");
    }

    /**
     * Download Berita Acara dalam bentuk ZIP berisi individual PDFs
     */
    public function generateBeritaAcaraZip(Request $request): BinaryFileResponse|RedirectResponse
    {
        $periodeId = $request->get('periode_id');
        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');

        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ]);

        if ($periodeId) {
            $query->where('periode_id', $periodeId);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        $sidangs = $query->get();

        if ($sidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada data sidang mahasiswa pada filter yang dipilih.');
        }

        $periode = $periodeId ? Periode::find($periodeId) : Periode::where('aktif', true)->first();
        $namaPeriode = $periode ? $periode->nama_periode : 'Periode ' . date('Y');

        $zipFilename = "Berita_Acara_Sidang_Skripsi_" . str_replace(['/', ' '], '_', $namaPeriode) . ".zip";
        $tempZipPath = storage_path('app/public/' . $zipFilename);

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

        foreach ($sidangs as $sidang) {
            $pdf = Pdf::loadView('administrasi.berita-acara.pdf', [
                'sidangs'   => collect([$sidang]),
                'kopBase64' => $kopBase64,
            ]);

            $pdf->setPaper('a4', 'portrait');

            $pdfContent = $pdf->output();
            $safeName = preg_replace('/[^\w\s\.,-]/', '_', $sidang->nama_mahasiswa);
            $zip->addFromString("Berita Acara - {$sidang->nim} - {$safeName}.pdf", $pdfContent);
        }

        $zip->close();

        return response()->download($tempZipPath)->deleteFileAfterSend(true);
    }

    public function skIndex(Request $request): View
    {
        $periodes = Periode::orderBy('id', 'desc')->get();

        $selectedPeriodeId = $request->get('periode_id');
        if (!$selectedPeriodeId) {
            $activePeriode = Periode::where('aktif', true)->first();
            $selectedPeriodeId = $activePeriode ? $activePeriode->id : ($periodes->first()?->id);
        }

        $selectedPeriode = Periode::find($selectedPeriodeId);
        $jenis = $request->get('jenis'); // 'skripsi' | 'sempro' | null

        $query = Sidang::query();
        if ($selectedPeriodeId) {
            $query->where('periode_id', $selectedPeriodeId);
        }

        // Filter berdasarkan jenis tugas akhir
        if ($jenis === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } elseif ($jenis === 'skripsi') {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        $totalSidangs = (clone $query)->count();
        $totalSempro  = ($jenis === 'skripsi') ? 0 : (clone $query)->where('jenis_tugas_akhir', 'sempro')->count();
        $totalSkripsi = ($jenis === 'sempro') ? 0 : (clone $query)->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])->count();

        // Unique lecturers count (sesuai jenis yang ditampilkan)
        $dosenPembimbingIds = (clone $query)->pluck('dosen_pembimbing_utama_id')
            ->merge((clone $query)->pluck('dosen_pembimbing_pendamping_id'))
            ->filter()->unique();

        $dosenPengujiIds = (clone $query)->pluck('ketua_penguji_id')
            ->merge((clone $query)->pluck('anggota_penguji_1_id'))
            ->merge((clone $query)->pluck('anggota_penguji_2_id'))
            ->filter()->unique();

        return view('administrasi.sk.index', compact(
            'periodes', 'selectedPeriode', 'selectedPeriodeId',
            'totalSidangs', 'totalSempro', 'totalSkripsi',
            'dosenPembimbingIds', 'dosenPengujiIds', 'jenis'
        ));
    }    /**
     * Helper to generate unique & valid Excel sheet title for a lecturer (Max 31 chars)
     */
    private function generateUniqueSheetTitle(string $namaDosen, array &$existingTitles): string
    {
        $cleanName = preg_replace('/[\/\\\?\*\:\[\]]/', '', $namaDosen);
        $cleanName = preg_replace('/[^\w\s\.\-]/u', '', $cleanName);
        $cleanName = trim($cleanName);

        if (empty($cleanName)) {
            $cleanName = 'Dosen';
        }

        $baseTitle = mb_substr($cleanName, 0, 25);
        $title = $baseTitle;
        $counter = 1;

        while (in_array(strtolower($title), array_map('strtolower', $existingTitles))) {
            $title = mb_substr($baseTitle, 0, 20) . '_' . $counter;
            $counter++;
        }

        $existingTitles[] = $title;
        return $title;
    }

    /**
     * Export SK Pembimbing (Excel .xlsx) - Multi-sheet per Dosen with Rangkuman as Sheet 1
     */
    public function exportSkPembimbingExcel(Request $request)
    {
        $selectedPeriodeId = $request->get('periode_id');
        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');
        $jenisTa = $request->get('jenis_tugas_akhir');

        $query = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'periode']);

        if ($selectedPeriodeId) {
            $query->where('periode_id', $selectedPeriodeId);
        }

        if ($tglMulai) {
            $query->whereDate('tanggal_pendaftaran', '>=', $tglMulai);
        }

        if ($tglSelesai) {
            $query->whereDate('tanggal_pendaftaran', '<=', $tglSelesai);
        }

        if ($jenisTa) {
            if ($jenisTa === 'sempro') {
                $query->where('jenis_tugas_akhir', 'sempro');
            } else {
                $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
            }
        }

        $sidangs = $query->orderBy('nama_mahasiswa')->get();

        if ($sidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada data pendaftaran tugas akhir pada filter yang dipilih.');
        }

        $namaPeriode = 'Semua Periode';
        if ($selectedPeriodeId) {
            $p = Periode::find($selectedPeriodeId);
            if ($p) $namaPeriode = $p->nama_periode;
        }

        $spreadsheet = new Spreadsheet();
        $usedTitles = [];

        // ── SHEET 1: RANGKUMAN (REKAP DOSEN + MASTER LIST) ──
        $sheet1 = $spreadsheet->getActiveSheet();
        $titleRangkuman = $this->generateUniqueSheetTitle('Rangkuman SK Pembimbing', $usedTitles);
        $sheet1->setTitle($titleRangkuman);

        // Header Block Sheet 1
        $sheet1->setCellValue('A1', 'UNIVERSITAS MURIA KUDUS - FAKULTAS TEKNIK');
        $sheet1->mergeCells('A1:J1');
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet1->setCellValue('A2', 'PROGRAM STUDI TEKNIK INFORMATIKA');
        $sheet1->mergeCells('A2:J2');
        $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet1->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet1->setCellValue('A3', 'SURAT KEPUTUS (SK) DEKAN - RANGKUMAN DOSEN PEMBIMBING TUGAS AKHIR');
        $sheet1->mergeCells('A3:J3');
        $sheet1->getStyle('A3')->getFont()->setBold(true)->setSize(11);
        $sheet1->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet1->setCellValue('A5', 'PERIODE AKADEMIK:');
        $sheet1->setCellValue('C5', $namaPeriode);
        $sheet1->getStyle('A5')->getFont()->setBold(true);
        $sheet1->getStyle('C5')->getFont()->setBold(true);

        $sheet1->setCellValue('A6', 'TANGGAL GENERATE:');
        $sheet1->setCellValue('C6', now()->locale('id')->isoFormat('D MMMM Y (HH:mm WIB)'));
        $sheet1->getStyle('A6')->getFont()->setBold(true);

        // Subtitle 1: Rekapitulasi Per Dosen
        $sheet1->setCellValue('A8', 'I. REKAPITULASI JUMLAH BIMBINGAN PER DOSEN');
        $sheet1->getStyle('A8')->getFont()->setBold(true)->setSize(11);

        $headersRekap = ['No', 'NIDN', 'Nama Dosen Pembimbing', 'Pembimbing Utama', 'Pembimbing Pendamping', 'Total Bimbingan'];
        foreach ($headersRekap as $colIdx => $hText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet1->setCellValue("{$colLetter}9", $hText);
        }
        $sheet1->getStyle('A9:F9')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet1->getStyle('A9:F9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B');
        $sheet1->getStyle('A9:F9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $dosens = Dosen::orderBy('nama_dosen')->get();
        $rRow = 10;
        $rNo = 1;
        $activeDosensForSheet = [];

        foreach ($dosens as $d) {
            $countUtama = $sidangs->where('dosen_pembimbing_utama_id', $d->id)->count();
            $countPendamping = $sidangs->where('dosen_pembimbing_pendamping_id', $d->id)->count();
            $total = $countUtama + $countPendamping;

            if ($total === 0) continue;

            $activeDosensForSheet[] = $d;

            $sheet1->setCellValue("A{$rRow}", $rNo++);
            $sheet1->setCellValue("B{$rRow}", $d->nidn ?? '-');
            $sheet1->setCellValue("C{$rRow}", $d->nama_dosen);
            $sheet1->setCellValue("D{$rRow}", $countUtama);
            $sheet1->setCellValue("E{$rRow}", $countPendamping);
            $sheet1->setCellValue("F{$rRow}", $total);
            $rRow++;
        }
        $lastRRow = max(9, $rRow - 1);
        $sheet1->getStyle("A9:F{$lastRRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet1->getStyle("A10:B{$lastRRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("D10:F{$lastRRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Subtitle 2: Master List Seluruh Mahasiswa
        $masterStartRow = $lastRRow + 3;
        $sheet1->setCellValue("A{$masterStartRow}", 'II. DAFTAR SELURUH PENDAFTARAN MAHASISWA & PEMBIMBING');
        $sheet1->getStyle("A{$masterStartRow}")->getFont()->setBold(true)->setSize(11);

        $headerRowMaster = $masterStartRow + 1;
        $headersMaster = ['No', 'NIM', 'Nama Mahasiswa', 'Judul Skripsi / Tugas Akhir', 'Jenis TA', 'Pembimbing Utama', 'Pembimbing Pendamping', 'Tanggal Pendaftaran', 'Status'];
        foreach ($headersMaster as $colIdx => $hText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet1->setCellValue("{$colLetter}{$headerRowMaster}", $hText);
        }
        $sheet1->getStyle("A{$headerRowMaster}:I{$headerRowMaster}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet1->getStyle("A{$headerRowMaster}:I{$headerRowMaster}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
        $sheet1->getStyle("A{$headerRowMaster}:I{$headerRowMaster}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $mRow = $headerRowMaster + 1;
        $mNo = 1;

        foreach ($sidangs as $s) {
            $jenisStr = 'Seminar Proposal';
            if ($s->jenis_tugas_akhir === 'sidang' || $s->jenis_tugas_akhir === 'skripsi') {
                $jenisStr = 'Sidang Skripsi';
            } elseif ($s->jenis_tugas_akhir === 'jurnal') {
                $jenisStr = 'Jurnal';
            }

            $sheet1->setCellValue("A{$mRow}", $mNo++);
            $sheet1->setCellValue("B{$mRow}", $s->nim);
            $sheet1->setCellValue("C{$mRow}", $s->nama_mahasiswa);
            $sheet1->setCellValue("D{$mRow}", $s->judul_skripsi);
            $sheet1->setCellValue("E{$mRow}", $jenisStr);
            $sheet1->setCellValue("F{$mRow}", $s->pembimbingUtama->nama_dosen ?? '-');
            $sheet1->setCellValue("G{$mRow}", $s->pembimbingPendamping->nama_dosen ?? '-');
            $sheet1->setCellValue("H{$mRow}", $s->tanggal_pendaftaran ? Carbon::parse($s->tanggal_pendaftaran)->format('d/m/Y') : '-');
            $sheet1->setCellValue("I{$mRow}", ucfirst($s->verifikasi_status ?? 'menunggu'));

            $mRow++;
        }
        $lastMRow = max($headerRowMaster, $mRow - 1);
        $sheet1->getStyle("A{$headerRowMaster}:I{$lastMRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet1->getStyle("A".($headerRowMaster+1).":B{$lastMRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("E".($headerRowMaster+1).":E{$lastMRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("H".($headerRowMaster+1).":I{$lastMRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range(1, 9) as $colIdx) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet1->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // ── SHEET 2..N: SHEET PER DOSEN PEMBIMBING ──
        foreach ($activeDosensForSheet as $dosen) {
            $mySidangs = $sidangs->filter(function ($s) use ($dosen) {
                return $s->dosen_pembimbing_utama_id == $dosen->id || $s->dosen_pembimbing_pendamping_id == $dosen->id;
            })->values();

            if ($mySidangs->isEmpty()) continue;

            $sheetDosen = $spreadsheet->createSheet();
            $titleDosen = $this->generateUniqueSheetTitle($dosen->nama_dosen, $usedTitles);
            $sheetDosen->setTitle($titleDosen);

            // Header Dosen Sheet
            $sheetDosen->setCellValue('A1', 'UNIVERSITAS MURIA KUDUS - FAKULTAS TEKNIK');
            $sheetDosen->mergeCells('A1:H1');
            $sheetDosen->getStyle('A1')->getFont()->setBold(true)->setSize(13);
            $sheetDosen->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheetDosen->setCellValue('A2', 'PROGRAM STUDI TEKNIK INFORMATIKA');
            $sheetDosen->mergeCells('A2:H2');
            $sheetDosen->getStyle('A2')->getFont()->setBold(true)->setSize(11);
            $sheetDosen->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheetDosen->setCellValue('A3', 'SURAT KEPUTUS (SK) DEKAN - DAFTAR BIMBINGAN TUGAS AKHIR');
            $sheetDosen->mergeCells('A3:H3');
            $sheetDosen->getStyle('A3')->getFont()->setBold(true)->setSize(11);
            $sheetDosen->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheetDosen->setCellValue('A5', 'DOSEN PEMBIMBING:');
            $sheetDosen->setCellValue('C5', $dosen->nama_dosen . ($dosen->nidn ? ' (NIDN: ' . $dosen->nidn . ')' : ''));
            $sheetDosen->getStyle('A5')->getFont()->setBold(true);
            $sheetDosen->getStyle('C5')->getFont()->setBold(true);

            $sheetDosen->setCellValue('A6', 'PERIODE AKADEMIK:');
            $sheetDosen->setCellValue('C6', $namaPeriode);
            $sheetDosen->getStyle('A6')->getFont()->setBold(true);

            $headersDosen = ['No', 'NIM', 'Nama Mahasiswa', 'Judul Skripsi / Tugas Akhir', 'Jenis TA', 'Peran Pembimbing', 'Pembimbing Pendamping / Utama', 'Tanggal Pendaftaran'];
            foreach ($headersDosen as $colIdx => $hText) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheetDosen->setCellValue("{$colLetter}8", $hText);
            }
            $sheetDosen->getStyle('A8:H8')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheetDosen->getStyle('A8:H8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
            $sheetDosen->getStyle('A8:H8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $dRow = 9;
            $dNo = 1;

            foreach ($mySidangs as $ms) {
                $jenisStr = 'Seminar Proposal';
                if ($ms->jenis_tugas_akhir === 'sidang' || $ms->jenis_tugas_akhir === 'skripsi') {
                    $jenisStr = 'Sidang Skripsi';
                } elseif ($ms->jenis_tugas_akhir === 'jurnal') {
                    $jenisStr = 'Jurnal';
                }

                $peran = $ms->dosen_pembimbing_utama_id == $dosen->id ? 'Pembimbing Utama' : 'Pembimbing Pendamping';
                $pembimbingLain = $ms->dosen_pembimbing_utama_id == $dosen->id
                    ? ($ms->pembimbingPendamping->nama_dosen ?? '-')
                    : ($ms->pembimbingUtama->nama_dosen ?? '-');

                $sheetDosen->setCellValue("A{$dRow}", $dNo++);
                $sheetDosen->setCellValue("B{$dRow}", $ms->nim);
                $sheetDosen->setCellValue("C{$dRow}", $ms->nama_mahasiswa);
                $sheetDosen->setCellValue("D{$dRow}", $ms->judul_skripsi);
                $sheetDosen->setCellValue("E{$dRow}", $jenisStr);
                $sheetDosen->setCellValue("F{$dRow}", $peran);
                $sheetDosen->setCellValue("G{$dRow}", $pembimbingLain);
                $sheetDosen->setCellValue("H{$dRow}", $ms->tanggal_pendaftaran ? Carbon::parse($ms->tanggal_pendaftaran)->format('d/m/Y') : '-');

                $dRow++;
            }

            $lastDRow = max(8, $dRow - 1);
            $sheetDosen->getStyle("A8:H{$lastDRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheetDosen->getStyle("A9:B{$lastDRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetDosen->getStyle("E9:F{$lastDRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetDosen->getStyle("H9:H{$lastDRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            foreach (range(1, 8) as $colIdx) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheetDosen->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $cleanPeriode = preg_replace('/[^\w\s\.,-]/', '_', $namaPeriode);
        $fileName = "SK_Pembimbing_Skripsi_{$cleanPeriode}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export SK Penguji Skripsi & Sempro (Excel .xlsx) - Multi-sheet per Dosen with Rangkuman as Sheet 1
     */
    public function exportSkPengujiExcel(Request $request)
    {
        $selectedPeriodeId = $request->get('periode_id');
        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');
        $jenisTa = $request->get('jenis_tugas_akhir');

        $query = Sidang::with([
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'pembimbingUtama', 'pembimbingPendamping',
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

        if ($jenisTa) {
            if ($jenisTa === 'sempro') {
                $query->where('jenis_tugas_akhir', 'sempro');
            } else {
                $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
            }
        }

        $sidangs = $query->orderBy('tanggal', 'asc')->orderBy('jam', 'asc')->get();

        if ($sidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada data sidang / ujian pada filter yang dipilih.');
        }

        $namaPeriode = 'Semua Periode';
        if ($selectedPeriodeId) {
            $p = Periode::find($selectedPeriodeId);
            if ($p) $namaPeriode = $p->nama_periode;
        }

        $spreadsheet = new Spreadsheet();
        $usedTitles = [];

        // ── SHEET 1: RANGKUMAN (REKAP BEBAN PENGUJI + MASTER LIST UJIAN) ──
        $sheet1 = $spreadsheet->getActiveSheet();
        $titleRangkuman = $this->generateUniqueSheetTitle('Rangkuman SK Penguji', $usedTitles);
        $sheet1->setTitle($titleRangkuman);

        // Header Block Sheet 1
        $sheet1->setCellValue('A1', 'UNIVERSITAS MURIA KUDUS - FAKULTAS TEKNIK');
        $sheet1->mergeCells('A1:L1');
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet1->setCellValue('A2', 'PROGRAM STUDI TEKNIK INFORMATIKA');
        $sheet1->mergeCells('A2:L2');
        $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet1->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet1->setCellValue('A3', 'SURAT KEPUTUS (SK) DEKAN - RANGKUMAN DOSEN PENGUJI SEMPRO & SIDANG SKRIPSI');
        $sheet1->mergeCells('A3:L3');
        $sheet1->getStyle('A3')->getFont()->setBold(true)->setSize(11);
        $sheet1->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet1->setCellValue('A5', 'PERIODE AKADEMIK:');
        $sheet1->setCellValue('C5', $namaPeriode);
        $sheet1->getStyle('A5')->getFont()->setBold(true);
        $sheet1->getStyle('C5')->getFont()->setBold(true);

        $sheet1->setCellValue('A6', 'TANGGAL GENERATE:');
        $sheet1->setCellValue('C6', now()->locale('id')->isoFormat('D MMMM Y (HH:mm WIB)'));
        $sheet1->getStyle('A6')->getFont()->setBold(true);

        // Subtitle 1: Rekapitulasi Per Dosen
        $sheet1->setCellValue('A8', 'I. REKAPITULASI JUMLAH MENGUJI PER DOSEN');
        $sheet1->getStyle('A8')->getFont()->setBold(true)->setSize(11);

        $headersRekap = ['No', 'NIDN', 'Nama Dosen Penguji', 'Ketua Penguji', 'Anggota Penguji 1', 'Anggota Penguji 2', 'Total Ujian'];
        foreach ($headersRekap as $colIdx => $hText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet1->setCellValue("{$colLetter}9", $hText);
        }
        $sheet1->getStyle('A9:G9')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet1->getStyle('A9:G9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B');
        $sheet1->getStyle('A9:G9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $dosens = Dosen::orderBy('nama_dosen')->get();
        $rRow = 10;
        $rNo = 1;
        $activeDosensForSheet = [];

        foreach ($dosens as $d) {
            $cKetua = $sidangs->where('ketua_penguji_id', $d->id)->count();
            $cPenguji1 = $sidangs->where('anggota_penguji_1_id', $d->id)->count();
            $cPenguji2 = $sidangs->where('anggota_penguji_2_id', $d->id)->count();
            $cSemproPembimbing = $sidangs->where('jenis_tugas_akhir', 'sempro')->filter(fn($s) => $s->dosen_pembimbing_utama_id == $d->id || $s->dosen_pembimbing_pendamping_id == $d->id)->count();
            
            $total = $cKetua + $cPenguji1 + $cPenguji2 + $cSemproPembimbing;

            if ($total === 0) continue;

            $activeDosensForSheet[] = $d;

            $sheet1->setCellValue("A{$rRow}", $rNo++);
            $sheet1->setCellValue("B{$rRow}", $d->nidn ?? '-');
            $sheet1->setCellValue("C{$rRow}", $d->nama_dosen);
            $sheet1->setCellValue("D{$rRow}", $cKetua);
            $sheet1->setCellValue("E{$rRow}", $cPenguji1);
            $sheet1->setCellValue("F{$rRow}", $cPenguji2);
            $sheet1->setCellValue("G{$rRow}", $total);
            $rRow++;
        }
        $lastRRow = max(9, $rRow - 1);
        $sheet1->getStyle("A9:G{$lastRRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet1->getStyle("A10:B{$lastRRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("D10:G{$lastRRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Subtitle 2: Master List Seluruh Ujian
        $masterStartRow = $lastRRow + 3;
        $sheet1->setCellValue("A{$masterStartRow}", 'II. DAFTAR SELURUH AGENDASIDANG / UJIAN & TIM PENGUJI');
        $sheet1->getStyle("A{$masterStartRow}")->getFont()->setBold(true)->setSize(11);

        $headerRowMaster = $masterStartRow + 1;
        $headersMaster = ['No', 'NIM', 'Nama Mahasiswa', 'Judul Skripsi / Tugas Akhir', 'Jenis Ujian', 'Ketua Penguji', 'Anggota Penguji 1', 'Anggota Penguji 2', 'Tanggal Ujian', 'Jam', 'Ruangan', 'Status'];
        foreach ($headersMaster as $colIdx => $hText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet1->setCellValue("{$colLetter}{$headerRowMaster}", $hText);
        }
        $sheet1->getStyle("A{$headerRowMaster}:L{$headerRowMaster}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet1->getStyle("A{$headerRowMaster}:L{$headerRowMaster}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF7C3AED');
        $sheet1->getStyle("A{$headerRowMaster}:L{$headerRowMaster}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $mRow = $headerRowMaster + 1;
        $mNo = 1;

        foreach ($sidangs as $s) {
            $jenisStr = 'Seminar Proposal';
            if ($s->jenis_tugas_akhir === 'sidang' || $s->jenis_tugas_akhir === 'skripsi') {
                $jenisStr = 'Sidang Skripsi';
            } elseif ($s->jenis_tugas_akhir === 'jurnal') {
                $jenisStr = 'Jurnal';
            }

            $tglStr = $s->tanggal ? Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') : 'Belum diplotting';
            $ruangKode = $s->ruang ? $s->ruang->kode_ruangan : '-';

            $sheet1->setCellValue("A{$mRow}", $mNo++);
            $sheet1->setCellValue("B{$mRow}", $s->nim);
            $sheet1->setCellValue("C{$mRow}", $s->nama_mahasiswa);
            $sheet1->setCellValue("D{$mRow}", $s->judul_skripsi);
            $sheet1->setCellValue("E{$mRow}", $jenisStr);
            $sheet1->setCellValue("F{$mRow}", $s->ketuaPenguji->nama_dosen ?? ($s->jenis_tugas_akhir === 'sempro' ? ($s->pembimbingUtama->nama_dosen ?? '-') : '-'));
            $sheet1->setCellValue("G{$mRow}", $s->anggotaPenguji1->nama_dosen ?? ($s->jenis_tugas_akhir === 'sempro' ? ($s->pembimbingPendamping->nama_dosen ?? '-') : '-'));
            $sheet1->setCellValue("H{$mRow}", $s->anggotaPenguji2->nama_dosen ?? '-');
            $sheet1->setCellValue("I{$mRow}", $tglStr);
            $sheet1->setCellValue("J{$mRow}", $s->jam ?? '-');
            $sheet1->setCellValue("K{$mRow}", $ruangKode);
            $sheet1->setCellValue("L{$mRow}", ucfirst($s->verifikasi_status ?? 'menunggu'));

            $mRow++;
        }
        $lastMRow = max($headerRowMaster, $mRow - 1);
        $sheet1->getStyle("A{$headerRowMaster}:L{$lastMRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet1->getStyle("A".($headerRowMaster+1).":B{$lastMRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("E".($headerRowMaster+1).":E{$lastMRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("I".($headerRowMaster+1).":L{$lastMRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range(1, 12) as $colIdx) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet1->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // ── SHEET 2..N: SHEET PER DOSEN PENGUJI ──
        foreach ($activeDosensForSheet as $dosen) {
            $mySidangs = $sidangs->filter(function ($s) use ($dosen) {
                if ($s->jenis_tugas_akhir === 'sempro') {
                    return $s->dosen_pembimbing_utama_id == $dosen->id || $s->dosen_pembimbing_pendamping_id == $dosen->id;
                }
                return $s->ketua_penguji_id == $dosen->id ||
                       $s->anggota_penguji_1_id == $dosen->id ||
                       $s->anggota_penguji_2_id == $dosen->id;
            })->values();

            if ($mySidangs->isEmpty()) continue;

            $sheetDosen = $spreadsheet->createSheet();
            $titleDosen = $this->generateUniqueSheetTitle($dosen->nama_dosen, $usedTitles);
            $sheetDosen->setTitle($titleDosen);

            // Header Dosen Sheet
            $sheetDosen->setCellValue('A1', 'UNIVERSITAS MURIA KUDUS - FAKULTAS TEKNIK');
            $sheetDosen->mergeCells('A1:J1');
            $sheetDosen->getStyle('A1')->getFont()->setBold(true)->setSize(13);
            $sheetDosen->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheetDosen->setCellValue('A2', 'PROGRAM STUDI TEKNIK INFORMATIKA');
            $sheetDosen->mergeCells('A2:J2');
            $sheetDosen->getStyle('A2')->getFont()->setBold(true)->setSize(11);
            $sheetDosen->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheetDosen->setCellValue('A3', 'SURAT KEPUTUS (SK) DEKAN - JADWAL DOSEN PENGUJI UJIAN');
            $sheetDosen->mergeCells('A3:J3');
            $sheetDosen->getStyle('A3')->getFont()->setBold(true)->setSize(11);
            $sheetDosen->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheetDosen->setCellValue('A5', 'DOSEN PENGUJI:');
            $sheetDosen->setCellValue('C5', $dosen->nama_dosen . ($dosen->nidn ? ' (NIDN: ' . $dosen->nidn . ')' : ''));
            $sheetDosen->getStyle('A5')->getFont()->setBold(true);
            $sheetDosen->getStyle('C5')->getFont()->setBold(true);

            $sheetDosen->setCellValue('A6', 'PERIODE AKADEMIK:');
            $sheetDosen->setCellValue('C6', $namaPeriode);
            $sheetDosen->getStyle('A6')->getFont()->setBold(true);

            $headersDosen = ['No', 'NIM', 'Nama Mahasiswa', 'Judul Skripsi / Tugas Akhir', 'Jenis Ujian', 'Peran Penguji', 'Tanggal Ujian', 'Jam', 'Ruangan', 'Status Ujian'];
            foreach ($headersDosen as $colIdx => $hText) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheetDosen->setCellValue("{$colLetter}8", $hText);
            }
            $sheetDosen->getStyle('A8:J8')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheetDosen->getStyle('A8:J8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF7C3AED');
            $sheetDosen->getStyle('A8:J8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $dRow = 9;
            $dNo = 1;

            foreach ($mySidangs as $ms) {
                $jenisStr = 'Seminar Proposal';
                if ($ms->jenis_tugas_akhir === 'sidang' || $ms->jenis_tugas_akhir === 'skripsi') {
                    $jenisStr = 'Sidang Skripsi';
                } elseif ($ms->jenis_tugas_akhir === 'jurnal') {
                    $jenisStr = 'Jurnal';
                }

                $peran = '-';
                if ($ms->jenis_tugas_akhir === 'sempro') {
                    $peran = $ms->dosen_pembimbing_utama_id == $dosen->id ? 'Pembimbing 1 (Penguji Sempro)' : 'Pembimbing 2 (Penguji Sempro)';
                } else {
                    if ($ms->ketua_penguji_id == $dosen->id) $peran = 'Ketua Penguji';
                    elseif ($ms->anggota_penguji_1_id == $dosen->id) $peran = 'Anggota Penguji 1';
                    elseif ($ms->anggota_penguji_2_id == $dosen->id) $peran = 'Anggota Penguji 2';
                }

                $tglStr = $ms->tanggal ? Carbon::parse($ms->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') : 'Belum diplotting';
                $ruangKode = $ms->ruang ? $ms->ruang->kode_ruangan : '-';

                $sheetDosen->setCellValue("A{$dRow}", $dNo++);
                $sheetDosen->setCellValue("B{$dRow}", $ms->nim);
                $sheetDosen->setCellValue("C{$dRow}", $ms->nama_mahasiswa);
                $sheetDosen->setCellValue("D{$dRow}", $ms->judul_skripsi);
                $sheetDosen->setCellValue("E{$dRow}", $jenisStr);
                $sheetDosen->setCellValue("F{$dRow}", $peran);
                $sheetDosen->setCellValue("G{$dRow}", $tglStr);
                $sheetDosen->setCellValue("H{$dRow}", $ms->jam ?? '-');
                $sheetDosen->setCellValue("I{$dRow}", $ruangKode);
                $sheetDosen->setCellValue("J{$dRow}", ucfirst($ms->verifikasi_status ?? 'menunggu'));

                $dRow++;
            }

            $lastDRow = max(8, $dRow - 1);
            $sheetDosen->getStyle("A8:J{$lastDRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheetDosen->getStyle("A9:B{$lastDRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetDosen->getStyle("E9:F{$lastDRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetDosen->getStyle("G9:J{$lastDRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            foreach (range(1, 10) as $colIdx) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheetDosen->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $cleanPeriode = preg_replace('/[^\w\s\.,-]/', '_', $namaPeriode);
        $fileName = "SK_Penguji_Skripsi_{$cleanPeriode}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Public page for viewing lecturer examiner schedule without login
     */
    public function publicJadwalDosen(Request $request, Dosen $dosen): View
    {
        $activePeriode = Periode::where('aktif', true)->first();
        
        $mySidangs = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ])
        ->where(function ($q) use ($dosen) {
            $q->where('ketua_penguji_id', $dosen->id)
              ->orWhere('anggota_penguji_1_id', $dosen->id)
              ->orWhere('anggota_penguji_2_id', $dosen->id)
              ->orWhere('dosen_pembimbing_utama_id', $dosen->id)
              ->orWhere('dosen_pembimbing_pendamping_id', $dosen->id);
        })
        ->orderBy('tanggal', 'asc')
        ->orderBy('jam', 'asc')
        ->get();

        return view('administrasi.undangan.public-jadwal', compact('dosen', 'mySidangs', 'activePeriode'));
    }
}

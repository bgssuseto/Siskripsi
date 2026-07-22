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

        // Query sidangs for examiners
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
            'tglMulai', 'tglSelesai', 'dosenList', 'sidangs'
        ));
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
            'dosen'       => $dosen,
            'namaPeriode' => $namaPeriode,
            'rekapSesi'   => $rekapSesi,
            'sidangs'     => $mySidangs,
            'kopBase64'   => $kopBase64,
            'totalUji'    => $mySidangs->count(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = "Undangan Sidang - {$dosen->nama_dosen}.pdf";

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
        $titleText = "REKAP HARI DAN RUANG SIDANG SKRIPSI " . strtoupper($namaPeriode);
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
        $table2->addCell(2500)->addText('Penguji2', ['bold' => true, 'size' => 9]);
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

        $periode = $periodeId ? Periode::find($periodeId) : Periode::where('aktif', true)->first();
        $namaPeriode = $periode ? $periode->nama_periode : 'Periode ' . date('Y');

        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ]);

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
        }

        $dosens = Dosen::whereIn('id', $dosenExaminerIds->unique())->get();

        if ($dosens->isEmpty()) {
            return back()->with('warning', 'Tidak ada dosen penguji pada filter pendaftaran yang dipilih.');
        }

        $zipFilename = "Undangan_Sidang_Skripsi_" . str_replace(['/', ' '], '_', $namaPeriode) . ".zip";
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
                       $s->anggota_penguji_2_id == $dosen->id;
            })->sortBy('tanggal')->values();

            if ($mySidangs->isEmpty()) continue;

            $rekapSesi = $this->buildSimplifiedSessions($mySidangs);

            $pdf = Pdf::loadView('administrasi.undangan.pdf', [
                'dosen'       => $dosen,
                'namaPeriode' => $namaPeriode,
                'rekapSesi'   => $rekapSesi,
                'sidangs'     => $mySidangs,
                'kopBase64'   => $kopBase64,
                'totalUji'    => $mySidangs->count(),
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

        $this->buildUndanganExcelForDosen($sheet, $dosen, $mySidangs, $namaPeriode);

        $writer = new Xlsx($spreadsheet);
        $safeName = preg_replace('/[^\w\s\.,-]/', '_', $dosen->nama_dosen);
        $fileName = "Undangan_Menguji_{$safeName}.xlsx";

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

        $sidangs = $query->get();

        $dosenExaminerIds = collect();
        foreach ($sidangs as $s) {
            if ($s->ketua_penguji_id) $dosenExaminerIds->push($s->ketua_penguji_id);
            if ($s->anggota_penguji_1_id) $dosenExaminerIds->push($s->anggota_penguji_1_id);
            if ($s->anggota_penguji_2_id) $dosenExaminerIds->push($s->anggota_penguji_2_id);
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

            $this->buildUndanganExcelForDosen($sheet, $dosen, $mySidangs, $namaPeriode);
            $sheetIndex++;
        }

        if ($sheetIndex === 0) {
            return back()->with('warning', 'Tidak ada data penguji yang dapat diexport.');
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = "Undangan_Menguji_Semua_Dosen.xlsx";

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
    private function buildUndanganExcelForDosen($sheet, Dosen $dosen, $mySidangs, string $namaPeriode): void
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

        $sheet->setCellValue('A3', 'JADWAL UNDANGAN MENGUJI SIDANG SKRIPSI / JURNAL');
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
        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'Judul Skripsi / Artikel', 'Peran Penguji', 'Hari & Tanggal', 'Jam', 'Ruangan', 'Jenis Ujian'];
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
        $no = 1;

        foreach ($mySidangs as $s) {
            $role = '-';
            if ($s->ketua_penguji_id == $dosen->id) {
                $role = 'Ketua Penguji';
            } elseif ($s->anggota_penguji_1_id == $dosen->id) {
                $role = 'Anggota Penguji 1';
            } elseif ($s->anggota_penguji_2_id == $dosen->id) {
                $role = 'Anggota Penguji 2';
            }

            $tglStr = $s->tanggal ? Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') : '-';
            $ruangKode = $s->ruang ? $s->ruang->kode_ruangan : '-';
            $jenisStr = ($s->jenis_tugas_akhir == 'sidang' || $s->jenis_tugas_akhir == 'skripsi') ? 'Sidang Skripsi' : 'Sidang Jurnal';

            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValue("B{$currentRow}", $s->nim);
            $sheet->setCellValue("C{$currentRow}", $s->nama_mahasiswa);
            $sheet->setCellValue("D{$currentRow}", $s->judul_skripsi);
            $sheet->setCellValue("E{$currentRow}", $role);
            $sheet->setCellValue("F{$currentRow}", $tglStr);
            $sheet->setCellValue("G{$currentRow}", $s->jam ?? '-');
            $sheet->setCellValue("H{$currentRow}", $ruangKode);
            $sheet->setCellValue("I{$currentRow}", $jenisStr);

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

        $tglMulai = $request->get('tanggal_pendaftaran_mulai');
        $tglSelesai = $request->get('tanggal_pendaftaran_selesai');

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

        $sidangs = $query->orderBy('tanggal', 'asc')
                         ->orderBy('jam', 'asc')
                         ->orderBy('nama_mahasiswa', 'asc')
                         ->get();

        return view('administrasi.berita-acara.index', compact(
            'periodes', 'selectedPeriode', 'selectedPeriodeId',
            'tglMulai', 'tglSelesai', 'sidangs'
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

    public function skIndex(): View
    {
        return view('administrasi.sk.index');
    }
}

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
 * SK Pembimbing & SK Penguji (Excel/PDF).
 * (Dipecah dari AdministrasiController.)
 */
class SkController extends Controller
{
    use Concerns\BantuCetakAdministrasi;

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

        $selectedGelombang = $request->get('gelombang');
        // SK Pembimbing mencakup tahap Sempro → tampilkan gelombang Sempro & Skripsi.
        $gelombangOptions  = $this->gelombangOptions($selectedPeriodeId ? (int) $selectedPeriodeId : null, null);

        $query = Sidang::query();
        if ($selectedPeriodeId) {
            $query->where('periode_id', $selectedPeriodeId);
        }
        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        // SK Pembimbing & Penguji hanya dihitung dari ujian skripsi — sempro tidak ikut.
        $query->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET);

        $totalSidangs       = (clone $query)->count();
        $totalJurnal        = (clone $query)->where('jenis_tugas_akhir', 'jurnal')->count();
        $totalSidangReguler = $totalSidangs - $totalJurnal;

        // SK Pembimbing dihitung sejak tahap Sempro (disetujui) + ujian skripsi, satu
        // baris per mahasiswa — lihat SkPdfService::pembimbingSidangs().
        $pembimbingSidangs = \App\Services\SkPdfService::pembimbingSidangs([
            'periode_id' => $selectedPeriodeId,
            'gelombang'  => $selectedGelombang,
        ]);
        $totalBimbingan       = $pembimbingSidangs->count();
        $totalBimbinganSempro = $pembimbingSidangs->where('jenis_tugas_akhir', 'sempro')->count();
        $dosenPembimbingIds = $pembimbingSidangs->pluck('dosen_pembimbing_utama_id')
            ->merge($pembimbingSidangs->pluck('dosen_pembimbing_pendamping_id'))
            ->filter()->unique();

        $dosenPengujiIds = (clone $query)->pluck('ketua_penguji_id')
            ->merge((clone $query)->pluck('anggota_penguji_1_id'))
            ->merge((clone $query)->pluck('anggota_penguji_2_id'))
            ->filter()->unique();

        // Infografis: distribusi jalur, dan beban kerja top-8 dosen pembimbing/penguji
        // pada filter periode/jenis yang sedang aktif.
        $sidangsForChart = (clone $query)->get();
        $dosensAll = Dosen::orderBy('nama_dosen')->get();

        $pembimbingChart = $dosensAll->map(function ($d) use ($pembimbingSidangs) {
            $total = $pembimbingSidangs->where('dosen_pembimbing_utama_id', $d->id)->count()
                + $pembimbingSidangs->where('dosen_pembimbing_pendamping_id', $d->id)->count();
            return ['nama' => $d->nama_dosen, 'total' => $total];
        })->filter(fn ($row) => $row['total'] > 0)
            ->sortByDesc('total')->take(8)->values();

        $pengujiChart = $dosensAll->map(function ($d) use ($sidangsForChart) {
            $total = $sidangsForChart->where('ketua_penguji_id', $d->id)->count()
                + $sidangsForChart->where('anggota_penguji_1_id', $d->id)->count()
                + $sidangsForChart->where('anggota_penguji_2_id', $d->id)->count();
            return ['nama' => $d->nama_dosen, 'total' => $total];
        })->filter(fn ($row) => $row['total'] > 0)
            ->sortByDesc('total')->take(8)->values();

        $statusFinal = $selectedPeriode ? \App\Services\SkPdfService::statusFinal($selectedPeriode) : null;

        return view('administrasi.sk.index', compact(
            'periodes', 'selectedPeriode', 'selectedPeriodeId',
            'totalSidangs', 'totalJurnal', 'totalSidangReguler', 'totalBimbingan', 'totalBimbinganSempro',
            'dosenPembimbingIds', 'dosenPengujiIds', 'jenis',
            'pembimbingChart', 'pengujiChart', 'selectedGelombang', 'gelombangOptions', 'statusFinal'
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

        // Bimbingan sejak tahap Sempro (disetujui) + ujian skripsi, satu baris per
        // mahasiswa — sama dengan PDF SK Pembimbing (SkPdfService::pembimbingSidangs()).
        $sidangs = \App\Services\SkPdfService::pembimbingSidangs([
            'periode_id'      => $selectedPeriodeId,
            'gelombang'       => $request->get('gelombang'),
            'tanggal_mulai'   => $request->get('tanggal_pendaftaran_mulai'),
            'tanggal_selesai' => $request->get('tanggal_pendaftaran_selesai'),
        ])->load(['pembimbingUtama', 'pembimbingPendamping', 'periode']);

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

        $selectedGelombang = $request->get('gelombang');
        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        // SK Pembimbing & Penguji hanya dihitung dari ujian skripsi (skripsi /
        // sidang / jurnal) — sempro tidak ikut dihitung.
        $query->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET);

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
     * Export SK Pembimbing (PDF) — a compact rangkuman report: per-dosen bimbingan
     * totals plus the full mahasiswa/pembimbing master list, using the same filters
     * as the Excel export. Meant as a quick printable summary, not a per-dosen
     * breakdown (use the Excel export for that level of detail).
     */
    public function exportSkPembimbingPdf(Request $request)
    {
        return $this->exportSkPdf($request, 'pembimbing');
    }

    /**
     * Export SK Penguji (PDF) — format surat permohonan yang sama dengan SK
     * Pembimbing, berisi Ketua/Anggota Penguji. Untuk Sempro penguji = pembimbing
     * (ketua = pembimbing utama, anggota = pembimbing pendamping).
     */
    public function exportSkPengujiPdf(Request $request)
    {
        return $this->exportSkPdf($request, 'penguji');
    }

    /**
     * Surat Permohonan SK Pembimbing / Penguji mengikuti format
     * "PENGAJUAN SK PENGUJI GANJIL2526.docx": surat ke Dekan, Lampiran 1
     * (rekap jumlah per dosen), lalu rincian mahasiswa per dosen.
     */
    private function exportSkPdf(Request $request, string $mode)
    {
        $this->siapkanCetakBesar();

        $hasil = \App\Services\SkPdfService::build($mode, [
            'periode_id'      => $this->resolvePeriodeId($request),
            'gelombang'       => $request->get('gelombang'),
            'tanggal_mulai'   => $request->get('tanggal_pendaftaran_mulai'),
            'tanggal_selesai' => $request->get('tanggal_pendaftaran_selesai'),
        ]);

        if (!$hasil) {
            return back()->with('warning', 'Tidak ada data pada filter yang dipilih.');
        }

        return $hasil['pdf']->download($hasil['filename']);
    }
}

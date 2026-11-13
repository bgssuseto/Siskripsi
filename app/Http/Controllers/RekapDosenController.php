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
 * Rekap pembimbing & rekap dosen penguji (Excel).
 * (Dipecah dari AdministrasiController.)
 */
class RekapDosenController extends Controller
{
    /**
     * Halaman Rekap Dosen Pembimbing — jumlah mahasiswa bimbingan per dosen,
     * dipisah antara Pembimbing Utama dan Pembimbing Pendamping.
     * Filter: Periode Akademik, Gelombang, Jenis TA.
     */
    public function rekapPembimbingIndex(Request $request): View
    {
        $periodes = Periode::orderBy('id', 'desc')->get();

        $selectedPeriodeId = $request->get('periode_id');
        if (!$selectedPeriodeId) {
            $activePeriode = Periode::where('aktif', true)->first();
            $selectedPeriodeId = $activePeriode ? $activePeriode->id : ($periodes->first()?->id);
        }

        $selectedPeriode = Periode::find($selectedPeriodeId);
        $jenisTa = $request->get('jenis');
        $selectedGelombang = $request->get('gelombang');

        $query = Sidang::with(['pembimbingUtama', 'pembimbingPendamping']);

        if ($selectedPeriodeId) {
            $query->where('periode_id', $selectedPeriodeId);
        }

        if ($jenisTa) {
            if ($jenisTa === 'sempro') {
                $query->where('jenis_tugas_akhir', 'sempro');
            } else {
                $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
            }
        }

        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        $sidangs = $query->get();

        $jenisBucket = $jenisTa === 'sempro' ? 'sempro' : 'skripsi';
        $gelombangOptions = $selectedPeriodeId
            ? PendaftaranPeriode::where('periode_id', $selectedPeriodeId)
                ->where('jenis', $jenisBucket)
                ->orderBy('gelombang')
                ->pluck('gelombang')
            : collect();

        $dosenRows = Dosen::orderBy('nama_dosen')->get()->map(function ($d) use ($sidangs) {
            $countUtama = $sidangs->where('dosen_pembimbing_utama_id', $d->id)->count();
            $countPendamping = $sidangs->where('dosen_pembimbing_pendamping_id', $d->id)->count();

            $mahasiswa = $sidangs->filter(function ($s) use ($d) {
                return $s->dosen_pembimbing_utama_id == $d->id || $s->dosen_pembimbing_pendamping_id == $d->id;
            })->map(function ($s) use ($d) {
                return [
                    'nim'   => $s->nim,
                    'nama'  => $s->nama_mahasiswa,
                    'judul' => $s->judul_skripsi,
                    'peran' => $s->dosen_pembimbing_utama_id == $d->id ? 'Pembimbing Utama' : 'Pembimbing Pendamping',
                ];
            })->values();

            return [
                'dosen'      => $d,
                'utama'      => $countUtama,
                'pendamping' => $countPendamping,
                'total'      => $countUtama + $countPendamping,
                'mahasiswa'  => $mahasiswa,
            ];
        })->filter(fn ($row) => $row['total'] > 0)->values();

        return view('administrasi.rekap-pembimbing.index', compact(
            'periodes', 'selectedPeriode', 'selectedPeriodeId',
            'jenisTa', 'selectedGelombang', 'gelombangOptions', 'dosenRows'
        ));
    }

    /**
     * Export Rekap Dosen Penguji – multi-sheet Excel (.xlsx)
     * Format: 1 sheet per dosen, columns: NIM | Nama Mahasiswa | Peran | Ketua Penguji | Anggota Penguji 1 | Anggota Penguji 2 | Hari | Jam | Ruangan
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

        $selectedGelombang = $request->get('gelombang');
        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        $sidangs = $query->orderBy('tanggal')->orderBy('jam')->get();

        // Collect all dosen IDs that appear as penguji
        $dosenExaminerIds = collect();
        foreach ($sidangs as $s) {
            if ($s->ketua_penguji_id) $dosenExaminerIds->push($s->ketua_penguji_id);
            if ($s->anggota_penguji_1_id) $dosenExaminerIds->push($s->anggota_penguji_1_id);
            if ($s->anggota_penguji_2_id) $dosenExaminerIds->push($s->anggota_penguji_2_id);
        }

        $dosens = Dosen::whereIn('id', $dosenExaminerIds->unique())->excludingSuperAdminPlaceholder()->orderBy('nama_dosen')->get();

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

            // ── Header Row ── (Sempro: Anggota Penguji 1 & 2; Skripsi: + Ketua Penguji)
            $labelDewan = Sidang::labelDewanPengujiUndangan($jenisUndangan);
            $headers = array_merge(['NIM', 'Nama Mahasiswa', 'Peran'], $labelDewan, ['Hari', 'Jam', 'Ruangan']);
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
            foreach ($headers as $colIdx => $headerText) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->setCellValue("{$colLetter}1", $headerText);
            }

            $headerRange = "A1:{$lastCol}1";
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // ── Data Rows ──
            $currentRow = 2;
            foreach ($mySidangs as $s) {
                $tglStr = $s->tanggal
                    ? Carbon::parse($s->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y')
                    : '-';
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

            // Auto-size columns
            foreach (range(1, count($headers)) as $colIdx) {
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
}

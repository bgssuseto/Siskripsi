<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendaftaranController extends Controller
{
    /**
     * Display Sempro registrations verification list
     */
    public function semproIndex(Request $request): View
    {
        $query = Sidang::with([
            'pembimbingUtama',
            'pembimbingPendamping',
            'periode'
        ])->where('jenis_tugas_akhir', 'sempro');

        // Filter search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mahasiswa', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('judul_skripsi', 'like', "%{$search}%");
            });
        }

        // Filter verifikasi status (default shows 'menunggu' or all)
        if ($verifikasiStatus = $request->get('verifikasi_status')) {
            $query->where('verifikasi_status', $verifikasiStatus);
        }

        // Filter periode
        if ($periodeId = $request->get('periode_id')) {
            $query->where('periode_id', $periodeId);
        } else {
            $activePeriode = Periode::where('aktif', true)->first();
            if ($activePeriode) {
                $query->where('periode_id', $activePeriode->id);
            }
        }

        $sidangs = $query->orderByRaw("CASE WHEN verifikasi_status = 'menunggu' THEN 1 WHEN verifikasi_status = 'ditolak' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();

        $counts = [
            'total'     => Sidang::where('jenis_tugas_akhir', 'sempro')->count(),
            'menunggu'  => Sidang::where('jenis_tugas_akhir', 'sempro')->where('verifikasi_status', 'menunggu')->count(),
            'disetujui' => Sidang::where('jenis_tugas_akhir', 'sempro')->where('verifikasi_status', 'disetujui')->count(),
            'ditolak'   => Sidang::where('jenis_tugas_akhir', 'sempro')->where('verifikasi_status', 'ditolak')->count(),
        ];

        return view('pendaftaran.sempro', compact('sidangs', 'periodes', 'activePeriode', 'counts'));
    }

    /**
     * Display Skripsi registrations verification list
     */
    public function skripsiIndex(Request $request): View
    {
        $query = Sidang::with([
            'pembimbingUtama',
            'pembimbingPendamping',
            'periode'
        ])->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);

        // Filter search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mahasiswa', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('judul_skripsi', 'like', "%{$search}%");
            });
        }

        // Filter verifikasi status
        if ($verifikasiStatus = $request->get('verifikasi_status')) {
            $query->where('verifikasi_status', $verifikasiStatus);
        }

        // Filter periode
        if ($periodeId = $request->get('periode_id')) {
            $query->where('periode_id', $periodeId);
        } else {
            $activePeriode = Periode::where('aktif', true)->first();
            if ($activePeriode) {
                $query->where('periode_id', $activePeriode->id);
            }
        }

        $sidangs = $query->orderByRaw("CASE WHEN verifikasi_status = 'menunggu' THEN 1 WHEN verifikasi_status = 'ditolak' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();

        $counts = [
            'total'     => Sidang::whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])->count(),
            'menunggu'  => Sidang::whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])->where('verifikasi_status', 'menunggu')->count(),
            'disetujui' => Sidang::whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])->where('verifikasi_status', 'disetujui')->count(),
            'ditolak'   => Sidang::whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])->where('verifikasi_status', 'ditolak')->count(),
        ];

        return view('pendaftaran.skripsi', compact('sidangs', 'periodes', 'activePeriode', 'counts'));
    }

    /**
     * Process verification action (Approve / Reject)
     */
    public function verifikasi(Request $request, Sidang $sidang)
    {
        $validated = $request->validate([
            'verifikasi_status'   => ['required', 'string', 'in:disetujui,ditolak,menunggu'],
            'verifikasi_komentar' => ['nullable', 'string', 'max:500'],
        ]);

        $sidang->update([
            'verifikasi_status'   => $validated['verifikasi_status'],
            'verifikasi_komentar' => $validated['verifikasi_status'] === 'ditolak' ? $validated['verifikasi_komentar'] : null,
            'verifikasi_tanggal'  => now(),
        ]);

        $statusMessage = match($validated['verifikasi_status']) {
            'disetujui' => "Pendaftaran mahasiswa {$sidang->nama_mahasiswa} ({$sidang->nim}) berhasil disetujui! Data kini muncul pada Master Data.",
            'ditolak'   => "Pendaftaran mahasiswa {$sidang->nama_mahasiswa} ({$sidang->nim}) telah ditolak dengan catatan.",
            default     => "Status pendaftaran mahasiswa {$sidang->nama_mahasiswa} diubah menjadi Menunggu Verifikasi."
        };

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'sidang'  => $sidang->fresh()
            ]);
        }

        return back()->with('success', $statusMessage);
    }

    /**
     * Delete pendaftaran record so student can re-register
     */
    public function destroy(Request $request, Sidang $sidang)
    {
        $namaMahasiswa = $sidang->nama_mahasiswa;
        $nim = $sidang->nim;
        $jenis = $sidang->jenis_tugas_akhir === 'sempro' ? 'Sempro' : 'Skripsi';

        $sidang->delete();

        $message = "Data pendaftaran {$jenis} mahasiswa {$namaMahasiswa} ({$nim}) berhasil dihapus. Mahasiswa kini dapat melakukan pendaftaran ulang.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Export Pendaftaran Sempro to Excel
     */
    public function exportExcelSempro(Request $request)
    {
        $query = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'periode'])
            ->where('jenis_tugas_akhir', 'sempro');

        if ($s = $request->get('search')) {
            $query->where(fn($q) => $q->where('nama_mahasiswa', 'like', "%$s%")->orWhere('nim', 'like', "%$s%")->orWhere('judul_skripsi', 'like', "%$s%"));
        }
        if ($vs = $request->get('verifikasi_status')) $query->where('verifikasi_status', $vs);
        if ($pid = $request->get('periode_id')) {
            $query->where('periode_id', $pid);
        } else {
            $ap = \App\Models\Periode::where('aktif', true)->first();
            if ($ap) $query->where('periode_id', $ap->id);
        }

        $data = $query->orderByRaw("CASE WHEN verifikasi_status='menunggu' THEN 1 WHEN verifikasi_status='ditolak' THEN 2 ELSE 3 END")->orderByDesc('id')->get();

        return $this->generateExcelDownload($data, 'Pendaftaran Sempro', [
            'No', 'NIM', 'Nama Mahasiswa', 'No. WA', 'Judul Proposal',
            'Dosbing Utama', 'Dosbing Pendamping', 'Periode', 'Tgl Daftar',
            'Status Verifikasi', 'Catatan'
        ], function ($s, $i) {
            return [
                $i + 1, $s->nim, $s->nama_mahasiswa, $s->no_wa_aktif ?? '-', $s->judul_skripsi,
                $s->pembimbingUtama?->nama_dosen ?? '-', $s->pembimbingPendamping?->nama_dosen ?? '-',
                $s->periode?->nama_periode ?? '-',
                $s->tanggal_pendaftaran ? \Carbon\Carbon::parse($s->tanggal_pendaftaran)->locale('id')->translatedFormat('l, d/m/Y') : '-',
                ucfirst($s->verifikasi_status ?? 'menunggu'),
                $s->verifikasi_komentar ?? '-',
            ];
        }, 'FF1E293B', 'Pendaftaran_Sempro');
    }

    /**
     * Export Pendaftaran Skripsi to Excel
     */
    public function exportExcelSkripsi(Request $request)
    {
        $query = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'periode'])
            ->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);

        if ($s = $request->get('search')) {
            $query->where(fn($q) => $q->where('nama_mahasiswa', 'like', "%$s%")->orWhere('nim', 'like', "%$s%")->orWhere('judul_skripsi', 'like', "%$s%"));
        }
        if ($vs = $request->get('verifikasi_status')) $query->where('verifikasi_status', $vs);
        if ($pid = $request->get('periode_id')) {
            $query->where('periode_id', $pid);
        } else {
            $ap = \App\Models\Periode::where('aktif', true)->first();
            if ($ap) $query->where('periode_id', $ap->id);
        }

        $data = $query->orderByRaw("CASE WHEN verifikasi_status='menunggu' THEN 1 WHEN verifikasi_status='ditolak' THEN 2 ELSE 3 END")->orderByDesc('id')->get();

        return $this->generateExcelDownload($data, 'Pendaftaran Skripsi', [
            'No', 'NIM', 'Nama Mahasiswa', 'No. WA', 'Judul Skripsi / Proposal',
            'Jenis TA', 'Dosbing Utama', 'Dosbing Pendamping', 'Periode', 'Tgl Daftar',
            'Status Verifikasi', 'Catatan'
        ], function ($s, $i) {
            return [
                $i + 1, $s->nim, $s->nama_mahasiswa, $s->no_wa_aktif ?? '-', $s->judul_skripsi,
                ucfirst($s->jenis_tugas_akhir),
                $s->pembimbingUtama?->nama_dosen ?? '-', $s->pembimbingPendamping?->nama_dosen ?? '-',
                $s->periode?->nama_periode ?? '-',
                $s->tanggal_pendaftaran ? \Carbon\Carbon::parse($s->tanggal_pendaftaran)->locale('id')->translatedFormat('l, d/m/Y') : '-',
                ucfirst($s->verifikasi_status ?? 'menunggu'),
                $s->verifikasi_komentar ?? '-',
            ];
        }, 'FF581C87', 'Pendaftaran_Skripsi');
    }

    private function generateExcelDownload($data, $title, $headers, $rowBuilder, $headerColor, $filenamePrefix)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title);

        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getStyle("{$col}1")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($headerColor);
            $sheet->getStyle("{$col}1")->getFont()->getColor()->setARGB('FFFFFFFF');
        }

        foreach ($data as $i => $s) {
            $row = $i + 2;
            $values = $rowBuilder($s, $i);
            foreach ($values as $j => $val) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j + 1);
                $sheet->setCellValue("{$col}{$row}", $val);
            }
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "{$filenamePrefix}_" . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}


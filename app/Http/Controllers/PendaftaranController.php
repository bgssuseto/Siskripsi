<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendaftaranController extends Controller
{
    /**
     * Display Sempro registrations verification list
     */
    /**
     * Shared filter-builder for the Sempro verification page (pendaftaran.sempro.*):
     * used by both the index page and its Excel export, so export always matches
     * whatever filters are currently applied on screen (or the full table when none
     * are applied).
     */
    private function buildPendaftaranSemproQuery(Request $request): array
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
                $periodeId = $activePeriode->id;
                $query->where('periode_id', $activePeriode->id);
            }
        }

        // Filter gelombang
        $selectedGelombang = $request->get('gelombang');
        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        // Filter Dosen Pembimbing (Utama atau Pendamping) — Sempro tidak memiliki peran penguji
        if ($dosenPembimbingId = $request->get('dosen_pembimbing_id')) {
            $query->where(function ($q) use ($dosenPembimbingId) {
                $q->where('dosen_pembimbing_utama_id', $dosenPembimbingId)
                  ->orWhere('dosen_pembimbing_pendamping_id', $dosenPembimbingId);
            });
        }

        return ['query' => $query, 'periode_id' => $periodeId, 'gelombang' => $selectedGelombang];
    }

    public function semproIndex(Request $request): View
    {
        $built = $this->buildPendaftaranSemproQuery($request);
        $query = $built['query'];
        $periodeId = $built['periode_id'];
        $selectedGelombang = $built['gelombang'];

        $gelombangOptions = $periodeId
            ? PendaftaranPeriode::where('periode_id', $periodeId)
                ->where('jenis', 'sempro')
                ->orderBy('gelombang')
                ->pluck('gelombang')
            : collect();

        $dosens = \App\Models\Dosen::orderBy('nama_dosen')->get();

        $sidangs = $query->orderByRaw("CASE WHEN verifikasi_status = 'menunggu' THEN 1 WHEN verifikasi_status = 'ditolak' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();

        $counts = [
            'total'     => Sidang::where('jenis_tugas_akhir', 'sempro')->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))->count(),
            'menunggu'  => Sidang::where('jenis_tugas_akhir', 'sempro')->where('verifikasi_status', 'menunggu')->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))->count(),
            'disetujui' => Sidang::where('jenis_tugas_akhir', 'sempro')->where('verifikasi_status', 'disetujui')->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))->count(),
            'ditolak'   => Sidang::where('jenis_tugas_akhir', 'sempro')->where('verifikasi_status', 'ditolak')->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))->count(),
        ];

        return view('pendaftaran.sempro', compact('sidangs', 'periodes', 'activePeriode', 'counts', 'selectedGelombang', 'gelombangOptions', 'dosens'));
    }

    /**
     * Display Skripsi registrations verification list
     */
    /**
     * Shared filter-builder for the Skripsi verification page (pendaftaran.skripsi.*):
     * used by both the index page and its Excel export, so export always matches
     * whatever filters are currently applied on screen (or the full table when none
     * are applied).
     */
    private function buildPendaftaranSkripsiQuery(Request $request): array
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
                $periodeId = $activePeriode->id;
                $query->where('periode_id', $activePeriode->id);
            }
        }

        // Filter gelombang
        $selectedGelombang = $request->get('gelombang');
        if ($selectedGelombang !== null && $selectedGelombang !== '') {
            $query->where('gelombang', $selectedGelombang);
        }

        // Filter Dosen Pembimbing (Utama atau Pendamping)
        if ($dosenPembimbingId = $request->get('dosen_pembimbing_id')) {
            $query->where(function ($q) use ($dosenPembimbingId) {
                $q->where('dosen_pembimbing_utama_id', $dosenPembimbingId)
                  ->orWhere('dosen_pembimbing_pendamping_id', $dosenPembimbingId);
            });
        }

        // Filter Dosen Penguji (Ketua, Penguji 1, atau Penguji 2)
        if ($dosenPengujiId = $request->get('dosen_penguji_id')) {
            $query->where(function ($q) use ($dosenPengujiId) {
                $q->where('ketua_penguji_id', $dosenPengujiId)
                  ->orWhere('anggota_penguji_1_id', $dosenPengujiId)
                  ->orWhere('anggota_penguji_2_id', $dosenPengujiId);
            });
        }

        return ['query' => $query, 'periode_id' => $periodeId, 'gelombang' => $selectedGelombang];
    }

    public function skripsiIndex(Request $request): View
    {
        $built = $this->buildPendaftaranSkripsiQuery($request);
        $query = $built['query'];
        $periodeId = $built['periode_id'];
        $selectedGelombang = $built['gelombang'];

        $gelombangOptions = $periodeId
            ? PendaftaranPeriode::where('periode_id', $periodeId)
                ->where('jenis', 'skripsi')
                ->orderBy('gelombang')
                ->pluck('gelombang')
            : collect();

        $dosens = \App\Models\Dosen::orderBy('nama_dosen')->get();

        $sidangs = $query->orderByRaw("CASE WHEN verifikasi_status = 'menunggu' THEN 1 WHEN verifikasi_status = 'ditolak' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();

        $counts = [
            'total'     => Sidang::whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))->count(),
            'menunggu'  => Sidang::whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)->where('verifikasi_status', 'menunggu')->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))->count(),
            'disetujui' => Sidang::whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)->where('verifikasi_status', 'disetujui')->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))->count(),
            'ditolak'   => Sidang::whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)->where('verifikasi_status', 'ditolak')->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))->count(),
        ];

        return view('pendaftaran.skripsi', compact('sidangs', 'periodes', 'activePeriode', 'counts', 'selectedGelombang', 'gelombangOptions', 'dosens'));
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

        $statusBefore = $sidang->verifikasi_status;

        $sidang->update([
            'verifikasi_status'   => $validated['verifikasi_status'],
            'verifikasi_komentar' => $validated['verifikasi_status'] === 'ditolak' ? $validated['verifikasi_komentar'] : null,
            'verifikasi_tanggal'  => now(),
        ]);

        ActivityLogger::log(
            'verifikasi',
            $sidang,
            "Mengubah status verifikasi pendaftaran {$sidang->nama_mahasiswa} ({$sidang->nim}) dari \"{$statusBefore}\" menjadi \"{$validated['verifikasi_status']}\"." .
                (!empty($validated['verifikasi_komentar']) ? " Catatan: {$validated['verifikasi_komentar']}" : ''),
            ['before' => ['verifikasi_status' => $statusBefore], 'after' => ['verifikasi_status' => $validated['verifikasi_status']]]
        );

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
     * Admin/koordinator directly registers a student (e.g. a remidi/retake student
     * who can no longer self-register, or any manual entry). Unlike the student's
     * own self-service registration, this is allowed to create a new record even if
     * the student already has records in OTHER periods — a fresh attempt in a new
     * period is legitimate. Duplicate registration within the SAME period+jenis is
     * still blocked. The record is created as already 'disetujui' since an
     * admin/koordinator is registering it directly.
     */
    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'nim'                            => ['required', 'string', 'max:30'],
            'nama_mahasiswa'                 => ['required', 'string', 'max:255'],
            'jenis_tugas_akhir'               => ['required', 'string', 'in:sempro,skripsi'],
            'jenis_ta_pilihan'                => ['nullable', 'string', 'in:sidang,jurnal'],
            'judul_skripsi'                   => ['required', 'string'],
            'dosen_pembimbing_utama_id'       => ['required', 'exists:dosens,id'],
            'dosen_pembimbing_pendamping_id'  => ['nullable', 'exists:dosens,id'],
            'no_wa_aktif'                     => ['nullable', 'string', 'max:20'],
            'periode_id'                      => ['nullable', 'exists:periodes,id'],
        ]);

        $periode = $validated['periode_id']
            ? Periode::find($validated['periode_id'])
            : Periode::where('aktif', true)->first();

        if (!$periode) {
            $msg = 'Tidak ada periode akademik yang aktif/dipilih.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $jenisBucket = $validated['jenis_tugas_akhir'] === 'sempro' ? ['sempro'] : Sidang::SKRIPSI_BUCKET;

        $user = \App\Models\User::where('nim', $validated['nim'])->first();
        if ($user && $user->status_kelulusan === 'lulus') {
            $msg = "Mahasiswa {$validated['nama_mahasiswa']} ({$validated['nim']}) sudah dinyatakan LULUS dan tidak dapat didaftarkan lagi.";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $duplicate = Sidang::where('nim', $validated['nim'])
            ->whereIn('jenis_tugas_akhir', $jenisBucket)
            ->where('periode_id', $periode->id)
            ->exists();

        if ($duplicate) {
            $msg = "Mahasiswa {$validated['nama_mahasiswa']} ({$validated['nim']}) sudah memiliki pendaftaran " .
                ($validated['jenis_tugas_akhir'] === 'sempro' ? 'Sempro' : 'Skripsi') . " pada periode {$periode->nama_periode}.";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $actualJenis = $validated['jenis_tugas_akhir'] === 'skripsi'
            ? ($validated['jenis_ta_pilihan'] ?? 'sidang')
            : 'sempro';

        $sidang = Sidang::create([
            'nim'                            => $validated['nim'],
            'nama_mahasiswa'                 => $validated['nama_mahasiswa'],
            'judul_skripsi'                  => $validated['judul_skripsi'],
            'dosen_pembimbing_utama_id'      => $validated['dosen_pembimbing_utama_id'],
            'dosen_pembimbing_pendamping_id' => $validated['dosen_pembimbing_pendamping_id'] ?? null,
            'jenis_tugas_akhir'               => $actualJenis,
            'jalur_ta'                        => $validated['jenis_tugas_akhir'] === 'sempro' ? ($validated['jenis_ta_pilihan'] ?? null) : null,
            'periode_id'                      => $periode->id,
            'tanggal_pendaftaran'             => now()->timezone('Asia/Jakarta')->format('Y-m-d'),
            'no_wa_aktif'                     => $validated['no_wa_aktif'] ?? null,
            'verifikasi_status'               => 'disetujui',
            'verifikasi_tanggal'              => now(),
        ]);

        ActivityLogger::log(
            'created',
            $sidang,
            "Mendaftarkan {$sidang->nama_mahasiswa} ({$sidang->nim}) secara manual untuk periode {$periode->nama_periode} (oleh admin/koordinator)."
        );

        $message = "Mahasiswa {$sidang->nama_mahasiswa} ({$sidang->nim}) berhasil didaftarkan.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /**
     * Bulk verifikasi (setujui / tolak) beberapa pendaftaran sekaligus.
     */
    public function bulkVerifikasi(Request $request)
    {
        $validated = $request->validate([
            'ids'                 => ['required', 'array', 'min:1'],
            'ids.*'                => ['integer', 'exists:sidangs,id'],
            'verifikasi_status'   => ['required', 'string', 'in:disetujui,ditolak,menunggu'],
            'verifikasi_komentar' => ['nullable', 'string', 'max:500'],
        ]);

        $sidangs = Sidang::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($sidangs as $sidang) {
            $statusBefore = $sidang->verifikasi_status;
            $sidang->update([
                'verifikasi_status'   => $validated['verifikasi_status'],
                'verifikasi_komentar' => $validated['verifikasi_status'] === 'ditolak' ? ($validated['verifikasi_komentar'] ?? null) : null,
                'verifikasi_tanggal'  => now(),
            ]);

            ActivityLogger::log(
                'verifikasi',
                $sidang,
                "Mengubah status verifikasi pendaftaran {$sidang->nama_mahasiswa} ({$sidang->nim}) dari \"{$statusBefore}\" menjadi \"{$validated['verifikasi_status']}\" (bulk action)." .
                    (!empty($validated['verifikasi_komentar']) ? " Catatan: {$validated['verifikasi_komentar']}" : ''),
                ['before' => ['verifikasi_status' => $statusBefore], 'after' => ['verifikasi_status' => $validated['verifikasi_status']]]
            );
            $count++;
        }

        $statusLabel = match ($validated['verifikasi_status']) {
            'disetujui' => 'disetujui',
            'ditolak'   => 'ditolak',
            default     => 'diubah menjadi menunggu verifikasi',
        };

        $message = "✅ {$count} pendaftaran berhasil {$statusLabel}.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /**
     * Bulk hapus beberapa pendaftaran sekaligus, agar mahasiswa terkait dapat daftar ulang.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:sidangs,id'],
        ]);

        $count = Sidang::whereIn('id', $validated['ids'])->delete();

        $message = "🗑️ {$count} data pendaftaran berhasil dihapus. Mahasiswa terkait kini dapat melakukan pendaftaran ulang.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /**
     * Export Pendaftaran Sempro to Excel
     */
    public function exportExcelSempro(Request $request)
    {
        $query = $this->buildPendaftaranSemproQuery($request)['query'];

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
        $query = $this->buildPendaftaranSkripsiQuery($request)['query'];

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


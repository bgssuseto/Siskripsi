<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use App\Models\Dosen;
use App\Models\Ruang;
use App\Models\Periode;
use App\Services\SidangConflictService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class SemproController extends Controller
{
    // ─── Index ───────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $query = Sidang::with([
            'pembimbingUtama',
            'pembimbingPendamping',
            'ruang',
            'periode'
        ])->where('jenis_tugas_akhir', 'sempro')
          ->where('verifikasi_status', 'disetujui');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mahasiswa', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('judul_skripsi', 'like', "%{$search}%")
                  ->orWhereHas('pembimbingUtama', function ($dq) use ($search) {
                      $dq->where('nama_dosen', 'like', "%{$search}%");
                  })
                  ->orWhereHas('ruang', function ($rq) use ($search) {
                      $rq->where('nama_ruangan', 'like', "%{$search}%")
                         ->orWhere('kode_ruangan', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Status
        if ($status = $request->get('status')) {
            if ($status === 'belum') {
                $query->whereNull('tanggal');
            } elseif ($status === 'sudah') {
                $query->whereNotNull('tanggal');
            }
        }

        // Filter Tanggal
        if ($tanggal = $request->get('tanggal')) {
            $query->whereDate('tanggal', $tanggal);
        }

        // Filter Periode
        if ($periodeId = $request->get('periode_id')) {
            $query->where('periode_id', $periodeId);
        } else {
            // Default to active period if exists
            $activePeriode = Periode::where('aktif', true)->first();
            if ($activePeriode) {
                $query->where('periode_id', $activePeriode->id);
            }
        }

        // Fetch all records in DB to compute global conflict detection for table badges & calendar
        $allSidangs = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ruang', 'periode'
        ])->get();

        // Detect all conflicts across the entire database
        $conflictMap = SidangConflictService::detectAllConflicts($allSidangs);

        // Get array of IDs that have conflicts
        $conflictIds = [];
        foreach ($conflictMap as $sId => $cEntry) {
            if (!empty($cEntry['schedule']) || !empty($cEntry['rules'])) {
                $conflictIds[] = $sId;
            }
        }

        // Get matching records
        $allMatching = $query->get();

        // Sort: Items WITH conflict FIRST, then date, jam, id
        $sortedSidangs = $allMatching->sortBy(function ($s) use ($conflictIds) {
            $hasConflict = in_array($s->id, $conflictIds);
            $tglOrder    = $s->tanggal ? $s->tanggal->format('Y-m-d') : '9999-12-31';
            $jamOrder    = $s->jam ?? '99:99';

            return [
                $hasConflict ? 0 : 1,
                $tglOrder,
                $jamOrder,
                $s->id
            ];
        })->values();

        // Paginate (per_page options: 5, 10, 25, 100)
        $perPage = (int) $request->get('per_page', 5);
        if (!in_array($perPage, [5, 10, 25, 100])) {
            $perPage = 5;
        }

        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage('page') ?: 1;
        $currentPageItems = $sortedSidangs->slice(($page - 1) * $perPage, $perPage)->values();

        $sidangs = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageItems,
            $sortedSidangs->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        // Dropdown lists
        $dosens = Dosen::orderBy('nama_dosen')->get();
        $ruangs = Ruang::orderBy('kode_ruangan')->get();
        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();

        // Filter valid dates for calendar events (only sempro for this view)
        $calendarEvents = $allSidangs->filter(fn($s) => !empty($s->tanggal) && $s->jenis_tugas_akhir === 'sempro')->map(function ($s) use ($conflictMap) {
            $conflictEntry   = $conflictMap[$s->id] ?? [];
            $hasSchedule     = !empty($conflictEntry['schedule']);
            $hasConflict     = $hasSchedule;

            $prefix      = $hasSchedule ? '⚠️ ' : '';
            $title       = $prefix . $s->nama_mahasiswa . ' (Sempro)';
            $ruangName   = $s->ruang ? $s->ruang->kode_ruangan : 'TBA';
            $dosbing     = $s->pembimbingUtama ? $s->pembimbingUtama->nama_dosen : 'TBA';

            $description = "NIM: {$s->nim}\nJudul: {$s->judul_skripsi}\nDosbing: {$dosbing}\nJam: {$s->jam}\nRuang: {$ruangName}";
            if ($hasSchedule) {
                $description .= "\n\n⚠️ BENTROK JADWAL:\n" . implode("\n", $conflictEntry['schedule']);
            }

            $eventColor = $hasSchedule ? '#ef4444' : '#a855f7'; // Purple for sempro
            $borderColor = $hasSchedule ? '#dc2626' : '#9333ea';

            return [
                'id'              => $s->id,
                'title'           => $title,
                'start'           => $s->tanggal->format('Y-m-d'),
                'description'     => $description,
                'color'           => $eventColor,
                'backgroundColor' => $eventColor,
                'borderColor'     => $borderColor,
                'textColor'       => '#ffffff',
                'extendedProps'   => [
                    'nim'            => $s->nim,
                    'mahasiswa'      => $s->nama_mahasiswa,
                    'judul'          => $s->judul_skripsi,
                    'dosbing'        => $dosbing,
                    'jam'            => $s->jam ?? '-',
                    'ruang'          => $ruangName,
                    'jenis'          => 'Sempro',
                    'has_conflict'   => $hasConflict,
                    'conflict_notes' => !empty($conflictEntry['schedule']) ? implode('; ', $conflictEntry['schedule']) : null,
                ]
            ];
        });

        // Distinct dates for filter dropdown
        $daftarTanggal = Sidang::select('tanggal')
            ->distinct()
            ->whereNotNull('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('tanggal');

        $totalSempro = Sidang::where('jenis_tugas_akhir', 'sempro')->count();

        return view('master.sempro.index', compact(
            'sidangs', 'dosens', 'ruangs', 'periodes', 'activePeriode', 
            'daftarTanggal', 'totalSempro', 'calendarEvents', 'conflictMap'
        ));
    }

    // ─── Export Excel (Data Sempro) ───────────────────────────────────────────

    public function exportExcel(Request $request)
    {
        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping', 'ruang', 'periode'
        ])->where('jenis_tugas_akhir', 'sempro')
          ->where('verifikasi_status', 'disetujui');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mahasiswa', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('judul_skripsi', 'like', "%{$search}%");
            });
        }
        if ($periodeId = $request->get('periode_id')) {
            $query->where('periode_id', $periodeId);
        } else {
            $activePeriode = Periode::where('aktif', true)->first();
            if ($activePeriode) $query->where('periode_id', $activePeriode->id);
        }

        $data = $query->orderBy('nama_mahasiswa')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Sempro');

        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'Judul Proposal', 'Periode',
                    'Tgl Daftar', 'Dosbing Utama', 'Dosbing Pendamping',
                    'Ruang', 'Tgl Sempro', 'Jam'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getStyle("{$col}1")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF581C87');
            $sheet->getStyle("{$col}1")->getFont()->getColor()->setARGB('FFFFFFFF');
        }

        foreach ($data as $i => $s) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $s->nim);
            $sheet->setCellValue("C{$row}", $s->nama_mahasiswa);
            $sheet->setCellValue("D{$row}", $s->judul_skripsi);
            $sheet->setCellValue("E{$row}", $s->periode?->nama_periode ?? '-');
            $sheet->setCellValue("F{$row}", $s->tanggal_pendaftaran ? $s->tanggal_pendaftaran->locale('id')->translatedFormat('l, d/m/Y') : '-');
            $sheet->setCellValue("G{$row}", $s->pembimbingUtama?->nama_dosen ?? '-');
            $sheet->setCellValue("H{$row}", $s->pembimbingPendamping?->nama_dosen ?? '-');
            $sheet->setCellValue("I{$row}", $s->ruang?->kode_ruangan ?? '-');
            $sheet->setCellValue("J{$row}", $s->tanggal ? $s->tanggal->locale('id')->translatedFormat('l, d/m/Y') : '-');
            $sheet->setCellValue("K{$row}", $s->jam ?? '-');
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $periodeLabel = $request->get('periode_id') ? "Periode_{$request->get('periode_id')}" : 'Semua_Periode';
        $filename = "Data_Sempro_{$periodeLabel}_" . date('Ymd') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    // ─── Jadwal Index (Halaman Jadwal Sempro) ─────────────────────────────────

    public function jadwalIndex(Request $request): View
    {
        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping', 'ruang', 'periode'
        ])->where('jenis_tugas_akhir', 'sempro');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mahasiswa', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('judul_skripsi', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            if ($status === 'belum') {
                $query->whereNull('tanggal');
            } elseif ($status === 'sudah') {
                $query->whereNotNull('tanggal');
            }
        }

        if ($periodeId = $request->get('periode_id')) {
            $query->where('periode_id', $periodeId);
        } else {
            $activePeriode = Periode::where('aktif', true)->first();
            if ($activePeriode) {
                $query->where('periode_id', $activePeriode->id);
            }
        }

        $allSidangs = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'ruang', 'periode'])
                            ->where('jenis_tugas_akhir', 'sempro')->get();
        $conflictMap = SidangConflictService::detectAllConflicts($allSidangs);

        $conflictIds = [];
        foreach ($conflictMap as $sId => $cEntry) {
            if (!empty($cEntry['schedule']) || !empty($cEntry['rules'])) {
                $conflictIds[] = $sId;
            }
        }

        $allMatching = $query->get();

        $sortedSidangs = $allMatching->sortBy(function ($s) use ($conflictIds) {
            $hasConflict = in_array($s->id, $conflictIds);
            $hasDate     = !empty($s->tanggal) ? 0 : 1;
            $tglOrder    = $s->tanggal ? $s->tanggal->format('Y-m-d') : '9999-12-31';

            return [
                $hasConflict ? 0 : 1,
                $hasDate,
                $tglOrder,
                $s->id
            ];
        })->values();

        $perPage = (int) $request->get('per_page', 5);
        if (!in_array($perPage, [5, 10, 25, 100])) {
            $perPage = 5;
        }

        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage('page') ?: 1;
        $currentPageItems = $sortedSidangs->slice(($page - 1) * $perPage, $perPage)->values();

        $sidangs = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageItems,
            $sortedSidangs->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );
        $calendarEvents = $allSidangs->filter(fn($s) => !empty($s->tanggal))->map(function ($s) use ($conflictMap) {
            $conflictEntry   = $conflictMap[$s->id] ?? [];
            $hasSchedule     = !empty($conflictEntry['schedule']);
            $hasConflict     = $hasSchedule;

            $prefix      = $hasSchedule ? '⚠️ ' : '';
            $title       = $prefix . $s->nama_mahasiswa . ' (Sempro)';
            $ruangName   = $s->ruang ? $s->ruang->kode_ruangan : 'TBA';
            $dosbing     = $s->pembimbingUtama ? $s->pembimbingUtama->nama_dosen : 'TBA';

            $description = "NIM: {$s->nim}\nJudul: {$s->judul_skripsi}\nDosbing: {$dosbing}\nJam: {$s->jam}\nRuang: {$ruangName}";
            if ($hasSchedule) {
                $description .= "\n\n⚠️ BENTROK JADWAL:\n" . implode("\n", $conflictEntry['schedule']);
            }

            $eventColor = $hasSchedule ? '#ef4444' : '#8b5cf6';
            $borderColor = $hasSchedule ? '#dc2626' : '#7c3aed';

            return [
                'id'              => $s->id,
                'title'           => $title,
                'start'           => $s->tanggal->format('Y-m-d'),
                'description'     => $description,
                'color'           => $eventColor,
                'backgroundColor' => $eventColor,
                'borderColor'     => $borderColor,
                'textColor'       => '#ffffff',
                'extendedProps'   => [
                    'nim'            => $s->nim,
                    'mahasiswa'      => $s->nama_mahasiswa,
                    'judul'          => $s->judul_skripsi,
                    'dosbing'        => $dosbing,
                    'jam'            => $s->jam ?? '-',
                    'ruang'          => $ruangName,
                    'jenis'          => 'Sempro',
                    'has_conflict'   => $hasConflict,
                    'conflict_notes' => !empty($conflictEntry['schedule']) ? implode('; ', $conflictEntry['schedule']) : null,
                ]
            ];
        });

        $dosens  = Dosen::orderBy('nama_dosen')->get();
        $ruangs  = Ruang::orderBy('kode_ruangan')->get();
        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();
        $daftarTanggal = Sidang::select('tanggal')->distinct()->whereNotNull('tanggal')
                               ->where('jenis_tugas_akhir', 'sempro')->orderBy('tanggal')->pluck('tanggal');
        $totalSempro = Sidang::where('jenis_tugas_akhir', 'sempro')->count();

        $kesediaanDosens = \App\Models\KesediaanDosen::with('dosen')->orderBy('tanggal', 'asc')->get();

        return view('sempro.index', compact(
            'sidangs', 'dosens', 'ruangs', 'periodes', 'activePeriode',
            'daftarTanggal', 'totalSempro', 'calendarEvents', 'conflictMap', 'kesediaanDosens'
        ));
    }

    // ─── Jadwalkan (Plotting Jadwal Sempro) ───────────────────────────────────

    public function jadwalkan(Request $request, Sidang $sidang)
    {
        if ($request->filled('jam_mulai') && $request->filled('jam_selesai')) {
            $request->merge(['jam' => $request->input('jam_mulai') . ' - ' . $request->input('jam_selesai')]);
        }

        foreach (['ruang_id'] as $fk) {
            if ($request->has($fk) && ($request->input($fk) === '' || $request->input($fk) === 'null')) {
                $request->merge([$fk => null]);
            }
        }

        $validated = $request->validate([
            'tanggal'  => ['required', 'date'],
            'jam'      => ['required', 'string', 'max:100'],
            'ruang_id' => ['required', 'exists:ruangs,id'],
        ], [
            'tanggal.required'  => 'Tanggal sempro wajib diisi.',
            'jam.required'      => 'Waktu / Jam sempro wajib dipilih.',
            'ruang_id.required' => 'Ruangan sempro wajib dipilih.',
            'ruang_id.exists'   => 'Ruangan yang dipilih tidak valid.',
        ]);

        // Check schedule conflicts
        $checkData = array_merge($sidang->toArray(), $validated);
        $scheduleConflicts = SidangConflictService::checkConflicts($checkData, $sidang->id);
        if (!empty($scheduleConflicts)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => '⚠️ Bentrok Jadwal: ' . implode(' | ', $scheduleConflicts)], 422);
            }
            return back()->with('warning', '⚠️ Bentrok Jadwal: ' . implode(' | ', $scheduleConflicts));
        }

        $sidang->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => '✅ Jadwal sempro berhasil ditetapkan untuk ' . $sidang->nama_mahasiswa . '!', 'sidang' => $sidang->fresh()]);
        }
        return back()->with('success', '✅ Jadwal sempro berhasil ditetapkan untuk ' . $sidang->nama_mahasiswa . '!');
    }

    // ─── Store (manual input) ─────────────────────────────────────────────────

    public function store(Request $request)
    {
        if ($request->filled('jam_mulai') && $request->filled('jam_selesai')) {
            $request->merge(['jam' => $request->input('jam_mulai') . ' - ' . $request->input('jam_selesai')]);
        }

        $validated = $request->validate([
            'nim'                            => ['required', 'string', 'max:30'],
            'nama_mahasiswa'                 => ['required', 'string', 'max:255'],
            'judul_skripsi'                  => ['required', 'string'],
            'dosen_pembimbing_utama_id'      => ['required', 'exists:dosens,id'],
            'dosen_pembimbing_pendamping_id' => ['nullable', 'exists:dosens,id'],
            'ruang_id'                       => ['nullable', 'exists:ruangs,id'],
            'periode_id'                     => ['nullable', 'exists:periodes,id'],
            'tanggal'                        => ['nullable', 'date'],
            'tanggal_pendaftaran'            => ['nullable', 'date'],
            'jam'                            => ['nullable', 'string', 'max:100'],
        ]);

        $validated['jenis_tugas_akhir'] = 'sempro';
        $validated['verifikasi_status'] = 'disetujui';
        $validated['ketua_penguji_id'] = null;
        $validated['anggota_penguji_1_id'] = null;
        $validated['anggota_penguji_2_id'] = null;

        // Check schedule conflicts (room overlaps)
        $scheduleConflicts = SidangConflictService::checkConflicts($validated);
        if (!empty($scheduleConflicts)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bentrok Jadwal: ' . implode(' | ', $scheduleConflicts)
                ], 422);
            }
            return back()->withInput()->with('warning', '⚠️ Bentrok Jadwal: ' . implode(' | ', $scheduleConflicts));
        }

        // Auto set active period if null
        if (empty($validated['periode_id'])) {
            $activePeriode = Periode::where('aktif', true)->first();
            $validated['periode_id'] = $activePeriode ? $activePeriode->id : null;
        }

        $sidang = Sidang::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data sempro mahasiswa berhasil ditambahkan!',
                'sidang' => $sidang
            ]);
        }

        return back()->with('success', 'Data sempro mahasiswa berhasil ditambahkan!');
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, Sidang $sidang)
    {
        if ($request->filled('jam_mulai') && $request->filled('jam_selesai')) {
            $request->merge(['jam' => $request->input('jam_mulai') . ' - ' . $request->input('jam_selesai')]);
        }

        $validated = $request->validate([
            'nim'                            => ['required', 'string', 'max:30'],
            'nama_mahasiswa'                 => ['required', 'string', 'max:255'],
            'judul_skripsi'                  => ['required', 'string'],
            'dosen_pembimbing_utama_id'      => ['required', 'exists:dosens,id'],
            'dosen_pembimbing_pendamping_id' => ['nullable', 'exists:dosens,id'],
            'ruang_id'                       => ['nullable', 'exists:ruangs,id'],
            'periode_id'                     => ['nullable', 'exists:periodes,id'],
            'tanggal'                        => ['nullable', 'date'],
            'tanggal_pendaftaran'            => ['nullable', 'date'],
            'jam'                            => ['nullable', 'string', 'max:100'],
        ]);

        $validated['jenis_tugas_akhir'] = 'sempro';
        $validated['ketua_penguji_id'] = null;
        $validated['anggota_penguji_1_id'] = null;
        $validated['anggota_penguji_2_id'] = null;

        // Check schedule conflicts excluding current ID
        $scheduleConflicts = SidangConflictService::checkConflicts($validated, $sidang->id);
        if (!empty($scheduleConflicts)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bentrok Jadwal: ' . implode(' | ', $scheduleConflicts)
                ], 422);
            }
            return back()->withInput()->with('warning', '⚠️ Bentrok Jadwal: ' . implode(' | ', $scheduleConflicts));
        }

        $sidang->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data sempro berhasil diperbarui!',
                'sidang' => $sidang
            ]);
        }

        return back()->with('success', 'Data sempro berhasil diperbarui!');
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(Request $request, Sidang $sidang)
    {
        $sidang->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data sempro berhasil dihapus!'
            ]);
        }

        return back()->with('success', 'Data sempro berhasil dihapus!');
    }

    /**
     * Hapus SELURUH data sempro dengan konfirmasi
     */
    public function destroyAll(Request $request): RedirectResponse
    {
        $count = Sidang::where('jenis_tugas_akhir', 'sempro')->delete();

        return redirect()->route('master.sempro.index')->with('success', "Berhasil menghapus seluruh data sempro ({$count} data berhasil dihapus).");
    }

    // ─── Import Excel ─────────────────────────────────────────────────────────

    public function importForm(): View
    {
        return view('sempro.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls'],
        ], [
            'excel_file.required' => 'File Excel wajib diunggah.',
            'excel_file.mimes'    => 'File harus berformat .xlsx atau .xls.',
        ]);

        $file = $request->file('excel_file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Gagal membaca file Excel: ' . $e->getMessage()]);
        }

        // Ensure active period exists
        $activePeriode = Periode::where('aktif', true)->first();
        if (!$activePeriode) {
            $activePeriode = Periode::create([
                'nama_periode' => 'Periode Akademik ' . date('Y') . '/' . (date('Y') + 1),
                'aktif' => true
            ]);
        }

        $created  = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        $sheets = $spreadsheet->getAllSheets();

        foreach ($sheets as $sheet) {
            $sheetName = $sheet->getTitle();
            $maxRow    = $sheet->getHighestRow();

            if ($maxRow < 1) {
                continue;
            }

            // Determine default jenis_tugas_akhir from sheet title
            $sheetLower = strtolower(trim($sheetName));
            if (str_contains($sheetLower, 'jurnal') || str_contains($sheetLower, 'article') || str_contains($sheetLower, 'artikel')) {
                $defaultJenis = 'jurnal';
            } elseif (str_contains($sheetLower, 'sidang') || str_contains($sheetLower, 'skripsi')) {
                $defaultJenis = 'skripsi';
            } else {
                $defaultJenis = 'sempro';
            }

            // Detect Header Row (inspect top 3 rows)
            $headerRow = 1;
            $maxColNum = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

            for ($r = 1; $r <= 3; $r++) {
                $foundKeyHeader = false;
                for ($c = 1; $c <= $maxColNum; $c++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $val = strtolower(trim((string)$sheet->getCell("{$colLetter}{$r}")->getValue()));
                    if (str_contains($val, 'nim') || str_contains($val, 'nama')) {
                        $foundKeyHeader = true;
                        break;
                    }
                }
                if ($foundKeyHeader) {
                    $headerRow = $r;
                    break;
                }
            }

            // Build Column Map from Header Row
            $colMap = [];
            for ($c = 1; $c <= $maxColNum; $c++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $val = strtolower(trim((string)$sheet->getCell("{$colLetter}{$headerRow}")->getValue()));

                if (empty($val)) continue;

                // Explicitly ignore 'NO' / 'No.' column
                if ($val === 'no' || $val === 'no.' || $val === 'no urut' || $val === 'no.') {
                    continue;
                }

                if (str_contains($val, 'pendaftaran') || str_contains($val, 'tgl daftar') || str_contains($val, 'tanggal daftar') || str_contains($val, 'tgl pendaftaran')) {
                    $colMap['tgl_daftar'] = $colLetter;
                } elseif (str_contains($val, 'jenis') || str_contains($val, 'tugas akhir') || str_contains($val, 'tipe') || str_contains($val, 'kategori')) {
                    $colMap['jenis'] = $colLetter;
                } elseif (str_contains($val, 'nim')) {
                    $colMap['nim'] = $colLetter;
                } elseif (str_contains($val, 'nama')) {
                    $colMap['nama'] = $colLetter;
                } elseif (str_contains($val, 'judul')) {
                    $colMap['judul'] = $colLetter;
                } elseif (str_contains($val, 'pembimbing utama') || str_contains($val, 'dosbing utama') || str_contains($val, 'dosbing 1') || str_contains($val, 'pembimbing 1')) {
                    $colMap['dosbing1'] = $colLetter;
                } elseif (str_contains($val, 'pembimbing pendamping') || str_contains($val, 'dosbing pendamping') || str_contains($val, 'dosbing 2') || str_contains($val, 'pembimbing 2')) {
                    $colMap['dosbing2'] = $colLetter;
                } elseif (str_contains($val, 'hari') || str_contains($val, 'tanggal')) {
                    $colMap['hari_tgl'] = $colLetter;
                } elseif (str_contains($val, 'jam') || str_contains($val, 'waktu')) {
                    $colMap['jam'] = $colLetter;
                } elseif (str_contains($val, 'ruang')) {
                    $colMap['ruang'] = $colLetter;
                }
            }

            // Fallback column positions if header mapping missed basic columns (ignoring column A if it is NO)
            $firstColVal = strtolower(trim((string)$sheet->getCell("A{$headerRow}")->getValue()));
            $hasNoCol    = in_array($firstColVal, ['no', 'no.', 'no urut']);
            $colMap['nim']       = $colMap['nim']       ?? ($hasNoCol ? 'B' : 'A');
            $colMap['nama']      = $colMap['nama']      ?? ($hasNoCol ? 'C' : 'B');
            $colMap['judul']     = $colMap['judul']     ?? ($hasNoCol ? 'D' : 'C');
            $colMap['dosbing1']  = $colMap['dosbing1']  ?? ($hasNoCol ? 'E' : 'D');
            $colMap['dosbing2']  = $colMap['dosbing2']  ?? ($hasNoCol ? 'F' : 'E');

            $getVal = function ($sheetObj, $col, $r) {
                if (empty($col)) return '';
                $cell = $sheetObj->getCell($col . $r);
                if ($cell->isFormula()) {
                    try {
                        return trim((string)$cell->getOldCalculatedValue());
                    } catch (\Exception $e) {
                        try {
                            return trim((string)$cell->getCalculatedValue());
                        } catch (\Exception $e2) {
                            return trim((string)$cell->getValue());
                        }
                    }
                }
                return trim((string)$cell->getFormattedValue());
            };

            $resolveDosen = function (?string $namaDosen) {
                return Dosen::resolveByName($namaDosen);
            };

            $startRow = $headerRow + 1;

            for ($row = $startRow; $row <= $maxRow; $row++) {
                $nim       = $getVal($sheet, $colMap['nim'] ?? '', $row);
                $namaMhs   = $getVal($sheet, $colMap['nama'] ?? '', $row);
                $judul     = $getVal($sheet, $colMap['judul'] ?? '', $row);
                $dosbing1  = $getVal($sheet, $colMap['dosbing1'] ?? '', $row);
                $dosbing2  = $getVal($sheet, $colMap['dosbing2'] ?? '', $row);
                $hariTgl   = isset($colMap['hari_tgl']) ? $getVal($sheet, $colMap['hari_tgl'], $row) : '';
                $tglDaftar = isset($colMap['tgl_daftar']) ? $getVal($sheet, $colMap['tgl_daftar'], $row) : '';
                $waktuJam  = isset($colMap['jam']) ? $getVal($sheet, $colMap['jam'], $row) : '';
                $kodeRuang = isset($colMap['ruang']) ? $getVal($sheet, $colMap['ruang'], $row) : '';

                $hariTglCell   = isset($colMap['hari_tgl']) ? $sheet->getCell($colMap['hari_tgl'] . $row) : null;
                $tglDaftarCell = isset($colMap['tgl_daftar']) ? $sheet->getCell($colMap['tgl_daftar'] . $row) : null;

                if (empty($nim) && empty($namaMhs)) {
                    continue; // Skip empty rows
                }

                // In Sempro import, the jenis_tugas_akhir is always 'sempro'
                $jenisParsed = 'sempro';

                try {
                    // Resolve Dosen IDs
                    $dosenUtama = $resolveDosen($dosbing1);
                    $dosenPend  = $resolveDosen($dosbing2);

                    // Resolve Ruang ID if present in Excel
                    $ruang = !empty($kodeRuang) ? Ruang::firstOrCreate(
                        ['kode_ruangan' => $kodeRuang],
                        ['nama_ruangan' => 'Ruangan ' . $kodeRuang]
                    ) : null;

                    // Parse Tanggal Ujian (Jadwal Sempro) from Excel
                    $tanggalParsed = !empty($hariTgl) ? $this->parseIndonesianDate($hariTgl, $hariTglCell) : null;

                    // Parse Tanggal Pendaftaran
                    $tglDaftarParsed = !empty($tglDaftar) ? $this->parseIndonesianDate($tglDaftar, $tglDaftarCell) : null;

                    $sidang = Sidang::updateOrCreate(
                        [
                            'nim'               => $nim,
                            'jenis_tugas_akhir' => $jenisParsed,
                        ],
                        [
                            'nama_mahasiswa'               => $namaMhs,
                            'judul_skripsi'                => $judul,
                            'dosen_pembimbing_utama_id'      => $dosenUtama?->id,
                            'dosen_pembimbing_pendamping_id' => $dosenPend?->id,
                            'ketua_penguji_id'               => null,
                            'anggota_penguji_1_id'           => null,
                            'anggota_penguji_2_id'           => null,
                            'tanggal'                      => $tanggalParsed,
                            'jam'                          => $waktuJam ?: null,
                            'ruang_id'                     => $ruang?->id,
                            'periode_id'                   => $activePeriode->id,
                            'tanggal_pendaftaran'          => $tglDaftarParsed,
                            'verifikasi_status'            => 'disetujui',
                            'verifikasi_tanggal'           => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]
                    );

                    if ($sidang->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Baris {$row} ({$namaMhs}): " . $e->getMessage();
                }
            }
        }

        $total = $created + $updated;
        $message = "Berhasil mengimpor {$total} data sempro! ({$created} baru, {$updated} diperbarui/ditimpa)";
        if ($skipped > 0) {
            $message .= " — {$skipped} baris gagal diproses.";
        }

        return redirect()->route('master.sempro.index')->with('success', $message);
    }

    /**
     * Helper to parse Indonesian date string like "Senin, 13 Juli 2026" or "13 Juli 2026" to Y-m-d.
     */
    private function parseIndonesianDate(mixed $dateVal, $cell = null): ?string
    {
        if (empty($dateVal)) return null;

        if ($cell && is_numeric($cell->getValue())) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cell->getValue())->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        if (is_numeric($dateVal)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateVal)->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        $dateStr = trim((string)$dateVal);
        if (empty($dateStr)) return null;

        // Standard Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        // Clean string for Indonesian word month
        $clean = preg_replace('/^[A-Za-z]+,\s*/u', '', $dateStr);
        $bulanIndo = [
            'Januari'   => '01', 'Februari' => '02', 'Maret'     => '03',
            'April'     => '04', 'Mei'      => '05', 'Juni'      => '06',
            'Juli'      => '07', 'Agustus'  => '08', 'September' => '09',
            'Oktober'   => '10', 'November' => '11', 'Desember'  => '12',
        ];

        foreach ($bulanIndo as $namaBulan => $numBulan) {
            if (stripos($clean, $namaBulan) !== false) {
                if (preg_match('/(\d{1,2})\s+' . $namaBulan . '\s+(\d{4})/i', $clean, $m)) {
                    $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                    $year = $m[2];
                    return "{$year}-{$numBulan}-{$day}";
                }
            }
        }

        try {
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $dateStr, $m)) {
                $val1 = (int)$m[1];
                $val2 = (int)$m[2];
                $year = (int)$m[3];
                if ($val1 > 12 && $val2 <= 12) {
                    return sprintf('%04d-%02d-%02d', $year, $val2, $val1);
                } elseif ($val2 > 12 && $val1 <= 12) {
                    return sprintf('%04d-%02d-%02d', $year, $val1, $val2);
                } else {
                    return sprintf('%04d-%02d-%02d', $year, $val2, $val1);
                }
            }
            return null;
        }
    }
}

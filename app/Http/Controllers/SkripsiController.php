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

class SkripsiController extends Controller
{
    // ─── Index ───────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $query = Sidang::with([
            'pembimbingUtama',
            'pembimbingPendamping',
            'ketuaPenguji',
            'anggotaPenguji1',
            'anggotaPenguji2',
            'ruang',
            'periode'
        ])->whereIn('jenis_tugas_akhir', ['skripsi', 'jurnal', 'sidang'])
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

        // Filter Jenis
        if ($jenis = $request->get('jenis')) {
            if (in_array($jenis, ['skripsi', 'jurnal', 'sidang'])) {
                $query->where('jenis_tugas_akhir', $jenis);
            }
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

        // 1. Fetch all records in DB to compute global conflict detection for table badges & calendar
        $allSidangs = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ])->get();

        // Detect all conflicts across the entire database
        $conflictMap = SidangConflictService::detectAllConflicts($allSidangs);

        // Get array of IDs that have conflicts (schedule or rules)
        $conflictIds = [];
        foreach ($conflictMap as $sId => $cEntry) {
            if (!empty($cEntry['schedule']) || !empty($cEntry['rules'])) {
                $conflictIds[] = $sId;
            }
        }

        // Get matching records
        $allMatching = $query->get();

        // Sort: Items WITH conflict FIRST, then skripsi vs jurnal, date, jam, id
        $sortedSidangs = $allMatching->sortBy(function ($s) use ($conflictIds) {
            $hasConflict = in_array($s->id, $conflictIds);
            $jenisOrder  = ($s->jenis_tugas_akhir === 'skripsi') ? 0 : 1;
            $tglOrder    = $s->tanggal ? $s->tanggal->format('Y-m-d') : '9999-12-31';
            $jamOrder    = $s->jam ?? '99:99';

            return [
                $hasConflict ? 0 : 1,
                $jenisOrder,
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

        // Filter valid dates for calendar events (only skripsi, sidang, and jurnal for this view)
        $calendarEvents = $allSidangs->filter(fn($s) => !empty($s->tanggal) && in_array($s->jenis_tugas_akhir, ['skripsi', 'jurnal', 'sidang']))->map(function ($s) use ($conflictMap) {
            $conflictEntry   = $conflictMap[$s->id] ?? [];
            $hasSchedule     = !empty($conflictEntry['schedule']);
            $hasRuleViolation = !empty($conflictEntry['rules']);
            $hasConflict     = $hasSchedule || $hasRuleViolation;

            $prefix      = $hasSchedule ? '⚠️ ' : ($hasRuleViolation ? '❌ ' : '');
            $title       = $prefix . $s->nama_mahasiswa . ' (' . ($s->jenis_tugas_akhir == 'skripsi' ? 'Skripsi' : 'Jurnal') . ')';
            $ruangName   = $s->ruang ? $s->ruang->kode_ruangan : 'TBA';
            $dosbing     = $s->pembimbingUtama ? $s->pembimbingUtama->nama_dosen : 'TBA';

            $description = "NIM: {$s->nim}\nJudul: {$s->judul_skripsi}\nDosbing: {$dosbing}\nPenguji: " . ($s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : '-') . "\nJam: {$s->jam}\nRuang: {$ruangName}";
            if ($hasSchedule) {
                $description .= "\n\n⚠️ BENTROK JADWAL:\n" . implode("\n", $conflictEntry['schedule']);
            }
            if ($hasRuleViolation) {
                $description .= "\n\n❌ PELANGGARAN ATURAN:\n" . implode("\n", $conflictEntry['rules']);
            }

            // Flatten all messages for conflict_notes
            $allNotes = array_merge($conflictEntry['schedule'] ?? [], $conflictEntry['rules'] ?? []);

            $eventColor = $hasSchedule ? '#ef4444' : ($hasRuleViolation ? '#f97316' : ($s->jenis_tugas_akhir == 'skripsi' ? '#6366f1' : '#10b981'));
            $borderColor = $hasSchedule ? '#dc2626' : ($hasRuleViolation ? '#ea580c' : ($s->jenis_tugas_akhir == 'skripsi' ? '#4f46e5' : '#059669'));

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
                    'ketua_penguji'  => $s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : '-',
                    'penguji_1'      => $s->anggotaPenguji1 ? $s->anggotaPenguji1->nama_dosen : '-',
                    'penguji_2'      => $s->anggotaPenguji2 ? $s->anggotaPenguji2->nama_dosen : '-',
                    'jam'            => $s->jam ?? '-',
                    'ruang'          => $ruangName,
                    'jenis'          => $s->jenis_label,
                    'has_conflict'   => $hasConflict,
                    'conflict_notes' => !empty($allNotes) ? implode('; ', $allNotes) : null,
                ]
            ];
        });

        // Distinct dates for filter dropdown
        $daftarTanggal = Sidang::select('tanggal')
            ->distinct()
            ->whereNotNull('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('tanggal');

        $totalSkripsi = Sidang::where('jenis_tugas_akhir', 'skripsi')->count();
        $totalJurnal = Sidang::where('jenis_tugas_akhir', 'jurnal')->count();

        return view('master.skripsi.index', compact(
            'sidangs', 'dosens', 'ruangs', 'periodes', 'activePeriode', 
            'daftarTanggal', 'totalSkripsi', 'totalJurnal', 'calendarEvents', 'conflictMap'
        ));
    }

    // ─── Export Excel (Data Skripsi) ──────────────────────────────────────────

    public function exportExcel(Request $request)
    {
        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ])->whereIn('jenis_tugas_akhir', ['skripsi', 'jurnal', 'sidang'])
          ->where('verifikasi_status', 'disetujui');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mahasiswa', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('judul_skripsi', 'like', "%{$search}%");
            });
        }
        if ($jenis = $request->get('jenis')) {
            $query->where('jenis_tugas_akhir', $jenis);
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
        $sheet->setTitle('Data Skripsi');

        // Header
        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'Judul Skripsi', 'Jenis TA', 'Periode',
                    'Tgl Daftar', 'Dosbing Utama', 'Dosbing Pendamping',
                    'Ketua Penguji', 'Penguji 1', 'Penguji 2',
                    'Ruang', 'Tgl Sidang', 'Jam'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getStyle("{$col}1")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF1E293B');
            $sheet->getStyle("{$col}1")->getFont()->getColor()->setARGB('FFFFFFFF');
        }

        foreach ($data as $i => $s) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $s->nim);
            $sheet->setCellValue("C{$row}", $s->nama_mahasiswa);
            $sheet->setCellValue("D{$row}", $s->judul_skripsi);
            $sheet->setCellValue("E{$row}", ucfirst($s->jenis_tugas_akhir));
            $sheet->setCellValue("F{$row}", $s->periode?->nama_periode ?? '-');
            $sheet->setCellValue("G{$row}", $s->tanggal_pendaftaran ? $s->tanggal_pendaftaran->locale('id')->translatedFormat('l, d/m/Y') : '-');
            $sheet->setCellValue("H{$row}", $s->pembimbingUtama?->nama_dosen ?? '-');
            $sheet->setCellValue("I{$row}", $s->pembimbingPendamping?->nama_dosen ?? '-');
            $sheet->setCellValue("J{$row}", $s->ketuaPenguji?->nama_dosen ?? '-');
            $sheet->setCellValue("K{$row}", $s->anggotaPenguji1?->nama_dosen ?? '-');
            $sheet->setCellValue("L{$row}", $s->anggotaPenguji2?->nama_dosen ?? '-');
            $sheet->setCellValue("M{$row}", $s->ruang?->kode_ruangan ?? '-');
            $sheet->setCellValue("N{$row}", $s->tanggal ? $s->tanggal->locale('id')->translatedFormat('l, d/m/Y') : '-');
            $sheet->setCellValue("O{$row}", $s->jam ?? '-');
        }

        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $periodeLabel = $request->get('periode_id') ? "Periode_{$request->get('periode_id')}" : 'Semua_Periode';
        $filename = "Data_Skripsi_{$periodeLabel}_" . date('Ymd') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    // ─── Jadwal Index (Halaman Jadwal Sidang Skripsi) ─────────────────────────

    public function jadwalIndex(Request $request): View
    {
        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ])->whereIn('jenis_tugas_akhir', ['skripsi', 'jurnal', 'sidang']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mahasiswa', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('judul_skripsi', 'like', "%{$search}%");
            });
        }

        if ($jenis = $request->get('jenis')) {
            if (in_array($jenis, ['skripsi', 'jurnal', 'sidang'])) {
                $query->where('jenis_tugas_akhir', $jenis);
            }
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

        $allSidangs = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2', 'ruang', 'periode'])->whereIn('jenis_tugas_akhir', ['skripsi', 'jurnal', 'sidang'])->get();
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
            $conflictEntry    = $conflictMap[$s->id] ?? [];
            $hasSchedule      = !empty($conflictEntry['schedule']);
            $hasRuleViolation = !empty($conflictEntry['rules']);
            $hasConflict      = $hasSchedule || $hasRuleViolation;

            $prefix      = $hasSchedule ? '⚠️ ' : ($hasRuleViolation ? '❌ ' : '');
            $title       = $prefix . $s->nama_mahasiswa . ' (' . ($s->jenis_tugas_akhir == 'skripsi' ? 'Skripsi' : 'Jurnal') . ')';
            $ruangName   = $s->ruang ? $s->ruang->kode_ruangan : 'TBA';
            $dosbing     = $s->pembimbingUtama ? $s->pembimbingUtama->nama_dosen : 'TBA';

            $description = "NIM: {$s->nim}\nJudul: {$s->judul_skripsi}\nDosbing: {$dosbing}\nPenguji: " . ($s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : '-') . "\nJam: {$s->jam}\nRuang: {$ruangName}";
            if ($hasSchedule) {
                $description .= "\n\n⚠️ BENTROK JADWAL:\n" . implode("\n", $conflictEntry['schedule']);
            }
            if ($hasRuleViolation) {
                $description .= "\n\n❌ PELANGGARAN ATURAN:\n" . implode("\n", $conflictEntry['rules']);
            }

            $allNotes = array_merge($conflictEntry['schedule'] ?? [], $conflictEntry['rules'] ?? []);

            $eventColor  = $hasSchedule ? '#ef4444' : ($hasRuleViolation ? '#f97316' : ($s->jenis_tugas_akhir == 'skripsi' ? '#6366f1' : '#10b981'));
            $borderColor = $hasSchedule ? '#dc2626' : ($hasRuleViolation ? '#ea580c' : ($s->jenis_tugas_akhir == 'skripsi' ? '#4f46e5' : '#059669'));

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
                    'ketua_penguji'  => $s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : '-',
                    'penguji_1'      => $s->anggotaPenguji1 ? $s->anggotaPenguji1->nama_dosen : '-',
                    'penguji_2'      => $s->anggotaPenguji2 ? $s->anggotaPenguji2->nama_dosen : '-',
                    'jam'            => $s->jam ?? '-',
                    'ruang'          => $ruangName,
                    'jenis'          => $s->jenis_label,
                    'has_conflict'   => $hasConflict,
                    'conflict_notes' => !empty($allNotes) ? implode('; ', $allNotes) : null,
                ]
            ];
        });

        $dosens = Dosen::orderBy('nama_dosen')->get();
        $ruangs = Ruang::orderBy('kode_ruangan')->get();
        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();
        $daftarTanggal = Sidang::select('tanggal')->distinct()->whereNotNull('tanggal')->whereIn('jenis_tugas_akhir', ['skripsi', 'jurnal', 'sidang'])->orderBy('tanggal')->pluck('tanggal');
        $totalSkripsi = Sidang::where('jenis_tugas_akhir', 'skripsi')->count();
        $totalJurnal  = Sidang::where('jenis_tugas_akhir', 'jurnal')->count();

        $kesediaanDosens = \App\Models\KesediaanDosen::with('dosen')->orderBy('tanggal', 'asc')->get();

        return view('skripsi.index', compact(
            'sidangs', 'dosens', 'ruangs', 'periodes', 'activePeriode',
            'daftarTanggal', 'totalSkripsi', 'totalJurnal', 'calendarEvents', 'conflictMap', 'kesediaanDosens'
        ));
    }

    // ─── Jadwalkan (Plotting Jadwal Sidang Skripsi) ───────────────────────────

    public function jadwalkan(Request $request, Sidang $sidang)
    {
        if ($request->filled('jam_mulai') && $request->filled('jam_selesai')) {
            $request->merge(['jam' => $request->input('jam_mulai') . ' - ' . $request->input('jam_selesai')]);
        }

        // Convert empty string values to null for optional relations
        foreach (['ketua_penguji_id', 'anggota_penguji_1_id', 'anggota_penguji_2_id', 'ruang_id'] as $fk) {
            if ($request->has($fk) && ($request->input($fk) === '' || $request->input($fk) === 'null')) {
                $request->merge([$fk => null]);
            }
        }

        $validated = $request->validate([
            'tanggal'              => ['required', 'date'],
            'jam'                  => ['required', 'string', 'max:100'],
            'ruang_id'             => ['required', 'exists:ruangs,id'],
            'ketua_penguji_id'     => ['nullable', 'exists:dosens,id'],
            'anggota_penguji_1_id' => ['nullable', 'exists:dosens,id'],
            'anggota_penguji_2_id' => ['nullable', 'exists:dosens,id'],
        ], [
            'tanggal.required'  => 'Tanggal sidang wajib diisi.',
            'jam.required'      => 'Waktu / Jam sidang wajib dipilih.',
            'ruang_id.required' => 'Ruangan sidang wajib dipilih.',
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
            return response()->json(['success' => true, 'message' => '✅ Jadwal sidang berhasil ditetapkan untuk ' . $sidang->nama_mahasiswa . '!', 'sidang' => $sidang->fresh()]);
        }
        return back()->with('success', '✅ Jadwal sidang berhasil ditetapkan untuk ' . $sidang->nama_mahasiswa . '!');
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
            'ketua_penguji_id'               => ['required', 'exists:dosens,id'],
            'anggota_penguji_1_id'           => ['required', 'exists:dosens,id'],
            'anggota_penguji_2_id'           => ['nullable', 'exists:dosens,id'],
            'ruang_id'                       => ['nullable', 'exists:ruangs,id'],
            'periode_id'                     => ['nullable', 'exists:periodes,id'],
            'tanggal'                        => ['nullable', 'date'],
            'tanggal_pendaftaran'            => ['nullable', 'date'],
            'jam'                            => ['nullable', 'string', 'max:100'],
            'jenis_tugas_akhir'              => ['required', 'in:skripsi,jurnal'],
        ]);

        // 1. Check business rules (Pembimbing Utama wajib jadi Penguji 2, Pendamping tidak boleh menguji)
        $ruleErrors = SidangConflictService::checkBusinessRules($validated);
        if (!empty($ruleErrors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggaran Aturan: ' . implode(' | ', $ruleErrors)
                ], 422);
            }
            return back()->withInput()->with('error', '❌ Pelanggaran Aturan: ' . implode(' | ', $ruleErrors));
        }

        // 2. Check schedule conflicts (room, examiner overlap)
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

        $validated['verifikasi_status'] = 'disetujui';
        $sidang = Sidang::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data skripsi mahasiswa berhasil ditambahkan!',
                'sidang' => $sidang
            ]);
        }

        return back()->with('success', 'Data skripsi mahasiswa berhasil ditambahkan!');
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
            'ketua_penguji_id'               => ['required', 'exists:dosens,id'],
            'anggota_penguji_1_id'           => ['required', 'exists:dosens,id'],
            'anggota_penguji_2_id'           => ['nullable', 'exists:dosens,id'],
            'ruang_id'                       => ['nullable', 'exists:ruangs,id'],
            'periode_id'                     => ['nullable', 'exists:periodes,id'],
            'tanggal'                        => ['nullable', 'date'],
            'tanggal_pendaftaran'            => ['nullable', 'date'],
            'jam'                            => ['nullable', 'string', 'max:100'],
            'jenis_tugas_akhir'              => ['required', 'in:skripsi,jurnal'],
        ]);

        // 1. Check business rules
        $ruleErrors = SidangConflictService::checkBusinessRules($validated);
        if (!empty($ruleErrors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggaran Aturan: ' . implode(' | ', $ruleErrors)
                ], 422);
            }
            return back()->withInput()->with('error', '❌ Pelanggaran Aturan: ' . implode(' | ', $ruleErrors));
        }

        // 2. Check schedule conflicts excluding current ID
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
                'message' => 'Data skripsi berhasil diperbarui!',
                'sidang' => $sidang
            ]);
        }

        return back()->with('success', 'Data skripsi berhasil diperbarui!');
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(Request $request, Sidang $sidang)
    {
        $sidang->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data skripsi berhasil dihapus!'
            ]);
        }

        return back()->with('success', 'Data skripsi berhasil dihapus!');
    }

    /**
     * Hapus SELURUH data skripsi & jurnal dengan konfirmasi
     */
    public function destroyAll(Request $request): RedirectResponse
    {
        $count = Sidang::whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])->delete();

        return redirect()->route('master.skripsi.index')->with('success', "Berhasil menghapus seluruh data skripsi & jurnal ({$count} data berhasil dihapus).");
    }

    // ─── Import Excel ─────────────────────────────────────────────────────────

    public function importForm(): View
    {
        return view('skripsi.import');
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

            // Determine default jenis_tugas_akhir from sheet title (Sidang/Skripsi -> skripsi, Jurnal -> jurnal, Sempro -> sempro)
            $sheetLower = strtolower(trim($sheetName));
            if (str_contains($sheetLower, 'jurnal') || str_contains($sheetLower, 'article') || str_contains($sheetLower, 'artikel')) {
                $defaultJenis = 'jurnal';
            } elseif (str_contains($sheetLower, 'sempro') || str_contains($sheetLower, 'proposal')) {
                $defaultJenis = 'sempro';
            } else {
                $defaultJenis = 'skripsi';
            }

            // 1. Detect Header Row (inspect top 3 rows)
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

            // 2. Build Column Map from Header Row
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
                } elseif (str_contains($val, 'ketua')) {
                    $colMap['ketua'] = $colLetter;
                } elseif (str_contains($val, 'penguji 1') || str_contains($val, 'penguji i') || $val === 'penguji 1') {
                    $colMap['penguji1'] = $colLetter;
                } elseif (str_contains($val, 'penguji 2') || str_contains($val, 'penguji ii') || $val === 'penguji 2') {
                    $colMap['penguji2'] = $colLetter;
                } elseif (str_contains($val, 'hari') || str_contains($val, 'tanggal')) {
                    $colMap['hari_tgl'] = $colLetter;
                } elseif (str_contains($val, 'jam') || str_contains($val, 'waktu')) {
                    $colMap['jam'] = $colLetter;
                } elseif (str_contains($val, 'ruang')) {
                    $colMap['ruang'] = $colLetter;
                }
            }

            // Fallback column positions if header mapping missed basic columns
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
                $nim       = $getVal($sheet, $colMap['nim'], $row);
                $namaMhs   = $getVal($sheet, $colMap['nama'], $row);
                $judul     = $getVal($sheet, $colMap['judul'], $row);
                $dosbing1  = $getVal($sheet, $colMap['dosbing1'], $row);
                $dosbing2  = $getVal($sheet, $colMap['dosbing2'], $row);
                $waktuJam  = isset($colMap['jam']) ? $getVal($sheet, $colMap['jam'], $row) : '';
                $kodeRuang = isset($colMap['ruang']) ? $getVal($sheet, $colMap['ruang'], $row) : '';
                $ketuaPeng = isset($colMap['ketua']) ? $getVal($sheet, $colMap['ketua'], $row) : '';
                $penguji1  = isset($colMap['penguji1']) ? $getVal($sheet, $colMap['penguji1'], $row) : '';
                $penguji2  = isset($colMap['penguji2']) ? $getVal($sheet, $colMap['penguji2'], $row) : '';
                $hariTgl   = isset($colMap['hari_tgl']) ? $getVal($sheet, $colMap['hari_tgl'], $row) : '';
                $tglDaftar = isset($colMap['tgl_daftar']) ? $getVal($sheet, $colMap['tgl_daftar'], $row) : '';
                $rawJenis  = isset($colMap['jenis']) ? $getVal($sheet, $colMap['jenis'], $row) : '';

                if (empty($nim) && empty($namaMhs)) {
                    continue; // Skip empty rows
                }

                // Resolve jenis tugas akhir
                if (!empty($rawJenis)) {
                    $jenisLower = strtolower($rawJenis);
                    if (str_contains($jenisLower, 'jurnal') || str_contains($jenisLower, 'artikel')) {
                        $jenisParsed = 'jurnal';
                    } elseif (str_contains($jenisLower, 'sempro') || str_contains($jenisLower, 'proposal')) {
                        $jenisParsed = 'sempro';
                    } else {
                        $jenisParsed = 'skripsi';
                    }
                } else {
                    $jenisParsed = $defaultJenis;
                }

                try {
                    // Resolve Dosen IDs (auto-insert ke master dosen jika belum ada)
                    $dosenUtama = $resolveDosen($dosbing1);
                    $dosenPend  = $resolveDosen($dosbing2);
                    $ketua      = $resolveDosen($ketuaPeng);
                    $p1         = $resolveDosen($penguji1);
                    $p2         = $resolveDosen($penguji2);

                    // Resolve Ruang ID if present in Excel
                    $ruang = !empty($kodeRuang) ? Ruang::firstOrCreate(
                        ['kode_ruangan' => $kodeRuang],
                        ['nama_ruangan' => 'Ruangan ' . $kodeRuang]
                    ) : null;

                    $hariTglCell   = isset($colMap['hari_tgl']) ? $sheet->getCell($colMap['hari_tgl'] . $row) : null;
                    $tglDaftarCell = isset($colMap['tgl_daftar']) ? $sheet->getCell($colMap['tgl_daftar'] . $row) : null;

                    // Parse Tanggal Ujian (Jadwal Sidang) from Excel
                    $tanggalParsed = !empty($hariTgl) ? $this->parseIndonesianDate($hariTgl, $hariTglCell) : null;

                    // Parse Tanggal Pendaftaran
                    $tglDaftarParsed = !empty($tglDaftar) ? $this->parseIndonesianDate($tglDaftar, $tglDaftarCell) : null;

                    // updateOrCreate: jika NIM + jenis sudah ada → timpa/update, jika belum → buat baru
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
                            'ketua_penguji_id'               => $ketua?->id,
                            'anggota_penguji_1_id'           => $p1?->id,
                            'anggota_penguji_2_id'           => $p2?->id,
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
        $message = "Berhasil mengimpor {$total} data skripsi! ({$created} baru, {$updated} diperbarui/ditimpa)";
        if ($skipped > 0) {
            $message .= " — {$skipped} baris gagal diproses.";
        }

        return redirect()->route('master.skripsi.index')->with('success', $message);
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

        // Clean string
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

    /**
     * Update student registration verification status
     */
    public function verifikasi(Request $request, Sidang $sidang)
    {
        $validated = $request->validate([
            'verifikasi_status'   => ['required', 'string', 'in:disetujui,ditolak,menunggu'],
            'verifikasi_komentar' => ['nullable', 'string'],
        ]);

        $sidang->update([
            'verifikasi_status'   => $validated['verifikasi_status'],
            'verifikasi_komentar' => $validated['verifikasi_status'] === 'ditolak' ? $validated['verifikasi_komentar'] : null,
            'verifikasi_tanggal'  => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status verifikasi pendaftaran berhasil diperbarui!',
                'status'  => $validated['verifikasi_status'],
                'komentar' => $validated['verifikasi_status'] === 'ditolak' ? $validated['verifikasi_komentar'] : null,
            ]);
        }

        return back()->with('success', 'Status verifikasi pendaftaran berhasil diperbarui!');
    }
}

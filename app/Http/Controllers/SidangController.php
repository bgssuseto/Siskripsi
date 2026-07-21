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

class SidangController extends Controller
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
        ]);

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
            if (in_array($jenis, ['sidang', 'jurnal'])) {
                $query->where('jenis_tugas_akhir', $jenis);
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

        $sidangs = $query->orderByRaw("CASE WHEN jenis_tugas_akhir='sidang' THEN 0 ELSE 1 END")
                         ->orderBy('tanggal', 'asc')
                         ->orderBy('jam', 'asc')
                         ->orderBy('id', 'asc')
                         ->paginate(25)
                         ->withQueryString();

        // 1. Fetch all sidangs in DB to compute global conflict detection for table badges & calendar
        $allSidangs = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode'
        ])->get();

        // Detect all conflicts across the entire database
        $conflictMap = SidangConflictService::detectAllConflicts($allSidangs);

        // Dropdown lists
        $dosens = Dosen::orderBy('nama_dosen')->get();
        $ruangs = Ruang::orderBy('kode_ruangan')->get();
        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();

        // Filter valid dates for calendar events
        $calendarEvents = $allSidangs->filter(fn($s) => !empty($s->tanggal))->map(function ($s) use ($conflictMap) {
            $conflictEntry   = $conflictMap[$s->id] ?? [];
            $hasSchedule     = !empty($conflictEntry['schedule']);
            $hasRuleViolation = !empty($conflictEntry['rules']);
            $hasConflict     = $hasSchedule || $hasRuleViolation;

            $prefix      = $hasSchedule ? '⚠️ ' : ($hasRuleViolation ? '❌ ' : '');
            $title       = $prefix . $s->nama_mahasiswa . ' (' . ($s->jenis_tugas_akhir == 'sidang' ? 'Sidang' : 'Jurnal') . ')';
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

            $eventColor = $hasSchedule ? '#ef4444' : ($hasRuleViolation ? '#f97316' : ($s->jenis_tugas_akhir == 'sidang' ? '#6366f1' : '#10b981'));
            $borderColor = $hasSchedule ? '#dc2626' : ($hasRuleViolation ? '#ea580c' : ($s->jenis_tugas_akhir == 'sidang' ? '#4f46e5' : '#059669'));

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

        $totalSidang = Sidang::where('jenis_tugas_akhir', 'sidang')->count();
        $totalJurnal = Sidang::where('jenis_tugas_akhir', 'jurnal')->count();

        return view('sidang.index', compact(
            'sidangs', 'dosens', 'ruangs', 'periodes', 'activePeriode', 
            'daftarTanggal', 'totalSidang', 'totalJurnal', 'calendarEvents', 'conflictMap'
        ));
    }

    // ─── Store (manual input) ─────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
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
            'jenis_tugas_akhir'              => ['required', 'in:sidang,jurnal'],
        ]);

        // 1. Check business rules (Pembimbing Utama wajib jadi Penguji 2, Pendamping tidak boleh menguji)
        $ruleErrors = SidangConflictService::checkBusinessRules($validated);
        if (!empty($ruleErrors)) {
            return back()->withInput()->with('error', '❌ Pelanggaran Aturan: ' . implode(' | ', $ruleErrors));
        }

        // 2. Check schedule conflicts (room, examiner overlap)
        $scheduleConflicts = SidangConflictService::checkConflicts($validated);
        if (!empty($scheduleConflicts)) {
            return back()->withInput()->with('warning', '⚠️ Bentrok Jadwal: ' . implode(' | ', $scheduleConflicts));
        }

        // Auto set active period if null
        if (empty($validated['periode_id'])) {
            $activePeriode = Periode::where('aktif', true)->first();
            $validated['periode_id'] = $activePeriode ? $activePeriode->id : null;
        }

        Sidang::create($validated);

        return back()->with('success', 'Data sidang mahasiswa berhasil ditambahkan!');
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, Sidang $sidang): RedirectResponse
    {
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
            'jenis_tugas_akhir'              => ['required', 'in:sidang,jurnal'],
        ]);

        // 1. Check business rules
        $ruleErrors = SidangConflictService::checkBusinessRules($validated);
        if (!empty($ruleErrors)) {
            return back()->withInput()->with('error', '❌ Pelanggaran Aturan: ' . implode(' | ', $ruleErrors));
        }

        // 2. Check schedule conflicts excluding current ID
        $scheduleConflicts = SidangConflictService::checkConflicts($validated, $sidang->id);
        if (!empty($scheduleConflicts)) {
            return back()->withInput()->with('warning', '⚠️ Bentrok Jadwal: ' . implode(' | ', $scheduleConflicts));
        }

        $sidang->update($validated);

        return back()->with('success', 'Data sidang berhasil diperbarui!');
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(Sidang $sidang): RedirectResponse
    {
        $sidang->delete();
        return back()->with('success', 'Data sidang berhasil dihapus!');
    }

    // ─── Import Excel ─────────────────────────────────────────────────────────

    public function importForm(): View
    {
        return view('sidang.import');
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

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        $sheets = $spreadsheet->getAllSheets();

        foreach ($sheets as $sheet) {
            $sheetName = $sheet->getTitle();
            $maxRow    = $sheet->getHighestRow();

            if ($maxRow < 1) {
                continue;
            }

            // Determine jenis_tugas_akhir from sheet title
            $sheetLower = strtolower($sheetName);
            $jenis = (str_contains($sheetLower, 'jurnal') || str_contains($sheetLower, 'article') || str_contains($sheetLower, 'artikel'))
                ? 'jurnal'
                : 'sidang';

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

                if (str_contains($val, 'pendaftaran')) {
                    $colMap['tgl_daftar'] = $colLetter;
                } elseif (str_contains($val, 'nim')) {
                    $colMap['nim'] = $colLetter;
                } elseif (str_contains($val, 'nama')) {
                    $colMap['nama'] = $colLetter;
                } elseif (str_contains($val, 'judul')) {
                    $colMap['judul'] = $colLetter;
                } elseif (str_contains($val, 'pembimbing utama') || str_contains($val, 'dosbing utama')) {
                    $colMap['dosbing1'] = $colLetter;
                } elseif (str_contains($val, 'pembimbing pendamping') || str_contains($val, 'dosbing pendamping')) {
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
            $hasNoCol = isset($colMap['nim']) && $colMap['nim'] === 'B';
            $colMap['nim']       = $colMap['nim']       ?? ($hasNoCol ? 'B' : 'A');
            $colMap['nama']      = $colMap['nama']      ?? ($hasNoCol ? 'C' : 'B');
            $colMap['judul']     = $colMap['judul']     ?? ($hasNoCol ? 'D' : 'C');
            $colMap['dosbing1']  = $colMap['dosbing1']  ?? ($hasNoCol ? 'E' : 'D');
            $colMap['dosbing2']  = $colMap['dosbing2']  ?? ($hasNoCol ? 'F' : 'E');
            $colMap['ketua']     = $colMap['ketua']     ?? ($hasNoCol ? 'G' : 'F');
            $colMap['penguji1']  = $colMap['penguji1']  ?? ($hasNoCol ? 'H' : 'G');
            $colMap['penguji2']  = $colMap['penguji2']  ?? ($hasNoCol ? 'I' : 'H');
            $colMap['hari_tgl']  = $colMap['hari_tgl']  ?? ($hasNoCol ? 'J' : 'I');
            $colMap['jam']       = $colMap['jam']       ?? ($hasNoCol ? 'K' : 'J');
            $colMap['ruang']     = $colMap['ruang']     ?? ($hasNoCol ? 'L' : 'K');

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
                if (empty($namaDosen)) return null;
                $dosen = Dosen::where('nama_dosen', $namaDosen)->first();
                if (!$dosen) {
                    $dummyNidn = 'NIDN-' . strtoupper(substr(md5($namaDosen), 0, 8));
                    $dosen = Dosen::create([
                        'nama_dosen' => $namaDosen,
                        'nidn'       => $dummyNidn,
                    ]);
                }
                return $dosen;
            };

            $startRow = $headerRow + 1;

            for ($row = $startRow; $row <= $maxRow; $row++) {
                $nim       = $getVal($sheet, $colMap['nim'], $row);
                $namaMhs   = $getVal($sheet, $colMap['nama'], $row);
                $judul     = $getVal($sheet, $colMap['judul'], $row);
                $dosbing1  = $getVal($sheet, $colMap['dosbing1'], $row);
                $dosbing2  = $getVal($sheet, $colMap['dosbing2'], $row);
                $ketuaPeng = $getVal($sheet, $colMap['ketua'], $row);
                $penguji1  = $getVal($sheet, $colMap['penguji1'], $row);
                $penguji2  = $getVal($sheet, $colMap['penguji2'], $row);
                $hariTgl   = $getVal($sheet, $colMap['hari_tgl'], $row);
                $waktuJam  = $getVal($sheet, $colMap['jam'], $row);
                $kodeRuang = $getVal($sheet, $colMap['ruang'], $row);
                $tglDaftar = isset($colMap['tgl_daftar']) ? $getVal($sheet, $colMap['tgl_daftar'], $row) : '';

                if (empty($nim) && empty($namaMhs)) {
                    continue; // Skip empty rows
                }

                try {
                    // Resolve Dosen IDs
                    $dosenUtama = $resolveDosen($dosbing1);
                    $dosenPend  = $resolveDosen($dosbing2);
                    $ketua      = $resolveDosen($ketuaPeng);
                    $p1         = $resolveDosen($penguji1);
                    $p2         = $resolveDosen($penguji2);

                    // Resolve Ruang ID
                    $ruang = $kodeRuang ? Ruang::firstOrCreate(
                        ['kode_ruangan' => $kodeRuang],
                        ['nama_ruangan' => 'Ruangan ' . $kodeRuang]
                    ) : null;

                    // Parse Tanggal
                    $tanggalParsed = $this->parseIndonesianDate($hariTgl);
                    $tglDaftarParsed = $tglDaftar ? $this->parseIndonesianDate($tglDaftar) : null;

                    Sidang::updateOrCreate(
                        [
                            'nim'               => $nim,
                            'jenis_tugas_akhir' => $jenis,
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
                        ]
                    );

                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Baris {$row} ({$namaMhs}): " . $e->getMessage();
                }
            }
        }

        $message = "Berhasil mengimpor {$imported} data sidang!";
        if ($skipped > 0) {
            $message .= " ({$skipped} baris dilewati/gagal)";
        }

        return redirect()->route('sidang.index')->with('success', $message);
    }

    /**
     * Helper to parse Indonesian date string like "Senin, 13 Juli 2026" or "13 Juli 2026" to Y-m-d.
     */
    private function parseIndonesianDate(mixed $dateVal): ?string
    {
        if (empty($dateVal)) return null;

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

        // Standard d/m/Y or d-m-Y
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $dateStr, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
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
            return null;
        }
    }
}

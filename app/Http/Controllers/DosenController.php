<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Validation\Rule;

class DosenController extends Controller
{
    public function index(Request $request): View
    {
        $query = Dosen::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_dosen', 'like', "%{$search}%")
                  ->orWhere('nidn', 'like', "%{$search}%");
            });
        }

        $dosens = $query->latest()->paginate(5)->withQueryString();

        return view('master.dosen.index', compact('dosens'));
    }

    /**
     * Export the current Master Dosen list (respecting the active search filter) to Excel.
     */
    public function exportExcel(Request $request)
    {
        $query = Dosen::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_dosen', 'like', "%{$search}%")
                  ->orWhere('nidn', 'like', "%{$search}%");
            });
        }

        $dosens = $query->orderBy('nama_dosen')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Dosen');

        $headers = ['No', 'NIDN', 'Nama & Gelar', 'Email', 'No WhatsApp', 'Alias/Inisial', 'Kepakaran', 'Jabatan Fungsional'];
        foreach ($headers as $colIdx => $text) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $text);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach ($dosens as $idx => $dosen) {
            $row = $idx + 2;
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValueExplicit("B{$row}", $dosen->nidn, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", $dosen->nama_dosen);
            $sheet->setCellValue("D{$row}", $dosen->email ?? '-');
            $sheet->setCellValueExplicit("E{$row}", $dosen->no_wa ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("F{$row}", $dosen->alias ?: $dosen->initials);
            $sheet->setCellValue("G{$row}", $dosen->kepakaran ?? '-');
            $sheet->setCellValue("H{$row}", $dosen->jabatan_fungsional ?? '-');
        }

        $lastRow = $dosens->count() + 1;
        $sheet->getStyle("A1:H{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Data_Dosen_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request)
    {
        // If a previously soft-deleted dosen already occupies this NIDN, this is a
        // "re-add" — restore that same record instead of creating a new one, so any
        // bimbingan/penguji history that referenced it (now showing as "-") reconnects
        // automatically instead of ending up duplicated under a new id.
        $trashed = Dosen::onlyTrashed()->where('nidn', $request->input('nidn'))->first();

        if ($trashed) {
            $validated = $request->validate([
                'nidn' => ['required', 'string', 'max:50'],
                'nama_dosen' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'alias' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('dosens', 'alias')->ignore($trashed->id)],
                'kepakaran' => ['nullable', 'string', 'max:255'],
                'jabatan_fungsional' => ['nullable', 'string', Rule::in(array_keys(Dosen::JABATAN_FUNGSIONAL_RANKS))],
                'no_wa' => ['nullable', 'string', 'max:30'],
            ], [
                'nama_dosen.required' => 'Nama dosen wajib diisi.',
                'alias.alpha_dash' => 'Alias hanya boleh huruf, angka, strip, dan underscore (tanpa spasi).',
                'alias.unique' => 'Alias sudah dipakai dosen lain.',
            ]);

            $trashed->restore();
            $trashed->update($validated);

            ActivityLogger::log('created', $trashed, "Memulihkan data dosen: {$trashed->nama_dosen} (NIDN: {$trashed->nidn}). Riwayat bimbingan/penguji sebelumnya otomatis terhubung kembali.");

            $message = "Data dosen dengan NIDN ini sebelumnya pernah dihapus — data berhasil dipulihkan, lengkap dengan riwayat bimbingan/penguji sebelumnya.";

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'dosen' => $trashed]);
            }

            return redirect()->route('master.dosen.index')->with('success', $message);
        }

        $validated = $request->validate([
            'nidn' => ['required', 'string', 'max:50', 'unique:dosens,nidn'],
            'nama_dosen' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'alias' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:dosens,alias'],
            'kepakaran' => ['nullable', 'string', 'max:255'],
            'jabatan_fungsional' => ['nullable', 'string', Rule::in(array_keys(Dosen::JABATAN_FUNGSIONAL_RANKS))],
            'no_wa' => ['nullable', 'string', 'max:30'],
        ], [
            'nidn.required' => 'NIDN wajib diisi.',
            'nidn.unique' => 'NIDN sudah terdaftar.',
            'nama_dosen.required' => 'Nama dosen wajib diisi.',
            'alias.alpha_dash' => 'Alias hanya boleh huruf, angka, strip, dan underscore (tanpa spasi).',
            'alias.unique' => 'Alias sudah dipakai dosen lain.',
        ]);

        $dosen = Dosen::create($validated);

        ActivityLogger::log('created', $dosen, "Menambahkan data dosen baru: {$dosen->nama_dosen} (NIDN: {$dosen->nidn}).");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data dosen berhasil ditambahkan!',
                'dosen' => $dosen
            ]);
        }

        return redirect()->route('master.dosen.index')->with('success', 'Data dosen berhasil ditambahkan!');
    }

    public function update(Request $request, Dosen $dosen)
    {
        $validated = $request->validate([
            'nidn' => ['required', 'string', 'max:50', 'unique:dosens,nidn,' . $dosen->id],
            'nama_dosen' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'alias' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:dosens,alias,' . $dosen->id],
            'kepakaran' => ['nullable', 'string', 'max:255'],
            'jabatan_fungsional' => ['nullable', 'string', Rule::in(array_keys(Dosen::JABATAN_FUNGSIONAL_RANKS))],
            'no_wa' => ['nullable', 'string', 'max:30'],
        ], [
            'nidn.required' => 'NIDN wajib diisi.',
            'nidn.unique' => 'NIDN sudah terdaftar.',
            'nama_dosen.required' => 'Nama dosen wajib diisi.',
            'alias.alpha_dash' => 'Alias hanya boleh huruf, angka, strip, dan underscore (tanpa spasi).',
            'alias.unique' => 'Alias sudah dipakai dosen lain.',
        ]);

        $trackedFields = ['nidn', 'nama_dosen', 'email', 'alias', 'kepakaran', 'jabatan_fungsional', 'no_wa'];
        $before = $dosen->only($trackedFields);
        $dosen->update($validated);

        ActivityLogger::log(
            'updated',
            $dosen,
            "Memperbarui data dosen: {$dosen->nama_dosen} (NIDN: {$dosen->nidn}).",
            ['before' => $before, 'after' => $dosen->only($trackedFields)]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data dosen berhasil diperbarui!',
                'dosen' => $dosen
            ]);
        }

        return redirect()->route('master.dosen.index')->with('success', 'Data dosen berhasil diperbarui!');
    }

    /**
     * Soft-delete a Dosen. Every Sidang/KesediaanDosen row that referenced this
     * dosen keeps its original foreign key untouched — since Dosen uses
     * SoftDeletes, those relations simply resolve to null (displayed as "-")
     * while the dosen is trashed, and automatically resolve correctly again the
     * moment the dosen is restored (re-added with the same NIDN via store()).
     * Nothing is reassigned to a placeholder, so no history is ever lost.
     */
    private function attemptDeleteDosen(Dosen $dosen): ?string
    {
        // Refuse to delete a Dosen that still has a linked User login account —
        // while trashed, relations to it resolve to null, which would break that
        // person's own dosen-portal dashboard while they still have an active login.
        $linkedUser = \App\Models\User::where('dosen_id', $dosen->id)->first();
        if ($linkedUser) {
            return "masih terhubung dengan akun login \"{$linkedUser->name}\"";
        }

        $namaDosenDihapus = $dosen->nama_dosen;
        $nidnDosenDihapus = $dosen->nidn;
        $dosen->delete();

        ActivityLogger::log(
            'deleted',
            $dosen,
            "Menghapus data dosen: {$namaDosenDihapus} (NIDN: {$nidnDosenDihapus}). Data bimbingan/penguji terkait akan tampil sebagai \"-\" dan otomatis kembali seperti semula jika dosen ini ditambahkan lagi dengan NIDN yang sama."
        );

        return null;
    }

    public function destroy(Request $request, Dosen $dosen)
    {
        $refusalReason = $this->attemptDeleteDosen($dosen);

        if ($refusalReason !== null) {
            $message = "Tidak dapat menghapus data dosen ini karena {$refusalReason}. Lepaskan tautan akun tersebut terlebih dahulu sebelum menghapus.";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data dosen berhasil dihapus!'
            ]);
        }

        return redirect()->route('master.dosen.index')->with('success', 'Data dosen berhasil dihapus!');
    }

    /**
     * Bulk-delete several Dosen records at once. Each is deleted via the same
     * reassignment logic as a single delete; any that are refused (still linked
     * to a login account) are skipped and reported back rather than failing the
     * whole batch.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:dosens,id'],
        ]);

        $dosens = Dosen::whereIn('id', $validated['ids'])->get();

        $deleted = 0;
        $skipped = [];
        foreach ($dosens as $dosen) {
            $refusalReason = $this->attemptDeleteDosen($dosen);
            if ($refusalReason === null) {
                $deleted++;
            } else {
                $skipped[] = "{$dosen->nama_dosen} ({$refusalReason})";
            }
        }

        $message = "{$deleted} data dosen berhasil dihapus.";
        if (!empty($skipped)) {
            $message .= ' Dilewati: ' . implode('; ', $skipped) . '.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted' => $deleted,
                'skipped' => $skipped,
            ]);
        }

        return back()->with($deleted > 0 ? 'success' : 'error', $message);
    }

    /**
     * Import Master Dosen from Excel (.xlsx, .xls, .csv)
     */
    public function importExcel(Request $request)
    {
        // Validate by file extension rather than sniffed MIME type — browsers/OS report
        // wildly inconsistent MIME types for .xlsx/.csv exports (e.g. application/octet-stream),
        // which caused legitimate Excel files to be rejected by the stricter `mimes:` rule.
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required'   => 'File Excel wajib diunggah.',
            'file.extensions' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'        => 'Ukuran file maksimal 5MB.'
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (count($rows) < 2) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'File Excel kosong atau tidak memiliki data.'], 422);
                }
                return back()->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            // Detect Header Row
            $headerRowIndex = 0;
            $namaCol = 1;
            $nidnCol = 2;
            $waCol = 3;
            $aliasCol = null;
            $kepakaranCol = null;
            $jabatanCol = null;
            $emailCol = null;

            foreach ($rows as $rIdx => $rData) {
                if (!is_array($rData)) continue;
                $matchedInRow = false;
                foreach ($rData as $cIdx => $cellVal) {
                    $valLower = strtolower(trim((string)$cellVal));
                    if (str_contains($valLower, 'jabatan')) {
                        $jabatanCol = $cIdx;
                        $matchedInRow = true;
                    } elseif (str_contains($valLower, 'kepakaran')) {
                        $kepakaranCol = $cIdx;
                        $matchedInRow = true;
                    } elseif (str_contains($valLower, 'email')) {
                        $emailCol = $cIdx;
                        $matchedInRow = true;
                    } elseif (str_contains($valLower, 'alias') || str_contains($valLower, 'inisial')) {
                        $aliasCol = $cIdx;
                        $matchedInRow = true;
                    } elseif (str_contains($valLower, 'nama')) {
                        $namaCol = $cIdx;
                        $matchedInRow = true;
                    } elseif (str_contains($valLower, 'nidn')) {
                        $nidnCol = $cIdx;
                        $matchedInRow = true;
                    } elseif (str_contains($valLower, 'wa') || str_contains($valLower, 'whatsapp') || str_contains($valLower, 'telepon') || str_contains($valLower, 'hp')) {
                        $waCol = $cIdx;
                        $matchedInRow = true;
                    }
                }
                if ($matchedInRow) {
                    $headerRowIndex = $rIdx;
                    break;
                }
            }

            $importedCount = 0;
            $updatedCount = 0;

            \Illuminate\Support\Facades\DB::beginTransaction();

            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (!is_array($row)) continue;

                $namaDosen = trim((string)($row[$namaCol] ?? ''));
                $nidn = trim((string)($row[$nidnCol] ?? ''));
                $noWa = trim((string)($row[$waCol] ?? ''));
                $alias = $aliasCol !== null ? trim((string)($row[$aliasCol] ?? '')) : '';
                $kepakaran = $kepakaranCol !== null ? trim((string)($row[$kepakaranCol] ?? '')) : '';
                $jabatanRaw = $jabatanCol !== null ? trim((string)($row[$jabatanCol] ?? '')) : '';
                $email = $emailCol !== null ? trim((string)($row[$emailCol] ?? '')) : '';
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $email = '';
                }

                // Skip if both nama and nidn are empty
                if (empty($namaDosen) && empty($nidn)) {
                    continue;
                }

                // Format WhatsApp number
                if (!empty($noWa)) {
                    $noWaClean = preg_replace('/[^\d+]/', '', $noWa);
                    if (str_starts_with($noWaClean, '8')) {
                        $noWaClean = '0' . $noWaClean;
                    }
                    $noWa = $noWaClean;
                }

                // Normalize alias: alphanumeric/dash/underscore only, matching the manual-entry validation rule.
                if (!empty($alias)) {
                    $alias = preg_replace('/[^A-Za-z0-9_-]/', '', $alias);
                    if ($alias === '' || Dosen::where('alias', $alias)->exists()) {
                        $alias = null;
                    }
                } else {
                    $alias = null;
                }

                // Only accept jabatan fungsional values that match the known rank list (case-insensitive);
                // anything else is left blank rather than silently stored as an invalid value.
                $jabatanFungsional = null;
                if (!empty($jabatanRaw)) {
                    foreach (array_keys(Dosen::JABATAN_FUNGSIONAL_RANKS) as $validJabatan) {
                        if (strcasecmp($validJabatan, $jabatanRaw) === 0) {
                            $jabatanFungsional = $validJabatan;
                            break;
                        }
                    }
                }

                // Match existing dosen by NIDN or Nama — including previously soft-deleted
                // ones, which are restored here so a re-imported lecturer reconnects with
                // their prior bimbingan/penguji history instead of colliding on the unique NIDN.
                $existing = null;
                if (!empty($nidn)) {
                    $existing = Dosen::withTrashed()->where('nidn', $nidn)->first();
                }
                if (!$existing && !empty($namaDosen)) {
                    $existing = Dosen::withTrashed()->where('nama_dosen', $namaDosen)->first();
                }

                if ($existing && $existing->trashed()) {
                    $existing->restore();
                }

                if ($existing) {
                    $updateData = [];
                    if (!empty($namaDosen)) $updateData['nama_dosen'] = $namaDosen;
                    if (!empty($nidn) && str_starts_with($existing->nidn, 'NIDN-')) $updateData['nidn'] = $nidn;
                    if (!empty($email)) $updateData['email'] = $email;
                    if (!empty($noWa)) $updateData['no_wa'] = $noWa;
                    if (!empty($alias) && empty($existing->alias)) $updateData['alias'] = $alias;
                    if (!empty($kepakaran)) $updateData['kepakaran'] = $kepakaran;
                    if ($jabatanFungsional) $updateData['jabatan_fungsional'] = $jabatanFungsional;
                    if (!empty($updateData)) {
                        $existing->update($updateData);
                        $updatedCount++;
                    }
                } else {
                    // If nidn is empty, fallback to generated NIDN from slug
                    if (empty($nidn)) {
                        $nidn = 'NIDN-' . \Illuminate\Support\Str::slug($namaDosen);
                    }

                    Dosen::create([
                        'nidn' => $nidn,
                        'nama_dosen' => $namaDosen,
                        'email' => $email ?: null,
                        'no_wa' => $noWa ?: null,
                        'alias' => $alias,
                        'kepakaran' => $kepakaran ?: null,
                        'jabatan_fungsional' => $jabatanFungsional,
                    ]);
                    $importedCount++;
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            $msg = "Berhasil memproses data dosen! ({$importedCount} baru ditambahkan, {$updatedCount} diperbarui)";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg
                ]);
            }

            return redirect()->route('master.dosen.index')->with('success', $msg);

        } catch (\Exception $e) {
            if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
                \Illuminate\Support\Facades\DB::rollBack();
            }
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal membaca file Excel: ' . $e->getMessage()], 422);
            }
            return back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel Import Template
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Dosen');

        // Header
        $headers = ['No', 'Nama & Gelar', 'NIDN', 'Email', 'No WhatsApp', 'Alias/Inisial', 'Kepakaran', 'Jabatan Fungsional'];
        foreach ($headers as $colIdx => $text) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $text);
        }

        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Sample Rows
        $samples = [
            [1, 'Dr. Budi Santoso, M.T.', '0012058501', 'budi.santoso@umk.ac.id', '081234567890', 'bds', 'Kecerdasan Buatan', 'Lektor'],
            [2, 'Siti Aminah, S.Kom., M.Cs.', '0015088802', 'siti.aminah@umk.ac.id', '085712345678', 'sam', 'Rekayasa Perangkat Lunak', 'Asisten Ahli'],
        ];

        foreach ($samples as $rIdx => $sample) {
            $rowNum = $rIdx + 2;
            $sheet->setCellValue("A{$rowNum}", $sample[0]);
            $sheet->setCellValue("B{$rowNum}", $sample[1]);
            $sheet->setCellValueExplicit("C{$rowNum}", $sample[2], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("D{$rowNum}", $sample[3]);
            $sheet->setCellValueExplicit("E{$rowNum}", $sample[4], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("F{$rowNum}", $sample[5]);
            $sheet->setCellValue("G{$rowNum}", $sample[6]);
            $sheet->setCellValue("H{$rowNum}", $sample[7]);
        }

        $sheet->getStyle('A1:H3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A2:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:F3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Template_Import_Dosen.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

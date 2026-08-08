<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nidn' => ['required', 'string', 'max:50', 'unique:dosens,nidn'],
            'nama_dosen' => ['required', 'string', 'max:255'],
            'no_wa' => ['nullable', 'string', 'max:30'],
        ], [
            'nidn.required' => 'NIDN wajib diisi.',
            'nidn.unique' => 'NIDN sudah terdaftar.',
            'nama_dosen.required' => 'Nama dosen wajib diisi.',
        ]);

        $dosen = Dosen::create($validated);

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
            'no_wa' => ['nullable', 'string', 'max:30'],
        ], [
            'nidn.required' => 'NIDN wajib diisi.',
            'nidn.unique' => 'NIDN sudah terdaftar.',
            'nama_dosen.required' => 'Nama dosen wajib diisi.',
        ]);

        $dosen->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data dosen berhasil diperbarui!',
                'dosen' => $dosen
            ]);
        }

        return redirect()->route('master.dosen.index')->with('success', 'Data dosen berhasil diperbarui!');
    }

    public function destroy(Request $request, Dosen $dosen)
    {
        // Ensure Super Administrator Dosen exists
        $superAdminDosen = Dosen::firstOrCreate(
            ['nidn' => '0000000000'],
            ['nama_dosen' => 'Super Administrator']
        );

        // Link the first super_admin User to this Super Administrator Dosen if they are not already linked
        $superAdminUser = \App\Models\User::where('role', \App\Models\User::ROLE_SUPER_ADMIN)->first();
        if ($superAdminUser && !$superAdminUser->dosen_id) {
            $superAdminUser->update(['dosen_id' => $superAdminDosen->id]);
        }

        $dosenId = $dosen->id;
        if ($dosenId !== $superAdminDosen->id) {
            // Reassign kesediaan_dosens
            \App\Models\KesediaanDosen::where('dosen_id', $dosenId)
                ->update(['dosen_id' => $superAdminDosen->id]);

            // Reassign sidang roles
            \App\Models\Sidang::where('dosen_pembimbing_utama_id', $dosenId)
                ->update(['dosen_pembimbing_utama_id' => $superAdminDosen->id]);

            \App\Models\Sidang::where('dosen_pembimbing_pendamping_id', $dosenId)
                ->update(['dosen_pembimbing_pendamping_id' => $superAdminDosen->id]);

            \App\Models\Sidang::where('ketua_penguji_id', $dosenId)
                ->update(['ketua_penguji_id' => $superAdminDosen->id]);

            \App\Models\Sidang::where('anggota_penguji_1_id', $dosenId)
                ->update(['anggota_penguji_1_id' => $superAdminDosen->id]);

            \App\Models\Sidang::where('anggota_penguji_2_id', $dosenId)
                ->update(['anggota_penguji_2_id' => $superAdminDosen->id]);
        }

        $dosen->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data dosen berhasil dihapus!'
            ]);
        }

        return redirect()->route('master.dosen.index')->with('success', 'Data dosen berhasil dihapus!');
    }

    /**
     * Import Master Dosen from Excel (.xlsx, .xls, .csv)
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5MB.'
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

            foreach ($rows as $rIdx => $rData) {
                if (!is_array($rData)) continue;
                $matchedInRow = false;
                foreach ($rData as $cIdx => $cellVal) {
                    $valLower = strtolower(trim((string)$cellVal));
                    if (str_contains($valLower, 'nama')) {
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

            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (!is_array($row)) continue;

                $namaDosen = trim((string)($row[$namaCol] ?? ''));
                $nidn = trim((string)($row[$nidnCol] ?? ''));
                $noWa = trim((string)($row[$waCol] ?? ''));

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

                // Match existing dosen by NIDN or Nama
                $existing = null;
                if (!empty($nidn)) {
                    $existing = Dosen::where('nidn', $nidn)->first();
                }
                if (!$existing && !empty($namaDosen)) {
                    $existing = Dosen::where('nama_dosen', $namaDosen)->first();
                }

                if ($existing) {
                    $updateData = [];
                    if (!empty($namaDosen)) $updateData['nama_dosen'] = $namaDosen;
                    if (!empty($nidn) && str_starts_with($existing->nidn, 'NIDN-')) $updateData['nidn'] = $nidn;
                    if (!empty($noWa)) $updateData['no_wa'] = $noWa;
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
                        'no_wa' => $noWa ?: null,
                    ]);
                    $importedCount++;
                }
            }

            $msg = "Berhasil memproses data dosen! ({$importedCount} baru ditambahkan, {$updatedCount} diperbarui)";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg
                ]);
            }

            return redirect()->route('master.dosen.index')->with('success', $msg);

        } catch (\Exception $e) {
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
        $headers = ['No', 'Nama & Gelar', 'NIDN', 'No WhatsApp'];
        foreach ($headers as $colIdx => $text) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $text);
        }

        $sheet->getStyle('A1:D1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet->getStyle('A1:D1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
        $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Sample Rows
        $samples = [
            [1, 'Dr. Budi Santoso, M.T.', '0012058501', '081234567890'],
            [2, 'Siti Aminah, S.Kom., M.Cs.', '0015088802', '085712345678'],
        ];

        foreach ($samples as $rIdx => $sample) {
            $rowNum = $rIdx + 2;
            $sheet->setCellValue("A{$rowNum}", $sample[0]);
            $sheet->setCellValue("B{$rowNum}", $sample[1]);
            $sheet->setCellValueExplicit("C{$rowNum}", $sample[2], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", $sample[3], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        $sheet->getStyle('A1:D3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A2:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range('A', 'D') as $col) {
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

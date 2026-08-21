<?php

namespace App\Http\Controllers;

use App\Models\KesediaanDosen;
use App\Models\Dosen;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class KesediaanDosenController extends Controller
{
    /**
     * Display a listing of Dosen Availability (Kesediaan Dosen)
     */
    public function index(Request $request): View
    {
        $query = KesediaanDosen::with(['dosen', 'periode', 'wave']);

        if ($request->filled('dosen_id')) {
            $query->where('dosen_id', $request->dosen_id);
        }

        if ($request->filled('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('dosen', function ($q) use ($search) {
                $q->where('nama_dosen', 'like', "%{$search}%")
                  ->orWhere('nidn', 'like', "%{$search}%");
            })->orWhere('keterangan', 'like', "%{$search}%");
        }

        $perPage = (int) $request->get('per_page', 10);
        if (!in_array($perPage, [5, 10, 25, 100])) {
            $perPage = 10;
        }

        $rawKesediaans = $query->orderBy('tanggal', 'asc')->get();

        $grouped = $rawKesediaans->groupBy(function ($item) {
            return $item->dosen_id . '_' . ($item->wave_id ?? '0');
        })->map(function ($items) {
            $first = $items->first();
            return [
                'dosen_id' => $first->dosen_id,
                'dosen' => $first->dosen,
                'wave_id' => $first->wave_id,
                'wave' => $first->wave,
                'periode_id' => $first->periode_id,
                'periode' => $first->periode,
                'dates' => $items->pluck('tanggal')->toArray(),
                'keterangans' => $items->pluck('keterangan')->filter()->unique()->toArray(),
            ];
        })->values();

        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $grouped->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $kesediaans = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $grouped->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $dosens = Dosen::orderBy('nama_dosen')->get();
        $periodes = Periode::orderBy('id', 'desc')->get();
        $waves = PendaftaranPeriode::with('periode')->orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();

        return view('master.kesediaan_dosen.index', compact('kesediaans', 'dosens', 'periodes', 'waves', 'activePeriode'));
    }

    /**
     * Import Kesediaan Dosen dari file Excel formulir Google Form
     * ("Silahkan Pilih Nama Anda" + "Silahkan Pilih Hari (Lebih dari 1 Hari)"
     * berisi daftar "Nama Hari, D Bulan YYYY" digabung koma dalam satu sel).
     * Karena formulir hanya menangkap tanggal (bukan jam), tiap tanggal yang
     * dipilih diimport dengan rentang jam kerja default 08:00–17:00.
     */
    public function importExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120'],
            'periode_id' => ['required', 'exists:periodes,id'],
            'wave_id' => ['nullable', 'exists:pendaftaran_periodes,id'],
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.extensions' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5MB.',
            'periode_id.required' => 'Periode akademik wajib dipilih.',
        ]);

        $bulanMap = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        ];
        $dayPattern = '/(?:Senin|Selasa|Rabu|Kamis|Jum[\'’]?at|Sabtu|Minggu)\s*,\s*(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/iu';

        try {
            $file = $request->file('file');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            if (count($rows) < 2) {
                return back()->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            // Cari kolom "nama" & kolom "hari"/"tanggal" dari baris header (baris pertama yang berisi teks)
            $namaCol = 'B';
            $hariCol = 'C';
            $headerRowIndex = 1;
            foreach ($rows as $rIdx => $rData) {
                foreach ($rData as $cIdx => $cellVal) {
                    $valLower = mb_strtolower(trim((string) $cellVal));
                    if ($valLower === '') continue;
                    if (str_contains($valLower, 'nama')) {
                        $namaCol = $cIdx;
                    } elseif (str_contains($valLower, 'hari') || str_contains($valLower, 'tanggal')) {
                        $hariCol = $cIdx;
                    }
                }
                $headerRowIndex = $rIdx;
                break;
            }

            $importedCount = 0;
            $updatedCount = 0;
            $skippedRows = [];
            $dosenTouched = [];

            \Illuminate\Support\Facades\DB::beginTransaction();

            foreach ($rows as $rIdx => $row) {
                if ($rIdx <= $headerRowIndex) continue;

                $namaRaw = trim((string) ($row[$namaCol] ?? ''));
                $hariRaw = (string) ($row[$hariCol] ?? '');

                if ($namaRaw === '' || trim($hariRaw) === '') {
                    continue;
                }

                $dosen = Dosen::resolveByName($namaRaw);
                if (!$dosen) {
                    $skippedRows[] = "Baris {$rIdx}: nama '{$namaRaw}' tidak dapat dicocokkan.";
                    continue;
                }

                if (!preg_match_all($dayPattern, $hariRaw, $matches, PREG_SET_ORDER)) {
                    $skippedRows[] = "Baris {$rIdx} ({$dosen->nama_dosen}): tidak ada tanggal yang bisa dibaca.";
                    continue;
                }

                $dosenTouched[$dosen->id] = $dosen->nama_dosen;

                foreach ($matches as $m) {
                    $day = (int) $m[1];
                    $monthName = mb_strtolower($m[2]);
                    $year = (int) $m[3];
                    $month = $bulanMap[$monthName] ?? null;
                    if (!$month) continue;

                    if (!checkdate($month, $day, $year)) continue;
                    $tanggal = sprintf('%04d-%02d-%02d', $year, $month, $day);

                    $existing = KesediaanDosen::where('dosen_id', $dosen->id)
                        ->where('periode_id', $request->periode_id)
                        ->where('wave_id', $request->wave_id ?: null)
                        ->whereDate('tanggal', $tanggal)
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'jam_mulai' => $existing->jam_mulai ?: '08:00',
                            'jam_selesai' => $existing->jam_selesai ?: '17:00',
                            'keterangan' => 'Import Formulir Kesediaan Menguji (Excel)',
                        ]);
                        $updatedCount++;
                    } else {
                        KesediaanDosen::create([
                            'dosen_id' => $dosen->id,
                            'periode_id' => $request->periode_id,
                            'wave_id' => $request->wave_id ?: null,
                            'tanggal' => $tanggal,
                            'jam_mulai' => '08:00',
                            'jam_selesai' => '17:00',
                            'keterangan' => 'Import Formulir Kesediaan Menguji (Excel)',
                        ]);
                        $importedCount++;
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            $message = "✅ Import selesai: {$importedCount} slot baru ditambahkan, {$updatedCount} slot diperbarui, untuk " . count($dosenTouched) . " dosen.";
            $message .= ' Jam diasumsikan 08:00–17:00 karena file sumber hanya mencatat tanggal, bukan jam spesifik.';
            if (!empty($skippedRows)) {
                $message .= ' ⚠️ ' . count($skippedRows) . ' baris dilewati: ' . implode(' | ', array_slice($skippedRows, 0, 5));
            }

            return back()->with($importedCount + $updatedCount > 0 ? 'success' : 'error', $message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal mengimpor file: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified availability record
     */
    public function destroy(KesediaanDosen $kesediaanDosen): RedirectResponse
    {
        $kesediaanDosen->delete();

        return redirect()->back()->with('success', 'Data kesediaan dosen berhasil dihapus.');
    }

    /**
     * Update Kesediaan Settings
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'show_form_kesediaan' => 'required|boolean',
            'lock_form_kesediaan' => 'required|boolean',
        ]);

        $activePeriode = Periode::where('aktif', true)->first();

        if (!$activePeriode) {
            return redirect()->back()->with('error', 'Gagal menyimpan. Tidak ada periode akademik yang aktif saat ini.');
        }

        $activePeriode->update([
            'show_form_kesediaan' => $request->show_form_kesediaan,
            'lock_form_kesediaan' => $request->lock_form_kesediaan,
        ]);

        return redirect()->back()->with('success', 'Pengaturan form kesediaan menguji berhasil diperbarui.');
    }

    /**
     * Toggle individual Dosen form access
     */
    public function toggleAccess(Dosen $dosen): RedirectResponse
    {
        $dosen->update([
            'can_fill_kesediaan' => !$dosen->can_fill_kesediaan,
        ]);

        $status = $dosen->can_fill_kesediaan ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Akses form kesediaan untuk {$dosen->nama_dosen} berhasil {$status}.");
    }

    /**
     * Delete grouped availability slots
     */
    public function destroyGroup(Request $request): RedirectResponse
    {
        $request->validate([
            'dosen_id' => 'required|exists:dosens,id',
            'wave_id' => 'nullable',
        ]);

        $query = KesediaanDosen::where('dosen_id', $request->dosen_id);
        if ($request->filled('wave_id')) {
            $query->where('wave_id', $request->wave_id);
        } else {
            $query->whereNull('wave_id');
        }

        $query->delete();

        return redirect()->back()->with('success', 'Seluruh data kesediaan dosen untuk gelombang ini berhasil dihapus.');
    }

    /**
     * Hapus SELURUH data kesediaan dosen (semua dosen, semua periode/gelombang).
     */
    public function destroyAll(Request $request): RedirectResponse
    {
        $count = KesediaanDosen::query()->delete();

        return redirect()->route('master.kesediaan-dosen.index')->with('success', "Berhasil menghapus seluruh data kesediaan dosen ({$count} data berhasil dihapus).");
    }
}

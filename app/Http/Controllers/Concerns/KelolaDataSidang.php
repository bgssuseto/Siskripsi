<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Sidang;
use App\Services\ActivityLogger;
use App\Services\SidangConflictService;
use App\Support\TanggalIndo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Aksi yang sama persis di SemproController & SkripsiController — hapus (satu,
 * terpilih, semua), geser jadwal (drag di kalender), form impor, dan pembaca
 * tanggal Excel. Perbedaannya (jenis data, teks pesan, route) diambil dari
 * dataSidangKonfigurasi() di masing-masing controller.
 */
trait KelolaDataSidang
{
    /**
     * @return array{
     *   jenis: array<int,string>,     // jenis_tugas_akhir yang dikelola
     *   label: string,                // "sempro" / "skripsi"
     *   label_semua: string,          // dipakai pesan "hapus semua"
     *   label_terpilih: string,       // dipakai pesan "hapus terpilih"
     *   label_jadwal: string,         // dipakai catatan aktivitas geser jadwal
     *   route_index: string,
     *   view_import: string,
     * }
     */
    abstract protected function dataSidangKonfigurasi(): array;

    public function destroy(Request $request, Sidang $sidang)
    {
        $sidang->delete();
        $pesan = 'Data ' . $this->dataSidangKonfigurasi()['label'] . ' berhasil dihapus!';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $pesan]);
        }

        return back()->with('success', $pesan);
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $k = $this->dataSidangKonfigurasi();
        // ->get()->each->delete() (bukan ->delete() query builder) supaya hook
        // deleting() di model jalan per data dan berkas unggahan ikut terhapus.
        $rows = Sidang::whereIn('jenis_tugas_akhir', $k['jenis'])->get();
        $count = $rows->count();
        $rows->each->delete();

        return redirect()->route($k['route_index'])->with('success', "Berhasil menghapus seluruh data {$k['label_semua']} ({$count} data berhasil dihapus).");
    }

    public function bulkDestroy(Request $request)
    {
        $k = $this->dataSidangKonfigurasi();
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:sidangs,id'],
        ]);

        $rows = Sidang::whereIn('jenis_tugas_akhir', $k['jenis'])->whereIn('id', $validated['ids'])->get();
        $count = $rows->count();
        $rows->each->delete();

        $message = "🗑️ {$count} data {$k['label_terpilih']} berhasil dihapus.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /** Geser jadwal dari kalender (drag & drop): hanya tanggal & jam yang berubah. */
    public function reschedule(Request $request, Sidang $sidang)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam'     => ['required', 'string', 'max:100'],
        ], [
            'tanggal.required' => 'Tanggal baru tidak valid.',
            'jam.required'     => 'Jam baru tidak valid.',
        ]);

        $checkData = array_merge($sidang->toArray(), $validated);
        $scheduleConflicts = SidangConflictService::checkConflicts($checkData, $sidang->id);
        if (!empty($scheduleConflicts)) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Bentrok Jadwal: ' . implode(' | ', $scheduleConflicts),
                'conflicts' => $scheduleConflicts,
            ], 422);
        }

        $before = $sidang->only(['tanggal', 'jam']);
        $sidang->update($validated);

        ActivityLogger::log(
            'reschedule',
            $sidang,
            "Menggeser jadwal {$this->dataSidangKonfigurasi()['label_jadwal']} {$sidang->nama_mahasiswa} ({$sidang->nim}) dari {$before['tanggal']} {$before['jam']} ke {$validated['tanggal']} {$validated['jam']}.",
            ['before' => $before, 'after' => $validated]
        );

        return response()->json([
            'success' => true,
            'message' => '✅ Jadwal ' . $sidang->nama_mahasiswa . ' berhasil dipindahkan.',
        ]);
    }

    public function importForm(): View
    {
        return view($this->dataSidangKonfigurasi()['view_import']);
    }

    private function parseIndonesianDate(mixed $dateVal, $cell = null): ?string
    {
        return TanggalIndo::dariExcel($dateVal, $cell);
    }
}

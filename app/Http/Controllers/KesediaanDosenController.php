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
}

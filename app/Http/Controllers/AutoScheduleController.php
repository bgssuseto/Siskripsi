<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Services\ActivityLogger;
use App\Services\AutoScheduleService;
use App\Services\SidangConflictService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutoScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $periodes = Periode::orderBy('id', 'desc')->get();

        $selectedPeriodeId = $request->get('periode_id');
        if (!$selectedPeriodeId) {
            $activePeriode = Periode::where('aktif', true)->first();
            $selectedPeriodeId = $activePeriode ? $activePeriode->id : ($periodes->first()?->id);
        }

        $jenis = $request->get('jenis', 'skripsi');
        if (!in_array($jenis, ['skripsi', 'sempro'])) {
            $jenis = 'skripsi';
        }

        $selectedGelombang = $request->get('gelombang');
        $slotMinutes = (int) $request->get('slot_menit', 60);
        if (!in_array($slotMinutes, [30, 60, 90, 120])) {
            $slotMinutes = 60;
        }

        $gelombangOptions = $selectedPeriodeId
            ? PendaftaranPeriode::where('periode_id', $selectedPeriodeId)
                ->where('jenis', $jenis)
                ->orderBy('gelombang')
                ->pluck('gelombang')
            : collect();

        $proposals = [];
        $unresolved = [];
        $generated = $request->boolean('generate') && $selectedPeriodeId;

        if ($generated) {
            $result = AutoScheduleService::generateProposals(
                (int) $selectedPeriodeId,
                $jenis,
                $selectedGelombang !== null && $selectedGelombang !== '' ? (int) $selectedGelombang : null,
                $slotMinutes
            );

            $dosenIds = collect($result['proposals'])->flatMap(fn ($p) => array_values($p['dosen']))->unique();
            $dosenNames = Dosen::whereIn('id', $dosenIds)->pluck('nama_dosen', 'id');

            $proposals = collect($result['proposals'])->map(function ($p) use ($dosenNames) {
                $p['dosen_display'] = collect($p['dosen'])->map(fn ($id, $role) => "{$role}: " . ($dosenNames[$id] ?? '-'))->values()->all();
                return $p;
            })->all();

            $unresolved = $result['unresolved'];
        }

        $ruangs = Ruang::orderBy('kode_ruangan')->get();

        return view('penjadwalan.auto-schedule.index', compact(
            'periodes', 'selectedPeriodeId', 'jenis', 'selectedGelombang', 'gelombangOptions',
            'slotMinutes', 'proposals', 'unresolved', 'generated', 'ruangs'
        ));
    }

    public function apply(Request $request): RedirectResponse
    {
        $selected = (array) $request->input('selected', []);
        $rows = (array) $request->input('proposals', []);

        $applied = 0;
        $skipped = [];

        foreach ($selected as $sidangId) {
            $row = $rows[$sidangId] ?? null;
            if (!$row || empty($row['tanggal']) || empty($row['jam']) || empty($row['ruang_id'])) {
                continue;
            }

            $sidang = Sidang::find($sidangId);
            if (!$sidang || $sidang->tanggal) {
                $skipped[] = "{$sidangId}: sudah terjadwal atau tidak ditemukan, dilewati.";
                continue;
            }

            $checkData = array_merge($sidang->toArray(), [
                'tanggal'  => $row['tanggal'],
                'jam'      => $row['jam'],
                'ruang_id' => $row['ruang_id'],
            ]);

            $conflicts = SidangConflictService::checkConflicts($checkData, $sidang->id);
            if (!empty($conflicts)) {
                $skipped[] = "{$sidang->nama_mahasiswa}: bentrok jadwal terbaru, dilewati (" . implode(' | ', $conflicts) . ').';
                continue;
            }

            $before = $sidang->only(['tanggal', 'jam', 'ruang_id']);
            $sidang->update([
                'tanggal'  => $row['tanggal'],
                'jam'      => $row['jam'],
                'ruang_id' => $row['ruang_id'],
            ]);

            ActivityLogger::log(
                'jadwalkan',
                $sidang,
                "Menetapkan jadwal otomatis untuk {$sidang->nama_mahasiswa} ({$sidang->nim}) pada {$row['tanggal']} {$row['jam']} via Asisten Plotting Jadwal Otomatis.",
                ['before' => $before, 'after' => $sidang->only(array_keys($before))]
            );

            $applied++;
        }

        $message = "✅ {$applied} jadwal berhasil diterapkan.";
        if (!empty($skipped)) {
            $message .= ' ⚠️ ' . count($skipped) . ' dilewati: ' . implode(' ', $skipped);
        }

        return back()->with($applied > 0 ? 'success' : 'warning', $message);
    }
}

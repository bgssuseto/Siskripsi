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

        $sidangIds = array_filter(array_map('intval', (array) $request->get('ids', [])));

        $proposals = [];
        $unresolved = [];
        $generated = $request->boolean('generate') && $selectedPeriodeId;

        if ($generated) {
            $result = AutoScheduleService::generateProposals(
                (int) $selectedPeriodeId,
                $jenis,
                $selectedGelombang !== null && $selectedGelombang !== '' ? (int) $selectedGelombang : null,
                $slotMinutes,
                !empty($sidangIds) ? $sidangIds : null
            );

            $dosenIds = collect($result['proposals'])->flatMap(fn ($p) => array_values($p['dosen']))->unique();
            $dosenNames = Dosen::whereIn('id', $dosenIds)->pluck('nama_dosen', 'id');

            $proposals = collect($result['proposals'])->map(function ($p) use ($dosenNames) {
                $beban = $p['beban'] ?? [];
                $p['dosen_display'] = collect($p['dosen'])->map(function ($id, $role) use ($dosenNames, $beban) {
                    $line = "{$role}: " . ($dosenNames[$id] ?? '-');
                    if (isset($beban[$role])) {
                        $line .= " (meluluskan {$beban[$role]['meluluskan']} · menguji {$beban[$role]['menguji']})";
                    }
                    return $line;
                })->values()->all();
                return $p;
            })->all();

            $unresolved = $result['unresolved'];
        }

        $ruangs = Ruang::orderBy('kode_ruangan')->get();
        $dosens = Dosen::orderBy('nama_dosen')->get();
        $jamOptions = ['07.00', '07.30', '08.00', '08.30', '09.00', '09.30', '10.00', '10.30', '11.00', '11.30', '12.00', '12.30', '13.00', '13.30', '14.00', '14.30', '15.00', '15.30', '16.00', '16.30', '17.00', '17.30', '18.00'];

        return view('penjadwalan.auto-schedule.index', compact(
            'periodes', 'selectedPeriodeId', 'jenis', 'selectedGelombang', 'gelombangOptions',
            'slotMinutes', 'proposals', 'unresolved', 'generated', 'ruangs', 'sidangIds', 'dosens', 'jamOptions'
        ));
    }

    public function apply(Request $request): RedirectResponse
    {
        $request->validate([
            'selected'                          => ['nullable', 'array'],
            'selected.*'                        => ['integer', 'exists:sidangs,id'],
            'proposals'                          => ['nullable', 'array'],
            'proposals.*.tanggal'                => ['nullable', 'date'],
            'proposals.*.jam_mulai'              => ['nullable', 'string'],
            'proposals.*.jam_selesai'            => ['nullable', 'string'],
            'proposals.*.ruang_id'               => ['nullable', 'integer', 'exists:ruangs,id'],
            'proposals.*.ketua_penguji_id'       => ['nullable', 'integer', 'exists:dosens,id'],
            'proposals.*.anggota_penguji_1_id'  => ['nullable', 'integer', 'exists:dosens,id'],
        ]);

        $selected = (array) $request->input('selected', []);
        $rows = (array) $request->input('proposals', []);

        $applied = 0;
        $skipped = [];

        foreach ($selected as $sidangId) {
            $row = $rows[$sidangId] ?? null;
            if ($row && !empty($row['jam_mulai']) && !empty($row['jam_selesai'])) {
                $row['jam'] = $row['jam_mulai'] . ' - ' . $row['jam_selesai'];
            }
            if (!$row || empty($row['tanggal']) || empty($row['jam']) || empty($row['ruang_id'])) {
                continue;
            }

            $sidang = Sidang::find($sidangId);
            if (!$sidang || $sidang->tanggal) {
                $skipped[] = "{$sidangId}: sudah terjadwal atau tidak ditemukan, dilewati.";
                continue;
            }

            $update = [
                'tanggal'  => $row['tanggal'],
                'jam'      => $row['jam'],
                'ruang_id' => $row['ruang_id'],
            ];
            if (!empty($row['ketua_penguji_id']) && !empty($row['anggota_penguji_1_id'])) {
                $update['ketua_penguji_id'] = $row['ketua_penguji_id'];
                $update['anggota_penguji_1_id'] = $row['anggota_penguji_1_id'];
            }

            $checkData = array_merge($sidang->toArray(), $update);

            $conflicts = SidangConflictService::checkConflicts($checkData, $sidang->id);
            if (!empty($conflicts)) {
                $skipped[] = "{$sidang->nama_mahasiswa}: bentrok jadwal terbaru, dilewati (" . implode(' | ', $conflicts) . ').';
                continue;
            }

            $compositionErrors = SidangConflictService::checkPengujiCompositionRules($checkData);
            if (!empty($compositionErrors)) {
                $skipped[] = "{$sidang->nama_mahasiswa}: melanggar Rule Komposisi Dosen Penguji, dilewati (" . implode(' | ', $compositionErrors) . ').';
                continue;
            }

            $before = $sidang->only(array_keys($update));
            $sidang->update($update);

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

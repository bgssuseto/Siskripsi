<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalitikController extends Controller
{
    /**
     * Dashboard Analitik untuk Koordinator/Kaprodi — beban dosen, keterpakaian
     * ruang, dan timeline sidang dalam satu tampilan level program studi.
     */
    public function index(Request $request): View
    {
        $periodes = Periode::orderBy('id', 'desc')->get();

        $selectedPeriodeId = $request->get('periode_id');
        if (!$selectedPeriodeId) {
            $activePeriode = Periode::where('aktif', true)->first();
            $selectedPeriodeId = $activePeriode ? $activePeriode->id : ($periodes->first()?->id);
        }
        $selectedPeriode = Periode::find($selectedPeriodeId);

        $jenis = $request->get('jenis'); // '' | 'skripsi' | 'sempro'

        $query = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2', 'ruang']);
        if ($selectedPeriodeId) {
            $query->where('periode_id', $selectedPeriodeId);
        }
        if ($jenis === 'sempro') {
            $query->where('jenis_tugas_akhir', 'sempro');
        } elseif ($jenis === 'skripsi') {
            $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
        }

        $sidangs = $query->get();

        // ── KPI Ringkas ──
        $totalSkripsi = $sidangs->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])->count();
        $totalSempro = $sidangs->where('jenis_tugas_akhir', 'sempro')->count();
        $totalTerjadwal = $sidangs->whereNotNull('tanggal')->count();
        $totalBelumJadwal = $sidangs->whereNull('tanggal')->count();

        // ── Beban Dosen (bimbingan + menguji digabung, diranking) ──
        $bebanDosen = Dosen::orderBy('nama_dosen')->get()->map(function ($d) use ($sidangs) {
            $bimbingUtama = $sidangs->where('dosen_pembimbing_utama_id', $d->id)->count();
            $bimbingPendamping = $sidangs->where('dosen_pembimbing_pendamping_id', $d->id)->count();
            $menguji = $sidangs->filter(function ($s) use ($d) {
                return $s->ketua_penguji_id == $d->id || $s->anggota_penguji_1_id == $d->id || $s->anggota_penguji_2_id == $d->id;
            })->count();
            $total = $bimbingUtama + $bimbingPendamping + $menguji;

            return [
                'dosen'              => $d,
                'bimbing_utama'      => $bimbingUtama,
                'bimbing_pendamping' => $bimbingPendamping,
                'menguji'            => $menguji,
                'total'              => $total,
            ];
        })->filter(fn ($row) => $row['total'] > 0)->sortByDesc('total')->values();

        $maxBeban = (int) ($bebanDosen->max('total') ?? 0) ?: 1;

        // ── Keterpakaian Ruang ──
        $keterpakaianRuang = Ruang::orderBy('kode_ruangan')->get()->map(function ($r) use ($sidangs) {
            $jumlah = $sidangs->where('ruang_id', $r->id)->whereNotNull('tanggal')->count();
            return ['ruang' => $r, 'jumlah' => $jumlah];
        })->filter(fn ($row) => $row['jumlah'] > 0)->sortByDesc('jumlah')->values();

        $maxRuang = (int) ($keterpakaianRuang->max('jumlah') ?? 0) ?: 1;

        // ── Timeline Sidang per Tanggal ──
        $timeline = $sidangs->whereNotNull('tanggal')
            ->groupBy(fn ($s) => $s->tanggal->format('Y-m-d'))
            ->map->count()
            ->sortKeys();

        $maxTimeline = (int) ($timeline->max() ?? 0) ?: 1;

        return view('administrasi.analitik.index', compact(
            'periodes', 'selectedPeriode', 'selectedPeriodeId', 'jenis',
            'totalSkripsi', 'totalSempro', 'totalTerjadwal', 'totalBelumJadwal',
            'bebanDosen', 'maxBeban', 'keterpakaianRuang', 'maxRuang', 'timeline', 'maxTimeline'
        ));
    }
}

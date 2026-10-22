<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Ruang;
use App\Models\Sidang;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SemuaJadwalController extends Controller
{
    /**
     * Combined "Semua Jadwal" view for super_admin: every scheduled sidang
     * (sempro AND skripsi/jurnal together) in one list, filterable by tanggal
     * (hanya tanggal yang benar-benar ada jadwalnya), examiner/penguji, and room — on top of the per-jenis pages
     * (Jadwal Sidang Skripsi / Jadwal Sempro) which only ever show one type
     * at a time.
     */
    public function index(Request $request): View
    {
        $query = Sidang::with([
            'pembimbingUtama', 'pembimbingPendamping',
            'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
            'ruang', 'periode',
        ])->whereNotNull('tanggal');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mahasiswa', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('judul_skripsi', 'like', "%{$search}%");
            });
        }

        if ($tanggal = $request->get('tanggal')) {
            // Termasuk sesi Pembimbing Pendamping pada sempro berjadwal terpisah.
            $query->where(fn ($q) => $q->whereDate('tanggal', $tanggal)->orWhereDate('tanggal_pendamping', $tanggal));
        }

        if ($ruangId = $request->get('ruang_id')) {
            $query->where('ruang_id', $ruangId);
        }

        if ($pengujiId = $request->get('penguji_id')) {
            $query->where(function ($q) use ($pengujiId) {
                $q->where('ketua_penguji_id', $pengujiId)
                  ->orWhere('anggota_penguji_1_id', $pengujiId)
                  ->orWhere('anggota_penguji_2_id', $pengujiId)
                  ->orWhere('dosen_pembimbing_utama_id', $pengujiId)
                  ->orWhere('dosen_pembimbing_pendamping_id', $pengujiId);
            });
        }

        $sidangs = $query->orderBy('tanggal', 'asc')
                          ->orderBy('jam', 'asc')
                          ->paginate(25)
                          ->withQueryString();

        $ruangs = Ruang::orderBy('kode_ruangan')->get();
        $dosens = Dosen::excludingSuperAdminPlaceholder()->orderBy('nama_dosen')->get();

        // Pilihan filter tanggal: hanya tanggal yang punya jadwal, mis. "Selasa, 14 Juli 2026 · 3 jadwal".
        $tanggalOptions = Sidang::whereNotNull('tanggal')->pluck('tanggal')
            ->merge(Sidang::whereNotNull('tanggal_pendamping')->pluck('tanggal_pendamping'))
            ->map(fn ($t) => \Illuminate\Support\Carbon::parse($t)->toDateString())
            ->countBy()
            ->sortKeys()
            ->map(fn ($jumlah, $tgl) => \Illuminate\Support\Carbon::parse($tgl)->locale('id')->translatedFormat('l, j F Y') . " · {$jumlah} jadwal");

        $totalSempro = Sidang::whereNotNull('tanggal')->where('jenis_tugas_akhir', 'sempro')->count();
        $totalSkripsi = Sidang::whereNotNull('tanggal')->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)->count();

        return view('jadwal.semua', compact('sidangs', 'ruangs', 'dosens', 'tanggalOptions', 'totalSempro', 'totalSkripsi'));
    }
}

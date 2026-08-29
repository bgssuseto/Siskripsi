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
     * (sempro AND skripsi/jurnal together) in one list, filterable by day of
     * week, examiner/penguji, and room — on top of the per-jenis pages
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

        if ($hari = $request->get('hari')) {
            // 0 (Sunday) .. 6 (Saturday), matching Carbon::dayOfWeek
            $query->whereRaw('DAYOFWEEK(tanggal) = ?', [((int) $hari) + 1]);
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
        $dosens = Dosen::orderBy('nama_dosen')->get();

        $hariOptions = [
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu',
        ];

        $totalSempro = Sidang::whereNotNull('tanggal')->where('jenis_tugas_akhir', 'sempro')->count();
        $totalSkripsi = Sidang::whereNotNull('tanggal')->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)->count();

        return view('jadwal.semua', compact('sidangs', 'ruangs', 'dosens', 'hariOptions', 'totalSempro', 'totalSkripsi'));
    }
}

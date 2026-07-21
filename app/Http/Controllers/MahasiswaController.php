<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class MahasiswaController extends Controller
{
    /**
     * Helper to retrieve sidang records strictly belonging to the logged-in student (by NIM or Name)
     */
    private function getStudentSidangs($user): Collection
    {
        $nim  = $user->nim ?? null;
        $name = $user->name ?? null;

        $query = Sidang::with([
            'pembimbingUtama',
            'pembimbingPendamping',
            'ketuaPenguji',
            'anggotaPenguji1',
            'anggotaPenguji2',
            'ruang',
            'periode'
        ]);

        if ($nim && $name) {
            return $query->where(function ($q) use ($nim, $name) {
                $q->where('nim', $nim)
                  ->orWhere('nama_mahasiswa', 'LIKE', '%' . $name . '%');
            })->orderByDesc('id')->get();
        } elseif ($nim) {
            return $query->where('nim', $nim)->orderByDesc('id')->get();
        } elseif ($name) {
            return $query->where('nama_mahasiswa', 'LIKE', '%' . $name . '%')->orderByDesc('id')->get();
        }

        return collect();
    }

    /**
     * Student Dashboard
     */
    public function dashboard(): View
    {
        $user    = Auth::user();
        $sidangs = $this->getStudentSidangs($user);

        $activePeriode = Periode::where('aktif', true)->first();

        // Stats
        $sidangSkripsi = $sidangs->where('jenis_tugas_akhir', 'sidang')->first();
        $sidangJurnal  = $sidangs->where('jenis_tugas_akhir', 'jurnal')->first();

        return view('mahasiswa.dashboard', compact(
            'user', 'sidangs', 'sidangSkripsi', 'sidangJurnal', 'activePeriode'
        ));
    }

    /**
     * Student Sempro registration index
     */
    public function pendaftaranIndex(): View
    {
        return $this->semproIndex();
    }

    public function semproIndex(): View
    {
        $user     = Auth::user();
        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();

        // All student's registrations
        $sidangs = $this->getStudentSidangs($user);

        return view('mahasiswa.sempro', compact(
            'user', 'sidangs', 'periodes', 'activePeriode'
        ));
    }

    /**
     * Student Skripsi registration index
     */
    public function skripsiIndex(): View
    {
        $user     = Auth::user();
        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('aktif', true)->first();

        // Filter student's sidang skripsi only
        $sidangs = $this->getStudentSidangs($user)->where('jenis_tugas_akhir', 'sidang');

        return view('mahasiswa.skripsi', compact(
            'user', 'sidangs', 'periodes', 'activePeriode'
        ));
    }

    /**
     * Student Jadwal Sidang index - Strictly filters schedule for THIS student only
     */
    public function jadwalIndex(): View
    {
        $user          = Auth::user();
        $activePeriode = Periode::where('aktif', true)->first();

        // Strictly fetch schedule matching logged-in student's NIM or Name
        $sidangs = $this->getStudentSidangs($user)->sortByDesc('tanggal');

        return view('mahasiswa.jadwal', compact(
            'user', 'sidangs', 'activePeriode'
        ));
    }
}

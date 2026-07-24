<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use App\Models\Dosen;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use App\Models\KesediaanDosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DosenPortalController extends Controller
{
    /**
     * Dosen Dashboard
     */
    public function dashboard(): View
    {
        $user = Auth::user();
        $dosen = $user->dosen;

        $activePeriode = Periode::where('aktif', true)->first();

        $stats = [
            'total_sempro' => 0,
            'total_skripsi' => 0,
            'total_jurnal' => 0,
            'recent_schedules' => collect(),
            'is_linked' => $dosen !== null,
        ];

        $todayStr = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        
        $activeWaveOpen = PendaftaranPeriode::whereDate('tanggal_mulai', '<=', $todayStr)
            ->whereDate('tanggal_selesai', '>=', $todayStr)
            ->exists();

        // Registration is closed if no wave is currently open for registration or if registration wave ended
        $isRegistrationClosed = !$activeWaveOpen;
        $showFormKesediaan = $activePeriode ? (bool) $activePeriode->show_form_kesediaan : true;
        if ($dosen) {
            $showFormKesediaan = $showFormKesediaan && (bool) $dosen->can_fill_kesediaan;
        }
        $isLockedKesediaan = $activePeriode ? (bool) $activePeriode->lock_form_kesediaan : false;
        $activeWaveInfo = PendaftaranPeriode::orderBy('tanggal_selesai', 'desc')->first();

        if ($dosen) {
            $dosenId = $dosen->id;

            $existingKesediaan = KesediaanDosen::with(['wave', 'periode'])
                ->where('dosen_id', $dosen->id)
                ->orderBy('tanggal', 'asc')
                ->get();

            // Total Bimbingan Mahasiswa
            $totalBimbingan = Sidang::where(function ($q) use ($dosenId) {
                $q->where('dosen_pembimbing_utama_id', $dosenId)
                  ->orWhere('dosen_pembimbing_pendamping_id', $dosenId);
            })->count();

            // Total Menguji Sempro
            $stats['total_sempro'] = Sidang::where('jenis_tugas_akhir', 'sempro')
                ->where(function ($q) use ($dosenId) {
                    $q->where('ketua_penguji_id', $dosenId)
                      ->orWhere('anggota_penguji_1_id', $dosenId)
                      ->orWhere('anggota_penguji_2_id', $dosenId)
                      ->orWhere('dosen_pembimbing_utama_id', $dosenId)
                      ->orWhere('dosen_pembimbing_pendamping_id', $dosenId);
                })->count();

            // Total Menguji Skripsi
            $stats['total_skripsi'] = Sidang::whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])
                ->where(function ($q) use ($dosenId) {
                    $q->where('ketua_penguji_id', $dosenId)
                      ->orWhere('anggota_penguji_1_id', $dosenId)
                      ->orWhere('anggota_penguji_2_id', $dosenId)
                      ->orWhere('dosen_pembimbing_utama_id', $dosenId);
                })->count();

            $stats['total_jurnal'] = $totalBimbingan;

            $stats['recent_schedules'] = Sidang::where(function ($q) use ($dosenId) {
                    $q->where('dosen_pembimbing_utama_id', $dosenId)
                      ->orWhere('dosen_pembimbing_pendamping_id', $dosenId)
                      ->orWhere('ketua_penguji_id', $dosenId)
                      ->orWhere('anggota_penguji_1_id', $dosenId)
                      ->orWhere('anggota_penguji_2_id', $dosenId);
                })
                ->with(['pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2', 'ruang', 'periode'])
                ->orderBy('tanggal', 'desc')
                ->limit(10)
                ->get();
        } else {
            $existingKesediaan = collect();
        }

        $allWaves = PendaftaranPeriode::orderBy('id', 'desc')->get();

        return view('dosen.dashboard', compact('stats', 'dosen', 'showFormKesediaan', 'isLockedKesediaan', 'activeWaveInfo', 'allWaves', 'existingKesediaan', 'isRegistrationClosed', 'activePeriode'));
    }

    /**
     * Store Dosen Availability
     */
    public function storeKesediaan(Request $request)
    {
        $user = Auth::user();
        $dosen = $user->dosen;

        if (!$dosen) {
            return redirect()->back()->with('error', 'Akun Anda belum terhubung dengan data Dosen.');
        }

        $request->validate([
            'slots' => 'required|array|min:1',
            'slots.*.tanggal' => 'required|date',
            'slots.*.keterangan' => 'nullable|string',
            'wave_id' => 'required|exists:pendaftaran_periodes,id',
        ]);

        $activePeriode = Periode::where('aktif', true)->first();

        foreach ($request->slots as $slot) {
            KesediaanDosen::create([
                'dosen_id' => $dosen->id,
                'periode_id' => $activePeriode->id ?? null,
                'wave_id' => $request->wave_id,
                'tanggal' => $slot['tanggal'],
                'jam_mulai' => '',
                'jam_selesai' => '',
                'keterangan' => $slot['keterangan'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'Form kesediaan menguji berhasil disimpan.');
    }

    /**
     * Delete Dosen Availability Slot
     */
    public function destroyKesediaan($id)
    {
        $user = Auth::user();
        $dosen = $user->dosen;

        if (!$dosen) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $kesediaan = KesediaanDosen::where('id', $id)->where('dosen_id', $dosen->id)->firstOrFail();
        $kesediaan->delete();

        return redirect()->back()->with('success', 'Slot kesediaan berhasil dihapus.');
    }

    /**
     * Dosen Seminar Proposal Schedule
     */
    public function sempro(Request $request): View
    {
        $user = Auth::user();
        $dosen = $user->dosen;
        $schedules = collect();

        $activePeriode = Periode::where('aktif', true)->first();

        if ($dosen && $activePeriode) {
            $dosenId = $dosen->id;
            $query = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2', 'ruang', 'periode'])
                ->where('periode_id', $activePeriode->id)
                ->where('jenis_tugas_akhir', 'sempro')
                ->where(function ($q) use ($dosenId) {
                    $q->where('dosen_pembimbing_utama_id', $dosenId)
                      ->orWhere('dosen_pembimbing_pendamping_id', $dosenId)
                      ->orWhere('ketua_penguji_id', $dosenId)
                      ->orWhere('anggota_penguji_1_id', $dosenId)
                      ->orWhere('anggota_penguji_2_id', $dosenId);
                });

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_mahasiswa', 'like', "%{$search}%")
                      ->orWhere('nim', 'like', "%{$search}%")
                      ->orWhere('judul_skripsi', 'like', "%{$search}%");
                });
            }

            if ($request->filled('tanggal')) {
                $query->whereDate('tanggal', $request->tanggal);
            }

            if ($request->filled('status')) {
                $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
                $status = $request->status;
                if ($status === 'terjadwal') {
                    $query->whereNotNull('tanggal')->whereDate('tanggal', '>', $today);
                } elseif ($status === 'proses') {
                    $query->whereNotNull('tanggal')->whereDate('tanggal', '=', $today);
                } elseif ($status === 'sudah') {
                    $query->whereNotNull('tanggal')->whereDate('tanggal', '<', $today);
                } elseif ($status === 'belum_plotting') {
                    $query->whereNull('tanggal');
                }
            }

            $schedules = $query->orderBy('tanggal', 'desc')->orderBy('jam', 'asc')->paginate(10)->withQueryString();
        }

        return view('dosen.sempro', compact('schedules', 'dosen'));
    }

    /**
     * Dosen Sidang Skripsi & Jurnal Schedule
     */
    public function skripsi(Request $request): View
    {
        $user = Auth::user();
        $dosen = $user->dosen;
        $schedules = collect();

        $activePeriode = Periode::where('aktif', true)->first();

        if ($dosen && $activePeriode) {
            $dosenId = $dosen->id;
            $query = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2', 'ruang', 'periode'])
                ->where('periode_id', $activePeriode->id)
                ->whereIn('jenis_tugas_akhir', ['skripsi', 'jurnal', 'sidang'])
                ->where(function ($q) use ($dosenId) {
                    $q->where('dosen_pembimbing_utama_id', $dosenId)
                      ->orWhere('ketua_penguji_id', $dosenId)
                      ->orWhere('anggota_penguji_1_id', $dosenId)
                      ->orWhere('anggota_penguji_2_id', $dosenId);
                });

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_mahasiswa', 'like', "%{$search}%")
                      ->orWhere('nim', 'like', "%{$search}%")
                      ->orWhere('judul_skripsi', 'like', "%{$search}%");
                });
            }

            if ($request->filled('jenis') && in_array($request->jenis, ['skripsi', 'jurnal'], true)) {
                $query->where('jenis_tugas_akhir', $request->jenis);
            }

            if ($request->filled('tanggal')) {
                $query->whereDate('tanggal', $request->tanggal);
            }

            if ($request->filled('status')) {
                $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
                $status = $request->status;
                if ($status === 'terjadwal') {
                    $query->whereNotNull('tanggal')->whereDate('tanggal', '>', $today);
                } elseif ($status === 'proses') {
                    $query->whereNotNull('tanggal')->whereDate('tanggal', '=', $today);
                } elseif ($status === 'sudah') {
                    $query->whereNotNull('tanggal')->whereDate('tanggal', '<', $today);
                } elseif ($status === 'belum_plotting') {
                    $query->whereNull('tanggal');
                }
            }

            $schedules = $query->orderBy('tanggal', 'desc')->orderBy('jam', 'asc')->paginate(10)->withQueryString();
        }

        return view('dosen.skripsi', compact('schedules', 'dosen'));
    }

    /**
     * Dosen Calendar View
     */
    public function kalender(Request $request): View
    {
        $user = Auth::user();
        $dosen = $user->dosen;
        $schedules = collect();
        $calendarEvents = collect();

        $activePeriode = Periode::where('aktif', true)->first();

        if ($dosen && $activePeriode) {
            $dosenId = $dosen->id;
            
            $query = Sidang::with([
                'ruang', 'periode', 'pembimbingUtama', 'pembimbingPendamping',
                'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'
            ])
                ->where('periode_id', $activePeriode->id)
                ->whereNotNull('tanggal')
                ->where(function ($q) use ($dosenId) {
                    $q->where(function ($sq) use ($dosenId) {
                        $sq->where('jenis_tugas_akhir', 'sempro')
                           ->where(function ($inner) use ($dosenId) {
                               $inner->where('dosen_pembimbing_utama_id', $dosenId)
                                     ->orWhere('dosen_pembimbing_pendamping_id', $dosenId)
                                     ->orWhere('ketua_penguji_id', $dosenId)
                                     ->orWhere('anggota_penguji_1_id', $dosenId)
                                     ->orWhere('anggota_penguji_2_id', $dosenId);
                           });
                    })->orWhere(function ($sq) use ($dosenId) {
                        $sq->whereIn('jenis_tugas_akhir', ['skripsi', 'jurnal', 'sidang'])
                           ->where(function ($inner) use ($dosenId) {
                               $inner->where('dosen_pembimbing_utama_id', $dosenId)
                                     ->orWhere('ketua_penguji_id', $dosenId)
                                     ->orWhere('anggota_penguji_1_id', $dosenId)
                                     ->orWhere('anggota_penguji_2_id', $dosenId);
                           });
                    });
                });

            // Apply Filters for the List View
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_mahasiswa', 'like', "%{$search}%")
                      ->orWhere('nim', 'like', "%{$search}%")
                      ->orWhere('judul_skripsi', 'like', "%{$search}%");
                });
            }

            if ($request->filled('jenis') && in_array($request->jenis, ['sempro', 'skripsi', 'jurnal'], true)) {
                $query->where('jenis_tugas_akhir', $request->jenis);
            }

            if ($request->filled('tanggal')) {
                $query->whereDate('tanggal', $request->tanggal);
            }

            if ($request->filled('status')) {
                $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
                $status = $request->status;
                if ($status === 'terjadwal') {
                    $query->whereDate('tanggal', '>', $today);
                } elseif ($status === 'proses') {
                    $query->whereDate('tanggal', '=', $today);
                } elseif ($status === 'sudah') {
                    $query->whereDate('tanggal', '<', $today);
                }
            }

            $schedules = $query->orderBy('tanggal', 'desc')->orderBy('jam', 'asc')->get();

            // Fetch ALL scheduled to render fully in the calendar (calendar should show all)
            $allScheduled = Sidang::with([
                'ruang', 'periode', 'pembimbingUtama', 'pembimbingPendamping',
                'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'
            ])
                ->where('periode_id', $activePeriode->id)
                ->whereNotNull('tanggal')
                ->where(function ($q) use ($dosenId) {
                    $q->where(function ($sq) use ($dosenId) {
                        $sq->where('jenis_tugas_akhir', 'sempro')
                           ->where(function ($inner) use ($dosenId) {
                               $inner->where('dosen_pembimbing_utama_id', $dosenId)
                                     ->orWhere('dosen_pembimbing_pendamping_id', $dosenId)
                                     ->orWhere('ketua_penguji_id', $dosenId)
                                     ->orWhere('anggota_penguji_1_id', $dosenId)
                                     ->orWhere('anggota_penguji_2_id', $dosenId);
                           });
                    })->orWhere(function ($sq) use ($dosenId) {
                        $sq->whereIn('jenis_tugas_akhir', ['skripsi', 'jurnal', 'sidang'])
                           ->where(function ($inner) use ($dosenId) {
                               $inner->where('dosen_pembimbing_utama_id', $dosenId)
                                     ->orWhere('ketua_penguji_id', $dosenId)
                                     ->orWhere('anggota_penguji_1_id', $dosenId)
                                     ->orWhere('anggota_penguji_2_id', $dosenId);
                           });
                    });
                })
                ->get();

            $calendarEvents = $allScheduled->map(function ($s) use ($dosen) {
                $isSempro = $s->jenis_tugas_akhir === 'sempro';
                $eventColor = $isSempro ? '#3b82f6' : '#10b981'; // blue for sempro, emerald for sidang
                $borderColor = $isSempro ? '#2563eb' : '#059669';

                $roles = [];
                 if ($s->dosen_pembimbing_utama_id === $dosen->id) $roles[] = 'Pembimbing Utama';
                 if ($isSempro && $s->dosen_pembimbing_pendamping_id === $dosen->id) $roles[] = 'Pembimbing Pendamping';
                 if ($s->ketua_penguji_id === $dosen->id) $roles[] = 'Ketua Penguji';
                 if ($s->anggota_penguji_1_id === $dosen->id) $roles[] = 'Anggota Penguji 1';
                 if ($s->anggota_penguji_2_id === $dosen->id) $roles[] = 'Anggota Penguji 2';

                return [
                    'id'              => $s->id,
                    'title'           => ($isSempro ? 'Sempro: ' : 'Sidang: ') . $s->nama_mahasiswa,
                    'start'           => $s->tanggal->format('Y-m-d'),
                    'color'           => $eventColor,
                    'backgroundColor' => $eventColor,
                    'borderColor'     => $borderColor,
                    'textColor'       => '#ffffff',
                    'extendedProps'   => [
                        'nim'            => $s->nim,
                        'mahasiswa'      => $s->nama_mahasiswa,
                        'judul'          => $s->judul_skripsi,
                        'jenis'          => $isSempro ? 'Seminar Proposal' : 'Sidang Skripsi',
                        'dosbing'        => $s->pembimbingUtama ? $s->pembimbingUtama->nama_dosen : '-',
                        'dosbing_p'      => $s->pembimbingPendamping ? $s->pembimbingPendamping->nama_dosen : '-',
                        'ketua_penguji'  => $s->ketuaPenguji ? $s->ketuaPenguji->nama_dosen : '-',
                        'penguji_1'      => $s->anggotaPenguji1 ? $s->anggotaPenguji1->nama_dosen : '-',
                        'penguji_2'      => $s->anggotaPenguji2 ? $s->anggotaPenguji2->nama_dosen : '-',
                        'jam'            => $s->jam ?? '-',
                        'ruang'          => $s->ruang ? $s->ruang->kode_ruangan : 'TBA',
                        'role'           => implode(', ', $roles)
                    ]
                ];
            });
        }

        return view('dosen.kalender', compact('schedules', 'dosen', 'calendarEvents'));
    }

    /**
     * Dosen Profile Detail
     */
    public function profil(): View
    {
        $user = Auth::user();
        $dosen = $user->dosen;

        $stats = [
            'total_sempro' => 0,
            'total_skripsi' => 0,
            'total_jurnal' => 0,
        ];

        if ($dosen) {
            $dosenId = $dosen->id;

            $dosenExamsQuery = Sidang::where(function ($q) use ($dosenId) {
                $q->where('dosen_pembimbing_utama_id', $dosenId)
                  ->orWhere('dosen_pembimbing_pendamping_id', $dosenId)
                  ->orWhere('ketua_penguji_id', $dosenId)
                  ->orWhere('anggota_penguji_1_id', $dosenId)
                  ->orWhere('anggota_penguji_2_id', $dosenId);
            });

            $stats['total_sempro'] = (clone $dosenExamsQuery)->where('jenis_tugas_akhir', 'sempro')->count();
            $stats['total_skripsi'] = (clone $dosenExamsQuery)->where('jenis_tugas_akhir', 'skripsi')->count();
            $stats['total_jurnal'] = (clone $dosenExamsQuery)->where('jenis_tugas_akhir', 'jurnal')->count();
        }

        return view('dosen.profil', compact('user', 'dosen', 'stats'));
    }
}

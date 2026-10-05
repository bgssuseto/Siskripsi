<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use App\Models\Dosen;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use App\Models\KesediaanDosen;
use App\Services\KelulusanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            'bimbingan_utama' => 0,
            'bimbingan_pendamping' => 0,
            'bimbingan_total' => 0,
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

            // Scope ke periode aktif saja — tanpa ini, slot dari periode lama yang
            // sudah lewat ikut tampil selamanya di kartu "Saat Ini" pada dashboard.
            $existingKesediaan = KesediaanDosen::with(['wave', 'periode'])
                ->where('dosen_id', $dosen->id)
                ->when($activePeriode, fn ($q) => $q->where('periode_id', $activePeriode->id))
                ->orderBy('tanggal', 'asc')
                ->get();

            // Total Bimbingan Mahasiswa
            $totalBimbingan = Sidang::where(function ($q) use ($dosenId) {
                $q->where('dosen_pembimbing_utama_id', $dosenId)
                  ->orWhere('dosen_pembimbing_pendamping_id', $dosenId);
            })->count();

            // Rincian peran bimbingan: Pembimbing Utama vs Pembimbing Pendamping
            $stats['bimbingan_utama'] = Sidang::where('dosen_pembimbing_utama_id', $dosenId)->count();
            $stats['bimbingan_pendamping'] = Sidang::where('dosen_pembimbing_pendamping_id', $dosenId)->count();
            $stats['bimbingan_total'] = $totalBimbingan;

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
                // Sembunyikan ujian yang sudah lewat tanggalnya agar tidak membingungkan
                // saat gelombang ujian berikutnya dibuka.
                ->where(function ($q) use ($todayStr) {
                    $q->whereNull('tanggal')->orWhereDate('tanggal', '>=', $todayStr);
                })
                ->with(['pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2', 'ruang', 'periode'])
                ->orderBy('tanggal', 'desc')
                ->limit(10)
                ->get();
        } else {
            $existingKesediaan = collect();
        }

        // Seluruh gelombang periode aktif ini — SENGAJA tidak dibatasi ke yang
        // sedang berjalan hari ini, supaya dosen tetap bisa mengisi kesediaan
        // walau jendela pendaftaran gelombangnya sudah lewat/belum dibuka,
        // selama koordinator membuka form ini lewat menu Kesediaan Dosen
        // (lihat storeKesediaan(), yang kini hanya mensyaratkan wave_id-nya
        // milik periode aktif ini, bukan lagi "sedang berjalan"). Gelombang
        // yang rentangnya > 1 bulan disembunyikan dari dropdown ini supaya
        // tidak membingungkan (lihat PendaftaranPeriode::isTooLongForKesediaanPicker()).
        $allWaves = $activePeriode
            ? PendaftaranPeriode::where('periode_id', $activePeriode->id)
                ->orderBy('gelombang')
                ->get()
                ->reject(fn ($w) => $w->isTooLongForKesediaanPicker())
                ->values()
            : collect();

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

        $activePeriode = Periode::where('aktif', true)->first();
        if (!$activePeriode) {
            return redirect()->back()->with('error', 'Tidak ada periode akademik aktif saat ini.');
        }

        // The dashboard hides the "Tambah Slot" button / whole card when any of
        // these are off (show_form_kesediaan, can_fill_kesediaan, lock_form_kesediaan),
        // but that's cosmetic only — re-check server-side too, since a stale tab
        // or a direct POST replay could otherwise still write through a closed/
        // locked form.
        $showFormKesediaan = (bool) $activePeriode->show_form_kesediaan && (bool) $dosen->can_fill_kesediaan;
        if (!$showFormKesediaan || $activePeriode->lock_form_kesediaan) {
            return redirect()->back()->with('error', 'Form kesediaan sedang tidak dapat diisi (ditutup/dikunci oleh koordinator).');
        }

        $request->validate([
            'slots' => 'required|array|min:1',
            'slots.*.tanggal' => 'required|date',
            'slots.*.keterangan' => 'nullable|string',
            // Wajib — kesediaan harus selalu ditautkan ke gelombang + periode
            // semester aktif yang jelas, bukan "kesediaan umum" tanpa konteks.
            'wave_id' => 'required|exists:pendaftaran_periodes,id',
        ]);

        // Pastikan gelombang yang dipilih benar-benar milik periode AKTIF ini —
        // TIDAK lagi disyaratkan "sedang berjalan" hari ini, supaya dosen tetap
        // bisa mengisi kesediaan walau jendela pendaftaran gelombang itu sudah
        // lewat/belum dibuka (admin yang mengatur buka/tutupnya lewat toggle
        // show_form_kesediaan/lock_form_kesediaan di menu Kesediaan Dosen).
        $wave = PendaftaranPeriode::where('id', $request->wave_id)
            ->where('periode_id', $activePeriode->id)
            ->first();

        if (!$wave) {
            return redirect()->back()->with('error', 'Gelombang yang dipilih tidak valid untuk periode aktif ini.');
        }

        foreach ($request->slots as $slot) {
            // firstOrCreate on the natural key so a double-submit (double-click,
            // browser retry) doesn't leave duplicate slots for the same date.
            KesediaanDosen::firstOrCreate(
                [
                    'dosen_id' => $dosen->id,
                    'wave_id' => $wave->id,
                    'tanggal' => $slot['tanggal'],
                ],
                [
                    'periode_id' => $activePeriode->id,
                    'jam_mulai' => '',
                    'jam_selesai' => '',
                    'keterangan' => $slot['keterangan'] ?? null,
                ]
            );
        }

        return redirect()->back()->with('success', 'Form kesediaan menguji berhasil disimpan.');
    }

    /**
     * Delete Dosen Availability Slot
     */
    public function destroyKesediaan($hashId)
    {
        $user = Auth::user();
        $dosen = $user->dosen;

        if (!$dosen) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $activePeriode = Periode::where('aktif', true)->first();
        if ($activePeriode && $activePeriode->lock_form_kesediaan) {
            return redirect()->back()->with('error', 'Data kesediaan sedang dikunci oleh koordinator dan tidak dapat dihapus.');
        }

        $id = KesediaanDosen::decodeHashId($hashId);

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

            // Sembunyikan ujian yang sudah selesai (tanggal < hari ini) secara default,
            // kecuali dosen memilih filter status secara eksplisit atau menampilkan riwayat.
            $showRiwayat = $request->boolean('riwayat') || $request->get('status') === 'sudah';
            if (!$request->filled('status') && !$showRiwayat) {
                $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
                $query->where(function ($q) use ($today) {
                    $q->whereNull('tanggal')->orWhereDate('tanggal', '>=', $today);
                });
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

            // Sembunyikan ujian yang sudah selesai (tanggal < hari ini) secara default,
            // kecuali dosen memilih filter status secara eksplisit atau menampilkan riwayat.
            $showRiwayat = $request->boolean('riwayat') || $request->get('status') === 'sudah';
            if (!$request->filled('status') && !$showRiwayat) {
                $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
                $query->where(function ($q) use ($today) {
                    $q->whereNull('tanggal')->orWhereDate('tanggal', '>=', $today);
                });
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

            $todayStr = now()->timezone('Asia/Jakarta')->format('Y-m-d');

            if ($request->filled('status')) {
                $status = $request->status;
                if ($status === 'terjadwal') {
                    $query->whereDate('tanggal', '>', $todayStr);
                } elseif ($status === 'proses') {
                    $query->whereDate('tanggal', '=', $todayStr);
                } elseif ($status === 'sudah') {
                    $query->whereDate('tanggal', '<', $todayStr);
                }
            } elseif (!$request->filled('tanggal')) {
                // Sembunyikan ujian yang sudah lewat tanggalnya secara default agar fokus
                // dosen tidak terpecah, kecuali dia sengaja memilih status/tanggal spesifik.
                $query->where(function ($q) use ($todayStr) {
                    $q->whereNull('tanggal')->orWhereDate('tanggal', '>=', $todayStr);
                });
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
                 // Untuk Sempro, ketua_penguji_id/anggota_penguji_1_id/2_id SELALU
                 // mencerminkan pembimbing utama/pendamping (lihat
                 // Sidang::syncPengujiFromPembimbing()) — sudah tercakup 2 baris di
                 // atas, jadi dilewati di sini supaya tidak dobel ("Pembimbing Utama"
                 // + "Ketua Penguji" untuk peran yang sama).
                 if (!$isSempro && $s->ketua_penguji_id === $dosen->id) $roles[] = 'Ketua Penguji';
                 if (!$isSempro && $s->anggota_penguji_1_id === $dosen->id) $roles[] = 'Anggota Penguji 1';
                 if (!$isSempro && $s->anggota_penguji_2_id === $dosen->id) $roles[] = 'Anggota Penguji 2';

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

    /**
     * Query dasar Riwayat Menguji: seluruh Sidang (sempro & skripsi, lintas
     * periode/tahun ajaran — TIDAK dibatasi periode aktif saja seperti
     * sempro()/skripsi(), supaya riwayat karier menguji dosen tetap utuh) di
     * mana dosen ini terlibat sebagai pembimbing/penguji, dan tanggal
     * ujiannya sudah lewat (bukan cuma terjadwal). Dipakai bersama oleh
     * riwayat() (tampilan) dan riwayatExport() (unduh Excel) agar filternya
     * selalu identik.
     */
    private function riwayatQuery(Request $request, int $dosenId): Builder
    {
        $todayStr = now()->timezone('Asia/Jakarta')->format('Y-m-d');

        $query = Sidang::with(['pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2', 'ruang', 'periode'])
            ->whereNotNull('tanggal')
            ->whereDate('tanggal', '<=', $todayStr)
            ->where(function ($q) use ($dosenId) {
                $q->where('dosen_pembimbing_utama_id', $dosenId)
                  ->orWhere('dosen_pembimbing_pendamping_id', $dosenId)
                  ->orWhere('ketua_penguji_id', $dosenId)
                  ->orWhere('anggota_penguji_1_id', $dosenId)
                  ->orWhere('anggota_penguji_2_id', $dosenId);
            });

        if ($request->filled('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }

        if ($request->filled('jenis')) {
            if ($request->jenis === 'sempro') {
                $query->where('jenis_tugas_akhir', 'sempro');
            } elseif ($request->jenis === 'skripsi') {
                $query->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET);
            }
        }

        if ($request->filled('gelombang')) {
            $query->where('gelombang', $request->gelombang);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_mahasiswa', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('judul_skripsi', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('tanggal')->orderByDesc('jam');
    }

    /**
     * Riwayat Menguji — rekap seluruh sidang/sempro yang SUDAH benar-benar
     * berlangsung (tanggal & jam ujian sudah lewat) untuk dosen ini, bisa
     * disaring per tahun ajaran (periode) & gelombang, lengkap dengan hasil
     * ujian (lulus/tidak lulus) yang selama ini tidak terlihat sama sekali
     * di halaman jadwal dosen.
     */
    public function riwayat(Request $request): View
    {
        $user = Auth::user();
        $dosen = $user->dosen;

        $periodes = Periode::orderByDesc('id')->get();
        $gelombangOptions = collect();
        $schedules = collect();
        $rekap = [
            'total' => 0,
            'sempro' => 0,
            'skripsi' => 0,
            'lulus' => 0,
            'tidak_lulus' => 0,
            'belum' => 0,
        ];

        if ($dosen) {
            // Data lama yang tanggal ujiannya sudah lewat tapi belum pernah
            // dibuka lagi oleh admin/koordinator (satu-satunya tempat yang
            // memanggil ini sebelumnya) bisa saja masih status_ujian kosong —
            // sinkronkan dulu supaya riwayat dosen selalu akurat.
            KelulusanService::autoFinalizePastExams();

            $schedules = $this->riwayatQuery($request, $dosen->id)
                ->get()
                ->filter(fn (Sidang $s) => $s->isExamEnded())
                ->values();

            $rekap['total'] = $schedules->count();
            $rekap['sempro'] = $schedules->where('jenis_tugas_akhir', 'sempro')->count();
            $rekap['skripsi'] = $rekap['total'] - $rekap['sempro'];
            $rekap['lulus'] = $schedules->where('status_ujian', 'lulus')->count();
            $rekap['tidak_lulus'] = $schedules->where('status_ujian', 'tidak_lulus')->count();
            $rekap['belum'] = $rekap['total'] - $rekap['lulus'] - $rekap['tidak_lulus'];

            $gelombangOptions = PendaftaranPeriode::when(
                    $request->filled('periode_id'),
                    fn ($q) => $q->where('periode_id', $request->periode_id)
                )
                ->orderBy('gelombang')
                ->pluck('gelombang')
                ->unique()
                ->values();
        }

        return view('dosen.riwayat', compact('schedules', 'dosen', 'periodes', 'gelombangOptions', 'rekap'));
    }

    /**
     * Unduh rekap Riwayat Menguji (Excel) — filter sama persis dengan riwayat().
     */
    public function riwayatExport(Request $request)
    {
        $user = Auth::user();
        $dosen = $user->dosen;

        if (!$dosen) {
            return redirect()->back()->with('error', 'Akun Anda belum terhubung dengan data Dosen.');
        }

        KelulusanService::autoFinalizePastExams();

        $schedules = $this->riwayatQuery($request, $dosen->id)
            ->get()
            ->filter(fn (Sidang $s) => $s->isExamEnded())
            ->values();

        if ($schedules->isEmpty()) {
            return redirect()->back()->with('warning', 'Tidak ada data riwayat menguji untuk diexport.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Riwayat Menguji');

        $headers = ['NO', 'NIM', 'NAMA MAHASISWA', 'JUDUL', 'JENIS', 'PERAN', 'TAHUN AJARAN', 'GELOMBANG', 'TANGGAL', 'JAM', 'RUANGAN', 'HASIL UJIAN'];
        foreach ($headers as $colIdx => $headerText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $headerText);
        }
        $headerRange = 'A1:L1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach ($schedules as $i => $s) {
            $isSempro = $s->jenis_tugas_akhir === 'sempro';

            $role = 'Penguji';
            if ($s->dosen_pembimbing_utama_id === $dosen->id) {
                $role = 'Pembimbing Utama';
            } elseif ($s->dosen_pembimbing_pendamping_id === $dosen->id) {
                $role = 'Pembimbing Pendamping';
            } elseif ($s->ketua_penguji_id === $dosen->id) {
                $role = 'Ketua Penguji';
            } elseif ($s->anggota_penguji_1_id === $dosen->id) {
                $role = 'Anggota Penguji 1';
            } elseif ($s->anggota_penguji_2_id === $dosen->id) {
                $role = 'Anggota Penguji 2';
            }

            $hasil = match ($s->status_ujian) {
                'lulus' => 'Lulus',
                'tidak_lulus' => 'Tidak Lulus / Remidi',
                default => 'Belum Diisi',
            };

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $s->nim);
            $sheet->setCellValue("C{$row}", $s->nama_mahasiswa);
            $sheet->setCellValue("D{$row}", $s->judul_skripsi);
            $sheet->setCellValue("E{$row}", $isSempro ? 'Seminar Proposal' : 'Sidang Skripsi');
            $sheet->setCellValue("F{$row}", $role);
            $sheet->setCellValue("G{$row}", $s->periode->nama_periode ?? '-');
            $sheet->setCellValue("H{$row}", $s->gelombang ?? '-');
            $sheet->setCellValue("I{$row}", $s->tanggal ? $s->tanggal->locale('id')->isoFormat('dddd, D MMMM Y') : '-');
            $sheet->setCellValue("J{$row}", $s->jam ?? '-');
            $sheet->setCellValue("K{$row}", $s->ruang->kode_ruangan ?? '-');
            $sheet->setCellValue("L{$row}", $hasil);

            $row++;
        }

        foreach (range(1, 12) as $colIdx) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        $cleanName = preg_replace('/[^\w\s,.]/', '', $dosen->nama_dosen);
        $fileName = 'Riwayat_Menguji_' . str_replace(' ', '_', trim($cleanName)) . '_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use App\Models\Periode;
use App\Models\Dosen;
use App\Models\PendaftaranPeriode;
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

        $activePeriode = Periode::where('aktif', true)->first();
        if (!$activePeriode) {
            return collect();
        }

        $query = Sidang::with([
            'pembimbingUtama',
            'pembimbingPendamping',
            'ketuaPenguji',
            'anggotaPenguji1',
            'anggotaPenguji2',
            'ruang',
            'periode'
        ])->where('periode_id', $activePeriode->id);

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
        $dosens = Dosen::orderBy('nama_dosen')->get();

        // All student's registrations
        $sidangs = $this->getStudentSidangs($user);

        return view('mahasiswa.sempro', compact(
            'user', 'sidangs', 'periodes', 'activePeriode', 'dosens'
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
        $dosens = Dosen::orderBy('nama_dosen')->get();

        // Filter student's sidang skripsi only
        $sidangs = $this->getStudentSidangs($user)->where('jenis_tugas_akhir', 'sidang');

        return view('mahasiswa.skripsi', compact(
            'user', 'sidangs', 'periodes', 'activePeriode', 'dosens'
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

    public function jadwalSemproIndex(): View
    {
        $user          = Auth::user();
        $activePeriode = Periode::where('aktif', true)->first();
        $sidangs       = $this->getStudentSidangs($user)->where('jenis_tugas_akhir', 'sempro')->sortByDesc('tanggal');

        return view('mahasiswa.jadwal', compact('user', 'sidangs', 'activePeriode'));
    }

    public function jadwalSkripsiIndex(): View
    {
        $user          = Auth::user();
        $activePeriode = Periode::where('aktif', true)->first();
        $sidangs       = $this->getStudentSidangs($user)->whereIn('jenis_tugas_akhir', ['sidang', 'skripsi', 'jurnal'])->sortByDesc('tanggal');

        return view('mahasiswa.jadwal', compact('user', 'sidangs', 'activePeriode'));
    }

    /**
     * Store registration from student
     */
    public function storeRegistration(Request $request)
    {
        $user = Auth::user();
        
        $activePeriode = Periode::where('aktif', true)->first();
        if (!$activePeriode) {
            return back()->with('error', 'Tidak ada periode akademik yang aktif saat ini.');
        }

        $jenisTugasAkhir = $request->input('jenis_tugas_akhir') === 'skripsi' ? 'sidang' : $request->input('jenis_tugas_akhir');

        // Check if student already has a registration record in this active period
        $existing = Sidang::where('nim', $request->input('nim'))
            ->where('jenis_tugas_akhir', $jenisTugasAkhir)
            ->where('periode_id', $activePeriode->id)
            ->first();

        $isRevision = $existing && $existing->verifikasi_status === 'ditolak';

        // Check if student already registered for this in this active period and was NOT rejected
        if ($existing && $existing->verifikasi_status !== 'ditolak') {
            if ($existing->verifikasi_status === 'disetujui') {
                $msg = 'Pendaftaran Anda telah terverifikasi dan disetujui koordinator.';
            } else {
                $msg = 'Pendaftaran Anda sudah dikirim dan sedang menunggu verifikasi.';
            }
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        // Validate request
        $validated = $request->validate([
            'nim'                            => ['required', 'string', 'max:30'],
            'jenis_tugas_akhir'              => ['required', 'string', 'in:sempro,skripsi'],
            'judul_skripsi'                  => ['required', 'string'],
            'dosen_pembimbing_utama_id'      => ['required', 'exists:dosens,id'],
            'dosen_pembimbing_pendamping_id' => ['nullable', 'exists:dosens,id'],
            'bukti_pembayaran'               => [$isRevision ? 'nullable' : 'required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:2048'],
        ], [
            'nim.required'                       => 'NIM wajib diisi.',
            'judul_skripsi.required'             => 'Judul tugas akhir wajib diisi.',
            'dosen_pembimbing_utama_id.required' => 'Dosen Pembimbing Utama wajib dipilih.',
            'bukti_pembayaran.required'          => 'Bukti pembayaran wajib diunggah.',
            'bukti_pembayaran.mimes'             => 'Format bukti pembayaran harus PDF, PNG, JPG, atau JPEG.',
            'bukti_pembayaran.max'               => 'Ukuran bukti pembayaran maksimal 2 MB.',
        ]);

        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        
        // Check if current date falls in any defined registration wave/period
        $wave = PendaftaranPeriode::where('periode_id', $activePeriode->id)
            ->where('jenis', $validated['jenis_tugas_akhir'])
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->first();

        if (!$wave) {
            $msg = 'Pendaftaran gagal! Saat ini pendaftaran untuk ' . ($validated['jenis_tugas_akhir'] === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi') . ' sedang ditutup.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        // Handle File Upload
        $filePath = $existing ? $existing->bukti_pembayaran : null;
        if ($request->hasFile('bukti_pembayaran')) {
            // Delete old file if exists
            if ($existing && $existing->bukti_pembayaran) {
                $oldPath = public_path($existing->bukti_pembayaran);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Ensure directory exists
            $uploadDir = public_path('uploads/bukti_pembayaran');
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            // Save new file
            $file = $request->file('bukti_pembayaran');
            $fileName = time() . '_' . $validated['nim'] . '_' . $validated['jenis_tugas_akhir'] . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $fileName);
            $filePath = 'uploads/bukti_pembayaran/' . $fileName;
        }

        if ($isRevision) {
            // Overwrite existing record (revise)
            $existing->update([
                'nim'                            => $validated['nim'],
                'judul_skripsi'                  => $validated['judul_skripsi'],
                'dosen_pembimbing_utama_id'      => $validated['dosen_pembimbing_utama_id'],
                'dosen_pembimbing_pendamping_id' => $validated['dosen_pembimbing_pendamping_id'] ?? null,
                'bukti_pembayaran'               => $filePath,
                'verifikasi_status'              => 'menunggu',
                'verifikasi_komentar'            => null,
                'verifikasi_tanggal'             => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ]);
            $message = 'Pendaftaran berhasil direvisi dan dikirim kembali!';
        } else {
            // Create new registration
            Sidang::create([
                'nim'                            => $validated['nim'],
                'nama_mahasiswa'                 => $user->name,
                'judul_skripsi'                  => $validated['judul_skripsi'],
                'dosen_pembimbing_utama_id'      => $validated['dosen_pembimbing_utama_id'],
                'dosen_pembimbing_pendamping_id' => $validated['dosen_pembimbing_pendamping_id'] ?? null,
                'jenis_tugas_akhir'              => $validated['jenis_tugas_akhir'] === 'skripsi' ? 'sidang' : $validated['jenis_tugas_akhir'],
                'periode_id'                     => $activePeriode->id,
                'tanggal_pendaftaran'            => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'ketua_penguji_id'               => null,
                'anggota_penguji_1_id'           => null,
                'anggota_penguji_2_id'           => null,
                'verifikasi_status'              => 'menunggu',
                'bukti_pembayaran'               => $filePath,
            ]);
            $message = 'Pendaftaran ' . ($validated['jenis_tugas_akhir'] === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi') . ' berhasil dikirim!';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => $message,
                'revision' => $isRevision,
            ]);
        }
        return back()->with('success', $message);
    }

    /**
     * Update payment receipt only
     */
    public function updateBukti(Request $request, Sidang $sidang)
    {
        $user = Auth::user();
        
        // Ensure this belongs to the logged-in student
        if ($sidang->nim !== $user->nim && $sidang->nama_mahasiswa !== $user->name) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'bukti_pembayaran' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:2048'],
        ], [
            'bukti_pembayaran.required' => 'Bukti pembayaran wajib diunggah.',
            'bukti_pembayaran.mimes'    => 'Format bukti pembayaran harus PDF, PNG, JPG, atau JPEG.',
            'bukti_pembayaran.max'      => 'Ukuran bukti pembayaran maksimal 2 MB.',
        ]);

        // Delete old file
        if ($sidang->bukti_pembayaran) {
            $oldPath = public_path($sidang->bukti_pembayaran);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Ensure directory exists
        $uploadDir = public_path('uploads/bukti_pembayaran');
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        // Store new file
        $file = $request->file('bukti_pembayaran');
        $fileName = time() . '_' . $sidang->nim . '_revisi_bukti.' . $file->getClientOriginalExtension();
        $file->move($uploadDir, $fileName);
        
        $sidang->update([
            'bukti_pembayaran' => 'uploads/bukti_pembayaran/' . $fileName,
            'verifikasi_status' => 'menunggu',
            'verifikasi_komentar' => null,
            'verifikasi_tanggal' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bukti pembayaran berhasil diperbarui!',
            ]);
        }
        return back()->with('success', 'Bukti pembayaran berhasil diperbarui!');
    }
}

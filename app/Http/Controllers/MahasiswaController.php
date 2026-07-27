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

        $allStudentSidangs = $this->getStudentSidangs($user);
        // Filter student's sidang skripsi only
        $sidangs = $allStudentSidangs->where('jenis_tugas_akhir', 'sidang');

        // Check if student has registered for Sempro
        $semproRecord = $allStudentSidangs->where('jenis_tugas_akhir', 'sempro')->first();
        $hasSempro = $semproRecord ? true : false;
        $isSemproApproved = $semproRecord && $semproRecord->verifikasi_status === 'disetujui';

        return view('mahasiswa.skripsi', compact(
            'user', 'sidangs', 'periodes', 'activePeriode', 'dosens', 'hasSempro', 'isSemproApproved', 'semproRecord'
        ));
    }

    /**
     * Student Jadwal Sidang index - Strictly filters schedule for THIS student only
     */
    public function jadwalIndex()
    {
        return redirect()->route('mahasiswa.jadwal.sempro');
    }

    public function jadwalSemproIndex(): View
    {
        $user          = Auth::user();
        $activePeriode = Periode::where('aktif', true)->first();
        $sidangs       = $this->getStudentSidangs($user)->where('jenis_tugas_akhir', 'sempro')->sortByDesc('tanggal');
        $type          = 'sempro';

        return view('mahasiswa.jadwal', compact('user', 'sidangs', 'activePeriode', 'type'));
    }

    public function jadwalSkripsiIndex(): View
    {
        $user           = Auth::user();
        $activePeriode  = Periode::where('aktif', true)->first();
        $studentSidangs = $this->getStudentSidangs($user);
        $sidangs        = $studentSidangs->whereIn('jenis_tugas_akhir', ['sidang', 'skripsi', 'jurnal'])->sortByDesc('tanggal');
        $semproHistory  = $studentSidangs->where('jenis_tugas_akhir', 'sempro')->sortByDesc('tanggal');
        $type           = 'skripsi';

        return view('mahasiswa.jadwal', compact('user', 'sidangs', 'semproHistory', 'activePeriode', 'type'));
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

        // Check if registering for skripsi, ensure student has registered sempro first
        if ($request->input('jenis_tugas_akhir') === 'skripsi') {
            $hasSempro = Sidang::where('nim', $request->input('nim'))
                ->where('jenis_tugas_akhir', 'sempro')
                ->exists();

            if (!$hasSempro) {
                $msg = 'Pendaftaran Skripsi gagal! Anda belum melakukan pendaftaran Seminar Proposal (Sempro). Silakan daftar Sempro terlebih dahulu.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }
        }

        // Validate request
        $validated = $request->validate([
            'nim'                            => ['required', 'string', 'max:30'],
            'jenis_tugas_akhir'              => ['required', 'string', 'in:sempro,skripsi'],
            'judul_skripsi'                  => ['required', 'string'],
            'dosen_pembimbing_utama_id'      => ['required', 'exists:dosens,id'],
            'dosen_pembimbing_pendamping_id' => ['nullable', 'exists:dosens,id'],
            'no_wa_aktif'                    => ['required', 'string', 'max:20'],
            'jenis_ta_pilihan'               => ['nullable', 'string', 'in:sidang,jurnal'],
            'file_persyaratan'               => [$isRevision ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:4096'],
        ], [
            'nim.required'                       => 'NIM wajib diisi.',
            'judul_skripsi.required'             => 'Judul tugas akhir wajib diisi.',
            'dosen_pembimbing_utama_id.required' => 'Dosen Pembimbing Utama wajib dipilih.',
            'no_wa_aktif.required'               => 'Nomor WhatsApp aktif wajib diisi.',
            'file_persyaratan.required'          => 'File persyaratan wajib diunggah.',
            'file_persyaratan.mimes'             => 'File persyaratan harus berformat PDF.',
            'file_persyaratan.max'               => 'Ukuran file persyaratan maksimal 4 MB.',
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

        // Handle File Upload (file_persyaratan - PDF only, max 4MB, overwrite old)
        $filePath = $existing ? $existing->file_persyaratan : null;
        if ($request->hasFile('file_persyaratan')) {
            // Delete old file if exists
            if ($existing && $existing->file_persyaratan) {
                $oldPath = public_path($existing->file_persyaratan);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Ensure directory exists
            $uploadDir = public_path('uploads/persyaratan');
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            // Save new file
            $file = $request->file('file_persyaratan');
            $fileName = time() . '_' . $validated['nim'] . '_' . $validated['jenis_tugas_akhir'] . '.pdf';
            $file->move($uploadDir, $fileName);
            $filePath = 'uploads/persyaratan/' . $fileName;
        }

        // Determine actual jenis_tugas_akhir for skripsi (sidang/jurnal)
        $actualJenis = $validated['jenis_tugas_akhir'];
        if ($actualJenis === 'skripsi') {
            $actualJenis = $request->input('jenis_ta_pilihan', 'sidang') ?: 'sidang';
        }

        if ($isRevision) {
            // Overwrite existing record (revise)
            $existing->update([
                'nim'                            => $validated['nim'],
                'judul_skripsi'                  => $validated['judul_skripsi'],
                'dosen_pembimbing_utama_id'      => $validated['dosen_pembimbing_utama_id'],
                'dosen_pembimbing_pendamping_id' => $validated['dosen_pembimbing_pendamping_id'] ?? null,
                'no_wa_aktif'                    => $validated['no_wa_aktif'],
                'file_persyaratan'               => $filePath,
                'jenis_tugas_akhir'              => $actualJenis,
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
                'jenis_tugas_akhir'              => $actualJenis,
                'periode_id'                     => $activePeriode->id,
                'tanggal_pendaftaran'            => now()->timezone('Asia/Jakarta')->format('Y-m-d'),
                'no_wa_aktif'                    => $validated['no_wa_aktif'],
                'ketua_penguji_id'               => null,
                'anggota_penguji_1_id'           => null,
                'anggota_penguji_2_id'           => null,
                'verifikasi_status'              => 'menunggu',
                'file_persyaratan'               => $filePath,
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
            'file_persyaratan' => ['required', 'file', 'mimes:pdf', 'max:4096'],
        ], [
            'file_persyaratan.required' => 'File persyaratan wajib diunggah.',
            'file_persyaratan.mimes'    => 'File persyaratan harus berformat PDF.',
            'file_persyaratan.max'      => 'Ukuran file persyaratan maksimal 4 MB.',
        ]);

        // Delete old file
        if ($sidang->file_persyaratan) {
            $oldPath = public_path($sidang->file_persyaratan);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Ensure directory exists
        $uploadDir = public_path('uploads/persyaratan');
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        // Store new file
        $file = $request->file('file_persyaratan');
        $fileName = time() . '_' . $sidang->nim . '_revisi_persyaratan.pdf';
        $file->move($uploadDir, $fileName);
        
        $sidang->update([
            'file_persyaratan'    => 'uploads/persyaratan/' . $fileName,
            'verifikasi_status'   => 'menunggu',
            'verifikasi_komentar' => null,
            'verifikasi_tanggal'  => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'File persyaratan berhasil diperbarui!',
            ]);
        }
        return back()->with('success', 'File persyaratan berhasil diperbarui!');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use App\Models\Periode;
use App\Models\Dosen;
use App\Models\PendaftaranPeriode;
use App\Services\KelulusanService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

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

        if ($nim) {
            // NIM is the reliable identifier — never widen this with a fuzzy name
            // match, which could pull in another student's records if names overlap.
            return $query->where('nim', $nim)->orderByDesc('id')->get();
        } elseif ($name) {
            // No NIM on file yet: fall back to an exact (not substring) name match.
            return $query->where('nama_mahasiswa', $name)->orderByDesc('id')->get();
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

        // Stats. Student self-registration stores the thesis-defense track as
        // 'sidang' (see storeRegistration()), but koordinator-created/edited
        // records (SkripsiController) use the literal 'skripsi' for the same
        // track — both must match here or an admin-entered record silently
        // disappears from this card.
        $sidangSkripsi = $sidangs->whereIn('jenis_tugas_akhir', ['sidang', 'skripsi'])->first();
        $sidangJurnal  = $sidangs->where('jenis_tugas_akhir', 'jurnal')->first();

        // Countdown gelombang pendaftaran (sempro & skripsi)
        $today = now()->timezone('Asia/Jakarta')->startOfDay();
        $registrationWaves = collect();

        if ($activePeriode) {
            $waves = \App\Models\PendaftaranPeriode::where('periode_id', $activePeriode->id)
                ->whereIn('jenis', ['sempro', 'skripsi'])
                ->orderBy('tanggal_mulai')
                ->get();

            foreach ($waves as $wave) {
                $start = $wave->tanggal_mulai->startOfDay();
                $end   = $wave->tanggal_selesai->endOfDay();

                if ($today->gt($end)) continue; // sudah lewat

                $daysUntilOpen  = $today->lt($start) ? (int) $today->diffInDays($start) : 0;
                $daysUntilClose = (int) $today->diffInDays($end->copy()->startOfDay());
                $isOpen         = $today->gte($start) && $today->lte($end);
                $isComingSoon   = !$isOpen && $daysUntilOpen <= 7;

                if ($isOpen || $isComingSoon) {
                    $registrationWaves->push([
                        'jenis'          => $wave->jenis,
                        'gelombang'      => $wave->gelombang,
                        'tanggal_mulai'  => $wave->tanggal_mulai,
                        'tanggal_selesai'=> $wave->tanggal_selesai,
                        'is_open'        => $isOpen,
                        'is_coming_soon' => $isComingSoon,
                        'days_until_open'  => $daysUntilOpen,
                        'days_until_close' => $daysUntilClose,
                        'is_closing_soon'  => $isOpen && $daysUntilClose <= 3,
                    ]);
                }
            }
        }

        return view('mahasiswa.dashboard', compact(
            'user', 'sidangs', 'sidangSkripsi', 'sidangJurnal', 'activePeriode', 'registrationWaves'
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
        // Exclude dosen yang akunnya super_admin
        $dosens = Dosen::orderBy('nama_dosen')
            ->whereDoesntHave('user', fn ($q) => $q->where('role', \App\Models\User::ROLE_SUPER_ADMIN))
            ->get();

        // All student's registrations
        $sidangs = $this->getStudentSidangs($user);

        $needsCoordinator = $user->nim
            ? KelulusanService::needsCoordinatorForRemidi($user->nim, ['sempro'], $activePeriode?->id)
            : false;

        return view('mahasiswa.sempro', compact(
            'user', 'sidangs', 'periodes', 'activePeriode', 'dosens', 'needsCoordinator'
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
        // Exclude dosen yang akunnya super_admin
        $dosens = Dosen::orderBy('nama_dosen')
            ->whereDoesntHave('user', fn ($q) => $q->where('role', \App\Models\User::ROLE_SUPER_ADMIN))
            ->get();

        $allStudentSidangs = $this->getStudentSidangs($user);
        // Filter student's skripsi-track records only (sidang reguler or jurnal)
        $sidangs = $allStudentSidangs->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET);

        // Check if student has registered for Sempro
        $semproRecord = $allStudentSidangs->where('jenis_tugas_akhir', 'sempro')->first();
        $hasSempro = $semproRecord ? true : false;
        $isSemproApproved = $semproRecord && $semproRecord->verifikasi_status === 'disetujui';

        $needsCoordinator = $user->nim
            ? KelulusanService::needsCoordinatorForRemidi($user->nim, Sidang::SKRIPSI_BUCKET, $activePeriode?->id)
            : false;

        return view('mahasiswa.skripsi', compact(
            'user', 'sidangs', 'periodes', 'activePeriode', 'dosens', 'hasSempro', 'isSemproApproved', 'semproRecord', 'needsCoordinator'
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

        if ($user->status_kelulusan === 'lulus') {
            $msg = 'Anda sudah dinyatakan LULUS dan tidak dapat mendaftar kembali.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $activePeriode = Periode::where('aktif', true)->first();
        if (!$activePeriode) {
            return back()->with('error', 'Tidak ada periode akademik yang aktif saat ini.');
        }

        $jenisBucket = $request->input('jenis_tugas_akhir') === 'sempro' ? ['sempro'] : Sidang::SKRIPSI_BUCKET;
        if ($user->nim && KelulusanService::needsCoordinatorForRemidi($user->nim, $jenisBucket, $activePeriode->id)) {
            $msg = 'Pendaftaran Anda pada periode sebelumnya belum lulus/remidi. Silakan hubungi Koordinator Skripsi untuk didaftarkan kembali pada periode ini.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $jenisTugasAkhir = $request->input('jenis_tugas_akhir') === 'skripsi' ? 'sidang' : $request->input('jenis_tugas_akhir');

        // Check if student already has a registration record in this active period.
        // Always key this off the authenticated user's own NIM — never the raw request
        // input, which is only readonly client-side and could be tampered with to target
        // another student's record.
        $existing = Sidang::where('nim', $user->nim)
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


        // Validate request. kategori_jurnal/link_jurnal only make sense for the
        // SKRIPSI form's jurnal track — jenis_ta_pilihan='jurnal' is also
        // submitted by the Sempro form (declaring an intended future track),
        // so this must check jenis_tugas_akhir==='skripsi' too, or a Sempro
        // registrant declaring "jurnal" would wrongly be forced to fill in a
        // kategori_jurnal field that doesn't even exist on that form.
        $isJurnal = $request->input('jenis_tugas_akhir') === 'skripsi'
            && $request->input('jenis_ta_pilihan') === 'jurnal';
        $validated = $request->validate([
            'nim'                            => ['required', 'string', 'max:30'],
            'jenis_tugas_akhir'              => ['required', 'string', 'in:sempro,skripsi'],
            'judul_skripsi'                  => ['required', 'string'],
            'dosen_pembimbing_utama_id'      => ['required', 'exists:dosens,id'],
            'dosen_pembimbing_pendamping_id' => ['nullable', 'exists:dosens,id'],
            'no_wa_aktif'                    => ['required', 'string', 'max:20'],
            'jenis_ta_pilihan'               => ['nullable', 'string', 'in:sidang,jurnal'],
            'kategori_jurnal'                => $isJurnal ? ['required', 'string', Rule::in(Sidang::KATEGORI_JURNAL_OPTIONS)] : ['nullable', 'string'],
            'link_jurnal'                    => ['nullable', 'url', 'max:500'],
            'file_persyaratan'               => [$isRevision ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:4096'],
        ], [
            'nim.required'                       => 'NIM wajib diisi.',
            'judul_skripsi.required'             => 'Judul tugas akhir wajib diisi.',
            'dosen_pembimbing_utama_id.required' => 'Dosen Pembimbing Utama wajib dipilih.',
            'no_wa_aktif.required'               => 'Nomor WhatsApp aktif wajib diisi.',
            'kategori_jurnal.required'           => 'Kategori jurnal wajib dipilih untuk jalur Jurnal / Artikel.',
            'link_jurnal.url'                    => 'Link jurnal harus berupa URL yang valid.',
            'file_persyaratan.required'          => 'File persyaratan wajib diunggah.',
            'file_persyaratan.mimes'             => 'File persyaratan harus berformat PDF.',
            'file_persyaratan.max'               => 'Ukuran file persyaratan maksimal 4 MB.',
        ]);

        // Guard against submitting another student's NIM (the form field is readonly
        // client-side only) — never trust the client for whose record this is.
        if ($validated['nim'] !== $user->nim) {
            $msg = 'NIM tidak sesuai dengan akun Anda.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

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

        // Jalur (sidang/jurnal) yang dipilih mahasiswa saat mendaftar Sempro —
        // untuk skripsi, jalur otomatis mengikuti jenis_tugas_akhir via Sidang::booted().
        $jalurTa = $validated['jenis_tugas_akhir'] === 'sempro' ? $request->input('jenis_ta_pilihan') : null;

        // Null out on any submission that isn't jurnal, so revising a rejected
        // jurnal registration back into the sidang track doesn't leave stale
        // kategori/link data behind.
        $kategoriJurnal = $isJurnal ? $validated['kategori_jurnal'] : null;
        $linkJurnal     = $isJurnal ? ($validated['link_jurnal'] ?? null) : null;

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
                'jalur_ta'                       => $jalurTa,
                'kategori_jurnal'                => $kategoriJurnal,
                'link_jurnal'                    => $linkJurnal,
                'verifikasi_status'              => 'menunggu',
                'verifikasi_komentar'            => null,
                'verifikasi_tanggal'             => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ]);
            $message = 'Pendaftaran berhasil direvisi dan dikirim kembali!';
        } else {
            // Create new registration. A unique DB constraint on
            // (nim, jenis_tugas_akhir, periode_id) is the final backstop against
            // a double-click/retry racing past the $existing check above (both
            // requests reading "no existing row" before either one commits) —
            // catch it here and turn it into the same friendly message the
            // $existing check already gives for the non-race case.
            try {
                Sidang::create([
                    'nim'                            => $validated['nim'],
                    'nama_mahasiswa'                 => $user->name,
                    'judul_skripsi'                  => $validated['judul_skripsi'],
                    'dosen_pembimbing_utama_id'      => $validated['dosen_pembimbing_utama_id'],
                    'dosen_pembimbing_pendamping_id' => $validated['dosen_pembimbing_pendamping_id'] ?? null,
                    'jenis_tugas_akhir'              => $actualJenis,
                    'jalur_ta'                       => $jalurTa,
                    'kategori_jurnal'                => $kategoriJurnal,
                    'link_jurnal'                    => $linkJurnal,
                    'periode_id'                     => $activePeriode->id,
                    'tanggal_pendaftaran'            => now()->timezone('Asia/Jakarta')->format('Y-m-d'),
                    'no_wa_aktif'                    => $validated['no_wa_aktif'],
                    'ketua_penguji_id'               => null,
                    'anggota_penguji_1_id'           => null,
                    'anggota_penguji_2_id'           => null,
                    'verifikasi_status'              => 'menunggu',
                    'file_persyaratan'               => $filePath,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ((int) $e->getCode() === 23000) {
                    $msg = 'Pendaftaran Anda sudah tersimpan (kemungkinan terkirim dua kali). Muat ulang halaman untuk melihat status terbaru.';
                    if ($request->expectsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->with('error', $msg);
                }
                throw $e;
            }
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

        // Ensure this belongs to the logged-in student. NIM is the reliable
        // identifier (see getStudentSidangs() above) — only fall back to an
        // exact name match when the user has no NIM on file yet. Matching on
        // EITHER nim OR name (as this used to do) would let a same-named
        // student hijack someone else's registration by nim alone.
        $isOwner = $user->nim
            ? $sidang->nim === $user->nim
            : $sidang->nama_mahasiswa === $user->name;

        if (! $isOwner) {
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

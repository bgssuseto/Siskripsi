<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Sidang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user'      => $user,
            'dosen'     => $user->isDosen() ? $user->dosen : null,
            'dosenStat' => $user->isDosen() && $user->dosen ? $this->statistikDosen($user->dosen->id) : null,
            'tugasAkhir'=> $user->isMahasiswa() && $user->nim ? $this->tugasAkhirMahasiswa($user->nim) : null,
        ]);
    }

    /** Ringkasan keterlibatan dosen (seluruh periode). */
    private function statistikDosen(int $dosenId): array
    {
        $base = Sidang::query();
        $pembimbing = (clone $base)->where(fn ($q) => $q->where('dosen_pembimbing_utama_id', $dosenId)->orWhere('dosen_pembimbing_pendamping_id', $dosenId));
        $penguji = (clone $base)->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)
            ->where(fn ($q) => $q->where('ketua_penguji_id', $dosenId)->orWhere('anggota_penguji_1_id', $dosenId)->orWhere('anggota_penguji_2_id', $dosenId));

        return [
            'bimbingan_sempro'  => (clone $pembimbing)->where('jenis_tugas_akhir', 'sempro')->count(),
            'bimbingan_skripsi' => (clone $pembimbing)->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)->count(),
            'menguji'           => $penguji->count(),
            'akan_datang'       => Sidang::whereDate('tanggal', '>=', now()->timezone('Asia/Jakarta')->toDateString())
                ->where(fn ($q) => $q->where('dosen_pembimbing_utama_id', $dosenId)->orWhere('dosen_pembimbing_pendamping_id', $dosenId)
                    ->orWhere('ketua_penguji_id', $dosenId)->orWhere('anggota_penguji_1_id', $dosenId)->orWhere('anggota_penguji_2_id', $dosenId))
                ->count(),
        ];
    }

    /** Pendaftaran Sempro & Skripsi terbaru milik mahasiswa. */
    private function tugasAkhirMahasiswa(string $nim): array
    {
        $terbaru = fn ($q) => $q->with(['pembimbingUtama', 'pembimbingPendamping', 'periode'])->latest('id')->first();

        return [
            'sempro'  => $terbaru(Sidang::where('nim', $nim)->where('jenis_tugas_akhir', 'sempro')),
            'skripsi' => $terbaru(Sidang::where('nim', $nim)->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)),
        ];
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        // Only update nim for mahasiswa, ignore nim/nidn for super_admin
        if ($user->hasRole('mahasiswa')) {
            $user->fill($request->safe()->only(['name', 'email', 'no_hp']));
            // NIM menjadi kunci kepemilikan data (pendaftaran, berkas, surat) — sekali
            // terisi tidak bisa diubah sendiri oleh mahasiswa, hanya oleh admin.
            if (blank($user->nim) && filled($request->validated('nim'))) {
                $user->nim = trim($request->validated('nim'));
            }
            // Nama mahasiswa diseragamkan HURUF KAPITAL agar konsisten dengan
            // data pendaftaran sidang/sempro.
            if ($user->isDirty('name') && $user->name !== null) {
                $user->name = mb_strtoupper(trim($user->name));
            }
        } else {
            $user->fill($request->safe()->only(['name', 'email', 'no_hp']));
        }

        // Akun dosen terhubung ke Master Dosen (lihat sinkronisasi di bawah).
        $dosen = $user->isDosen() ? $user->dosen : null;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Handle Avatar / Profile Photo Upload
        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil) {
                $oldPath = public_path($user->foto_profil);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $uploadDir = public_path('uploads/avatars');
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $file = $request->file('foto_profil');

            // Never trust the client-supplied filename/extension for the saved
            // filename — an image+PHP polyglot can pass the `image`/`mimes`
            // validation (which sniffs actual file content) while still being
            // uploaded with an attacker-chosen filename like "shell.php". Derive
            // the extension from the sniffed MIME type instead, and whitelist it,
            // so the file can never be saved as anything web-server-executable.
            $safeExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $detectedExtension = strtolower((string) $file->extension());
            if (!in_array($detectedExtension, $safeExtensions, true)) {
                return back()->withErrors(['foto_profil' => 'Format foto profil tidak didukung.']);
            }

            $fileName = time() . '_' . $user->id . '.' . $detectedExtension;
            $file->move($uploadDir, $fileName);
            $user->foto_profil = 'uploads/avatars/' . $fileName;
        }

        $user->save();

        // Dosen: profil = data Master Dosen. Nama, NIDN, email, no. WhatsApp &
        // kepakaran disimpan ke Master Dosen juga (jabatan fungsional & status
        // kepegawaian tetap dikelola admin karena memengaruhi aturan penguji).
        if ($dosen) {
            $dosen->fill(array_filter([
                'nama_dosen' => $user->name,
                'email'      => $user->email,
                'no_wa'      => $user->no_hp,
                'nidn'       => $request->filled('nidn') ? trim($request->input('nidn')) : null,
            ], fn ($v) => $v !== null));
            $dosen->kepakaran = $request->filled('kepakaran') ? trim($request->input('kepakaran')) : $dosen->kepakaran;
            if (!$request->filled('no_hp')) {
                $dosen->no_wa = null;
            }
            $dosen->save();
        }

        return Redirect::route('profile.edit')->with('success', 'Profil Anda berhasil diperbarui!');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password Anda berhasil diperbarui!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

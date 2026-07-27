<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
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
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        // Only update nim for mahasiswa, ignore nim/nidn for super_admin
        if ($user->hasRole('mahasiswa')) {
            $user->fill($request->safe()->only(['name', 'email', 'nim', 'no_hp']));
        } else {
            $user->fill($request->safe()->only(['name', 'email', 'no_hp']));
        }

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
            $fileName = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $fileName);
            $user->foto_profil = 'uploads/avatars/' . $fileName;
        }

        $user->save();

        // Update NIDN for dosen role
        if ($user->hasRole('dosen') && $user->dosen && $request->filled('nidn')) {
            $user->dosen->update(['nidn' => $request->input('nidn')]);
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

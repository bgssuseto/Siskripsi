<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && in_array($request->role, [User::ROLE_SUPER_ADMIN, User::ROLE_KOORDINATOR, User::ROLE_MAHASISWA, User::ROLE_DOSEN], true)) {
            $query->where('role', $request->role);
        }

        $users = $query->with('additionalRoles')->latest()->paginate(5)->withQueryString();

        $stats = [
            'total' => User::count(),
            'super_admin' => User::where('role', User::ROLE_SUPER_ADMIN)->count(),
            'koordinator' => User::where('role', User::ROLE_KOORDINATOR)->count(),
            'mahasiswa' => User::where('role', User::ROLE_MAHASISWA)->count(),
            'dosen' => User::where('role', User::ROLE_DOSEN)->count(),
        ];

        $dosens = \App\Models\Dosen::orderBy('nama_dosen')->get();

        return view('users.index', compact('users', 'stats', 'dosens'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_KOORDINATOR, User::ROLE_MAHASISWA, User::ROLE_DOSEN])],
            'dosen_id' => ['nullable', 'exists:dosens,id'],
            'jadikan_koordinator' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'dosen_id' => $validated['role'] === User::ROLE_DOSEN ? ($validated['dosen_id'] ?? null) : null,
        ]);

        $this->syncKoordinatorRole($user, $request->boolean('jadikan_koordinator'));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User berhasil ditambahkan!',
                'user' => $user
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan!');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_KOORDINATOR, User::ROLE_MAHASISWA, User::ROLE_DOSEN])],
            'password' => ['nullable', 'string', 'min:8'],
            'dosen_id' => ['nullable', 'exists:dosens,id'],
            'jadikan_koordinator' => ['nullable', 'boolean'],
        ]);

        // Prevent self role demotion if last super admin
        if (Auth::id() === $user->id && $user->isSuperAdmin() && $validated['role'] !== User::ROLE_SUPER_ADMIN) {
            $superAdminCount = User::where('role', User::ROLE_SUPER_ADMIN)->count();
            if ($superAdminCount <= 1) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak dapat mengubah role karena Anda adalah satu-satunya Super Admin!'
                    ], 422);
                }
                return redirect()->back()->with('error', 'Tidak dapat mengubah role karena Anda adalah satu-satunya Super Admin!');
            }
        }

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'dosen_id' => $validated['role'] === User::ROLE_DOSEN ? ($validated['dosen_id'] ?? null) : null,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        $this->syncKoordinatorRole($user, $request->boolean('jadikan_koordinator'));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil diperbarui!',
                'user' => $user
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Data user berhasil diperbarui!');
    }

    /**
     * Grant or revoke the additional "koordinator" role on top of a user's primary
     * role (e.g. a dosen who has also been designated koordinator). This does not
     * touch the primary `role` column — it only adds/removes a row in `user_roles`,
     * so the user's default portal/dashboard stays driven by their primary role
     * while gaining koordinator's menu access on top.
     */
    private function syncKoordinatorRole(User $user, bool $jadikanKoordinator): void
    {
        if ($user->role === User::ROLE_KOORDINATOR) {
            // Already koordinator as primary role — nothing extra to grant/revoke.
            return;
        }

        if ($jadikanKoordinator) {
            \App\Models\UserRole::firstOrCreate(['user_id' => $user->id, 'role' => User::ROLE_KOORDINATOR]);
        } else {
            \App\Models\UserRole::where('user_id', $user->id)->where('role', User::ROLE_KOORDINATOR)->delete();
        }
    }

    public function destroy(Request $request, User $user)
    {
        if (Auth::id() === $user->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan!'
                ], 422);
            }
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan!');
        }

        $dosenId = $user->dosen_id;
        if ($user->role === User::ROLE_DOSEN && $dosenId) {
            // Ensure Super Administrator Dosen exists
            $superAdminDosen = \App\Models\Dosen::firstOrCreate(
                ['nidn' => '0000000000'],
                ['nama_dosen' => 'Super Administrator']
            );

            // Link the first super_admin User to this Super Administrator Dosen if they are not already linked
            $superAdminUser = User::where('role', User::ROLE_SUPER_ADMIN)->first();
            if ($superAdminUser && !$superAdminUser->dosen_id) {
                $superAdminUser->update(['dosen_id' => $superAdminDosen->id]);
            }

            // Reassign kesediaan_dosens
            \App\Models\KesediaanDosen::where('dosen_id', $dosenId)
                ->update(['dosen_id' => $superAdminDosen->id]);

            // Reassign sidang roles
            \App\Models\Sidang::where('dosen_pembimbing_utama_id', $dosenId)
                ->update(['dosen_pembimbing_utama_id' => $superAdminDosen->id]);

            \App\Models\Sidang::where('dosen_pembimbing_pendamping_id', $dosenId)
                ->update(['dosen_pembimbing_pendamping_id' => $superAdminDosen->id]);

            \App\Models\Sidang::where('ketua_penguji_id', $dosenId)
                ->update(['ketua_penguji_id' => $superAdminDosen->id]);

            \App\Models\Sidang::where('anggota_penguji_1_id', $dosenId)
                ->update(['anggota_penguji_1_id' => $superAdminDosen->id]);

            \App\Models\Sidang::where('anggota_penguji_2_id', $dosenId)
                ->update(['anggota_penguji_2_id' => $superAdminDosen->id]);
        }

        $user->delete();

        if ($dosenId) {
            \App\Models\Dosen::where('id', $dosenId)->delete();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus!'
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus!');
    }
}

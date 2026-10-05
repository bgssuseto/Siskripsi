<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        $dosens = \App\Models\Dosen::excludingSuperAdminPlaceholder()->orderBy('nama_dosen')->get();

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
            // Nama akun mahasiswa diseragamkan HURUF KAPITAL agar konsisten
            // dengan data pendaftaran sidang/sempro.
            'name' => $validated['role'] === User::ROLE_MAHASISWA ? mb_strtoupper(trim($validated['name'])) : $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'dosen_id' => $validated['role'] === User::ROLE_DOSEN ? ($validated['dosen_id'] ?? null) : null,
        ]);

        $this->syncKoordinatorRole($user, $request->boolean('jadikan_koordinator'));

        ActivityLogger::log('created', $user, "Menambahkan user baru: {$user->name} ({$user->email}) dengan role {$user->role}.");

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

        $before = $user->only(['name', 'email', 'role', 'dosen_id']);

        $userData = [
            // Nama akun mahasiswa diseragamkan HURUF KAPITAL agar konsisten
            // dengan data pendaftaran sidang/sempro.
            'name' => $validated['role'] === User::ROLE_MAHASISWA ? mb_strtoupper(trim($validated['name'])) : $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'dosen_id' => $validated['role'] === User::ROLE_DOSEN ? ($validated['dosen_id'] ?? null) : null,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        $this->syncKoordinatorRole($user, $request->boolean('jadikan_koordinator'));

        $roleChanged = $before['role'] !== $user->role;
        ActivityLogger::log(
            'updated',
            $user,
            $roleChanged
                ? "Mengubah data user {$user->name} ({$user->email}) — role diubah dari {$before['role']} menjadi {$user->role}."
                : "Memperbarui data user: {$user->name} ({$user->email}).",
            ['before' => $before, 'after' => $user->only(['name', 'email', 'role', 'dosen_id'])]
        );

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

        $deletedName = $user->name;
        $deletedEmail = $user->email;
        $deletedRole = $user->role;

        $dosenId = $user->dosen_id;
        if ($user->role === User::ROLE_DOSEN && $dosenId) {
            // Ensure Super Administrator Dosen exists
            $superAdminDosen = \App\Models\Dosen::firstOrCreate(
                ['nidn' => \App\Models\Dosen::NIDN_SUPER_ADMIN_PLACEHOLDER],
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

        ActivityLogger::log('deleted', $user, "Menghapus user: {$deletedName} ({$deletedEmail}), role {$deletedRole}.");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus!'
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus!');
    }

    /**
     * Export the current user list (respecting search/role filter) to Excel —
     * berfungsi sebagai backup ringan yang bisa direstore lewat importExcel().
     * Password TIDAK PERNAH diikutkan (baik plaintext maupun hash) — bukan
     * data yang aman untuk berada di file spreadsheet yang bisa tersimpan di
     * mana saja. Restore akun baru dari file ini akan memakai password
     * default, akun yang sudah ada tidak tersentuh passwordnya sama sekali.
     */
    public function exportExcel(Request $request)
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

        $users = $query->with('dosen')->orderBy('name')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data User');

        $headers = ['NAMA', 'EMAIL', 'ROLE', 'NIM (MAHASISWA)', 'NIDN (DOSEN)', 'NO. HP'];
        foreach ($headers as $colIdx => $headerText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $headerText);
        }
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach ($users as $u) {
            $sheet->setCellValue("A{$row}", $u->name);
            $sheet->setCellValueExplicit("B{$row}", $u->email, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", $u->role);
            $sheet->setCellValueExplicit("D{$row}", $u->nim ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$row}", $u->dosen->nidn ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$row}", $u->no_hp ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }

        foreach (range(1, 6) as $colIdx) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Data_User_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import/restore user accounts from Excel (format identik dengan
     * exportExcel()) — dicocokkan lewat EMAIL:
     * - Email sudah terdaftar: nama/role/nim/tautan dosen diperbarui,
     *   PASSWORD TIDAK PERNAH disentuh sama sekali (akun tetap bisa login
     *   dengan password lama).
     * - Email baru: akun baru dibuat dengan password default "password" —
     *   wajib diganti setelah login pertama.
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required'   => 'File Excel wajib diunggah.',
            'file.extensions' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'        => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $rows = $spreadsheet->getActiveSheet()->toArray();

            if (count($rows) < 2) {
                $message = 'File Excel kosong atau tidak memiliki data.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return back()->with('error', $message);
            }

            // Deteksi kolom header secara fleksibel (urutan kolom boleh berbeda),
            // sama seperti pola import Master Dosen.
            $namaCol = 0;
            $emailCol = 1;
            $roleCol = null;
            $nimCol = null;
            $nidnCol = null;
            $noHpCol = null;
            $headerRowIndex = 0;

            foreach ($rows as $rIdx => $rData) {
                if (!is_array($rData)) {
                    continue;
                }
                $matched = false;
                foreach ($rData as $cIdx => $cellVal) {
                    $valLower = strtolower(trim((string) $cellVal));
                    if (str_contains($valLower, 'email')) {
                        $emailCol = $cIdx;
                        $matched = true;
                    } elseif (str_contains($valLower, 'role')) {
                        $roleCol = $cIdx;
                        $matched = true;
                    } elseif (str_contains($valLower, 'nim')) {
                        $nimCol = $cIdx;
                        $matched = true;
                    } elseif (str_contains($valLower, 'nidn')) {
                        $nidnCol = $cIdx;
                        $matched = true;
                    } elseif (str_contains($valLower, 'hp') || str_contains($valLower, 'wa') || str_contains($valLower, 'telepon')) {
                        $noHpCol = $cIdx;
                        $matched = true;
                    } elseif (str_contains($valLower, 'nama')) {
                        $namaCol = $cIdx;
                        $matched = true;
                    }
                }
                if ($matched) {
                    $headerRowIndex = $rIdx;
                    break;
                }
            }

            $validRoles = [User::ROLE_SUPER_ADMIN, User::ROLE_KOORDINATOR, User::ROLE_MAHASISWA, User::ROLE_DOSEN];

            $created = 0;
            $updated = 0;
            $skipped = [];

            \Illuminate\Support\Facades\DB::beginTransaction();

            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (!is_array($row)) {
                    continue;
                }

                $name = trim((string) ($row[$namaCol] ?? ''));
                $email = trim((string) ($row[$emailCol] ?? ''));

                if ($name === '' && $email === '') {
                    continue;
                }

                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped[] = ($name ?: "baris " . ($i + 1)) . ' (email kosong/tidak valid)';
                    continue;
                }

                $roleRaw = strtolower(trim((string) ($roleCol !== null ? ($row[$roleCol] ?? '') : '')));
                $role = in_array($roleRaw, $validRoles, true) ? $roleRaw : null;

                $nim = $nimCol !== null ? trim((string) ($row[$nimCol] ?? '')) : '';
                $nidn = $nidnCol !== null ? trim((string) ($row[$nidnCol] ?? '')) : '';
                $noHp = $noHpCol !== null ? trim((string) ($row[$noHpCol] ?? '')) : '';

                $dosenId = null;
                if ($nidn !== '') {
                    $dosenId = Dosen::where('nidn', $nidn)->value('id');
                }

                $existing = User::where('email', $email)->first();

                if ($existing) {
                    $effectiveRole = $role ?? $existing->role;
                    $existing->update([
                        'name'     => $effectiveRole === User::ROLE_MAHASISWA && $name !== '' ? mb_strtoupper(trim($name)) : ($name !== '' ? $name : $existing->name),
                        'role'     => $effectiveRole,
                        'nim'      => $effectiveRole === User::ROLE_MAHASISWA ? ($nim ?: $existing->nim) : $existing->nim,
                        'dosen_id' => $effectiveRole === User::ROLE_DOSEN ? ($dosenId ?? $existing->dosen_id) : $existing->dosen_id,
                        'no_hp'    => $noHp !== '' ? $noHp : $existing->no_hp,
                    ]);
                    $updated++;
                    continue;
                }

                if ($name === '') {
                    $skipped[] = "{$email} (nama kosong untuk akun baru)";
                    continue;
                }

                $effectiveRole = $role ?? User::ROLE_MAHASISWA;

                User::create([
                    'name'     => $effectiveRole === User::ROLE_MAHASISWA ? mb_strtoupper(trim($name)) : $name,
                    'email'    => $email,
                    // Password default — akun baru hasil import wajib ganti password
                    // sendiri; tidak pernah menerima/menyimpan password dari file Excel.
                    'password' => Hash::make('password'),
                    'role'     => $effectiveRole,
                    'nim'      => $effectiveRole === User::ROLE_MAHASISWA ? ($nim ?: null) : null,
                    'dosen_id' => $effectiveRole === User::ROLE_DOSEN ? $dosenId : null,
                    'no_hp'    => $noHp ?: null,
                ]);
                $created++;
            }

            \Illuminate\Support\Facades\DB::commit();

            ActivityLogger::log('created', null, "Import data user dari Excel: {$created} akun baru dibuat, {$updated} akun diperbarui." . (!empty($skipped) ? ' Dilewati: ' . count($skipped) . '.' : ''));

            $message = "{$created} akun baru dibuat, {$updated} akun diperbarui.";
            if (!empty($skipped)) {
                $message .= ' Dilewati: ' . implode('; ', array_slice($skipped, 0, 10)) . (count($skipped) > 10 ? ' (dan lainnya)' : '') . '.';
            }
            if ($created > 0) {
                $message .= ' Akun baru menggunakan password default "password" — segera minta pengguna menggantinya.';
            }

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'created' => $created, 'updated' => $updated, 'skipped' => $skipped]);
            }

            return redirect()->route('users.index')->with('success', $message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $message = 'Gagal meng-import data user: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }
    }
}

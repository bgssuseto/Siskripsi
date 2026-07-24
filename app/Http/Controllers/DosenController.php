<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DosenController extends Controller
{
    public function index(Request $request): View
    {
        $query = Dosen::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_dosen', 'like', "%{$search}%")
                  ->orWhere('nidn', 'like', "%{$search}%");
            });
        }

        $dosens = $query->latest()->paginate(5)->withQueryString();

        return view('master.dosen.index', compact('dosens'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nidn' => ['required', 'string', 'max:50', 'unique:dosens,nidn'],
            'nama_dosen' => ['required', 'string', 'max:255'],
        ], [
            'nidn.required' => 'NIDN wajib diisi.',
            'nidn.unique' => 'NIDN sudah terdaftar.',
            'nama_dosen.required' => 'Nama dosen wajib diisi.',
        ]);

        $dosen = Dosen::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data dosen berhasil ditambahkan!',
                'dosen' => $dosen
            ]);
        }

        return redirect()->route('master.dosen.index')->with('success', 'Data dosen berhasil ditambahkan!');
    }

    public function update(Request $request, Dosen $dosen)
    {
        $validated = $request->validate([
            'nidn' => ['required', 'string', 'max:50', 'unique:dosens,nidn,' . $dosen->id],
            'nama_dosen' => ['required', 'string', 'max:255'],
        ], [
            'nidn.required' => 'NIDN wajib diisi.',
            'nidn.unique' => 'NIDN sudah terdaftar.',
            'nama_dosen.required' => 'Nama dosen wajib diisi.',
        ]);

        $dosen->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data dosen berhasil diperbarui!',
                'dosen' => $dosen
            ]);
        }

        return redirect()->route('master.dosen.index')->with('success', 'Data dosen berhasil diperbarui!');
    }

    public function destroy(Request $request, Dosen $dosen)
    {
        // Ensure Super Administrator Dosen exists
        $superAdminDosen = Dosen::firstOrCreate(
            ['nidn' => '0000000000'],
            ['nama_dosen' => 'Super Administrator']
        );

        // Link the first super_admin User to this Super Administrator Dosen if they are not already linked
        $superAdminUser = \App\Models\User::where('role', \App\Models\User::ROLE_SUPER_ADMIN)->first();
        if ($superAdminUser && !$superAdminUser->dosen_id) {
            $superAdminUser->update(['dosen_id' => $superAdminDosen->id]);
        }

        $dosenId = $dosen->id;
        if ($dosenId !== $superAdminDosen->id) {
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

        $dosen->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data dosen berhasil dihapus!'
            ]);
        }

        return redirect()->route('master.dosen.index')->with('success', 'Data dosen berhasil dihapus!');
    }
}

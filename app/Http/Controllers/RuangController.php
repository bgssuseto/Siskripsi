<?php

namespace App\Http\Controllers;

use App\Models\Ruang;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RuangController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ruang::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_ruangan', 'like', "%{$search}%")
                  ->orWhere('kode_ruangan', 'like', "%{$search}%");
            });
        }

        $ruangs = $query->latest()->paginate(10);

        return view('master.ruang.index', compact('ruangs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_ruangan' => ['required', 'string', 'max:50', 'unique:ruangs,kode_ruangan'],
            'nama_ruangan' => ['required', 'string', 'max:255'],
        ], [
            'kode_ruangan.required' => 'Kode ruangan wajib diisi.',
            'kode_ruangan.unique' => 'Kode ruangan sudah terdaftar.',
            'nama_ruangan.required' => 'Nama ruangan wajib diisi.',
        ]);

        Ruang::create($validated);

        return redirect()->route('master.ruang.index')->with('success', 'Data ruangan berhasil ditambahkan!');
    }

    public function update(Request $request, Ruang $ruang): RedirectResponse
    {
        $validated = $request->validate([
            'kode_ruangan' => ['required', 'string', 'max:50', 'unique:ruangs,kode_ruangan,' . $ruang->id],
            'nama_ruangan' => ['required', 'string', 'max:255'],
        ], [
            'kode_ruangan.required' => 'Kode ruangan wajib diisi.',
            'kode_ruangan.unique' => 'Kode ruangan sudah terdaftar.',
            'nama_ruangan.required' => 'Nama ruangan wajib diisi.',
        ]);

        $ruang->update($validated);

        return redirect()->route('master.ruang.index')->with('success', 'Data ruangan berhasil diperbarui!');
    }

    public function destroy(Ruang $ruang): RedirectResponse
    {
        $ruang->delete();

        return redirect()->route('master.ruang.index')->with('success', 'Data ruangan berhasil dihapus!');
    }
}

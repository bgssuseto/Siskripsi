<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PeriodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Periode::query();

        if ($request->has('search') && $request->search != '') {
            $query->where('nama_periode', 'like', '%' . $request->search . '%');
        }

        $periodes = $query->orderBy('id', 'desc')->paginate(10);

        return view('master.periode.index', compact('periodes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_periode' => ['required', 'string', 'max:255', 'unique:periodes,nama_periode'],
            'aktif'        => ['nullable', 'boolean'],
        ], [
            'nama_periode.required' => 'Nama Periode wajib diisi.',
            'nama_periode.unique'   => 'Nama Periode sudah terdaftar.',
        ]);

        $aktif = $request->has('aktif');

        if ($aktif) {
            // Set all other periods to inactive
            Periode::where('aktif', true)->update(['aktif' => false]);
        }

        Periode::create([
            'nama_periode' => $request->nama_periode,
            'aktif'        => $aktif,
        ]);

        return redirect()->route('master.periode.index')->with('success', 'Data Periode berhasil ditambahkan!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Periode $periode): RedirectResponse
    {
        $request->validate([
            'nama_periode' => ['required', 'string', 'max:255', 'unique:periodes,nama_periode,' . $periode->id],
            'aktif'        => ['nullable', 'boolean'],
        ], [
            'nama_periode.required' => 'Nama Periode wajib diisi.',
            'nama_periode.unique'   => 'Nama Periode sudah terdaftar.',
        ]);

        $aktif = $request->has('aktif');

        if ($aktif) {
            // Set all other periods to inactive
            Periode::where('aktif', true)->update(['aktif' => false]);
        }

        $periode->update([
            'nama_periode' => $request->nama_periode,
            'aktif'        => $aktif,
        ]);

        return redirect()->route('master.periode.index')->with('success', 'Data Periode berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Periode $periode): RedirectResponse
    {
        // Check if there are sidangs in this period
        if ($periode->sidangs()->count() > 0) {
            return redirect()->route('master.periode.index')->with('error', 'Gagal menghapus! Periode ini masih digunakan oleh data sidang.');
        }

        $periode->delete();

        return redirect()->route('master.periode.index')->with('success', 'Data Periode berhasil dihapus!');
    }

    /**
     * Set active a period.
     */
    public function setActive(Periode $periode): RedirectResponse
    {
        Periode::where('aktif', true)->update(['aktif' => false]);
        $periode->update(['aktif' => true]);

        return redirect()->route('master.periode.index')->with('success', "Periode {$periode->nama_periode} telah diaktifkan!");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use App\Services\ActivityLogger;
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

        $periodes = $query->orderBy('id', 'desc')->paginate(5)->withQueryString();

        // Fetch all registration waves/periods
        $pendaftaranPeriodes = PendaftaranPeriode::with('periode')->orderBy('id', 'desc')->get();

        // Most recently created periode, used to offer "reuse the same WA group link" on the Tambah Periode form.
        $latestPeriode = Periode::orderBy('id', 'desc')->first();

        return view('master.periode.index', compact('periodes', 'pendaftaranPeriodes', 'latestPeriode'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_periode'         => ['required', 'string', 'max:255', 'unique:periodes,nama_periode'],
            'aktif'                => ['nullable', 'boolean'],
            'link_grup_wa_skripsi' => ['nullable', 'url', 'max:500'],
            'link_grup_wa_sempro'  => ['nullable', 'url', 'max:500'],
        ], [
            'nama_periode.required'         => 'Nama Periode wajib diisi.',
            'nama_periode.unique'           => 'Nama Periode sudah terdaftar.',
            'link_grup_wa_skripsi.url'      => 'Link Grup WhatsApp Skripsi harus berupa URL yang valid.',
            'link_grup_wa_sempro.url'       => 'Link Grup WhatsApp Sempro harus berupa URL yang valid.',
        ]);

        $aktif = $request->has('aktif');

        if ($aktif) {
            // Set all other periods to inactive
            Periode::where('aktif', true)->update(['aktif' => false]);
        }

        $periode = Periode::create([
            'nama_periode'         => $request->nama_periode,
            'aktif'                => $aktif,
            'link_grup_wa_skripsi' => $validated['link_grup_wa_skripsi'] ?? null,
            'link_grup_wa_sempro'  => $validated['link_grup_wa_sempro'] ?? null,
        ]);

        ActivityLogger::log('created', $periode, "Menambahkan periode akademik baru: {$periode->nama_periode}" . ($aktif ? ' (langsung diaktifkan).' : '.'));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data Periode berhasil ditambahkan!',
                'periode' => $periode
            ]);
        }

        return redirect()->route('master.periode.index')->with('success', 'Data Periode berhasil ditambahkan!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Periode $periode)
    {
        $validated = $request->validate([
            'nama_periode'         => ['required', 'string', 'max:255', 'unique:periodes,nama_periode,' . $periode->id],
            'aktif'                => ['nullable', 'boolean'],
            'link_grup_wa_skripsi' => ['nullable', 'url', 'max:500'],
            'link_grup_wa_sempro'  => ['nullable', 'url', 'max:500'],
        ], [
            'nama_periode.required'         => 'Nama Periode wajib diisi.',
            'nama_periode.unique'           => 'Nama Periode sudah terdaftar.',
            'link_grup_wa_skripsi.url'      => 'Link Grup WhatsApp Skripsi harus berupa URL yang valid.',
            'link_grup_wa_sempro.url'       => 'Link Grup WhatsApp Sempro harus berupa URL yang valid.',
        ]);

        $aktif = $request->has('aktif');

        if ($aktif) {
            // Set all other periods to inactive
            Periode::where('aktif', true)->update(['aktif' => false]);
        }

        $before = $periode->only(['nama_periode', 'aktif', 'link_grup_wa_skripsi', 'link_grup_wa_sempro']);

        $periode->update([
            'nama_periode'         => $request->nama_periode,
            'aktif'                => $aktif,
            'link_grup_wa_skripsi' => $validated['link_grup_wa_skripsi'] ?? null,
            'link_grup_wa_sempro'  => $validated['link_grup_wa_sempro'] ?? null,
        ]);

        ActivityLogger::log(
            'updated',
            $periode,
            "Memperbarui periode akademik: {$periode->nama_periode}.",
            ['before' => $before, 'after' => $periode->only(['nama_periode', 'aktif', 'link_grup_wa_skripsi', 'link_grup_wa_sempro'])]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data Periode berhasil diperbarui!',
                'periode' => $periode
            ]);
        }

        return redirect()->route('master.periode.index')->with('success', 'Data Periode berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Periode $periode)
    {
        // Check if there are sidangs in this period
        if ($periode->sidangs()->count() > 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus! Periode ini masih digunakan oleh data sidang.'
                ], 422);
            }
            return redirect()->route('master.periode.index')->with('error', 'Gagal menghapus! Periode ini masih digunakan oleh data sidang.');
        }

        $namaPeriode = $periode->nama_periode;
        $periode->delete();

        ActivityLogger::log('deleted', $periode, "Menghapus periode akademik: {$namaPeriode}.");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data Periode berhasil dihapus!'
            ]);
        }

        return redirect()->route('master.periode.index')->with('success', 'Data Periode berhasil dihapus!');
    }

    /**
     * Set active a period.
     */
    public function setActive(Request $request, Periode $periode)
    {
        Periode::where('aktif', true)->update(['aktif' => false]);
        $periode->update(['aktif' => true]);

        ActivityLogger::log('updated', $periode, "Mengaktifkan periode akademik: {$periode->nama_periode}.");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Periode {$periode->nama_periode} telah diaktifkan!"
            ]);
        }

        return redirect()->route('master.periode.index')->with('success', "Periode {$periode->nama_periode} telah diaktifkan!");
    }

    /**
     * Store registration wave
     */
    public function storePendaftaranPeriode(Request $request)
    {
        $validated = $request->validate([
            'periode_id'      => ['required', 'exists:periodes,id'],
            'jenis'           => ['required', 'string', 'in:sempro,skripsi'],
            'gelombang'       => ['required', 'integer', 'min:1', 'max:10'],
            'tanggal_mulai'   => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ], [
            'periode_id.required'      => 'Periode Akademik wajib dipilih.',
            'jenis.required'           => 'Jenis pendaftaran wajib dipilih.',
            'gelombang.required'       => 'Gelombang wajib diisi.',
            'tanggal_mulai.required'   => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ]);

        $exists = PendaftaranPeriode::where('periode_id', $validated['periode_id'])
            ->where('jenis', $validated['jenis'])
            ->where('gelombang', $validated['gelombang'])
            ->exists();

        if ($exists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gelombang pendaftaran ini sudah terdaftar untuk periode dan jenis yang sama.'
                ], 422);
            }
            return back()->with('error', 'Gelombang pendaftaran ini sudah terdaftar untuk periode dan jenis yang sama.');
        }

        $pendaftaran = PendaftaranPeriode::create($validated);

        ActivityLogger::log('created', $pendaftaran, "Menambahkan gelombang pendaftaran {$pendaftaran->jenis} #{$pendaftaran->gelombang} ({$pendaftaran->tanggal_mulai->format('d/m/Y')} - {$pendaftaran->tanggal_selesai->format('d/m/Y')}).");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Gelombang Pendaftaran berhasil ditambahkan!',
                'pendaftaran' => $pendaftaran
            ]);
        }

        return redirect()->route('master.periode.index')->with('success', 'Gelombang Pendaftaran berhasil ditambahkan!');
    }

    /**
     * Update registration wave
     */
    public function updatePendaftaranPeriode(Request $request, PendaftaranPeriode $pendaftaranPeriode)
    {
        $validated = $request->validate([
            'periode_id'      => ['required', 'exists:periodes,id'],
            'jenis'           => ['required', 'string', 'in:sempro,skripsi'],
            'gelombang'       => ['required', 'integer', 'min:1', 'max:10'],
            'tanggal_mulai'   => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ], [
            'periode_id.required'      => 'Periode Akademik wajib dipilih.',
            'jenis.required'           => 'Jenis pendaftaran wajib dipilih.',
            'gelombang.required'       => 'Gelombang wajib diisi.',
            'tanggal_mulai.required'   => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ]);

        $exists = PendaftaranPeriode::where('periode_id', $validated['periode_id'])
            ->where('jenis', $validated['jenis'])
            ->where('gelombang', $validated['gelombang'])
            ->where('id', '!=', $pendaftaranPeriode->id)
            ->exists();

        if ($exists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gelombang pendaftaran ini sudah terdaftar untuk periode dan jenis yang sama.'
                ], 422);
            }
            return back()->with('error', 'Gelombang pendaftaran ini sudah terdaftar untuk periode dan jenis yang sama.');
        }

        $before = $pendaftaranPeriode->only(['periode_id', 'jenis', 'gelombang', 'tanggal_mulai', 'tanggal_selesai']);

        $pendaftaranPeriode->update($validated);

        ActivityLogger::log(
            'updated',
            $pendaftaranPeriode,
            "Memperbarui gelombang pendaftaran {$pendaftaranPeriode->jenis} #{$pendaftaranPeriode->gelombang}.",
            ['before' => $before, 'after' => $pendaftaranPeriode->only(['periode_id', 'jenis', 'gelombang', 'tanggal_mulai', 'tanggal_selesai'])]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Gelombang Pendaftaran berhasil diperbarui!',
                'pendaftaran' => $pendaftaranPeriode
            ]);
        }

        return redirect()->route('master.periode.index')->with('success', 'Gelombang Pendaftaran berhasil diperbarui!');
    }

    /**
     * Delete registration wave
     */
    public function destroyPendaftaranPeriode(Request $request, PendaftaranPeriode $pendaftaranPeriode)
    {
        $jenis = $pendaftaranPeriode->jenis;
        $gelombang = $pendaftaranPeriode->gelombang;

        $pendaftaranPeriode->delete();

        ActivityLogger::log('deleted', $pendaftaranPeriode, "Menghapus gelombang pendaftaran {$jenis} #{$gelombang}.");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Gelombang Pendaftaran berhasil dihapus!'
            ]);
        }

        return redirect()->route('master.periode.index')->with('success', 'Gelombang Pendaftaran berhasil dihapus!');
    }
}

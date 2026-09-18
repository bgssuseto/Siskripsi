<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\KesediaanDosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Form kesediaan menguji publik (tanpa login) via link token per-periode.
 * Link ini otomatis aktif/nonaktif mengikuti jendela tanggal Gelombang
 * (PendaftaranPeriode) yang sedang berjalan — lihat Periode::isKesediaanPublicLinkActive().
 */
class PublicKesediaanController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $periode = Periode::where('kesediaan_public_token', $token)->first();

        if (!$periode || !$periode->isKesediaanPublicLinkActive()) {
            return view('public.kesediaan-inactive', [
                'periode' => $periode,
            ]);
        }

        $activeWaves = $periode->pendaftaranPeriodes()
            ->whereDate('tanggal_mulai', '<=', now()->timezone('Asia/Jakarta')->format('Y-m-d'))
            ->whereDate('tanggal_selesai', '>=', now()->timezone('Asia/Jakarta')->format('Y-m-d'))
            ->orderBy('gelombang')
            ->get();

        $dosens = Dosen::where('can_fill_kesediaan', true)
            ->orderBy('nama_dosen')
            ->get();

        return view('public.kesediaan', [
            'periode' => $periode,
            'token' => $token,
            'activeWaves' => $activeWaves,
            'dosens' => $dosens,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $periode = Periode::where('kesediaan_public_token', $token)->first();

        if (!$periode || !$periode->isKesediaanPublicLinkActive()) {
            return redirect()->route('public.kesediaan.show', $token)
                ->with('error', 'Link ini sudah tidak aktif — di luar jadwal gelombang yang berjalan.');
        }

        $validated = $request->validate([
            'dosen_id' => ['required', 'integer', 'exists:dosens,id'],
            'wave_id'  => ['required', 'integer', 'exists:pendaftaran_periodes,id'],
            'slots'    => ['required', 'array', 'min:1'],
            'slots.*.tanggal'    => ['required', 'date'],
            'slots.*.keterangan' => ['nullable', 'string'],
        ]);

        $dosen = Dosen::where('id', $validated['dosen_id'])->where('can_fill_kesediaan', true)->first();
        if (!$dosen) {
            return back()->with('error', 'Dosen tidak ditemukan atau belum diberi akses mengisi kesediaan.')->withInput();
        }

        // Pastikan gelombang yang dipilih benar-benar salah satu gelombang yang
        // SEDANG aktif untuk periode ini (bukan gelombang periode lain / sudah lewat).
        $wave = $periode->pendaftaranPeriodes()
            ->where('id', $validated['wave_id'])
            ->whereDate('tanggal_mulai', '<=', now()->timezone('Asia/Jakarta')->format('Y-m-d'))
            ->whereDate('tanggal_selesai', '>=', now()->timezone('Asia/Jakarta')->format('Y-m-d'))
            ->first();

        if (!$wave) {
            return back()->with('error', 'Gelombang yang dipilih sudah tidak aktif. Silakan muat ulang halaman.')->withInput();
        }

        foreach ($validated['slots'] as $slot) {
            KesediaanDosen::create([
                'dosen_id'    => $dosen->id,
                'periode_id'  => $periode->id,
                'wave_id'     => $wave->id,
                'tanggal'     => $slot['tanggal'],
                'jam_mulai'   => '',
                'jam_selesai' => '',
                'keterangan'  => $slot['keterangan'] ?? null,
            ]);
        }

        return redirect()->route('public.kesediaan.show', $token)
            ->with('success', 'Terima kasih, ' . $dosen->nama_dosen . ' — kesediaan menguji Anda berhasil disimpan.');
    }
}

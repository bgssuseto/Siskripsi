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
 * Aktif/nonaktifnya murni dikontrol admin lewat menu Kesediaan Dosen (toggle
 * Tampilkan/Kunci) — TIDAK tergantung gelombang (PendaftaranPeriode) sedang
 * buka atau tutup, supaya koordinator bisa tetap mengumpulkan kesediaan dosen
 * walau jendela pendaftaran gelombangnya sudah lewat. Lihat
 * Periode::isKesediaanPublicLinkActive().
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

        // Seluruh gelombang periode ini — SENGAJA tidak dibatasi ke yang sedang
        // berjalan hari ini, supaya dosen tetap bisa mengisikan kesediaan untuk
        // gelombang yang jendela pendaftarannya sudah lewat/belum dibuka selama
        // admin membuka form ini lewat menu Kesediaan Dosen.
        $activeWaves = $periode->pendaftaranPeriodes()
            ->orderBy('gelombang')
            ->get();

        $dosens = Dosen::where('can_fill_kesediaan', true)
            ->excludingSuperAdminPlaceholder()
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

        $dosen = Dosen::where('id', $validated['dosen_id'])
            ->where('can_fill_kesediaan', true)
            ->excludingSuperAdminPlaceholder()
            ->first();
        if (!$dosen) {
            return back()->with('error', 'Dosen tidak ditemukan atau belum diberi akses mengisi kesediaan.')->withInput();
        }

        // Pastikan gelombang yang dipilih benar-benar milik periode ini — tidak
        // lagi disyaratkan "sedang berjalan" (lihat show() di atas), karena
        // koordinator boleh membuka form ini untuk gelombang yang sudah lewat/
        // belum dibuka selama toggle di menu Kesediaan Dosen mengizinkan.
        $wave = $periode->pendaftaranPeriodes()
            ->where('id', $validated['wave_id'])
            ->first();

        if (!$wave) {
            return back()->with('error', 'Gelombang yang dipilih tidak valid untuk periode ini. Silakan muat ulang halaman.')->withInput();
        }

        foreach ($validated['slots'] as $slot) {
            // firstOrCreate on the natural key so a double-submit (double-click,
            // browser retry) doesn't leave duplicate slots for the same date.
            KesediaanDosen::firstOrCreate(
                [
                    'dosen_id'    => $dosen->id,
                    'wave_id'     => $wave->id,
                    'tanggal'     => $slot['tanggal'],
                ],
                [
                    'periode_id'  => $periode->id,
                    'jam_mulai'   => '',
                    'jam_selesai' => '',
                    'keterangan'  => $slot['keterangan'] ?? null,
                ]
            );
        }

        return redirect()->route('public.kesediaan.show', $token)
            ->with('success', 'Terima kasih, ' . $dosen->nama_dosen . ' — kesediaan menguji Anda berhasil disimpan.');
    }
}

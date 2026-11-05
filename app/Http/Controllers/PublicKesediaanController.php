<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\KesediaanDosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
    public function show(Request $request, string $token): Response
    {
        $periode = Periode::where('kesediaan_public_token', $token)->first();

        if (!$periode || !$periode->isKesediaanPublicLinkActive()) {
            return $this->tanpaCache(response()->view('public.kesediaan-inactive', [
                'periode' => $periode,
            ]));
        }

        // Seluruh gelombang periode ini — SENGAJA tidak dibatasi ke yang sedang
        // berjalan hari ini, supaya dosen tetap bisa mengisikan kesediaan untuk
        // gelombang yang jendela pendaftarannya sudah lewat/belum dibuka selama
        // admin membuka form ini lewat menu Kesediaan Dosen. Gelombang yang
        // rentangnya > 1 bulan disembunyikan supaya tidak membingungkan (lihat
        // PendaftaranPeriode::isTooLongForKesediaanPicker()) -- wave_id karena
        // itu juga dibuat opsional di bawah, supaya form tetap bisa dipakai
        // walau semua gelombang periode ini "disembunyikan".
        $activeWaves = $periode->pendaftaranPeriodes()
            ->orderBy('gelombang')
            ->get()
            ->reject(fn ($w) => $w->isTooLongForKesediaanPicker())
            ->values();

        $dosens = Dosen::where('can_fill_kesediaan', true)
            ->excludingSuperAdminPlaceholder()
            ->orderBy('nama_dosen')
            ->get();

        return $this->tanpaCache(response()->view('public.kesediaan', [
            'periode' => $periode,
            'token' => $token,
            'activeWaves' => $activeWaves,
            'dosens' => $dosens,
        ]));
    }

    /**
     * Halaman form publik tidak boleh disimpan cache (browser, maupun cache server
     * seperti LiteSpeed di Hostinger) — supaya dosen selalu melihat status form,
     * pilihan gelombang & rentang tanggal terbaru.
     */
    private function tanpaCache(Response $response): Response
    {
        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-LiteSpeed-Cache-Control', 'no-cache');
    }

    /**
     * Dipanggil lewat AJAX (fetch) dari public/kesediaan.blade.php, BUKAN form
     * native — sengaja, supaya tujuan request selalu persis window.location.pathname
     * halaman yang sedang dibuka dosen (lihat komentar di view-nya), tidak
     * bergantung sama sekali pada route()/APP_URL di server (skema/host/subfolder
     * bisa salah di balik proxy/alias hosting kampus dan berakhir 404 — ini yang
     * sempat terjadi walau sudah sekali diperbaiki lewat redirect relatif).
     * Setiap jalur keluar mengembalikan JSON {success, message} supaya halaman
     * bisa menampilkan alert sukses/gagal tanpa pernah pindah ke URL lain.
     */
    public function store(Request $request, string $token): RedirectResponse|JsonResponse
    {
        $periode = Periode::where('kesediaan_public_token', $token)->first();

        if (!$periode || !$periode->isKesediaanPublicLinkActive()) {
            $msg = 'Link ini sudah tidak aktif — di luar jadwal gelombang yang berjalan.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('public.kesediaan.show', $token)->with('error', $msg);
        }

        $validated = $request->validate([
            'dosen_id' => ['required', 'integer', 'exists:dosens,id'],
            // Wajib — kesediaan harus selalu ditautkan ke gelombang + periode
            // semester ini, bukan "kesediaan umum" tanpa konteks.
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
            $msg = 'Dosen tidak ditemukan atau belum diberi akses mengisi kesediaan.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        // Pastikan gelombang yang dipilih benar-benar milik periode ini — tidak
        // lagi disyaratkan "sedang berjalan" (lihat show() di atas), karena
        // koordinator boleh membuka form ini untuk gelombang yang sudah lewat/
        // belum dibuka selama toggle di menu Kesediaan Dosen mengizinkan.
        $wave = $periode->pendaftaranPeriodes()
            ->where('id', $validated['wave_id'])
            ->first();

        if (!$wave) {
            $msg = 'Gelombang yang dipilih tidak valid untuk periode ini. Silakan muat ulang halaman.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        // Tanggal harus berada dalam rentang pengisian yang diatur koordinator.
        $diLuar = $periode->tanggalDiLuarRentangKesediaan(array_column($validated['slots'], 'tanggal'));
        if ($diLuar) {
            $msg = $periode->pesanDiLuarRentangKesediaan($diLuar);
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
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

        $msg = 'Terima kasih, ' . $dosen->nama_dosen . ' — kesediaan menguji Anda berhasil disimpan.';
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'count' => count($validated['slots'])]);
        }

        // Fallback non-JS: alamat RELATIF (tetap di domain & http/https yang sama
        // dengan halaman dosen), plus penanda ?tersimpan=N supaya pesan sukses
        // tetap tampil walau browser dosen tidak menyimpan cookie sesi.
        return redirect()->away(route('public.kesediaan.show', $token, false) . '?tersimpan=' . count($validated['slots']))
            ->with('success', $msg);
    }
}

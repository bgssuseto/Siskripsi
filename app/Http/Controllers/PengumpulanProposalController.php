<?php

namespace App\Http\Controllers;

use App\Models\PengumpulanProposal;
use App\Models\Sidang;
use App\Services\ActivityLogger;
use App\Services\SuratPengumpulanProposal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengumpulan Proposal Skripsi: admin/koordinator menerima atau menolak dari
 * menu Data Sempro; bila diterima mahasiswa bisa mencetak surat keterangannya.
 */
class PengumpulanProposalController extends Controller
{
    /** Terima / tolak / batalkan pengumpulan proposal (admin & koordinator). */
    public function verifikasi(Request $request, Sidang $sidang): JsonResponse
    {
        if ($sidang->jenis_tugas_akhir !== 'sempro' || !$sidang->isTerverifikasi()) {
            return response()->json(['success' => false, 'message' => 'Pengumpulan proposal hanya untuk pendaftaran Sempro yang sudah disetujui.'], 422);
        }

        $proposal = $sidang->pengumpulanProposal ?? new PengumpulanProposal(['sidang_id' => $sidang->id]);

        $validated = $request->validate([
            'status'        => ['required', Rule::in([PengumpulanProposal::STATUS_DITERIMA, PengumpulanProposal::STATUS_DITOLAK, PengumpulanProposal::STATUS_BELUM])],
            'catatan'       => ['nullable', 'required_if:status,ditolak', 'string', 'max:1000'],
            'nomor_surat'   => ['nullable', 'integer', 'min:1', Rule::unique('pengumpulan_proposals', 'nomor_surat')->ignore($proposal->id)],
        ], [
            'catatan.required_if' => 'Catatan wajib diisi bila proposal ditolak.',
            'nomor_surat.unique'  => 'Nomor surat ini sudah dipakai mahasiswa lain.',
        ]);

        $statusSebelum = $proposal->status ?? PengumpulanProposal::STATUS_BELUM;
        $userId = (int) $request->user()->id;

        match ($validated['status']) {
            PengumpulanProposal::STATUS_DITERIMA => $proposal->terima($userId, $validated['nomor_surat'] ?? null),
            PengumpulanProposal::STATUS_DITOLAK  => $proposal->tolak($userId, $validated['catatan']),
            default                              => $proposal->batalkan($userId),
        };

        ActivityLogger::log(
            'verifikasi_proposal',
            $sidang,
            "Mengubah status pengumpulan proposal {$sidang->nama_mahasiswa} ({$sidang->nim}) dari \"{$statusSebelum}\" menjadi \"{$proposal->status}\"." .
                ($proposal->isDiterima() ? " Nomor surat: {$proposal->nomor_surat_lengkap}." : '') .
                (!empty($validated['catatan']) && $proposal->status === PengumpulanProposal::STATUS_DITOLAK ? " Catatan: {$validated['catatan']}" : ''),
            ['before' => ['status' => $statusSebelum], 'after' => ['status' => $proposal->status]]
        );

        $message = match ($proposal->status) {
            PengumpulanProposal::STATUS_DITERIMA => "Proposal {$sidang->nama_mahasiswa} diterima. Surat keterangan ({$proposal->nomor_surat_lengkap}) kini bisa dicetak mahasiswa.",
            PengumpulanProposal::STATUS_DITOLAK  => "Proposal {$sidang->nama_mahasiswa} ditolak dengan catatan.",
            default                              => "Status pengumpulan proposal {$sidang->nama_mahasiswa} dikembalikan ke Belum Mengumpulkan.",
        };

        return response()->json(['success' => true, 'message' => $message]);
    }

    /** Cetak surat dari menu Data Sempro (admin & koordinator). */
    public function cetak(Sidang $sidang): Response
    {
        $proposal = $sidang->pengumpulanProposal;
        abort_unless($proposal && $proposal->isDiterima(), 404);

        return SuratPengumpulanProposal::stream($proposal);
    }

    /** Cetak surat oleh mahasiswa pemilik pendaftaran. */
    public function mahasiswaCetak(Request $request, Sidang $sidang): Response
    {
        $user = $request->user();
        abort_unless(filled($user->nim) && $sidang->nim === $user->nim, 403);

        $proposal = $sidang->pengumpulanProposal;
        abort_unless($proposal && $proposal->isDiterima(), 404);

        return SuratPengumpulanProposal::stream($proposal);
    }
}

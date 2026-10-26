<?php

namespace App\Http\Controllers;

use App\Models\Sidang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DokumenController extends Controller
{
    /**
     * Serve dokumen pendaftaran (persyaratan / bukti pembayaran) lewat PHP,
     * bukan link statis langsung ke public/uploads — supaya tetap bisa diakses
     * walau webroot production tidak persis sama dengan public_path() Laravel
     * (kasus umum pada deployment subfolder di shared hosting).
     */
    public function show(Request $request, Sidang $sidang, string $type): BinaryFileResponse
    {
        $user = Auth::user();

        $isOwner = $user->isMahasiswa() && filled($user->nim) && $user->nim === $sidang->nim;
        // Koordinator bisa berupa role utama maupun role tambahan pada akun dosen.
        $isStaff = $user->hasRole([\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_KOORDINATOR]);

        abort_unless($isOwner || $isStaff, 403);

        $path = match ($type) {
            'persyaratan' => $sidang->file_persyaratan,
            'bukti-pembayaran' => $sidang->bukti_pembayaran,
            default => null,
        };

        abort_if(! $path, 404);

        $fullPath = \App\Support\BerkasMahasiswa::lokasi($path);

        abort_unless($fullPath && is_file($fullPath), 404);

        if ($request->boolean('download')) {
            return response()->download($fullPath, basename($fullPath));
        }

        return response()->file($fullPath);
    }
}

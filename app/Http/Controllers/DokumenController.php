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

        $isOwner = $user->isMahasiswa() && $user->nim === $sidang->nim;
        $isStaff = $user->isSuperAdmin() || $user->isKoordinator();

        abort_unless($isOwner || $isStaff, 403);

        $path = match ($type) {
            'persyaratan' => $sidang->file_persyaratan,
            'bukti-pembayaran' => $sidang->bukti_pembayaran,
            default => null,
        };

        abort_if(! $path, 404);

        $fullPath = public_path($path);

        abort_unless(is_file($fullPath), 404);

        if ($request->boolean('download')) {
            return response()->download($fullPath, basename($fullPath));
        }

        return response()->file($fullPath);
    }
}

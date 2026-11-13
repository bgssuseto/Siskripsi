<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Services\ActivityLogger;
use App\Services\KalenderIcs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Sinkron jadwal ujian dosen ke Google Calendar / kalender HP lewat link
 * langganan .ics (tanpa login; diamankan token acak per dosen).
 */
class KalenderDosenController extends Controller
{
    /** Feed .ics publik — dipanggil berkala oleh aplikasi kalender. */
    public function ics(string $token): Response
    {
        $dosen = strlen($token) >= 32 ? Dosen::where('token_kalender', $token)->first() : null;
        abort_unless($dosen, 404);

        return response(KalenderIcs::untukDosen($dosen), 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="jadwal-ujian.ics"',
            'Cache-Control'       => 'no-cache, private',
            'X-Robots-Tag'        => 'noindex',
        ]);
    }

    /** Buat link baru; link lama langsung tidak berlaku (mis. bila sempat tersebar). */
    public function gantiToken(Request $request): RedirectResponse
    {
        $dosen = $request->user()->dosen;
        abort_unless($dosen, 404);

        $dosen->tokenKalender(true);
        ActivityLogger::log('updated', $dosen, "Membuat ulang link sinkron kalender dosen {$dosen->nama_dosen}; link lama tidak berlaku lagi.");

        return redirect()->route('dosen.kalender')->with('success', 'Link sinkron kalender baru sudah dibuat. Link lama tidak berlaku lagi — tambahkan ulang ke aplikasi kalender Anda.');
    }
}

<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Sidang;
use App\Models\Dosen;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use Carbon\Carbon;
use App\Services\SidangConflictService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use App\Support\PdfGabung;
use Illuminate\Support\Facades\Crypt;

/**
 * Helper bersama menu Administrasi (Berita Acara & SK): kop surat, resolusi
 * periode/gelombang, dan persiapan memori/waktu untuk cetak besar.
 */
trait BantuCetakAdministrasi
{
    /**
     * Load the official UMK kop surat (letterhead) image as a base64 data URI,
     * for embedding directly into a dompdf-rendered view.
     */
    private function kopSuratBase64(): string
    {
        $kopPath = public_path('images/kop_surat.png');
        if (!file_exists($kopPath)) {
            return '';
        }

        $type = pathinfo($kopPath, PATHINFO_EXTENSION);
        $data = file_get_contents($kopPath);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    /**
     * Periode terpilih; default ke periode aktif (atau terbaru) bila tidak dipilih.
     */
    private function resolvePeriodeId(Request $request, $periodes = null): ?int
    {
        if ($request->filled('periode_id')) {
            return (int) $request->get('periode_id');
        }

        return Periode::where('aktif', true)->value('id')
            ?? ($periodes ?? Periode::orderBy('id', 'desc')->get())->first()?->id;
    }

    /**
     * Opsi dropdown Gelombang untuk periode + jenis terpilih, diambil dari Master
     * Gelombang (PendaftaranPeriode) beserta rentang tanggalnya.
     */
    private function gelombangOptions(?int $periodeId, ?string $jenis)
    {
        if (!$periodeId) {
            return collect();
        }

        return PendaftaranPeriode::where('periode_id', $periodeId)
            ->when($jenis === 'sempro', fn ($q) => $q->where('jenis', 'sempro'))
            ->when($jenis === 'skripsi', fn ($q) => $q->where('jenis', 'skripsi'))
            ->orderBy('gelombang')
            ->get()
            ->groupBy('gelombang')
            ->map(function ($waves, $gelombang) {
                $w = $waves->first();
                $rentang = $w->tanggal_mulai && $w->tanggal_selesai
                    ? ' (' . Carbon::parse($w->tanggal_mulai)->locale('id')->translatedFormat('j M') . ' – ' . Carbon::parse($w->tanggal_selesai)->locale('id')->translatedFormat('j M Y') . ')'
                    : '';
                return ['value' => (string) $gelombang, 'label' => 'Gelombang ' . $gelombang . ($waves->count() === 1 ? $rentang : '')];
            })
            ->values();
    }

    /**
     * DomPDF butuh ±3 MB memori per mahasiswa untuk satu PDF gabungan (4 halaman
     * per mahasiswa), jadi "Cetak Semua" untuk satu periode penuh dulu berakhir
     * dengan error 500 (memory exhausted). Naikkan batas memori & waktu bila
     * server mengizinkan, lalu hitung berapa mahasiswa yang muat dalam satu PDF.
     */
    private function siapkanCetakBesar(): void
    {
        @set_time_limit(600);

        if ($this->memoriMb() < 1024) {
            @ini_set('memory_limit', '1024M');
        }
    }

    private function memoriMb(): int
    {
        $limit = trim((string) ini_get('memory_limit'));
        if ($limit === '' || $limit === '-1') {
            return PHP_INT_MAX;
        }

        $angka = (int) $limit;
        return match (strtolower(substr($limit, -1))) {
            'g'     => $angka * 1024,
            'm'     => $angka,
            'k'     => intdiv($angka, 1024),
            default => intdiv($angka, 1048576),
        };
    }
}

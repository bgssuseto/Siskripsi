<?php

namespace App\Http\Controllers;

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
 * Berita acara ujian (PDF satuan, massal, gabungan, ZIP).
 * (Dipecah dari AdministrasiController.)
 */
class BeritaAcaraController extends Controller
{
    use Concerns\BantuCetakAdministrasi;

    /**
     * Halaman Dashboard Berita Acara Sidang
     */
    public function beritaAcaraIndex(Request $request): View
    {
        $periodes = Periode::orderBy('id', 'desc')->get();
        $jenis    = $request->get('jenis'); // 'skripsi' | 'sempro' | null

        $selectedPeriodeId = $this->resolvePeriodeId($request, $periodes);
        $selectedPeriode   = Periode::find($selectedPeriodeId);
        $selectedGelombang = $request->get('gelombang');
        $gelombangOptions  = $this->gelombangOptions($selectedPeriodeId, $jenis);

        $sidangs = $this->beritaAcaraQuery($request, $selectedPeriodeId)
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam', 'asc')
            ->orderBy('nama_mahasiswa', 'asc')
            ->get();

        $maksCetakGabungan = $this->maksBeritaAcaraGabungan();

        return view('administrasi.berita-acara.index', compact(
            'periodes', 'selectedPeriode', 'selectedPeriodeId',
            'selectedGelombang', 'gelombangOptions', 'jenis', 'sidangs', 'maksCetakGabungan'
        ));
    }

    /**
     * Query berita acara sesuai filter: periode, gelombang, jenis (dan rentang
     * tanggal pendaftaran untuk link lama yang masih membawanya).
     */
    private function beritaAcaraQuery(Request $request, ?int $periodeId)
    {
        $jenis = $request->get('jenis');

        return Sidang::with([
                'pembimbingUtama', 'pembimbingPendamping',
                'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2',
                'ruang', 'ruangPendamping', 'periode',
            ])
            ->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))
            ->when($request->filled('gelombang'), fn ($q) => $q->where('gelombang', $request->get('gelombang')))
            ->when($request->filled('tanggal_pendaftaran_mulai'), fn ($q) => $q->whereDate('tanggal_pendaftaran', '>=', $request->get('tanggal_pendaftaran_mulai')))
            ->when($request->filled('tanggal_pendaftaran_selesai'), fn ($q) => $q->whereDate('tanggal_pendaftaran', '<=', $request->get('tanggal_pendaftaran_selesai')))
            ->when($jenis === 'sempro', fn ($q) => $q->where('jenis_tugas_akhir', 'sempro'))
            ->when($jenis === 'skripsi', fn ($q) => $q->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']))
            // Pencarian: tiap kata harus cocok dengan NIM, nama, judul, ruang, atau nama dosen.
            ->when(trim((string) $request->get('q')) !== '', function ($q) use ($request) {
                $kataKunci = preg_split('/\s+/', trim((string) $request->get('q')));
                foreach (array_slice($kataKunci, 0, 6) as $kata) {
                    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $kata) . '%';
                    $q->where(function ($w) use ($like) {
                        $w->where('nim', 'like', $like)
                          ->orWhere('nama_mahasiswa', 'like', $like)
                          ->orWhere('judul_skripsi', 'like', $like);
                        foreach (['pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'] as $rel) {
                            $w->orWhereHas($rel, fn ($d) => $d->where('nama_dosen', 'like', $like));
                        }
                        $w->orWhereHas('ruang', fn ($r) => $r->where('kode_ruangan', 'like', $like)->orWhere('nama_ruangan', 'like', $like));
                    });
                }
            });
    }

    private function maksBeritaAcaraGabungan(): int
    {
        $this->siapkanCetakBesar();

        return max(10, min(250, intdiv(min($this->memoriMb(), 4096) - 64, 3)));
    }

    private function renderBeritaAcaraGabungan(Request $request)
    {
        $periodeId = $this->resolvePeriodeId($request);

        $sidangs = $this->beritaAcaraQuery($request, $periodeId)
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam', 'asc')
            ->orderBy('nama_mahasiswa', 'asc')
            ->get();

        if ($sidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada data sidang mahasiswa pada filter yang dipilih.');
        }

        $maks = $this->maksBeritaAcaraGabungan();
        if ($sidangs->count() > $maks) {
            return back()->with('warning', "Terlalu banyak data untuk dicetak dalam satu PDF ({$sidangs->count()} mahasiswa, maksimal {$maks}). Pilih Gelombang terlebih dahulu, atau gunakan \"Unduh Semua (ZIP)\".");
        }

        return Pdf::loadView('administrasi.berita-acara.pdf', [
            'sidangs'   => $sidangs,
            'kopBase64' => $this->kopSuratBase64(),
        ])->setPaper('a4', 'portrait');
    }

    private function namaFileBeritaAcara(Request $request, string $ext): string
    {
        $jenis = $request->get('jenis') === 'sempro' ? 'Sempro' : ($request->get('jenis') === 'skripsi' ? 'Skripsi' : 'Sidang');
        $periode = Periode::find($this->resolvePeriodeId($request))?->nama_periode ?? 'Semua Periode';
        $gelombang = $request->filled('gelombang') ? '_Gelombang_' . $request->get('gelombang') : '';

        return trim(preg_replace('/[^\w\.-]+/', '_', "Berita_Acara_{$jenis}_{$periode}{$gelombang}"), '_') . ".{$ext}";
    }

    /**
     * Generate & Download Berita Acara PDF untuk 1 Mahasiswa
     */
    public function generateBeritaAcaraPdf(Request $request, Sidang $sidang): Response
    {
        $pdf = Pdf::loadView('administrasi.berita-acara.pdf', [
            'sidangs'   => collect([$sidang]),
            'kopBase64' => $this->kopSuratBase64(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("Berita Acara - {$sidang->nim} - {$sidang->nama_mahasiswa}.pdf");
    }

    /**
     * Preview Berita Acara PDF untuk 1 Mahasiswa di Browser
     */
    public function previewBeritaAcaraPdf(Request $request, Sidang $sidang): Response
    {
        $pdf = Pdf::loadView('administrasi.berita-acara.pdf', [
            'sidangs'   => collect([$sidang]),
            'kopBase64' => $this->kopSuratBase64(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("Berita Acara - {$sidang->nama_mahasiswa}.pdf");
    }

    /**
     * Generate & Download Berita Acara PDF Massal (Combined PDF)
     */
    public function generateBeritaAcaraMassPdf(Request $request): Response|RedirectResponse
    {
        $pdf = $this->renderBeritaAcaraGabungan($request);

        return $pdf instanceof RedirectResponse ? $pdf : $pdf->download($this->namaFileBeritaAcara($request, 'pdf'));
    }

    /**
     * Preview Berita Acara PDF Massal (Combined PDF) di Browser
     */
    public function previewBeritaAcaraMassPdf(Request $request): Response|RedirectResponse
    {
        $pdf = $this->renderBeritaAcaraGabungan($request);

        return $pdf instanceof RedirectResponse ? $pdf : $pdf->stream($this->namaFileBeritaAcara($request, 'pdf'));
    }

    /**
     * Unduh semua Berita Acara pada filter sebagai SATU PDF gabungan, tanpa
     * batas jumlah mahasiswa. Tiap mahasiswa dirender sendiri-sendiri (seperti
     * ZIP) lalu digabung, jadi memori tidak habis walau datanya banyak.
     */
    public function generateBeritaAcaraGabunganPdf(Request $request): BinaryFileResponse|RedirectResponse
    {
        $sidangs = $this->beritaAcaraQuery($request, $this->resolvePeriodeId($request))
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam', 'asc')
            ->orderBy('nama_mahasiswa', 'asc')
            ->get();

        if ($sidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada data sidang mahasiswa pada filter yang dipilih.');
        }

        $this->siapkanCetakBesar();

        $namaFile = $this->namaFileBeritaAcara($request, 'pdf');
        $tempPath = storage_path('app/public/' . uniqid('', true) . '_' . $namaFile);
        $kopBase64 = $this->kopSuratBase64();

        try {
            $gabung = new PdfGabung($tempPath);

            foreach ($sidangs as $sidang) {
                $pdf = Pdf::loadView('administrasi.berita-acara.pdf', [
                    'sidangs'   => collect([$sidang]),
                    'kopBase64' => $kopBase64,
                ])->setPaper('a4', 'portrait');

                $gabung->tambah($pdf->output());
                unset($pdf);
            }

            $gabung->selesai();
        } catch (\Throwable $e) {
            @unlink($tempPath);
            report($e);

            return back()->with('error', 'Gagal membuat PDF gabungan. Silakan coba "Unduh Semua (ZIP)".');
        }

        return response()->download($tempPath, $namaFile)->deleteFileAfterSend(true);
    }

    /**
     * Download Berita Acara dalam bentuk ZIP berisi individual PDFs — dirender
     * satu per satu, jadi pemakaian memori tidak bertambah seiring jumlah data.
     */
    public function generateBeritaAcaraZip(Request $request): BinaryFileResponse|RedirectResponse
    {
        $sidangs = $this->beritaAcaraQuery($request, $this->resolvePeriodeId($request))
            ->orderBy('nama_mahasiswa')
            ->get();

        if ($sidangs->isEmpty()) {
            return back()->with('warning', 'Tidak ada data sidang mahasiswa pada filter yang dipilih.');
        }

        $this->siapkanCetakBesar();

        $zipFilename = $this->namaFileBeritaAcara($request, 'zip');
        // uniqid() prefix on the on-disk temp path only — the download itself
        // keeps the clean $zipFilename — so two concurrent exports never
        // overwrite/truncate each other's zip.
        $tempZipPath = storage_path('app/public/' . uniqid('', true) . '_' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        $kopBase64 = $this->kopSuratBase64();

        foreach ($sidangs as $sidang) {
            $pdf = Pdf::loadView('administrasi.berita-acara.pdf', [
                'sidangs'   => collect([$sidang]),
                'kopBase64' => $kopBase64,
            ]);

            $pdf->setPaper('a4', 'portrait');

            $safeName = preg_replace('/[^\w\s\.,-]/', '_', $sidang->nama_mahasiswa);
            $zip->addFromString("Berita Acara - {$sidang->nim} - {$safeName}.pdf", $pdf->output());
            unset($pdf);
        }

        $zip->close();

        return response()->download($tempZipPath, $zipFilename)->deleteFileAfterSend(true);
    }
}

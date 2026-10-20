<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Kaprodi;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\Sidang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/**
 * Surat Permohonan SK Pembimbing / Penguji (format "PENGAJUAN SK PENGUJI
 * GANJIL2526.docx": surat ke Dekan, Lampiran 1 rekap per dosen, lalu rincian
 * mahasiswa per dosen). Dipakai menu Administrasi > SK (seluruh dosen) dan menu
 * SK di portal dosen (hanya bagian dosen yang login).
 *
 * SK Penguji dihitung dari ujian skripsi (skripsi / sidang / jurnal) saja.
 * SK Pembimbing dihitung sejak tahap Sempro (lihat pembimbingSidangs()) — pembimbing
 * sudah membimbing sejak proposal, sehingga SK-nya bisa terbit di semester berjalan
 * walau pendaftaran sidang skripsi belum dibuka.
 * Filter gelombang 'tanpa' = pendaftaran tanpa gelombang (input manual admin).
 */
class SkPdfService
{
    public const MODES = ['pembimbing', 'penguji'];

    /** [kolom peran => [label di tabel rincian, indeks kolom rekap]] */
    public static function peranMap(string $mode): array
    {
        return $mode === 'penguji'
            ? ['ketua_penguji_id' => ['KETUA PENGUJI', 0], 'anggota_penguji_1_id' => ['ANGGOTA PENGUJI', 1], 'anggota_penguji_2_id' => ['ANGGOTA PENGUJI', 1]]
            : ['dosen_pembimbing_utama_id' => ['PEMBIMBING UTAMA', 0], 'dosen_pembimbing_pendamping_id' => ['PEMBIMBING PENDAMPING', 1]];
    }

    /**
     * @param  array{periode_id?: ?int, gelombang?: ?string, tanggal_mulai?: ?string, tanggal_selesai?: ?string}  $filter
     */
    public static function sidangs(array $filter, string $mode = 'penguji'): Collection
    {
        if ($mode === 'pembimbing') {
            return self::pembimbingSidangs($filter);
        }

        return self::filtered($filter)
            ->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)
            ->orderByRaw('tanggal IS NULL')->orderBy('tanggal')->orderBy('jam')->orderBy('nama_mahasiswa')
            ->get();
    }

    /**
     * Mahasiswa bimbingan untuk SK Pembimbing: pendaftaran ujian skripsi (sidang/jurnal)
     * DAN pendaftaran Sempro yang sudah disetujui. Satu baris per mahasiswa — bila
     * mahasiswa punya keduanya, data skripsi (tahap terakhir) yang dipakai, sebab
     * pembimbingnya bisa saja berganti setelah sempro.
     */
    public static function pembimbingSidangs(array $filter): Collection
    {
        return self::filtered($filter)
            ->where(fn ($q) => $q->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)
                ->orWhere(fn ($w) => $w->where('jenis_tugas_akhir', 'sempro')->where('verifikasi_status', 'disetujui')))
            ->where(fn ($q) => $q->whereNotNull('dosen_pembimbing_utama_id')->orWhereNotNull('dosen_pembimbing_pendamping_id'))
            ->get()
            ->sortByDesc(fn ($s) => sprintf('%d-%012d', in_array($s->jenis_tugas_akhir, Sidang::SKRIPSI_BUCKET, true) ? 1 : 0, $s->id))
            ->unique(fn ($s) => $s->nim ?: 'nama:' . $s->nama_mahasiswa)
            ->sortBy('nama_mahasiswa')
            ->values();
    }

    /** Query Sidang dengan filter periode / gelombang / rentang tanggal pendaftaran. */
    private static function filtered(array $filter): \Illuminate\Database\Eloquent\Builder
    {
        $periodeId = $filter['periode_id'] ?? null;
        $gelombang = $filter['gelombang'] ?? null;

        return Sidang::query()
            ->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))
            ->when($gelombang === 'tanpa', fn ($q) => $q->whereNull('gelombang'))
            ->when($gelombang !== null && $gelombang !== '' && $gelombang !== 'tanpa', fn ($q) => $q->where('gelombang', $gelombang))
            ->when($filter['tanggal_mulai'] ?? null, fn ($q, $tgl) => $q->whereDate('tanggal_pendaftaran', '>=', $tgl))
            ->when($filter['tanggal_selesai'] ?? null, fn ($q, $tgl) => $q->whereDate('tanggal_pendaftaran', '<=', $tgl));
    }

    /**
     * Rekap & rincian per dosen. $hanyaDosenId membatasi ke satu dosen saja.
     */
    public static function dosenRincian(Collection $sidangs, string $mode, ?int $hanyaDosenId = null): Collection
    {
        $peranMap = self::peranMap($mode);

        return Dosen::excludingSuperAdminPlaceholder()
            ->when($hanyaDosenId, fn ($q) => $q->whereKey($hanyaDosenId))
            ->orderBy('nama_dosen')->get()
            ->map(function ($d) use ($sidangs, $peranMap) {
                $jumlah = [0, 0];
                $mahasiswa = [[], []];
                foreach ($sidangs as $s) {
                    foreach ($peranMap as $kolom => [$label, $idx]) {
                        if ((int) $s->{$kolom} === $d->id) {
                            $jumlah[$idx]++;
                            $mahasiswa[$idx][] = ['nim' => $s->nim, 'nama' => $s->nama_mahasiswa, 'peran' => $label];
                            break;
                        }
                    }
                }

                return [
                    'nama_dosen' => $d->nama_dosen,
                    'jumlah'     => $jumlah,
                    // Ketua / Pembimbing Utama dulu, lalu Anggota / Pendamping.
                    'mahasiswa'  => array_merge($mahasiswa[0], $mahasiswa[1]),
                ];
            })
            ->filter(fn ($d) => $d['jumlah'][0] + $d['jumlah'][1] > 0)
            ->values();
    }

    /**
     * Apakah data SK satu periode sudah final? Final bila semester sudah berganti
     * (periode tidak aktif lagi), ATAU gelombang pendaftaran skripsi terakhir periode
     * itu sudah ditutup dan tidak ada lagi sidang yang belum/akan dilaksanakan.
     * Sebelum final, SK Pembimbing di portal dosen hanya terbit sebagai SEMENTARA.
     */
    public static function periodeFinal(Periode $periode): bool
    {
        if (!$periode->aktif) {
            return true;
        }

        $hariIni = now()->timezone('Asia/Jakarta')->toDateString();
        $gelombangSkripsi = PendaftaranPeriode::where('periode_id', $periode->id)->where('jenis', 'skripsi')->get();
        if ($gelombangSkripsi->isEmpty()) {
            return false;
        }

        $penutupan = $gelombangSkripsi->max(fn ($g) => $g->tanggal_selesai?->toDateString());
        if ($penutupan === null || $penutupan >= $hariIni) {
            return false;
        }

        $masihBerjalan = Sidang::where('periode_id', $periode->id)
            ->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)
            ->where(fn ($q) => $q->whereNull('verifikasi_status')->orWhere('verifikasi_status', '!=', 'ditolak'))
            ->where(fn ($q) => $q->whereNull('tanggal')->orWhereDate('tanggal', '>=', $hariIni))
            ->exists();

        return !$masihBerjalan;
    }

    /**
     * @param  bool  $sementara  Tandai PDF sebagai SK SEMENTARA (watermark + catatan, nama file _SEMENTARA)
     * @return array{pdf: \Barryvdh\DomPDF\PDF, filename: string}|null  null bila tidak ada data
     */
    public static function build(string $mode, array $filter, ?int $hanyaDosenId = null, bool $sementara = false): ?array
    {
        $dosenRincian = self::dosenRincian(self::sidangs($filter, $mode), $mode, $hanyaDosenId);

        if ($dosenRincian->isEmpty()) {
            return null;
        }

        @set_time_limit(600);

        $namaPeriode  = Periode::find($filter['periode_id'] ?? null)?->nama_periode ?? 'Semua Periode';
        // SK satu dosen per gelombang (portal dosen): sebut gelombangnya di isi surat.
        $namaPeriodeSurat = $hanyaDosenId && is_numeric($filter['gelombang'] ?? null)
            ? $namaPeriode . ' Gelombang ' . $filter['gelombang']
            : $namaPeriode;
        $tanggalSurat = now()->timezone('Asia/Jakarta');

        $pdf = Pdf::loadView('administrasi.sk.pdf', [
            'mode'         => $mode,
            'namaPeriode'  => $namaPeriodeSurat,
            'kegiatan'     => $mode === 'pembimbing' ? ['nama' => 'Skripsi', 'hal' => 'Skripsi'] : ['nama' => 'Sidang Skripsi', 'hal' => 'Skripsi'],
            'dosenRincian' => $dosenRincian,
            'tanggalSurat' => $tanggalSurat->copy()->locale('id')->translatedFormat('j F Y'),
            'kaprodi'      => rescue(fn () => Kaprodi::untukTanggal($tanggalSurat)?->nama, null, false),
            'kopBase64'    => self::kopSuratBase64(),
            'sementara'    => $sementara,
        ])->setPaper('a4', 'portrait');

        $gelombang = ($filter['gelombang'] ?? '') !== '' && ($filter['gelombang'] ?? null) !== null ? '_Gelombang_' . $filter['gelombang'] : '';
        $dosen = $hanyaDosenId ? '_' . ($dosenRincian->first()['nama_dosen'] ?? '') : '';
        $filename = trim(preg_replace('/[^\w\.-]+/', '_', ($hanyaDosenId ? 'SK_' : 'Pengajuan_SK_') . ucfirst($mode) . ($sementara ? '_SEMENTARA' : '') . "_{$namaPeriode}{$gelombang}{$dosen}"), '_') . '.pdf';

        return ['pdf' => $pdf, 'filename' => $filename];
    }

    private static function kopSuratBase64(): string
    {
        $path = public_path('images/kop_surat.png');

        return is_file($path)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($path))
            : '';
    }
}

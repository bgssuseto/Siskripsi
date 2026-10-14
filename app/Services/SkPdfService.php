<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Kaprodi;
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
 * SK hanya dihitung dari ujian skripsi (skripsi / sidang / jurnal) — sempro tidak.
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
    public static function sidangs(array $filter): Collection
    {
        $periodeId = $filter['periode_id'] ?? null;
        $gelombang = $filter['gelombang'] ?? null;

        return Sidang::query()
            ->when($periodeId, fn ($q) => $q->where('periode_id', $periodeId))
            ->when($gelombang !== null && $gelombang !== '', fn ($q) => $q->where('gelombang', $gelombang))
            ->when($filter['tanggal_mulai'] ?? null, fn ($q, $tgl) => $q->whereDate('tanggal_pendaftaran', '>=', $tgl))
            ->when($filter['tanggal_selesai'] ?? null, fn ($q, $tgl) => $q->whereDate('tanggal_pendaftaran', '<=', $tgl))
            ->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)
            ->orderByRaw('tanggal IS NULL')->orderBy('tanggal')->orderBy('jam')->orderBy('nama_mahasiswa')
            ->get();
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
     * @return array{pdf: \Barryvdh\DomPDF\PDF, filename: string}|null  null bila tidak ada data
     */
    public static function build(string $mode, array $filter, ?int $hanyaDosenId = null): ?array
    {
        $dosenRincian = self::dosenRincian(self::sidangs($filter), $mode, $hanyaDosenId);

        if ($dosenRincian->isEmpty()) {
            return null;
        }

        @set_time_limit(600);

        $namaPeriode  = Periode::find($filter['periode_id'] ?? null)?->nama_periode ?? 'Semua Periode';
        $tanggalSurat = now()->timezone('Asia/Jakarta');

        $pdf = Pdf::loadView('administrasi.sk.pdf', [
            'mode'         => $mode,
            'namaPeriode'  => $namaPeriode,
            'kegiatan'     => ['nama' => 'Sidang Skripsi', 'hal' => 'Skripsi'],
            'dosenRincian' => $dosenRincian,
            'tanggalSurat' => $tanggalSurat->copy()->locale('id')->translatedFormat('j F Y'),
            'kaprodi'      => rescue(fn () => Kaprodi::untukTanggal($tanggalSurat)?->nama, null, false),
            'kopBase64'    => self::kopSuratBase64(),
        ])->setPaper('a4', 'portrait');

        $gelombang = ($filter['gelombang'] ?? '') !== '' && ($filter['gelombang'] ?? null) !== null ? '_Gelombang_' . $filter['gelombang'] : '';
        $dosen = $hanyaDosenId ? '_' . ($dosenRincian->first()['nama_dosen'] ?? '') : '';
        $filename = trim(preg_replace('/[^\w\.-]+/', '_', ($hanyaDosenId ? 'SK_' : 'Pengajuan_SK_') . ucfirst($mode) . "_{$namaPeriode}{$gelombang}{$dosen}"), '_') . '.pdf';

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

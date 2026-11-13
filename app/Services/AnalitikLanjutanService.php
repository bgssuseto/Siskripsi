<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Sidang;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Analitik lintas periode untuk Koordinator/Kaprodi:
 *  - waktu tempuh Sempro → Sidang, perkiraan lama studi, dan waktu tunggu daftar → ujian;
 *  - mahasiswa yang "macet" di satu tahap;
 *  - beban bimbingan & menguji per dosen per semester (periode).
 * Hanya menghitung pendaftaran yang berkasnya disetujui (kecuali kategori macet
 * yang memang tentang berkas menunggu/ditolak).
 */
class AnalitikLanjutanService
{
    public const JENIS_SKRIPSI = ['skripsi', 'sidang', 'jurnal'];

    /** Ambang "macet" (hari). */
    public const BATAS_MENUNGGU_VERIFIKASI = 3;
    public const BATAS_BELUM_DIJADWALKAN  = 14;
    public const BATAS_DITOLAK            = 14;
    public const BATAS_TIDAK_LULUS        = 30;
    public const BATAS_SEMPRO_KE_SIDANG   = 180;
    /** Kategori "lulus sempro belum sidang" hanya melihat sempro 2 tahun terakhir. */
    public const JENDELA_SEMPRO_HARI      = 730;

    private Collection $semua;
    private Carbon $hariIni;

    public function __construct(?Carbon $hariIni = null)
    {
        $this->hariIni = ($hariIni ?? now('Asia/Jakarta'))->copy()->startOfDay();
        $this->semua = Sidang::query()->get([
            'id', 'nim', 'nama_mahasiswa', 'jenis_tugas_akhir', 'verifikasi_status', 'verifikasi_tanggal',
            'status_ujian', 'tanggal', 'tanggal_pendaftaran', 'periode_id', 'created_at',
            'dosen_pembimbing_utama_id', 'dosen_pembimbing_pendamping_id',
            'ketua_penguji_id', 'anggota_penguji_1_id', 'anggota_penguji_2_id',
        ]);
    }

    // ───────────────────────── Waktu tempuh & lama studi ─────────────────────────

    public function waktuTempuh(): array
    {
        $lulus = $this->semua->where('verifikasi_status', 'disetujui')->where('status_ujian', 'lulus')->whereNotNull('tanggal');
        $semproLulus = $lulus->where('jenis_tugas_akhir', 'sempro')->groupBy('nim')->map(fn ($g) => $g->min('tanggal'));
        $sidangLulus = $lulus->whereIn('jenis_tugas_akhir', self::JENIS_SKRIPSI)->groupBy('nim')->map(fn ($g) => $g->sortBy('tanggal')->first());

        // Sempro → Sidang (hari)
        $tempuh = $sidangLulus->map(function ($s, $nim) use ($semproLulus) {
            $sempro = $semproLulus->get($nim);
            return $sempro && $s->tanggal->gt($sempro) ? (int) $sempro->diffInDays($s->tanggal) : null;
        })->filter()->values();

        // Lama studi: perkiraan dari tahun angkatan di 4 digit awal NIM (masuk ±1 September).
        $tahunIni = (int) $this->hariIni->format('Y');
        $lamaStudi = $sidangLulus->map(function ($s) use ($tahunIni) {
            $angkatan = (int) substr((string) $s->nim, 0, 4);
            if ($angkatan < 2000 || $angkatan > $tahunIni) {
                return null;
            }
            $tahun = Carbon::create($angkatan, 9, 1)->diffInMonths($s->tanggal) / 12;
            return ($tahun >= 2 && $tahun <= 10) ? round($tahun, 2) : null;
        })->filter()->values();

        // Waktu tunggu daftar → ujian, 12 bulan terakhir.
        $sejak = $this->hariIni->copy()->subYear();
        $tunggu = fn (array $jenis) => $this->semua
            ->where('verifikasi_status', 'disetujui')
            ->whereIn('jenis_tugas_akhir', $jenis)
            ->filter(fn ($s) => $s->tanggal && $s->tanggal_pendaftaran && $s->tanggal->gte($sejak) && $s->tanggal->gte($s->tanggal_pendaftaran))
            ->map(fn ($s) => (int) $s->tanggal_pendaftaran->diffInDays($s->tanggal))
            ->values();

        return [
            'tempuh' => [
                'jumlah'     => $tempuh->count(),
                'rata_bulan' => $tempuh->isEmpty() ? null : round($tempuh->avg() / 30.44, 1),
                'median_bulan' => $tempuh->isEmpty() ? null : round($this->median($tempuh) / 30.44, 1),
                'distribusi' => $this->distribusi($tempuh->map(fn ($h) => $h / 30.44), [
                    '≤ 3 bulan' => [0, 3], '3–6 bulan' => [3, 6], '6–12 bulan' => [6, 12], '> 12 bulan' => [12, INF],
                ]),
            ],
            'lama_studi' => [
                'jumlah'     => $lamaStudi->count(),
                'rata_tahun' => $lamaStudi->isEmpty() ? null : round($lamaStudi->avg(), 1),
                'median_tahun' => $lamaStudi->isEmpty() ? null : round($this->median($lamaStudi), 1),
                'distribusi' => $this->distribusi($lamaStudi, [
                    '≤ 4 tahun' => [0, 4], '4–5 tahun' => [4, 5], '5–6 tahun' => [5, 6], '> 6 tahun' => [6, INF],
                ]),
            ],
            'tunggu' => [
                'sempro'  => $this->ringkasHari($tunggu(['sempro'])),
                'skripsi' => $this->ringkasHari($tunggu(self::JENIS_SKRIPSI)),
            ],
        ];
    }

    // ───────────────────────── Mahasiswa macet ─────────────────────────

    /**
     * @return array<string, array{judul: string, keterangan: string, warna: string, mahasiswa: Collection}>
     */
    public function macet(): array
    {
        $kategori = [
            'menunggu' => ['judul' => 'Berkas belum diverifikasi', 'keterangan' => 'Menunggu verifikasi lebih dari ' . self::BATAS_MENUNGGU_VERIFIKASI . ' hari', 'warna' => 'amber'],
            'belum_jadwal' => ['judul' => 'Disetujui, belum dijadwalkan', 'keterangan' => 'Lebih dari ' . self::BATAS_BELUM_DIJADWALKAN . ' hari sejak disetujui', 'warna' => 'sky'],
            'ditolak' => ['judul' => 'Ditolak, belum daftar ulang', 'keterangan' => 'Lebih dari ' . self::BATAS_DITOLAK . ' hari sejak ditolak', 'warna' => 'rose'],
            'tidak_lulus' => ['judul' => 'Tidak lulus, belum daftar ulang', 'keterangan' => 'Lebih dari ' . self::BATAS_TIDAK_LULUS . ' hari sejak ujian', 'warna' => 'rose'],
            'sempro_ke_sidang' => ['judul' => 'Lulus Sempro, belum daftar Sidang', 'keterangan' => 'Lebih dari 6 bulan sejak Sempro (Sempro 2 tahun terakhir)', 'warna' => 'violet'],
        ];
        $daftar = array_fill_keys(array_keys($kategori), []);

        foreach ($this->semua->groupBy('nim') as $nim => $records) {
            if (!$nim) {
                continue;
            }
            $skripsi = $records->whereIn('jenis_tugas_akhir', self::JENIS_SKRIPSI)->sortByDesc('id')->first();
            $sempro = $records->where('jenis_tugas_akhir', 'sempro')->sortByDesc('id')->first();
            $aktif = $skripsi ?? $sempro;
            if (!$aktif) {
                continue;
            }

            $tahap = $aktif->jenis_tugas_akhir === 'sempro' ? 'Sempro' : 'Sidang Skripsi';
            $sejak = null;
            $kunci = null;

            if (in_array($aktif->verifikasi_status, [null, '', 'menunggu'], true)) {
                $sejak = $aktif->created_at ?? $aktif->tanggal_pendaftaran;
                $kunci = $this->lewat($sejak, self::BATAS_MENUNGGU_VERIFIKASI) ? 'menunggu' : null;
            } elseif ($aktif->verifikasi_status === 'ditolak') {
                $sejak = $aktif->verifikasi_tanggal ?? $aktif->created_at;
                $kunci = $this->lewat($sejak, self::BATAS_DITOLAK) ? 'ditolak' : null;
            } elseif (!$aktif->tanggal) {
                $sejak = $aktif->verifikasi_tanggal ?? $aktif->created_at;
                $kunci = $this->lewat($sejak, self::BATAS_BELUM_DIJADWALKAN) ? 'belum_jadwal' : null;
            } elseif ($aktif->status_ujian === 'tidak_lulus') {
                $sejak = $aktif->tanggal;
                $kunci = $this->lewat($sejak, self::BATAS_TIDAK_LULUS) ? 'tidak_lulus' : null;
            } elseif (!$skripsi && $aktif->status_ujian === 'lulus'
                && $this->lewat($aktif->tanggal, self::BATAS_SEMPRO_KE_SIDANG)
                && !$this->lewat($aktif->tanggal, self::JENDELA_SEMPRO_HARI)) {
                $sejak = $aktif->tanggal;
                $kunci = 'sempro_ke_sidang';
            }

            if ($kunci) {
                $daftar[$kunci][] = [
                    'nim'   => $nim,
                    'nama'  => $aktif->nama_mahasiswa,
                    'tahap' => $tahap,
                    'sejak' => Carbon::parse($sejak),
                    'hari'  => (int) Carbon::parse($sejak)->startOfDay()->diffInDays($this->hariIni),
                ];
            }
        }

        foreach ($kategori as $kunci => &$k) {
            $k['mahasiswa'] = collect($daftar[$kunci])->sortByDesc('hari')->values();
        }

        return $kategori;
    }

    // ───────────────────────── Beban dosen per semester ─────────────────────────

    /**
     * @return array{periodes: Collection, baris: Collection}
     */
    public function bebanPerSemester(int $jumlahPeriode = 4): array
    {
        $periodes = Periode::orderByDesc('id')->limit($jumlahPeriode)->get()->sortBy('id')->values();
        $sidangs = $this->semua->where('verifikasi_status', 'disetujui')->whereIn('periode_id', $periodes->pluck('id'));

        $nama = Dosen::withTrashed()->excludingSuperAdminPlaceholder()->pluck('nama_dosen', 'id');

        $baris = $nama->map(function ($namaDosen, $id) use ($periodes, $sidangs) {
            $perPeriode = $periodes->mapWithKeys(function ($p) use ($id, $sidangs) {
                $diPeriode = $sidangs->where('periode_id', $p->id);
                $bimbing = $diPeriode->filter(fn ($s) => (int) $s->dosen_pembimbing_utama_id === $id || (int) $s->dosen_pembimbing_pendamping_id === $id)->count();
                // Penguji yang juga pembimbing pada ujian yang sama tidak dihitung dua kali.
                $uji = $diPeriode->filter(function ($s) use ($id) {
                    $penguji = in_array($id, [(int) $s->ketua_penguji_id, (int) $s->anggota_penguji_1_id, (int) $s->anggota_penguji_2_id], true);
                    $pembimbing = in_array($id, [(int) $s->dosen_pembimbing_utama_id, (int) $s->dosen_pembimbing_pendamping_id], true);
                    return $penguji && !$pembimbing;
                })->count();

                return [$p->id => ['bimbing' => $bimbing, 'uji' => $uji, 'total' => $bimbing + $uji]];
            });

            return ['id' => $id, 'nama' => $namaDosen, 'per_periode' => $perPeriode, 'total' => $perPeriode->sum('total')];
        })->filter(fn ($r) => $r['total'] > 0)->sortByDesc('total')->values();

        return ['periodes' => $periodes, 'baris' => $baris];
    }

    // ───────────────────────── helper ─────────────────────────

    private function lewat($sejak, int $hari): bool
    {
        return $sejak && Carbon::parse($sejak)->startOfDay()->diffInDays($this->hariIni, false) > $hari;
    }

    private function median(Collection $nilai): float
    {
        $urut = $nilai->sort()->values();
        $n = $urut->count();
        return $n % 2 ? (float) $urut[intdiv($n, 2)] : ($urut[$n / 2 - 1] + $urut[$n / 2]) / 2;
    }

    private function ringkasHari(Collection $hari): array
    {
        return [
            'jumlah' => $hari->count(),
            'rata'   => $hari->isEmpty() ? null : (int) round($hari->avg()),
            'median' => $hari->isEmpty() ? null : (int) round($this->median($hari)),
        ];
    }

    /** @param array<string, array{0: float, 1: float}> $kelompok rentang [bawah, atas) */
    private function distribusi(Collection $nilai, array $kelompok): array
    {
        $total = max(1, $nilai->count());
        $hasil = [];
        foreach ($kelompok as $label => [$bawah, $atas]) {
            $jumlah = $nilai->filter(fn ($v) => $v > $bawah && $v <= $atas || ($bawah == 0 && $v == 0))->count();
            $hasil[] = ['label' => $label, 'jumlah' => $jumlah, 'persen' => (int) round($jumlah / $total * 100)];
        }
        return $hasil;
    }
}

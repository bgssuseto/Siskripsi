<?php

namespace App\Services;

use App\Mail\PemberitahuanMail;
use App\Models\Dosen;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\Sidang;
use App\Models\User;
use App\Services\FcmNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/**
 * Notifikasi email otomatis:
 * - ke mahasiswa saat berkas DISETUJUI / DITOLAK,
 * - ke mahasiswa saat jadwal ujian DITETAPKAN / BERUBAH,
 * - pengingat H-1 ke mahasiswa & dosen yang terlibat.
 *
 * Perubahan tidak langsung dikirim: dicatat dulu lalu dikirim oleh penjadwal setelah
 * JEDA_MENIT tanpa perubahan lagi — supaya saat koordinator menggeser-geser jadwal
 * (plotting) mahasiswa hanya menerima SATU email berisi jadwal final.
 * Dimatikan dengan NOTIFIKASI_EMAIL=false di .env.
 */
class NotifikasiService
{
    public const JEDA_MENIT = 15;
    private const KUNCI_TERTUNDA = 'notifikasi:tertunda';

    /**
     * Notifikasi otomatis dikirim oleh penjadwal — tanpa penjadwal tidak ada yang
     * memprosesnya, jadi jangan ditampung (akan menumpuk tanpa pernah terkirim).
     */
    public static function aktif(): bool
    {
        return (bool) config('app.notifikasi_email', true) && (bool) config('app.penjadwal_aktif', false);
    }

    /** Dipanggil dari event model Sidang::updated. */
    public static function catatPerubahan(Sidang $s): void
    {
        if (!self::aktif()) {
            return;
        }
        if ($s->wasChanged('verifikasi_status') && in_array($s->verifikasi_status, ['disetujui', 'ditolak'], true)) {
            self::tunda('verifikasi', $s->id);
        }
        if ($s->tanggal && $s->wasChanged(['tanggal', 'jam', 'ruang_id', 'tanggal_pendamping', 'jam_pendamping', 'ruang_id_pendamping'])) {
            self::tunda('jadwal', $s->id);
        }
    }

    private static function tunda(string $jenis, int $id): void
    {
        $antrian = Cache::get(self::KUNCI_TERTUNDA, []);
        $antrian["{$jenis}:{$id}"] = now()->timestamp;
        Cache::forever(self::KUNCI_TERTUNDA, $antrian);
    }

    /** Kirim pemberitahuan yang sudah "tenang" selama JEDA_MENIT. Mengembalikan jumlah email terkirim. */
    public static function prosesTertunda(int $jedaMenit = self::JEDA_MENIT): int
    {
        $antrian = Cache::get(self::KUNCI_TERTUNDA, []);
        if (!$antrian) {
            return 0;
        }

        $batas = now()->subMinutes($jedaMenit)->timestamp;
        $terkirim = 0;
        $siap = array_filter($antrian, fn ($ts) => $ts <= $batas);

        foreach (array_keys($siap) as $kunci) {
            [$jenis, $id] = explode(':', $kunci, 2);
            $sidang = Sidang::with(['ruang', 'ruangPendamping'])->find((int) $id);
            if ($sidang && self::aktif()) {
                $terkirim += $jenis === 'verifikasi' ? self::kirimVerifikasi($sidang) : self::kirimJadwal($sidang);
            }
        }

        // Baca ulang supaya perubahan yang masuk selama proses tidak hilang.
        $terbaru = Cache::get(self::KUNCI_TERTUNDA, []);
        foreach ($siap as $kunci => $ts) {
            if (($terbaru[$kunci] ?? null) === $ts) {
                unset($terbaru[$kunci]);
            }
        }
        Cache::forever(self::KUNCI_TERTUNDA, $terbaru);

        return $terkirim;
    }

    private static function kirimVerifikasi(Sidang $s): int
    {
        $user = self::userMahasiswa($s);
        $email = $user?->email;
        $tanda = 'notifikasi:terkirim:verifikasi:' . $s->id;
        $isi = $s->verifikasi_status . '|' . $s->verifikasi_komentar;
        if (!$email || !in_array($s->verifikasi_status, ['disetujui', 'ditolak'], true) || Cache::get($tanda) === md5($isi)) {
            return 0;
        }

        $jenis = self::jenisPanjang($s);
        $disetujui = $s->verifikasi_status === 'disetujui';
        $paragraf = $disetujui
            ? ["Berkas pendaftaran {$jenis} Anda telah DIPERIKSA dan DISETUJUI oleh Koordinator Skripsi.",
               'Langkah berikutnya adalah penjadwalan ujian. Anda akan menerima email lagi setelah jadwal ditetapkan.']
            : ["Berkas pendaftaran {$jenis} Anda DITOLAK dan perlu diperbaiki.",
               'Silakan perbaiki sesuai catatan di bawah, lalu unggah ulang berkas melalui sistem.'];

        $rincian = [array_filter([
            'Nama'    => $s->nama_mahasiswa,
            'NIM'     => $s->nim,
            'Ujian'   => $jenis,
            'Status'  => $disetujui ? 'Disetujui' : 'Ditolak',
            'Catatan' => $s->verifikasi_komentar ?: null,
        ])];

        $ok = self::kirim($email, new PemberitahuanMail(
            ($disetujui ? 'Berkas Disetujui' : 'Berkas Perlu Diperbaiki') . " - Pendaftaran {$jenis}",
            "Halo {$s->nama_mahasiswa},",
            $paragraf,
            $rincian,
            'Buka Sistem Skripsi',
            self::url('mahasiswa.dashboard'),
            $disetujui ? '#10b981' : '#e11d48',
        ));

        self::push(
            $user,
            $disetujui ? 'Berkas Disetujui' : 'Berkas Perlu Diperbaiki',
            $disetujui
                ? "Berkas pendaftaran {$jenis} Anda telah disetujui koordinator."
                : "Berkas pendaftaran {$jenis} Anda ditolak" . ($s->verifikasi_komentar ? ": {$s->verifikasi_komentar}" : '.'),
            ['type' => 'verifikasi', 'sidang_id' => (string) $s->id],
        );

        if ($ok) {
            Cache::put($tanda, md5($isi), now()->addMonths(6));
        }
        return (int) $ok;
    }

    private static function kirimJadwal(Sidang $s): int
    {
        $user = self::userMahasiswa($s);
        $email = $user?->email;
        if (!$email || !$s->tanggal) {
            return 0;
        }
        $isi = implode('|', [$s->tanggal->format('Y-m-d'), $s->jam, $s->ruang_id, $s->tanggal_pendamping?->format('Y-m-d'), $s->jam_pendamping, $s->ruang_id_pendamping]);
        $tanda = 'notifikasi:terkirim:jadwal:' . $s->id;
        $sebelumnya = Cache::get($tanda);
        if ($sebelumnya === md5($isi) || $s->tanggal->lt(now()->startOfDay())) {
            return 0;
        }

        $jenis = self::jenisPanjang($s);
        $diperbarui = (bool) $sebelumnya;
        $paragraf = [$diperbarui
            ? "Jadwal {$jenis} Anda telah DIPERBARUI. Mohon perhatikan jadwal terbaru di bawah ini."
            : "Jadwal {$jenis} Anda telah ditetapkan. Mohon hadir tepat waktu dan mempersiapkan presentasi serta berkas yang diperlukan."];

        $ok = self::kirim($email, new PemberitahuanMail(
            ($diperbarui ? 'Perubahan Jadwal' : 'Jadwal') . " {$jenis} - " . self::tanggal($s->tanggal),
            "Halo {$s->nama_mahasiswa},",
            $paragraf,
            self::rincianJadwal($s),
            'Lihat Jadwal di Sistem',
            self::url('mahasiswa.dashboard'),
            '#4f46e5',
        ));

        $jamLabel = $s->jam ? " pukul {$s->jam} WIB" : '';
        self::push(
            $user,
            ($diperbarui ? 'Perubahan Jadwal' : 'Jadwal') . " {$jenis}",
            ($diperbarui ? "Jadwal {$jenis} Anda diperbarui: " : "Jadwal {$jenis} Anda telah ditetapkan: ")
                . self::tanggal($s->tanggal) . $jamLabel . ($s->ruang ? ", Ruang {$s->ruang->kode_ruangan}" : ''),
            ['type' => 'jadwal', 'sidang_id' => (string) $s->id],
        );

        if ($ok) {
            Cache::put($tanda, md5($isi), now()->addMonths(6));
        }
        return (int) $ok;
    }

    /** Pengingat ujian besok untuk mahasiswa & dosen. Hanya sekali per tanggal. */
    public static function kirimPengingatBesok(): int
    {
        if (!self::aktif()) {
            return 0;
        }
        $besok = now()->timezone('Asia/Jakarta')->addDay()->startOfDay();
        $tanda = 'notifikasi:pengingat:' . $besok->format('Y-m-d');
        if (Cache::has($tanda)) {
            return 0;
        }

        $sidangs = Sidang::with(['ruang', 'pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'])
            ->whereDate('tanggal', $besok->format('Y-m-d'))
            ->where(fn ($q) => $q->whereNull('verifikasi_status')->orWhere('verifikasi_status', '!=', 'ditolak'))
            ->get()
            ->sortBy(fn ($s) => (SidangConflictService::parseJamRange($s->jam)['start'] ?? 9999))
            ->values();

        $terkirim = 0;
        $perDosen = [];

        foreach ($sidangs as $s) {
            $user = self::userMahasiswa($s);
            if ($user?->email) {
                $terkirim += (int) self::kirim($user->email, new PemberitahuanMail(
                    "Pengingat: {$s->jenis_label} Anda besok, " . self::tanggal($s->tanggal),
                    "Halo {$s->nama_mahasiswa},",
                    ['Ini pengingat bahwa ujian Anda dijadwalkan BESOK. Pastikan Anda hadir 15 menit sebelum jadwal dan membawa kelengkapan yang diperlukan.'],
                    self::rincianJadwal($s),
                    'Lihat Jadwal di Sistem',
                    self::url('mahasiswa.dashboard'),
                    '#f59e0b',
                ));
            }
            $jamLabel = $s->jam ? " pukul {$s->jam} WIB" : '';
            self::push(
                $user,
                "Pengingat: {$s->jenis_label} besok",
                "Ujian Anda dijadwalkan BESOK, " . self::tanggal($s->tanggal) . $jamLabel
                    . ($s->ruang ? ", Ruang {$s->ruang->kode_ruangan}" : '') . '.',
                ['type' => 'pengingat_h1', 'sidang_id' => (string) $s->id],
            );
            foreach (self::peranDosen($s) as $dosenId => [$dosen, $peran]) {
                $perDosen[$dosenId]['dosen'] = $dosen;
                $perDosen[$dosenId]['ujian'][] = [$s, $peran];
            }
        }

        foreach ($perDosen as ['dosen' => $dosen, 'ujian' => $ujian]) {
            $jumlah = count($ujian);
            if ($dosen->email) {
                $rincian = array_map(fn ($u) => array_filter([
                    'Mahasiswa' => $u[0]->nama_mahasiswa . ' (' . $u[0]->nim . ')',
                    'Ujian'     => self::jenisPanjang($u[0]),
                    'Jam'       => $u[0]->jam ? $u[0]->jam . ' WIB' : 'Belum ditentukan',
                    'Ruang'     => $u[0]->ruang?->kode_ruangan,
                    'Peran Anda' => $u[1],
                ]), $ujian);
                $terkirim += (int) self::kirim($dosen->email, new PemberitahuanMail(
                    "Pengingat: {$jumlah} jadwal ujian besok, " . self::tanggal($besok),
                    "Yth. Bapak/Ibu {$dosen->nama_dosen},",
                    ["Dengan hormat, kami mengingatkan bahwa Bapak/Ibu dijadwalkan pada {$jumlah} ujian tugas akhir BESOK, " . self::tanggal($besok) . ', dengan rincian berikut.'],
                    $rincian,
                    'Buka Jadwal Saya',
                    self::url('dosen.dashboard'),
                    '#f59e0b',
                ));
            }
            self::push(
                self::userForDosen($dosen),
                "Pengingat: {$jumlah} jadwal ujian besok",
                "Bapak/Ibu dijadwalkan pada {$jumlah} ujian tugas akhir BESOK, " . self::tanggal($besok) . '.',
                ['type' => 'pengingat_h1_dosen'],
            );
        }

        Cache::put($tanda, true, now()->addDays(3));
        return $terkirim;
    }

    /**
     * Pengingat gelombang pendaftaran Sempro/Skripsi (periode aktif saja):
     * - saat gelombang baru DIBUKA (tanggal_mulai = hari ini) → ke mahasiswa
     *   yang belum punya pendaftaran (bukan ditolak) di gelombang/jenis ini.
     * - H-3 & H-1 sebelum DITUTUP (tanggal_selesai) → ke mahasiswa yang sama,
     *   plus ke mahasiswa yang SUDAH daftar tapi berkasnya masih menunggu
     *   verifikasi, supaya sempat diperiksa sebelum gelombang ini tutup.
     */
    public static function kirimPengingatGelombang(): int
    {
        if (!self::aktif()) {
            return 0;
        }

        $periode = Periode::where('aktif', true)->first();
        if (!$periode) {
            return 0;
        }

        $today = now()->timezone('Asia/Jakarta')->startOfDay();
        $h3 = $today->copy()->addDays(3);
        $h1 = $today->copy()->addDays(1);
        $terkirim = 0;

        foreach (PendaftaranPeriode::where('periode_id', $periode->id)->get() as $wave) {
            if ($wave->tanggal_mulai?->isSameDay($today)) {
                $terkirim += self::pengingatBelumDaftar($wave, $periode, 'buka');
            }
            if ($wave->tanggal_selesai?->isSameDay($h3)) {
                $terkirim += self::pengingatBelumDaftar($wave, $periode, 'tutup_h3');
                $terkirim += self::pengingatBerkasBelumLengkap($wave, $periode, 'h3');
            }
            if ($wave->tanggal_selesai?->isSameDay($h1)) {
                $terkirim += self::pengingatBelumDaftar($wave, $periode, 'tutup_h1');
                $terkirim += self::pengingatBerkasBelumLengkap($wave, $periode, 'h1');
            }
        }

        return $terkirim;
    }

    private static function pengingatBelumDaftar(PendaftaranPeriode $wave, Periode $periode, string $momen): int
    {
        $tanda = "notifikasi:gelombang-daftar:{$wave->id}:{$momen}";
        if (Cache::has($tanda)) {
            return 0;
        }

        $bucket = $wave->jenis === 'sempro' ? ['sempro'] : Sidang::SKRIPSI_BUCKET;
        $sudahDaftar = Sidang::where('periode_id', $periode->id)
            ->whereIn('jenis_tugas_akhir', $bucket)
            ->where('verifikasi_status', '!=', 'ditolak')
            ->pluck('nim');

        $mahasiswa = User::where('role', User::ROLE_MAHASISWA)
            ->where(fn ($q) => $q->whereNull('status_kelulusan')->orWhere('status_kelulusan', '!=', 'lulus'))
            ->whereNotNull('nim')
            ->whereNotIn('nim', $sudahDaftar)
            ->get();

        $jenisLabel = $wave->jenis === 'sempro' ? 'Seminar Proposal' : 'Skripsi';
        $terkirim = 0;

        foreach ($mahasiswa as $m) {
            if ($momen === 'buka') {
                $judul = "Pendaftaran {$jenisLabel} Dibuka";
                $isi = "Pendaftaran {$jenisLabel} Gelombang {$wave->gelombang} telah DIBUKA, " . self::tanggal($wave->tanggal_mulai) . ' s/d ' . self::tanggal($wave->tanggal_selesai) . '. Segera daftar melalui sistem.';
                $warna = '#10b981';
            } else {
                $judul = "Pendaftaran {$jenisLabel} Segera Ditutup";
                $isi = "Pendaftaran {$jenisLabel} Gelombang {$wave->gelombang} akan DITUTUP " . self::tanggal($wave->tanggal_selesai) . '. Anda belum terdaftar — segera daftar sebelum ditutup.';
                $warna = '#e11d48';
            }

            if ($m->email) {
                $terkirim += (int) self::kirim($m->email, new PemberitahuanMail(
                    $judul, "Halo {$m->name},", [$isi], [], 'Buka Sistem Skripsi', self::url('mahasiswa.dashboard'), $warna,
                ));
            }
            self::push($m, $judul, $isi, ['type' => 'pendaftaran_gelombang', 'wave_id' => (string) $wave->id, 'momen' => $momen]);
        }

        Cache::put($tanda, true, now()->addDays(10));
        return $terkirim;
    }

    private static function pengingatBerkasBelumLengkap(PendaftaranPeriode $wave, Periode $periode, string $momen): int
    {
        $tanda = "notifikasi:gelombang-berkas:{$wave->id}:{$momen}";
        if (Cache::has($tanda)) {
            return 0;
        }

        $bucket = $wave->jenis === 'sempro' ? ['sempro'] : Sidang::SKRIPSI_BUCKET;
        $belumLengkap = Sidang::where('periode_id', $periode->id)
            ->whereIn('jenis_tugas_akhir', $bucket)
            ->where('gelombang', $wave->gelombang)
            ->where(fn ($q) => $q->whereNull('verifikasi_status')->orWhere('verifikasi_status', 'menunggu'))
            ->get();

        $jenisLabel = $wave->jenis === 'sempro' ? 'Seminar Proposal' : 'Skripsi';
        $terkirim = 0;

        foreach ($belumLengkap as $s) {
            $user = self::userMahasiswa($s);
            if (!$user) {
                continue;
            }

            $judul = "Lengkapi Berkas Pendaftaran {$jenisLabel}";
            $isi = "Berkas pendaftaran {$jenisLabel} Anda masih menunggu verifikasi koordinator, dan gelombang pendaftaran ini akan ditutup " . self::tanggal($wave->tanggal_selesai) . '. Pastikan berkas Anda sudah lengkap.';

            if ($user->email) {
                $terkirim += (int) self::kirim($user->email, new PemberitahuanMail(
                    $judul, "Halo {$s->nama_mahasiswa},", [$isi], [], 'Buka Sistem Skripsi', self::url('mahasiswa.dashboard'), '#f59e0b',
                ));
            }
            self::push($user, $judul, $isi, ['type' => 'berkas_belum_lengkap', 'sidang_id' => (string) $s->id]);
        }

        Cache::put($tanda, true, now()->addDays(10));
        return $terkirim;
    }

    // ── Pembantu ──

    private static function kirim(string $email, PemberitahuanMail $mail): bool
    {
        try {
            Mail::to($email)->send($mail);
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    private static function emailMahasiswa(Sidang $s): ?string
    {
        return self::userMahasiswa($s)?->email;
    }

    private static function userMahasiswa(Sidang $s): ?User
    {
        if (!$s->nim) {
            return null;
        }
        return User::where('nim', $s->nim)->where('role', User::ROLE_MAHASISWA)->first();
    }

    private static function userForDosen(Dosen $d): ?User
    {
        return User::where('dosen_id', $d->id)->first();
    }

    /** Push notification ke satu user — no-op kalau FCM belum dikonfigurasi atau user tidak punya device terdaftar. */
    private static function push(?User $user, string $title, string $body, array $data = []): void
    {
        if (!$user) {
            return;
        }
        app(FcmNotificationService::class)->sendToUser($user, $title, $body, $data);
    }

    /** @return array<int, array{0: Dosen, 1: string}> */
    private static function peranDosen(Sidang $s): array
    {
        $peran = [];
        $tambah = function (?Dosen $d, string $label) use (&$peran) {
            if ($d && $d->nidn !== Dosen::NIDN_SUPER_ADMIN_PLACEHOLDER) {
                $peran[$d->id] = [$d, isset($peran[$d->id]) ? $peran[$d->id][1] . ' & ' . $label : $label];
            }
        };
        if ($s->jenis_tugas_akhir === 'sempro') {
            $tambah($s->pembimbingUtama, 'Pembimbing Utama');
            $tambah($s->pembimbingPendamping, 'Pembimbing Pendamping');
        } else {
            $tambah($s->ketuaPenguji, 'Ketua Penguji');
            $tambah($s->anggotaPenguji1, 'Anggota Penguji 1');
            $tambah($s->anggotaPenguji2, 'Anggota Penguji 2');
        }
        return $peran;
    }

    /** @return array<int, array<string,string>> */
    private static function rincianJadwal(Sidang $s): array
    {
        $utama = array_filter([
            'Ujian'   => self::jenisPanjang($s),
            'Judul'   => $s->judul_skripsi,
            'Hari, tanggal' => self::tanggal($s->tanggal),
            'Jam'     => $s->jam ? $s->jam . ' WIB' : 'Belum ditentukan',
            'Ruang'   => $s->ruang ? trim($s->ruang->kode_ruangan . ' ' . ($s->ruang->nama_ruangan ? '(' . $s->ruang->nama_ruangan . ')' : '')) : 'Belum ditentukan',
        ]);
        $blok = [$utama];
        if ($s->jadwal_terpisah && $s->tanggal_pendamping) {
            $blok[] = array_filter([
                'Sesi'    => 'Bersama Pembimbing Pendamping',
                'Hari, tanggal' => self::tanggal($s->tanggal_pendamping),
                'Jam'     => $s->jam_pendamping ? $s->jam_pendamping . ' WIB' : null,
                'Ruang'   => $s->ruangPendamping?->kode_ruangan,
            ]);
        }
        return $blok;
    }

    private static function jenisPanjang(Sidang $s): string
    {
        return match ($s->jenis_tugas_akhir) {
            'sempro' => 'Seminar Proposal',
            'jurnal' => 'Sidang Jurnal',
            default  => 'Sidang Skripsi',
        };
    }

    private static function tanggal($t): string
    {
        return Carbon::parse($t)->locale('id')->translatedFormat('l, j F Y');
    }

    private static function url(string $route): ?string
    {
        return Route::has($route) ? route($route) : null;
    }
}

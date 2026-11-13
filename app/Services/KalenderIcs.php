<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Sidang;
use App\Support\PeranDosen;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Feed iCalendar (RFC 5545) jadwal menguji/membimbing seorang dosen, untuk
 * dilanggan dari Google Calendar, kalender iPhone/Android, atau Outlook.
 * Kalender klien mengambil ulang feed ini secara berkala, jadi perubahan
 * jadwal (geser/batal) ikut terbarui otomatis.
 */
class KalenderIcs
{
    private const ZONA = 'Asia/Jakarta';

    /** Jadwal sejauh ini ke belakang yang masih dimuat (biar feed tetap ringan). */
    private const BULAN_KE_BELAKANG = 6;

    public static function untukDosen(Dosen $dosen): string
    {
        $baris = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Teknik Informatika UMK//SiSkripsi//ID',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . self::teks('Jadwal Ujian TI UMK - ' . $dosen->nama_dosen),
            'X-WR-TIMEZONE:' . self::ZONA,
            'REFRESH-INTERVAL;VALUE=DURATION:PT1H',
            'X-PUBLISHED-TTL:PT1H',
        ];

        foreach (self::sesiDosen($dosen) as $sesi) {
            array_push($baris, ...self::event($sesi));
        }

        $baris[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([self::class, 'lipat'], $baris)) . "\r\n";
    }

    /**
     * Semua sesi ujian dosen ini (pendaftaran disetujui & sudah terjadwal).
     * Sempro berjadwal terpisah: Pembimbing Pendamping mendapat sesi pendampingnya sendiri.
     */
    public static function sesiDosen(Dosen $dosen): Collection
    {
        $id = $dosen->id;
        $batas = now(self::ZONA)->subMonths(self::BULAN_KE_BELAKANG)->startOfDay()->toDateString();

        $sidangs = Sidang::with(['ruang', 'ruangPendamping', 'pembimbingUtama', 'pembimbingPendamping', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'])
            ->terverifikasi()
            ->where(function ($q) use ($id) {
                foreach (['dosen_pembimbing_utama_id', 'dosen_pembimbing_pendamping_id', 'ketua_penguji_id', 'anggota_penguji_1_id', 'anggota_penguji_2_id'] as $kolom) {
                    $q->orWhere($kolom, $id);
                }
            })
            ->where(function ($q) use ($batas) {
                $q->whereDate('tanggal', '>=', $batas)->orWhereDate('tanggal_pendamping', '>=', $batas);
            })
            ->get();

        return $sidangs->flatMap(function (Sidang $s) use ($id) {
            $peran = PeranDosen::untuk($s, $id);
            if (!$peran) {
                return [];
            }

            $pakaiSesiPendamping = $s->jenis_tugas_akhir === 'sempro'
                && $s->isJadwalTerpisah()
                && (int) $s->dosen_pembimbing_pendamping_id === $id
                && (int) $s->dosen_pembimbing_utama_id !== $id;

            $sesi = $pakaiSesiPendamping
                ? ['kunci' => 'pendamping', 'tanggal' => $s->tanggal_pendamping, 'jam' => $s->jam_pendamping, 'ruang' => $s->ruangPendamping]
                : ['kunci' => 'utama', 'tanggal' => $s->tanggal, 'jam' => $s->jam, 'ruang' => $s->ruang];

            return $sesi['tanggal'] ? [$sesi + ['sidang' => $s, 'peran' => $peran]] : [];
        })->sortBy(fn ($sesi) => $sesi['tanggal']->format('Y-m-d') . ($sesi['jam'] ?? ''))->values();
    }

    private static function event(array $sesi): array
    {
        /** @var Sidang $s */
        $s = $sesi['sidang'];
        $jenis = match ($s->jenis_tugas_akhir) {
            'sempro' => 'Seminar Proposal',
            'jurnal' => 'Sidang Jurnal',
            default  => 'Sidang Skripsi',
        };
        $peran = implode(', ', $sesi['peran']);
        $ruang = $sesi['ruang'] ? trim($sesi['ruang']->kode_ruangan . ' - ' . $sesi['ruang']->nama_ruangan, ' -') : 'Ruang belum ditentukan';

        $tgl = Carbon::parse($sesi['tanggal']->format('Y-m-d'), self::ZONA);
        $rentang = SidangConflictService::parseJamRange($sesi['jam']);
        if ($rentang) {
            $mulai = $tgl->copy()->addMinutes($rentang['start'])->utc()->format('Ymd\THis\Z');
            $selesai = $tgl->copy()->addMinutes($rentang['end'])->utc()->format('Ymd\THis\Z');
            $waktu = ["DTSTART:{$mulai}", "DTEND:{$selesai}"];
        } else {
            $waktu = ['DTSTART;VALUE=DATE:' . $tgl->format('Ymd'), 'DTEND;VALUE=DATE:' . $tgl->copy()->addDay()->format('Ymd')];
        }

        $dewan = $s->jenis_tugas_akhir === 'sempro'
            ? ['Pembimbing Utama' => $s->pembimbingUtama, 'Pembimbing Pendamping' => $s->pembimbingPendamping]
            : ['Pembimbing' => $s->pembimbingUtama, 'Ketua Penguji' => $s->ketuaPenguji, 'Anggota Penguji 1' => $s->anggotaPenguji1, 'Anggota Penguji 2' => $s->anggotaPenguji2];
        $deskripsi = "Peran Anda: {$peran}\nMahasiswa: {$s->nama_mahasiswa} ({$s->nim})\nJudul: {$s->judul_skripsi}\nJam: " . ($sesi['jam'] ?: '-') . "\nRuang: {$ruang}\n";
        foreach ($dewan as $label => $d) {
            $deskripsi .= "\n{$label}: " . ($d->nama_dosen ?? '-');
        }

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'siskripsi';

        return [
            'BEGIN:VEVENT',
            "UID:sidang-{$s->id}-{$sesi['kunci']}@{$host}",
            'DTSTAMP:' . ($s->updated_at ?? now())->copy()->utc()->format('Ymd\THis\Z'),
            'LAST-MODIFIED:' . ($s->updated_at ?? now())->copy()->utc()->format('Ymd\THis\Z'),
            ...$waktu,
            'SUMMARY:' . self::teks("{$jenis}: {$s->nama_mahasiswa} ({$peran})"),
            'LOCATION:' . self::teks($ruang),
            'DESCRIPTION:' . self::teks($deskripsi),
            'URL:' . route('dosen.kalender'),
            'STATUS:CONFIRMED',
            'TRANSP:OPAQUE',
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'DESCRIPTION:' . self::teks("{$jenis}: {$s->nama_mahasiswa}"),
            'TRIGGER:-PT1H',
            'END:VALARM',
            'END:VEVENT',
        ];
    }

    /** Escape nilai teks iCalendar (RFC 5545 §3.3.11). */
    private static function teks(?string $nilai): string
    {
        $nilai = str_replace(["\r\n", "\r"], "\n", (string) $nilai);

        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $nilai);
    }

    /** Lipat baris > 75 oktet (RFC 5545 §3.1) tanpa memotong karakter UTF-8. */
    private static function lipat(string $baris): string
    {
        if (strlen($baris) <= 75) {
            return $baris;
        }

        $hasil = [];
        $sekarang = '';
        foreach (mb_str_split($baris) as $huruf) {
            $batas = $hasil ? 74 : 75; // baris lanjutan diawali 1 spasi
            if (strlen($sekarang) + strlen($huruf) > $batas) {
                $hasil[] = $sekarang;
                $sekarang = '';
            }
            $sekarang .= $huruf;
        }
        $hasil[] = $sekarang;

        return implode("\r\n ", $hasil);
    }
}

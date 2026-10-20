<?php

namespace App\Models;

use App\Concerns\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sidang extends Model
{
    use HasFactory, HasHashedRouteKey;

    /**
     * jenis_tugas_akhir values that represent the skripsi track (as opposed to sempro).
     */
    public const SKRIPSI_BUCKET = ['skripsi', 'sidang', 'jurnal'];

    /**
     * Kategori jurnal yang bisa dipilih mahasiswa saat mendaftar jalur jurnal —
     * target/tingkat publikasi, bukan status terbit (itu ditandai lewat link_jurnal).
     */
    public const KATEGORI_JURNAL_OPTIONS = [
        'Sinta 1', 'Sinta 2', 'Sinta 3', 'Sinta 4',
        'Q1', 'Q2', 'Q3', 'Q4',
        'Seminar Internasional', 'Lainnya',
    ];

    protected $table = 'sidangs';

    protected $fillable = [
        'nim',
        'nama_mahasiswa',
        'judul_skripsi',
        'dosen_pembimbing_utama_id',
        'dosen_pembimbing_pendamping_id',
        'ketua_penguji_id',
        'anggota_penguji_1_id',
        'anggota_penguji_2_id',
        'ruang_id',
        'periode_id',
        'gelombang',
        'tanggal',
        'tanggal_pendaftaran',
        'jam',
        'jadwal_terpisah',
        'tanggal_pendamping',
        'jam_pendamping',
        'ruang_id_pendamping',
        'catatan_jadwal_terpisah',
        'jenis_tugas_akhir',
        'jalur_ta',
        'kategori_jurnal',
        'link_jurnal',
        'verifikasi_status',
        'verifikasi_komentar',
        'verifikasi_tanggal',
        'wa_joined_at',
        'status_ujian',
        'bukti_pembayaran',
        'no_wa_aktif',
        'file_persyaratan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_pendaftaran' => 'date',
        'verifikasi_tanggal' => 'datetime',
        'wa_joined_at' => 'datetime',
        'jadwal_terpisah' => 'boolean',
        'tanggal_pendamping' => 'date',
    ];

    /**
     * Nama mahasiswa selalu diseragamkan HURUF KAPITAL — baik saat mendaftar
     * maupun saat diubah admin/koordinator lewat Data Master — supaya konsisten
     * di semua tampilan/laporan tanpa perlu menormalkan tiap titik input satu-satu.
     */
    public function setNamaMahasiswaAttribute($value): void
    {
        $this->attributes['nama_mahasiswa'] = $value === null ? null : mb_strtoupper(trim($value));
    }

    /**
     * Judul skripsi/proposal juga selalu HURUF KAPITAL (dan spasi berlebih
     * dirapikan) — pendaftaran sempro & skripsi, edit admin, maupun impor.
     */
    public function setJudulSkripsiAttribute($value): void
    {
        $this->attributes['judul_skripsi'] = $value === null
            ? null
            : mb_strtoupper(preg_replace('/\s+/u', ' ', trim($value)));
    }

    /**
     * Booted model events.
     * Default anggota_penguji_2_id to dosen_pembimbing_utama_id if empty.
     * Only applies to skripsi/sidang, NOT sempro.
     */
    protected static function booted()
    {
        static::creating(function ($sidang) {
            static::syncPengujiFromPembimbing($sidang);
            $sidang->gelombang = static::computeGelombang($sidang);
            static::syncJalurTa($sidang);
        });

        static::updating(function ($sidang) {
            static::syncPengujiFromPembimbing($sidang);

            if ($sidang->isDirty(['periode_id', 'tanggal_pendaftaran', 'jenis_tugas_akhir'])) {
                $sidang->gelombang = static::computeGelombang($sidang);
            }

            static::syncJalurTa($sidang);
        });

        // Deleting a registration (so the student can re-register) previously
        // left its uploaded file_persyaratan/bukti_pembayaran orphaned on disk
        // forever — clean them up alongside the record. Bulk-delete controllers
        // must use ->get()->each->delete() rather than a query-builder ->delete()
        // for this hook to fire.
        static::deleting(function ($sidang) {
            foreach (['file_persyaratan', 'bukti_pembayaran'] as $field) {
                $path = $sidang->{$field};
                if ($path) {
                    $fullPath = public_path($path);
                    if (is_file($fullPath)) {
                        @unlink($fullPath);
                    }
                }
            }
        });
    }

    /**
     * Dewan penguji Sempro SELALU = pembimbing mahasiswa sendiri — sempro tidak
     * punya penguji eksternal sama sekali (lihat SemproController::store/update
     * yang tidak pernah menampilkan field pemilihan penguji). Disinkronkan PENUH
     * setiap kali disimpan (bukan cuma "kalau kosong"), supaya kalau pembimbingnya
     * berubah (mis. saat revisi pendaftaran) dewan pengujinya ikut berubah, tidak
     * nyangkut ke pembimbing lama. Pemetaan kolomnya sengaja mengikuti konvensi
     * tampilan yang sudah ada (undangan/pdf.blade.php, undangan/preview.blade.php):
     * Ketua Penguji <-> Pembimbing Utama, Penguji 1 <-> Pembimbing Pendamping.
     *
     * Untuk Skripsi/Sidang/Jurnal, HANYA anggota_penguji_2_id yang di-default ke
     * pembimbing utama SAAT MASIH KOSONG — ketua_penguji_id & anggota_penguji_1_id
     * memang sengaja diisi manual atau oleh Asisten Plotting dengan dosen lain.
     */
    protected static function syncPengujiFromPembimbing($sidang): void
    {
        if ($sidang->jenis_tugas_akhir === 'sempro') {
            $sidang->ketua_penguji_id = $sidang->dosen_pembimbing_utama_id;
            $sidang->anggota_penguji_1_id = $sidang->dosen_pembimbing_pendamping_id;
            $sidang->anggota_penguji_2_id = null;
            return;
        }

        if (empty($sidang->anggota_penguji_2_id) && !empty($sidang->dosen_pembimbing_utama_id)) {
            $sidang->anggota_penguji_2_id = $sidang->dosen_pembimbing_utama_id;
        }
    }

    /**
     * Hitung ulang & simpan kolom `gelombang` untuk seluruh Sidang pada
     * periode+jenis ini berdasarkan Master Gelombang (PendaftaranPeriode) SAAT
     * INI. Perlu dipanggil setiap kali sebuah gelombang dibuat/diubah/dihapus
     * (lihat PeriodeController) — computeGelombang() di bawah cuma jalan otomatis
     * saat Sidang itu sendiri dibuat/diupdate, TIDAK retroaktif untuk Sidang yang
     * sudah ada sebelum gelombangnya ditambahkan/diedit (mis. Asisten Plotting
     * tidak menemukan mahasiswa pada gelombang yang baru saja dibuat).
     *
     * @param  string  $waveJenis  'sempro' | 'skripsi' — persis seperti kolom
     *                              pendaftaran_periodes.jenis, bukan jenis_tugas_akhir.
     */
    public static function recomputeGelombangForPeriode(?int $periodeId, string $waveJenis): void
    {
        if (!$periodeId) {
            return;
        }

        $jenisList = $waveJenis === 'sempro' ? ['sempro'] : self::SKRIPSI_BUCKET;

        // Reset dulu, supaya mahasiswa yang tanggal daftarnya sudah tidak masuk
        // gelombang manapun (mis. gelombangnya baru dihapus/diperkecil rentangnya)
        // ikut ter-null-kan, bukan nyangkut ke gelombang lama.
        static::where('periode_id', $periodeId)
            ->whereIn('jenis_tugas_akhir', $jenisList)
            ->update(['gelombang' => null]);

        PendaftaranPeriode::where('periode_id', $periodeId)
            ->where('jenis', $waveJenis)
            ->orderBy('gelombang')
            ->get()
            ->each(function ($wave) use ($periodeId, $jenisList) {
                static::where('periode_id', $periodeId)
                    ->whereIn('jenis_tugas_akhir', $jenisList)
                    ->whereNotNull('tanggal_pendaftaran')
                    ->whereDate('tanggal_pendaftaran', '>=', $wave->tanggal_mulai)
                    ->whereDate('tanggal_pendaftaran', '<=', $wave->tanggal_selesai)
                    ->update(['gelombang' => $wave->gelombang]);
            });
    }

    /**
     * Untuk record skripsi/jurnal, jalur_ta selalu mengikuti jenis_tugas_akhir
     * (sudah eksplisit dipilih di form). Untuk sempro, jalur_ta datang dari
     * input terpisah (dipilih mahasiswa saat mendaftar) dan tidak diubah di sini.
     */
    protected static function syncJalurTa($sidang): void
    {
        if ($sidang->jenis_tugas_akhir === 'sempro') {
            return;
        }

        $sidang->jalur_ta = $sidang->jenis_tugas_akhir === 'jurnal' ? 'jurnal' : 'sidang';
    }

    /**
     * Determine which gelombang (wave) a Sidang belongs to, by matching its
     * periode_id + jenis bucket + tanggal_pendaftaran against the configured
     * PendaftaranPeriode (Master Gelombang) date ranges.
     */
    protected static function computeGelombang($sidang): ?int
    {
        if (empty($sidang->periode_id) || empty($sidang->tanggal_pendaftaran)) {
            return null;
        }

        $bucket = $sidang->jenis_tugas_akhir === 'sempro' ? 'sempro' : 'skripsi';
        $tanggal = $sidang->tanggal_pendaftaran instanceof \Carbon\Carbon
            ? $sidang->tanggal_pendaftaran->format('Y-m-d')
            : \Carbon\Carbon::parse($sidang->tanggal_pendaftaran)->format('Y-m-d');

        return PendaftaranPeriode::where('periode_id', $sidang->periode_id)
            ->where('jenis', $bucket)
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->value('gelombang');
    }

    /**
     * Relasi ke Dosen Pembimbing Utama
     */
    public function pembimbingUtama(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_pembimbing_utama_id');
    }

    /**
     * Relasi ke Dosen Pembimbing Pendamping
     */
    public function pembimbingPendamping(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_pembimbing_pendamping_id');
    }

    /**
     * Relasi ke Ketua Penguji
     */
    public function ketuaPenguji(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'ketua_penguji_id');
    }

    /**
     * Relasi ke Anggota Penguji 1
     */
    public function anggotaPenguji1(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'anggota_penguji_1_id');
    }

    /**
     * Relasi ke Anggota Penguji 2
     */
    public function anggotaPenguji2(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'anggota_penguji_2_id');
    }

    /**
     * Relasi ke Ruang Sidang
     */
    public function ruang(): BelongsTo
    {
        return $this->belongsTo(Ruang::class, 'ruang_id');
    }

    /**
     * Ruang untuk sesi Pembimbing Pendamping, hanya relevan kalau
     * jadwal_terpisah=true (lihat isJadwalTerpisah()).
     */
    public function ruangPendamping(): BelongsTo
    {
        return $this->belongsTo(Ruang::class, 'ruang_id_pendamping');
    }

    /**
     * Relasi ke Periode Akademik
     */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    /**
     * Accessor: label badge jenis tugas akhir.
     */
    public function getJenisBadgeClassAttribute(): string
    {
        return match ($this->jenis_tugas_akhir) {
            'sidang'  => 'badge-sidang',
            'skripsi' => 'badge-sidang',
            'jurnal'  => 'badge-jurnal',
            'sempro'  => 'badge-sempro',
            default   => 'badge-default',
        };
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis_tugas_akhir) {
            'sidang'  => 'Sidang Skripsi',
            'skripsi' => 'Sidang Skripsi',
            'sempro'  => 'Sempro',
            'jurnal'  => 'Jurnal / Artikel',
            default   => ucfirst($this->jenis_tugas_akhir ?? ''),
        };
    }

    public function getJalurLabelAttribute(): string
    {
        return match ($this->jalur_ta) {
            'sidang' => 'Skripsi Reguler',
            'jurnal' => 'Jurnal / Artikel',
            default  => 'Belum Ditentukan',
        };
    }

    /**
     * Apakah sidang/ujian ini SUDAH benar-benar berlangsung — dibandingkan
     * lengkap tanggal+jam selesainya (bukan cuma tanggal), supaya sidang hari
     * ini yang jamnya belum lewat tetap tampil "Proses Ujian", bukan langsung
     * "Selesai". Kalau `jam` kosong/tidak bisa diparse, fallback ke perbandingan
     * tanggal saja (jam akhir dianggap akhir hari itu).
     *
     * Untuk Sempro berjadwal_terpisah (lihat isJadwalTerpisah()): baru dianggap
     * selesai kalau KEDUA sesi (Pembimbing Utama & Pendamping) sudah berlangsung.
     */
    public function isExamEnded(): bool
    {
        if (empty($this->tanggal)) {
            return false;
        }

        $utamaEnded = self::hasSessionEnded($this->tanggal, $this->jam);

        if (!$this->jadwal_terpisah) {
            return $utamaEnded;
        }

        return $utamaEnded && $this->isSesiPendampingEnded();
    }

    public function isJadwalTerpisah(): bool
    {
        return (bool) $this->jadwal_terpisah;
    }

    /**
     * Khusus Sempro berjadwal_terpisah: apakah sesi Pembimbing Pendamping
     * (tanggal_pendamping/jam_pendamping) sudah berlangsung — logika sama
     * seperti isExamEnded() tapi untuk sesi keduanya.
     */
    public function isSesiPendampingEnded(): bool
    {
        if (empty($this->tanggal_pendamping)) {
            return false;
        }

        return self::hasSessionEnded($this->tanggal_pendamping, $this->jam_pendamping);
    }

    private static function hasSessionEnded($tanggal, ?string $jam): bool
    {
        $now = now()->timezone('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        $jadwal = $tanggal->format('Y-m-d');

        if ($jadwal < $today) {
            return true;
        }

        if ($jadwal > $today) {
            return false;
        }

        // Hari ini — cek jam selesainya betul-betul sudah lewat.
        $jamRange = \App\Services\SidangConflictService::parseJamRange($jam);
        if (!$jamRange) {
            // Tidak ada jam tercatat — anggap belum selesai selama masih hari ini.
            return false;
        }

        $examEndsAt = $tanggal->copy()->timezone('Asia/Jakarta')->startOfDay()->addMinutes($jamRange['end']);

        return $now->greaterThan($examEndsAt);
    }

    /**
     * Get the schedule status badge HTML — termasuk badge tanggal, jam, dan
     * ruang saat sidang sudah terjadwal, supaya info lengkap terlihat tanpa
     * harus melihat kolom lain. Dipakai di semua role (admin/koordinator,
     * penjadwalan, dosen).
     */
    public function getJadwalStatusHtml(): string
    {
        if (empty($this->tanggal)) {
            return '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200 whitespace-nowrap dark:bg-rose-500/15 dark:text-rose-200 dark:border-rose-500/30">● Belum Plotting</span>';
        }

        if ($this->jadwal_terpisah) {
            return $this->getJadwalStatusHtmlTerpisah();
        }

        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $jadwal = $this->tanggal->format('Y-m-d');

        $detailBadges = '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap dark:bg-indigo-500/15 dark:text-indigo-200 dark:border-indigo-500/30">📅 ' . e($this->tanggal->locale('id')->translatedFormat('d M Y')) . '</span>';
        if (!empty($this->jam)) {
            $detailBadges .= '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap dark:bg-indigo-500/15 dark:text-indigo-200 dark:border-indigo-500/30">🕐 ' . e($this->jam) . '</span>';
        }
        $ruangKode = $this->ruang->kode_ruangan ?? null;
        if (!empty($ruangKode)) {
            $detailBadges .= '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap dark:bg-indigo-500/15 dark:text-indigo-200 dark:border-indigo-500/30">📍 ' . e($ruangKode) . '</span>';
        }

        if ($this->isExamEnded()) {
            return '<div class="flex flex-wrap gap-1">' .
                   '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-500/15 dark:text-teal-200 dark:border-teal-500/30 whitespace-nowrap">✓ Selesai</span>' .
                   $detailBadges .
                   '</div>';
        }

        if ($jadwal > $today) {
            return '<div class="flex flex-wrap gap-1">' .
                   '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 whitespace-nowrap dark:bg-emerald-500/15 dark:text-emerald-200 dark:border-emerald-500/30">✓ Terjadwal</span>' .
                   '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200 whitespace-nowrap dark:bg-amber-500/15 dark:text-amber-200 dark:border-amber-500/30">● Belum Sidang</span>' .
                   $detailBadges .
                   '</div>';
        }

        return '<div class="flex flex-wrap gap-1">' .
               '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 border border-blue-200 whitespace-nowrap dark:bg-blue-500/15 dark:text-blue-200 dark:border-blue-500/30">⏳ Proses Ujian</span>' .
               $detailBadges .
               '</div>';
    }

    /**
     * Varian getJadwalStatusHtml() untuk Sempro berjadwal_terpisah — Pembimbing
     * Utama & Pendamping diuji di tanggal/jam/ruang masing-masing, jadi
     * ditampilkan sebagai dua baris status terpisah alih-alih satu.
     */
    private function getJadwalStatusHtmlTerpisah(): string
    {
        $html = '<div class="flex flex-col gap-1">';
        $html .= '<span class="inline-flex items-center gap-1 text-[9px] font-extrabold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 border border-purple-200 whitespace-nowrap w-fit dark:bg-purple-500/15 dark:text-purple-200 dark:border-purple-500/30">🔀 Jadwal Terpisah</span>';
        $html .= $this->sessionStatusBadgeHtml('Pembimbing Utama', $this->tanggal, $this->jam, $this->ruang->kode_ruangan ?? null);

        if ($this->tanggal_pendamping) {
            $html .= $this->sessionStatusBadgeHtml('Pembimbing Pendamping', $this->tanggal_pendamping, $this->jam_pendamping, $this->ruangPendamping->kode_ruangan ?? null);
        } else {
            $html .= '<span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200 whitespace-nowrap w-fit dark:bg-rose-500/15 dark:text-rose-200 dark:border-rose-500/30">⚠️ Sesi Pembimbing Pendamping belum dijadwalkan</span>';
        }

        if ($this->catatan_jadwal_terpisah) {
            $html .= '<span class="text-[9px] text-slate-500 dark:text-slate-400 italic">📝 ' . e($this->catatan_jadwal_terpisah) . '</span>';
        }

        $html .= '</div>';

        return $html;
    }

    private function sessionStatusBadgeHtml(string $label, $tanggal, ?string $jam, ?string $ruangKode): string
    {
        $ended = self::hasSessionEnded($tanggal, $jam);
        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $tglYmd = $tanggal->format('Y-m-d');

        if ($ended) {
            $statusBadge = '<span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-500/15 dark:text-teal-200 dark:border-teal-500/30 whitespace-nowrap">✓ Selesai</span>';
        } elseif ($tglYmd === $today) {
            $statusBadge = '<span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 border border-blue-200 whitespace-nowrap dark:bg-blue-500/15 dark:text-blue-200 dark:border-blue-500/30">⏳ Proses</span>';
        } else {
            $statusBadge = '<span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200 whitespace-nowrap dark:bg-amber-500/15 dark:text-amber-200 dark:border-amber-500/30">● Belum</span>';
        }

        $detail = '<span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap dark:bg-indigo-500/15 dark:text-indigo-200 dark:border-indigo-500/30">📅 ' . e($tanggal->locale('id')->translatedFormat('d M Y')) . '</span>';
        if (!empty($jam)) {
            $detail .= '<span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap dark:bg-indigo-500/15 dark:text-indigo-200 dark:border-indigo-500/30">🕐 ' . e($jam) . '</span>';
        }
        if (!empty($ruangKode)) {
            $detail .= '<span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap dark:bg-indigo-500/15 dark:text-indigo-200 dark:border-indigo-500/30">📍 ' . e($ruangKode) . '</span>';
        }

        return '<div class="flex flex-wrap items-center gap-1">' .
               '<span class="text-[9px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wide">' . e($label) . ':</span>' .
               $statusBadge . $detail .
               '</div>';
    }

    /**
     * Get the verification status badge HTML.
     */
    public function getVerifikasiStatusHtml(): string
    {
        $status = $this->verifikasi_status ?? 'menunggu';
        if ($status === 'disetujui') {
            return '<div class="flex flex-col items-center">' .
                   '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 whitespace-nowrap dark:bg-emerald-500/15 dark:text-emerald-200 dark:border-emerald-500/30">✓ Terverifikasi Koordinator</span>' .
                   '</div>';
        } elseif ($status === 'ditolak') {
            $html = '<div class="flex flex-col items-center gap-1">' .
                    '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-rose-100 text-rose-700 border border-rose-200 whitespace-nowrap dark:bg-rose-500/15 dark:text-rose-200 dark:border-rose-500/30">✕ Ditolak</span>';
            if ($this->verifikasi_komentar) {
                $html .= '<span class="text-[10px] text-slate-500 font-normal text-center max-w-[200px]">Catatan: ' . e($this->verifikasi_komentar) . '</span>';
            }
            $html .= '</div>';
            return $html;
        } else {
            return '<div class="flex flex-col items-center">' .
                   '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 border border-amber-200 whitespace-nowrap dark:bg-amber-500/15 dark:text-amber-200 dark:border-amber-500/30">⏳ Menunggu Verifikasi</span>' .
                   '</div>';
        }
    }

    /**
     * Accessor for verifikasi_status_html attribute.
     */
    public function getVerifikasiStatusHtmlAttribute(): string
    {
        return $this->getVerifikasiStatusHtml();
    }

    /**
     * Whether the koordinator has finished plotting this exam. Sempro only needs
     * tanggal + ruang — its ketua_penguji_id is always auto-derived from the
     * pembimbing (see syncPengujiFromPembimbing() above), never a separate
     * plotting step — while skripsi/jurnal also needs its ketua penguji assigned.
     */
    public function isPlotted(): bool
    {
        if (empty($this->tanggal) || empty($this->ruang_id)) {
            return false;
        }

        if ($this->jadwal_terpisah) {
            return !empty($this->tanggal_pendamping) && !empty($this->ruang_id_pendamping);
        }

        return $this->jenis_tugas_akhir === 'sempro' || !empty($this->ketua_penguji_id);
    }

    /**
     * Whether the exam result (lulus/tidak lulus) can be set for this record yet
     * — only once it has actually been scheduled and the exam date has passed.
     * Untuk Sempro berjadwal_terpisah, baru bisa diisi setelah KEDUA sesi lewat.
     */
    public function canSetHasilUjian(): bool
    {
        if (empty($this->tanggal) || !$this->tanggal->isPast()) {
            return false;
        }

        if ($this->jadwal_terpisah) {
            return !empty($this->tanggal_pendamping) && $this->tanggal_pendamping->isPast();
        }

        return true;
    }

    /**
     * Get the exam-result (hasil ujian) badge HTML.
     */
    public function getHasilUjianHtml(): string
    {
        if ($this->status_ujian === 'lulus') {
            return '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 whitespace-nowrap dark:bg-emerald-500/15 dark:text-emerald-200 dark:border-emerald-500/30">🎓 Lulus</span>';
        }

        if ($this->status_ujian === 'tidak_lulus') {
            return '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-rose-100 text-rose-700 border border-rose-200 whitespace-nowrap dark:bg-rose-500/15 dark:text-rose-200 dark:border-rose-500/30">✕ Tidak Lulus / Remidi</span>';
        }

        return '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-500/15 dark:text-sky-200 dark:border-sky-500/30 whitespace-nowrap">Belum Lulus</span>';
    }

    /**
     * Accessor for hasil_ujian_html attribute.
     */
    public function getHasilUjianHtmlAttribute(): string
    {
        return $this->getHasilUjianHtml();
    }
}

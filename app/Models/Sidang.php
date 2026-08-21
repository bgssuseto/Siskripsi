<?php

namespace App\Models;

use App\Concerns\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sidang extends Model
{
    use HasFactory, HasHashedRouteKey;

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
        'jenis_tugas_akhir',
        'jalur_ta',
        'verifikasi_status',
        'verifikasi_komentar',
        'verifikasi_tanggal',
        'bukti_pembayaran',
        'no_wa_aktif',
        'file_persyaratan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_pendaftaran' => 'date',
        'verifikasi_tanggal' => 'datetime',
    ];

    /**
     * Booted model events.
     * Default anggota_penguji_2_id to dosen_pembimbing_utama_id if empty.
     * Only applies to skripsi/sidang, NOT sempro.
     */
    protected static function booted()
    {
        static::creating(function ($sidang) {
            // Dosbing utama hanya wajib jadi penguji 2 pada skripsi, bukan sempro
            if ($sidang->jenis_tugas_akhir !== 'sempro'
                && empty($sidang->anggota_penguji_2_id)
                && !empty($sidang->dosen_pembimbing_utama_id)) {
                $sidang->anggota_penguji_2_id = $sidang->dosen_pembimbing_utama_id;
            }

            $sidang->gelombang = static::computeGelombang($sidang);
            static::syncJalurTa($sidang);
        });

        static::updating(function ($sidang) {
            // Dosbing utama hanya wajib jadi penguji 2 pada skripsi, bukan sempro
            if ($sidang->jenis_tugas_akhir !== 'sempro'
                && empty($sidang->anggota_penguji_2_id)
                && !empty($sidang->dosen_pembimbing_utama_id)) {
                $sidang->anggota_penguji_2_id = $sidang->dosen_pembimbing_utama_id;
            }

            if ($sidang->isDirty(['periode_id', 'tanggal_pendaftaran', 'jenis_tugas_akhir'])) {
                $sidang->gelombang = static::computeGelombang($sidang);
            }

            static::syncJalurTa($sidang);
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
     * Get the schedule status badge HTML — termasuk badge tanggal, jam, dan
     * ruang saat sidang sudah terjadwal, supaya info lengkap terlihat tanpa
     * harus melihat kolom lain. Dipakai di semua role (admin/koordinator,
     * penjadwalan, dosen).
     */
    public function getJadwalStatusHtml(): string
    {
        if (empty($this->tanggal)) {
            return '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200 whitespace-nowrap">● Belum Plotting</span>';
        }

        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $jadwal = $this->tanggal->format('Y-m-d');

        $detailBadges = '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap">📅 ' . e($this->tanggal->locale('id')->translatedFormat('d M Y')) . '</span>';
        if (!empty($this->jam)) {
            $detailBadges .= '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap">🕐 ' . e($this->jam) . '</span>';
        }
        $ruangKode = $this->ruang->kode_ruangan ?? null;
        if (!empty($ruangKode)) {
            $detailBadges .= '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap">📍 ' . e($ruangKode) . '</span>';
        }

        if ($jadwal > $today) {
            return '<div class="flex flex-wrap gap-1">' .
                   '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 whitespace-nowrap">✓ Terjadwal</span>' .
                   '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200 whitespace-nowrap">● Belum Sidang</span>' .
                   $detailBadges .
                   '</div>';
        } elseif ($jadwal === $today) {
            return '<div class="flex flex-wrap gap-1">' .
                   '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 border border-blue-200 whitespace-nowrap">⏳ Proses Ujian</span>' .
                   $detailBadges .
                   '</div>';
        } else {
            return '<div class="flex flex-wrap gap-1">' .
                   '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap">✓ Sudah Sidang</span>' .
                   $detailBadges .
                   '</div>';
        }
    }

    /**
     * Get the verification status badge HTML.
     */
    public function getVerifikasiStatusHtml(): string
    {
        $status = $this->verifikasi_status ?? 'menunggu';
        if ($status === 'disetujui') {
            return '<div class="flex flex-col items-center">' .
                   '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 whitespace-nowrap">✓ Terverifikasi Koordinator</span>' .
                   '</div>';
        } elseif ($status === 'ditolak') {
            $html = '<div class="flex flex-col items-center gap-1">' .
                    '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-rose-100 text-rose-700 border border-rose-200 whitespace-nowrap">✕ Ditolak</span>';
            if ($this->verifikasi_komentar) {
                $html .= '<span class="text-[10px] text-slate-500 font-normal text-center max-w-[200px]">Catatan: ' . e($this->verifikasi_komentar) . '</span>';
            }
            $html .= '</div>';
            return $html;
        } else {
            return '<div class="flex flex-col items-center">' .
                   '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 border border-amber-200 whitespace-nowrap">⏳ Menunggu Verifikasi</span>' .
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sidang extends Model
{
    use HasFactory;

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
        'tanggal',
        'tanggal_pendaftaran',
        'jam',
        'jenis_tugas_akhir',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_pendaftaran' => 'date',
    ];

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
            'sidang'  => 'Skripsi',
            'skripsi' => 'Skripsi',
            'sempro'  => 'Sempro',
            'jurnal'  => 'Jurnal / Artikel',
            default   => ucfirst($this->jenis_tugas_akhir ?? ''),
        };
    }
}

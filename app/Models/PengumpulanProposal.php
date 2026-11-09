<?php

namespace App\Models;

use App\Support\KoordinatorSkripsi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Status pengumpulan Proposal Skripsi seorang mahasiswa (per pendaftaran Sempro)
 * beserta data Surat Keterangan Pengumpulan Proposal yang diterbitkan.
 */
class PengumpulanProposal extends Model
{
    public const STATUS_BELUM    = 'belum';
    public const STATUS_DITERIMA = 'diterima';
    public const STATUS_DITOLAK  = 'ditolak';

    protected $fillable = [
        'sidang_id', 'status', 'catatan', 'nomor_surat', 'kode_verifikasi', 'tanggal_surat',
        'penandatangan_id', 'nama_penandatangan', 'nidn_penandatangan', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'nomor_surat'   => 'integer',
        'tanggal_surat' => 'date',
        'verified_at'   => 'datetime',
    ];

    public function sidang(): BelongsTo
    {
        return $this->belongsTo(Sidang::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isDiterima(): bool
    {
        return $this->status === self::STATUS_DITERIMA;
    }

    /** URL halaman verifikasi publik yang dituju QR tanda tangan. */
    public function getUrlVerifikasiAttribute(): ?string
    {
        return $this->kode_verifikasi ? route('verifikasi.proposal', $this->kode_verifikasi) : null;
    }

    /** Contoh: 1285/PROPOSAL.TI.UMK/11/2026 */
    public function getNomorSuratLengkapAttribute(): ?string
    {
        if (!$this->nomor_surat || !$this->tanggal_surat) {
            return null;
        }

        return $this->nomor_surat . '/PROPOSAL.TI.UMK/' . $this->tanggal_surat->format('n') . '/' . $this->tanggal_surat->format('Y');
    }

    /** Nomor surat yang akan dipakai bila menerima proposal baru sekarang. */
    public static function nomorBerikutnya(): int
    {
        return max((int) static::max('nomor_surat') + 1, (int) config('proposal.nomor_awal'));
    }

    /**
     * Terima proposal & terbitkan surat. Nomor & kode QR yang sudah pernah terbit
     * untuk mahasiswa ini dipakai lagi (surat yang sudah dicetak tetap bisa
     * diverifikasi). Dikunci dalam transaksi supaya dua admin yang menerima
     * bersamaan tidak mendapat nomor yang sama.
     */
    public function terima(int $verifierId, ?int $nomor = null, ?string $tanggalSurat = null): void
    {
        DB::transaction(function () use ($verifierId, $nomor, $tanggalSurat) {
            if (!$nomor) {
                $nomor = $this->nomor_surat;
            }
            if (!$nomor) {
                $max   = static::lockForUpdate()->max('nomor_surat');
                $nomor = max((int) $max + 1, (int) config('proposal.nomor_awal'));
            }

            $koordinator = KoordinatorSkripsi::dosen();

            $this->fill([
                'status'             => self::STATUS_DITERIMA,
                'catatan'            => null,
                'nomor_surat'        => $nomor,
                'kode_verifikasi'    => $this->kode_verifikasi ?? Str::random(32),
                'tanggal_surat'      => $tanggalSurat ?? $this->tanggal_surat?->toDateString() ?? now()->timezone('Asia/Jakarta')->toDateString(),
                'penandatangan_id'   => $koordinator?->id,
                'nama_penandatangan' => $koordinator?->nama_dosen,
                'nidn_penandatangan' => $koordinator?->nidn,
                'verified_by'        => $verifierId,
                'verified_at'        => now(),
            ])->save();
        });
    }

    public function tolak(int $verifierId, string $catatan): void
    {
        $this->fill([
            'status'      => self::STATUS_DITOLAK,
            'catatan'     => $catatan,
            'verified_by' => $verifierId,
            'verified_at' => now(),
        ])->save();
    }

    public function batalkan(int $verifierId): void
    {
        $this->fill([
            'status'      => self::STATUS_BELUM,
            'catatan'     => null,
            'verified_by' => $verifierId,
            'verified_at' => now(),
        ])->save();
    }
}

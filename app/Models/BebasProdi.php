<?php

namespace App\Models;

use App\Concerns\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class BebasProdi extends Model
{
    use HasHashedRouteKey;

    public const STATUS_MENUNGGU  = 'menunggu';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_DITOLAK   = 'ditolak';

    /** Poin yang tercentang pada surat (sesuai format surat prodi). */
    public const POIN_SURAT = [
        'Scan Sertifikat ESQ',
        'Scan Sertifikat KW',
        'Scan Transkrip Nilai',
        'Bebas Peminjaman Perangkat Lab TI',
        'Bebas Administrasi Skripsi',
        'Scan Sertifikat Keahlian di Bidang IT',
    ];

    protected $fillable = [
        'user_id', 'nim', 'nama_mahasiswa', 'jalur', 'link_poster', 'link_linkedin', 'link_program',
        'link_jurnal', 'file_berkas', 'status', 'komentar', 'nomor_surat', 'tanggal_surat', 'kaprodi_id',
        'verified_by', 'verified_at', 'submitted_at',
    ];

    protected $casts = [
        'nomor_surat'   => 'integer',
        'tanggal_surat' => 'date',
        'verified_at'   => 'datetime',
        'submitted_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kaprodi(): BelongsTo
    {
        return $this->belongsTo(Kaprodi::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getNomorSuratLengkapAttribute(): ?string
    {
        return $this->nomor_surat ? 'TI/BP/' . $this->nomor_surat : null;
    }

    /**
     * Status kelulusan Sempro & Skripsi seorang mahasiswa (by NIM), plus jalur
     * skripsi (sidang/jurnal) dari pendaftaran skripsi yang lulus. Bebas Prodi
     * baru terbuka bila keduanya lulus — kecuali NIM di config
     * bebas_prodi.bypass_nims (akun testing).
     */
    public static function kelayakan(?string $nim): array
    {
        if (!$nim) {
            return ['lulus_sempro' => false, 'lulus_skripsi' => false, 'eligible' => false, 'bypass' => false, 'jalur' => null];
        }

        $lulusSempro = Sidang::where('nim', $nim)
            ->where('jenis_tugas_akhir', 'sempro')
            ->where('status_ujian', 'lulus')
            ->exists();

        $skripsiLulus = Sidang::where('nim', $nim)
            ->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)
            ->where('status_ujian', 'lulus')
            ->orderByDesc('id')
            ->first();

        $bypass = in_array($nim, config('bebas_prodi.bypass_nims', []), true);

        // Akun bypass yang belum punya skripsi lulus: jalur diambil dari
        // pendaftaran skripsi terakhir, atau default ke jalur Sidang.
        $skripsiRef = $skripsiLulus ?? ($bypass
            ? Sidang::where('nim', $nim)->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)->orderByDesc('id')->first()
            : null);

        return [
            'lulus_sempro'  => $lulusSempro,
            'lulus_skripsi' => (bool) $skripsiLulus,
            'eligible'      => ($lulusSempro && $skripsiLulus) || $bypass,
            'bypass'        => $bypass && !($lulusSempro && $skripsiLulus),
            'jalur'         => $skripsiRef ? ($skripsiRef->jenis_tugas_akhir === 'jurnal' ? 'jurnal' : 'sidang') : ($bypass ? 'sidang' : null),
        ];
    }

    /**
     * Setujui pengajuan & terbitkan nomor surat berurutan. Nomor yang sudah
     * pernah terbit untuk pengajuan ini dipakai lagi (tidak loncat nomor saat
     * pengajuan disetujui ulang). Dikunci dalam transaksi supaya dua admin yang
     * menyetujui bersamaan tidak mendapat nomor yang sama. Kaprodi penandatangan
     * dipilih manual, atau otomatis yang menjabat pada tanggal surat.
     */
    public function setujui(int $verifierId, ?string $tanggalSurat = null, ?int $kaprodiId = null): void
    {
        DB::transaction(function () use ($verifierId, $tanggalSurat, $kaprodiId) {
            $tanggal = $tanggalSurat ?? $this->tanggal_surat?->toDateString() ?? now()->timezone('Asia/Jakarta')->toDateString();

            $nomor = $this->nomor_surat;
            if (!$nomor) {
                $max   = static::lockForUpdate()->max('nomor_surat');
                $nomor = max((int) $max + 1, (int) config('bebas_prodi.nomor_awal'));
            }

            $this->update([
                'status'        => self::STATUS_DISETUJUI,
                'komentar'      => null,
                'nomor_surat'   => $nomor,
                'tanggal_surat' => $tanggal,
                'kaprodi_id'    => $kaprodiId ?? Kaprodi::untukTanggal($tanggal)?->id,
                'verified_by'   => $verifierId,
                'verified_at'   => now(),
            ]);
        });
    }
}

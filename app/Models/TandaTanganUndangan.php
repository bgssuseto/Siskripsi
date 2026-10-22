<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Tanda tangan elektronik Koordinator Skripsi pada undangan menguji dosen.
 * Satu baris per isi undangan yang unik: mencetak ulang undangan yang sama
 * menghasilkan QR yang sama, sedangkan bila jadwal berubah terbit kode baru.
 */
class TandaTanganUndangan extends Model
{
    protected $fillable = [
        'kode', 'sidik', 'dosen_id', 'penandatangan_id', 'nama_penandatangan', 'nidn_penandatangan',
        'jenis', 'periode_id', 'nama_periode', 'gelombang', 'jumlah_mahasiswa', 'rincian',
    ];

    protected $casts = [
        'rincian'          => 'array',
        'jumlah_mahasiswa' => 'integer',
    ];

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function penandatangan(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'penandatangan_id');
    }

    public function getUrlVerifikasiAttribute(): string
    {
        return route('verifikasi.undangan', $this->kode);
    }

    public function getJenisLabelAttribute(): string
    {
        return $this->jenis === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi';
    }

    /**
     * Ambil (atau terbitkan) tanda tangan untuk satu undangan.
     *
     * @param  Collection<int, Sidang>  $sidangs
     */
    public static function untuk(
        Dosen $dosen,
        Dosen $penandatangan,
        string $jenis,
        ?int $periodeId,
        string $namaPeriode,
        ?string $gelombang,
        Collection $sidangs
    ): self {
        $rincian = $sidangs->map(fn (Sidang $s) => [
            'nim'     => $s->nim,
            'nama'    => $s->nama_mahasiswa,
            'peran'   => $s->peranPengujiDosen($dosen->id),
            'tanggal' => $s->tanggal ? \Carbon\Carbon::parse($s->tanggal)->toDateString() : null,
            'jam'     => $s->jam,
            'ruang'   => $s->ruang?->kode_ruangan,
        ])
            // Urutan tetap supaya undangan yang sama (dari halaman mana pun) memakai QR yang sama.
            ->sortBy([['tanggal', 'asc'], ['jam', 'asc'], ['nim', 'asc']])
            ->values()->all();

        $isi = [$dosen->id, $penandatangan->id, $penandatangan->nama_dosen, $jenis, $periodeId, $gelombang, $rincian];
        $sidik = hash('sha256', json_encode($isi));

        return static::firstOrCreate(['sidik' => $sidik], [
            'kode'               => Str::random(32),
            'dosen_id'           => $dosen->id,
            'penandatangan_id'   => $penandatangan->id,
            'nama_penandatangan' => $penandatangan->nama_dosen,
            'nidn_penandatangan' => $penandatangan->nidn,
            'jenis'              => $jenis === 'sempro' ? 'sempro' : 'skripsi',
            'periode_id'         => $periodeId,
            'nama_periode'       => $namaPeriode,
            'gelombang'          => ($gelombang === null || $gelombang === '') ? null : (string) $gelombang,
            'jumlah_mahasiswa'   => count($rincian),
            'rincian'            => $rincian,
        ]);
    }
}

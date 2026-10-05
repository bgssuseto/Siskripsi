<?php

namespace App\Models;

use App\Concerns\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendaftaranPeriode extends Model
{
    use HasHashedRouteKey;

    protected $table = 'pendaftaran_periodes';

    protected $fillable = [
        'periode_id',
        'jenis',
        'gelombang',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    /**
     * Gelombang yang rentang tanggalnya lebih dari 1 bulan terlalu longgar
     * untuk jadi pilihan di dropdown form kesediaan menguji — biasanya
     * menandakan ini bukan jendela pendaftaran yang sempit, melainkan
     * rentang semester/periode yang lebar, sehingga justru membingungkan
     * dosen soal gelombang mana yang sebenarnya relevan. Dipakai untuk
     * menyaring dropdown ("auto hide"), BUKAN untuk menolak submit — kalau
     * sudah tersimpan, data kesediaannya tetap valid.
     */
    public function isTooLongForKesediaanPicker(): bool
    {
        if (!$this->tanggal_mulai || !$this->tanggal_selesai) {
            return false;
        }

        return $this->tanggal_mulai->copy()->addMonth()->lt($this->tanggal_selesai);
    }
}

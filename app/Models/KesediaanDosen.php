<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KesediaanDosen extends Model
{
    protected $fillable = [
        'dosen_id',
        'periode_id',
        'wave_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    public function wave(): BelongsTo
    {
        return $this->belongsTo(PendaftaranPeriode::class, 'wave_id');
    }
}

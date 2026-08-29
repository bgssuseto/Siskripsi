<?php

namespace App\Models;

use App\Concerns\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periode extends Model
{
    use HasFactory, HasHashedRouteKey;

    protected $table = 'periodes';

    protected $fillable = [
        'nama_periode',
        'aktif',
        'show_form_kesediaan',
        'lock_form_kesediaan',
        'link_grup_wa_skripsi',
        'link_grup_wa_sempro',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'show_form_kesediaan' => 'boolean',
        'lock_form_kesediaan' => 'boolean',
    ];

    /**
     * Relationship with Sidangs.
     */
    public function sidangs(): HasMany
    {
        return $this->hasMany(Sidang::class, 'periode_id');
    }
}

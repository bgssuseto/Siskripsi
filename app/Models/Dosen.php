<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    use HasFactory;

    protected $fillable = [
        'nidn',
        'nama_dosen',
        'can_fill_kesediaan',
    ];

    protected $casts = [
        'can_fill_kesediaan' => 'boolean',
    ];
}

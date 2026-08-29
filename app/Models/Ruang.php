<?php

namespace App\Models;

use App\Concerns\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruang extends Model
{
    use HasFactory, HasHashedRouteKey;

    protected $fillable = [
        'kode_ruangan',
        'nama_ruangan',
    ];
}

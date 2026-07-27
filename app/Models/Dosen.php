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

    public function sidangsSebagaiPembimbingUtama()
    {
        return $this->hasMany(Sidang::class, 'dosen_pembimbing_utama_id');
    }

    public function sidangsSebagaiKetuaPenguji()
    {
        return $this->hasMany(Sidang::class, 'ketua_penguji_id');
    }
}

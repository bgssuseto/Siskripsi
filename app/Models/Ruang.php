<?php

namespace App\Models;

use App\Concerns\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruang extends Model
{
    use HasFactory, HasHashedRouteKey;

    public const STATUS_SIAP = 'siap_digunakan';
    public const STATUS_BELUM_SIAP = 'belum_siap_digunakan';

    public const STATUS_LABELS = [
        self::STATUS_SIAP => 'Siap Digunakan',
        self::STATUS_BELUM_SIAP => 'Belum Siap Digunakan',
    ];

    protected $fillable = [
        'kode_ruangan',
        'nama_ruangan',
        'status',
    ];

    public function isSiapDigunakan(): bool
    {
        return $this->status === self::STATUS_SIAP;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}

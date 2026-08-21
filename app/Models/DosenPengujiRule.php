<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DosenPengujiRule extends Model
{
    protected $fillable = [
        'dosen_id',
        'boleh_dosen_ids',
        'tidak_boleh_dosen_ids',
        'keterangan',
    ];

    protected $casts = [
        'boleh_dosen_ids' => 'array',
        'tidak_boleh_dosen_ids' => 'array',
    ];

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    /**
     * Semua pasangan dosen_id yang diblokir untuk dosen tertentu, dikumpulkan
     * dari kedua arah: rule milik dosen itu sendiri, DAN rule dosen lain yang
     * mencantumkan dosen ini di daftar "tidak boleh" mereka (larangan bersifat
     * dua arah meski hanya didefinisikan dari satu sisi).
     */
    public static function blockedPartnersFor(int $dosenId): array
    {
        $ownRule = self::where('dosen_id', $dosenId)->first();
        $blocked = $ownRule->tidak_boleh_dosen_ids ?? [];

        $others = self::where('dosen_id', '!=', $dosenId)
            ->whereJsonContains('tidak_boleh_dosen_ids', $dosenId)
            ->pluck('dosen_id')
            ->toArray();

        return array_values(array_unique(array_map('intval', array_merge($blocked, $others))));
    }
}

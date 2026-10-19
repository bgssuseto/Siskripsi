<?php

namespace App\Models;

use App\Concerns\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Kaprodi extends Model
{
    use HasHashedRouteKey;

    protected $fillable = ['nama', 'mulai_menjabat', 'selesai_menjabat'];

    protected $casts = [
        'mulai_menjabat'   => 'date',
        'selesai_menjabat' => 'date',
    ];

    public function bebasProdis(): HasMany
    {
        return $this->hasMany(BebasProdi::class);
    }

    /**
     * Kaprodi yang menjabat pada tanggal tertentu. Bila masa jabatan tumpang
     * tindih, yang mulai paling akhir yang dipakai. Bila tidak ada yang cocok
     * (mis. tanggal di luar semua masa jabatan), pakai kaprodi terbaru.
     */
    public static function untukTanggal(Carbon|string|null $tanggal = null): ?self
    {
        $tgl = Carbon::parse($tanggal ?? now()->timezone('Asia/Jakarta'))->toDateString();

        $terbaru = fn ($q) => $q->orderByRaw('mulai_menjabat IS NULL')->orderByDesc('mulai_menjabat')->orderByDesc('id');

        return $terbaru(static::query()
                ->where(fn ($q) => $q->whereNull('mulai_menjabat')->orWhereDate('mulai_menjabat', '<=', $tgl))
                ->where(fn ($q) => $q->whereNull('selesai_menjabat')->orWhereDate('selesai_menjabat', '>=', $tgl)))
                ->first()
            ?? $terbaru(static::query())->first();
    }

    public function getMasaJabatanAttribute(): string
    {
        $fmt = fn ($d) => $d ? $d->locale('id')->translatedFormat('j M Y') : null;

        return ($fmt($this->mulai_menjabat) ?? '…') . ' – ' . ($fmt($this->selesai_menjabat) ?? 'sekarang');
    }
}

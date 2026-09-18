<?php

namespace App\Models;

use App\Concerns\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Periode extends Model
{
    use HasFactory, HasHashedRouteKey;

    protected $table = 'periodes';

    protected $fillable = [
        'nama_periode',
        'aktif',
        'show_form_kesediaan',
        'lock_form_kesediaan',
        'kesediaan_public_token',
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

    public function pendaftaranPeriodes(): HasMany
    {
        return $this->hasMany(PendaftaranPeriode::class, 'periode_id');
    }

    /**
     * Token publik (link) untuk form kesediaan dosen tanpa login. Dibuat sekali
     * secara lazy saat pertama kali dibutuhkan (bukan di boot/creating), supaya
     * link yang sama tetap dipakai selama periode ini belum di-reset manual
     * oleh admin, dan tidak berubah setiap kali record disimpan ulang.
     */
    public function ensureKesediaanPublicToken(): string
    {
        if (empty($this->kesediaan_public_token)) {
            $this->kesediaan_public_token = Str::random(40);
            $this->save();
        }

        return $this->kesediaan_public_token;
    }

    public function getKesediaanPublicUrlAttribute(): string
    {
        return route('public.kesediaan.show', ['token' => $this->ensureKesediaanPublicToken()]);
    }

    /**
     * Link kesediaan publik hanya aktif kalau: form tidak disembunyikan admin,
     * tidak dikunci, DAN saat ini benar-benar berada di dalam rentang tanggal
     * salah satu Gelombang (PendaftaranPeriode) periode ini — begitu tanggal
     * berjalan keluar dari semua gelombang, link otomatis nonaktif lagi tanpa
     * perlu tindakan manual ("reset" terjadi otomatis mengikuti tanggal).
     */
    public function isKesediaanPublicLinkActive(): bool
    {
        if (!$this->show_form_kesediaan || $this->lock_form_kesediaan) {
            return false;
        }

        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');

        return $this->pendaftaranPeriodes()
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->exists();
    }

    /**
     * Gelombang (PendaftaranPeriode) yang tanggal hari ini berada di
     * dalamnya, kalau ada — dipakai untuk menandai wave_id default saat
     * dosen mengisi kesediaan lewat link publik.
     */
    public function currentActiveWave(): ?PendaftaranPeriode
    {
        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');

        return $this->pendaftaranPeriodes()
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->first();
    }
}

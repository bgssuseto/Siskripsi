<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'changes',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'changes'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return self::actionLabel($this->action);
    }

    public static function actionLabel(?string $action): string
    {
        return match ($action) {
            'jadwalkan'    => 'Plotting Jadwal',
            'reschedule'   => 'Geser Jadwal',
            'verifikasi'   => 'Verifikasi Pendaftaran',
            'hasil-ujian'  => 'Hasil Ujian',
            'created'    => 'Tambah Data',
            'updated'    => 'Ubah Data',
            'deleted'    => 'Hapus Data',
            default      => ucfirst((string) $action),
        };
    }
}

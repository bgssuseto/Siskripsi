<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Record an audit trail entry for an action taken on a model.
     *
     * @param string $action e.g. 'jadwalkan', 'reschedule', 'verifikasi', 'created', 'updated', 'deleted'
     * @param Model|null $subject The model the action was performed on
     * @param string $description Human-readable summary of what happened
     * @param array $changes Optional ['before' => [...], 'after' => [...]] snapshot
     */
    public static function log(string $action, ?Model $subject, string $description, array $changes = []): void
    {
        $user = Auth::user();

        ActivityLog::create([
            'user_id'       => $user?->id,
            'user_name'     => $user?->name,
            'user_role'     => $user?->role,
            'action'        => $action,
            'subject_type'  => $subject ? get_class($subject) : null,
            'subject_id'    => $subject?->id,
            'subject_label' => $subject?->nama_mahasiswa ?? $subject?->nama_dosen ?? null,
            'description'   => $description,
            'changes'       => !empty($changes) ? $changes : null,
            'ip_address'    => Request::ip(),
            'created_at'    => now(),
        ]);
    }
}

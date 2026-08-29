<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Halaman Riwayat Aktivitas / Audit Log
     */
    public function index(Request $request): View
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('subject_label', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->get('tanggal_mulai'));
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('created_at', '<=', $request->get('tanggal_selesai'));
        }

        $logs = $query->paginate(25)->withQueryString();

        $actionOptions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $userOptions = User::whereIn('id', ActivityLog::whereNotNull('user_id')->select('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('administrasi.audit-log.index', compact('logs', 'actionOptions', 'userOptions'));
    }
}

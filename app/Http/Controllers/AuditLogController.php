<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('audit.view'), 403);

        $query = AuditLog::query()->with('user:id,name,username');

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('type')) {
            $query->where('auditable_type', 'like', '%'.$request->input('type').'%');
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', Carbon::parse($request->input('from'))->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', Carbon::parse($request->input('to'))->endOfDay());
        }

        return view('audit.index', [
            'logs' => $query->orderByDesc('created_at')->paginate(30)->withQueryString(),
            'users' => User::query()->orderBy('name')->pluck('name', 'id'),
            'events' => ['create', 'update', 'delete', 'login', 'export', 'view_sensitive', 'approve', 'reject'],
        ]);
    }
}

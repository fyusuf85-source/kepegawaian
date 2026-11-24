<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginHistoryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('login_history.view'), 403);

        $query = LoginHistory::query()->with('user:id,name,username');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('success')) {
            $query->where('success', $request->input('success') === '1');
        }

        return view('login-histories.index', [
            'histories' => $query->orderByDesc('login_at')->paginate(30)->withQueryString(),
            'users' => User::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}

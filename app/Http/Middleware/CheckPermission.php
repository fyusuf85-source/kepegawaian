<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        abort_unless($user && $user->hasPermission($permission), 403, 'Anda tidak memiliki hak akses untuk tindakan ini.');

        return $next($request);
    }
}

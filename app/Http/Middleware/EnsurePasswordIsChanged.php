<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password
            && ! $request->routeIs('password.*')
            && ! $request->routeIs('logout')) {
            return redirect()
                ->route('password.change')
                ->with('warning', 'Anda wajib mengganti kata sandi sebelum melanjutkan.');
        }

        return $next($request);
    }
}

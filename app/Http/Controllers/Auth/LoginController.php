<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->logAttempt($request, null, $credentials['username'], false, 'throttled');

            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        RateLimiter::hit($key, 60);

        $user = User::query()
            ->where('username', $credentials['username'])
            ->orWhere('email', $credentials['username'])
            ->first();

        if (! $user || ! Auth::attempt(
            ['username' => $user->username, 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            $this->logAttempt($request, $user, $credentials['username'], false, 'invalid_credentials');

            throw ValidationException::withMessages([
                'username' => 'Username atau kata sandi tidak sesuai.',
            ]);
        }

        if (! $user->is_active) {
            Auth::logout();
            $this->logAttempt($request, $user, $credentials['username'], false, 'inactive');

            throw ValidationException::withMessages([
                'username' => 'Akun Anda nonaktif. Hubungi administrator.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->logAttempt($request, $user, $credentials['username'], true);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function logAttempt(
        Request $request,
        ?User $user,
        string $username,
        bool $success,
        ?string $reason = null
    ): void {
        LoginHistory::create([
            'user_id' => $user?->id,
            'username_attempt' => Str::limit($username, 100),
            'success' => $success,
            'failure_reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255),
            'login_at' => now(),
        ]);
    }
}

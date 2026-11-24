@extends('layouts.guest', ['title' => 'Masuk'])

@section('content')
    <h2 class="text-lg font-semibold text-slate-900">Masuk ke akun Anda</h2>
    <p class="mt-1 text-sm text-slate-500">Gunakan username atau email yang terdaftar.</p>

    @if($errors->any())
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}" class="mt-5 space-y-4">
        @csrf
        <div>
            <label for="username" class="block text-sm font-medium text-slate-700">Username atau Email</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
            Ingat saya
        </label>

        <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">
            Masuk
        </button>
    </form>
@endsection

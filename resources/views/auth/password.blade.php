@extends('layouts.app', ['title' => 'Ubah Kata Sandi', 'heading' => 'Ubah Kata Sandi'])

@section('content')
    <div class="max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @if(auth()->user()->must_change_password)
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                Anda menggunakan kata sandi sementara. Ganti kata sandi sebelum melanjutkan.
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Kata Sandi Saat Ini</label>
                <input type="password" name="current_password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Kata Sandi Baru</label>
                <input type="password" name="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Minimal 8 karakter.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Ulangi Kata Sandi Baru</label>
                <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
        </form>
    </div>
@endsection

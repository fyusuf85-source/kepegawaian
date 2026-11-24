@extends('layouts.app', ['title' => 'Pengguna', 'heading' => 'Pengguna', 'subheading' => 'Akun dan peran pengguna aplikasi'])

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Nama, username, email" class="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">Cari</button>
        </form>
        @if($canManage)
            <a href="{{ route('users.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tambah Pengguna</a>
        @endif
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Username</th><th class="px-4 py-3">Pegawai</th><th class="px-4 py-3">Peran</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Login Terakhir</th><th class="px-4 py-3 text-right">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($users as $user)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $user->name }}<p class="text-xs text-slate-500">{{ $user->email ?: '—' }}</p></td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $user->username }}</td>
                    <td class="px-4 py-3">{{ $user->employee?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                    <td class="px-4 py-3">
                        @if($user->is_active)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Aktif</span>
                        @else
                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-700">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::dateTime($user->last_login_at) }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($canManage)
                            <a href="{{ route('users.edit', $user) }}" class="text-emerald-700 hover:underline">Ubah</a>
                        @endif
                        @if(auth()->user()->hasPermission('user.reset_password') && $user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.reset-password', $user) }}" class="ml-3 inline" onsubmit="return confirm('Reset kata sandi pengguna ini?')">
                                @csrf
                                <button class="text-amber-700 hover:underline">Reset Sandi</button>
                            </form>
                        @endif
                        @if(auth()->user()->hasPermission('user.deactivate') && $user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="ml-3 inline">
                                @csrf
                                <button class="text-rose-600 hover:underline">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Belum ada pengguna.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection

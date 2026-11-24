@extends('layouts.app', ['title' => 'Peran & Hak Akses', 'heading' => 'Peran & Hak Akses', 'subheading' => 'Matriks RBAC sesuai dokumen ACCESS_CONTROL'])

@section('content')
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($roles as $role)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-900">{{ $role->name }}</h2>
                        <p class="font-mono text-xs text-slate-500">{{ $role->code }}</p>
                    </div>
                    @if($role->is_system)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">Sistem</span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-slate-600">{{ $role->description ?: '—' }}</p>
                <p class="mt-3 text-xs text-slate-500">{{ $role->permissions_count }} permission · {{ $role->users_count }} pengguna</p>
                <a href="{{ route('roles.edit', $role) }}" class="mt-4 inline-block rounded-lg border border-emerald-600 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Atur Hak Akses</a>
            </div>
        @endforeach
    </div>
@endsection

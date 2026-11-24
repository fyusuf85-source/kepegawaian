@extends('layouts.app', [
    'title' => $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna',
    'heading' => $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna',
])

@section('content')
    @php($selectedRoles = old('role_ids', $user->roles->pluck('id')->all()))
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}"
          class="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Nama <span class="text-rose-600">*</span></label>
                <input name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Username <span class="text-rose-600">*</span></label>
                <input name="username" value="{{ old('username', $user->username) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tautkan ke Pegawai</label>
                <select name="employee_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Tidak ditautkan —</option>
                    @foreach($options['employees'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('employee_id', $user->employee_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Kata Sandi {{ $user->exists ? '(kosongkan bila tidak diubah)' : '' }}</label>
                <input type="password" name="password" @required(! $user->exists) class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Ulangi Kata Sandi</label>
                <input type="password" name="password_confirmation" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Cakupan Unit Peran</label>
                <select name="org_unit_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Tanpa batasan unit</option>
                    @foreach($options['orgUnits'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('org_unit_id', $user->roles->first()?->pivot?->org_unit_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Berlaku untuk seluruh peran yang dipilih.</p>
            </div>
            <div class="space-y-2 pt-6">
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="rounded"> Akun aktif
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="must_change_password" value="0">
                    <input type="checkbox" name="must_change_password" value="1" @checked(old('must_change_password', $user->must_change_password ?? true)) class="rounded"> Wajib ganti sandi saat masuk
                </label>
            </div>
        </div>

        <div>
            <span class="block text-sm font-medium text-slate-700">Peran <span class="text-rose-600">*</span></span>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach($options['roles'] as $role)
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, $selectedRoles)) class="mt-0.5 rounded">
                        <span><span class="font-medium">{{ $role->name }}</span><span class="block text-xs text-slate-500">{{ $role->description }}</span></span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
            <a href="{{ route('users.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

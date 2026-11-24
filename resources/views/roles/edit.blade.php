@extends('layouts.app', ['title' => 'Hak Akses '.$role->name, 'heading' => 'Hak Akses: '.$role->name, 'subheading' => $role->code])

@section('content')
    <form method="POST" action="{{ route('roles.update', $role) }}" class="space-y-4">
        @csrf @method('PUT')

        @foreach($permissions as $module => $items)
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">{{ $module }}</h2>
                    <label class="flex items-center gap-2 text-xs text-slate-500">
                        <input type="checkbox" class="rounded" onclick="document.querySelectorAll('.module-{{ $loop->index }} input[type=checkbox]').forEach(c => c.checked = this.checked)">
                        Pilih semua
                    </label>
                </div>
                <div class="module-{{ $loop->index }} mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($items as $permission)
                        <label class="flex items-start gap-2 rounded-lg border border-slate-100 px-3 py-2 text-sm hover:bg-slate-50">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                   @checked(in_array($permission->id, $assigned)) class="mt-0.5 rounded">
                            <span class="text-xs text-slate-700">{{ $permission->name }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="sticky bottom-0 flex gap-3 border-t border-slate-200 bg-slate-100 py-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Hak Akses</button>
            <a href="{{ route('roles.index') }}" class="text-sm text-slate-600 hover:underline">Kembali</a>
        </div>
    </form>
@endsection

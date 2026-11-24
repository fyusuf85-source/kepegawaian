@extends('layouts.app', [
    'title' => $vacancy->exists ? 'Ubah Lowongan' : 'Tambah Lowongan',
    'heading' => $vacancy->exists ? 'Ubah Lowongan' : 'Tambah Lowongan',
])

@section('content')
    <form method="POST" action="{{ $vacancy->exists ? route('vacancies.update', $vacancy) : route('vacancies.store') }}"
          class="max-w-4xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($vacancy->exists) @method('PUT') @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Kode Lowongan</label>
                <input name="code" value="{{ old('code', $vacancy->code) }}" placeholder="Otomatis bila kosong" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Judul Lowongan <span class="text-rose-600">*</span></label>
                <input name="title" value="{{ old('title', $vacancy->title) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Unit Organisasi <span class="text-rose-600">*</span></label>
                <select name="org_unit_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Pilih unit —</option>
                    @foreach($options['orgUnits'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('org_unit_id', $vacancy->org_unit_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jabatan</label>
                <select name="position_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Tidak ditentukan —</option>
                    @foreach($options['positions'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('position_id', $vacancy->position_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Kategori Pegawai <span class="text-rose-600">*</span></label>
                <select name="employee_category" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(['tetap' => 'Tetap', 'harian' => 'Harian', 'khidmah' => 'Khidmah'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('employee_category', $vacancy->employee_category) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Kuota <span class="text-rose-600">*</span></label>
                <input type="number" min="1" name="quota" value="{{ old('quota', $vacancy->quota) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Dibuka <span class="text-rose-600">*</span></label>
                <input type="date" name="open_date" value="{{ old('open_date', $vacancy->open_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Ditutup <span class="text-rose-600">*</span></label>
                <input type="date" name="close_date" value="{{ old('close_date', $vacancy->close_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Status <span class="text-rose-600">*</span></label>
                <select name="status" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(['draft' => 'Draf', 'dibuka' => 'Dibuka', 'ditutup' => 'Ditutup', 'dibatalkan' => 'Dibatalkan'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $vacancy->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Deskripsi</label>
                <textarea name="description" rows="5" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('description', $vacancy->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Persyaratan</label>
                <textarea name="requirements" rows="5" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('requirements', $vacancy->requirements) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
            <a href="{{ route('vacancies.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

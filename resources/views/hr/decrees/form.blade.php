@extends('layouts.app', [
    'title' => $decree->exists ? 'Ubah SK' : 'Tambah SK',
    'heading' => $decree->exists ? 'Ubah SK' : 'Tambah SK',
])

@section('content')
    <form method="POST" action="{{ $decree->exists ? route('decrees.update', $decree) : route('decrees.store') }}"
          class="max-w-4xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($decree->exists) @method('PUT') @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Pegawai <span class="text-rose-600">*</span></label>
                <select name="employee_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Pilih pegawai —</option>
                    @foreach($employees as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('employee_id', $decree->employee_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jenis SK <span class="text-rose-600">*</span></label>
                <select name="decree_type" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(['pengangkatan' => 'Pengangkatan', 'penempatan' => 'Penempatan', 'perubahan_jabatan' => 'Perubahan Jabatan', 'mutasi' => 'Mutasi', 'pemberhentian' => 'Pemberhentian', 'lain' => 'Lain'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('decree_type', $decree->decree_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Nomor SK</label>
                <input name="number" value="{{ old('number', $decree->number) }}" placeholder="Otomatis bila kosong" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal SK <span class="text-rose-600">*</span></label>
                <input type="date" name="decree_date" value="{{ old('decree_date', $decree->decree_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Efektif <span class="text-rose-600">*</span></label>
                <input type="date" name="effective_date" value="{{ old('effective_date', $decree->effective_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Judul SK <span class="text-rose-600">*</span></label>
                <input name="title" value="{{ old('title', $decree->title) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Isi / Dasar Keputusan</label>
                <textarea name="description" rows="6" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('description', $decree->description) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
            <a href="{{ route('decrees.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

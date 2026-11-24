@extends('layouts.app', ['title' => $component->exists ? 'Ubah Komponen Gaji' : 'Tambah Komponen Gaji', 'heading' => $component->exists ? 'Ubah Komponen Gaji' : 'Tambah Komponen Gaji'])

@section('content')
<form method="POST" action="{{ $component->exists ? route('payroll.components.update', $component) : route('payroll.components.store') }}" class="max-w-3xl space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    @csrf
    @if($component->exists) @method('PUT') @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-slate-700">Kode</label>
            <input name="code" value="{{ old('code', $component->code) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @error('code')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Nama Komponen</label>
            <input name="name" value="{{ old('name', $component->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-slate-700">Tipe</label>
            <select name="type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @foreach(['penghasilan' => 'Penghasilan', 'potongan' => 'Potongan', 'iuran_pemberi' => 'Iuran Pemberi'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('type', $component->type) === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Kategori</label>
            <input name="category" value="{{ old('category', $component->category) }}" placeholder="gaji, tunjangan, bpjs, dll" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Metode Hitung</label>
            <select name="calculation_method" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @foreach(['fixed' => 'Tetap (Fixed)', 'per_day' => 'Per Hari', 'per_minute' => 'Per Menit', 'percentage' => 'Persentase', 'manual' => 'Manual'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('calculation_method', $component->calculation_method) === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-slate-700">Nominal (Rp)</label>
            <input type="number" step="0.01" name="amount" value="{{ old('amount', $component->amount) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Persentase (%)</label>
            <input type="number" step="0.001" name="percentage" value="{{ old('percentage', $component->percentage) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Formula (Opsional)</label>
        <input name="formula" value="{{ old('formula', $component->formula) }}" placeholder="Misal: gaji_pokok * 0.05" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-slate-700">Berlaku Mulai</label>
            <input type="date" name="effective_from" value="{{ old('effective_from', $component->effective_from?->toDateString() ?? now()->toDateString()) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Berlaku Sampai (Kosongkan jika aktif terus)</label>
            <input type="date" name="effective_to" value="{{ old('effective_to', $component->effective_to?->toDateString()) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
    </div>

    <div class="flex gap-6 pt-2">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_taxable" value="1" @checked(old('is_taxable', $component->is_taxable ?? true)) class="rounded border-slate-300">
            Kena Pajak (Taxable)
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $component->is_active ?? true)) class="rounded border-slate-300">
            Aktif
        </label>
    </div>

    <div class="pt-4 flex gap-3">
        <button class="rounded-lg bg-emerald-700 px-6 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Komponen</button>
        <a href="{{ route('payroll.components.index') }}" class="rounded-lg border border-slate-300 px-6 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</a>
    </div>
</form>
@endsection

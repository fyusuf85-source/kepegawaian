@extends('layouts.app', [
    'title' => $letter->exists ? 'Ubah Surat' : 'Buat Surat',
    'heading' => $letter->exists ? 'Ubah Surat' : 'Buat Surat',
])

@section('content')
    <form method="POST" action="{{ $letter->exists ? route('letters.update', $letter) : route('letters.store') }}"
          class="max-w-4xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($letter->exists) @method('PUT') @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Pegawai <span class="text-rose-600">*</span></label>
                <select name="employee_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Pilih pegawai —</option>
                    @foreach($employees as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('employee_id', $letter->employee_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jenis Surat <span class="text-rose-600">*</span></label>
                <select name="letter_type" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(['teguran' => 'Teguran', 'pembinaan' => 'Pembinaan', 'pemberhentian' => 'Pemberhentian', 'keterangan' => 'Keterangan', 'lain' => 'Lain'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('letter_type', $letter->letter_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Nomor Surat</label>
                <input name="number" value="{{ old('number', $letter->number) }}" placeholder="Otomatis bila kosong" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Surat <span class="text-rose-600">*</span></label>
                <input type="date" name="letter_date" value="{{ old('letter_date', $letter->letter_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Perihal <span class="text-rose-600">*</span></label>
                <input name="subject" value="{{ old('subject', $letter->subject) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Isi Surat</label>
                <textarea name="body" rows="10" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('body', $letter->body) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Teks ini akan dimasukkan pada template surat resmi saat PDF dibuat.</p>
            </div>
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
            <a href="{{ route('letters.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

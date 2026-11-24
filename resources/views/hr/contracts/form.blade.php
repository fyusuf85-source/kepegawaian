@extends('layouts.app', [
    'title' => $contract->exists ? 'Ubah Kontrak' : (isset($previous) ? 'Perpanjang Kontrak' : 'Tambah Kontrak'),
    'heading' => isset($previous) ? 'Perpanjang Kontrak' : ($contract->exists ? 'Ubah Kontrak' : 'Tambah Kontrak'),
])

@section('content')
    @if(isset($previous))
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Perpanjangan dari kontrak <strong>{{ $previous->contract_number }}</strong>
            ({{ \App\Support\Format::date($previous->start_date) }} – {{ \App\Support\Format::date($previous->end_date) }}).
            Kontrak lama akan ditandai diperpanjang setelah kontrak baru disetujui.
        </div>
    @endif

    <form method="POST"
          action="{{ isset($previous) ? route('contracts.renew', $previous) : ($contract->exists ? route('contracts.update', $contract) : route('contracts.store')) }}"
          class="max-w-4xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($contract->exists && ! isset($previous)) @method('PUT') @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Pegawai <span class="text-rose-600">*</span></label>
                <select name="employee_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" @disabled(isset($previous))>
                    <option value="">— Pilih pegawai —</option>
                    @foreach($employees as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('employee_id', $contract->employee_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @if(isset($previous))
                    <input type="hidden" name="employee_id" value="{{ $contract->employee_id }}">
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Nomor Kontrak</label>
                <input name="contract_number" value="{{ old('contract_number', $contract->contract_number) }}" placeholder="Otomatis bila kosong" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jenis Kontrak <span class="text-rose-600">*</span></label>
                <select name="contract_type" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(['pkwtt' => 'PKWTT (tetap)', 'pkwt' => 'PKWT (waktu tertentu)', 'percobaan' => 'Percobaan', 'khidmah' => 'Khidmah'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('contract_type', $contract->contract_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Mulai <span class="text-rose-600">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Berakhir</label>
                <input type="date" name="end_date" value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Akhir Masa Percobaan</label>
                <input type="date" name="probation_end_date" value="{{ old('probation_end_date', $contract->probation_end_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Ditandatangani</label>
                <input type="date" name="signed_date" value="{{ old('signed_date', $contract->signed_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Catatan</label>
                <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('notes', $contract->notes) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
            <a href="{{ route('contracts.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

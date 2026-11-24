@extends('layouts.app', ['title' => 'Usul Mutasi', 'heading' => 'Usul Mutasi / Perubahan Jabatan'])

@section('content')
    <form method="POST" action="{{ route('mutations.store') }}" class="max-w-4xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Pegawai <span class="text-rose-600">*</span></label>
                <select name="employee_id" id="employee_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Pilih pegawai —</option>
                    @foreach($options['employees'] as $employee)
                        <option value="{{ $employee->id }}" data-unit="{{ $options['orgUnits'][$employee->org_unit_id] ?? '—' }}" data-position="{{ $options['positions'][$employee->position_id] ?? '—' }}" @selected((string) old('employee_id', $mutation->employee_id) === (string) $employee->id)>
                            {{ $employee->full_name }} ({{ $employee->nip }})
                        </option>
                    @endforeach
                </select>
                <p id="current-placement" class="mt-1 text-xs text-slate-500">Penempatan saat ini: —</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jenis <span class="text-rose-600">*</span></label>
                <select name="mutation_type" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(['mutasi' => 'Mutasi', 'promosi' => 'Promosi', 'demosi' => 'Demosi', 'perubahan_jabatan' => 'Perubahan Jabatan', 'penempatan_ulang' => 'Penempatan Ulang'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('mutation_type', $mutation->mutation_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Efektif <span class="text-rose-600">*</span></label>
                <input type="date" name="effective_date" value="{{ old('effective_date', $mutation->effective_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Unit Tujuan</label>
                <select name="to_org_unit_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Tetap di unit saat ini —</option>
                    @foreach($options['orgUnits'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('to_org_unit_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jabatan Tujuan</label>
                <select name="to_position_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Tetap di jabatan saat ini —</option>
                    @foreach($options['positions'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('to_position_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Atasan Langsung Baru</label>
                <select name="to_supervisor_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Tetap —</option>
                    @foreach($options['employees'] as $employee)
                        <option value="{{ $employee->id }}" @selected((string) old('to_supervisor_id') === (string) $employee->id)>{{ $employee->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Alasan <span class="text-rose-600">*</span></label>
                <input name="reason" value="{{ old('reason') }}" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
            Penempatan aktif lama ditutup dengan tanggal efektif H-1, lalu baris penempatan baru dibuat. Riwayat tidak ditimpa.
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Usulan</button>
            <a href="{{ route('mutations.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>

    <script>
        const employeeSelect = document.getElementById('employee_id');
        const placement = document.getElementById('current-placement');
        const syncPlacement = () => {
            const option = employeeSelect.options[employeeSelect.selectedIndex];
            placement.textContent = option && option.dataset.unit
                ? `Penempatan saat ini: ${option.dataset.unit} · ${option.dataset.position}`
                : 'Penempatan saat ini: —';
        };
        employeeSelect.addEventListener('change', syncPlacement);
        syncPlacement();
    </script>
@endsection

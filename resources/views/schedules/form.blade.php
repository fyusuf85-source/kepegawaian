@extends('layouts.app', [
    'title' => $schedule->exists ? 'Ubah Jadwal' : 'Tambah Jadwal',
    'heading' => $schedule->exists ? 'Ubah Jadwal Kerja' : 'Tambah Jadwal Kerja',
])

@section('content')
    <form method="POST" action="{{ $schedule->exists ? route('schedules.update', $schedule) : route('schedules.store') }}"
          class="max-w-4xl space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($schedule->exists) @method('PUT') @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Nama Jadwal <span class="text-rose-600">*</span></label>
                <input name="name" value="{{ old('name', $schedule->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Unit</label>
                <select name="org_unit_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua unit</option>
                    @foreach($options['orgUnits'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('org_unit_id', $schedule->org_unit_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Kategori Pegawai</label>
                <select name="employee_category" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua kategori</option>
                    @foreach(['tetap' => 'Tetap', 'harian' => 'Harian', 'khidmah' => 'Khidmah'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('employee_category', $schedule->employee_category) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 pt-6 text-sm font-medium text-slate-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $schedule->is_active ?? true)) class="rounded">
                Jadwal Aktif
            </label>
            <div>
                <label class="block text-sm font-medium text-slate-700">Berlaku Sejak <span class="text-rose-600">*</span></label>
                <input type="date" name="effective_from" value="{{ old('effective_from', $schedule->effective_from?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Berlaku Sampai</label>
                <input type="date" name="effective_to" value="{{ old('effective_to', $schedule->effective_to?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Pola Mingguan</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="py-2">Hari</th><th class="py-2">Hari Kerja</th><th class="py-2">Shift</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @for($day = 1; $day <= 7; $day++)
                        @php($entry = $days->get($day))
                        <tr>
                            <td class="py-2 font-medium">{{ \App\Support\Format::dayName($day) }}</td>
                            <td class="py-2">
                                <input type="hidden" name="days[{{ $day }}][is_workday]" value="0">
                                <input type="checkbox" name="days[{{ $day }}][is_workday]" value="1"
                                       @checked(old("days.$day.is_workday", $entry?->is_workday ?? ($day <= 5)))
                                       class="rounded border-slate-300">
                            </td>
                            <td class="py-2">
                                <select name="days[{{ $day }}][shift_id]" class="w-64 rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                                    <option value="">— Libur / tanpa shift —</option>
                                    @foreach($options['shifts'] as $shift)
                                        <option value="{{ $shift->id }}" @selected((string) old("days.$day.shift_id", $entry?->shift_id) === (string) $shift->id)>
                                            {{ $shift->name }} ({{ \App\Support\Format::time($shift->start_time) }}–{{ \App\Support\Format::time($shift->end_time) }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endfor
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
            <a href="{{ route('schedules.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

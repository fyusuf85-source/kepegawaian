@extends('layouts.app', ['title' => 'Saldo Cuti', 'heading' => 'Saldo Cuti', 'subheading' => 'Entitlement, pemakaian, dan penyesuaian saldo per pegawai'])

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <form method="POST" action="{{ route('leave.balances.store') }}"
              class="space-y-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <p class="text-sm font-semibold text-slate-800">Tetapkan saldo</p>
            <div>
                <label class="block text-xs font-medium text-slate-500">Pegawai</label>
                <select name="employee_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Pilih pegawai —</option>
                    @foreach($employees as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('employee_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Jenis</label>
                <select name="leave_type_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Pilih jenis —</option>
                    @foreach($types as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('leave_type_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500">Tahun</label>
                    <input type="number" name="year" value="{{ old('year', $year) }}" min="2000" max="2100" required
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">Hak (hari)</label>
                    <input type="number" name="entitlement_days" value="{{ old('entitlement_days', 12) }}" min="0" max="366" step="0.5" required
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Penyesuaian (hari)</label>
                <input type="number" name="adjustment_days" value="{{ old('adjustment_days', 0) }}" min="-366" max="366" step="0.5"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Nilai positif menambah saldo, negatif mengurangi.</p>
            </div>
            <button class="w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Saldo</button>
        </form>

        <div class="lg:col-span-2">
            <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div>
                    <label class="block text-xs font-medium text-slate-500">Tahun</label>
                    <input type="number" name="year" value="{{ $year }}" min="2000" max="2100"
                           class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">Pegawai</label>
                    <select name="employee_id" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua</option>
                        @foreach($employees as $id => $name)
                            <option value="{{ $id }}" @selected((string) request('employee_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">Jenis</label>
                    <select name="leave_type_id" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua</option>
                        @foreach($types as $id => $name)
                            <option value="{{ $id }}" @selected((string) request('leave_type_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm hover:bg-slate-100">Tampilkan</button>
            </form>

            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Pegawai</th>
                            <th class="px-4 py-3">Jenis</th>
                            <th class="px-4 py-3">Hak</th>
                            <th class="px-4 py-3">Penyesuaian</th>
                            <th class="px-4 py-3">Terpakai</th>
                            <th class="px-4 py-3">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($balances as $balance)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $balance->employee?->full_name }}</p>
                                <p class="text-xs text-slate-500">{{ $balance->employee?->nip }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ $balance->leaveType?->name }}</td>
                            <td class="px-4 py-3">{{ rtrim(rtrim(number_format((float) $balance->entitlement_days, 1, ',', '.'), '0'), ',') }}</td>
                            <td class="px-4 py-3">{{ rtrim(rtrim(number_format((float) $balance->adjustment_days, 1, ',', '.'), '0'), ',') }}</td>
                            <td class="px-4 py-3">{{ rtrim(rtrim(number_format((float) $balance->used_days, 1, ',', '.'), '0'), ',') }}</td>
                            <td class="px-4 py-3 font-semibold">
                                {{ rtrim(rtrim(number_format($balance->remainingDays(), 1, ',', '.'), '0'), ',') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada saldo pada tahun {{ $year }}.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $balances->links() }}</div>
        </div>
    </div>
@endsection

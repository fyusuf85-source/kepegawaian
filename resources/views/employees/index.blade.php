@extends('layouts.app', ['title' => 'Data Pegawai', 'heading' => 'Data Pegawai', 'subheading' => 'Daftar pegawai sesuai cakupan akses Anda'])

@section('content')
    <div class="flex flex-col gap-4">
        <form method="GET" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
            <input name="q" value="{{ request('q') }}" placeholder="Nama, NIP, atau email"
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="category" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua kategori</option>
                @foreach(['tetap' => 'Tetap', 'harian' => 'Harian', 'khidmah' => 'Khidmah'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua status</option>
                @foreach(['aktif' => 'Aktif', 'probation' => 'Probation', 'cuti_panjang' => 'Cuti Panjang', 'nonaktif' => 'Nonaktif', 'berhenti' => 'Berhenti'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="org_unit_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua unit</option>
                @foreach($orgUnits as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('org_unit_id') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Terapkan</button>
        </form>

        <div class="flex items-center justify-between gap-3">
            <div class="text-sm text-slate-500">
                Total: <span class="font-semibold text-slate-900">{{ $employees->total() }}</span> pegawai
            </div>
            <div class="flex gap-2">
                @if($canImport)
                    <a href="{{ route('employees.import.form') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Impor</a>
                @endif
                @if($canExport)
                    <a href="{{ route('employees.export') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ekspor</a>
                @endif
                @if($canCreate)
                    <a href="{{ route('employees.create') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tambah Pegawai</a>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">NIP</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Unit</th>
                    <th class="px-4 py-3">Jabatan</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($employees as $employee)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs">{{ $employee->nip }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('employees.show', $employee) }}" class="font-medium text-emerald-700 hover:underline">{{ $employee->full_name }}</a>
                        <p class="text-xs text-slate-500">{{ $employee->email ?: '—' }}</p>
                    </td>
                    <td class="px-4 py-3">{{ $employee->orgUnit?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $employee->position?->name ?? '—' }}</td>
                    <td class="px-4 py-3 capitalize">{{ $employee->employee_category }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $employee->employment_status }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('employees.show', $employee) }}" class="text-emerald-700 hover:underline">Detail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Tidak ada pegawai yang cocok dengan filter.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>
@endsection

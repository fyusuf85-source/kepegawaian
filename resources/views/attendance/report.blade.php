@extends('layouts.app', ['title' => 'Rekap Absensi', 'heading' => 'Rekap Absensi', 'subheading' => 'Ringkasan kehadiran dan menit kerja per pegawai'])

@section('content')
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label class="block text-xs font-medium text-slate-500">Dari</label>
            <input type="date" name="from" value="{{ $from }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Sampai</label>
            <input type="date" name="to" value="{{ $to }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
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
            <label class="block text-xs font-medium text-slate-500">Status</label>
            <select name="status" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tampilkan</button>
    </form>

    @php($total = (int) ($summary->total ?? 0))
    <div class="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach([
            'hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'pulang_awal' => 'Pulang awal',
            'izin' => 'Izin', 'sakit' => 'Sakit', 'tidak_hadir' => 'Tidak hadir',
        ] as $key => $label)
            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold">{{ (int) ($summary->{$key} ?? 0) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-3 flex flex-wrap gap-4 rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm">
        <span>Total baris: <strong>{{ $total }}</strong></span>
        <span>Total menit terlambat: <strong>{{ (int) ($summary->total_late_minutes ?? 0) }}</strong></span>
        <span>Total menit pulang awal: <strong>{{ (int) ($summary->total_early_minutes ?? 0) }}</strong></span>
        <span>Total menit kerja: <strong>{{ (int) ($summary->total_work_minutes ?? 0) }}</strong></span>
        <span>Dispensasi: <strong>{{ (int) ($summary->dispensasi ?? 0) }}</strong></span>
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Pegawai</th>
                    <th class="px-4 py-3">Shift</th>
                    <th class="px-4 py-3">Masuk</th>
                    <th class="px-4 py-3">Pulang</th>
                    <th class="px-4 py-3">Terlambat</th>
                    <th class="px-4 py-3">Pulang awal</th>
                    <th class="px-4 py-3">Kerja</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($rows as $row)
                <tr>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($row->work_date) }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('attendance.show', $row) }}" class="text-emerald-700 hover:underline">{{ $row->employee?->full_name }}</a>
                        <div class="text-xs text-slate-500">{{ $row->employee?->nip }} · {{ $row->employee?->orgUnit?->name }}</div>
                    </td>
                    <td class="px-4 py-3 text-xs">{{ $row->shift?->name }}</td>
                    <td class="px-4 py-3">{{ $row->checkInLocal()?->format('H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $row->checkOutLocal()?->format('H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $row->late_minutes > 0 ? $row->late_minutes.' mnt' : '—' }}</td>
                    <td class="px-4 py-3">{{ $row->early_leave_minutes > 0 ? $row->early_leave_minutes.' mnt' : '—' }}</td>
                    <td class="px-4 py-3">{{ intdiv($row->work_minutes, 60) }}j {{ $row->work_minutes % 60 }}m</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $statuses[$row->status] ?? $row->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="9" class="px-4 py-6 text-center text-slate-500">Tidak ada data absensi pada rentang tersebut.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
@endsection

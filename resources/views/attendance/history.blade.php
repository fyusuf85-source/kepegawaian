@extends('layouts.app', ['title' => 'Riwayat Absensi', 'heading' => 'Riwayat Absensi Saya', 'subheading' => $employee->full_name.' · '.$employee->nip])

@section('content')
    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach(['hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'pulang_awal' => 'Pulang awal', 'izin' => 'Izin', 'sakit' => 'Sakit', 'tidak_hadir' => 'Tidak hadir'] as $key => $label)
            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <p class="text-xs text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold">{{ (int) ($summary->{$key} ?? 0) }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" class="mt-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label class="block text-xs font-medium text-slate-500">Bulan</label>
            <input type="month" name="month" value="{{ $month }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <button class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm hover:bg-slate-100">Tampilkan</button>
        <div class="ml-auto text-xs text-slate-500">
            Total menit terlambat: <strong>{{ (int) ($summary->total_late_minutes ?? 0) }}</strong> ·
            Total menit kerja: <strong>{{ (int) ($summary->total_work_minutes ?? 0) }}</strong>
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
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
                    <td class="px-4 py-3">
                        <a href="{{ route('attendance.show', $row) }}" class="text-emerald-700 hover:underline">{{ \App\Support\Format::date($row->work_date) }}</a>
                        <div class="text-xs text-slate-500">{{ \App\Support\Format::dayName($row->work_date->dayOfWeekIso) }}</div>
                    </td>
                    <td class="px-4 py-3 text-xs">{{ $row->shift?->name }}</td>
                    <td class="px-4 py-3">{{ $row->checkInLocal()?->format('H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $row->checkOutLocal()?->format('H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $row->late_minutes > 0 ? $row->late_minutes.' mnt' : '—' }}</td>
                    <td class="px-4 py-3">{{ $row->early_leave_minutes > 0 ? $row->early_leave_minutes.' mnt' : '—' }}</td>
                    <td class="px-4 py-3">{{ intdiv($row->work_minutes, 60) }}j {{ $row->work_minutes % 60 }}m</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ \App\Models\Attendance::statusLabels()[$row->status] ?? $row->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">Belum ada data absensi pada bulan ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
@endsection

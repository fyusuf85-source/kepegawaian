@extends('layouts.app', ['title' => 'Izin & Cuti', 'heading' => 'Izin & Cuti', 'subheading' => 'Pengajuan izin, cuti tahunan, sakit, dan dinas luar'])

@section('content')
    <div class="flex flex-wrap items-center gap-3">
        <form method="GET" class="flex flex-1 flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-slate-500">Status</label>
                <select name="status" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
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
            <div>
                <label class="block text-xs font-medium text-slate-500">Dari</label>
                <input type="date" name="from" value="{{ request('from') }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Sampai</label>
                <input type="date" name="to" value="{{ request('to') }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm hover:bg-slate-100">Filter</button>
        </form>
        @if($canRequest)
            <a href="{{ route('leave.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Ajukan Izin/Cuti</a>
        @endif
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Pegawai</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Hari</th>
                    <th class="px-4 py-3">Diajukan</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($requests as $leave)
                <tr>
                    <td class="px-4 py-3">
                        <a href="{{ route('leave.show', $leave) }}" class="font-medium text-emerald-700 hover:underline">{{ $leave->employee?->full_name }}</a>
                        <div class="text-xs text-slate-500">{{ $leave->employee?->nip }} · {{ $leave->employee?->orgUnit?->name }}</div>
                    </td>
                    <td class="px-4 py-3 text-xs">{{ $leave->leaveType?->name }}</td>
                    <td class="px-4 py-3 text-xs">
                        {{ \App\Support\Format::date($leave->start_date) }} – {{ \App\Support\Format::date($leave->end_date) }}
                    </td>
                    <td class="px-4 py-3">{{ $leave->total_days }}</td>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::dateTime($leave->submitted_at) }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $statuses[$leave->status] ?? $leave->status }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada pengajuan izin/cuti.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
@endsection

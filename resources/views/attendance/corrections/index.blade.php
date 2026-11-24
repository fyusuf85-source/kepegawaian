@extends('layouts.app', ['title' => 'Koreksi Absensi', 'heading' => 'Koreksi Absensi', 'subheading' => 'Pengajuan koreksi dua tingkat: atasan langsung → HRD'])

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
            <a href="{{ route('corrections.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Ajukan Koreksi</a>
        @endif
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Pegawai</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Alasan</th>
                    <th class="px-4 py-3">Atasan</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($corrections as $correction)
                <tr>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($correction->work_date) }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('corrections.show', $correction) }}" class="text-emerald-700 hover:underline">{{ $correction->employee?->full_name }}</a>
                        <div class="text-xs text-slate-500">{{ $correction->employee?->nip }} · {{ $correction->employee?->orgUnit?->name }}</div>
                    </td>
                    <td class="px-4 py-3 text-xs">{{ \App\Models\AttendanceCorrection::typeLabels()[$correction->correction_type] ?? $correction->correction_type }}</td>
                    <td class="px-4 py-3 text-xs">{{ \Illuminate\Support\Str::limit($correction->reason, 60) }}</td>
                    <td class="px-4 py-3 text-xs">{{ $correction->supervisor?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $statuses[$correction->status] ?? $correction->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada pengajuan koreksi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $corrections->links() }}</div>
@endsection

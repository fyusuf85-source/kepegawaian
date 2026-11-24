@extends('layouts.app', ['title' => 'Penugasan Jadwal', 'heading' => 'Penugasan Jadwal', 'subheading' => 'Tetapkan jadwal kerja kepada pegawai'])

@section('content')
    @if($canManage)
        <form method="POST" action="{{ route('schedule-assignments.store') }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-6">
            @csrf
            <select name="employee_id" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Pegawai</option>
                @foreach($employees as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <select name="work_schedule_id" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Jadwal</option>
                @foreach($schedules as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <select name="work_location_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Lokasi Kerja (Opsional)</option>
                @foreach($locations as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <input type="date" name="effective_from" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <input type="date" name="effective_to" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tetapkan</button>
        </form>
    @endif

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Pegawai</th><th class="px-4 py-3">Jadwal</th><th class="px-4 py-3">Lokasi</th><th class="px-4 py-3">Berlaku</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($assignments as $assignment)
                <tr>
                    <td class="px-4 py-3">{{ $assignment->employee?->full_name }} <span class="text-xs text-slate-500">{{ $assignment->employee?->nip }}</span></td>
                    <td class="px-4 py-3">{{ $assignment->workSchedule?->name }}</td>
                    <td class="px-4 py-3">{{ $assignment->workLocation?->name ?? '<span class="text-slate-400">Lokasi profil pegawai</span>' }}</td>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($assignment->effective_from) }} – {{ $assignment->effective_to ? \App\Support\Format::date($assignment->effective_to) : 'sekarang' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($canManage)
                            <form method="POST" action="{{ route('schedule-assignments.destroy', $assignment) }}" onsubmit="return confirm('Hapus penugasan ini?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-600 hover:underline">Hapus</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada penugasan jadwal.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $assignments->links() }}</div>
@endsection

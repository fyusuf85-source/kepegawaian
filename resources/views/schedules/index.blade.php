@extends('layouts.app', ['title' => 'Jadwal Kerja', 'heading' => 'Jadwal Kerja', 'subheading' => 'Pola jadwal per unit dan kategori pegawai'])

@section('content')
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">Total {{ $schedules->total() }} jadwal</p>
        <div class="flex gap-2">
            <a href="{{ route('schedule-assignments.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">Penugasan</a>
            @if($canManage)
                <a href="{{ route('schedules.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tambah Jadwal</a>
            @endif
        </div>
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Unit</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Berlaku</th><th class="px-4 py-3">Hari Kerja</th><th class="px-4 py-3">Pegawai</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($schedules as $schedule)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $schedule->name }}</td>
                    <td class="px-4 py-3">{{ $schedule->orgUnit?->name ?? 'Semua unit' }}</td>
                    <td class="px-4 py-3">{{ $schedule->employee_category ?? 'Semua' }}</td>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($schedule->effective_from) }} – {{ $schedule->effective_to ? \App\Support\Format::date($schedule->effective_to) : 'sekarang' }}</td>
                    <td class="px-4 py-3">{{ $schedule->days_count }}</td>
                    <td class="px-4 py-3">{{ $schedule->assignments_count }}</td>
                    <td class="px-4 py-3">{{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($canManage)
                            <a href="{{ route('schedules.edit', $schedule) }}" class="text-emerald-700 hover:underline">Ubah</a>
                            <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" class="ml-3 inline" onsubmit="return confirm('Hapus jadwal ini?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-600 hover:underline">Hapus</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">Belum ada jadwal kerja.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $schedules->links() }}</div>
@endsection

@extends('layouts.app', ['title' => 'Dispensasi Absensi', 'heading' => 'Dispensasi Absensi', 'subheading' => 'Pengajuan presensi tanpa GPS/kamera atau untuk penugasan luar'])

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
            <button class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm hover:bg-slate-100">Filter</button>
        </form>
        @if($canRequest)
            <a href="{{ route('exceptions.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Ajukan Dispensasi</a>
        @endif
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Pegawai</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Jam diajukan</th>
                    <th class="px-4 py-3">Alasan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($exceptions as $exception)
                <tr>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($exception->work_date) }}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium">{{ $exception->employee?->full_name }}</p>
                        <p class="text-xs text-slate-500">{{ $exception->employee?->nip }} · {{ $exception->employee?->orgUnit?->name }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs">{{ $types[$exception->type] ?? $exception->type }}</td>
                    <td class="px-4 py-3 text-xs">
                        {{ $exception->check_in_at?->copy()->timezone('Asia/Jakarta')->format('H:i') ?? '—' }} –
                        {{ $exception->check_out_at?->copy()->timezone('Asia/Jakarta')->format('H:i') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs">{{ \Illuminate\Support\Str::limit($exception->reason, 70) }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $statuses[$exception->status] ?? $exception->status }}</span>
                        @if($exception->approver)
                            <div class="mt-1 text-xs text-slate-500">{{ $exception->approver->name }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @if($exception->evidence_path)
                            <a href="{{ route('exceptions.evidence', $exception) }}" class="text-emerald-700 hover:underline">Bukti</a>
                        @endif
                        @if($canManage && $exception->status === 'menunggu')
                            <div class="mt-2 flex flex-col gap-1">
                                <form method="POST" action="{{ route('exceptions.approve', $exception) }}">
                                    @csrf
                                    <button class="w-full rounded-md bg-emerald-700 px-2 py-1 font-medium text-white hover:bg-emerald-800">Setujui</button>
                                </form>
                                <form method="POST" action="{{ route('exceptions.reject', $exception) }}" class="flex gap-1">
                                    @csrf
                                    <input name="reason" required minlength="5" placeholder="Alasan tolak"
                                           class="w-full rounded-md border border-slate-300 px-2 py-1">
                                    <button class="rounded-md border border-rose-300 px-2 py-1 text-rose-700 hover:bg-rose-50">Tolak</button>
                                </form>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Belum ada pengajuan dispensasi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $exceptions->links() }}</div>
@endsection

@extends('layouts.app', ['title' => 'Kontrak Kerja', 'heading' => 'Kontrak Kerja', 'subheading' => 'Kontrak, perpanjangan, dan pengingat masa berakhir'])

@section('content')
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label class="block text-xs font-medium text-slate-500">Cari</label>
            <input name="q" value="{{ request('q') }}" placeholder="Nomor / nama pegawai" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
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
                @foreach(['draf', 'aktif', 'akan_berakhir', 'berakhir', 'diperpanjang', 'dibatalkan'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm hover:bg-slate-100">Filter</button>
        @if($canManage)
            <a href="{{ route('contracts.create') }}" class="ml-auto rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tambah Kontrak</a>
        @endif
    </form>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Nomor</th><th class="px-4 py-3">Pegawai</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Mulai</th><th class="px-4 py-3">Berakhir</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Dokumen</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($contracts as $contract)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs"><a href="{{ route('contracts.show', $contract) }}" class="text-emerald-700 hover:underline">{{ $contract->contract_number }}</a></td>
                    <td class="px-4 py-3">{{ $contract->employee?->full_name }}<div class="text-xs text-slate-500">{{ $contract->employee?->nip }} · {{ $contract->employee?->orgUnit?->name }}</div></td>
                    <td class="px-4 py-3">{{ strtoupper($contract->contract_type) }}</td>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($contract->start_date) }}</td>
                    <td class="px-4 py-3 text-xs">{{ $contract->end_date ? \App\Support\Format::date($contract->end_date) : 'Tidak berbatas' }}</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $contract->status) }}</span></td>
                    <td class="px-4 py-3 text-xs">{{ $contract->document_path ? 'Ada' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Belum ada kontrak.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $contracts->links() }}</div>
@endsection

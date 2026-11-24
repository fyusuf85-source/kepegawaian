@extends('layouts.app', ['title' => 'Mutasi', 'heading' => 'Mutasi & Perubahan Jabatan', 'subheading' => 'Riwayat penempatan diperbarui tanpa menimpa data lama'])

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div><label class="block text-xs font-medium text-slate-500">Cari Pegawai</label><input name="q" value="{{ request('q') }}" placeholder="Nama / NIP" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Status</label>
                <select name="status" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    @foreach(['draf', 'menunggu', 'disetujui', 'ditolak', 'diterapkan'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm hover:bg-slate-100">Filter</button>
        </form>
        @if($canManage)
            <a href="{{ route('mutations.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Usul Mutasi</a>
        @endif
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Pegawai</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Dari</th><th class="px-4 py-3">Ke</th><th class="px-4 py-3">Efektif</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($mutations as $mutation)
                <tr>
                    <td class="px-4 py-3"><a href="{{ route('mutations.show', $mutation) }}" class="font-medium text-emerald-700 hover:underline">{{ $mutation->employee?->full_name }}</a><div class="text-xs text-slate-500">{{ $mutation->employee?->nip }}</div></td>
                    <td class="px-4 py-3">{{ str_replace('_', ' ', $mutation->mutation_type) }}</td>
                    <td class="px-4 py-3 text-xs">{{ $mutation->fromOrgUnit?->name ?? '—' }}<div class="text-slate-500">{{ $mutation->fromPosition?->name ?? '—' }}</div></td>
                    <td class="px-4 py-3 text-xs">{{ $mutation->toOrgUnit?->name ?? '—' }}<div class="text-slate-500">{{ $mutation->toPosition?->name ?? '—' }}</div></td>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($mutation->effective_date) }}</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $mutation->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada usulan mutasi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $mutations->links() }}</div>
@endsection

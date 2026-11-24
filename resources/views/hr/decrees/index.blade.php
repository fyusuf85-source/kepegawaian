@extends('layouts.app', ['title' => 'Surat Keputusan', 'heading' => 'Surat Keputusan (SK)', 'subheading' => 'Pengangkatan, penempatan, mutasi, dan pemberhentian'])

@section('content')
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div><label class="block text-xs font-medium text-slate-500">Cari</label><input name="q" value="{{ request('q') }}" placeholder="Nomor / judul / pegawai" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Jenis</label>
            <select name="decree_type" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach(['pengangkatan', 'penempatan', 'perubahan_jabatan', 'mutasi', 'pemberhentian', 'lain'] as $type)
                    <option value="{{ $type }}" @selected(request('decree_type') === $type)>{{ str_replace('_', ' ', $type) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Status</label>
            <select name="status" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach(['draf', 'menunggu_persetujuan', 'disetujui', 'ditolak', 'dibatalkan'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm hover:bg-slate-100">Filter</button>
        @if($canManage)
            <a href="{{ route('decrees.create') }}" class="ml-auto rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tambah SK</a>
        @endif
    </form>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Nomor</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Pegawai</th><th class="px-4 py-3">Judul</th><th class="px-4 py-3">Tanggal SK</th><th class="px-4 py-3">Efektif</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($decrees as $decree)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs"><a href="{{ route('decrees.show', $decree) }}" class="text-emerald-700 hover:underline">{{ $decree->number }}</a></td>
                    <td class="px-4 py-3">{{ str_replace('_', ' ', $decree->decree_type) }}</td>
                    <td class="px-4 py-3">{{ $decree->employee?->full_name }}<div class="text-xs text-slate-500">{{ $decree->employee?->nip }}</div></td>
                    <td class="px-4 py-3">{{ $decree->title }}</td>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($decree->decree_date) }}</td>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($decree->effective_date) }}</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $decree->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Belum ada SK.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $decrees->links() }}</div>
@endsection

@extends('layouts.app', ['title' => 'Lowongan', 'heading' => 'Lowongan Rekrutmen', 'subheading' => 'Kelola lowongan dan kuota penerimaan pegawai'])

@section('content')
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label class="block text-xs font-medium text-slate-500">Cari</label>
            <input name="q" value="{{ request('q') }}" placeholder="Kode atau judul" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Status</label>
            <select name="status" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach(['draft' => 'Draf', 'dibuka' => 'Dibuka', 'ditutup' => 'Ditutup', 'dibatalkan' => 'Dibatalkan'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm hover:bg-slate-100">Filter</button>
        @if($canManage)
            <a href="{{ route('vacancies.create') }}" class="ml-auto rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tambah Lowongan</a>
        @endif
    </form>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Kode</th><th class="px-4 py-3">Judul</th><th class="px-4 py-3">Unit / Jabatan</th>
                    <th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Kuota</th><th class="px-4 py-3">Periode</th>
                    <th class="px-4 py-3">Pelamar</th><th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($vacancies as $vacancy)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs">{{ $vacancy->code }}</td>
                    <td class="px-4 py-3"><a href="{{ route('vacancies.show', $vacancy) }}" class="font-medium text-emerald-700 hover:underline">{{ $vacancy->title }}</a></td>
                    <td class="px-4 py-3">{{ $vacancy->orgUnit?->name }}<div class="text-xs text-slate-500">{{ $vacancy->position?->name ?? '—' }}</div></td>
                    <td class="px-4 py-3">{{ ucfirst($vacancy->employee_category) }}</td>
                    <td class="px-4 py-3">{{ $vacancy->quota }}</td>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($vacancy->open_date) }} – {{ \App\Support\Format::date($vacancy->close_date) }}</td>
                    <td class="px-4 py-3">{{ $vacancy->applicants_count }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $vacancy->status === 'dibuka' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($vacancy->status) }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">Belum ada lowongan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $vacancies->links() }}</div>
@endsection

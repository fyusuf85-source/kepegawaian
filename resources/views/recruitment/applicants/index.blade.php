@extends('layouts.app', ['title' => 'Pelamar', 'heading' => 'Data Pelamar', 'subheading' => 'Proses seleksi hingga konversi menjadi pegawai'])

@section('content')
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label class="block text-xs font-medium text-slate-500">Cari</label>
            <input name="q" value="{{ request('q') }}" placeholder="Nama, kode, telepon" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Lowongan</label>
            <select name="vacancy_id" class="mt-1 max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach($vacancies as $id => $title)
                    <option value="{{ $id }}" @selected((string) request('vacancy_id') === (string) $id)>{{ $title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Status</label>
            <select name="status" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach(['baru', 'seleksi_administrasi', 'wawancara', 'dipertimbangkan', 'diterima', 'ditolak'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm hover:bg-slate-100">Filter</button>
        @if($canManage)
            <a href="{{ route('applicants.create') }}" class="ml-auto rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tambah Pelamar</a>
        @endif
    </form>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Lowongan</th><th class="px-4 py-3">Pendidikan</th><th class="px-4 py-3">Wawancara</th><th class="px-4 py-3">Skor</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Pegawai</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($applicants as $applicant)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs">{{ $applicant->code }}</td>
                    <td class="px-4 py-3"><a href="{{ route('applicants.show', $applicant) }}" class="font-medium text-emerald-700 hover:underline">{{ $applicant->full_name }}</a><div class="text-xs text-slate-500">{{ $applicant->phone }}</div></td>
                    <td class="px-4 py-3">{{ $applicant->vacancy?->title }}<div class="text-xs text-slate-500">{{ $applicant->vacancy?->orgUnit?->name }}</div></td>
                    <td class="px-4 py-3">{{ $applicant->education?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $applicant->interviews_count }}</td>
                    <td class="px-4 py-3">{{ $applicant->final_score ?? '—' }}</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $applicant->status) }}</span></td>
                    <td class="px-4 py-3 text-xs">{{ $applicant->convertedEmployee?->nip ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">Belum ada pelamar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $applicants->links() }}</div>
@endsection

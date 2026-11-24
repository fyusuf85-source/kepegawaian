@extends('layouts.app', ['title' => $vacancy->title, 'heading' => $vacancy->title, 'subheading' => 'Lowongan '.$vacancy->code])

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs uppercase text-slate-500">Unit</dt><dd class="font-medium">{{ $vacancy->orgUnit?->name }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Jabatan</dt><dd class="font-medium">{{ $vacancy->position?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Kategori</dt><dd class="font-medium">{{ ucfirst($vacancy->employee_category) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Kuota</dt><dd class="font-medium">{{ $vacancy->quota }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Periode</dt><dd class="font-medium">{{ \App\Support\Format::date($vacancy->open_date) }} – {{ \App\Support\Format::date($vacancy->close_date) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Status</dt><dd class="font-medium">{{ ucfirst($vacancy->status) }}</dd></div>
                </dl>
                @if($vacancy->description)
                    <div class="mt-4 border-t border-slate-100 pt-4 text-sm whitespace-pre-line">{{ $vacancy->description }}</div>
                @endif
                @if($vacancy->requirements)
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <p class="text-xs font-semibold uppercase text-slate-500">Persyaratan</p>
                        <div class="mt-1 text-sm whitespace-pre-line">{{ $vacancy->requirements }}</div>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700">Pelamar ({{ $vacancy->applicants->count() }})</h2>
                    @if($canManageApplicants)
                        <a href="{{ route('applicants.create', ['vacancy_id' => $vacancy->id]) }}" class="text-sm text-emerald-700 hover:underline">Tambah Pelamar</a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr><th class="px-4 py-2">Kode</th><th class="px-4 py-2">Nama</th><th class="px-4 py-2">Telepon</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Skor</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($vacancy->applicants as $applicant)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $applicant->code }}</td>
                                <td class="px-4 py-2"><a href="{{ route('applicants.show', $applicant) }}" class="text-emerald-700 hover:underline">{{ $applicant->full_name }}</a></td>
                                <td class="px-4 py-2">{{ $applicant->phone }}</td>
                                <td class="px-4 py-2">{{ str_replace('_', ' ', $applicant->status) }}</td>
                                <td class="px-4 py-2">{{ $applicant->final_score ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-5 text-center text-slate-500">Belum ada pelamar.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @if($canManage)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-700">Aksi</h2>
                    <div class="mt-3 space-y-2 text-sm">
                        <a href="{{ route('vacancies.edit', $vacancy) }}" class="block rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50">Ubah lowongan</a>
                        <form method="POST" action="{{ route('vacancies.status', $vacancy) }}" class="flex gap-2">
                            @csrf
                            <select name="status" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                @foreach(['draft' => 'Draf', 'dibuka' => 'Dibuka', 'ditutup' => 'Ditutup', 'dibatalkan' => 'Dibatalkan'] as $value => $label)
                                    <option value="{{ $value }}" @selected($vacancy->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white">Simpan</button>
                        </form>
                        <form method="POST" action="{{ route('vacancies.destroy', $vacancy) }}" onsubmit="return confirm('Hapus lowongan ini?')">
                            @csrf @method('DELETE')
                            <button class="w-full rounded-lg border border-rose-200 px-3 py-2 text-left text-rose-600 hover:bg-rose-50">Hapus lowongan</button>
                        </form>
                    </div>
                </div>
            @endif
            <a href="{{ route('vacancies.index') }}" class="block text-sm text-slate-600 hover:underline">← Kembali ke daftar lowongan</a>
        </div>
    </div>
@endsection

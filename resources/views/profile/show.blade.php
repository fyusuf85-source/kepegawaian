@extends('layouts.app', ['title' => 'Profil Saya', 'heading' => 'Profil Saya', 'subheading' => $employee->nip])

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            @if($employee->photo_path)
                <img src="{{ route('employees.photo', $employee) }}" alt="Foto" class="h-16 w-16 rounded-full object-cover">
            @else
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-xl font-semibold text-emerald-700">{{ mb_substr($employee->full_name, 0, 1) }}</div>
            @endif
            <div>
                <p class="text-lg font-semibold">{{ $employee->full_name }}</p>
                <p class="text-sm text-slate-500">{{ $employee->position?->name ?? '—' }} · {{ $employee->orgUnit?->name ?? '—' }}</p>
            </div>
        </div>
        <a href="{{ route('password.change') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">Ubah Kata Sandi</a>
    </div>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Data Pribadi</h2>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 xl:grid-cols-3">
            <div><dt class="text-slate-500">NIK</dt><dd class="font-medium">{{ $employee->nik ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Jenis Kelamin</dt><dd class="font-medium capitalize">{{ $employee->gender }}</dd></div>
            <div><dt class="text-slate-500">Tempat, Tanggal Lahir</dt><dd class="font-medium">{{ $employee->birth_place ?: '—' }}, {{ \App\Support\Format::date($employee->birth_date) }}</dd></div>
            <div><dt class="text-slate-500">Telepon</dt><dd class="font-medium">{{ $employee->phone ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $employee->email ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Alamat</dt><dd class="font-medium">{{ $employee->address ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Tanggal Masuk</dt><dd class="font-medium">{{ \App\Support\Format::date($employee->hire_date) }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="font-medium capitalize">{{ $employee->employment_status }}</dd></div>
            <div><dt class="text-slate-500">Kategori</dt><dd class="font-medium capitalize">{{ $employee->employee_category }}</dd></div>
        </dl>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Keluarga</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse($employee->families as $family)
                    <li class="flex items-center justify-between py-2">
                        <span>{{ $family->name }} <span class="text-xs text-slate-500 capitalize">({{ $family->relation }})</span></span>
                        <span class="text-xs text-slate-500">{{ \App\Support\Format::date($family->birth_date) }}</span>
                    </li>
                @empty
                    <li class="py-3 text-slate-500">Belum ada data.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Pendidikan</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse($employee->educations as $education)
                    <li class="py-2">
                        <p class="font-medium">{{ $education->education?->name }} · {{ $education->institution }}</p>
                        <p class="text-xs text-slate-500">{{ $education->major ?: '—' }} · {{ $education->graduation_year ?: '—' }}</p>
                    </li>
                @empty
                    <li class="py-3 text-slate-500">Belum ada data.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Dokumen Saya</h2>
        <ul class="mt-3 divide-y divide-slate-100 text-sm">
            @forelse($employee->documents as $document)
                <li class="flex items-center justify-between py-2">
                    <span class="uppercase">{{ $document->doc_type }}</span>
                    <a href="{{ route('employees.documents.download', [$employee, $document]) }}" class="text-emerald-700 hover:underline">Unduh</a>
                </li>
            @empty
                <li class="py-3 text-slate-500">Belum ada dokumen.</li>
            @endforelse
        </ul>
    </section>
@endsection

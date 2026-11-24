@extends('layouts.app', ['title' => 'SK '.$decree->number, 'heading' => 'SK '.$decree->number, 'subheading' => $decree->employee?->full_name])

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs uppercase text-slate-500">Jenis</dt><dd class="font-medium">{{ str_replace('_', ' ', $decree->decree_type) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Status</dt><dd>{{ str_replace('_', ' ', $decree->status) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Pegawai</dt><dd><a class="text-emerald-700 hover:underline" href="{{ route('employees.show', $decree->employee_id) }}">{{ $decree->employee?->full_name }} ({{ $decree->employee?->nip }})</a></dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Unit / Jabatan</dt><dd>{{ $decree->employee?->orgUnit?->name }} · {{ $decree->employee?->position?->name }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Tanggal SK</dt><dd>{{ \App\Support\Format::date($decree->decree_date) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Efektif</dt><dd>{{ \App\Support\Format::date($decree->effective_date) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Disetujui</dt><dd>{{ $decree->approver?->name ?? '—' }}{{ $decree->approved_at ? ' · '.\App\Support\Format::dateTime($decree->approved_at) : '' }}</dd></div>
                </dl>
                <div class="mt-4 border-t border-slate-100 pt-3">
                    <p class="text-sm font-semibold">{{ $decree->title }}</p>
                    @if($decree->description)<p class="mt-1 text-sm whitespace-pre-line text-slate-600">{{ $decree->description }}</p>@endif
                </div>
            </div>

            @include('partials.approval-panel', ['approval' => $approval])

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Dokumen PDF</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('decrees.pdf', $decree) }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Buat / Unduh PDF</a>
                    @if($decree->document_path)
                        <a href="{{ route('decrees.download', $decree) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Unduh Tersimpan</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @if($canManage)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm text-sm">
                    <h2 class="text-sm font-semibold text-slate-700">Aksi</h2>
                    <div class="mt-3 space-y-2">
                        @if($decree->status === 'draf')
                            <form method="POST" action="{{ route('decrees.submit', $decree) }}">
                                @csrf
                                <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 font-semibold text-white hover:bg-emerald-800">Ajukan Persetujuan</button>
                            </form>
                            <a href="{{ route('decrees.edit', $decree) }}" class="block rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50">Ubah</a>
                            <form method="POST" action="{{ route('decrees.destroy', $decree) }}" onsubmit="return confirm('Hapus SK ini?')">
                                @csrf @method('DELETE')
                                <button class="w-full rounded-lg border border-rose-200 px-3 py-2 text-left text-rose-600 hover:bg-rose-50">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
            <a href="{{ route('decrees.index') }}" class="block text-sm text-slate-600 hover:underline">← Kembali ke daftar SK</a>
        </div>
    </div>
@endsection

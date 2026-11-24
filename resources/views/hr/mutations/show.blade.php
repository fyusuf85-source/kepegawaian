@extends('layouts.app', ['title' => 'Mutasi Pegawai', 'heading' => 'Usulan Mutasi', 'subheading' => $mutation->employee?->full_name])

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs uppercase text-slate-500">Pegawai</dt><dd class="font-medium">{{ $mutation->employee?->full_name }} ({{ $mutation->employee?->nip }})</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Jenis</dt><dd>{{ str_replace('_', ' ', $mutation->mutation_type) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Dari Unit</dt><dd>{{ $mutation->fromOrgUnit?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Ke Unit</dt><dd>{{ $mutation->toOrgUnit?->name ?? 'Tetap' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Dari Jabatan</dt><dd>{{ $mutation->fromPosition?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Ke Jabatan</dt><dd>{{ $mutation->toPosition?->name ?? 'Tetap' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Atasan Lama</dt><dd>{{ $mutation->fromSupervisor?->full_name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Atasan Baru</dt><dd>{{ $mutation->toSupervisor?->full_name ?? 'Tetap' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Efektif</dt><dd>{{ \App\Support\Format::date($mutation->effective_date) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Status</dt><dd class="font-semibold">{{ $mutation->status }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs uppercase text-slate-500">Alasan</dt><dd>{{ $mutation->reason }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs uppercase text-slate-500">SK Terkait</dt><dd>{{ $mutation->decree?->number ?? '—' }}</dd></div>
                </dl>
                @if($mutation->approver)
                    <p class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500">Disetujui: {{ $mutation->approver->name }}</p>
                @endif
            </div>

            @include('partials.approval-panel', ['approval' => $approval])
        </div>

        <div class="space-y-4">
            @if($canManage)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm text-sm">
                    <h2 class="text-sm font-semibold text-slate-700">Aksi</h2>
                    <div class="mt-3 space-y-2">
                        @if($mutation->status === 'draf')
                            <form method="POST" action="{{ route('mutations.submit', $mutation) }}">
                                @csrf
                                <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 font-semibold text-white hover:bg-emerald-800">Ajukan Persetujuan</button>
                            </form>
                        @endif
                        @if($mutation->status === 'disetujui')
                            <form method="POST" action="{{ route('mutations.apply', $mutation) }}" onsubmit="return confirm('Terapkan mutasi dan perbarui penempatan aktif?')">
                                @csrf
                                <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 font-semibold text-white hover:bg-emerald-800">Terapkan Mutasi</button>
                            </form>
                        @endif
                        @if($mutation->status === 'diterapkan')
                            <div class="rounded-lg bg-emerald-50 px-3 py-2 text-emerald-800">Mutasi sudah diterapkan dan riwayat penempatan tercatat.</div>
                        @endif
                    </div>
                </div>
            @endif

            @if($canManageDecree && ! $mutation->decree_id)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-700">Terbitkan SK Mutasi</h2>
                    <form method="POST" action="{{ route('mutations.decree', $mutation) }}" class="mt-3 space-y-2">
                        @csrf
                        <input type="date" name="decree_date" value="{{ now('Asia/Jakarta')->toDateString() }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <input name="title" value="SK Mutasi {{ $mutation->employee?->full_name }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <textarea name="description" rows="2" placeholder="Dasar / isi SK" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ $mutation->reason }}</textarea>
                        <button class="w-full rounded-lg border border-emerald-700 px-3 py-2 font-semibold text-emerald-700 hover:bg-emerald-50">Buat Draf SK</button>
                    </form>
                </div>
            @endif

            <a href="{{ route('mutations.index') }}" class="block text-sm text-slate-600 hover:underline">← Kembali ke daftar mutasi</a>
        </div>
    </div>
@endsection

@extends('layouts.app', ['title' => 'Kontrak '.$contract->contract_number, 'heading' => 'Kontrak '.$contract->contract_number, 'subheading' => $contract->employee?->full_name])

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs uppercase text-slate-500">Pegawai</dt><dd class="font-medium"><a class="text-emerald-700 hover:underline" href="{{ route('employees.show', $contract->employee_id) }}">{{ $contract->employee?->full_name }}</a> ({{ $contract->employee?->nip }})</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Unit / Jabatan</dt><dd>{{ $contract->employee?->orgUnit?->name }} · {{ $contract->employee?->position?->name }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Jenis</dt><dd>{{ strtoupper($contract->contract_type) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Status</dt><dd>{{ str_replace('_', ' ', $contract->status) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Mulai</dt><dd>{{ \App\Support\Format::date($contract->start_date) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Berakhir</dt><dd>{{ $contract->end_date ? \App\Support\Format::date($contract->end_date) : 'Tidak berbatas waktu' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Akhir Percobaan</dt><dd>{{ \App\Support\Format::date($contract->probation_end_date) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Ditandatangani</dt><dd>{{ \App\Support\Format::date($contract->signed_date) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Kontrak Sebelumnya</dt><dd>{{ $contract->previousContract?->contract_number ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Perpanjangan</dt><dd>{{ $contract->renewals->count() }} kontrak</dd></div>
                </dl>
                @if($contract->notes)
                    <p class="mt-4 border-t border-slate-100 pt-3 text-sm whitespace-pre-line">{{ $contract->notes }}</p>
                @endif
            </div>

            @include('partials.approval-panel', ['approval' => $approval])

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Dokumen PDF</h2>
                <p class="mt-1 text-sm text-slate-500">Dokumen dibuat dari template aktif dan disimpan privat.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('contracts.pdf', $contract) }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Buat / Unduh PDF</a>
                    @if($contract->document_path)
                        <a href="{{ route('contracts.download', $contract) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Unduh Tersimpan</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @if($canManage)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-700">Aksi</h2>
                    <div class="mt-3 space-y-2 text-sm">
                        @if($contract->status === 'draf')
                            <form method="POST" action="{{ route('contracts.submit', $contract) }}">
                                @csrf
                                <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Ajukan Persetujuan</button>
                            </form>
                            <a href="{{ route('contracts.edit', $contract) }}" class="block rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50">Ubah</a>
                            <form method="POST" action="{{ route('contracts.destroy', $contract) }}" onsubmit="return confirm('Hapus kontrak ini?')">
                                @csrf @method('DELETE')
                                <button class="w-full rounded-lg border border-rose-200 px-3 py-2 text-left text-rose-600 hover:bg-rose-50">Hapus</button>
                            </form>
                        @endif
                        @if($contract->end_date)
                            <a href="{{ route('contracts.renew.form', $contract) }}" class="block rounded-lg border border-emerald-200 px-3 py-2 text-emerald-700 hover:bg-emerald-50">Buat Perpanjangan</a>
                        @endif
                    </div>
                </div>
            @endif

            @if($contract->renewals->isNotEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm text-sm">
                    <h2 class="text-sm font-semibold text-slate-700">Riwayat Perpanjangan</h2>
                    <ul class="mt-2 space-y-1">
                        @foreach($contract->renewals as $renewal)
                            <li><a href="{{ route('contracts.show', $renewal) }}" class="text-emerald-700 hover:underline">{{ $renewal->contract_number }}</a> · {{ $renewal->status }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <a href="{{ route('contracts.index') }}" class="block text-sm text-slate-600 hover:underline">← Kembali ke daftar kontrak</a>
        </div>
    </div>
@endsection

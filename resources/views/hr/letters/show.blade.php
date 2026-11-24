@extends('layouts.app', ['title' => 'Surat '.$letter->number, 'heading' => 'Surat '.$letter->number, 'subheading' => $letter->subject])

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs uppercase text-slate-500">Jenis</dt><dd class="font-medium">{{ ucfirst($letter->letter_type) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Status</dt><dd>{{ str_replace('_', ' ', $letter->status) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Pegawai</dt><dd><a class="text-emerald-700 hover:underline" href="{{ route('employees.show', $letter->employee_id) }}">{{ $letter->employee?->full_name }} ({{ $letter->employee?->nip }})</a></dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Unit / Jabatan</dt><dd>{{ $letter->employee?->orgUnit?->name }} · {{ $letter->employee?->position?->name }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Tanggal Surat</dt><dd>{{ \App\Support\Format::date($letter->letter_date) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Disetujui</dt><dd>{{ $letter->approver?->name ?? '—' }}</dd></div>
                </dl>
                <div class="mt-4 border-t border-slate-100 pt-3 text-sm whitespace-pre-line">{{ $letter->body ?: 'Belum ada isi surat.' }}</div>
            </div>

            @include('partials.approval-panel', ['approval' => $approval])

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Dokumen PDF</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('letters.pdf', $letter) }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Buat / Unduh PDF</a>
                    @if($letter->document_path)
                        <a href="{{ route('letters.download', $letter) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Unduh Tersimpan</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @if($canManage)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm text-sm">
                    <h2 class="text-sm font-semibold text-slate-700">Aksi</h2>
                    <div class="mt-3 space-y-2">
                        @if($letter->status === 'draf')
                            <form method="POST" action="{{ route('letters.submit', $letter) }}">
                                @csrf
                                <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 font-semibold text-white hover:bg-emerald-800">Ajukan Persetujuan</button>
                            </form>
                            <a href="{{ route('letters.edit', $letter) }}" class="block rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50">Ubah</a>
                            <form method="POST" action="{{ route('letters.destroy', $letter) }}" onsubmit="return confirm('Hapus surat ini?')">
                                @csrf @method('DELETE')
                                <button class="w-full rounded-lg border border-rose-200 px-3 py-2 text-left text-rose-600 hover:bg-rose-50">Hapus</button>
                            </form>
                        @endif
                        @if($letter->status === 'disetujui')
                            <form method="POST" action="{{ route('letters.send', $letter) }}">
                                @csrf
                                <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 font-semibold text-white hover:bg-emerald-800">Tandai Terkirim</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
            <a href="{{ route('letters.index') }}" class="block text-sm text-slate-600 hover:underline">← Kembali ke daftar surat</a>
        </div>
    </div>
@endsection

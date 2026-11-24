@extends('layouts.app', ['title' => 'Impor Pegawai', 'heading' => 'Impor Data Pegawai', 'subheading' => 'Unggah berkas Excel sesuai template resmi'])

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1 space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Unggah Berkas</h2>
                <form method="POST" action="{{ route('employees.import') }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                    @csrf
                    <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="w-full text-sm">
                    <p class="text-xs text-slate-500">Format XLSX/XLS/CSV, maksimal 5 MB. Setiap baris divalidasi; baris gagal tidak menggagalkan baris lain.</p>
                    <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Mulai Impor</button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Template</h2>
                <p class="mt-1 text-xs text-slate-500">Gunakan kode unit, jabatan, lokasi, dan pendidikan yang sudah terdaftar. Baris contoh berawalan CONTOH akan dilewati.</p>
                <a href="{{ route('employees.import.template') }}" class="mt-3 inline-block rounded-lg border border-emerald-600 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Unduh Template</a>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Riwayat Impor Terakhir</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($batches as $batch)
                        <div class="px-5 py-4 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="font-medium">{{ $batch->file_name }}</p>
                                    <p class="text-xs text-slate-500">{{ \App\Support\Format::dateTime($batch->created_at) }} · {{ $batch->creator?->name ?? '—' }}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                                    @class([
                                        'bg-emerald-100 text-emerald-700' => $batch->status === 'completed',
                                        'bg-amber-100 text-amber-800' => $batch->status === 'completed_with_errors',
                                        'bg-rose-100 text-rose-700' => $batch->status === 'failed',
                                        'bg-slate-100 text-slate-600' => $batch->status === 'processing',
                                    ])">{{ $batch->status }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-600">
                                Total {{ $batch->total_rows }} baris · berhasil {{ $batch->success_rows }} · gagal {{ $batch->failed_rows }}
                            </p>
                            @if($batch->errors)
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-xs font-medium text-rose-700">Lihat {{ count($batch->errors) }} kesalahan</summary>
                                    <ul class="mt-1 max-h-48 space-y-1 overflow-y-auto text-xs text-rose-700">
                                        @foreach($batch->errors as $error)
                                            <li>Baris {{ $error['row'] ?? '?' }}: {{ implode(' ', (array) ($error['messages'] ?? [])) }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-6 text-sm text-slate-500">Belum ada riwayat impor.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

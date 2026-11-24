@extends('layouts.app', ['title' => 'Pembinaan', 'heading' => 'Catatan Pembinaan'])

@section('content')
@php($u = auth()->user())
<div class="space-y-6">
    @if($u->hasPermission('coaching.manage'))
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-600">Catat Pembinaan</h3>
        <form method="POST" action="{{ route('coaching.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
            @csrf
            <select name="employee_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                <option value="">Pilih pegawai</option>
                @foreach(\App\Models\Employee::query()->visibleTo($u)->where('is_active', true)->get(['id','nip','full_name']) as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->nip }} — {{ $employee->full_name }}</option>
                @endforeach
            </select>
            <input name="date" type="date" value="{{ old('date', now('Asia/Jakarta')->toDateString()) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
            <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                @foreach(['konseling','pembinaan_kedisiplinan','teguran','evaluasi','pelatihan','penghargaan'] as $type)
                    <option value="{{ $type }}">{{ ucwords(str_replace('_',' ', $type)) }}</option>
                @endforeach
            </select>
            <input name="result" value="{{ old('result') }}" placeholder="Hasil / tindak lanjut" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <textarea name="reason" rows="2" placeholder="Alasan / uraian pembinaan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm sm:col-span-2" required>{{ old('reason') }}</textarea>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white sm:col-span-2">Simpan Catatan</button>
        </form>
    </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-600">Riwayat Pembinaan</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr><th class="px-3 py-2">Tanggal</th><th class="px-3 py-2">Pegawai</th><th class="px-3 py-2">Jenis</th><th class="px-3 py-2">Uraian</th><th class="px-3 py-2">Status</th><th class="px-3 py-2"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $record)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $record->date->format('d/m/Y') }}</td>
                        <td class="px-3 py-2">{{ $record->employee?->full_name }}<div class="text-xs text-slate-500">{{ $record->employee?->nip }}</div></td>
                        <td class="px-3 py-2">{{ ucwords(str_replace('_',' ', $record->type)) }}</td>
                        <td class="px-3 py-2">{{ $record->reason }}@if($record->result)<div class="text-xs text-slate-500">Hasil: {{ $record->result }}</div>@endif</td>
                        <td class="px-3 py-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold">{{ strtoupper($record->status) }}</span></td>
                        <td class="px-3 py-2 text-right">
                            @if($record->status === 'draft' && $u->hasPermission('coaching.approve'))
                                <form method="POST" action="{{ route('coaching.approve', $record) }}">@csrf<button class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Setujui</button></form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-3 py-8 text-center text-slate-400">Belum ada catatan pembinaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
@endsection

@extends('layouts.app', ['title' => 'KPI & Pembinaan', 'heading' => 'KPI dan Pembinaan'])

@section('content')
@php($u = auth()->user())
<div class="space-y-6">
    @if($u->hasPermission('kpi.period.manage') || $u->hasPermission('kpi.template.manage'))
    <div class="grid gap-6 lg:grid-cols-2">
        @if($u->hasPermission('kpi.period.manage'))
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-600">Periode KPI</h3>
            <form method="POST" action="{{ route('kpi.periods.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <input name="code" value="{{ old('code') }}" placeholder="Kode (2026-11)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                <input name="start_date" type="date" value="{{ old('start_date') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                <input name="end_date" type="date" value="{{ old('end_date') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                <input name="notes" value="{{ old('notes') }}" placeholder="Catatan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Tambah Periode</button>
            </form>
            <div class="mt-4 divide-y divide-slate-100 text-sm">
                @foreach($periods as $period)
                <div class="flex items-center justify-between gap-3 py-2">
                    <div><span class="font-semibold">{{ $period->code }}</span> <span class="text-xs text-slate-500">{{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}</span></div>
                    <form method="POST" action="{{ route('kpi.periods.status', $period) }}" class="flex gap-1">
                        @csrf
                        <select name="status" class="rounded border border-slate-300 px-2 py-1 text-xs">
                            @foreach(['draft','open','closed'] as $status)
                                <option value="{{ $status }}" @selected($period->status === $status)>{{ strtoupper($status) }}</option>
                            @endforeach
                        </select>
                        <button class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold">Ubah</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($u->hasPermission('kpi.template.manage'))
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-600">Template & Indikator</h3>
            <p class="mt-1 text-xs text-slate-500">Total bobot wajib 100%. Template DEMO sudah tersedia.</p>
            <form method="POST" action="{{ route('kpi.templates.store') }}" class="mt-4 space-y-3">
                @csrf
                <input name="name" value="{{ old('name') }}" placeholder="Nama template" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                <textarea name="description" placeholder="Deskripsi" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" rows="2">{{ old('description') }}</textarea>
                @for($i = 0; $i < 4; $i++)
                <div class="grid gap-2 sm:grid-cols-5">
                    <input name="indicators[{{ $i }}][code]" value="{{ old("indicators.$i.code") }}" placeholder="Kode" class="rounded border border-slate-300 px-2 py-1 text-xs">
                    <input name="indicators[{{ $i }}][name]" value="{{ old("indicators.$i.name") }}" placeholder="Nama indikator" class="rounded border border-slate-300 px-2 py-1 text-xs sm:col-span-2">
                    <input name="indicators[{{ $i }}][weight]" type="number" step="0.01" min="0" max="100" value="{{ old("indicators.$i.weight") }}" placeholder="Bobot %" class="rounded border border-slate-300 px-2 py-1 text-xs">
                    <input type="hidden" name="indicators[{{ $i }}][target_type]" value="min">
                    <input name="indicators[{{ $i }}][unit]" value="{{ old("indicators.$i.unit") }}" placeholder="Satuan" class="rounded border border-slate-300 px-2 py-1 text-xs">
                </div>
                @endfor
                <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Template</button>
            </form>
            <div class="mt-4 space-y-2 text-sm">
                @foreach($templates as $template)
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <p class="font-semibold">{{ $template->name }}</p>
                    <p class="text-xs text-slate-500">{{ $template->indicators->count() }} indikator · bobot {{ $template->indicators->sum('weight') }}%</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-600">Daftar Penilaian</h3>
            <div class="flex gap-2">
                <a href="{{ route('coaching.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold hover:bg-slate-50">Pembinaan</a>
                @if($u->hasPermission('kpi.assess'))
                <a href="{{ route('kpi.assessments.create') }}" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">Nilai Pegawai</a>
                @endif
            </div>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr><th class="px-3 py-2">Pegawai</th><th class="px-3 py-2">Periode</th><th class="px-3 py-2">Nilai</th><th class="px-3 py-2">Status</th><th class="px-3 py-2 text-right">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($assessments as $assessment)
                    <tr>
                        <td class="px-3 py-2">{{ $assessment->employee?->full_name }}<div class="text-xs text-slate-500">{{ $assessment->employee?->nip }}</div></td>
                        <td class="px-3 py-2">{{ $assessment->period?->code }}</td>
                        <td class="px-3 py-2 font-semibold">{{ number_format((float) $assessment->final_score, 2) }}</td>
                        <td class="px-3 py-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold">{{ strtoupper($assessment->status) }}</span></td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex flex-wrap justify-end gap-1">
                                <a href="{{ route('kpi.assessments.show', $assessment) }}" class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold">Lihat</a>
                                @if($assessment->status === 'draft' && $u->hasPermission('kpi.assess'))
                                    <a href="{{ route('kpi.assessments.edit', $assessment) }}" class="rounded bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700">Nilai</a>
                                    <form method="POST" action="{{ route('kpi.assessments.submit', $assessment) }}">@csrf<button class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Ajukan</button></form>
                                @endif
                                @if($assessment->status === 'submitted' && $u->hasPermission('kpi.verify'))
                                    <form method="POST" action="{{ route('kpi.assessments.verify', $assessment) }}">@csrf<button class="rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">Verifikasi</button></form>
                                @endif
                                @if($assessment->status === 'verified' && $u->hasPermission('kpi.approve'))
                                    <form method="POST" action="{{ route('kpi.assessments.approve', $assessment) }}">@csrf<button class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Setujui</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-3 py-8 text-center text-slate-400">Belum ada penilaian KPI.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $assessments->links() }}</div>
    </div>
</div>
@endsection

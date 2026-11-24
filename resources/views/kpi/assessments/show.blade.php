@extends('layouts.app', ['title' => 'Detail KPI', 'heading' => 'Detail Penilaian KPI'])

@section('content')
@php($u = auth()->user())
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold">{{ $assessment->employee->full_name }}</h2>
                <p class="text-sm text-slate-500">{{ $assessment->employee->nip }} · {{ $assessment->period->code }} · {{ $assessment->template->name }}</p>
                <p class="mt-1 text-xs text-slate-500">Penilai: {{ $assessment->assessor?->full_name ?? '—' }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase tracking-wider text-slate-500">Nilai Akhir</p>
                <p class="text-3xl font-bold text-emerald-700">{{ number_format((float) $assessment->final_score, 2) }}</p>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold">{{ strtoupper($assessment->status) }}</span>
            </div>
        </div>
        @if($assessment->notes)<p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">{{ $assessment->notes }}</p>@endif
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr><th class="px-3 py-2">Indikator</th><th class="px-3 py-2">Bobot</th><th class="px-3 py-2">Target</th><th class="px-3 py-2">Realisasi</th><th class="px-3 py-2">Skor</th><th class="px-3 py-2">Terbobot</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($assessment->items as $item)
                    <tr>
                        <td class="px-3 py-2">{{ $item->indicator->name }}</td>
                        <td class="px-3 py-2">{{ $item->indicator->weight }}%</td>
                        <td class="px-3 py-2">{{ number_format((float) $item->target, 2) }}</td>
                        <td class="px-3 py-2">{{ number_format((float) $item->actual, 2) }}</td>
                        <td class="px-3 py-2">{{ number_format((float) $item->score, 2) }}</td>
                        <td class="px-3 py-2 font-semibold">{{ number_format((float) $item->weighted_score, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('kpi.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Kembali</a>
        @if($assessment->status === 'draft' && $u->hasPermission('kpi.assess'))
            <a href="{{ route('kpi.assessments.edit', $assessment) }}" class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Ubah Nilai</a>
            <form method="POST" action="{{ route('kpi.assessments.submit', $assessment) }}">@csrf<button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Ajukan Verifikasi</button></form>
        @endif
        @if($assessment->status === 'submitted' && $u->hasPermission('kpi.verify'))
            <form method="POST" action="{{ route('kpi.assessments.verify', $assessment) }}">@csrf<button class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white">Verifikasi HRD</button></form>
        @endif
        @if($assessment->status === 'verified' && $u->hasPermission('kpi.approve'))
            <form method="POST" action="{{ route('kpi.assessments.approve', $assessment) }}">@csrf<button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Setujui Pimpinan</button></form>
        @endif
        @if($assessment->status === 'approved' && $u->hasPermission('kpi.unlock'))
            <form method="POST" action="{{ route('kpi.assessments.unlock', $assessment) }}" class="flex gap-2">@csrf
                <input name="reason" placeholder="Alasan buka kunci" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                <button class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white">Buka Kunci</button>
            </form>
        @endif
    </div>
</div>
@endsection

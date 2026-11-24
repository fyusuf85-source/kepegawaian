@extends('layouts.app', ['title' => 'Nilai KPI', 'heading' => 'Penilaian KPI — '.$assessment->employee->full_name])

@section('content')
<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 text-sm">
        <div>
            <p class="font-semibold">{{ $assessment->employee->full_name }} <span class="text-slate-500">({{ $assessment->employee->nip }})</span></p>
            <p class="text-xs text-slate-500">Periode {{ $assessment->period->code }} · {{ $assessment->template->name }}</p>
        </div>
        <a href="{{ route('kpi.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold">Kembali</a>
    </div>

    <form method="POST" action="{{ route('kpi.assessments.update', $assessment) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr><th class="px-3 py-2">Indikator</th><th class="px-3 py-2">Bobot</th><th class="px-3 py-2">Target</th><th class="px-3 py-2">Realisasi</th><th class="px-3 py-2">Skor</th><th class="px-3 py-2">Catatan</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($assessment->items as $index => $item)
                    <tr>
                        <td class="px-3 py-2">
                            {{ $item->indicator->name }}
                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                        </td>
                        <td class="px-3 py-2">{{ $item->indicator->weight }}%</td>
                        <td class="px-3 py-2"><input name="items[{{ $index }}][target]" type="number" step="0.01" min="0" value="{{ old("items.$index.target", $item->target) }}" class="w-24 rounded border border-slate-300 px-2 py-1 text-sm"></td>
                        <td class="px-3 py-2"><input name="items[{{ $index }}][actual]" type="number" step="0.01" min="0" value="{{ old("items.$index.actual", $item->actual) }}" class="w-24 rounded border border-slate-300 px-2 py-1 text-sm"></td>
                        <td class="px-3 py-2 font-semibold">{{ number_format((float) $item->score, 2) }}</td>
                        <td class="px-3 py-2"><input name="items[{{ $index }}][notes]" value="{{ old("items.$index.notes", $item->notes) }}" class="w-full rounded border border-slate-300 px-2 py-1 text-sm"></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Catatan penilaian</label>
            <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('notes', $assessment->notes) }}</textarea>
        </div>
        @if($errors->any())<div class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
        <div class="flex justify-end">
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Simpan Penilaian</button>
        </div>
    </form>
</div>
@endsection

@extends('layouts.app', ['title' => 'Penilaian KPI Baru', 'heading' => 'Buat Penilaian KPI'])

@section('content')
<div class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('kpi.assessments.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="text-sm font-medium text-slate-700">Pegawai yang dinilai</label>
            <select name="employee_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                <option value="">Pilih pegawai</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->nip }} — {{ $employee->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Periode KPI</label>
            <select name="kpi_period_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                <option value="">Pilih periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" @selected(old('kpi_period_id') == $period->id)>{{ $period->code }} ({{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Template indikator</label>
            <select name="kpi_template_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                <option value="">Pilih template</option>
                @foreach($templates as $template)
                    <option value="{{ $template->id }}" @selected(old('kpi_template_id') == $template->id)>{{ $template->name }}</option>
                @endforeach
            </select>
        </div>
        @if($errors->any())
            <div class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif
        <div class="flex justify-end gap-2">
            <a href="{{ route('kpi.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Batal</a>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Buat Penilaian</button>
        </div>
    </form>
</div>
@endsection

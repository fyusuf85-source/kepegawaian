@extends('layouts.app', ['title' => 'Ajukan Penyesuaian Payroll', 'heading' => 'Ajukan Penyesuaian Payroll'])
@section('content')
<form method="POST" action="{{ route('payroll.adjustments.store') }}" class="max-w-2xl space-y-4 rounded-xl border bg-white p-5">@csrf
<div><label class="block text-sm font-medium">Periode Payroll</label><select name="payroll_period_id" required class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Pilih periode</option>@foreach($periods as $period)<option value="{{ $period->id }}">{{ $period->code }}</option>@endforeach</select></div>
<div><label class="block text-sm font-medium">Pegawai</label><select name="employee_id" required class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Pilih pegawai</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->nip }} — {{ $employee->full_name }}</option>@endforeach</select></div>
<div><label class="block text-sm font-medium">Komponen (opsional)</label><select name="component_id" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Manual</option>@foreach($components as $component)<option value="{{ $component->id }}">{{ $component->name }} ({{ $component->type }})</option>@endforeach</select></div>
<div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium">Tipe</label><select name="type" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="penghasilan">Penghasilan</option><option value="potongan">Potongan</option></select></div><div><label class="block text-sm font-medium">Nominal (Rp)</label><input type="number" min="1" name="amount" required class="mt-1 w-full rounded-lg border px-3 py-2"></div></div>
<div><label class="block text-sm font-medium">Alasan</label><textarea name="reason" required rows="4" class="mt-1 w-full rounded-lg border px-3 py-2"></textarea></div>
<button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Ajukan</button></form>
@endsection

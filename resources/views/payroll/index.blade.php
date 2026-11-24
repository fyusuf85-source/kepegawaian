@extends('layouts.app', ['title' => 'Periode Payroll', 'heading' => 'Periode Payroll', 'subheading' => 'Kelola periode penggajian, snapshot absensi, dan penguncian'])
@section('content')
<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
    <strong>Kerangka Tahap 5.</strong> Tarif, rumus, BPJS, dan PPh 21 belum dikonfigurasi sesuai kebijakan resmi. Perhitungan final dan slip gaji diblokir sampai konfigurasi lengkap.
</div>
@if(auth()->user()->hasPermission('payroll.period.manage'))<div class="mt-3"><a href="{{ route('payroll.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Tambah Periode</a></div>@endif
<div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Rentang</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($periods as $period)
<tr>
<td class="px-4 py-3 font-medium">{{ $period->code }}</td>
<td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($period->start_date) }} – {{ \App\Support\Format::date($period->end_date) }}</td>
<td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $period->status }}</span></td>
<td class="px-4 py-3 text-right"><a href="{{ route('payroll.show', $period) }}" class="text-emerald-700 hover:underline">Buka</a></td>
</tr>
@empty
<tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada periode payroll.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $periods->links() }}</div>
@endsection

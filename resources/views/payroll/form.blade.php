@extends('layouts.app', ['title' => 'Tambah Periode Payroll', 'heading' => 'Tambah Periode Payroll'])
@section('content')
<form method="POST" action="{{ route('payroll.store') }}" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">@csrf
<div><label class="block text-sm font-medium">Kode (YYYY-MM)</label><input name="code" value="{{ old('code') }}" placeholder="2026-11" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">@error('code')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
<div class="grid gap-4 sm:grid-cols-2"><div><label class="block text-sm font-medium">Tanggal Mulai</label><input type="date" name="start_date" value="{{ old('start_date') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div><div><label class="block text-sm font-medium">Tanggal Akhir</label><input type="date" name="end_date" value="{{ old('end_date') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div></div>
<div><label class="block text-sm font-medium">Tanggal Pembayaran</label><input type="date" name="payment_date" value="{{ old('payment_date') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
<div><label class="block text-sm font-medium">Catatan</label><textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea></div>
<button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Simpan Periode</button>
</form>
@endsection

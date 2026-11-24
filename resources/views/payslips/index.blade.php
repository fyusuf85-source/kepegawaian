@extends('layouts.app', ['title' => 'Slip Gaji Saya', 'heading' => 'Slip Gaji Saya', 'subheading' => 'Riwayat slip gaji yang sudah diterbitkan'])
@section('content')
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
<tr><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Tanggal Terbit</th><th class="px-4 py-3">Bruto</th><th class="px-4 py-3">Potongan</th><th class="px-4 py-3">Netto</th><th class="px-4 py-3"></th></tr>
</thead>
<tbody class="divide-y divide-slate-100">
@forelse($payslips as $slip)
<tr>
    <td class="px-4 py-3 font-medium">{{ $slip->period->code }}</td>
    <td class="px-4 py-3 text-xs">{{ $slip->generated_at?->format('d-m-Y H:i') ?? '—' }}</td>
    <td class="px-4 py-3 text-xs">Rp {{ number_format($slip->gross, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-xs text-rose-600">Rp {{ number_format($slip->total_deduction, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-xs font-bold text-emerald-600">Rp {{ number_format($slip->net, 0, ',', '.') }}</td>
    <td class="px-4 py-3 text-right">
        @if($slip->file_path)
            <a href="{{ route('payslips.download', $slip) }}" class="text-emerald-700 hover:underline text-xs font-semibold">Unduh PDF</a>
        @else
            <span class="text-slate-400 text-xs">Belum tersedia</span>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada slip gaji yang diterbitkan.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $payslips->links() }}</div>
@endsection

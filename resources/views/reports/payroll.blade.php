@extends('layouts.app', ['title' => 'Laporan Payroll', 'heading' => 'Laporan Rekap Penggajian'])

@section('content')
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('reports.payroll') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider text-slate-500">Periode Payroll</label>
                <select name="period_code" class="mt-1 block min-w-56 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @forelse($periods as $option)
                        <option value="{{ $option->code }}" @selected($period?->id === $option->id)>{{ $option->code }}</option>
                    @empty
                        <option value="">Belum ada periode</option>
                    @endforelse
                </select>
            </div>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tampilkan</button>
            @if($period)
                <a href="{{ route('reports.payroll.export', ['period_code' => $period->code]) }}" class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800">Ekspor Excel</a>
            @endif
            <a href="{{ route('reports.executive') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Laporan Eksekutif</a>
        </form>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-500">Periode</p>
            <p class="mt-2 text-xl font-bold">{{ $period?->code ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-500">Versi Run</p>
            <p class="mt-2 text-xl font-bold">{{ $run?->version ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-500">Jumlah Pegawai</p>
            <p class="mt-2 text-xl font-bold">{{ number_format($rows->count(), 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-emerald-700">Total Netto</p>
            <p class="mt-2 text-xl font-bold text-emerald-800">Rp {{ number_format((float) ($run?->total_net ?? 0), 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">NIP</th>
                        <th class="px-3 py-2">Nama</th>
                        <th class="px-3 py-2 text-right">Bruto</th>
                        <th class="px-3 py-2 text-right">Potongan</th>
                        <th class="px-3 py-2 text-right">Kontribusi Pemberi Kerja</th>
                        <th class="px-3 py-2 text-right">Netto</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                    <tr>
                        <td class="px-3 py-2 font-mono text-xs">{{ $row->employee?->nip }}</td>
                        <td class="px-3 py-2">{{ $row->employee?->full_name }}</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format((float) $row->gross, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right text-rose-700">Rp {{ number_format((float) $row->total_deduction, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right text-slate-600">Rp {{ number_format((float) $row->employer_contribution, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-emerald-700">Rp {{ number_format((float) $row->net, 0, ',', '.') }}</td>
                        <td class="px-3 py-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold">{{ strtoupper($row->status) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-3 py-8 text-center text-slate-400">Belum ada payroll run untuk periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

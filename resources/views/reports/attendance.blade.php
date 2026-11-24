@extends('layouts.app', ['title' => 'Laporan Absensi', 'heading' => 'Laporan Rekapitulasi Absensi'])

@section('content')
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('reports.attendance') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider text-slate-500">Bulan</label>
                <select name="month" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" @selected((int) $month === $m)>{{ \Illuminate\Support\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tahun</label>
                <input name="year" type="number" min="2000" max="2100" value="{{ $year }}" class="mt-1 block w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tampilkan</button>
            <a href="{{ route('reports.attendance.export', ['month' => $month, 'year' => $year]) }}" class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800">Ekspor Excel</a>
            <a href="{{ route('reports.executive') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Laporan Eksekutif</a>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">NIP</th>
                        <th class="px-3 py-2">Nama</th>
                        <th class="px-3 py-2 text-right">Hadir</th>
                        <th class="px-3 py-2 text-right">Terlambat</th>
                        <th class="px-3 py-2 text-right">Izin/Sakit/Libur</th>
                        <th class="px-3 py-2 text-right">Tidak Hadir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report as $employee)
                    <tr>
                        <td class="px-3 py-2 font-mono text-xs">{{ $employee->nip }}</td>
                        <td class="px-3 py-2">{{ $employee->full_name }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-emerald-700">{{ $employee->hadir }}</td>
                        <td class="px-3 py-2 text-right text-amber-700">{{ $employee->terlambat }}</td>
                        <td class="px-3 py-2 text-right text-slate-600">{{ $employee->izin }}</td>
                        <td class="px-3 py-2 text-right text-rose-700">{{ $employee->alpa }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-3 py-8 text-center text-slate-400">Tidak ada data pegawai aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $report->links() }}</div>
    </div>
</div>
@endsection

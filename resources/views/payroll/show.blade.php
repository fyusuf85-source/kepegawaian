@extends('layouts.app', ['title' => 'Detail Periode: '.$period->code, 'heading' => 'Periode '.$period->code])

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold uppercase tracking-wide text-slate-700 border border-slate-200">
            Status: {{ strtoupper($period->status) }}
        </span>
        @if($period->locked_at)
            <span class="text-xs text-slate-500">Dikunci pada {{ $period->locked_at->format('d/m/Y H:i') }}</span>
        @endif
    </div>
    <div class="flex gap-2">
        <a href="{{ route('payroll.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Kembali</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <!-- Kolom Alur Kerja -->
    <div class="lg:col-span-1 space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Langkah Pengerjaan</h3>
            <div class="mt-4 space-y-4">
                <!-- Step 1: Snapshot -->
                <div class="flex gap-3">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $period->summaries()->exists() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} text-xs font-bold">1</div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">Snapshot Absensi</p>
                        <p class="text-xs text-slate-500">Ambil data kehadiran untuk periode ini.</p>
                        @if(!$period->isLocked())
                        <form method="POST" action="{{ route('payroll.snapshot', $period) }}" class="mt-2">
                            @csrf
                            <button class="w-full rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">
                                {{ $period->summaries()->exists() ? 'Perbarui Snapshot' : 'Generate Snapshot' }}
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                <!-- Step 2: Hitung -->
                <div class="flex gap-3">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $period->runs()->exists() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} text-xs font-bold">2</div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">Kalkulasi Payroll</p>
                        <p class="text-xs text-slate-500">Hitung gaji berdasarkan komponen & tarif.</p>
                        @if(!$period->isLocked() && $period->summaries()->exists())
                        <form method="POST" action="{{ route('payroll.calculate', $period) }}" class="mt-2">
                            @csrf
                            <button class="w-full rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">Hitung Sekarang</button>
                        </form>
                        @endif
                    </div>
                </div>

                <!-- Step 3: Approval -->
                <div class="flex gap-3">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ in_array($period->status, ['verifikasi_hrd', 'verifikasi_keuangan', 'disetujui', 'dikunci'], true) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} text-xs font-bold">3</div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">Persetujuan Berjenjang</p>
                        <p class="text-xs text-slate-500">HRD mengajukan, lalu Keuangan → Pimpinan.</p>
                        @if(!$period->isLocked() && in_array($period->status, ['dihitung', 'diperiksa'], true))
                        <form method="POST" action="{{ route('payroll.submit', $period) }}" class="mt-2">@csrf
                            <button class="w-full rounded-lg bg-indigo-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-800">Ajukan Persetujuan</button>
                        </form>
                        @endif
                    </div>
                </div>

                <!-- Step 4: Kunci -->
                <div class="flex gap-3">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $period->isLocked() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} text-xs font-bold">4</div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">Finalisasi & Kunci</p>
                        <p class="text-xs text-slate-500">Kunci data setelah disetujui.</p>
                        @if(!$period->isLocked() && $period->status === 'disetujui')
                        <form method="POST" action="{{ route('payroll.lock', $period) }}" class="mt-2" onsubmit="return confirm('Setelah dikunci, data tidak dapat diubah. Lanjutkan?')">
                            @csrf
                            <button class="w-full rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">Kunci Periode</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Laporan & Slip</h3>
            <div class="mt-4 space-y-2">
                @if($period->isLocked())
                    <form method="POST" action="{{ route('payroll.payslips.generate', $period) }}">@csrf
                        <button class="block w-full rounded-lg bg-emerald-700 px-4 py-2 text-center text-xs font-semibold text-white hover:bg-emerald-800">Buat Slip PDF Individual</button>
                    </form>
                    <a href="{{ route('payroll.payslips.batch', $period) }}" class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-xs font-semibold hover:bg-slate-50">Unduh Slip Gaji Batch</a>
                @else
                    <div class="rounded-lg bg-slate-50 px-4 py-2 text-center text-xs text-slate-500">Slip PDF aktif setelah periode dikunci.</div>
                @endif
                <a href="{{ route('payroll.export', [$period, 'report' => 'payroll']) }}" class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-xs font-semibold hover:bg-slate-50">Ekspor Laporan Payroll</a>
                <a href="{{ route('payroll.export', [$period, 'report' => 'transfer_gaji']) }}" class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-xs font-semibold hover:bg-slate-50">Ekspor Transfer Gaji</a>
                <a href="{{ route('payroll.export', [$period, 'report' => 'transfer_snack']) }}" class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-xs font-semibold hover:bg-slate-50">Ekspor Transfer Snack</a>
                <a href="{{ route('payroll.export', [$period, 'report' => 'bpjs_tk']) }}" class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-xs font-semibold hover:bg-slate-50">Ekspor Laporan BPJS TK</a>
                <a href="{{ route('payroll.export', [$period, 'report' => 'bpjs_kes']) }}" class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-xs font-semibold hover:bg-slate-50">Ekspor Laporan BPJS Kesehatan</a>
                <a href="{{ route('payroll.export', [$period, 'report' => 'pajak']) }}" class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-xs font-semibold hover:bg-slate-50">Ekspor Laporan PPh 21</a>
            </div>
        </div>
    </div>

    <!-- Kolom Data -->
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-700">Ringkasan Data</h3>
            </div>
            <div class="p-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-center">
                    <p class="text-[10px] uppercase tracking-wider text-slate-500">Pegawai</p>
                    <p class="mt-1 text-lg font-bold">{{ $run?->employee_count ?? $period->summaries()->count() }}</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-center">
                    <p class="text-[10px] uppercase tracking-wider text-slate-500">Total Bruto</p>
                    <p class="mt-1 text-lg font-bold">Rp {{ number_format((float) ($run?->total_gross ?? 0), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-center">
                    <p class="text-[10px] uppercase tracking-wider text-slate-500">Total Potongan</p>
                    <p class="mt-1 text-lg font-bold text-rose-600">Rp {{ number_format((float) ($run?->total_deduction ?? 0), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-center">
                    <p class="text-[10px] uppercase tracking-wider text-slate-500">Total Netto</p>
                    <p class="mt-1 text-lg font-bold text-emerald-600">Rp {{ number_format((float) ($run?->total_net ?? 0), 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-4 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-700">Rincian Per Pegawai</h3>
                <div class="text-xs text-slate-500">Menampilkan 20 data terbaru</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Pegawai</th>
                            <th class="px-4 py-3">Hadir/Kerja</th>
                            <th class="px-4 py-3">Bruto</th>
                            <th class="px-4 py-3">Netto</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php
                            $rows = $run ? $run->employees()->with(['employee:id,full_name,nip','attendanceSummary'])->limit(20)->get() : collect();
                        @endphp
                        @forelse($rows as $row)
                        @php $summary = $row->attendanceSummary; @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $row->employee->full_name }}</div>
                                <div class="text-[10px] text-slate-500">{{ $row->employee->nip }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div>{{ $summary?->present_days ?? 0 }}/{{ $summary?->work_days ?? 0 }} Hari</div>
                                @if(($summary?->total_late_minutes ?? 0) > 0)
                                    <div class="text-rose-600">Late: {{ $summary->total_late_minutes }}m</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-700">Rp {{ number_format((float) $row->gross, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 font-bold text-emerald-700">Rp {{ number_format((float) $row->net, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full {{ $row->status === 'anomali' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }} px-2 py-0.5 text-[10px] font-semibold">{{ strtoupper($row->status) }}</span>
                                @if($row->notes)
                                    <div class="mt-1 text-[10px] leading-tight text-amber-600 italic">{{ $row->notes }}</div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada data snapshot untuk periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($run && $run->employees()->count() > 20)
            <div class="bg-slate-50 px-5 py-3 text-center border-t border-slate-100">
                <a href="#" class="text-xs font-bold text-emerald-700 hover:underline">Lihat Semua Data Pegawai</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

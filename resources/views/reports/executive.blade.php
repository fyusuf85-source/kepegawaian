@extends('layouts.app', ['title' => 'Laporan Eksekutif & Dashboard', 'heading' => 'Laporan Eksekutif & Ringkasan Kinerja'])

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-500">Pegawai Aktif</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['total_employees']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-500">Kontrak Aktif</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['active_contracts']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-500">Cuti Menunggu</p>
            <p class="mt-2 text-2xl font-bold text-indigo-600">{{ number_format($stats['pending_leaves']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-500">Netto Payroll Periode Ini</p>
            <p class="mt-2 text-xl font-bold text-emerald-600">Rp {{ number_format($stats['latest_payroll_net'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-slate-500">Rata-Rata Skor KPI</p>
            <p class="mt-2 text-2xl font-bold text-amber-600">{{ number_format($stats['kpi_avg_score'], 1) }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-800">Pusat Laporan & Analitik Terpadu</h3>
        <p class="mt-1 text-sm text-slate-500">Akses seluruh rekapitulasi data kepegawaian, absensi, penggajian, dan penilaian kinerja pesantren.</p>
        
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                <h4 class="font-semibold text-slate-800 text-sm">Kepegawaian & Administrasi</h4>
                <ul class="mt-2 space-y-1 text-xs text-indigo-600">
                    <li><a href="{{ route('employees.index') }}" class="hover:underline">→ Direktori & Profil Pegawai</a></li>
                    <li><a href="{{ route('contracts.index') }}" class="hover:underline">→ Daftar Kontrak & SK</a></li>
                    <li><a href="{{ route('mutations.index') }}" class="hover:underline">→ Riwayat Mutasi & Penempatan</a></li>
                    <li><a href="{{ route('reports.attendance') }}" class="hover:underline">→ Rekap Absensi Bulanan</a></li>
                </ul>
            </div>
            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                <h4 class="font-semibold text-slate-800 text-sm">Penggajian & Pajak</h4>
                <ul class="mt-2 space-y-1 text-xs text-indigo-600">
                    <li><a href="{{ route('payroll.index') }}" class="hover:underline">→ Daftar Periode & Kalkulasi Payroll</a></li>
                    <li><a href="{{ route('reports.payroll') }}" class="hover:underline">→ Rekap Penggajian per Periode</a></li>
                    <li><a href="{{ route('payroll.adjustments.index') }}" class="hover:underline">→ Penyesuaian Gaji / Adjustment</a></li>
                    <li><a href="{{ route('payslips.index') }}" class="hover:underline">→ Arsip Slip Gaji Privat</a></li>
                </ul>
            </div>
            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                <h4 class="font-semibold text-slate-800 text-sm">Kinerja & Pembinaan (KPI)</h4>
                <ul class="mt-2 space-y-1 text-xs text-indigo-600">
                    <li><a href="{{ route('kpi.index') ?? '#' }}" class="hover:underline">→ Penilaian Kinerja Bulanan</a></li>
                    <li><a href="{{ route('coaching.index') ?? '#' }}" class="hover:underline">→ Catatan Pembinaan & Teguran</a></li>
                    <li><a href="{{ route('audit.index') }}" class="hover:underline">→ Jejak Audit Sistem (Audit Trail)</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

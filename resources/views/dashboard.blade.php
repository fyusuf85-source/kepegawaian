@extends('layouts.app', ['title' => 'Dasbor', 'heading' => 'Dasbor', 'subheading' => 'Ringkasan kepegawaian sesuai hak akses Anda'])

@section('content')
@php($u = auth()->user())
@php($hour = (int) now('Asia/Jakarta')->format('H'))
@php($greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam')))

<!-- Hero Greeting -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-700 via-emerald-800 to-teal-900 px-6 py-8 text-white shadow-lg shadow-emerald-900/10 sm:px-8 sm:py-10">
    <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/5"></div>
    <div class="absolute -bottom-16 right-32 h-40 w-40 rounded-full bg-emerald-400/10"></div>
    <div class="relative z-10 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-emerald-200">{{ now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</p>
            <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{{ $greeting }}, {{ $u->name }} 👋</h2>
            <p class="mt-2 max-w-xl text-sm text-emerald-100/80">Berikut ringkasan aktivitas kepegawaian dan hal-hal yang perlu Anda perhatikan hari ini.</p>
        </div>
        @if(($stats['pending_approvals'] ?? 0) > 0)
            <a href="{{ route('approvals.index') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold text-white ring-1 ring-inset ring-white/20 backdrop-blur-sm transition hover:bg-white/20">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>{{ $stats['pending_approvals'] }} Persetujuan Menunggu</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        @endif
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
    @if($u->hasPermission('attendance.self'))
        <a href="{{ route('attendance.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-600 group-hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-700 group-hover:text-emerald-700">Presensi</span>
        </a>
    @endif
    @if($u->hasPermission('leave.request'))
        <a href="{{ route('leave.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600 transition group-hover:bg-sky-600 group-hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-700 group-hover:text-sky-700">Ajukan Cuti</span>
        </a>
    @endif
    @if($u->employee)
        <a href="{{ route('finance.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition group-hover:bg-amber-600 group-hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-700 group-hover:text-amber-700">Kasbon</span>
        </a>
    @endif
    <a href="{{ route('profile.show') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600 transition group-hover:bg-violet-600 group-hover:text-white">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <span class="text-sm font-semibold text-slate-700 group-hover:text-violet-700">Profil Saya</span>
    </a>
</div>

<!-- Stats Grid -->
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @isset($stats['employees_active'])
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pegawai Aktif</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['employees_active'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-2.5 text-emerald-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
            </div>
            <p class="mt-3 text-xs text-slate-400">dari {{ number_format($stats['employees_total'], 0, ',', '.') }} pegawai terlihat</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pegawai Baru</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['employees_new'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-sky-50 p-2.5 text-sky-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg></div>
            </div>
            <p class="mt-3 text-xs text-slate-400">Bergabung bulan ini</p>
        </div>
    @endisset

    @isset($stats['kpi_count'])
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Penilaian KPI</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['kpi_count'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-violet-50 p-2.5 text-violet-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
            </div>
            <p class="mt-3 text-xs text-slate-400">Rata-rata: {{ number_format((float) $stats['kpi_average'], 1) }}</p>
        </div>
    @endisset

    @isset($stats['contracts_active'])
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kontrak Aktif</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['contracts_active'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-indigo-50 p-2.5 text-indigo-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
            </div>
            <p class="mt-3 text-xs text-slate-400">Sedang berjalan</p>
        </div>
    @endisset

    @isset($stats['leave_pending'])
        <div class="rounded-2xl border border-amber-200/80 bg-amber-50 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Izin Menunggu</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-amber-900">{{ number_format($stats['leave_pending'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-amber-100 p-2.5 text-amber-700"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            </div>
            <a href="{{ route('leave.index') }}" class="mt-3 inline-block text-xs font-semibold text-amber-700 hover:underline">Lihat pengajuan →</a>
        </div>
    @endisset

    <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/50 p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Persetujuan Tertunda</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-indigo-700">{{ number_format($stats['pending_approvals'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg bg-indigo-100 p-2.5 text-indigo-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></div>
        </div>
        <a href="{{ route('approvals.index') }}" class="mt-3 inline-block text-xs font-semibold text-indigo-700 hover:underline">Lihat daftar →</a>
    </div>

    @isset($stats['payroll_net'])
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Netto Payroll</p>
                    <p class="mt-2 text-xl font-bold tracking-tight text-emerald-700">Rp {{ number_format((float) $stats['payroll_net'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-2.5 text-emerald-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            </div>
            <p class="mt-3 text-xs text-slate-400">Periode terakhir</p>
        </div>
    @endisset
</div>

<!-- Charts -->
@if($attendanceChart || $categoryChart)
<div class="mt-6 grid gap-5 lg:grid-cols-3">
    @if($attendanceChart)
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Tren Kehadiran</h3>
                    <p class="mt-0.5 text-xs text-slate-400">7 hari terakhir</p>
                </div>
                <div class="flex items-center gap-3 text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Hadir</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Terlambat</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-sky-500"></span>Izin</span>
                </div>
            </div>
            <div class="mt-4 h-64"><canvas id="attendanceChart"></canvas></div>
        </div>
    @endif
    @if($categoryChart)
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900">Komposisi Pegawai</h3>
            <p class="mt-0.5 text-xs text-slate-400">Berdasarkan kategori</p>
            <div class="mt-4 h-64"><canvas id="categoryChart"></canvas></div>
        </div>
    @endif
</div>
@endif

<!-- Profil & Hari Libur -->
<div class="mt-6 grid gap-5 lg:grid-cols-3">
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Profil Singkat</h3>
            @if($employee)
                <a href="{{ route('profile.show') }}" class="text-xs font-semibold text-emerald-700 hover:underline">Lihat detail →</a>
            @endif
        </div>
        @if($employee)
            <div class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 text-2xl font-bold text-white">
                    {{ strtoupper(substr($employee->full_name, 0, 1)) }}
                </div>
                <div class="grid flex-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><p class="text-[11px] uppercase tracking-wide text-slate-400">Nama</p><p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $employee->full_name }}</p></div>
                    <div><p class="text-[11px] uppercase tracking-wide text-slate-400">NIP</p><p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $employee->nip }}</p></div>
                    <div><p class="text-[11px] uppercase tracking-wide text-slate-400">Unit</p><p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $employee->orgUnit?->name ?? '—' }}</p></div>
                    <div><p class="text-[11px] uppercase tracking-wide text-slate-400">Jabatan</p><p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $employee->position?->name ?? '—' }}</p></div>
                </div>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-500">Akun ini belum terhubung ke data pegawai.</p>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900">Hari Libur Berikutnya</h3>
        <div class="mt-4 space-y-3">
            @forelse($upcomingHolidays as $holiday)
                <div class="flex items-center justify-between border-b border-slate-100 pb-2 text-sm last:border-0 last:pb-0">
                    <span class="font-medium text-slate-700">{{ $holiday->name }}</span>
                    <span class="text-xs text-slate-400">{{ \App\Support\Format::date($holiday->date) }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada data hari libur.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Recent Employees -->
@if($recentEmployees && $recentEmployees->count())
    <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900">Pegawai Terbaru</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                    <tr><th class="py-2">Nama</th><th class="py-2">NIP</th><th class="py-2">Unit</th><th class="py-2">Jabatan</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($recentEmployees as $recent)
                    <tr class="hover:bg-slate-50/50">
                        <td class="py-2.5"><a href="{{ route('employees.show', $recent) }}" class="font-semibold text-emerald-700 hover:underline">{{ $recent->full_name }}</a></td>
                        <td class="py-2.5 text-slate-500">{{ $recent->nip }}</td>
                        <td class="py-2.5 text-slate-500">{{ $recent->orgUnit?->name ?? '—' }}</td>
                        <td class="py-2.5 text-slate-500">{{ $recent->position?->name ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($attendanceChart)
    new Chart(document.getElementById('attendanceChart'), {
        type: 'line',
        data: {
            labels: @json($attendanceChart['labels']),
            datasets: [
                { label: 'Hadir', data: @json($attendanceChart['hadir']), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)', fill: true, tension: .4, borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#10b981' },
                { label: 'Terlambat', data: @json($attendanceChart['terlambat']), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.1)', fill: true, tension: .4, borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#f59e0b' },
                { label: 'Izin', data: @json($attendanceChart['izin']), borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,.1)', fill: true, tension: .4, borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#0ea5e9' },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 8, titleFont: { family: 'Inter', size: 11 }, bodyFont: { family: 'Inter', size: 12 } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 } }, border: { display: false } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 }, precision: 0 }, border: { display: false } },
            },
        },
    });
    @endif
    @if($categoryChart)
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: @json($categoryChart['labels']),
            datasets: [{ data: @json($categoryChart['data']), backgroundColor: ['#059669','#0ea5e9','#8b5cf6','#f59e0b','#ef4444'], borderWidth: 0, hoverOffset: 6 }],
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '72%',
            plugins: { legend: { position: 'bottom', labels: { color: '#64748b', font: { family: 'Inter', size: 11 }, usePointStyle: true, pointStyle: 'circle', padding: 14 } }, tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 8 } },
        },
    });
    @endif
});
</script>
@endpush
@endsection

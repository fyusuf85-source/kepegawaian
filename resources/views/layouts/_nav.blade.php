@php($u = auth()->user())
@php($link = 'group flex items-center gap-3 rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 transition-colors')
@php($active = 'group flex items-center gap-3 rounded-lg bg-emerald-50 px-3 py-2 font-semibold text-emerald-700')
@php($icon = 'h-[18px] w-[18px] shrink-0 text-slate-400 group-hover:text-emerald-600')
@php($iconActive = 'h-[18px] w-[18px] shrink-0 text-emerald-600')
@php($section = 'px-3 pt-5 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400')

{{-- Dasbor --}}
<a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? $active : $link }}">
    <svg class="{{ request()->routeIs('dashboard') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    <span>Dasbor</span>
</a>

{{-- Kepegawaian --}}
@if($u->hasAnyPermission(['employee.view.self', 'employee.view.unit', 'employee.view.all']))
    <p class="{{ $section }}">Kepegawaian</p>
    <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? $active : $link }}">
        <svg class="{{ request()->routeIs('employees.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>Data Pegawai</span>
    </a>
@endif

@if($u->hasAnyPermission(['vacancy.view', 'applicant.view']))
    <p class="{{ $section }}">Rekrutmen</p>
    @if($u->hasPermission('vacancy.view'))
        <a href="{{ route('vacancies.index') }}" class="{{ request()->routeIs('vacancies.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('vacancies.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span>Lowongan</span>
        </a>
    @endif
    @if($u->hasPermission('applicant.view'))
        <a href="{{ route('applicants.index') }}" class="{{ request()->routeIs('applicants.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('applicants.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span>Pelamar</span>
        </a>
    @endif
@endif

{{-- Presensi & Cuti --}}
@if($u->hasAnyPermission([
    'attendance.self', 'attendance.view.self', 'attendance.view.unit', 'attendance.view.all',
    'attendance.report', 'attendance.correction.request', 'attendance.correction.approve.supervisor',
    'attendance.correction.approve.hrd', 'attendance.exception.manage',
    'leave.request', 'leave.view.self', 'leave.view.unit', 'leave.view.all',
    'leave.approve.supervisor', 'leave.approve.hrd', 'leave.type.manage', 'leave.balance.manage',
]))
    <p class="{{ $section }}">Presensi & Izin</p>
    @if($u->hasPermission('attendance.self'))
        <a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.index') ? $active : $link }}">
            <svg class="{{ request()->routeIs('attendance.index') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Presensi</span>
        </a>
    @endif
    @if($u->hasPermission('attendance.view.self'))
        <a href="{{ route('attendance.history') }}" class="{{ request()->routeIs('attendance.history') ? $active : $link }}">
            <svg class="{{ request()->routeIs('attendance.history') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Riwayat Absensi</span>
        </a>
    @endif
    @if($u->hasPermission('attendance.report'))
        <a href="{{ route('attendance.report') }}" class="{{ request()->routeIs('attendance.report') ? $active : $link }}">
            <svg class="{{ request()->routeIs('attendance.report') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Rekap Absensi</span>
        </a>
    @endif
    @if($u->hasAnyPermission(['attendance.correction.request', 'attendance.correction.approve.supervisor', 'attendance.correction.approve.hrd']))
        <a href="{{ route('corrections.index') }}" class="{{ request()->routeIs('corrections.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('corrections.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>Koreksi Absensi</span>
        </a>
    @endif
    @if($u->hasAnyPermission(['attendance.self', 'attendance.exception.manage']))
        <a href="{{ route('exceptions.index') }}" class="{{ request()->routeIs('exceptions.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('exceptions.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span>Dispensasi</span>
        </a>
    @endif
    @if($u->hasAnyPermission(['leave.request', 'leave.view.self', 'leave.view.unit', 'leave.view.all', 'leave.approve.supervisor', 'leave.approve.hrd']))
        <a href="{{ route('leave.index') }}" class="{{ request()->routeIs('leave.index', 'leave.create', 'leave.show') ? $active : $link }}">
            <svg class="{{ request()->routeIs('leave.index', 'leave.create', 'leave.show') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Izin & Cuti</span>
        </a>
    @endif
    @if($u->hasPermission('leave.balance.manage'))
        <a href="{{ route('leave.balances.index') }}" class="{{ request()->routeIs('leave.balances.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('leave.balances.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            <span>Saldo Cuti</span>
        </a>
    @endif
@endif

{{-- Keuangan --}}
@if($u->hasAnyPermission(['payroll.view.all', 'payroll.run.manage', 'payroll.adjustment.manage', 'payroll.component.view', 'payroll.report', 'reimbursement.view.all', 'loan.view.all']) || $u->employee)
    <p class="{{ $section }}">Keuangan</p>
    @if($u->hasAnyPermission(['payroll.view.all', 'payroll.run.manage', 'payroll.report']))
        <a href="{{ route('payroll.index') }}" class="{{ request()->routeIs('payroll.index', 'payroll.show', 'payroll.create', 'payroll.edit') ? $active : $link }}">
            <svg class="{{ request()->routeIs('payroll.index', 'payroll.show', 'payroll.create', 'payroll.edit') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Payroll</span>
        </a>
    @endif
    <a href="{{ route('finance.index') }}" class="{{ request()->routeIs('finance.*') ? $active : $link }}">
        <svg class="{{ request()->routeIs('finance.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
        <span>Reimbursement & Kasbon</span>
    </a>
    @if($u->hasPermission('payroll.adjustment.manage'))
        <a href="{{ route('payroll.adjustments.index') }}" class="{{ request()->routeIs('payroll.adjustments.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('payroll.adjustments.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            <span>Penyesuaian Payroll</span>
        </a>
    @endif
@endif

{{-- KPI --}}
@if($u->hasAnyPermission(['kpi.view.self', 'kpi.assess', 'kpi.verify', 'kpi.approve', 'coaching.view.self', 'coaching.manage']))
    <p class="{{ $section }}">Kinerja</p>
    <a href="{{ route('kpi.index') }}" class="{{ request()->routeIs('kpi.*') ? $active : $link }}">
        <svg class="{{ request()->routeIs('kpi.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span>Penilaian KPI</span>
    </a>
    <a href="{{ route('coaching.index') }}" class="{{ request()->routeIs('coaching.*') ? $active : $link }}">
        <svg class="{{ request()->routeIs('coaching.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <span>Pembinaan</span>
    </a>
@endif

{{-- Laporan --}}
@if($u->hasAnyPermission(['report.kpi', 'report.employee', 'report.attendance', 'report.payroll']))
    <p class="{{ $section }}">Laporan</p>
    <a href="{{ route('reports.executive') }}" class="{{ request()->routeIs('reports.*') ? $active : $link }}">
        <svg class="{{ request()->routeIs('reports.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
        <span>Pusat Laporan</span>
    </a>
@endif

{{-- Administrasi HRD --}}
@if($u->hasAnyPermission(['contract.view', 'decree.view', 'mutation.view', 'letter.view']))
    <p class="{{ $section }}">Administrasi HRD</p>
    @if($u->hasPermission('contract.view'))
        <a href="{{ route('contracts.index') }}" class="{{ request()->routeIs('contracts.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('contracts.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Kontrak Kerja</span>
        </a>
    @endif
    @if($u->hasPermission('decree.view'))
        <a href="{{ route('decrees.index') }}" class="{{ request()->routeIs('decrees.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('decrees.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            <span>Surat Keputusan</span>
        </a>
    @endif
    @if($u->hasPermission('mutation.view'))
        <a href="{{ route('mutations.index') }}" class="{{ request()->routeIs('mutations.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('mutations.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            <span>Mutasi & Jabatan</span>
        </a>
    @endif
    @if($u->hasPermission('letter.view'))
        <a href="{{ route('letters.index') }}" class="{{ request()->routeIs('letters.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('letters.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span>Surat</span>
        </a>
    @endif
@endif

{{-- Persetujuan --}}
@php($pendingApprovals = app(\App\Services\ApprovalService::class)->pendingForUser($u)->count())
@php($unreadNotifications = \App\Models\Notification::query()->where('user_id', $u->id)->whereNull('read_at')->count())
<p class="{{ $section }}">Inbox</p>
<a href="{{ route('approvals.index') }}" class="{{ request()->routeIs('approvals.*') ? $active : $link }}">
    <svg class="{{ request()->routeIs('approvals.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
    <span class="flex-1">Persetujuan</span>
    @if($pendingApprovals > 0)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">{{ $pendingApprovals }}</span>@endif
</a>
<a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? $active : $link }}">
    <svg class="{{ request()->routeIs('notifications.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
    <span class="flex-1">Notifikasi</span>
    @if($unreadNotifications > 0)<span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700">{{ $unreadNotifications }}</span>@endif
</a>

{{-- Organisasi & Master --}}
@if($u->hasAnyPermission(['org.view', 'org.manage', 'schedule.view', 'schedule.manage']))
    <p class="{{ $section }}">Data Master</p>
    @if($u->hasPermission('org.view') || $u->hasPermission('org.manage'))
        <a href="{{ route('master.org-units.index') }}" class="{{ request()->routeIs('master.org-units.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('master.org-units.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span>Unit Organisasi</span>
        </a>
        <a href="{{ route('master.positions.index') }}" class="{{ request()->routeIs('master.positions.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('master.positions.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
            <span>Jabatan</span>
        </a>
        <a href="{{ route('master.work-locations.index') }}" class="{{ request()->routeIs('master.work-locations.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('master.work-locations.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Lokasi Kerja</span>
        </a>
    @endif
    @if($u->hasPermission('schedule.view') || $u->hasPermission('schedule.manage'))
        <a href="{{ route('schedules.index') }}" class="{{ request()->routeIs('schedules.*', 'master.work-shifts.*', 'schedule-assignments.*', 'master.holidays.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('schedules.*', 'master.work-shifts.*', 'schedule-assignments.*', 'master.holidays.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Jadwal & Shift</span>
        </a>
    @endif
    @if($u->hasPermission('leave.type.manage'))
        <a href="{{ route('master.leave-types.index') }}" class="{{ request()->routeIs('master.leave-types.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('master.leave-types.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            <span>Jenis Izin</span>
        </a>
    @endif
@endif

{{-- Sistem --}}
@if($u->hasAnyPermission(['user.view', 'role.manage', 'audit.view', 'login_history.view', 'setting.view', 'letter.view']))
    <p class="{{ $section }}">Sistem</p>
    @if($u->hasPermission('user.view'))
        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('users.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span>Pengguna</span>
        </a>
    @endif
    @if($u->hasPermission('role.manage'))
        <a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('roles.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            <span>Peran & Akses</span>
        </a>
    @endif
    @if($u->hasPermission('audit.view'))
        <a href="{{ route('audit.index') }}" class="{{ request()->routeIs('audit.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('audit.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Audit Trail</span>
        </a>
    @endif
    @if($u->hasPermission('setting.view'))
        <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? $active : $link }}">
            <svg class="{{ request()->routeIs('settings.*') ? $iconActive : $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Pengaturan</span>
        </a>
    @endif
@endif

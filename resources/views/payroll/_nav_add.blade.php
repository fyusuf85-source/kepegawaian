@php($u = auth()->user())
@if($u->hasPermission('payroll.view.all') || $u->hasPermission('payroll.component.view'))
    <p class="px-3 pt-4 pb-1 text-xs uppercase tracking-wider text-emerald-400">Penggajian</p>
    @if($u->hasPermission('payroll.view.all'))
        <a href="{{ route('payroll.index') }}" class="{{ request()->routeIs('payroll.*') && !request()->routeIs('payroll.components.*') ? $active : $link }}">Periode Payroll</a>
    @endif
    @if($u->hasPermission('payroll.component.view'))
        <a href="{{ route('payroll.components.index') }}" class="{{ request()->routeIs('payroll.components.*') ? $active : $link }}">Komponen Gaji</a>
    @endif
@endif
@if($u->hasPermission('payroll.view.self'))
    <a href="{{ route('payslips.index') }}" class="{{ request()->routeIs('payslips.*') ? $active : $link }}">Slip Gaji Saya</a>
@endif

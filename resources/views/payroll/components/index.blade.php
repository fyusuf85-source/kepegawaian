@extends('layouts.app', ['title' => 'Master Komponen Gaji', 'heading' => 'Master Komponen Gaji', 'subheading' => 'Kelola komponen penghasilan dan potongan'])
@section('content')
<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
    <strong>Kerangka Tahap 5.</strong> Nominal tarif, formula BPJS, dan PPh 21 belum diisi. Perhitungan payroll final diblokir sampai data konfigurasi lengkap (K-01–K-12).
</div>
@if(auth()->user()->hasPermission('payroll.component.manage'))<div class="mt-3"><a href="{{ route('payroll.components.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Tambah Komponen</a></div>@endif
<div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
<tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Metode</th><th class="px-4 py-3">Nominal</th><th class="px-4 py-3">Aktif</th><th class="px-4 py-3"></th></tr>
</thead>
<tbody class="divide-y divide-slate-100">
@forelse($components as $item)
<tr>
<td class="px-4 py-3 font-mono text-xs">{{ $item->code }}</td>
<td class="px-4 py-3 font-medium">{{ $item->name }}</td>
<td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $item->type }}</span></td>
<td class="px-4 py-3 text-xs">{{ $item->category }}</td>
<td class="px-4 py-3 text-xs">{{ $item->calculation_method }}</td>
<td class="px-4 py-3 text-xs">{{ $item->amount ? number_format($item->amount, 0, ',', '.') : '—' }}</td>
<td class="px-4 py-3">@if($item->is_active)<span class="text-emerald-600 text-xs font-semibold">Ya</span>@else<span class="text-slate-400 text-xs">Tidak</span>@endif</td>
<td class="px-4 py-3 text-right"><a href="{{ route('payroll.components.edit', $item) }}" class="text-emerald-700 hover:underline text-xs font-semibold">Ubah</a></td>
</tr>
@empty
<tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">Belum ada komponen gaji.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $components->links() }}</div>
@endsection

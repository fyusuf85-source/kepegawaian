@extends('layouts.app', ['title' => 'Pengaturan', 'heading' => 'Pengaturan Aplikasi', 'subheading' => 'Nilai kebijakan tersimpan di database'])

@section('content')
    @php($groupLabels = [
        'app' => 'Aplikasi',
        'attendance' => 'Absensi',
        'payroll' => 'Penggajian',
        'security' => 'Keamanan',
        'notification' => 'Notifikasi',
    ])

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-5">
        @csrf @method('PUT')

        @foreach($settings as $group => $items)
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ $groupLabels[$group] ?? $group }}</h2>
                <div class="mt-3 space-y-4">
                    @foreach($items as $setting)
                        <div class="grid gap-2 md:grid-cols-3 md:items-center">
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ $setting->description ?: $setting->key }}</p>
                            </div>
                            <div class="md:col-span-2">
                                @if($setting->value_type === 'boolean')
                                    <select name="settings[{{ $setting->id }}]" @disabled(! $canManage) class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100">
                                        <option value="1" @selected(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))>Ya</option>
                                        <option value="0" @selected(! filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))>Tidak</option>
                                    </select>
                                @elseif(in_array($setting->value_type, ['integer', 'decimal'], true))
                                    <input type="number" step="{{ $setting->value_type === 'decimal' ? '0.01' : '1' }}"
                                           name="settings[{{ $setting->id }}]" value="{{ $setting->value }}"
                                           @disabled(! $canManage) class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100">
                                @elseif($setting->value_type === 'json')
                                    <textarea name="settings[{{ $setting->id }}]" rows="3" @disabled(! $canManage)
                                              class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-xs disabled:bg-slate-100">{{ $setting->value }}</textarea>
                                @else
                                    <input name="settings[{{ $setting->id }}]" value="{{ $setting->value }}"
                                           @disabled(! $canManage) class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        @if($canManage)
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Pengaturan</button>
        @else
            <p class="text-sm text-slate-500">Anda hanya dapat melihat pengaturan.</p>
        @endif
    </form>
@endsection

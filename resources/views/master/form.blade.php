@extends('layouts.app', [
    'title' => ($record ? 'Ubah ' : 'Tambah ').$def['singular'],
    'heading' => ($record ? 'Ubah ' : 'Tambah ').$def['singular'],
    'subheading' => $def['title'],
])

@section('content')
    <form method="POST"
          action="{{ $record ? route($def['route'].'.update', $record->getKey()) : route($def['route'].'.store') }}"
          class="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($record) @method('PUT') @endif

        @foreach($def['fields'] as $field)
            @php($name = $field['name'])
            @php($value = old($name, $record?->{$name}))
            @php($type = $field['type'])

            @if($type === 'checkbox')
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="hidden" name="{{ $name }}" value="0">
                    <input type="checkbox" name="{{ $name }}" value="1"
                           @checked(old($name, $record?->{$name} ?? true))
                           class="rounded border-slate-300">
                    {{ $field['label'] }}
                </label>
                @continue
            @endif

            <div>
                <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">
                    {{ $field['label'] }}
                    @if(! empty($field['required']))<span class="text-rose-600">*</span>@endif
                </label>

                @if($type === 'select')
                    <select id="{{ $name }}" name="{{ $name }}" @required(! empty($field['required']))
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">— Pilih —</option>
                        @foreach($field['options'] as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                @elseif(in_array($type, ['org_unit', 'parent_org_unit', 'employee'], true))
                    <select id="{{ $name }}" name="{{ $name }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">— Tidak ada —</option>
                        @foreach($options[$name] ?? [] as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                @elseif($type === 'textarea')
                    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $field['rows'] ?? 5 }}"
                              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm">{{ $value }}</textarea>
                @elseif($type === 'date')
                    <input id="{{ $name }}" name="{{ $name }}" type="date"
                           value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : '' }}"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @elseif($type === 'time')
                    <input id="{{ $name }}" name="{{ $name }}" type="time"
                           value="{{ $value ? substr((string) $value, 0, 5) : '' }}"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @elseif($type === 'number')
                    <input id="{{ $name }}" name="{{ $name }}" type="number" step="1" min="0" value="{{ $value }}"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @elseif($type === 'decimal')
                    <input id="{{ $name }}" name="{{ $name }}" type="number" step="any" value="{{ $value }}"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @else
                    <input id="{{ $name }}" name="{{ $name }}" type="text" value="{{ $value }}"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @endif

                @isset($field['help'])
                    <p class="mt-1 text-xs text-slate-500">{{ $field['help'] }}</p>
                @endisset
            </div>
        @endforeach

        <div class="flex items-center gap-3 border-t border-slate-200 pt-4">
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
            <a href="{{ route($def['route'].'.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

@extends('layouts.app', [
    'title' => $def['title'],
    'heading' => $def['title'],
    'subheading' => 'Master data '.config('app.name'),
])

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Cari {{ strtolower($def['title']) }}..."
                   class="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">Cari</button>
        </form>
        @if($canManage)
            <a href="{{ route($def['route'].'.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                Tambah {{ $def['singular'] }}
            </a>
        @endif
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    @foreach($def['columns'] as $column)
                        <th class="px-4 py-3">{{ $column['label'] }}</th>
                    @endforeach
                    @if($canManage)<th class="px-4 py-3 text-right">Aksi</th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($rows as $row)
                <tr class="hover:bg-slate-50">
                    @foreach($def['columns'] as $column)
                        @php($value = data_get($row, $column['key']))
                        <td class="px-4 py-3">
                            @if(($column['type'] ?? null) === 'boolean')
                                @if($value)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Nonaktif</span>
                                @endif
                            @elseif(($column['type'] ?? null) === 'date')
                                {{ \App\Support\Format::date($value) }}
                            @elseif(($column['type'] ?? null) === 'time')
                                {{ \App\Support\Format::time($value) }}
                            @else
                                {{ filled($value) ? $value : '—' }}
                            @endif
                        </td>
                    @endforeach
                    @if($canManage)
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route($def['route'].'.edit', $row->getKey()) }}" class="text-emerald-700 hover:underline">Ubah</a>
                            <form method="POST" action="{{ route($def['route'].'.destroy', $row->getKey()) }}" class="inline"
                                  onsubmit="return confirm('Hapus {{ $def['singular'] }} ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="ml-3 text-rose-600 hover:underline">Hapus</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ count($def['columns']) + 1 }}" class="px-4 py-6 text-center text-slate-500">Belum ada data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
@endsection

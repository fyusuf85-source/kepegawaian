@extends('layouts.app', ['title' => 'Audit Trail', 'heading' => 'Audit Trail', 'subheading' => 'Catatan append-only aktivitas data'])

@section('content')
    <form method="GET" class="grid gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-6">
        <select name="event" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua event</option>
            @foreach($events as $event)
                <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
            @endforeach
        </select>
        <select name="user_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua pengguna</option>
            @foreach($users as $id => $name)
                <option value="{{ $id }}" @selected((string) request('user_id') === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        <input name="type" value="{{ request('type') }}" placeholder="Tipe model" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">Terapkan</button>
    </form>

    <div class="mt-4 space-y-2">
        @forelse($logs as $log)
            <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs">{{ $log->event }}</span>
                        <span class="ml-2 font-medium">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id ?? '—' }}</span>
                        <span class="ml-2 text-slate-500">oleh {{ $log->user?->name ?? 'sistem' }}</span>
                    </div>
                    <span class="text-xs text-slate-500">{{ \App\Support\Format::dateTime($log->created_at) }} · {{ $log->ip_address }}</span>
                </div>
                @if($log->old_values || $log->new_values)
                    <details class="mt-2">
                        <summary class="cursor-pointer text-xs font-medium text-emerald-700">Lihat perubahan</summary>
                        <div class="mt-2 grid gap-2 md:grid-cols-2">
                            <pre class="max-h-56 overflow-auto rounded bg-slate-50 p-2 text-xs">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            <pre class="max-h-56 overflow-auto rounded bg-emerald-50 p-2 text-xs">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </details>
                @endif
            </div>
        @empty
            <p class="rounded-lg border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Belum ada catatan audit.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection

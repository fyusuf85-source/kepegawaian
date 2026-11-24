@extends('layouts.app', ['title' => 'Riwayat Login', 'heading' => 'Riwayat Login', 'subheading' => 'Percobaan masuk berhasil dan gagal'])

@section('content')
    <form method="GET" class="grid gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
        <select name="user_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua pengguna</option>
            @foreach($users as $id => $name)
                <option value="{{ $id }}" @selected((string) request('user_id') === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        <select name="success" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            <option value="1" @selected(request('success') === '1')>Berhasil</option>
            <option value="0" @selected(request('success') === '0')>Gagal</option>
        </select>
        <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">Terapkan</button>
    </form>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Pengguna</th><th class="px-4 py-3">Username Dicoba</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Alasan</th><th class="px-4 py-3">IP</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($histories as $history)
                <tr>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::dateTime($history->login_at) }}</td>
                    <td class="px-4 py-3">{{ $history->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $history->username_attempt }}</td>
                    <td class="px-4 py-3">
                        @if($history->success)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Berhasil</span>
                        @else
                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-700">Gagal</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs">{{ $history->failure_reason ?: '—' }}</td>
                    <td class="px-4 py-3 text-xs">{{ $history->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada riwayat login.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $histories->links() }}</div>
@endsection

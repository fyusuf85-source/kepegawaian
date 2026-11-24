@extends('layouts.app', ['title' => 'Notifikasi', 'heading' => 'Notifikasi', 'subheading' => 'Pengingat kontrak dan persetujuan'])

@section('content')
    <div class="mb-3 flex items-center justify-between">
        <p class="text-sm text-slate-500">Total {{ $notifications->total() }} notifikasi</p>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">Tandai Semua Dibaca</button>
        </form>
    </div>

    <div class="space-y-2">
        @forelse($notifications as $notification)
            <div class="rounded-xl border px-4 py-3 shadow-sm {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-emerald-200 bg-emerald-50/50' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold">{{ $notification->title }}</p>
                        <p class="text-sm text-slate-600">{{ $notification->message }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $notification->type }} · {{ \App\Support\Format::dateTime($notification->created_at) }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        @if(isset($notification->data['approval_instance_id']))
                            <a href="{{ route('approvals.show', $notification->data['approval_instance_id']) }}" class="text-emerald-700 hover:underline">Buka</a>
                        @elseif(isset($notification->data['kpi_assessment_id']))
                            <a href="{{ route('kpi.assessments.show', $notification->data['kpi_assessment_id']) }}" class="text-emerald-700 hover:underline">Buka</a>
                        @elseif(isset($notification->data['coaching_record_id']))
                            <a href="{{ route('coaching.index') }}" class="text-emerald-700 hover:underline">Buka</a>
                        @endif
                        @if(! $notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                @csrf
                                <button class="text-slate-600 hover:underline">Tandai dibaca</button>
                            </form>
                        @else
                            <span class="text-xs text-slate-400">Sudah dibaca</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-slate-500">Belum ada notifikasi.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection

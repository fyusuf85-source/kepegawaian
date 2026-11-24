@if($approval)
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-slate-700">Alur Persetujuan</h2>
            <a href="{{ route('approvals.show', $approval) }}" class="text-sm text-emerald-700 hover:underline">Detail</a>
        </div>
        <p class="mt-2 text-sm">
            Status:
            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $approval->status === 'disetujui' ? 'bg-emerald-100 text-emerald-700' : ($approval->status === 'ditolak' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                {{ ucfirst(str_replace('_', ' ', $approval->status)) }}
            </span>
            @if($approval->isPending())
                <span class="ml-2 text-xs text-slate-500">Langkah {{ $approval->current_step }} dari {{ $approval->flow->steps->count() }}</span>
            @endif
        </p>
        <ol class="mt-3 space-y-2 text-sm">
            @foreach($approval->actions as $action)
                <li class="rounded-lg bg-slate-50 px-3 py-2">
                    <span class="font-medium">{{ $action->actor?->name }}</span>
                    <span class="text-xs uppercase text-slate-500">{{ $action->action }}</span>
                    <span class="text-xs text-slate-500">· {{ \App\Support\Format::dateTime($action->acted_at) }}</span>
                    @if($action->note)<div class="mt-1 text-xs text-slate-600">{{ $action->note }}</div>@endif
                </li>
            @endforeach
            @if($approval->actions->isEmpty())
                <li class="text-sm text-slate-500">Belum ada keputusan.</li>
            @endif
        </ol>
    </div>
@endif

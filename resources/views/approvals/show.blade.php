@extends('layouts.app', ['title' => 'Detail Persetujuan', 'heading' => 'Detail Persetujuan', 'subheading' => $approval->flow->name])

@section('content')
    @php
        $subject = $approval->subject;
        $link = null;
        if ($subject instanceof \App\Models\EmploymentContract) { $link = route('contracts.show', $subject); }
        elseif ($subject instanceof \App\Models\Decree) { $link = route('decrees.show', $subject); }
        elseif ($subject instanceof \App\Models\Mutation) { $link = route('mutations.show', $subject); }
        elseif ($subject instanceof \App\Models\EmployeeLetter) { $link = route('letters.show', $subject); }
    @endphp

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs uppercase text-slate-500">Dokumen</dt><dd class="font-medium">{{ $subject && method_exists($subject, 'approvalLabel') ? $subject->approvalLabel() : $approval->flow->name }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Jenis Alur</dt><dd>{{ $approval->flow->name }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Pengaju</dt><dd>{{ $approval->requester?->name }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Status</dt><dd class="font-semibold">{{ $approval->status }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Diajukan</dt><dd>{{ \App\Support\Format::dateTime($approval->requested_at) }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Langkah</dt><dd>{{ $approval->current_step }} / {{ $approval->flow->steps->count() }}</dd></div>
                </dl>
                @if($link)<a href="{{ $link }}" class="mt-3 inline-block text-sm text-emerald-700 hover:underline">Buka dokumen →</a>@endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Riwayat Keputusan</h2>
                <ol class="mt-3 space-y-2 text-sm">
                    @forelse($approval->actions as $action)
                        <li class="rounded-lg bg-slate-50 px-3 py-2">
                            <span class="font-medium">{{ $action->actor?->name }}</span>
                            <span class="ml-1 rounded-full bg-white px-2 py-0.5 text-xs uppercase">{{ $action->action }}</span>
                            <span class="text-xs text-slate-500">· langkah {{ $action->sequence }} · {{ \App\Support\Format::dateTime($action->acted_at) }}</span>
                            @if($action->note)<div class="mt-1 text-xs text-slate-600">{{ $action->note }}</div>@endif
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Belum ada keputusan.</li>
                    @endforelse
                </ol>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Tahapan</h2>
                <ol class="mt-3 space-y-2 text-sm">
                    @foreach($approval->flow->steps as $step)
                        @php($done = $approval->actions->where('sequence', $step->sequence)->where('action', 'setuju')->isNotEmpty())
                        <li class="flex items-center justify-between rounded-lg border px-3 py-2 {{ $done ? 'border-emerald-200 bg-emerald-50' : ($approval->isPending() && $approval->current_step === $step->sequence ? 'border-amber-200 bg-amber-50' : 'border-slate-200') }}">
                            <span>{{ $step->sequence }}. {{ $step->role?->name }}</span>
                            <span class="text-xs">{{ $done ? 'selesai' : ($approval->current_step === $step->sequence ? 'aktif' : 'menunggu') }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>

            @if($canAct)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-semibold text-slate-700">Keputusan Anda</h2>
                    <form method="POST" action="{{ route('approvals.approve', $approval) }}" class="space-y-2">
                        @csrf
                        <textarea name="note" rows="2" placeholder="Catatan (opsional)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Setujui</button>
                    </form>
                    <form method="POST" action="{{ route('approvals.reject', $approval) }}" class="space-y-2 border-t border-slate-100 pt-3">
                        @csrf
                        <textarea name="note" rows="2" required placeholder="Alasan penolakan (wajib)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        <button class="w-full rounded-lg border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Tolak</button>
                    </form>
                </div>
            @endif

            @if($approval->isPending() && $approval->requested_by === auth()->id())
                <form method="POST" action="{{ route('approvals.cancel', $approval) }}" onsubmit="return confirm('Batalkan pengajuan ini?')" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    @csrf
                    <button class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Batalkan Pengajuan</button>
                </form>
            @endif

            <a href="{{ route('approvals.index') }}" class="block text-sm text-slate-600 hover:underline">← Kembali ke daftar persetujuan</a>
        </div>
    </div>
@endsection

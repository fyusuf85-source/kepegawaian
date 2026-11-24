@extends('layouts.app', ['title' => 'Persetujuan', 'heading' => 'Alur Persetujuan', 'subheading' => 'Pengajuan yang menunggu keputusan Anda dan riwayat pengajuan sendiri'])

@section('content')
    <div class="space-y-6">
        <section>
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Menunggu Keputusan Saya ({{ $pending->count() }})</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">Dokumen</th><th class="px-4 py-3">Pengaju</th><th class="px-4 py-3">Langkah</th><th class="px-4 py-3">Diajukan</th><th class="px-4 py-3 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($pending as $approval)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $approval->subject && method_exists($approval->subject, 'approvalLabel') ? $approval->subject->approvalLabel() : $approval->flow->name }}</td>
                            <td class="px-4 py-3">{{ $approval->requester?->name }}</td>
                            <td class="px-4 py-3 text-xs">{{ $approval->current_step }} / {{ $approval->flow->steps->count() }} · {{ $approval->currentStep()?->role?->name }}</td>
                            <td class="px-4 py-3 text-xs">{{ \App\Support\Format::dateTime($approval->requested_at) }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('approvals.show', $approval) }}" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">Tinjau</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Tidak ada pengajuan yang menunggu keputusan Anda.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Pengajuan Saya</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">Dokumen</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Diajukan</th><th class="px-4 py-3">Selesai</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($submitted as $approval)
                        <tr>
                            <td class="px-4 py-3">{{ $approval->subject && method_exists($approval->subject, 'approvalLabel') ? $approval->subject->approvalLabel() : $approval->flow->name }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $approval->status }}</span></td>
                            <td class="px-4 py-3 text-xs">{{ \App\Support\Format::dateTime($approval->requested_at) }}</td>
                            <td class="px-4 py-3 text-xs">{{ \App\Support\Format::dateTime($approval->completed_at) }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('approvals.show', $approval) }}" class="text-emerald-700 hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada pengajuan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $submitted->links() }}</div>
        </section>
    </div>
@endsection

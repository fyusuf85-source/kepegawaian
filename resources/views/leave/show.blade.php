@extends('layouts.app', [
    'title' => 'Detail Izin/Cuti',
    'heading' => 'Detail Izin/Cuti',
    'subheading' => $leave->leaveType?->name.' · '.$leave->employee?->full_name,
])

@section('content')
    @php($statuses = \App\Models\LeaveRequest::statusLabels())

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Pegawai</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $leave->employee?->full_name }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $leave->employee?->nip }} · {{ $leave->employee?->position?->name }} · {{ $leave->employee?->orgUnit?->name }}
                    </p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                    {{ $statuses[$leave->status] ?? $leave->status }}
                </span>
            </div>

            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-slate-500">Jenis</dt>
                    <dd class="font-medium">{{ $leave->leaveType?->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Total hari kerja</dt>
                    <dd class="font-medium">{{ $leave->total_days }} hari</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Rentang tanggal</dt>
                    <dd class="font-medium">{{ \App\Support\Format::date($leave->start_date) }} – {{ \App\Support\Format::date($leave->end_date) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Diajukan</dt>
                    <dd class="font-medium">{{ \App\Support\Format::dateTime($leave->submitted_at) }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-slate-500">Alasan</dt>
                    <dd class="mt-1 rounded-lg bg-slate-50 p-3">{{ $leave->reason }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-slate-500">Hari efektif</dt>
                    <dd class="mt-1 flex flex-wrap gap-1">
                        @foreach($leave->days as $day)
                            <span class="rounded-md bg-slate-100 px-2 py-1 text-xs">{{ \App\Support\Format::date($day->date) }}</span>
                        @endforeach
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-slate-500">Dokumen</dt>
                    <dd class="mt-1">
                        @if($leave->document_path)
                            <a href="{{ route('leave.document', $leave) }}" class="text-emerald-700 hover:underline">Unduh dokumen</a>
                        @else
                            <span class="text-xs text-slate-500">Tidak ada dokumen.</span>
                        @endif
                    </dd>
                </div>
                @if($leave->rejection_reason)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">Alasan penolakan</dt>
                        <dd class="mt-1 rounded-lg bg-rose-50 p-3 text-rose-900">{{ $leave->rejection_reason }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="space-y-4">
            @if($canApproveSupervisor)
                <form method="POST" action="{{ route('leave.approve-supervisor', $leave) }}"
                      class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    @csrf
                    <p class="text-sm font-semibold text-emerald-900">Persetujuan atasan</p>
                    <p class="mt-1 text-xs text-emerald-800">Setujui untuk diteruskan ke HRD.</p>
                    <textarea name="note" rows="2" placeholder="Catatan (opsional)"
                              class="mt-3 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm"></textarea>
                    <button class="mt-3 w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                        Setujui &amp; Teruskan ke HRD
                    </button>
                </form>
            @endif

            @if($canApproveHrd)
                <form method="POST" action="{{ route('leave.approve-hrd', $leave) }}"
                      class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    @csrf
                    <p class="text-sm font-semibold text-emerald-900">Verifikasi HRD</p>
                    <p class="mt-1 text-xs text-emerald-800">
                        Persetujuan akan membuat absensi bertanda izin/sakit/dispensasi dan memotong saldo bila berlaku.
                    </p>
                    <textarea name="note" rows="2" placeholder="Catatan (opsional)"
                              class="mt-3 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm"></textarea>
                    <button class="mt-3 w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                        Setujui &amp; Terapkan
                    </button>
                </form>
            @endif

            @if($canApproveSupervisor || $canApproveHrd)
                <form method="POST" action="{{ route('leave.reject', $leave) }}"
                      class="rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    @csrf
                    <p class="text-sm font-semibold text-rose-900">Tolak pengajuan</p>
                    <textarea name="reason" rows="2" required minlength="5" placeholder="Alasan penolakan (wajib)"
                              class="mt-2 w-full rounded-lg border border-rose-300 px-3 py-2 text-sm"></textarea>
                    <button class="mt-2 w-full rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                        Tolak Pengajuan
                    </button>
                </form>
            @endif

            @if($canCancel)
                <form method="POST" action="{{ route('leave.cancel', $leave) }}">
                    @csrf
                    <button class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Batalkan Pengajuan
                    </button>
                </form>
            @endif

            <a href="{{ route('leave.index') }}" class="block text-center text-sm text-slate-600 hover:underline">Kembali ke daftar izin/cuti</a>
        </div>
    </div>
@endsection

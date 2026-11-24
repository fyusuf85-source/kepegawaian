@extends('layouts.app', [
    'title' => 'Detail Koreksi Absensi',
    'heading' => 'Detail Koreksi Absensi',
    'subheading' => \App\Support\Format::date($correction->work_date).' · '.$correction->employee?->full_name,
])

@section('content')
    @php($labels = \App\Models\AttendanceCorrection::typeLabels())
    @php($statuses = \App\Models\AttendanceCorrection::statusLabels())

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Pegawai</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $correction->employee?->full_name }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $correction->employee?->nip }} ·
                        {{ $correction->employee?->position?->name }} ·
                        {{ $correction->employee?->orgUnit?->name }}
                    </p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                    {{ $statuses[$correction->status] ?? $correction->status }}
                </span>
            </div>

            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-slate-500">Tanggal</dt>
                    <dd class="font-medium">{{ \App\Support\Format::date($correction->work_date) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Jenis koreksi</dt>
                    <dd class="font-medium">{{ $labels[$correction->correction_type] ?? $correction->correction_type }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Jam masuk diminta</dt>
                    <dd class="font-medium">{{ $correction->requested_check_in_at?->copy()->timezone('Asia/Jakarta')->format('H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Jam pulang diminta</dt>
                    <dd class="font-medium">{{ $correction->requested_check_out_at?->copy()->timezone('Asia/Jakarta')->format('H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Atasan langsung</dt>
                    <dd class="font-medium">{{ $correction->supervisor?->full_name ?? 'Tidak ada (langsung HRD)' }}</dd>
                    <dd class="text-xs text-slate-500">
                        {{ $correction->supervisor_decision_at ? 'Diputuskan '.\App\Support\Format::dateTime($correction->supervisor_decision_at) : 'Belum diputuskan' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Verifikator HRD</dt>
                    <dd class="font-medium">{{ $correction->hrd?->name ?? '—' }}</dd>
                    <dd class="text-xs text-slate-500">
                        {{ $correction->hrd_decision_at ? 'Diputuskan '.\App\Support\Format::dateTime($correction->hrd_decision_at) : 'Belum diputuskan' }}
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-slate-500">Alasan pengajuan</dt>
                    <dd class="mt-1 rounded-lg bg-slate-50 p-3 text-sm">{{ $correction->reason }}</dd>
                </div>
                @if($correction->supervisor_note || $correction->hrd_note)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">Catatan keputusan</dt>
                        <dd class="mt-1 space-y-1 text-sm">
                            @if($correction->supervisor_note)<p><span class="text-slate-500">Atasan:</span> {{ $correction->supervisor_note }}</p>@endif
                            @if($correction->hrd_note)<p><span class="text-slate-500">HRD:</span> {{ $correction->hrd_note }}</p>@endif
                        </dd>
                    </div>
                @endif
                <div class="sm:col-span-2">
                    <dt class="text-xs text-slate-500">Bukti</dt>
                    <dd class="mt-1">
                        @if($correction->evidence_path)
                            <a href="{{ route('corrections.evidence', $correction) }}" class="text-emerald-700 hover:underline">Unduh bukti</a>
                        @else
                            <span class="text-xs text-slate-500">Tidak ada berkas bukti.</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="space-y-4">
            @if($correction->attendance)
                <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
                    <p class="font-semibold text-slate-800">Absensi saat ini</p>
                    <p class="mt-2 text-xs text-slate-500">Masuk</p>
                    <p class="font-medium">{{ $correction->attendance->checkInLocal()?->format('H:i') ?? '—' }}</p>
                    <p class="mt-2 text-xs text-slate-500">Pulang</p>
                    <p class="font-medium">{{ $correction->attendance->checkOutLocal()?->format('H:i') ?? '—' }}</p>
                    <p class="mt-2 text-xs text-slate-500">Status</p>
                    <p class="font-medium">{{ \App\Models\Attendance::statusLabels()[$correction->attendance->status] ?? $correction->attendance->status }}</p>
                    <p class="mt-3 text-xs text-slate-500">
                        <a href="{{ route('attendance.show', $correction->attendance) }}" class="text-emerald-700 hover:underline">Lihat detail absensi</a>
                    </p>
                </div>
            @endif

            @if($canApproveSupervisor)
                <form method="POST" action="{{ route('corrections.approve-supervisor', $correction) }}"
                      class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    @csrf
                    <p class="text-sm font-semibold text-emerald-900">Persetujuan atasan</p>
                    <p class="mt-1 text-xs text-emerald-800">Setujui untuk diteruskan ke verifikasi HRD.</p>
                    <textarea name="note" rows="2" placeholder="Catatan (opsional)"
                              class="mt-3 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm"></textarea>
                    <button class="mt-3 w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                        Setujui &amp; Teruskan ke HRD
                    </button>
                </form>
            @endif

            @if($canApproveHrd)
                <form method="POST" action="{{ route('corrections.approve-hrd', $correction) }}"
                      class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    @csrf
                    <p class="text-sm font-semibold text-emerald-900">Verifikasi HRD</p>
                    <p class="mt-1 text-xs text-emerald-800">Persetujuan akan langsung mengubah data absensi.</p>
                    <textarea name="note" rows="2" placeholder="Catatan (opsional)"
                              class="mt-3 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm"></textarea>
                    <button class="mt-3 w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                        Setujui &amp; Terapkan
                    </button>
                </form>
            @endif

            @if($canApproveSupervisor || $canApproveHrd)
                <form method="POST" action="{{ route('corrections.reject', $correction) }}"
                      class="rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    @csrf
                    <p class="text-sm font-semibold text-rose-900">Tolak pengajuan</p>
                    <textarea name="reason" rows="2" required minlength="5" placeholder="Alasan penolakan (wajib)"
                              class="mt-2 w-full rounded-lg border border-rose-300 px-3 py-2 text-sm"></textarea>
                    <button class="mt-2 w-full rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                        Tolak Koreksi
                    </button>
                </form>
            @endif

            @if($canCancel)
                <form method="POST" action="{{ route('corrections.cancel', $correction) }}">
                    @csrf
                    <button class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Batalkan Pengajuan
                    </button>
                </form>
            @endif

            <a href="{{ route('corrections.index') }}" class="block text-center text-sm text-slate-600 hover:underline">Kembali ke daftar koreksi</a>
        </div>
    </div>
@endsection

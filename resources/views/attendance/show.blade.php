@extends('layouts.app', [
    'title' => 'Detail Absensi',
    'heading' => 'Detail Absensi',
    'subheading' => \App\Support\Format::date($attendance->work_date).' · '.$attendance->employee?->full_name,
])

@section('content')
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-xs text-slate-500">Pegawai</dt><dd class="font-medium">{{ $attendance->employee?->full_name }}</dd><dd class="text-xs text-slate-500">{{ $attendance->employee?->nip }}</dd></div>
                <div><dt class="text-xs text-slate-500">Unit / Jabatan</dt><dd>{{ $attendance->employee?->orgUnit?->name }}</dd><dd class="text-xs text-slate-500">{{ $attendance->employee?->position?->name }}</dd></div>
                <div><dt class="text-xs text-slate-500">Shift</dt><dd>{{ $attendance->shift?->name }}</dd><dd class="text-xs text-slate-500">{{ \App\Support\Format::time($attendance->shift?->start_time) }}–{{ \App\Support\Format::time($attendance->shift?->end_time) }}</dd></div>
                <div><dt class="text-xs text-slate-500">Status</dt><dd><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ \App\Models\Attendance::statusLabels()[$attendance->status] ?? $attendance->status }}</span></dd></div>
                <div><dt class="text-xs text-slate-500">Masuk</dt><dd>{{ $attendance->checkInLocal()?->format('H:i') ?? '—' }}</dd><dd class="text-xs text-slate-500">{{ $attendance->check_in_distance_m !== null ? round((float) $attendance->check_in_distance_m).' m · akurasi '.round((float) $attendance->check_in_accuracy_m).' m' : '' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Pulang</dt><dd>{{ $attendance->checkOutLocal()?->format('H:i') ?? '—' }}</dd><dd class="text-xs text-slate-500">{{ $attendance->check_out_distance_m !== null ? round((float) $attendance->check_out_distance_m).' m · akurasi '.round((float) $attendance->check_out_accuracy_m).' m' : '' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Terlambat / pulang awal</dt><dd>{{ $attendance->late_minutes }} / {{ $attendance->early_leave_minutes }} menit</dd></div>
                <div><dt class="text-xs text-slate-500">Menit kerja</dt><dd>{{ intdiv($attendance->work_minutes, 60) }} jam {{ $attendance->work_minutes % 60 }} menit</dd></div>
                <div><dt class="text-xs text-slate-500">Sumber</dt><dd>{{ $attendance->source }} @if($attendance->is_corrected) · dikoreksi @endif</dd></div>
                <div><dt class="text-xs text-slate-500">Catatan</dt><dd>{{ $attendance->notes ?? '—' }}</dd></div>
            </dl>

            @if($attendance->is_locked)
                <p class="mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-900">Data terkunci payroll. Perubahan hanya melalui koreksi resmi.</p>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800">Foto presensi</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach(['in' => 'Masuk', 'out' => 'Pulang'] as $type => $label)
                        @php($path = $type === 'in' ? $attendance->check_in_photo_path : $attendance->check_out_photo_path)
                        <div>
                            <p class="text-xs text-slate-500">{{ $label }}</p>
                            @if($path)
                                <a href="{{ route('attendance.photo', [$attendance, $type]) }}" target="_blank">
                                    <img src="{{ route('attendance.photo', [$attendance, $type]) }}" alt="Foto {{ $label }}" class="mt-1 h-40 w-full rounded-lg border border-slate-200 object-cover">
                                </a>
                            @else
                                <p class="mt-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-500">Tidak ada foto.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-800">Riwayat koreksi</h2>
                    @if(auth()->user()->hasPermission('attendance.correction.request') && auth()->id() === $attendance->employee?->user?->id && ! $attendance->is_locked)
                        <a href="{{ route('corrections.create', ['attendance_id' => $attendance->id]) }}" class="text-xs text-emerald-700 hover:underline">Ajukan koreksi</a>
                    @endif
                </div>
                <ul class="mt-2 divide-y divide-slate-100 text-sm">
                    @forelse($attendance->corrections as $correction)
                        <li class="py-2">
                            <a href="{{ route('corrections.show', $correction) }}" class="text-emerald-700 hover:underline">{{ \App\Models\AttendanceCorrection::typeLabels()[$correction->correction_type] ?? $correction->correction_type }}</a>
                            <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ \App\Models\AttendanceCorrection::statusLabels()[$correction->status] ?? $correction->status }}</span>
                            <p class="text-xs text-slate-500">{{ \App\Support\Format::date($correction->work_date) }} · {{ $correction->reason }}</p>
                        </li>
                    @empty
                        <li class="py-3 text-xs text-slate-500">Belum ada koreksi.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection

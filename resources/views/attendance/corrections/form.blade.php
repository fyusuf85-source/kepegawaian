@extends('layouts.app', [
    'title' => 'Koreksi Absensi',
    'heading' => 'Ajukan Koreksi Absensi',
    'subheading' => $employee->full_name.' · '.$employee->nip,
])

@section('content')
    @if($attendance)
        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
            <p class="font-medium">Absensi {{ \App\Support\Format::date($attendance->work_date) }}</p>
            <p class="text-xs text-slate-500">
                Masuk {{ $attendance->checkInLocal()?->format('H:i') ?? '—' }} ·
                Pulang {{ $attendance->checkOutLocal()?->format('H:i') ?? '—' }} ·
                Status {{ \App\Models\Attendance::statusLabels()[$attendance->status] ?? $attendance->status }}
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('corrections.store') }}" enctype="multipart/form-data"
          class="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($attendance)
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal <span class="text-rose-600">*</span></label>
                <input type="date" name="work_date" value="{{ old('work_date', $workDate) }}" required
                       max="{{ now('Asia/Jakarta')->toDateString() }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jenis koreksi <span class="text-rose-600">*</span></label>
                <select name="correction_type" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(\App\Models\AttendanceCorrection::typeLabels() as $value => $label)
                        @unless($value === 'status')
                            <option value="{{ $value }}" @selected(old('correction_type', 'masuk') === $value)>{{ $label }}</option>
                        @endunless
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jam masuk yang diminta</label>
                <input type="time" name="requested_check_in_at" value="{{ old('requested_check_in_at', $attendance?->checkInLocal()?->format('H:i')) }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Wajib untuk koreksi masuk/keduanya.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jam pulang yang diminta</label>
                <input type="time" name="requested_check_out_at" value="{{ old('requested_check_out_at', $attendance?->checkOutLocal()?->format('H:i')) }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Wajib untuk koreksi pulang/keduanya.</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Alasan <span class="text-rose-600">*</span></label>
                <textarea name="reason" rows="4" required minlength="10" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('reason') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Minimal 10 karakter, jelaskan kejadian sebenarnya.</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Bukti (opsional)</label>
                <input type="file" name="evidence" accept="image/*,application/pdf"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau PDF maksimum 4 MB.</p>
            </div>
        </div>

        <div class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">
            Koreksi diproses dua tingkat: atasan langsung, lalu verifikasi HRD. Data absensi baru berubah setelah HRD menyetujui.
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Kirim Pengajuan</button>
            <a href="{{ route('corrections.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

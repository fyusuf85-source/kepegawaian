@extends('layouts.app', [
    'title' => 'Ajukan Dispensasi Absensi',
    'heading' => 'Ajukan Dispensasi Absensi',
    'subheading' => $employee->full_name.' · '.$employee->nip,
])

@section('content')
    <form method="POST" action="{{ route('exceptions.store') }}" enctype="multipart/form-data"
          class="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal <span class="text-rose-600">*</span></label>
                <input type="date" name="work_date" value="{{ old('work_date', $workDate) }}" required
                       max="{{ now('Asia/Jakarta')->toDateString() }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jenis dispensasi <span class="text-rose-600">*</span></label>
                <select name="type" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(\App\Models\AttendanceException::typeLabels() as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jam masuk</label>
                <input type="time" name="check_in_at" value="{{ old('check_in_at') }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jam pulang</label>
                <input type="time" name="check_out_at" value="{{ old('check_out_at') }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Isi minimal salah satu jam masuk atau pulang.</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Alasan <span class="text-rose-600">*</span></label>
                <textarea name="reason" rows="4" required minlength="10"
                          class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('reason') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Minimal 10 karakter. Jelaskan kondisi GPS/kamera atau penugasan luar.</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Bukti (opsional)</label>
                <input type="file" name="evidence" accept="image/*,application/pdf"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau PDF maksimum 4 MB.</p>
            </div>
        </div>

        <div class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">
            Dispensasi harus disetujui HRD. Setelah disetujui, status absensi tanggal tersebut menjadi <strong>Dispensasi</strong>.
            Kebijakan penugasan luar (K-30) masih menunggu konfirmasi; pengajuan dicatat apa adanya.
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Kirim Pengajuan</button>
            <a href="{{ route('exceptions.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

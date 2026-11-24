@extends('layouts.app', [
    'title' => 'Ajukan Izin/Cuti',
    'heading' => 'Ajukan Izin/Cuti',
    'subheading' => $employee->full_name.' · '.$employee->nip,
])

@section('content')
    <form method="POST" action="{{ route('leave.store') }}" enctype="multipart/form-data"
          class="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Jenis izin/cuti <span class="text-rose-600">*</span></label>
                <select name="leave_type_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Pilih jenis —</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" @selected((string) old('leave_type_id') === (string) $type->id)>
                            {{ $type->name }}@if($type->affects_balance) (mengurangi saldo)@endif
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">
                    Aturan dokumen dan saldo mengikuti master jenis izin (sementara, menunggu konfirmasi kebijakan K-16).
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal mulai <span class="text-rose-600">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date', now('Asia/Jakarta')->toDateString()) }}" required
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal selesai <span class="text-rose-600">*</span></label>
                <input type="date" name="end_date" value="{{ old('end_date', now('Asia/Jakarta')->toDateString()) }}" required
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Alasan <span class="text-rose-600">*</span></label>
                <textarea name="reason" rows="4" required minlength="10"
                          class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('reason') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Minimal 10 karakter.</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Dokumen pendukung</label>
                <input type="file" name="document" accept="image/*,application/pdf"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">
                    Wajib untuk jenis yang mensyaratkan dokumen (mis. surat sakit). JPG, PNG, atau PDF maksimum 4 MB.
                </p>
            </div>
        </div>

        <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
            Hari yang dihitung hanya hari kerja efektif sesuai jadwal dan hari libur.
            Alur persetujuan: atasan langsung → HRD. Saldo diperiksa terhadap pengajuan yang masih menunggu.
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Kirim Pengajuan</button>
            <a href="{{ route('leave.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

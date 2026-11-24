@extends('layouts.app', ['title' => 'Konversi Pelamar', 'heading' => 'Konversi Pelamar menjadi Pegawai', 'subheading' => $applicant->full_name.' — '.$applicant->code])

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-1">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Data dari Pelamar</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="text-xs text-slate-500">Nama</dt><dd class="font-medium">{{ $applicant->full_name }}</dd></div>
                <div><dt class="text-xs text-slate-500">NIK</dt><dd>{{ $applicant->nik ?: '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Telepon</dt><dd>{{ $applicant->phone }}</dd></div>
                <div><dt class="text-xs text-slate-500">Email</dt><dd>{{ $applicant->email ?: '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Alamat</dt><dd>{{ $applicant->address ?: '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Pendidikan</dt><dd>{{ $applicant->education?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Lowongan</dt><dd>{{ $applicant->vacancy?->title }}</dd></div>
            </dl>
            <p class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">Data identitas di atas otomatis dipakai pada data pegawai. Tidak perlu input ulang.</p>
        </div>

        <form method="POST" action="{{ route('applicants.convert', $applicant) }}" class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">NIP</label>
                    <input name="nip" value="{{ old('nip') }}" placeholder="Otomatis bila kosong" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nama Panggilan</label>
                    <input name="nick_name" value="{{ old('nick_name') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Agama</label>
                    <input name="religion" value="{{ old('religion', 'Islam') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Status Pernikahan <span class="text-rose-600">*</span></label>
                    <select name="marital_status" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach(['lajang' => 'Lajang', 'menikah' => 'Menikah', 'cerai' => 'Cerai', 'cerai_mati' => 'Cerai mati'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('marital_status', 'lajang') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jumlah Tanggungan</label>
                    <input type="number" min="0" max="20" name="dependents_count" value="{{ old('dependents_count', 0) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Unit Organisasi <span class="text-rose-600">*</span></label>
                    <select name="org_unit_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">— Pilih unit —</option>
                        @foreach($options['orgUnits'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('org_unit_id', $applicant->vacancy?->org_unit_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jabatan <span class="text-rose-600">*</span></label>
                    <select name="position_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">— Pilih jabatan —</option>
                        @foreach($options['positions'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('position_id', $applicant->vacancy?->position_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Lokasi Kerja</label>
                    <select name="work_location_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">—</option>
                        @foreach($options['workLocations'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('work_location_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Atasan Langsung</label>
                    <select name="direct_supervisor_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">—</option>
                        @foreach($options['supervisors'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('direct_supervisor_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Kategori Pegawai <span class="text-rose-600">*</span></label>
                    <select name="employee_category" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach(['tetap' => 'Tetap', 'harian' => 'Harian', 'khidmah' => 'Khidmah'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employee_category', $applicant->vacancy?->employee_category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Status Kepegawaian <span class="text-rose-600">*</span></label>
                    <select name="employment_status" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach(['aktif' => 'Aktif', 'probation' => 'Percobaan', 'cuti_panjang' => 'Cuti panjang', 'nonaktif' => 'Nonaktif'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_status', 'aktif') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tanggal Mulai Kerja <span class="text-rose-600">*</span></label>
                    <input type="date" name="hire_date" value="{{ old('hire_date', now('Asia/Jakarta')->toDateString()) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Akhir Masa Percobaan</label>
                    <input type="date" name="probation_end_date" value="{{ old('probation_end_date') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Diisi manual; durasi percobaan menunggu klarifikasi K-08.</p>
                </div>
            </div>

            <div class="flex gap-3">
                <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Konversi Sekarang</button>
                <a href="{{ route('applicants.show', $applicant) }}" class="text-sm text-slate-600 hover:underline">Batal</a>
            </div>
        </form>
    </div>
@endsection

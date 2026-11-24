@extends('layouts.app', [
    'title' => $applicant->exists ? 'Ubah Pelamar' : 'Tambah Pelamar',
    'heading' => $applicant->exists ? 'Ubah Data Pelamar' : 'Tambah Pelamar',
])

@section('content')
    <form method="POST" action="{{ $applicant->exists ? route('applicants.update', $applicant) : route('applicants.store') }}"
          class="max-w-5xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($applicant->exists) @method('PUT') @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Lowongan <span class="text-rose-600">*</span></label>
                <select name="vacancy_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Pilih lowongan —</option>
                    @foreach($options['vacancies'] as $vacancy)
                        <option value="{{ $vacancy->id }}" @selected((string) old('vacancy_id', $applicant->vacancy_id) === (string) $vacancy->id)>
                            {{ $vacancy->code }} — {{ $vacancy->title }} ({{ ucfirst($vacancy->status) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Kode Pelamar</label>
                <input name="code" value="{{ old('code', $applicant->code) }}" placeholder="Otomatis bila kosong" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Nama Lengkap <span class="text-rose-600">*</span></label>
                <input name="full_name" value="{{ old('full_name', $applicant->full_name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">NIK</label>
                <input name="nik" inputmode="numeric" maxlength="16" value="{{ old('nik', $applicant->nik) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Tersimpan terenkripsi; wajib 16 digit bila diisi.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Telepon <span class="text-rose-600">*</span></label>
                <input name="phone" value="{{ old('phone', $applicant->phone) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tempat Lahir</label>
                <input name="birth_place" value="{{ old('birth_place', $applicant->birth_place) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Lahir</label>
                <input type="date" name="birth_date" value="{{ old('birth_date', $applicant->birth_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jenis Kelamin</label>
                <select name="gender" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">—</option>
                    <option value="laki-laki" @selected(old('gender', $applicant->gender) === 'laki-laki')>Laki-laki</option>
                    <option value="perempuan" @selected(old('gender', $applicant->gender) === 'perempuan')>Perempuan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email', $applicant->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Pendidikan</label>
                <select name="education_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">—</option>
                    @foreach($options['educations'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('education_id', $applicant->education_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Pengalaman (tahun)</label>
                <input type="number" min="0" max="60" name="experience_years" value="{{ old('experience_years', $applicant->experience_years) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Ekspektasi Gaji (Rp)</label>
                <input type="number" min="0" step="1000" name="expected_salary" value="{{ old('expected_salary', $applicant->expected_salary) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Sumber Lamaran</label>
                <input name="source" value="{{ old('source', $applicant->source) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Alamat</label>
                <input name="address" value="{{ old('address', $applicant->address) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Catatan</label>
                <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('notes', $applicant->notes) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
            <a href="{{ $applicant->exists ? route('applicants.show', $applicant) : route('applicants.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

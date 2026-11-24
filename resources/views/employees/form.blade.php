@extends('layouts.app', [
    'title' => $employee->exists ? 'Ubah Pegawai' : 'Tambah Pegawai',
    'heading' => $employee->exists ? 'Ubah Data Pegawai' : 'Tambah Pegawai',
    'subheading' => $employee->exists ? $employee->full_name : 'NIP dibuat otomatis bila dikosongkan',
])

@section('content')
    @php($editing = $employee->exists)
    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('employees.update', $employee) : route('employees.store') }}"
          class="space-y-6">
        @csrf
        @if($editing) @method('PUT') @endif

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Identitas</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nama Lengkap <span class="text-rose-600">*</span></label>
                    <input name="full_name" value="{{ old('full_name', $employee->full_name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nama Panggilan</label>
                    <input name="nick_name" value="{{ old('nick_name', $employee->nick_name) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">NIP</label>
                    <input name="nip" value="{{ old('nip', $employee->nip) }}" placeholder="Kosongkan untuk dibuat otomatis"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">NIK (16 digit)</label>
                    <input name="nik" value="{{ old('nik', $employee->nik) }}" inputmode="numeric" maxlength="16"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jenis Kelamin <span class="text-rose-600">*</span></label>
                    <select name="gender" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach(['laki-laki' => 'Laki-laki', 'perempuan' => 'Perempuan'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('gender', $employee->gender) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Status Perkawinan <span class="text-rose-600">*</span></label>
                    <select name="marital_status" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach(['lajang' => 'Lajang', 'menikah' => 'Menikah', 'cerai' => 'Cerai', 'cerai_mati' => 'Cerai Mati'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('marital_status', $employee->marital_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tempat Lahir</label>
                    <input name="birth_place" value="{{ old('birth_place', $employee->birth_place) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tanggal Lahir</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Agama</label>
                    <input name="religion" value="{{ old('religion', $employee->religion) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Golongan Darah</label>
                    <input name="blood_type" value="{{ old('blood_type', $employee->blood_type) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Foto</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png" class="mt-1 w-full text-sm">
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Kontak & Alamat</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Telepon</label>
                    <input name="phone" value="{{ old('phone', $employee->phone) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="xl:col-span-3">
                    <label class="block text-sm font-medium text-slate-700">Alamat</label>
                    <input name="address" value="{{ old('address', $employee->address) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Desa/Kelurahan</label>
                    <input name="village" value="{{ old('village', $employee->village) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Kecamatan</label>
                    <input name="district" value="{{ old('district', $employee->district) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Kabupaten/Kota</label>
                    <input name="city" value="{{ old('city', $employee->city) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Provinsi</label>
                    <input name="province" value="{{ old('province', $employee->province) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Kode Pos</label>
                    <input name="postal_code" value="{{ old('postal_code', $employee->postal_code) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Data Kepegawaian</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Kategori <span class="text-rose-600">*</span></label>
                    <select name="employee_category" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach(['tetap' => 'Tetap', 'harian' => 'Harian', 'khidmah' => 'Khidmah'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employee_category', $employee->employee_category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jenis Khidmah</label>
                    <input name="khidmah_type" value="{{ old('khidmah_type', $employee->khidmah_type) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Status Kepegawaian <span class="text-rose-600">*</span></label>
                    <select name="employment_status" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach(['aktif' => 'Aktif', 'probation' => 'Probation', 'cuti_panjang' => 'Cuti Panjang', 'nonaktif' => 'Nonaktif', 'berhenti' => 'Berhenti'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_status', $employee->employment_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Unit Organisasi</label>
                    <select name="org_unit_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">— Pilih —</option>
                        @foreach($options['orgUnits'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('org_unit_id', $employee->org_unit_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jabatan</label>
                    <select name="position_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">— Pilih —</option>
                        @foreach($options['positions'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('position_id', $employee->position_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Atasan Langsung</label>
                    <select name="direct_supervisor_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">— Tidak ada —</option>
                        @foreach($options['supervisors'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('direct_supervisor_id', $employee->direct_supervisor_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Lokasi Kerja</label>
                    <select name="work_location_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">— Pilih —</option>
                        @foreach($options['workLocations'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('work_location_id', $employee->work_location_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tanggal Masuk <span class="text-rose-600">*</span></label>
                    <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Akhir Probation</label>
                    <input type="date" name="probation_end_date" value="{{ old('probation_end_date', $employee->probation_end_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tanggal Berhenti</label>
                    <input type="date" name="termination_date" value="{{ old('termination_date', $employee->termination_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Alasan Berhenti</label>
                    <input name="termination_reason" value="{{ old('termination_reason', $employee->termination_reason) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <label class="flex items-center gap-2 pt-6 text-sm font-medium text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee->is_active ?? true)) class="rounded border-slate-300">
                    Pegawai Aktif
                </label>
            </div>

            @if(! $editing && auth()->user()->hasPermission('employee.payroll_profile.manage'))
                <div class="mt-6 grid gap-4 border-t border-slate-100 pt-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Pendidikan Terakhir</label>
                        <select name="education_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">— Pilih —</option>
                            @foreach($options['educations'] as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('education_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Jumlah Tanggungan (0–3)</label>
                        <input type="number" name="dependents_count" min="0" max="3" value="{{ old('dependents_count', 0) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
            @endif
        </section>

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
            <a href="{{ $editing ? route('employees.show', $employee) : route('employees.index') }}" class="text-sm text-slate-600 hover:underline">Batal</a>
        </div>
    </form>
@endsection

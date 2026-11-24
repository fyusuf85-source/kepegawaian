@extends('layouts.app', [
    'title' => $employee->full_name,
    'heading' => $employee->full_name,
    'subheading' => 'NIP '.$employee->nip,
])

@section('content')
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-center gap-4">
            @if($employee->photo_path)
                <img src="{{ route('employees.photo', $employee) }}" alt="Foto {{ $employee->full_name }}" class="h-20 w-20 rounded-full object-cover ring-2 ring-emerald-100">
            @else
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-2xl font-semibold text-emerald-700">
                    {{ mb_substr($employee->full_name, 0, 1) }}
                </div>
            @endif
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">{{ $employee->employment_status }}</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $employee->employee_category }}</span>
                    @unless($employee->is_active)
                        <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-700">Nonaktif</span>
                    @endunless
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $employee->position?->name ?? '—' }} · {{ $employee->orgUnit?->name ?? '—' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('employees.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">Kembali</a>
            @if($canUpdate)
                <a href="{{ route('employees.edit', $employee) }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Ubah Data</a>
            @endif
            @if(auth()->user()->hasPermission('employee.delete'))
                <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Hapus pegawai ini (soft delete)?')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg border border-rose-300 px-3 py-2 text-sm text-rose-700 hover:bg-rose-50">Hapus</button>
                </form>
            @endif
        </div>
    </div>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Data Pribadi & Kepegawaian</h2>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
            <div><dt class="text-slate-500">NIK</dt><dd class="font-medium">{{ $canSensitive ? ($employee->nik ?: '—') : '••••••••••••••••' }}</dd></div>
            <div><dt class="text-slate-500">Jenis Kelamin</dt><dd class="font-medium capitalize">{{ $employee->gender }}</dd></div>
            <div><dt class="text-slate-500">Tempat, Tanggal Lahir</dt><dd class="font-medium">{{ $employee->birth_place ?: '—' }}, {{ \App\Support\Format::date($employee->birth_date) }}</dd></div>
            <div><dt class="text-slate-500">Status Perkawinan</dt><dd class="font-medium capitalize">{{ $employee->marital_status }}</dd></div>
            <div><dt class="text-slate-500">Telepon</dt><dd class="font-medium">{{ $employee->phone ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $employee->email ?: '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-slate-500">Alamat</dt><dd class="font-medium">{{ $employee->address ?: '—' }}{{ $employee->city ? ', '.$employee->city : '' }}{{ $employee->province ? ', '.$employee->province : '' }}</dd></div>
            <div><dt class="text-slate-500">Unit</dt><dd class="font-medium">{{ $employee->orgUnit?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Jabatan</dt><dd class="font-medium">{{ $employee->position?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Atasan Langsung</dt><dd class="font-medium">{{ $employee->supervisor?->full_name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Lokasi Kerja</dt><dd class="font-medium">{{ $employee->workLocation?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Tanggal Masuk</dt><dd class="font-medium">{{ \App\Support\Format::date($employee->hire_date) }}</dd></div>
            <div><dt class="text-slate-500">Akhir Probation</dt><dd class="font-medium">{{ \App\Support\Format::date($employee->probation_end_date) }}</dd></div>
            <div><dt class="text-slate-500">Jenis Khidmah</dt><dd class="font-medium">{{ $employee->khidmah_type ?: '—' }}</dd></div>
        </dl>
    </section>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Data Keluarga</h2>
        </div>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="py-2">Nama</th><th class="py-2">Hubungan</th><th class="py-2">Tanggal Lahir</th><th class="py-2">Pendidikan</th><th class="py-2">Pekerjaan</th><th class="py-2">Tanggungan</th>@if($canUpdate)<th></th>@endif</tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($employee->families as $family)
                    <tr>
                        <td class="py-2">{{ $family->name }}</td>
                        <td class="py-2 capitalize">{{ $family->relation }}</td>
                        <td class="py-2">{{ \App\Support\Format::date($family->birth_date) }}</td>
                        <td class="py-2">{{ $family->education ?: '—' }}</td>
                        <td class="py-2">{{ $family->occupation ?: '—' }}</td>
                        <td class="py-2">{{ $family->is_dependent ? 'Ya' : 'Tidak' }}</td>
                        @if($canUpdate)
                            <td class="py-2 text-right">
                                <form method="POST" action="{{ route('employees.families.destroy', [$employee, $family]) }}" onsubmit="return confirm('Hapus data keluarga?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-4 text-center text-slate-500">Belum ada data keluarga.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($canUpdate)
            <details class="mt-4 rounded-lg border border-slate-200 p-4">
                <summary class="cursor-pointer text-sm font-medium text-emerald-700">Tambah data keluarga</summary>
                <form method="POST" action="{{ route('employees.families.store', $employee) }}" class="mt-4 grid gap-3 md:grid-cols-3">
                    @csrf
                    <select name="relation" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Hubungan</option>
                        @foreach(['pasangan' => 'Pasangan', 'anak' => 'Anak', 'ayah' => 'Ayah', 'ibu' => 'Ibu', 'saudara' => 'Saudara', 'lainnya' => 'Lainnya'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="name" required placeholder="Nama lengkap" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input name="nik" placeholder="NIK (opsional)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="date" name="birth_date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input name="education" placeholder="Pendidikan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input name="occupation" placeholder="Pekerjaan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="date" name="marriage_date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_dependent" value="0"><input type="checkbox" name="is_dependent" value="1" checked class="rounded"> Tanggungan</label>
                    <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Simpan</button>
                </form>
            </details>
        @endif
    </section>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Riwayat Pendidikan</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="py-2">Jenjang</th><th class="py-2">Institusi</th><th class="py-2">Jurusan</th><th class="py-2">Tahun Lulus</th><th class="py-2">Tertinggi</th>@if($canUpdate)<th></th>@endif</tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($employee->educations as $education)
                    <tr>
                        <td class="py-2">{{ $education->education?->name ?? '—' }}</td>
                        <td class="py-2">{{ $education->institution }}</td>
                        <td class="py-2">{{ $education->major ?: '—' }}</td>
                        <td class="py-2">{{ $education->graduation_year ?: '—' }}</td>
                        <td class="py-2">{{ $education->is_highest ? 'Ya' : 'Tidak' }}</td>
                        @if($canUpdate)
                            <td class="py-2 text-right">
                                <form method="POST" action="{{ route('employees.educations.destroy', [$employee, $education]) }}" onsubmit="return confirm('Hapus riwayat pendidikan?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-center text-slate-500">Belum ada riwayat pendidikan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($canUpdate)
            <details class="mt-4 rounded-lg border border-slate-200 p-4">
                <summary class="cursor-pointer text-sm font-medium text-emerald-700">Tambah riwayat pendidikan</summary>
                <form method="POST" action="{{ route('employees.educations.store', $employee) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-3">
                    @csrf
                    <select name="education_id" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Jenjang</option>
                        @foreach($options['educations'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <input name="institution" required placeholder="Nama institusi" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input name="major" placeholder="Jurusan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="number" name="graduation_year" placeholder="Tahun lulus" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_highest" value="0"><input type="checkbox" name="is_highest" value="1" class="rounded"> Pendidikan tertinggi</label>
                    <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Simpan</button>
                </form>
            </details>
        @endif
    </section>

    @if($canSensitive)
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Rekening Bank <span class="text-xs font-normal text-amber-600">(data rahasia — akses dicatat)</span></h2>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="py-2">Bank</th><th class="py-2">Nomor</th><th class="py-2">Pemilik</th><th class="py-2">Utama</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($employee->bankAccounts as $account)
                        <tr>
                            <td class="py-2">{{ $account->bank_name }}</td>
                            <td class="py-2 font-mono">{{ $account->account_number }}</td>
                            <td class="py-2">{{ $account->account_holder }}</td>
                            <td class="py-2">{{ $account->is_primary ? 'Ya' : 'Tidak' }}</td>
                            <td class="py-2 text-right">
                                @if($canUpdate || $canPayroll)
                                    <form method="POST" action="{{ route('employees.bank-accounts.destroy', [$employee, $account]) }}" onsubmit="return confirm('Hapus rekening?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600 hover:underline">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-slate-500">Belum ada rekening.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($canUpdate || $canPayroll)
                <details class="mt-4 rounded-lg border border-slate-200 p-4">
                    <summary class="cursor-pointer text-sm font-medium text-emerald-700">Tambah rekening</summary>
                    <form method="POST" action="{{ route('employees.bank-accounts.store', $employee) }}" class="mt-4 grid gap-3 md:grid-cols-4">
                        @csrf
                        <input name="bank_name" required placeholder="Nama bank" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <input name="account_number" required placeholder="Nomor rekening" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <input name="account_holder" required placeholder="Nama pemilik" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_primary" value="0"><input type="checkbox" name="is_primary" value="1" class="rounded"> Utama</label>
                        <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Simpan</button>
                    </form>
                </details>
            @endif
        </section>
    @endif

    @if($canViewDocuments)
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Dokumen Pegawai</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="py-2">Jenis</th><th class="py-2">Nomor</th><th class="py-2">Terbit</th><th class="py-2">Kadaluarsa</th><th class="py-2">Verifikasi</th><th class="py-2 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($employee->documents as $document)
                        <tr>
                            <td class="py-2 uppercase">{{ $document->doc_type }}{{ $document->is_confidential ? ' (rahasia)' : '' }}</td>
                            <td class="py-2">{{ $document->number ?: '—' }}</td>
                            <td class="py-2">{{ \App\Support\Format::date($document->issued_date) }}</td>
                            <td class="py-2">{{ \App\Support\Format::date($document->expiry_date) }}</td>
                            <td class="py-2">{{ $document->verified_at ? 'Terverifikasi' : 'Belum' }}</td>
                            <td class="py-2 text-right">
                                <a href="{{ route('employees.documents.download', [$employee, $document]) }}" class="text-emerald-700 hover:underline">Unduh</a>
                                @if($canManageDocuments && $canUpdate && ! $document->verified_at)
                                    <form method="POST" action="{{ route('employees.documents.verify', [$employee, $document]) }}" class="ml-3 inline">
                                        @csrf
                                        <button class="text-sky-700 hover:underline">Verifikasi</button>
                                    </form>
                                @endif
                                @if($canManageDocuments)
                                    <form method="POST" action="{{ route('employees.documents.destroy', [$employee, $document]) }}" class="ml-3 inline" onsubmit="return confirm('Hapus dokumen?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600 hover:underline">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-slate-500">Belum ada dokumen.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($canManageDocuments)
                <details class="mt-4 rounded-lg border border-slate-200 p-4">
                    <summary class="cursor-pointer text-sm font-medium text-emerald-700">Unggah dokumen</summary>
                    <form method="POST" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-3">
                        @csrf
                        <select name="doc_type" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Jenis dokumen</option>
                            @foreach(['ktp' => 'KTP', 'kk' => 'Kartu Keluarga', 'ijazah' => 'Ijazah', 'sertifikat' => 'Sertifikat', 'kontrak' => 'Kontrak', 'sk' => 'SK', 'npwp' => 'NPWP', 'bpjs' => 'BPJS', 'lainnya' => 'Lainnya'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input name="number" placeholder="Nomor dokumen" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <input type="date" name="issued_date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <input type="date" name="expiry_date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_confidential" value="0"><input type="checkbox" name="is_confidential" value="1" class="rounded"> Rahasia</label>
                        <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Unggah</button>
                    </form>
                </details>
            @endif
        </section>
    @endif

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Riwayat Status & Penempatan</h2>
        <div class="mt-3 grid gap-6 lg:grid-cols-2">
            <div>
                <h3 class="text-xs font-semibold uppercase text-slate-400">Status Kepegawaian</h3>
                <table class="mt-2 min-w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                    @forelse($employee->statusHistories as $history)
                        <tr>
                            <td class="py-2 capitalize">{{ $history->status }}</td>
                            <td class="py-2">{{ \App\Support\Format::date($history->effective_date) }}</td>
                            <td class="py-2 text-slate-500">{{ $history->reason }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-3 text-slate-500">Belum ada riwayat.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                @if($canUpdate)
                    <form method="POST" action="{{ route('employees.status-histories.store', $employee) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                        @csrf
                        <select name="status" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Status</option>
                            @foreach(['aktif' => 'Aktif', 'probation' => 'Probation', 'cuti_panjang' => 'Cuti Panjang', 'nonaktif' => 'Nonaktif', 'berhenti' => 'Berhenti'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="effective_date" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <input name="reason" required placeholder="Alasan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm sm:col-span-2">
                        <button class="rounded-lg border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700">Catat status</button>
                    </form>
                @endif
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase text-slate-400">Penempatan</h3>
                <table class="mt-2 min-w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                    @forelse($employee->assignments as $assignment)
                        <tr>
                            <td class="py-2">{{ $assignment->orgUnit?->name ?? '—' }}</td>
                            <td class="py-2">{{ $assignment->position?->name ?? '—' }}</td>
                            <td class="py-2 text-slate-500">{{ \App\Support\Format::date($assignment->effective_from) }} – {{ $assignment->effective_to ? \App\Support\Format::date($assignment->effective_to) : 'sekarang' }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-3 text-slate-500">Belum ada riwayat penempatan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                @if($canUpdate)
                    <details class="mt-3 rounded-lg border border-slate-200 p-3">
                        <summary class="cursor-pointer text-sm font-medium text-emerald-700">Tambah penempatan</summary>
                        <form method="POST" action="{{ route('employees.assignments.store', $employee) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                            @csrf
                            <select name="org_unit_id" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Unit</option>
                                @foreach($options['orgUnits'] as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <select name="position_id" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Jabatan</option>
                                @foreach($options['positions'] as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <select name="work_location_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Lokasi kerja</option>
                                @foreach($options['workLocations'] as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <input name="decision_number" placeholder="Nomor SK (opsional)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <input type="date" name="effective_from" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <input type="date" name="effective_to" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <input name="reason" required placeholder="Alasan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm sm:col-span-2">
                            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Simpan</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    </section>

    @if($canPayroll)
        @php($profile = $employee->payrollProfile)
        <section class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-amber-700">Profil Penggajian <span class="text-xs font-normal">(data rahasia — akses dicatat)</span></h2>
            <form method="POST" action="{{ route('employees.payroll-profile.update', $employee) }}" class="mt-4 grid gap-3 md:grid-cols-3">
                @csrf @method('PUT')
                <div>
                    <label class="block text-xs font-medium text-slate-600">Pendidikan</label>
                    <select name="education_id" class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                        <option value="">— Pilih —</option>
                        @foreach($options['educations'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) $profile?->education_id === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">Status Pajak</label>
                    <select name="tax_status" class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                        <option value="">— Pilih —</option>
                        @foreach(['TK/0', 'TK/1', 'TK/2', 'TK/3', 'K/0', 'K/1', 'K/2', 'K/3'] as $status)
                            <option value="{{ $status }}" @selected($profile?->tax_status === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">Jumlah Tanggungan</label>
                    <input type="number" name="dependents_count" min="0" max="3" value="{{ old('dependents_count', $profile?->dependents_count ?? 0) }}" required class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">Jarak Transport (km)</label>
                    <input type="number" step="0.01" name="transport_distance_km" value="{{ old('transport_distance_km', $profile?->transport_distance_km) }}" class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">Kelas Tunjangan Jabatan</label>
                    <input name="position_allowance_class" value="{{ old('position_allowance_class', $profile?->position_allowance_class) }}" class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">No. BPJS Kesehatan</label>
                    <input name="bpjs_kesehatan_no" value="{{ old('bpjs_kesehatan_no', $profile?->bpjs_kesehatan_no) }}" class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">No. BPJS Ketenagakerjaan</label>
                    <input name="bpjs_tk_no" value="{{ old('bpjs_tk_no', $profile?->bpjs_tk_no) }}" class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">NPWP</label>
                    <input name="npwp" value="{{ old('npwp', $profile?->npwp) }}" class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">Berlaku Sejak</label>
                    <input type="date" name="effective_from" value="{{ old('effective_from', $profile?->effective_from?->format('Y-m-d') ?? $employee->hire_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                </div>
                @if(auth()->user()->hasPermission('payroll.component.manage'))
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Nilai Dasar Gaji Khusus</label>
                        <input type="number" step="0.01" name="salary_basis_override" value="{{ old('salary_basis_override', $profile?->salary_basis_override) }}" class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600">Alasan Nilai Khusus</label>
                        <input name="override_reason" value="{{ old('override_reason', $profile?->override_reason) }}" class="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2 text-sm">
                    </div>
                @endif
                <div class="flex items-end">
                    <button class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Simpan Profil Gaji</button>
                </div>
            </form>
        </section>
    @endif
@endsection

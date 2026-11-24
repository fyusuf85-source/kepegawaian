@extends('layouts.app', ['title' => $applicant->full_name, 'heading' => $applicant->full_name, 'subheading' => 'Pelamar '.$applicant->code])

@section('content')
    @php
        $statusLabels = [
            'baru' => 'Baru', 'seleksi_administrasi' => 'Seleksi Administrasi', 'wawancara' => 'Wawancara',
            'dipertimbangkan' => 'Dipertimbangkan', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak',
        ];
        $nextStatuses = [
            'baru' => ['seleksi_administrasi' => 'Lolos seleksi administrasi', 'ditolak' => 'Tolak'],
            'seleksi_administrasi' => ['wawancara' => 'Lanjut wawancara', 'ditolak' => 'Tolak'],
            'wawancara' => ['dipertimbangkan' => 'Dipertimbangkan', 'ditolak' => 'Tolak'],
            'dipertimbangkan' => ['ditolak' => 'Tolak'],
        ];
        $scoreRows = $applicant->scores->values();
        $rowCount = max(3, $scoreRows->count());
    @endphp

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $statusLabels[$applicant->status] ?? $applicant->status }}</span>
        <span class="text-sm text-slate-500">{{ $applicant->vacancy?->title }} · {{ $applicant->vacancy?->orgUnit?->name }}</span>
        <span class="text-sm text-slate-500">Skor: <strong>{{ $applicant->final_score ?? '—' }}</strong></span>
        @if($canManage && ! $applicant->converted_employee_id)
            <a href="{{ route('applicants.edit', $applicant) }}" class="ml-auto rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Ubah</a>
            <form method="POST" action="{{ route('applicants.destroy', $applicant) }}" onsubmit="return confirm('Hapus data pelamar ini?')">
                @csrf @method('DELETE')
                <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-sm text-rose-600 hover:bg-rose-50">Hapus</button>
            </form>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Identitas Pelamar</h2>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">NIK</dt><dd>{{ $applicant->nik ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Telepon</dt><dd>{{ $applicant->phone }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Email</dt><dd>{{ $applicant->email ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Pendidikan</dt><dd>{{ $applicant->education?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Tempat/Tanggal Lahir</dt><dd>{{ $applicant->birth_place ?: '—' }}{{ $applicant->birth_date ? ', '.\App\Support\Format::date($applicant->birth_date) : '' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Jenis Kelamin</dt><dd>{{ $applicant->gender ? ucfirst($applicant->gender) : '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Pengalaman</dt><dd>{{ $applicant->experience_years !== null ? $applicant->experience_years.' tahun' : '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Ekspektasi Gaji</dt><dd>{{ $applicant->expected_salary ? \App\Support\Format::money($applicant->expected_salary) : '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Alamat</dt><dd>{{ $applicant->address ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Sumber</dt><dd>{{ $applicant->source ?: '—' }}</dd></div>
                </dl>
                @if($applicant->notes)
                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Catatan</p>
                        <p class="mt-1 text-sm whitespace-pre-line">{{ $applicant->notes }}</p>
                    </div>
                @endif
            </div>

            @if($canManage && isset($nextStatuses[$applicant->status]))
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-700">Tahapan Seleksi</h2>
                    <form method="POST" action="{{ route('applicants.status', $applicant) }}" class="mt-3 grid gap-3 sm:grid-cols-3">
                        @csrf
                        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach($nextStatuses[$applicant->status] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input name="note" placeholder="Catatan (wajib untuk tolak)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm sm:col-span-2">
                        <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white sm:col-span-3 sm:w-max">Perbarui Status</button>
                    </form>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700">Dokumen Pelamar</h2>
                    <p class="text-xs text-slate-500">Berkas disimpan privat dan hanya dapat diunduh pengguna berwenang.</p>
                </div>
                <div class="space-y-3 p-5">
                    @forelse($applicant->documents as $document)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <div>
                                <span class="font-medium">{{ strtoupper($document->doc_type) }}</span>
                                <span class="ml-2 text-xs text-slate-500">{{ \App\Support\Format::dateTime($document->uploaded_at) }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('applicants.documents.download', [$applicant, $document]) }}" class="text-emerald-700 hover:underline">Unduh</a>
                                @if($canManage)
                                    <form method="POST" action="{{ route('applicants.documents.destroy', [$applicant, $document]) }}" onsubmit="return confirm('Hapus dokumen ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600 hover:underline">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada dokumen.</p>
                    @endforelse

                    @if($canManage)
                        <form method="POST" action="{{ route('applicants.documents.store', $applicant) }}" enctype="multipart/form-data" class="grid gap-3 border-t border-slate-100 pt-3 sm:grid-cols-3">
                            @csrf
                            <select name="doc_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @foreach(['ktp' => 'KTP', 'kk' => 'Kartu Keluarga', 'ijazah' => 'Ijazah', 'transkrip' => 'Transkrip', 'cv' => 'CV', 'sertifikat' => 'Sertifikat', 'foto' => 'Foto', 'lainnya' => 'Lainnya'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                            <button class="rounded-lg border border-emerald-700 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Unggah</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700">Wawancara</h2>
                </div>
                <div class="space-y-4 p-5">
                    @forelse($applicant->interviews as $interview)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $interview->stage)) }}</span>
                                <span class="text-slate-500">{{ \App\Support\Format::dateTime($interview->scheduled_at) }}</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $interview->status }}</span>
                                @if($interview->result)<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">{{ $interview->result }}</span>@endif
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Pewawancara: {{ $interview->interviewer?->full_name ?? '—' }} · {{ $interview->mode }} · {{ $interview->location ?: '—' }}</p>
                            @if($canAssess)
                                <form method="POST" action="{{ route('applicants.interviews.update', [$applicant, $interview]) }}" class="mt-2 grid gap-2 sm:grid-cols-5">
                                    @csrf @method('PUT')
                                    <input type="number" min="0" max="100" step="0.01" name="score" value="{{ old('score', $interview->score) }}" placeholder="Nilai" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                    <select name="result" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                        <option value="">Hasil</option>
                                        @foreach(['lulus' => 'Lulus', 'tidak_lulus' => 'Tidak lulus', 'ditunda' => 'Ditunda'] as $value => $label)
                                            <option value="{{ $value }}" @selected($interview->result === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="status" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                        @foreach(['dijadwalkan' => 'Dijadwalkan', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $value => $label)
                                            <option value="{{ $value }}" @selected($interview->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input name="notes" value="{{ $interview->notes }}" placeholder="Catatan" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm sm:col-span-2">
                                    <button class="rounded-lg border border-emerald-700 px-3 py-1.5 text-sm text-emerald-700 sm:col-span-5 sm:w-max">Simpan Hasil</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada jadwal wawancara.</p>
                    @endforelse

                    @if($canAssess)
                        <form method="POST" action="{{ route('applicants.interviews.store', $applicant) }}" class="grid gap-2 border-t border-slate-100 pt-3 sm:grid-cols-3">
                            @csrf
                            <select name="stage" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                @foreach(['hrd' => 'HRD', 'kepala_bagian' => 'Kepala Bagian', 'pimpinan' => 'Pimpinan'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="datetime-local" name="scheduled_at" required class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                            <select name="interviewer_id" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                <option value="">Pewawancara</option>
                                @foreach($interviewers as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <select name="mode" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                <option value="tatap_muka">Tatap muka</option>
                                <option value="online">Online</option>
                            </select>
                            <input name="location" placeholder="Lokasi / tautan" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                            <button class="rounded-lg border border-emerald-700 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Jadwalkan</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700">Penilaian Berbobot</h2>
                    <p class="text-xs text-slate-500">Total bobot seluruh kriteria wajib 100.</p>
                </div>
                <form method="POST" action="{{ route('applicants.scores.store', $applicant) }}" class="space-y-3 p-5">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase text-slate-500">
                                <tr><th class="py-2">Kriteria</th><th class="py-2">Bobot (%)</th><th class="py-2">Nilai (0–100)</th><th class="py-2">Catatan</th></tr>
                            </thead>
                            <tbody>
                            @for($i = 0; $i < $rowCount; $i++)
                                @php($row = $scoreRows->get($i))
                                <tr>
                                    <td class="py-1 pr-2"><input name="scores[{{ $i }}][criterion]" value="{{ old("scores.$i.criterion", $row?->criterion) }}" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm" @disabled(! $canAssess)></td>
                                    <td class="py-1 pr-2"><input type="number" min="0" max="100" step="0.001" name="scores[{{ $i }}][weight]" value="{{ old("scores.$i.weight", $row?->weight) }}" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm" @disabled(! $canAssess)></td>
                                    <td class="py-1 pr-2"><input type="number" min="0" max="100" step="0.01" name="scores[{{ $i }}][score]" value="{{ old("scores.$i.score", $row?->score) }}" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm" @disabled(! $canAssess)></td>
                                    <td class="py-1"><input name="scores[{{ $i }}][notes]" value="{{ old("scores.$i.notes", $row?->notes) }}" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm" @disabled(! $canAssess)></td>
                                </tr>
                            @endfor
                            </tbody>
                        </table>
                    </div>
                    @if($canAssess)
                        <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Penilaian</button>
                    @endif
                </form>
            </div>
        </div>

        <div class="space-y-4">
            @if($canDecide && ! in_array($applicant->status, ['diterima', 'ditolak'], true))
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-700">Keputusan Penerimaan</h2>
                    <form method="POST" action="{{ route('applicants.decide', $applicant) }}" class="mt-3 space-y-2">
                        @csrf
                        <select name="decision" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="diterima">Diterima</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                        <textarea name="reason" rows="3" required placeholder="Alasan keputusan (wajib)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        <button class="w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Keputusan</button>
                        <p class="text-xs text-slate-500">Penerimaan hanya dapat diputuskan pada tahap dipertimbangkan.</p>
                    </form>
                </div>
            @endif

            @if($applicant->convertedEmployee)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-emerald-800">Sudah Dikonversi</h2>
                    <p class="mt-1 text-sm">Pegawai: <a class="font-semibold underline" href="{{ route('employees.show', $applicant->convertedEmployee) }}">{{ $applicant->convertedEmployee->full_name }} ({{ $applicant->convertedEmployee->nip }})</a></p>
                </div>
            @elseif($canConvert && $applicant->canBeConverted())
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-emerald-800">Konversi ke Pegawai</h2>
                    <p class="mt-1 text-sm text-emerald-900">Identitas pelamar dipakai ulang; lengkapi data penempatan dan status kepegawaian.</p>
                    <a href="{{ route('applicants.convert.form', $applicant) }}" class="mt-3 inline-block rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Proses Konversi</a>
                </div>
            @endif

            @if($applicant->scores->isNotEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm text-sm">
                    <h2 class="text-sm font-semibold text-slate-700">Ringkasan Nilai</h2>
                    <ul class="mt-2 space-y-1">
                        @foreach($applicant->scores as $score)
                            <li class="flex justify-between gap-2"><span class="truncate">{{ $score->criterion }} ({{ (float) $score->weight }}%)</span><span>{{ (float) $score->score }}</span></li>
                        @endforeach
                    </ul>
                    <p class="mt-2 border-t border-slate-100 pt-2 font-semibold">Skor akhir: {{ $applicant->final_score }}</p>
                </div>
            @endif

            <a href="{{ route('applicants.index') }}" class="block text-sm text-slate-600 hover:underline">← Kembali ke daftar pelamar</a>
        </div>
    </div>
@endsection

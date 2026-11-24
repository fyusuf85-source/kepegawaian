@extends('layouts.app', ['title' => 'Presensi', 'heading' => 'Presensi Mandiri', 'subheading' => 'GPS radius + foto selfie · zona waktu Asia/Jakarta'])

@section('content')
    @if(! $employee)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            Akun Anda belum terhubung dengan data pegawai. Presensi hanya dapat dilakukan oleh akun pegawai.
        </div>
    @else
        @php($today = now('Asia/Jakarta'))
        @php($checkedIn = $attendance?->check_in_at)
        @php($checkedOut = $attendance?->check_out_at)

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Pegawai</p>
                        <p class="text-lg font-semibold text-slate-900">{{ $employee->full_name }}</p>
                        <p class="text-xs text-slate-500">{{ $employee->nip }} · {{ $employee->position?->name }} · {{ $employee->orgUnit?->name }}</p>
                    </div>
                    <div class="text-right text-xs text-slate-500">
                        <p class="font-medium text-slate-700">{{ $today->translatedFormat('l, d F Y') }}</p>
                        <p>{{ $today->format('H:i') }} WIB</p>
                    </div>
                </div>

                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">Lokasi kerja</dt>
                        <dd class="mt-0.5 font-medium">{{ $location?->name ?? 'Belum diatur' }}</dd>
                        <dd class="text-xs text-slate-500">
                            @if($location)
                                Radius {{ $location->radius_m }} m · akurasi maks. {{ $location->accuracy_threshold_m }} m
                            @else
                                Hubungi HRD untuk menetapkan lokasi.
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">Jadwal hari ini</dt>
                        <dd class="mt-0.5 font-medium">
                            @if($resolved['is_workday'] && $resolved['shift'])
                                {{ $resolved['shift']->name }} · {{ \App\Support\Format::time($resolved['shift']->start_time) }}–{{ \App\Support\Format::time($resolved['shift']->end_time) }}
                            @else
                                Bukan hari kerja
                            @endif
                        </dd>
                        <dd class="text-xs text-slate-500">{{ $resolved['schedule']?->name ?? 'Jadwal belum ditetapkan' }}</dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">Presensi masuk</dt>
                        <dd class="mt-0.5 font-medium">{{ $attendance?->checkInLocal()?->format('H:i') ?? '—' }}</dd>
                        <dd class="text-xs text-slate-500">
                            @if($attendance?->check_in_distance_m !== null)
                                {{ round((float) $attendance->check_in_distance_m) }} m dari lokasi
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">Presensi pulang</dt>
                        <dd class="mt-0.5 font-medium">{{ $attendance?->checkOutLocal()?->format('H:i') ?? '—' }}</dd>
                        <dd class="text-xs text-slate-500">
                            @if($attendance)
                                {{ $attendance->late_minutes > 0 ? 'Terlambat '.$attendance->late_minutes.' menit' : 'Tidak terlambat' }}
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $checkedOut ? 'bg-sky-100 text-sky-800' : ($checkedIn ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600') }}">
                        {{ $checkedOut ? 'Presensi hari ini selesai' : ($checkedIn ? 'Sudah presensi masuk' : 'Belum presensi') }}
                    </span>
                    @if($attendance)
                        <a href="{{ route('attendance.show', $attendance) }}" class="text-emerald-700 hover:underline">Lihat detail</a>
                    @endif
                    <a href="{{ route('attendance.history') }}" class="text-emerald-700 hover:underline">Riwayat saya</a>
                    
                    <button id="sync-btn" class="hidden text-rose-600 font-semibold hover:underline" onclick="window.PisAttendanceOffline.sync()">
                        Sinkronisasi Presensi Offline
                    </button>
                </div>
                <script>
                    window.addEventListener('load', async () => {
                        const count = (await window.PisAttendanceOffline.pending()).length;
                        if (count > 0) {
                            const btn = document.getElementById('sync-btn');
                            btn.textContent = `Sinkronisasi ${count} Presensi Offline`;
                            btn.classList.remove('hidden');
                        }
                    });
                </script>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-800">Lokasi perangkat</p>
                <p id="geo-status" class="mt-1 text-xs text-slate-500">Mengambil lokasi GPS…</p>
                <dl class="mt-3 space-y-1 text-xs text-slate-600">
                    <div class="flex justify-between"><dt>Latitude</dt><dd id="geo-lat">—</dd></div>
                    <div class="flex justify-between"><dt>Longitude</dt><dd id="geo-lng">—</dd></div>
                    <div class="flex justify-between"><dt>Akurasi</dt><dd id="geo-acc">—</dd></div>
                </dl>
                <button type="button" id="geo-refresh" class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs hover:bg-slate-50">Perbarui lokasi</button>
                <p class="mt-3 text-xs text-slate-500">
                    GPS/kamera bermasalah?
                    <a href="{{ route('exceptions.create') }}" class="text-emerald-700 hover:underline">Ajukan dispensasi</a>.
                </p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @if(! $checkedIn)
                <form method="POST" action="{{ route('attendance.check-in') }}" enctype="multipart/form-data"
                      class="punch-form rounded-xl border border-slate-200 bg-white p-5 shadow-sm" data-form="masuk">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $checkInKey }}">
                    <input type="hidden" name="lat" data-lat>
                    <input type="hidden" name="lng" data-lng>
                    <input type="hidden" name="accuracy_m" data-accuracy>

                    <h2 class="text-base font-semibold text-slate-900">Presensi Masuk</h2>
                    <p class="mt-1 text-xs text-slate-500">Berdiri di area {{ $location?->name ?? 'lokasi kerja' }}, ambil selfie, lalu kirim.</p>

                    <label class="mt-4 block text-sm font-medium text-slate-700">Foto selfie <span class="text-rose-600">*</span></label>
                    <input type="file" name="photo" accept="image/*" capture="user" required
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Maksimum {{ $maxPhotoKb }} KB. Foto disimpan privat.</p>

                    <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                        Jarak dan akurasi dihitung di server. Di luar radius tanpa dispensasi akan ditolak.
                    </div>

                    <button class="mt-4 w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">
                        Kirim Presensi Masuk
                    </button>
                </form>
            @elseif(! $checkedOut)
                <form method="POST" action="{{ route('attendance.check-out') }}" enctype="multipart/form-data"
                      class="punch-form rounded-xl border border-slate-200 bg-white p-5 shadow-sm" data-form="pulang">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $checkOutKey }}">
                    <input type="hidden" name="lat" data-lat>
                    <input type="hidden" name="lng" data-lng>
                    <input type="hidden" name="accuracy_m" data-accuracy>

                    <h2 class="text-base font-semibold text-slate-900">Presensi Pulang</h2>
                    <p class="mt-1 text-xs text-slate-500">Masuk pukul {{ $attendance->checkInLocal()?->format('H:i') }}. Ambil selfie untuk mencatat pulang.</p>

                    <label class="mt-4 block text-sm font-medium text-slate-700">Foto selfie <span class="text-rose-600">*</span></label>
                    <input type="file" name="photo" accept="image/*" capture="user" required
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Maksimum {{ $maxPhotoKb }} KB. Foto disimpan privat.</p>

                    <button class="mt-4 w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">
                        Kirim Presensi Pulang
                    </button>
                </form>
            @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
                    <p class="font-semibold">Presensi hari ini sudah lengkap.</p>
                    <p class="mt-1">Masuk {{ $attendance->checkInLocal()?->format('H:i') }} · Pulang {{ $attendance->checkOutLocal()?->format('H:i') }}
                        · Kerja {{ intdiv($attendance->work_minutes, 60) }} jam {{ $attendance->work_minutes % 60 }} menit.</p>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">7 hari terakhir</h2>
                    <a href="{{ route('attendance.history') }}" class="text-xs text-emerald-700 hover:underline">Semua</a>
                </div>
                <ul class="mt-3 divide-y divide-slate-100 text-sm">
                    @forelse($recent as $row)
                        <li class="flex items-center justify-between gap-2 py-2">
                            <div>
                                <p class="font-medium">{{ \App\Support\Format::date($row->work_date) }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $row->checkInLocal()?->format('H:i') ?? '—' }}–{{ $row->checkOutLocal()?->format('H:i') ?? '—' }}
                                    · {{ $row->shift?->name }}
                                </p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ \App\Models\Attendance::statusLabels()[$row->status] ?? $row->status }}</span>
                        </li>
                    @empty
                        <li class="py-3 text-center text-xs text-slate-500">Belum ada riwayat.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <script>
            (function () {
                const status = document.getElementById('geo-status');
                const latEl = document.getElementById('geo-lat');
                const lngEl = document.getElementById('geo-lng');
                const accEl = document.getElementById('geo-acc');
                const refresh = document.getElementById('geo-refresh');
                let current = null;

                function setInputs() {
                    document.querySelectorAll('.punch-form').forEach(function (form) {
                        form.querySelectorAll('[data-lat]').forEach(function (input) {
                            input.value = current ? current.lat : '';
                        });
                        form.querySelectorAll('[data-lng]').forEach(function (input) {
                            input.value = current ? current.lng : '';
                        });
                        form.querySelectorAll('[data-accuracy]').forEach(function (input) {
                            input.value = current ? current.accuracy : '';
                        });
                    });
                }

                function locate() {
                    status.textContent = 'Mengambil lokasi GPS…';
                    status.className = 'mt-1 text-xs text-slate-500';

                    if (!navigator.geolocation) {
                        status.textContent = 'Perangkat tidak mendukung GPS. Gunakan dispensasi.';
                        status.className = 'mt-1 text-xs text-rose-600';
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(function (position) {
                        current = {
                            lat: position.coords.latitude.toFixed(7),
                            lng: position.coords.longitude.toFixed(7),
                            accuracy: position.coords.accuracy.toFixed(2),
                        };
                        latEl.textContent = current.lat;
                        lngEl.textContent = current.lng;
                        accEl.textContent = current.accuracy + ' m';
                        status.textContent = 'Lokasi terbaca. Pastikan akurasi memadai.';
                        status.className = 'mt-1 text-xs text-emerald-700';
                        setInputs();
                    }, function (error) {
                        current = null;
                        setInputs();
                        status.textContent = 'GPS gagal: ' + error.message + '. Gunakan dispensasi bila perlu.';
                        status.className = 'mt-1 text-xs text-rose-600';
                    }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
                }

                refresh.addEventListener('click', locate);
                locate();

                document.querySelectorAll('.punch-form').forEach(function (form) {
                    form.addEventListener('submit', async function (event) {
                        if (!current) {
                            event.preventDefault();
                            status.textContent = 'Lokasi GPS belum terbaca. Tunggu atau perbarui lokasi.';
                            status.className = 'mt-1 text-xs text-rose-600';
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                            return;
                        }
                        if (!navigator.onLine && window.PisAttendanceOffline) {
                            event.preventDefault();
                            try {
                                await window.PisAttendanceOffline.save(form);
                            } catch (error) {
                                alert('Gagal menyimpan antrean: ' + error.message);
                            }
                        }
                    });
                });
            })();
        </script>
    @endif
@endsection

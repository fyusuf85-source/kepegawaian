# TECHNICAL ARCHITECTURE — Aplikasi Kepegawaian PIS

| Atribut | Nilai |
|---|---|
| Versi | 1.0 |
| Tanggal | 18-11-2026 |
| Status | Disetujui untuk Tahap 2 |
| Acuan | `MASTER_SPECIFICATION.md`, `SRS.md`, `DATABASE_DESIGN.md` |

---

## 1. Ringkasan keputusan teknologi

| Lapisan | Pilihan | Versi | Alasan |
|---|---|---|---|
| Bahasa | PHP | 8.3.36 | Didukung Laravel 13 (8.3–8.5); versi yang terpasang & diuji |
| Framework | Laravel | 13.43.0 | Rekomendasi dokumen induk; ekosistem lengkap |
| Database | MySQL | 8.4.9 | Rekomendasi dokumen induk; mendukung CTE rekursif & JSON |
| Frontend | Blade + Tailwind + Alpine.js | Tailwind 4.x | Controller + Blade dipakai; Livewire 4 terpasang namun belum dipakai |
| CSS | Tailwind CSS + Vite | Tailwind 4.x | Konsisten dengan design system, build ringan |
| Grafik | Chart.js | 4.x | Dashboard |
| PDF | barryvdh/laravel-dompdf | 3.x | Slip gaji, SK, laporan |
| Excel | maatwebsite/excel | 3.1.x | Impor/ekspor |
| RBAC | Implementasi khusus (`roles`, `permissions`, pivot) | — | Mengikuti `DATABASE_DESIGN.md`, termasuk `role_user.org_unit_id` untuk cakupan unit |
| Gambar | intervention/image | 3.x | Kompresi/validasi foto |
| Antrian | Database driver → Redis (produksi lanjutan) | — | Sederhana di awal, dapat ditingkatkan |
| Cache/Sesi | Database | — | Tanpa dependensi tambahan di awal |
| Web server | Nginx + PHP-FPM (produksi) | Nginx 1.24+ | Standar, performa baik |
| OS produksi | Ubuntu Server LTS | 24.04 LTS | Didukung panjang |

> Versi **dipin** saat instalasi Tahap 2 dan dicatat di `composer.lock`/`package-lock.json`. Jika versi berbeda tersedia, gunakan versi stabil terdekat dan perbarui dokumen ini — jangan menebak.

### 1.1 Hasil pemeriksaan lingkungan (18-11-2026)

| Komponen | Status | Catatan |
|---|---|---|
| Folder proyek | Ada | Laravel 13.43.0 terpasang; dokumen `docs/` dipertahankan |
| PHP | **8.3.36** | `PHP.PHP.8.3`; ekstensi `openssl`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `intl`, `zip`, `bcmath`, `curl` aktif |
| Composer | **2.11.2** | Installer resmi; CA lokal diset karena Avast Web/Mail Shield |
| MySQL | **8.4.9** | Port 3306; server dijalankan manual, datadir `C:\Users\USER\AppData\Local\mysql-data` |
| Node.js | 26.13.0 | Vite 7.3.8 |
| npm | 11.20.0 | `npm run build` berhasil |
| Git | **2.56.0** | Terpasang; repositori lokal belum diinisialisasi |
| winget | Tersedia | Sumber paket terverifikasi |
| Web server lokal | Tidak ada (XAMPP/Laragon tidak ditemukan) | Pembangunan memakai `php artisan serve` + MySQL lokal |

**Konsekuensi:** Tahap 2 diawali dengan instalasi toolchain. Tanpa PHP/MySQL, aplikasi tidak dapat dijalankan atau diuji — instalasi wajib dilakukan sebelum migration.

---

## 2. Arsitektur aplikasi

### 2.1 Gaya arsitektur

Monolit modular (modular monolith) dengan pemisahan lapisan:

```
Request → Route → Middleware (auth, can, throttle)
        → Controller / Livewire Component (validasi input)
        → Service (aturan bisnis: AttendanceService, PayrollService, WorkflowService)
        → Repository/Query (Eloquent + scope)
        → MySQL
```

Prinsip:
- **Controller tipis** — tidak berisi rumus bisnis.
- **Service layer** untuk domain kompleks (absensi, payroll, KPI, workflow).
- **Policy** untuk otorisasi; **Form Request** untuk validasi.
- **Event/Listener** untuk efek samping (notifikasi, audit), dijalankan via queue bila tidak kritis.
- **Snapshot** untuk data yang dipakai perhitungan historis.

### 2.2 Modul (namespace aplikasi)

```
App\Modules\Auth
App\Modules\Organization
App\Modules\Employee
App\Modules\Recruitment
App\Modules\HrAdministration
App\Modules\Attendance
App\Modules\Leave
App\Modules\Payroll
App\Modules\Kpi
App\Modules\Coaching
App\Modules\Reporting
App\Modules\Notification
App\Modules\System
```

Setiap modul berisi: `Http/Controllers`, `Livewire`, `Models`, `Services`, `Policies`, `Requests`, `Resources`, `Database/Migrations` (dikelompokkan, tetap satu database), `Tests`.

### 2.3 Struktur folder (Laravel)

```
app/
  Console/Commands/          # payroll:calculate, contract:remind, attendance:summary
  Enums/                     # EmployeeCategory, EmploymentStatus, AttendanceStatus, ...
  Http/Middleware/           # EnsureUserIsActive, DataScope
  Livewire/                  # komponen UI per modul
  Models/
  Modules/<Modul>/Services/
  Policies/
  Providers/
  Support/                   # Money, Distance, WorkDate, FormulaEvaluator
config/
database/migrations/         # urut sesuai DATABASE_DESIGN.md
database/seeders/
docs/                        # dokumen ini
resources/views/
routes/web.php, routes/console.php
storage/app/private/         # foto, dokumen, PDF (tidak dapat diakses langsung)
tests/Feature, tests/Unit
```

---

## 3. Autentikasi dan sesi

| Aspek | Keputusan |
|---|---|
| Login | Username atau email + password |
| Hashing | bcrypt cost 12 (default Laravel dapat dinaikkan; argon2id opsional) |
| Session | Driver `database`; masa hidup 120 menit; `encrypt` aktif |
| Cookie | `Secure`, `HttpOnly`, `SameSite=Lax` di produksi |
| Regenerasi | Session ID diregenerasi saat login |
| Logout | Invalidasi sesi + regenerasi token CSRF |
| Percobaan login | Rate limit 5/menit per IP+username, kunci 15 menit |
| Akun nonaktif | Middleware menolak akses + sesi diakhiri |
| Reset password | Administrator; token sekali pakai; wajib ganti saat login |
| Sesi tunggal | Opsional (`security.session_single_device`); default nonaktif karena pegawai boleh memakai HP berbeda |
| 2FA | Ditunda ke Tahap 8 untuk administrator (opsional) |
| Audit login | `login_histories` + `audit_logs` |

---

## 4. Otorisasi

- `spatie/laravel-permission` untuk role/permission; permission code mengikuti `ACCESS_CONTROL.md`.
- Scope data (self/unit/all) diimplementasikan sebagai **query scope** di service, bukan hanya di UI.
- Setiap controller/Livewire memanggil `$this->authorize(...)` atau middleware `can:`.
- Uji negatif wajib: pengguna tidak boleh mengakses data di luar scope meskipun menebak URL/ID.
- Pemisahan tugas dijaga di service payroll/workflow (bukan hanya tombol UI).

---

## 5. Penyimpanan file

| Jenis | Lokasi | Akses | Retensi |
|---|---|---|---|
| Foto presensi | `storage/app/private/attendance/{YYYY}/{MM}/{DD}/` | Signed URL 5 menit, pemilik/HRD | 2 tahun (konfigurabel) |
| Dokumen pegawai | `storage/app/private/employees/{id}/` | Policy + signed URL | Sesuai kebijakan |
| Slip gaji | `storage/app/private/payslips/{YYYY-MM}/` | Pemilik + payroll | 10 tahun |
| SK/surat | `storage/app/private/decrees/{YYYY}/` | HRD/Pimpinan | Permanen |
| Ekspor sementara | `storage/app/private/exports/` | Pembuat | 7 hari |

Aturan:
- Nama file: UUID + ekstensi tervalidasi; tidak memakai nama asli.
- Validasi: MIME riil (finfo), ekstensi, ukuran (foto ≤ 2 MB; dokumen ≤ 10 MB), gambar divalidasi ulang dengan GD/Imagick (re-encode menghapus metadata berbahaya).
- Tidak ada file sensitif di `public/`.
- Backup dokumen terpisah dari backup database.

---

## 6. Keamanan

| Area | Kontrol |
|---|---|
| Input | Form Request + validasi tipe/panjang/rentang; sanitasi output Blade (auto-escape) |
| SQL | Eloquent/query builder dengan binding; tanpa string SQL dari input |
| XSS | Escape default; konten HTML kaya (template surat) disanitasi dengan allowlist |
| CSRF | Middleware Laravel pada semua form; token di meta |
| Mass assignment | `$fillable` eksplisit |
| IDOR | Policy + scope; uji akses lintas pengguna |
| Upload | Validasi MIME, ukuran, re-encode gambar, penyimpanan privat |
| Password | Hash kuat; kebijakan minimal 8 karakter + huruf besar/kecil/angka; cek password umum (opsional) |
| Session | Cookie aman, regenerasi, timeout, daftar sesi aktif |
| Rate limit | Login, presensi, ekspor, API internal |
| Header | CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS |
| Enkripsi | Kolom sensitif (NIK, rekening, NPWP); HTTPS wajib |
| Log | Jangan mencatat password/token; audit akses data sensitif |
| Dependency | `composer audit`/`npm audit` pada setiap tahap |
| Backup | Terenkripsi, akses terbatas, uji restore |

**Yang tidak diklaim:** deteksi liveness, anti-spoofing wajah, deteksi mock GPS. Sistem hanya mencatat bukti dan membatasi sesuai kebijakan; klaim tersebut memerlukan teknologi tambahan dan pengujian khusus.

---

## 7. Backup dan pemulihan

| Aspek | Rencana |
|---|---|
| Database | `mysqldump --single-transaction --routines --triggers` harian 02.00 WIB |
| Dokumen | Arsip增量 (rsync/zip) harian |
| Full | Mingguan (database + dokumen) |
| Lokasi | Server + salinan luar server (minimal 1 lokasi berbeda) |
| Enkripsi | GPG/age dengan kunci terpisah dari server aplikasi |
| Retensi | Harian 30 hari, mingguan 12 minggu, bulanan 12 bulan |
| Verifikasi | Cek integritas file + uji restore ke database uji setiap kuartal |
| RPO/RTO | RPO 24 jam; RTO 8 jam (target awal) |
| Dokumentasi | `docs/operations/BACKUP_RESTORE.md` (dibuat Tahap 8) |

---

## 8. Deployment produksi

### 8.1 Topologi

```
Internet → Nginx (443, TLS) → PHP-FPM 8.4 → Laravel
                                   ├── MySQL 8.4 (localhost)
                                   ├── storage/app/private
                                   └── queue worker (systemd)
cron: schedule:run setiap menit
```

### 8.2 Langkah deployment (ringkas)

1. Provision Ubuntu 24.04, buat user non-root dengan sudo.
2. Pasang Nginx, PHP 8.4 + ekstensi (`mbstring`, `xml`, `curl`, `zip`, `gd`, `intl`, `bcmath`, `mysql`, `fileinfo`, `openssl`), MySQL 8.4, Composer, Node (build aset).
3. Buat database + user khusus aplikasi (bukan root), charset `utf8mb4`.
4. Clone/deploy kode ke `/var/www/pis-kepegawaian`; `composer install --no-dev --optimize-autoloader`.
5. Konfigurasi `.env` produksi (rahasia, tanpa commit), `APP_ENV=production`, `APP_DEBUG=false`.
6. `php artisan migrate --force`, seeder terkontrol.
7. `php artisan storage:link` (hanya aset publik non-sensitif), `config:cache`, `route:cache`, `view:cache`.
8. Nginx server block + Certbot (HTTPS), redirect 80 → 443.
9. systemd unit untuk `queue:work`; cron `schedule:run`.
10. UFW (22 terbatas, 80, 443), fail2ban, pembaruan keamanan otomatis.
11. Backup terjadwal + monitoring uptime/log.
12. UAT dan daftar periksa go-live.

### 8.3 Lingkungan

| Lingkungan | Tujuan | Data |
|---|---|---|
| Local | Pengembangan | Data DEMO |
| Staging | UAT | Salinan anonim atau DEMO |
| Production | Operasional | Data nyata |

Kredensial produksi **tidak** disimpan di repositori/dokumen; hanya di secret manager/`.env` server.

---

## 9. Konfigurasi aplikasi (environment)

| Variabel | Contoh | Catatan |
|---|---|---|
| `APP_NAME` | `Aplikasi Kepegawaian PIS` | |
| `APP_ENV` | `local`/`production` | |
| `APP_TIMEZONE` | `Asia/Jakarta` | Penyimpanan tetap UTC |
| `APP_URL` | `https://kepegawaian.pis...` | Wajib HTTPS produksi |
| `DB_CONNECTION` | `mysql` | |
| `DB_DATABASE` | `pis_kepegawaian` | |
| `DB_USERNAME` | `pis_app` | Bukan root |
| `DB_PASSWORD` | *(rahasia)* | |
| `SESSION_DRIVER` | `database` | |
| `QUEUE_CONNECTION` | `database` | |
| `FILESYSTEM_DISK` | `local` | Privat |
| `ATTENDANCE_PHOTO_MAX_KB` | `2048` | |
| `PAYROLL_LOCK_AFTER_APPROVAL` | `true` | |
| `MAIL_*` | opsional | Untuk reset password/notifikasi |

Nilai kebijakan bisnis **tidak** diletakkan di `.env` jika harus bertanggal/berubah; gunakan tabel `settings`/`payroll_component_rates`.

---

## 10. Performa

| Kebutuhan | Rencana |
|---|---|
| Daftar pegawai | Index + pagination server-side; eager loading relasi |
| Rekap absensi | Agregasi SQL, cache 5 menit untuk dashboard |
| Payroll | Chunk 200 pegawai, queue job, transaksi per pegawai; lock periode |
| Laporan | Query terindeks; ekspor via queue untuk data besar |
| Dashboard | Cache ringkasan 1–5 menit; invalidasi saat ada perubahan |
| Gambar | Kompresi klien + server; thumbnail untuk daftar |
| Aset | Vite build, cache-busting, kompresi Brotli/Gzip |

---

## 11. Logging dan monitoring

- Log aplikasi harian (`daily`, retensi 14 hari), level `warning` di produksi.
- Log audit terpisah di database (append-only).
- Monitoring: uptime HTTP, error rate, waktu respons, ruang disk, status backup, antrian gagal.
- Alert ke administrator (email/WhatsApp gateway bila tersedia).
- `failed_jobs` dipantau; retry manual dengan catatan.

---

## 12. Standar pengembangan

| Aspek | Aturan |
|---|---|
| Gaya kode | PSR-12 + Laravel Pint |
| Tipe | `declare(strict_types=1)` pada service/domain baru |
| Penamaan | Bahasa Inggris untuk kode; Bahasa Indonesia untuk teks UI |
| Validasi | Form Request; pesan bahasa Indonesia |
| Database | Migration reversible; tanpa perubahan manual di produksi |
| Testing | Unit test untuk rumus payroll/absensi; feature test untuk alur & otorisasi |
| Commit | Pesan jelas; satu fitur satu commit; tanpa kredensial |
| Dokumentasi | Perbarui `DEVELOPMENT_PROGRESS.md`, `CHANGELOG.md`, `TEST_REPORT.md` setiap tahap |
| Data | Tidak ada data produksi di lingkungan pengembangan; data demo berlabel |

---

## 13. Strategi pengujian

| Jenis | Alat | Cakupan |
|---|---|---|
| Unit | PHPUnit/Pest | Rumus payroll (semua kategori), jarak GPS, keterlambatan, prorata, bobot KPI |
| Feature | Pest + database uji | Login, RBAC, CRUD, alur persetujuan, impor, payroll end-to-end |
| Otorisasi | Feature test negatif | IDOR, unit scope, self scope |
| Browser manual | Checklist | Presensi kamera/GPS di Android & iOS, responsif, PDF |
| Performa | Seeder 1.000 pegawai | Waktu daftar, payroll, laporan |
| Keamanan | Checklist OWASP + `composer audit` | Upload, XSS, CSRF, session, akses langsung |
| Restore | Uji nyata | Backup dipulihkan ke DB uji dan diverifikasi |

Hasil nyata dicatat di `TEST_REPORT.md`; tidak ada klaim lulus tanpa eksekusi.

---

## 14. Instalasi toolchain (Tahap 2, sudah dijalankan)

Hasil 18-11-2026:

| Komponen | Versi terpasang | Sumber |
|---|---|---|
| PHP | 8.3.36 | `winget PHP.PHP.8.3` |
| Composer | 2.11.2 | Installer resmi getcomposer.org |
| MySQL | 8.4.9 | `winget Oracle.MySQL`, dijalankan manual |
| Git | 2.56.0 | `winget Git.Git` |
| Node.js / npm | 26.13.0 / 11.20.0 | Sudah tersedia |

Perintah yang dijalankan (ringkas):

```powershell
winget install --id PHP.PHP.8.3 --exact --accept-package-agreements --accept-source-agreements
winget install --id Oracle.MySQL --exact --accept-package-agreements --accept-source-agreements
winget install --id Git.Git --exact --accept-package-agreements --accept-source-agreements
# Composer: installer resmi, signature diverifikasi
# CA lokal (Avast): $env:COMPOSER_CAFILE = 'D:\APLIKASI KEPEGAWAIAN\.tools\cacert.pem'
```

Langkah lanjutan yang sudah diselesaikan:
1. Verifikasi `php -v`, `composer -V`, `mysql --version`.
2. Ekstensi PHP aktif: `openssl`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `intl`, `zip`, `bcmath`, `curl`.
3. Database `pis_kepegawaian` (dev) dan `pis_kepegawaian_test` (uji) dibuat; user aplikasi `pis_app`.
4. Laravel 13.43.0 dipasang tanpa menghapus `docs/` dan prompt induk.
5. `.env` dikonfigurasi, `APP_KEY` dibuat, `php artisan migrate:fresh --seed` berhasil.
6. `npm install`, `npm run build`, dan `php artisan serve` siap dipakai.

Catatan khusus lingkungan:
- **Avast Web/Mail Shield** memutus TLS saat Composer mengunduh paket. Solusi: set `COMPOSER_CAFILE` ke `cacert.pem` lokal (sudah diset global).
- **MySQL belum menjadi Windows service**; server dijalankan manual dengan datadir `C:\Users\USER\AppData\Local\mysql-data`.
- Composer memakai `--no-interaction`; jika gagal TLS, periksa ulang CA sebelum mengubah konfigurasi lain.

Jika instalasi gagal (kebijakan organisasi/hak akses), **jangan** mengganti teknologi secara diam-diam; laporkan dan minta keputusan (lihat `CLARIFICATIONS.md` K-23).

---

## 15. Risiko teknis dan mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| GPS tidak akurat di dalam gedung | Presensi ditolak/keliru | Ambang akurasi + dispensasi tercatat; uji lapangan |
| Kamera/browser tidak mendukung | Pegawai tidak bisa presensi | Deteksi dukungan + panduan + dispensasi |
| Manipulasi jam perangkat | Kecurangan | Waktu server; jam perangkat tidak dipakai |
| Kebijakan payroll berubah | Salah hitung | Konfigurasi bertanggal + versioning run + uji manual |
| Data awal Excel tidak konsisten | Impor gagal | Templat + validasi + laporan per baris |
| Beban server saat presensi pagi | Lambat | Index, queue, cache, uji beban |
| Kehilangan data | Fatal | Backup harian + uji restore |
| Perubahan aturan pajak/BPJS | Salah potong | Tabel tarif bertanggal, review tahunan |

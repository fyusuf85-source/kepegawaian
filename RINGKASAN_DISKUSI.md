# Ringkasan Diskusi & Status Aplikasi Kepegawaian PIS

**Tanggal**: 23 November 2026  
**Aplikasi**: `D:\APLIKASI KEPEGAWAIAN` (Laravel HRIS, MySQL, Tailwind v4, Vite)

## 1. Status Umum

- Seluruh modul inti Tahap 1–8 selesai: Blueprint, Foundation, Admin, Attendance, Leave, Payroll, Recruitment, KPI/Coaching, Dashboards, Reports, Security, PWA Offline, Multi-Lokasi, Reimbursement/Kasbon.
- Test otomatis: **89 tests, 427 assertions, PASS 100%**.
- Lingkungan lokal: `http://127.0.0.1:8000`
- Akun: Admin `admin` / `Admin#2026`, role demo `Demo#2026`.

## 2. Fitur yang Sudah Diimplementasikan

### Operasional HRIS
- Data pegawai, master data, organisasi, jabatan, lokasi kerja, jadwal/shift.
- Rekrutmen: lowongan dan pelamar.
- Administrasi HRD: kontrak, SK, mutasi, surat.
- Absensi: GPS radius + selfie + idempotency + validasi multi-lokasi dinamis berbasis `schedule_assignments.work_location_id`.
- Izin/cuti/koreksi absensi/dispensasi: alur persetujuan 2 tingkat.
- Payroll: approval HRD -> Keuangan -> Pimpinan; lock butuh `ApprovalInstance` approved + `payroll_runs.status = final`; adjustment approved jadi `PayrollDetail` dengan `source_type = manual_adjustment`.
- KPI: HRD prepare -> Dept Head assess -> HRD verify -> Pimpinan approve.
- Reimbursement & Pinjaman/Kasbon: modul finance dengan 2-level approval.
- Export payroll transfer format BSI.

### PWA / Offline
- Manifest dan service worker tersedia.
- Offline queue presensi via IndexedDB + auto-sync saat online.
- Dokumen uji manual: `docs/PWA_OFFLINE_TEST.md`.

### Keamanan & Operasional
- RBAC permission-based, audit trail, notifikasi, pengaturan sistem.
- Panduan deployment: `docs/DEVELOPMENT_GUIDE.md`.

## 3. Perubahan UI/UX yang Dilakukan

### Redesign Dashboard & Navigasi
- Layout utama: `resources/views/layouts/app.blade.php`
  - Font Inter, topbar sticky, sidebar responsif, notifikasi badge, profil/logout.
- Navigasi: `resources/views/layouts/_nav.blade.php`
  - Grup menu: Kepegawaian, Rekrutmen, Presensi & Izin, Keuangan, Kinerja, Laporan, Administrasi HRD, Inbox, Data Master, Sistem.
  - Ikon SVG, active state, badge persetujuan/notifikasi.
- Dashboard: `resources/views/dashboard.blade.php`
  - Hero greeting dinamis (pagi/siang/sore/malam).
  - Quick actions: Presensi, Ajukan Cuti, Kasbon, Profil.
  - Kartu statistik modular.
  - Grafik Chart.js via CDN: Tren Kehadiran 7 hari (line) dan Komposisi Pegawai (doughnut).
  - Profil singkat, hari libur berikutnya, pegawai terbaru.
- Controller: `app/Http/Controllers/DashboardController.php`
  - Menambah data `attendanceChart` dan `categoryChart`.

## 4. Bug yang Ditemukan & Perbaikan

### A. Tampilan dashboard tidak berubah / layout berantakan
**Penyebab**:
- Service worker cache-first menyajikan aset build lama (`app-BMt9B2AN.css`).
- Perubahan Blade/CSS belum terlihat karena cache browser/SW dan build lama.

**Perbaikan**:
- `public/sw.js` diubah ke `CACHE_NAME = 'pis-hris-v2'`, `skipWaiting`, `claim`, hapus cache lama saat activate, network-first untuk navigasi dan `/build/*`.
- Jalankan `npm run build` dan `php artisan view:clear`.
- User perlu hard refresh (`Ctrl+Shift+R`) atau unregister Service Worker sekali.

### B. Sidebar hilang / layout rusak di lebar tertentu
**Penyebab**:
- Konflik class Tailwind: `-translate-x-full` vs `lg:translate-x-0` pada viewport < 1024px membuat sidebar tersembunyi; layout terlihat tidak karuan.
- Build CSS lama tidak memuat class baru.

**Perbaikan**:
- Sidebar dibuat responsif dengan breakpoint `md:`:
  - `md:static md:translate-x-0`
  - overlay dan hamburger menggunakan `md:hidden`
- JS `initSidebar/toggleSidebar` disesuaikan ke breakpoint 768px.
- CSS dibuild ulang.

### C. Halaman Pengaturan menampilkan key teknis duplikat/mengganggu
**Masalah**: `notification.document_expiry_days` tampil sebagai key teknis; user ingin tampilan bersih.

**Perbaikan**:
- `resources/views/settings/index.blade.php`: deskripsi ditampilkan sebagai label utama; key teknis dihilangkan dari tampilan.
- `php artisan view:clear`.

## 5. Cara Menjalankan Aplikasi

### Jalankan normal
```bash
cd D:\APLIKASI KEPEGAWAIAN
php artisan serve
```
Buka: `http://127.0.0.1:8000`

### Jika port 8000 bentrok
Cek pemakai port:
```powershell
netstat -ano | findstr :8000
tasklist /FI "PID eq <PID>"
```

Matikan proses:
```powershell
taskkill /PID <PID> /F
```

Atau jalankan di port lain:
```bash
php artisan serve --port=8001
```

### Menjalankan banyak aplikasi Laravel
- **Laravel Herd (disarankan di Windows)**: tambahkan folder aplikasi di Herd, akses via domain `.test` tanpa konflik port.
- **Manual**: jalankan tiap app dengan port berbeda (`8000`, `8001`, dst).
- **Batch opsional**:
```batch
start cmd /k "cd /d D:\APLIKASI KEPEGAWAIAN && php artisan serve --port=8000"
start cmd /k "cd /d D:\APLIKASI_LAIN && php artisan serve --port=8001"
```

## 6. Catatan Operasional

- Setelah ubah Blade/CSS/JS frontend: jalankan `npm run build` lalu `php artisan view:clear`.
- Jika tampilan lama masih muncul karena PWA: hard refresh atau unregister Service Worker di DevTools > Application > Service Workers.
- `public/hot` tidak boleh ada di production/build biasa; saat ini dicek tidak ada.
- Service worker saat ini network-first untuk navigasi dan `/build/*`.

## 7. Usulan Lanjutan (Ditunda)

1. **Denda keterlambatan otomatis ke payroll** - butuh kebijakan resmi pimpinan.
2. **Struktur golongan & skala upah (salary grades)** - butuh desain tabel skala upah formal.
3. **API publik terbatas untuk integrasi accounting** - butuh kebutuhan integrasi eksternal (Accurate/Jurnal/dll).

## 8. File Penting

- `docs/DEVELOPMENT_GUIDE.md` - panduan deployment/operasional.
- `docs/PWA_OFFLINE_TEST.md` - panduan uji PWA offline.
- `app/Services/AttendanceService.php` - validasi absensi/multi-lokasi.
- `app/Services/FinanceService.php` - reimbursement & loan approval.
- `resources/js/app.js` - IndexedDB offline queue + sync.
- `resources/views/layouts/app.blade.php` - layout utama.
- `resources/views/layouts/_nav.blade.php` - navigasi.
- `resources/views/dashboard.blade.php` - dashboard modern.
- `app/Http/Controllers/DashboardController.php` - data dashboard/chart.
- `public/sw.js` - service worker.
- `resources/views/settings/index.blade.php` - tampilan pengaturan.

## 9. Status Terakhir

- Dashboard modern aktif, grafik tampil, sidebar responsif.
- Halaman pengaturan sudah dibersihkan dari key teknis.
- Build frontend terakhir sukses.
- Aplikasi siap dijalankan lokal; untuk produksi ikuti `docs/DEVELOPMENT_GUIDE.md`.

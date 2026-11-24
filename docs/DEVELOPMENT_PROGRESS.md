# DEVELOPMENT PROGRESS — Aplikasi Kepegawaian PIS

| Atribut | Nilai |
|---|---|
| Versi dokumen | 1.3 |
| Tanggal | 22-11-2026 |
| Tahap aktif | Tahap 5 Penggajian & Slip Gaji (hampir selesai) |
| Database | MySQL 8.4.9 — `pis_kepegawaian` (dev), `pis_kepegawaian_test` (uji) |
| Kode aplikasi | Laravel 13.43.0 + PHP 8.3.36 + Blade/Tailwind 4 |

---

## 1. Status ringkas

| Tahap | Status | Catatan |
|---|---|---|
| 1 — Analisis & desain | ✅ Selesai | 12 dokumen blueprint + pemeriksaan konsistensi |
| 2 — Fondasi sistem | ✅ Selesai | Auth, RBAC, organisasi, master pegawai, impor/ekspor, audit; 29 uji lulus |
| 3 — Administrasi kepegawaian | ✅ Selesai | Rekrutmen, kontrak + pengingat, SK, mutasi, surat, persetujuan, notifikasi; 38 uji lulus |
| 4 — Presensi & izin | ✅ Selesai | Presensi GPS radius + selfie, izin, koreksi/pengecualian |
| 5 — Penggajian & slip gaji | 🔄 Berjalan | Migration parameter/TER/BPJS, kalkulator gaji harian/tetap, tunjangan, BPJS, PPh 21 TER, penyesuaian, laporan Excel, slip PDF, anomali |
| 6–8 | ⏸ Belum dimulai | KPI, laporan eksekutif, deployment akhir |

---

## 2. Lingkungan pengembangan terverifikasi (18-11-2026)

| Komponen | Hasil | Catatan |
|---|---|---|
| PHP | **8.3.36** | `php -v`; ekstensi `openssl`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `intl`, `zip`, `bcmath`, `curl` aktif |
| Composer | **2.11.2** | Sertifikat CA lokal diset karena Avast Web/Mail Shield (`COMPOSER_CAFILE`) |
| MySQL | **8.4.9** | Port 3306; dijalankan manual (belum sebagai Windows service); datadir `C:\Users\USER\AppData\Local\mysql-data` |
| Node.js | **26.13.0** | Vite 7.3.8 |
| npm | **11.20.0** | `npm install` & `npm run build` berhasil |
| Git | **2.56.0** | Repositori lokal belum diinisialisasi |
| Python | 3.15.2 | Untuk `scripts/check_docs.py` |

Catatan operasional: bila MySQL berhenti, jalankan server manual dengan datadir di atas sebelum `php artisan`.

---

## 3. Deliverable Tahap 1 (tidak berubah)

| Dokumen | Isi |
|---|---|
| `MASTER_SPECIFICATION.md` | Keputusan induk, aturan payroll awal, istilah |
| `SRS.md` | 113 kebutuhan fungsional, 17 aturan bisnis, 17 NFR, 17 use case |
| `DATABASE_DESIGN.md` | 71 tabel domain + 7 framework, ERD, urutan migration, seed |
| `BUSINESS_PROCESS.md` | 15 diagram proses + matriks persetujuan + SLA |
| `UI_UX_SPECIFICATION.md` | Design system, navigasi per peran, spesifikasi halaman |
| `ACCESS_CONTROL.md` | 114 permission, matriks 6 peran, scope data |
| `TECHNICAL_ARCHITECTURE.md` | Stack, arsitektur, keamanan, backup, deployment |
| `DEVELOPMENT_ROADMAP.md` | 8 tahap + kriteria keluar |
| `CLARIFICATIONS.md` | 36 pertanyaan + 15 asumsi sementara |

---

## 4. Deliverable Tahap 2 (terpasang)

### 4.1 Fondasi teknis
- Proyek Laravel 13.43.0 di `D:\APLIKASI KEPEGAWAIAN` (dokumen `docs/` dipertahankan).
- 18 migration berjalan pada MySQL: pengguna/akses, organisasi, pegawai, administrasi pegawai, jadwal, pengaturan, sistem.
- Vite + Tailwind 4; aset produksi terbentuk di `public/build`.
- Paket: `maatwebsite/excel`, `barryvdh/laravel-dompdf`, `intervention/image`, `livewire` (terpasang, belum dipakai).

### 4.2 Modul fungsional
| Modul | Implementasi |
|---|---|
| Autentikasi | Login username/email, rate limit 5/menit, catat `login_histories`, akun nonaktif ditolak, wajib ganti sandi |
| RBAC | 6 peran, 114 permission dari `ACCESS_CONTROL.md`, `Gate::before`, middleware `permission`, cakupan unit |
| Organisasi | CRUD unit (hierarki + level otomatis), jabatan, lokasi kerja, jenjang pendidikan |
| Master pegawai | CRUD lengkap, NIP otomatis `PIS-{tahun}-{urut:4}`, NIK terenkripsi + hash unik, foto |
| Data pribadi | Keluarga, pendidikan, rekening (terenkripsi), dokumen privat + verifikasi, riwayat status & penempatan |
| Profil gaji | Pendidikan, tanggungan, pajak, BPJS/NPWP terenkripsi; nilai khusus hanya Keuangan + alasan |
| Impor/ekspor | Template XLSX, impor per baris dengan laporan galat (`import_batches`), ekspor XLSX |
| Jadwal kerja | Shift, pola mingguan, penugasan per pegawai dengan cek tumpang tindih |
| Pengguna & peran | CRUD pengguna, reset sandi sementara, aktif/nonaktif, editor matriks hak akses |
| Audit & login | Audit append-only dengan masking data sensitif, riwayat login, pengaturan sistem |

### 4.3 Data DEMO
| Akun | Sandi | Peran |
|---|---|---|
| `admin` | `Admin#2026` | administrator |
| `hrd.demo` | `Demo#2026` | hrd |
| `keuangan.demo` | `Demo#2026` | keuangan |
| `pimpinan.demo` | `Demo#2026` | pimpinan |
| `kepala.demo` | `Demo#2026` | kepala_bagian |
| `pegawai.demo` | `Demo#2026` | pegawai |

5 pegawai DEMO, 7 unit organisasi DEMO, 8 jabatan, 1 lokasi, 1 shift, 1 jadwal, 1 hari libur DEMO, 8 pengaturan.

---

## 4A. Deliverable Tahap 3 (terpasang)

### 4A.1 Basis data dan model
- 3 migration baru: `2026_11_18_002000_create_recruitment_tables`, `002100_create_hr_administration_tables`, `002200_create_approval_and_notification_tables`.
- Tabel: `job_vacancies`, `applicants`, `applicant_documents`, `interviews`, `applicant_scores`, `employment_contracts`, `decrees`, `mutations`, `employee_letters`, `approval_flows`, `approval_flow_steps`, `approval_instances`, `approval_actions`, `notifications`.
- Model + relasi untuk seluruh tabel; kontrak `App\Contracts\Approvable` menyatukan subjek persetujuan.

### 4A.2 Modul fungsional
| Modul | Implementasi |
|---|---|
| Rekrutmen | CRUD lowongan + status; pelamar + unggah dokumen privat; tahapan seleksi; jadwal & hasil wawancara; penilaian berbobot (total 100); keputusan penerimaan |
| Konversi pegawai | Identitas pelamar dipakai ulang; NIP otomatis; cegah konversi ganda & NIK duplikat; penempatan awal + riwayat status |
| Kontrak kerja | CRUD draf, perpanjangan tertaut, PDF dari template, status sesuai desain DB, pengingat H-30/H-14/H-7 |
| SK | Jenis pengangkatan/penempatan/perubahan jabatan/mutasi/pemberhentian, PDF, alur persetujuan, penerbitan SK dari mutasi |
| Mutasi | Usulan perubahan unit/jabatan/atasan; persetujuan dua tingkat; penerapan menutup penempatan aktif H-1 dan membuat baris baru |
| Surat | Teguran/pembinaan/pemberhentian/keterangan, PDF, persetujuan, penandaan terkirim |
| Persetujuan | Mesin 4 tabel generik; langkah per jenis dokumen; cakupan `all`/`unit`/`self`; larangan menyetujui pengajuan sendiri; riwayat aksi |
| Notifikasi | Notifikasi dalam aplikasi, badge navigasi, dedupe pengingat kontrak, tanda baca |
| Template dokumen | `KONTRAK-UMUM`, `SK-UMUM`, `SURAT-UMUM` (DEMO) + editor master template |

### 4A.3 Alur persetujuan yang di-seed
| Kode | Dokumen | Langkah |
|---|---|---|
| `contract` | Kontrak kerja | Pimpinan (all) |
| `decree` | SK | Pimpinan (all) |
| `mutation` | Mutasi | Kepala bagian (unit) → Pimpinan (all) |
| `letter` | Surat | Pimpinan (all) |

Langkah Yayasan belum di-seed karena menunggu K-26.

### 4A.4 Penomoran sementara
`LOW/{tahun}/{urut:4}` (lowongan), `PLM/...` (pelamar), `KTR/...` (kontrak), `SK/...` (SK), `SRT/...` (surat). Seluruhnya **sementara** sampai K-18 dijawab.

### 4A.5 Pengujian
38 pengujian otomatis (213 assertion) lulus pada MySQL `pis_kepegawaian_test`. Rincian di `TEST_REPORT.md` §13.

---

## 5. Keputusan penting tahap ini

1. Stack aktual: **Laravel 13.43 + PHP 8.3.36 + MySQL 8.4.9**, Blade + Tailwind 4 (Livewire belum dipakai).
2. RBAC ditulis khusus (`roles`, `permissions`, `permission_role`, `role_user.org_unit_id`) agar cocok dengan desain database.
3. Kolom sensitif (`nik`, `account_number`, `npwp`, `bpjs_*`) memakai cast `encrypted`; NIK punya `nik_hash` SHA-256 untuk keunikan.
4. Berkas privat disimpan di `storage/app/private`, bukan `public`.
5. Audit menyamarkan nilai sensitif menjadi `[disembunyikan]` dan tidak memiliki rute hapus.
6. Administrator **tidak** otomatis mendapat akses payroll/data pribadi; sesuai matriks dipakai akun HRD/Keuangan.
7. Tarif gaji/pajak tetap tidak diisi sampai klarifikasi K-01..K-04 dan K-09.
8. Persetujuan memakai mesin generik 4 tabel (`approval_flows`, `approval_flow_steps`, `approval_instances`, `approval_actions`) dengan aturan pengaju tidak boleh menyetujui pengajuannya sendiri.
9. Status kontrak mengikuti desain database: pengajuan yang menunggu tetap `draf`; penolakan menjadi `dibatalkan`; kontrak lama baru `diperpanjang` setelah kontrak baru disetujui.
10. Mutasi yang diterapkan menutup penempatan aktif dengan `effective_to = effective_date - 1` lalu membuat baris `employee_assignments` baru.
11. Konversi pelamar hanya dari status `diterima`, sekali per pelamar, dan identitas pelamar dipakai ulang tanpa input ulang.

---

## 6. Kendala dan risiko saat ini

| # | Kendala | Dampak | Rencana |
|---|---|---|---|
| 1 | MySQL belum terdaftar sebagai Windows service | Server harus dijalankan manual | Daftarkan service atau dokumentasikan skrip mulai |
| 2 | Avast Web/Mail Shield memutus TLS Composer | Instalasi paket gagal tanpa CA lokal | Gunakan `COMPOSER_CAFILE` (sudah diset) |
| 3 | Koordinat/radius lokasi nyata belum ada (K-13) | Presensi hanya bisa diuji dengan lokasi DEMO | Minta data lokasi |
| 4 | Rumus payroll belum final (K-01..K-04, K-09) | Payroll produksi tidak boleh dijalankan | Klarifikasi sebelum Tahap 5 |
| 5 | Berkas Excel asli belum tersedia (K-22) | Impor diuji dengan baris sintetis | Minta contoh berkas |
| 6 | Struktur organisasi aktual belum ada (K-31) | Master organisasi memakai DEMO | Minta struktur resmi |
| 7 | Email/SMTP belum dikonfirmasi (K-34) | Reset sandi via email belum aktif | Konfirmasi infrastruktur |
| 8 | Ekspor Excel, unggah dokumen/foto, tampilan peramban belum diuji end-to-end | Ada risiko cacat alur berkas | Uji manual sebelum Tahap 8/UAT |
| 9 | Format nomor resmi NIP/kontrak/SK/surat belum diputuskan (K-18) | Penomoran memakai pola sementara `KTR/SK/SRT/{tahun}/{urut}` | Minta format resmi sebelum produksi |
| 10 | Kewenangan dan dokumen yang butuh persetujuan Yayasan belum jelas (K-26) | Langkah Yayasan belum di-seed pada alur persetujuan | Konfirmasi matriks kewenangan |
| 11 | Durasi masa percobaan belum ditetapkan (K-08) | Tanggal probation diisi manual, tanpa perhitungan otomatis | Konfirmasi aturan percobaan |

---

## 7. Cara menjalankan

```powershell
# 1. Pastikan MySQL berjalan (port 3306)

# 2. Siapkan basis data (sekali saja)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS pis_kepegawaian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Migration + data DEMO
php artisan migrate:fresh --seed

# 4. Aset frontend
npm install
npm run build

# 5. Jalankan aplikasi
php artisan serve
```

Buka `http://127.0.0.1:8000`, masuk dengan akun DEMO pada §4.3.

Pengujian:

```powershell
php artisan test        # MySQL pis_kepegawaian_test
python scripts\check_docs.py
```

---

## 8. Pekerjaan berikutnya (Tahap 4)

1. Jawab klarifikasi yang masih menghambat: K-08, K-18, K-26, serta K-01..K-04, K-09, K-13, K-22, K-23, K-31, K-34 untuk tahap lanjutan.
2. Bangun presensi mandiri GPS radius + foto selfie, pengajuan izin/cuti, dan koreksi presensi — sesuai `DEVELOPMENT_ROADMAP.md`.
3. Uji manual alur berkas yang belum tercakup (ekspor Excel, unggah dokumen/foto, tampilan peramban, tata letak PDF).
4. Perbarui `TEST_REPORT.md`, `CHANGELOG.md`, dan dokumen ini setiap tahap.

---

## 9. Riwayat pembaruan dokumen ini

| Tanggal | Versi | Perubahan |
|---|---|---|
| 18-11-2026 | 1.0 | Tahap 1 selesai; blueprint lengkap |
| 18-11-2026 | 1.1 | Tahap 2 selesai; toolchain terpasang, fondasi aplikasi berjalan, 29 uji lulus |
| 18-11-2026 | 1.2 | Tahap 3 selesai; rekrutmen, kontrak, SK, mutasi, surat, persetujuan, notifikasi; 38 uji lulus (213 assertion) |

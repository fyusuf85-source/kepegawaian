# TEST REPORT — Aplikasi Kepegawaian PIS

| Atribut | Nilai |
|---|---|
| Versi | 1.2 |
| Tanggal | 18-11-2026 |
| Tahap | 1 — Analisis dan desain (selesai) · 2 — Fondasi sistem (selesai) · 3 — Rekrutmen dan administrasi HRD (selesai) |
| Lingkungan | Windows, PHP 8.3.36, MySQL 8.4.9, Node 26.13.0, Python 3.15.2 |
| Status aplikasi | Tahap 3 berjalan: fondasi sistem + rekrutmen, kontrak, SK, mutasi, surat, persetujuan, notifikasi |

> Dokumen ini hanya mencatat pengujian yang **benar-benar dijalankan**. Tidak ada klaim lulus untuk fitur yang belum dibuat.

---

## 1. Ruang lingkup pengujian Tahap 1

Yang dapat diuji pada Tahap 1 hanyalah **konsistensi dokumen blueprint**:

1. Kelengkapan berkas wajib.
2. Keunikan ID kebutuhan (`FR-`, `BR-`, `K-`).
3. Validitas referensi klarifikasi (`K-xx`).
4. Format & keunikan katalog permission.
5. Referensi permission antar dokumen.
6. Kelengkapan spesifikasi tabel (PK, daftar tabel vs spesifikasi).
7. Konsistensi jumlah tabel yang diklaim.
8. Keseimbangan blok kode/Mermaid.
9. Konsistensi kolom tabel Markdown.
10. Kelengkapan 8 tahap roadmap dan jumlah kebutuhan fungsional.

Alat: `scripts/check_docs.py` (pemeriksaan otomatis, dapat dijalankan ulang).

---

## 2. Hasil eksekusi otomatis

Perintah:

```powershell
python scripts\check_docs.py
```

```text
==============================================================================
HASIL PEMERIKSAAN DOKUMEN — TAHAP 1
==============================================================================
[LULUS] Semua 12 berkas dokumentasi wajib tersedia
[LULUS] README.md tersedia
[LULUS] Berkas prompt induk tetap utuh
[LULUS] SRS.md: ID FR- unik (113 ID)
[LULUS] SRS.md: ID BR- unik (17 ID)
[LULUS] CLARIFICATIONS.md: ID K- unik (36 ID)
[LULUS] Semua referensi K-xx terdefinisi (36 kode)
[LULUS] ACCESS_CONTROL.md: 114 kode permission unik
[LULUS] Semua kode permission memakai format modul.aksi
[LULUS] Semua permission yang disebut dokumen lain ada di katalog
[LULUS] DATABASE_DESIGN.md: seluruh 71 tabel domain memiliki spesifikasi kolom
[LULUS] Jumlah tabel konsisten: 78 (termasuk tabel framework)
[LULUS] Setiap tabel memiliki penanda primary key (PK)
[LULUS] 20 diagram Mermaid dengan blok kode seimbang
[LULUS] Struktur tabel Markdown konsisten
[LULUS] Roadmap memuat 8 tahap berurutan
[LULUS] SRS memuat 113 kebutuhan fungsional unik dengan prioritas

RINGKASAN: SEMUA LULUS
Total pemeriksaan: 17 | Gagal: 0 | Peringatan: 0
```

---

## 3. Pemeriksaan manual

| # | Pemeriksaan | Hasil | Catatan |
|---|---|---|---|
| M-01 | Setiap kebutuhan fungsional punya ID, prioritas, kriteria penerimaan | ✅ | 113 FR |
| M-02 | Setiap tabel transaksi memiliki kolom waktu/audit yang memadai | ✅ | `created_at`/`updated_at`, audit terpisah |
| M-03 | Tidak ada nominal gaji/pajak yang ditanam sebagai konstanta kode | ✅ | Semua melalui tabel konfigurasi bertanggal |
| M-04 | Matriks hak akses mencakup 6 peran pada setiap modul | ✅ | 114 permission |
| M-05 | Alur koreksi absensi dan payroll memisahkan pemohon/penyetuju | ✅ | Ditegaskan di aturan bisnis & constraint |
| M-06 | Klaim fitur keamanan sesuai kemampuan nyata | ✅ | Liveness/anti-spoofing dinyatakan **tidak** tersedia |
| M-07 | Kebijakan yang belum diputuskan tidak dikarang | ✅ | 36 pertanyaan, 15 asumsi ditandai |
| M-08 | Render visual diagram Mermaid | ⚠️ Belum diuji | Hanya struktur blok yang diperiksa otomatis |
| M-09 | Tampilan UI sebenarnya | ⚠️ Belum diuji | Belum ada kode |
| M-10 | Migration pada MySQL | ⚠️ Belum diuji | MySQL belum terpasang |

---

## 4. Cacat yang ditemukan dan diperbaiki selama Tahap 1

| # | Cacat | Berkas | Perbaikan |
|---|---|---|---|
| D-01 | Baris tabel `employment_contracts` kurang satu kolom | `DATABASE_DESIGN.md` | Kolom keterangan ditambahkan |
| D-02 | Jumlah tabel yang diklaim (72) tidak sama dengan hasil hitung (78) | `DATABASE_DESIGN.md` | Klaim diselaraskan menjadi 78 (71 domain + 7 framework) |
| D-03 | Kode peran terbaca sebagai permission oleh pemeriksa | `scripts/check_docs.py` | Validasi permission dibatasi pada pola bertitik |
| D-04 | Kunci pengaturan (`attendance.default_radius_m`, dsb.) dianggap permission | `scripts/check_docs.py` | Daftar kunci pengaturan dikecualikan |
| D-05 | Deteksi duplikasi `K-xx` salah menghitung referensi | `scripts/check_docs.py` | Duplikasi hanya dihitung dari baris definisi |

---

## 5. Yang belum diuji (dan tidak boleh diklaim)

| Area | Alasan |
|---|---|
| Login, RBAC, CRUD | Aplikasi belum dibuat |
| Presensi GPS & kamera | Aplikasi belum dibuat; koordinat nyata belum tersedia |
| Perhitungan payroll | Rumus belum final (K-01..K-04, K-09) |
| Impor Excel | File sumber belum tersedia (K-22) |
| PDF slip/SK | Belum ada kode |
| Performa 1.000 pegawai | Belum ada data & kode |
| Backup/restore | Belum ada database |
| Keamanan aplikasi (OWASP) | Belum ada permukaan serang |
| UAT | Belum dapat dilakukan |

---

## 6. Kesimpulan Tahap 1

- Blueprint dinyatakan **lengkap dan konsisten** berdasarkan pemeriksaan otomatis dan manual di atas.
- **Aplikasi belum siap** dan belum diuji; statusnya masih rancangan.
- Tahap 2 boleh dimulai setelah blueprint disetujui dan toolchain (PHP/Composer/MySQL) terpasang.
- Pengujian fungsional pertama yang wajib dilakukan pada Tahap 2: login, RBAC, CRUD pegawai, impor Excel, riwayat, audit trail.

---

## 7. Riwayat pengujian

| Tanggal | Tahap | Alat | Hasil |
|---|---|---|---|
| 18-11-2026 | 1 | `scripts/check_docs.py` | Lihat §2 (seluruh pemeriksaan lulus) |
| 18-11-2026 | 1 | Tinjauan manual | 7 lulus, 3 belum dapat diuji |
| 18-11-2026 | 2 | `php artisan test` | 29 lulus, 0 gagal, 106 assertion (lihat §8) |
| 18-11-2026 | 3 | `php artisan test` | 38 lulus, 0 gagal, 213 assertion (lihat §13) |

---

# Bagian Tahap 2 — Fondasi Sistem

## 8. Ringkasan hasil pengujian otomatis Tahap 2

Perintah:

```powershell
php artisan test
```

Hasil terakhir (18-11-2026, database uji MySQL `pis_kepegawaian_test`):

```text
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\AuditTrailTest
PASS  Tests\Feature\AuthTest
PASS  Tests\Feature\AuthorizationTest
PASS  Tests\Feature\EmployeeImportTest
PASS  Tests\Feature\EmployeeManagementTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\SmokePagesTest

Tests: 29 passed (106 assertions)
Duration: 14.56s
```

Rincian berkas uji:

| Berkas | Cakupan | Status |
|---|---|---|
| `tests/Feature/AuthTest.php` | Login sukses/gagal, akun nonaktif, wajib ganti sandi, logout, riwayat login | ✅ 6 lulus |
| `tests/Feature/AuthorizationTest.php` | Permission per rute, `Gate`, cakupan unit induk→turunan, relasi peran–permission | ✅ 6 lulus |
| `tests/Feature/EmployeeManagementTest.php` | Tambah pegawai + NIP otomatis + NIK terenkripsi, tolak NIK duplikat, masking NIK, ubah, hapus (soft delete) | ✅ 6 lulus |
| `tests/Feature/EmployeeImportTest.php` | Baris valid tersimpan beserta pendidikan/pajak, baris tidak valid tercatat gagal | ✅ 2 lulus |
| `tests/Feature/AuditTrailTest.php` | Audit create/update, masking NIK, format tahun & padding nomor urut | ✅ 3 lulus |
| `tests/Feature/SmokePagesTest.php` | Halaman utama & master sebagai admin, detail/form pegawai sebagai HRD, pembatasan akses pegawai | ✅ 4 lulus |
| `tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php` | Unit dasar dan redirect tamu ke login | ✅ 2 lulus |

## 9. Pemeriksaan manual Tahap 2

| # | Pemeriksaan | Hasil | Catatan |
|---|---|---|---|
| M-11 | `php artisan migrate:fresh --seed` pada MySQL 8.4.9 | ✅ | Seluruh migration berjalan; seeder selesai |
| M-12 | Jumlah data hasil seed | ✅ | users=6, employees=5, roles=6, permissions=114, settings=8 |
| M-13 | `php artisan route:list --except-vendor` | ✅ | 98 rute terdaftar |
| M-14 | `npm run build` (Vite 7 + Tailwind 4) | ✅ | Aset produksi terbentuk |
| M-15 | Uji halaman utama (HTTP test) untuk admin/HRD/pegawai | ✅ | Lihat `SmokePagesTest` |
| M-16 | Ekspor Excel & unduh template | ⚠️ Belum diuji end-to-end | Kelas export ada; pengujian berkas nyata belum dibuat |
| M-17 | Unggah dokumen & foto pegawai | ⚠️ Belum diuji end-to-end | Alur tersedia; belum ada berkas uji |
| M-18 | Render tampilan di peramban nyata | ⚠️ Belum diuji | Hanya respons HTTP & isi HTML yang diperiksa |
| M-19 | Presensi GPS, payroll, PDF slip/SK | ❌ Belum ada | Tahap 4–5; menunggu klarifikasi |

## 10. Cacat yang ditemukan dan diperbaiki selama Tahap 2

| # | Cacat | Berkas | Perbaikan |
|---|---|---|---|
| D-06 | Model `Education` mencari tabel `education` | `app/Models/Education.php` | `$table = 'educations'` |
| D-07 | Model `EmployeeEducation` mencari tabel `employee_education` | `app/Models/EmployeeEducation.php` | `$table = 'employee_educations'` |
| D-08 | Rute `/pegawai/tambah` 404 karena `Route::resource` memakai segmen `create` | `routes/web.php` | Rute pegawai ditulis eksplisit dengan segmen Indonesia |
| D-09 | Factory pengguna tidak mengisi `is_active` sehingga middleware menolak akun uji | `database/factories/UserFactory.php` | Nilai bawaan `is_active=true`, `must_change_password=false` |
| D-10 | Audit create mengambil log terakhir (profil gaji), bukan pegawai | `tests/Feature/AuditTrailTest.php` | Filter `auditable_type` |
| D-11 | Impor gagal karena data pendidikan belum ada pada DB uji | `tests/Feature/EmployeeImportTest.php` | Seed `educations` pada `setUp` |
| D-12 | Kolom `bpjs_*` terlalu pendek untuk nilai terenkripsi | migration `employee_administration_tables` | Diubah menjadi `TEXT` |

## 11. Yang belum diuji (dan tidak boleh diklaim)

| Area | Alasan |
|---|---|
| Presensi GPS & kamera | Tahap 4; koordinat/radius nyata belum tersedia (K-13) |
| Perhitungan payroll, BPJS, PPh 21 | Rumus belum final (K-01..K-04, K-09); sengaja belum diisi |
| Impor Excel dengan berkas asli pesantren | Berkas sumber belum tersedia (K-22); yang diuji adalah pemrosesan baris |
| Ekspor Excel & PDF slip/SK | Belum diuji terhadap berkas hasil nyata |
| Struktur organisasi resmi | Masih memakai data DEMO (K-31) |
| Performa 1.000 pegawai | Belum ada data volume tersebut |
| Backup/restore, deployment, UAT | Tahap 8 |
| Keamanan menyeluruh (OWASP) | Baru mencakup autentikasi, otorisasi, enkripsi kolom sensitif, audit |

## 12. Kesimpulan Tahap 2

- Fondasi sistem (autentikasi, RBAC, organisasi, master pegawai, impor/ekspor, audit) **berjalan** dan lulus 29 pengujian otomatis.
- Seluruh data contoh diberi label **DEMO**; tidak ada tarif gaji/pajak yang ditanam di kode.
- Aplikasi **belum siap produksi**: modul Tahap 3–8 belum ada dan beberapa alur berkas belum diuji end-to-end.
- Langkah berikutnya: Tahap 3 (rekrutmen, kontrak, SK, mutasi, surat) setelah klarifikasi terkait.

---

# Bagian Tahap 3 — Rekrutmen dan Administrasi HRD

## 13. Ringkasan hasil pengujian otomatis Tahap 3

Perintah:

```powershell
php artisan test
```

Hasil terakhir (18-11-2026, database uji MySQL `pis_kepegawaian_test`):

```text
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ApprovalWorkflowTest
PASS  Tests\Feature\AuditTrailTest
PASS  Tests\Feature\AuthTest
PASS  Tests\Feature\AuthorizationTest
PASS  Tests\Feature\EmployeeImportTest
PASS  Tests\Feature\EmployeeManagementTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\RecruitmentTest
PASS  Tests\Feature\SmokePagesTest

Tests: 38 passed (213 assertions)
Duration: 31.98s
```

Rincian berkas uji baru/berubah:

| Berkas | Cakupan | Status |
|---|---|---|
| `tests/Feature/RecruitmentTest.php` | Lowongan → pelamar → seleksi → penilaian berbobot → keputusan → konversi pegawai tanpa input ulang identitas; tolak konversi ganda; total bobot wajib 100; penerimaan hanya pada tahap dipertimbangkan | ✅ 3 lulus (28 assertion) |
| `tests/Feature/ApprovalWorkflowTest.php` | Pengaju tidak boleh menyetujui pengajuannya sendiri; pimpinan menyetujui kontrak; mutasi dua tingkat (kepala bagian → pimpinan) lalu diterapkan; riwayat penempatan lama ditutup H-1 dan baris baru dibuat; perpanjangan kontrak; pengingat H-30 tanpa duplikat; PDF kontrak & SK tersimpan privat; otorisasi HRD/pegawai/keuangan | ✅ 5 lulus (64 assertion) |
| `tests/Feature/SmokePagesTest.php` | Ditambah 13 halaman Tahap 3 untuk admin dan form mutasi/surat untuk HRD | ✅ 5 lulus (43 assertion) |

## 14. Pemeriksaan manual Tahap 3

| # | Pemeriksaan | Hasil | Catatan |
|---|---|---|---|
| M-20 | `php artisan migrate --force` pada dev DB | ✅ | 3 migration Tahap 3 berhasil |
| M-21 | `php artisan route:list --except-vendor` | ✅ | 171 rute terdaftar (bertambah 73 rute Tahap 3) |
| M-22 | `php artisan view:cache` | ✅ | Seluruh Blade Tahap 3 terkompilasi |
| M-23 | Seeder alur persetujuan & template dokumen | ✅ | `contract`, `decree`, `mutation`, `letter`; template `KONTRAK-UMUM`, `SK-UMUM`, `SURAT-UMUM` |
| M-24 | Penjadwalan pengingat kontrak | ✅ | `contracts:remind` harian 07:00 Asia/Jakarta, `withoutOverlapping` |
| M-25 | Percobaan langsung di peramban (login & navigasi Tahap 3) | ⚠️ Belum diuji menyeluruh | Hanya respons HTTP, isi HTML, dan alur uji otomatis yang diperiksa |
| M-26 | Tampilan PDF di pembaca PDF nyata | ⚠️ Belum diuji visual | Berkas PDF valid terbentuk dan tersimpan; tata letak belum ditinjau manusia |

## 15. Cacat yang ditemukan dan diperbaiki selama Tahap 3

| # | Cacat | Berkas | Perbaikan |
|---|---|---|---|
| D-13 | Lowongan hari ini dianggap belum dibuka karena perbandingan tanggal UTC vs Asia/Jakarta | `app/Models/JobVacancy.php` | Perbandingan memakai string tanggal kalender Asia/Jakarta |
| D-14 | Pengingat H-30/H-14/H-7 salah hitung hari karena selisih zona waktu | `app/Services/ContractService.php` | Kedua tanggal dinormalkan ke Asia/Jakarta sebelum `diffInDays` |
| D-15 | Unduhan PDF gagal (HTTP 500) karena nomor dokumen memuat `/` | `ContractController`, `DecreeController`, `LetterController`, `ApplicantController` | Nama unduhan dibersihkan melalui `Format::filename()` |
| D-16 | Asumsi urutan riwayat penempatan terbalik (relasi memakai `orderByDesc`) | `tests/Feature/ApprovalWorkflowTest.php` | Uji memakai query eksplisit berurut naik |

## 16. Yang belum diuji (dan tidak boleh diklaim) — Tahap 3

| Area | Alasan |
|---|---|
| Format nomor resmi NIP/kontrak/SK/surat | Menunggu klarifikasi K-18; pola `KTR/SK/SRT/{tahun}/{urut}` masih sementara |
| Persetujuan Yayasan | Menunggu K-26; langkah Yayasan belum di-seed |
| Durasi masa percobaan otomatis | Menunggu K-08; tanggal probation diisi manual |
| Tampilan PDF visual & cetak | Berkas terbentuk, tetapi tata letak belum ditinjau |
| Uji peramban nyata & UAT | Tahap 8 |
| Notifikasi email/WhatsApp | Baru notifikasi dalam aplikasi (K-34) |

## 17. Kesimpulan Tahap 3

- Alur rekrutmen hingga konversi pegawai, kontrak + perpanjangan + pengingat, SK, mutasi + riwayat penempatan, surat, persetujuan berjenjang, dan notifikasi **berjalan** dan lulus 38 pengujian otomatis (213 assertion).
- Kriteria keluar Tahap 3 terpenuhi pada tingkat perangkat lunak: pelamar diproses tanpa input ulang identitas; kontrak/SK menyimpan PDF privat; mutasi memperbarui penempatan aktif dan riwayat; alur persetujuan tercatat.
- Belum siap produksi: format nomor resmi (K-18), kewenangan Yayasan (K-26), dan durasi percobaan (K-08) masih menunggu keputusan pesantren.

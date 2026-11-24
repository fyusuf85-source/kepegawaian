# Dokumen Induk Prompt Pembangunan Aplikasi Kepegawaian PIS

**Instansi:** Pesantren Imam Syafi'i\
**Versi:** 1.0\
**Tanggal:** 17 November 2026\
**Platform:** Web Application\
**Cakupan:** Sistem kepegawaian terintegrasi\
**Metode absensi:** Mandiri melalui GPS radius dan foto selfie

------------------------------------------------------------------------

## Daftar Isi

1.  [Keputusan dan prinsip proyek](#1-keputusan-dan-prinsip-proyek)
2.  [Master Prompt](#2-master-prompt)
3.  [Prompt Tahap 1 --- Analisis dan
    desain](#3-prompt-tahap-1--analisis-dan-desain-sistem)
4.  [Prompt Tahap 2 --- Fondasi
    sistem](#4-prompt-tahap-2--fondasi-sistem-dan-master-data)
5.  [Prompt Tahap 3 --- Rekrutmen dan administrasi
    HRD](#5-prompt-tahap-3--rekrutmen-dan-administrasi-hrd)
6.  [Prompt Tahap 4 --- Absensi dan
    perizinan](#6-prompt-tahap-4--absensi-kedisiplinan-dan-perizinan)
7.  [Prompt Tahap 5 ---
    Penggajian](#7-prompt-tahap-5--penggajian-dan-slip-gaji)
8.  [Prompt Tahap 6 --- KPI dan
    pembinaan](#8-prompt-tahap-6--kpi-evaluasi-dan-pembinaan)
9.  [Prompt Tahap 7 --- Dashboard dan
    laporan](#9-prompt-tahap-7--dashboard-laporan-dan-notifikasi)
10. [Prompt Tahap 8 --- Pengujian dan
    deployment](#10-prompt-tahap-8--pengujian-keamanan-dan-deployment)
11. [Prompt pengendali perpindahan
    tahap](#11-prompt-pengendali-perpindahan-tahap)
12. [Checklist progres](#12-checklist-progres)
13. [Panduan penggunaan](#13-panduan-penggunaan)

------------------------------------------------------------------------

# 1. Keputusan dan prinsip proyek

Dokumen ini menjadi pedoman utama pembangunan **Aplikasi Kepegawaian
Pesantren Imam Syafi'i (PIS)**. Seluruh tahap pembangunan wajib
mengikuti keputusan yang tercantum di dalamnya.

## 1.1 Identitas aplikasi

-   **Nama:** Aplikasi Kepegawaian PIS
-   **Instansi:** Pesantren Imam Syafi'i
-   **Lokasi:** Sukamakmur, Aceh Besar, Aceh, Indonesia
-   **Bahasa:** Indonesia
-   **Mata uang:** Rupiah (IDR)
-   **Format tanggal:** DD-MM-YYYY
-   **Zona waktu:** Asia/Jakarta

## 1.2 Keputusan yang telah disepakati

  Komponen          Keputusan
  ----------------- ------------------------------------------
  Platform          Web Application
  Cakupan           Terintegrasi
  Database          Database baru dan terpusat
  Migrasi data      Impor dari Excel yang tersedia
  Metode absensi    Presensi mandiri
  Validasi lokasi   GPS dengan radius lokasi pesantren
  Bukti presensi    Foto selfie wajib setiap presensi
  Perangkat         Pegawai boleh menggunakan HP berbeda
  Koreksi absensi   Atasan langsung → HRD
  Antarmuka         Responsif untuk komputer, tablet, dan HP

## 1.3 Prinsip pembangunan

1.  Satu proyek, satu database utama, dan satu standar desain.
2.  Seluruh modul saling terintegrasi.
3.  Master data pegawai menjadi sumber data utama.
4.  Pembangunan dilakukan bertahap dan setiap tahap diuji.
5.  Tidak membuat ulang proyek atau database pada setiap tahap.
6.  Tidak menghapus fitur yang telah berjalan tanpa alasan dan
    persetujuan.
7.  Seluruh perubahan penting memiliki riwayat dan audit trail.
8.  Kebijakan yang dapat berubah harus disimpan sebagai konfigurasi
    dengan tanggal berlaku.
9.  Data demonstrasi harus diberi label DEMO dan tidak boleh dianggap
    sebagai data produksi.
10. Jangan mengarang hasil pengujian atau menyatakan fitur selesai jika
    belum diuji.

## 1.4 Modul utama

1.  Dashboard.
2.  Master Data Pegawai.
3.  Struktur Organisasi.
4.  Rekrutmen dan Penerimaan Pegawai.
5.  Administrasi HRD.
6.  Absensi dan Kedisiplinan.
7.  Perizinan dan Cuti.
8.  Penggajian.
9.  Penilaian Kinerja (KPI).
10. Pembinaan Pegawai.
11. Laporan.
12. Pengaturan Sistem.

## 1.5 Kategori dan status pegawai

Kategori pegawai:

-   Tetap.
-   Harian.
-   Khidmah.

Kategori pegawai harus dibedakan dari status operasional. Status
operasional dapat mencakup:

-   Aktif.
-   Dalam masa percobaan.
-   Cuti atau izin sesuai ketentuan.
-   Nonaktif sementara.
-   Mengundurkan diri.
-   Diberhentikan.

Perubahan kategori atau status harus menyimpan tanggal efektif, alasan,
dokumen pendukung bila diperlukan, dan riwayat perubahan.

## 1.6 Keputusan absensi

1.  Pegawai melakukan presensi mandiri melalui smartphone.
2.  Pegawai harus login menggunakan akun masing-masing.
3.  GPS wajib digunakan.
4.  Presensi diterima jika berada dalam radius lokasi kerja yang
    ditentukan.
5.  Foto selfie wajib diambil setiap presensi.
6.  Waktu presensi menggunakan waktu server, bukan jam perangkat.
7.  Sistem mencegah presensi ganda.
8.  Sistem menyimpan koordinat, waktu, pegawai, dan bukti foto.
9.  Pegawai boleh menggunakan HP berbeda.
10. Koreksi absensi melalui atasan langsung kemudian HRD.
11. Radius GPS, titik koordinat, lokasi kerja, jadwal, dan toleransi
    keterlambatan harus dapat dikonfigurasi.
12. Jika GPS atau kamera tidak tersedia, sistem harus menampilkan
    penjelasan dan menyediakan prosedur pengecualian yang tercatat.
    Jangan menerima presensi seolah-olah valid tanpa bukti yang
    disyaratkan.

Jangan mengklaim memiliki deteksi liveness, anti-spoofing, atau
pencegahan manipulasi GPS tingkat lanjut apabila fitur tersebut belum
benar-benar diimplementasikan.

## 1.7 Keputusan pengelolaan HRD

-   HRD mengelola administrasi pegawai.
-   Kepala bagian menjadi atasan langsung sesuai struktur organisasi.
-   Kepala bagian melakukan penilaian atau verifikasi sesuai
    kewenangannya.
-   HRD melakukan verifikasi administrasi dan proses yang menjadi
    kewenangannya.
-   Pimpinan memberikan persetujuan akhir untuk keputusan yang
    memerlukan kewenangan pimpinan.
-   Setiap keputusan dan dokumen harus memiliki riwayat.

## 1.8 Ketentuan penggajian PIS

Angka berikut merupakan konfigurasi awal berdasarkan keputusan yang
telah dibahas. Sebelum digunakan untuk payroll produksi, seluruh rumus,
basis, syarat penerima, dan perlakuan prorata harus diuji serta
dikonfirmasi terhadap kebijakan resmi pesantren.

  Komponen                                                  Ketentuan awal
  ---------------------------------- -------------------------------------
  Gaji harian pegawai tetap               Rp51.000 per hari kalender aktif
  Potongan ketidakhadiran              Rp51.000 per hari, sesuai ketentuan
  Potongan keterlambatan                                Rp106,25 per menit
  Basis pendidikan SMA                                           Rp700.000
  Basis pendidikan D3                                            Rp900.000
  Basis pendidikan S1                                          Rp1.100.000
  Kenaikan tahunan pegawai tetap                    5% dari basis terakhir
  Peningkatan pendidikan D3 ke S1                   15% dari gaji terakhir
  Tunjangan pasangan                                             Rp200.000
  Tunjangan anak                                        Rp100.000 per anak
  Transportasi 1--5 km                                             Rp5.000
  Transportasi lebih dari 5--10 km                                Rp10.000
  Transportasi jarak lebih jauh        Dapat dikonfigurasi sampai Rp20.000
  Tunjangan kehadiran MSU/SD                                     Rp270.000
  Rumus proporsional MSU/SD                           Rp270.000 / 30 / 240
  BPJS Kesehatan --- basis awal                                Rp4.200.000
  BPJS Kesehatan --- pemberi kerja                                      4%
  BPJS Kesehatan --- pegawai                                            1%
  JHT --- pemberi kerja                                              3,70%
  JHT --- pegawai                                                       2%
  JKK --- pemberi kerja                                              0,24%
  JKM --- pemberi kerja                                              0,30%

Ketentuan tambahan:

-   Tunjangan kehadiran berlaku untuk pegawai Tetap dan Khidmah khusus
    sesuai kebijakan.
-   Tunjangan kehadiran MSU/SD menggantikan tunjangan kehadiran lainnya.
-   Tunjangan beras berdasarkan jumlah anggota keluarga dan tidak
    diberikan kepada pegawai lajang.
-   Tunjangan pasangan tidak diberikan kepada istri apabila suami juga
    bekerja di pesantren.
-   Tunjangan jabatan utama, tambahan, dan istimewa dapat dikonfigurasi.
-   BPJS Kesehatan dan BPJS Ketenagakerjaan memiliki pengaturan
    kepesertaan dan basis perhitungan tersendiri.
-   Uang snack tidak diberikan kepada pegawai berstatus Driver.
-   Satu pegawai maksimal menerima satu uang snack per tanggal untuk
    setiap jadwal yang memenuhi syarat sesuai aturan operasional.
-   Khidmah biasa dan Khidmah khusus harus dibedakan.
-   Masa percobaan dan ketentuan penggajian selama masa percobaan harus
    dapat dikonfigurasi.

**Penting:** ketentuan pajak, BPJS, upah, dan potongan harus dapat
disesuaikan dengan peraturan yang berlaku dan kebijakan resmi pesantren.
Jangan menganggap angka historis sebagai ketentuan hukum yang selalu
berlaku. Jangan menanamkan angka secara permanen di dalam kode program.

## 1.9 Keputusan KPI

-   Penilaian dilakukan bulanan.
-   Evaluasi dilakukan triwulanan.
-   Kepala bagian menjadi penilai.
-   HRD menjadi verifikator.
-   Pimpinan memberikan persetujuan akhir.
-   Tahap awal KPI digunakan untuk evaluasi dan pembinaan.
-   KPI tidak otomatis mengubah gaji atau status pegawai.
-   Penghargaan atau hubungan KPI dengan kompensasi hanya dikembangkan
    setelah ada keputusan resmi.

## 1.10 Peran pengguna

-   Administrator.
-   Pimpinan.
-   Kepala Bidang/Bagian.
-   HRD.
-   Keuangan.
-   Pegawai.

Hak akses harus dapat dibatasi berdasarkan peran, unit kerja, dan
hubungan atasan-bawahan.

------------------------------------------------------------------------

# 2. Master Prompt

**Gunakan Master Prompt ini sebagai instruksi dasar pada setiap tahap
pembangunan.**

``` text
# MASTER PROMPT
# PEMBANGUNAN APLIKASI KEPEGAWAIAN PIS

## 1. IDENTITAS DAN PERAN

Anda bertindak sebagai tim pengembang perangkat lunak profesional yang terdiri atas:

- Senior System Analyst
- Software Architect
- Database Engineer
- UI/UX Designer
- Backend Developer
- Frontend Developer
- Quality Assurance Engineer
- Application Security Engineer

Tugas Anda adalah membangun Sistem Informasi Manajemen Kepegawaian (HRIS) untuk Pesantren Imam Syafi'i.

Nama aplikasi: Aplikasi Kepegawaian PIS.

Aplikasi harus benar-benar berfungsi, bukan sekadar prototipe, desain visual, atau simulasi.

## 2. IDENTITAS INSTANSI

Nama: Pesantren Imam Syafi'i.
Lokasi: Sukamakmur, Aceh Besar, Aceh, Indonesia.
Bahasa: Indonesia.
Mata uang: Rupiah (IDR).
Format tanggal: DD-MM-YYYY.
Zona waktu: Asia/Jakarta.

## 3. TUJUAN

Membangun aplikasi terintegrasi untuk mengelola seluruh siklus kepegawaian, mulai dari rekrutmen, penerimaan, administrasi, absensi, perizinan, penggajian, penilaian kinerja, pembinaan, hingga pemberhentian pegawai.

## 4. KEPUTUSAN ARSITEKTUR

- Platform: Web Application.
- Responsif untuk komputer, tablet, dan smartphone.
- Satu database utama.
- Mendukung beberapa unit pendidikan dan bagian.
- Login dan hak akses bertingkat.
- Impor dan ekspor Excel.
- Dokumen PDF.
- Audit trail.
- Backup dan pemulihan data.

Teknologi yang direkomendasikan:
- Backend: Laravel.
- Database: MySQL.
- Frontend: teknologi yang kompatibel dengan Laravel.
- UI: responsive web interface.
- Presensi: GPS, radius lokasi, dan kamera smartphone.
- Arsitektur: modular dan mudah dikembangkan.

Periksa lingkungan pengembangan sebelum menetapkan versi teknologi. Jangan mengganti teknologi tanpa alasan teknis dan dokumentasi.

## 5. MODUL APLIKASI

1. Dashboard.
2. Master Data Pegawai.
3. Struktur Organisasi.
4. Rekrutmen dan Penerimaan Pegawai.
5. Administrasi HRD.
6. Absensi dan Kedisiplinan.
7. Perizinan dan Cuti.
8. Penggajian.
9. Penilaian Kinerja (KPI).
10. Pembinaan Pegawai.
11. Laporan.
12. Pengaturan Sistem.

## 6. KATEGORI DAN STATUS PEGAWAI

Kategori:
- Tetap.
- Harian.
- Khidmah.

Status operasional harus dibedakan dari kategori pegawai. Perubahan status harus memiliki tanggal efektif dan riwayat.

## 7. KEPUTUSAN ABSENSI

- Login dengan akun masing-masing.
- GPS wajib digunakan.
- Presensi hanya diterima dalam radius yang ditetapkan.
- Foto selfie wajib setiap presensi.
- Perangkat HP bebas.
- Waktu presensi menggunakan waktu server.
- Mencegah presensi ganda.
- Merekam koordinat dan waktu.
- Menyimpan bukti foto.
- Mendukung presensi masuk dan pulang.
- Mendukung jadwal kerja berbeda.
- Koreksi absensi melalui atasan langsung kemudian HRD.

Radius GPS, koordinat, jadwal, dan toleransi keterlambatan harus dapat dikonfigurasi.

## 8. KEPUTUSAN PENGELOLAAN HRD

- HRD mengelola administrasi pegawai.
- Kepala bagian menjadi atasan langsung sesuai struktur organisasi.
- Kepala bagian melakukan verifikasi sesuai kewenangannya.
- Pimpinan memberikan persetujuan akhir untuk keputusan yang memerlukan kewenangan pimpinan.
- Semua dokumen dan keputusan memiliki riwayat.

## 9. KEPUTUSAN PENGGAJIAN

Gunakan ketentuan penggajian PIS pada dokumen induk sebagai konfigurasi awal.

Semua komponen penghasilan dan potongan harus dapat dikonfigurasi. Jangan mengunci angka dalam kode. Jika ada aturan belum jelas, tandai dan minta klarifikasi.

## 10. KEPUTUSAN KPI

- Penilaian bulanan.
- Evaluasi triwulanan.
- Kepala bagian sebagai penilai.
- HRD sebagai verifikator.
- Pimpinan sebagai pemberi persetujuan akhir.
- Tahap awal untuk evaluasi dan pembinaan.
- Tidak otomatis menghubungkan KPI dengan kompensasi.

## 11. HAK AKSES

Sistem mendukung Administrator, Pimpinan, Kepala Bidang/Bagian, HRD, Keuangan, dan Pegawai.

Hak akses dapat dikonfigurasi berdasarkan peran, unit, dan kewenangan.

## 12. KEAMANAN

- Password di-hash.
- Validasi seluruh input.
- Proteksi SQL injection dan serangan web umum.
- CSRF protection.
- Pengelolaan sesi aman.
- Pembatasan akses berdasarkan role dan unit.
- Audit trail.
- Soft delete untuk data yang relevan.
- Backup dan restore.
- Proteksi dokumen pribadi.
- Persetujuan sesuai kewenangan.

## 13. STANDAR PENGEMBANGAN

Pembangunan dilakukan dalam delapan tahap.

Setiap tahap harus:
1. Melanjutkan proyek yang sama.
2. Menggunakan database yang sama.
3. Mempertahankan keputusan sebelumnya.
4. Tidak menghapus fitur yang sudah berjalan.
5. Memiliki pengujian.
6. Memiliki dokumentasi.
7. Memiliki daftar pekerjaan selesai dan belum selesai.
8. Memastikan kompatibilitas dengan modul berikutnya.

Jangan membuat database baru untuk setiap tahap.

Jangan membuat data pegawai fiktif sebagai data produksi. Data demonstrasi harus diberi label DEMO.

## 14. PEMERIKSAAN DAN PELAPORAN

Sebelum memulai tahap baru:
- Periksa proyek yang sudah ada.
- Periksa dokumentasi tahap sebelumnya.
- Periksa struktur database.
- Periksa fitur yang sudah selesai.
- Identifikasi risiko perubahan.

Setelah menyelesaikan tahap:
- Laporkan pekerjaan yang selesai.
- Laporkan file yang dibuat atau diubah.
- Laporkan hasil pengujian yang benar-benar dijalankan.
- Laporkan kendala.
- Laporkan fitur yang belum selesai.
- Berikan instruksi untuk tahap berikutnya.

Jangan menyatakan fitur selesai jika belum diuji.

## 15. ATURAN UTAMA

Konsistensi sistem lebih penting daripada kecepatan pembangunan.

Jika terdapat pertentangan antara instruksi baru dan keputusan yang telah disepakati, hentikan perubahan dan minta klarifikasi.

Bangun aplikasi untuk kebutuhan operasional nyata Pesantren Imam Syafi'i dan pengembangan jangka panjang.
```

------------------------------------------------------------------------

# 3. Prompt Tahap 1 --- Analisis dan desain sistem

**Target:** menghasilkan blueprint lengkap sebelum pemrograman.

``` text
# PROMPT TAHAP 1
# ANALISIS KEBUTUHAN DAN DESAIN SISTEM

Gunakan Master Prompt Aplikasi Kepegawaian PIS sebagai pedoman utama.

Bertindak sebagai Senior System Analyst dan Software Architect.

Tugas Anda adalah menyusun rancangan menyeluruh sebelum proses pemrograman dimulai.

## A. Analisis kebutuhan

Identifikasi:
1. Kebutuhan administrasi HRD.
2. Kebutuhan pimpinan.
3. Kebutuhan kepala bagian.
4. Kebutuhan keuangan.
5. Kebutuhan pegawai.
6. Kebutuhan absensi mandiri.
7. Kebutuhan penggajian.
8. Kebutuhan KPI.
9. Kebutuhan laporan.

## B. Analisis proses bisnis

Buat diagram alur untuk:
- Penerimaan pegawai.
- Pengangkatan pegawai.
- Perubahan status pegawai.
- Mutasi dan perubahan jabatan.
- Pengajuan izin.
- Presensi.
- Koreksi absensi.
- Penggajian.
- Penilaian KPI.
- Pembinaan.
- Pemberhentian.

## C. Rancangan database

Buat Entity Relationship Diagram (ERD) dan spesifikasi tabel.

Setiap tabel harus memiliki:
- Nama tabel.
- Nama kolom.
- Tipe data.
- Primary key.
- Foreign key.
- Relasi.
- Aturan validasi.

Pastikan database mampu mendukung seluruh delapan tahap.

## D. Rancangan UI/UX

Buat:
- Struktur navigasi.
- Sidebar.
- Dashboard.
- Halaman data pegawai.
- Formulir input.
- Profil pegawai.
- Halaman absensi.
- Halaman penggajian.
- Halaman KPI.
- Halaman laporan.

## E. Rancangan hak akses

Buat matriks hak akses lengkap berdasarkan role, unit, dan jenis data.

## F. Rancangan teknologi

Tetapkan:
- Framework.
- Database.
- Arsitektur backend.
- Frontend.
- Autentikasi.
- Sistem penyimpanan foto dan dokumen.
- Sistem backup.
- Sistem keamanan.
- Strategi deployment.

## G. Dokumentasi

Buat dokumen:
1. Software Requirements Specification (SRS).
2. Database Design Document.
3. Business Process Document.
4. UI/UX Specification.
5. Access Control Matrix.
6. Technical Architecture Document.
7. Development Roadmap.

## H. Hasil akhir

Tahap 1 selesai jika seluruh rancangan tersedia, saling konsisten, dan dapat digunakan sebagai dasar pembangunan.

Jangan mulai membuat kode aplikasi sebelum blueprint selesai.

Jika terdapat kebutuhan yang belum diketahui, buat daftar pertanyaan klarifikasi dan jangan mengarang kebijakan pesantren.
```

------------------------------------------------------------------------

# 4. Prompt Tahap 2 --- Fondasi sistem dan master data

**Target:** aplikasi memiliki login, pengguna, hak akses, database, data
pegawai, dan organisasi.

``` text
# PROMPT TAHAP 2
# PEMBANGUNAN FONDASI SISTEM

Gunakan Master Prompt dan hasil Tahap 1.

Bangun fondasi aplikasi yang benar-benar berfungsi.

## A. Persiapan proyek

- Periksa lingkungan server.
- Buat struktur proyek.
- Konfigurasikan Laravel dan MySQL sesuai rancangan.
- Siapkan environment configuration.
- Siapkan migration dan seeder.
- Siapkan dokumentasi instalasi.

## B. Autentikasi

Bangun:
- Login.
- Logout.
- Manajemen sesi.
- Penggantian password.
- Lupa password jika layanan email tersedia.
- Pengelolaan akun aktif dan nonaktif.

## C. Manajemen pengguna

Fitur:
- Tambah pengguna.
- Ubah pengguna.
- Nonaktifkan pengguna.
- Reset password.
- Hubungkan akun dengan profil pegawai.
- Pengelolaan role.
- Pengelolaan permission.

## D. Hak akses

Implementasikan RBAC untuk:
- Administrator.
- Pimpinan.
- Kepala Bidang/Bagian.
- HRD.
- Keuangan.
- Pegawai.

Dukung pembatasan data berdasarkan unit dan hubungan atasan-bawahan.

## E. Master data organisasi

Buat pengelolaan:
- Yayasan.
- Pimpinan.
- Bidang.
- Bagian.
- Seksi.
- Unit pendidikan.
- Jabatan.
- Atasan langsung.

Struktur harus dapat berubah tanpa mengubah kode program.

## F. Master data pegawai

Buat fitur:
- Tambah pegawai.
- Ubah data pegawai.
- Lihat profil pegawai.
- Pencarian.
- Filter.
- Pengelompokan.
- Impor Excel.
- Ekspor Excel.
- Unggah foto.
- Unggah dokumen.

Data pegawai mencakup identitas, keluarga, pendidikan, kontak, status, jabatan, penempatan, rekening, NPWP, BPJS, dan dokumen.

Kategori pegawai:
- Tetap.
- Harian.
- Khidmah.

Buat nomor induk pegawai yang unik.

## G. Riwayat pegawai

Simpan:
- Riwayat status.
- Riwayat jabatan.
- Riwayat penempatan.
- Riwayat perubahan data penting.
- Riwayat atasan langsung.

Jangan menimpa riwayat lama ketika ada perubahan.

## H. Audit trail

Catat:
- Pengguna.
- Aktivitas.
- Data yang diubah.
- Nilai sebelum dan sesudah.
- Waktu perubahan.
- Informasi pendukung yang diperlukan.

## I. Pengujian

Uji:
- Login.
- Hak akses.
- Tambah pegawai.
- Edit pegawai.
- Validasi NIP.
- Impor Excel.
- Struktur organisasi.
- Riwayat pegawai.
- Audit trail.

## J. Kriteria selesai

Fondasi dinyatakan selesai apabila administrator dapat mengelola pengguna dan pegawai, setiap role memiliki akses sesuai kewenangannya, dan seluruh data tersimpan dengan benar dalam database.

Jangan lanjut ke Tahap 3 sebelum seluruh fungsi utama Tahap 2 lulus pengujian.
```

------------------------------------------------------------------------

# 5. Prompt Tahap 3 --- Rekrutmen dan administrasi HRD

**Target:** mengelola proses penerimaan hingga administrasi pegawai.

``` text
# PROMPT TAHAP 3
# REKRUTMEN DAN ADMINISTRASI HRD

Gunakan Master Prompt serta seluruh hasil Tahap 1 dan Tahap 2.

Bangun modul rekrutmen dan administrasi kepegawaian yang terintegrasi dengan master data pegawai.

## A. Rekrutmen

Buat pengelolaan:
- Lowongan.
- Data calon pegawai.
- Formulir lamaran.
- Dokumen pelamar.
- Seleksi administrasi.
- Jadwal wawancara.
- Catatan wawancara.
- Penilaian kandidat.
- Keputusan penerimaan.

Status proses:
- Pelamar baru.
- Seleksi administrasi.
- Wawancara.
- Dipertimbangkan.
- Diterima.
- Ditolak.

Pelamar yang diterima dapat dikonversi menjadi pegawai tanpa input ulang.

## B. Administrasi kepegawaian

Buat pengelolaan:
- Perjanjian kerja.
- SK pengangkatan.
- SK penempatan.
- SK perubahan jabatan.
- SK mutasi.
- Perpanjangan kontrak.
- Surat teguran.
- Surat pembinaan.
- Surat pemberhentian.

## C. Kontrak kerja

Fitur:
- Nomor kontrak.
- Tanggal mulai.
- Tanggal berakhir.
- Status kontrak.
- Jenis hubungan kerja.
- Dokumen kontrak.
- Pengingat masa berakhir.
- Riwayat perpanjangan.

Masa percobaan harus dapat dikonfigurasi.

## D. Mutasi dan perubahan jabatan

Setiap perubahan harus menyimpan:
- Data sebelumnya.
- Data baru.
- Tanggal efektif.
- Alasan.
- Pejabat yang menyetujui.
- Dokumen keputusan.

## E. Surat dan dokumen

Sediakan:
- Template dokumen.
- Penomoran surat.
- Pengisian otomatis data pegawai.
- Cetak PDF.
- Arsip digital.
- Riwayat dokumen.

## F. Alur persetujuan

Terapkan persetujuan sesuai jenis keputusan dan kewenangan:
- Atasan langsung.
- HRD.
- Pimpinan.
- Yayasan apabila diperlukan.

Jangan menyamakan seluruh proses persetujuan. Setiap jenis dokumen dapat memiliki alur berbeda.

## G. Pengujian

Uji proses dari calon pegawai hingga menjadi pegawai aktif, pembuatan kontrak, perpanjangan, mutasi, perubahan jabatan, dan pemberhentian.

Pastikan semua perubahan terhubung dengan riwayat pegawai.

## H. Kriteria selesai

Modul rekrutmen dan administrasi HRD dapat digunakan untuk mengelola proses penerimaan sampai seluruh administrasi pegawai secara digital.
```

------------------------------------------------------------------------

# 6. Prompt Tahap 4 --- Absensi, kedisiplinan, dan perizinan

**Target:** presensi mandiri melalui HP dengan GPS radius dan selfie.

``` text
# PROMPT TAHAP 4
# PEMBANGUNAN MODUL ABSENSI DAN PERIZINAN

Gunakan Master Prompt dan seluruh hasil tahap sebelumnya.

Bangun sistem presensi mandiri melalui smartphone dengan GPS dan foto selfie.

## A. Akun pegawai

Setiap pegawai harus dapat:
- Login melalui HP.
- Melihat jadwal kerja.
- Melakukan presensi masuk.
- Melakukan presensi pulang.
- Melihat riwayat kehadiran.
- Mengajukan koreksi absensi.
- Mengajukan izin dan cuti.

## B. Validasi GPS

Ketentuan:
- Ambil koordinat GPS dari perangkat.
- Bandingkan dengan koordinat lokasi kerja yang ditetapkan.
- Hitung jarak dari titik lokasi kerja.
- Terima presensi jika berada dalam radius yang diizinkan.
- Tolak presensi di luar radius kecuali melalui prosedur dispensasi resmi.

Koordinat dan radius harus dapat diatur untuk lokasi kerja yang berbeda.

Sediakan mekanisme penanganan jika GPS tidak tersedia atau perangkat menolak izin lokasi.

Jangan menggunakan lokasi perkiraan sebagai pengganti bukti lokasi presensi.

## C. Foto selfie

- Kamera wajib digunakan setiap presensi.
- Foto disimpan bersama data presensi.
- Foto memiliki keterkaitan dengan pegawai, waktu, dan lokasi presensi.
- Batasi ukuran dan format file.
- Lindungi foto dari akses pengguna yang tidak berwenang.
- Cegah penggunaan foto lama melalui mekanisme pengambilan foto langsung jika didukung perangkat.

Jangan mengklaim adanya deteksi liveness atau anti-spoofing jika belum benar-benar diimplementasikan.

## D. Perangkat

Pegawai boleh menggunakan HP berbeda.

Namun:
- Akun harus terautentikasi.
- Aktivitas perangkat dicatat seperlunya.
- Satu akun tidak boleh melakukan presensi ganda.
- Sistem dapat membatasi sesi bersamaan sesuai kebijakan.

## E. Jadwal kerja

Buat pengaturan:
- Jam masuk.
- Jam pulang.
- Hari kerja.
- Hari libur.
- Shift.
- Toleransi keterlambatan.
- Jadwal berbeda berdasarkan unit dan kategori pegawai.

## F. Rekap kehadiran

Hitung:
- Hadir.
- Terlambat.
- Pulang lebih awal.
- Tidak hadir.
- Izin.
- Sakit.
- Dispensasi.
- Kehadiran khusus.

## G. Koreksi absensi

Alur:
1. Pegawai mengajukan koreksi.
2. Atasan langsung memeriksa.
3. HRD memverifikasi dan menyetujui.
4. Sistem menyimpan hasil koreksi.
5. Audit trail mencatat seluruh proses.

## H. Izin dan cuti

Jenis izin dapat dikonfigurasi:
- Izin pribadi.
- Sakit.
- Keluarga.
- Cuti.
- Dinas.
- Dispensasi.

Sediakan dokumen pendukung dan alur persetujuan.

## I. Integrasi penggajian

Data absensi yang sudah divalidasi menjadi sumber perhitungan penggajian.

Jangan mengubah data absensi yang sudah digunakan dalam penggajian tanpa prosedur koreksi resmi.

## J. Kriteria selesai

Presensi mandiri dapat dilakukan melalui HP dengan GPS dan selfie, rekapitulasi berjalan, proses koreksi berjalan, dan data absensi siap digunakan oleh modul penggajian.
```

------------------------------------------------------------------------

# 7. Prompt Tahap 5 --- Penggajian dan slip gaji

**Target:** perhitungan payroll yang akurat, dapat diverifikasi, dan
terintegrasi.

``` text
# PROMPT TAHAP 5
# PEMBANGUNAN MODUL PENGGAJIAN PIS

Gunakan Master Prompt serta seluruh hasil tahap sebelumnya.

Bangun modul penggajian yang terintegrasi dengan master data pegawai dan absensi.

## A. Master komponen penggajian

Buat konfigurasi:
- Gaji pokok.
- Basis pendidikan.
- Tunjangan keluarga.
- Tunjangan beras.
- Tunjangan transportasi.
- Tunjangan jabatan.
- Tunjangan kehadiran.
- Tunjangan khusus.
- Honorarium.
- Uang snack.
- THR.
- Bonus.
- BPJS.
- PPh 21.
- Potongan ketidakhadiran.
- Potongan keterlambatan.
- Potongan lainnya.

Setiap komponen harus memiliki:
- Nama.
- Kategori penghasilan atau potongan.
- Metode perhitungan.
- Nominal atau persentase.
- Syarat penerima.
- Periode berlaku.
- Status aktif.

## B. Ketentuan PIS

Implementasikan konfigurasi awal berdasarkan dokumen induk.

Ketentuan penting:
- Pegawai Tetap memperoleh Rp51.000 per hari kalender selama masa aktif.
- Ketidakhadiran yang memenuhi ketentuan dikenakan potongan Rp51.000 per hari.
- Potongan keterlambatan Rp106,25 per menit.
- Basis pendidikan SMA Rp700.000, D3 Rp900.000, dan S1 Rp1.100.000.
- Kenaikan tahunan pegawai tetap 5% dari basis terakhir.
- Peningkatan pendidikan D3 ke S1 sebesar 15% dari gaji terakhir.
- Tunjangan pasangan Rp200.000.
- Tunjangan anak Rp100.000 per anak.
- Tunjangan beras berdasarkan jumlah anggota keluarga.
- Tunjangan pasangan tidak diberikan kepada istri apabila suami juga bekerja di pesantren.
- Transportasi berdasarkan jarak dan rentang nominal yang dapat dikonfigurasi.
- Tunjangan kehadiran untuk Tetap dan Khidmah khusus.
- Tunjangan kehadiran MSU/SD menggantikan tunjangan kehadiran lainnya.
- Nilai tunjangan kehadiran MSU/SD Rp270.000 dengan perhitungan proporsional Rp270.000/30/240.
- BPJS Kesehatan menggunakan basis Rp4.200.000, pemberi kerja 4% dan pegawai 1%.
- BPJS Ketenagakerjaan JHT pemberi kerja 3,70%, pegawai 2%, JKK 0,24%, JKM 0,30%.
- Uang snack tidak diberikan kepada Driver.
- Satu pegawai hanya boleh menerima uang snack satu kali pada tanggal dan jadwal yang memenuhi ketentuan.

Ketentuan khusus Khidmah biasa dan Khidmah khusus harus dibedakan.

Jangan menyamakan seluruh kategori pegawai.

Apabila terdapat aturan yang belum ditentukan, sediakan konfigurasi dan ajukan klarifikasi.

## C. Periode penggajian

Buat:
- Pembukaan periode.
- Pengambilan data absensi.
- Perhitungan otomatis.
- Pemeriksaan.
- Koreksi.
- Verifikasi HRD.
- Verifikasi keuangan.
- Persetujuan pimpinan sesuai kewenangan.
- Penguncian periode.

## D. Slip gaji

Buat slip gaji individual yang menampilkan:
- Identitas pegawai.
- Jabatan dan unit.
- Periode.
- Rincian penghasilan.
- Rincian potongan.
- BPJS.
- PPh 21.
- Total penghasilan.
- Total potongan.
- Gaji bersih.

Dukung unduh PDF individual dan batch.

## E. Laporan

Sediakan:
- Rekap gaji.
- Rekap tunjangan.
- Rekap potongan.
- Rekap BPJS.
- Rekap pajak.
- Rekap per unit.
- Riwayat gaji pegawai.
- Daftar penerima transfer.

## F. Kontrol dan audit

- Perhitungan harus dapat ditelusuri.
- Setiap komponen memiliki sumber data.
- Perubahan kebijakan memiliki tanggal berlaku.
- Periode yang telah disetujui tidak dapat diubah sembarangan.
- Koreksi harus memiliki alasan dan persetujuan.
- Tidak boleh terjadi perhitungan ganda.

## G. Pengujian

Uji perhitungan pada berbagai kategori pegawai, variasi absensi, izin, keterlambatan, tunjangan, BPJS, pajak, dan penguncian periode.

Bandingkan hasil aplikasi dengan perhitungan manual menggunakan kasus uji yang sama.

## H. Kriteria selesai

Modul penggajian mampu menghasilkan perhitungan gaji yang akurat, dapat diverifikasi, memiliki riwayat, dan menghasilkan slip gaji PDF.
```

------------------------------------------------------------------------

# 8. Prompt Tahap 6 --- KPI, evaluasi, dan pembinaan

**Target:** penilaian kinerja yang terstruktur dan dapat disesuaikan
dengan jabatan.

``` text
# PROMPT TAHAP 6
# PEMBANGUNAN MODUL KPI DAN PEMBINAAN

Gunakan Master Prompt serta hasil tahap sebelumnya.

Bangun sistem penilaian kinerja pegawai yang dapat dikonfigurasi berdasarkan jabatan dan unit kerja.

## A. Pengaturan KPI

Sediakan:
- Periode penilaian.
- Indikator.
- Bobot.
- Target.
- Standar penilaian.
- Skala nilai.
- Deskripsi indikator.

Indikator harus dapat berbeda untuk pegawai administrasi, keuangan, kebersihan, pengajar, dan unit lainnya.

## B. Proses penilaian

Periode:
- Penilaian bulanan.
- Evaluasi triwulanan.

Alur:
1. Kepala bagian melakukan penilaian.
2. HRD memverifikasi.
3. Pimpinan menyetujui.

## C. Penilaian

Setiap penilaian berisi:
- Pegawai yang dinilai.
- Penilai.
- Periode.
- Indikator.
- Target.
- Realisasi.
- Skor.
- Catatan.
- Rekomendasi.

## D. Hasil penilaian

Sediakan:
- Nilai akhir.
- Predikat.
- Grafik perkembangan.
- Perbandingan periode.
- Riwayat penilaian.
- Rekomendasi pembinaan.

## E. Pembinaan pegawai

Kelola:
- Konseling.
- Pembinaan kedisiplinan.
- Teguran.
- Evaluasi.
- Pelatihan.
- Penghargaan.
- Tindak lanjut.

Catat tanggal, petugas, alasan, hasil, dan dokumen pendukung.

## F. Integrasi

Hubungkan KPI dengan:
- Profil pegawai.
- Jabatan.
- Unit kerja.
- Riwayat pembinaan.
- Laporan HRD.

Tahap awal KPI hanya untuk evaluasi dan pembinaan. Jangan otomatis menghubungkan hasil KPI dengan pemotongan gaji atau perubahan status pegawai.

## G. Kriteria selesai

Kepala bagian dapat melakukan penilaian, HRD dapat memverifikasi, pimpinan dapat menyetujui, dan hasil penilaian dapat dilihat sesuai kewenangan.
```

------------------------------------------------------------------------

# 9. Prompt Tahap 7 --- Dashboard, laporan, dan notifikasi

**Target:** monitoring manajemen dan pelaporan terpadu.

``` text
# PROMPT TAHAP 7
# PEMBANGUNAN DASHBOARD DAN LAPORAN TERINTEGRASI

Gunakan Master Prompt dan seluruh modul yang sudah dibangun.

Bangun dashboard manajemen dan pusat pelaporan.

## A. Dashboard pimpinan

Tampilkan:
- Total pegawai.
- Pegawai aktif.
- Distribusi pegawai.
- Pegawai masuk dan keluar.
- Kehadiran.
- Kedisiplinan.
- Masa kontrak.
- Penggajian.
- Hasil KPI.
- Ringkasan pembinaan.

## B. Dashboard HRD

Tampilkan:
- Data pegawai.
- Dokumen yang belum lengkap.
- Kontrak mendekati berakhir.
- Pegawai baru.
- Perubahan status.
- Pengajuan izin.
- Koreksi absensi.
- Data KPI yang belum dinilai.

## C. Dashboard kepala bagian

Tampilkan pegawai dalam unitnya:
- Kehadiran.
- Keterlambatan.
- Perizinan.
- Penilaian KPI.
- Catatan pembinaan.

## D. Dashboard keuangan

Tampilkan:
- Ringkasan penggajian.
- Jumlah penerima.
- Total penghasilan.
- Total potongan.
- Rekap BPJS.
- Rekap PPh 21.
- Status persetujuan payroll.

## E. Dashboard pegawai

Pegawai dapat melihat:
- Profil sendiri.
- Jadwal kerja.
- Riwayat absensi.
- Pengajuan izin.
- Status persetujuan.
- Slip gaji sendiri.
- Hasil KPI sendiri sesuai kebijakan.

## F. Laporan

Buat laporan:
1. Data pegawai.
2. Pegawai berdasarkan unit.
3. Pegawai berdasarkan status.
4. Riwayat masa kerja.
5. Kontrak kerja.
6. Absensi.
7. Keterlambatan.
8. Izin dan cuti.
9. Penggajian.
10. BPJS.
11. PPh 21.
12. KPI.
13. Pembinaan.
14. Pegawai masuk dan keluar.

## G. Filter dan ekspor

Dukung:
- Filter tanggal.
- Filter periode.
- Filter unit.
- Filter status.
- Filter pegawai.
- Ekspor Excel.
- Ekspor PDF.
- Cetak laporan.

## H. Notifikasi

Buat notifikasi untuk:
- Kontrak hampir berakhir.
- Pengajuan izin.
- Koreksi absensi.
- Persetujuan yang tertunda.
- Jadwal penilaian KPI.
- Dokumen yang perlu diperbarui.
- Periode penggajian.

Notifikasi harus memperhatikan hak akses dan tidak membocorkan informasi sensitif.

## I. Kriteria selesai

Setiap role memperoleh dashboard yang sesuai kewenangannya, laporan mengambil data dari database yang sama, dan hasil ekspor konsisten dengan data sumber.
```

------------------------------------------------------------------------

# 10. Prompt Tahap 8 --- Pengujian, keamanan, dan deployment

**Target:** memastikan aplikasi siap digunakan secara operasional.

``` text
# PROMPT TAHAP 8
# PENGUJIAN MENYELURUH DAN PENERAPAN APLIKASI

Gunakan Master Prompt dan seluruh hasil Tahap 1 sampai Tahap 7.

Bertindak sebagai QA Engineer, Security Engineer, dan Deployment Engineer.

Tugas Anda adalah memastikan seluruh aplikasi siap digunakan secara operasional.

## A. Pengujian fungsional

Uji seluruh modul:
- Login.
- Pengguna.
- Hak akses.
- Master pegawai.
- Organisasi.
- Rekrutmen.
- Administrasi HRD.
- Absensi GPS.
- Foto selfie.
- Perizinan.
- Penggajian.
- Slip gaji.
- KPI.
- Pembinaan.
- Dashboard.
- Laporan.

## B. Pengujian integrasi

Pastikan:
- Data pegawai terhubung dengan akun.
- Struktur organisasi terhubung dengan hak akses.
- Absensi terhubung dengan penggajian.
- Izin terhubung dengan absensi.
- Penggajian terhubung dengan BPJS dan pajak.
- KPI terhubung dengan pegawai.
- Seluruh aktivitas tercatat dalam audit trail.

## C. Pengujian keamanan

Uji:
- Login.
- Hak akses.
- Pembatasan data antarunit.
- Proteksi dokumen.
- Password.
- Session management.
- CSRF.
- SQL injection.
- Upload file.
- Akses langsung URL.
- Percobaan akses data gaji pegawai lain.

## D. Pengujian absensi

Uji:
- GPS di dalam radius.
- GPS di luar radius.
- GPS tidak aktif.
- Foto selfie.
- Presensi ganda.
- Perangkat berbeda.
- Perbedaan waktu perangkat dan server.
- Koreksi absensi.
- Presensi ketika jaringan bermasalah.

## E. Pengujian penggajian

Bandingkan perhitungan aplikasi dengan perhitungan manual.

Uji berbagai kasus:
- Pegawai Tetap.
- Pegawai Harian.
- Khidmah biasa.
- Khidmah khusus.
- Pegawai baru.
- Pegawai keluar.
- Keterlambatan.
- Ketidakhadiran.
- Izin.
- Perubahan tunjangan.
- BPJS.
- PPh 21.
- Koreksi payroll.

## F. Pengujian performa

Pastikan aplikasi mampu digunakan oleh beberapa pengguna secara bersamaan.

Uji kecepatan akses, proses pencarian, pemrosesan laporan, dan pengolahan data.

## G. Backup dan pemulihan

Siapkan:
- Backup database.
- Backup dokumen.
- Prosedur restore.
- Jadwal backup.
- Pengujian pemulihan.
- Dokumentasi tanggap insiden.

## H. Deployment

Siapkan aplikasi untuk server produksi.

Lakukan:
- Konfigurasi domain.
- HTTPS.
- Database produksi.
- Penyimpanan file.
- Konfigurasi email jika tersedia.
- Pengaturan keamanan.
- Backup otomatis.
- Monitoring aplikasi.

Jangan menaruh kredensial produksi di source code atau dokumentasi publik.

## I. Dokumentasi

Buat:
1. Panduan administrator.
2. Panduan HRD.
3. Panduan kepala bagian.
4. Panduan keuangan.
5. Panduan pegawai.
6. Panduan instalasi.
7. Panduan backup dan restore.
8. Dokumentasi database.
9. Dokumentasi API jika tersedia.
10. Catatan versi dan perubahan aplikasi.

## J. Kriteria penerimaan

Aplikasi dinyatakan siap operasional apabila:
- Semua modul utama berfungsi.
- Integrasi berhasil.
- Pengujian kritis lulus.
- Tidak ada celah keamanan kritis yang belum ditangani.
- Data dapat dicadangkan dan dipulihkan.
- Pengguna dapat menjalankan proses bisnis.
- Dokumentasi tersedia.
- Pimpinan dan pengelola telah melakukan uji penerimaan pengguna (UAT).

Jangan menyatakan aplikasi siap produksi jika masih terdapat masalah kritis.

Sampaikan laporan hasil pengujian secara jujur dan terperinci.
```

------------------------------------------------------------------------

# 11. Prompt pengendali perpindahan tahap

Gunakan prompt ini setiap kali berpindah dari satu tahap ke tahap
berikutnya.

``` text
Saya akan melanjutkan pembangunan Aplikasi Kepegawaian PIS ke tahap berikutnya.

Sebelum bekerja:

1. Periksa seluruh file dan kode proyek yang sudah ada.
2. Baca dokumentasi spesifikasi induk.
3. Periksa database dan struktur tabel.
4. Periksa seluruh fitur yang sudah selesai.
5. Pastikan tidak ada pekerjaan sebelumnya yang hilang.
6. Identifikasi ketergantungan dengan modul sebelumnya.

Selanjutnya, kerjakan tahap berikutnya sesuai prompt yang telah disusun.

Ketentuan:
- Jangan membuat proyek baru.
- Jangan mengganti database.
- Jangan mengubah keputusan bisnis yang sudah disepakati.
- Jangan merusak fitur yang sudah berjalan.
- Jangan membuat fitur tiruan.
- Uji seluruh fungsi yang ditambahkan.
- Perbarui dokumentasi dan catatan progres.

Di akhir pekerjaan, berikan laporan:
1. Pekerjaan yang selesai.
2. File yang dibuat atau diubah.
3. Database yang ditambahkan atau diubah.
4. Hasil pengujian.
5. Kendala yang ditemukan.
6. Pekerjaan yang belum selesai.
7. Instruksi untuk melanjutkan tahap selanjutnya.

Mulai pekerjaan hanya setelah pemeriksaan proyek selesai.
```

------------------------------------------------------------------------

# 12. Checklist progres pembangunan

Gunakan checklist berikut untuk mencatat status proyek. Checklist ini
merupakan catatan manual dan harus diperbarui berdasarkan hasil
pekerjaan yang benar-benar selesai dan diuji.

-   [ ] **Tahap 1 --- Analisis dan desain sistem**
    -   [ ] SRS.
    -   [ ] ERD dan desain database.
    -   [ ] Diagram proses bisnis.
    -   [ ] Desain UI/UX.
    -   [ ] Matriks hak akses.
    -   [ ] Arsitektur teknis.
-   [ ] **Tahap 2 --- Fondasi sistem**
    -   [ ] Login dan logout.
    -   [ ] Manajemen pengguna.
    -   [ ] RBAC dan pembatasan unit.
    -   [ ] Database dan migration.
    -   [ ] Master data pegawai.
    -   [ ] Struktur organisasi.
    -   [ ] Riwayat pegawai.
    -   [ ] Impor Excel.
    -   [ ] Audit trail.
-   [ ] **Tahap 3 --- Administrasi HRD**
    -   [ ] Rekrutmen.
    -   [ ] Seleksi dan wawancara.
    -   [ ] Kontrak kerja.
    -   [ ] SK kepegawaian.
    -   [ ] Mutasi dan perubahan jabatan.
    -   [ ] Arsip dokumen.
-   [ ] **Tahap 4 --- Absensi dan perizinan**
    -   [ ] Presensi masuk dan pulang.
    -   [ ] GPS radius.
    -   [ ] Foto selfie.
    -   [ ] Jadwal kerja.
    -   [ ] Rekap kehadiran.
    -   [ ] Koreksi absensi.
    -   [ ] Izin dan cuti.
-   [ ] **Tahap 5 --- Penggajian**
    -   [ ] Komponen gaji.
    -   [ ] Konfigurasi kebijakan.
    -   [ ] Perhitungan payroll.
    -   [ ] BPJS.
    -   [ ] PPh 21.
    -   [ ] Verifikasi dan persetujuan.
    -   [ ] Penguncian periode.
    -   [ ] Slip gaji PDF.
-   [ ] **Tahap 6 --- KPI dan pembinaan**
    -   [ ] Indikator KPI.
    -   [ ] Penilaian bulanan.
    -   [ ] Verifikasi HRD.
    -   [ ] Persetujuan pimpinan.
    -   [ ] Evaluasi triwulanan.
    -   [ ] Riwayat pembinaan.
-   [ ] **Tahap 7 --- Dashboard dan laporan**
    -   [ ] Dashboard pimpinan.
    -   [ ] Dashboard HRD.
    -   [ ] Dashboard kepala bagian.
    -   [ ] Dashboard keuangan.
    -   [ ] Dashboard pegawai.
    -   [ ] Laporan dan ekspor.
    -   [ ] Notifikasi.
-   [ ] **Tahap 8 --- Pengujian dan deployment**
    -   [ ] Pengujian fungsional.
    -   [ ] Pengujian integrasi.
    -   [ ] Pengujian keamanan.
    -   [ ] Pengujian performa.
    -   [ ] Backup dan restore.
    -   [ ] UAT.
    -   [ ] Deployment.
    -   [ ] Dokumentasi operasional.

------------------------------------------------------------------------

# 13. Panduan penggunaan

## 13.1 Urutan penggunaan prompt

1.  Kirim Master Prompt sebagai instruksi utama proyek.
2.  Kirim Prompt Tahap 1 dan selesaikan blueprint.
3.  Setelah blueprint disetujui, kirim Prompt Tahap 2.
4.  Lanjutkan Tahap 3 sampai Tahap 7 secara berurutan.
5.  Gunakan Prompt Pengendali setiap kali berpindah tahap.
6.  Jalankan Tahap 8 untuk pengujian menyeluruh dan persiapan
    operasional.

**Jangan mengirim seluruh prompt untuk dieksekusi sekaligus.** Semua
prompt disiapkan sejak awal agar arah proyek jelas, tetapi
implementasinya tetap dilakukan satu tahap pada satu waktu.

## 13.2 Dokumen yang harus dipelihara selama proyek

Simpan dan perbarui setidaknya:

-   `MASTER_SPECIFICATION.md` --- keputusan dan spesifikasi induk.
-   `DATABASE_DESIGN.md` --- struktur database dan relasi.
-   `BUSINESS_PROCESS.md` --- proses bisnis.
-   `ACCESS_CONTROL.md` --- hak akses.
-   `DEVELOPMENT_PROGRESS.md` --- progres dan kendala.
-   `CHANGELOG.md` --- catatan perubahan versi.
-   `TEST_REPORT.md` --- hasil pengujian.

Dokumen tersebut harus berada bersama proyek atau lokasi dokumentasi
yang disepakati agar dapat dibaca kembali saat melanjutkan pekerjaan.

## 13.3 Prinsip penerimaan setiap tahap

Satu tahap hanya boleh dinyatakan selesai jika:

1.  Fitur sesuai ruang lingkup telah dibuat.
2.  Fitur dapat dijalankan.
3.  Pengujian yang relevan telah dilakukan.
4.  Hasil pengujian dicatat.
5.  Tidak ada masalah kritis yang belum diselesaikan.
6.  Dokumentasi diperbarui.
7.  Pekerjaan berikutnya dapat dilanjutkan tanpa merusak hasil
    sebelumnya.

------------------------------------------------------------------------

## Penutup

Dokumen ini menjadi pedoman induk pembangunan **Aplikasi Kepegawaian
Pesantren Imam Syafi'i**.

Tujuan akhirnya adalah membangun satu sistem manajemen kepegawaian yang
terintegrasi, aman, mudah digunakan, dapat dipertanggungjawabkan, dan
mampu mendukung kebutuhan administrasi serta pengelolaan SDM pesantren
dalam jangka panjang.

**Prinsip utama: satu data pegawai, satu database, seluruh proses saling
terhubung.**

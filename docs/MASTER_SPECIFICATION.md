# MASTER SPECIFICATION — Aplikasi Kepegawaian PIS

| Atribut | Nilai |
|---|---|
| Nama aplikasi | Aplikasi Kepegawaian PIS |
| Instansi | Pesantren Imam Syafi'i |
| Lokasi | Sukamakmur, Aceh Besar, Aceh, Indonesia |
| Versi dokumen | 1.0 |
| Tanggal | 18-11-2026 |
| Status | Disetujui sebagai dasar Tahap 2 (menunggu klarifikasi pada `CLARIFICATIONS.md`) |
| Sumber | `Prompt_Induk_Aplikasi_Kepegawaian_PIS.md` |
| Bahasa | Indonesia |
| Mata uang | Rupiah (IDR) |
| Format tanggal | DD-MM-YYYY |
| Zona waktu | Asia/Jakarta (WIB, UTC+7) |

## 1. Tujuan

Dokumen ini merangkum keputusan induk proyek yang tidak boleh diubah tanpa persetujuan. Seluruh dokumen lain (`SRS.md`, `DATABASE_DESIGN.md`, `BUSINESS_PROCESS.md`, `UI_UX_SPECIFICATION.md`, `ACCESS_CONTROL.md`, `TECHNICAL_ARCHITECTURE.md`, `DEVELOPMENT_ROADMAP.md`) wajib konsisten dengan dokumen ini.

Tujuan sistem: mengelola seluruh siklus kepegawaian Pesantren Imam Syafi'i — rekrutmen, penerimaan, administrasi, absensi, perizinan, penggajian, penilaian kinerja, pembinaan, hingga pemberhentian — dalam satu database terpusat dan satu standar desain.

## 2. Keputusan yang mengikat

| Komponen | Keputusan |
|---|---|
| Platform | Web Application (responsif: komputer, tablet, HP) |
| Cakupan | Terintegrasi, satu database utama |
| Database | Database baru dan terpusat; data awal diimpor dari Excel |
| Metode absensi | Presensi mandiri oleh pegawai |
| Validasi lokasi | GPS dengan radius lokasi kerja |
| Bukti presensi | Foto selfie wajib setiap presensi |
| Perangkat | Pegawai boleh memakai HP berbeda |
| Koreksi absensi | Atasan langsung → HRD |
| Teknologi | Laravel 13 + MySQL 8.4, frontend Blade/Livewire/Tailwind (lihat `TECHNICAL_ARCHITECTURE.md`) |
| Autentikasi | Akun per pegawai, sesi aman, RBAC |
| Dokumen | Ekspor PDF, impor/ekspor Excel |

## 3. Prinsip pembangunan

1. Satu proyek, satu database, satu standar desain.
2. Seluruh modul saling terintegrasi; master pegawai adalah sumber data utama.
3. Pembangunan bertahap (8 tahap); setiap tahap diuji sebelum lanjut.
4. Tidak membuat proyek/database baru pada setiap tahap.
5. Tidak menghapus fitur yang sudah berjalan tanpa alasan dan persetujuan.
6. Perubahan penting memiliki riwayat dan audit trail.
7. Kebijakan yang dapat berubah disimpan sebagai konfigurasi dengan tanggal berlaku.
8. Data demonstrasi diberi label `DEMO` dan tidak dianggap data produksi.
9. Tidak mengklaim fitur (mis. liveness detection, anti-spoofing) yang belum diimplementasikan.
10. Tidak menyatakan fitur selesai sebelum diuji.

## 4. Kategori dan status pegawai

**Kategori (atribut tetap pada master pegawai):**

- `tetap`
- `harian`
- `khidmah` — dengan sub-jenis `biasa` atau `khusus` (keduanya wajib dibedakan dalam penggajian).

**Status operasional (berubah menurut waktu, wajib memiliki tanggal efektif + alasan + riwayat):**

- `aktif`
- `percobaan`
- `cuti_izin`
- `nonaktif_sementara`
- `mengundurkan_diri`
- `diberhentikan`

Kategori tidak boleh dipakai sebagai pengganti status, dan sebaliknya.

## 5. Peran pengguna

`administrator`, `pimpinan`, `kepala_bagian`, `hrd`, `keuangan`, `pegawai`.

Hak akses dibatasi berdasarkan peran, unit kerja (termasuk turunan unit), dan hubungan atasan–bawahan. Rincian pada `ACCESS_CONTROL.md`.

## 6. Modul

1. Dashboard
2. Master Data Pegawai
3. Struktur Organisasi
4. Rekrutmen dan Penerimaan
5. Administrasi HRD
6. Absensi dan Kedisiplinan
7. Perizinan dan Cuti
8. Penggajian
9. Penilaian Kinerja (KPI)
10. Pembinaan Pegawai
11. Laporan
12. Pengaturan Sistem

## 7. Ketentuan absensi

1. Presensi hanya melalui akun terautentikasi milik pegawai.
2. GPS wajib; presensi diterima hanya dalam radius lokasi kerja yang ditetapkan.
3. Foto selfie wajib, diambil langsung pada saat presensi.
4. Waktu presensi memakai waktu server (`Asia/Jakarta`), bukan jam perangkat.
5. Presensi ganda dicegah pada tingkat basis data (unique constraint) dan aplikasi.
6. Koordinat, jarak, waktu, perangkat, dan foto disimpan.
7. Radius, koordinat, jadwal, shift, dan toleransi keterlambatan dapat dikonfigurasi per lokasi/unit.
8. Jika GPS/kamera tidak tersedia, sistem menolak presensi normal dan menawarkan prosedur dispensasi yang tercatat (bukan presensi "seolah valid").
9. Sistem **tidak** mengklaim deteksi liveness/anti-spoofing. Yang tersedia: pengambilan foto langsung melalui kamera (capture attribute), batas waktu unggah, dan pencatatan metadata perangkat.
10. Koreksi absensi: pegawai mengajukan → atasan langsung memeriksa → HRD memverifikasi/menyetujui → audit trail.

## 8. Ketentuan pengelolaan HRD

- HRD mengelola administrasi pegawai dan verifikasi administrasi.
- Kepala bagian adalah atasan langsung sesuai struktur organisasi dan melakukan verifikasi/penilaian.
- Pimpinan memberi persetujuan akhir untuk keputusan yang memerlukan kewenangannya.
- Setiap keputusan/dokumen memiliki riwayat dan lampiran dokumen.
- Pemohon tidak boleh menyetujui pengajuannya sendiri.

## 9. Ketentuan penggajian (konfigurasi awal)

> Seluruh angka adalah **konfigurasi awal** yang disimpan di tabel konfigurasi dengan `effective_from`. Angka **tidak boleh** ditanam permanen di kode. Sebelum payroll produksi, seluruh rumus wajib diuji dan dikonfirmasi (lihat `CLARIFICATIONS.md`).

| Komponen | Nilai awal |
|---|---|
| Gaji harian pegawai tetap | Rp51.000 / hari kalender aktif |
| Potongan ketidakhadiran | Rp51.000 / hari |
| Potongan keterlambatan | Rp106,25 / menit |
| Basis pendidikan SMA | Rp700.000 |
| Basis pendidikan D3 | Rp900.000 |
| Basis pendidikan S1 | Rp1.100.000 |
| Kenaikan tahunan pegawai tetap | 5% dari basis terakhir |
| Peningkatan pendidikan D3 → S1 | 15% dari gaji terakhir |
| Tunjangan pasangan | Rp200.000 |
| Tunjangan anak | Rp100.000 / anak |
| Transportasi 1–5 km | Rp5.000 |
| Transportasi >5–10 km | Rp10.000 |
| Transportasi jarak lebih jauh | Konfigurabel s.d. Rp20.000 |
| Tunjangan kehadiran MSU/SD | Rp270.000 |
| Rumus proporsional MSU/SD | Rp270.000 / 30 / 240 (tafsir teknis menunggu klarifikasi K-01) |
| BPJS Kesehatan — basis awal | Rp4.200.000 |
| BPJS Kesehatan — pemberi kerja | 4% |
| BPJS Kesehatan — pegawai | 1% |
| JHT — pemberi kerja | 3,70% |
| JHT — pegawai | 2% |
| JKK — pemberi kerja | 0,24% |
| JKM — pemberi kerja | 0,30% |

Aturan tambahan yang mengikat:

- Tunjangan kehadiran berlaku untuk pegawai Tetap dan Khidmah sesuai kebijakan yang dikonfigurasi.
- Tunjangan kehadiran MSU/SD menggantikan tunjangan kehadiran lain (tidak dobel).
- Tunjangan beras berdasarkan jumlah anggota keluarga; tidak diberikan kepada pegawai lajang.
- Tunjangan pasangan tidak diberikan kepada istri bila suami juga bekerja di pesantren.
- Tunjangan jabatan utama/tambahan/istimewa dapat dikonfigurasi.
- BPJS Kesehatan dan BPJS Ketenagakerjaan memiliki kepesertaan dan basis perhitungan sendiri.
- Uang snack tidak diberikan kepada pegawai berstatus Driver.
- Satu pegawai maksimal menerima satu uang snack per tanggal per jadwal yang memenuhi syarat.
- Khidmah biasa dan Khidmah khusus dibedakan.
- Masa percobaan dan ketentuan penggajiannya dapat dikonfigurasi.
- PPh 21 dan BPJS mengikuti peraturan yang berlaku; tarif disimpan sebagai konfigurasi bertanggal, bukan konstanta kode.

## 10. Ketentuan KPI

- Penilaian bulanan; evaluasi triwulanan.
- Penilai: kepala bagian. Verifikator: HRD. Persetujuan akhir: pimpinan.
- Tahap awal: KPI hanya untuk evaluasi dan pembinaan.
- KPI **tidak** otomatis mengubah gaji atau status pegawai.
- Hubungan KPI–kompensasi hanya dikembangkan setelah keputusan resmi.

## 11. Keamanan wajib

Password di-hash (bcrypt/argon2id), validasi seluruh input, proteksi SQL injection & XSS, CSRF, sesi aman, otorisasi per role/unit, audit trail, soft delete untuk data relevan, backup & restore teruji, proteksi dokumen pribadi (penyimpanan privat + signed URL), persetujuan sesuai kewenangan, dan pemisahan tugas (pemohon ≠ penyetuju, penghitung payroll ≠ pemberi persetujuan).

## 12. Istilah

| Istilah | Arti |
|---|---|
| NIP | Nomor Induk Pegawai, unik |
| SK | Surat Keputusan |
| KPI | Key Performance Indicator (indikator kinerja) |
| Payroll run | Satu proses perhitungan gaji untuk satu periode |
| Snapshot absensi | Rekap absensi yang dikunci sebagai input payroll |
| Scope data | Batas data yang boleh diakses (self/unit/all) |
| DEMO | Label untuk data demonstrasi, bukan produksi |

## 13. Perubahan dokumen

Setiap perubahan keputusan pada dokumen ini wajib: (a) mendapat persetujuan, (b) dicatat di `CHANGELOG.md`, (c) memiliki alasan teknis/bisnis, dan (d) tidak merusak fitur yang sudah berjalan.

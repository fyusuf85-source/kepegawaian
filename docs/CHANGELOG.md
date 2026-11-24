# CHANGELOG — Aplikasi Kepegawaian PIS

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/) dan versi semantik.

## [0.8.0] — 22-11-2026 — Tahap 8: Finalisasi, Keamanan, & Deployment

### Ditambahkan
- Modul KPI & Pembinaan (Tahap 6): Migrasi tabel `kpi_*` dan `coaching_records`, template indikator berbobot 100%, alur penilaian Penilai -> Verifikasi HRD -> Persetujuan Pimpinan, catatan pembinaan dengan persetujuan.
- Dashboard & Laporan (Tahap 7): Dashboard khusus peran dengan metrik real-time (KPI avg, payroll net, pending approval, employee stats), laporan rekap absensi bulanan, laporan payroll per periode dengan ekspor Excel terpadu.
- Notifikasi sistem untuk KPI dan Pembinaan (Tahap 7.3).
- Pengujian & Keamanan (Tahap 8): 82 pengujian otomatis (398 asersi) lulus 100%. Proteksi IDOR pada profil, slip gaji, dan laporan. Pengalihan tamu ke login. Masking NIK tanpa izin sensitif.
- Panduan Deployment (`docs/DEPLOYMENT_GUIDE.md`): Langkah instalasi produksi, Supervisor worker, dan checklist keamanan.

### Diperbaiki
- Ekspor `PayrollReportExport` diperbarui untuk mendukung sumber data `PayrollPeriod` maupun `PayrollRun` secara polimorfik.
- Pencegahan penilaian diri sendiri pada modul KPI.
- Sinkronisasi `KpiTemplateSeeder` ke `DatabaseSeeder`.

---

## [0.5.0] — 22-11-2026 — Tahap 5: Penggajian (Payroll)

### Ditambahkan
- Mesin Payroll: Snapshot absensi, kalkulasi gaji harian/bulanan, tunjangan jabatan/pendidikan/transportasi/masa kerja.
- Kalkulator BPJS: JKK/JKM (Pemberi Kerja), JHT (Pemberi Kerja + Pegawai), BPJS Kesehatan.
- Kalkulator PPh 21: Mendukung kategori TER A/B/C sesuai tarif resmi 2024, potongan pajak ditanggung yayasan/pegawai.
- Alur Persetujuan Payroll: HRD -> Keuangan -> Pimpinan.
- Slip Gaji PDF Privat: Terintegrasi dengan sistem penyimpanan berkas aman.
- Penyesuaian Gaji (Manual Adjustment): Penambahan/potongan luar sistem dengan alur persetujuan.
- Laporan Operasional: Rekap transfer bank, BPJS, dan Pajak.

---

## [0.4.0] — 18-11-2026 — Tahap 4: Presensi dan Izin/Cuti

### Ditambahkan
- Presensi GPS: Validasi radius kantor, akurasi sinyal, dan foto selfie wajib.
- Idempotensi Presensi: Mencegah data ganda akibat gangguan jaringan.
- Penanganan Shift Lintas Malam: Otomatisasi baris absensi untuk jam kerja melewati pukul 00:00.
- Modul Izin/Cuti: Pengajuan mandiri, validasi saldo cuti tahunan, lampiran dokumen sakit, alur persetujuan Atasan -> HRD.
- Koreksi & Dispensasi: Perubahan data absensi lewat jalur persetujuan.

---

## [0.3.0] — 18-11-2026 — Tahap 3: Rekrutmen dan Administrasi HRD

### Ditambahkan
- 3 migration baru: rekrutmen (`job_vacancies`, `applicants`, `applicant_documents`, `interviews`, `applicant_scores`), administrasi HRD (`employment_contracts`, `decrees`, `mutations`, `employee_letters`), persetujuan/notifikasi (`approval_flows`, `approval_flow_steps`, `approval_instances`, `approval_actions`, `notifications`).
- Rekrutmen: CRUD lowongan + status buka/tutup, pelamar + unggah dokumen privat, tahapan seleksi, jadwal & hasil wawancara, penilaian berbobot dengan validasi total 100, keputusan penerimaan.
- Konversi pelamar → pegawai tanpa input ulang identitas: NIP otomatis, cegah konversi ganda dan NIK duplikat, penempatan awal + riwayat status.
- Kontrak kerja: draf, perpanjangan tertaut ke kontrak lama, PDF dari template, status sesuai desain DB, pengingat H-30/H-14/H-7.
- SK: jenis pengangkatan/penempatan/perubahan jabatan/mutasi/pemberhentian, PDF, alur persetujuan, penerbitan SK dari mutasi.
- Mutasi: usulan perubahan unit/jabatan/atasan, persetujuan dua tingkat, penerapan menutup penempatan aktif H-1 dan membuat baris penempatan baru.
- Surat: teguran/pembinaan/pemberhentian/keterangan, PDF, persetujuan, penandaan terkirim.
- Mesin persetujuan generik 4 tabel: langkah per jenis dokumen, cakupan `all`/`unit`/`self`, larangan menyetujui pengajuan sendiri, riwayat aksi.
- Notifikasi dalam aplikasi + badge navigasi, dedupe pengingat kontrak, tanda baca.
- Perintah `contracts:remind` dan jadwal harian 07:00 Asia/Jakarta.
- Seeder alur persetujuan (`contract`, `decree`, `mutation`, `letter`) dan template dokumen `KONTRAK-UMUM`, `SK-UMUM`, `SURAT-UMUM`.
- 9 pengujian baru/berubah: 38 pengujian otomatis (213 assertion) lulus pada MySQL `pis_kepegawaian_test`.

### Diperbaiki
- Lowongan yang dibuka hari ini dianggap belum dibuka akibat perbandingan UTC vs Asia/Jakarta (`JobVacancy::isOpen`).
- Pengingat kontrak salah hitung hari karena selisih zona waktu (`ContractService::sendReminders`).
- Unduhan PDF gagal (HTTP 500) ketika nomor dokumen memuat `/`; nama berkas kini dibersihkan lewat `Format::filename()`.
- Asumsi urutan riwayat penempatan pada pengujian diselaraskan dengan relasi `orderByDesc`.

### Catatan
- Format nomor resmi NIP/kontrak/SK/surat masih sementara (`KTR/SK/SRT/{tahun}/{urut}`) sampai klarifikasi K-18 dijawab.
- Langkah persetujuan Yayasan belum di-seed karena menunggu K-26.
- Durasi masa percobaan belum dihitung otomatis (K-08); tanggal probation diisi manual.
- Tampilan PDF dan uji peramban nyata belum ditinjau manusia; notifikasi baru dalam aplikasi (K-34).

---

## [0.2.0] — 18-11-2026 — Tahap 2: Fondasi Sistem

### Ditambahkan
- Proyek Laravel 13.43.0 + PHP 8.3.36 + MySQL 8.4.9, Blade + Tailwind CSS 4 + Vite.
- 18 migration MySQL: pengguna/akses, organisasi, pegawai, data pribadi, administrasi pegawai, jadwal, pengaturan, sistem.
- Autentikasi: login username/email, pembatasan percobaan, riwayat login, akun nonaktif, kewajiban ganti sandi, logout.
- RBAC: 6 peran, 114 permission (dibangkitkan dari `ACCESS_CONTROL.md`), `Gate::before`, middleware `permission`, cakupan unit organisasi.
- CRUD master: unit organisasi (hierarki + level otomatis), jabatan, lokasi kerja, jenjang pendidikan, shift, jadwal kerja, penugasan jadwal, hari libur, template dokumen.
- Master pegawai: CRUD, NIP otomatis `PIS-{tahun}-{urut:4}`, NIK terenkripsi + hash unik, foto privat, data keluarga, pendidikan, rekening, dokumen + verifikasi, riwayat status & penempatan.
- Profil penggajian: pendidikan, tanggungan, status pajak, BPJS/NPWP terenkripsi, nilai dasar khusus dengan alasan (khusus Keuangan).
- Impor dan ekspor pegawai berbasis Excel, template resmi, pencatatan `import_batches` beserta galat per baris.
- Audit trail append-only dengan penyamaran data sensitif, riwayat login, pengaturan sistem.
- Pengguna & peran: CRUD pengguna, reset sandi sementara, aktif/nonaktif, editor matriks hak akses.
- Seeder: 114 permission, 6 peran, master DEMO, 6 akun DEMO, 5 pegawai DEMO.
- 29 pengujian otomatis (106 assertion) pada MySQL `pis_kepegawaian_test`.

### Diperbaiki
- Model `Education` dan `EmployeeEducation` memakai nama tabel yang benar.
- Rute tambah pegawai memakai segmen Indonesia (`/pegawai/tambah`), bukan bawaan `resource`.
- Kolom `bpjs_*` diperlebar menjadi `TEXT` agar nilai terenkripsi tidak terpotong.
- Factory pengguna mengisi `is_active` dan `must_change_password` secara eksplisit.

### Catatan
- Livewire terpasang tetapi belum dipakai; antarmuka memakai controller + Blade.
- Tarif PPh 21/PTKP tetap kosong sampai klarifikasi K-09.
- Payroll, presensi GPS, PDF slip/SK belum diimplementasikan.
- Data DEMO wajib diberi label dan tidak boleh dicampur data produksi.

---
## [0.1.0] — 18-11-2026 — Tahap 1: Blueprint

### Ditambahkan
- `docs/MASTER_SPECIFICATION.md` — keputusan dan spesifikasi induk.
- `docs/SRS.md` — 113 kebutuhan fungsional, 17 aturan bisnis, 17 kebutuhan non-fungsional, 17 use case.
- `docs/DATABASE_DESIGN.md` — ERD 5 domain, spesifikasi 71 tabel domain + 7 tabel framework, urutan migration, seed, aturan integritas.
- `docs/BUSINESS_PROCESS.md` — 15 diagram proses bisnis, matriks persetujuan, SLA usulan.
- `docs/UI_UX_SPECIFICATION.md` — design system, navigasi per peran, spesifikasi halaman, alur presensi mobile.
- `docs/ACCESS_CONTROL.md` — 114 permission, matriks 6 peran, scope data, kewenangan persetujuan.
- `docs/TECHNICAL_ARCHITECTURE.md` — stack terverifikasi (Laravel 13, PHP 8.4, MySQL 8.4), keamanan, backup, deployment.
- `docs/DEVELOPMENT_ROADMAP.md` — rencana 8 tahap.
- `docs/CLARIFICATIONS.md` — 36 pertanyaan klarifikasi dan 15 asumsi sementara.
- `docs/DEVELOPMENT_PROGRESS.md`, `docs/TEST_REPORT.md`.
- `scripts/check_docs.py` — pemeriksaan konsistensi dokumentasi.
- `README.md` — indeks proyek.

### Diperbaiki
- Inkonsistensi jumlah kolom tabel pada spesifikasi `employment_contracts`.
- Jumlah tabel pada dokumen database diselaraskan dengan hasil hitung (78).
- Format permission dibatasi pada pola `modul.aksi`; kunci pengaturan tidak lagi dianggap permission.

### Catatan
- Tidak ada kode aplikasi pada versi ini (sesuai aturan Tahap 1).
- PHP/Composer/MySQL belum terpasang pada lingkungan pengembangan; instalasi dijadwalkan Tahap 2.
- Tarif PPh 21 dan PTKP sengaja belum diisi (menunggu klarifikasi K-09).

---

## Rencana versi berikutnya

| Versi | Tahap | Perkiraan isi |
|---|---|---|
| 0.2.0 | 2 | ✅ Selesai — login, RBAC, organisasi, master pegawai, impor Excel, audit |
| 0.3.0 | 3 | ✅ Selesai — rekrutmen, kontrak, SK, mutasi, surat, persetujuan, notifikasi |
| 0.4.0 | 4 | Presensi GPS + selfie, izin, koreksi |
| 0.5.0 | 5 | Payroll, BPJS, PPh 21, slip PDF |
| 0.6.0 | 6 | KPI, pembinaan |
| 0.7.0 | 7 | Dashboard, laporan, notifikasi |
| 1.0.0 | 8 | Pengujian menyeluruh, deployment, UAT |

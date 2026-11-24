# SRS — Software Requirements Specification
## Aplikasi Kepegawaian PIS

| Atribut | Nilai |
|---|---|
| Versi | 1.0 |
| Tanggal | 18-11-2026 |
| Tahap | 1 — Analisis dan desain |
| Acuan | `MASTER_SPECIFICATION.md`, `Prompt_Induk_Aplikasi_Kepegawaian_PIS.md` |
| Status | Dasar pengembangan Tahap 2–8 |

---

## 1. Pendahuluan

### 1.1 Tujuan dokumen
Menetapkan kebutuhan fungsional dan non-fungsional yang dapat diuji untuk Aplikasi Kepegawaian PIS, sehingga setiap tahap pembangunan memiliki kriteria penerimaan yang jelas.

### 1.2 Ruang lingkup
Sistem web terintegrasi untuk mengelola: master pegawai, struktur organisasi, rekrutmen, administrasi HRD (kontrak/SK/mutasi/surat), absensi mandiri berbasis GPS + selfie, izin/cuti, penggajian (termasuk BPJS dan PPh 21), KPI, pembinaan, dashboard, laporan, notifikasi, pengaturan, audit trail, dan backup.

### 1.3 Aktor

| Aktor | Deskripsi |
|---|---|
| Administrator | Mengelola pengguna, peran, konfigurasi sistem, audit, backup |
| Pimpinan | Monitoring, persetujuan akhir |
| Kepala Bagian | Atasan langsung: verifikasi absensi/izin, penilaian KPI unitnya |
| HRD | Administrasi pegawai, rekrutmen, kontrak/SK, verifikasi izin & koreksi absensi, verifikasi KPI |
| Keuangan | Verifikasi payroll, BPJS, pajak, laporan keuangan |
| Pegawai | Presensi, pengajuan izin/koreksi, melihat slip gaji & KPI sendiri |

### 1.4 Definisi prioritas
- **Wajib** — harus ada agar tahap dinyatakan selesai.
- **Sebaiknya** — ditargetkan, dapat ditunda dengan catatan.
- **Opsional** — pengembangan lanjutan.

### 1.5 Referensi
1. `MASTER_SPECIFICATION.md`
2. `DATABASE_DESIGN.md`
3. `BUSINESS_PROCESS.md`
4. `ACCESS_CONTROL.md`
5. `UI_UX_SPECIFICATION.md`
6. `TECHNICAL_ARCHITECTURE.md`
7. `CLARIFICATIONS.md`

---

## 2. Deskripsi umum

### 2.1 Karakteristik pengguna
Pengguna tersebar di unit pendidikan dan bagian pesantren; sebagian besar menggunakan smartphone untuk presensi; sebagian pengguna administratif menggunakan komputer. Kemampuan teknis beragam → antarmuka harus sederhana, berbahasa Indonesia, dan memberi pesan kesalahan yang jelas.

### 2.2 Batasan
- Web responsif; tidak ada aplikasi native pada tahap ini.
- Presensi memerlukan HTTPS (kamera & geolokasi browser hanya aktif pada konteks aman).
- GPS adalah bukti lokasi yang dinyatakan pengguna; sistem mencatat akurasi dan menolak akurasi yang buruk sesuai ambang konfigurasi. Sistem tidak menjamin deteksi pemalsuan GPS.
- Kebijakan pesantren yang belum diputuskan tidak boleh dikarang; ditandai di `CLARIFICATIONS.md`.

### 2.3 Asumsi sementara
Lihat `CLARIFICATIONS.md` bagian "Asumsi sementara" — semua asumsi wajib dikonfirmasi sebelum produksi.

---

## 3. Kebutuhan fungsional

Format ID: `FR-<MODUL>-<nomor>`. Kolom Tahap merujuk `DEVELOPMENT_ROADMAP.md`.

### 3.1 Autentikasi dan sesi (AUTH — Tahap 2)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-AUTH-001 | Login dengan username/email + password | Wajib | Kredensial benar → masuk; salah → pesan generik, tidak membocorkan data |
| FR-AUTH-002 | Logout dan penghapusan sesi | Wajib | Sesi berakhir; tombol kembali tidak membuka halaman terproteksi |
| FR-AUTH-003 | Ganti password (wajib password lama) | Wajib | Password baru tervalidasi kebijakan; hash tersimpan; sesi lain dipertahankan/diakhiri sesuai konfigurasi |
| FR-AUTH-004 | Reset password oleh administrator | Wajib | Administrator menghasilkan password sementara; pengguna wajib mengganti saat login |
| FR-AUTH-005 | Lupa password via email (jika SMTP tersedia) | Sebaiknya | Token sekali pakai, kedaluwarsa; bila SMTP tidak tersedia, menu disembunyikan dan tertulis di dokumentasi |
| FR-AUTH-006 | Akun aktif/nonaktif | Wajib | Akun nonaktif tidak bisa login; riwayat login tercatat |
| FR-AUTH-007 | Batas percobaan login (rate limit) | Wajib | Setelah N percobaan gagal, login diblokir sementara; tercatat di `login_histories` |
| FR-AUTH-008 | Kebijakan sesi tunggal (opsional, konfigurabel) | Sebaiknya | Jika aktif, login baru mengakhiri sesi lama; jika nonaktif, multi-perangkat diizinkan |

### 3.2 Pengguna, peran, dan hak akses (USER — Tahap 2)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-USER-001 | CRUD pengguna | Wajib | Validasi unik username/email; password di-hash; soft delete |
| FR-USER-002 | Hubungkan akun dengan profil pegawai (1:1 opsional) | Wajib | Satu pegawai maksimal satu akun aktif; satu akun maksimal satu pegawai |
| FR-USER-003 | Kelola peran (role) dan permission | Wajib | Perubahan permission langsung berlaku; tercatat di audit |
| FR-USER-004 | Penetapan peran dan cakupan unit | Wajib | Pengguna dengan peran kepala bagian hanya melihat unit turunannya |
| FR-USER-005 | Reset password & nonaktifkan akun | Wajib | Aksi tercatat; sesi aktif pengguna tersebut diakhiri |
| FR-USER-006 | Larangan pengguna mengubah perannya sendiri | Wajib | Permintaan ditolak 403 |

### 3.3 Struktur organisasi dan master data (ORG — Tahap 2)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-ORG-001 | CRUD unit organisasi bertingkat (yayasan/bidang/bagian/seksi/unit pendidikan) | Wajib | Mendukung parent-child tanpa batas level; tidak boleh ada siklus |
| FR-ORG-002 | CRUD jabatan | Wajib | Jabatan dapat dikaitkan ke unit; dapat dinonaktifkan |
| FR-ORG-003 | Penetapan pimpinan/kepala unit | Wajib | Satu unit satu kepala aktif; riwayat tersimpan |
| FR-ORG-004 | CRUD lokasi kerja (koordinat, radius) | Wajib | Radius > 0; koordinat valid; lokasi dapat ditautkan ke unit |
| FR-ORG-005 | CRUD shift dan jadwal kerja | Wajib | Mendukung lintas hari, hari kerja per minggu, toleransi keterlambatan |
| FR-ORG-006 | CRUD hari libur | Wajib | Tanggal unik; dipakai perhitungan absensi |
| FR-ORG-007 | CRUD jenjang pendidikan | Wajib | Dipakai basis gaji dan kualifikasi |
| FR-ORG-008 | Penomoran otomatis (NIP, surat, kontrak, SK) | Wajib | Format konfigurabel; tidak ada duplikasi; reset periodik opsional |

### 3.4 Master data pegawai (EMP — Tahap 2)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-EMP-001 | Tambah pegawai dengan NIP unik otomatis/manual | Wajib | NIP unik; validasi NIK 16 digit; data wajib tervalidasi |
| FR-EMP-002 | Ubah data pegawai | Wajib | Perubahan data penting tercatat (before/after) |
| FR-EMP-003 | Profil pegawai lengkap (identitas, keluarga, pendidikan, kontak, rekening, NPWP, BPJS, dokumen) | Wajib | Semua tab tampil; data sensitif hanya untuk peran berwenang |
| FR-EMP-004 | Pencarian dan filter (nama, NIP, unit, kategori, status, jabatan) | Wajib | Hasil < 1 detik untuk 1.000 pegawai (indeks) |
| FR-EMP-005 | Kategori pegawai tetap/harian/khidmah + sub-jenis khidmah | Wajib | Validasi kombinasi; perubahan kategori memiliki tanggal efektif + alasan |
| FR-EMP-006 | Status operasional dengan tanggal efektif dan riwayat | Wajib | Riwayat tidak ditimpa; status saat ini terlihat di profil |
| FR-EMP-007 | Riwayat jabatan, unit, atasan langsung | Wajib | Setiap mutasi membuat baris riwayat baru |
| FR-EMP-008 | Unggah foto dan dokumen | Wajib | Format & ukuran dibatasi; file disimpan privat; akses via otorisasi |
| FR-EMP-009 | Impor Excel | Wajib | Templat resmi; validasi per baris; laporan sukses/gagal; impor atomik per batch |
| FR-EMP-010 | Ekspor Excel | Wajib | Sesuai filter aktif; kolom konsisten dengan templat impor |
| FR-EMP-011 | Data keluarga (pasangan, anak, anggota keluarga) | Wajib | Dipakai tunjangan; tanggal lahir anak tervalidasi |
| FR-EMP-012 | Penugasan jadwal kerja per pegawai | Wajib | Berlaku per tanggal; jadwal default per unit |
| FR-EMP-013 | Profil penggajian pegawai (pendidikan, jarak, kelas jabatan, kepesertaan BPJS, status pajak) | Wajib | Hanya HRD/Keuangan yang dapat mengubah; perubahan bertanggal |

### 3.5 Rekrutmen (REC — Tahap 3)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-REC-001 | CRUD lowongan | Wajib | Status buka/tutup; kuota; unit & jabatan |
| FR-REC-002 | Pendaftaran/input pelamar + dokumen | Wajib | Data pelamar tervalidasi; dokumen tersimpan privat |
| FR-REC-003 | Seleksi administrasi | Wajib | Status berubah + catatan + petugas |
| FR-REC-004 | Jadwal & catatan wawancara | Wajib | Satu pelamar dapat beberapa tahap; penilaian tersimpan |
| FR-REC-005 | Penilaian kandidat berbobot | Wajib | Skor total dihitung dari bobot kriteria |
| FR-REC-006 | Keputusan diterima/ditolak | Wajib | Hanya peran berwenang; alasan wajib |
| FR-REC-007 | Konversi pelamar → pegawai tanpa input ulang | Wajib | Data terpetakan; tidak ada duplikasi pegawai; tercatat relasinya |
| FR-REC-008 | Status proses pelamar | Wajib | baru → seleksi_administrasi → wawancara → dipertimbangkan → diterima/ditolak |

### 3.6 Administrasi HRD (HRA — Tahap 3)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-HRA-001 | Kontrak kerja (nomor, mulai, akhir, jenis, dokumen) | Wajib | Nomor unik; status otomatis (aktif/akan berakhir/berakhir) |
| FR-HRA-002 | Perpanjangan kontrak dengan riwayat | Wajib | Kontrak baru merujuk kontrak lama; riwayat tidak hilang |
| FR-HRA-003 | Pengingat kontrak berakhir (H-30/H-14/H-7) | Wajib | Notifikasi ke HRD dan kepala bagian |
| FR-HRA-004 | SK pengangkatan/penempatan/perubahan jabatan/mutasi | Wajib | Nomor, tanggal, efektif, dokumen, persetujuan |
| FR-HRA-005 | Mutasi & perubahan jabatan | Wajib | Simpan data lama, baru, alasan, pejabat penyetuju, dokumen |
| FR-HRA-006 | Template dokumen + penomoran otomatis | Sebaiknya | Isi otomatis dari data pegawai; hasil PDF |
| FR-HRA-007 | Surat teguran/pembinaan/pemberhentian | Wajib | Terhubung ke modul pembinaan; arsip digital |
| FR-HRA-008 | Arsip dokumen pegawai dengan masa berlaku | Wajib | Peringatan dokumen kedaluwarsa |
| FR-HRA-009 | Alur persetujuan per jenis dokumen | Wajib | Setiap jenis dapat memiliki alur berbeda; pemohon ≠ penyetuju |

### 3.7 Absensi (ATT — Tahap 4)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-ATT-001 | Presensi masuk dan pulang | Wajib | Satu baris per pegawai/tanggal/shift; waktu server |
| FR-ATT-002 | Validasi radius GPS per lokasi kerja | Wajib | Di luar radius ditolak dengan pesan jarak; di dalam diterima |
| FR-ATT-003 | Foto selfie wajib | Wajib | Tanpa foto → ditolak; foto tersimpan & tertaut ke presensi |
| FR-ATT-004 | Pencegahan presensi ganda | Wajib | Unique (pegawai, tanggal, shift) + proteksi transaksi; percobaan kedua ditolak |
| FR-ATT-005 | Deteksi lokasi di luar radius + dispensasi | Wajib | Dispensasi memerlukan alasan, bukti, persetujuan; tercatat terpisah |
| FR-ATT-006 | Penanganan GPS/kamera tidak tersedia | Wajib | Panduan langkah + opsi dispensasi; tidak ada presensi palsu |
| FR-ATT-007 | Keterlambatan & pulang lebih awal | Wajib | Dihitung terhadap shift + toleransi; menit tersimpan |
| FR-ATT-008 | Jadwal kerja per unit/kategori/pegawai | Wajib | Perubahan jadwal tidak mengubah absensi historis |
| FR-ATT-009 | Rekap kehadiran (hadir, terlambat, pulang awal, tidak hadir, izin, sakit, dispensasi) | Wajib | Angka dapat ditelusuri ke baris absensi sumber |
| FR-ATT-010 | Pengajuan koreksi absensi | Wajib | Alasan + bukti wajib; status alur jelas |
| FR-ATT-011 | Persetujuan koreksi 2 tingkat (atasan → HRD) | Wajib | Kedua persetujuan tercatat dengan waktu & catatan |
| FR-ATT-012 | Penguncian data absensi yang telah dipakai payroll | Wajib | Perubahan hanya via koreksi resmi + persetujuan |
| FR-ATT-013 | Pencatatan perangkat & IP | Sebaiknya | Perangkat berbeda diizinkan; metadata tersimpan |
| FR-ATT-014 | Presensi dengan jaringan buruk (retry aman) | Wajib | Idempotency key mencegah duplikasi saat retry |
| FR-ATT-015 | Pengecualian hari libur/shift khusus | Wajib | Libur tidak dihitung alpa; shift lintas hari dihitung benar |

### 3.8 Izin dan cuti (LV — Tahap 4)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-LV-001 | Jenis izin konfigurabel (pribadi, sakit, keluarga, cuti, dinas, dispensasi) | Wajib | Setiap jenis punya aturan dokumen & apakah mengurangi kuota |
| FR-LV-002 | Pengajuan izin/cuti dengan tanggal & alasan | Wajib | Validasi tumpang tindih dengan pengajuan lain |
| FR-LV-003 | Dokumen pendukung wajib sesuai jenis | Wajib | Tanpa dokumen → tidak dapat diajukan (jika diwajibkan) |
| FR-LV-004 | Alur persetujuan atasan → HRD | Wajib | Penolakan mengembalikan status; catatan wajib saat menolak |
| FR-LV-005 | Saldo cuti per tahun | Sebaiknya | Saldo berkurang saat disetujui; penyesuaian oleh HRD tercatat |
| FR-LV-006 | Integrasi ke rekap absensi | Wajib | Hari izin disetujui tidak dihitung alpa |
| FR-LV-007 | Pembatalan pengajuan | Sebaiknya | Hanya sebelum diproses; tercatat |
| FR-LV-008 | Rekap izin per pegawai/unit/periode | Wajib | Dapat diekspor |

### 3.9 Penggajian (PAY — Tahap 5)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-PAY-001 | Master komponen gaji (penghasilan/potongan/iuran pemberi kerja) | Wajib | Metode, nominal/persentase, syarat, periode berlaku, status aktif |
| FR-PAY-002 | Konfigurasi bertanggal (tarif, basis pendidikan, transport, BPJS, pajak) | Wajib | Perubahan tidak mengubah perhitungan periode lampau |
| FR-PAY-003 | Periode payroll dengan status alur | Wajib | draft → dihitung → diperiksa → verifikasi HRD → verifikasi keuangan → disetujui → dikunci |
| FR-PAY-004 | Ambil snapshot absensi & izin ke payroll | Wajib | Snapshot dikunci; angka dapat ditelusuri |
| FR-PAY-005 | Perhitungan komponen per pegawai | Wajib | Setiap baris menyimpan basis, tarif, kuantitas, hasil, sumber |
| FR-PAY-006 | BPJS Kesehatan & Ketenagakerjaan | Wajib | Porsi pegawai memotong gaji; porsi pemberi kerja dilaporkan terpisah |
| FR-PAY-007 | PPh 21 | Wajib | Memakai konfigurasi tarif berlaku; bila belum dikonfigurasi → perhitungan diblokir dengan pesan jelas |
| FR-PAY-008 | Prorata pegawai masuk/keluar periode | Wajib | Rumus dapat dikonfigurasi; hasil dapat diverifikasi manual |
| FR-PAY-009 | Slip gaji PDF individual & batch | Wajib | Memuat identitas, periode, rincian, total, gaji bersih |
| FR-PAY-010 | Koreksi payroll dengan alasan & persetujuan | Wajib | Tidak ada perhitungan ganda; versi run baru tercatat |
| FR-PAY-011 | Penguncian periode | Wajib | Setelah dikunci, tidak dapat diubah tanpa prosedur pembukaan (tercatat) |
| FR-PAY-012 | Laporan gaji, tunjangan, potongan, BPJS, pajak, per unit, daftar transfer | Wajib | Ekspor Excel/PDF; angka sama dengan sumber |
| FR-PAY-013 | Uang snack: larangan Driver, maksimal sekali per tanggal/shift | Wajib | Aturan diuji dengan kasus Driver dan non-Driver |
| FR-PAY-014 | Tunjangan kehadiran MSU/SD menggantikan tunjangan kehadiran lain | Wajib | Tidak terjadi pembayaran ganda |
| FR-PAY-015 | Larangan tunjangan pasangan jika suami juga bekerja di pesantren | Wajib | Validasi berbasis data keluarga + data pegawai |

### 3.10 KPI dan pembinaan (KPI/COA — Tahap 6)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-KPI-001 | Template indikator per jabatan/unit | Wajib | Bobot total = 100%; berlaku bertanggal |
| FR-KPI-002 | Periode bulanan & evaluasi triwulanan | Wajib | Satu penilaian per pegawai/periode/template |
| FR-KPI-003 | Penilaian oleh kepala bagian | Wajib | Hanya bawahan langsung/unitnya |
| FR-KPI-004 | Verifikasi HRD | Wajib | Status dan catatan verifikasi tersimpan |
| FR-KPI-005 | Persetujuan pimpinan | Wajib | Setelah disetujui, penilaian terkunci (kecuali dibuka dengan alasan) |
| FR-KPI-006 | Nilai akhir, predikat, grafik tren, perbandingan periode | Wajib | Predikat dari rentang konfigurabel |
| FR-KPI-007 | Rekomendasi pembinaan | Wajib | Terhubung ke catatan pembinaan |
| FR-KPI-008 | KPI tidak mengubah gaji/status otomatis | Wajib | Tidak ada efek samping ke payroll |
| FR-COA-001 | Catatan pembinaan (konseling, teguran, pelatihan, penghargaan) | Wajib | Tanggal, petugas, alasan, hasil, dokumen |
| FR-COA-002 | Tindak lanjut & status | Wajib | Riwayat lengkap per pegawai |

### 3.11 Dashboard, laporan, dan notifikasi (DSH/RPT/NOT — Tahap 7)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-DSH-001 | Dashboard per peran (pimpinan, HRD, kepala bagian, keuangan, pegawai) | Wajib | Widget hanya menampilkan data yang boleh diakses |
| FR-DSH-002 | Ringkasan kehadiran, kontrak, payroll, KPI, pembinaan | Wajib | Angka konsisten dengan laporan |
| FR-RPT-001 | 14 jenis laporan wajib (pegawai, unit, status, masa kerja, kontrak, absensi, keterlambatan, izin, penggajian, BPJS, PPh 21, KPI, pembinaan, masuk-keluar) | Wajib | Filter tanggal/periode/unit/status/pegawai |
| FR-RPT-002 | Ekspor Excel & PDF, cetak | Wajib | Isi ekspor = data filter aktif |
| FR-NOT-001 | Notifikasi dalam aplikasi | Wajib | Kontrak berakhir, pengajuan izin, koreksi absensi, persetujuan tertunda, jadwal KPI, dokumen kedaluwarsa, periode payroll |
| FR-NOT-002 | Notifikasi tidak membocorkan data sensitif | Wajib | Penerima hanya yang berwenang |

### 3.12 Pengaturan, audit, dan pemeliharaan (SYS — Tahap 2 & 8)

| ID | Kebutuhan | Prioritas | Kriteria penerimaan |
|---|---|---|---|
| FR-SYS-001 | Pengaturan aplikasi terpusat (identitas, kebijakan, integrasi) | Wajib | Perubahan tercatat + tanggal berlaku bila relevan |
| FR-SYS-002 | Audit trail (pengguna, aksi, data, before/after, waktu, IP) | Wajib | Tidak dapat dihapus dari UI; dapat difilter |
| FR-SYS-003 | Riwayat login | Wajib | Waktu, IP, user agent, status |
| FR-SYS-004 | Backup database & dokumen terjadwal | Wajib | Backup dapat dipulihkan (diuji) |
| FR-SYS-005 | Impor Excel dengan laporan kesalahan per baris | Wajib | Baris gagal tidak meninggalkan data setengah jadi |
| FR-SYS-006 | Log ekspor laporan | Sebaiknya | Siapa, kapan, filter apa |
| FR-SYS-007 | Mode DEMO untuk data demonstrasi | Wajib | Data demo berlabel dan dapat dibersihkan tanpa menyentuh data produksi |

---

## 4. Aturan bisnis

| ID | Aturan |
|---|---|
| BR-001 | Presensi memakai waktu server; jam perangkat tidak pernah dipakai untuk keputusan. |
| BR-002 | Satu pegawai hanya boleh memiliki satu presensi masuk per tanggal per shift. |
| BR-003 | Presensi di luar radius hanya sah melalui dispensasi yang disetujui dan tercatat. |
| BR-004 | Data absensi yang sudah masuk payroll terkunci; perubahan hanya via koreksi resmi. |
| BR-005 | Kategori pegawai dan status operasional adalah dua dimensi berbeda. |
| BR-006 | Setiap perubahan status/kategori/jabatan/unit menyimpan data lama, baru, tanggal efektif, alasan, dan pelaku. |
| BR-007 | Angka penggajian berasal dari konfigurasi bertanggal; tidak ada angka upah di dalam kode. |
| BR-008 | Pemohon tidak boleh menyetujui pengajuannya sendiri. |
| BR-009 | Penghitung payroll tidak boleh menjadi pemberi persetujuan akhir. |
| BR-010 | Tunjangan kehadiran MSU/SD menggantikan tunjangan kehadiran lain. |
| BR-011 | Tunjangan pasangan tidak diberikan bila pasangan (istri) juga bekerja di pesantren. |
| BR-012 | Uang snack tidak untuk Driver; maksimal satu per tanggal per shift. |
| BR-013 | KPI tidak mengubah gaji/status secara otomatis. |
| BR-014 | Dokumen pribadi (KTP, rekening, NPWP, foto, slip gaji) hanya dapat diakses pemilik dan peran berwenang. |
| BR-015 | Semua aksi tulis pada data sensitif masuk audit trail. |
| BR-016 | Data DEMO wajib berlabel dan tidak boleh bercampur tanpa penanda. |
| BR-017 | Soft delete untuk pegawai, pengguna, dokumen, dan transaksi keuangan; penghapusan permanen hanya oleh administrator dengan audit. |

---

## 5. Kebutuhan non-fungsional

| ID | Kategori | Kebutuhan | Target terukur |
|---|---|---|---|
| NFR-001 | Performa | Halaman daftar & pencarian | < 2 detik (p95) untuk 1.000 pegawai, 50 pengguna bersamaan |
| NFR-002 | Performa | Perhitungan payroll | < 60 detik untuk 1.000 pegawai (satu periode) |
| NFR-003 | Performa | Presensi | Respons < 5 detik termasuk unggah foto (jaringan 3G wajar) |
| NFR-004 | Keamanan | Password | Hash bcrypt cost ≥ 12 atau argon2id; kebijakan panjang minimal 8 + kompleksitas |
| NFR-005 | Keamanan | Transport | HTTPS wajib di produksi; HSTS; cookie Secure/HttpOnly/SameSite |
| NFR-006 | Keamanan | Otorisasi | Setiap route & query memeriksa permission + scope data (deny by default) |
| NFR-007 | Keamanan | Unggahan file | Validasi MIME + ekstensi + ukuran; disimpan di luar public root; nama file diacak |
| NFR-008 | Keamanan | Audit | 100% aksi tulis pada entitas sensitif tercatat |
| NFR-009 | Keandalan | Backup | Harian; retensi 30 hari; uji restore minimal 1× per kuartal; RPO 24 jam, RTO 8 jam |
| NFR-010 | Kegunaan | Bahasa & format | Seluruh UI berbahasa Indonesia; tanggal DD-MM-YYYY; angka format Indonesia |
| NFR-011 | Kompatibilitas | Browser | Chrome/Edge/Firefox/Safari 2 versi terakhir; Android Chrome; iOS Safari |
| NFR-012 | Responsif | Layar | 360 px – 1920 px tanpa horizontal scroll |
| NFR-013 | Aksesibilitas | Dasar | Label form, kontras WCAG AA, navigasi keyboard, pesan error terbaca |
| NFR-014 | Pemeliharaan | Kode | PSR-12 (Pint), test otomatis untuk logika kritis (payroll, absensi, otorisasi) |
| NFR-015 | Auditabilitas | Perhitungan | Setiap angka payroll dapat ditelusuri ke sumber & konfigurasi |
| NFR-016 | Lokalisasi waktu | Zona waktu | Semua waktu disimpan UTC, ditampilkan Asia/Jakarta |
| NFR-017 | Privasi | Data pribadi | Akses minimum; dokumen privat; log akses dokumen sensitif |

---

## 6. Use case utama

| ID | Use case | Aktor | Pra-kondisi | Pasca-kondisi |
|---|---|---|---|---|
| UC-01 | Login | Semua | Akun aktif | Sesi dibuat, login tercatat |
| UC-02 | Kelola pegawai | HRD | Login + `employee.create/update` | Data tersimpan + audit |
| UC-03 | Impor pegawai dari Excel | HRD | Templat valid | Pegawai dibuat; laporan impor tersedia |
| UC-04 | Presensi masuk | Pegawai | Login, dalam radius, kamera diizinkan | Baris absensi + foto tersimpan |
| UC-05 | Presensi di luar radius | Pegawai | Login | Ditolak; opsi dispensasi ditawarkan |
| UC-06 | Ajukan koreksi absensi | Pegawai | Ada absensi terkait | Pengajuan masuk alur atasan → HRD |
| UC-07 | Setujui koreksi absensi | Kepala bagian, HRD | Pengajuan menunggu | Absensi diperbarui + audit |
| UC-08 | Ajukan izin/cuti | Pegawai | Saldo/dokumen memadai | Pengajuan masuk alur |
| UC-09 | Proses rekrutmen → pegawai | HRD | Pelamar diterima | Pegawai baru terbentuk tanpa input ulang |
| UC-10 | Hitung payroll | Keuangan/HRD | Periode dibuka, data absensi lengkap | Run payroll + rincian per pegawai |
| UC-11 | Verifikasi & setujui payroll | HRD, Keuangan, Pimpinan | Run berstatus dihitung | Status maju; setelah disetujui dapat dikunci |
| UC-12 | Unduh slip gaji | Pegawai | Payroll disetujui | PDF slip gaji sendiri terunduh |
| UC-13 | Nilai KPI | Kepala bagian | Periode aktif, template tersedia | Penilaian tersimpan, menunggu verifikasi |
| UC-14 | Verifikasi & setujui KPI | HRD, Pimpinan | Penilaian dikirim | Nilai final terlihat sesuai kewenangan |
| UC-15 | Lihat dashboard | Semua | Login | Data sesuai scope |
| UC-16 | Ekspor laporan | HRD/Keuangan/Pimpinan | Login + permission laporan | File Excel/PDF konsisten dengan filter |
| UC-17 | Backup & restore | Administrator | Akses server | Backup terverifikasi dapat dipulihkan |

---

## 7. Kebutuhan antarmuka eksternal

| Antarmuka | Kebutuhan |
|---|---|
| Browser | Geolocation API, MediaDevices (kamera), Canvas untuk kompresi foto |
| Excel | Impor `.xlsx`/`.csv` dengan templat; ekspor `.xlsx` |
| PDF | Slip gaji, SK, surat, laporan |
| Email | Reset password & notifikasi (jika SMTP tersedia) |
| Penyimpanan | Filesystem privat (lokal/NAS), dapat dipindah ke object storage |
| Database | MySQL 8.4 |

---

## 8. Matriks keterlacakan tahap → kebutuhan

| Tahap | Kebutuhan utama |
|---|---|
| 2 | AUTH, USER, ORG, EMP, SYS-001/002/003/005/007 |
| 3 | REC, HRA |
| 4 | ATT, LV |
| 5 | PAY |
| 6 | KPI, COA |
| 7 | DSH, RPT, NOT |
| 8 | Pengujian menyeluruh, keamanan, backup/restore, deployment, UAT, dokumentasi operasional |

---

## 9. Di luar ruang lingkup (tahap ini)

- Aplikasi mobile native.
- Deteksi liveness / anti-spoofing wajah.
- Deteksi manipulasi GPS tingkat lanjut (mock location).
- Integrasi langsung ke mesin absensi/fingerprint.
- Integrasi bank/transfer otomatis.
- Portal pelamaran publik (dapat ditambahkan kemudian).
- Payroll multibahasa/multivaluta.

---

## 10. Kriteria penerimaan Tahap 1

1. Seluruh dokumen blueprint tersedia dan saling konsisten.
2. Setiap kebutuhan fungsional memiliki ID dan kriteria penerimaan.
3. ERD mencakup seluruh modul Tahap 2–8.
4. Matriks hak akses mencakup seluruh peran dan modul.
5. Arsitektur teknologi menetapkan versi yang tersedia di lingkungan.
6. Pertanyaan kebijakan yang belum diputuskan tercatat di `CLARIFICATIONS.md` — tidak dikarang.
7. Roadmap menetapkan urutan, ketergantungan, dan kriteria keluar tiap tahap.

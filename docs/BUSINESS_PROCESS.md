# BUSINESS PROCESS — Aplikasi Kepegawaian PIS

| Atribut | Nilai |
|---|---|
| Versi | 1.0 |
| Tanggal | 18-11-2026 |
| Acuan | `MASTER_SPECIFICATION.md`, `SRS.md` |
| Notasi | Mermaid (flowchart, stateDiagram) |

Dokumen ini menjelaskan proses bisnis target. Semua diagram adalah **rancangan** yang menjadi acuan implementasi Tahap 2–8. Kebijakan yang belum diputuskan ditandai `K-xx` dan dirujuk ke `CLARIFICATIONS.md`.

---

## 1. Peta proses utama

```mermaid
flowchart LR
    A[Lowongan] --> B[Pelamar]
    B --> C[Seleksi]
    C -->|Diterima| D[Kontrak & SK Pengangkatan]
    C -->|Ditolak| Z1[Arsip Pelamar]
    D --> E[Pegawai Aktif]
    E --> F[Presensi Harian]
    E --> G[Izin & Cuti]
    E --> H[KPI Bulanan]
    F --> I[Payroll]
    G --> I
    H --> J[Pembinaan]
    I --> K[Slip Gaji]
    E --> L[Mutasi / Perubahan Status]
    L --> E
    E --> M[Pemberhentian]
    J --> M
```

---

## 2. Rekrutmen dan penerimaan pegawai (Tahap 3)

```mermaid
flowchart TD
    A[HRD membuat lowongan] --> B{Lowongan dibuka?}
    B -->|Ya| C[Pelamar mengisi data & dokumen]
    B -->|Tidak| B1[Tidak menerima lamaran]
    C --> D[Seleksi administrasi]
    D -->|Lolos| E[Jadwal wawancara]
    D -->|Tidak lolos| F[Tolak + alasan]
    E --> G[Wawancara & penilaian berbobot]
    G --> H[Dipertimbangkan]
    H --> I{Keputusan}
    I -->|Diterima| J[Konversi ke pegawai]
    I -->|Ditolak| F
    J --> K[Lengkapi data pegawai dari data pelamar]
    K --> L[Terbitkan kontrak & SK]
    L --> M[Status: pegawai aktif / percobaan]
```

**Aturan**
- Konversi wajib memakai data pelamar yang ada (tanpa input ulang identitas utama).
- Satu pelamar hanya dapat dikonversi satu kali (`applicants.converted_employee_id` unik bila terisi).
- Keputusan penerimaan wajib memiliki alasan dan pelaku.
- Masa percobaan mengikuti konfigurasi `probation_months` (K-08).

---

## 3. Pengangkatan dan penetapan pegawai (Tahap 3)

```mermaid
flowchart TD
    A[Data pegawai lengkap] --> B[HRD menyiapkan SK & kontrak]
    B --> C[Atasan langsung memeriksa]
    C --> D[HRD verifikasi administrasi]
    D --> E[Pimpinan menyetujui]
    E --> F[SK berkekuatan resmi]
    F --> G[Sistem mencatat riwayat jabatan/unit/status]
    G --> H[Akun pengguna dibuat & ditautkan]
```

| Tahap | Pelaku | Keluaran |
|---|---|---|
| 1 | HRD | Draf SK, kontrak, penempatan |
| 2 | Kepala bagian | Persetujuan/penolakan atasan |
| 3 | HRD | Verifikasi kelengkapan |
| 4 | Pimpinan | Persetujuan akhir |
| 5 | Sistem | Riwayat + akun + audit |

---

## 4. Perubahan status pegawai (Tahap 3)

```mermaid
stateDiagram-v2
    [*] --> Percobaan
    Percobaan --> Aktif: Lulus percobaan
    Percobaan --> Diberhentikan: Tidak lulus
    Aktif --> CutiIzin: Cuti/izin panjang
    CutiIzin --> Aktif: Kembali
    Aktif --> NonaktifSementara: Penangguhan
    NonaktifSementara --> Aktif: Diaktifkan
    Aktif --> MengundurkanDiri: Pengunduran diri
    Aktif --> Diberhentikan: Pemberhentian
    MengundurkanDiri --> [*]
    Diberhentikan --> [*]
```

**Aturan wajib**
1. Perubahan hanya lewat form yang meminta: status baru, tanggal efektif, alasan, dokumen (jika perlu), pejabat penyetuju.
2. Baris `employee_status_histories` baru dibuat; baris lama tidak diubah.
3. Status `mengundurkan_diri`/`diberhentikan` menonaktifkan akun dan menutup akses presensi pada tanggal efektif.
4. Payroll memakai status pada rentang tanggal periode, bukan status hari ini.

---

## 5. Mutasi dan perubahan jabatan (Tahap 3)

```mermaid
flowchart TD
    A[Usulan mutasi/perubahan jabatan] --> B[Atasan & HRD menyiapkan usulan]
    B --> C[Data lama: unit, jabatan, atasan]
    C --> D[Data baru + tanggal efektif + alasan]
    D --> E[Persetujuan pimpinan]
    E -->|Disetujui| F[Terbit SK]
    E -->|Ditolak| G[Usulan ditutup dengan alasan]
    F --> H[Update penempatan aktif]
    H --> I[Tulis employee_assignments baru]
    I --> J[Audit trail + notifikasi]
```

- Penempatan aktif = baris `employee_assignments` dengan `effective_to IS NULL`.
- Perubahan tidak boleh menimpa riwayat; penutupan baris lama memakai `effective_to` = H-1 tanggal efektif baru.

---

## 6. Presensi harian (Tahap 4)

```mermaid
flowchart TD
    A[Pegawai login di HP] --> B{Jadwal kerja hari ini?}
    B -->|Libur / tidak ada shift| B1[Presensi ditolak: tidak ada jadwal]
    B -->|Ada shift| C[Ambil GPS]
    C --> D{GPS tersedia & akurasi memadai?}
    D -->|Tidak| D1[Tampilkan panduan + opsi dispensasi]
    D -->|Ya| E[Hitung jarak ke lokasi kerja]
    E --> F{Dalam radius?}
    F -->|Tidak| F1[Tolak + tampilkan jarak + opsi dispensasi]
    F -->|Ya| G[Ambil foto selfie langsung]
    G --> H{Foto valid?}
    H -->|Tidak| H1[Tolak: foto wajib]
    H -->|Ya| I[Kirim ke server + idempotency key]
    I --> J{Sudah ada presensi?}
    J -->|Ya| J1[Tolak: presensi ganda]
    J -->|Tidak| K[Simpan: waktu server, koordinat, jarak, foto, perangkat]
    K --> L[Hitung keterlambatan / pulang awal]
    L --> M[Audit + tampilkan bukti presensi]
```

**Perhitungan keterlambatan**

```
jadwal_masuk   = shift.start_time (tanggal kerja, zona Asia/Jakarta)
batas_masuk    = jadwal_masuk + shift.late_tolerance_minutes
terlambat      = max(0, menit(check_in_at - batas_masuk))
pulang_awal    = max(0, menit(batas_pulang - check_out_at))
menit_kerja    = max(0, menit(check_out_at - check_in_at) - istirahat)
```

**Aturan**
- Waktu keputusan selalu `now()` server.
- Shift lintas hari: `work_date` tetap tanggal mulai shift.
- Libur nasional/pesantren: tidak dihitung alpa (kecuali pegawai punya shift khusus).
- Akurasi GPS di atas ambang (mis. 100 m, K-14) ditolak atau ditandai; keputusan final menunggu klarifikasi.
- Presensi di luar radius tanpa dispensasi tetap tercatat sebagai percobaan gagal (untuk audit), bukan sebagai kehadiran.

---

## 7. Koreksi absensi (Tahap 4)

```mermaid
flowchart TD
    A[Pegawai membuka riwayat absensi] --> B[Pilih tanggal & jenis koreksi]
    B --> C[Isi jam benar + alasan + bukti]
    C --> D[Status: menunggu atasan]
    D --> E{Atasan langsung}
    E -->|Tolak| F[Selesai: ditolak + catatan]
    E -->|Setujui| G[Status: menunggu HRD]
    G --> H{HRD verifikasi}
    H -->|Tolak| F
    H -->|Setujui| I[Update absensi + tandai sumber koreksi]
    I --> J[Audit trail lengkap]
    J --> K{Jika periode payroll terkunci?}
    K -->|Ya| L[Wajib koreksi payroll resmi pada periode terkait]
    K -->|Tidak| M[Selesai]
```

**Aturan**
- Koreksi tidak pernah menghapus nilai asli: nilai lama disimpan pada `attendance_corrections` dan audit trail.
- Setelah payroll dikunci, koreksi absensi menghasilkan penyesuaian payroll (bukan edit diam-diam).
- Pemohon tidak dapat menyetujui koreksinya sendiri; atasan yang juga pemohon harus dieskalasi ke HRD.

---

## 8. Izin dan cuti (Tahap 4)

```mermaid
flowchart TD
    A[Pegawai memilih jenis izin] --> B{Jenis butuh dokumen?}
    B -->|Ya| C[Unggah dokumen wajib]
    B -->|Tidak| D[Isi tanggal & alasan]
    C --> D
    D --> E{Saldo cukup?}
    E -->|Tidak| E1[Tolak pengajuan / tawarkan izin tidak berbayar]
    E -->|Ya| F[Tumpang tindih dengan pengajuan lain?]
    F -->|Ya| F1[Tolak: tanggal bentrok]
    F -->|Tidak| G[Status: menunggu atasan]
    G --> H{Atasan}
    H -->|Tolak| I[Ditolak + alasan]
    H -->|Setujui| J{Perlu HRD?}
    J -->|Ya| K[HRD verifikasi]
    J -->|Tidak| L[Disetujui]
    K -->|Setujui| L
    K -->|Tolak| I
    L --> M[Potong saldo bila berlaku]
    M --> N[Integrasi rekap absensi]
```

**Jenis izin awal:** izin pribadi, sakit, keluarga, cuti tahunan, dinas, dispensasi. Aturan saldo dan dokumen per jenis berada di `leave_types` (konfigurabel).

---

## 9. Penggajian (Tahap 5)

```mermaid
flowchart TD
    A[Keuangan/HRD membuka periode] --> B[Validasi kelengkapan data]
    B -->|Belum lengkap| B1[Kembalikan ke unit terkait]
    B -->|Lengkap| C[Snapshot absensi & izin]
    C --> D[Hitung komponen per pegawai]
    D --> E[Pemeriksaan otomatis: negatif, anomali, duplikat]
    E --> F{Ada anomali?}
    F -->|Ya| G[Koreksi / penyesuaian dengan alasan]
    G --> D
    F -->|Tidak| H[Review internal]
    H --> I[Verifikasi HRD]
    I --> J[Verifikasi Keuangan]
    J --> K[Persetujuan Pimpinan]
    K -->|Ditolak| G
    K -->|Disetujui| L[Terbitkan slip gaji PDF]
    L --> M[Kunci periode]
    M --> N[Arsip + laporan BPJS/PPh 21]
```

**Urutan status periode**

```mermaid
stateDiagram-v2
    [*] --> Draft
    Draft --> Dihitung: Hitung
    Dihitung --> Diperiksa: Review
    Diperiksa --> Dihitung: Perbaikan
    Diperiksa --> VerifikasiHRD: Kirim
    VerifikasiHRD --> Diperiksa: Dikembalikan
    VerifikasiHRD --> VerifikasiKeuangan: Setujui HRD
    VerifikasiKeuangan --> VerifikasiHRD: Dikembalikan
    VerifikasiKeuangan --> Disetujui: Setujui Keuangan
    Disetujui --> Dikunci: Kunci
    Dikunci --> [*]
```

**Aturan perhitungan**
1. Semua tarif diambil dari konfigurasi dengan `effective_from <= periode` dan `effective_to IS NULL OR >= periode`.
2. Gaji pokok dihitung sesuai kategori; komponen yang tidak berlaku tidak menghasilkan baris.
3. Setiap baris `payroll_details` menyimpan: komponen, tipe, kuantitas, tarif, basis, hasil, catatan rumus, sumber data.
4. Potongan tidak boleh membuat gaji bersih negatif tanpa persetujuan eksplisit; sistem memberi peringatan.
5. Perhitungan ulang membuat `payroll_runs` versi baru; versi lama disimpan.
6. Periode terkunci tidak dapat diubah tanpa prosedur pembukaan (dicatat + disetujui).

**Rumus acuan (konfigurasi awal, wajib dikonfirmasi)**

```
gaji_harian_tetap     = hari_kalender_aktif × tarif_harian
potongan_alpa         = hari_alpa × tarif_potongan_harian
potongan_terlambat    = total_menit_terlambat × tarif_per_menit
basis_pendidikan      = tarif_pendidikan[level] (+ kenaikan tahunan 5%)
tunjangan_pasangan    = 200000 bila memenuhi syarat
tunjangan_anak        = 100000 × jumlah_anak_memenuhi_syarat
tunjangan_transport   = band_jarak(jarak_km)
tunjangan_kehadiran   = tarif_kehadiran (MSU/SD menggantikan yang lain)
bpjs_kesehatan_pegawai= 1% × basis_bpjs   (basis awal 4.200.000)
jht_pegawai           = 2% × basis_jht
pph21                 = f(bruto_setahun, PTKP, tarif_berlaku)
gaji_bersih           = total_penghasilan − total_potongan_pegawai
```

> Angka dan tafsir rumus yang belum pasti tercantum pada `CLARIFICATIONS.md` (K-01 s.d. K-12). Jangan menganggap contoh di atas sebagai ketentuan final.

---

## 10. KPI (Tahap 6)

```mermaid
flowchart TD
    A[HRD menyiapkan periode & template] --> B[Kepala bagian membuka penilaian]
    B --> C[Isi realisasi & skor per indikator]
    C --> D[Sistem hitung nilai berbobot + predikat]
    D --> E[Kirim ke HRD]
    E --> F{HRD verifikasi}
    F -->|Perlu perbaikan| B
    F -->|Setujui| G[Pimpinan]
    G -->|Tolak| B
    G -->|Setujui| H[Nilai final + rekomendasi pembinaan]
    H --> I[Dashboard, laporan, riwayat pegawai]
    H --> J[Tidak mengubah gaji/status otomatis]
```

**Aturan**
- Bobot indikator per template harus berjumlah 100% (validasi simpan).
- Satu penilaian per pegawai/periode/template; nilai historis tidak diubah setelah disetujui.
- Evaluasi triwulanan merangkum tiga penilaian bulanan (metode agregasi dikonfigurasi, K-20).

---

## 11. Pembinaan pegawai (Tahap 6)

```mermaid
flowchart TD
    A[Sumber: KPI, keterlambatan, pelanggaran, pengaduan] --> B[HRD/atasan membuat catatan pembinaan]
    B --> C{Jenis}
    C --> D[Konseling]
    C --> E[Teguran]
    C --> F[Pelatihan]
    C --> G[Penghargaan]
    D --> H[Tindak lanjut + tenggat]
    E --> H
    F --> H
    G --> H
    H --> I[Evaluasi hasil]
    I --> J{Perlu eskalasi?}
    J -->|Ya| K[Pemberhentian / tindakan lanjutan]
    J -->|Tidak| L[Selesai + arsip]
```

---

## 12. Pemberhentian (Tahap 3/6)

```mermaid
flowchart TD
    A[Usulan pemberhentian] --> B[Alasan + dokumen pendukung]
    B --> C[Atasan langsung]
    C --> D[HRD verifikasi]
    D --> E[Pimpinan / Yayasan jika diperlukan]
    E -->|Disetujui| F[SK pemberhentian]
    F --> G[Tanggal efektif]
    G --> H[Status: mengundurkan diri / diberhentikan]
    H --> I[Nonaktifkan akun + tutup jadwal]
    I --> J[Hitung hak akhir: gaji, cuti, potongan]
    J --> K[Arsip + audit]
```

---

## 13. Kontrak dan perpanjangan (Tahap 3)

```mermaid
flowchart TD
    A[Kontrak aktif] --> B[H-30: pengingat otomatis]
    B --> C{Keputusan perpanjangan}
    C -->|Diperpanjang| D[Draf kontrak baru]
    D --> E[Persetujuan sesuai kewenangan]
    E --> F[Kontrak baru aktif + riwayat]
    C -->|Tidak| G[Proses pemberhentian/berakhir]
    F --> H[Update jadwal pengingat berikutnya]
```

---

## 14. Matriks persetujuan (ringkas)

| Jenis pengajuan | Tingkat 1 | Tingkat 2 | Tingkat 3 | Catatan |
|---|---|---|---|---|
| Koreksi absensi | Atasan langsung | HRD | — | Wajib dua tingkat |
| Izin pribadi/keluarga | Atasan langsung | HRD | — | HRD memverifikasi administrasi |
| Sakit | Atasan langsung | HRD | — | Surat dokter wajib bila > 1 hari (K-16) |
| Cuti tahunan | Atasan langsung | HRD | — | Saldo cuti |
| Dinas | Atasan langsung | HRD | — | Dapat tanpa potongan |
| Kontrak kerja | HRD | Pimpinan | Yayasan (bila perlu) | Tergantung jenis |
| SK pengangkatan/mutasi | HRD | Pimpinan | Yayasan (bila perlu) | Setiap SK punya alur |
| Payroll | HRD | Keuangan | Pimpinan | Tidak boleh satu orang |
| KPI | Kepala bagian (penilai) | HRD | Pimpinan | Penilai ≠ verifikator |
| Pemberhentian | Atasan | HRD | Pimpinan/Yayasan | Wajib dokumen |
| Pembinaan berat | HRD | Pimpinan | — | Teguran berat/pemberhentian |

Rincian kewenangan per peran: `ACCESS_CONTROL.md`.

---

## 15. SLA proses (usulan, perlu persetujuan)

| Proses | Sasaran waktu |
|---|---|
| Koreksi absensi — atasan | ≤ 2 hari kerja |
| Koreksi absensi — HRD | ≤ 2 hari kerja |
| Izin/cuti | ≤ 2 hari kerja sebelum tanggal mulai (kecuali darurat) |
| Verifikasi payroll HRD | ≤ 2 hari kerja setelah perhitungan |
| Verifikasi payroll keuangan | ≤ 2 hari kerja |
| Persetujuan pimpinan | ≤ 3 hari kerja |
| Verifikasi KPI HRD | ≤ 5 hari kerja setelah penilaian |
| Persetujuan KPI pimpinan | ≤ 5 hari kerja |

SLA hanya menjadi pengingat/notifikasi; tidak mengubah status otomatis tanpa keputusan.

# UI/UX SPECIFICATION — Aplikasi Kepegawaian PIS

| Atribut | Nilai |
|---|---|
| Versi | 1.0 |
| Tanggal | 18-11-2026 |
| Prinsip | Sederhana, konsisten, mobile-first untuk presensi, desktop-first untuk administrasi |
| Bahasa | Indonesia |
| Acuan | `SRS.md`, `ACCESS_CONTROL.md` |

---

## 1. Prinsip desain

1. **Satu pola untuk hal yang sama** — tabel, filter, form, tombol aksi, dan dialog konfirmasi identik di seluruh modul.
2. **Mobile-first hanya untuk alur pegawai** (presensi, izin, slip). Modul administratif dioptimalkan untuk desktop tetapi tetap responsif.
3. **Jelas > cantik** — status, angka, dan konsekuensi aksi harus terbaca tanpa menebak.
4. **Aman secara default** — aksi destruktif memakai konfirmasi + alasan; data sensitif tidak muncul di tempat yang tidak perlu.
5. **Umpan balik nyata** — setiap aksi memberi hasil (sukses/gagal) dengan alasan yang dapat ditindaklanjuti.
6. **Tanpa data palsu** — angka kosong ditampilkan sebagai "Belum ada data", bukan `0` yang menyesatkan (kecuali memang nol).

---

## 2. Design system

### 2.1 Warna

| Token | Nilai | Penggunaan |
|---|---|---|
| `--color-primary` | `#14532D` (hijau tua, nuansa pesantren) | Navigasi aktif, tombol utama |
| `--color-primary-600` | `#166534` | Hover |
| `--color-accent` | `#CA8A04` | Sorotan, badge penting |
| `--color-danger` | `#B91C1C` | Tolak, hapus, di luar radius |
| `--color-warning` | `#B45309` | Peringatan, menunggu persetujuan |
| `--color-success` | `#15803D` | Disetujui, hadir tepat waktu |
| `--color-info` | `#1D4ED8` | Informasi |
| `--color-bg` | `#F8FAFC` | Latar halaman |
| `--color-surface` | `#FFFFFF` | Kartu, tabel |
| `--color-text` | `#0F172A` | Teks utama |
| `--color-muted` | `#64748B` | Teks sekunder |

Status absensi memakai kombinasi warna + ikon + teks (tidak hanya warna) agar aksesibel.

### 2.2 Tipografi

- Font: `Inter`, fallback `system-ui, Segoe UI, sans-serif`.
- Skala: 12/14/16/18/20/24/30 px.
- Angka uang dan tanggal memakai `font-variant-numeric: tabular-nums`.
- Judul halaman 24 px, subjudul 16 px, body 14–16 px.

### 2.3 Spasi dan radius

- Grid 4 px; jarak komponen 8/12/16/24/32.
- Radius: 8 px (input/tombol), 12 px (kartu), 16 px (modal mobile).
- Bayangan halus, tanpa gradien berlebihan.

### 2.4 Breakpoint

| Nama | Lebar | Perilaku |
|---|---|---|
| `sm` | ≥ 360 px | HP; sidebar menjadi drawer; tabel → kartu |
| `md` | ≥ 768 px | Tablet; filter 2 kolom |
| `lg` | ≥ 1024 px | Desktop; sidebar tetap |
| `xl` | ≥ 1280 px | Konten maksimum 1200 px |

---

## 3. Struktur navigasi

### 3.1 Layout utama (desktop)

```
┌──────────────────────────────────────────────────────────────┐
│ Topbar: logo | pencarian global | notifikasi | profil         │
├───────────────┬──────────────────────────────────────────────┤
│ Sidebar       │ Breadcrumb                                   │
│ (240 px)      │ Judul halaman + aksi utama                    │
│               │ ┌──────────────────────────────────────────┐ │
│ Dashboard     │ │ Filter / ringkasan                        │ │
│ Pegawai       │ ├──────────────────────────────────────────┤ │
│ Organisasi    │ │ Tabel / konten                            │ │
│ Rekrutmen     │ │                                          │ │
│ Administrasi  │ │                                          │ │
│ Absensi       │ │                                          │ │
│ Izin & Cuti   │ │                                          │ │
│ Penggajian    │ │                                          │ │
│ KPI           │ │                                          │ │
│ Pembinaan     │ │                                          │ │
│ Laporan       │ │                                          │ │
│ Pengaturan    │ └──────────────────────────────────────────┘ │
└───────────────┴──────────────────────────────────────────────┘
```

### 3.2 Sidebar per peran

| Menu | Administrator | Pimpinan | Kepala Bagian | HRD | Keuangan | Pegawai |
|---|---|---|---|---|---|---|
| Dashboard | ✓ | ✓ (pimpinan) | ✓ (unit) | ✓ (HRD) | ✓ (keuangan) | ✓ (self) |
| Pegawai | ✓ | ✓ (read) | ✓ (unit) | ✓ | — | — |
| Organisasi | ✓ | ✓ (read) | ✓ (read) | ✓ | — | — |
| Rekrutmen | ✓ | ✓ (read) | — | ✓ | — | — |
| Administrasi HRD | ✓ | ✓ (approve) | ✓ (unit) | ✓ | — | — |
| Absensi | ✓ | ✓ | ✓ (unit) | ✓ | ✓ (self) | ✓ (self) |
| Izin & Cuti | ✓ | ✓ | ✓ (approve) | ✓ | ✓ (self) | ✓ (self) |
| Penggajian | — | ✓ (approve) | — | ✓ (verifikasi) | ✓ | ✓ (slip) |
| KPI | ✓ | ✓ (approve) | ✓ (nilai) | ✓ (verifikasi) | — | ✓ (self) |
| Pembinaan | ✓ | ✓ | ✓ (unit) | ✓ | — | ✓ (self) |
| Laporan | ✓ | ✓ | ✓ (unit) | ✓ | ✓ (payroll) | — |
| Pengaturan | ✓ | ✓ (read) | — | — | — | — |
| Profil Saya | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

Menu yang tidak memiliki permission **tidak dirender** (bukan sekadar dinonaktifkan).

### 3.3 Mobile

- Topbar ringkas: judul + tombol menu + notifikasi.
- Sidebar menjadi drawer dari kiri.
- Aksi utama menjadi tombol lebar penuh di bawah (sticky).
- Tabel berubah menjadi kartu berlabel (label: nilai).

---

## 4. Halaman dan komponen wajib

### 4.1 Dashboard
Komponen: kartu KPI (angka + label + tren), grafik garis (kehadiran 30 hari), grafik batang (komposisi pegawai per unit/kategori), daftar tugas (persetujuan tertunda), peringatan (kontrak berakhir, dokumen kedaluwarsa). Setiap widget menghormati scope data.

Keadaan kosong: "Belum ada data untuk periode ini." + tautan ke modul sumber.

### 4.2 Daftar pegawai
- Filter: pencarian (nama/NIP), unit, kategori, status, jabatan.
- Kolom: NIP, nama, unit, jabatan, kategori, status, aksi.
- Aksi: lihat profil, ubah, riwayat, unduh (sesuai permission).
- Bulk action (impor/ekspor) hanya bila diizinkan.
- Pagination server-side; jumlah per halaman 25/50/100.

### 4.3 Profil pegawai (tab)
1. Identitas
2. Keluarga
3. Pendidikan
4. Kontak & alamat
5. Kepegawaian (kategori, status, unit, jabatan, atasan)
6. Rekening & pajak/BPJS (sensitif)
7. Dokumen
8. Riwayat (status, jabatan, unit, atasan)
9. Absensi ringkas
10. Payroll ringkas (sesuai izin)
11. KPI & pembinaan

### 4.4 Formulir
- Label di atas field; tanda `*` untuk wajib; teks bantuan untuk aturan rumit.
- Validasi inline + ringkasan error di atas form.
- Tombol: `Simpan`, `Simpan & Tambah Lagi` (bila relevan), `Batal`.
- Perubahan belum disimpan memicu peringatan saat meninggalkan halaman.

### 4.5 Presensi (mobile) — alur kritis

```
┌──────────────────────────────┐
│  Presensi        Rabu, 18 Nov│
│  Shift Pagi 07:00–15:00      │
├──────────────────────────────┤
│  ● Dalam radius  42 m        │
│  Lokasi: Kantor PIS          │
│                              │
│  ┌────────────────────────┐  │
│  │                        │  │
│  │     [ kamera selfie ]  │  │
│  │                        │  │
│  └────────────────────────┘  │
│  Ambil Foto                  │
│                              │
│  [   PRESENSI MASUK   ]      │
└──────────────────────────────┘
```

Status GPS:
- `Mencari lokasi…` (spinner, tombol nonaktif)
- `Dalam radius (42 m)` (hijau, tombol aktif)
- `Di luar radius (1,2 km)` (merah, tombol nonaktif + tautan "Ajukan dispensasi")
- `Izin lokasi ditolak` (merah, panduan langkah + tautan dispensasi)
- `Akurasi rendah (±180 m)` (kuning, minta ulangi)

Status kamera:
- Pratinjau langsung, tombol "Ambil Foto", pratinjau hasil + "Ulangi".
- Jika kamera tidak tersedia/izin ditolak: pesan jelas + tautan dispensasi.
- Foto dikompresi di perangkat (maks. 1.280 px sisi terpanjang, JPEG kualitas 0,8, ≤ 2 MB).

Setelah sukses: kartu bukti berisi waktu server, lokasi, jarak, foto, dan status (tepat waktu/terlambat). Tombol "Presensi Pulang" muncul sesuai jadwal.

Pesan galat baku:
- "Presensi ditolak: Anda berada 1,2 km dari lokasi kerja (maksimum 100 m)."
- "Presensi ditolak: foto selfie wajib diambil."
- "Presensi sudah tercatat untuk shift ini pada 07:02."
- "GPS tidak tersedia. Aktifkan lokasi atau ajukan dispensasi."

### 4.6 Riwayat absensi (pegawai)
Kartu per tanggal: jam masuk/pulang, status, keterlambatan, foto (klik untuk pratinjau), tombol "Ajukan Koreksi" bila memenuhi syarat.

### 4.7 Koreksi absensi
Form: tanggal, jenis koreksi, jam yang benar, alasan (min. 10 karakter), bukti (opsional/wajib sesuai jenis). Timeline status: Diajukan → Atasan → HRD → Selesai.

### 4.8 Izin dan cuti
Form: jenis izin, tanggal mulai/selesai, jumlah hari (otomatis, dapat disesuaikan dengan alasan), alasan, dokumen (wajib sesuai jenis). Menampilkan saldo tersisa dan konflik tanggal.

### 4.9 Penggajian
- Daftar periode: kode, rentang, status (badge), jumlah pegawai, total bersih.
- Halaman periode: ringkasan angka, tombol aksi sesuai status (`Hitung`, `Periksa`, `Verifikasi`, `Setujui`, `Kunci`) dengan konfirmasi.
- Daftar pegawai: nama, unit, bruto, potongan, bersih, status; klik untuk rincian komponen.
- Rincian komponen menampilkan `formula_note` agar dapat diaudit.
- Slip gaji: pratinjau PDF + unduh; batch hanya untuk peran berwenang.

### 4.10 KPI
- Daftar penilaian per periode + progres (belum dinilai/dinilai/diverifikasi/disetujui).
- Form penilaian: indikator, bobot, target, realisasi, skor, catatan; total berbobot terlihat langsung.
- Hasil: nilai akhir, predikat, grafik tren antarperiode.

### 4.11 Laporan
- Halaman indeks 14 laporan (kartu).
- Setiap laporan: filter (tanggal/periode/unit/status/pegawai), pratinjau, tombol `Ekspor Excel`, `Ekspor PDF`, `Cetak`.
- Menampilkan jumlah baris hasil sebelum ekspor.

### 4.12 Pengaturan
Tab: Identitas aplikasi, Absensi (radius, akurasi, ukuran foto), Jadwal & shift, Payroll (tarif, BPJS, pajak), Alur persetujuan, Notifikasi, Keamanan, Backup.
Setiap perubahan kebijakan menampilkan `effective_from` dan dampaknya ("berlaku untuk periode berikutnya").

---

## 5. Komponen UI

| Komponen | Perilaku |
|---|---|
| Tabel data | Header sticky, sort, pagination server-side, empty state, loading skeleton |
| Filter bar | Dapat dilipat di mobile; tombol `Terapkan`, `Reset` |
| Badge status | Warna + teks; tidak bergantung pada warna saja |
| Timeline | Untuk alur persetujuan dan riwayat |
| Modal konfirmasi | Wajib untuk aksi destruktif; meminta alasan bila relevan |
| Toast | Sukses/gagal, hilang otomatis 5 detik (gagal tidak hilang otomatis) |
| Date picker | Format input `DD-MM-YYYY`, nilai tersimpan `YYYY-MM-DD` |
| Currency input | Mask rupiah, tanpa desimal untuk input pengguna |
| File upload | Batas ukuran + tipe ditampilkan sebelum unggah; progress bar |
| Empty state | Ikon + penjelasan + aksi yang disarankan |
| Error state | Pesan spesifik + langkah perbaikan, bukan "Terjadi kesalahan" saja |

---

## 6. Alur navigasi utama

### 6.1 Pegawai — presensi harian
`Login → Dashboard → Presensi → (GPS & kamera) → Konfirmasi → Bukti presensi`

### 6.2 HRD — tambah pegawai
`Pegawai → Tambah → Identitas → Keluarga → Pendidikan → Kepegawaian → Rekening/BPJS → Dokumen → Simpan → Profil`

### 6.3 HRD — impor Excel
`Pegawai → Impor → Unduh templat → Unggah → Pratinjau validasi → Konfirmasi → Laporan impor`

### 6.4 Keuangan — payroll
`Penggajian → Periode → Buka → Hitung → Periksa → Kirim ke HRD → Verifikasi → Setujui Pimpinan → Kunci → Slip`

### 6.5 Kepala bagian — KPI
`KPI → Periode → Pilih pegawai → Isi realisasi & skor → Simpan → Kirim ke HRD`

---

## 7. Aksesibilitas dan kesalahan

- Semua input memiliki `<label>` terkait; error dihubungkan via `aria-describedby`.
- Fokus terlihat jelas; urutan tab logis.
- Kontras teks minimal 4,5:1.
- Tombol ikon memiliki `aria-label`.
- Pesan galat tidak menyebut detail teknis (stack trace) ke pengguna.
- Konfirmasi aksi destruktif tidak dapat ditutup dengan klik luar tanpa konfirmasi.

---

## 8. PDF dan cetak

| Dokumen | Ukuran | Isi wajib |
|---|---|---|
| Slip gaji | A4 portrait | Identitas, periode, rincian, total, gaji bersih, catatan "dokumen internal" |
| SK & surat | A4 portrait | Kop, nomor, isi, tanda tangan pejabat, arsip |
| Laporan | A4 landscape (tabel lebar) | Judul, filter aktif, tanggal cetak, nomor halaman |
| Rekap absensi | A4 landscape | Unit, periode, kolom status, tanda tangan |

Header/footer PDF konsisten; tidak menampilkan data di luar filter.

---

## 9. Kriteria penerimaan UI

1. Semua halaman yang tercantum pada `SRS.md` memiliki rancangan dan dapat diakses sesuai peran.
2. Presensi dapat diselesaikan di layar 360 px dalam ≤ 4 langkah.
3. Pesan galat GPS/kamera/presensi ganda jelas dan diuji pada Tahap 4.
4. Tidak ada menu yang tampil tanpa permission.
5. Tabel tetap dapat dibaca di mobile (berubah menjadi kartu).
6. Semua form memiliki validasi inline + ringkasan error.

# CLARIFICATIONS — Pertanyaan dan Asumsi
## Aplikasi Kepegawaian PIS

| Atribut | Nilai |
|---|---|
| Versi | 1.0 |
| Tanggal | 18-11-2026 |
| Tujuan | Mencegah sistem mengarang kebijakan pesantren |
| Aturan | Setiap jawaban dicatat + tanggal + pemberi jawaban; perubahan keputusan masuk `CHANGELOG.md` |

---

## 1. Cara memakai dokumen ini

1. Setiap pertanyaan memiliki kode `K-xx`.
2. Jawab dengan format: `K-01: <jawaban> (pemberi: ..., tanggal: ...)`.
3. Selama belum dijawab, sistem memakai **asumsi sementara** yang ditandai di UI/dokumen dan **tidak boleh** dipakai untuk data produksi.
4. Payroll produksi tidak boleh dijalankan selama pertanyaan bertanda 🔴 belum dijawab.
5. Jika jawaban mengubah skema database, perubahan dilakukan lewat migration baru + catatan di `CHANGELOG.md` — bukan mengubah data historis.

Keterangan prioritas: 🔴 memblokir produksi · 🟠 memblokir tahap terkait · 🟢 dapat menyusul.

---

## 2. Penggajian (memblokir Tahap 5)

| Kode | Prioritas | Pertanyaan | Mengapa penting |
|---|---|---|---|
| K-01 | 🔴 | Rumus tunjangan kehadiran MSU/SD `Rp270.000 / 30 / 240`: angka 240 menyatakan apa (menit kerja? jam pelajaran? jumlah hari?) dan komponen apa yang dikalikan? | Menentukan rumus proporsional; tanpa ini hasil bisa salah total |
| K-02 | 🔴 | Bagaimana hubungan **gaji harian Rp51.000** dengan **basis pendidikan SMA/D3/S1 (700rb/900rb/1,1jt)**? Apakah basis pendidikan: (a) komponen terpisah, (b) pengganti gaji harian, atau (c) dasar kenaikan? | Menentukan struktur gaji pokok |
| K-03 | 🔴 | Kenaikan tahunan 5%: kapan diterapkan (Januari / ulang tahun bekerja), dihitung dari basis apa, dan apakah berlaku untuk pegawai Harian/Khidmah? | Menentukan jadwal & dasar kenaikan |
| K-04 | 🔴 | Peningkatan pendidikan D3 → S1 sebesar 15%: dihitung dari gaji terakhir yang mana, apakah menggantikan basis pendidikan, dan kapan berlaku? | Menghindari kenaikan ganda |
| K-05 | 🟠 | Tunjangan anak Rp100.000: batas usia anak, jumlah maksimal anak, syarat (sekolah/kuliah/belum menikah), dan apakah anak berkebutuhan khusus diatur khusus? | Menentukan penerima tunjangan |
| K-06 | 🟠 | Tunjangan beras: nominal per anggota keluarga, siapa yang dihitung (pasangan, anak, tanggungan lain), dan apakah diberikan bulanan? | Nominal belum ada di dokumen induk |
| K-07 | 🟠 | Tunjangan pasangan: bagaimana membuktikan "suami juga bekerja di pesantren"? Apakah berlaku sebaliknya (suami tidak mendapat bila istri bekerja di PIS)? | Mencegah pembayaran ganda |
| K-08 | 🟠 | Masa percobaan: berapa bulan, berapa gaji selama percobaan, dan apakah dapat diperpanjang? | Perhitungan pegawai baru |
| K-09 | 🔴 | PPh 21: memakai metode TER bulanan (PP 58/2023) atau perhitungan tahunan? Status PTKP apa yang dipakai bila data belum lengkap? Bagaimana pegawai tanpa NPWP? | Potongan pajak wajib benar |
| K-10 | 🟠 | BPJS: apakah basis Rp4.200.000 tetap untuk semua pegawai atau mengikuti gaji? Apakah ada batas atas/bawah upah? Kategori pegawai mana yang menjadi peserta? | Iuran pemberi & pegawai |
| K-11 | 🟠 | Uang snack: nominal per kehadiran, syarat jadwal (shift apa), dan definisi "Driver" (jabatan/penugasan)? | Komponen variabel |
| K-12 | 🟠 | THR, bonus, dan honorarium: rumus, syarat penerima, dan waktu pembayaran? | Komponen tidak boleh dikarang |
| K-28 | 🟠 | Toleransi keterlambatan default berapa menit, dan apakah potongan keterlambatan dihitung dari menit pertama atau setelah toleransi? Apakah ada batas maksimal potongan? | Potongan keterlambatan |
| K-29 | 🟠 | Apakah pegawai Harian dibayar berdasarkan hari hadir (bukan hari kalender)? Bagaimana perlakuan hari libur bagi mereka? | Kategori Harian |

---

## 3. Absensi dan jadwal (memblokir Tahap 4)

| Kode | Prioritas | Pertanyaan | Mengapa penting |
|---|---|---|---|
| K-13 | 🔴 | Koordinat dan radius setiap lokasi kerja (kantor pusat, unit pendidikan, lokasi lain), serta berapa lokasi yang harus didukung? | Validasi GPS tidak dapat diuji tanpa titik nyata |
| K-14 | 🟠 | Berapa ambang akurasi GPS yang diterima (mis. ≤100 m)? Apa yang terjadi bila akurasi rendah: tolak, tandai, atau minta ulang? | Mencegah presensi keliru |
| K-15 | 🟠 | Jadwal kerja aktual: jam masuk/pulang per unit, hari kerja (Senin–Sabtu?), shift, jadwal Jumat, dan jadwal Ramadan? | Perhitungan keterlambatan & alpa |
| K-16 | 🟠 | Aturan sakit: wajib surat dokter setelah berapa hari? Apakah izin sakit berbayar? Berapa jatah cuti tahunan dan bolehkah carry-over? | Izin & potongan |
| K-17 | 🟢 | Sumber kalender libur (pemerintah + pesantren) dan siapa yang memperbarui? | Perhitungan hari kerja |
| K-24 | 🟠 | Batas waktu pengajuan koreksi absensi (mis. 7 hari setelah tanggal) dan apa yang terjadi bila atasan langsung sedang cuti? | Alur koreksi |
| K-27 | 🟠 | Apakah presensi pulang wajib? Bagaimana bila pegawai lupa presensi pulang? Apakah ada lembur dan bagaimana perhitungannya? | Rekap & payroll |
| K-30 | 🟢 | Apakah pegawai boleh presensi dari lokasi berbeda untuk penugasan luar (dinas) tanpa dispensasi? | Fleksibilitas lokasi |

---

## 4. Organisasi, pegawai, dan data awal (memblokir Tahap 2/3)

| Kode | Prioritas | Pertanyaan | Mengapa penting |
|---|---|---|---|
| K-18 | 🟠 | Format NIP resmi (contoh: `PIS-2026-0001`?) dan format nomor kontrak/SK/surat? | Penomoran otomatis |
| K-21 | 🟠 | Apakah pimpinan dan kepala bagian juga tercatat sebagai pegawai dan melakukan presensi? Bagaimana penanganan peran ganda? | Akun & presensi |
| K-22 | 🟠 | File Excel data pegawai yang tersedia: berapa baris, kolom apa saja, dan apakah memuat data keluarga/pendidikan/rekening? Mohon disertakan contoh (anonim). | Impor tidak bisa dirancang tanpa struktur nyata |
| K-26 | 🟠 | SK/keputusan jenis apa yang memerlukan persetujuan Yayasan, dan siapa pejabat yang berwenang? | Alur persetujuan |
| K-25 | 🟢 | Prosedur pegawai keluar: cutoff gaji, pengembalian aset, pencabutan akses, dan dokumen yang wajib? | Pemberhentian |
| K-31 | 🟠 | Struktur organisasi aktual (yayasan, bidang, bagian, seksi, unit pendidikan) beserta nama jabatan? | Master organisasi |
| K-32 | 🟢 | Kategori "Khidmah khusus" diberikan kepada siapa dan apa bedanya dengan Khidmah biasa dalam hak gaji? | Pembedaan gaji |
| K-33 | 🟠 | Apakah data pegawai boleh disimpan selamanya atau ada kebijakan retensi? | Privasi & retensi |

---

## 5. Infrastruktur dan operasional

| Kode | Prioritas | Pertanyaan | Mengapa penting |
|---|---|---|---|
| K-23 | 🔴 | Ketersediaan server/hosting, domain, dan izin instalasi PHP/MySQL di komputer pengembangan. Apakah ada server Linux/VPS untuk produksi? | Menentukan kelayakan deployment |
| K-34 | 🟠 | Apakah tersedia SMTP/email resmi untuk reset password & notifikasi? Apakah ada gateway WhatsApp? | Autentikasi & notifikasi |
| K-35 | 🟠 | Siapa administrator sistem setelah go-live dan bagaimana prosedur darurat (mis. akun terkunci)? | Operasional |
| K-19 | 🟢 | Berapa lama foto presensi dan dokumen disimpan? Apakah ada kewajiban arsip? | Kapasitas & privasi |
| K-20 | 🟠 | Bagaimana agregasi KPI triwulanan (rata-rata 3 bulan? bulan terakhir?) dan rentang predikat (mis. ≥90 sangat baik)? | Modul KPI |
| K-36 | 🟢 | Zona waktu perangkat pegawai dan jadwal server: apakah server produksi akan memakai WIB? | Konsistensi waktu |

---

## 6. Asumsi sementara (dipakai sampai dijawab)

| Kode | Asumsi | Dampak bila salah |
|---|---|---|
| A-01 | Satu pegawai = satu akun; satu akun = satu pegawai | Perlu perubahan relasi & migrasi |
| A-02 | Presensi terdiri atas masuk dan pulang per shift | Rekap & payroll |
| A-03 | Payroll dihitung per bulan kalender (1–akhir bulan) | Potongan & prorata |
| A-04 | Hari kerja awal: Senin–Sabtu, jam 07.00–15.00 (contoh, bukan keputusan) | Semua perhitungan absensi |
| A-05 | Jatah cuti tahunan 12 hari (belum dikonfirmasi) | Saldo cuti |
| A-06 | Foto presensi disimpan 2 tahun | Kapasitas penyimpanan |
| A-07 | Tarif PPh 21 & PTKP **tidak diisi** sampai K-09 dijawab; payroll diblokir bila kosong | Payroll tidak dapat final |
| A-08 | Radius GPS awal 100 m, akurasi maksimum 100 m | Presensi ditolak/ diterima salah |
| A-09 | Uang snack dan tunjangan beras memakai nominal konfigurasi kosong sampai dijawab | Komponen tidak dihitung |
| A-10 | Impor awal memakai data DEMO bila file Excel belum tersedia | Data produksi tidak boleh tercampur |
| A-11 | Tanggal efektif kenaikan tahunan = 1 Januari (sementara) | Gaji pegawai |
| A-12 | Tunjangan kehadiran MSU/SD dihitung proporsional berdasarkan hari hadir | Gaji MSU/SD |
| A-13 | Pimpinan/kepala bagian juga pegawai dan dapat presensi | Akun & role |
| A-14 | Tidak ada integrasi transfer bank otomatis | Proses pembayaran |
| A-15 | Kalender libur mengikuti penetapan pemerintah + pesantren yang diinput HRD | Hari kerja |

Asumsi **wajib** ditampilkan pada UI pengaturan sebagai "belum dikonfirmasi" dan dicatat pada laporan tahap.

---

## 7. Keputusan yang sudah diambil

| Tanggal | Keputusan | Oleh | Catatan |
|---|---|---|---|
| 18-11-2026 | Tahap 1 dikerjakan lebih dahulu (blueprint), belum menulis kode aplikasi | Pengguna | Sesuai aturan dokumen induk |
| 18-11-2026 | Teknologi: Laravel + MySQL (instal PHP/MySQL), bukan mengganti stack | Pengguna | Laravel 13 + MySQL 8.4 |
| 18-11-2026 | Versi Laravel 13 (PHP 8.3–8.5) dan MySQL 8.4.9 mengikuti ketersediaan resmi | Tim | Diverifikasi dari laravel.com & winget |

---

## 8. Daftar pertanyaan untuk sesi klarifikasi berikutnya

Urutan yang disarankan (paling memblokir lebih dahulu):

1. **K-01, K-02, K-03, K-04, K-09, K-23** — payroll & infrastruktur.
2. **K-13, K-15, K-14** — absensi dan jadwal nyata.
3. **K-16, K-22, K-31** — izin, data awal, struktur organisasi.
4. **K-05 s.d. K-12, K-28, K-29** — tunjangan dan komponen gaji.
5. Sisanya dapat menyusul sebelum tahap terkait.

Jika jawaban belum tersedia saat tahap terkait dimulai, pekerjaan tetap dapat berjalan memakai konfigurasi kosong/DEMO, tetapi **tidak boleh** dinyatakan siap produksi.

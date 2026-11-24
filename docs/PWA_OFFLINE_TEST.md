# Panduan Uji PWA & Presensi Offline

Dokumen ini panduan bagi tim IT Pesantren untuk melakukan verifikasi fitur **Presensi Offline** sebelum dirilis ke pegawai.

---

## 1. Persiapan
- HP Android/iOS (Chrome atau Safari).
- Pastikan aplikasi PIS HRIS bisa dibuka via browser HP.
- **Instalasi**: Buka menu browser (titik tiga) -> pilih **"Add to Home Screen" / "Tambahkan ke Layar Utama"**. Aplikasi sekarang berjalan layaknya aplikasi native.

## 2. Prosedur Uji Presensi Offline
1. **Langkah awal**: Buka aplikasi PIS HRIS di HP (status harus sudah login).
2. **Putus Koneksi**: Aktifkan **Mode Pesawat (Airplane Mode)**.
3. **Presensi**: 
   - Klik menu **Presensi**.
   - Isi form (GPS harus terbaca duluan, foto selfie).
   - Klik tombol **Presensi Masuk**.
   - **Hasil Diharapkan**: Muncul alert: *"Internet mati. Absensi disimpan di HP..."*. Form otomatis ter-reset.
4. **Verifikasi Antrean**:
   - Refresh halaman atau masuk ke menu Presensi lagi.
   - Periksa apakah muncul tombol **"Sinkronisasi [N] Presensi Offline"** di bagian bawah.
5. **Sinkronisasi**:
   - Matikan Mode Pesawat (nyalakan Wi-Fi/Data).
   - Tunggu 1-2 detik atau klik tombol **"Sinkronisasi [N] Presensi Offline"**.
   - **Hasil Diharapkan**: Muncul notifikasi "1 presensi tertunda berhasil dikirim". Halaman otomatis refresh.
6. **Validasi**:
   - Cek **Riwayat Absensi**. Presensi tadi harus sudah muncul dengan status sukses.

## 3. Trouble Shooting
- **Gagal Sinkron**: Cek koneksi internet. Tombol sinkron akan tetap muncul jika gagal (data tidak hilang dari HP).
- **GPS Tidak Terbaca**: Pastikan akses lokasi diizinkan untuk PIS HRIS di pengaturan HP.
- **Ikon Aplikasi**: Jika belum muncul ikon, pastikan `manifest.json` terbaca (bisa cek via Chrome DevTools di PC/Laptop dengan *Inspect* ke situs).

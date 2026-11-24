# Panduan Deployment & Operasional — Aplikasi Kepegawaian PIS (HRIS)

Dokumen ini menjelaskan langkah-langkah instalasi, migrasi, konfigurasi, dan pemeliharaan Aplikasi Kepegawaian Pesantren Imam Syafi'i di lingkungan produksi.

---

## 1. Persyaratan Sistem (Prasyarat)
- **OS**: Linux Ubuntu 22.04 LTS / Debian 12 (Direkomendasikan) atau Windows Server.
- **PHP**: Versi 8.2 atau lebih tinggi dengan ekstensi: `BCMath`, `Ctype`, `Fileinfo`, `JSON`, `Mbstring`, `OpenSSL`, `PDO`, `Tokenizer`, `XML`, `GD`, `Zip`.
- **Database**: MySQL 8.0 / MariaDB 10.6+.
- **Web Server**: Nginx atau Apache (Nginx direkomendasikan).
- **Process Manager**: Supervisor (untuk antrean Laravel queue).

---

## 2. Langkah Instalasi Awal
1. **Kloning Repositori / Unggah Berkas**:
   Letakkan direktori aplikasi di `/var/www/aplikasi-kepegawaian`.
2. **Atur Hak Akses Direktori**:
   ```bash
   sudo chown -R www-data:www-data /var/www/aplikasi-kepegawaian
   sudo chmod -R 775 /var/www/aplikasi-kepegawaian/storage /var/www/aplikasi-kepegawaian/bootstrap/cache
   ```
3. **Instal Dependensi Composer**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. **Konfigurasi `.env`**:
   Salin `.env.example` menjadi `.env`, lalu sesuaikan kunci berikut:
   ```env
   APP_NAME="Aplikasi Kepegawaian PIS"
   APP_ENV=production
   APP_KEY=base64:... (buat dengan php artisan key:generate)
   APP_DEBUG=false
   APP_URL=https://kepegawaian.pesantren.id

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pis_kepegawaian_prod
   DB_USERNAME=db_user
   DB_PASSWORD=db_password

   BROADCAST_CONNECTION=log
   FILESYSTEM_DISK=local
   QUEUE_CONNECTION=database
   SESSION_DRIVER=database
   SESSION_LIFETIME=120
   ```

---

## 3. Migrasi & Seeder Master Data
Jalankan migrasi database dan seeders wajib (komponen gaji, parameter, PTKP/TER, peran, hak akses, dan data demo/awal):
```bash
php artisan migrate --force
php artisan db:seed --force
```

---

## 4. Konfigurasi Scheduler & Queue Worker
Untuk mengaktifkan pengingat kontrak (H-30) dan tugas terjadwal:
1. Tambahkan Cron Job di server (`crontab -e`):
   ```cron
   * * * * * cd /var/www/aplikasi-kepegawaian && php artisan schedule:run >> /dev/null 2>&1
   ```
2. Konfigurasi Supervisor untuk Queue (`/etc/supervisor/conf.d/pis-worker.conf`):
   ```ini
   [program:pis-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/aplikasi-kepegawaian/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   user=www-data
   numprocs=1
   redirect_stderr=true
   stdout_logfile=/var/www/aplikasi-kepegawaian/storage/logs/worker.log
   ```
   Aktifkan supervisor:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start pis-worker.*
   ```

---

## 5. Cache & Optimasi Produksi
Jalankan perintah optimasi setelah setiap pembaruan kode:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 6. Akses & Kredensial Default
- **Administrator Sistem**: `admin` / `Admin#2026` (wajib segera diganti setelah login pertama).
- **Akun Demo**: Tersedia akun `hrd.demo`, `keuangan.demo`, `pimpinan.demo`, `kepala.demo`, dan `pegawai.demo` dengan sandi `Demo#2026` (bila menggunakan seeder demo).

---

## 7. Kebijakan Keamanan & Pencadangan (Backup)
- **Backup Database Harian**: Buat skrip otomatis `mysqldump` yang dijalankan via cron pukul 02:00 WIB ke penyimpanan terenkripsi/cloud.
- **Enkripsi NIK**: NIK pegawai dienkripsi secara otomatis pada level Eloquent menggunakan enkripsi AES Laravel (`APP_KEY`).
- **Audit Trail**: Seluruh operasi penting (perubahan data pegawai, payroll, persetujuan) dicatat dalam tabel `audit_logs` secara otomatis.

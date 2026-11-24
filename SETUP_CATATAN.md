# Catatan Perbaikan & Setup

## 1. phpunit.xml — Sudah Diperbaiki ✅
- `DB_PORT` diubah dari `3306` → `3307` di `phpunit.xml`.
- Tes sekarang bisa dijalankan langsung tanpa override env:
  ```powershell
  & ".\.tools\php\php.exe" artisan test
  ```

## 2. MySQL84 (port 3306) — Belum Diperbaiki ⏳
- Service `MySQL84` berjalan di port 3306 dengan password root tidak diketahui.
- **Cara stop (butuh Administrator):**
  1. Buka `services.msc` (Win+R → `services.msc` → Enter).
  2. Cari `MySQL84` → klik kanan → **Stop**.
  3. Atau: buka CMD sebagai Administrator, jalankan:
     ```
     net stop MySQL84
     ```
  4. Alternatif via `sc`:
     ```
     sc stop MySQL84
     ```
- Instance MySQL baru berjalan di port 3307 (data di `C:\Users\USER\AppData\Local\mysql-data`).
- Jika port 3306 dibutuhkan aplikasi lain, pastikan hanya satu instance MySQL yang aktif.

## 3. .env — Catatan Produksi ⚠️
- `APP_DEBUG=true` → **wajib ubah `false`** sebelum production.
- `DB_PASSWORD` plain teks → pastikan file `.env` dibatasi akses:
  - Windows: klik kanan `.env` → Properties → Security → pastikan hanya user Anda yang punya Full Control.
- `SESSION_ENCRYPT=false` → untuk session di database, bisa `true` untuk keamanan tambahan.

## 4. Cara Jalankan Aplikasi (Setelah Restart Komputer)

### Opsi A: Instance baru (port 3307) — direkomendasikan
```powershell
# Terminal 1 — MySQL
& "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe" --datadir="C:\Users\USER\AppData\Local\mysql-data" --port=3307 --console

# Terminal 2 — Aplikasi
cd "D:\MEGA\HRD\APLIKASI KEPEGAWAIAN"
& ".\.tools\php\php.exe" artisan serve
```

### Opsi B: Service MySQL84 (port 3306) — jika service running
```powershell
cd "D:\MEGA\HRD\APLIKASI KEPEGAWAIAN"
& ".\.tools\php\php.exe" artisan serve
```
> Pastikan MySQL84 service berjalan. Jika tidak, jalankan Opsi A.

## 5. Ringkasan Tes Terakhir
- **89 tests, 427 assertions — PASS 100%**
- Modul: Auth, RBAC, Employee Management, Attendance, Leave, Payroll, KPI/Coaching, Security, Recruitment.
- Semua fitur Tahap 1–8 (sesuai README) berfungsi.

## 6. Login Demo
| Username | Password | Role |
|---|---|---|
| `admin` | `Admin#2026` | Administrator |
| `hrd.demo` | `Demo#2026` | HRD |
| `keuangan.demo` | `Demo#2026` | Keuangan |
| `pimpinan.demo` | `Demo#2026` | Pimpinan |
| `kepala.demo` | `Demo#2026` | Kepala Bagian |
| `pegawai.demo` | `Demo#2026` | Pegawai |

---
*Pembaruan: 24 November 2026*
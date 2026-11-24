# ACCESS CONTROL — Aplikasi Kepegawaian PIS

| Atribut | Nilai |
|---|---|
| Versi | 1.0 |
| Tanggal | 18-11-2026 |
| Acuan | `MASTER_SPECIFICATION.md`, `SRS.md`, `DATABASE_DESIGN.md` |
| Model | RBAC + data scope (self / unit / all) + segregation of duties |

---

## 1. Prinsip

1. **Deny by default** — akses hanya diberikan bila permission dan scope terpenuhi.
2. **Least privilege** — peran hanya mendapat yang diperlukan untuk tugasnya.
3. **Dua dimensi otorisasi**: *apa* (permission) dan *data mana* (scope).
4. **Segregation of duties** — pemohon ≠ penyetuju; penghitung payroll ≠ pemberi persetujuan akhir.
5. **Audit** — aksi pada data sensitif selalu dicatat (siapa, kapan, data apa).
6. **Administrator bukan "super user bisnis"** — admin mengelola sistem, tetapi akses gaji/berkas pribadi dibatasi dan diaudit.

---

## 2. Peran

| Kode | Nama | Cakupan data bawaan |
|---|---|---|
| `administrator` | Administrator | Semua data **kecuali** payroll & dokumen pribadi (dapat diberikan terbatas + audit) |
| `pimpinan` | Pimpinan | Semua data (read), persetujuan akhir |
| `kepala_bagian` | Kepala Bidang/Bagian | Unit kerja + turunannya, bawahan langsung |
| `hrd` | HRD | Semua data pegawai & administrasi; verifikasi |
| `keuangan` | Keuangan | Payroll, BPJS, pajak, laporan keuangan; data pegawai read-only |
| `pegawai` | Pegawai | Data sendiri |

Catatan: seorang pengguna dapat memiliki lebih dari satu peran (mis. Kepala Bagian yang juga Pegawai), dengan `role_user.org_unit_id` untuk membatasi cakupan peran.

---

## 3. Klasifikasi data

| Kelas | Contoh | Aturan |
|---|---|---|
| Internal | Struktur organisasi, jadwal, pengumuman | Semua pengguna login |
| Terbatas | Data pegawai non-sensitif, absensi unit | Sesuai peran + unit |
| Rahasia | Gaji, slip, rekening, NPWP, NIK, dokumen pribadi, catatan medis | Pemilik + peran berwenang; akses dicatat |
| Sangat rahasia | Audit log, backup, kredensial integrasi | Administrator tertentu; tidak melalui UI biasa |

---

## 4. Katalog permission

Format: `modul.aksi`. Katalog ini adalah **sumber kebenaran**; seeder Tahap 2 wajib mengikuti daftar ini. `S` = hanya data sendiri, `U` = unit/turunan unit.

### 4.1 Dashboard
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `dashboard.pimpinan` | Dashboard ringkasan pimpinan | ✓ | ✓ | — | — | — | — |
| `dashboard.hrd` | Dashboard HRD | ✓ | — | — | ✓ | — | — |
| `dashboard.kepala` | Dashboard unit | — | — | ✓ | — | — | — |
| `dashboard.keuangan` | Dashboard payroll/keuangan | — | — | — | — | ✓ | — |
| `dashboard.pegawai` | Dashboard pegawai (self) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

### 4.2 Pegawai
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `employee.view.self` | Lihat profil sendiri | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ (S) |
| `employee.view.unit` | Lihat pegawai unit | — | ✓ | ✓ (U) | ✓ | — | — |
| `employee.view.all` | Lihat semua pegawai | ✓ | ✓ | — | ✓ | ✓ | — |
| `employee.create` | Tambah pegawai | — | — | — | ✓ | — | — |
| `employee.update` | Ubah data pegawai | — | — | — | ✓ | — | — |
| `employee.delete` | Hapus (soft delete) pegawai | ✓ | — | — | — | — | — |
| `employee.import` | Impor Excel | — | — | — | ✓ | — | — |
| `employee.export` | Ekspor data pegawai | ✓ | ✓ | — | ✓ | ✓ | — |
| `employee.history.view` | Riwayat status/jabatan/unit | — | ✓ | ✓ (U) | ✓ | — | — |
| `employee.document.view` | Lihat dokumen pegawai | — | ✓ | — | ✓ | — | ✓ (S) |
| `employee.document.manage` | Unggah/verifikasi dokumen | — | — | — | ✓ | — | ✓ (S, unggah) |
| `employee.sensitive.view` | Lihat NIK/rekening/NPWP | — | — | — | ✓ | ✓ | — |
| `employee.payroll_profile.manage` | Ubah profil penggajian | — | — | — | ✓ | ✓ | — |

### 4.3 Organisasi dan jadwal
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `org.view` | Lihat struktur organisasi | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `org.manage` | Kelola unit & jabatan | ✓ | — | — | ✓ | — | — |
| `work_location.manage` | Kelola lokasi & radius GPS | ✓ | — | — | ✓ | — | — |
| `schedule.view` | Lihat jadwal | ✓ | ✓ | ✓ (U) | ✓ | — | ✓ (S) |
| `schedule.manage` | Kelola shift & jadwal | ✓ | — | — | ✓ | — | — |
| `holiday.manage` | Kelola hari libur | ✓ | — | — | ✓ | — | — |

### 4.4 Pengguna dan sistem
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `user.view` | Lihat daftar pengguna | ✓ | ✓ | — | — | — | — |
| `user.manage` | Tambah/ubah pengguna | ✓ | — | — | — | — | — |
| `user.reset_password` | Reset password | ✓ | — | — | — | — | — |
| `user.deactivate` | Nonaktifkan akun | ✓ | — | — | — | — | — |
| `role.manage` | Kelola peran & permission | ✓ | — | — | — | — | — |
| `audit.view` | Lihat audit trail | ✓ | ✓ | — | — | — | — |
| `login_history.view` | Lihat riwayat login | ✓ | — | — | — | — | — |
| `setting.view` | Lihat pengaturan | ✓ | ✓ | — | ✓ | ✓ | — |
| `setting.manage` | Ubah pengaturan | ✓ | — | — | — | — | — |
| `approval_flow.manage` | Kelola alur persetujuan | ✓ | — | — | — | — | — |
| `backup.manage` | Buat/unduh backup | ✓ | — | — | — | — | — |
| `backup.restore` | Pulihkan backup | ✓ | — | — | — | — | — |
| `demo_data.manage` | Kelola data DEMO | ✓ | — | — | — | — | — |

### 4.5 Rekrutmen
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `vacancy.view` | Lihat lowongan | ✓ | ✓ | — | ✓ | — | — |
| `vacancy.manage` | Kelola lowongan | ✓ | — | — | ✓ | — | — |
| `applicant.view` | Lihat pelamar | ✓ | ✓ | — | ✓ | — | — |
| `applicant.manage` | Kelola data pelamar | ✓ | — | — | ✓ | — | — |
| `interview.assess` | Menilai wawancara | ✓ | ✓ | ✓ (U) | ✓ | — | — |
| `applicant.decide` | Keputusan penerimaan | — | ✓ | — | ✓ | — | — |
| `applicant.convert` | Konversi pelamar → pegawai | — | — | — | ✓ | — | — |

### 4.6 Administrasi HRD
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `contract.view` | Lihat kontrak | ✓ | ✓ | ✓ (U) | ✓ | — | ✓ (S) |
| `contract.manage` | Kelola kontrak | ✓ | — | — | ✓ | — | — |
| `contract.approve` | Setujui kontrak | — | ✓ | — | — | — | — |
| `decree.view` | Lihat SK | ✓ | ✓ | ✓ (U) | ✓ | — | ✓ (S) |
| `decree.manage` | Kelola SK | ✓ | — | — | ✓ | — | — |
| `decree.approve` | Setujui SK | — | ✓ | — | — | — | — |
| `mutation.view` | Lihat mutasi | ✓ | ✓ | ✓ (U) | ✓ | — | ✓ (S) |
| `mutation.manage` | Usulkan mutasi | — | — | ✓ (U) | ✓ | — | — |
| `mutation.approve` | Setujui mutasi | — | ✓ | — | — | — | — |
| `letter.view` | Lihat surat | ✓ | ✓ | ✓ (U) | ✓ | — | ✓ (S) |
| `letter.manage` | Buat surat | — | — | — | ✓ | — | — |
| `letter.approve` | Setujui surat | — | ✓ | — | — | — | — |

### 4.7 Absensi
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `attendance.self` | Presensi masuk/pulang | — | ✓ | ✓ | ✓ | ✓ | ✓ |
| `attendance.view.self` | Riwayat absensi sendiri | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `attendance.view.unit` | Absensi unit | — | ✓ | ✓ (U) | ✓ | — | — |
| `attendance.view.all` | Absensi semua | ✓ | ✓ | — | ✓ | — | — |
| `attendance.report` | Laporan absensi | ✓ | ✓ | ✓ (U) | ✓ | — | — |
| `attendance.correction.request` | Ajukan koreksi | — | ✓ | ✓ | ✓ | ✓ | ✓ |
| `attendance.correction.approve.supervisor` | Setujui koreksi (atasan) | — | — | ✓ (U) | — | — | — |
| `attendance.correction.approve.hrd` | Verifikasi koreksi (HRD) | — | — | — | ✓ | — | — |
| `attendance.exception.manage` | Kelola dispensasi | ✓ | — | — | ✓ | — | — |

### 4.8 Izin dan cuti
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `leave.request` | Ajukan izin/cuti | — | ✓ | ✓ | ✓ | ✓ | ✓ |
| `leave.view.self` | Riwayat izin sendiri | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `leave.view.unit` | Izin unit | — | ✓ | ✓ (U) | ✓ | — | — |
| `leave.view.all` | Izin semua | ✓ | ✓ | — | ✓ | — | — |
| `leave.approve.supervisor` | Setujui izin (atasan) | — | — | ✓ (U) | — | — | — |
| `leave.approve.hrd` | Verifikasi izin (HRD) | — | — | — | ✓ | — | — |
| `leave.type.manage` | Kelola jenis izin | ✓ | — | — | ✓ | — | — |
| `leave.balance.manage` | Kelola saldo cuti | — | — | — | ✓ | — | — |

### 4.9 Penggajian
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `payroll.view.self` | Lihat slip sendiri | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ (S) |
| `payslip.download.self` | Unduh slip sendiri | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ (S) |
| `payroll.view.all` | Lihat payroll semua | — | ✓ | — | ✓ | ✓ | — |
| `payroll.sensitive.view` | Lihat rekening/daftar transfer | — | ✓ | — | — | ✓ | — |
| `payroll.component.view` | Lihat komponen & tarif | ✓ | ✓ | — | ✓ | ✓ | — |
| `payroll.component.manage` | Kelola komponen & tarif | — | — | — | — | ✓ | — |
| `payroll.period.manage` | Buka/tutup periode | — | — | — | — | ✓ | — |
| `payroll.calculate` | Hitung payroll | — | — | — | — | ✓ | — |
| `payroll.review` | Periksa hasil hitung | — | — | — | ✓ | ✓ | — |
| `payroll.verify.hrd` | Verifikasi HRD | — | — | — | ✓ | — | — |
| `payroll.verify.finance` | Verifikasi keuangan | — | — | — | — | ✓ | — |
| `payroll.approve` | Persetujuan akhir | — | ✓ | — | — | — | — |
| `payroll.lock` | Kunci periode | — | — | — | — | ✓ | — |
| `payroll.adjustment.manage` | Koreksi/penyesuaian | — | — | — | ✓ | ✓ | — |
| `payroll.report` | Laporan payroll | — | ✓ | — | ✓ | ✓ | — |
| `tax.manage` | Konfigurasi PPh 21 | ✓ | — | — | — | ✓ | — |
| `bpjs.manage` | Konfigurasi BPJS | ✓ | — | — | — | ✓ | — |

### 4.10 KPI dan pembinaan
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `kpi.template.manage` | Kelola template & indikator | ✓ | — | — | ✓ | — | — |
| `kpi.period.manage` | Kelola periode KPI | ✓ | — | — | ✓ | — | — |
| `kpi.assess` | Menilai KPI bawahan | — | — | ✓ (U) | — | — | — |
| `kpi.view.self` | Lihat KPI sendiri | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ (S) |
| `kpi.view.unit` | KPI unit | — | ✓ | ✓ (U) | ✓ | — | — |
| `kpi.view.all` | KPI semua | ✓ | ✓ | — | ✓ | — | — |
| `kpi.verify` | Verifikasi KPI | — | — | — | ✓ | — | — |
| `kpi.approve` | Setujui KPI | — | ✓ | — | — | — | — |
| `kpi.report` | Laporan KPI | ✓ | ✓ | — | ✓ | — | — |
| `kpi.unlock` | Buka penilaian terkunci | — | ✓ | — | — | — | — |
| `coaching.view.self` | Lihat pembinaan sendiri | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ (S) |
| `coaching.view.unit` | Pembinaan unit | — | ✓ | ✓ (U) | ✓ | — | — |
| `coaching.view.all` | Pembinaan semua | ✓ | ✓ | — | ✓ | — | — |
| `coaching.manage` | Catat pembinaan | — | — | ✓ (U) | ✓ | — | — |
| `coaching.approve` | Setujui pembinaan berat | — | ✓ | — | — | — | — |

### 4.11 Laporan
| Kode | Deskripsi | ADM | PIM | KAB | HRD | KEU | PEG |
|---|---|---|---|---|---|---|---|
| `report.employee` | Laporan pegawai/unit/status/masa kerja/kontrak/turnover | ✓ | ✓ | ✓ (U) | ✓ | — | — |
| `report.attendance` | Laporan absensi/keterlambatan | ✓ | ✓ | ✓ (U) | ✓ | — | — |
| `report.leave` | Laporan izin/cuti | ✓ | ✓ | ✓ (U) | ✓ | — | — |
| `report.payroll` | Laporan gaji/tunjangan/potongan/transfer | — | ✓ | — | ✓ | ✓ | — |
| `report.bpjs` | Laporan BPJS | — | ✓ | — | ✓ | ✓ | — |
| `report.tax` | Laporan PPh 21 | — | ✓ | — | ✓ | ✓ | — |
| `report.kpi` | Laporan KPI | ✓ | ✓ | ✓ (U) | ✓ | — | — |
| `report.coaching` | Laporan pembinaan | ✓ | ✓ | ✓ (U) | ✓ | — | — |
| `report.export` | Ekspor Excel/PDF | ✓ | ✓ | ✓ (U) | ✓ | ✓ | — |

---

## 5. Aturan scope data

### 5.1 `self`
Pemetaan: `users.employee_id` = `resource.employee_id`.
Berlaku untuk: profil sendiri, absensi sendiri, slip gaji sendiri, KPI sendiri, pembinaan sendiri, dokumen sendiri.

### 5.2 `unit` (subtree)
```sql
-- unit pengguna dari role_user.org_unit_id atau employees.org_unit_id
WITH RECURSIVE unit_tree AS (
    SELECT id FROM org_units WHERE id = :user_unit_id
    UNION ALL
    SELECT o.id FROM org_units o JOIN unit_tree u ON o.parent_id = u.id
)
SELECT e.* FROM employees e WHERE e.org_unit_id IN (SELECT id FROM unit_tree);
```
Kepala bagian dapat melihat seluruh unit turunannya. Untuk persetujuan, approver konkret harus atasan langsung (`employees.direct_supervisor_id`) atau kepala unit; bila tidak ditemukan, eskalasi ke HRD.

### 5.3 `all`
HRD, Pimpinan, dan peran dengan permission eksplisit. Tetap tunduk pada klasifikasi data (mis. HRD dapat melihat data pegawai, tetapi tidak otomatis boleh mengubah tarif payroll — itu `payroll.component.manage` milik Keuangan).

### 5.4 Batasan tambahan
- Data payroll hanya untuk pemilik slip dan peran payroll.
- Dokumen pribadi: pemilik, HRD, Pimpinan (read), tanpa unduh massal.
- Audit log: read-only, tanpa edit/hapus.
- Ekspor data sensitif dicatat di `report_exports` + `audit_logs`.

---

## 6. Kewenangan persetujuan

| Objek | Penyusun | Verifikator | Penyetuju akhir | Tidak boleh |
|---|---|---|---|---|
| Koreksi absensi | Pegawai | Atasan → HRD | HRD | Pemohon menyetujui sendiri |
| Izin/cuti | Pegawai | Atasan → HRD | HRD (sesuai jenis) | Atasan menyetujui izinnya sendiri |
| Kontrak kerja | HRD | — | Pimpinan | HRD menyetujui kontrak sendiri |
| SK pengangkatan/mutasi | HRD | — | Pimpinan (Yayasan bila perlu) | — |
| Payroll | Keuangan (hitung) | HRD | Pimpinan | Penghitung = penyetuju |
| KPI | Kepala bagian | HRD | Pimpinan | Penilai = verifikator |
| Pembinaan berat | HRD/atasan | — | Pimpinan | — |
| Pemberhentian | HRD/atasan | — | Pimpinan/Yayasan | — |
| Mutasi | Atasan/HRD | HRD | Pimpinan | — |

Aturan teknis: `approval_actions.actor_id != approval_instances.requested_by`. Jika atasan langsung adalah pemohon, langkah atasan dilewati otomatis dengan catatan audit.

---

## 7. Implementasi (Laravel)

1. Permission disimpan di `permissions`; pengecekan memakai `Gate::allows($code)` atau middleware `can:$code`.
2. Setiap model memiliki Policy (`EmployeePolicy`, `AttendancePolicy`, `PayslipPolicy`, dll) yang menggabungkan permission + scope.
3. Query scope wajib melalui `EmployeeScope`/`DataScope` service; **tidak ada** query langsung `Employee::all()` pada controller.
4. Route admin dilindungi middleware `auth`, `verified` (bila ada), `can:...`, `throttle`.
5. Aksi sensitif (lihat gaji orang lain, unduh dokumen, ekspor) menulis `audit_logs` dengan event `view_sensitive`/`export`.
6. Uji otorisasi wajib pada Tahap 2 dan diulang pada Tahap 8 (lihat `TEST_REPORT.md`).

---

## 8. Matriks uji otorisasi minimal

| Skenario | Harapan |
|---|---|
| Pegawai membuka `/employees/{id}` milik orang lain | 403 |
| Pegawai membuka slip gaji pegawai lain | 403 |
| Kepala bagian membuka pegawai unit lain | 403 |
| Kepala bagian menyetujui izin pegawai unit lain | 403 |
| HRD mengubah tarif payroll | 403 |
| Keuangan menyetujui payroll (bukan verifikasi) | 403 |
| Pimpinan menyetujui pengajuannya sendiri | 403 |
| Administrator membuka slip gaji tanpa permission | 403 + tercatat |
| Pengguna nonaktif mengakses API | 401/403 |
| Akses langsung URL laporan dengan filter unit lain | Data terfilter/403 |

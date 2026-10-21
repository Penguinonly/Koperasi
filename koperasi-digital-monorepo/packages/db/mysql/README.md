# Database MySQL Koperasi Digital

Folder ini berisi skema MySQL 8 untuk sistem Koperasi Digital desktop + Android eksternal.

## File

- `schema.sql`: struktur database lengkap, index, relasi, view dashboard, dan procedure pembuat jadwal 60 hari tanpa hari Minggu.

## Cara Import

```bash
mysql -u root -p < schema.sql
```

Atau lewat phpMyAdmin/MySQL Workbench dengan menjalankan isi `schema.sql`.

## Alur Data Penting

1. User dibuat di `users`.
2. Karyawan/Admin/Master Admin punya detail di `employee_profiles`.
3. Nasabah punya detail usaha dan penanggung jawab di `customer_profiles`.
4. Pengajuan pinjaman masuk ke `loan_applications`, lalu divalidasi Admin.
5. Pinjaman aktif masuk ke `loans`.
6. Jalankan `CALL sp_generate_loan_schedules(<loan_id>, <start_date>);` untuk membuat 60 tagihan harian tanpa hari Minggu.
7. Penagih/Admin membuat QR di `payment_qrs` dan menghubungkannya ke jadwal di `payment_qr_schedules`.
8. Scan QR dari Android membuat `payments` dan `payment_allocations`.
9. Absensi IoT masuk ke `attendance_logs`, ringkasannya ada di `attendance_records`.

# Koperasi Digital Monorepo

Blueprint ini memisahkan backend API, aplikasi desktop operasional, shared package, database schema, firmware ESP32, dan dokumen field untuk integrasi Android Studio.

## Stack Utama

- `apps/api`: Node.js + Express + Prisma, endpoint REST, QR payment, attendance IoT.
- `apps/web`: React + Vite sebagai UI desktop untuk Karyawan/Penagih, Admin, dan Master Admin.
- `apps/desktop`: Tauri wrapper untuk membungkus UI desktop dari `apps/web`.
- `packages/db`: Prisma schema PostgreSQL.
- `packages/db/mysql`: skema MySQL 8 siap import untuk phpMyAdmin/MySQL Workbench.
- `packages/shared`: RBAC, aturan 60 hari, badge tunggakan, slot absensi.
- `firmware/esp32-attendance`: contoh client ESP32 pengirim log fingerprint.
- `docs/android-fields.md`: daftar field API untuk aplikasi Android yang dibuat di Android Studio.

## Mulai Cepat

```bash
pnpm install
cp .env.example .env
pnpm db:generate
pnpm dev
```

## Dokumen Penting

- ERD dan skema relasi: `docs/erd.md`
- Arsitektur monorepo: `docs/architecture.md`
- Kontrak API awal: `docs/api-contracts.md`
- Field integrasi Android Studio: `docs/android-fields.md`
- Master prompt implementasi proyek: `docs/master-prompt.md`
- Skema MySQL: `packages/db/mysql/schema.sql`

## Prinsip Implementasi

- Siklus pinjaman disimpan sebagai 60 baris `LoanSchedule`, hanya hari penagihan dan Minggu dikecualikan.
- QR dinamis mengikat `PaymentQr` ke satu atau lebih `LoanSchedule` melalui `PaymentQrSchedule`.
- Scan QR nasabah menghasilkan `Payment`, lalu dialokasikan ke jadwal melalui `PaymentAllocation`.
- Dashboard cepat memakai `CustomerDailySnapshot` untuk streak, tunggakan, dan badge warna.
- Absensi IoT menyimpan log mentah di `AttendanceLog` dan ringkasan harian di `AttendanceRecord`.
- Android tidak dibuat di repo ini; repo ini hanya menyediakan API dan field yang dibutuhkan Android Studio.

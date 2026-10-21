# Arsitektur Desktop Koperasi Digital

Repo ini fokus pada aplikasi desktop untuk Karyawan/Penagih, Admin, dan Master Admin. Aplikasi Android dibuat terpisah di Android Studio dan memakai field/API dari dokumen integrasi.

## Struktur Folder

```text
koperasi-digital-monorepo/
  apps/
    api/                 # Express API, auth, payment QR, attendance IoT, report
    web/                 # React renderer untuk desktop dashboard
    desktop/             # Tauri wrapper untuk web admin
  packages/
    db/                  # Prisma schema dan migrasi database
    shared/              # RBAC, business rules, DTO, helper lintas app
    ui/                  # Design tokens dan komponen UI bersama
  firmware/
    esp32-attendance/    # Firmware absensi fingerprint ESP32
  docs/                  # ERD, arsitektur, kontrak API
```

## Akses Desktop

| Role | Desktop | Modul Utama |
|---|---:|---|
| Nasabah | Tidak | Hanya Android eksternal |
| Karyawan/Penagih | Ya | Pengajuan nasabah, QR penagihan, monitoring harian, absensi diri |
| Admin | Ya | Validasi, CRUD karyawan, monitoring tunggakan, absensi semua karyawan, laporan bulanan |
| Master Admin | Ya | CRUD Admin/Karyawan dan pengawasan super user |

Aturan ini berada di `packages/shared/src/index.ts` agar backend dan desktop memakai sumber kebenaran yang sama. Android Studio tetap mengikuti field kontrak di `docs/android-fields.md`.

## Alur Modul

1. `apps/web` adalah UI desktop operasional.
2. `apps/desktop` membungkus `apps/web` sebagai aplikasi desktop Tauri.
3. `apps/api` memvalidasi JWT, RBAC desktop, QR signature, transaksi pembayaran, dan event IoT.
4. `packages/db` menyimpan skema PostgreSQL untuk pinjaman, pembayaran, absensi, dan approval.
5. `firmware/esp32-attendance` mengirim log fingerprint ke `POST /api/attendance/iot/logs`.
6. Android Studio memakai API yang sama, tetapi source code Android tidak berada di repo ini.

## Siklus Pinjaman 60 Hari

- Saat pinjaman aktif, backend membuat 60 record `LoanSchedule`.
- Fungsi pembuat jadwal wajib melewati hari Minggu.
- Tunggakan dihitung dari jadwal dengan `dueDate < today` dan status belum `PAID`.
- Sunday tidak membuat tagihan baru dan tidak memutus streak pembayaran.

## QR Code Dinamis

- Karyawan/Admin membuat `PaymentQr` untuk mode `ONE_DAY`, `TWO_DAYS`, `ARREARS_ACCUMULATED`, atau `CUSTOM`.
- Jadwal yang tertutup QR dicatat di `PaymentQrSchedule`.
- Nasabah wajib scan QR dari aplikasi Android.
- Setelah scan valid, backend membuat `Payment` dan `PaymentAllocation`, lalu memperbarui `LoanSchedule`.

## Absensi IoT

- ESP32 mengirim `deviceCode`, `fingerprintTemplateId`, `employeeId`, dan `occurredAt`.
- Backend menyimpan semua event ke `AttendanceLog`.
- Worker harian/real-time melakukan upsert ke `AttendanceRecord`.
- Kalender absensi memakai warna: biru lengkap, kuning parsial, merah alpha.

## Modul Desktop

`apps/desktop` disiapkan sebagai wrapper Tauri. Untuk rilis awal, desktop memuat build `apps/web` agar dashboard Karyawan, Admin, dan Master Admin tidak dibuat dua kali.

### Karyawan/Penagih

- Pengajuan nasabah dan analisis kelayakan 60 hari.
- Generate QR penagihan untuk 1 hari, 2 hari, atau akumulasi tunggakan.
- Monitoring harian nasabah yang sudah/belum scan.
- Leaderboard streak terbaik dan tunggakan terburuk.
- Riwayat absensi diri.

### Admin

- Validasi pengajuan nasabah dan perubahan data.
- CRUD Karyawan/Penagih.
- Monitoring tunggakan per Karyawan.
- Filter kategori merah dan hitam.
- Generate QR dan mengelola nasabah sendiri.
- Monitoring absensi semua karyawan.
- Rekap bulanan.

### Master Admin

- CRUD Admin.
- CRUD Karyawan/Penagih.
- Audit akses super user.

# Panduan Fitur Admin Koperasi Harmoni

**Audit:** 21 Oktober 2026  
**Akses web:** `http://127.0.0.1:8000/admin`  
**Akun development:** `admin@koperasi.id` / `password`  
**Perhatian:** Ganti password development sebelum deployment.

Panduan ini membedakan fitur yang sudah dapat dipakai melalui web, operasi yang hanya tersedia melalui API, dan bagian yang masih dalam pengerjaan.

## Akses dan Peran

- Halaman `/admin/*` memakai session web dan middleware `admin.session`.
- Role `admin` dan `master_admin` yang aktif dapat membuka area admin web.
- Web saat ini membatasi akses berdasarkan role; permission granular belum diterapkan pada halaman.
- API memakai Bearer token Sanctum dan membatasi endpoint melalui middleware role.
- Role `collector` dan `customer` tidak dapat membuka halaman admin web.

## Fitur Web yang Tersedia

### Dashboard

`GET /admin`

Menampilkan ringkasan jumlah collector, admin, dan anggota aktif. Angka dihitung dari data saat halaman dibuka. Dashboard ini belum menampilkan seluruh KPI pinjaman dan tunggakan yang direncanakan.

### Data Karyawan

`GET /admin/employees`

- Daftar collector dengan pencarian nama, ID karyawan, atau email dan pagination.
- Tambah karyawan dengan nama, email, ID karyawan, dan password.
- Edit nama, email, ID karyawan, status aktif, serta password secara opsional.
- Nonaktifkan akun tanpa menghapus histori relasi.
- Label `Online`/`Offline` pada tabel saat ini mengikuti `is_active`; itu bukan indikator kehadiran realtime.

### Data Nasabah dan Detail Anggota

`GET /admin/customers`

- Cari nama, ID anggota, atau pekerjaan.
- Filter status anggota dan pagination; daftar diurutkan menurut ID numerik.
- Provisioning akun anggota melalui aksi `Buat Akun Anggota`.
- Detail anggota memuat profil, status akun, dan daftar pinjaman.
- Admin dapat mengubah nama, ID anggota, jenis kelamin, pekerjaan, alamat, status, tanggal berhenti, dan alasan berhenti.

### Pengelolaan Pinjaman dari Detail Anggota

Route detail: `GET /admin/customers/{customer}/loans/{loan}`

- Tambah pinjaman untuk anggota dengan collector, nominal, durasi, dan bunga.
- Pinjaman baru dibuat dengan status `PENDING_APPROVAL` beserta record approval `PENDING`.
- Edit nominal, durasi, bunga, atau status pinjaman.
- Hapus pinjaman dengan konfirmasi.
- Detail pinjaman menampilkan ringkasan, kalender, serta jadwal pembayaran.

### Validasi Pinjaman

Daftar: `GET /admin/loan-validations`  
Detail: `GET /admin/loan-validations/{approval}`

- Filter status `PENDING`, `APPROVED`, `REJECTED`, dan rentang tanggal pengajuan.
- Ringkasan jumlah pengajuan menurut status.
- Detail menampilkan anggota, nominal, durasi, bunga, collector, pemohon, tanggal, dan ringkasan jadwal.
- Approve menerima catatan opsional, mengaktifkan pinjaman, mencatat admin dan waktu keputusan, serta membuat jadwal pembayaran.
- Reject mewajibkan alasan minimal 5 karakter.
- Keputusan hanya berlaku saat approval masih `PENDING` dan loan masih `PENDING_APPROVAL`; aksi kedua ditolak dengan HTTP 409.
- Approval, perubahan status loan, dan pembuatan jadwal dilakukan dalam transaksi dengan row lock.

## Fitur Admin yang Tersedia Melalui API

Endpoint berikut memerlukan Bearer Sanctum dengan role yang sesuai.

| Endpoint | Role | Fungsi |
|---|---|---|
| `GET /api/admin/approvals?status=PENDING` | Admin, Master Admin | Daftar approval pinjaman |
| `POST /api/admin/approvals/{approval}/approve` | Admin, Master Admin | Menyetujui approval; menerima `notes` opsional |
| `POST /api/admin/approvals/{approval}/reject` | Admin, Master Admin | Menolak approval; memerlukan `rejected_reason` |
| `GET /api/admin/arrears/by-collector` | Admin, Master Admin | Ringkasan tunggakan menurut collector |
| `GET /api/admin/attendance?date=YYYY-MM-DD&status=...` | Admin, Master Admin | Data dan ringkasan absensi untuk tanggal tertentu |
| `POST /api/admin/employees` | Admin, Master Admin | Membuat akun employee melalui API |
| `POST /api/master/admins` | Master Admin | Membuat akun admin melalui API |
| `POST /api/master/employees` | Master Admin | Membuat akun employee melalui API |

Endpoint customer dan loan yang dilindungi Sanctum tercatat di [API_CONTRACTS.md](API_CONTRACTS.md). Token payment sync juga tersedia melalui endpoint collector/member, tetapi bukan halaman web admin.

## Belum Tersedia atau Belum Siap

- **Data Admin web:** pembuatan dan pengelolaan akun admin baru tersedia melalui API Master Admin.
- **Permission web:** role tersedia, tetapi permission per tindakan belum diterapkan pada UI.
- **Absensi web:** API admin tersedia; halaman monitoring admin belum tersedia.
- **UI rekonsiliasi pembayaran:** belum tersedia untuk konflik token, setoran tunai, atau laporan.
- **Riwayat Pinjaman:** route dan controller daftar sudah dibuat (`GET /admin/loan-history`) dengan pencarian, filter status/tanggal, progress, tunggakan, dan pagination. Namun fitur ini belum siap dipakai: link navigasi admin masih placeholder, view perlu dirapikan, dan belum ada feature test khusus.
- **Import/export:** import CSV anggota tersedia; import XLSX dan export data belum tersedia.
- **Audit perubahan:** belum ada audit log umum untuk perubahan admin.
- **Pengelolaan akun berisiko:** belum tersedia layar untuk reset password atau pengelolaan sesi.

## Route Web Admin

| Method | Route | Fungsi |
|---|---|---|
| `GET` | `/admin` | Dashboard |
| `GET` | `/admin/employees` | Daftar/tambah/edit karyawan |
| `POST` | `/admin/employees` | Tambah karyawan |
| `PUT` | `/admin/employees/{employee}` | Perbarui karyawan |
| `DELETE` | `/admin/employees/{employee}` | Nonaktifkan karyawan |
| `GET` | `/admin/customers` | Daftar anggota |
| `POST` | `/admin/customers/provision-users` | Buat akun anggota |
| `GET` | `/admin/customers/{customer}` | Detail dan edit anggota |
| `PUT` | `/admin/customers/{customer}` | Simpan perubahan anggota |
| `GET` | `/admin/customers/{customer}/loans/{loan}` | Detail pinjaman dan kalender |
| `POST` | `/admin/customers/{customer}/loans` | Tambah pinjaman |
| `PUT` | `/admin/loans/{loan}` | Edit pinjaman |
| `DELETE` | `/admin/loans/{loan}` | Hapus pinjaman |
| `GET` | `/admin/loan-validations` | Daftar validasi |
| `GET` | `/admin/loan-validations/{approval}` | Detail validasi |
| `POST` | `/admin/loan-validations/{approval}/approve` | Setujui pinjaman |
| `POST` | `/admin/loan-validations/{approval}/reject` | Tolak pinjaman |
| `GET` | `/admin/loan-history` | Riwayat pinjaman, masih dalam pengerjaan |

## Pemeriksaan

```powershell
php artisan route:list --path=admin
php artisan test --filter=LoanValidationTest
php artisan view:cache
```

Untuk daftar fitur sistem dan prioritas pengembangan lintas role, lihat [PROJECT_STATUS.md](PROJECT_STATUS.md) dan [IMPLEMENTATION_ROADMAP.md](IMPLEMENTATION_ROADMAP.md).

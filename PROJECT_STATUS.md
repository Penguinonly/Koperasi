# Koperasi Harmoni - Status Implementasi

**Tanggal audit:** 21 Oktober 2026

**Framework:** Laravel 12, PHP 8.2+, SQLite untuk development, Vite + Tailwind CSS v4  
**Status:** Fondasi backend, area admin dasar, portal anggota, dan backend token pembayaran offline tersedia. Integrasi Android, UI validasi pinjaman, UI operasional collector, serta rekonsiliasi pembayaran masih menjadi pekerjaan berikutnya.

**Panduan admin:** Lihat [ADMIN_FEATURES.md](ADMIN_FEATURES.md) untuk fitur web/API admin, route, role, dan bagian yang masih belum tersedia.  
**Blueprint UI:** Lihat [UI_REQUIREMENTS.md](UI_REQUIREMENTS.md) untuk inventaris halaman, role, prioritas sprint, komponen bersama, dan token visual.

## 1. Ringkasan

Aplikasi saat ini memiliki dua permukaan utama:

1. **Web Laravel/Blade** untuk login berbasis role, dashboard admin, pengelolaan karyawan, nasabah, dan detail pinjaman.
2. **REST API Sanctum** untuk autentikasi, nasabah, pinjaman, pembayaran, absensi, approval, dan operasi collector/admin.
3. **Portal anggota berbasis web session** untuk profil pribadi, ringkasan pinjaman, scan QR pembayaran, histori pembayaran, dan streak.
4. **Backend token pembayaran offline** untuk penerbitan token Ed25519, konfirmasi sinkronisasi, alokasi pembayaran, audit, dan expiry.

Mockup yang sudah mulai diwujudkan:

- Login Koperasi Harmoni.
- Dashboard Master Admin.
- Halaman Data Karyawan Master Admin.
- Daftar nasabah admin dengan urutan ID numerik.
- Detail anggota dan detail pinjaman dengan kalender pembayaran.
- Workspace karyawan/collector untuk nasabah yang ditugaskan.

Mockup yang **belum seluruhnya menjadi halaman web**:

- Data Admin.
- Validasi Pinjaman.
- Riwayat Pinjaman.
- Absensi.
- Validasi pembayaran dan rekonsiliasi operasional yang lengkap.

## 2. Fitur yang Sudah Ditambahkan

### 2.1 Database dan data anggota

- SQLite aktif dan migrations sudah dijalankan.
- Tabel autentikasi Laravel `users`, `sessions`, password reset, cache, dan jobs tersedia.
- Tabel domain koperasi tersedia:
  - `roles`
  - `permissions`
  - `role_permission`
  - `customers`
  - `loans`
  - `loan_schedules`
  - `payment_qrs`
  - `payment_qr_schedules`
  - `payments`
  - `attendance_logs`
  - `attendance_records`
  - `approvals`
- Tabel `customers` diperluas untuk data anggota dari CSV:
  - `member_code`
  - `gender`
  - `occupation`
  - `address`
  - `resignation_requested_at`
  - `resigned_at`
  - `exit_reason`
- Seeder anggota tersedia di `database/seeders/MemberSeeder.php`.
- Importer CSV dapat dijalankan ulang menggunakan `updateOrCreate`, sehingga kode anggota tidak menggandakan data.
- ID anggota disimpan tanpa leading zero: `1`, `2`, `3`, dan seterusnya.
- File data anggota bersifat lokal dan tidak disertakan di repository karena memuat informasi pribadi. Format kolom tersedia di `database/data/members.example.csv`.
- Status impor:
  - Tanpa tanggal berhenti: `ACTIVE`.
  - Dengan tanggal berhenti: `INACTIVE`.

### 2.2 Autentikasi

- API token authentication menggunakan Laravel Sanctum.
- Web session authentication menggunakan guard `web` Laravel.
- Login web memakai email dan password.
- Session diregenerasi setelah login.
- Logout menghapus session dan meregenerasi CSRF token.
- Error CSRF/session `419` diarahkan kembali ke login dengan pesan sesi kedaluwarsa.
- Role admin dibatasi ke `admin` dan `master_admin`.
- Akun seed untuk pengujian:
  - `admin@koperasi.id` / `password`
  - `master@koperasi.id` / `password`
  - `collector@koperasi.id` / `password`
  - `customer@koperasi.id` / `password` untuk pengujian role/API; akun ini tidak terhubung ke profil anggota web secara default.

### 2.3 Web admin

Route web yang tersedia:

- `GET /login`
- `POST /login`
- `POST /logout`
- `GET /admin`
- `GET /admin/employees`
- `POST /admin/employees`
- `PUT /admin/employees/{employee}`
- `DELETE /admin/employees/{employee}`

Route admin nasabah dan pinjaman:

- `GET /admin/customers`
- `POST /admin/customers/provision-users`
- `GET /admin/customers/{customer}`
- `PUT /admin/customers/{customer}`
- `GET /admin/customers/{customer}/loans/{loan}`
- `POST /admin/customers/{customer}/loans`
- `PUT /admin/loans/{loan}`
- `DELETE /admin/loans/{loan}`
- `GET /admin/loan-validations`
- `GET /admin/loan-validations/{approval}`
- `POST /admin/loan-validations/{approval}/approve`
- `POST /admin/loan-validations/{approval}/reject`

Fitur halaman Data Karyawan:

- Daftar karyawan role `collector`.
- Pencarian nama, ID karyawan, atau email.
- Tambah karyawan.
- Edit karyawan.
- Aktif/nonaktif karyawan.
- Delete aman melalui soft deactivation, bukan menghapus histori database.
- Pagination.
- Proteksi CSRF dan middleware session admin.

### 2.4 Web admin nasabah dan pinjaman

- Anggota yang dimuat dari CSV dapat dibuka melalui `GET /admin/customers`.
- Pencarian berdasarkan nama, ID anggota, atau pekerjaan.
- Daftar nasabah diurutkan berdasarkan ID anggota ascending (`1`, `2`, `3`, dan seterusnya).
- Filter status `ACTIVE`, `INACTIVE`, dan `SUSPENDED`.
- Detail nasabah dengan edit nama, ID anggota, gender, pekerjaan, alamat, status, tanggal berhenti, dan alasan berhenti.
- Tambah pinjaman dari detail nasabah.
- Edit nominal, durasi, bunga, dan status pinjaman.
- Hapus pinjaman dengan konfirmasi.
- Detail anggota dan detail pinjaman tetap menggunakan `admin-shell` yang sama.
- Detail pinjaman menampilkan ringkasan, kalender pembayaran, dan jadwal harian.
- Validasi pinjaman memiliki daftar dengan filter status dan rentang tanggal, detail anggota/pinjaman, approve dengan catatan opsional, serta reject dengan alasan wajib.
- Keputusan hanya berlaku untuk approval `PENDING` dan loan `PENDING_APPROVAL`; approval, loan, dan pembuatan jadwal berjalan dalam transaksi dengan row lock.
- Import dapat diulang melalui `php artisan members:import database/data/members.csv`.
- Akun login customer dapat dibuat melalui `php artisan members:provision-users` atau tombol `Buat Akun Anggota`.
- Format akun development: `member{id}@koperasi.id` dengan password awal `password`.
- Contoh akun portal anggota setelah provisioning: `member1@koperasi.id` / `password`.
- Anggota `INACTIVE` dibuat sebagai user nonaktif dan tidak dapat login.

### 2.5 Akun anggota dan workspace karyawan

- Relasi akun anggota menggunakan `customers.user_id`.
- Provisioning tersedia melalui `php artisan members:provision-users` dan tombol `Buat Akun Anggota`.
- Provisioning kedua menghasilkan 0 akun baru jika semua anggota sudah memiliki akun.
- Route workspace:
  - `GET /workspace/customers`
  - `GET /workspace/customers/{customer}`
  - `GET /workspace/customers/{customer}/loans/{loan}`
- Admin/master admin dapat melihat seluruh nasabah.
- Collector hanya dapat melihat nasabah yang ditugaskan melalui `collector_id`.
- Workspace menampilkan detail profil, daftar pinjaman, kalender pembayaran, dan jadwal harian.

### 2.6 Portal anggota web

- Login role `customer` diarahkan ke `/member` setelah akun memiliki relasi `customers.user_id`.
- Akun customer tanpa profil anggota ditolak dengan pesan yang jelas dan dikembalikan ke halaman login.
- Dashboard anggota menampilkan identitas, pinjaman aktif jika ada, streak pembayaran, tiga pembayaran terakhir, dan scanner QR.
- Profil anggota dapat diperbarui sendiri untuk nama, telepon, jenis kelamin, pekerjaan, nama usaha, dan alamat.
- ID anggota dan status keanggotaan tidak dapat diubah melalui portal anggota.
- Riwayat pembayaran hanya memuat transaksi `COMPLETED` milik anggota yang sedang login dan mendukung pagination.
- Scan QR memakai dua tahap: preview server-side lalu konfirmasi pembayaran.
- Preview menampilkan nama anggota, ID anggota, nominal, dan masa berlaku QR sebelum transaksi dicatat.
- QR hanya dapat diproses oleh anggota pemilik pinjaman yang terkait; QR anggota lain ditolak.
- Scanner kamera menggunakan `html5-qrcode` dan memiliki kontrol mulai, hentikan, serta scan ulang.
- Streak dihitung dari rangkaian jadwal pinjaman terakhir yang berstatus `PAID`; jadwal hari ini belum memutus streak sampai hari tersebut terlewati.
- Setelah pembayaran berhasil, streak dihitung ulang dan ditampilkan pada respons scan serta halaman anggota.

Route web anggota:

- `GET /member`
- `GET /member/profile`
- `PUT /member/profile`
- `GET /member/payments`
- `POST /member/scan/preview`
- `POST /member/scan`

### 2.7 REST API

API publik:

- Health check.
- Login, register, me, logout, refresh.
- Registrasi nasabah.
- Scan pembayaran.
- Log absensi perangkat IoT.

API terlindungi Sanctum:

- Customer list, create, detail.
- Loan list, create, detail.
- Payment list dan QR payment.
- Penerbitan paket token harian collector dan konfirmasi klaim collector/anggota.
- Attendance pengguna.
- Collector customer list, monitoring harian, leaderboard, attendance.
- Admin approval/rejection pinjaman, arrears collector, attendance, create employee.
- Master admin create admin dan employee.

### 2.8 Frontend build

- Tailwind CSS v4 terhubung melalui Vite.
- `resources/css/app.css` untuk style global.
- `resources/css/login.css` untuk login.
- Asset logo koperasi tersedia di `public/images/koperasi-logo.png`.
- `npm run build` berhasil dijalankan.
- Blade view cache berhasil dijalankan.

### 2.9 Token pembayaran offline

- Lima migration token tersedia di `database/migrations`: counter sequence, payment tokens, sync log, relasi payment-token/alokasi, dan hasil audit `REJECTED`.
- Counter sequence dikeluarkan server; unique `(loan_id, sequence)` menjadi pengaman database.
- `paragonie/sodium_compat` menyediakan signing/verifikasi Ed25519; keypair per-environment dibuat dengan `php artisan tokens:generate-keys`.
- Private key disimpan di `.env`, tidak ditampilkan command; public keyring dan `key_id` dikirim pada paket sinkronisasi.
- `POST /api/collector/sync/tokens` mengeluarkan satu token utama dan dua cadangan per pinjaman aktif yang ditugaskan dan memiliki tunggakan jatuh tempo.
- `POST /api/collector/sync/confirm` dan `POST /api/member/sync/confirm` memvalidasi signature, role/ownership, nominal, idempotensi, dan sequence.
- Konfirmasi mengalokasikan pembayaran ke jadwal jatuh tempo tertua; pembayaran parsial disimpan melalui `payment_allocations`.
- Klaim v1 hanya menerima `CASH`; nominal dibatasi plafon token dan sisa jadwal jatuh tempo.
- Server menerima klaim hingga 24 jam setelah token kedaluwarsa; jam perangkat hanya metadata, `confirmed_at` adalah waktu final.
- `tokens:expire` berjalan setiap jam melalui Laravel scheduler dan mencatat expiry pada `token_sync_log`.
- QR online berbasis `payment_qrs` tetap berjalan paralel selama masa transisi.
- Verifikasi signature di perangkat, penyimpanan sequence anti-rollback, antrean offline, UI collector/anggota, dan rekonsiliasi admin belum tersedia di repository ini; aplikasi Android berada di luar workspace.

## 3. Fitur yang Baru Sebagian

| Area | Kondisi saat ini | Kekurangan |
|---|---|---|
| Login | Web admin sudah berfungsi | Belum ada reset password, rate limit khusus web, dan halaman error khusus |
| Dashboard admin | Tampilan awal sudah ada | Statistik masih dihitung langsung di view dan navigasi sebagian masih placeholder |
| Karyawan | CRUD web tersedia | Belum ada detail profil, audit trail, dan import karyawan |
| Nasabah | Model, migration, API, import CSV, akun anggota, list, detail, dan edit tersedia | Import XLSX dan export belum tersedia |
| Pinjaman | Model, API, approval, schedule, CRUD admin dasar, kalender, detail, serta UI validasi approve/reject tersedia | UI Riwayat Pinjaman dengan progres dan tunggakan belum tersedia |
| Pembayaran | QR online lama dan backend token offline tersedia: sync, audit, alokasi, keyring, expiry | Integrasi Android offline, UI collector, rekonsiliasi konflik/setoran, dan laporan belum tersedia |
| Absensi | Model dan API IoT tersedia | Belum ada UI admin/collector dan integrasi perangkat nyata |
| Permissions | Role dan permission tersedia | Middleware web saat ini memeriksa role; permission belum dipakai pada UI web |
| Tabungan | Migration, model, service, dan controller ledger tersedia di workspace | Belum dipasang pada route atau UI; masih berupa kerangka kerja |
| Testing | API auth, portal anggota, token sync, dan validasi pinjaman memiliki feature test (19 test/111 assertion terakhir) | Uji konkurensi di database produksi, UI riwayat, dan rekonsiliasi masih perlu dibuat |

## 4. Fitur yang Belum Ditambahkan

Pekerjaan yang belum tersedia mencakup aplikasi Android offline, UI collector dan rekonsiliasi token, UI Riwayat Pinjaman, Data Admin/permission, absensi, ledger tabungan yang dipasang ke route/UI, serta import/export dan audit perubahan.

## 5. Struktur File Penting

### Autentikasi dan akses

- `app/Http/Controllers/WebAuthController.php`
- `app/Http/Middleware/EnsureAdminSession.php`
- `app/Http/Middleware/EnsureStaffSession.php`
- `app/Models/User.php`
- `app/Models/Role.php`
- `routes/web.php`
- `routes/api.php`

### Admin

- `app/Http/Controllers/AdminEmployeeController.php`
- `app/Http/Controllers/AdminCustomerController.php`
- `app/Http/Controllers/AdminLoanController.php`
- `app/Http/Controllers/StaffCustomerController.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/employees.blade.php`
- `resources/views/admin/customers.blade.php`
- `resources/views/admin/customer-detail.blade.php`
- `resources/views/admin/loan-detail.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/layouts/workspace.blade.php`
- `resources/views/staff/customers.blade.php`
- `resources/views/staff/customer-detail.blade.php`
- `resources/views/staff/loan-detail.blade.php`

### Anggota dan domain koperasi

- `app/Models/Customer.php`
- `app/Models/Loan.php`
- `app/Models/Payment.php`
- `app/Models/AttendanceRecord.php`
- `database/migrations/2026_09_22_000000_extend_customers_for_members.php`
- `database/migrations/2026_09_25_000000_normalize_member_codes.php`
- `database/migrations/2026_09_25_000001_add_member_user_index.php`
- `database/seeders/MemberSeeder.php`
- `database/data/members.example.csv`
- `app/Services/MemberCsvImporter.php`
- `app/Services/MemberAccountProvisioner.php`
- `app/Services/PaymentTokenService.php`
- `app/Http/Controllers/PaymentTokenSyncController.php`
- `app/Models/PaymentToken.php`
- `app/Models/LoanTokenCounter.php`
- `app/Models/PaymentAllocation.php`
- `app/Console/Commands/GeneratePaymentTokenKeys.php`
- `app/Console/Commands/ExpirePaymentTokens.php`
- `config/payment_tokens.php`
- `PAYMENT_TOKEN_PROTOCOL.md`
- `tests/Feature/PaymentTokenSyncTest.php`
- `app/Http/Controllers/MemberPortalController.php`
- `resources/views/layouts/member.blade.php`
- `resources/views/member/dashboard.blade.php`
- `resources/views/member/profile.blade.php`
- `resources/views/member/payments.blade.php`
- `resources/js/member-qr-scanner.js`
- `tests/Feature/MemberPortalTest.php`

### Frontend

- `resources/views/login.blade.php`
- `resources/css/app.css`
- `resources/css/login.css`
- `resources/js/app.js`
- `vite.config.js`

## 6. Cara Menjalankan dan Memeriksa

```powershell
composer install
npm install
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

Akses:

- Login: `http://127.0.0.1:8000/login`
- Admin dashboard: `http://127.0.0.1:8000/admin`
- Data karyawan: `http://127.0.0.1:8000/admin/employees`
- Data nasabah admin: `http://127.0.0.1:8000/admin/customers`
- Workspace karyawan: `http://127.0.0.1:8000/workspace/customers`
- Portal anggota: `http://127.0.0.1:8000/member`
- Profil anggota: `http://127.0.0.1:8000/member/profile`
- Riwayat pembayaran anggota: `http://127.0.0.1:8000/member/payments`

Pemeriksaan teknis:

```powershell
php artisan migrate:status
php artisan route:list
php artisan view:cache
php artisan test
```

Konfigurasi token dan scheduler:

```powershell
php artisan tokens:generate-keys
php artisan schedule:work
```

## 7. Prioritas Pengembangan Berikutnya

Urutan yang disarankan setelah backend token tersedia:

1. Integrasikan protokol ke aplikasi Android: verifikasi Ed25519 offline, penyimpanan sequence tahan reset, antrean klaim lokal, dan status menunggu sinkronisasi.
2. Bangun alur collector di klien operasional: sinkronisasi paket token, tampilkan QR, input nominal tunai aktual, dan kirim klaim saat online.
3. Buat UI rekonsiliasi admin untuk konflik, duplikasi, expiry, dan pencocokan setoran kas. Token adalah klaim pembayaran, bukan bukti uang telah disetor.
4. Buat UI Riwayat Pinjaman dengan progres angsuran dan tunggakan.
5. Perluas test konkurensi sequence/idempotensi pada database produksi serta test session admin, approval, CRUD, dan provisioning.
6. Lanjutkan Data Admin/permission, absensi, import XLSX, export, audit perubahan, dan dokumen anggota.
7. Pensiunkan `payment_qrs` setelah klien dan laporan lama selesai dimigrasikan.

## 8. Catatan Penting

- `customers` dipakai sebagai entitas nasabah sekaligus anggota koperasi agar relasi dengan `loans` tetap konsisten.
- Delete karyawan dari UI sengaja dibuat sebagai nonaktifasi. Ini menjaga relasi historis pinjaman, pembayaran, dan absensi.
- API Sanctum dan web session adalah dua mekanisme autentikasi berbeda tetapi menggunakan tabel `users` dan role yang sama.
- Portal anggota memakai web session dan membatasi data berdasarkan relasi akun `users.customer`.
- Endpoint web scan memiliki preview sebelum konfirmasi; endpoint API `POST /api/payments/scan` tetap tersedia untuk klien mobile/guest sesuai kontrak API.
- Aplikasi Android tidak berada di workspace ini; integrasi offline memerlukan perubahan pada repository Android dan harus memakai protokol di `PAYMENT_TOKEN_PROTOCOL.md`.
- Token yang lolos verifikasi lokal belum final sampai server mengembalikan hasil sync `ACCEPTED`.
- `device_id` hanya metadata audit; otorisasi saat ini bersumber dari akun Sanctum dan relasi pinjaman, bukan registrasi perangkat.
- Laravel scheduler harus aktif agar expiry berjalan otomatis.
- `IMPLEMENTATION_ROADMAP.md` berisi rencana awal. Dokumen ini adalah status aktual berdasarkan kode yang saat ini ada.

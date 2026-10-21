# Panduan Program Koperasi Harmoni

**Pembaruan:** 21 Oktober 2026

**Tujuan:** peta teknis dan operasional untuk membaca, menjalankan, dan mengembangkan seluruh workspace.

> Workspace ini berisi dua implementasi yang berdiri sendiri: aplikasi Laravel di root repository dan blueprint monorepo TypeScript di <code>koperasi-digital-monorepo/</code>. Keduanya belum terhubung sebagai satu sistem. Untuk kondisi fitur saat ini, lihat juga [PROJECT_STATUS.md](PROJECT_STATUS.md).

## 1. Ringkasan Sistem

### Aplikasi Laravel di root

Ini adalah aplikasi yang paling lengkap dan menjadi jalur utama saat ingin menjalankan sistem. Laravel menyediakan halaman web, portal anggota, REST API, autentikasi, akses database, alur pinjaman dan pembayaran, absensi, serta sinkronisasi klaim token pembayaran offline.

~~~mermaid
flowchart LR
    Browser[Browser: admin, collector, anggota] -->|Web session| Laravel[Laravel 12]
    Mobile[Klien mobile / integrasi eksternal] -->|Sanctum atau endpoint publik| Laravel
    Device[Perangkat absensi] -->|Webhook IoT| Laravel
    Laravel --> DB[(Database dari konfigurasi Laravel)]
    Laravel --> Assets[Vite: CSS, JS, QR scanner]
~~~

### Blueprint monorepo bersarang

<code>koperasi-digital-monorepo/</code> berisi rancangan React, Express, Prisma, Tauri, dan firmware ESP32. Struktur ini berguna sebagai prototipe antarmuka dan referensi integrasi, tetapi backend Express belum memakai database atau autentikasi produksi. Jangan menganggap endpoint prototipe sebagai pengganti endpoint Laravel.

| Bagian | Teknologi | Kondisi saat ini |
|---|---|---|
| Aplikasi root | Laravel 12, PHP 8.2+, Blade, Vite, Tailwind CSS 4 | Aplikasi utama; memakai migration Laravel dan database yang dikonfigurasi di <code>.env</code>. |
| REST API root | Laravel Sanctum | Endpoint implementasi aktual didefinisikan di <code>routes/api.php</code>. |
| UI monorepo | React 19, Vite | Prototipe UI berbasis data contoh; belum terhubung ke API. |
| API monorepo | Express, TypeScript, Zod | Endpoint contoh; belum menyimpan transaksi ke database. |
| Database monorepo | Prisma/PostgreSQL dan skema SQL MySQL | Skema rancangan terpisah; belum dipakai oleh API Express. |
| Aplikasi desktop | Tauri | Wrapper direncanakan; konfigurasi Tauri belum lengkap. |
| Firmware | ESP32/Arduino | Kerangka contoh; pembacaan sidik jari dan pengiriman waktu masih placeholder. |

## 2. Peta Repository

~~~text
app/                         Kode Laravel: controller, model, middleware, service, command
bootstrap/                   Bootstrap Laravel
config/                      Konfigurasi Laravel
database/migrations/          Sumber skema database Laravel
database/seeders/             Role, permission, akun demo, dan importer anggota
database/data/                 Template CSV; data anggota asli disimpan lokal
resources/views/              Halaman Blade untuk login, admin, workspace, portal anggota
resources/js/                 JavaScript, termasuk scanner QR anggota
resources/css/                Style yang diproses Vite
routes/web.php                Route halaman berbasis session
routes/api.php                Route REST API Laravel
tests/                         Feature dan unit test Laravel yang tersedia

koperasi-digital-monorepo/
  apps/api/                    API Express prototipe
  apps/web/                    UI React prototipe
  apps/desktop/                Wrapper Tauri yang direncanakan
  packages/db/                 Skema Prisma PostgreSQL dan skema MySQL
  packages/shared/             Tipe dan aturan bisnis TypeScript bersama
  packages/ui/                 Titik awal package UI bersama
  firmware/esp32-attendance/   Contoh firmware perangkat absensi
  docs/                        ERD, arsitektur, kontrak awal, field Android
~~~

## 3. Aplikasi Laravel

### Teknologi dan alur request

- PHP minimal 8.2, Laravel 12, Laravel Sanctum 4, dan database yang dikonfigurasi lewat <code>.env</code>.
- Database development bawaan adalah SQLite (<code>DB_CONNECTION=sqlite</code>). File <code>database/database.sqlite</code> bersifat lokal dan diabaikan Git.
- Halaman Laravel memakai Blade. Vite memproses Tailwind CSS 4, stylesheet, dan JavaScript. Library <code>html5-qrcode</code> dipakai di scanner portal anggota.
- Web menggunakan session Laravel dan perlindungan CSRF. API menggunakan bearer token Sanctum untuk route yang berada di dalam middleware <code>auth:sanctum</code>.
- Migration di <code>database/migrations/</code> adalah sumber kebenaran skema Laravel. [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) adalah referensi tambahan dan bisa tertinggal dari kode migration.

### Menyiapkan lingkungan lokal (PowerShell)

Jalankan dari folder root repository:

~~~powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite | Out-Null }
npm ci
php artisan migrate --seed
npm run build
composer run dev
~~~

<code>composer run dev</code> menjalankan server Laravel, queue listener, log viewer, dan Vite. Alternatifnya, jalankan <code>php artisan serve</code> dan <code>npm run dev</code> di terminal terpisah. Halaman lokal tersedia di <code>http://127.0.0.1:8000</code>.

Seed standar membuat role, permission, dan akun pengembangan. Seeder anggota hanya mengimpor data bila file lokal <code>database/data/members.csv</code> tersedia. Data anggota asli tidak disertakan karena memuat informasi pribadi. Buat file tersebut sendiri dengan kolom dari [members.example.csv](database/data/members.example.csv), atau berikan path file ke importer:

~~~powershell
php artisan members:import database/data/members.csv
php artisan members:provision-users
~~~

CSV perlu memiliki kolom <code>ID Anggota</code>, <code>Nama</code>, <code>Jenis Kelamin</code>, <code>Mata Pencaharian</code>, <code>Alamat Tempat Tinggal</code>, <code>Tanggal Minta Berhenti</code>, <code>Tanggal Berhenti/Dipecat</code>, <code>Alasan Berhenti/Dipecat</code>, dan <code>Names</code>. Importer menggunakan ID anggota yang dinormalisasi sebagai kode unik dan melewati baris tanpa ID atau nama.

### Akun dan peran

| Role | Kegunaan |
|---|---|
| <code>customer</code> | Anggota koperasi. Portal web memerlukan relasi <code>users.customer</code> yang terhubung ke profil anggota. |
| <code>collector</code> | Karyawan/penagih. Dapat memakai workspace dan endpoint operasional collector. |
| <code>admin</code> | Operasional administrasi, validasi pinjaman, pengelolaan anggota dan karyawan. |
| <code>master_admin</code> | Akses admin dan endpoint API khusus pengelolaan admin. |

Seed development menyediakan <code>admin@koperasi.id</code>, <code>master@koperasi.id</code>, <code>collector@koperasi.id</code>, serta <code>customer@koperasi.id</code>, dengan password awal <code>password</code>. Akun anggota hasil provisioning juga memakai password awal <code>password</code>. Kredensial ini hanya untuk development; ganti sebelum sistem dipakai di lingkungan bersama atau produksi.

Web dan API memakai dua jalur autentikasi berbeda, tetapi berbagi tabel <code>users</code> dan role. Middleware <code>admin.session</code> dan <code>staff.session</code> melindungi area web. API memakai <code>auth:sanctum</code> lalu middleware <code>role</code>. Tabel <code>permissions</code> tersedia, tetapi akses layar web saat ini terutama diperiksa berdasarkan role; permission granular belum diterapkan menyeluruh.

### Fitur yang tersedia di aplikasi Laravel

#### Admin web

- Dashboard ringkasan.
- Daftar, tambah, edit, dan nonaktifkan akun collector/karyawan.
- Cari, filter, dan lihat anggota; edit data profil anggota.
- Provisioning akun portal anggota.
- Buat dan edit pinjaman dari detail anggota.
- Daftar dan detail validasi pinjaman, setujui atau tolak dengan catatan.
- Riwayat pinjaman dan halaman rekonsiliasi token memiliki route, tetapi masih perlu penyelesaian UI sesuai catatan di [ADMIN_FEATURES.md](ADMIN_FEATURES.md).

#### Workspace collector

Workspace menampilkan anggota dan pinjaman yang menjadi tanggung jawab collector, detail anggota, kalender, dan jadwal pembayaran. Filter penugasan dilakukan berdasarkan <code>customers.collector_id</code>.

#### Portal anggota

- Ringkasan profil, pinjaman aktif, streak, dan pembayaran terakhir.
- Edit nama, telepon, gender, pekerjaan, nama usaha, dan alamat; ID anggota dan status tetap dikelola pengurus.
- Scan QR dua tahap: minta preview ke server, lalu konfirmasi transaksi.
- Riwayat pembayaran yang sudah berstatus selesai.

Profil anggota harus terhubung dengan akun melalui <code>customers.user_id</code>. Scanner kamera memerlukan <code>localhost</code> atau HTTPS.

#### Pinjaman dan approval

Collector atau admin membuat pengajuan pinjaman. Pinjaman baru berstatus <code>PENDING_APPROVAL</code> dan mempunyai record approval <code>PENDING</code>. Admin menyetujui atau menolak pengajuan yang masih menunggu. Persetujuan mengubah status pinjaman dan membuat jadwal dalam transaksi database; keputusan berulang pada approval yang sudah diproses ditolak.

#### QR pembayaran dan pembayaran

Laravel memiliki jalur QR dinamis lama berbasis <code>payment_qrs</code> dan jalur token offline berbasis <code>payment_tokens</code>. QR dinamis menghubungkan satu atau beberapa jadwal pinjaman. Portal anggota meminta preview dan hanya pemilik pinjaman terkait yang boleh mengonfirmasi scan. Riwayat portal hanya menampilkan pembayaran berstatus <code>COMPLETED</code>.

#### Token pembayaran offline

1. Siapkan pasangan kunci Ed25519 lokal dengan <code>php artisan tokens:generate-keys</code>.
2. Collector yang terautentikasi meminta paket token untuk pinjaman yang ditugaskan kepadanya.
3. Klien offline memverifikasi signature dan sequence, lalu menyimpan klaim pembayaran lokal.
4. Klien mengirim klaim saat kembali online. Server memvalidasi token, actor, nominal, sequence, dan idempotensi sebelum membuat pembayaran/alokasi.
5. Hanya metode <code>CASH</code> yang diterima oleh protokol offline saat ini. Klaim yang lolos pemeriksaan lokal belum final sebelum server mengembalikan hasil sinkronisasi.

Private key berada di <code>.env</code> dan tidak boleh dimasukkan ke Git. Keyring public dapat dibagikan ke klien. Konfigurasi pentingnya adalah <code>PAYMENT_TOKEN_PRIVATE_KEY</code>, <code>PAYMENT_TOKEN_PUBLIC_KEY</code>, <code>PAYMENT_TOKEN_KEY_ID</code>, <code>PAYMENT_TOKEN_PUBLIC_KEYS</code>, <code>PAYMENT_TOKEN_DAILY_COUNT</code> (default 3), dan <code>PAYMENT_TOKEN_SYNC_GRACE_HOURS</code> (default 24). Protokol payload, urutan sequence, status, serta hasil sinkronisasi dijelaskan di [PAYMENT_TOKEN_PROTOCOL.md](PAYMENT_TOKEN_PROTOCOL.md).

Expiry token dijadwalkan setiap jam di <code>routes/console.php</code>. Jalankan scheduler di lingkungan development atau server:

~~~powershell
php artisan schedule:work
~~~

<code>device_id</code> dicatat sebagai metadata; autentikasi saat ini berasal dari akun Sanctum dan penugasan pinjaman, bukan registrasi perangkat.

#### Absensi

Endpoint IoT menerima log perangkat dan menyimpan event mentah pada <code>attendance_logs</code>. Jika employee ID ditemukan, aplikasi memperbarui ringkasan harian <code>attendance_records</code>. Kode Laravel membagi waktu menjadi empat slot: <code>MORNING_IN</code> (07.00–10.59), <code>MORNING_OUT</code> (11.00–15.59), <code>AFTERNOON_IN</code> (16.00–17.29), dan <code>AFTERNOON_OUT</code> (17.30–23.59). Monitor admin web belum tersedia.

**Pengamanan perangkat:** <code>IOT_API_KEY</code> harus dikonfigurasi. Kode webhook hanya memeriksa header <code>X-IOT-API-KEY</code> bila nilai environment tersebut terisi; jika kosong, route menerima request tanpa pemeriksaan kunci. Jangan biarkan kosong pada server yang dapat diakses jaringan.

#### Catatan implementasi tabungan

Pada workspace saat dokumen ini dibuat, migration, model, service, dan controller ledger tabungan sudah ada untuk setoran/penarikan idempoten. Namun <code>AdminSavingsController</code> belum dipasang pada route dan belum ada halaman web/API yang dapat memanggilnya. Anggap ini kerangka kerja yang belum tersedia bagi pengguna, bukan fitur operasional.

### Peta kode Laravel

| Area | Berkas utama | Tanggung jawab |
|---|---|---|
| Login web/API | <code>WebAuthController</code>, <code>AuthController</code>, <code>EnsureAdminSession</code>, <code>EnsureStaffSession</code>, <code>CheckRole</code> | Login session, token API, pengalihan role, dan pemeriksaan akses. |
| Dashboard dan admin | <code>DashboardController</code>, <code>AdminController</code>, <code>AdminEmployeeController</code>, <code>AdminCustomerController</code> | Ringkasan dashboard, employee, anggota, provisioning, serta aksi admin. |
| Pinjaman | <code>LoanController</code>, <code>AdminLoanController</code>, <code>AdminLoanValidationController</code>, <code>AdminLoanHistoryController</code> | API pinjaman, CRUD admin, daftar/detail validasi, dan riwayat. |
| Workspace | <code>CollectorController</code>, <code>StaffCustomerController</code> | Data collector via API dan halaman workspace berbasis penugasan. |
| Pembayaran | <code>PaymentController</code>, <code>PaymentTokenSyncController</code>, <code>PaymentTokenService</code> | QR, scan/preview, penerbitan token, validasi klaim, dan sync. |
| Absensi | <code>AttendanceController</code>, <code>AttendanceLog</code>, <code>AttendanceRecord</code> | Webhook perangkat, event mentah, dan ringkasan absensi harian. |
| Rekonsiliasi | <code>AdminTokenReconciliationController</code>, <code>TokenReconciliationReview</code> | Daftar log token dan pencatatan hasil review admin. |
| Anggota CSV | <code>MemberCsvImporter</code>, <code>MemberAccountProvisioner</code>, <code>ImportMembers</code>, <code>ProvisionMemberUsers</code> | Import data anggota dan buat akun portal anggota. |
| Token terjadwal | <code>GeneratePaymentTokenKeys</code>, <code>ExpirePaymentTokens</code> | Membuat keypair lokal dan menandai token kedaluwarsa. |
| Tabungan (WIP) | <code>AdminSavingsController</code>, <code>SavingsLedgerService</code>, <code>SavingsTransaction</code> | Kerangka ledger; belum dipasang ke route. |

Model domain lainnya berada di <code>app/Models/</code>: <code>User</code>, <code>Role</code>, <code>Permission</code>, <code>Customer</code>, <code>Loan</code>, <code>LoanSchedule</code>, <code>Approval</code>, <code>Payment</code>, <code>PaymentQr</code>, <code>PaymentAllocation</code>, <code>PaymentToken</code>, <code>LoanTokenCounter</code>, dan <code>TokenSyncLog</code>. Halaman Blade dikelompokkan di <code>resources/views/admin/</code>, <code>resources/views/staff/</code>, dan <code>resources/views/member/</code>; layout masing-masing ada di <code>resources/views/layouts/</code>.

### Route halaman web Laravel

| Route | Akses/fungsi |
|---|---|
| <code>/login</code>, <code>POST /login</code>, <code>POST /logout</code> | Login dan logout berbasis session. |
| <code>/dashboard</code> | Pengalihan/halaman dashboard berdasarkan akun. |
| <code>/admin/*</code> | Dashboard, karyawan, anggota, pinjaman, validasi, riwayat, rekonsiliasi; dilindungi <code>admin.session</code>. |
| <code>/workspace/customers/*</code> | Data dan pinjaman anggota untuk karyawan; dilindungi <code>staff.session</code>. |
| <code>/member/*</code> | Dashboard, profil, pembayaran, scan; perlu login role <code>customer</code>. |

Daftar method dan nama route lengkap ada di [routes/web.php](routes/web.php).

### REST API Laravel

Semua path di tabel berikut memiliki prefix <code>/api</code>. Definisi otoritatif dan middleware ada di [routes/api.php](routes/api.php); skema request/response tambahan ada di [API_CONTRACTS.md](API_CONTRACTS.md).

| Endpoint | Akses route | Fungsi |
|---|---|---|
| <code>GET /health</code> | Publik | Health check. |
| <code>POST /auth/login</code>, <code>/auth/register</code> | Publik | Login API dan registrasi. |
| <code>POST /customers/register</code> | Publik | Registrasi anggota/nasabah API. |
| <code>POST /payments/scan</code> | Publik | Scan pembayaran untuk klien API lama. |
| <code>POST /attendance/iot/logs</code> | Publik; kunci IoT opsional dari environment | Menerima log perangkat. Konfigurasikan <code>IOT_API_KEY</code>. |
| <code>GET /auth/me</code>, <code>POST /auth/logout</code>, <code>POST /auth/refresh</code> | Sanctum | Informasi user dan sesi/token. |
| <code>GET /customers</code>, <code>POST /customers</code>, <code>GET /customers/{customer}</code> | Sanctum; role sesuai route | Daftar, tambah, dan detail customer. |
| <code>GET /loans</code>, <code>POST /loans</code>, <code>GET /loans/{loan}</code> | Sanctum; pembuatan untuk collector | Daftar, ajukan, dan lihat pinjaman. |
| <code>GET /payments</code>, <code>POST /payments/qr</code> | Sanctum; QR untuk collector | Daftar pembayaran dan membuat QR. |
| <code>GET /attendance/me</code> | Sanctum; collector/admin/master admin | Ringkasan absensi user. |
| <code>/collector/customers</code>, <code>/daily-monitoring</code>, <code>/leaderboard</code>, <code>/attendance-me</code> | Sanctum + role collector | Data kerja harian collector. Semua memakai prefix <code>/collector</code>. |
| <code>POST /collector/sync/tokens</code>, <code>/collector/sync/confirm</code> | Sanctum + role collector | Terbitkan token dan sinkronkan klaim offline. |
| <code>POST /member/sync/confirm</code> | Sanctum + role customer | Sinkronkan klaim token anggota. |
| <code>/admin/approvals/*</code>, <code>/admin/arrears/by-collector</code>, <code>/admin/attendance</code>, <code>/admin/employees</code> | Sanctum + role admin/master admin | Approval, laporan, absensi, dan pembuatan employee. |
| <code>POST /master/admins</code>, <code>/master/employees</code> | Sanctum + role master admin | Pembuatan admin dan employee. |

<code>API_CONTRACTS.md</code> adalah panduan payload, tetapi jika ada perbedaan, route dan implementasi controller Laravel yang berlaku. Endpoint publik harus dinilai tersendiri sebelum dipublikasikan ke internet.

### Model data Laravel

Migration adalah sumber kebenaran. Hubungan inti:

| Kelompok | Tabel | Hubungan / tujuan |
|---|---|---|
| Akun | <code>users</code>, <code>roles</code>, <code>permissions</code>, <code>role_permission</code>, <code>personal_access_tokens</code> | User memiliki role; role dan permission berelasi many-to-many; Sanctum menyimpan token API. |
| Anggota | <code>customers</code> | Profil anggota, dapat terhubung ke <code>users</code> dan collector. Menjadi parent bagi pinjaman, pembayaran, dan ledger tabungan. |
| Pinjaman | <code>loans</code>, <code>loan_schedules</code>, <code>approvals</code> | Pinjaman milik anggota dan collector; mempunyai jadwal serta record approval. |
| QR lama | <code>payment_qrs</code>, <code>payment_qr_schedules</code> | QR collector mengikat pinjaman/jadwal tertentu dan memiliki expiry. |
| Pembayaran | <code>payments</code>, <code>payment_allocations</code> | Pembayaran anggota dialokasikan ke jadwal pinjaman. |
| Token offline | <code>loan_token_counters</code>, <code>payment_tokens</code>, <code>token_sync_log</code>, <code>token_reconciliation_reviews</code> | Sequence per pinjaman, token bertanda tangan, hasil sync, dan catatan review. |
| Absensi | <code>attendance_logs</code>, <code>attendance_records</code> | Event perangkat mentah dan ringkasan harian employee. |
| Laravel | <code>sessions</code>, <code>password_reset_tokens</code>, <code>cache</code>, <code>cache_locks</code>, <code>jobs</code>, <code>job_batches</code>, <code>failed_jobs</code> | Infrastruktur session, cache, reset password, dan queue. |
| Tabungan (WIP) | <code>savings_transactions</code> | Ledger setoran/penarikan; implementasi belum di-route. |

Migration yang paling relevan dimulai dari <code>2026_07_17_122000_create_koperasi_domain_tables.php</code>; migration setelahnya menambah profil anggota, token, rekonsiliasi, dan tabungan. SQL di <code>DATABASE_SCHEMA.md</code> serta <code>koperasi-digital-monorepo/packages/db/</code> tidak otomatis menggambarkan database aktif Laravel.

## 4. Blueprint TypeScript Bersarang

### Komponen

- <code>apps/api/</code>: server Express pada port <code>API_PORT</code> (default 4000), router attendance, loans, payments.
- <code>apps/web/</code>: UI React/Vite pada port <code>WEB_PORT</code> (default 5173); <code>App.tsx</code> berisi layar login, dashboard, pinjaman, absensi, histori, dan pengaturan dengan data contoh lokal.
- <code>packages/shared/</code>: role/platform, aturan warna risiko, slot absensi, dan generator jadwal 60 hari tanpa hari Minggu.
- <code>packages/db/prisma/schema.prisma</code>: rancangan PostgreSQL.
- <code>packages/db/mysql/schema.sql</code>: alternatif skema MySQL; jangan jalankan bersamaan dengan PostgreSQL untuk database yang sama.
- <code>packages/ui/</code>: package UI bersama yang masih berupa titik awal.
- <code>apps/desktop/</code>: package Tauri; belum siap dijalankan sampai konfigurasi dan shell native dibuat.
- <code>firmware/esp32-attendance/</code>: skeleton client perangkat; fungsi baca fingerprint mengembalikan nilai placeholder.

Fungsi bersama di <code>packages/shared/src/index.ts</code> meliputi <code>canAccessPlatform</code>, <code>getLoanRiskColor</code>, <code>getAttendanceSlot</code>, <code>getAttendanceCalendarColor</code>, <code>generateCollectionDates</code>, dan <code>toDateOnly</code>. UI React berada di <code>apps/web/src/App.tsx</code>; server Express memasang router dari <code>apps/api/src/modules/</code>.

### Menjalankan prototipe API dan Web

Dari <code>koperasi-digital-monorepo/</code>:

~~~powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
pnpm install
~~~

Jalankan proses API dan Web di terminal terpisah:

~~~powershell
# Terminal 1
pnpm --filter @koperasi/api dev

# Terminal 2
pnpm --filter @koperasi/web dev
~~~

API default: <code>http://localhost:4000</code>; web default: <code>http://localhost:5173</code>. <code>pnpm dev</code> pada root Turbo juga mencoba menjalankan package desktop, sedangkan konfigurasi Tauri belum lengkap. <code>pnpm db:generate</code> menghasilkan Prisma Client dari skema; API contoh saat ini belum memakai Prisma Client untuk menyimpan data.

### Endpoint prototipe Express

| Endpoint | Perilaku saat ini |
|---|---|
| <code>GET /health</code> | Status server Express. |
| <code>POST /api/attendance/iot/logs</code> | Menghitung slot/warna dari payload; belum menyimpan ke database. |
| <code>GET /api/attendance/me</code> | Mengembalikan contoh data absensi tetap. |
| <code>POST /api/loans/preview-schedule</code> | Menghasilkan 60 tanggal penagihan dengan melewati hari Minggu. |
| <code>GET /api/loans/risk-preview/{arrearsDays}</code> | Mengubah jumlah hari tunggakan menjadi warna risiko. |
| <code>POST /api/payments/qr</code> | Memvalidasi body dengan Zod lalu menghasilkan token dan expiry contoh. |
| <code>POST /api/payments/scan</code> | Menghasilkan respons pembayaran contoh; tidak menyimpan transaksi. |

Server prototipe memakai CORS terbuka dan tidak memasang middleware autentikasi pada router contoh. Gunakan hanya untuk pengembangan lokal, bukan sebagai backend operasional atau publik.

## 5. Konfigurasi dan Operasional

| Variabel | Fungsi |
|---|---|
| <code>APP_KEY</code> | Enkripsi Laravel; buat dengan <code>php artisan key:generate</code>. |
| <code>DB_CONNECTION</code>, <code>DB_DATABASE</code> | Driver dan lokasi database Laravel. Development memakai SQLite. |
| <code>APP_ENV</code>, <code>APP_DEBUG</code>, <code>APP_URL</code> | Lingkungan dan URL aplikasi. Matikan debug pada deployment. |
| <code>IOT_API_KEY</code> | Kunci webhook IoT; wajib diisi agar pemeriksaan header aktif. |
| <code>PAYMENT_TOKEN_*</code> | Kunci Ed25519, key ID, keyring public, grace period, dan jumlah token harian. |
| <code>API_PORT</code>, <code>WEB_PORT</code>, <code>DATABASE_URL</code> | Konfigurasi prototipe monorepo; <code>DATABASE_URL</code> untuk Prisma PostgreSQL. |

<code>.env</code> dan database lokal dikecualikan Git. Jangan menyalin private key ke dokumentasi, issue, atau repository. Untuk server sungguhan, gunakan kredensial unik, HTTPS, backup database dan private key, serta ubah password seed.

### Perintah Laravel yang sering dipakai

| Perintah | Kegunaan |
|---|---|
| <code>php artisan migrate --seed</code> | Terapkan migration dan seed data dasar. |
| <code>php artisan route:list</code> | Tinjau route terdaftar. |
| <code>php artisan members:import &lt;path&gt;</code> | Impor CSV anggota. |
| <code>php artisan members:provision-users</code> | Membuat akun untuk anggota yang belum punya user. |
| <code>php artisan tokens:generate-keys</code> | Membuat keypair token offline; <code>--force</code> hanya untuk rotasi terencana. |
| <code>php artisan tokens:expire</code> | Menandai token yang lewat masa sinkronisasi sebagai expired. |
| <code>php artisan schedule:work</code> | Menjalankan scheduler Laravel lokal. |
| <code>npm run dev</code>, <code>npm run build</code> | Vite development server dan build asset Laravel. |
| <code>php artisan test</code> | Menjalankan suite test Laravel. |

Test yang ada berada di <code>tests/Feature</code> dan <code>tests/Unit</code>. Keberadaan perintah test di sini bukan pernyataan bahwa test dijalankan saat menyusun dokumen ini.

## 6. Batasan yang Perlu Diingat

1. Data anggota yang sebenarnya tidak disertakan di Git. Seeder anggota akan melewati impor jika <code>database/data/members.csv</code> tidak tersedia.
2. Akun seed dan akun provisioning memakai password awal <code>password</code>; hanya untuk development.
3. Webhook attendance terbuka bila <code>IOT_API_KEY</code> kosong. Isi key tersebut sebelum membuka API ke jaringan.
4. Tabel <code>permissions</code> tersedia, tetapi kontrol permission granular belum diterapkan pada semua layar.
5. Token offline hanya klaim pembayaran tunai. Konfirmasi server adalah keputusan final; <code>device_id</code> bukan autentikasi perangkat.
6. UI rekonsiliasi, UI attendance admin, UI riwayat pinjaman, aplikasi Android, dan tabungan web belum lengkap/tersedia.
7. Monorepo Express, UI React, Prisma, Tauri, dan ESP32 adalah prototipe terpisah; jangan menyamakan skemanya dengan database Laravel.
8. Berkas <code>AdminSavingsController</code>, <code>SavingsLedgerService</code>, model dan migration tabungan tampak di workspace, tetapi belum terpasang pada route. Periksa status Git sebelum rilis; berkas itu belum menjadi fitur siap pakai.

## 7. Dokumen Lain dan Sumber Kebenaran

| Dokumen | Isi |
|---|---|
| [README.md](README.md) | Quick start dan petunjuk dasar. |
| [PROJECT_STATUS.md](PROJECT_STATUS.md) | Snapshot fitur dan pekerjaan yang tersisa. |
| [ADMIN_FEATURES.md](ADMIN_FEATURES.md) | Panduan halaman admin dan batasan akses. |
| [API_CONTRACTS.md](API_CONTRACTS.md) | Contoh kontrak payload API Laravel. Cocokkan dengan route/controller aktif bila berbeda. |
| [PAYMENT_TOKEN_PROTOCOL.md](PAYMENT_TOKEN_PROTOCOL.md) | Format dan aturan token pembayaran offline. |
| [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) | Referensi skema; migration Laravel adalah sumber kebenaran implementasi. |
| [UI_REQUIREMENTS.md](UI_REQUIREMENTS.md) | Arah desain dan kebutuhan UI. |
| [IMPLEMENTATION_ROADMAP.md](IMPLEMENTATION_ROADMAP.md) | Roadmap dan referensi historis implementasi. |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Blueprint arsitektur awal; panduan ini menjelaskan implementasi yang ada sekarang. |
| <code>koperasi-digital-monorepo/docs/</code> | Dokumen khusus prototipe TypeScript dan integrasi eksternal. |

Untuk kode aktual, periksa berurutan: <code>routes/</code>, controller di <code>app/Http/Controllers/</code>, service di <code>app/Services/</code>, model di <code>app/Models/</code>, lalu migration di <code>database/migrations/</code>.

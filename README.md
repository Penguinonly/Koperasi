# Koperasi Harmoni

Sistem koperasi berbasis Laravel untuk mengelola anggota, karyawan, pinjaman, pembayaran, absensi, approval, dan portal mandiri anggota.

## Status Proyek

Lihat [PROJECT_STATUS.md](PROJECT_STATUS.md) untuk daftar fitur yang sudah ditambahkan, fitur yang masih sebagian, dan pekerjaan berikutnya.

Dokumen teknis lain:

- [ADMIN_FEATURES.md](ADMIN_FEATURES.md) - panduan fitur, route, role, dan batasan area admin.
- [ARCHITECTURE.md](ARCHITECTURE.md) - rancangan arsitektur dan domain.
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) - struktur database.
- [API_CONTRACTS.md](API_CONTRACTS.md) - kontrak REST API.
- [IMPLEMENTATION_ROADMAP.md](IMPLEMENTATION_ROADMAP.md) - roadmap awal implementasi.

## Menjalankan Aplikasi

Prasyarat: PHP 8.2+, Composer, Node.js, dan npm.

Data anggota tidak disertakan di repository karena berisi informasi pribadi. Untuk memuat data anggota, siapkan `database/data/members.csv` secara lokal dengan format kolom dari [`database/data/members.example.csv`](database/data/members.example.csv), lalu jalankan seeder atau `php artisan members:import`.

```powershell
composer install
npm install
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000/login` dan gunakan akun admin seed:

```text
Email: admin@koperasi.id
Password: password
```

Halaman admin tersedia di `http://127.0.0.1:8000/admin`.

### Portal Anggota

Anggota dengan akun yang sudah terhubung ke data anggota dapat membuka `http://127.0.0.1:8000/member` untuk:

- Melihat ringkasan profil dan pinjaman aktif.
- Melihat streak pembayaran dari jadwal pinjaman terakhir.
- Memindai QR pembayaran dari petugas melalui kamera.
- Meninjau nama, ID, nominal, dan masa berlaku QR sebelum konfirmasi.
- Melihat histori pembayaran yang sudah berhasil.
- Memperbarui nama, telepon, jenis kelamin, pekerjaan, nama usaha, dan alamat.

ID anggota dan status keanggotaan tetap dikelola pengurus. Akun anggota development dibuat melalui:

```powershell
php artisan members:provision-users
```

Format akun development adalah `member{id}@koperasi.id` dengan password awal `password`; contohnya `member1@koperasi.id`. Akun demo `customer@koperasi.id` hanya disediakan untuk pengujian role/API dan tidak terhubung ke profil anggota web secara default. Browser harus berjalan pada `localhost` atau HTTPS agar izin kamera dapat digunakan.

### Token Pembayaran Offline

Backend dapat menerbitkan paket token harian per pinjaman aktif yang ditugaskan kepada collector. Token ditandatangani Ed25519; aplikasi Android harus memverifikasi signature dan sequence secara lokal. Konfirmasi ke server tetap diperlukan agar pembayaran menjadi final.

Siapkan keypair satu kali per environment:

```powershell
php artisan tokens:generate-keys
```

Private key disimpan di `.env` lokal dan tidak boleh masuk repository. Jangan jalankan `--force` kecuali melakukan rotasi key terencana. Keyring public dibagikan lewat respons sinkronisasi. Jalankan scheduler Laravel agar token lewat masa sinkronisasi ditandai expired:

```powershell
php artisan schedule:work
```

Endpoint tersedia di `/api/collector/sync/tokens`, `/api/collector/sync/confirm`, dan `/api/member/sync/confirm`. Klaim offline v1 hanya menerima metode `CASH`; `redeemed_offline_at` hanya metadata perangkat, sedangkan `confirmed_at` adalah waktu server yang dipercaya. Lihat [PAYMENT_TOKEN_PROTOCOL.md](PAYMENT_TOKEN_PROTOCOL.md) untuk format payload, aturan sequence, grace 24 jam, dan batas keamanan offline.

Backend token sudah tersedia, tetapi verifikasi offline Ed25519, penyimpanan sequence tahan reset, dan antrean klaim lokal belum tersedia di workspace Laravel ini. Fitur tersebut perlu diimplementasikan pada aplikasi Android.

## Pemeriksaan

```powershell
php artisan migrate:status
php artisan route:list
php artisan view:cache
php artisan test
```

Pemeriksaan portal anggota secara khusus:

```powershell
php artisan test --filter=MemberPortalTest
php artisan test --filter=PaymentTokenSyncTest
```

## Laravel Framework

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Koperasi Harmoni

Aplikasi web Laravel untuk mengelola setoran dan penarikan tabungan anggota melalui satu halaman admin. Halaman mempertahankan tampilan kartu anggota yang sudah ada, dengan pencarian, filter status, saldo, dan transaksi terakhir.

## Fitur aktif

- Admin mencari anggota berdasarkan nama atau nomor anggota dan memfilter status keanggotaan.
- Admin mencatat setoran atau penarikan untuk tabungan Pokok, Wajib, atau Sukarela.
- Saldo keseluruhan tetap ditampilkan; dropdown Riwayat/Pokok/Wajib/Sukarela menampilkan riwayat dan total kategori yang dipilih.
- Saldo dan transaksi terakhir diperbarui otomatis setiap lima detik.
- Riwayat transaksi dapat dibuka per anggota dan difilter menurut kategori.
- Dalam Mode edit, admin dapat mengubah transaksi, memilih semua transaksi sesuai filter, serta menghapus satu atau beberapa transaksi terpilih.
- Riwayat lengkap atau kategori yang sedang dipilih dapat diunduh per anggota sebagai file Excel `.xlsx`; kolom saldo menjadi saldo berjalan kategori yang dipilih.
- Impor dan data profil anggota tetap tersedia. Halaman karyawan dan portal anggota sudah dihapus.

Halaman web operasional: `/admin`. Login tersedia di `/login`. Hanya akun ber-role `admin` atau `master_admin` yang dapat masuk. Seeder standar membuat `admin@koperasi.id` dengan password awal `password`; ganti password itu sebelum dipakai bersama.

## Menjalankan aplikasi

Prasyarat: PHP 8.2+, Composer, Node.js, dan npm.

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite | Out-Null }
npm ci
php artisan migrate --seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000/login`.

Data anggota asli tidak disertakan karena berisi informasi pribadi. Untuk mengimpornya, simpan CSV lokal di `database/data/members.csv` mengikuti format [`database/data/members.example.csv`](database/data/members.example.csv), lalu jalankan:

```powershell
php artisan members:import database/data/members.csv
```

Import anggota tidak membuat akun login anggota. Anggota tanpa transaksi tabungan akan menampilkan saldo `Rp 0,00`; saldo awal tidak ditebak dari data pinjaman atau pembayaran lama.

## Catatan operasional

- Setoran hanya bisa dicatat untuk anggota berstatus aktif.
- Penarikan ditolak jika melebihi saldo keseluruhan atau saldo kategori yang dipilih.
- Setiap transaksi menyimpan nominal, saldo setelah transaksi, referensi, admin pencatat, waktu, dan catatan opsional.
- Edit dan hapus memperbarui saldo keseluruhan serta subtotal kategori. Hapus memakai soft delete agar perubahan tetap memiliki jejak audit; perubahan ditolak jika membuat saldo transaksi berikutnya menjadi negatif.
- Transaksi lama yang belum memiliki kategori tetap terlihat di Riwayat dan saldo keseluruhan; tidak dihitung ke saldo Pokok, Wajib, atau Sukarela.
- Akun karyawan/anggota lama dan relasi datanya tidak dihapus dari database, tetapi tidak dapat login atau mengakses halaman melalui aplikasi ini. Ini mempertahankan histori dan relasi audit.
- Halaman mengambil saldo terbaru dengan polling HTTP lima detik; belum memakai WebSocket.

## Dokumentasi

- [PANDUAN_PROGRAM.md](PANDUAN_PROGRAM.md) — arsitektur, alur tabungan, data, route, setup, dan prototipe terpisah.
- [PROJECT_STATUS.md](PROJECT_STATUS.md) — ringkasan implementasi aktif dan batasannya.
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) — referensi skema; migration Laravel adalah sumber kebenaran.
- [PAYMENT_TOKEN_PROTOCOL.md](PAYMENT_TOKEN_PROTOCOL.md) — protokol token pembayaran lama sebagai referensi historis.

Folder `koperasi-digital-monorepo/` adalah prototipe TypeScript terpisah dan belum terhubung ke aplikasi Laravel.

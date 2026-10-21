# Fitur Admin Koperasi Harmoni

**Status aktif:** 21 Oktober 2026
**Halaman:** `/admin`
**Akun development:** `admin@koperasi.id` / `password`

Admin memakai satu halaman untuk mencari anggota, melihat saldo tabungan, mencatat setoran/penarikan, dan memeriksa riwayat transaksi. Panduan lengkap ada di [PANDUAN_PROGRAM.md](PANDUAN_PROGRAM.md).

## Akses

- Web session dan middleware `auth` + `admin.session` melindungi halaman serta endpoint tabungan.
- Role yang diizinkan: `admin` dan `master_admin`.
- Login menolak role collector dan customer. Route provisioning akun karyawan/anggota sudah dihapus.
- Akun lama tetap tersimpan untuk menjaga relasi histori, tetapi tidak dapat masuk ke aplikasi.

## Operasi halaman

1. Cari anggota berdasarkan nama atau nomor anggota.
2. Filter anggota aktif, tidak aktif, ditangguhkan, atau semua.
3. Lihat saldo dan transaksi terakhir di kartu.
4. Tekan **Setor** atau **Tarik**, isi nominal dan catatan opsional, lalu simpan.
5. Tekan **Detail** untuk membuka riwayat transaksi anggota.

Snapshot kartu diperbarui tiap lima detik melalui polling HTTP. Setoran hanya tersedia untuk anggota aktif; penarikan ditolak bila saldo kurang.

## Route aktif

| Method | Route | Fungsi |
|---|---|---|
| `GET` | `/admin` | Daftar dan filter anggota. |
| `GET` | `/admin/savings/snapshot` | Saldo dan transaksi terbaru untuk kartu yang tampil. |
| `GET` | `/admin/savings/{customer}` | Saldo dan riwayat 20 transaksi per halaman. |
| `POST` | `/admin/savings/{customer}` | Catat setoran atau penarikan. |

Halaman karyawan, anggota, pinjaman, dan fitur admin lama telah dihapus dari web. REST API bisnis lama tidak aktif; `/api/health` tetap tersedia.

## Konsistensi saldo

`SavingsLedgerService` menghitung saldo dari transaksi terakhir, menggunakan transaksi database dan mengunci anggota selama penulisan. Setiap request membawa UUID idempotensi. Catatan `savings_transactions` memuat anggota, admin pencatat, jenis, nominal, saldo setelah transaksi, kode referensi, catatan, dan waktu.

Anggota tanpa transaksi memiliki saldo tampilan `Rp 0,00`. Importer anggota tidak memberi saldo awal. Jangan menghapus record anggota atau akun pencatat setelah transaksi tersimpan karena foreign key menjaga histori ledger.

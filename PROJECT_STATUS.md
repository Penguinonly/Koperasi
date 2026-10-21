# Status Implementasi Koperasi Harmoni

**Diperbarui:** 21 Oktober 2026

## Aplikasi aktif

Aplikasi Laravel di root repository saat ini memiliki satu area operasional: admin tabungan anggota.

- Login web berbasis session untuk role `admin` dan `master_admin`.
- Halaman `/admin` memakai UI kartu anggota yang telah ada, dengan pencarian nama/kode anggota dan filter status.
- Kartu menampilkan saldo tabungan serta transaksi terakhir.
- Admin dapat mencatat setoran dan penarikan untuk tabungan Pokok, Wajib, dan Sukarela.
- Modal mempertahankan saldo keseluruhan dan menyediakan dropdown Riwayat/Pokok/Wajib/Sukarela dengan total saldo kategori serta riwayat tersaring.
- Saldo diperbarui otomatis setiap lima detik melalui endpoint snapshot.
- Data profil anggota dan importer CSV tetap dipakai.
- Route web selain login, logout, redirect dashboard lama, dan halaman admin telah dihapus. REST API Laravel hanya menyediakan health check.

## Ledger tabungan

Ledger disimpan di `savings_transactions`. Saldo keseluruhan adalah `balance_after` pada transaksi terakhir; bila belum ada transaksi, UI menampilkan nol. Setoran mensyaratkan anggota aktif. Penarikan tidak boleh melebihi saldo keseluruhan maupun saldo kategori yang dipilih. Saldo Pokok/Wajib/Sukarela dihitung dari setoran dikurangi penarikan pada kategori tersebut. Pencatatan memakai transaksi database, penguncian baris anggota, dan kunci idempotensi.

Migration tabungan: `database/migrations/2026_10_21_000001_create_savings_transactions_table.php` dan `database/migrations/2026_10_21_000002_add_category_to_savings_transactions.php`. Baris lama dibiarkan tanpa kategori agar tidak salah diklasifikasikan; jumlahnya tetap masuk saldo keseluruhan dan tampak di filter Riwayat.

## Akses dan retensi data

- Seeder hanya membuat akun admin pengembangan `admin@koperasi.id` / `password`.
- Login menolak role karyawan/collector dan anggota/customer, termasuk akun lama yang masih tersimpan.
- Fitur provisioning akun anggota telah dihapus.
- Data anggota, akun terdahulu, pinjaman, pembayaran, dan histori tidak dihapus. Beberapa tabel dan service lama tetap ada untuk menjaga data historis, tetapi route-nya tidak aktif pada aplikasi web/API saat ini.
- Prototipe `koperasi-digital-monorepo/` terpisah dan tidak mengakses database Laravel.

## Batasan

- “Realtime” memakai polling lima detik, bukan koneksi WebSocket.
- Data impor anggota tidak menyertakan saldo pembukaan. Anggota yang belum memiliki ledger dimulai dari saldo tampilan nol.
- Hanya akun admin yang dapat mengubah ledger dari UI.
- Akun seed memakai password development; ubah sebelum pemakaian di lingkungan bersama.
- Test suite tidak dijalankan. `php artisan route:list`, pemeriksaan sintaks PHP, `php artisan view:cache`, dan `npm run build` berhasil.

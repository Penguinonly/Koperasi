# Master Prompt Project Koperasi Digital

Gunakan prompt ini sebagai arahan utama ketika memulai implementasi Android, Desktop, Backend, Database, dan IoT.

## Master Prompt

Buatkan sistem **Koperasi Digital** yang terdiri dari aplikasi Android, aplikasi Desktop, Backend API, Database MySQL, dan integrasi IoT fingerprint untuk absensi karyawan.

### 1. Platform dan Role

Sistem memiliki empat role:

1. **Nasabah**
2. **Karyawan/Penagih**
3. **Admin**
4. **Master Admin**

Aturan akses:

- Aplikasi **Android** dapat diakses oleh Nasabah, Karyawan/Penagih, Admin, dan Master Admin.
- Aplikasi **Desktop** hanya dapat diakses oleh Karyawan/Penagih, Admin, dan Master Admin.
- Nasabah tidak boleh login ke Desktop.
- Master Admin memiliki hak tertinggi untuk mengelola Admin dan Karyawan.

### 2. Stack yang Digunakan

- **Android**: Android Studio native atau Kotlin/Java.
- **Desktop**: React.js sebagai renderer desktop, dapat dibungkus dengan Electron atau Tauri.
- **Backend**: Node.js + Express.
- **Database**: MySQL 8.
- **IoT**: ESP32 + fingerprint sensor yang mengirim log absensi ke Backend.
- **Maps**: Google Maps API untuk penentuan lokasi usaha Nasabah setelah survei.

### 3. Aturan Pinjaman

- Setiap pinjaman wajib memiliki siklus penagihan **60 hari**.
- Penagihan dilakukan setiap hari kecuali **hari Minggu**.
- Saat pinjaman disetujui, sistem harus membuat 60 jadwal tagihan di tabel `loan_schedules`.
- Hari Minggu tidak dibuatkan jadwal tagihan dan tidak memutus streak pembayaran.
- Setiap jadwal tagihan memiliki status: pending, partial, paid, overdue, atau skipped.

### 4. Alur Nasabah

Nasabah melakukan pendaftaran melalui Android dengan data:

- Nama lengkap
- Nomor HP
- Password
- Data KTP/NIK jika diperlukan
- Nama usaha/pekerjaan
- Jenis usaha
- Alamat usaha
- Titik koordinat Google Maps setelah survei
- Karyawan/Penagih yang mengajukan dan menjadi penanggung jawab

Nasabah dapat:

- Melihat status pinjaman aktif.
- Melihat streak pembayaran.
- Melakukan pembayaran tunai atau transfer.
- Melakukan scan QR dari Karyawan/Penagih sebagai validasi wajib pembayaran.

### 5. QR Pembayaran Dinamis

Karyawan/Penagih atau Admin dapat membuat QR pembayaran dengan mode:

1. Tagihan 1 hari
2. Tagihan 2 hari
3. Akumulasi tunggakan hari sebelumnya
4. Custom beberapa jadwal tagihan

QR harus memiliki:

- Token unik yang aman.
- Waktu kedaluwarsa.
- Relasi ke Nasabah, pinjaman, dan jadwal tagihan yang dibayar.
- Status active, scanned, expired, atau cancelled.

Setelah Nasabah scan QR, sistem membuat data pembayaran dan mengalokasikan nominalnya ke jadwal tagihan terkait.

### 6. Color Coding Nasabah

Tampilkan badge warna pada card/profil Nasabah:

- **Biru**: pembayaran lancar tanpa tunggakan.
- **Kuning**: tunggakan 1 sampai 2 hari.
- **Merah**: tunggakan 3 sampai 9 hari.
- **Hitam/Merah Gelap**: tunggakan lebih dari 10 hari atau kredit macet.

Dashboard harus menampilkan:

- Nasabah dengan streak pembayaran tertinggi.
- Nasabah dengan tunggakan terburuk.
- Nasabah yang sudah scan QR hari ini.
- Nasabah yang belum scan QR hari ini.

### 7. Fitur Karyawan/Penagih

Karyawan/Penagih dapat menggunakan Android dan Desktop.

Fitur:

- Login.
- Dashboard.
- Mengajukan Nasabah baru.
- Mengisi data usaha/pekerjaan Nasabah.
- Mengisi nominal pinjaman yang diajukan.
- Mengisi analisis apakah pinjaman mampu dilunasi dalam 60 hari.
- Mengunggah foto usaha dan jaminan.
- CRUD data Nasabah, tetapi perubahan penting harus divalidasi Admin.
- Generate QR pembayaran.
- Melihat daftar Nasabah berdasarkan penanggung jawab dirinya.
- Melihat riwayat scan QR harian.
- Melihat daftar Nasabah yang belum scan QR pada hari berjalan.
- Melihat leaderboard streak dan tunggakan.
- Melihat absensi diri sendiri.

### 8. Fitur Admin

Admin dapat menggunakan Android dan Desktop.

Fitur:

- Login.
- Dashboard monitoring.
- Validasi pengajuan Nasabah dari Karyawan.
- Validasi update data Nasabah.
- CRUD Karyawan/Penagih.
- Melihat daftar Nasabah menunggak berdasarkan Karyawan penanggung jawab.
- Filter Nasabah kategori merah dan hitam.
- Generate QR seperti Karyawan.
- Memiliki dan mengelola Nasabah sendiri.
- Memantau absensi semua Karyawan.
- Membuat rekap bulanan berisi:
  - daftar Nasabah,
  - nominal pinjaman tersisa,
  - jumlah hari tunggakan,
  - streak pembayaran,
  - status risiko warna.

### 9. Fitur Master Admin

Master Admin dapat menggunakan Android dan Desktop.

Fitur:

- CRUD Admin.
- CRUD Karyawan/Penagih.
- Mengatur status akun.
- Melihat audit aktivitas super user.
- Memantau ringkasan operasional seluruh sistem.

### 10. Absensi IoT

Perangkat fingerprint mengirim log absensi ke Backend.

Setiap hari terdapat empat slot absensi:

1. **Masuk Pagi**: 07.00 - 10.59
2. **Keluar Siang**: 11.00 - 15.59
3. **Masuk Sore**: 16.00 - 17.29
4. **Keluar Malam**: 17.30 - 23.59

Warna kalender absensi:

- **Biru**: lengkap empat slot.
- **Kuning**: parsial, hanya absen pagi saja/sore saja atau tidak lengkap.
- **Merah**: alpha, tidak melakukan absen sama sekali.

Backend harus menyimpan:

- Log mentah dari IoT.
- Ringkasan absensi harian per karyawan.
- Status warna kalender.

### 11. Desain UI Desktop

UI Desktop mengikuti gaya referensi:

- Halaman login split screen.
- Sisi kiri menampilkan ilustrasi gedung/koperasi/logo.
- Sisi kanan menampilkan form login dengan background biru muda.
- Setelah login, tampil top navigation:
  - Logo koperasi
  - Dashboard
  - Peminjaman
  - Absensi
  - Riwayat
  - Pengaturan
- Gunakan card dengan shadow halus dan badge status seperti contoh.
- Warna utama: biru tua `#003D5B`, biru muda `#E6F4FF`, merah `#FF3131`, putih, abu muda.

### 12. Database Utama

Gunakan tabel utama:

- `users`
- `employee_profiles`
- `customer_profiles`
- `loan_applications`
- `loan_business_photos`
- `loan_collaterals`
- `loans`
- `loan_schedules`
- `payment_qrs`
- `payment_qr_schedules`
- `payments`
- `payment_allocations`
- `customer_daily_snapshots`
- `approval_requests`
- `iot_devices`
- `fingerprint_credentials`
- `attendance_logs`
- `attendance_records`
- `audit_logs`

Gunakan foreign key, index, unique constraint, dan view dashboard agar data mudah dimonitor.

### 13. Output yang Diminta

Buat:

1. Database MySQL siap import.
2. Backend API modular.
3. UI Desktop dengan halaman login dan halaman Dashboard, Peminjaman, Absensi, Riwayat, Pengaturan.
4. Field kontrak API untuk Android Studio.
5. Dokumentasi alur bisnis.
6. Integrasi endpoint IoT untuk fingerprint.

Pastikan implementasi modular, mudah dikembangkan, dan semua aturan bisnis 60 hari, QR dinamis, color coding tunggakan, serta absensi 4 slot berjalan konsisten.

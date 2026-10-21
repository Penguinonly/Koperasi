# Koperasi Harmoni - UI Requirements & Design Direction

**Tanggal observasi:** 23 September 2026  
**Sumber:** mockup Master Admin, mockup Data Nasabah/Karyawan/Admin, model database, route API, dan halaman Blade yang sudah tersedia.

**Snapshot implementasi:** 15 Oktober 2026. Login, shell admin, dashboard admin, Data Karyawan, Data Nasabah, detail anggota/pinjaman, kalender, workspace collector dasar, portal anggota, UI Validasi Pinjaman, serta backend token pembayaran offline tersedia. UI Riwayat Pinjaman, UI collector untuk token offline, rekonsiliasi, absensi, dan Data Admin masih belum tersedia.

## 1. Keputusan Produk

UI akan dibangun sebagai **operational workspace**, bukan landing page. Pengguna utama membutuhkan daftar data yang cepat dipindai, filter yang jelas, status yang mudah dibedakan, dan tindakan CRUD yang dekat dengan data.

Prinsip utama:

- Satu shell navigasi bersama untuk semua halaman internal.
- Setiap daftar memiliki search, filter, status, pagination, dan empty state.
- Setiap data penting memiliki halaman detail atau drawer/modal detail.
- Tindakan berisiko seperti delete, reject, dan deactivate membutuhkan konfirmasi.
- Warna status dipakai konsisten dan selalu didampingi label teks.
- Layout desktop mengikuti mockup; layout mobile berubah menjadi stacked list/card tanpa memaksa tabel melebar.

## 2. Role dan Kebutuhan Layar

### 2.1 Master Admin

Master Admin mengelola keseluruhan operasi dan akun internal.

Layar wajib:

1. Dashboard Master Admin
2. Data Admin
3. Data Karyawan
4. Data Nasabah
5. Validasi Pinjaman
6. Riwayat Pinjaman
7. Detail Nasabah
8. Detail Pinjaman
9. Absensi dan monitoring karyawan
10. Pengaturan role/permission

KPI dashboard:

- Jumlah karyawan aktif.
- Jumlah admin.
- Jumlah nasabah aktif.
- Pinjaman menunggu validasi.
- Total pinjaman aktif.
- Tunggakan hari ini.

### 2.2 Admin

Admin menjalankan operasi harian, tanpa mengelola Master Admin dan permission global.

Layar wajib:

1. Dashboard Admin
2. Data Karyawan
3. Data Nasabah
4. Validasi Pinjaman
5. Riwayat Pinjaman
6. Absensi
7. Detail data dan approval

Batasan:

- Tidak dapat mengubah Master Admin.
- Tidak dapat mengelola permission global.
- Dapat mengelola karyawan/nasabah sesuai permission.

### 2.3 Collector/Karyawan

Collector bekerja pada nasabah, pengajuan pinjaman, pembayaran, dan kehadiran sendiri.

Layar wajib:

1. Dashboard Collector
2. Daftar nasabah yang ditugaskan
3. Detail nasabah
4. Buat pengajuan pinjaman
5. Monitoring pinjaman harian
6. Buat QR pembayaran
7. Leaderboard
8. Absensi saya

### 2.4 Nasabah

Nasabah fokus pada informasi pribadi, pinjaman, jadwal pembayaran, dan pembayaran.

Layar wajib:

1. Dashboard Nasabah
2. Profil saya
3. Pengajuan/pinjaman aktif
4. Jadwal angsuran
5. Riwayat pembayaran
6. Scan atau konfirmasi pembayaran

Implementasi web anggota saat ini:

- Dashboard anggota: `/member`
- Profil anggota: `/member/profile`
- Riwayat pembayaran: `/member/payments`
- Preview QR: `POST /member/scan/preview`
- Konfirmasi QR: `POST /member/scan`
- Akses dibatasi ke customer yang memiliki relasi `customers.user_id`.
- Profil hanya dapat mengubah informasi pribadi; ID anggota dan status dikunci.
- Streak ditampilkan dalam satuan hari berdasarkan jadwal lunas beruntun pada pinjaman terakhir.

## 3. Prioritas Implementasi UI

### Sprint UI 1 - Admin Foundation

Status sebagian sudah tersedia.

- Login admin.
- Shell admin.
- Dashboard Master Admin.
- Data Karyawan: list, search, tambah, edit, nonaktifkan.
- Session guard dan logout.

**Status:** Selesai untuk login, shell, dashboard, dan Data Karyawan.

### Sprint UI 2 - Data Master

Prioritas berikutnya.

- Data Admin.
- Data Nasabah berdasarkan kolom CSV.
- Detail anggota.
- Import CSV/XLSX.
- Download/export data.

**Status:** Data Nasabah, detail anggota, akun anggota, dan import CSV sudah tersedia. Import XLSX dan export belum tersedia.

### Sprint UI 3 - Pinjaman

- Validasi pinjaman.
- Detail pinjaman.
- Approve/reject dengan alasan.
- Riwayat pinjaman.
- Filter status dan rentang tanggal.

**Status:** Detail pinjaman, kalender pembayaran, dan UI Validasi Pinjaman tersedia. UI Riwayat Pinjaman khusus belum tersedia.

### Sprint UI 4 - Operasional

- Dashboard collector.
- Monitoring harian.
- QR pembayaran.
- Absensi admin/collector.
- Riwayat pembayaran.

### Sprint UI 5 - Nasabah

- Dashboard nasabah dengan ringkasan pinjaman, streak, pembayaran terakhir, dan scan QR.
- Detail pinjaman dan jadwal.
- Histori dan konfirmasi pembayaran.
- Profil pribadi dengan edit informasi dasar.
- Profil dan dokumen.

**Status:** Dashboard anggota, profil pribadi, histori pembayaran, scan QR dua tahap, dan streak sudah tersedia. Upload dokumen belum tersedia.

Catatan batas implementasi: penerbitan dan konfirmasi token offline sudah tersedia pada API Laravel; verifikasi Ed25519 lokal, penyimpanan sequence, dan antrean sinkronisasi pada aplikasi Android masih harus dibuat di repository Android.

## 4. Peta Navigasi Internal

```text
Login
└── Role-based workspace
    ├── Master Admin
    │   ├── Dashboard
    │   ├── Data Admin
    │   ├── Data Karyawan
    │   ├── Data Nasabah
    │   ├── Validasi Pinjaman
    │   ├── Riwayat Pinjaman
    │   ├── Absensi
    │   └── Pengaturan
    ├── Admin
    │   ├── Dashboard
    │   ├── Data Karyawan
    │   ├── Data Nasabah
    │   ├── Validasi Pinjaman
    │   ├── Riwayat Pinjaman
    │   └── Absensi
    ├── Collector
    │   ├── Dashboard
    │   ├── Nasabah Saya
    │   ├── Monitoring Harian
    │   ├── Leaderboard
    │   └── Absensi Saya
    └── Nasabah
        ├── Dashboard
        ├── Profil
        ├── Pinjaman Saya
        ├── Jadwal Angsuran
        ├── Riwayat Pembayaran
        └── Scan QR Pembayaran
```

## 5. Tema Visual yang Dikunci

### 5.1 Karakter visual

- Terang, bersih, administratif, dan mudah dipindai.
- Nuansa koperasi Indonesia melalui logo dan warna biru tua.
- Bentuk panel sederhana dengan border tipis, bukan dekorasi berlebihan.
- Hero dashboard memakai biru muda sebagai bidang informasi utama.
- Tabel dan list memakai garis pemisah biru muda seperti mockup.

### 5.2 Token warna

| Token | Nilai | Penggunaan |
|---|---|---|
| `brand-900` | `#063C55` | Navigasi aktif, tombol utama, link utama |
| `brand-700` | `#07516F` | Hover dan emphasis |
| `sky-100` | `#D8F0FB` | Hero dashboard dan bidang informasi |
| `surface` | `#FFFFFF` | Panel, tabel, form |
| `canvas` | `#F5F7F8` | Background area kerja |
| `line` | `#D5DFE3` | Border dan divider |
| `line-strong` | `#A7CEDD` | Garis pemisah data |
| `ink` | `#111827` | Teks utama |
| `muted` | `#66757D` | Metadata dan label sekunder |
| `danger` | `#D71920` | Ditolak, offline, hapus, tunggakan |
| `success` | `#0B6B4F` | Aktif, lunas, verified |
| `warning` | `#B7791F` | Menunggu, perlu perhatian |

Tidak digunakan sebagai default:

- Gradient besar.
- Ungu sebagai warna dominan.
- Shadow berat.
- Card bertumpuk di dalam card.
- Ikon tanpa tooltip pada tindakan yang tidak umum.

### 5.3 Tipografi

- Font UI: `Instrument Sans` atau font sans yang sudah dipakai proyek.
- Judul halaman: 24-32px, tegas, tidak terlalu dekoratif.
- Tabel: 13-14px agar data padat tetapi tetap terbaca.
- Label: 12-13px dengan warna muted.
- Tombol: 13-14px, label singkat, ikon hanya jika membantu konteks.

### 5.4 Spacing dan bentuk

- Header: 64-72px.
- Content max width: 1440px.
- Jarak antar section: 20-32px.
- Radius panel: 8-12px.
- Radius tombol/filter: pill hanya untuk search/filter; tombol aksi biasa 6-8px.
- Tabel desktop dapat scroll horizontal pada layar kecil.
- Kartu mobile memiliki tinggi stabil agar status/action tidak melompat.

## 6. Komponen Bersama yang Dibutuhkan

Sebelum membuat banyak halaman, komponen berikut perlu distandarkan pada Blade partial atau komponen Blade:

1. `admin-shell` - header, logo, navigasi, logout.
2. `page-header` - judul, deskripsi singkat, action utama.
3. `search-filter-bar` - search, category/status filter, download.
4. `stat-strip` - tiga sampai lima KPI ringkas.
5. `data-table` - header, row, empty, loading, pagination.
6. `status-badge` - active, inactive, verified, pending, rejected, paid.
7. `action-buttons` - view, edit, delete/deactivate.
8. `form-panel` - create/edit dengan error validation.
9. `confirm-dialog` - deactivate, reject, delete.
10. `detail-panel` - ringkasan anggota, pinjaman, status, timeline.
11. `pagination` - konsisten untuk semua list.
12. `toast/alert` - sukses, error, validation.

## 7. Kebutuhan Data per Halaman

### Data Admin

- Nama.
- Email.
- Employee ID jika ada.
- Role.
- Status aktif.
- Last login jika audit log sudah tersedia.
- Action: view, edit, deactivate.

### Data Karyawan

- Nama.
- Employee ID.
- Email.
- Role collector.
- Platform.
- Status online/offline atau aktif/nonaktif.
- Total nasabah yang ditangani.
- Action: view, edit, deactivate.

### Data Nasabah

- `member_code` / ID anggota.
- Nama.
- Jenis kelamin.
- Mata pencaharian.
- Alamat.
- Status anggota.
- Tanggal berhenti jika ada.
- Alasan berhenti jika ada.
- Total pinjaman.
- Status pinjaman terakhir.
- Action: view, edit, export.

### Validasi Pinjaman

- Nama nasabah.
- Kode anggota.
- Collector.
- Jumlah pinjaman.
- Durasi.
- Bunga.
- Tanggal pengajuan.
- Status.
- Action: detail, approve, reject.

### Riwayat Pinjaman

- Nomor pinjaman.
- Nasabah.
- Collector.
- Nominal.
- Status.
- Progress pembayaran.
- Tanggal mulai/selesai.
- Tunggakan.

### Absensi

- Nama karyawan.
- Employee ID.
- Tanggal.
- Morning in/out.
- Afternoon in/out.
- Status complete/partial/absent.
- Filter tanggal dan status.

### Portal Anggota

- Identitas anggota dan status akun.
- Pinjaman aktif dan nominal.
- Streak pembayaran dalam hari.
- Tiga pembayaran terakhir pada dashboard.
- Riwayat pembayaran berhasil dengan tanggal, pinjaman, referensi, nominal, dan status.
- Form edit nama, telepon, jenis kelamin, pekerjaan, nama usaha, dan alamat.
- Scanner kamera QR.
- Preview nama anggota, ID, nominal, dan masa berlaku QR.
- Konfirmasi pembayaran eksplisit setelah preview.

## 8. Aturan Responsif

Desktop:

- Navigasi horizontal seperti mockup.
- Tabel penuh dengan kolom action di sisi kanan.
- KPI tampil dalam satu baris.

Tablet:

- Navigasi dipadatkan.
- Kolom sekunder dapat dipindahkan ke detail.
- Search/filter boleh turun ke baris kedua.

Mobile:

- Navigasi menjadi menu ringkas.
- Tabel berubah menjadi list/card data.
- Action tetap mudah dijangkau dan tidak hanya berupa ikon tanpa label.
- Form menjadi satu kolom.
- Portal anggota menampilkan bottom navigation untuk Beranda, Pembayaran, dan Profil.
- Scanner QR membutuhkan izin kamera dan menyediakan aksi mulai, hentikan, serta scan ulang.
- Hero dashboard menumpuk logo dan teks.

## 9. Acceptance Criteria UI

Sebuah halaman dianggap siap apabila:

- Memiliki route dan role access yang benar.
- Mengambil data nyata dari model/API terkait.
- Memiliki loading/empty/error/success state yang masuk akal.
- Search/filter/pagination bekerja jika halaman berupa list.
- Data anggota hanya dapat diubah oleh pemilik akun pada field profil yang diizinkan.
- Pembayaran QR menampilkan preview sebelum transaksi dicatat.
- QR anggota lain ditolak oleh server.
- Streak dan histori pembayaran diambil dari data pembayaran/jadwal nyata.
- Action destructive memiliki konfirmasi.
- Tidak ada teks atau tombol yang keluar container pada desktop/mobile.
- Warna status konsisten dengan token visual.
- Lulus `php artisan view:cache`, `npm run build`, dan feature test terkait.

## 10. Keputusan Implementasi Berikutnya

Urutan yang disarankan setelah blueprint ini disetujui:

1. Membuat `admin-shell` dan partial komponen bersama.
2. Menyempurnakan Data Karyawan menggunakan shell bersama.
3. Membuat Data Nasabah Master Admin karena data CSV dan model sudah tersedia.
4. Membuat halaman detail nasabah.
5. Menambahkan UI pembayaran operasional collector dan rekonsiliasi.
6. Membuat Validasi Pinjaman.
7. Membuat Riwayat Pinjaman.
8. Membuat Data Admin dan pengaturan role.
9. Membuat Absensi.

Dengan urutan ini, halaman baru akan menggunakan komponen dan tema yang sama sejak awal, bukan dipoles satu per satu setelah semua halaman terlanjur berbeda.

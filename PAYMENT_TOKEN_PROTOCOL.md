# Protokol Token Pembayaran Offline

**Versi:** 1  
**Tanggal:** 2 Oktober 2026  
**Status:** Backend Laravel v1 tersedia dan memiliki feature test; aplikasi Android perlu menerapkan verifikasi lokal yang sama.

## Tujuan dan Batas Kepercayaan

Server menerbitkan token dengan tanda tangan Ed25519. Aplikasi collector dan anggota memverifikasi tanda tangan secara offline dengan public key server. Collector hanya meneruskan token; private key tidak pernah berada di perangkat.

Verifikasi offline membuktikan token dibuat server dan payload tidak berubah. Verifikasi itu tidak membuktikan uang tunai benar-benar diterima, dan server tidak dapat membuktikan jam perangkat saat klaim offline dibuat. Pembayaran baru final setelah server menerima serta menerima klaim sinkronisasi.

## Payload Token v1

Signature detached Ed25519 dihitung atas JSON kanonis dengan urutan key tetap berikut. `max_amount_minor` memakai satuan sen agar tidak ada ambiguitas float/format desimal.

```json
{
  "version": 1,
  "key_id": "server-2026-01",
  "token_uuid": "uuid",
  "loan_id": 123,
  "sequence": 47,
  "issued_by": 12,
  "max_amount_minor": 5000000,
  "issued_at": "2026-10-02T06:00:00Z",
  "expires_at": "2026-10-02T23:59:59Z"
}
```

QR berisi payload tersebut dan detached signature base64url. Payload tidak dienkripsi. Server menyimpan signature serta field payload untuk audit dan membangun ulang string kanonis saat memverifikasi.

## Penerbitan Token

- Endpoint: `POST /api/collector/sync/tokens`, hanya role `collector` yang aktif.
- Respons menyertakan `active_key_id`, keyring public Ed25519, `server_time`, dan `sync_grace_hours` untuk verifikasi klien.
- Server memilih pinjaman `ACTIVE` yang ditugaskan kepada collector tersebut.
- Untuk setiap pinjaman dengan tagihan belum lunas yang sudah jatuh tempo, server menyiapkan satu token utama dan dua token cadangan.
- `max_amount` dihitung server dari sisa tagihan jatuh tempo saat penerbitan. Collector tidak dapat menentukan atau menaikkan plafon.
- Semua sequence dialokasikan atomik per pinjaman. Counter tidak boleh mundur; constraint unik `(loan_id, sequence)` menjadi pengaman terakhir.
- Permintaan sinkronisasi kedua pada hari server yang sama mengembalikan paket yang sudah diterbitkan, bukan menerbitkan paket baru.
- `device_id` adalah metadata audit, bukan bukti identitas perangkat. Identitas peminta ditentukan oleh autentikasi Sanctum.

## Klaim dan Konfirmasi

- Endpoint collector: `POST /api/collector/sync/confirm`.
- Endpoint anggota: `POST /api/member/sync/confirm`.
- Satu item klaim berisi token bertanda tangan, nominal aktual, metode `CASH`, `device_id`, dan `redeemed_offline_at` dari perangkat.
- Collector hanya boleh mengirim token yang diterbitkan untuknya; anggota hanya boleh mengirim token untuk pinjaman milik akun anggotanya.
- Nominal harus positif, tidak melebihi plafon token, dan tidak melebihi sisa tagihan yang dapat dialokasikan.
- Server mengalokasikan nominal ke jadwal jatuh tempo tertua. Alokasi parsial wajib disimpan pada tabel alokasi pembayaran; status jadwal menjadi `PAID` saat total alokasi mencapai `amount_due`.
- Pembayaran, alokasi, status token, counter, streak, dan log hasil sinkronisasi diperbarui dalam transaksi database.
- Retry dengan token dan nominal yang sama bersifat idempoten dan mengembalikan pembayaran yang sama. Klaim token yang sama dengan nominal berbeda menghasilkan `SEQUENCE_CONFLICT` dan tidak membuat pembayaran kedua.
- Klaim atas sequence yang lebih rendah dari high-water mark ditolak; sequence yang lebih tinggi dapat diterima dan membatalkan token sequence lebih rendah yang belum dipakai.
- Hasil setiap klaim dicatat append-only di `token_sync_log`, termasuk signature invalid, expired, duplicate, dan konflik.

## Kedaluwarsa dan Sinkronisasi Tertunda

Perangkat wajib menolak token setelah `expires_at`. Karena `redeemed_offline_at` berasal dari jam perangkat dan tidak dapat dipercaya server, server tidak menggunakannya sebagai bukti bahwa klaim terjadi sebelum kedaluwarsa.

Kebijakan v1 mengizinkan klaim diterima hingga 24 jam setelah `expires_at`, berdasarkan waktu penerimaan server. Waktu perangkat hanya metadata. Ini memberi kesempatan sinkronisasi terlambat, tetapi perangkat yang dimodifikasi dapat mencoba memakai token setelah waktu offline berakhir; risiko tersebut dibatasi oleh sequence tunggal, nominal maksimum, autentikasi akun, dan batas jendela 24 jam. Klaim setelah jendela tersebut ditolak sebagai `EXPIRED` dan perlu penyelesaian manual di luar alur pembayaran otomatis.

`confirmed_at` selalu waktu server dan merupakan waktu final sistem. `redeemed_offline_at` tidak boleh dipakai untuk laporan keuangan atau perhitungan streak.

## Perubahan Skema Lanjutan

Migration token sudah tersedia di `database/migrations`:

- `2026_10_02_000001_create_loan_token_counters_table.php`
- `2026_10_02_000002_create_payment_tokens_table.php`
- `2026_10_02_000003_create_token_sync_log_table.php`
- `2026_10_02_000004_add_payment_token_allocations.php` menambahkan `key_id`, versi payload, relasi nullable-unique `payments.payment_token_id`, dan `payment_allocations`.
- `2026_10_02_000005_add_rejected_token_sync_result.php` menambahkan hasil audit `REJECTED`.

Service dan endpoint Laravel sudah menggunakan skema tersebut. Migration ini menambah tabel token tanpa menghapus tabel QR lama.

Migration awal tidak menghapus `payment_qrs`. QR online lama dan token offline berjalan berdampingan selama masa transisi; tabel lama baru dipensiunkan setelah klien dan laporan dimigrasikan.

## Status Sinkronisasi

| Status token | Arti |
|---|---|
| `ISSUED` | Diterbitkan server, belum ada klaim diterima server |
| `REDEEMED_OFFLINE` | Status lokal perangkat; tidak final sebelum diterima server |
| `CONFIRMED` | Server menerima klaim dan membuat pembayaran final |
| `EXPIRED` | Melewati jendela sinkronisasi server tanpa konfirmasi |
| `VOID` | Dibatalkan oleh proses admin yang diaudit |

`token_sync_log.result` mencatat hasil tiap permintaan. `ACCEPTED` berarti pembayaran dibuat; `DUPLICATE` berarti retry identik; `SEQUENCE_CONFLICT` berarti klaim berbeda atas token/sequence yang sama. Konflik tidak diputuskan berdasarkan jam perangkat.

## Prasyarat Kriptografi

- Private key Ed25519 hanya disimpan sebagai secret server dan tidak dimasukkan ke repository, database token, atau log.
- Public key didistribusikan ke aplikasi bersama `key_id`.
- Rotasi melalui `php artisan tokens:generate-keys` membuat pasangan key baru dan mempertahankan public key lama pada keyring selama masa berlaku token yang sudah diterbitkan.
- Runtime PHP memakai API `paragonie/sodium_compat`; extension `ext-sodium` dapat diaktifkan kemudian sebagai akselerasi native.

## Implementasi Server yang Tersedia

- `POST /api/collector/sync/tokens` menerbitkan tiga token per pinjaman aktif yang ditugaskan dan memiliki tunggakan jatuh tempo, lalu mengembalikan `active_key_id`, `public_keys`, `server_time`, dan `sync_grace_hours`.
- `POST /api/collector/sync/confirm` dan `POST /api/member/sync/confirm` menerima batch klaim dan membatasi akses sesuai peran serta kepemilikan/penugasan pinjaman.
- Pembayaran dan alokasi dibuat atomik; retry klaim identik idempoten; perubahan nominal menghasilkan `SEQUENCE_CONFLICT`.
- `php artisan tokens:expire` serta scheduler hourly menandai token `ISSUED` yang melewati expiry + grace.
- Test backend token berada di `tests/Feature/PaymentTokenSyncTest.php`.

Verifikasi offline, penyimpanan sequence lokal, antrean transaksi, dan interface scanning collector/member belum diimplementasikan dalam repository ini.
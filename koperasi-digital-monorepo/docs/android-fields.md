# Field Integrasi Android Studio

Dokumen ini hanya berisi field yang dibutuhkan aplikasi Android eksternal. Source code Android tidak dibuat di monorepo ini.

## Auth

### `POST /api/auth/login`

| Field | Tipe | Wajib | Catatan |
|---|---|---:|---|
| `identifier` | string | Ya | Email atau nomor HP |
| `password` | string | Ya | Password pengguna |
| `platform` | enum | Ya | Kirim `ANDROID` |
| `deviceName` | string | Tidak | Nama device Android |
| `fcmToken` | string | Tidak | Untuk notifikasi |

## Registrasi Nasabah

### `POST /api/customers/register`

| Field | Tipe | Wajib | Catatan |
|---|---|---:|---|
| `name` | string | Ya | Nama lengkap nasabah |
| `phone` | string | Ya | Nomor HP unik |
| `password` | string | Ya | Password awal |
| `nationalId` | string | Tidak | NIK/KTP jika dipakai |
| `businessName` | string | Ya | Nama usaha |
| `businessType` | string | Tidak | Jenis usaha/pekerjaan |
| `businessAddress` | string | Ya | Alamat usaha |
| `businessLat` | number | Tidak | Latitude Google Maps |
| `businessLng` | number | Tidak | Longitude Google Maps |
| `collectorId` | string | Ya | Karyawan pengaju/penagih yang dipilih |

## Update Lokasi Usaha

### `PATCH /api/customers/me/business-location`

| Field | Tipe | Wajib | Catatan |
|---|---|---:|---|
| `businessAddress` | string | Ya | Alamat hasil survei |
| `businessLat` | number | Ya | Latitude Google Maps |
| `businessLng` | number | Ya | Longitude Google Maps |
| `surveyNote` | string | Tidak | Catatan survei |

## Scan QR Pembayaran

### `POST /api/payments/scan`

| Field | Tipe | Wajib | Catatan |
|---|---|---:|---|
| `token` | string | Ya | Token dari QR dinamis Karyawan/Admin |
| `method` | enum | Ya | `CASH` atau `TRANSFER` |
| `amount` | number | Ya | Nominal pembayaran |
| `transferReference` | string | Jika transfer | Nomor referensi/bukti transfer |
| `scannedAt` | string | Ya | ISO datetime dari Android |
| `locationLat` | number | Tidak | Latitude saat scan |
| `locationLng` | number | Tidak | Longitude saat scan |

## Statistik Streak Nasabah

### `GET /api/customers/me/streak`

Response minimal:

```json
{
  "customerId": "cus_123",
  "activeLoanId": "loan_123",
  "streakDays": 18,
  "arrearsDays": 0,
  "riskColor": "BLUE",
  "paidToday": true,
  "remainingAmount": 3200000
}
```

## Field Enum

### `platform`

- `ANDROID`

### `method`

- `CASH`
- `TRANSFER`

### `riskColor`

- `BLUE`: lancar tanpa tunggakan
- `YELLOW`: tunggakan 1-2 hari
- `RED`: tunggakan 3-9 hari
- `DARK`: tunggakan lebih dari 10 hari

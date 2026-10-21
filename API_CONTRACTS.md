# API Endpoint Contracts

> Base URL: `http://localhost:8000/api` (development)  
> Auth: Bearer token via `Authorization: Bearer <token>` header

---

## 🔐 Authentication Endpoints

### `POST /auth/login`
**Public endpoint** - No auth required

**Request:**
```json
{
  "email": "budi@koperasi.id",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@koperasi.id",
    "role": {
      "id": 2,
      "name": "collector",
      "description": "Karyawan/Penagih"
    },
    "permissions": [
      "create_loan",
      "process_payment",
      "view_customer"
    ],
    "employee_id": "EMP001",
    "platform": "WEB"
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 86400
}
```

**Error (401):**
```json
{
  "message": "Invalid credentials"
}
```

---

### `GET /auth/me`
**Protected** - `auth:sanctum` required

**Response (200):**
```json
{
  "id": 1,
  "name": "Budi Santoso",
  "email": "budi@koperasi.id",
  "role": {
    "id": 2,
    "name": "collector"
  },
  "permissions": ["create_loan", "process_payment"],
  "employee_id": "EMP001",
  "is_active": true
}
```

---

### `POST /auth/logout`
**Protected** - `auth:sanctum` required

**Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

## 👥 Customer Endpoints

### `POST /customers` (Collector creates customer)
**Protected** - `auth:sanctum`, `role:collector`

**Request:**
```json
{
  "name": "Bambang Wiranto",
  "phone": "08123456789",
  "business_name": "Toko Elektronik Maju",
  "business_location": {
    "latitude": -6.2088,
    "longitude": 106.8456,
    "address": "Jl. Merdeka No. 123, Jakarta"
  },
  "id_number": "3172056912880001",
  "id_type": "KTP",
  "monthly_income": 15000000
}
```

**Response (201):**
```json
{
  "id": 5,
  "user_id": null,
  "name": "Bambang Wiranto",
  "phone": "08123456789",
  "business_name": "Toko Elektronik Maju",
  "business_location": {...},
  "id_number": "3172056912880001",
  "status": "ACTIVE",
  "streak_count": 0,
  "created_at": "2026-05-22T10:30:00Z"
}
```

---

### `GET /collector/customers`
**Protected** - `auth:sanctum`, `role:collector`

**Query Params:**
- `status` (optional): ACTIVE | INACTIVE | SUSPENDED
- `page` (optional): default 1
- `per_page` (optional): default 15

**Response (200):**
```json
{
  "data": [
    {
      "id": 5,
      "name": "Bambang Wiranto",
      "phone": "08123456789",
      "business_name": "Toko Elektronik Maju",
      "status": "ACTIVE",
      "streak_count": 5,
      "active_loans": 1,
      "total_collected": 500000,
      "created_at": "2026-05-20T00:00:00Z"
    }
  ],
  "pagination": {
    "total": 25,
    "per_page": 15,
    "current_page": 1,
    "last_page": 2
  }
}
```

---

## 💰 Loan Endpoints

### `POST /loans`
**Protected** - `auth:sanctum`, `role:collector`

**Request:**
```json
{
  "customer_id": 5,
  "loan_amount": 5000000,
  "duration_days": 60,
  "interest_rate": 2.5
}
```

**Response (201):**
```json
{
  "id": 1,
  "customer_id": 5,
  "collector_id": 1,
  "loan_amount": 5000000,
  "total_with_interest": 5125000,
  "daily_payment": 85416.67,
  "duration_days": 60,
  "interest_rate": 2.5,
  "status": "PENDING_APPROVAL",
  "started_at": null,
  "created_at": "2026-05-22T10:35:00Z"
}
```

---

### `GET /loans/{id}`
**Protected** - `auth:sanctum`

**Response (200):**
```json
{
  "id": 1,
  "customer": {
    "id": 5,
    "name": "Bambang Wiranto",
    "streak_count": 5
  },
  "loan_amount": 5000000,
  "status": "ACTIVE",
  "started_at": "2026-05-22T00:00:00Z",
  "schedules": [
    {
      "day_number": 1,
      "due_date": "2026-05-23",
      "amount_due": 85416.67,
      "status": "PAID",
      "paid_at": "2026-05-23T14:30:00Z"
    },
    {
      "day_number": 2,
      "due_date": "2026-05-24",
      "amount_due": 85416.67,
      "status": "UNPAID",
      "paid_at": null
    }
  ],
  "total_paid": 85416.67,
  "total_outstanding": 5039583.33
}
```

---

## 💳 Payment Endpoints

### `POST /payments/qr`
**Protected** - `auth:sanctum`, `role:collector`

**Request:**
```json
{
  "loan_id": 1,
  "type": "ONE_DAY",
  "custom_amount": null,
  "expires_in_hours": 24
}
```

**Type options:**
- `ONE_DAY`: Today's schedule only
- `TWO_DAYS`: Last 2 days (today + yesterday)
- `ARREARS_ACCUMULATED`: All overdue schedules
- `CUSTOM`: Custom amount from `custom_amount` field

**Response (201):**
```json
{
  "id": 1,
  "loan_id": 1,
  "type": "ONE_DAY",
  "amount": 85416.67,
  "qr_code": "iVBORw0KGgoAAAANSUhEUgAAAHYAAAB2CAI...", // base64 PNG
  "qr_signature": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "status": "ACTIVE",
  "expires_at": "2026-05-23T14:30:00Z",
  "created_at": "2026-05-22T14:30:00Z"
}
```

---

### `POST /payments/scan` (Customer scans QR)
**Public/Mobile** - Can be authenticated or guest. Untuk akun customer yang terautentikasi, server memastikan QR terkait dengan pinjaman milik customer tersebut.

**Request:**
```json
{
  "qr_signature": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "payment_method": "TRANSFER" // or CASH, QR_CODE
}
```

Catatan keamanan dan streak:

- `customer_id` tidak diperlukan dari klien; customer diambil dari relasi pinjaman QR.
- Akun customer yang terautentikasi tidak dapat membayar QR milik anggota lain.
- Setelah jadwal terkait ditandai `PAID`, `streak_updated` dihitung ulang dari jadwal lunas berurutan pada pinjaman terakhir.
- Jadwal dengan tanggal hari ini belum memutus streak sebelum hari tersebut berakhir.

**Response (201):**
```json
{
  "payment_id": 1,
  "status": "COMPLETED",
  "amount": 85416.67,
  "reference_code": "PAY-20260522-001",
  "schedules_paid": [
    {"day_number": 1, "due_date": "2026-05-23"}
  ],
  "message": "Payment successful. Thank you!",
  "loan_status": "ACTIVE",
  "streak_updated": 5
}
```

**Error (400):**
```json
{
  "message": "QR code expired or invalid"
}
```

### Sinkronisasi Token Pembayaran Offline

Endpoint ini memakai Bearer Sanctum. Aplikasi klien harus memverifikasi signature Ed25519 secara lokal sebelum menampilkan token sebagai terverifikasi.

#### `POST /collector/sync/tokens`

**Role:** `collector`. Server hanya mengeluarkan token untuk pinjaman aktif yang ditugaskan kepada collector tersebut.

Request:

```json
{
  "device_id": "collector-device-001"
}
```

Response memuat `server_time`, `active_key_id`, `public_keys`, `sync_grace_hours`, dan paket pinjaman. Setiap paket berisi identitas anggota dan token dengan payload v1, detached signature Ed25519, `qr_data`, serta status token. Permintaan ulang pada hari yang sama mengembalikan paket yang sama.

#### `POST /collector/sync/confirm` dan `POST /member/sync/confirm`

**Role:** `collector` atau `customer` sesuai endpoint. Collector hanya dapat mengonfirmasi token yang diterbitkan untuk pinjamannya; anggota hanya dapat mengonfirmasi token untuk pinjaman miliknya.

Request berisi batch hingga 100 klaim:

```json
{
  "claims": [
    {
      "token": {
        "payload": {
          "version": 1,
          "key_id": "server-key-id",
          "token_uuid": "uuid",
          "loan_id": 123,
          "sequence": 7,
          "issued_by": 12,
          "max_amount_minor": 5000000,
          "issued_at": "2026-10-15T06:00:00Z",
          "expires_at": "2026-10-15T23:59:59Z"
        },
        "signature": "base64url-ed25519-signature"
      },
      "confirmed_amount": "50000.00",
      "payment_method": "CASH",
      "device_id": "member-device-001",
      "redeemed_offline_at": "2026-10-15T10:00:00+08:00"
    }
  ]
}
```

`redeemed_offline_at` hanya metadata perangkat. Response memberi hasil per klaim: `ACCEPTED`, `DUPLICATE`, `SEQUENCE_CONFLICT`, `EXPIRED`, `SIGNATURE_INVALID`, atau `REJECTED`. Pembayaran final hanya dibuat pada hasil `ACCEPTED`; retry identik mengembalikan `payment_id` yang sama. Token offline v1 menerima metode `CASH` saja.

### Portal Anggota Web

Portal anggota menggunakan session web, bukan token Sanctum. Route tersedia:

| Method | Route | Fungsi |
|---|---|---|
| `GET` | `/member` | Dashboard, streak, pembayaran terakhir, dan scanner QR |
| `GET` | `/member/profile` | Menampilkan profil anggota sendiri |
| `PUT` | `/member/profile` | Mengubah field profil yang diizinkan |
| `GET` | `/member/payments` | Histori pembayaran `COMPLETED` milik anggota |
| `POST` | `/member/scan/preview` | Memvalidasi QR dan menampilkan ringkasan sebelum pembayaran |
| `POST` | `/member/scan` | Mencatat pembayaran setelah konfirmasi |

Preview QR mengembalikan `customer_name`, `member_code`, `amount`, dan `expires_at`. Route portal mewajibkan role `customer` dan akun yang memiliki relasi ke record `customers`.

---

## 📋 Attendance Endpoints

### `POST /attendance/iot/logs` (ESP32 webhook)
**Public** - API Key required in header

**Headers:**
```
X-IOT-API-KEY: device-api-key-12345
Content-Type: application/json
```

**Request:**
```json
{
  "deviceCode": "ESP32-KANTOR-01",
  "fingerprintTemplateId": "FP-102",
  "employeeId": "EMP001",
  "occurredAt": "2026-05-22T07:30:00+08:00"
}
```

**Response (202):**
```json
{
  "accepted": true,
  "slot": "MORNING_IN",
  "calendarColor": "BLUE",
  "message": "Attendance recorded"
}
```

**Error (401):**
```json
{
  "accepted": false,
  "message": "Invalid API key"
}
```

---

### `GET /attendance/me`
**Protected** - `auth:sanctum`, `role:collector|admin|master_admin`

**Query Params:**
- `month` (optional): YYYY-MM format, default current month
- `year` (optional): YYYY format

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "Budi Santoso",
    "employee_id": "EMP001"
  },
  "period": "2026-05",
  "statistics": {
    "complete_days": 18,
    "partial_days": 2,
    "absent_days": 5,
    "total_working_days": 22
  },
  "records": [
    {
      "date": "2026-05-22",
      "morning_in": "07:30",
      "morning_out": "12:00",
      "afternoon_in": "13:00",
      "afternoon_out": "16:30",
      "status": "COMPLETE",
      "calendar_color": "BLUE"
    },
    {
      "date": "2026-05-21",
      "morning_in": "07:35",
      "morning_out": null,
      "afternoon_in": null,
      "afternoon_out": null,
      "status": "PARTIAL",
      "calendar_color": "YELLOW"
    }
  ]
}
```

---

## ✅ Approval Endpoints

### `GET /admin/approvals`
**Protected** - `auth:sanctum`, `role:admin|master_admin`

**Query Params:**
- `status` (optional): PENDING | APPROVED | REJECTED
- `page` (optional): default 1

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "loan_id": 1,
      "loan": {
        "id": 1,
        "customer_name": "Bambang Wiranto",
        "amount": 5000000
      },
      "requested_by": {
        "id": 1,
        "name": "Budi Santoso",
        "employee_id": "EMP001"
      },
      "status": "PENDING",
      "created_at": "2026-05-22T10:35:00Z"
    }
  ],
  "pagination": {...}
}
```

---

### `POST /admin/approvals/{id}/approve`
**Protected** - `auth:sanctum`, `role:admin|master_admin`

**Request:**
```json
{
  "notes": "Approved - customer has good track record"
}
```

**Response (200):**
```json
{
  "id": 1,
  "status": "APPROVED",
  "approval_date": "2026-05-22T15:00:00Z",
  "message": "Loan approved successfully"
}
```

---

### `POST /admin/approvals/{id}/reject`
**Protected** - `auth:sanctum`, `role:admin|master_admin`

**Request:**
```json
{
  "rejected_reason": "Insufficient monthly income"
}
```

**Response (200):**
```json
{
  "id": 1,
  "status": "REJECTED",
  "rejected_reason": "Insufficient monthly income",
  "message": "Loan rejected"
}
```

---

## 📊 Admin Reports Endpoints

### `GET /admin/arrears/by-collector`
**Protected** - `auth:sanctum`, `role:admin|master_admin`

**Response (200):**
```json
{
  "data": [
    {
      "collector_id": 1,
      "collector_name": "Budi Santoso",
      "total_customers": 15,
      "active_loans": 12,
      "total_arrears": 2500000,
      "arrears_count": 3,
      "collection_rate": 85.5,
      "top_risk": [
        {
          "customer_name": "Bambang Wiranto",
          "overdue_days": 7,
          "arrears_amount": 1200000,
          "status": "RED"
        }
      ]
    }
  ]
}
```

---

### `GET /admin/attendance`
**Protected** - `auth:sanctum`, `role:admin|master_admin`

**Query Params:**
- `date` (optional): YYYY-MM-DD, default today
- `status` (optional): COMPLETE | PARTIAL | ABSENT

**Response (200):**
```json
{
  "date": "2026-05-22",
  "total_employees": 25,
  "attendance_summary": {
    "complete": 20,
    "partial": 3,
    "absent": 2
  },
  "records": [
    {
      "employee_id": "EMP001",
      "name": "Budi Santoso",
      "status": "COMPLETE",
      "morning_in": "07:30",
      "afternoon_out": "16:30"
    }
  ]
}
```

---

## 👨‍💼 Master Admin Endpoints

### `POST /master/admins`
**Protected** - `auth:sanctum`, `role:master_admin`

**Request:**
```json
{
  "name": "Admin Baru",
  "email": "admin.baru@koperasi.id",
  "password": "securePassword123",
  "employee_id": "ADM001"
}
```

**Response (201):**
```json
{
  "id": 3,
  "name": "Admin Baru",
  "email": "admin.baru@koperasi.id",
  "role": "admin",
  "employee_id": "ADM001",
  "created_at": "2026-05-22T15:30:00Z"
}
```

---

## ❌ Error Responses

### Standard Error Format

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated"
}
```

**403 Forbidden (Role):**
```json
{
  "message": "This action is unauthorized for your role"
}
```

**422 Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken"],
    "loan_amount": ["The loan amount must be at least 1000000"]
  }
}
```

**404 Not Found:**
```json
{
  "message": "Resource not found"
}
```

**500 Server Error:**
```json
{
  "message": "Internal server error"
}
```

---

## 🔑 Summary of Endpoints by Role

| Role | Endpoints |
|------|-----------|
| **Public** | POST /auth/login, POST /customers/register, POST /payments/scan, POST /attendance/iot/logs |
| **Customer** | GET /auth/me, POST /auth/logout, GET /loans (own only), GET /payments (own only) |
| **Collector** | + POST /loans, GET /collector/customers, POST /payments/qr, GET /collector/daily-monitoring |
| **Admin** | + GET /admin/approvals, POST /admin/approvals/{id}/approve, GET /admin/arrears, GET /admin/attendance, POST /admin/employees |
| **Master Admin** | + POST /master/admins, POST /master/employees, GET /audit/logs |

---

## 📝 Testing with cURL

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"budi@koperasi.id","password":"password123"}'

# Get authenticated user
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Create loan
curl -X POST http://localhost:8000/api/loans \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"customer_id":5,"loan_amount":5000000,"duration_days":60,"interest_rate":2.5}'
```

---

## 📋 Postman Collection Format

All endpoints are importable into Postman. Each endpoint includes:
- Request/Response examples
- Authorization header template
- Query parameters documentation
- Form validation rules

**To generate**: Use OpenAPI spec from `routes/api.php`

# Kontrak API Awal

Base URL lokal: `http://localhost:4000/api`

Repo ini fokus pada desktop. Endpoint Android tetap didokumentasikan sebagai kontrak field untuk aplikasi Android Studio eksternal, bukan sebagai source mobile di monorepo.

## Auth

- `POST /auth/login`
- `POST /auth/refresh`
- `POST /auth/logout`
- `GET /auth/me`

Token JWT membawa `userId`, `role`, dan `platform`. Backend menolak role Nasabah saat `platform=WEB` atau `platform=DESKTOP`.

## Android Eksternal Nasabah

- `POST /customers/register`
- `GET /customers/me`
- `PATCH /customers/me/business-location`
- `GET /customers/me/streak`
- `POST /payments/scan`

## Karyawan/Penagih

- `POST /customers`
- `GET /collector/customers`
- `PATCH /collector/customers/:id`
- `POST /loan-applications`
- `GET /collector/daily-monitoring`
- `GET /collector/leaderboard`
- `POST /payments/qr`
- `GET /attendance/me`

## Admin

- `GET /admin/approvals`
- `POST /admin/approvals/:id/approve`
- `POST /admin/approvals/:id/reject`
- `GET /admin/arrears/by-collector`
- `GET /admin/arrears?risk=RED,DARK`
- `GET /admin/attendance`
- `GET /admin/reports/monthly`
- `POST /admin/employees`
- `PATCH /admin/employees/:id`

## Master Admin

- `POST /master/admins`
- `PATCH /master/admins/:id`
- `DELETE /master/admins/:id`
- `POST /master/employees`
- `PATCH /master/employees/:id`
- `DELETE /master/employees/:id`

## IoT Attendance

### `POST /attendance/iot/logs`

Header:

```http
X-IOT-API-KEY: <device-api-key>
```

Body:

```json
{
  "deviceCode": "ESP32-KANTOR-01",
  "fingerprintTemplateId": "FP-102",
  "employeeId": "emp_123",
  "occurredAt": "2026-05-22T07:30:00+08:00"
}
```

Response:

```json
{
  "accepted": true,
  "slot": "MORNING_IN",
  "calendarColor": "BLUE"
}
```

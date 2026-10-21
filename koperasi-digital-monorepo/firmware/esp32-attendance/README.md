# ESP32 Attendance Firmware

Firmware ini adalah skeleton client IoT untuk perangkat fingerprint ESP32.

## Payload ke Backend

Endpoint:

```http
POST /api/attendance/iot/logs
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

## Slot Absensi

- Masuk Pagi: 07.00 - 10.59
- Keluar Siang: 11.00 - 15.59
- Masuk Sore: 16.00 - 17.29
- Keluar Malam: 17.30 - 23.59

Perangkat cukup mengirim log mentah. Backend yang menentukan slot final dan ringkasan kalender.

# Database Schema - SQL Reference

> This file is a reference for the migration structure. Each table below will have its own migration file.

---

## Core Tables

### `roles`
```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name)
);

-- Seed data:
-- (1, 'customer', 'Customer/Nasabah)
-- (2, 'collector', 'Karyawan/Penagih)
-- (3, 'admin', 'Administrator)
-- (4, 'master_admin', 'Master Administrator)
```

### `permissions`
```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name)
);

-- Seed data examples:
-- create_loan, approve_loan, reject_loan
-- create_payment_qr, process_payment
-- create_customer, view_customer, edit_customer
-- view_attendance, manage_attendance
-- manage_employees, manage_admins
-- view_reports, export_reports
```

### `role_permission` (Pivot)
```sql
CREATE TABLE role_permission (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    INDEX idx_role_id (role_id),
    INDEX idx_permission_id (permission_id)
);
```

### `users` (ALTER EXISTING)
```sql
ALTER TABLE users ADD COLUMN (
    role_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER password,
    platform VARCHAR(50) DEFAULT 'WEB' AFTER role_id,
    employee_id VARCHAR(50) UNIQUE NULLABLE AFTER platform,
    is_active BOOLEAN DEFAULT TRUE AFTER employee_id,
    
    FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_role_id (role_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_platform (platform)
);

ALTER TABLE users ADD UNIQUE INDEX idx_email (email);
```

---

## Customer & Loan Domain

### `customers`
```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    business_name VARCHAR(255),
    business_location JSON, -- {"latitude": -6.2088, "longitude": 106.8456, "address": "..."}
    id_number VARCHAR(50) UNIQUE,
    id_type ENUM('KTP', 'SIM', 'PASSPORT') DEFAULT 'KTP',
    monthly_income DECIMAL(12, 2),
    status ENUM('ACTIVE', 'INACTIVE', 'SUSPENDED') DEFAULT 'ACTIVE',
    streak_count INT DEFAULT 0, -- rangkaian jadwal pembayaran PAID pada pinjaman terakhir
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_id_number (id_number)
);
```

`streak_count` dihitung ulang dari jadwal pinjaman terakhir yang sudah jatuh tempo. Nilainya bertambah untuk jadwal berurutan dengan status `PAID` dan berhenti pada jadwal tertunggak; jadwal yang jatuh tempo hari ini belum memutus streak sebelum hari tersebut berakhir. Field ini ditampilkan pada portal anggota dan diperbarui setelah pembayaran berhasil.

The Laravel implementation also stores the cooperative member registry fields imported from the member data file:

```sql
member_code                VARCHAR(20) UNIQUE,
gender                     CHAR(1), -- L or P
occupation                 VARCHAR(150),
address                    TEXT,
resignation_requested_at   DATE NULL,
resigned_at                DATE NULL,
exit_reason                TEXT NULL
```

`status` remains the operational member status used by the dashboard. Imported records with `resigned_at` are marked `INACTIVE`; records without an exit date are marked `ACTIVE`.

### `loans`
```sql
CREATE TABLE loans (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    collector_id BIGINT UNSIGNED NOT NULL, -- Foreign key to users
    loan_amount DECIMAL(12, 2) NOT NULL,
    duration_days INT DEFAULT 60,
    interest_rate DECIMAL(5, 2), -- Persentase bunga (e.g., 2.5)
    status ENUM('PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'ACTIVE', 'COMPLETED', 'DEFAULTED') DEFAULT 'PENDING_APPROVAL',
    approved_by BIGINT UNSIGNED NULLABLE,
    approval_date TIMESTAMP NULLABLE,
    started_at TIMESTAMP NULLABLE,
    completed_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (collector_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_collector_id (collector_id),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
);
```

### `loan_schedules`
```sql
CREATE TABLE loan_schedules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    loan_id BIGINT UNSIGNED NOT NULL,
    day_number INT NOT NULL, -- 1-60
    due_date DATE NOT NULL,
    amount_due DECIMAL(12, 2) NOT NULL,
    status ENUM('UNPAID', 'PAID', 'OVERDUE') DEFAULT 'UNPAID',
    paid_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    UNIQUE KEY unique_loan_day (loan_id, day_number),
    INDEX idx_loan_id (loan_id),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
);
```

---

## Payment & QR Domain

### `payments`
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    loan_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    payment_method ENUM('QR_CODE', 'TRANSFER', 'CASH') DEFAULT 'QR_CODE',
    payment_qr_id BIGINT UNSIGNED NULLABLE,
    payment_token_id BIGINT UNSIGNED NULLABLE UNIQUE,
    status ENUM('PENDING', 'COMPLETED', 'FAILED') DEFAULT 'PENDING',
    reference_code VARCHAR(100) UNIQUE NOT NULL,
    paid_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_qr_id) REFERENCES payment_qrs(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_token_id) REFERENCES payment_tokens(id) ON DELETE SET NULL,
    INDEX idx_loan_id (loan_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_reference_code (reference_code),
    INDEX idx_paid_at (paid_at)
);
```

### `payment_qrs`
```sql
CREATE TABLE payment_qrs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    loan_id BIGINT UNSIGNED NOT NULL,
    collector_id BIGINT UNSIGNED NOT NULL,
    type ENUM('ONE_DAY', 'TWO_DAYS', 'ARREARS_ACCUMULATED', 'CUSTOM') NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    qr_signature VARCHAR(500), -- JWT encoded: {loan_id, type, scheduleIds, expiresAt}
    status ENUM('ACTIVE', 'EXPIRED', 'COMPLETED') DEFAULT 'ACTIVE',
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (collector_id) REFERENCES users(id),
    INDEX idx_loan_id (loan_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
);
```

### `payment_qr_schedules` (Pivot)
```sql
CREATE TABLE payment_qr_schedules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payment_qr_id BIGINT UNSIGNED NOT NULL,
    loan_schedule_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_qr_schedule (payment_qr_id, loan_schedule_id),
    FOREIGN KEY (payment_qr_id) REFERENCES payment_qrs(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_schedule_id) REFERENCES loan_schedules(id) ON DELETE CASCADE
);
```

### Offline Payment Token Domain

QR online lama (`payment_qrs`) tetap berjalan selama masa transisi. Token offline tidak mengganti tabel lama secara destruktif; transaksi baru dapat mengacu ke salah satu sumber melalui `payment_qr_id` atau `payment_token_id`.

#### `loan_token_counters`

Satu counter per pinjaman. Server mengalokasikan sequence secara monoton dan mengunci baris ini saat menerbitkan atau mengonfirmasi token. Aturan `last_confirmed_sequence <= last_issued_sequence` ditegakkan di service layer.

```sql
CREATE TABLE loan_token_counters (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  loan_id BIGINT UNSIGNED NOT NULL UNIQUE,
  last_issued_sequence INT UNSIGNED NOT NULL DEFAULT 0,
  last_confirmed_sequence INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
);
```

#### `payment_tokens`

Payload token v1 ditandatangani Ed25519 oleh server. Nilai plafon memakai DECIMAL di database dan satuan minor integer di payload QR.

```sql
CREATE TABLE payment_tokens (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  token_uuid CHAR(36) UNIQUE NOT NULL,
  loan_id BIGINT UNSIGNED NOT NULL,
  sequence INT UNSIGNED NOT NULL,
  issued_by BIGINT UNSIGNED NOT NULL,
  max_amount DECIMAL(12,2) NOT NULL,
  issued_at TIMESTAMP NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  signature TEXT NOT NULL,
  key_id VARCHAR(80) NOT NULL DEFAULT 'server-2026-01',
  payload_version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('ISSUED','REDEEMED_OFFLINE','CONFIRMED','EXPIRED','VOID') NOT NULL DEFAULT 'ISSUED',
  redeemed_offline_at TIMESTAMP NULL,
  confirmed_at TIMESTAMP NULL,
  confirmed_amount DECIMAL(12,2) NULL,
  device_id VARCHAR(100) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uniq_loan_sequence (loan_id, sequence),
  INDEX idx_status (status),
  INDEX idx_expires_at (expires_at),
  FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
  FOREIGN KEY (issued_by) REFERENCES users(id)
);
```

`redeemed_offline_at` adalah waktu perangkat dan hanya metadata. `confirmed_at` adalah waktu server yang dipakai sebagai waktu final. Public key disimpan di keyring environment berdasarkan `key_id`; private key tidak berada di database.

#### `payment_allocations`

Mencatat porsi pembayaran yang dialokasikan ke jadwal tertentu. Satu pembayaran dapat melunasi beberapa jadwal atau membayar sebagian dari satu jadwal.

```sql
CREATE TABLE payment_allocations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  payment_id BIGINT UNSIGNED NOT NULL,
  loan_schedule_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY unique_payment_schedule (payment_id, loan_schedule_id),
  INDEX idx_loan_schedule_id (loan_schedule_id),
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
  FOREIGN KEY (loan_schedule_id) REFERENCES loan_schedules(id) ON DELETE CASCADE
);
```

#### `token_sync_log`

Log append-only dan sengaja tidak memiliki foreign key ke `payment_tokens`, sehingga signature atau UUID yang tidak valid tetap tercatat.

```sql
CREATE TABLE token_sync_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  token_uuid CHAR(36) NOT NULL,
  submitted_by_device VARCHAR(100) NULL,
  submitted_at TIMESTAMP NOT NULL,
  result ENUM('ACCEPTED','DUPLICATE','SEQUENCE_CONFLICT','EXPIRED','SIGNATURE_INVALID','REJECTED') NOT NULL,
  meta JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token_uuid (token_uuid),
  INDEX idx_result (result),
  INDEX idx_submitted_at (submitted_at)
);
```

---

## Attendance Domain

### `attendance_logs` (Raw IoT Events)
```sql
CREATE TABLE attendance_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    device_code VARCHAR(50) NOT NULL, -- 'ESP32-KANTOR-01'
    fingerprint_template_id VARCHAR(50) NOT NULL,
    employee_id VARCHAR(50), -- Custom ID dari device
    user_id BIGINT UNSIGNED NULLABLE, -- Matched user
    occurred_at TIMESTAMP NOT NULL,
    accepted BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_device_code (device_code),
    INDEX idx_occurred_at (occurred_at),
    INDEX idx_created_at (created_at)
);
```

### `attendance_records` (Consolidated Daily)
```sql
CREATE TABLE attendance_records (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    morning_in TIMESTAMP NULLABLE,
    morning_out TIMESTAMP NULLABLE,
    afternoon_in TIMESTAMP NULLABLE,
    afternoon_out TIMESTAMP NULLABLE,
    status ENUM('COMPLETE', 'PARTIAL', 'ABSENT') DEFAULT 'ABSENT',
    calendar_color ENUM('BLUE', 'YELLOW', 'RED', 'ALPHA') DEFAULT 'RED', 
    -- BLUE: all shifts, YELLOW: partial, RED: absent, ALPHA: leave
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, attendance_date),
    INDEX idx_user_id (user_id),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_status (status)
);
```

---

## Approval & Audit Domain

### `approvals`
```sql
CREATE TABLE approvals (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    loan_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL, -- collector_id
    approved_by BIGINT UNSIGNED NULLABLE, -- admin_id
    status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    notes TEXT,
    rejected_reason TEXT NULLABLE,
    approval_date TIMESTAMP NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_loan_approval (loan_id),
    INDEX idx_status (status),
    INDEX idx_approval_date (approval_date)
);
```

---

## Index Strategy

### Query Patterns & Indexes:
```sql
-- Find user by token → users.id ✅ (primary key)
-- Find user's customers → customers(user_id) ✅
-- Find active loans → loans(status, started_at) ✅
-- Find overdue schedules → loan_schedules(due_date, status) ✅
-- Find payments by customer → payments(customer_id, paid_at) ✅
-- Find today's attendance → attendance_records(attendance_date, user_id) ✅
-- Find ESP32 events → attendance_logs(occurred_at, device_code) ✅
-- Find pending approvals → approvals(status, created_at) ✅
```

---

## Constraints & Validation

```sql
-- Loan amount must be positive
ALTER TABLE loans ADD CONSTRAINT chk_loan_amount CHECK (loan_amount > 0);

-- Payment amount must be positive
ALTER TABLE payments ADD CONSTRAINT chk_payment_amount CHECK (amount > 0);

-- Due date must be after loan start date
-- Handled in application logic

-- Only one active loan per customer
-- Handled in application logic (or trigger)

-- Payment QR expires_at must be in future
-- Validated in application

-- Attendance date cannot be in future
-- Validated in application
```

---

## Sample Data Structure

### Login Response
```json
{
  "user": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@koperasi.id",
    "role": {
      "id": 2,
      "name": "collector"
    },
    "permissions": [
      "create_loan",
      "process_payment",
      "view_customer"
    ],
    "employee_id": "EMP001"
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 86400
}
```

### Loan with Schedules
```json
{
  "id": 1,
  "customer_id": 5,
  "loan_amount": 5000000,
  "duration_days": 60,
  "interest_rate": 2.5,
  "status": "ACTIVE",
  "started_at": "2026-05-22T00:00:00Z",
  "schedules": [
    {
      "day_number": 1,
      "due_date": "2026-05-23",
      "amount_due": 83333.33,
      "status": "PAID",
      "paid_at": "2026-05-23T14:30:00Z"
    },
    ...
  ]
}
```

---

## Notes

- SQLite doesn't support all enum types; use CHECK constraints instead
- PostgreSQL supports native ENUM
- Timestamps use UTC; convert to Asia/Jakarta in application
- Foreign key cascade delete configured appropriately
- Unique constraints prevent duplicate entries where needed

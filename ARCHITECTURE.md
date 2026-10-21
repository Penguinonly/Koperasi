# Koperasi Digital - Backend Architecture & Setup Guide

> **Status dokumen:** ini blueprint arsitektur awal. Beberapa bagian menjelaskan struktur atau pekerjaan yang direncanakan, bukan implementasi Laravel saat ini. Untuk panduan aktual, lihat [PANDUAN_PROGRAM.md](PANDUAN_PROGRAM.md); route aktif ada di `routes/`, dan skema database aktif ada di `database/migrations/`.

**Project Type**: Laravel 12 API Backend for Cooperative Loan & Attendance System  
**Database**: SQLite (dev), PostgreSQL (production)  
**Auth**: Laravel Sanctum (API tokens with role-based access)  
**Date**: 2026-07-17

---

## 📐 Database Schema

### **1. Core Authentication & Authorization**

#### `users` (already exists, needs enhancement)
```sql
id              INTEGER PRIMARY KEY
name            VARCHAR(255)
email           VARCHAR(255) UNIQUE
password        VARCHAR(255)
role_id         FOREIGN KEY (roles.id)
platform        ENUM('WEB', 'MOBILE', 'DESKTOP', 'IOT') -- JWT platform claim
employee_id     VARCHAR(50) NULLABLE -- for staff/collector
is_active       BOOLEAN DEFAULT true
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `roles` (NEW)
```sql
id              INTEGER PRIMARY KEY
name            VARCHAR(50) UNIQUE -- 'admin', 'collector', 'customer', 'master_admin'
description     TEXT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `permissions` (NEW)
```sql
id              INTEGER PRIMARY KEY
name            VARCHAR(100) UNIQUE -- 'create_loan', 'approve_loan', 'process_payment'
description     TEXT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `role_permission` (NEW - Pivot table)
```sql
id              INTEGER PRIMARY KEY
role_id         FOREIGN KEY (roles.id)
permission_id   FOREIGN KEY (permissions.id)
created_at      TIMESTAMP
```

---

### **2. Loan & Payment Domain**

#### `customers` (NEW - for nasabah)
```sql
id              INTEGER PRIMARY KEY
user_id         FOREIGN KEY (users.id)
name            VARCHAR(255)
phone           VARCHAR(20)
business_name   VARCHAR(255)
business_location JSON -- {latitude, longitude, address}
id_number       VARCHAR(50) UNIQUE
id_type         ENUM('KTP', 'SIM', 'PASSPORT')
monthly_income  DECIMAL(12,2)
status          ENUM('ACTIVE', 'INACTIVE', 'SUSPENDED')
streak_count    INTEGER DEFAULT 0
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `loans` (NEW)
```sql
id              INTEGER PRIMARY KEY
customer_id     FOREIGN KEY (customers.id)
collector_id    FOREIGN KEY (users.id) -- karyawan/penagih yang buat
loan_amount     DECIMAL(12,2)
duration_days   INTEGER DEFAULT 60
interest_rate   DECIMAL(5,2) -- persentase
status          ENUM('PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'ACTIVE', 'COMPLETED', 'DEFAULTED')
approved_by     FOREIGN KEY (users.id) NULLABLE
approval_date   TIMESTAMP NULLABLE
started_at      TIMESTAMP NULLABLE
completed_at    TIMESTAMP NULLABLE
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `loan_schedules` (NEW - 60 daily schedules)
```sql
id              INTEGER PRIMARY KEY
loan_id         FOREIGN KEY (loans.id)
day_number      INTEGER -- 1-60
due_date        DATE
amount_due      DECIMAL(12,2)
status          ENUM('UNPAID', 'PAID', 'OVERDUE')
paid_at         TIMESTAMP NULLABLE
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `payments` (NEW)
```sql
id              INTEGER PRIMARY KEY
loan_id         FOREIGN KEY (loans.id)
customer_id     FOREIGN KEY (customers.id)
amount          DECIMAL(12,2)
payment_method  ENUM('QR_CODE', 'TRANSFER', 'CASH')
payment_qr_id   FOREIGN KEY (payment_qrs.id) NULLABLE
status          ENUM('PENDING', 'COMPLETED', 'FAILED')
reference_code  VARCHAR(100) UNIQUE
paid_at         TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `payment_qrs` (NEW - Dynamic QR codes)
```sql
id              INTEGER PRIMARY KEY
loan_id         FOREIGN KEY (loans.id)
collector_id    FOREIGN KEY (users.id)
type            ENUM('ONE_DAY', 'TWO_DAYS', 'ARREARS_ACCUMULATED', 'CUSTOM')
amount          DECIMAL(12,2)
qr_signature    VARCHAR(500) -- JWT encoded QR data
status          ENUM('ACTIVE', 'EXPIRED', 'COMPLETED')
expires_at      TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `payment_qr_schedules` (NEW - Pivot table)
```sql
id              INTEGER PRIMARY KEY
payment_qr_id   FOREIGN KEY (payment_qrs.id)
loan_schedule_id FOREIGN KEY (loan_schedules.id)
created_at      TIMESTAMP
```

---

### **3. Attendance & IoT**

#### `attendance_logs` (NEW - Raw IoT events)
```sql
id              INTEGER PRIMARY KEY
device_code     VARCHAR(50) -- 'ESP32-KANTOR-01'
fingerprint_template_id VARCHAR(50)
employee_id     VARCHAR(50)
user_id         FOREIGN KEY (users.id) NULLABLE
occurred_at     TIMESTAMP
accepted        BOOLEAN DEFAULT true
created_at      TIMESTAMP
```

#### `attendance_records` (NEW - Consolidated daily records)
```sql
id              INTEGER PRIMARY KEY
user_id         FOREIGN KEY (users.id)
attendance_date DATE
morning_in      TIMESTAMP NULLABLE
morning_out     TIMESTAMP NULLABLE
afternoon_in    TIMESTAMP NULLABLE
afternoon_out   TIMESTAMP NULLABLE
status          ENUM('COMPLETE', 'PARTIAL', 'ABSENT')
calendar_color  ENUM('BLUE', 'YELLOW', 'RED', 'ALPHA')
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

---

### **4. Approval & Admin**

#### `approvals` (NEW)
```sql
id              INTEGER PRIMARY KEY
loan_id         FOREIGN KEY (loans.id)
requested_by    FOREIGN KEY (users.id)
approved_by     FOREIGN KEY (users.id) NULLABLE
status          ENUM('PENDING', 'APPROVED', 'REJECTED')
notes           TEXT
rejected_reason TEXT NULLABLE
approval_date   TIMESTAMP NULLABLE
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

---

## 🔐 Authentication Flow

### **JWT Token Structure (Sanctum)**
```json
{
  "sub": "user_id",
  "email": "user@example.com",
  "role": "collector",
  "platform": "DESKTOP",
  "employee_id": "emp_123",
  "iat": 1234567890,
  "exp": 1234571490
}
```

### **Login Response**
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
    "permissions": ["create_loan", "process_payment"]
  },
  "token": "abc123...|token_hash",
  "expires_in": 3600
}
```

### **Role Hierarchy**
```
Master Admin
  ├── Approve/reject admins
  ├── Manage all employees
  └── View all audit logs

Admin
  ├── Approve loan applications
  ├── Manage collectors/employees
  ├── Monitor arrears
  └── View attendance of all staff

Collector/Employee
  ├── Create customer applications
  ├── Generate QR for payments
  ├── Monitor daily collections
  └── View own attendance

Customer (Mobile only)
  ├── View loan status
  ├── Scan QR for payment
  └── View payment history

IoT Device
  ├── POST /attendance/iot/logs only
  └── No other access
```

---

## 📁 File Structure to Create

### **Models** (`app/Models/`)
```
User.php                    ✅ EXISTS (needs update)
Role.php                    🆕
Permission.php              🆕
Customer.php                🆕
Loan.php                    🆕
LoanSchedule.php            🆕
Payment.php                 🆕
PaymentQr.php               🆕
AttendanceLog.php           🆕
AttendanceRecord.php        🆕
Approval.php                🆕
```

### **Controllers** (`app/Http/Controllers/`)
```
Controller.php              ✅ EXISTS
AuthController.php          🆕
LoanController.php          🆕
PaymentController.php       🆕
AttendanceController.php    🆕
CollectorController.php     🆕
AdminController.php         🆕
MasterAdminController.php   🆕
```

### **Requests** (`app/Http/Requests/`)
```
LoginRequest.php            🆕
CreateLoanRequest.php       🆕
CreatePaymentQrRequest.php  🆕
ApproveApprovalRequest.php  🆕
```

### **Resources** (`app/Http/Resources/`)
```
UserResource.php            🆕
LoanResource.php            🆕
PaymentResource.php         🆕
AttendanceResource.php      🆕
```

### **Migrations** (`database/migrations/`)
```
0001_01_01_000000_create_users_table.php                      ✅ EXISTS (needs alter)
0001_01_01_000001_create_cache_table.php                      ✅ EXISTS
0001_01_01_000002_create_jobs_table.php                       ✅ EXISTS
XXXX_XX_XX_XXXXXX_create_roles_table.php                      🆕
XXXX_XX_XX_XXXXXX_create_permissions_table.php                🆕
XXXX_XX_XX_XXXXXX_create_role_permission_table.php            🆕
XXXX_XX_XX_XXXXXX_create_customers_table.php                  🆕
XXXX_XX_XX_XXXXXX_create_loans_table.php                      🆕
XXXX_XX_XX_XXXXXX_create_loan_schedules_table.php             🆕
XXXX_XX_XX_XXXXXX_create_payments_table.php                   🆕
XXXX_XX_XX_XXXXXX_create_payment_qrs_table.php                🆕
XXXX_XX_XX_XXXXXX_create_payment_qr_schedules_table.php       🆕
XXXX_XX_XX_XXXXXX_create_attendance_logs_table.php            🆕
XXXX_XX_XX_XXXXXX_create_attendance_records_table.php         🆕
XXXX_XX_XX_XXXXXX_create_approvals_table.php                  🆕
```

### **Seeders** (`database/seeders/`)
```
DatabaseSeeder.php          ✅ EXISTS (needs update)
RoleSeeder.php              🆕
PermissionSeeder.php        🆕
UserSeeder.php              🆕
CustomerSeeder.php          🆕
```

---

## 🛣️ Routes Architecture

### **Public Routes** (`routes/api.php`)
```
POST   /auth/login                          → AuthController@login
POST   /auth/register                       → AuthController@register (customers only)
POST   /customers/register                  → CustomerController@register (mobile)
POST   /payments/scan                       → PaymentController@scan (mobile)
POST   /attendance/iot/logs                 → AttendanceController@logIot (with API key)
```

### **Protected Routes (Authenticated)**
```
GET    /auth/me                             → AuthController@me
POST   /auth/logout                         → AuthController@logout
POST   /auth/refresh                        → AuthController@refresh

--- COLLECTOR/EMPLOYEE ---
POST   /loans                               → LoanController@store
GET    /loans/{id}                          → LoanController@show
GET    /collector/customers                 → CollectorController@customers
PATCH  /collector/customers/{id}            → CollectorController@updateCustomer
GET    /collector/daily-monitoring          → CollectorController@dailyMonitoring
GET    /collector/leaderboard               → CollectorController@leaderboard
POST   /payments/qr                         → PaymentController@createQr
GET    /attendance/me                       → AttendanceController@me

--- ADMIN ---
GET    /admin/approvals                     → AdminController@approvals
POST   /admin/approvals/{id}/approve        → AdminController@approveApplication
POST   /admin/approvals/{id}/reject         → AdminController@rejectApplication
GET    /admin/arrears/by-collector          → AdminController@arrearsByCollector
GET    /admin/arrears                       → AdminController@arrears (filter by risk)
GET    /admin/attendance                    → AdminController@attendance
GET    /admin/reports/monthly               → AdminController@monthlyReport
POST   /admin/employees                     → AdminController@createEmployee
PATCH  /admin/employees/{id}                → AdminController@updateEmployee

--- MASTER ADMIN ---
POST   /master/admins                       → MasterAdminController@createAdmin
PATCH  /master/admins/{id}                  → MasterAdminController@updateAdmin
DELETE /master/admins/{id}                  → MasterAdminController@deleteAdmin
POST   /master/employees                    → MasterAdminController@createEmployee
PATCH  /master/employees/{id}               → MasterAdminController@updateEmployee
DELETE /master/employees/{id}               → MasterAdminController@deleteEmployee
```

---

## 🔧 Configuration Updates Needed

### **1. `.env` Changes**
```bash
# Add/update these lines
API_SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1:8000
SANCTUM_ENCRYPT_COOKIES=true

# For JWT claims
APP_TIMEZONE=Asia/Jakarta
```

### **2. `config/app.php` - Providers to register**
```php
'providers' => [
    // ...
    Laravel\Sanctum\SanctumServiceProvider::class,
]
```

### **3. `config/sanctum.php` - Setup if not exists**
```php
'stateful' => explode(',', env('API_SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000')),
'expiration' => 60 * 24, // 1 day
'middleware' => [
    'verify_csrf_token' => \App\Http\Middleware\VerifyCsrfToken::class,
    'encrypt_cookies' => \App\Http\Middleware\EncryptCookies::class,
],
```

---

## 📋 Implementation Sequence

### **Phase 1: Foundation (Day 1-2)**
1. ✅ Install Sanctum
2. ✅ Update User model with role_id, platform, employee_id
3. ✅ Create Role & Permission models
4. ✅ Create migrations for roles, permissions, role_permission pivot
5. ✅ Create seeders for roles & permissions
6. ✅ Run migrations
7. ✅ Create AuthController with login/logout/me

### **Phase 2: Core Entities (Day 2-3)**
1. ✅ Create Customer, Loan, LoanSchedule models & migrations
2. ✅ Create Payment, PaymentQr models & migrations
3. ✅ Create Attendance models & migrations
4. ✅ Create LoanController & PaymentController

### **Phase 3: Authorization (Day 3-4)**
1. ✅ Create middleware for role checking (CheckRole)
2. ✅ Create middleware for permission checking (CheckPermission)
3. ✅ Add route middleware groups (api.collector, api.admin, api.master_admin)

### **Phase 4: Business Logic (Day 4-5)**
1. ✅ Create services for Loan calculation
2. ✅ Create services for Payment processing
3. ✅ Create services for Attendance consolidation
4. ✅ Create CollectorController, AdminController, MasterAdminController

### **Phase 5: API Documentation (Day 5)**
1. ✅ Create OpenAPI/Swagger documentation
2. ✅ Test all endpoints with Postman

---

## 💾 Key Relationships (Models)

```
User (1) ──→ (1) Role
Role (1) ──→ (n) Permissions
Permission (n) ←─ (n) Roles [pivot: role_permission]

User (1) ──→ (n) Customers [if user is collector]
Customer (1) ──→ (n) Loans
Customer (1) ──→ (n) Payments
Loan (1) ──→ (n) LoanSchedules
Loan (1) ──→ (n) Payments
Loan (1) ──→ (1) Approval

User (1) ──→ (n) AttendanceRecords
AttendanceRecord (n) ←─ (n) AttendanceLogs [raw events]
```

---

## 🧪 Testing Checklist

```
[ ] POST /auth/login → returns token with correct JWT claims
[ ] GET /auth/me → returns authenticated user with role & permissions
[ ] POST /auth/logout → invalidates token
[ ] POST /loans → collector only, creates with 60 schedules
[ ] GET /collector/customers → returns customer list filtered by collector
[ ] POST /payments/qr → creates QR with encrypted signature
[ ] POST /admin/approvals/{id}/approve → updates loan status to APPROVED
[ ] POST /attendance/iot/logs → accepts ESP32 data with API key
[ ] Middleware → rejects customer trying to access /admin routes
```

---

## 📖 Next Steps

1. Review this architecture document
2. Confirm database schema & relationships
3. Approve role hierarchy & API endpoints
4. Start Phase 1 implementation
5. Run migrations & test auth flow

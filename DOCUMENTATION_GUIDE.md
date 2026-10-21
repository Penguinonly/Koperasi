# 📚 Backend Setup Documentation - Complete Guide

## Overview

Ini adalah dokumentasi lengkap untuk setup **Koperasi Digital Backend** dengan Laravel 12 + Sanctum Authentication + Role-Based Access Control.

**Status**: ✅ Documentation Complete - Ready for Implementation

---

## 📖 Documentation Files

### 1. **ARCHITECTURE.md** - System Overview & Design
- Database schema (13 tables + relationships)
- Authentication flow & JWT structure
- Role hierarchy & permissions
- Route architecture
- Configuration requirements
- Testing checklist
- **Use this for**: Understanding the complete system design

---

### 2. **DATABASE_SCHEMA.md** - SQL Reference
- Detailed SQL schema for all tables
- Foreign key relationships
- Indexes & performance optimization
- Constraints & validation
- Sample data structure
- **Use this for**: Creating migrations & understanding table structures

---

### 3. **IMPLEMENTATION_ROADMAP.md** - Step-by-Step Guide
- Complete checklist for each phase (1-6)
- Code snippets for all models & controllers
- Migration templates
- Seeder examples
- Setup timeline: 12-15 hours
- Success criteria
- **Use this for**: Following exact implementation steps

---

### 4. **API_CONTRACTS.md** - Endpoint Documentation
- 25+ API endpoints with request/response examples
- Error response formats
- Authentication flow
- Role-based endpoint summary
- cURL testing examples
- Postman collection format
- **Use this for**: Frontend development & API testing

---

## 🎯 Quick Start Path

### **Before You Begin** (5 minutes)
1. Read: **ARCHITECTURE.md** → Understand the big picture
2. Review: **DATABASE_SCHEMA.md** → Know the data structure

### **Phase 1: Setup** (Day 1 morning)
1. Follow: **IMPLEMENTATION_ROADMAP.md** → Phase 1
2. Commands: Sanctum install, migrations, seeders
3. Verify: `php artisan migrate` runs successfully

### **Phase 2-3: Core Models** (Day 1 afternoon)
1. Follow: **IMPLEMENTATION_ROADMAP.md** → Phase 2-3
2. Create all 8 models with migrations
3. Verify: All migrations passing

### **Phase 4: Authentication** (Day 2 morning)
1. Follow: **IMPLEMENTATION_ROADMAP.md** → Phase 3
2. Implement AuthController
3. Test: Login endpoint with **API_CONTRACTS.md**

### **Phase 5: Authorization & Controllers** (Day 2-3)
1. Follow: **IMPLEMENTATION_ROADMAP.md** → Phase 4-5
2. Implement middleware & role-based routes
3. Stub out domain controllers

### **Phase 6: Testing** (Day 3-4)
1. Run all migrations & seeders
2. Test endpoints using **API_CONTRACTS.md**
3. Create Postman collection

---

## 📋 Implementation Checklist

### Phase 1️⃣: Foundation
- [ ] Install Sanctum: `composer require laravel/sanctum`
- [ ] Publish Sanctum config
- [ ] Create Role & Permission models
- [ ] Create 13 migrations (see roadmap)
- [ ] Create RoleSeeder & PermissionSeeder
- [ ] Update User model
- [ ] Run: `php artisan migrate`
- [ ] Run: `php artisan seed`

### Phase 2️⃣: Models
- [ ] Create Customer model + migration
- [ ] Create Loan model + migration
- [ ] Create LoanSchedule model + migration
- [ ] Create Payment model + migration
- [ ] Create PaymentQr model + migration
- [ ] Create AttendanceLog model + migration
- [ ] Create AttendanceRecord model + migration
- [ ] Create Approval model + migration
- [ ] Setup all relationships

### Phase 3️⃣: Authentication
- [ ] Create AuthController
- [ ] Implement: login, me, logout, refresh
- [ ] Create login request validation
- [ ] Setup routes: `routes/api.php`
- [ ] Test: `POST /auth/login`
- [ ] Test: `GET /auth/me`

### Phase 4️⃣: RBAC & Middleware
- [ ] Create CheckRole middleware
- [ ] Create CheckPermission middleware
- [ ] Register middleware in Kernel.php
- [ ] Group routes by role
- [ ] Test: Collector → cannot access /admin routes

### Phase 5️⃣: Controllers
- [ ] Create LoanController
- [ ] Create PaymentController
- [ ] Create CollectorController
- [ ] Create AdminController
- [ ] Create AttendanceController
- [ ] Setup routes for each
- [ ] Implement core business logic

### Phase 6️⃣: Testing
- [ ] Run: `php artisan migrate:fresh --seed`
- [ ] Test all endpoints in **API_CONTRACTS.md**
- [ ] Create Postman collection
- [ ] Test role-based access
- [ ] Generate API documentation

---

## 🔑 Key Files to Create

### **Models** (8 files)
```
app/Models/
├── Role.php
├── Permission.php
├── Customer.php
├── Loan.php
├── LoanSchedule.php
├── Payment.php
├── PaymentQr.php
├── AttendanceLog.php
├── AttendanceRecord.php
└── Approval.php
```

### **Controllers** (6 files)
```
app/Http/Controllers/
├── AuthController.php
├── LoanController.php
├── PaymentController.php
├── CollectorController.php
├── AdminController.php
└── AttendanceController.php
```

### **Middleware** (2 files)
```
app/Http/Middleware/
├── CheckRole.php
└── CheckPermission.php
```

### **Migrations** (13 files)
```
database/migrations/
├── [timestamp]_alter_users_add_role_fields.php
├── [timestamp]_create_roles_table.php
├── [timestamp]_create_permissions_table.php
├── [timestamp]_create_role_permission_table.php
├── [timestamp]_create_customers_table.php
├── [timestamp]_create_loans_table.php
├── [timestamp]_create_loan_schedules_table.php
├── [timestamp]_create_payments_table.php
├── [timestamp]_create_payment_qrs_table.php
├── [timestamp]_create_payment_qr_schedules_table.php
├── [timestamp]_create_attendance_logs_table.php
├── [timestamp]_create_attendance_records_table.php
└── [timestamp]_create_approvals_table.php
```

### **Seeders** (2 files)
```
database/seeders/
├── RoleSeeder.php
└── PermissionSeeder.php
```

---

## 📊 Database Summary

### Tables Created: 13 Core

| Table | Purpose | Records |
|-------|---------|---------|
| roles | Role definitions | ~4 |
| permissions | Permission definitions | ~15-20 |
| role_permission | Role-Permission pivot | ~30 |
| users | Staff/employees | ~50 |
| customers | Nasabah/borrowers | ~500 |
| loans | Active loans | ~200 |
| loan_schedules | Daily schedules (60/loan) | ~12,000 |
| payments | Payment transactions | ~5,000 |
| payment_qrs | QR codes | ~1,000 |
| payment_qr_schedules | QR-Schedule pivot | ~2,000 |
| attendance_logs | Raw IoT events | ~50,000+ |
| attendance_records | Daily consolidated | ~2,000 |
| approvals | Loan approvals | ~200 |

---

## 🔐 Authentication Summary

**Token Format**: JWT via Laravel Sanctum

**Token Payload:**
```json
{
  "sub": "user_id",
  "email": "user@example.com",
  "role": "collector",
  "platform": "DESKTOP",
  "iat": 1234567890,
  "exp": 1234654290
}
```

**Roles:**
1. **master_admin** - Highest level
2. **admin** - Approval & reporting
3. **collector** - Loan creation & QR generation
4. **customer** - Payment & loan viewing (mobile only)
5. **iot_device** - ESP32 attendance only

---

## 🛣️ API Routes Summary

### **Public Routes** (5)
- POST /auth/login
- POST /auth/register
- POST /customers/register
- POST /payments/scan
- POST /attendance/iot/logs

### **Protected Routes** (20+)
- Auth (3): me, logout, refresh
- Loans (3): create, show, list
- Payments (2): create QR, list
- Collector (4): customers, daily-monitoring, leaderboard, attendance-me
- Admin (6): approvals, arrears, attendance, reports, employees
- Master Admin (4): admins, employees, audit

---

## ✅ Verification Steps

### After Completing Phase 1:
```bash
php artisan migrate
# Should create 13 tables without errors
```

### After Completing Phase 3:
```bash
php artisan tinker
User::first()->createToken('test')->plainTextToken
# Should return a token
```

### After Completing Phase 4:
```bash
curl http://localhost:8000/api/auth/login
# Should return 200 with user & token
```

---

## 📝 Notes & Tips

### Development Tips
- Use `php artisan tinker` for interactive testing
- Use `php artisan route:list` to verify all routes
- Use `php artisan migrate:refresh --seed` to reset data
- Enable query logging in `.env`: `DB_QUERY_LOGGING=true`

### Common Issues & Solutions

**Issue**: Migration fails with "table already exists"
**Solution**: `php artisan migrate:fresh` (will drop all tables)

**Issue**: Token not working
**Solution**: Check `.env` has `SANCTUM_ENCRYPT_COOKIES=true`

**Issue**: Role-based access not working
**Solution**: Verify middleware is registered in `Kernel.php`

### Performance Considerations
- Loan schedules: Pre-generate 60 records per loan
- Attendance consolidation: Run nightly worker task
- QR codes: Encrypt signature with JWT
- Indexes: All foreign keys and frequently-queried columns

---

## 🎓 Learning Resources

**Recommended Reading (in order):**
1. ARCHITECTURE.md - 15 minutes
2. DATABASE_SCHEMA.md - 10 minutes
3. IMPLEMENTATION_ROADMAP.md - 30 minutes
4. API_CONTRACTS.md - 20 minutes
5. Start implementation!

**Laravel Resources:**
- [Laravel 12 Documentation](https://laravel.com/docs/12)
- [Laravel Sanctum](https://laravel.com/docs/12/sanctum)
- [Eloquent ORM](https://laravel.com/docs/12/eloquent)
- [Database Migrations](https://laravel.com/docs/12/migrations)

---

## 🚀 Next Steps

1. **Read** all 4 documentation files (1 hour)
2. **Setup** Sanctum & migrations (2-3 hours) → Phase 1-3
3. **Implement** AuthController & routes (2 hours) → Phase 4
4. **Build** domain controllers (4-6 hours) → Phase 5
5. **Test** all endpoints (2-3 hours) → Phase 6

**Total Time**: ~15 hours spread over 3-4 days

---

## 📞 Questions?

Refer back to the specific documentation file:
- Architecture questions → **ARCHITECTURE.md**
- Database questions → **DATABASE_SCHEMA.md**
- Implementation questions → **IMPLEMENTATION_ROADMAP.md**
- API questions → **API_CONTRACTS.md**

---

**Last Updated**: 2026-07-17  
**Status**: Ready for Implementation ✅

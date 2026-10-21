# Implementation Roadmap - Status Aktual dan Tahap Berikutnya

**Pembaruan:** 15 Oktober 2026  
**Snapshot validasi:** `php artisan test` lulus, 19 test dan 111 assertion.

> **Status dokumen:** snapshot dan roadmap ini mendahului perubahan produk 21 Oktober 2026. Route aktif sekarang hanya login, halaman admin tabungan, dan health check API. Lihat [PANDUAN_PROGRAM.md](PANDUAN_PROGRAM.md) dan [PROJECT_STATUS.md](PROJECT_STATUS.md) untuk kondisi terbaru.

## Status Saat Ini

Fondasi Laravel, Sanctum/RBAC dasar, data anggota CSV, workspace admin/collector, portal anggota, QR online lama, serta backend token pembayaran offline v1 sudah tersedia. Backend token mencakup migration, keypair Ed25519, keyring rotasi, paket token harian, sequence per pinjaman, konfirmasi collector/anggota, alokasi parsial, audit sync, idempotensi, dan expiry grace 24 jam.

Belum tersedia: verifikasi dan antrean offline pada aplikasi Android, UI operasional collector untuk paket token, rekonsiliasi konflik/setoran tunai, UI Riwayat Pinjaman, Data Admin/permission, dan UI absensi.

## Urutan Pekerjaan Berikutnya

### Tahap 1 - Integrasi Aplikasi Android

- Memverifikasi signature Ed25519 menggunakan `key_id` dan public keyring dari server.
- Menyimpan sequence terakhir per pinjaman secara tahan rollback/reset perangkat.
- Menolak token invalid/kedaluwarsa dan menampilkan klaim lokal sebagai menunggu sinkronisasi.
- Menyimpan nominal aktual, metode `CASH`, dan klaim ke antrean lokal terenkripsi.
- Mengirim klaim ke `POST /api/member/sync/confirm` saat online dan menampilkan `ACCEPTED`, `DUPLICATE`, `EXPIRED`, `REJECTED`, atau `SEQUENCE_CONFLICT`.
- Menguji reinstall/restore state dan batas operasi offline.

**Catatan:** Source aplikasi Android tidak berada di repository ini; tahap ini memerlukan repository Android serta kontrak [PAYMENT_TOKEN_PROTOCOL.md](PAYMENT_TOKEN_PROTOCOL.md).

### Tahap 2 - Alur Collector

- Menyinkronkan paket harian melalui `POST /api/collector/sync/tokens`.
- Menampilkan anggota, pinjaman, token QR utama/cadangan, plafon, dan expiry.
- Mengonfirmasi jumlah uang tunai aktual tanpa mengubah plafon token.
- Mengantre klaim offline dan mengirim batch ke `POST /api/collector/sync/confirm`.
- Menampilkan hasil server tanpa menyebut transaksi offline sebagai final sebelum `ACCEPTED`.

### Tahap 3 - Rekonsiliasi Pembayaran

- Membuat halaman admin untuk melihat `token_sync_log` dan detail klaim.
- Mengelompokkan `SEQUENCE_CONFLICT`, `DUPLICATE`, `EXPIRED`, `REJECTED`, dan `SIGNATURE_INVALID`.
- Menambahkan status tindak lanjut manusia dan audit keputusan, tanpa mengubah log mentah.
- Mencocokkan transaksi tunai dengan setoran kas; token sendiri bukan bukti setoran.

### Tahap 4 - Riwayat Pinjaman Web

- UI Validasi Pinjaman sudah tersedia di `/admin/loan-validations` dengan filter status/tanggal, detail, approve, dan reject beralasan.
- Keputusan hanya dapat diproses sekali untuk approval pending dan berjalan dalam transaksi.
- Membuat halaman riwayat dengan progres jadwal, pembayaran, dan tunggakan.

### Tahap 5 - Operasional dan Tata Kelola

- Uji konkurensi sequence/idempotensi pada database produksi (PostgreSQL/MySQL), bukan hanya SQLite.
- Memperluas test session admin, approval, CRUD, dan provisioning akun.
- Menambahkan Data Admin dan permission web.
- Menambahkan UI monitoring absensi.
- Menambahkan import XLSX, export, audit perubahan, dan upload dokumen.
- Memensiunkan `payment_qrs` hanya setelah semua klien dan laporan lama dimigrasikan.

---

## Panduan Setup Awal (Referensi Historis)

Bagian berikut adalah checklist scaffold awal Laravel. Gunakan bagian **Status Saat Ini** dan **Urutan Pekerjaan Berikutnya** sebagai roadmap aktif; jangan mengulang setup yang sudah tersedia.

## 📋 Complete Checklist for Backend Setup

### Phase 1️⃣: Authentication Foundation (Day 1)

#### 1.1 Install & Configure Sanctum
- [ ] Run: `composer require laravel/sanctum`
- [ ] Run: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- [ ] Add Sanctum middleware to `config/app.php`
- [ ] Update `.env`: `SANCTUM_ENCRYPT_COOKIES=true`

#### 1.2 Create Role & Permission Models
**Files to create:**
```
app/Models/Role.php
app/Models/Permission.php
```

**Role.php content:**
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description'];
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
```

**Permission.php content:**
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'description'];
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
```

#### 1.3 Create Migrations (13 files)
```bash
# Run these commands one by one
php artisan make:migration alter_users_add_role_fields
php artisan make:migration create_roles_table
php artisan make:migration create_permissions_table
php artisan make:migration create_role_permission_table
php artisan make:migration create_customers_table
php artisan make:migration create_loans_table
php artisan make:migration create_loan_schedules_table
php artisan make:migration create_payments_table
php artisan make:migration create_payment_qrs_table
php artisan make:migration create_payment_qr_schedules_table
php artisan make:migration create_attendance_logs_table
php artisan make:migration create_attendance_records_table
php artisan make:migration create_approvals_table
```

#### 1.4 Migration Content Templates

**`alter_users_add_role_fields` (Migration)**
```php
Schema::table('users', function (Blueprint $table) {
    $table->unsignedBigInteger('role_id')->default(1)->after('password');
    $table->string('platform')->default('WEB')->after('role_id');
    $table->string('employee_id')->nullable()->unique()->after('platform');
    $table->boolean('is_active')->default(true)->after('employee_id');
    
    $table->foreign('role_id')->references('id')->on('roles');
    $table->index('role_id');
});
```

**`create_roles_table` (Migration)**
```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->text('description')->nullable();
    $table->timestamps();
    $table->index('name');
});
```

**`create_permissions_table` (Migration)**
```php
Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->text('description')->nullable();
    $table->timestamps();
    $table->index('name');
});
```

**`create_role_permission_table` (Migration)**
```php
Schema::create('role_permission', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('role_id');
    $table->unsignedBigInteger('permission_id');
    $table->timestamps();
    
    $table->unique(['role_id', 'permission_id']);
    $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
    $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
});
```

#### 1.5 Update User Model
```php
// app/Models/User.php - Add these

public function role()
{
    return $this->belongsTo(Role::class);
}

public function permissions()
{
    return $this->role->permissions() ?? collect();
}

public function hasPermission($permission)
{
    return $this->permissions()->where('name', $permission)->exists();
}

public function hasRole($role)
{
    return $this->role->name === $role;
}

protected $fillable = [
    'name',
    'email',
    'password',
    'role_id',
    'platform',
    'employee_id',
];
```

#### 1.6 Create Seeders
```bash
php artisan make:seeder RoleSeeder
php artisan make:seeder PermissionSeeder
```

**`RoleSeeder` content:**
```php
public function run(): void
{
    Role::query()->create(['name' => 'customer', 'description' => 'Customer']);
    Role::query()->create(['name' => 'collector', 'description' => 'Collector/Employee']);
    Role::query()->create(['name' => 'admin', 'description' => 'Administrator']);
    Role::query()->create(['name' => 'master_admin', 'description' => 'Master Admin']);
}
```

**`PermissionSeeder` content:**
```php
public function run(): void
{
    $permissions = [
        'create_loan' => 'Create loan application',
        'approve_loan' => 'Approve loan application',
        'reject_loan' => 'Reject loan application',
        'view_customer' => 'View customer details',
        'create_payment_qr' => 'Create payment QR',
        'process_payment' => 'Process payment',
        'view_attendance' => 'View attendance',
        'manage_employees' => 'Manage employees',
    ];
    
    foreach ($permissions as $name => $desc) {
        Permission::query()->create(['name' => $name, 'description' => $desc]);
    }
}
```

#### 1.7 Run Migrations
```bash
php artisan migrate
php artisan seed  # Runs all seeders including RoleSeeder & PermissionSeeder
```

---

### Phase 2️⃣: Core Models (Day 1-2)

#### 2.1 Create Customer, Loan Models
```bash
php artisan make:model Customer -m
php artisan make:model Loan -m
php artisan make:model LoanSchedule -m
php artisan make:model Payment -m
php artisan make:model PaymentQr -m
php artisan make:model AttendanceLog -m
php artisan make:model AttendanceRecord -m
php artisan make:model Approval -m
```

#### 2.2 Customer.php
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'user_id', 'name', 'phone', 'business_name', 'business_location',
        'id_number', 'id_type', 'monthly_income', 'status', 'streak_count'
    ];
    
    protected $casts = ['business_location' => 'json'];
    
    public function user() { return $this->belongsTo(User::class); }
    public function loans() { return $this->hasMany(Loan::class); }
    public function payments() { return $this->hasMany(Payment::class); }
}
```

#### 2.3 Loan.php (Example)
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'customer_id', 'collector_id', 'loan_amount', 'duration_days',
        'interest_rate', 'status', 'approved_by', 'approval_date',
        'started_at', 'completed_at'
    ];
    
    public function customer() { return $this->belongsTo(Customer::class); }
    public function collector() { return $this->belongsTo(User::class, 'collector_id'); }
    public function schedules() { return $this->hasMany(LoanSchedule::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function approval() { return $this->hasOne(Approval::class); }
}
```

---

### Phase 3️⃣: Authentication Routes & Controller (Day 2)

#### 3.1 Create AuthController
```bash
php artisan make:controller AuthController
```

#### 3.2 AuthController.php (Skeleton)
```php
<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validate email/password
        // Hash password check
        // Generate Sanctum token
        // Return token + user + permissions
    }
    
    public function me(Request $request)
    {
        // Return authenticated user with role & permissions
    }
    
    public function logout(Request $request)
    {
        // Revoke current token
    }
    
    public function refresh(Request $request)
    {
        // Refresh token (optional for long-lived apps)
    }
}
```

#### 3.3 Setup Auth Routes
**`routes/api.php` content:**
```php
<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
```

---

### Phase 4️⃣: Middleware & RBAC (Day 2-3)

#### 4.1 Create Middleware
```bash
php artisan make:middleware CheckRole
php artisan make:middleware CheckPermission
```

#### 4.2 CheckRole.php
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check() || !in_array(auth()->user()->role->name, $roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return $next($request);
    }
}
```

#### 4.3 Register Middleware in `app/Http/Kernel.php`
```php
protected $routeMiddleware = [
    // ... existing
    'role' => \App\Http\Middleware\CheckRole::class,
    'permission' => \App\Http\Middleware\CheckPermission::class,
];
```

#### 4.4 Group Routes by Role
**`routes/api.php` update:**
```php
Route::middleware(['auth:sanctum', 'role:collector'])->prefix('collector')->group(function () {
    Route::post('/loans', [LoanController::class, 'store']);
    Route::get('/customers', [CollectorController::class, 'customers']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/approvals', [AdminController::class, 'approvals']);
    Route::post('/approvals/{id}/approve', [AdminController::class, 'approve']);
});
```

---

### Phase 5️⃣: Core Controllers (Day 3-4)

#### 5.1 Create Controllers
```bash
php artisan make:controller LoanController
php artisan make:controller PaymentController
php artisan make:controller CollectorController
php artisan make:controller AdminController
php artisan make:controller MasterAdminController
php artisan make:controller AttendanceController
```

#### 5.2 LoanController.php (Skeleton)
```php
<?php
namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function store(Request $request)
    {
        // Create loan with 60 day schedules
        // Only collector role
    }
    
    public function show($id)
    {
        // Return loan with all schedules
    }
    
    public function index()
    {
        // List loans (filtered by role)
    }
}
```

---

### Phase 6️⃣: Final Testing (Day 4-5)

#### 6.1 Run Migrations
```bash
php artisan migrate:refresh --seed
```

#### 6.2 Test Auth Endpoint
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

#### 6.3 Create Postman Collection
- [ ] Import endpoints from `routes/api.php`
- [ ] Test with JWT token
- [ ] Verify role-based access

---

## 📋 File Summary

### To Create (18 files)
- **Models (8)**: Customer, Loan, LoanSchedule, Payment, PaymentQr, AttendanceLog, AttendanceRecord, Approval
- **Controllers (6)**: Auth, Loan, Payment, Collector, Admin, Attendance
- **Middleware (2)**: CheckRole, CheckPermission
- **Seeders (2)**: Role, Permission

### To Update (3 files)
- **User.php**: Add role relationship + methods
- **routes/api.php**: Add auth + role-based routes
- **Kernel.php**: Register middleware

### Migrations (13)
- All listed in Phase 1.3

---

## ✅ Success Criteria

- [ ] `php artisan migrate` runs without errors
- [ ] `POST /auth/login` returns valid JWT token
- [ ] `GET /auth/me` returns authenticated user with role & permissions
- [ ] Middleware blocks collector from accessing `/admin` routes
- [ ] Database contains all 13 tables with correct relationships

---

## 🚀 Timeline

| Phase | Tasks | Duration |
|-------|-------|----------|
| 1 | Sanctum + Auth models + migrations + seeders | 3-4 hours |
| 2 | Customer/Loan/Payment models + migrations | 2-3 hours |
| 3 | AuthController + routes | 2 hours |
| 4 | Middleware + role-based routes | 1.5 hours |
| 5 | Core controllers (stubs) | 2 hours |
| 6 | Testing + Postman collection | 1.5 hours |
| **Total** | | **12-15 hours** |

---

## 📝 Notes

- Use `php artisan tinker` to test queries interactively
- Generate test tokens in tinker: `User::first()->createToken('test')->plainTextToken`
- Check `.env` for timezone: `APP_TIMEZONE=Asia/Jakarta`
- SQLite doesn't support all features; use PostgreSQL for production

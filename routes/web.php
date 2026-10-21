<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminEmployeeController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLoanController;
use App\Http\Controllers\AdminLoanHistoryController;
use App\Http\Controllers\AdminTokenReconciliationController;
use App\Http\Controllers\AdminLoanValidationController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\StaffCustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::view('/login', 'login')->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.authenticate');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware(['auth', 'role:customer'])->prefix('member')->name('member.')->group(function () {
    Route::get('/', [MemberPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [MemberPortalController::class, 'profile'])->name('profile');
    Route::put('/profile', [MemberPortalController::class, 'updateProfile'])->name('profile.update');
    Route::get('/payments', [MemberPortalController::class, 'payments'])->name('payments');
    Route::post('/scan/preview', [PaymentController::class, 'previewScan'])->name('scan.preview');
    Route::post('/scan', [PaymentController::class, 'scan'])->name('scan');
});

Route::middleware('staff.session')->prefix('workspace')->name('workspace.')->group(function () {
    Route::get('/customers', [StaffCustomerController::class, 'index'])->name('customers');
    Route::get('/customers/{customer}', [StaffCustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/loans/{loan}', [StaffCustomerController::class, 'loan'])->name('loans.show');
});

Route::middleware('admin.session')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => view('admin.dashboard', ['active' => 'dashboard']))->name('dashboard');
    Route::get('/employees', [AdminEmployeeController::class, 'index'])->name('employees');
    Route::post('/employees', [AdminEmployeeController::class, 'store'])->name('employees.store');
    Route::put('/employees/{employee}', [AdminEmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [AdminEmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::get('/loan-history', [AdminLoanHistoryController::class, 'index'])->name('loan-history');
    Route::get('/token-reconciliation', [AdminTokenReconciliationController::class, 'index'])->name('token-reconciliation');
    Route::post('/token-reconciliation/{syncLog}/review', [AdminTokenReconciliationController::class, 'review'])->name('token-reconciliation.review');
    Route::get('/loan-validations', [AdminLoanValidationController::class, 'index'])->name('loan-validations');
    Route::get('/loan-validations/{approval}', [AdminLoanValidationController::class, 'show'])->name('loan-validations.show');
    Route::post('/loan-validations/{approval}/approve', [AdminController::class, 'approve'])->name('loan-validations.approve');
    Route::post('/loan-validations/{approval}/reject', [AdminController::class, 'reject'])->name('loan-validations.reject');
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers');
    Route::post('/customers/provision-users', [AdminCustomerController::class, 'provisionUsers'])->name('customers.provision-users');
    Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/loans/{loan}', [AdminCustomerController::class, 'loan'])->name('customers.loans.show');
    Route::put('/customers/{customer}', [AdminCustomerController::class, 'update'])->name('customers.update');
    Route::post('/customers/{customer}/loans', [AdminLoanController::class, 'store'])->name('customers.loans.store');
    Route::put('/loans/{loan}', [AdminLoanController::class, 'update'])->name('loans.update');
    Route::delete('/loans/{loan}', [AdminLoanController::class, 'destroy'])->name('loans.destroy');
});

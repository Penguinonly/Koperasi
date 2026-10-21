<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollectorController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MasterAdminController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentTokenSyncController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['ok' => true, 'service' => 'koperasi-laravel-api']);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/customers/register', [CustomerController::class, 'register']);
Route::post('/payments/scan', [PaymentController::class, 'scan']);
Route::post('/attendance/iot/logs', [AttendanceController::class, 'iotLog']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    Route::get('/customers', [CustomerController::class, 'index'])->middleware('role:collector,admin,master_admin');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('role:collector');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('role:collector,admin,master_admin');

    Route::get('/loans', [LoanController::class, 'index']);
    Route::post('/loans', [LoanController::class, 'store'])->middleware('role:collector');
    Route::get('/loans/{loan}', [LoanController::class, 'show']);

    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments/qr', [PaymentController::class, 'createQr'])->middleware('role:collector');

    Route::get('/attendance/me', [AttendanceController::class, 'me'])->middleware('role:collector,admin,master_admin');

    Route::prefix('collector')->middleware('role:collector')->group(function () {
        Route::get('/customers', [CollectorController::class, 'customers']);
        Route::get('/daily-monitoring', [CollectorController::class, 'dailyMonitoring']);
        Route::get('/leaderboard', [CollectorController::class, 'leaderboard']);
        Route::get('/attendance-me', [CollectorController::class, 'attendanceMe']);
        Route::post('/sync/tokens', [PaymentTokenSyncController::class, 'issue']);
        Route::post('/sync/confirm', [PaymentTokenSyncController::class, 'confirm']);
    });

    Route::prefix('member')->middleware('role:customer')->group(function () {
        Route::post('/sync/confirm', [PaymentTokenSyncController::class, 'confirm']);
    });

    Route::prefix('admin')->middleware('role:admin,master_admin')->group(function () {
        Route::get('/approvals', [AdminController::class, 'approvals']);
        Route::post('/approvals/{approval}/approve', [AdminController::class, 'approve']);
        Route::post('/approvals/{approval}/reject', [AdminController::class, 'reject']);
        Route::get('/arrears/by-collector', [AdminController::class, 'arrearsByCollector']);
        Route::get('/attendance', [AdminController::class, 'attendance']);
        Route::post('/employees', [AdminController::class, 'storeEmployee']);
    });

    Route::prefix('master')->middleware('role:master_admin')->group(function () {
        Route::post('/admins', [MasterAdminController::class, 'storeAdmin']);
        Route::post('/employees', [MasterAdminController::class, 'storeEmployee']);
    });
});

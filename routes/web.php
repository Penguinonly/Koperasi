<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminSavingsController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::view('/login', 'login')->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.authenticate');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))->name('dashboard');

Route::middleware(['auth', 'admin.session'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/savings/snapshot', [DashboardController::class, 'snapshot'])->name('savings.snapshot');
    Route::get('/savings/{customer}/export', [AdminSavingsController::class, 'export'])->name('savings.export');
    Route::get('/savings/{customer}/transactions/ids', [AdminSavingsController::class, 'transactionIds'])->name('savings.transactions.ids');
    Route::delete('/savings/{customer}/transactions', [AdminSavingsController::class, 'destroySelected'])->name('savings.transactions.destroy-selected');
    Route::put('/savings/{customer}/transactions/{transactionId}', [AdminSavingsController::class, 'update'])->whereNumber('transactionId')->name('savings.transactions.update');
    Route::delete('/savings/{customer}/transactions/{transactionId}', [AdminSavingsController::class, 'destroy'])->whereNumber('transactionId')->name('savings.transactions.destroy');
    Route::get('/savings/{customer}', [AdminSavingsController::class, 'show'])->name('savings.show');
    Route::post('/savings/{customer}', [AdminSavingsController::class, 'store'])->name('savings.store');
});

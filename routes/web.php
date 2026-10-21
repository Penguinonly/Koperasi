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
    Route::get('/savings/{customer}', [AdminSavingsController::class, 'show'])->name('savings.show');
    Route::post('/savings/{customer}', [AdminSavingsController::class, 'store'])->name('savings.store');
});

<?php

use App\Http\Controllers\PobFormController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Auth;

// =========================================
// HALAMAN MITRA (TANPA LOGIN)
// =========================================
Route::get('/', [PobFormController::class, 'index'])->name('form.index');
Route::post('/submit', [PobFormController::class, 'store'])->name('form.store');

// =========================================
// AUTH
// =========================================
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// =========================================
// DASHBOARD (PERLU LOGIN)
// =========================================
Route::middleware('auth')->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/import', [ImportController::class, 'showForm'])->name('dashboard.import');
    Route::post('/import', [ImportController::class, 'upload'])->name('dashboard.import.upload');
});
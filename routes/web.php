<?php

use App\Http\Controllers\PobFormController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────
// PUBLIC — Form Mitra (tanpa login)
// ─────────────────────────────────────────────
// Step 1: Isi data POB
Route::get('/',                  [PobFormController::class, 'index'])->name('form.index');
Route::post('/submit',           [PobFormController::class, 'store'])->name('form.store');

// Step 2: Upload Excel karyawan
Route::get('/upload-karyawan',   [PobFormController::class, 'showUpload'])->name('form.upload');
Route::post('/upload-karyawan',  [PobFormController::class, 'processUpload'])->name('form.upload.post');

// Done
Route::get('/selesai',           [PobFormController::class, 'done'])->name('form.done');

// Download template
Route::get('/template-karyawan', [PobFormController::class, 'downloadTemplate'])->name('form.template');

// ─────────────────────────────────────────────
// AUTH
// ─────────────────────────────────────────────
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout',[LoginController::class, 'logout'])->name('logout');

// ─────────────────────────────────────────────
// DASHBOARD (perlu login)
// ─────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::prefix('dashboard')->group(function () {
        Route::get('/',       [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/export', [DashboardController::class, 'export'])->name('dashboard.export');
        Route::get('/import', [ImportController::class, 'showForm'])->name('dashboard.import');
        Route::post('/import',[ImportController::class, 'upload'])->name('dashboard.import.upload');
    });

    Route::prefix('employees')->group(function () {
        Route::get('/',       [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/upload', [EmployeeController::class, 'showUpload'])->name('employees.upload');
        Route::post('/upload',[EmployeeController::class, 'upload'])->name('employees.upload.post');
        Route::get('/export', [EmployeeController::class, 'export'])->name('employees.export');
        Route::get('/template', [PobFormController::class, 'downloadTemplate'])->name('employees.template');
    });
});

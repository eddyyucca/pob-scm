<?php
use App\Http\Controllers\PobFormController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PobEntryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── PUBLIC (mitra) ────────────────────────────────────────────────
Route::get('/',                  [PobFormController::class, 'index'])->name('form.index');
Route::post('/submit',           [PobFormController::class, 'store'])->name('form.store');
Route::get('/upload-karyawan',   [PobFormController::class, 'showUpload'])->name('form.upload');
Route::post('/upload-karyawan',  [PobFormController::class, 'processUpload'])->name('form.upload.post');
Route::get('/selesai',           [PobFormController::class, 'done'])->name('form.done');
Route::get('/template-karyawan', [PobFormController::class, 'downloadTemplate'])->name('form.template');
Route::get('/panduan-pob',       [PobFormController::class, 'downloadGuide'])->name('form.guide');

// ── AUTH ─────────────────────────────────────────────────────────
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout',[LoginController::class, 'logout'])->name('logout');

// ── DASHBOARD ADMIN ───────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard',         [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export',  [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/dashboard/import',  [ImportController::class, 'showForm'])->name('dashboard.import');
    Route::post('/dashboard/import', [ImportController::class, 'upload'])->name('dashboard.import.upload');

    // CRUD Laporan POB
    Route::get('/pob-entries',                          [PobEntryController::class, 'index'])->name('pob-entries.index');
    Route::get('/pob-entries/{pobEntry}',               [PobEntryController::class, 'show'])->name('pob-entries.show');
    Route::get('/pob-entries/{pobEntry}/edit',          [PobEntryController::class, 'edit'])->name('pob-entries.edit');
    Route::put('/pob-entries/{pobEntry}',               [PobEntryController::class, 'update'])->name('pob-entries.update');
    Route::delete('/pob-entries/{pobEntry}',            [PobEntryController::class, 'destroy'])->name('pob-entries.destroy');
    Route::delete('/pob-employees/{employee}',          [PobEntryController::class, 'destroyEmployee'])->name('pob-entries.employee.destroy');

    // Data karyawan
    Route::get('/employees',          [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/upload',   [EmployeeController::class, 'showUpload'])->name('employees.upload');
    Route::post('/employees/upload',  [EmployeeController::class, 'upload'])->name('employees.upload.post');
    Route::get('/employees/export',   [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('/employees/template', [PobFormController::class, 'downloadTemplate'])->name('employees.template');

    // CRUD Perusahaan
    Route::get('/companies',              [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/create',       [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies',             [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{company}/edit',   [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}',        [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}',     [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::patch('/companies/{company}/toggle',[CompanyController::class, 'toggle'])->name('companies.toggle');

    // CRUD Users
    Route::get('/users',              [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create',       [UserController::class, 'create'])->name('users.create');
    Route::post('/users',             [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit',  [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}',       [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}',    [UserController::class, 'destroy'])->name('users.destroy');

    // Notifikasi WA
    Route::get('/notifications',                             [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/send',                       [NotificationController::class, 'sendReminder'])->name('notifications.send');
    Route::get('/report',                                         [ReportController::class, 'index'])->name('report.index');
    Route::post('/notifications/send-scheduled',              [NotificationController::class, 'sendScheduled'])->name('notifications.send-scheduled');
    Route::post('/notifications/send-friday',                [NotificationController::class, 'sendFridayAll'])->name('notifications.send-friday');
    Route::post('/notifications/contacts',                   [NotificationController::class, 'storeContact'])->name('notifications.contact.store');
    Route::delete('/notifications/contacts/{contact}',       [NotificationController::class, 'destroyContact'])->name('notifications.contact.destroy');
    Route::patch('/notifications/contacts/{contact}/toggle', [NotificationController::class, 'toggleContact'])->name('notifications.contact.toggle');
});
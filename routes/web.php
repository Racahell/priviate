<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Guest\DashboardController as GuestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Student\DashboardController as StudentController;
use App\Http\Controllers\Tutor\DashboardController as TutorController;
use App\Http\Controllers\Admin\DashboardController as AdminController;
use App\Http\Controllers\Owner\DashboardController as OwnerController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminController;
use App\Http\Controllers\Superadmin\WhitelabelController;

// 1. Guest / Public
Route::get('/', [GuestController::class, 'index'])->name('home');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// 2. Authenticated Routes
Route::middleware(['auth'])->group(function () {
    
    // Student
    Route::group(['prefix' => 'student', 'as' => 'student.', 'middleware' => ['role:siswa']], function () {
        Route::get('/dashboard', [StudentController::class, 'index'])->name('dashboard');
        // Add booking, invoices, etc.
        Route::get('/booking', function() { return 'Booking Page'; })->name('booking');
        Route::get('/invoices', function() { return 'Invoices Page'; })->name('invoices');
    });

    // Tutor
    Route::group(['prefix' => 'tutor', 'as' => 'tutor.', 'middleware' => ['role:tentor']], function () {
        Route::get('/dashboard', [TutorController::class, 'index'])->name('dashboard');
        Route::get('/schedule', function() { return 'Schedule Page'; })->name('schedule');
        Route::get('/wallet', function() { return 'Wallet Page'; })->name('wallet');
        
        // Geofencing API (usually in api.php but here for demo)
        Route::post('/session/start', function() { return response()->json(['allowed' => true]); }); 
    });

    // Admin
    Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['role:admin']], function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/kyc', function() { return 'KYC Page'; })->name('kyc');
        Route::get('/disputes', function() { return 'Disputes Page'; })->name('disputes');
        Route::get('/monitor', function() { return 'Monitor Page'; })->name('monitor');
    });

    // Owner
    Route::group(['prefix' => 'owner', 'as' => 'owner.', 'middleware' => ['role:owner']], function () {
        Route::get('/dashboard', [OwnerController::class, 'index'])->name('dashboard');
        Route::get('/financials', [OwnerController::class, 'financials'])->name('financials');
    });

    // Superadmin
    Route::group(['prefix' => 'superadmin', 'as' => 'superadmin.', 'middleware' => ['role:superadmin']], function () {
        Route::get('/dashboard', [SuperadminController::class, 'index'])->name('dashboard');
        
        // Whitelabeling
        Route::get('/whitelabel', [WhitelabelController::class, 'index'])->name('whitelabel');
        Route::put('/whitelabel/{id}', [WhitelabelController::class, 'update'])->name('whitelabel.update');
        
        Route::get('/rbac', function() { return 'RBAC Page'; })->name('rbac');
        Route::get('/audit', function() { return 'Audit Logs'; })->name('audit');
    });
});

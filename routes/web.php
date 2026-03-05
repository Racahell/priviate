<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guest\DashboardController as GuestController;
use App\Http\Controllers\ModuleDataController;
use App\Http\Controllers\Owner\DashboardController as OwnerController;
use App\Http\Controllers\Owner\ReportController as OwnerReportController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\ParentUser\DashboardController as ParentDashboardController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Superadmin\SystemManagementController;
use App\Http\Controllers\Superadmin\WhitelabelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GuestController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register/pre-verify', [AuthController::class, 'showPreVerifyForm'])->name('register.preverify');
    Route::post('/register/pre-verify', [AuthController::class, 'sendPreVerification'])->name('register.preverify.send');
    Route::get('/register/activate/{token}', [AuthController::class, 'activatePreVerification'])->name('register.activate');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    Route::get('/two-factor', [AuthController::class, 'showTwoFactorForm'])->name('twofactor.form');
    Route::post('/two-factor', [AuthController::class, 'verifyTwoFactor'])->name('twofactor.verify');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.forgot');
    Route::post('/forgot-password', [AuthController::class, 'sendForgotPasswordOtp'])->name('password.forgot.send');
    Route::get('/forgot-password/{requestId}', [AuthController::class, 'showForgotPasswordVerifyForm'])->name('password.forgot.verify');
    Route::post('/forgot-password/{requestId}', [AuthController::class, 'resetForgotPassword'])->name('password.forgot.reset');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'access.control', 'menu.permission'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/location-consent', [AuthController::class, 'updateLocationConsent'])->name('location.consent');

    Route::post('/ops/package/select', [OperationsController::class, 'selectPackage'])->name('ops.package.select');
    Route::post('/ops/payment/success', [OperationsController::class, 'paymentSuccess'])->name('ops.payment.success');
    Route::post('/ops/subject/select', [OperationsController::class, 'selectSubject'])->name('ops.subject.select');
    Route::post('/ops/schedule/create', [OperationsController::class, 'createSchedule'])->name('ops.schedule.create');
    Route::post('/ops/session/{sessionId}/reminder', [OperationsController::class, 'sendReminder'])->name('ops.session.reminder');
    Route::post('/ops/session/{sessionId}/start', [OperationsController::class, 'startSession'])->name('ops.session.start');
    Route::post('/ops/session/{sessionId}/attendance', [OperationsController::class, 'markAttendance'])->name('ops.attendance.mark');
    Route::post('/ops/session/{sessionId}/material', [OperationsController::class, 'submitMaterial'])->name('ops.material.submit');
    Route::post('/ops/dispute', [OperationsController::class, 'createDispute'])->name('ops.dispute.create');
    Route::put('/ops/dispute/{id}', [OperationsController::class, 'updateDispute'])->name('ops.dispute.update');
    Route::post('/ops/dispute/{id}/resolve', [OperationsController::class, 'resolveDispute'])->name('ops.dispute.resolve');
    Route::post('/ops/payout/create', [OperationsController::class, 'createPayout'])->name('ops.payout.create');
    Route::post('/ops/payout/{id}/paid', [OperationsController::class, 'markPayoutPaid'])->name('ops.payout.paid');
    Route::post('/ops/reschedule/request', [OperationsController::class, 'requestReschedule'])->name('ops.reschedule.request');
    Route::post('/ops/reschedule/{id}/approve', [OperationsController::class, 'approveReschedule'])->name('ops.reschedule.approve');
    Route::post('/ops/reschedule/{id}/deny', [OperationsController::class, 'denyReschedule'])->name('ops.reschedule.deny');

    Route::group(['prefix' => 'student', 'as' => 'student.', 'middleware' => ['role:siswa']], function () {
        Route::get('/dashboard', fn () => redirect()->route('dashboard'))->name('dashboard');
        Route::get('/booking', [PortalController::class, 'studentBooking'])->name('booking');
        Route::get('/invoices', [PortalController::class, 'studentInvoices'])->name('invoices');
    });

    Route::group(['prefix' => 'tutor', 'as' => 'tutor.', 'middleware' => ['role:tentor']], function () {
        Route::get('/dashboard', fn () => redirect()->route('dashboard'))->name('dashboard');
        Route::get('/schedule', [PortalController::class, 'tutorSchedule'])->name('schedule');
        Route::get('/wallet', [PortalController::class, 'tutorWallet'])->name('wallet');
        Route::post('/session/start', fn () => response()->json(['allowed' => true]))->name('session.start');
    });

    Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['role:admin']], function () {
        Route::get('/dashboard', fn () => redirect()->route('dashboard'))->name('dashboard');
        Route::get('/kyc', [PortalController::class, 'adminKyc'])->name('kyc');
        Route::get('/disputes', [PortalController::class, 'adminDisputes'])->name('disputes');
        Route::get('/monitor', [PortalController::class, 'adminMonitor'])->name('monitor');
        Route::get('/modules/packages', [ModuleDataController::class, 'index'])->defaults('module', 'packages')->name('modules.packages');
        Route::get('/modules/subjects', [ModuleDataController::class, 'index'])->defaults('module', 'subjects')->name('modules.subjects');
        Route::get('/modules/items', [ModuleDataController::class, 'index'])->defaults('module', 'items')->name('modules.items');
        Route::get('/modules/users', [ModuleDataController::class, 'index'])->defaults('module', 'users')->name('modules.users');
        Route::post('/modules/{module}', [ModuleDataController::class, 'store'])->name('modules.store');
        Route::put('/modules/{module}/{id}', [ModuleDataController::class, 'update'])->name('modules.update');
        Route::post('/modules/{module}/bulk', [ModuleDataController::class, 'bulk'])->name('modules.bulk');
        Route::delete('/modules/{module}/{id}', [ModuleDataController::class, 'softDelete'])->name('modules.softdelete');
        Route::get('/import', [SystemManagementController::class, 'importCenter'])->name('import.center');
        Route::post('/import/users', [SystemManagementController::class, 'importUsers'])->name('import.users');
        Route::post('/import/items', [SystemManagementController::class, 'importItems'])->name('import.items');
    });

    Route::group(['prefix' => 'manager', 'as' => 'manager.', 'middleware' => ['role:manager']], function () {
        Route::get('/dashboard', fn () => redirect()->route('dashboard'))->name('dashboard');
    });

    Route::group(['prefix' => 'orang-tua', 'as' => 'parent.', 'middleware' => ['role:orang_tua']], function () {
        Route::get('/dashboard', [ParentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/anak', [ParentDashboardController::class, 'children'])->name('children');
        Route::post('/anak', [ParentDashboardController::class, 'linkChild'])->name('children.link');
    });

    Route::group(['prefix' => 'owner', 'as' => 'owner.', 'middleware' => ['role:owner']], function () {
        Route::get('/dashboard', fn () => redirect()->route('dashboard'))->name('dashboard');
        Route::get('/financials', [OwnerController::class, 'financials'])->name('financials');
        Route::get('/reports', [OwnerReportController::class, 'index'])->name('reports');
        Route::get('/reports/data', [OwnerReportController::class, 'data'])->name('reports.data');
        Route::post('/reports/operational-cost', [OwnerReportController::class, 'storeOperationalCost'])->name('reports.cost.store');
        Route::get('/reports/export', [OwnerReportController::class, 'export'])->name('reports.export');
    });

    Route::group(['prefix' => 'superadmin', 'as' => 'superadmin.', 'middleware' => ['role:superadmin']], function () {
        Route::get('/dashboard', fn () => redirect()->route('dashboard'))->name('dashboard');

        Route::get('/whitelabel', [WhitelabelController::class, 'index'])->name('whitelabel');
        Route::put('/whitelabel/{id}', [WhitelabelController::class, 'update'])->name('whitelabel.update');

        Route::get('/settings', [SystemManagementController::class, 'settings'])->name('settings');
        Route::post('/settings', [SystemManagementController::class, 'updateSettings'])->name('settings.update');

        Route::get('/menu-access', [SystemManagementController::class, 'menuAccess'])->name('menu.access');
        Route::get('/menu-access/{role}', [SystemManagementController::class, 'menuAccessRole'])->name('menu.access.role');
        Route::post('/menu-access/{role}', [SystemManagementController::class, 'updateMenuAccessRole'])->name('menu.access.role.update');

        Route::get('/modules/packages', [ModuleDataController::class, 'index'])->defaults('module', 'packages')->name('modules.packages');
        Route::get('/modules/subjects', [ModuleDataController::class, 'index'])->defaults('module', 'subjects')->name('modules.subjects');
        Route::get('/modules/items', [ModuleDataController::class, 'index'])->defaults('module', 'items')->name('modules.items');
        Route::get('/modules/users', [ModuleDataController::class, 'index'])->defaults('module', 'users')->name('modules.users');
        Route::post('/modules/{module}', [ModuleDataController::class, 'store'])->name('modules.store');
        Route::put('/modules/{module}/{id}', [ModuleDataController::class, 'update'])->name('modules.update');
        Route::post('/modules/{module}/bulk', [ModuleDataController::class, 'bulk'])->name('modules.bulk');
        Route::delete('/modules/{module}/{id}', [ModuleDataController::class, 'softDelete'])->name('modules.softdelete');
        Route::post('/modules/{module}/{id}/restore', [ModuleDataController::class, 'restore'])->name('modules.restore');
        Route::delete('/modules/{module}/{id}/force', [ModuleDataController::class, 'forceDelete'])->name('modules.forceDelete');

        Route::get('/backup', [SystemManagementController::class, 'backupCenter'])->name('backup.center');
        Route::post('/backup', [SystemManagementController::class, 'createBackup'])->name('backup.create');
        Route::post('/backup/{backupId}/preview', [SystemManagementController::class, 'previewPartialRestore'])->name('backup.preview');
        Route::post('/backup/{backupId}/partial-restore', [SystemManagementController::class, 'applyPartialRestore'])->name('backup.partial.restore');
        Route::post('/backup/{backupId}/disaster-restore', [SystemManagementController::class, 'disasterRestore'])->name('backup.disaster.restore');

        Route::get('/import', [SystemManagementController::class, 'importCenter'])->name('import.center');
        Route::post('/import/users', [SystemManagementController::class, 'importUsers'])->name('import.users');
        Route::post('/import/items', [SystemManagementController::class, 'importItems'])->name('import.items');

        Route::get('/rbac', [PortalController::class, 'superadminRbac'])->name('rbac');
        Route::get('/audit', [PortalController::class, 'superadminAudit'])->name('audit');
    });
});

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

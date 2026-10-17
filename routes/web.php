<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncement;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendance;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\ReportController as AdminReport;
use App\Http\Controllers\Admin\ScheduleController as AdminSchedule;
use App\Http\Controllers\Admin\ScheduleRequestController as AdminScheduleRequest;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Pegawai\AnnouncementController as PegawaiAnnouncement;
use App\Http\Controllers\Pegawai\AttendanceController as PegawaiAttendance;
use App\Http\Controllers\Pegawai\DashboardController as PegawaiDashboard;
use App\Http\Controllers\Pegawai\ProfileController;
use App\Http\Controllers\Pegawai\ReportController as PegawaiReport;
use App\Http\Controllers\Pegawai\RequestController;
use App\Http\Controllers\Pegawai\ScheduleController as PegawaiSchedule;
use App\Http\Controllers\Pegawai\ScheduleRequestController as PegawaiScheduleRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check()
    ? redirect()->route(Auth::user()->dashboardRoute())
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/attachments/{type}/{id}', AttachmentController::class)->where(['type' => 'schedule|leave', 'id' => '[0-9]+'])->name('attachments.show');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

/* ===================== ADMIN ===================== */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');

    Route::resource('employees', EmployeeController::class)->except('destroy');
    Route::patch('employees/{employee}/toggle', [EmployeeController::class, 'toggle'])->name('employees.toggle');
    Route::post('employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('employees.reset-password');

    Route::prefix('master/{type}')->where(['type' => 'department|position', 'id' => '[0-9]+'])
        ->name('master.')->controller(MasterDataController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{id}', 'update')->name('update');
            Route::patch('/{id}/toggle', 'toggle')->name('toggle');
        });

    // Jadwal
    Route::resource('schedules', AdminSchedule::class)->except('destroy');
    Route::patch('schedules/{schedule}/cancel', [AdminSchedule::class, 'cancel'])->name('schedules.cancel');

    Route::controller(ShiftController::class)->prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{shift}', 'update')->name('update');
        Route::patch('/{shift}/toggle', 'toggle')->name('toggle');
    });

    // Pengajuan jadwal
    Route::controller(AdminScheduleRequest::class)->prefix('schedule-requests')->name('schedule-requests.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{scheduleRequest}', 'show')->name('show');
        Route::patch('/{scheduleRequest}/approve', 'approve')->name('approve');
        Route::patch('/{scheduleRequest}/reject', 'reject')->name('reject');
    });

    // Absensi
    Route::controller(AdminAttendance::class)->prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{record}', 'update')->name('update');
    });

    // Cuti/izin/sakit & lembur
    Route::prefix('approvals/{type}')->where(['type' => 'leave|overtime', 'id' => '[0-9]+'])
        ->name('approvals.')->controller(ApprovalController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::patch('/{id}/approve', 'approve')->name('approve');
            Route::patch('/{id}/reject', 'reject')->name('reject');
        });

    Route::resource('announcements', AdminAnnouncement::class)->except('show');
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::controller(SettingController::class)->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/', 'update')->name('update');
        Route::post('/holidays', 'storeHoliday')->name('holidays.store');
        Route::delete('/holidays/{holiday}', 'destroyHoliday')->name('holidays.destroy');
    });

    Route::controller(AdminReport::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/print', 'print')->name('print');
        Route::get('/pdf', 'pdf')->name('pdf');
        Route::get('/excel', 'excel')->name('excel');
    });
});

/* ===================== PEGAWAI ===================== */
Route::middleware(['auth', 'role:pegawai'])->prefix('pegawai')->name('pegawai.')->group(function () {
    Route::get('/dashboard', PegawaiDashboard::class)->name('dashboard');
    Route::get('/schedules', [PegawaiSchedule::class, 'index'])->name('schedules.index');

    Route::controller(PegawaiScheduleRequest::class)->prefix('schedule-requests')->name('schedule-requests.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::patch('/{scheduleRequest}/cancel', 'cancel')->name('cancel');
    });

    Route::controller(PegawaiAttendance::class)->prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{schedule}/check-in', 'checkIn')->name('check-in');
        Route::post('/{schedule}/check-out', 'checkOut')->name('check-out');
    });

    Route::prefix('requests/{type}')->where(['type' => 'leave|overtime', 'id' => '[0-9]+'])
        ->name('requests.')->controller(RequestController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::patch('/{id}/cancel', 'cancel')->name('cancel');
        });

    Route::get('/announcements', [PegawaiAnnouncement::class, 'index'])->name('announcements.index');

    Route::controller(PegawaiReport::class)->prefix('report')->name('report.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/print', 'print')->name('print');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

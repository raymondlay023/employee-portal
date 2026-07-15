<?php

use App\Authorization\Permissions;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveRequestController;
use App\Livewire\Hr\AttendanceReport;
use App\Livewire\Hr\AttendanceReportDetail;
use App\Livewire\System\ApiLogs;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

    Route::view('daily-work-logs', 'daily-work-logs')->name('daily-work-logs.index');

    Route::resource('employees', EmployeeController::class);

    Route::get('system/api-logs', ApiLogs::class)
        ->name('system.api-logs')
        ->middleware('can:'.Permissions::VIEW_API_LOGS);

    // Attendance
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');

    Route::get('attendance-report', AttendanceReport::class)
        ->name('attendance-report')
        ->middleware('can:'.Permissions::VIEW_ANY_ATTENDANCE);

    Route::get('attendance-report/{employee}', AttendanceReportDetail::class)
        ->name('attendance-report.detail')
        ->middleware('can:'.Permissions::VIEW_ANY_ATTENDANCE);

    // Leave requests
    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::resource('leave-requests', LeaveRequestController::class);
});

require __DIR__.'/auth.php';

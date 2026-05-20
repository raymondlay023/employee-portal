<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveRequestController;

Route::redirect('/', '/login');

Route::middleware(['auth','verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

    Route::resource('employees', EmployeeController::class);

    // Attendance
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');

    // Leave requests
    Route::resource('leave-requests', LeaveRequestController::class);
});

require __DIR__.'/auth.php';

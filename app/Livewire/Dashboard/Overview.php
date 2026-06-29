<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\DailyWorkLog;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class Overview extends Component
{
    public function render()
    {
        $user = auth()->user();
        $employee = $user->employee;
        
        // Attendance logs status
        $todayLog = null;
        if ($employee) {
            $todayLog = AttendanceLog::where('employee_id', $employee->id)
                ->whereNull('clock_out_at')
                ->first();
        }
        
        // Personal leave requests
        $pendingLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
            
        // Admin overview check
        $isAdminOrHR = $user->hasRole('Admin') || $user->hasRole('HR') || $user->hasRole('Manager');

        $isDefaultPassword = Hash::check('12345678', $user->password);
        $isFallbackEmail = str_contains($user->email ?? '', '@employee-portal.local');
        $needsSecurityUpdate = $isDefaultPassword || $isFallbackEmail;

        // Daily Work Logs calculations
        $todayWorkLogs = DailyWorkLog::where('user_id', $user->id)
            ->where('date', now()->toDateString())
            ->orderBy('start_time', 'asc')
            ->get();
            
        $todayTotalHours = 0;
        foreach ($todayWorkLogs as $wlog) {
            if (!empty($wlog->start_time) && !empty($wlog->end_time)) {
                try {
                    $start = Carbon::createFromFormat('H:i', substr($wlog->start_time, 0, 5));
                    $end = Carbon::createFromFormat('H:i', substr($wlog->end_time, 0, 5));
                    if ($end->greaterThan($start)) {
                        $todayTotalHours += ($end->timestamp - $start->timestamp) / 3600;
                    }
                } catch (\Exception $e) {}
            }
        }
        
        $todayPercentage = min(100, ($todayTotalHours / 8) * 100);
        $todayIsComplete = $todayTotalHours >= 8;
        $todayFormattedHours = number_format($todayTotalHours, $todayTotalHours == (int)$todayTotalHours ? 0 : 1);

        // Admin stats
        $activeEmployeesCount = $isAdminOrHR ? Employee::active()->count() ?? 0 : 0;
        
        $activeLeavesTodayCount = $isAdminOrHR ? LeaveRequest::where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->where('status', 'approved')
            ->count() : 0;
            
        $presentTodayCount = $isAdminOrHR ? AttendanceLog::whereDate('clock_in_at', now()->toDateString())->count() : 0;

        return view('livewire.dashboard.overview', compact(
            'user', 'employee', 'todayLog', 'pendingLeaves', 'isAdminOrHR',
            'isDefaultPassword', 'isFallbackEmail', 'needsSecurityUpdate',
            'todayWorkLogs', 'todayTotalHours', 'todayPercentage', 'todayIsComplete', 'todayFormattedHours',
            'activeEmployeesCount', 'activeLeavesTodayCount', 'presentTodayCount'
        ));
    }
}

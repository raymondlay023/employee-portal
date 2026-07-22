<?php

namespace App\Livewire\Dashboard;

use App\Authorization\Permissions;
use App\Models\AttendanceLog;
use App\Models\DailyWorkLog;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Overview extends Component
{
    public function render()
    {
        $user = auth()->user();
        $employee = $user->employee;

        // Attendance logs status
        $todayLog = null;
        if ($employee) {
            $todayLog = AttendanceLog::where('employee_id', $employee->employee_id)
                ->whereNull('clock_out_at')
                ->first();
        }

        // Personal leave requests
        $pendingLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // Admin/HR overview check
        $isManagerial = $user->can(Permissions::ACCESS_HR_PORTAL);

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
            if (! empty($wlog->start_time) && ! empty($wlog->end_time)) {
                try {
                    $start = Carbon::createFromFormat('H:i', substr($wlog->start_time, 0, 5));
                    $end = Carbon::createFromFormat('H:i', substr($wlog->end_time, 0, 5));
                    if ($end->greaterThan($start)) {
                        $todayTotalHours += ($end->timestamp - $start->timestamp) / 3600;
                    }
                } catch (\Exception $e) {
                }
            }
        }

        $todayPercentage = min(100, ($todayTotalHours / 8) * 100);
        $todayIsComplete = $todayTotalHours >= 8;
        $todayFormattedHours = number_format($todayTotalHours, $todayTotalHours == (int) $todayTotalHours ? 0 : 1);

        // Admin stats
        $activeEmployeesCount = 0;
        $pendingLeavesCount = 0;
        $presentTodayCount = 0;
        $isManagerWithoutDept = false;

        if ($isManagerial) {
            $isGlobalManager = $user->hasRole('HR') || $user->hasRole('Admin');

            if (! $isGlobalManager && (! $employee || ! $employee->department_id)) {
                $isManagerWithoutDept = true;
            } else {
                $deptId = $isGlobalManager ? null : $employee->department_id;

                // Active employees count
                $activeEmployeesQuery = Employee::active();
                if (! $isGlobalManager) {
                    $activeEmployeesQuery->where('department_id', $deptId);
                }
                $activeEmployeesCount = $activeEmployeesQuery->count() ?? 0;

                // Pending leaves count
                $pendingLeavesQuery = LeaveRequest::where('status', 'pending');
                if (! $isGlobalManager) {
                    $pendingLeavesQuery->whereHas('user.employee', function ($q) use ($deptId) {
                        $q->where('department_id', $deptId);
                    });
                }
                $pendingLeavesCount = $pendingLeavesQuery->count() ?? 0;

                // Present today count
                $todayStr = now()->toDateString();
                $manualPresentQuery = AttendanceLog::whereDate('clock_in_at', $todayStr);
                $jpayrollPresentQuery = JPayrollAttendance::where('shift_date', $todayStr)
                    ->where('alpha', 0)
                    ->where('sakit', 0)
                    ->where('izin', 0);

                if (! $isGlobalManager) {
                    $manualPresentQuery->whereHas('employee', function ($q) use ($deptId) {
                        $q->where('department_id', $deptId);
                    });
                    $jpayrollPresentQuery->whereHas('employee', function ($q) use ($deptId) {
                        $q->where('department_id', $deptId);
                    });
                }

                $manualPresent = $manualPresentQuery->with('employee')->get()->pluck('employee.id')->filter()->unique()->toArray();
                $jpayrollPresent = $jpayrollPresentQuery->pluck('employee_id')->toArray();
                $presentTodayCount = count(array_unique(array_merge($manualPresent, $jpayrollPresent)));
            }
        }

        // Work Log Reminders & Missing Workday checks
        $todayStr = now()->toDateString();
        $hasClockedInToday = false;
        if ($employee) {
            $hasClockedInToday = AttendanceLog::where('employee_id', $employee->employee_id)
                ->whereDate('clock_in_at', $todayStr)
                ->exists();
        }

        $needsWorkLogToday = $hasClockedInToday && $todayWorkLogs->isEmpty();

        $missingPastDates = [];
        if ($employee) {
            $pastDays = collect(range(1, 7))->map(fn ($i) => now()->subDays($i)->toDateString());

            foreach ($pastDays as $dateStr) {
                $wasPresent = AttendanceLog::where('employee_id', $employee->employee_id)
                    ->whereDate('clock_in_at', $dateStr)
                    ->exists()
                    || JPayrollAttendance::where('employee_id', $employee->id)
                        ->whereDate('shift_date', $dateStr)
                        ->where('alpha', 0)
                        ->where('sakit', 0)
                        ->where('izin', 0)
                        ->exists();

                if ($wasPresent) {
                    $hasWorkLogs = DailyWorkLog::where('user_id', $user->id)
                        ->where('date', $dateStr)
                        ->exists();

                    if (! $hasWorkLogs) {
                        $missingPastDates[] = $dateStr;
                    }
                }
            }
        }

        return view('livewire.dashboard.overview', compact(
            'user', 'employee', 'todayLog', 'pendingLeaves', 'isManagerial',
            'isDefaultPassword', 'isFallbackEmail', 'needsSecurityUpdate',
            'todayWorkLogs', 'todayTotalHours', 'todayPercentage', 'todayIsComplete', 'todayFormattedHours',
            'activeEmployeesCount', 'pendingLeavesCount', 'presentTodayCount', 'isManagerWithoutDept',
            'needsWorkLogToday', 'missingPastDates'
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Authorization\Permissions;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AttendanceController extends Controller
{
    /**
     * Show the attendance page with both manual logs and JPayroll synced data.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Validate optional filter parameters
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', 'in:all,present,absent,late,leave,sick'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();

        // ── JPayroll attendance records ───────────────────────────────────────
        $jpayrollQuery = JPayrollAttendance::with('employee')
            ->whereBetween('shift_date', [$dateFrom, $dateTo])
            ->orderBy('shift_date', 'desc');

        $targetEmployeeId = null;

        if (! empty($validated['employee_id'])) {
            $requestedEmployeeId = $validated['employee_id'];

            if ($user->can(Permissions::MANAGE_ATTENDANCE)) {
                $targetEmployeeId = $requestedEmployeeId;
            } elseif ($user->can(Permissions::VIEW_ANY_ATTENDANCE)) {
                // Check if the requested employee belongs to the manager's department
                $requestedEmployee = Employee::find($requestedEmployeeId);
                $managerDepartmentId = $employee?->department_id;

                if ($requestedEmployee && $managerDepartmentId && $requestedEmployee->department_id === $managerDepartmentId) {
                    $targetEmployeeId = $requestedEmployeeId;
                } else {
                    abort(403, 'You are only authorized to view attendance logs for employees in your department.');
                }
            } else {
                abort(403, 'Unauthorized action.');
            }
        }

        if ($targetEmployeeId === null) {
            if (! $employee) {
                abort(403, 'Employee profile not linked to your account. Please contact an administrator.');
            }
            $targetEmployeeId = $employee->id;
        }

        $targetEmployee = Employee::findOrFail($targetEmployeeId);
        $jpayrollQuery->where('employee_id', $targetEmployeeId);

        // ── Calculate Summary (Always single employee targeted now) ───────
        $summaryQuery = clone $jpayrollQuery;
        // Remove orderBy for the aggregate query
        $summaryQuery->getQuery()->orders = null;

        $summary = $summaryQuery->selectRaw('
            COUNT(*) as total_days,
            SUM(CASE WHEN alpha > 0 THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN alpha <= 0 AND sakit > 0 THEN 1 ELSE 0 END) as sick_days,
            SUM(CASE WHEN alpha <= 0 AND sakit = 0 AND izin > 0 THEN 1 ELSE 0 END) as leave_days,
            SUM(CASE WHEN alpha <= 0 AND sakit = 0 AND izin <= 0 AND telat > 0 THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN alpha = 0 AND sakit = 0 AND izin = 0 THEN 1 ELSE 0 END) as present_days
        ')->first();

        // Apply advanced status filters
        if (! empty($validated['status']) && $validated['status'] !== 'all') {
            $status = $validated['status'];
            if ($status === 'present') {
                $jpayrollQuery->where('alpha', 0)
                    ->where('izin', 0)
                    ->where('sakit', 0);
            } elseif ($status === 'absent') {
                $jpayrollQuery->where('alpha', '>', 0);
            } elseif ($status === 'late') {
                $jpayrollQuery->where('telat', '>', 0);
            } elseif ($status === 'leave') {
                $jpayrollQuery->where('izin', '>', 0);
            } elseif ($status === 'sick') {
                $jpayrollQuery->where('sakit', '>', 0);
            }
        }

        $jpayrollLogs = $jpayrollQuery->paginate(20, ['*'], 'jp_page');

        // ── Manual clock-in / clock-out logs ─────────────────────────────────
        $manualLogs = collect();
        if ($employee) {
            $manualLogs = AttendanceLog::where('employee_id', $employee->id)
                ->latest()
                ->paginate(15, ['*'], 'manual_page');
        }

        // Last JPayroll sync timestamp
        $lastSync = Cache::get('jpayroll_attendance_last_sync');

        return view('attendance.index', compact(
            'jpayrollLogs',
            'manualLogs',
            'dateFrom',
            'dateTo',
            'lastSync',
            'targetEmployee',
            'summary',
        ));
    }

    /**
     * Clock in the authenticated employee.
     */
    public function clockIn(Request $request)
    {
        if (! config('app.enable_manual_attendance')) {
            return redirect()->back()->with('error', 'Manual attendance is temporarily disabled.');
        }

        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return back()->with('error', 'Employee profile not found.');
        }

        return Cache::lock('attendance_clock_'.$employee->id, 5)->get(function () use ($employee) {
            // Check for any unclosed sessions
            $openSessions = AttendanceLog::where('employee_id', $employee->id)->whereNull('clock_out_at')->get();

            foreach ($openSessions as $session) {
                // If the session is from a previous day, auto-close it 8 hours after clock in
                if (! $session->clock_in_at->isToday()) {
                    $session->update([
                        'clock_out_at' => $session->clock_in_at->copy()->addHours(8),
                        'note' => ltrim($session->note.' (Auto-closed by system)'),
                    ]);
                } else {
                    // Session is from today, they are already clocked in
                    return back()->with('error', 'You are already clocked in.');
                }
            }

            AttendanceLog::create([
                'employee_id' => $employee->id,
                'clock_in_at' => now(),
            ]);

            return back()->with('success', 'Clocked in at '.now()->timezone('Asia/Jakarta')->format('H:i:s'));
        });
    }

    /**
     * Clock out the authenticated employee.
     */
    public function clockOut(Request $request)
    {
        if (! config('app.enable_manual_attendance')) {
            return redirect()->back()->with('error', 'Manual attendance is temporarily disabled.');
        }

        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return back()->with('error', 'Employee profile not found.');
        }

        return Cache::lock('attendance_clock_'.$employee->id, 5)->get(function () use ($employee) {
            $open = AttendanceLog::where('employee_id', $employee->id)
                ->whereNull('clock_out_at')
                ->whereDate('clock_in_at', today())
                ->first();

            if (! $open) {
                return back()->with('error', 'No active clock-in found for today.');
            }

            $open->update(['clock_out_at' => now()]);

            return back()->with('success', 'Clocked out at '.now()->timezone('Asia/Jakarta')->format('H:i:s'));
        });
    }
}

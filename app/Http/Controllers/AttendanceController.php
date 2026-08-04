<?php

namespace App\Http\Controllers;

use App\Authorization\Permissions;
use App\Models\ApiSyncLog;
use App\Models\AttendanceLog;
use App\Models\DailyWorkLog;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2020,2030'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', 'in:all,present,absent,late,leave,sick'],
        ]);

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
                return view('attendance.index', [
                    'jpayrollLogs' => collect(),
                    'manualLogs' => collect(),
                    'combinedLogs' => collect(),
                    'todayLog' => null,
                    'month' => (int) $request->input('month', now()->month),
                    'year' => (int) $request->input('year', now()->year),
                    'availableMonths' => range(1, 12),
                    'availableYears' => [now()->year],
                    'targetEmployee' => null,
                    'summary' => null,
                    'lastSync' => null,
                    'jpayrollLastSync' => null,
                    'biometricLastSync' => null,
                    'unlinkedProfileError' => true,
                ]);
            }
            $targetEmployeeId = $employee->id;
        }

        $targetEmployee = Employee::findOrFail($targetEmployeeId);

        // Fetch distinct years with data for the target employee
        $jpYears = JPayrollAttendance::where('employee_id', $targetEmployeeId)
            ->selectRaw('DISTINCT YEAR(shift_date) as year')
            ->pluck('year')
            ->toArray();

        $manualYears = AttendanceLog::where('employee_id', $targetEmployee->employee_id)
            ->selectRaw('DISTINCT YEAR(clock_in_at) as year')
            ->pluck('year')
            ->toArray();

        $availableYears = array_unique(array_merge($jpYears, $manualYears));
        sort($availableYears);

        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        $year = (int) $request->input('year', now()->year);
        if (! in_array($year, $availableYears)) {
            $year = end($availableYears);
        }

        // Fetch distinct months for the selected year
        $jpMonths = JPayrollAttendance::where('employee_id', $targetEmployeeId)
            ->whereYear('shift_date', $year)
            ->selectRaw('DISTINCT MONTH(shift_date) as month')
            ->pluck('month')
            ->toArray();

        $manualMonths = AttendanceLog::where('employee_id', $targetEmployee->employee_id)
            ->whereYear('clock_in_at', $year)
            ->selectRaw('DISTINCT MONTH(clock_in_at) as month')
            ->pluck('month')
            ->toArray();

        $availableMonths = array_unique(array_merge($jpMonths, $manualMonths));
        sort($availableMonths);

        if (empty($availableMonths)) {
            $availableMonths = [now()->month];
        }

        $month = (int) $request->input('month', now()->month);
        if (! in_array($month, $availableMonths)) {
            $month = end($availableMonths);
        }

        $selectedDate = now()->setDate($year, $month, 1);
        $startDate = $selectedDate->copy()->startOfMonth()->toDateString();
        $endDate = $selectedDate->copy()->endOfMonth()->toDateString();

        // ── JPayroll attendance records ───────────────────────────────────────
        $jpayrollQuery = JPayrollAttendance::with('employee')
            ->join('employees', 'jpayroll_attendances.employee_id', '=', 'employees.id')
            ->leftJoin(DB::raw('(SELECT employee_id, DATE(clock_in_at) as punch_date FROM attendance_logs GROUP BY employee_id, DATE(clock_in_at)) as logs'), function ($join) {
                $join->on('logs.employee_id', '=', 'employees.employee_id')
                    ->on('logs.punch_date', '=', 'jpayroll_attendances.shift_date');
            })
            ->select('jpayroll_attendances.*')
            ->where('jpayroll_attendances.employee_id', $targetEmployeeId)
            ->whereBetween('jpayroll_attendances.shift_date', [$startDate, $endDate])
            ->orderBy('jpayroll_attendances.shift_date', 'desc');

        // ── Calculate Summary (Always single employee targeted now) ───────
        $summaryQuery = clone $jpayrollQuery;
        // Remove orderBy and columns for the aggregate query
        $summaryQuery->getQuery()->orders = null;
        $summaryQuery->getQuery()->columns = null;

        $summary = $summaryQuery->selectRaw('
            COUNT(jpayroll_attendances.id) as total_days,
            SUM(CASE WHEN jpayroll_attendances.alpha > 0 THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit > 0 THEN 1 ELSE 0 END) as sick_days,
            SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin > 0 THEN 1 ELSE 0 END) as leave_days,
            SUM(CASE WHEN jpayroll_attendances.alpha = 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin = 0 AND jpayroll_attendances.telat > 0 AND logs.employee_id IS NOT NULL THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN jpayroll_attendances.alpha = 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin = 0 AND logs.employee_id IS NOT NULL THEN 1 ELSE 0 END) as present_days
        ')->first();

        // Apply advanced status filters
        if (! empty($validated['status']) && $validated['status'] !== 'all') {
            $status = $validated['status'];
            if ($status === 'present') {
                $jpayrollQuery->where('jpayroll_attendances.alpha', 0)
                    ->where('jpayroll_attendances.izin', 0)
                    ->where('jpayroll_attendances.sakit', 0)
                    ->whereNotNull('logs.employee_id');
            } elseif ($status === 'absent') {
                $jpayrollQuery->where('jpayroll_attendances.alpha', '>', 0);
            } elseif ($status === 'late') {
                $jpayrollQuery->where('jpayroll_attendances.telat', '>', 0);
            } elseif ($status === 'leave') {
                $jpayrollQuery->where('jpayroll_attendances.izin', '>', 0);
            } elseif ($status === 'sick') {
                $jpayrollQuery->where('jpayroll_attendances.sakit', '>', 0);
            }
        }

        $jpayrollLogs = $jpayrollQuery->get();

        // ── Manual clock-in / clock-out logs ─────────────────────────────────
        $manualLogs = collect();
        $todayLog = null;
        if ($employee) {
            $manualLogs = AttendanceLog::where('employee_id', $targetEmployee->employee_id)
                ->whereBetween('clock_in_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->orderBy('clock_in_at', 'desc')
                ->get();

            JPayrollAttendance::seedBiometricLogsCache($targetEmployee->employee_id, $manualLogs, $startDate, $endDate);

            $todayLog = AttendanceLog::where('employee_id', $employee->employee_id)
                ->whereNull('clock_out_at')
                ->whereDate('clock_in_at', today())
                ->first();
        }

        // ── Construct Unified Combined Logs ─────────────────────────────────
        $combinedLogs = collect();

        foreach ($jpayrollLogs as $jpLog) {
            $dateStr = $jpLog->shift_date->toDateString();
            $combinedLogs->put($dateStr, [
                'date' => $jpLog->shift_date,
                'type' => 'jpayroll',
                'jpayroll' => $jpLog,
                'biometric' => $jpLog->getBiometricLog(),
            ]);
        }

        $statusFilter = request('status', 'all');
        if ($statusFilter === 'all' || empty($statusFilter)) {
            foreach ($manualLogs as $manualLog) {
                if ($manualLog->clock_in_at) {
                    $dateStr = $manualLog->clock_in_at->toDateString();
                    if (! $combinedLogs->has($dateStr)) {
                        $combinedLogs->put($dateStr, [
                            'date' => $manualLog->clock_in_at->copy()->startOfDay(),
                            'type' => 'biometric_only',
                            'jpayroll' => null,
                            'biometric' => $manualLog,
                        ]);
                    }
                }
            }
        }

        $combinedLogs = $combinedLogs->sortByDesc(function ($item) {
            return $item['date']->timestamp;
        })->values();

        $groupedLogs = $combinedLogs->groupBy(function ($item) {
            $date = $item['date'];
            $firstDayOfMonth = $date->copy()->startOfMonth();
            $firstDayOffset = $firstDayOfMonth->dayOfWeekIso - 1;
            $dayOfMonth = $date->day - 1;
            $weekNumber = (int) floor(($dayOfMonth + $firstDayOffset) / 7) + 1;

            return 'Week '.$weekNumber;
        });

        // Last sync timestamps
        $jpayrollLastSync = ApiSyncLog::where('api_name', 'jpayroll_attendance')
            ->where('status', 'success')
            ->latest('ended_at')
            ->value('ended_at') ?? Cache::get('jpayroll_attendance_last_sync');

        $biometricLastSync = ApiSyncLog::where('api_name', 'biometric_device')
            ->where('status', 'success')
            ->latest('ended_at')
            ->value('ended_at') ?? AttendanceLog::latest('updated_at')->value('updated_at');

        $lastSync = $jpayrollLastSync;

        $workLogs = DailyWorkLog::where('user_id', $targetEmployee->user_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($log) => $log->date->toDateString());

        return view('attendance.index', compact(
            'jpayrollLogs',
            'manualLogs',
            'groupedLogs',
            'todayLog',
            'month',
            'year',
            'availableMonths',
            'availableYears',
            'lastSync',
            'jpayrollLastSync',
            'biometricLastSync',
            'targetEmployee',
            'summary',
            'workLogs'
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
            $openSessions = AttendanceLog::where('employee_id', $employee->employee_id)->whereNull('clock_out_at')->get();

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
                'employee_id' => $employee->employee_id,
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
            $open = AttendanceLog::where('employee_id', $employee->employee_id)
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

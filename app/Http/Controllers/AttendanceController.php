<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\JPayrollAttendance;
use App\Models\ApiSyncLog;

class AttendanceController extends Controller
{
    /**
     * Show the attendance page with both manual logs and JPayroll synced data.
     */
    public function index(Request $request)
    {
        $user     = Auth::user();
        $employee = $user->employee;

        // Validate optional filter parameters
        $validated = $request->validate([
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date', 'after_or_equal:date_from'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status'      => ['nullable', 'in:all,present,absent,late,leave,sick'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo   = $validated['date_to']   ?? now()->toDateString();

        // ── JPayroll attendance records ───────────────────────────────────────
        $jpayrollQuery = JPayrollAttendance::with('employee')
            ->whereBetween('shift_date', [$dateFrom, $dateTo])
            ->orderBy('shift_date', 'desc');

        // Employees only see their own records; HR/Admin see all or filtered
        if ($user->can('manage attendance')) {
            if (!empty($validated['employee_id'])) {
                $jpayrollQuery->where('employee_id', $validated['employee_id']);
            }
        } else {
            if ($employee) {
                $jpayrollQuery->where('employee_id', $employee->id);
            } else {
                $jpayrollQuery->whereRaw('1 = 0');
            }
        }

        // ── Calculate Summary (Only if a specific employee is targeted) ───────
        $summary = null;
        $isSingleEmployeeTargeted = (!$user->can('manage attendance') && $employee) 
                                 || ($user->can('manage attendance') && !empty($validated['employee_id']));
                                 
        if ($isSingleEmployeeTargeted) {
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
        }

        // Apply advanced status filters
        if (!empty($validated['status']) && $validated['status'] !== 'all') {
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

        $employees = collect();
        $syncLogs  = collect();
        if ($user->can('manage attendance') || $user->can('sync attendance')) {
            $employees = Employee::orderBy('first_name')->orderBy('last_name')->get();
        }
        if ($user->can('sync attendance')) {
            $syncLogs  = ApiSyncLog::with('triggeredBy')
                ->where('api_name', 'jpayroll_attendance')
                ->orderBy('started_at', 'desc')
                ->take(10)
                ->get();
        }

        return view('attendance.index', compact(
            'jpayrollLogs',
            'manualLogs',
            'dateFrom',
            'dateTo',
            'lastSync',
            'employees',
            'syncLogs',
            'summary',
        ));
    }

    /**
     * Clock in the authenticated employee.
     */
    public function clockIn(Request $request)
    {
        $user     = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return back()->with('error', 'Employee profile not found.');
        }

        return Cache::lock('attendance_clock_' . $employee->id, 5)->get(function () use ($employee) {
            // Check for any unclosed sessions
            $openSessions = AttendanceLog::where('employee_id', $employee->id)->whereNull('clock_out_at')->get();

            foreach ($openSessions as $session) {
                // If the session is from a previous day, auto-close it 8 hours after clock in
                if (!$session->clock_in_at->isToday()) {
                    $session->update([
                        'clock_out_at' => $session->clock_in_at->copy()->addHours(8),
                        'note' => ltrim($session->note . ' (Auto-closed by system)')
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

            return back()->with('success', 'Clocked in at ' . now()->timezone('Asia/Jakarta')->format('H:i:s'));
        });
    }

    /**
     * Clock out the authenticated employee.
     */
    public function clockOut(Request $request)
    {
        $user     = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return back()->with('error', 'Employee profile not found.');
        }

        return Cache::lock('attendance_clock_' . $employee->id, 5)->get(function () use ($employee) {
            $open = AttendanceLog::where('employee_id', $employee->id)
                ->whereNull('clock_out_at')
                ->whereDate('clock_in_at', today())
                ->first();

            if (! $open) {
                return back()->with('error', 'No active clock-in found for today.');
            }

            $open->update(['clock_out_at' => now()]);

            return back()->with('success', 'Clocked out at ' . now()->timezone('Asia/Jakarta')->format('H:i:s'));
        });
    }

    /**
     * Trigger a JPayroll attendance sync manually (HR / Admin only).
     * Guarded by the 'sync attendance' permission via route middleware.
     */
    public function syncFromJPayroll(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'nik'       => ['nullable', 'string', 'max:20'],
        ]);

        $date1 = isset($validated['date_from']) ? \Carbon\Carbon::parse($validated['date_from'])->format('d/m/Y') : null;
        $date2 = isset($validated['date_to']) ? \Carbon\Carbon::parse($validated['date_to'])->format('d/m/Y') : null;

        $options = array_filter([
            '--date1'        => $date1,
            '--date2'        => $date2,
            '--nik'          => $validated['nik'] ?: null,
            '--trigger'      => 'manual',
            '--triggered-by' => Auth::id(),
        ]);

        // Push to background queue instead of running synchronously
        Artisan::queue('jpayroll:sync-attendance', $options);

        return back()->with('success', 'JPayroll attendance sync queued successfully. Please check the logs in a few moments.');
    }
}

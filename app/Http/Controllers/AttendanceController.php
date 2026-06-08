<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\JPayrollAttendance;

class AttendanceController extends Controller
{
    /**
     * Show the attendance page with both manual logs and JPayroll synced data.
     */
    public function index(Request $request)
    {
        $user     = Auth::user();
        $employee = $user->employee;

        // Validate optional date range filter
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo   = $validated['date_to']   ?? now()->toDateString();

        // ── JPayroll attendance records ───────────────────────────────────────
        $jpayrollQuery = JPayrollAttendance::with('employee')
            ->whereBetween('shift_date', [$dateFrom, $dateTo])
            ->orderBy('shift_date', 'desc');

        // Employees only see their own records; HR/Admin see all
        if ($employee && !$user->can('manage attendance')) {
            $jpayrollQuery->where('employee_id', $employee->id);
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

        // Prevent double clock-in without clock-out
        $open = AttendanceLog::where('employee_id', $employee->id)->whereNull('clock_out_at')->first();
        if ($open) {
            return back()->with('error', 'You are already clocked in.');
        }

        AttendanceLog::create([
            'employee_id' => $employee->id,
            'clock_in_at' => now(),
        ]);

        return back()->with('success', 'Clocked in at ' . now()->format('H:i:s'));
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

        $open = AttendanceLog::where('employee_id', $employee->id)->whereNull('clock_out_at')->first();
        if (! $open) {
            return back()->with('error', 'No active clock-in found.');
        }

        $open->update(['clock_out_at' => now()]);

        return back()->with('success', 'Clocked out at ' . now()->format('H:i:s'));
    }

    /**
     * Trigger a JPayroll attendance sync manually (HR / Admin only).
     * Guarded by the 'sync attendance' permission via route middleware.
     */
    public function syncFromJPayroll(Request $request)
    {
        $validated = $request->validate([
            'date1' => ['nullable', 'date_format:d/m/Y'],
            'date2' => ['nullable', 'date_format:d/m/Y'],
            'nik'   => ['nullable', 'string', 'max:20'],
        ]);

        $options = array_filter([
            '--date1' => $validated['date1'] ?? null,
            '--date2' => $validated['date2'] ?? null,
            '--nik'   => $validated['nik']   ?? null,
        ]);

        // Run synchronously for immediate feedback (acceptable for manual triggers)
        $exitCode = Artisan::call('jpayroll:sync-attendance', $options);

        if ($exitCode === 0) {
            return back()->with('success', 'JPayroll attendance sync completed successfully.');
        }

        return back()->with('error', 'JPayroll sync encountered an error. Please check the logs.');
    }
}

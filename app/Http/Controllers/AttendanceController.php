<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\AttendanceLog;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee;

        $logs = [];
        if ($employee) {
            $logs = AttendanceLog::where('employee_id', $employee->id)->latest()->paginate(15);
        }

        return view('attendance.index', compact('logs'));
    }

    public function clockIn(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return back()->with('error', 'Employee profile not found.');
        }

        // prevent double clock in without clock out
        $open = AttendanceLog::where('employee_id', $employee->id)->whereNull('clock_out_at')->first();
        if ($open) {
            return back()->with('error', 'You are already clocked in.');
        }

        $log = AttendanceLog::create([
            'employee_id' => $employee->id,
            'clock_in_at' => now(),
        ]);

        return back()->with('success', 'Clocked in at '.now());
    }

    public function clockOut(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return back()->with('error', 'Employee profile not found.');
        }

        $open = AttendanceLog::where('employee_id', $employee->id)->whereNull('clock_out_at')->first();
        if (! $open) {
            return back()->with('error', 'No active clock-in found.');
        }

        $open->update(['clock_out_at' => now()]);

        return back()->with('success', 'Clocked out at '.now());
    }
}

<?php

namespace App\Http\Controllers;

use App\Authorization\Permissions;
use App\Models\ApiSyncLog;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\JPayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdminOrHR = $user->can(Permissions::MANAGE_LEAVES);

        $query = LeaveRequest::query()->with('user.employee');

        if ($isAdminOrHR) {
            if ($request->get('scope') === 'personal') {
                $query->where('user_id', $user->id);
            } else {
                // If Manager (not HR or Admin), scope to department
                if (! $user->hasRole('HR') && ! $user->hasRole('Admin')) {
                    $deptId = $user->employee?->department_id;
                    $query->whereHas('user.employee', function ($q) use ($deptId) {
                        $q->where('department_id', $deptId);
                    });
                }

                if ($request->filled('status') && $request->status !== 'all') {
                    $query->where('status', $request->status);
                }
            }
        } else {
            $query->where('user_id', $user->id);
        }

        $leaveRequests = $query->latest()->paginate(15)->withQueryString();

        $annualLeave = null;
        $employeeId = $user->employee?->employee_id;
        $pendingLeaveDays = 0;
        $lastSyncedAt = null;

        // Fetch annual leave details from JPayroll API when viewing personal leaves
        if ($employeeId && (! $isAdminOrHR || $request->get('scope') === 'personal')) {
            try {
                $year = now()->format('Y');
                $forceRefresh = $request->has('refresh_leave');
                $annualLeave = $this->getCachedAnnualLeave($year, $employeeId, $user, $forceRefresh);
                $lastSyncedAt = cache()->get("jpayroll_annual_leave_last_sync_{$employeeId}_{$year}");

                // Calculate pending annual leave requests locally
                $pendingLeaveDays = LeaveRequest::where('user_id', $user->id)
                    ->where('type', 'annual')
                    ->where('status', 'pending')
                    ->get()
                    ->sum(function ($lr) {
                        return $lr->start_date->diffInDays($lr->end_date) + 1;
                    });
            } catch (\Exception $e) {
                Log::warning('Failed to fetch annual leave details', ['message' => $e->getMessage()]);
            }
        }

        return view('leave.index', compact('leaveRequests', 'isAdminOrHR', 'annualLeave', 'employeeId', 'pendingLeaveDays', 'lastSyncedAt'));
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        $this->authorizeManageLeaves($leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be approved.');
        }

        $leaveRequest->update(['status' => 'approved']);

        return back()->with('success', 'Leave request approved successfully.');
    }

    public function reject(LeaveRequest $leaveRequest)
    {
        $this->authorizeManageLeaves($leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be rejected.');
        }

        $leaveRequest->update(['status' => 'rejected']);

        return back()->with('success', 'Leave request rejected.');
    }

    private function authorizeManageLeaves(?LeaveRequest $leaveRequest = null)
    {
        $user = Auth::user();
        if (! $user->can(Permissions::MANAGE_LEAVES)) {
            abort(403, 'Unauthorized action.');
        }

        if ($leaveRequest && ! $user->hasRole('HR') && ! $user->hasRole('Admin')) {
            $managerDept = $user->employee?->department_id;
            $applicantDept = $leaveRequest->user->employee?->department_id;

            if (! $managerDept || $managerDept !== $applicantDept) {
                abort(403, 'You can only manage leave requests for employees in your department.');
            }
        }
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $employeeId = $user->employee?->employee_id;
        $annualLeave = null;
        $pendingLeaveDays = 0;
        $lastSyncedAt = null;

        if ($employeeId) {
            try {
                $year = now()->format('Y');
                $annualLeave = $this->getCachedAnnualLeave($year, $employeeId, $user);
                $lastSyncedAt = cache()->get("jpayroll_annual_leave_last_sync_{$employeeId}_{$year}");

                // Calculate pending annual leave requests locally
                $pendingLeaveDays = LeaveRequest::where('user_id', $user->id)
                    ->where('type', 'annual')
                    ->where('status', 'pending')
                    ->get()
                    ->sum(function ($lr) {
                        return $lr->start_date->diffInDays($lr->end_date) + 1;
                    });
            } catch (\Exception $e) {
                Log::warning('Failed to fetch annual leave details', ['message' => $e->getMessage()]);
            }
        }

        return view('leave.create', compact('annualLeave', 'employeeId', 'pendingLeaveDays', 'lastSyncedAt'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $data['user_id'] = Auth::id();
        $data['status'] = 'pending';

        LeaveRequest::create($data);

        return redirect()->route('leave-requests.index')->with('success', 'Leave request submitted');
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();
        $isAdminOrHR = $user->can(Permissions::MANAGE_LEAVES);
        $annualLeave = null;

        // Scope validation for non-HR/Admin viewing others' leaves
        if (! $user->hasRole('HR') && ! $user->hasRole('Admin') && $leaveRequest->user_id !== $user->id) {
            $managerDept = $user->employee?->department_id;
            $applicantDept = $leaveRequest->user->employee?->department_id;

            if (! $user->hasRole('Manager') || ! $managerDept || $managerDept !== $applicantDept) {
                abort(403, 'You are not authorized to view this leave request.');
            }
        }

        $employeeId = $leaveRequest->user->employee?->employee_id;

        if ($employeeId && ($isAdminOrHR || $leaveRequest->user_id === $user->id)) {
            try {
                $year = $leaveRequest->start_date->format('Y');
                $annualLeave = $this->getCachedAnnualLeave($year, $employeeId, $user);
            } catch (\Exception $e) {
                Log::warning('Failed to fetch annual leave details in show view', ['message' => $e->getMessage()]);
            }
        }

        return view('leave.show', compact('leaveRequest', 'annualLeave'));
    }

    public function edit(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();
        if ($leaveRequest->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        // only allow editing pending requests
        if ($leaveRequest->status !== 'pending') {
            abort(403);
        }

        return view('leave.edit', compact('leaveRequest'));
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $user = Auth::user();
        if ($leaveRequest->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($leaveRequest->status !== 'pending') {
            abort(403);
        }

        $data = $request->validate([
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $leaveRequest->update($data);

        return redirect()->route('leave-requests.index')->with('success', 'Leave request updated');
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();
        if ($leaveRequest->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($leaveRequest->status !== 'pending') {
            abort(403);
        }

        $leaveRequest->delete();

        return redirect()->route('leave-requests.index')->with('success', 'Leave request cancelled');
    }

    /**
     * Retrieve and cache annual leave balance from JPayroll API with dynamic failure throttling.
     */
    private function getCachedAnnualLeave(string $year, string $employeeId, User $user, bool $forceRefresh = false): ?array
    {
        $cacheKey = "jpayroll_annual_leave_{$employeeId}_{$year}";

        if ($forceRefresh) {
            cache()->forget($cacheKey);
            cache()->forget("jpayroll_annual_leave_last_sync_{$employeeId}_{$year}");
        }

        $cached = cache()->get($cacheKey);

        if ($cached === 'failed') {
            return null;
        }

        if ($cached !== null) {
            return $cached;
        }

        // Create API sync log
        $syncLog = ApiSyncLog::create([
            'api_name' => 'jpayroll_annual_leave',
            'trigger_type' => 'manual',
            'triggered_by_user_id' => $user->id,
            'parameters' => [
                'year' => $year,
                'employee_id' => $employeeId,
            ],
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $jpayroll = app(JPayrollService::class);
            $data = $jpayroll->fetchAnnualLeave($year, $employeeId);

            if ($data) {
                $syncLog->update([
                    'status' => 'success',
                    'ended_at' => now(),
                    'records_fetched' => 1,
                    'records_processed' => 1,
                ]);
                cache()->put($cacheKey, $data, 1800);
                cache()->put("jpayroll_annual_leave_last_sync_{$employeeId}_{$year}", now(), 1800);

                return $data;
            }

            $syncLog->update([
                'status' => 'failed',
                'ended_at' => now(),
                'error_message' => 'API returned empty data or error status',
            ]);
            cache()->put($cacheKey, 'failed', 60);

            return null;
        } catch (\Exception $e) {
            $syncLog->update([
                'status' => 'failed',
                'ended_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            cache()->put($cacheKey, 'failed', 60);

            return null;
        }
    }
}

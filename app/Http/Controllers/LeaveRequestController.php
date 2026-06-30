<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use App\Authorization\Permissions;

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
            } elseif ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
        } else {
            $query->where('user_id', $user->id);
        }

        $leaveRequests = $query->latest()->paginate(15)->withQueryString();

        return view('leave.index', compact('leaveRequests', 'isAdminOrHR'));
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        $this->authorizeManageLeaves();

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be approved.');
        }

        $leaveRequest->update(['status' => 'approved']);

        return back()->with('success', 'Leave request approved successfully.');
    }

    public function reject(LeaveRequest $leaveRequest)
    {
        $this->authorizeManageLeaves();

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be rejected.');
        }

        $leaveRequest->update(['status' => 'rejected']);

        return back()->with('success', 'Leave request rejected.');
    }

    private function authorizeManageLeaves()
    {
        $user = Auth::user();
        if (!$user->can(Permissions::MANAGE_LEAVES)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function create()
    {
        return view('leave.create');
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
        return view('leave.show', compact('leaveRequest'));
    }

    public function edit(LeaveRequest $leaveRequest)
    {
        // only allow editing pending requests
        if ($leaveRequest->status !== 'pending') {
            abort(403);
        }

        return view('leave.edit', compact('leaveRequest'));
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
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
        if ($leaveRequest->status !== 'pending') {
            abort(403);
        }

        $leaveRequest->delete();

        return redirect()->route('leave-requests.index')->with('success', 'Leave request cancelled');
    }
}

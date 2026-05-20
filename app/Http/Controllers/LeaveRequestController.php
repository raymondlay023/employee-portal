<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $leaveRequests = LeaveRequest::where('user_id', $user->id)->latest()->paginate(15);

        return view('leave.index', compact('leaveRequests'));
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

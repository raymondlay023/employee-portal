<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">
                    {{ request('scope') === 'personal' || !$isAdminOrHR ? 'My Leave Requests' : 'Leave Approvals' }}
                </h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">
                    {{ request('scope') === 'personal' || !$isAdminOrHR ? 'Submit and track your personal leave applications' : 'Manage company-wide leave requests and approvals' }}
                </p>
            </div>
            <div>
                <a href="{{ route('leave-requests.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all hover:-translate-y-0.5 active:translate-y-0 transform gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Leave Request
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Flash messages -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-2xl p-4 flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Filters (Admin/HR Only - Company Scope Only) -->
        @if($isAdminOrHR && request('scope', 'company') !== 'personal')
            <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-150 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status:</span>
                    <div class="flex gap-1">
                        @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
                            <a href="{{ route('leave-requests.index', ['scope' => 'company', 'status' => $val]) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ (request('status', 'all') === $val) ? 'bg-brand-50 text-brand-700 border border-brand-100 shadow-sm' : 'text-slate-600 hover:bg-slate-50 border border-transparent' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Data Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="bg-slate-50/60">
                            @if($isAdminOrHR && request('scope', 'company') !== 'personal')
                                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Employee</th>
                            @endif
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Period</th>
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-4 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-4 text-right text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($leaveRequests as $lr)
                            @php
                                $typeColor = match($lr->type) {
                                    'annual' => 'bg-blue-50 text-blue-700 border-blue-100',
                                    'sick' => 'bg-purple-50 text-purple-700 border-purple-100',
                                    default => 'bg-slate-50 text-slate-700 border-slate-100',
                                };
                                $statusColor = match($lr->status) {
                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200/60',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200/60',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/40 transition-colors group">
                                @if($isAdminOrHR && request('scope', 'company') !== 'personal')
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-gradient-to-br from-brand-100 to-brand-200 flex flex-shrink-0 items-center justify-center text-brand-700 font-extrabold text-xs border border-brand-200/50 shadow-inner">
                                                {{ $lr->user->employee->initials ?? '?' }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-slate-800 tracking-tight">
                                                    {{ $lr->user->employee->first_name ?? $lr->user->name }} {{ $lr->user->employee->last_name ?? '' }}
                                                </p>
                                                <p class="text-[10px] text-slate-400 font-semibold">
                                                    {{ $lr->user->employee->employee_id ?? 'No NIK' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold border uppercase tracking-wider {{ $typeColor }}">
                                        {{ $lr->type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-800">
                                            {{ $lr->start_date->format('d M Y') }} - {{ $lr->end_date->format('d M Y') }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-semibold mt-0.5">
                                            {{ $lr->start_date->diffInDays($lr->end_date) + 1 }} {{ Str::plural('day', $lr->start_date->diffInDays($lr->end_date) + 1) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs text-slate-600 max-w-xs truncate" title="{{ $lr->reason }}">
                                        {{ $lr->reason ?: '—' }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider {{ $statusColor }}">
                                        {{ $lr->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs text-slate-500 font-semibold">
                                        {{ $lr->created_at->format('d M Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($lr->status === 'pending')
                                            @if($isAdminOrHR)
                                                <!-- HR Approval Actions -->
                                                <form action="{{ route('leave-requests.approve', $lr) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center justify-center px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-100 rounded-lg text-[10px] font-extrabold transition-colors cursor-pointer uppercase tracking-wider">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('leave-requests.reject', $lr) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center justify-center px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-100 rounded-lg text-[10px] font-extrabold transition-colors cursor-pointer uppercase tracking-wider">
                                                        Reject
                                                    </button>
                                                </form>
                                            @else
                                                <!-- Employee Edit/Delete Actions -->
                                                <a href="{{ route('leave-requests.edit', $lr) }}" class="inline-flex items-center justify-center px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-150 rounded-lg text-[10px] font-extrabold transition-colors cursor-pointer uppercase tracking-wider">
                                                    Edit
                                                </a>
                                                <form action="{{ route('leave-requests.destroy', $lr) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center justify-center px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-100 rounded-lg text-[10px] font-extrabold transition-colors cursor-pointer uppercase tracking-wider">
                                                        Cancel
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <a href="{{ route('leave-requests.show', $lr) }}" class="inline-flex items-center justify-center px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-150 rounded-lg text-[10px] font-extrabold transition-colors cursor-pointer uppercase tracking-wider">
                                                View
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdminOrHR && request('scope', 'company') !== 'personal' ? 7 : 6 }}" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 text-slate-400">
                                        <svg class="w-10 h-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        <p class="text-xs font-bold">No leave requests found</p>
                                        <p class="text-[10px] text-slate-400/80">There are no applications matching your current criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($leaveRequests->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $leaveRequests->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

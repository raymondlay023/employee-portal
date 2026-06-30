<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">Leave Request Detail</h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Detailed view of the submitted leave application</p>
            </div>
            <div>
                <a href="{{ route('leave-requests.index') }}" 
                   class="inline-flex items-center justify-center px-4 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all cursor-pointer">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $typeColor = match($leaveRequest->type) {
            'annual' => 'bg-blue-50 text-blue-700 border-blue-100',
            'sick' => 'bg-purple-50 text-purple-700 border-purple-100',
            default => 'bg-slate-50 text-slate-700 border-slate-100',
        };
        $statusColor = match($leaveRequest->status) {
            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
            'rejected' => 'bg-red-50 text-red-700 border-red-200/60',
            default => 'bg-amber-50 text-amber-700 border-amber-200/60',
        };
        $isAdminOrHR = auth()->user()->can(\App\Authorization\Permissions::MANAGE_LEAVES);
    @endphp

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Header Status Banner -->
            <div class="p-6 sm:p-8 bg-slate-50/50 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider {{ $statusColor }}">
                        {{ $leaveRequest->status }}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-extrabold border uppercase tracking-wider {{ $typeColor }}">
                        {{ $leaveRequest->type }}
                    </span>
                </div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    Submitted {{ $leaveRequest->created_at->diffForHumans() }}
                </div>
            </div>

            <!-- Content Details -->
            <div class="p-6 sm:p-8 space-y-6">
                <!-- Employee Info (Only if HR/Admin) -->
                @if($isAdminOrHR)
                    <div class="space-y-2 pb-6 border-b border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Applicant</h4>
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-brand-100 to-brand-200 flex flex-shrink-0 items-center justify-center text-brand-700 font-extrabold text-sm border border-brand-200/50 shadow-inner">
                                {{ $leaveRequest->user->employee->initials ?? '?' }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800 tracking-tight">
                                    {{ $leaveRequest->user->employee->first_name ?? $leaveRequest->user->name }} {{ $leaveRequest->user->employee->last_name ?? '' }}
                                </p>
                                <p class="text-xs text-slate-500 font-semibold mt-0.5">
                                    {{ $leaveRequest->user->employee->employee_id ?? 'No NIK' }} &bull; {{ $leaveRequest->user->employee->department->name ?? 'No Department' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Period & Duration -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pb-6 border-b border-slate-100">
                    <div class="space-y-1">
                        <h4 class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Time Period</h4>
                        <p class="text-sm font-bold text-slate-800">
                            {{ $leaveRequest->start_date->format('d M Y') }} — {{ $leaveRequest->end_date->format('d M Y') }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Duration</h4>
                        <p class="text-sm font-bold text-slate-800">
                            {{ $leaveRequest->start_date->diffInDays($leaveRequest->end_date) + 1 }} {{ Str::plural('day', $leaveRequest->start_date->diffInDays($leaveRequest->end_date) + 1) }}
                        </p>
                    </div>
                </div>

                <!-- Reason -->
                <div class="space-y-2">
                    <h4 class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Reason / Description</h4>
                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 text-sm text-slate-700 leading-relaxed font-medium">
                        {{ $leaveRequest->reason ?: 'No reason provided.' }}
                    </div>
                </div>

                <!-- Actions -->
                @if($leaveRequest->status === 'pending')
                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                        @if($isAdminOrHR)
                            <form action="{{ route('leave-requests.reject', $leaveRequest) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center px-5 py-3 bg-red-50 hover:bg-red-100 text-red-700 border border-red-150 rounded-xl text-xs font-bold transition-colors cursor-pointer">
                                    Reject Request
                                </button>
                            </form>
                            <form action="{{ route('leave-requests.approve', $leaveRequest) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center px-5 py-3 bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all hover:-translate-y-0.5 active:translate-y-0 transform cursor-pointer">
                                    Approve Request
                                </button>
                            </form>
                        @else
                            <a href="{{ route('leave-requests.edit', $leaveRequest) }}" 
                               class="inline-flex items-center justify-center px-5 py-3 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all cursor-pointer">
                                Edit Request
                              </a>
                            <form action="{{ route('leave-requests.destroy', $leaveRequest) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center px-5 py-3 bg-red-50 hover:bg-red-100 text-red-600 border border-red-100 rounded-xl text-xs font-bold transition-colors cursor-pointer">
                                    Cancel Request
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

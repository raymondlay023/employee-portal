<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">
                    {{ request('scope') === 'personal' || !$isAdminOrHR ? __('My Leave Requests') : __('Leave Approvals') }}
                </h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">
                    {{ request('scope') === 'personal' || !$isAdminOrHR ? __('Submit and track your personal leave applications') : __('Manage company-wide leave requests and approvals') }}
                </p>
            </div>
            <div>
                <a href="{{ route('leave-requests.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all hover:-translate-y-0.5 active:translate-y-0 transform gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('New Leave Request') }}
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

        <!-- Annual Leave Balance Cards (Personal Scope Only) -->
        @if(request('scope') === 'personal' || !$isAdminOrHR)
            @if($employeeId)
                @if($annualLeave)
                    @php
                        $quota = $annualLeave['Balance'] ?? 0;
                        $taken = $annualLeave['Posted'] ?? 0;
                        $pending = $pendingLeaveDays ?? 0;
                        $remaining = $annualLeave['Remain'] ?? 0;
                        $year = $annualLeave['Year'] ?? now()->format('Y');
                    @endphp
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Annual Leave Summary (Year: :year)', ['year' => $year]) }}</span>
                                @if($lastSyncedAt)
                                    <span class="text-[9px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ __('Synced :time', ['time' => $lastSyncedAt->diffForHumans()]) }}</span>
                                @endif
                            </div>
                            <a href="{{ request()->fullUrlWithQuery(['refresh_leave' => 1]) }}" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-brand-600 hover:text-brand-700 bg-brand-50 hover:bg-brand-100/80 px-2.5 py-1.5 rounded-lg transition-all cursor-pointer">
                                <svg class="w-3.5 h-3.5 hover:rotate-180 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                {{ __('Sync with JPayroll') }}
                            </a>
                        </div>
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Quota -->
                            <div class="bg-gradient-to-br from-white to-slate-50/50 p-5 rounded-3xl border border-slate-200/80 shadow-soft flex items-center gap-4 hover:shadow-md transition-shadow">
                                <div class="p-3.5 bg-blue-50 text-blue-600 rounded-2xl border border-blue-100 flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Leave Quota') }}</p>
                                    <p class="text-xl font-black text-slate-800 mt-0.5">{{ __($quota != 1 ? ':count days' : ':count day', ['count' => $quota]) }}</p>
                                </div>
                            </div>
                            
                            <!-- Taken -->
                            <div class="bg-gradient-to-br from-white to-slate-50/50 p-5 rounded-3xl border border-slate-200/80 shadow-soft flex items-center gap-4 hover:shadow-md transition-shadow">
                                <div class="p-3.5 bg-purple-50 text-purple-600 rounded-2xl border border-purple-100 flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Leave Taken') }}</p>
                                    <p class="text-xl font-black text-slate-800 mt-0.5">{{ __($taken != 1 ? ':count days' : ':count day', ['count' => $taken]) }}</p>
                                </div>
                            </div>

                            <!-- Pending -->
                            <div class="bg-gradient-to-br from-white to-slate-50/50 p-5 rounded-3xl border border-slate-200/80 shadow-soft flex items-center gap-4 hover:shadow-md transition-shadow">
                                <div class="p-3.5 bg-amber-50 text-amber-600 rounded-2xl border border-amber-100 flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Pending Approval') }}</p>
                                    <p class="text-xl font-black text-slate-800 mt-0.5">{{ __($pending != 1 ? ':count days' : ':count day', ['count' => $pending]) }}</p>
                                </div>
                            </div>

                            <!-- Remaining / Available -->
                            <div class="bg-gradient-to-br from-brand-500 to-brand-600 p-5 rounded-3xl border border-brand-600 shadow-soft flex items-center gap-4 hover:shadow-md transition-shadow relative overflow-hidden group">
                                <div class="absolute -right-3 -top-3 w-16 h-16 bg-white/10 rounded-full blur-xl group-hover:scale-125 transition-transform"></div>
                                <div class="p-3.5 bg-white/15 text-white rounded-2xl border border-white/20 flex-shrink-0 z-10">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l8.904-4.43m-8.904-.666L2.25 12l13.904-6.222 4.43 8.904" />
                                    </svg>
                                </div>
                                <div class="min-w-0 z-10 text-white">
                                    <p class="text-[10px] font-extrabold text-white/70 uppercase tracking-wider">{{ __('Available Balance') }}</p>
                                    <p class="text-xl font-black mt-0.5">{{ __($remaining != 1 ? ':count days' : ':count day', ['count' => $remaining]) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- JPayroll Connection Error Fallback -->
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <h4 class="text-xs font-bold text-amber-800 uppercase tracking-wider">{{ __('JPayroll Sync Unreachable') }}</h4>
                                <p class="text-[10px] text-amber-700/90 font-semibold mt-0.5">{{ __('We could not fetch your real-time annual leave balance. Please try syncing again.') }}</p>
                            </div>
                        </div>
                        <a href="{{ request()->fullUrlWithQuery(['refresh_leave' => 1]) }}" class="inline-flex items-center gap-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold shadow-sm transition-colors cursor-pointer self-start sm:self-auto">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            {{ __('Retry Sync') }}
                        </a>
                    </div>
                @endif
            @else
                <!-- NIK Configuration Fallback -->
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">{{ __('Leave Balance Offline') }}</h4>
                        <p class="text-[10px] text-slate-500 font-semibold mt-0.5">{{ __('Please ensure your Employee ID (NIK) is properly configured in your profile to sync with JPayroll.') }}</p>
                    </div>
                </div>
            @endif
        @endif

        <!-- Filters (Admin/HR Only - Company Scope Only) -->
        @if($isAdminOrHR && request('scope', 'company') !== 'personal')
            <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-150 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Status:') }}</span>
                    <div class="flex gap-1">
                        @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
                            <a href="{{ route('leave-requests.index', ['scope' => 'company', 'status' => $val]) }}" 
                               class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ (request('status', 'all') === $val) ? 'bg-brand-50 text-brand-700 border border-brand-100 shadow-sm' : 'text-slate-600 hover:bg-slate-50 border border-transparent' }}">
                                {{ __($label) }}
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
                                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Employee') }}</th>
                            @endif
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Type') }}</th>
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Period') }}</th>
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Reason') }}</th>
                            <th class="px-6 py-4 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Submitted') }}</th>
                            <th class="px-6 py-4 text-right text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Actions') }}</th>
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
                                                    {{ $lr->user->employee->employee_id ?? __('No NIK') }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold border uppercase tracking-wider {{ $typeColor }}">
                                        {{ __($lr->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-800">
                                            {{ $lr->start_date->translatedFormat('d M Y') }} - {{ $lr->end_date->translatedFormat('d M Y') }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-semibold mt-0.5">
                                            {{ $lr->start_date->diffInDays($lr->end_date) + 1 }} {{ $lr->start_date->diffInDays($lr->end_date) + 1 == 1 ? __('day') : __('days') }}
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
                                        {{ __($lr->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs text-slate-500 font-semibold">
                                        {{ $lr->created_at->translatedFormat('d M Y') }}
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
                                                        {{ __('Approve') }}
                                                    </button>
                                                </form>
                                                <form action="{{ route('leave-requests.reject', $lr) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center justify-center px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-100 rounded-lg text-[10px] font-extrabold transition-colors cursor-pointer uppercase tracking-wider">
                                                        {{ __('Reject') }}
                                                    </button>
                                                </form>
                                            @else
                                                <!-- Employee Edit/Delete Actions -->
                                                <a href="{{ route('leave-requests.edit', $lr) }}" class="inline-flex items-center justify-center px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-150 rounded-lg text-[10px] font-extrabold transition-colors cursor-pointer uppercase tracking-wider">
                                                    {{ __('Edit') }}
                                                </a>
                                                <form action="{{ route('leave-requests.destroy', $lr) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to cancel this request?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center justify-center px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-100 rounded-lg text-[10px] font-extrabold transition-colors cursor-pointer uppercase tracking-wider">
                                                        {{ __('Cancel') }}
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <a href="{{ route('leave-requests.show', $lr) }}" class="inline-flex items-center justify-center px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-150 rounded-lg text-[10px] font-extrabold transition-colors cursor-pointer uppercase tracking-wider">
                                                {{ __('View') }}
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
                                        <p class="text-xs font-bold">{{ __('No leave requests found') }}</p>
                                        <p class="text-[10px] text-slate-400/80">{{ __('There are no applications matching your current criteria.') }}</p>
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

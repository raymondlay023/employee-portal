<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-900 tracking-tight">
                {{ __('Hello, ') . auth()->user()->name }}
            </h2>
            <div class="text-sm text-gray-500 font-medium">
                {{ now()->format('l, F j, Y') }}
            </div>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $employee = $user->employee;
        
        // Attendance logs status
        $todayLog = null;
        if ($employee) {
            $todayLog = \App\Models\AttendanceLog::where('employee_id', $employee->id)
                ->whereNull('clock_out_at')
                ->first();
        }
        
        // Personal leave requests
        $pendingLeaves = \App\Models\LeaveRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
            
        // Admin overview check
        $isAdminOrHR = $user->hasRole('Admin') || $user->hasRole('HR') || $user->hasRole('Manager');
    @endphp

    <div class="py-6 space-y-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if (session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 flex items-center space-x-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-2xl p-4 flex items-center space-x-3 shadow-sm">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-semibold">{{ session('error') }}</span>
                </div>
            @endif

            @php
                $isDefaultPassword = \Hash::check('12345678', $user->password);
                $isFallbackEmail = str_contains($user->email ?? '', '@employee-portal.local');
                $needsSecurityUpdate = $isDefaultPassword || $isFallbackEmail;
            @endphp

            @if ($needsSecurityUpdate)
                <div class="bg-gradient-to-r from-amber-50/70 to-orange-50/20 border border-amber-200/60 rounded-2xl p-5 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 hover:shadow transition-all duration-300">
                    <div class="flex items-start space-x-3.5">
                        <div class="bg-amber-100 p-2.5 rounded-xl text-amber-800 border border-amber-200 flex-shrink-0">
                            <svg class="w-5.5 h-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-amber-900">Security Recommendation</h4>
                            <p class="text-xs text-amber-700/95 font-medium mt-1 leading-relaxed max-w-2xl">
                                You are currently using 
                                @if($isDefaultPassword && $isFallbackEmail)
                                    your temporary email (<span class="font-mono bg-amber-100/50 px-1 py-0.5 rounded text-amber-800">{{ $user->email }}</span>) and default password.
                                @elseif($isDefaultPassword)
                                    your default password.
                                @else
                                    your temporary email (<span class="font-mono bg-amber-100/50 px-1 py-0.5 rounded text-amber-800">{{ $user->email }}</span>).
                                @endif
                                Please update these settings in your profile to secure your account.
                            </p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 w-full sm:w-auto">
                        <a href="{{ route('profile') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-xs shadow-md shadow-amber-500/10 transition-all cursor-pointer">
                            Update Profile
                        </a>
                    </div>
                </div>
            @endif

            <!-- Quick Actions Banner / Info Bar -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Clock In/Out Quick Card -->
                <div class="card p-6 flex flex-col justify-between bg-white border border-slate-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-brand-50/50 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Attendance Desk</span>
                            @if ($todayLog)
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                            @else
                                <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                            @endif
                        </div>
                        <h4 class="text-lg font-extrabold text-slate-950 mb-1">
                            @if ($todayLog)
                                Active Shift Started
                            @else
                                Ready to Clock In
                            @endif
                        </h4>
                        <p class="text-xs text-slate-500 font-medium">
                            @if ($todayLog)
                                You clocked in at <strong class="text-slate-800 font-semibold">{{ \Carbon\Carbon::parse($todayLog->clock_in_at)->format('H:i') }}</strong>. Don't forget to clock out when you leave!
                            @else
                                Record your attendance for today. Make sure your location is active.
                            @endif
                        </p>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <span class="text-sm font-black text-slate-700 tracking-tight" id="clock-display">{{ now()->format('H:i:s') }}</span>
                        @if ($todayLog)
                            <form action="{{ route('attendance.clock-out') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger font-bold text-xs px-5 py-2.5 rounded-xl shadow-lg shadow-red-500/20 hover:shadow-red-500/30 hover:-translate-y-0.5 active:translate-y-0 transform transition-all cursor-pointer">
                                    Clock Out
                                </button>
                            </form>
                        @else
                            <form action="{{ route('attendance.clock-in') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn bg-brand-600 hover:bg-brand-700 font-bold text-xs px-5 py-2.5 rounded-xl shadow-lg shadow-brand-500/20 hover:shadow-brand-500/30 hover:-translate-y-0.5 active:translate-y-0 transform transition-all text-white cursor-pointer">
                                    Clock In
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Personal Leaves Quick Card -->
                <div class="card p-6 flex flex-col justify-between bg-white border border-slate-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-indigo-50/50 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Leave Balance</span>
                            <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-100">Active</span>
                        </div>
                        <h4 class="text-lg font-extrabold text-slate-950 mb-1">
                            {{ $pendingLeaves > 0 ? "$pendingLeaves Pending Requests" : "No Pending Leaves" }}
                        </h4>
                        <p class="text-xs text-slate-500 font-medium">
                            Need a break or medical leave? Request leave directly here and track your approval status.
                        </p>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500">Fast Approvals</span>
                        <a href="{{ route('leave-requests.create') }}" class="inline-flex justify-center items-center px-4 py-2.5 bg-indigo-50 text-indigo-700 border border-indigo-100 hover:bg-indigo-100 hover:text-indigo-800 rounded-xl font-bold text-xs transition-colors cursor-pointer">
                            Request Leave
                        </a>
                    </div>
                </div>

                <!-- Personal Info Card -->
                <div class="card p-6 flex flex-col justify-between bg-white border border-slate-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-amber-50/50 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Portal Profile</span>
                            <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-100">Synced</span>
                        </div>
                        <h4 class="text-lg font-extrabold text-slate-950 mb-1">
                            {{ $employee ? $employee->first_name . ' ' . $employee->last_name : $user->name }}
                        </h4>
                        <p class="text-xs text-slate-500 font-medium leading-relaxed mt-2">
                            <strong>NIK:</strong> {{ $employee->employee_id ?? 'N/A' }} <br>
                            <strong>Branch:</strong> {{ $employee->branch ?? 'N/A' }} <br>
                            <strong>Dept:</strong> {{ $employee->department->name ?? 'N/A' }}
                        </p>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500">Master Data</span>
                        <a href="{{ route('profile') }}" class="inline-flex justify-center items-center px-4 py-2.5 bg-amber-50 text-amber-700 border border-amber-100 hover:bg-amber-100 hover:text-amber-800 rounded-xl font-bold text-xs transition-colors cursor-pointer">
                            View Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Welcome Premium Banner -->
            <div class="card p-8 bg-gradient-to-r from-brand-600 to-brand-800 text-white shadow-xl border-0 relative overflow-hidden group">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white/10 via-transparent to-transparent"></div>
                <div class="relative z-10">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-white/70 bg-white/10 px-3 py-1.5 rounded-full backdrop-blur-md border border-white/10 inline-block mb-4">Employee Workspace</span>
                    <h3 class="text-3xl font-extrabold mb-2 tracking-tight">Your Employee Portal is fully synced!</h3>
                    <p class="text-brand-100 mb-6 max-w-2xl text-sm leading-relaxed">Welcome back to your company portal. Here, you can easily clock in or out for work shifts, request leaves, and search the master employee directory.</p>
                    <div class="flex space-x-4">
                        <a href="{{ route('employees.index') }}" class="inline-flex items-center px-5 py-3 bg-white text-brand-700 font-bold text-xs rounded-xl hover:bg-brand-50 transition-colors shadow-lg shadow-black/10 cursor-pointer">
                            Search Employees Directory
                        </a>
                        <a href="{{ route('attendance.index') }}" class="inline-flex items-center px-5 py-3 bg-brand-700 border border-brand-500 text-white font-bold text-xs rounded-xl hover:bg-brand-600 transition-colors shadow-lg shadow-black/10 cursor-pointer">
                            View My Attendance Logs
                        </a>
                    </div>
                </div>
            </div>

            @if($isAdminOrHR)
                <!-- Admin Overview Section (Conditional) -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-bold text-slate-900 tracking-tight">Company Management Overview</h4>
                        <span class="text-xs font-bold text-brand-600 bg-brand-50 px-2.5 py-1 rounded-full border border-brand-100">Admin Controls</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Card 1 -->
                        <div class="card p-6 flex items-center space-x-4 hover:shadow transition-shadow duration-300">
                            <div class="p-3 rounded-xl bg-brand-50 text-brand-600 border border-brand-100 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Synced Employees</p>
                                <p class="text-2xl font-black text-slate-950">{{\App\Models\Employee::count() ?? 0}}</p>
                            </div>
                        </div>

                        <!-- Card 2 -->
                        <div class="card p-6 flex items-center space-x-4 hover:shadow transition-shadow duration-300">
                            <div class="p-3 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Leaves (Today)</p>
                                <p class="text-2xl font-black text-slate-950">
                                    {{\App\Models\LeaveRequest::where('start_date', '<=', now()->toDateString())
                                        ->where('end_date', '>=', now()->toDateString())
                                        ->where('status', 'approved')
                                        ->count()}}
                                </p>
                            </div>
                        </div>

                        <!-- Card 3 -->
                        <div class="card p-6 flex items-center space-x-4 hover:shadow transition-shadow duration-300">
                            <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Present Today</p>
                                <p class="text-2xl font-black text-slate-950">
                                    {{\App\Models\AttendanceLog::whereDate('clock_in_at', now()->toDateString())->count()}}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <!-- Clock display auto-updating JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const clockEl = document.getElementById('clock-display');
            if (clockEl) {
                setInterval(() => {
                    const now = new Date();
                    clockEl.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                }, 1000);
            }
        });
    </script>
</x-app-layout>


<x-app-layout>
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

        $isDefaultPassword = \Hash::check('12345678', $user->password);
        $isFallbackEmail = str_contains($user->email ?? '', '@employee-portal.local');
        $needsSecurityUpdate = $isDefaultPassword || $isFallbackEmail;
    @endphp

    <div class="space-y-6">
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 flex items-center space-x-3 shadow-sm transition-all duration-300">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-2xl p-4 flex items-center space-x-3 shadow-sm transition-all duration-300">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- 1. Premium Welcome Hero Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-700 to-brand-900 text-white shadow-xl border-0 p-8 md:p-10 group transition-all duration-300 hover:shadow-2xl">
            <!-- Glassmorphic light background effect -->
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white/15 via-transparent to-transparent pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] uppercase tracking-wider font-extrabold text-white/90 bg-white/10 px-3 py-1 rounded-full backdrop-blur-md border border-white/10">
                            {{ __('Workspace Portal') }}
                        </span>
                        <span class="text-[10px] tracking-wider font-bold text-white/80">
                            {{ now()->format('l, F j, Y') }}
                        </span>
                    </div>
                    
                    <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight">
                        {{ __('Hello, ') . auth()->user()->name }}!
                    </h2>
                    
                    <p class="text-sm text-brand-100 max-w-2xl leading-relaxed">
                        Welcome back to your employee dashboard. Easily manage your shifts, leaves, and look up team contacts from one secure portal.
                    </p>
                </div>

                <!-- Banner Quick Links -->
                <div class="flex flex-wrap gap-3 flex-shrink-0">
                    @can('view employees')
                        <a href="{{ route('employees.index') }}" class="inline-flex items-center px-4 py-2.5 bg-white text-brand-700 font-bold text-xs rounded-xl hover:bg-brand-50 transition-all shadow-md shadow-black/5 hover:-translate-y-0.5 active:translate-y-0 transform cursor-pointer">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Search Directory
                        </a>
                    @endcan
                    <a href="{{ route('attendance.index') }}" class="inline-flex items-center px-4 py-2.5 bg-brand-800 border border-brand-600 text-white font-bold text-xs rounded-xl hover:bg-brand-700 transition-all shadow-md shadow-black/5 hover:-translate-y-0.5 active:translate-y-0 transform cursor-pointer">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2" />
                        </svg>
                        Attendance History
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Dual-Column Core Workspace Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Side Column: Attendance and Leaves (Takes 2 Cols) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Attendance Card (Live Digital Clock & Actions) -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-32 h-32 bg-brand-50/40 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Attendance Desk</span>
                                @if ($todayLog)
                                    <span class="flex h-2 w-2 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    <span class="text-[10px] text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full font-bold">ACTIVE SHIFT</span>
                                @else
                                    <span class="text-[10px] text-slate-500 bg-slate-50 border border-slate-150 px-2 py-0.5 rounded-full font-bold">READY</span>
                                @endif
                            </div>
                            
                            <h3 class="text-xl font-extrabold text-slate-900 leading-tight">
                                @if ($todayLog)
                                    Active shift started at {{ \Carbon\Carbon::parse($todayLog->clock_in_at)->format('H:i') }}
                                @else
                                    Ready to record your daily attendance
                                @endif
                            </h3>
                            
                            <p class="text-xs text-slate-500 leading-relaxed max-w-lg">
                                @if ($todayLog)
                                    Shift is active. Make sure to clock out when finishing your work day to complete log registration.
                                @else
                                    Ensure your device location services are active. Clock in to begin recording your portal shift.
                                @endif
                            </p>
                        </div>

                        <!-- Spacious Digital Clock & Forms -->
                        <div class="flex flex-col items-center md:items-end justify-center gap-3 flex-shrink-0 bg-slate-50/60 p-5 rounded-2xl border border-slate-100/50 w-full md:w-auto">
                            <span class="text-2xl font-black text-slate-800 tracking-wider font-mono" id="clock-display">
                                {{ now()->format('H:i:s') }}
                            </span>
                            
                            @if ($todayLog)
                                <form action="{{ route('attendance.clock-out') }}" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full px-6 py-2.5 bg-red-650 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-md shadow-red-500/10 hover:shadow-red-500/20 hover:-translate-y-0.5 active:translate-y-0 transform transition-all cursor-pointer border-0">
                                        Clock Out Now
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('attendance.clock-in') }}" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-md shadow-brand-500/10 hover:shadow-brand-500/20 hover:-translate-y-0.5 active:translate-y-0 transform transition-all cursor-pointer border-0">
                                        Clock In Now
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Leaves Card -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-32 h-32 bg-indigo-50/40 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>
                    
                    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Leave Balance Desk</span>
                                <span class="text-[10px] text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-full font-bold">ONLINE</span>
                            </div>
                            
                            <h3 class="text-xl font-extrabold text-slate-900 leading-tight">
                                {{ $pendingLeaves > 0 ? "$pendingLeaves Pending Requests awaiting review" : "No active pending leave requests" }}
                            </h3>
                            
                            <p class="text-xs text-slate-500 leading-relaxed max-w-lg">
                                Review your personal balances, create medical or personal leave requests, and monitor manager approvals directly.
                            </p>
                        </div>

                        <div class="flex-shrink-0">
                            <a href="{{ route('leave-requests.create') }}" class="inline-flex justify-center items-center px-5 py-3 bg-indigo-50 text-indigo-700 border border-indigo-100 hover:bg-indigo-100 hover:text-indigo-800 rounded-xl font-bold text-xs transition-all shadow-sm hover:-translate-y-0.5 active:translate-y-0 transform cursor-pointer">
                                Request Time Off
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Side Column: Employee Profile Details & Alerts (Takes 1 Col) -->
            <div class="lg:col-span-1">
                
                <!-- Synced Profile Card -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group flex flex-col justify-between h-full">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-28 h-28 bg-amber-50/40 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>
                    
                    <div class="relative z-10 space-y-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Portal Sync Profile</span>
                            <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-100 px-2.5 py-0.5 rounded-full font-bold">Synced</span>
                        </div>
                        
                        <!-- Employee Info Header -->
                        <div class="space-y-1">
                            <h4 class="text-lg font-extrabold text-slate-950">
                                {{ $employee ? $employee->first_name . ' ' . $employee->last_name : $user->name }}
                            </h4>
                        </div>
                        
                        <!-- List of Details -->
                        <div class="space-y-3 border-t border-slate-100 pt-4 text-xs font-medium text-slate-600">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-400">NIK (ID)</span>
                                <span class="font-mono text-slate-800 font-bold bg-slate-50 border border-slate-100 px-2 py-0.5 rounded">{{ $employee->employee_id ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-400">Branch</span>
                                <span class="text-slate-800 font-bold">{{ $employee->branch ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-400">Department</span>
                                <span class="text-slate-800 font-bold text-right truncate max-w-[150px]">{{ $employee->department->name ?? 'N/A' }}</span>
                            </div>
                        </div>

                        <!-- Integrated Security Alert (Elegant Warning Pill inside Card) -->
                        @if ($needsSecurityUpdate)
                            <div class="mt-4 rounded-2xl p-4 bg-gradient-to-br from-amber-50/60 to-orange-50/20 border border-amber-200/50 space-y-2">
                                <h5 class="text-xs font-bold text-amber-800 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                                    </svg>
                                    Security Update Recommended
                                </h5>
                                <p class="text-[10px] text-amber-700 leading-relaxed font-medium">
                                    @if ($isDefaultPassword && $isFallbackEmail)
                                        You are using the default password and a temporary fallback email.
                                    @elseif ($isDefaultPassword)
                                        You are using the default placeholder password.
                                    @else
                                        You are using a temporary placeholder email.
                                    @endif
                                    Please update these settings to secure your account.
                                </p>
                                <div class="pt-1">
                                    <a href="{{ route('profile') }}" class="inline-flex items-center text-[10px] font-extrabold text-amber-800 hover:text-amber-950 transition-colors gap-0.5 cursor-pointer">
                                        Update Settings
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <div class="relative z-10 mt-5 pt-4 border-t border-slate-100">
                        <a href="{{ route('profile') }}" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-amber-50 text-amber-700 border border-amber-100 hover:bg-amber-100 hover:text-amber-800 rounded-xl font-bold text-xs transition-colors cursor-pointer">
                            Configure Profile Settings
                        </a>
                    </div>
                </div>

            </div>

        </div>

        <!-- 3. Company Management Overview (Conditional for Admins/HR/Managers) -->
        @if($isAdminOrHR)
            <div class="space-y-4 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <h4 class="text-base font-bold text-slate-800 tracking-tight">Company Management Overview</h4>
                    <span class="text-[10px] font-bold text-brand-600 bg-brand-50 px-2.5 py-1 rounded-full border border-brand-100">Admin Controls</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Stat Card 1 -->
                    <div class="bg-white rounded-3xl p-5 flex items-center space-x-4 border border-slate-100 hover:shadow-sm transition-shadow duration-300">
                        <div class="p-3 rounded-2xl bg-brand-50 text-brand-600 border border-brand-100 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Synced Employees</p>
                            <p class="text-2xl font-black text-slate-900 leading-tight">{{\App\Models\Employee::count() ?? 0}}</p>
                        </div>
                    </div>

                    <!-- Stat Card 2 -->
                    <div class="bg-white rounded-3xl p-5 flex items-center space-x-4 border border-slate-100 hover:shadow-sm transition-shadow duration-300">
                        <div class="p-3 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Leaves (Today)</p>
                            <p class="text-2xl font-black text-slate-900 leading-tight">
                                {{\App\Models\LeaveRequest::where('start_date', '<=', now()->toDateString())
                                    ->where('end_date', '>=', now()->toDateString())
                                    ->where('status', 'approved')
                                    ->count()}}
                            </p>
                        </div>
                    </div>

                    <!-- Stat Card 3 -->
                    <div class="bg-white rounded-3xl p-5 flex items-center space-x-4 border border-slate-100 hover:shadow-sm transition-shadow duration-300">
                        <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Present Today</p>
                            <p class="text-2xl font-black text-slate-900 leading-tight">
                                {{\App\Models\AttendanceLog::whereDate('clock_in_at', now()->toDateString())->count()}}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

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

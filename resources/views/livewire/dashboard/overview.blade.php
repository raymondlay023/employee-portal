<div wire:poll.5s class="space-y-6">
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

    <!-- Security Action Alert -->
    @if ($needsSecurityUpdate)
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-50 to-orange-50/60 border border-amber-200 p-6 shadow-sm transition-all duration-300 hover:shadow-md">
            <!-- Glassmorphic background blur shapes -->
            <div class="absolute -right-10 -top-10 w-36 h-36 bg-amber-200/40 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 rounded-2xl bg-amber-500 text-white shadow-md shadow-amber-500/20 animate-pulse flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-extrabold text-amber-900 tracking-tight flex items-center gap-2">
                            Critical Security Action Required
                        </h4>
                        <p class="text-xs text-amber-750 text-amber-850 text-amber-800 leading-relaxed font-semibold max-w-2xl">
                            @if ($isDefaultPassword && $isFallbackEmail)
                                Your account is currently using the <span class="text-amber-950 font-black">default temporary password</span> and a <span class="text-amber-950 font-black">temporary placeholder email</span>.
                            @elseif ($isDefaultPassword)
                                Your account is currently using the <span class="text-amber-950 font-black">default temporary password</span>.
                            @else
                                Your account is currently using a <span class="text-amber-950 font-black">temporary placeholder email</span>.
                            @endif
                            To secure your employee portal and prevent unauthorized access, please update these credentials immediately.
                        </p>
                    </div>
                </div>
                
                <div class="flex-shrink-0">
                    <a href="{{ route('profile') }}" class="inline-flex justify-center items-center px-5 py-3 bg-amber-600 hover:bg-amber-700 text-white shadow-md shadow-amber-500/15 hover:shadow-amber-500/25 rounded-xl font-bold text-xs transition-all hover:-translate-y-0.5 active:translate-y-0 transform cursor-pointer border-0">
                        Secure Your Account
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Profile Incomplete warning banner for unassigned managers -->
    @if ($isManagerWithoutDept)
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-red-50 to-orange-50/60 border border-red-200 p-6 shadow-sm transition-all duration-300 hover:shadow-md">
            <div class="absolute -right-10 -top-10 w-36 h-36 bg-red-200/40 rounded-full blur-2xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 rounded-2xl bg-red-500 text-white shadow-md shadow-red-500/20 animate-pulse flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-extrabold text-red-900 tracking-tight">Profile Incomplete</h4>
                        <p class="text-xs text-red-800 leading-relaxed font-semibold">
                            Your manager profile is not currently linked to any department. Please contact HR to assign your department.
                        </p>
                    </div>
                </div>
            </div>
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
                @can(\App\Authorization\Permissions::MANAGE_EMPLOYEES)
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
                                <div class="flex items-center gap-2 mt-4 text-emerald-700 bg-emerald-50 rounded-xl px-4 py-3">
                                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                    <span class="text-xs font-bold">
                                        Active shift started at {{ \Carbon\Carbon::parse($todayLog->clock_in_at)->timezone('Asia/Jakarta')->format('H:i') }}
                                    </span>
                                </div>
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
                        <span class="text-2xl font-black text-slate-800 tracking-wider font-mono" 
                              x-data="{ time: '{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}' }" 
                              x-init="setInterval(() => time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }), 1000)" 
                              x-text="time" 
                              wire:ignore>
                            {{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}
                        </span>
                        
                        @if(config('app.enable_manual_attendance'))
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
                        @else
                            <div class="w-full px-4 py-2.5 bg-slate-100 text-slate-500 font-bold text-[10px] rounded-xl border border-slate-200 text-center uppercase tracking-wider">
                                Manual Entry Disabled
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Daily Work Logs Card (Today's Progress) -->
            <div class="bg-white rounded-3xl p-6 border border-slate-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
                <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-32 h-32 bg-emerald-50/40 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>
                
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                    <div class="space-y-3 flex-grow">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Activity & Timesheet Desk</span>
                            @if ($todayIsComplete)
                                <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full font-bold">TARGET REACHED</span>
                            @elseif ($todayTotalHours > 0)
                                <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-full font-bold">IN PROGRESS</span>
                            @else
                                <span class="text-[10px] text-slate-500 bg-slate-50 border border-slate-150 px-2 py-0.5 rounded-full font-bold">READY</span>
                            @endif
                        </div>
                        
                        <h3 class="text-xl font-extrabold text-slate-900 leading-tight">
                            {{ $todayFormattedHours }} Hour{{ $todayTotalHours != 1 ? 's' : '' }} Logged for Today
                        </h3>
                        
                        <!-- Small Progress Bar -->
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden border border-slate-200/50 max-w-md">
                            <div 
                                class="h-full bg-gradient-to-r {{ $todayIsComplete ? 'from-emerald-400 to-teal-500' : 'from-brand-500 to-brand-700' }} rounded-full transition-all duration-500" 
                                style="width: {{ $todayPercentage }}%"
                            ></div>
                        </div>

                        @if($todayWorkLogs->isNotEmpty())
                            <p class="text-xs text-slate-500 leading-relaxed font-medium">
                                Latest activity: <span class="font-bold text-slate-700">{{ $todayWorkLogs->last()->activity }}</span> ({{ $todayWorkLogs->last()->start_time }} - {{ $todayWorkLogs->last()->end_time }})
                            </p>
                        @else
                            <p class="text-xs text-slate-500 leading-relaxed font-medium">
                                You haven't recorded any work slots for today yet. Make sure to log your hourly work to reach the 8-hour target.
                            </p>
                        @endif
                    </div>

                    <div class="flex-shrink-0">
                        <a href="{{ route('daily-work-logs.index') }}" class="inline-flex justify-center items-center px-5 py-3 bg-brand-50 text-brand-700 border border-brand-100 hover:bg-brand-100 hover:text-brand-800 rounded-xl font-bold text-xs transition-all shadow-sm hover:-translate-y-0.5 active:translate-y-0 transform cursor-pointer">
                            Manage Timesheet
                        </a>
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
                        
                        <h3 class="text-xl font-extrabold text-slate-950 leading-tight">
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
    @if($isManagerial && !$isManagerWithoutDept)
        <div class="space-y-4 pt-6 border-t border-slate-100">
            <div class="flex items-center justify-between">
                <h4 class="text-base font-bold text-slate-800 tracking-tight">
                    {{ (Auth::user()->hasRole('HR') || Auth::user()->hasRole('Admin')) ? 'Company Management Overview' : 'Department Management Overview' }}
                </h4>
                <span class="text-[10px] font-bold text-brand-600 bg-brand-50 px-2.5 py-1 rounded-full border border-brand-100">
                    {{ (Auth::user()->hasRole('HR') || Auth::user()->hasRole('Admin')) ? 'Admin Controls' : 'Manager Controls' }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Stat Card 1: Active Employees -->
                <a href="{{ route('employees.index') }}" class="bg-white rounded-3xl p-5 flex items-center space-x-4 border border-slate-200/60 hover:border-brand-300 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300 transform group">
                    <div class="p-3 rounded-2xl bg-brand-50 text-brand-600 border border-brand-100 flex-shrink-0 group-hover:scale-105 transition-transform duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            {{ (Auth::user()->hasRole('HR') || Auth::user()->hasRole('Admin')) ? 'Active Employees' : 'Department Employees' }}
                        </p>
                        <p class="text-2xl font-black text-slate-900 leading-tight">{{ $activeEmployeesCount }}</p>
                    </div>
                </a>

                <!-- Stat Card 2: Pending Leaves -->
                <a href="{{ route('leave-requests.index') }}?status=pending" class="bg-white rounded-3xl p-5 flex items-center space-x-4 border border-slate-200/60 hover:border-amber-300 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300 transform group">
                    <div class="p-3 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex-shrink-0 group-hover:scale-105 transition-transform duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            {{ (Auth::user()->hasRole('HR') || Auth::user()->hasRole('Admin')) ? 'Pending Leaves' : 'Dept Pending Leaves' }}
                        </p>
                        <p class="text-2xl font-black text-slate-900 leading-tight">
                            {{ $pendingLeavesCount }}
                        </p>
                    </div>
                </a>

                <!-- Stat Card 3: Present Today -->
                <a href="{{ route('hr.attendance-report') }}" class="bg-white rounded-3xl p-5 flex items-center space-x-4 border border-slate-200/60 hover:border-emerald-300 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300 transform group">
                    <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex-shrink-0 group-hover:scale-105 transition-transform duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            {{ (Auth::user()->hasRole('HR') || Auth::user()->hasRole('Admin')) ? 'Present Today' : 'Dept Present Today' }}
                        </p>
                        <p class="text-2xl font-black text-slate-900 leading-tight">
                            {{ $presentTodayCount }}
                        </p>
                    </div>
                </a>
            </div>
        </div>
    @endif
</div>

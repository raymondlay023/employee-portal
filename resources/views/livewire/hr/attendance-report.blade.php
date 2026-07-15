<div>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">
                    {{ __('Attendance Report') }}
                </h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">
                    {{ __('Company-wide monthly attendance aggregates from JPayroll') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @can(\App\Authorization\Permissions::MANAGE_ATTENDANCE)
                <livewire:attendance.sync-jpayroll />
            @endcan

            <!-- Controls Header -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h3 class="text-lg font-bold text-slate-800">{{ __('Monthly Aggregates') }}</h3>
                    <p class="text-xs text-slate-500">{{ __('Overview of all employee attendance records from JPayroll.') }}</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <select wire:model.live="month" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50">
                        @foreach($availableMonths as $m)
                            <option value="{{ $m }}">{{ __(date('F', mktime(0, 0, 0, $m, 1))) }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="year" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                    @can(\App\Authorization\Permissions::MANAGE_ATTENDANCE)
                    <select wire:model.live="department_id" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50">
                        <option value="">{{ __('All Departments') }}</option>
                        @if(isset($departments))
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        @endif
                    </select>
                    @endcan

                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search employee...') }}" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50 w-full md:w-48 lg:w-64">
                    </div>
                </div>
            </div>

            <!-- Active Filter Toggle -->
            <div class="flex justify-end -mt-2">
                <label class="flex items-center gap-2 cursor-pointer bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors">
                    <input type="checkbox" wire:model.live="activeOnly" class="rounded text-brand-600 focus:ring-brand-500 border-slate-300 w-4 h-4 cursor-pointer">
                    <span class="text-xs font-bold text-slate-700">{{ __('Active Employees Only') }}</span>
                </label>
            </div>

            <!-- Main Data Table -->
            @if(!auth()->user()->can(\App\Authorization\Permissions::MANAGE_ATTENDANCE) && empty(auth()->user()->employee?->department_id))
                <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl shadow-sm flex items-start gap-3">
                    <svg class="w-5 h-5 mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="font-bold">{{ __('No Department Assigned') }}</h4>
                        <p class="text-sm">{{ __('You are not assigned to any department, so no employee attendance records can be displayed. Please contact the administrator.') }}</p>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden relative">
                <div wire:loading class="absolute inset-0 bg-white/50 backdrop-blur-sm z-10 flex items-center justify-center">
                    <svg class="animate-spin h-8 w-8 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100 text-[10px] uppercase font-extrabold tracking-wider text-slate-500">
                                <th class="py-4 px-6 cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('first_name')">
                                    <div class="flex items-center gap-2">
                                        {{ __('Employee') }}
                                        @if($sortField === 'first_name')
                                            <svg class="w-3 h-3 text-brand-600 transition-transform {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('present_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ __('Present') }}
                                        @if($sortField === 'present_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('absent_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ __('Absent') }}
                                        @if($sortField === 'absent_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('late_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ __('Late') }}
                                        @if($sortField === 'late_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('sick_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ __('Sick') }}
                                        @if($sortField === 'sick_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('leave_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ __('Leave') }}
                                        @if($sortField === 'leave_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-6 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('total_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ __('Total Days') }}
                                        @if($sortField === 'total_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-6 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($reportData as $row)
                                <tr class="hover:bg-slate-50/60 transition-colors group">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-brand-100 to-brand-200 flex flex-shrink-0 items-center justify-center text-brand-700 font-extrabold text-sm border border-brand-200/50 shadow-inner">
                                                {{ $row->employee->initials ?? '?' }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-bold text-slate-800 tracking-tight">{{ $row->employee->first_name ?? __('Unknown') }} {{ $row->employee->last_name ?? '' }}</p>
                                                    @if($row->absent_days >= 2 || $row->late_days >= 3)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-red-100 text-red-700 border border-red-200" title="High absenteeism or lates">
                                                            <span class="w-1 h-1 bg-red-500 rounded-full mr-1"></span>
                                                            {{ __('Flagged') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-[10px] text-slate-500 font-semibold">{{ $row->employee->employee_id ?? __('No ID') }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 bg-green-50 text-green-700 font-bold text-xs rounded-lg border border-green-200/60 shadow-sm">
                                            {{ (int) $row->present_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $row->absent_days > 0 ? 'bg-red-50 text-red-700 border-red-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $row->absent_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $row->late_days > 0 ? 'bg-amber-50 text-amber-700 border-amber-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $row->late_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $row->sick_days > 0 ? 'bg-purple-50 text-purple-700 border-purple-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $row->sick_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $row->leave_days > 0 ? 'bg-blue-50 text-blue-700 border-blue-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $row->leave_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-xs rounded-lg shadow-inner">
                                            {{ (int) $row->total_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <a href="{{ route('attendance-report.detail', ['employee' => $row->employee_id, 'month' => $month, 'year' => $year]) }}" wire:navigate class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 text-slate-600 hover:text-brand-600 hover:border-brand-200 hover:bg-brand-50 rounded-xl font-bold text-[10px] uppercase tracking-wider transition-all shadow-sm">
                                            {{ __('Details') }}
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-16 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                                            <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-600">{{ __('No attendance data found') }}</p>
                                        <p class="text-xs text-slate-400 mt-1">{{ __('Try selecting a different month or year.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            @if ($reportData->hasPages())
                <div class="mt-4">
                    {{ $reportData->links() }}
                </div>
            @endif

        </div>
    </div>
</div>

<div>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">
                    {{ __('Work Log Report') }}
                </h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">
                    {{ __('Employee daily timesheets and work activities summary') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Stats Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Employees -->
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-brand-50 text-brand-600 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Total Employees') }}</p>
                        <p class="text-2xl font-black text-slate-800 mt-0.5">{{ $stats['total'] }}</p>
                    </div>
                </div>

                <!-- Logged Work Logs -->
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-green-50 text-green-600 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Logged Today') }}</p>
                        <p class="text-2xl font-black text-slate-800 mt-0.5">{{ $stats['logged'] }}</p>
                    </div>
                </div>

                <!-- Average Hours -->
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Average Hours') }}</p>
                        <p class="text-2xl font-black text-slate-800 mt-0.5">{{ $stats['average'] }}h</p>
                    </div>
                </div>

                <!-- Completion Rate -->
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Completion Rate') }}</p>
                        <p class="text-2xl font-black text-slate-800 mt-0.5">{{ $stats['completion_rate'] }}%</p>
                    </div>
                </div>
            </div>

            <!-- Controls Header -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 flex flex-col gap-5">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold text-slate-800">{{ __('Work Log Overview') }}</h3>
                        <p class="text-xs text-slate-500">{{ __('Track employee timesheet hours and daily logs completion details.') }}</p>
                    </div>

                    <!-- View Mode Toggle Buttons -->
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 self-start lg:self-center">
                        <button wire:click="$set('viewMode', 'day')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $viewMode === 'day' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">{{ __('Day') }}</button>
                        <button wire:click="$set('viewMode', 'week')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $viewMode === 'week' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">{{ __('Week') }}</button>
                        <button wire:click="$set('viewMode', 'month')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $viewMode === 'month' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">{{ __('Month') }}</button>
                        <button wire:click="$set('viewMode', 'range')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $viewMode === 'range' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">{{ __('Range') }}</button>
                    </div>
                </div>

                <hr class="border-slate-100">

                <div class="flex flex-col md:flex-row flex-wrap items-center gap-4 justify-between w-full">
                    <!-- Date Pickers depending on View Mode -->
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        @if($viewMode === 'day' || $viewMode === 'week')
                            <div class="flex items-center gap-2 w-full md:w-auto">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ $viewMode === 'day' ? __('Date') : __('Week Of') }}:</span>
                                <input type="date" wire:model.live="date" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50 w-full md:w-auto">
                            </div>
                        @elseif($viewMode === 'month')
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Period') }}:</span>
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
                            </div>
                        @elseif($viewMode === 'range')
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('From') }}:</span>
                                <input type="date" wire:model.live="startDate" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 focus:border-brand-500 focus:ring focus:ring-brand-200/50">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('To') }}:</span>
                                <input type="date" wire:model.live="endDate" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 focus:border-brand-500 focus:ring focus:ring-brand-200/50">
                            </div>
                        @endif
                    </div>

                    <!-- Department dropdown & Search -->
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                        @if(auth()->user()->can(\App\Authorization\Permissions::MANAGE_EMPLOYEES))
                            <select wire:model.live="department_id" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50 w-full sm:w-auto">
                                <option value="">{{ __('All Departments') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        @endif

                        <div class="relative w-full sm:w-48 lg:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search employee...') }}" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50 w-full">
                        </div>
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
            @if(!auth()->user()->can(\App\Authorization\Permissions::MANAGE_EMPLOYEES) && empty(auth()->user()->employee?->department_id))
                <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl shadow-sm flex items-start gap-3">
                    <svg class="w-5 h-5 mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="font-bold">{{ __('No Department Assigned') }}</h4>
                        <p class="text-sm">{{ __('You are not assigned to any department, so no employee work logs can be displayed. Please contact the administrator.') }}</p>
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
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('total_hours')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ __('Hours Logged') }}
                                        @if($sortField === 'total_hours')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('activities_count')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{ __('Activities') }}
                                        @if($sortField === 'activities_count')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-6 text-center">{{ __('Completion') }}</th>
                                <th class="py-4 px-6 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($reportData as $row)
                                <tr class="hover:bg-slate-50/60 transition-colors group">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-brand-100 to-brand-200 flex flex-shrink-0 items-center justify-center text-brand-700 font-extrabold text-sm border border-brand-200/50 shadow-inner">
                                                {{ $row->initials ?? '?' }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-bold text-slate-800 tracking-tight">{{ $row->first_name ?? __('Unknown') }} {{ $row->last_name ?? '' }}</p>
                                                </div>
                                                <p class="text-[10px] text-slate-500 font-semibold">{{ $row->employee_id ?? __('No ID') }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-xs rounded-lg shadow-inner">
                                            {{ round($row->total_hours, 1) }}h
                                        </span>
                                    </td>

                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $row->activities_count > 0 ? 'bg-indigo-50 text-indigo-700 border-indigo-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $row->activities_count }}
                                        </span>
                                    </td>

                                    <td class="py-4 px-6 text-center">
                                        @if($row->total_hours >= 8)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span>
                                                {{ __('Complete') }}
                                            </span>
                                        @elseif($row->total_hours > 0)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full mr-1.5"></span>
                                                {{ __('Partial') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-55 text-slate-500 border border-slate-200">
                                                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-1.5"></span>
                                                {{ __('Not Logged') }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-4 px-6 text-right">
                                        <a href="{{ route('work-log-report.detail', ['employee' => $row->id, 'viewMode' => $viewMode, 'date' => $date, 'startDate' => $startDate, 'endDate' => $endDate, 'month' => $month, 'year' => $year]) }}" wire:navigate class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 text-slate-600 hover:text-brand-600 hover:border-brand-200 hover:bg-brand-50 rounded-xl font-bold text-[10px] uppercase tracking-wider transition-all shadow-sm">
                                            {{ __('Details') }}
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-16 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                                            <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-600">{{ __('No work logs found') }}</p>
                                        <p class="text-xs text-slate-400 mt-1">{{ __('Try selecting a different date or department.') }}</p>
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

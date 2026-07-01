<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">{{ __('New Leave Request') }}</h2>
            <p class="text-xs text-slate-400 font-medium mt-0.5">{{ __('Apply for time off by filling in the details below') }}</p>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-6">{{ __('Leave Details') }}</h3>

                <!-- Live Annual Leave Balance from JPayroll -->
                @if($employeeId)
                    @if($annualLeave)
                        @php
                            $quota = $annualLeave['Balance'] ?? 0;
                            $taken = $annualLeave['Posted'] ?? 0;
                            $pending = $pendingLeaveDays ?? 0;
                            $remaining = $annualLeave['Remain'] ?? 0;
                        @endphp
                        <div class="mb-6 p-4 bg-brand-50/50 border border-brand-100 rounded-2xl flex items-center justify-between shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-brand-100/80 text-brand-700 rounded-xl">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold text-brand-800 uppercase tracking-wider">{{ __('Available Annual Leave Balance') }}</p>
                                    <p class="text-xs font-semibold text-slate-500 mt-0.5">
                                        {{ __('Quota: :quota | Used: :used | Pending: :pending', ['quota' => $quota, 'used' => $taken, 'pending' => $pending]) }}
                                        @if($lastSyncedAt)
                                            <span class="text-[9px] font-medium text-slate-400 block sm:inline sm:ml-2">({{ __('Synced :time', ['time' => $lastSyncedAt->diffForHumans()]) }})</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center justify-center px-3 py-1.5 bg-brand-600 text-white font-black text-xs rounded-xl shadow-sm">
                                    {{ __(':count days left', ['count' => $remaining]) }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="mb-6 p-4 bg-amber-50/40 border border-amber-100 rounded-2xl flex items-center gap-3">
                            <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <p class="text-[10px] text-amber-700 font-bold uppercase tracking-wider leading-relaxed">{{ __('JPayroll balance unreachable. Submitting requests may be subject to manual validation by HR.') }}</p>
                        </div>
                    @endif
                @else
                    <div class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-center gap-3">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider leading-relaxed">{{ __('Configure your Employee ID (NIK) in your profile to see live remaining leave days.') }}</p>
                    </div>
                @endif
                
                <form method="POST" action="{{ route('leave-requests.store') }}" class="space-y-6">
                    @csrf

                    <!-- Leave Type -->
                    <div class="flex flex-col gap-1.5">
                        <label for="type" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Leave Type') }}</label>
                        <select id="type" name="type" required
                                class="w-full text-sm border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-slate-50 transition-all">
                            <option value="annual">{{ __('Annual Leave') }}</option>
                            <option value="sick">{{ __('Sick Leave') }}</option>
                            <option value="unpaid">{{ __('Unpaid Leave') }}</option>
                        </select>
                        @error('type') <span class="text-[10px] text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Dates Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Start Date -->
                        <div class="flex flex-col gap-1.5">
                            <label for="start_date" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Start Date') }}</label>
                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}" required
                                   class="w-full text-sm border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-slate-50 transition-all">
                            @error('start_date') <span class="text-[10px] text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- End Date -->
                        <div class="flex flex-col gap-1.5">
                            <label for="end_date" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('End Date') }}</label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date', now()->format('Y-m-d')) }}" required
                                   class="w-full text-sm border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-slate-50 transition-all">
                            @error('end_date') <span class="text-[10px] text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="flex flex-col gap-1.5">
                        <label for="reason" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Reason / Remarks') }}</label>
                        <textarea id="reason" name="reason" rows="4" placeholder="{{ __('Please provide a brief reason for your leave request...') }}"
                                  class="w-full text-sm border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-slate-50 transition-all">{{ old('reason') }}</textarea>
                        @error('reason') <span class="text-[10px] text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('leave-requests.index') }}" 
                           class="inline-flex items-center justify-center px-5 py-3 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all cursor-pointer">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center justify-center px-5 py-3 bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all hover:-translate-y-0.5 active:translate-y-0 transform cursor-pointer">
                            {{ __('Submit Request') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">{{ __('Edit Leave Request') }}</h2>
            <p class="text-xs text-slate-400 font-medium mt-0.5">{{ __('Modify your pending leave request details below') }}</p>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-6">{{ __('Leave Details') }}</h3>
                
                <form method="POST" action="{{ route('leave-requests.update', $leaveRequest) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Leave Type -->
                    <div class="flex flex-col gap-1.5">
                        <label for="type" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Leave Type') }}</label>
                        <select id="type" name="type" required
                                class="w-full text-sm border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-slate-50 transition-all">
                            <option value="annual" {{ old('type', $leaveRequest->type) === 'annual' ? 'selected' : '' }}>{{ __('Annual Leave') }}</option>
                            <option value="sick" {{ old('type', $leaveRequest->type) === 'sick' ? 'selected' : '' }}>{{ __('Sick Leave') }}</option>
                            <option value="unpaid" {{ old('type', $leaveRequest->type) === 'unpaid' ? 'selected' : '' }}>{{ __('Unpaid Leave') }}</option>
                        </select>
                        @error('type') <span class="text-[10px] text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Dates Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Start Date -->
                        <div class="flex flex-col gap-1.5">
                            <label for="start_date" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Start Date') }}</label>
                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $leaveRequest->start_date->format('Y-m-d')) }}" required
                                   class="w-full text-sm border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-slate-50 transition-all">
                            @error('start_date') <span class="text-[10px] text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- End Date -->
                        <div class="flex flex-col gap-1.5">
                            <label for="end_date" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('End Date') }}</label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $leaveRequest->end_date->format('Y-m-d')) }}" required
                                   class="w-full text-sm border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-slate-50 transition-all">
                            @error('end_date') <span class="text-[10px] text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="flex flex-col gap-1.5">
                        <label for="reason" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Reason / Remarks') }}</label>
                        <textarea id="reason" name="reason" rows="4" placeholder="{{ __('Please provide a brief reason for your leave request...') }}"
                                  class="w-full text-sm border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-slate-50 transition-all">{{ old('reason', $leaveRequest->reason) }}</textarea>
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
                            {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

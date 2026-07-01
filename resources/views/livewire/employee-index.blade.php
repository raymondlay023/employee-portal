<div>
    <!-- Filters & Search -->
    <div class="card mb-6">
        <div class="p-4 bg-slate-50 border-b border-slate-100">
            <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                <div class="flex flex-col sm:flex-row gap-4 items-center w-full lg:w-auto">
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search employees...') }}" class="input pl-10" />
                    </div>
                    
                    <div class="w-full sm:w-48">
                        <select wire:model.live="department" class="input">
                            <option value="">{{ __('All Departments') }}</option>
                            @if(isset($departments))
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Active Toggle Filter -->
                <div class="w-full lg:w-auto flex justify-end">
                    <label class="flex items-center gap-2 cursor-pointer bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors">
                        <input type="checkbox" wire:model.live="activeOnly" class="rounded text-brand-600 focus:ring-brand-500 border-slate-300 w-4 h-4 cursor-pointer">
                        <span class="text-xs font-bold text-slate-700">{{ __('Active Employees Only') }}</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto relative">
            <div wire:loading class="absolute inset-0 bg-white/50 backdrop-blur-sm z-10 flex items-center justify-center">
                <svg class="animate-spin h-8 w-8 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/60">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Employee') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Department') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Role') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Joined') }}</th>
                        <th scope="col" class="px-6 py-3 text-right text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($employees as $emp)
                        <tr class="hover:bg-slate-50/40 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold text-sm">
                                            {{ $emp->initials }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                                            {{ $emp->first_name }} {{ $emp->last_name }}
                                            @if($emp->status !== 'active' || $emp->end_date)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-red-50 text-red-750 border border-red-100 uppercase tracking-wider">{{ __('Inactive') }}</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $emp->employee_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-700">
                                    {{ $emp->department?->name ?? __('N/A') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $emp->designation?->title ?? __('N/A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $emp->joined_at?->translatedFormat('j M Y') ?? __('N/A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('employees.show', $emp) }}" class="text-brand-600 hover:text-brand-900 font-bold" wire:navigate>{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                <p class="mt-4 text-sm font-medium">{{ __('No employees found.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $employees->links() }}
    </div>
</div>

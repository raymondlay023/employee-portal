<x-app-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('employees.index') }}" wire:navigate class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('Employee Profile') }}</h1>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 flex items-start">
                <svg class="h-5 w-5 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-green-800 text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Column: Avatar & Quick Info -->
            <div class="col-span-1">
                <div class="card p-6 flex flex-col items-center text-center">
                    <div class="h-24 w-24 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-3xl font-bold shadow-inner mb-4">
                        {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $employee->first_name }} {{ $employee->last_name }}</h2>
                    <p class="text-sm font-medium text-brand-600 mt-1">{{ $employee->designation?->title ?? __('No Designation') }}</p>
                    
                    <div class="mt-4 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $employee->status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $employee->status ? __($employee->status) : __('Active') }}
                    </div>

                    <div class="w-full border-t border-gray-100 mt-6 pt-6">
                        <div class="flex items-center text-sm text-gray-500 mb-3">
                            <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                            </svg>
                            {{ $employee->department?->name ?? __('No Department') }}
                        </div>
                        <div class="flex items-center text-sm text-gray-500 mb-3">
                            <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                            {{ $employee->email ?? __('N/A') }}
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="h-5 w-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                            {{ $employee->phone ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Full Details -->
            <div class="col-span-1 md:col-span-2 space-y-6">
                <!-- Employment Details -->
                <div class="card p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">{{ __('Employment Details') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Employee ID') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $employee->employee_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Joined Date') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $employee->joined_at ? $employee->joined_at->translatedFormat('j F Y') : __('N/A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Department') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $employee->department?->name ?? __('N/A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Designation') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $employee->designation?->title ?? __('N/A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Gender') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $employee->gender == 'M' ? __('Male') : ($employee->gender == 'F' ? __('Female') : __('N/A')) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Account Type') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $employee->account_type ?? __('N/A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Branch') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $employee->branch ?? __('N/A') }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Skills & Tags (If exists) -->
                @if($employee->skills && count($employee->skills) > 0)
                <div class="card p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">{{ __('Skills & Tags') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($employee->skills as $skill)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-brand-50 text-brand-700 border border-brand-100">
                                {{ $skill }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

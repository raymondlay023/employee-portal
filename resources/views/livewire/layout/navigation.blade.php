<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;
use App\Authorization\Permissions;

new class extends Component
{
    public array $personalLinks = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'params' => [],
            'pattern' => 'dashboard',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />',
        ],
        [
            'label' => 'My Attendance',
            'route' => 'attendance.index',
            'params' => [],
            'pattern' => 'attendance.*',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        [
            'label' => 'My Leaves',
            'route' => 'leave-requests.index',
            'params' => ['scope' => 'personal'],
            'pattern' => 'leave-requests.*',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />',
        ],
        [
            'label' => 'Daily Work Logs',
            'route' => 'daily-work-logs.index',
            'params' => [],
            'pattern' => 'daily-work-logs.*',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
        ],
    ];

    public array $managementLinks = [
        [
            'label' => 'Employee Directory',
            'route' => 'employees.index',
            'params' => [],
            'pattern' => 'employees.*',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
            'permission' => Permissions::MANAGE_EMPLOYEES,
        ],
        [
            'label' => 'Attendance Report',
            'route' => 'hr.attendance-report',
            'params' => [],
            'pattern' => 'hr.attendance-report',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />',
            'permission' => Permissions::VIEW_ANY_ATTENDANCE,
        ],
        [
            'label' => 'Leave Approvals',
            'route' => 'leave-requests.index',
            'params' => ['scope' => 'company'],
            'pattern' => 'leave-requests.*',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'permission' => Permissions::MANAGE_LEAVES,
        ],
        [
            'label' => 'API Logs',
            'route' => 'system.api-logs',
            'params' => [],
            'pattern' => 'system.api-logs',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />',
            'permission' => Permissions::VIEW_API_LOGS,
        ],
    ];

    /**
     * Set the application language.
     */
    public function setLanguage(string $locale): void
    {
        if (in_array($locale, ['en', 'id'], true)) {
            session(['locale' => $locale]);
            
            if (auth()->check()) {
                $user = auth()->user();
                $user->locale = $locale;
                $user->save();
            }
            
            $this->redirect(request()->header('Referer', '/dashboard'), navigate: true);
        }
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>
<div class="h-full">
    <!-- Off-canvas menu for mobile -->
    <div x-show="sidebarOpen" class="relative z-40 md:hidden" role="dialog" aria-modal="true">
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-600 bg-opacity-75"></div>
    
        <div class="fixed inset-0 z-40 flex">
            <div x-show="sidebarOpen" @click.away="sidebarOpen = false" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex w-full max-w-xs flex-1 flex-col bg-white pt-5 pb-4 shadow-xl">
                
                <div x-show="sidebarOpen" class="absolute top-0 right-0 -mr-12 pt-2">
                    <button type="button" @click="sidebarOpen = false" class="ml-1 flex h-10 w-10 items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Close sidebar</span>
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
    
                <div class="flex flex-shrink-0 items-center px-4">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center space-x-2">
                        <x-application-logo class="block h-8 w-auto fill-current text-brand-600" />
                        <span class="text-xl font-bold text-slate-900 tracking-tight">Employee Portal</span>
                    </a>
                </div>
                <div class="mt-8 h-0 flex-1 overflow-y-auto">
                    <nav class="space-y-1 px-2">
                        <!-- Mobile Links -->
                        @php
                            $hasManagementAccess = auth()->user()->can(\App\Authorization\Permissions::ACCESS_HR_PORTAL);
                        @endphp

                        <div class="space-y-6">
                            <!-- Personal Portal Section -->
                            <div class="space-y-1">
                                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider px-2 mb-2">My Portal</div>
                                @foreach($personalLinks as $link)
                                    <a href="{{ route($link['route'], $link['params']) }}" @click="sidebarOpen = false" wire:navigate class="{{ request()->routeIs($link['pattern']) && (empty($link['params']['scope']) || request('scope') === $link['params']['scope']) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors">
                                        <svg class="{{ request()->routeIs($link['pattern']) && (empty($link['params']['scope']) || request('scope') === $link['params']['scope']) ? 'text-brand-700' : 'text-slate-400 group-hover:text-slate-500' }} mr-4 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            {!! $link['icon'] !!}
                                        </svg>
                                        {{ __($link['label']) }}
                                    </a>
                                @endforeach
                            </div>

                            <!-- Management Section -->
                            @if($hasManagementAccess)
                                <div class="space-y-1 pt-4 border-t border-slate-100">
                                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider px-2 mb-2">HR Management</div>
                                    @foreach($managementLinks as $link)
                                        @if(!$link['permission'] || auth()->user()->can($link['permission']))
                                            <a href="{{ route($link['route'], $link['params']) }}" @click="sidebarOpen = false" wire:navigate class="{{ request()->routeIs($link['pattern']) && (empty($link['params']['scope']) || request('scope') === $link['params']['scope']) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors">
                                                <svg class="{{ request()->routeIs($link['pattern']) && (empty($link['params']['scope']) || request('scope') === $link['params']['scope']) ? 'text-brand-700' : 'text-slate-400 group-hover:text-slate-500' }} mr-4 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    {!! $link['icon'] !!}
                                                </svg>
                                                {{ __($link['label']) }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </nav>
                </div>
                
                <div class="px-4 py-3 border-t border-slate-200 flex items-center justify-between text-sm">
                    <span class="text-slate-500 font-medium">{{ __('Language') }}</span>
                    <div class="inline-flex rounded-md shadow-sm">
                        <button wire:click="setLanguage('en')" class="px-3 py-1 text-xs font-semibold rounded-l-md {{ app()->getLocale() === 'en' ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100' }} border border-slate-200 transition-colors">EN</button>
                        <button wire:click="setLanguage('id')" class="px-3 py-1 text-xs font-semibold rounded-r-md {{ app()->getLocale() === 'id' ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100' }} border-t border-b border-r border-slate-200 transition-colors">ID</button>
                    </div>
                </div>
                <div class="flex flex-shrink-0 border-t border-slate-200 p-4">
                    <a href="{{ route('profile') }}" wire:navigate class="group block flex-shrink-0 w-full">
                        <div class="flex items-center">
                            <div>
                                <div class="inline-block h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-slate-700 group-hover:text-slate-900" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></p>
                                <p class="text-xs font-medium text-slate-500 group-hover:text-slate-700">{{ __('View profile') }}</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="p-4 border-t border-slate-100">
                    <button wire:click="logout" class="flex w-full items-center px-2 py-2 text-base font-medium text-red-600 hover:bg-red-50 rounded-md transition-colors">
                        <svg class="mr-4 h-6 w-6 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                        {{ __('Log Out') }}
                    </button>
                </div>
            </div>
            <div class="w-14 flex-shrink-0" aria-hidden="true"></div>
        </div>
    </div>
    
    <!-- Static sidebar for desktop -->
    <div class="hidden md:flex md:flex-shrink-0 md:w-64 bg-white border-r border-slate-200 z-10 shadow-sm relative h-full">
        <div class="flex flex-grow flex-col overflow-y-auto pt-5 pb-4">
            <div class="flex flex-shrink-0 items-center px-6">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center space-x-3">
                    <x-application-logo class="block h-8 w-auto fill-current text-brand-600" />
                    <span class="text-xl font-bold text-slate-900 tracking-tight">Employee Portal</span>
                </a>
            </div>
            <div class="mt-8 flex flex-grow flex-col">
                <nav class="flex-1 space-y-2 px-4 bg-white">
                    <!-- Desktop Links -->
                    @php
                        $hasManagementAccess = auth()->user()->can(\App\Authorization\Permissions::ACCESS_HR_PORTAL);
                    @endphp

                    <div class="space-y-6">
                        <!-- Personal Portal Section -->
                        <div class="space-y-1">
                            <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">My Portal</div>
                            @foreach($personalLinks as $link)
                                <a href="{{ route($link['route'], $link['params']) }}" wire:navigate class="{{ request()->routeIs($link['pattern']) && (empty($link['params']['scope']) || request('scope') === $link['params']['scope']) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                                    <svg class="{{ request()->routeIs($link['pattern']) && (empty($link['params']['scope']) || request('scope') === $link['params']['scope']) ? 'text-brand-700' : 'text-slate-400 group-hover:text-slate-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        {!! $link['icon'] !!}
                                    </svg>
                                    {{ __($link['label']) }}
                                </a>
                            @endforeach
                        </div>

                        <!-- Management Section -->
                        @if($hasManagementAccess)
                            <div class="space-y-1 pt-4 border-t border-slate-100">
                                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">HR Management</div>
                                @foreach($managementLinks as $link)
                                    @if(!$link['permission'] || auth()->user()->can($link['permission']))
                                        <a href="{{ route($link['route'], $link['params']) }}" wire:navigate class="{{ request()->routeIs($link['pattern']) && (empty($link['params']['scope']) || request('scope') === $link['params']['scope']) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                                            <svg class="{{ request()->routeIs($link['pattern']) && (empty($link['params']['scope']) || request('scope') === $link['params']['scope']) ? 'text-brand-700' : 'text-slate-400 group-hover:text-slate-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                {!! $link['icon'] !!}
                                            </svg>
                                            {{ __($link['label']) }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </nav>
            </div>
            
            <div class="px-4 py-3 border-t border-slate-200 flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">{{ __('Language') }}</span>
                <div class="inline-flex rounded-md shadow-sm">
                    <button wire:click="setLanguage('en')" class="px-2.5 py-1 text-[10px] font-bold rounded-l-md {{ app()->getLocale() === 'en' ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100' }} border border-slate-200 transition-colors">EN</button>
                    <button wire:click="setLanguage('id')" class="px-2.5 py-1 text-[10px] font-bold rounded-r-md {{ app()->getLocale() === 'id' ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100' }} border-t border-b border-r border-slate-200 transition-colors">ID</button>
                </div>
            </div>
            <div class="flex flex-col border-t border-slate-200">
                <a href="{{ route('profile') }}" wire:navigate class="group block w-full flex-shrink-0 p-4 hover:bg-slate-50 transition-colors">
                    <div class="flex items-center">
                        <div>
                            <div class="inline-block h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-slate-700 group-hover:text-slate-900" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></p>
                            <p class="text-xs font-medium text-slate-500 group-hover:text-slate-700">{{ __('View profile') }}</p>
                        </div>
                    </div>
                </a>
                <div class="p-4 border-t border-slate-100">
                    <button wire:click="logout" class="flex w-full items-center px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="mr-3 h-5 w-5 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                        {{ __('Log Out') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

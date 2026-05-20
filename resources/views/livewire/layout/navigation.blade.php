<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
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
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>
    
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
                        <span class="text-xl font-bold text-gray-900 tracking-tight">HR Portal</span>
                    </a>
                </div>
                <div class="mt-8 h-0 flex-1 overflow-y-auto">
                    <nav class="space-y-1 px-2">
                        <!-- Mobile Links -->
                        <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" wire:navigate class="{{ request()->routeIs('dashboard') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors">
                            <svg class="{{ request()->routeIs('dashboard') ? 'text-brand-700' : 'text-gray-400 group-hover:text-gray-500' }} mr-4 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            Dashboard
                        </a>
                        
                        <a href="{{ route('employees.index') }}" @click="sidebarOpen = false" wire:navigate class="{{ request()->routeIs('employees.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors">
                            <svg class="{{ request()->routeIs('employees.*') ? 'text-brand-700' : 'text-gray-400 group-hover:text-gray-500' }} mr-4 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            Employees
                        </a>
    
                        <a href="{{ route('attendance.index') }}" @click="sidebarOpen = false" wire:navigate class="{{ request()->routeIs('attendance.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors">
                            <svg class="{{ request()->routeIs('attendance.*') ? 'text-brand-700' : 'text-gray-400 group-hover:text-gray-500' }} mr-4 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Attendance
                        </a>
    
                        <a href="{{ route('leave-requests.index') }}" @click="sidebarOpen = false" wire:navigate class="{{ request()->routeIs('leave-requests.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors">
                            <svg class="{{ request()->routeIs('leave-requests.*') ? 'text-brand-700' : 'text-gray-400 group-hover:text-gray-500' }} mr-4 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                            </svg>
                            Leave Requests
                        </a>
                    </nav>
                </div>
                
                <div class="flex flex-shrink-0 border-t border-gray-200 p-4">
                    <a href="{{ route('profile') }}" wire:navigate class="group block flex-shrink-0 w-full">
                        <div class="flex items-center">
                            <div>
                                <div class="inline-block h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></p>
                                <p class="text-xs font-medium text-gray-500 group-hover:text-gray-700">View profile</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="p-4 border-t border-gray-100">
                    <button wire:click="logout" class="flex w-full items-center px-2 py-2 text-base font-medium text-red-600 hover:bg-red-50 rounded-md transition-colors">
                        <svg class="mr-4 h-6 w-6 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                        Log Out
                    </button>
                </div>
            </div>
            <div class="w-14 flex-shrink-0" aria-hidden="true"></div>
        </div>
    </div>
    
    <!-- Static sidebar for desktop -->
    <div class="hidden md:flex md:flex-shrink-0 md:w-64 bg-white border-r border-gray-200 z-10 shadow-sm relative h-full">
        <div class="flex flex-grow flex-col overflow-y-auto pt-5 pb-4">
            <div class="flex flex-shrink-0 items-center px-6">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center space-x-3">
                    <x-application-logo class="block h-8 w-auto fill-current text-brand-600" />
                    <span class="text-2xl font-bold text-gray-900 tracking-tight">HR Portal</span>
                </a>
            </div>
            <div class="mt-8 flex flex-grow flex-col">
                <nav class="flex-1 space-y-2 px-4 bg-white">
                    <!-- Desktop Links -->
                    <a href="{{ route('dashboard') }}" wire:navigate class="{{ request()->routeIs('dashboard') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="{{ request()->routeIs('dashboard') ? 'text-brand-700' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        Dashboard
                    </a>
                    
                    <a href="{{ route('employees.index') }}" wire:navigate class="{{ request()->routeIs('employees.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="{{ request()->routeIs('employees.*') ? 'text-brand-700' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        Employees
                    </a>
    
                    <a href="{{ route('attendance.index') }}" wire:navigate class="{{ request()->routeIs('attendance.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="{{ request()->routeIs('attendance.*') ? 'text-brand-700' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Attendance
                    </a>
    
                    <a href="{{ route('leave-requests.index') }}" wire:navigate class="{{ request()->routeIs('leave-requests.*') ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                        <svg class="{{ request()->routeIs('leave-requests.*') ? 'text-brand-700' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                        </svg>
                        Leave Requests
                    </a>
                </nav>
            </div>
            
            <div class="flex flex-col border-t border-gray-200">
                <a href="{{ route('profile') }}" wire:navigate class="group block w-full flex-shrink-0 p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center">
                        <div>
                            <div class="inline-block h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></p>
                            <p class="text-xs font-medium text-gray-500 group-hover:text-gray-700">View profile</p>
                        </div>
                    </div>
                </a>
                <div class="p-4 border-t border-gray-100">
                    <button wire:click="logout" class="flex w-full items-center px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="mr-3 h-5 w-5 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                        Log Out
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


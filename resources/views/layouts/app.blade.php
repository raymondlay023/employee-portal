<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-slate-50">
        <!-- App Wrapper -->
        <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden bg-slate-50">
            
            <!-- Sidebar Navigation -->
            <livewire:layout.navigation />

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col overflow-y-auto w-full relative">
                
                <!-- Mobile Header -->
                <div class="md:hidden flex items-center justify-between bg-white border-b border-gray-200 p-4 sticky top-0 z-10">
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" wire:navigate>
                            <x-application-logo class="block h-8 w-auto fill-current text-brand-600" />
                        </a>
                    </div>
                    <button @click="sidebarOpen = true" class="p-2 -mr-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <!-- Page Heading -->
                @if (isset($header))
                    <header class="max-w-7xl w-full mx-auto pt-8 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </header>
                @endif

                <!-- Page Content -->
                <main class="flex-1 max-w-7xl w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>

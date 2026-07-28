<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HR Portal') }} - Login</title>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=6">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=6">
        <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}?v=6">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900">
        <div class="min-h-screen flex">
            <!-- Left Side: Branding / Imagery -->
            <div class="hidden lg:flex lg:w-1/2 relative bg-brand-900 overflow-hidden items-center justify-center shadow-2xl z-10">
                <!-- Abstract Background Shapes -->
                <div class="absolute inset-0 bg-gradient-to-br from-brand-800 to-brand-950"></div>
                <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-brand-600 opacity-20 blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-72 h-72 rounded-full bg-brand-400 opacity-20 blur-3xl" style="animation-delay: 2s;"></div>
                
                <div class="relative z-10 p-12 text-center text-white">
                    <div class="bg-white/10 p-6 rounded-3xl backdrop-blur-md border border-white/20 inline-block mb-8 shadow-xl">
                        <x-application-logo class="w-20 h-20 text-white" />
                    </div>
                    <h1 class="text-5xl font-extrabold mb-6 tracking-tight drop-shadow-sm">Employee Portal</h1>
                    <p class="text-brand-100 text-xl max-w-md mx-auto leading-relaxed font-medium">Access your personalized workspace, manage your attendance, and request leave seamlessly.</p>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 relative">
                <!-- Mobile Background Decoration -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-brand-100 rounded-full blur-3xl opacity-50 -z-10 lg:hidden"></div>
                
                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>

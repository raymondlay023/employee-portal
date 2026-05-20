<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-10 lg:text-left">
        <div class="lg:hidden flex justify-center mb-8">
            <div class="bg-brand-50 p-4 rounded-2xl shadow-sm border border-brand-100">
                <x-application-logo class="w-14 h-14 text-brand-600" />
            </div>
        </div>
        <h2 class="text-4xl font-extrabold text-slate-900 tracking-tight">Welcome back</h2>
        <p class="mt-3 text-base text-slate-500 font-medium">Please enter your credentials to access your account.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form wire:submit="login" class="space-y-6">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Email address</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none transition-colors group-focus-within:text-brand-500">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-brand-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input wire:model="form.email" id="email" type="email" class="block w-full pl-11 px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 font-medium shadow-sm placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all" placeholder="you@company.com" required autofocus autocomplete="username">
            </div>
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-bold text-brand-600 hover:text-brand-700 transition-colors">Forgot password?</a>
                @endif
            </div>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-brand-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input wire:model="form.password" id="password" type="password" class="block w-full pl-11 px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 font-medium shadow-sm placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-2">
            <div class="flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="h-4.5 w-4.5 text-brand-600 focus:ring-brand-500 border-slate-300 rounded cursor-pointer transition-colors">
                <label for="remember" class="ml-2.5 block text-sm font-medium text-slate-700 cursor-pointer">Remember me for 30 days</label>
            </div>
        </div>

        <div class="pt-4">
            <button type="submit" class="w-full flex justify-center items-center space-x-2 py-3.5 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all transform hover:-translate-y-0.5 active:translate-y-0 relative overflow-hidden group">
                <span class="relative z-10">Sign In Securely</span>
                <svg class="w-5 h-5 relative z-10 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
                <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-in-out"></div>
            </button>
        </div>
    </form>
</div>

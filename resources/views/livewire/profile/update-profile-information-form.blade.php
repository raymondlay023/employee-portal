<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $locale = 'en';
    
    // Custom states for 6-digit OTP verification
    public string $verificationCode = '';
    public bool $codeSent = false;
    public string $pendingEmail = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->locale = $user->locale ?? 'en';
    }

    /**
     * Send email verification code to the new pending email address.
     */
    public function sendEmailVerificationCode(): void
    {
        $user = Auth::user();

        // Validate the email
        $this->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        if ($this->email === $user->email) {
            $this->addError('email', __('This is already your current email address.'));
            return;
        }

        // Generate a 6-digit numeric OTP code
        $code = sprintf('%06d', mt_rand(100000, 999999));

        // Store OTP details in the user's active session
        session(['email_verification' => [
            'code' => $code,
            'email' => $this->email,
            'expires_at' => now()->addMinutes(15),
        ]]);

        // Dispatch the custom notification to the new email address
        try {
            \Illuminate\Support\Facades\Notification::route('mail', $this->email)
                ->notify(new \App\Notifications\EmailVerificationCodeNotification($code));
        } catch (\Exception $e) {
            $this->addError('email', __('Could not send verification email: :msg', ['msg' => $e->getMessage()]));
            return;
        }

        $this->codeSent = true;
        $this->pendingEmail = $this->email;
        $this->verificationCode = ''; // clear input

        session()->flash('verification_status', 'code-sent');
    }

    /**
     * Cancel the pending email change and reset component state.
     */
    public function cancelEmailChange(): void
    {
        $user = Auth::user();
        $this->email = $user->email;
        $this->codeSent = false;
        $this->verificationCode = '';
        $this->pendingEmail = '';
        
        session()->forget('email_verification');
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        
        $this->validate([
            'locale' => ['required', 'string', Rule::in(['en', 'id'])],
        ]);

        $localeChanged = $this->locale !== ($user->locale ?? 'en');
        $emailChanged = $this->email !== $user->email;

        if ($emailChanged) {
            // First, run standard validation
            $validated = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            ]);

            // Ensure they have initiated the OTP flow for this email
            if (!$this->codeSent || $this->email !== $this->pendingEmail) {
                $this->addError('email', __('Please request a verification code for this new email address.'));
                return;
            }

            // Retrieve and validate stored OTP details
            $verification = session('email_verification');
            if (!$verification || $verification['email'] !== $this->email) {
                $this->addError('verificationCode', __('Verification session has expired. Please request a new code.'));
                return;
            }

            if (now()->greaterThan($verification['expires_at'])) {
                $this->addError('verificationCode', __('This verification code has expired. Please request a new one.'));
                return;
            }

            if ($this->verificationCode !== $verification['code']) {
                $this->addError('verificationCode', __('The 6-digit verification code is incorrect.'));
                return;
            }

            // Verification succeeded! Update name and email, then mark as verified
            $user->name = $this->name;
            $user->email = $this->email;
            $user->locale = $this->locale;
            $user->email_verified_at = now();
            $user->save();
            session(['locale' => $this->locale]);

            // Clear session & reset component states
            session()->forget('email_verification');
            $this->codeSent = false;
            $this->verificationCode = '';
            $this->pendingEmail = '';
        } else {
            // Name-only update: Standard validation & save
            $validated = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            ]);

            $user->name = $this->name;
            $user->locale = $this->locale;
            $user->save();
            session(['locale' => $this->locale]);
        }

        $this->dispatch('profile-updated', name: $user->name);

        if ($localeChanged) {
            $this->redirect(route('profile'), navigate: true);
        }
    }
}; ?>

<section class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
    <header class="border-b border-slate-100 pb-4 mb-6">
        <h2 class="text-xl font-semibold text-slate-950 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __("Update your account's display name and primary email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="space-y-6">
        <!-- Display Name -->
        <div>
            <x-input-label for="name" :value="__('Display Name')" class="text-slate-700 font-medium" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <!-- Primary Email -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" class="text-slate-700 font-medium" />
            
            <div class="relative">
                <x-text-input 
                    wire:model="email" 
                    id="email" 
                    name="email" 
                    type="email" 
                    class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 {{ $codeSent ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : '' }}" 
                    required 
                    autocomplete="username" 
                    :disabled="$codeSent" 
                />
                
                @if ($codeSent)
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-amber-500 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                        </svg>
                    </div>
                @endif
            </div>
            
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            <!-- Dynamic Verification Banner and Inputs -->
            @if ($email !== auth()->user()->email)
                <div class="mt-6 rounded-2xl p-5 border {{ $codeSent ? 'border-amber-100 bg-amber-50/30' : 'border-indigo-100 bg-indigo-50/30' }} transition-all duration-300">
                    @if (!$codeSent)
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                            </svg>
                            <div class="space-y-3">
                                <h4 class="font-medium text-slate-900">{{ __('Email Verification Required') }}</h4>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    {{ __('You are modifying your email from') }} <strong class="text-slate-800">{{ auth()->user()->email }}</strong> {{ __('to') }} <strong class="text-slate-800">{{ $email }}</strong>. {{ __('To complete this change, we will send a 6-digit verification code to the new address to confirm ownership.') }}
                                </p>
                                <div class="flex items-center gap-3">
                                    <button 
                                        wire:click.prevent="sendEmailVerificationCode" 
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg wire:loading wire:target="sendEmailVerificationCode" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ __('Send Code to') }} {{ $email }}
                                    </button>
                                    
                                    <button 
                                        wire:click.prevent="cancelEmailChange"
                                        class="text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors"
                                    >
                                        {{ __('Cancel Change') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <div class="space-y-4 w-full">
                                <div class="space-y-1">
                                    <h4 class="font-medium text-slate-900 flex items-center justify-between">
                                        <span>{{ __('Enter Verification Code') }}</span>
                                        <span class="text-[10px] uppercase font-bold text-amber-600 bg-amber-100/60 px-2 py-0.5 rounded-full">{{ __('Awaiting OTP') }}</span>
                                    </h4>
                                    <p class="text-xs text-slate-600 leading-relaxed">
                                        {{ __('A 6-digit confirmation code was sent to') }} <strong class="text-slate-800">{{ $pendingEmail }}</strong>. {{ __('Please enter it below within 15 minutes to save your email change.') }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <x-input-label for="verificationCode" :value="__('6-Digit Verification Code')" class="text-slate-700 text-xs font-semibold uppercase tracking-wider" />
                                    <div class="flex gap-3">
                                        <x-text-input 
                                            wire:model="verificationCode" 
                                            id="verificationCode" 
                                            type="text" 
                                            class="block w-48 text-center text-lg font-bold tracking-[0.25em] rounded-xl border-amber-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50" 
                                            placeholder="XXXXXX" 
                                            maxlength="6" 
                                            required 
                                        />
                                        
                                        <button 
                                            wire:click.prevent="sendEmailVerificationCode" 
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-xl text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors border border-indigo-100"
                                        >
                                            <svg wire:loading wire:target="sendEmailVerificationCode" class="animate-spin h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ __('Resend Code') }}
                                        </button>
                                        
                                        <button 
                                            wire:click.prevent="cancelEmailChange"
                                            class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors border border-slate-200"
                                        >
                                            {{ __('Cancel Change') }}
                                        </button>
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('verificationCode')" />
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Preferred Language -->
        <div>
            <x-input-label for="locale" :value="__('Preferred Language')" class="text-slate-700 font-medium" />
            <select wire:model="locale" id="locale" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50">
                <option value="en">{{ __('English') }}</option>
                <option value="id">{{ __('Bahasa Indonesia') }}</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('locale')" />
        </div>

        <!-- Submission Controls -->
        <div class="flex items-center gap-4 border-t border-slate-100 pt-5">
            @if ($email === auth()->user()->email)
                <!-- Standard Save Button (Name updates only) -->
                <x-primary-button class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 transition-all font-semibold">
                    {{ __('Save Display Name') }}
                </x-primary-button>
            @else
                <!-- Verify & Save Email (Email change mode) -->
                @if ($codeSent)
                    <x-primary-button class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800 transition-all font-semibold">
                        {{ __('Verify & Save Email') }}
                    </x-primary-button>
                @else
                    <!-- Disabled button prompting them to verify first -->
                    <button 
                        type="button" 
                        disabled 
                        class="px-5 py-2.5 text-sm font-semibold rounded-xl text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed"
                    >
                        {{ __('Verification Required') }}
                    </button>
                @endif
            @endif

            <x-action-message class="text-sm font-medium text-emerald-600 flex items-center gap-1" on="profile-updated">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ __('Profile saved successfully.') }}
            </x-action-message>
        </div>
    </form>
</section>

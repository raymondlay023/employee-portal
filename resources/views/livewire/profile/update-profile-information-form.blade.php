<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $locale = 'en';
    public $avatar;
    
    // Custom states for email change modal
    public string $newEmail = '';
    public string $verificationCode = '';
    public bool $codeSent = false;

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
     * Initiate the email change process by sending an OTP.
     */
    public function initiateEmailChange(): void
    {
        $user = Auth::user();

        $this->validate([
            'newEmail' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->id)],
        ]);

        if ($this->newEmail === $user->email) {
            $this->addError('newEmail', __('This is already your current email address.'));
            return;
        }

        $code = sprintf('%06d', mt_rand(100000, 999999));

        session(['email_verification' => [
            'code' => $code,
            'email' => $this->newEmail,
            'expires_at' => now()->addMinutes(15),
        ]]);

        try {
            \Illuminate\Support\Facades\Notification::route('mail', $this->newEmail)
                ->notify(new \App\Notifications\EmailVerificationCodeNotification($code));
        } catch (\Exception $e) {
            $this->addError('newEmail', __('Could not send verification email: :msg', ['msg' => $e->getMessage()]));
            return;
        }

        $this->codeSent = true;
        $this->verificationCode = '';
    }

    /**
     * Cancel the pending email change and reset modal state.
     */
    public function cancelEmailChange(): void
    {
        $this->codeSent = false;
        $this->verificationCode = '';
        $this->newEmail = '';
        $this->resetErrorBag();
        session()->forget('email_verification');
    }

    /**
     * Verify the OTP and save the new email.
     */
    public function verifyAndSaveEmail(): void
    {
        $user = Auth::user();

        if (!$this->codeSent) {
            return;
        }

        $verification = session('email_verification');
        if (!$verification || $verification['email'] !== $this->newEmail) {
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

        $user->email = $this->newEmail;
        $user->email_verified_at = now();
        $user->save();
        $this->email = $this->newEmail;

        session()->forget('email_verification');
        $this->codeSent = false;
        $this->verificationCode = '';
        $this->newEmail = '';
        
        $this->dispatch('email-updated');
        $this->dispatch('close-modal', 'change-email');
    }

    /**
     * Update the standard profile information (Name, Locale, Avatar).
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', Rule::in(['en', 'id'])],
            'avatar' => ['nullable', 'image', 'max:1024'],
        ]);

        $localeChanged = $this->locale !== ($user->locale ?? 'en');

        $user->name = $this->name;
        $user->locale = $this->locale;
        if ($this->avatar) {
            $path = $this->avatar->store('avatars', 'public');
            $user->avatar = $path;
        }
        $user->save();
        session(['locale' => $this->locale]);

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
            {{ __('Account Details') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __("Manage your account's display name, profile photo, and preferences.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="space-y-6">
        <!-- Avatar Upload -->
        <div>
            <x-input-label for="avatar" :value="__('Profile Photo')" class="text-slate-700 font-medium mb-3" />
            <div class="flex items-center gap-6">
                <div class="shrink-0">
                    @if ($avatar)
                        <img class="h-20 w-20 object-cover rounded-full border border-slate-200 shadow-sm" src="{{ $avatar->temporaryUrl() }}" alt="New Avatar Preview">
                    @elseif (auth()->user()->avatar)
                        <img class="h-20 w-20 object-cover rounded-full border border-slate-200 shadow-sm" src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}">
                    @else
                        <div class="h-20 w-20 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-500 shadow-sm">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                    @endif
                </div>
                <label class="block">
                    <span class="sr-only">Choose profile photo</span>
                    <input type="file" wire:model="avatar" accept="image/*" class="block w-full text-sm text-slate-500
                        file:mr-4 file:py-2.5 file:px-4
                        file:rounded-xl file:border-0
                        file:text-sm file:font-semibold
                        file:bg-indigo-50 file:text-indigo-700
                        hover:file:bg-indigo-100 transition-colors cursor-pointer
                        focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                    "/>
                </label>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <!-- Display Name -->
        <div>
            <x-input-label for="name" :value="__('Display Name')" class="text-slate-700 font-medium" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50" required autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <!-- Primary Email (Read-only + Change Modal Trigger) -->
        <div>
            <x-input-label :value="__('Email Address')" class="text-slate-700 font-medium mb-1" />
            <div class="flex items-center justify-between p-3 border border-slate-200 rounded-xl bg-slate-50">
                <div>
                    <p class="text-slate-900 font-medium">{{ $email }}</p>
                </div>
                <button 
                    type="button"
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'change-email')"
                    class="px-4 py-2 text-sm font-semibold text-indigo-700 bg-indigo-100 rounded-lg hover:bg-indigo-200 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 bg-white"
                >
                    {{ __('Change') }}
                </button>
            </div>
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
            <x-primary-button class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 transition-all font-semibold">
                {{ __('Save Changes') }}
            </x-primary-button>

            <x-action-message class="text-sm font-medium text-emerald-600 flex items-center gap-1" on="profile-updated">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ __('Saved successfully.') }}
            </x-action-message>
        </div>
    </form>

    <!-- Email Change Modal -->
    <x-modal name="change-email" :show="false" focusable>
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ __('Change Email Address') }}
            </h2>

            @if (!$codeSent)
                <div class="space-y-4">
                    <p class="text-sm text-slate-600">
                        {{ __('Please enter your new email address. We will send a verification code to confirm ownership.') }}
                    </p>
                    
                    <div>
                        <x-input-label for="newEmail" :value="__('New Email Address')" />
                        <x-text-input 
                            wire:model="newEmail" 
                            id="newEmail" 
                            type="email" 
                            class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50" 
                            placeholder="new@example.com"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('newEmail')" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button x-on:click="$dispatch('close-modal', 'change-email'); $wire.cancelEmailChange()">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        
                        <x-primary-button 
                            wire:click.prevent="initiateEmailChange" 
                            wire:loading.attr="disabled"
                            class="bg-indigo-600 hover:bg-indigo-700"
                        >
                            <svg wire:loading wire:target="initiateEmailChange" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('Send Verification Code') }}
                        </x-primary-button>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 flex gap-3">
                        <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ __('Enter Verification Code') }}</p>
                            <p class="text-xs text-slate-600 mt-1">
                                {{ __('A 6-digit confirmation code was sent to') }} <strong class="text-slate-800">{{ $newEmail }}</strong>. {{ __('Please enter it below.') }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="verificationCode" :value="__('6-Digit Code')" class="text-center font-bold text-slate-500 uppercase tracking-wider text-xs" />
                        <x-text-input 
                            wire:model="verificationCode" 
                            id="verificationCode" 
                            type="text" 
                            class="mt-1 block w-full text-center text-2xl font-bold tracking-[0.25em] rounded-xl border-indigo-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 py-3" 
                            placeholder="XXXXXX" 
                            maxlength="6" 
                        />
                        <x-input-error class="mt-2 text-center" :messages="$errors->get('verificationCode')" />
                    </div>

                    <div class="mt-6 flex justify-between items-center">
                        <button 
                            wire:click.prevent="initiateEmailChange" 
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors"
                        >
                            {{ __('Resend Code') }}
                        </button>
                        
                        <div class="flex gap-3">
                            <x-secondary-button x-on:click="$dispatch('close-modal', 'change-email'); $wire.cancelEmailChange()">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            
                            <x-primary-button 
                                wire:click.prevent="verifyAndSaveEmail" 
                                class="bg-emerald-600 hover:bg-emerald-700"
                            >
                                {{ __('Verify & Update') }}
                            </x-primary-button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-modal>
</section>

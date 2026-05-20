<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form');
    }

    public function test_display_name_can_be_updated_without_otp(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $this->actingAs($user);

        // Update name only
        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Updated Name')
            ->set('email', 'original@example.com')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('original@example.com', $user->email);
    }

    public function test_email_change_requires_verification_code(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $this->actingAs($user);

        // Attempt to update email directly without sending code first
        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Original Name')
            ->set('email', 'new@example.com')
            ->call('updateProfileInformation');

        $component->assertHasErrors(['email']);
        $this->assertSame('original@example.com', $user->refresh()->email);

        // Send verification code
        $component->call('sendEmailVerificationCode')
            ->assertHasNoErrors()
            ->assertSet('codeSent', true)
            ->assertSet('pendingEmail', 'new@example.com');

        // Confirm code was saved in session
        $verification = session('email_verification');
        $this->assertNotNull($verification);
        $this->assertSame('new@example.com', $verification['email']);
        $code = $verification['code'];

        // Attempt with wrong code
        $component->set('verificationCode', '000000')
            ->call('updateProfileInformation')
            ->assertHasErrors(['verificationCode']);
        $this->assertSame('original@example.com', $user->refresh()->email);

        // Update with correct code
        $component->set('verificationCode', $code)
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }


}

<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
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
            ->assertSeeVolt('profile.update-employee-information-form')
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

        $component = Volt::test('profile.update-profile-information-form')
            ->set('newEmail', 'new@example.com');

        // Send verification code
        $component->call('initiateEmailChange')
            ->assertHasNoErrors()
            ->assertSet('codeSent', true)
            ->assertSet('newEmail', 'new@example.com');

        // Confirm code was saved in session
        $verification = session('email_verification');
        $this->assertNotNull($verification);
        $this->assertSame('new@example.com', $verification['email']);
        $code = $verification['code'];

        // Update with correct code
        $component->set('verificationCode', $code)
            ->call('verifyAndSaveEmail')
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

    public function test_employee_information_can_be_updated(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $component = Volt::test('profile.update-employee-information-form')
            ->set('phone', '081234567890')
            ->set('gender', 'Male')
            ->set('skills', ['Laravel', 'Vue.js'])
            ->call('updateEmployeeInformation');

        $component->assertHasNoErrors();

        $employee->refresh();
        $this->assertSame('081234567890', $employee->phone);
        $this->assertSame('Male', $employee->gender);
        $this->assertSame(['Laravel', 'Vue.js'], $employee->skills);
    }

    public function test_avatar_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = File::image('avatar.jpg');

        $component = Volt::test('profile.update-profile-information-form')
            ->set('avatar', $file)
            ->call('updateProfileInformation');

        $component->assertHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }
}

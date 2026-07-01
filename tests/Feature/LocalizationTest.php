<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_set_language_via_session(): void
    {
        // Initially, the locale should be the default 'en'
        $this->get('/login')->assertStatus(200);
        $this->assertEquals('en', App::getLocale());

        // Call setLanguage action in the navigation component (using actingAs because sidebar rendering requires a user)
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('layout.navigation')
            ->call('setLanguage', 'id');

        $component->assertSessionHas('locale', 'id');

        // Log out the user to simulate subsequent guest requests
        auth()->logout();

        // Check if subsequent request sets the locale correctly
        $this->withSession(['locale' => 'id'])
            ->get('/login')
            ->assertStatus(200);

        $this->assertEquals('id', App::getLocale());
    }

    public function test_authenticated_user_language_persists_in_database(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $this->actingAs($user);

        // Update language via navigation component
        Volt::test('layout.navigation')
            ->call('setLanguage', 'id');

        $user->refresh();
        $this->assertEquals('id', $user->locale);

        // Subsequent requests should load the locale from database
        $this->get('/dashboard')->assertOk();
        $this->assertEquals('id', App::getLocale());
    }

    public function test_profile_form_updates_preferred_language(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $this->actingAs($user);

        // Update locale in profile form
        Volt::test('profile.update-profile-information-form')
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('locale', 'id')
            ->call('updateProfileInformation')
            ->assertHasNoErrors()
            ->assertRedirect(route('profile'));

        $user->refresh();
        $this->assertEquals('id', $user->locale);
        $this->assertEquals('id', session('locale'));
    }

    public function test_invalid_locale_is_rejected_and_not_applied(): void
    {
        // 1. Check SetLocale middleware ignores invalid session locale
        $this->withSession(['locale' => 'fr'])
            ->get('/login')
            ->assertStatus(200);

        $this->assertEquals('en', App::getLocale());

        // 2. Check layout navigation action rejects invalid locale
        session()->forget('locale');
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('layout.navigation')
            ->call('setLanguage', 'fr');

        $this->assertNotEquals('fr', session('locale'));

        // 3. Check profile form rejects invalid locale via validation
        Volt::test('profile.update-profile-information-form')
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('locale', 'fr')
            ->call('updateProfileInformation')
            ->assertHasErrors(['locale']);

        $user->refresh();
        $this->assertEquals('en', $user->locale);
    }

    public function test_indonesian_translation_resolves_correctly(): void
    {
        App::setLocale('id');

        $this->assertEquals('Tindakan Keamanan Penting Diperlukan', __('Critical Security Action Required'));
        $this->assertEquals('Log Jam Manual', __('Manual Clock Log'));
        $this->assertEquals('Kehadiran - John Doe', __('Attendance - :name', ['name' => 'John Doe']));
    }
}


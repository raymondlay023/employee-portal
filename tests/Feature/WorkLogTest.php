<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DailyWorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class WorkLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the work logs page is displayed successfully and embeds the Volt component.
     */
    public function test_work_logs_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/daily-work-logs');

        $response->assertOk()
            ->assertSeeVolt('pages.work-logs');
    }

    /**
     * Test that the work logs component initializes with today's date and a default slot of 07:30 - 08:30.
     */
    public function test_component_initializes_with_default_time_slot(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs');

        $component->assertSet('date', now()->toDateString())
            ->assertCount('logs', 1);

        $logs = $component->get('logs');
        $this->assertSame('07:30', $logs[0]['start_time']);
        $this->assertSame('08:30', $logs[0]['end_time']);
        $this->assertNull($logs[0]['id']);
    }

    /**
     * Test that clicking "Add Next Hour" appends a new sequential hour slot.
     */
    public function test_can_add_next_chronological_hour_slot(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs')
            ->call('addHourSlot');

        $component->assertCount('logs', 2);
        
        $logs = $component->get('logs');
        $this->assertSame('07:30', $logs[0]['start_time']);
        $this->assertSame('08:30', $logs[0]['end_time']);
        $this->assertSame('08:30', $logs[1]['start_time']);
        $this->assertSame('09:30', $logs[1]['end_time']);
    }

    /**
     * Test that clicking "Remove Last Slot" deletes the last appended row.
     */
    public function test_can_remove_last_hour_slot(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs')
            ->call('addHourSlot')
            ->assertCount('logs', 2)
            ->call('removeLastSlot')
            ->assertCount('logs', 1);
    }

    /**
     * Test that the user cannot remove the only remaining hour slot.
     */
    public function test_cannot_remove_last_slot_if_only_one_exists(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs')
            ->assertCount('logs', 1)
            ->call('removeLastSlot')
            ->assertCount('logs', 1); // should still be 1
    }

    /**
     * Test validation requires an activity for every row.
     */
    public function test_saving_logs_requires_activity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs')
            ->set('logs.0.activity', '') // blank activity
            ->call('save');

        $component->assertHasErrors(['logs.0.activity']);
        $this->assertDatabaseEmpty('daily_work_logs');
    }

    /**
     * Test that filled timesheet slots can be successfully saved to the database.
     */
    public function test_can_save_work_logs_to_database(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs')
            ->set('logs.0.activity', 'Coding Features')
            ->set('logs.0.remarks', 'Worked on Daily Work Log feature')
            ->call('addHourSlot')
            ->set('logs.1.activity', 'Meeting')
            ->set('logs.1.remarks', 'Discussed timesheet system with managers')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('daily_work_logs', 2);
        
        $this->assertDatabaseHas('daily_work_logs', [
            'user_id' => $user->id,
            'start_time' => '07:30',
            'end_time' => '08:30',
            'activity' => 'Coding Features',
            'remarks' => 'Worked on Daily Work Log feature',
        ]);

        $this->assertDatabaseHas('daily_work_logs', [
            'user_id' => $user->id,
            'start_time' => '08:30',
            'end_time' => '09:30',
            'activity' => 'Meeting',
            'remarks' => 'Discussed timesheet system with managers',
        ]);
    }
}

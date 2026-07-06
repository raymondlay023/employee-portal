<?php

namespace Tests\Feature;

use App\Models\DailyWorkLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
     * Test that the work logs component initializes with today's date and an empty default slot.
     */
    public function test_component_initializes_with_empty_time_slot(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs');

        $component->assertSet('date', now()->toDateString())
            ->assertCount('logs', 1);

        $logs = $component->get('logs');
        $this->assertSame('', $logs[0]['start_time']);
        $this->assertSame('', $logs[0]['end_time']);
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
            ->set('logs.0.start_time', '07:30')
            ->set('logs.0.end_time', '08:30')
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
        \Storage::fake('public');
        $file = UploadedFile::fake()->image('proof.jpg');

        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs')
            ->set('logs.0.start_time', '07:30')
            ->set('logs.0.end_time', '08:30')
            ->set('logs.0.activity', '') // blank activity
            ->set('newProofs.0', $file)
            ->call('save');

        $component->assertHasErrors(['logs.0.activity']);
        $this->assertDatabaseEmpty('daily_work_logs');
    }

    /**
     * Test that filled timesheet slots can be successfully saved to the database.
     */
    public function test_can_save_work_logs_to_database(): void
    {
        \Storage::fake('public');
        $file1 = UploadedFile::fake()->image('proof1.jpg');
        $file2 = UploadedFile::fake()->image('proof2.jpg');

        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs')
            ->set('logs.0.start_time', '07:30')
            ->set('logs.0.end_time', '08:30')
            ->set('logs.0.activity', 'Coding Features')
            ->set('logs.0.remarks', 'Worked on Daily Work Log feature')
            ->set('newProofs.0', $file1)
            ->call('addHourSlot')
            ->set('logs.1.activity', 'Meeting')
            ->set('logs.1.remarks', 'Discussed timesheet system with managers')
            ->set('newProofs.1', $file2)
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

    /**
     * Test that start and end time ranges can be overridden.
     */
    public function test_can_override_time_range_with_valid_times(): void
    {
        \Storage::fake('public');
        $file = UploadedFile::fake()->image('proof.jpg');

        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs')
            ->set('logs.0.start_time', '08:00')
            ->set('logs.0.end_time', '09:00')
            ->set('logs.0.activity', 'Coding')
            ->set('newProofs.0', $file)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('daily_work_logs', [
            'user_id' => $user->id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'activity' => 'Coding',
        ]);
    }

    /**
     * Test that overlapping time ranges cannot be saved.
     */
    public function test_cannot_save_overlapping_time_ranges(): void
    {
        \Storage::fake('public');
        $file1 = UploadedFile::fake()->image('proof1.jpg');
        $file2 = UploadedFile::fake()->image('proof2.jpg');

        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs')
            ->set('logs.0.start_time', '08:00')
            ->set('logs.0.end_time', '09:30')
            ->set('logs.0.activity', 'Task 1')
            ->set('newProofs.0', $file1)
            ->call('addHourSlot')
            ->set('logs.1.start_time', '09:00')
            ->set('logs.1.end_time', '10:00')
            ->set('logs.1.activity', 'Task 2')
            ->set('newProofs.1', $file2)
            ->call('save');

        $component->assertHasErrors(['logs.0.start_time', 'logs.1.start_time']);
        $this->assertDatabaseEmpty('daily_work_logs');
    }

    /**
     * Test that start time must be before end time.
     */
    public function test_cannot_save_if_start_time_is_after_end_time(): void
    {
        \Storage::fake('public');
        $file = UploadedFile::fake()->image('proof.jpg');

        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.work-logs')
            ->set('logs.0.start_time', '10:00')
            ->set('logs.0.end_time', '09:00')
            ->set('logs.0.activity', 'Task')
            ->set('newProofs.0', $file)
            ->call('save');

        $component->assertHasErrors(['logs.0.start_time']);
        $this->assertDatabaseEmpty('daily_work_logs');
    }

    /**
     * Test that users can upload an image proof.
     */
    public function test_can_upload_image_proof_for_time_slot(): void
    {
        \Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('screenshot.jpg');

        $component = Volt::test('pages.work-logs')
            ->set('logs.0.start_time', '08:00')
            ->set('logs.0.end_time', '09:00')
            ->set('logs.0.activity', 'Dev Work')
            ->set('newProofs.0', $file)
            ->call('save')
            ->assertHasNoErrors();

        $log = DailyWorkLog::first();
        $this->assertNotNull($log->proof_path);

        \Storage::disk('public')->assertExists($log->proof_path);
    }

    /**
     * Test that deleting a proof removes it from component state and requires a new proof to save.
     */
    public function test_deleting_proof_removes_it_from_state_and_requires_new_proof(): void
    {
        \Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file1 = UploadedFile::fake()->image('proof1.jpg');

        $component = Volt::test('pages.work-logs')
            ->set('logs.0.start_time', '07:30')
            ->set('logs.0.end_time', '08:30')
            ->set('logs.0.activity', 'Dev Work')
            ->set('newProofs.0', $file1)
            ->call('save')
            ->assertHasNoErrors();

        $log = DailyWorkLog::first();
        $this->assertNotNull($log->proof_path);
        \Storage::disk('public')->assertExists($log->proof_path);

        // Delete proof (clears component state)
        $component->call('deleteProof', 0);

        // Upload a new proof and save successfully
        $file2 = UploadedFile::fake()->image('proof2.jpg');
        $component->set('newProofs.0', $file2)
            ->call('save')
            ->assertHasNoErrors();

        $updatedLog = DailyWorkLog::first();
        $this->assertNotNull($updatedLog->proof_path);
        $this->assertNotEquals($log->proof_path, $updatedLog->proof_path);
        \Storage::disk('public')->assertExists($updatedLog->proof_path);
        \Storage::disk('public')->assertMissing($log->proof_path);
    }

    /**
     * Test that a user can undo a marked proof deletion and keep their original proof safe.
     */
    public function test_can_undo_marked_proof_deletion(): void
    {
        \Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('proof.jpg');

        $component = Volt::test('pages.work-logs')
            ->set('logs.0.start_time', '07:30')
            ->set('logs.0.end_time', '08:30')
            ->set('logs.0.activity', 'Dev Work')
            ->set('newProofs.0', $file)
            ->call('save')
            ->assertHasNoErrors();

        $log = DailyWorkLog::first();
        $this->assertNotNull($log->proof_path);
        \Storage::disk('public')->assertExists($log->proof_path);

        // Mark proof for deletion
        $component->call('deleteProof', 0);
        $this->assertTrue($component->get('pendingDeletions.0'));

        // Undo deletion
        $component->call('undoDeleteProof', 0);
        $this->assertNull($component->get('pendingDeletions.0'));

        // Save again and assert no changes or errors
        $component->call('save')
            ->assertHasNoErrors();

        $originalLog = DailyWorkLog::first();
        $this->assertSame($log->proof_path, $originalLog->proof_path);
        \Storage::disk('public')->assertExists($log->proof_path);
    }

    /**
     * Test that the weekly progress is correctly initialized and shifts upon navigation.
     */
    public function test_weekly_progress_initializes_and_navigates(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Pick a fixed Wednesday (e.g. 2026-07-01)
        $component = Volt::test('pages.work-logs')
            ->set('date', '2026-07-01'); // Wednesday

        $weekDays = $component->get('weekDays');
        $this->assertCount(7, $weekDays);

        // Sunday is 2026-06-28
        $this->assertSame('2026-06-28', $weekDays[0]['date']);
        $this->assertSame('Sun', $weekDays[0]['day_label']);
        $this->assertSame('28', $weekDays[0]['day_number']);

        // Wednesday (active date) is 2026-07-01
        $this->assertSame('2026-07-01', $weekDays[3]['date']);
        $this->assertTrue($weekDays[3]['is_active']);

        // Navigate to the previous week
        $component->call('previousWeek');
        $this->assertSame('2026-06-24', $component->get('date'));

        $newWeekDays = $component->get('weekDays');
        $this->assertSame('2026-06-21', $newWeekDays[0]['date']); // Previous Sunday

        // Navigate to the next week
        $component->call('nextWeek');
        $this->assertSame('2026-07-01', $component->get('date'));
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\ApiSyncLog;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ApiSyncLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.jpayroll.url', 'https://api.jpayroll.test');
        Config::set('services.jpayroll.key', 'test-api-key');
        Config::set('services.jpayroll.company_area', '10000');
    }

    private function createAdminUser(): User
    {
        $role = Role::findOrCreate('Admin');
        $permission = Permission::findOrCreate('sync attendance');
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function createEmployee(string $nik): Employee
    {
        $dept = Department::firstOrCreate(['name' => 'Test Dept']);
        return Employee::create([
            'employee_id' => $nik,
            'first_name'  => 'Test',
            'last_name'   => 'User',
            'department_id' => $dept->id,
            'status'      => 'active',
        ]);
    }

    public function test_manual_sync_from_controller_creates_sync_log(): void
    {
        $user = $this->createAdminUser();
        $this->createEmployee('07073');

        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response([
                'data' => [
                    ['NIK' => '07073', 'Name' => 'RAYMOND LAY', 'ShiftDate' => '01/05/2026', 'ABS' => '0', 'LT' => '0', 'CT' => '0', 'OP' => '0', 'HOS' => '0', 'WA' => '0', 'HOSWA' => '0'],
                ],
                'total' => '1',
            ], 200),
        ]);

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Attendance\SyncJpayroll::class)
            ->set('date_from', '2026-05-01')
            ->set('date_to', '2026-05-01')
            ->set('nik', '07073')
            ->call('sync')
            ->assertRedirect(route('attendance.index'));
        
        $this->assertDatabaseHas('api_sync_logs', [
            'api_name'             => 'jpayroll_attendance',
            'trigger_type'         => 'manual',
            'triggered_by_user_id' => $user->id,
            'status'               => 'success',
            'records_fetched'      => 1,
            'records_processed'    => 1,
            'records_failed'       => 0,
        ]);
    }

    public function test_command_failure_creates_failed_sync_log(): void
    {
        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response(['data' => [], 'total' => '0'], 200),
        ]);

        $this->artisan('jpayroll:sync-attendance', [
            '--date1'   => '01/05/2026',
            '--date2'   => '06/05/2026',
            '--trigger' => 'scheduled',
        ])->assertFailed();

        $this->assertDatabaseHas('api_sync_logs', [
            'api_name'             => 'jpayroll_attendance',
            'trigger_type'         => 'scheduled',
            'triggered_by_user_id' => null,
            'status'               => 'failed',
            'error_message'        => 'No attendance data fetched from JPayroll or an error occurred.',
        ]);
    }
}

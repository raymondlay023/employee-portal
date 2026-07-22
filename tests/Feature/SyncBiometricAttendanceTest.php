<?php

namespace Tests\Feature;

use App\Authorization\Permissions;
use App\Livewire\System\ApiLogs;
use App\Models\ApiSyncLog;
use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SyncBiometricAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.biometric_device.urls', [
            'http://192.168.6.96/iWsService',
            'http://192.168.6.97/iWsService',
        ]);
        Storage::fake('local');
    }

    private function createEmployee(string $employeeId, string $deptCode): Employee
    {
        $dept = Department::firstOrCreate(['name' => 'Test Dept', 'code' => $deptCode]);

        return Employee::create([
            'employee_id' => $employeeId,
            'first_name' => 'Test',
            'last_name' => 'User',
            'department_id' => $dept->id,
            'status' => 'active',
        ]);
    }

    private function sampleXmlDevice1(): string
    {
        return '<GetAttLogResponse>
    <Row>
        <PIN>10133006841</PIN>
        <DateTime>2026-07-20 08:33:45</DateTime>
        <Verified>1</Verified>
        <Status>0</Status>
        <WorkCode>0</WorkCode>
    </Row>
</GetAttLogResponse>';
    }

    private function sampleXmlDevice2(): string
    {
        return '<GetAttLogResponse>
    <Row>
        <PIN>10133006841</PIN>
        <DateTime>2026-07-20 17:57:51</DateTime>
        <Verified>1</Verified>
        <Status>0</Status>
        <WorkCode>0</WorkCode>
    </Row>
</GetAttLogResponse>';
    }

    public function test_sync_creates_attendance_logs_with_min_max_punches_from_both_devices(): void
    {
        // 2026-07-20 is today in the test context (controlled by Carbon/Now)
        $employee = $this->createEmployee('33006841', '101');

        Http::fake([
            'http://192.168.6.96/iWsService' => Http::response($this->sampleXmlDevice1(), 200),
            'http://192.168.6.97/iWsService' => Http::response($this->sampleXmlDevice2(), 200),
        ]);

        $this->artisan('device:sync-attendance', ['--days' => 7])->assertSuccessful();

        $this->assertDatabaseCount('attendance_logs', 1);
        $this->assertDatabaseHas('attendance_logs', [
            'employee_id' => $employee->employee_id,
            'clock_in_at' => '2026-07-20 01:33:45',
            'clock_out_at' => '2026-07-20 10:57:51',
        ]);
    }

    public function test_sync_is_idempotent_and_merges_with_existing_attendance_log(): void
    {
        $employee = $this->createEmployee('33006841', '101');

        // Seed an existing log for today in UTC (02:00:00 UTC = 09:00:00 GMT+7)
        AttendanceLog::create([
            'employee_id' => $employee->employee_id,
            'clock_in_at' => '2026-07-20 02:00:00',
            'clock_out_at' => '2026-07-20 09:00:00',
            'note' => 'Manual Entry',
        ]);

        Http::fake([
            'http://192.168.6.96/iWsService' => Http::response($this->sampleXmlDevice1(), 200),
            'http://192.168.6.97/iWsService' => Http::response($this->sampleXmlDevice2(), 200),
        ]);

        $this->artisan('device:sync-attendance', ['--days' => 7])->assertSuccessful();

        // Database count should still be 1 (idempotent / merge)
        $this->assertDatabaseCount('attendance_logs', 1);

        // Merged times: min(02:00, 09:00, 01:33, 10:57) = 01:33, max(02:00, 09:00, 01:33, 10:57) = 10:57
        $this->assertDatabaseHas('attendance_logs', [
            'employee_id' => $employee->employee_id,
            'clock_in_at' => '2026-07-20 01:33:45',
            'clock_out_at' => '2026-07-20 10:57:51',
            'note' => 'Manual Entry', // Preserves existing note
        ]);
    }

    public function test_sync_skips_punches_outside_date_limit(): void
    {
        $employee = $this->createEmployee('33006841', '101');

        // Punch date is 10 days ago (relative to local test time 2026-07-20)
        // XML content helper
        $oldXml = '<GetAttLogResponse>
    <Row>
        <PIN>10133006841</PIN>
        <DateTime>2026-07-10 08:33:45</DateTime>
        <Verified>1</Verified>
        <Status>0</Status>
        <WorkCode>0</WorkCode>
    </Row>
</GetAttLogResponse>';

        Http::fake([
            'http://192.168.6.96/iWsService' => Http::response($oldXml, 200),
            'http://192.168.6.97/iWsService' => Http::response('<GetAttLogResponse></GetAttLogResponse>', 200),
        ]);

        // Sync with a limit of 5 days (so a punch from 10 days ago should be ignored)
        $this->artisan('device:sync-attendance', ['--days' => 5])->assertSuccessful();

        $this->assertDatabaseCount('attendance_logs', 0);
    }

    public function test_sync_saves_raw_xml_payloads_to_storage_disk(): void
    {
        $this->createEmployee('33006841', '101');

        Http::fake([
            'http://192.168.6.96/iWsService' => Http::response($this->sampleXmlDevice1(), 200),
            'http://192.168.6.97/iWsService' => Http::response($this->sampleXmlDevice2(), 200),
        ]);

        $this->artisan('device:sync-attendance')->assertSuccessful();

        $syncLog = ApiSyncLog::where('api_name', 'biometric_device')->first();
        $this->assertNotNull($syncLog);
        $this->assertArrayHasKey('raw_payloads', $syncLog->parameters);

        $payloads = $syncLog->parameters['raw_payloads'];
        $this->assertArrayHasKey('192.168.6.96', $payloads);
        $this->assertArrayHasKey('192.168.6.97', $payloads);

        Storage::disk('local')->assertExists($payloads['192.168.6.96']);
        Storage::disk('local')->assertExists($payloads['192.168.6.97']);
    }

    public function test_admin_can_download_biometric_raw_payload(): void
    {
        Storage::put('biometric/raw_1_device_123.xml', '<raw>data</raw>');

        $user = User::factory()->create();
        $permission = Permission::findOrCreate(Permissions::VIEW_API_LOGS);
        $user->givePermissionTo($permission);

        $response = $this->actingAs($user)
            ->get(route('system.api-logs'));

        $response->assertSuccessful();

        // Call download payload through Livewire
        Livewire::actingAs($user)
            ->test(ApiLogs::class)
            ->call('downloadPayload', 'biometric/raw_1_device_123.xml')
            ->assertFileDownloaded('raw_1_device_123.xml');
    }

    public function test_unauthorized_user_cannot_download_biometric_raw_payload(): void
    {
        $user = User::factory()->create(); // No VIEW_API_LOGS permission

        Livewire::actingAs($user)
            ->test(ApiLogs::class)
            ->call('downloadPayload', 'biometric/raw_1_device_123.xml')
            ->assertStatus(403);
    }

    public function test_pruning_deletes_older_backups_and_updates_database_records(): void
    {
        // Re-creates storage fake for this method (clean slate)
        Storage::fake('local');

        // Create 2 sync logs: one 31 days old, one today
        $oldLog = ApiSyncLog::create([
            'api_name' => 'biometric_device',
            'trigger_type' => 'scheduled',
            'status' => 'success',
            'parameters' => [
                'days' => 7,
                'raw_payloads' => [
                    '192.168.6.96' => 'biometric/raw_old_device_1.xml',
                ],
            ],
        ]);
        $oldLog->created_at = now()->subDays(31);
        $oldLog->save();

        $newLog = ApiSyncLog::create([
            'api_name' => 'biometric_device',
            'trigger_type' => 'scheduled',
            'status' => 'success',
            'parameters' => [
                'days' => 7,
                'raw_payloads' => [
                    '192.168.6.96' => 'biometric/raw_new_device_1.xml',
                ],
            ],
        ]);

        Storage::put('biometric/raw_old_device_1.xml', '<old></old>');
        Storage::put('biometric/raw_new_device_1.xml', '<new></new>');

        // Run the prune command
        $this->artisan('device:prune-backups', ['--days' => 30])->assertSuccessful();

        // Verify old file deleted and log updated
        Storage::disk('local')->assertMissing('biometric/raw_old_device_1.xml');
        $oldLog->refresh();
        $this->assertArrayNotHasKey('raw_payloads', $oldLog->parameters);
        $this->assertTrue($oldLog->parameters['raw_payloads_pruned'] ?? false);

        // Verify new file exists and log NOT updated
        Storage::disk('local')->assertExists('biometric/raw_new_device_1.xml');
        $newLog->refresh();
        $this->assertArrayHasKey('raw_payloads', $newLog->parameters);
    }

    public function test_jpayroll_status_evaluates_biometric_presence_and_abnormalities(): void
    {
        $employee = $this->createEmployee('33006841', '101');

        // Case 1: Present in JPayroll but NO biometric punch and no telat -> resolves as Off Day (no conflict)
        $jp1 = JPayrollAttendance::create([
            'employee_id' => $employee->id,
            'shift_date' => '2026-07-20',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        $this->assertEquals('Off Day', $jp1->statusLabel());
        $this->assertNull($jp1->getAbnormality());

        // Case 2: Punched in biometrics but JPayroll says Absent -> resolves as Absent with conflict
        AttendanceLog::create([
            'employee_id' => $employee->employee_id,
            'clock_in_at' => '2026-07-21 08:00:00',
        ]);

        $jp2 = JPayrollAttendance::create([
            'employee_id' => $employee->id,
            'shift_date' => '2026-07-21',
            'alpha' => 1,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        $this->assertEquals('Absent', $jp2->statusLabel());
        $this->assertEquals('Punched while marked Absent', $jp2->getAbnormality());

        // Case 3: Marked Late in JPayroll but NO biometric punch -> resolves as Absent (No Biometric) with conflict
        $jp3 = JPayrollAttendance::create([
            'employee_id' => $employee->id,
            'shift_date' => '2026-07-22',
            'alpha' => 0,
            'telat' => 10,
            'izin' => 0,
            'sakit' => 0,
        ]);

        $this->assertEquals('Absent (No Biometric)', $jp3->statusLabel());
        $this->assertEquals('Marked Late but no Biometric Punch', $jp3->getAbnormality());

        // Case 4: Has biometric clock-in but NO clock-out, and shift date is in the past -> resolves as Missing Clock-Out
        AttendanceLog::create([
            'employee_id' => $employee->employee_id,
            'clock_in_at' => '2026-07-15 08:00:00',
            'clock_out_at' => null,
        ]);

        $jp4 = JPayrollAttendance::create([
            'employee_id' => $employee->id,
            'shift_date' => '2026-07-15',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        $this->assertEquals('Missing Clock-Out', $jp4->getAbnormality());
    }
}

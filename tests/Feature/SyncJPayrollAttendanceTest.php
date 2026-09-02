<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncJPayrollAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.jpayroll.url', 'https://api.jpayroll.test');
        Config::set('services.jpayroll.key', 'test-api-key');
        Config::set('services.jpayroll.company_area', '10000');
    }

    /** Seed a local employee matching a given NIK */
    private function createEmployee(string $nik): Employee
    {
        $dept = Department::firstOrCreate(['name' => 'Test Dept']);

        return Employee::create([
            'employee_id' => $nik,
            'first_name' => 'Test',
            'last_name' => 'User',
            'department_id' => $dept->id,
            'status' => 'active',
        ]);
    }

    /** Sample JPayroll response payload (6 days for one employee) */
    private function samplePayload(string $nik = '07073'): array
    {
        $data = [];
        foreach (range(1, 6) as $day) {
            $data[] = [
                'NIK' => $nik,
                'Name' => 'RAYMOND LAY',
                'ShiftDate' => sprintf('%02d/05/2026', $day),
                'ABS' => '0',
                'LT' => '0',
                'CT' => '0',
                'OP' => '0',
                'HOS' => '0',
                'WA' => '0',
                'HOSWA' => '0',
            ];
        }

        return ['data' => $data, 'total' => (string) count($data)];
    }

    public function test_sync_creates_jpayroll_attendance_rows(): void
    {
        $this->createEmployee('07073');

        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response($this->samplePayload(), 200),
        ]);

        $this->artisan('jpayroll:sync-attendance', [
            '--date1' => '01/05/2026',
            '--date2' => '06/05/2026',
            '--nik' => '07073',
        ])->assertSuccessful();

        $this->assertDatabaseCount('jpayroll_attendances', 6);
        $this->assertDatabaseHas('jpayroll_attendances', [
            'shift_date' => '2026-05-01',
            'alpha' => 0,
            'telat' => 0,
        ]);
    }

    public function test_sync_is_idempotent(): void
    {
        $this->createEmployee('07073');

        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response($this->samplePayload(), 200),
        ]);

        // Run twice
        $this->artisan('jpayroll:sync-attendance', [
            '--date1' => '01/05/2026',
            '--date2' => '06/05/2026',
            '--nik' => '07073',
        ])->assertSuccessful();

        $this->artisan('jpayroll:sync-attendance', [
            '--date1' => '01/05/2026',
            '--date2' => '06/05/2026',
            '--nik' => '07073',
        ])->assertSuccessful();

        // Still only 6 rows — no duplicates
        $this->assertDatabaseCount('jpayroll_attendances', 6);
    }

    public function test_sync_skips_unknown_niks(): void
    {
        // No local employee seeded
        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response($this->samplePayload('99999'), 200),
        ]);

        $this->artisan('jpayroll:sync-attendance', [
            '--date1' => '01/05/2026',
            '--date2' => '06/05/2026',
            '--nik' => '99999',
        ])->assertSuccessful(); // command itself succeeds (exit 0)

        // Nothing persisted
        $this->assertDatabaseCount('jpayroll_attendances', 0);
    }

    public function test_sync_records_last_sync_timestamp_in_cache(): void
    {
        $this->createEmployee('07073');

        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response($this->samplePayload(), 200),
        ]);

        Cache::forget('jpayroll_attendance_last_sync');

        $this->artisan('jpayroll:sync-attendance', [
            '--date1' => '01/05/2026',
            '--date2' => '06/05/2026',
        ])->assertSuccessful();

        $this->assertNotNull(Cache::get('jpayroll_attendance_last_sync'));
    }

    public function test_sync_maps_absence_flags_correctly(): void
    {
        $this->createEmployee('07073');

        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response([
                'data' => [[
                    'NIK' => '07073',
                    'Name' => 'RAYMOND LAY',
                    'ShiftDate' => '01/05/2026',
                    'ABS' => '1',
                    'LT' => '2',
                    'CT' => '3',
                    'OP' => '0',
                    'HOS' => '1',
                    'WA' => '0',
                    'HOSWA' => '1',
                ]],
                'total' => '1',
            ], 200),
        ]);

        $this->artisan('jpayroll:sync-attendance', [
            '--date1' => '01/05/2026',
            '--date2' => '01/05/2026',
        ])->assertSuccessful();

        $this->assertDatabaseHas('jpayroll_attendances', [
            'shift_date' => '2026-05-01',
            'alpha' => 1,
            'telat' => 2,
            'izin' => 0, // JPayroll CT is leave, so API sync defaults izin to 0
            'sakit' => 2, // HOS (1) + WA (0) + HOSWA (1)
        ]);
    }

    public function test_sync_returns_failure_when_api_returns_empty(): void
    {
        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response(['data' => [], 'total' => '0'], 200),
        ]);

        $this->artisan('jpayroll:sync-attendance', [
            '--date1' => '01/05/2026',
            '--date2' => '06/05/2026',
        ])->assertFailed();
    }
}

<?php

namespace Tests\Feature;

use App\Authorization\Permissions;
use App\Livewire\Attendance\UploadPermitCsv;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use App\Models\User;
use App\Services\PermitImportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class UploadPermitCsvTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->department = Department::create([
            'name' => 'IT Department',
            'code' => 'IT',
        ]);
    }

    private function createEmployee(string $nik, string $firstName = 'John', string $lastName = 'Doe'): Employee
    {
        return Employee::create([
            'employee_id' => $nik,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'department_id' => $this->department->id,
            'status' => 'active',
            'joined_at' => '2025-01-01',
        ]);
    }

    public function test_csv_parser_parses_semicolon_delimited_csv_correctly(): void
    {
        $csvContent = <<<'CSV'
No.;Employee ID;Name;Column1
1;00001;BERNADETT;0
2;00147;BEJO SANTOSO;1
3;06551;YOGA SUGIH PRATOMO;3
No.;Employee ID;Name;Column1
1;07055;ANDREAS LEONARDO;2
2;07073;RAYMOND LAY;0
CSV;

        $service = new PermitImportService;
        $parsed = $service->parseCsv($csvContent);

        $this->assertEquals([
            '00001' => 0,
            '00147' => 1,
            '06551' => 3,
            '07055' => 2,
            '07073' => 0,
        ], $parsed);
    }

    public function test_service_imports_permit_and_assigns_permit_days(): void
    {
        $emp1 = $this->createEmployee('00147', 'Bejo', 'Santoso');
        $emp2 = $this->createEmployee('06551', 'Yoga', 'Sugih');
        $emp3 = $this->createEmployee('07073', 'Raymond', 'Lay');

        $csvContent = "No.;Employee ID;Name;Column1\n1;00147;BEJO SANTOSO;1\n2;06551;YOGA SUGIH;3\n3;07073;RAYMOND LAY;0\n4;99999;UNKNOWN;1\n";

        $service = new PermitImportService;
        $result = $service->import($csvContent, 8, 2026);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(3, $result['employees_processed']);
        $this->assertEquals(4, $result['total_permit_days']); // 1 + 3 + 0
        $this->assertEquals(['99999'], $result['unmatched_niks']);

        // Check DB: emp1 has 1 permit day in August 2026
        $emp1PermitCount = JPayrollAttendance::where('employee_id', $emp1->id)
            ->whereBetween('shift_date', ['2026-08-01', '2026-08-31'])
            ->where('izin', 1)
            ->count();
        $this->assertEquals(1, $emp1PermitCount);

        // Check DB: emp2 has 3 permit days in August 2026
        $emp2PermitCount = JPayrollAttendance::where('employee_id', $emp2->id)
            ->whereBetween('shift_date', ['2026-08-01', '2026-08-31'])
            ->where('izin', 1)
            ->count();
        $this->assertEquals(3, $emp2PermitCount);

        // Check DB: emp3 has 0 permit days in August 2026
        $emp3PermitCount = JPayrollAttendance::where('employee_id', $emp3->id)
            ->whereBetween('shift_date', ['2026-08-01', '2026-08-31'])
            ->where('izin', 1)
            ->count();
        $this->assertEquals(0, $emp3PermitCount);

        // ApiSyncLog recorded
        $this->assertDatabaseHas('api_sync_logs', [
            'api_name' => 'jpayroll_permit_upload',
            'status' => 'success',
            'records_processed' => 3,
        ]);
    }

    public function test_service_import_is_idempotent_on_reupload(): void
    {
        $emp = $this->createEmployee('00147', 'Bejo', 'Santoso');

        $csvContentFirst = "No.;Employee ID;Name;Column1\n1;00147;BEJO SANTOSO;3\n";
        $csvContentSecond = "No.;Employee ID;Name;Column1\n1;00147;BEJO SANTOSO;1\n";

        $service = new PermitImportService;
        $service->import($csvContentFirst, 8, 2026);

        $firstCount = JPayrollAttendance::where('employee_id', $emp->id)
            ->whereBetween('shift_date', ['2026-08-01', '2026-08-31'])
            ->where('izin', 1)
            ->count();
        $this->assertEquals(3, $firstCount);

        // Re-upload with 1
        $service->import($csvContentSecond, 8, 2026);

        $secondCount = JPayrollAttendance::where('employee_id', $emp->id)
            ->whereBetween('shift_date', ['2026-08-01', '2026-08-31'])
            ->where('izin', 1)
            ->count();
        $this->assertEquals(1, $secondCount);
    }

    public function test_artisan_command_imports_csv_successfully(): void
    {
        $this->createEmployee('00147', 'Bejo', 'Santoso');

        $tempFile = tempnam(sys_get_temp_dir(), 'permit_').'.csv';
        file_put_contents($tempFile, "No.;Employee ID;Name;Column1\n1;00147;BEJO SANTOSO;2\n");

        $this->artisan('jpayroll:import-permit', [
            'file' => $tempFile,
            '--month' => '8',
            '--year' => '2026',
        ])->assertSuccessful();

        @unlink($tempFile);

        $this->assertEquals(2, JPayrollAttendance::where('izin', 1)->count());
    }

    public function test_livewire_component_allows_authorized_users_to_upload(): void
    {
        $this->createEmployee('07073', 'Raymond', 'Lay');

        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::MANAGE_ATTENDANCE);

        $csv = UploadedFile::fake()->createWithContent(
            'permit_august_2026.csv',
            "No.;Employee ID;Name;Column1\n1;07073;RAYMOND LAY;1\n"
        );

        Livewire::actingAs($user)
            ->test(UploadPermitCsv::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->set('csv_file', $csv)
            ->call('importPermit')
            ->assertHasNoErrors()
            ->assertDispatched('permit-uploaded');

        $this->assertEquals(1, JPayrollAttendance::where('izin', 1)->count());
    }

    public function test_livewire_component_blocks_unauthorized_users(): void
    {
        $user = User::factory()->create(); // No MANAGE_ATTENDANCE permission

        $csv = UploadedFile::fake()->createWithContent(
            'permit.csv',
            "No.;Employee ID;Name;Column1\n1;07073;RAYMOND LAY;1\n"
        );

        Livewire::actingAs($user)
            ->test(UploadPermitCsv::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->set('csv_file', $csv)
            ->call('importPermit')
            ->assertForbidden();
    }

    public function test_jpayroll_sync_does_not_overwrite_uploaded_permit(): void
    {
        $emp = $this->createEmployee('07073', 'Raymond', 'Lay');

        // 1. Upload Permit CSV to set 2 permit days
        $csvContent = "No.;Employee ID;Name;Column1\n1;07073;RAYMOND LAY;2\n";
        $service = new PermitImportService;
        $service->import($csvContent, 5, 2026);

        $this->assertEquals(2, JPayrollAttendance::where('employee_id', $emp->id)->where('izin', 1)->count());

        // 2. Run JPayroll sync
        Config::set('services.jpayroll.url', 'https://api.jpayroll.test');
        Config::set('services.jpayroll.key', 'test-api-key');
        Config::set('services.jpayroll.company_area', '10000');

        $payloadData = [];
        foreach (range(1, 5) as $day) {
            $payloadData[] = [
                'NIK' => '07073',
                'Name' => 'RAYMOND LAY',
                'ShiftDate' => sprintf('%02d/05/2026', $day),
                'ABS' => '0',
                'LT' => '0',
                'CT' => '1',
                'OP' => '0',
                'HOS' => '0',
                'WA' => '0',
                'HOSWA' => '0',
            ];
        }

        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response([
                'data' => $payloadData,
                'total' => '5',
            ], 200),
        ]);

        $this->artisan('jpayroll:sync-attendance', [
            '--date1' => '01/05/2026',
            '--date2' => '05/05/2026',
            '--nik' => '07073',
        ])->assertSuccessful();

        // 3. Permit count is still 2! JPayroll sync didn't wipe it to 0
        $this->assertEquals(2, JPayrollAttendance::where('employee_id', $emp->id)->where('izin', 1)->count());
    }
}

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

    public function test_csv_parser_parses_date_format_and_continuation_rows_correctly(): void
    {
        $csvContent = <<<'CSV'
No.;Employee ID;Name;Date;Attendance Status
1;07169;ANDYCO AMIHARDY;08/08/2026;FD
2;00147;BEJO SANTOSO;08/08/2026;FD
5;07232;ARIFIN SHOLEH;24/08/2026;FD
;;;25/08/2026;FD
21;06551;YOGA SUGIH PRATOMO;06/08/2026;FD
;;;10/08/2026;FD
;;;11/08/2026;FD
CSV;

        $service = new PermitImportService;
        $parsed = $service->parseCsv($csvContent);

        $this->assertEquals([
            '07169' => ['2026-08-08'],
            '00147' => ['2026-08-08'],
            '07232' => ['2026-08-24', '2026-08-25'],
            '06551' => ['2026-08-06', '2026-08-10', '2026-08-11'],
        ], $parsed);
    }

    public function test_service_imports_permit_and_assigns_exact_shift_dates(): void
    {
        $emp1 = $this->createEmployee('00147', 'Bejo', 'Santoso');
        $emp2 = $this->createEmployee('06551', 'Yoga', 'Sugih');
        $emp3 = $this->createEmployee('07073', 'Raymond', 'Lay');

        $csvContent = <<<'CSV'
No.;Employee ID;Name;Date;Attendance Status
1;00147;BEJO SANTOSO;08/08/2026;FD
2;06551;YOGA SUGIH;06/08/2026;FD
;;;10/08/2026;FD
;;;11/08/2026;FD
3;99999;UNKNOWN;15/08/2026;FD
CSV;

        $service = new PermitImportService;
        $result = $service->import($csvContent, 8, 2026);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(2, $result['employees_processed']);
        $this->assertEquals(4, $result['total_permit_days']); // 1 for emp1 + 3 for emp2
        $this->assertEquals(['99999'], $result['unmatched_niks']);

        // Check DB: emp1 has izin=1 on EXACTLY 2026-08-08
        $emp1Permit = JPayrollAttendance::where('employee_id', $emp1->id)
            ->where('shift_date', '2026-08-08')
            ->first();
        $this->assertNotNull($emp1Permit);
        $this->assertEquals(1, $emp1Permit->izin);

        // Check DB: emp2 has izin=1 on EXACTLY 2026-08-06, 2026-08-10, 2026-08-11
        $emp2PermitDates = JPayrollAttendance::where('employee_id', $emp2->id)
            ->where('izin', 1)
            ->pluck('shift_date')
            ->map(fn ($d) => is_string($d) ? substr($d, 0, 10) : $d->toDateString())
            ->sort()
            ->values()
            ->all();

        $this->assertEquals(['2026-08-06', '2026-08-10', '2026-08-11'], $emp2PermitDates);

        // Check DB: emp3 has 0 permit days
        $this->assertEquals(0, JPayrollAttendance::where('employee_id', $emp3->id)->where('izin', 1)->count());

        // ApiSyncLog recorded
        $this->assertDatabaseHas('api_sync_logs', [
            'api_name' => 'jpayroll_permit_upload',
            'status' => 'success',
            'records_processed' => 2,
        ]);
    }

    public function test_service_import_is_idempotent_on_reupload(): void
    {
        $emp = $this->createEmployee('00147', 'Bejo', 'Santoso');

        $csvFirst = "No.;Employee ID;Name;Date;Attendance Status\n1;00147;BEJO SANTOSO;08/08/2026;FD\n;;;09/08/2026;FD\n";
        $csvSecond = "No.;Employee ID;Name;Date;Attendance Status\n1;00147;BEJO SANTOSO;15/08/2026;FD\n";

        $service = new PermitImportService;
        $service->import($csvFirst, 8, 2026);

        $this->assertEquals(2, JPayrollAttendance::where('employee_id', $emp->id)->where('izin', 1)->count());

        // Re-upload with different date
        $service->import($csvSecond, 8, 2026);

        $secondRecords = JPayrollAttendance::where('employee_id', $emp->id)->where('izin', 1)->get();
        $this->assertCount(1, $secondRecords);
        $this->assertEquals('2026-08-15', substr((string) $secondRecords->first()->shift_date, 0, 10));
    }

    public function test_artisan_command_imports_csv_successfully(): void
    {
        $this->createEmployee('00147', 'Bejo', 'Santoso');

        $tempFile = tempnam(sys_get_temp_dir(), 'permit_').'.csv';
        file_put_contents($tempFile, "No.;Employee ID;Name;Date;Attendance Status\n1;00147;BEJO SANTOSO;08/08/2026;FD\n;;;12/08/2026;FD\n");

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
            'Izin_Karyawan_1-31_Agustus_2026.csv',
            "No.;Employee ID;Name;Date;Attendance Status\n1;07073;RAYMOND LAY;08/08/2026;FD\n"
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
            'Izin_Karyawan_1-31_Agustus_2026.csv',
            "No.;Employee ID;Name;Date;Attendance Status\n1;07073;RAYMOND LAY;08/08/2026;FD\n"
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

        // 1. Upload Permit CSV to set permit on 2026-05-02 and 2026-05-03
        $csvContent = "No.;Employee ID;Name;Date;Attendance Status\n1;07073;RAYMOND LAY;02/05/2026;FD\n;;;03/05/2026;FD\n";
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

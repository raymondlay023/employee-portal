<?php

namespace Tests\Feature;

use App\Services\JPayrollService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class JPayrollServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configure mock settings
        Config::set('services.jpayroll.url', 'https://api.jpayroll.test');
        Config::set('services.jpayroll.key', 'test-api-key');
        Config::set('services.jpayroll.company_area', '10000');
    }

    public function test_fetch_all_employees_success(): void
    {
        Http::fake([
            'https://api.jpayroll.test/API_View_Master_Employee.php' => Http::response([
                'status' => 200,
                'data' => [
                    ['NIK' => '07038', 'Name' => 'John Doe'],
                ],
            ], 200),
        ]);

        $service = new JPayrollService;
        $employees = $service->fetchAllEmployees();

        $this->assertCount(1, $employees);
        $this->assertEquals('07038', $employees[0]['NIK']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.jpayroll.test/API_View_Master_Employee.php'
                && $request->header('Authorization')[0] === 'test-api-key'
                && $request['CompanyArea'] === '10000';
        });
    }

    public function test_fetch_attendance_without_nik(): void
    {
        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response([
                'status' => 200,
                'data' => [
                    ['NIK' => '07073', 'Name' => 'RAYMOND LAY', 'ShiftDate' => '01/05/2026', 'ABS' => '0', 'LT' => '0', 'CT' => '0', 'OP' => '0', 'HOS' => '0', 'WA' => '0', 'HOSWA' => '0'],
                    ['NIK' => '07074', 'Name' => 'JANE DOE',    'ShiftDate' => '01/05/2026', 'ABS' => '1', 'LT' => '0', 'CT' => '0', 'OP' => '0', 'HOS' => '0', 'WA' => '0', 'HOSWA' => '0'],
                ],
            ], 200),
        ]);

        $service = new JPayrollService;
        $attendance = $service->fetchAttendance('01/05/2026', '06/05/2026');

        $this->assertCount(2, $attendance);
        $this->assertEquals('0', $attendance[0]['ABS']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.jpayroll.test/API_View_Attendance.php'
                && $request->header('Authorization')[0] === 'test-api-key'
                && $request['CompanyArea'] === '10000'
                && $request['Date1'] === '01/05/2026'
                && $request['Date2'] === '06/05/2026'
                && ! isset($request['NIK']);
        });
    }

    public function test_fetch_attendance_with_nik(): void
    {
        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response([
                'status' => 200,
                'data' => [
                    ['NIK' => '07073', 'Name' => 'RAYMOND LAY', 'ShiftDate' => '01/05/2026', 'ABS' => '0', 'LT' => '0', 'CT' => '0', 'OP' => '0', 'HOS' => '0', 'WA' => '0', 'HOSWA' => '0'],
                ],
            ], 200),
        ]);

        $service = new JPayrollService;
        $attendance = $service->fetchAttendance('01/05/2026', '06/05/2026', '07073');

        $this->assertCount(1, $attendance);
        $this->assertEquals('07073', $attendance[0]['NIK']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.jpayroll.test/API_View_Attendance.php'
                && $request->header('Authorization')[0] === 'test-api-key'
                && $request['CompanyArea'] === '10000'
                && $request['Date1'] === '01/05/2026'
                && $request['Date2'] === '06/05/2026'
                && $request['NIK'] === '07073';
        });
    }

    public function test_fetch_attendance_handles_failure(): void
    {
        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response('Internal Server Error', 500),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('JPayroll API (Attendance) HTTP error', \Mockery::on(function ($data) {
                return $data['status'] === 500 && $data['body'] === 'Internal Server Error';
            }));

        $service = new JPayrollService;
        $attendance = $service->fetchAttendance('01/05/2025', '06/05/2025');

        $this->assertEmpty($attendance);
    }

    public function test_fetch_annual_leave_success(): void
    {
        Http::fake([
            'https://api.jpayroll.test/API_View_AnnualLeave.php' => Http::response([
                'data' => [
                    [
                        'NIK' => '07073',
                        'Year' => '2026',
                        'StartDate' => '01/01/2026',
                        'EndDate' => '31/12/2026',
                        'Balance' => '12',
                        'Posted' => '6',
                        'Remain' => '6',
                    ],
                ],
                'total' => '1',
            ], 200),
        ]);

        $service = new JPayrollService;
        $leave = $service->fetchAnnualLeave('2026', '07073');

        $this->assertNotNull($leave);
        $this->assertEquals('07073', $leave['NIK']);
        $this->assertEquals('12', $leave['Balance']);
        $this->assertEquals('6', $leave['Posted']);
        $this->assertEquals('6', $leave['Remain']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.jpayroll.test/API_View_AnnualLeave.php'
                && $request->header('Authorization')[0] === 'test-api-key'
                && $request['CompanyArea'] === '10000'
                && $request['Year'] === '2026'
                && $request['NIK'] === '07073';
        });
    }

    public function test_fetch_annual_leave_handles_failure(): void
    {
        Http::fake([
            'https://api.jpayroll.test/API_View_AnnualLeave.php' => Http::response('Internal Server Error', 500),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('JPayroll API (Annual Leave) HTTP error', \Mockery::on(function ($data) {
                return $data['status'] === 500 && $data['body'] === 'Internal Server Error';
            }));

        $service = new JPayrollService;
        $leave = $service->fetchAnnualLeave('2026', '07073');

        $this->assertNull($leave);
    }
}

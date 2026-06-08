<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\JPayrollService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

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
                ]
            ], 200)
        ]);

        $service = new JPayrollService();
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
                    ['NIK' => '07038', 'Date' => '01/05/2025', 'Status' => 'Present'],
                    ['NIK' => '07039', 'Date' => '01/05/2025', 'Status' => 'Absent'],
                ]
            ], 200)
        ]);

        $service = new JPayrollService();
        $attendance = $service->fetchAttendance('01/05/2025', '06/05/2025');

        $this->assertCount(2, $attendance);
        $this->assertEquals('Present', $attendance[0]['Status']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.jpayroll.test/API_View_Attendance.php'
                && $request->header('Authorization')[0] === 'test-api-key'
                && $request['CompanyArea'] === '10000'
                && $request['Date1'] === '01/05/2025'
                && $request['Date2'] === '06/05/2025'
                && !isset($request['NIK']);
        });
    }

    public function test_fetch_attendance_with_nik(): void
    {
        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response([
                'status' => 200,
                'data' => [
                    ['NIK' => '07038', 'Date' => '01/05/2025', 'Status' => 'Present'],
                ]
            ], 200)
        ]);

        $service = new JPayrollService();
        $attendance = $service->fetchAttendance('01/05/2025', '06/05/2025', '07038');

        $this->assertCount(1, $attendance);
        $this->assertEquals('07038', $attendance[0]['NIK']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.jpayroll.test/API_View_Attendance.php'
                && $request->header('Authorization')[0] === 'test-api-key'
                && $request['CompanyArea'] === '10000'
                && $request['Date1'] === '01/05/2025'
                && $request['Date2'] === '06/05/2025'
                && $request['NIK'] === '07038';
        });
    }

    public function test_fetch_attendance_handles_failure(): void
    {
        Http::fake([
            'https://api.jpayroll.test/API_View_Attendance.php' => Http::response('Internal Server Error', 500)
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('JPayroll API (Attendance) HTTP error', \Mockery::on(function ($data) {
                return $data['status'] === 500 && $data['body'] === 'Internal Server Error';
            }));

        $service = new JPayrollService();
        $attendance = $service->fetchAttendance('01/05/2025', '06/05/2025');

        $this->assertEmpty($attendance);
    }
}

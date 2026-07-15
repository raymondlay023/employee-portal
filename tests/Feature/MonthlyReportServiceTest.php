<?php

namespace Tests\Feature;

use App\DataTransferObjects\AttendanceSummary;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use App\Services\MonthlyReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MonthlyReportService $service;

    protected Department $department;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MonthlyReportService;

        // Set up default department & active employee
        $this->department = Department::create([
            'name' => 'IT Department',
            'code' => 'IT',
        ]);

        $this->employee = Employee::create([
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'department_id' => $this->department->id,
            'status' => 'active',
            'joined_at' => now()->subYear(),
        ]);
    }

    public function test_get_attendance_summaries_for_department_calculates_correct_totals(): void
    {
        $startDate = Carbon::parse('2026-06-01');
        $endDate = Carbon::parse('2026-06-30');

        // Present day: all zero
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-06-01',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        // Absent day: alpha > 0
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-06-02',
            'alpha' => 1,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        // Late day: telat > 0
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-06-03',
            'alpha' => 0,
            'telat' => 10,
            'izin' => 0,
            'sakit' => 0,
        ]);

        // Sick day: sakit > 0
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-06-04',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 1,
        ]);

        // Leave day: izin > 0
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-06-05',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 1,
            'sakit' => 0,
        ]);

        $summaries = $this->service->getAttendanceSummariesForDepartment(
            $this->department->id,
            $startDate,
            $endDate
        );

        $this->assertCount(1, $summaries);

        /** @var AttendanceSummary $summary */
        $summary = $summaries->first();
        $this->assertEquals($this->employee->id, $summary->employeeId);
        $this->assertEquals('John Doe', $summary->employeeName);
        $this->assertEquals(5, $summary->totalDays);
        $this->assertEquals(2, $summary->presentDays);
        $this->assertEquals(1, $summary->absentDays);
        $this->assertEquals(1, $summary->lateDays);
        $this->assertEquals(1, $summary->sickDays);
        $this->assertEquals(1, $summary->leaveDays);
        $this->assertEquals(40.0, $summary->attendanceRate());
    }

    public function test_get_attendance_summaries_filters_by_date_range(): void
    {
        $startDate = Carbon::parse('2026-06-01');
        $endDate = Carbon::parse('2026-06-30');

        // Inside range
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-06-15',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        // Outside range (before)
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-05-31',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        // Outside range (after)
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-07-01',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        $summaries = $this->service->getAttendanceSummariesForDepartment(
            $this->department->id,
            $startDate,
            $endDate
        );

        $this->assertCount(1, $summaries);
        $this->assertEquals(1, $summaries->first()->totalDays);
    }

    public function test_get_attendance_summaries_excludes_inactive_employees(): void
    {
        $startDate = Carbon::parse('2026-06-01');
        $endDate = Carbon::parse('2026-06-30');

        // Create an inactive employee
        $inactiveEmployee = Employee::create([
            'employee_id' => 'EMP002',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'department_id' => $this->department->id,
            'status' => 'inactive',
        ]);

        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-06-15',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        JPayrollAttendance::create([
            'employee_id' => $inactiveEmployee->id,
            'shift_date' => '2026-06-15',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        $summaries = $this->service->getAttendanceSummariesForDepartment(
            $this->department->id,
            $startDate,
            $endDate
        );

        $this->assertCount(1, $summaries);
        $this->assertEquals('John Doe', $summaries->first()->employeeName);
    }

    public function test_get_attendance_summaries_returns_empty_for_department_with_no_data(): void
    {
        $startDate = Carbon::parse('2026-06-01');
        $endDate = Carbon::parse('2026-06-30');

        $summaries = $this->service->getAttendanceSummariesForDepartment(
            $this->department->id,
            $startDate,
            $endDate
        );

        $this->assertTrue($summaries->isEmpty());
    }

    public function test_get_attendance_summary_for_employee_calculates_correct_totals(): void
    {
        $startDate = Carbon::parse('2026-06-01');
        $endDate = Carbon::parse('2026-06-30');

        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-06-15',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-06-16',
            'alpha' => 1,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        $summary = $this->service->getAttendanceSummaryForEmployee(
            $this->employee->id,
            'John Doe',
            $startDate,
            $endDate
        );

        $this->assertEquals($this->employee->id, $summary->employeeId);
        $this->assertEquals('John Doe', $summary->employeeName);
        $this->assertEquals(2, $summary->totalDays);
        $this->assertEquals(1, $summary->presentDays);
        $this->assertEquals(1, $summary->absentDays);
        $this->assertEquals(50.0, $summary->attendanceRate());
    }

    public function test_get_attendance_summary_for_employee_with_no_records_returns_all_zero_dto(): void
    {
        $startDate = Carbon::parse('2026-06-01');
        $endDate = Carbon::parse('2026-06-30');

        $summary = $this->service->getAttendanceSummaryForEmployee(
            $this->employee->id,
            'John Doe',
            $startDate,
            $endDate
        );

        $this->assertEquals($this->employee->id, $summary->employeeId);
        $this->assertEquals('John Doe', $summary->employeeName);
        $this->assertEquals(0, $summary->totalDays);
        $this->assertEquals(0, $summary->presentDays);
        $this->assertEquals(0, $summary->absentDays);
        $this->assertEquals(0.0, $summary->attendanceRate());
    }

    public function test_attendance_summary_rate_with_zero_days(): void
    {
        $summary = new AttendanceSummary(
            employeeId: 1,
            employeeName: 'Test',
            totalDays: 0,
            presentDays: 0,
            absentDays: 0,
            lateDays: 0,
            sickDays: 0,
            leaveDays: 0
        );

        $this->assertEquals(0.0, $summary->attendanceRate());
    }
}

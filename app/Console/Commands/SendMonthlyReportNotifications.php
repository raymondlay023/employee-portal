<?php

namespace App\Console\Commands;

use App\Authorization\Roles;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\EmployeeMonthlyReport;
use App\Notifications\ManagerMonthlyReport;
use App\Services\MonthlyReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendMonthlyReportNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reports:send-monthly
                            {--month= : Month to report on (1-12), defaults to previous month}
                            {--year= : Year to report on, defaults to current/previous year}
                            {--dry-run : Preview what would be sent without dispatching}';

    /**
     * The console command description.
     */
    protected $description = 'Send monthly attendance report notifications to managers and employees';

    /**
     * Execute the console command.
     */
    public function handle(MonthlyReportService $reportService): int
    {
        $period = $this->resolvePeriod();
        $startDate = $period->copy()->startOfMonth();
        $endDate = $period->copy()->endOfMonth();
        $monthLabel = $period->translatedFormat('F Y');

        $this->info("Generating monthly reports for {$monthLabel}...");

        $managerCount = $this->sendManagerReports($reportService, $startDate, $endDate, $period);
        $employeeCount = $this->sendEmployeeReports($reportService, $startDate, $endDate, $period);

        $this->info("Done. Managers notified: {$managerCount}, Employees notified: {$employeeCount}");

        if (! $this->option('dry-run')) {
            Log::channel('daily')->info('Monthly report notifications sent', [
                'month' => $period->format('Y-m'),
                'managers_notified' => $managerCount,
                'employees_notified' => $employeeCount,
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * Resolve the target reporting month and year.
     */
    private function resolvePeriod(): Carbon
    {
        $month = $this->option('month');
        $year = $this->option('year');

        if ($month && $year) {
            return Carbon::createFromDate((int) $year, (int) $month, 1);
        }

        return now()->subMonthNoOverflow()->startOfMonth();
    }

    /**
     * Generate and send department summary reports to managers.
     */
    private function sendManagerReports(
        MonthlyReportService $reportService,
        Carbon $startDate,
        Carbon $endDate,
        Carbon $period
    ): int {
        $count = 0;

        Department::with('employees')->each(function (Department $department) use (
            $reportService, $startDate, $endDate, $period, &$count
        ) {
            $managers = User::role(Roles::MANAGER)
                ->whereHas('employee', function ($q) use ($department) {
                    $q->where('department_id', $department->id)->active();
                })
                ->get();

            if ($managers->isEmpty()) {
                return;
            }

            $summaries = $reportService->getAttendanceSummariesForDepartment(
                $department->id, $startDate, $endDate
            );

            if ($summaries->isEmpty()) {
                return;
            }

            foreach ($managers as $manager) {
                if ($this->option('dry-run')) {
                    $this->line("  [DRY RUN] Would notify manager: {$manager->name} ({$department->name}) - {$summaries->count()} employees");
                } else {
                    $manager->notify(new ManagerMonthlyReport($summaries, $period, $department->name));
                }
                $count++;
            }
        });

        return $count;
    }

    /**
     * Generate and send individual summary reports to employees.
     */
    private function sendEmployeeReports(
        MonthlyReportService $reportService,
        Carbon $startDate,
        Carbon $endDate,
        Carbon $period
    ): int {
        $count = 0;

        Employee::active()
            ->with('user')
            ->whereNotNull('user_id')
            ->whereHas('user', function ($q) {
                $q->role(Roles::EMPLOYEE);
            })
            ->chunkById(100, function ($employees) use (
                $reportService, $startDate, $endDate, $period, &$count
            ) {
                foreach ($employees as $employee) {
                    $fullName = trim("{$employee->first_name} {$employee->last_name}");
                    $summary = $reportService->getAttendanceSummaryForEmployee(
                        $employee->id, $fullName, $startDate, $endDate
                    );

                    if ($summary->totalDays === 0) {
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("  [DRY RUN] Would notify employee: {$fullName} - {$summary->presentDays}/{$summary->totalDays} present");
                    } else {
                        $employee->user->notify(new EmployeeMonthlyReport($summary, $period));
                    }
                    $count++;
                }
            });

        return $count;
    }
}

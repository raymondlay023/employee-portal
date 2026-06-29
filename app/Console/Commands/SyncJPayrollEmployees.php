<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JPayrollService;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use Carbon\Carbon;

class SyncJPayrollEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jpayroll:sync-employees {--trigger=} {--triggered-by=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync master employee data from JPayroll API to local database';

    /**
     * Execute the console command.
     */
    public function handle(JPayrollService $jpayroll)
    {
        $trigger = $this->option('trigger') ?: 'cli';
        $triggeredBy = $this->option('triggered-by') ?: null;

        // Initialize API audit log
        $syncLog = \App\Models\ApiSyncLog::create([
            'api_name'             => 'jpayroll_employees',
            'trigger_type'         => $trigger,
            'triggered_by_user_id' => $triggeredBy,
            'parameters'           => [],
            'status'               => 'running',
            'started_at'           => now(),
        ]);

        $this->info('Starting JPayroll Employee Sync...');

        try {
            $employees = $jpayroll->fetchAllEmployees();

            if (empty($employees)) {
                $this->error('No data fetched from JPayroll or an error occurred.');
                $syncLog->update([
                    'status'        => 'failed',
                    'ended_at'      => now(),
                    'error_message' => 'No data fetched from JPayroll or an error occurred.',
                ]);
                return 1;
            }

            $this->info('Fetched ' . count($employees) . ' employees from JPayroll. Syncing...');

            $syncedCount = 0;
            $failedCount = 0;

            foreach ($employees as $emp) {
                if (empty($emp['NIK'])) {
                    $failedCount++;
                    continue;
                }

                try {
                    // Map Dates
                    $joinedAt = null;
                    if (!empty($emp['StartDate'])) {
                        try {
                            $joinedAt = Carbon::createFromFormat('d/m/Y', $emp['StartDate'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            // fallback if format doesn't match
                        }
                    }

                    $endDate = null;
                    if (!empty($emp['EndDate'])) {
                        try {
                            $endDate = Carbon::createFromFormat('d/m/Y', $emp['EndDate'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            // fallback if format doesn't match
                        }
                    }

                    // Determine Status
                    $status = 'active';
                    if ($endDate !== null) {
                        $status = 'resigned';
                    }

                    // Map Gender
                    $gender = null;
                    if (in_array(strtoupper($emp['Sex'] ?? ''), ['M', 'F'])) {
                        $gender = strtoupper($emp['Sex']);
                    }

                    // Split Name
                    $nameParts = explode(' ', $emp['Name'] ?? 'Unknown', 2);
                    $firstName = $nameParts[0];
                    $lastName = $nameParts[1] ?? null;

                    // Resolve Department
                    $departmentId = null;
                    if (!empty($emp['CostCenterCode'])) {
                        $dept = Department::firstOrCreate(['name' => $emp['CostCenterCode']]);
                        $departmentId = $dept->id;
                    }

                    // Designation is kept empty as per user request
                    $designationId = null;

                    // Determine Account Type
                    $accountType = null;
                    if (!empty($emp['AccountCode'])) {
                        $accountCode = strtoupper($emp['AccountCode']);
                        if (str_contains($accountCode, 'DIRECT') || str_contains($accountCode, 'INDIRECT')) {
                            $accountType = $emp['AccountCode'];
                        } else {
                            $accountType = $emp['AccountCode'];
                        }
                    }

                    // Determine Branch
                    $branch = null;
                    $ccCode = strtoupper($emp['CostCenterCode'] ?? '');
                    $empStatus = strtoupper($emp['EmployeeStatus'] ?? '');
                    if (str_contains($ccCode, '-K') || str_contains($empStatus, 'KARAWANG')) {
                        $branch = 'Karawang';
                    } else {
                        $branch = 'Jakarta'; // Default or logic based
                    }

                    // Resolve and provision User account
                    $user = \App\Models\User::where('nik', $emp['NIK'])->first();
                    if (!$user) {
                        $user = \App\Models\User::create([
                            'name' => $emp['Name'] ?? 'Unknown',
                            'nik' => $emp['NIK'],
                            'email' => $emp['NIK'] . '@employee-portal.local',
                            'password' => bcrypt('12345678'), // Default password
                        ]);
                    } else {
                        // Update name in case it changed in JPayroll API
                        $user->update([
                            'name' => $emp['Name'] ?? $user->name,
                        ]);
                    }

                    // Securely assign the Employee role if they do not have a role yet
                    if (!$user->hasRole('Employee') && !$user->hasRole('Admin') && !$user->hasRole('HR') && !$user->hasRole('Manager')) {
                        $user->assignRole('Employee');
                    }

                    Employee::updateOrCreate(
                        ['employee_id' => $emp['NIK']],
                        [
                            'user_id' => $user->id,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'gender' => $gender,
                            'department_id' => $departmentId,
                            'designation_id' => $designationId,
                            'joined_at' => $joinedAt,
                            'end_date' => $endDate,
                            'status' => $status,
                            'account_type' => $accountType,
                            'organization_structure' => $emp['OrganizationStructure'] ?? null,
                            'branch' => $branch,
                        ]
                    );

                    $syncedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                }
            }

            $syncLog->update([
                'status'            => 'success',
                'ended_at'          => now(),
                'records_fetched'   => count($employees),
                'records_processed' => $syncedCount + $failedCount,
                'records_saved'     => $syncedCount,
                'records_failed'    => $failedCount,
            ]);

            $this->info("Sync completed successfully. Synced {$syncedCount} employees.");
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error during JPayroll sync: ' . $e->getMessage());
            
            $syncLog->update([
                'status'        => 'failed',
                'ended_at'      => now(),
                'error_message' => $e->getMessage(),
            ]);
            
            return 1;
        }
    }
}

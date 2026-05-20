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
    protected $signature = 'jpayroll:sync-employees';

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
        $this->info('Starting JPayroll Employee Sync...');

        $employees = $jpayroll->fetchAllEmployees();

        if (empty($employees)) {
            $this->error('No data fetched from JPayroll or an error occurred.');
            return;
        }

        $this->info('Fetched ' . count($employees) . ' employees from JPayroll. Syncing...');

        $syncedCount = 0;

        foreach ($employees as $emp) {
            if (empty($emp['NIK'])) {
                continue;
            }

            // Map Date
            $joinedAt = null;
            if (!empty($emp['StartDate'])) {
                try {
                    $joinedAt = Carbon::createFromFormat('d/m/Y', $emp['StartDate'])->format('Y-m-d');
                } catch (\Exception $e) {
                    // fallback if format doesn't match
                }
            }

            // Determine Status
            $status = 'active';
            if (!empty($emp['EndDate'])) {
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
                     // Assume the raw AccountCode could mean direct/indirect based on the user request, 
                     // but if we just save it as string:
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
                    'status' => $status,
                    'account_type' => $accountType,
                    'organization_structure' => $emp['OrganizationStructure'] ?? null,
                    'branch' => $branch,
                ]
            );

            $syncedCount++;
        }

        $this->info("Sync completed successfully. Synced {$syncedCount} employees.");
    }
}

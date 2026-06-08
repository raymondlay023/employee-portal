<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\JPayrollService;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use Carbon\Carbon;

class SyncJPayrollAttendance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jpayroll:sync-attendance
                            {--date1= : Start date in d/m/Y format (default: 7 days ago)}
                            {--date2= : End date in d/m/Y format (default: today)}
                            {--nik=   : Sync a single employee by NIK (optional)}';

    /**
     * The console command description.
     */
    protected $description = 'Sync daily attendance data from JPayroll API into local jpayroll_attendances table';

    /**
     * Execute the console command.
     */
    public function handle(JPayrollService $jpayroll): int
    {
        $date1 = $this->option('date1') ?? now()->subDays(7)->format('d/m/Y');
        $date2 = $this->option('date2') ?? now()->format('d/m/Y');
        $nik   = $this->option('nik') ?: null;

        $this->info("Syncing JPayroll attendance from {$date1} to {$date2}" . ($nik ? " (NIK: {$nik})" : '') . ' ...');

        $records = $jpayroll->fetchAttendance($date1, $date2, $nik);

        if (empty($records)) {
            $this->error('No attendance data fetched from JPayroll or an error occurred.');
            return self::FAILURE;
        }

        $this->info('Fetched ' . count($records) . ' records. Upserting...');

        $synced  = 0;
        $skipped = 0;

        // Build a NIK → local employee_id map to avoid N+1 queries
        $niks = collect($records)->pluck('NIK')->unique()->values()->all();
        $employeeMap = Employee::whereIn('employee_id', $niks)
            ->pluck('id', 'employee_id'); // ['07073' => 5, ...]

        foreach ($records as $row) {
            $nik = $row['NIK'] ?? null;

            if (!$nik || !isset($employeeMap[$nik])) {
                Log::warning('SyncJPayrollAttendance: no local employee found for NIK', ['nik' => $nik]);
                $skipped++;
                continue;
            }

            $employeeId = $employeeMap[$nik];

            // Parse ShiftDate from d/m/Y
            try {
                $shiftDate = Carbon::createFromFormat('d/m/Y', $row['ShiftDate'])->toDateString();
            } catch (\Exception $e) {
                Log::warning('SyncJPayrollAttendance: invalid ShiftDate', ['row' => $row]);
                $skipped++;
                continue;
            }

            JPayrollAttendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'shift_date'  => $shiftDate,
                ],
                [
                    'alpha'  => (int) ($row['ABS']   ?? 0),
                    'telat'  => (int) ($row['LT']    ?? 0),
                    'izin'   => (int) ($row['CT']    ?? 0),
                    'op'     => (int) ($row['OP']    ?? 0),
                    'hos'    => (int) ($row['HOS']   ?? 0),
                    'wa'     => (int) ($row['WA']    ?? 0),
                    'hoswa'  => (int) ($row['HOSWA'] ?? 0),
                ]
            );

            $synced++;
        }

        // Record the last sync timestamp in cache
        Cache::forever('jpayroll_attendance_last_sync', now()->toIso8601String());

        $this->info("Sync complete. Synced: {$synced}, Skipped: {$skipped}.");

        return self::SUCCESS;
    }
}

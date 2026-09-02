<?php

namespace App\Console\Commands;

use App\Models\ApiSyncLog;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use App\Services\JPayrollService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncJPayrollAttendance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jpayroll:sync-attendance
                            {--date1= : Start date in d/m/Y format (default: 7 days ago)}
                            {--date2= : End date in d/m/Y format (default: today)}
                            {--nik=   : Sync a single employee by NIK (optional)}
                            {--trigger=cli : The trigger type (manual, scheduled, cli)}
                            {--triggered-by= : User ID triggering the manual sync}';

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
        $nik = $this->option('nik') ?: null;
        $trigger = $this->option('trigger') ?: 'cli';
        $triggeredBy = $this->option('triggered-by') ?: null;

        // Initialize API audit log
        $syncLog = ApiSyncLog::create([
            'api_name' => 'jpayroll_attendance',
            'trigger_type' => $trigger,
            'triggered_by_user_id' => $triggeredBy,
            'parameters' => [
                'date_from' => $date1,
                'date_to' => $date2,
                'nik' => $nik,
            ],
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->info("Syncing JPayroll attendance from {$date1} to {$date2}".($nik ? " (NIK: {$nik})" : '').' ...');
        Log::channel('jpayroll')->info("Sync started. Source: {$trigger}, Target dates: {$date1} to {$date2}, NIK: ".($nik ?? 'All').' (Triggered by user ID: '.($triggeredBy ?? 'System').')');

        try {
            $records = $jpayroll->fetchAttendance($date1, $date2, $nik);

            if (empty($records)) {
                $errorMsg = 'No attendance data fetched from JPayroll or an error occurred.';
                $this->error($errorMsg);
                Log::channel('jpayroll')->error($errorMsg);

                $syncLog->update([
                    'status' => 'failed',
                    'error_message' => $errorMsg,
                    'ended_at' => now(),
                ]);

                return self::FAILURE;
            }

            $totalRecords = count($records);
            $this->info("Fetched {$totalRecords} records. Upserting...");
            Log::channel('jpayroll')->info("Fetched {$totalRecords} records from JPayroll API. Starting local updates.");

            $synced = 0;
            $skipped = 0;

            // Build a NIK → local employee_id map to avoid N+1 queries
            $niks = collect($records)->pluck('NIK')->unique()->values()->all();
            $employeeMap = Employee::whereIn('employee_id', $niks)
                ->pluck('id', 'employee_id'); // ['07073' => 5, ...]

            $upsertData = [];

            foreach ($records as $row) {
                $rowNik = $row['NIK'] ?? null;

                if (! $rowNik || ! isset($employeeMap[$rowNik])) {
                    $warnMsg = "SyncJPayrollAttendance: no local employee found for NIK: {$rowNik}";
                    Log::warning($warnMsg);
                    Log::channel('jpayroll')->warning($warnMsg, ['row' => $row]);
                    $skipped++;

                    continue;
                }

                $employeeId = $employeeMap[$rowNik];

                // Parse ShiftDate from d/m/Y
                try {
                    $shiftDate = Carbon::createFromFormat('d/m/Y', $row['ShiftDate'])->toDateString();
                } catch (\Exception $e) {
                    $warnMsg = 'SyncJPayrollAttendance: invalid ShiftDate format: '.($row['ShiftDate'] ?? 'null');
                    Log::warning($warnMsg);
                    Log::channel('jpayroll')->warning($warnMsg, ['row' => $row]);
                    $skipped++;

                    continue;
                }

                $upsertData[] = [
                    'employee_id' => $employeeId,
                    'shift_date' => $shiftDate,
                    'alpha' => (int) ($row['ABS'] ?? 0),
                    'telat' => (int) ($row['LT'] ?? 0),
                    'izin' => 0, // Izin is uploaded via CSV rather than fetched from JPayroll CT (Leave)
                    'sakit' => (int) ($row['HOS'] ?? 0) + (int) ($row['WA'] ?? 0) + (int) ($row['HOSWA'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $synced++;
            }

            if (! empty($upsertData)) {
                foreach (array_chunk($upsertData, 1000) as $chunk) {
                    JPayrollAttendance::upsert(
                        $chunk,
                        ['employee_id', 'shift_date'],
                        ['alpha', 'telat', 'sakit', 'updated_at']
                    );
                    Log::channel('jpayroll')->debug('Synced chunk of '.count($chunk).' attendance records');
                }
            }

            // Record the last sync timestamp in cache
            Cache::forever('jpayroll_attendance_last_sync', now()->toIso8601String());

            $successMsg = "Sync complete. Synced: {$synced}, Skipped: {$skipped}.";
            $this->info($successMsg);
            Log::channel('jpayroll')->info($successMsg);

            $syncLog->update([
                'status' => 'success',
                'records_fetched' => $totalRecords,
                'records_processed' => $synced,
                'records_failed' => $skipped,
                'ended_at' => now(),
            ]);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $errorMsg = 'Sync failed with exception: '.$e->getMessage();
            $this->error($errorMsg);
            Log::error($errorMsg, ['exception' => $e]);
            Log::channel('jpayroll')->error($errorMsg, [
                'exception_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'ended_at' => now(),
            ]);

            return self::FAILURE;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\ApiSyncLog;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Services\BiometricService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncBiometricAttendance extends Command
{
    protected $signature = 'device:sync-attendance
                            {--days=7 : Number of past days to sync}
                            {--trigger=cli : The trigger type (manual, scheduled, cli)}
                            {--triggered-by= : User ID triggering the manual sync}';

    protected $description = 'Sync attendance logs from multiple biometric devices into the attendance_logs table';

    public function handle(BiometricService $service): int
    {
        $trigger = $this->option('trigger') ?: 'cli';
        $triggeredBy = $this->option('triggered-by') ?: null;
        $days = (int) ($this->option('days') ?? 7);

        $syncLog = ApiSyncLog::create([
            'api_name' => 'biometric_device',
            'trigger_type' => $trigger,
            'triggered_by_user_id' => $triggeredBy,
            'parameters' => ['days' => $days],
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->info("Syncing biometric device attendance logs (last {$days} days)...");
        Log::channel('biometric')->info("Biometric sync started. Source: {$trigger}, Days: {$days} (Triggered by user ID: ".($triggeredBy ?? 'System').')');

        try {
            $records = $service->fetchAttendanceLogs($days, $syncLog->id);

            if ($records === null) {
                $errorMsg = 'Failed to fetch biometric attendance data from device(s).';
                $this->error($errorMsg);
                Log::channel('biometric')->error($errorMsg);

                $syncLog->update([
                    'status' => 'failed',
                    'error_message' => $errorMsg,
                    'ended_at' => now(),
                ]);

                return self::FAILURE;
            }

            $totalRecords = count($records);
            $this->info("Fetched {$totalRecords} raw punch records from last {$days} days.");
            Log::channel('biometric')->info("Fetched {$totalRecords} raw punch records from biometric device(s). Starting local updates.");

            // 1. Build a combined PIN map of all employees
            // PIN = [dept_code][employee_id]
            $employees = Employee::with('department')->get();
            $employeeMap = [];
            foreach ($employees as $emp) {
                $deptCode = $emp->department?->code ?? '';
                $nik = $emp->employee_id;

                $combinedPin = $deptCode.$nik;
                if ($combinedPin !== '') {
                    $employeeMap[$combinedPin] = $nik;
                }

                $employeeMap[$nik] = $nik;
            }

            // 2. Group punches by employee ID and date to find min (check-in) and max (check-out)
            $punches = [];
            $skipped = 0;
            $employeeIds = [];
            $dates = [];

            foreach ($records as $row) {
                $pin = $row['pin'] ?? null;
                if (! $pin || ! isset($employeeMap[$pin])) {
                    $skipped++;

                    continue;
                }

                $employeeId = $employeeMap[$pin];
                $time = $row['time_parsed'];
                $date = $time->toDateString();

                $punches[$employeeId][$date][] = $time;
                $employeeIds[$employeeId] = true;
                $dates[$date] = true;
            }

            $employeeIds = array_keys($employeeIds);
            $datesList = array_keys($dates);

            if (empty($employeeIds) || empty($datesList)) {
                $successMsg = "Sync complete. No relevant employee records found to process. Skipped: {$skipped} raw punches.";
                $this->info($successMsg);
                $syncLog->update([
                    'status' => 'success',
                    'records_fetched' => $totalRecords,
                    'records_processed' => 0,
                    'records_failed' => $skipped,
                    'parameters' => array_merge($syncLog->parameters ?? [], [
                        'days' => $days,
                        'raw_payloads' => $service->savedPayloadPaths,
                    ]),
                    'ended_at' => now(),
                ]);

                return self::SUCCESS;
            }

            // 3. Eager load existing logs in one single query to prevent N+1 queries
            $minDate = min($datesList);
            $maxDate = max($datesList);

            $existingLogs = AttendanceLog::whereIn('employee_id', $employeeIds)
                ->whereBetween('clock_in_at', [$minDate.' 00:00:00', $maxDate.' 23:59:59'])
                ->get()
                ->groupBy(fn ($log) => $log->employee_id.'_'.$log->clock_in_at->toDateString());

            $synced = 0;

            // 4. Batch updates in a transaction for rapid writes
            DB::transaction(function () use ($punches, $existingLogs, &$synced) {
                foreach ($punches as $employeeId => $dates) {
                    foreach ($dates as $date => $times) {
                        $key = $employeeId.'_'.$date;
                        $existingLog = $existingLogs->get($key)?->first();

                        if ($existingLog) {
                            $allTimes = collect([$existingLog->clock_in_at, $existingLog->clock_out_at, ...$times])
                                ->filter()
                                ->values();

                            $newClockIn = $allTimes->min();
                            $newClockOut = $allTimes->count() > 1 ? $allTimes->max() : null;

                            if (! $existingLog->clock_in_at->eq($newClockIn) ||
                                ($newClockOut && ! $existingLog->clock_out_at?->eq($newClockOut)) ||
                                (! $newClockOut && $existingLog->clock_out_at)) {

                                $existingLog->update([
                                    'clock_in_at' => $newClockIn,
                                    'clock_out_at' => $newClockOut,
                                    'note' => $existingLog->note ?: 'Synced from biometric device',
                                ]);
                                $synced++;
                            }
                        } else {
                            $newClockIn = collect($times)->min();
                            $newClockOut = count($times) > 1 ? collect($times)->max() : null;

                            AttendanceLog::create([
                                'employee_id' => $employeeId,
                                'clock_in_at' => $newClockIn,
                                'clock_out_at' => $newClockOut,
                                'note' => 'Synced from biometric device',
                            ]);
                            $synced++;
                        }
                    }
                }
            });

            $successMsg = "Sync complete. Processed/Updated logs for {$synced} employee-days. Skipped: {$skipped} raw punches.";
            $this->info($successMsg);
            Log::channel('biometric')->info($successMsg);

            $syncLog->update([
                'status' => 'success',
                'records_fetched' => $totalRecords,
                'records_processed' => $synced,
                'records_failed' => $skipped,
                'parameters' => array_merge($syncLog->parameters ?? [], [
                    'days' => $days,
                    'raw_payloads' => $service->savedPayloadPaths,
                ]),
                'ended_at' => now(),
            ]);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $errorMsg = 'Biometric sync failed with exception: '.$e->getMessage();
            $this->error($errorMsg);
            Log::error($errorMsg, ['exception' => $e]);
            Log::channel('biometric')->error($errorMsg, [
                'exception_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'parameters' => array_merge($syncLog->parameters ?? [], [
                    'days' => $days,
                    'raw_payloads' => $service->savedPayloadPaths,
                ]),
                'ended_at' => now(),
            ]);

            return self::FAILURE;
        }
    }
}

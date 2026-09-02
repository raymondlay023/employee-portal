<?php

namespace App\Services;

use App\Models\ApiSyncLog;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PermitImportService
{
    /**
     * Parse and import Permit CSV data into JPayroll Attendance records for a given month and year.
     *
     * @param  string|resource|UploadedFile  $file
     * @return array{
     *     status: string,
     *     month: int,
     *     year: int,
     *     total_rows: int,
     *     employees_processed: int,
     *     total_permit_days: int,
     *     unmatched_niks: array<string>,
     *     skipped_rows: int,
     *     error?: string
     * }
     */
    public function import(
        mixed $file,
        int $month,
        int $year,
        ?int $triggeredByUserId = null,
        string $triggerType = 'manual'
    ): array {
        $startedAt = now();

        $syncLog = ApiSyncLog::create([
            'api_name' => 'jpayroll_permit_upload',
            'trigger_type' => $triggerType,
            'triggered_by_user_id' => $triggeredByUserId,
            'parameters' => [
                'month' => $month,
                'year' => $year,
            ],
            'status' => 'running',
            'started_at' => $startedAt,
        ]);

        try {
            $parsedRows = $this->parseCsv($file);

            if (empty($parsedRows)) {
                throw new InvalidArgumentException('The uploaded CSV file is empty or contains no valid data rows.');
            }

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            // Collect unique NIKs from CSV
            $csvNiks = array_keys($parsedRows);
            $employees = Employee::whereIn('employee_id', $csvNiks)->get()->keyBy('employee_id');

            $employeesProcessed = 0;
            $totalPermitDays = 0;
            $unmatchedNiks = [];

            // Preload biometric clock-in dates for all matching employees in this month
            $biometricPunches = AttendanceLog::whereIn('employee_id', $csvNiks)
                ->whereBetween('clock_in_at', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
                ->selectRaw('employee_id, DATE(clock_in_at) as punch_date')
                ->get()
                ->groupBy('employee_id')
                ->map(fn ($logs) => $logs->pluck('punch_date')->unique()->values()->all());

            DB::transaction(function () use (
                $parsedRows,
                $employees,
                $startDate,
                $endDate,
                $daysInMonth,
                $year,
                $month,
                $biometricPunches,
                &$employeesProcessed,
                &$totalPermitDays,
                &$unmatchedNiks
            ) {
                foreach ($parsedRows as $nik => $permitCount) {
                    /** @var Employee|null $employee */
                    $employee = $employees->get($nik);

                    if (! $employee) {
                        $unmatchedNiks[] = $nik;

                        continue;
                    }

                    $totalPermitDays += $permitCount;
                    $employeesProcessed++;

                    // Fetch existing attendance records for the employee in this month
                    $records = JPayrollAttendance::where('employee_id', $employee->id)
                        ->whereBetween('shift_date', [$startDate, $endDate])
                        ->get()
                        ->keyBy(fn ($r) => $r->shift_date instanceof Carbon ? $r->shift_date->toDateString() : (string) $r->shift_date);

                    // If no attendance records exist for this month, create baseline daily records
                    if ($records->isEmpty()) {
                        $newRows = [];
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                            $newRows[] = [
                                'employee_id' => $employee->id,
                                'shift_date' => $dateStr,
                                'alpha' => 0,
                                'telat' => 0,
                                'izin' => 0,
                                'sakit' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        JPayrollAttendance::insert($newRows);

                        $records = JPayrollAttendance::where('employee_id', $employee->id)
                            ->whereBetween('shift_date', [$startDate, $endDate])
                            ->get()
                            ->keyBy(fn ($r) => $r->shift_date instanceof Carbon ? $r->shift_date->toDateString() : (string) $r->shift_date);
                    }

                    // Reset all izin counts to 0 for this employee in the month
                    JPayrollAttendance::where('employee_id', $employee->id)
                        ->whereBetween('shift_date', [$startDate, $endDate])
                        ->update(['izin' => 0]);

                    if ($permitCount <= 0) {
                        continue;
                    }

                    $empPunches = $biometricPunches->get($nik, []);

                    // Rank candidate dates to assign permit:
                    // 1. Shift dates without biometric punch and sick=0 and alpha=0
                    // 2. Shift dates without biometric punch and sick=0
                    // 3. Shift dates with sick=0
                    // 4. Any remaining shift dates
                    $sortedRecords = $records->values()->sort(function ($a, $b) use ($empPunches) {
                        $aDate = $a->shift_date instanceof Carbon ? $a->shift_date->toDateString() : (string) $a->shift_date;
                        $bDate = $b->shift_date instanceof Carbon ? $b->shift_date->toDateString() : (string) $b->shift_date;

                        $aScore = $this->calculateCandidateScore($a, in_array($aDate, $empPunches));
                        $bScore = $this->calculateCandidateScore($b, in_array($bDate, $empPunches));

                        if ($aScore === $bScore) {
                            return strcmp($aDate, $bDate);
                        }

                        return $bScore <=> $aScore; // Higher score first
                    });

                    // Assign izin=1 to top $permitCount records
                    $targetIds = $sortedRecords->take($permitCount)->pluck('id')->all();

                    if (! empty($targetIds)) {
                        JPayrollAttendance::whereIn('id', $targetIds)->update(['izin' => 1]);
                    }
                }
            });

            $result = [
                'status' => 'success',
                'month' => $month,
                'year' => $year,
                'total_rows' => count($parsedRows),
                'employees_processed' => $employeesProcessed,
                'total_permit_days' => $totalPermitDays,
                'unmatched_niks' => $unmatchedNiks,
                'skipped_rows' => count($unmatchedNiks),
            ];

            $syncLog->update([
                'status' => 'success',
                'records_fetched' => count($parsedRows),
                'records_processed' => $employeesProcessed,
                'records_failed' => count($unmatchedNiks),
                'ended_at' => now(),
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('PermitImportService import failed: '.$e->getMessage(), ['exception' => $e]);

            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'ended_at' => now(),
            ]);

            return [
                'status' => 'failed',
                'month' => $month,
                'year' => $year,
                'total_rows' => 0,
                'employees_processed' => 0,
                'total_permit_days' => 0,
                'unmatched_niks' => [],
                'skipped_rows' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Score a record for assigning Permit. Higher score means better candidate.
     */
    private function calculateCandidateScore(JPayrollAttendance $record, bool $hasPunch): int
    {
        $score = 0;

        // No biometric clock-in is the highest indicator of an off/permit day
        if (! $hasPunch) {
            $score += 100;
        }

        // Not sick
        if ($record->sakit === 0) {
            $score += 50;
        }

        // Unexcused absence can be converted to permitted absence
        if ($record->alpha > 0) {
            $score += 20;
        } elseif ($record->alpha === 0) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Parse CSV stream or file and extract map: ['00147' => 1, '06551' => 3, ...]
     *
     * @return array<string, int>
     */
    public function parseCsv(mixed $file): array
    {
        $content = '';

        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();
            if ($path && file_exists($path)) {
                $content = file_get_contents($path);
            }
            if (empty($content) && method_exists($file, 'get')) {
                $content = $file->get();
            }
        } elseif (is_string($file)) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
            } else {
                $content = $file;
            }
        } elseif (is_resource($file)) {
            $content = stream_get_contents($file);
        }

        if (empty($content)) {
            return [];
        }

        // Remove potential UTF-8 BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Normalize line breaks
        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        if (empty($lines)) {
            return [];
        }

        // Detect delimiter: check semicolon vs comma in first non-empty line
        $firstLine = $lines[0];
        $delimiter = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

        $parsed = [];
        $nikColIndex = 1;
        $permitColIndex = 3;
        $headerIdentified = false;

        foreach ($lines as $lineIndex => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $cols = str_getcsv($line, $delimiter);
            if (empty($cols)) {
                continue;
            }

            $cleanedCols = array_map('trim', $cols);

            // Check if this row is a header line
            $isHeader = false;
            foreach ($cleanedCols as $idx => $val) {
                $lower = strtolower($val);
                if (in_array($lower, ['employee id', 'employee_id', 'nik', 'id karyawan', 'no.'])) {
                    $isHeader = true;
                }
            }

            if ($isHeader) {
                // Determine column positions dynamically
                foreach ($cleanedCols as $idx => $val) {
                    $lower = strtolower($val);
                    if (in_array($lower, ['employee id', 'employee_id', 'nik', 'id karyawan'])) {
                        $nikColIndex = $idx;
                    }
                    if (in_array($lower, ['column1', 'permit', 'izin', 'jumlah izin', 'total izin', 'leave', 'cuti'])) {
                        $permitColIndex = $idx;
                    }
                }
                $headerIdentified = true;

                continue;
            }

            // Extract NIK
            $rawNik = $cleanedCols[$nikColIndex] ?? null;

            // Fallback: if only 2-3 columns or default index didn't work, search for numeric NIK column
            if (! $rawNik || ! preg_match('/^\d+$/', $rawNik)) {
                foreach ($cleanedCols as $idx => $val) {
                    if (preg_match('/^\d{4,10}$/', $val) && $idx !== 0) {
                        $rawNik = $val;
                        $nikColIndex = $idx;
                        break;
                    }
                }
            }

            if (! $rawNik) {
                continue;
            }

            // Ensure leading zeros are preserved (e.g. "00147")
            $nik = trim($rawNik);

            // Extract Permit Count
            $rawPermit = $cleanedCols[$permitColIndex] ?? null;
            if ($rawPermit === null || ! is_numeric($rawPermit)) {
                // If not at $permitColIndex, check the last numeric column
                for ($i = count($cleanedCols) - 1; $i >= 0; $i--) {
                    if ($i !== $nikColIndex && is_numeric($cleanedCols[$i])) {
                        $rawPermit = $cleanedCols[$i];
                        break;
                    }
                }
            }

            $permitCount = max(0, (int) ($rawPermit ?? 0));

            // Sum up if NIK appears multiple times in CSV
            if (isset($parsed[$nik])) {
                $parsed[$nik] += $permitCount;
            } else {
                $parsed[$nik] = $permitCount;
            }
        }

        return $parsed;
    }
}

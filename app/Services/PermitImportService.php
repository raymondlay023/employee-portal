<?php

namespace App\Services;

use App\Models\ApiSyncLog;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class PermitImportService
{
    /**
     * Parse and import Permit CSV data (with exact dates) into JPayroll Attendance records for a given month and year.
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

            DB::transaction(function () use (
                $parsedRows,
                $employees,
                $startDate,
                $endDate,
                $daysInMonth,
                $year,
                $month,
                &$employeesProcessed,
                &$totalPermitDays,
                &$unmatchedNiks
            ) {
                foreach ($parsedRows as $nik => $permitDates) {
                    /** @var Employee|null $employee */
                    $employee = $employees->get($nik);

                    if (! $employee) {
                        $unmatchedNiks[] = $nik;

                        continue;
                    }

                    // Filter permit dates belonging to target month and year
                    $validDatesInMonth = array_filter($permitDates, function ($d) use ($startDate, $endDate) {
                        return $d >= $startDate && $d <= $endDate;
                    });

                    $employeesProcessed++;
                    $totalPermitDays += count($validDatesInMonth);

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

                    if (empty($validDatesInMonth)) {
                        continue;
                    }

                    // Directly assign izin = 1 for the exact dates specified in CSV
                    foreach ($validDatesInMonth as $dateStr) {
                        if (isset($records[$dateStr])) {
                            JPayrollAttendance::where('id', $records[$dateStr]->id)->update(['izin' => 1]);
                        } else {
                            JPayrollAttendance::updateOrCreate(
                                [
                                    'employee_id' => $employee->id,
                                    'shift_date' => $dateStr,
                                ],
                                [
                                    'izin' => 1,
                                    'alpha' => 0,
                                    'telat' => 0,
                                    'sakit' => 0,
                                ]
                            );
                        }
                    }
                }
            });

            $syncLog->update([
                'status' => 'success',
                'records_processed' => $employeesProcessed,
                'summary_payload' => [
                    'month' => $month,
                    'year' => $year,
                    'employees_processed' => $employeesProcessed,
                    'total_permit_days' => $totalPermitDays,
                    'total_unique_employees_in_csv' => count($parsedRows),
                    'unmatched_niks' => $unmatchedNiks,
                ],
                'completed_at' => now(),
            ]);

            return [
                'status' => 'success',
                'month' => $month,
                'year' => $year,
                'total_rows' => count($parsedRows),
                'employees_processed' => $employeesProcessed,
                'total_permit_days' => $totalPermitDays,
                'unmatched_niks' => $unmatchedNiks,
                'skipped_rows' => count($unmatchedNiks),
            ];
        } catch (Throwable $e) {
            Log::error('Permit CSV import failed: '.$e->getMessage(), [
                'exception' => $e,
                'month' => $month,
                'year' => $year,
            ]);

            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
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
     * Parse the date-specific Permit CSV content into an array of [NIK => [DateString, ...]].
     *
     * Format:
     * No.; Employee ID; Name; Date; Attendance Status
     * 1;07169;ANDYCO AMIHARDY;08/08/2026;FD
     * 5;07232;ARIFIN SHOLEH;24/08/2026;FD
     * ;;;25/08/2026;FD  (continuation row inherits previous Employee ID)
     *
     * @return array<string, array<string>> Key is Employee NIK, value is array of unique 'YYYY-MM-DD' date strings.
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
        $dateColIndex = 3;
        $currentNik = null;

        foreach ($lines as $line) {
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
            foreach ($cleanedCols as $val) {
                $lower = strtolower($val);
                if (in_array($lower, ['employee id', 'employee_id', 'nik', 'id karyawan', 'attendance status', 'status kehadiran'])) {
                    $isHeader = true;
                    break;
                }
            }

            if ($isHeader) {
                // Determine column positions dynamically
                foreach ($cleanedCols as $idx => $val) {
                    $lower = strtolower($val);
                    if (in_array($lower, ['employee id', 'employee_id', 'nik', 'id karyawan'])) {
                        $nikColIndex = $idx;
                    }
                    if (in_array($lower, ['date', 'tanggal', 'shift date', 'shift_date', 'tgl'])) {
                        $dateColIndex = $idx;
                    }
                }

                continue;
            }

            // Extract or inherit NIK
            $rawNik = $cleanedCols[$nikColIndex] ?? '';
            if ($rawNik !== '' && preg_match('/\d+/', $rawNik)) {
                $currentNik = trim($rawNik);
            }

            if (! $currentNik) {
                continue;
            }

            // Extract Date column
            $rawDate = $cleanedCols[$dateColIndex] ?? '';
            if ($rawDate === '') {
                // If not at $dateColIndex, scan columns for a date string
                foreach ($cleanedCols as $val) {
                    if (preg_match('/\b\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}\b/', $val) || preg_match('/\b\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}\b/', $val)) {
                        $rawDate = $val;
                        break;
                    }
                }
            }

            if ($rawDate === '') {
                continue;
            }

            $parsedDate = $this->parseDateString($rawDate);
            if (! $parsedDate) {
                continue;
            }

            if (! isset($parsed[$currentNik])) {
                $parsed[$currentNik] = [];
            }

            if (! in_array($parsedDate, $parsed[$currentNik], true)) {
                $parsed[$currentNik][] = $parsedDate;
            }
        }

        return $parsed;
    }

    /**
     * Parse date string formatted as DD/MM/YYYY, DD-MM-YYYY, or YYYY-MM-DD into YYYY-MM-DD.
     */
    private function parseDateString(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);

        try {
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $dateStr, $matches)) {
                $day = (int) $matches[1];
                $month = (int) $matches[2];
                $year = (int) $matches[3];

                return Carbon::createFromDate($year, $month, $day)->toDateString();
            }

            if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $dateStr, $matches)) {
                $year = (int) $matches[1];
                $month = (int) $matches[2];
                $day = (int) $matches[3];

                return Carbon::createFromDate($year, $month, $day)->toDateString();
            }

            return Carbon::parse($dateStr)->toDateString();
        } catch (Exception) {
            return null;
        }
    }
}

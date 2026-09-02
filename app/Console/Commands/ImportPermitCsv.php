<?php

namespace App\Console\Commands;

use App\Services\PermitImportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportPermitCsv extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jpayroll:import-permit
                            {file : Path to CSV file}
                            {--month= : Month number (1-12)}
                            {--year= : Four digit year (e.g. 2026)}';

    /**
     * The console command description.
     */
    protected $description = 'Import employee permit dates from a CSV file into JPayroll attendance records';

    /**
     * The console command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['jpayroll:import-permit'];

    /**
     * Execute the console command.
     */
    public function handle(PermitImportService $importer): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        // Infer or read month & year
        $month = $this->option('month') ? (int) $this->option('month') : $this->inferMonthFromFilename($filePath);
        $year = $this->option('year') ? (int) $this->option('year') : $this->inferYearFromFilename($filePath);

        $monthName = Carbon::createFromDate($year, $month, 1)->format('F');

        $this->info("Importing Permit CSV for {$monthName} {$year} from: {$filePath} ...");

        $result = $importer->import(
            file: $filePath,
            month: $month,
            year: $year,
            triggeredByUserId: null,
            triggerType: 'cli'
        );

        if ($result['status'] === 'failed') {
            $this->error('Import failed: '.($result['error'] ?? 'Unknown error'));

            return self::FAILURE;
        }

        $this->info('Import completed successfully!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Target Month/Year', "{$monthName} {$year} (Month: {$month})"],
                ['Total Rows in CSV', $result['total_rows']],
                ['Employees Processed', $result['employees_processed']],
                ['Total Permit Days Assigned', $result['total_permit_days']],
                ['Unmatched / Skipped NIKs', count($result['unmatched_niks'])],
            ]
        );

        if (! empty($result['unmatched_niks'])) {
            $this->warn('Unmatched NIKs (not in database): '.implode(', ', array_slice($result['unmatched_niks'], 0, 20)));
            if (count($result['unmatched_niks']) > 20) {
                $this->warn('... and '.(count($result['unmatched_niks']) - 20).' more.');
            }
        }

        return self::SUCCESS;
    }

    /**
     * Infer month integer from filename (supports Indonesian and English month names).
     */
    private function inferMonthFromFilename(string $filename): int
    {
        $lower = Str::lower($filename);

        $monthMap = [
            'januari' => 1, 'january' => 1, 'jan' => 1,
            'februari' => 2, 'february' => 2, 'feb' => 2,
            'maret' => 3, 'march' => 3, 'mar' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5, 'may' => 5,
            'juni' => 6, 'june' => 6, 'jun' => 6,
            'juli' => 7, 'july' => 7, 'jul' => 7,
            'agustus' => 8, 'august' => 8, 'agt' => 8, 'aug' => 8,
            'september' => 9, 'sep' => 9, 'sept' => 9,
            'oktober' => 10, 'october' => 10, 'okt' => 10, 'oct' => 10,
            'november' => 11, 'nov' => 11,
            'desember' => 12, 'december' => 12, 'des' => 12, 'dec' => 12,
        ];

        foreach ($monthMap as $name => $num) {
            if (Str::contains($lower, $name)) {
                return $num;
            }
        }

        return (int) now()->month;
    }

    /**
     * Infer 4-digit year from filename.
     */
    private function inferYearFromFilename(string $filename): int
    {
        if (preg_match('/\b(20\d\d)\b/', $filename, $matches)) {
            return (int) $matches[1];
        }

        return (int) now()->year;
    }
}

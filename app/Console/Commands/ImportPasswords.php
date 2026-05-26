<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\User;
use Exception;

class ImportPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employees:import-passwords
        {file : path to CSV file (relative to project root or absolute)}
        {--delimiter=; : CSV delimiter}
        {--header : CSV has header row}
        {--dry-run : do not persist changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import employee passwords from CSV and update user passwords';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        $delimiter = $this->option('delimiter');
        $hasHeader = $this->option('header');
        $dryRun = (bool) $this->option('dry-run');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->error("Unable to open file: {$file}");
            return 1;
        }

        $row = 0;
        $updated = 0;
        $skipped = 0;
        if ($hasHeader) {
            fgetcsv($handle, 0, $delimiter);
        }

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $row++;
                if (count($data) < 3) {
                    $this->warn("Skipping row {$row}: not enough columns");
                    $skipped++;
                    continue;
                }

                $employeeId = trim($data[0]);
                $passcode = trim($data[2]);

                if ($employeeId === '' || $passcode === '') {
                    $this->warn("Skipping row {$row}: empty employee id or passcode");
                    $skipped++;
                    continue;
                }

                $employee = Employee::where('employee_id', $employeeId)->first();
                if (!$employee || !$employee->user) {
                    $this->warn("Row {$row}: employee {$employeeId} or user not found");
                    $skipped++;
                    continue;
                }

                $user = $employee->user;
                $newHashed = Hash::make($passcode);

                if ($dryRun) {
                    $this->info("Row {$row}: would update user {$user->id} (employee {$employeeId})");
                    $updated++;
                    continue;
                }

                $user->password = $newHashed;
                $user->save();
                $this->info("Row {$row}: updated user {$user->id} (employee {$employeeId})");
                $updated++;
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }

        fclose($handle);
        $this->line("Completed. Updated: {$updated}. Skipped: {$skipped}.");
        return 0;
    }
}

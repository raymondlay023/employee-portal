<?php

namespace App\Console\Commands;

use App\Models\ApiSyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PruneBiometricBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:prune-backups {--days= : Number of days to keep backups (overrides env)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old raw biometric XML backups from storage to save disk space';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = ! is_null($this->option('days'))
            ? (int) $this->option('days')
            : (int) env('BIOMETRIC_BACKUP_PRUNE_DAYS', 30);

        $cutoff = now()->subDays($days)->endOfDay();

        $this->info("Pruning raw biometric backups older than {$days} days (cutoff: {$cutoff->toDateTimeString()})...");
        Log::channel('biometric')->info("Pruning raw backups older than {$days} days started.");

        $logs = ApiSyncLog::where('api_name', 'biometric_device')
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('parameters')
            ->get();

        $deletedCount = 0;

        foreach ($logs as $log) {
            $parameters = $log->parameters;

            if (isset($parameters['raw_payloads']) && is_array($parameters['raw_payloads'])) {
                foreach ($parameters['raw_payloads'] as $device => $path) {
                    if (Storage::exists($path)) {
                        Storage::delete($path);
                        $deletedCount++;
                    }
                }

                // Update database log parameters to mark as pruned
                unset($parameters['raw_payloads']);
                $parameters['raw_payloads_pruned'] = true;

                $log->update([
                    'parameters' => $parameters,
                ]);
            }
        }

        $msg = "Pruned {$deletedCount} old biometric XML files.";
        $this->info($msg);
        Log::channel('biometric')->info($msg);

        return self::SUCCESS;
    }
}

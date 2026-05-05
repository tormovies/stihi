<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CleanupRequestLogs extends Command
{
    protected $signature = 'logs:cleanup-requests {--days=90 : Keep logs for this many days}';
    protected $description = 'Delete old request logs (404-YYYY-MM-DD.log and bots-YYYY-MM-DD.log)';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = CarbonImmutable::today()->subDays($days);
        $dir = storage_path('logs');

        if (!is_dir($dir)) {
            $this->line('Log directory not found, nothing to clean.');
            return self::SUCCESS;
        }

        $files = @scandir($dir);
        if (!is_array($files)) {
            $this->error('Cannot read log directory.');
            return self::FAILURE;
        }

        $deleted = 0;
        foreach ($files as $file) {
            if (!preg_match('/^(404|bots)-(\d{4}-\d{2}-\d{2})\.log$/', $file, $m)) {
                continue;
            }

            try {
                $date = CarbonImmutable::createFromFormat('Y-m-d', $m[2])->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }

            if ($date->lessThan($cutoff)) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (@unlink($path)) {
                    $deleted++;
                }
            }
        }

        $this->info("Done. Deleted {$deleted} file(s). Retention: {$days} days.");
        return self::SUCCESS;
    }
}

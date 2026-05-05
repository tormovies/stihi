<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupCookieConsentLogs extends Command
{
    protected $signature = 'consents:cleanup {--days=730 : Keep consent logs for this many days}';
    protected $description = 'Delete old cookie consent logs';

    public function handle(): int
    {
        $days = max(30, (int) $this->option('days'));
        $cutoff = CarbonImmutable::now()->subDays($days);

        $deleted = DB::table('cookie_consent_logs')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Done. Deleted {$deleted} cookie consent row(s). Retention: {$days} days.");
        return self::SUCCESS;
    }
}

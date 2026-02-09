<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPoemSeoPlaceholders extends Command
{
    protected $signature = 'poem:fix-seo-placeholders';
    protected $description = 'Replace old %%title%% placeholders with {title} in poems.meta_title';

    public function handle(): int
    {
        $updated = DB::table('poems')
            ->where('meta_title', 'like', '%' . '%%title%%' . '%')
            ->update([
                'meta_title' => DB::raw("REPLACE(meta_title, '%%title%%', '{title}')"),
            ]);

        $this->info("Updated meta_title in {$updated} poem(s): %%title%% → {title}");
        return self::SUCCESS;
    }
}

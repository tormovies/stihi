<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('seo_templates')->where('type', 'home')->doesntExist()) {
            DB::table('seo_templates')->insert([
                'type' => 'home',
                'meta_title' => 'Стихотворения поэтов классиков',
                'meta_description' => 'Портал классической поэзии — стихи русских поэтов-классиков.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('seo_templates')->where('type', 'home')->delete();
    }
};

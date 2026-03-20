<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('seo_templates')->where('type', 'liked_by_all')->doesntExist()) {
            DB::table('seo_templates')->insert([
                'type' => 'liked_by_all',
                'meta_title' => 'Понравившееся всем | Стихотворения поэтов классиков',
                'meta_description' => 'Стихи, которые читателям понравились больше всего: по числу отметок «Нравится».',
                'h1' => 'Понравившееся всем',
                'h1_description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('seo_templates')->where('type', 'liked_by_all')->delete();
    }
};

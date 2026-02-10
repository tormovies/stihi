<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('seo_templates')->where('type', 'favorites')->doesntExist()) {
            DB::table('seo_templates')->insert([
                'type' => 'favorites',
                'meta_title' => 'Понравившееся | Стихотворения поэтов классиков',
                'meta_description' => 'Стихи, отмеченные вами как понравившиеся. Читайте избранные произведения русских поэтов-классиков в одном месте.',
                'h1' => 'Понравившееся',
                'h1_description' => 'Стихи, которые вы отметили кнопкой «Нравится». Список хранится в вашем браузере.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('seo_templates')->where('type', 'favorites')->delete();
    }
};

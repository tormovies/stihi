<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tag', 'tags_index'] as $type) {
            if (DB::table('seo_templates')->where('type', $type)->doesntExist()) {
                DB::table('seo_templates')->insert([
                    'type' => $type,
                    'meta_title' => $type === 'tags_index' ? 'Теги стихов по темам | Стихотворения' : '{name} | Стихотворения',
                    'meta_description' => $type === 'tags_index'
                        ? 'Все теги и темы стихов. Выберите подборку и читайте стихи.'
                        : 'Стихи по теме «{name}». Читать подборку.',
                    'h1' => $type === 'tags_index' ? 'Теги стихов' : '{name}',
                    'h1_description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('seo_templates')->whereIn('type', ['tag', 'tags_index'])->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->unique()->comment('page, author, poem');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
        });

        // Default rows
        DB::table('seo_templates')->insert([
            ['type' => 'page', 'meta_title' => '{title}', 'meta_description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'author', 'meta_title' => '{name} — стихи', 'meta_description' => 'Стихи {name}. Читать текст стихотворений.', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'poem', 'meta_title' => '{title} — {author}', 'meta_description' => 'Стихотворение {title}, {author}. Читать текст.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_templates');
    }
};

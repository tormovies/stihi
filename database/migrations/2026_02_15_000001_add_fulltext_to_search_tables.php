<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FULLTEXT для поиска по релевантности (MATCH AGAINST).
     */
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->fullText(['name'], 'authors_name_fulltext');
        });

        Schema::table('poems', function (Blueprint $table) {
            $table->fullText(['title', 'body'], 'poems_title_body_fulltext');
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropFullText('authors_name_fulltext');
        });
        Schema::table('poems', function (Blueprint $table) {
            $table->dropFullText('poems_title_body_fulltext');
        });
    }
};

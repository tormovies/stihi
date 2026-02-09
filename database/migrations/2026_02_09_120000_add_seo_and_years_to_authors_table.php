<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->string('years_of_life')->nullable()->after('name');
            $table->string('meta_title')->nullable()->after('years_of_life');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('h1')->nullable()->after('meta_description');
            $table->string('h1_description', 500)->nullable()->after('h1');
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn([
                'years_of_life',
                'meta_title',
                'meta_description',
                'h1',
                'h1_description',
            ]);
        });
    }
};
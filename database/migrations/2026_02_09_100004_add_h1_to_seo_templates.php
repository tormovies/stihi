<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_templates', function (Blueprint $table) {
            $table->string('h1')->nullable()->after('meta_description');
            $table->string('h1_description', 500)->nullable()->after('h1');
        });
    }

    public function down(): void
    {
        Schema::table('seo_templates', function (Blueprint $table) {
            $table->dropColumn(['h1', 'h1_description']);
        });
    }
};

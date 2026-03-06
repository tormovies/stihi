<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deepseek_logs', function (Blueprint $table) {
            $table->string('entity_type', 20)->nullable()->default('poem')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('deepseek_logs', function (Blueprint $table) {
            $table->dropColumn('entity_type');
        });
    }
};

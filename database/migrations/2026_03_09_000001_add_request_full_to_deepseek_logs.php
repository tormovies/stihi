<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deepseek_logs', function (Blueprint $table) {
            $table->longText('request_full')->nullable()->after('request_payload');
        });
    }

    public function down(): void
    {
        Schema::table('deepseek_logs', function (Blueprint $table) {
            $table->dropColumn('request_full');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gone_paths', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->unique()->comment('Путь как в запросе (без нормализации)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gone_paths');
    }
};

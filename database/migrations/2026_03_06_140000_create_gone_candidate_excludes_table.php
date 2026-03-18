<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gone_candidate_excludes', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->unique()->comment('Путь, скрытый из списка кандидатов');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gone_candidate_excludes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poem_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poem_id')->constrained('poems')->cascadeOnDelete();
            $table->longText('analysis_text');
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('h1', 255)->nullable();
            $table->text('h1_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poem_analyses');
    }
};

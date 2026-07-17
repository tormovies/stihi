<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poem_suno_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poem_id')->unique()->constrained('poems')->cascadeOnDelete();
            $table->unsignedTinyInteger('score_hook')->default(0);
            $table->unsignedTinyInteger('score_rhythm')->default(0);
            $table->unsignedTinyInteger('score_dynamics')->default(0);
            $table->unsignedTinyInteger('score_plot')->default(0);
            $table->unsignedTinyInteger('score_vocal_air')->default(0);
            $table->unsignedTinyInteger('score_total')->default(0);
            $table->string('status', 16)->default('medium'); // super|strong|medium|weak
            $table->boolean('suitable_for_suno')->default(false);
            $table->unsignedTinyInteger('male_fit')->default(0);
            $table->string('male_verdict', 16)->default('maybe'); // yes|maybe|no
            $table->text('male_why')->nullable();
            $table->unsignedTinyInteger('folk_fit')->default(0);
            $table->string('folk_verdict', 16)->default('maybe');
            $table->text('folk_why')->nullable();
            $table->unsignedTinyInteger('comfort_fit')->default(0); // «Позитив»
            $table->string('comfort_verdict', 16)->default('maybe');
            $table->text('comfort_why')->nullable();
            $table->longText('marked_lyrics')->nullable();
            $table->json('styles')->nullable();
            $table->string('best_overall', 255)->nullable();
            $table->string('best_viral', 255)->nullable();
            $table->string('best_cult', 255)->nullable();
            $table->text('structure_notes')->nullable();
            $table->json('risks')->nullable();
            $table->longText('raw_response')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('suitable_for_suno');
            $table->index('male_verdict');
            $table->index('folk_verdict');
            $table->index('comfort_verdict');
            $table->index('score_total');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poem_suno_analyses');
    }
};

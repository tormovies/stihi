<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deepseek_logs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 50)->nullable(); // success, error, parse_error, api_error
            $table->json('request_payload')->nullable(); // массив {id, poet, title, poem}
            $table->longText('response_raw')->nullable(); // сырой ответ API
            $table->unsignedInteger('processed_count')->default(0);
            $table->json('failed_ids')->nullable(); // id стихов, которые не обновились
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deepseek_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cookie_consent_logs', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 64)->index();
            $table->string('policy_version', 20)->index();
            $table->boolean('analytics');
            $table->boolean('necessary')->default(true);
            $table->timestamp('consent_at')->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_consent_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64);
            $table->date('visit_date');
            $table->string('ip_address', 45)->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->unsignedBigInteger('visit_count')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['fingerprint', 'visit_date']);
            $table->index('visit_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_analytics');
    }
};

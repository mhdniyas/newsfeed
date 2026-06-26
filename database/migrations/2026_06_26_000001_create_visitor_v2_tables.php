<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id')->unique();
            $table->string('first_ip', 45)->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->text('first_user_agent')->nullable();
            $table->text('last_user_agent')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('platform', 100)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->timestamps();

            $table->index('last_seen_at');
        });

        Schema::create('visitor_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id');
            $table->string('session_id', 64)->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('page_views')->default(0);
            $table->text('referrer')->nullable();
            $table->string('landing_page', 255)->nullable();
            $table->string('exit_page', 255)->nullable();
            $table->timestamps();

            $table->index('visitor_id');
            $table->index('started_at');
        });

        Schema::create('visitor_page_views', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id');
            $table->string('session_id', 64);
            $table->string('url', 255);
            $table->string('route_name', 100)->nullable();
            $table->string('page_title', 255)->nullable();
            $table->timestamp('visited_at')->nullable();
            $table->timestamps();

            $table->index('visitor_id');
            $table->index('session_id');
            $table->index('visited_at');
        });

        Schema::create('visitor_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id');
            $table->date('visit_date');
            $table->unsignedInteger('page_views')->default(0);
            $table->timestamp('first_visit_at')->nullable();
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamps();

            $table->unique(['visitor_id', 'visit_date']);
            $table->index('visit_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_daily_stats');
        Schema::dropIfExists('visitor_page_views');
        Schema::dropIfExists('visitor_sessions');
        Schema::dropIfExists('visitors');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Raw page views (event stream log)
        Schema::create('analytics_page_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('session_id')->nullable()->index();
            $table->string('visitor_fingerprint')->index();
            $table->string('page_path')->index();
            $table->string('page_type')->index(); // 'news', 'lottery', 'gold', 'jobs', 'homepage', 'static', 'api'
            $table->unsignedBigInteger('model_id')->nullable()->index();
            $table->string('referrer_host')->nullable()->index();
            $table->string('device_type')->nullable(); // 'desktop', 'mobile', 'tablet'
            $table->string('browser_name')->nullable();
            $table->string('os_name')->nullable();
            $table->string('country_code')->nullable()->index();
            $table->boolean('is_bot')->default(false)->index();
            $table->string('bot_type')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        // 2. Active/completed sessions
        Schema::create('analytics_sessions', function (Blueprint $table) {
            $table->string('session_id')->primary();
            $table->string('visitor_fingerprint')->index();
            $table->integer('duration_seconds')->default(0);
            $table->integer('pages_count')->default(1);
            $table->boolean('bounce_rate')->default(true); // Bounce if pages_count == 1
            $table->string('country_code')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser_name')->nullable();
            $table->string('os_name')->nullable();
            $table->boolean('is_human')->default(true)->index();
            $table->timestamps();
        });

        // 3. Visitor profiles
        Schema::create('analytics_visitors', function (Blueprint $table) {
            $table->string('visitor_fingerprint')->primary();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->integer('views_count')->default(1);
            $table->integer('sessions_count')->default(1);
            $table->boolean('is_human')->default(true)->index();
            $table->timestamps();
        });

        // 4. Daily aggregations
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date')->unique();
            $table->integer('total_views')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->float('bounce_rate')->default(0.0);
            $table->integer('avg_duration_seconds')->default(0);
            $table->integer('human_views')->default(0);
            $table->integer('bot_views')->default(0);
            $table->timestamps();
        });

        // 5. Hourly aggregations
        Schema::create('analytics_hourly', function (Blueprint $table) {
            $table->increments('id');
            $table->datetime('date_hour')->unique();
            $table->integer('total_views')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('human_views')->default(0);
            $table->integer('bot_views')->default(0);
            $table->timestamps();
        });

        // 6. Content Modules: Articles Daily views
        Schema::create('analytics_articles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('article_id')->index();
            $table->date('date');
            $table->integer('views_count')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('reading_time_seconds')->default(0);
            $table->integer('scroll_depth_percent')->default(0);
            $table->timestamps();
            $table->unique(['article_id', 'date'], 'art_daily_unique');
        });

        // 7. Content Modules: Sections Daily views
        Schema::create('analytics_sections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->date('date');
            $table->integer('views_count')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('sessions_count')->default(0);
            $table->integer('live_visitors_peak')->default(0);
            $table->timestamps();
            $table->unique(['section_id', 'date'], 'sect_daily_unique');
        });

        // 8. Content Modules: Topics Daily views
        Schema::create('analytics_topics', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('topic_id')->index();
            $table->date('date');
            $table->integer('views_count')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->timestamps();
            $table->unique(['topic_id', 'date'], 'top_daily_unique');
        });

        // 9. Lottery Daily views & interactions
        Schema::create('analytics_lottery', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('lottery_result_id')->nullable()->index();
            $table->date('date');
            $table->integer('views_count')->default(0);
            $table->integer('pdf_downloads')->default(0);
            $table->integer('official_clicks')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->timestamps();
            $table->unique(['lottery_result_id', 'date'], 'lott_daily_unique');
        });

        // 10. Gold Rate Daily views
        Schema::create('analytics_gold', function (Blueprint $table) {
            $table->increments('id');
            $table->string('city')->index();
            $table->date('date');
            $table->integer('views_count')->default(0);
            $table->integer('calculator_usage')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->timestamps();
            $table->unique(['city', 'date'], 'gold_daily_unique');
        });

        // 11. Job Board Daily views & CTR
        Schema::create('analytics_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('job_post_id')->nullable()->index();
            $table->date('date');
            $table->integer('views_count')->default(0);
            $table->integer('apply_clicks')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->timestamps();
            $table->unique(['job_post_id', 'date'], 'jobs_daily_unique');
        });

        // 12. Referrer Traffic aggregates
        Schema::create('analytics_referrers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('referrer_host')->index();
            $table->date('date');
            $table->integer('views_count')->default(0);
            $table->timestamps();
            $table->unique(['referrer_host', 'date'], 'ref_daily_unique');
        });

        // 13. Devices aggregates
        Schema::create('analytics_devices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('device_type')->index();
            $table->date('date');
            $table->integer('views_count')->default(0);
            $table->timestamps();
            $table->unique(['device_type', 'date'], 'dev_daily_unique');
        });

        // 14. Browsers aggregates
        Schema::create('analytics_browsers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('browser_name')->index();
            $table->date('date');
            $table->integer('views_count')->default(0);
            $table->timestamps();
            $table->unique(['browser_name', 'date'], 'brow_daily_unique');
        });

        // 15. Crawler Bots aggregates
        Schema::create('analytics_bots', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bot_type')->index();
            $table->text('user_agent')->nullable();
            $table->date('date');
            $table->integer('requests_count')->default(0);
            $table->timestamps();
            $table->unique(['bot_type', 'date'], 'bot_daily_unique');
        });

        // 16. Search analytics
        Schema::create('analytics_search', function (Blueprint $table) {
            $table->increments('id');
            $table->string('keyword')->index();
            $table->date('date');
            $table->integer('searches_count')->default(0);
            $table->integer('zero_results_count')->default(0);
            $table->integer('click_throughs_count')->default(0);
            $table->timestamps();
            $table->unique(['keyword', 'date'], 'search_daily_unique');
        });

        // 17. Page load response and query errors
        Schema::create('analytics_errors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('error_type')->index(); // '404', '500'
            $table->string('page_path');
            $table->datetime('date_hour');
            $table->integer('occurrences_count')->default(0);
            $table->timestamps();
            $table->unique(['error_type', 'page_path', 'date_hour'], 'err_hourly_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_page_views');
        Schema::dropIfExists('analytics_sessions');
        Schema::dropIfExists('analytics_visitors');
        Schema::dropIfExists('analytics_daily');
        Schema::dropIfExists('analytics_hourly');
        Schema::dropIfExists('analytics_articles');
        Schema::dropIfExists('analytics_sections');
        Schema::dropIfExists('analytics_topics');
        Schema::dropIfExists('analytics_lottery');
        Schema::dropIfExists('analytics_gold');
        Schema::dropIfExists('analytics_jobs');
        Schema::dropIfExists('analytics_referrers');
        Schema::dropIfExists('analytics_devices');
        Schema::dropIfExists('analytics_browsers');
        Schema::dropIfExists('analytics_bots');
        Schema::dropIfExists('analytics_search');
        Schema::dropIfExists('analytics_errors');
    }
};

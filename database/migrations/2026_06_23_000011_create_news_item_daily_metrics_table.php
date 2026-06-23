<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_item_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_item_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamps();

            $table->unique(['news_item_id', 'metric_date']);
            $table->index(['metric_date', 'news_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_item_daily_metrics');
    }
};

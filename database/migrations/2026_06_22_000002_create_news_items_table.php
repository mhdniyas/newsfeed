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
        Schema::create('news_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_topic_id')->constrained('news_topics')->cascadeOnDelete();
            $table->string('title', 500); // 500 length in case title is long
            $table->string('source_name');
            $table->text('description')->nullable();
            $table->text('url');
            $table->text('image_url')->nullable();
            $table->string('hash')->unique();
            $table->timestamp('published_at');
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('published_at');
            $table->index('is_visible');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_items');
    }
};

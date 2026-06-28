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
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->json('extracted_body')->nullable();
            $table->text('url');
            $table->string('source_name');
            $table->dateTime('published_at')->index();
            $table->boolean('is_remote')->default(false)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->string('slug')->unique();
            $table->string('hash')->unique();
            $table->integer('views_count')->default(0);
            $table->integer('apply_clicks_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};

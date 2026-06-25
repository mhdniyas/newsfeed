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
        Schema::create('gold_rates', function (Blueprint $table) {
            $table->id();
            $table->date('rate_date');
            $table->string('city'); // 'India', 'Mumbai', 'Delhi', 'Chennai', 'Kerala'
            $table->string('state')->nullable();
            $table->string('purity'); // '24K', '22K', '18K'
            $table->decimal('price_1g', 10, 2);
            $table->decimal('price_8g', 10, 2);
            $table->decimal('price_10g', 10, 2);
            $table->decimal('change_amount', 10, 2)->nullable();
            $table->decimal('change_percent', 5, 2)->nullable();
            $table->string('source'); // 'IBJA', 'GoodReturns'
            $table->string('source_url')->nullable();
            $table->boolean('is_pending_review')->default(false);
            $table->timestamp('fetched_at');
            $table->timestamps();

            // Set unique key to avoid duplicate entries for the same city, date, and purity
            $table->unique(['rate_date', 'city', 'purity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gold_rates');
    }
};

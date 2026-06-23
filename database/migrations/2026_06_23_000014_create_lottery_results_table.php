<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_results', function (Blueprint $table) {
            $table->id();
            $table->string('lottery_name');
            $table->string('lottery_code', 32)->nullable();
            $table->string('draw_number', 64)->nullable();
            $table->date('result_date')->nullable()->index();
            $table->string('slug')->unique();
            $table->string('status', 32)->default('waiting')->index();
            $table->text('official_pdf_url')->nullable();
            $table->string('local_pdf_path')->nullable();
            $table->text('source_url')->nullable();
            $table->string('first_prize_ticket', 64)->nullable();
            $table->string('first_prize_amount', 64)->nullable();
            $table->string('second_prize_ticket', 64)->nullable();
            $table->string('second_prize_amount', 64)->nullable();
            $table->string('third_prize_ticket', 64)->nullable();
            $table->string('third_prize_amount', 64)->nullable();
            $table->json('consolation_prizes')->nullable();
            $table->json('other_prizes')->nullable();
            $table->longText('raw_text')->nullable();
            $table->timestamp('parsed_at')->nullable();
            $table->timestamp('last_fetch_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_results');
    }
};

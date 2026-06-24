<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('news_sections')
            ->where('card_limit', '<', 20)
            ->update(['card_limit' => 20]);
    }

    public function down(): void
    {
        DB::table('news_sections')
            ->where('card_limit', 20)
            ->update(['card_limit' => 6]);
    }
};

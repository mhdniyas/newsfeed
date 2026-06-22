<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_analytics', function (Blueprint $table) {
            $table->string('device_type', 32)->nullable()->after('timezone');
            $table->string('browser_name', 64)->nullable()->after('device_type');
            $table->string('os_name', 64)->nullable()->after('browser_name');
            $table->string('page_path', 255)->nullable()->after('os_name');
            $table->text('user_agent')->nullable()->after('page_path');

            $table->index('last_seen_at');
            $table->index('device_type');
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('visitor_analytics', function (Blueprint $table) {
            $table->dropIndex(['last_seen_at']);
            $table->dropIndex(['device_type']);
            $table->dropIndex(['country_code']);

            $table->dropColumn([
                'device_type',
                'browser_name',
                'os_name',
                'page_path',
                'user_agent',
            ]);
        });
    }
};

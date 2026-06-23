<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('hash');
            $table->text('canonical_url')->nullable()->after('url');
            $table->string('source_domain')->nullable()->after('source_name');
            $table->string('source_courtesy')->nullable()->after('source_domain');
            $table->json('extracted_body')->nullable()->after('description');
            $table->string('extracted_author')->nullable()->after('extracted_body');
            $table->text('extracted_image_url')->nullable()->after('image_url');
            $table->string('extraction_status')->default('pending')->after('is_favorite');
            $table->timestamp('extracted_at')->nullable()->after('extraction_status');
            $table->text('extraction_error')->nullable()->after('extracted_at');
            $table->timestamp('extraction_retry_after')->nullable()->after('extraction_error');
            $table->unsignedBigInteger('detail_views_count')->default(0)->after('views_count');
            $table->timestamp('last_detail_viewed_at')->nullable()->after('last_viewed_at');

            $table->unique('slug');
            $table->index(['extraction_status', 'published_at'], 'news_items_extraction_status_published_index');
        });

        DB::table('news_items')->orderBy('id')->get()->each(function ($item) {
            $title = trim((string) ($item->title ?? 'news-item'));
            $hash = (string) ($item->hash ?? '');
            $slug = Str::slug(Str::limit($title, 80, ''));
            $slug = ($slug !== '' ? $slug : 'news-item') . '-' . substr($hash !== '' ? $hash : md5((string) $item->id), 0, 8);

            $url = (string) ($item->url ?? '');
            $host = parse_url($url, PHP_URL_HOST);
            $host = is_string($host) ? strtolower($host) : null;
            $courtesy = $host ? preg_replace('/^www\./', '', $host) : null;

            DB::table('news_items')->where('id', $item->id)->update([
                'slug' => $slug,
                'canonical_url' => $url !== '' ? $url : null,
                'source_domain' => $host,
                'source_courtesy' => $courtesy ?: ($item->source_name ?: null),
                'extraction_status' => 'pending',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropIndex('news_items_extraction_status_published_index');
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug',
                'canonical_url',
                'source_domain',
                'source_courtesy',
                'extracted_body',
                'extracted_author',
                'extracted_image_url',
                'extraction_status',
                'extracted_at',
                'extraction_error',
                'extraction_retry_after',
                'detail_views_count',
                'last_detail_viewed_at',
            ]);
        });
    }
};

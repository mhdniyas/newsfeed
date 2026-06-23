<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TrendingNewsService;
use App\Models\NewsSection;
use App\Models\NewsTopic;
use Illuminate\Support\Facades\Http;

$section = NewsSection::firstOrCreate([
    'slug' => 'google-trends',
], [
    'name' => 'Google Trends',
    'description' => 'Trends',
    'is_active' => true,
]);

$topic = NewsTopic::firstOrCreate([
    'news_section_id' => $section->id,
    'keyword' => 'Apple Watch',
    'country' => 'US',
], [
    'name' => 'Apple Watch',
    'language' => 'en',
    'is_active' => true,
]);

$topics = $section->newsTopics()->where('is_active', true)->get();
echo "Topics count: " . $topics->count() . "\n";
foreach ($topics as $t) {
    echo "Topic: " . $t->name . " (Active: " . ($t->is_active ? 'yes' : 'no') . ")\n";
}

Http::fake([
    'news.google.com/*' => Http::response('<?xml version="1.0" encoding="utf-8"?><rss version="2.0"><channel><item><title>Test - ESPN</title><link>https://example.com/test</link><pubDate>Mon, 22 Jun 2026 10:00:00 GMT</pubDate><description>Desc</description></item></channel></rss>', 200),
]);

$service = app(TrendingNewsService::class);
$res = $service->syncTrendingNews(10);
echo "Saved count: " . $res . "\n";

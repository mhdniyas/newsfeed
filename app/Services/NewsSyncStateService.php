<?php

namespace App\Services;

use App\Models\NewsSection;
use App\Models\Setting;

class NewsSyncStateService
{
    public function startCycle(string $source, int $totalSections, int $totalTopics, int $cycleLimit): void
    {
        Setting::set('news_sync_status', 'running');
        Setting::set('news_sync_requested_at', Setting::get('news_sync_requested_at') ?: now()->toIso8601String());
        Setting::set('news_sync_started_at', now()->toIso8601String());
        Setting::set('news_sync_finished_at', null);
        Setting::set('news_sync_last_output', null);
        Setting::set('news_sync_finalize_dispatched', null);
        Setting::set('news_sync_log', json_encode([
            [
                'time' => now()->toIso8601String(),
                'level' => 'info',
                'message' => $source,
            ],
            [
                'time' => now()->toIso8601String(),
                'level' => 'info',
                'message' => 'Cycle started. Dispatching section fetch jobs.',
            ],
        ]));
        Setting::set('news_sync_meta', json_encode([
            'progress' => 4,
            'stage' => 'Dispatching section jobs',
            'processed_sections' => 0,
            'total_sections' => $totalSections,
            'processed_topics' => 0,
            'total_topics' => $totalTopics,
            'failed_sections' => 0,
            'article_limit' => $cycleLimit,
            'cycle_limit' => $cycleLimit,
            'section_limit' => 6,
            'current_section' => null,
            'current_topic' => null,
            'current_item' => null,
            'total_items' => null,
            'current_article' => null,
            'stats' => [
                'new_articles' => 0,
                'google_articles' => 0,
                'official_articles' => 0,
                'skipped_duplicates' => 0,
                'images_recovered' => 0,
                'completed_sections' => 0,
            ],
        ]));
    }

    public function meta(): array
    {
        return json_decode(Setting::get('news_sync_meta', '{}') ?: '{}', true) ?: [];
    }

    public function updateMeta(array $payload): void
    {
        Setting::set('news_sync_meta', json_encode(array_merge($this->meta(), $payload)));
    }

    public function appendLog(string $message, string $level = 'info'): void
    {
        $log = json_decode(Setting::get('news_sync_log', '[]') ?: '[]', true) ?: [];
        $log[] = [
            'time' => now()->toIso8601String(),
            'level' => $level,
            'message' => $message,
        ];

        Setting::set('news_sync_log', json_encode(array_slice($log, -60)));
    }

    public function globalLimitReached(): bool
    {
        $meta = $this->meta();
        $limit = (int) ($meta['cycle_limit'] ?? $meta['article_limit'] ?? 60);
        $saved = (int) (($meta['stats']['new_articles'] ?? 0));

        return $saved >= $limit;
    }

    public function savedArticles(): int
    {
        return (int) (($this->meta()['stats']['new_articles'] ?? 0));
    }

    public function beginSection(NewsSection $section, int $sectionTopics): void
    {
        $meta = $this->meta();
        $processedSections = (int) ($meta['processed_sections'] ?? 0);
        $totalSections = max(1, (int) ($meta['total_sections'] ?? 1));
        $progress = min(92, 8 + (int) floor(($processedSections / $totalSections) * 78));

        $this->updateMeta([
            'progress' => $progress,
            'stage' => "Processing {$section->name}",
            'current_section' => $section->name,
            'current_topic' => null,
            'current_item' => null,
            'total_items' => null,
            'current_article' => null,
            'section_limit' => $section->card_limit ?: 6,
            'section_topics' => $sectionTopics,
        ]);

        $this->appendLog("Section {$section->name} started.", 'info');
    }

    public function updateTopicProgress(NewsSection $section, string $topicName, int $processedTopics, int $totalTopics, int $currentItem = 0, int $totalItems = 0, ?string $articleTitle = null): void
    {
        $meta = $this->meta();
        $processedSections = (int) ($meta['processed_sections'] ?? 0);
        $totalSections = max(1, (int) ($meta['total_sections'] ?? 1));
        $sectionFraction = $processedSections / $totalSections;
        $topicFraction = $totalTopics > 0 ? ($processedTopics / max(1, $totalTopics)) / $totalSections : 0;

        $this->updateMeta([
            'progress' => min(95, 10 + (int) floor(($sectionFraction + $topicFraction) * 82)),
            'stage' => "Processing {$section->name}",
            'current_section' => $section->name,
            'current_topic' => $topicName,
            'current_item' => $currentItem ?: null,
            'total_items' => $totalItems ?: null,
            'current_article' => $articleTitle,
            'processed_topics' => $processedTopics,
            'total_topics' => $totalTopics,
        ]);
    }

    public function accumulateStats(array $delta): void
    {
        $meta = $this->meta();
        $stats = $meta['stats'] ?? [];

        foreach ([
            'new_articles',
            'google_articles',
            'official_articles',
            'skipped_duplicates',
            'images_recovered',
        ] as $key) {
            $stats[$key] = (int) ($stats[$key] ?? 0) + (int) ($delta[$key] ?? 0);
        }

        $meta['stats'] = $stats;
        Setting::set('news_sync_meta', json_encode($meta));
    }

    public function completeSection(NewsSection $section, array $summary): bool
    {
        $meta = $this->meta();
        $processedSections = ((int) ($meta['processed_sections'] ?? 0)) + 1;
        $totalSections = max(1, (int) ($meta['total_sections'] ?? 1));
        $meta['processed_sections'] = $processedSections;
        $meta['progress'] = min(96, 12 + (int) floor(($processedSections / $totalSections) * 84));
        $meta['stage'] = "Completed {$section->name}";
        $meta['current_section'] = $section->name;
        $meta['current_topic'] = null;
        $meta['current_item'] = null;
        $meta['total_items'] = null;
        $meta['current_article'] = null;
        $meta['stats']['completed_sections'] = $processedSections;

        Setting::set('news_sync_meta', json_encode($meta));
        $this->appendLog("Section {$section->name} completed: {$summary['new_articles']} new, {$summary['skipped_duplicates']} duplicates, {$summary['images_recovered']} recovered images.", 'success');

        return $processedSections >= $totalSections;
    }

    public function failSection(NewsSection $section, string $message): bool
    {
        $meta = $this->meta();
        $meta['failed_sections'] = ((int) ($meta['failed_sections'] ?? 0)) + 1;
        $meta['processed_sections'] = ((int) ($meta['processed_sections'] ?? 0)) + 1;
        $meta['stage'] = "Failed {$section->name}";
        $meta['current_section'] = $section->name;
        $meta['current_topic'] = null;
        $meta['current_item'] = null;
        $meta['total_items'] = null;
        $meta['current_article'] = null;
        $meta['progress'] = min(96, (int) ($meta['progress'] ?? 0) + 5);

        Setting::set('news_sync_meta', json_encode($meta));
        $this->appendLog("Section {$section->name} failed: {$message}", 'error');

        return (int) ($meta['processed_sections'] ?? 0) >= max(1, (int) ($meta['total_sections'] ?? 1));
    }

    public function finalizerShouldDispatch(): bool
    {
        if (Setting::get('news_sync_finalize_dispatched')) {
            return false;
        }

        Setting::set('news_sync_finalize_dispatched', '1');

        return true;
    }

    public function finalizeCycle(): string
    {
        $meta = $this->meta();
        $stats = $meta['stats'] ?? [];
        $failedSections = (int) ($meta['failed_sections'] ?? 0);
        $status = $failedSections > 0 ? 'partial_failed' : 'completed';
        $summary = "Cycle completed. Added " . ((int) ($stats['google_articles'] ?? 0)) . " Google News articles, "
            . ((int) ($stats['official_articles'] ?? 0)) . " official FIFA articles, recovered "
            . ((int) ($stats['images_recovered'] ?? 0)) . " images, skipped "
            . ((int) ($stats['skipped_duplicates'] ?? 0)) . " duplicates. Saved "
            . ((int) ($stats['new_articles'] ?? 0)) . '/' . ((int) ($meta['cycle_limit'] ?? 60))
            . " allowed articles across " . ((int) ($meta['processed_sections'] ?? 0)) . '/' . ((int) ($meta['total_sections'] ?? 0))
            . ' sections.';

        $this->updateMeta([
            'progress' => 100,
            'stage' => $failedSections > 0 ? 'Completed with issues' : 'Completed',
            'current_section' => null,
            'current_topic' => null,
            'current_item' => null,
            'total_items' => null,
            'current_article' => null,
            'summary' => $summary,
        ]);

        Setting::set('news_sync_status', $status);
        Setting::set('news_sync_finished_at', now()->toIso8601String());
        Setting::set('news_sync_last_output', $summary);
        Setting::set('news_sync_process_id', null);
        Setting::set('news_sync_total_runs', (string) (((int) Setting::get('news_sync_total_runs', '0')) + 1));
        Setting::set('news_sync_last_success_at', now()->toIso8601String());
        $this->appendLog($summary, $failedSections > 0 ? 'warning' : 'success');

        return $summary;
    }
}

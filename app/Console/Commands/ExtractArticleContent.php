<?php

namespace App\Console\Commands;

use App\Services\ArticleContentExtractionService;
use Illuminate\Console\Command;

class ExtractArticleContent extends Command
{
    protected $signature = 'news:extract-articles {--limit=10 : Number of recent articles to process}';

    protected $description = 'Fetch source content for recent news articles and build internal detail pages';

    public function handle(ArticleContentExtractionService $extractionService): int
    {
        $limit = max(1, min(50, (int) $this->option('limit')));
        $stats = $extractionService->processPending($limit);

        $this->info(sprintf(
            'Processed %d article(s): %d extracted, %d failed, %d skipped.',
            $stats['processed'],
            $stats['extracted'],
            $stats['failed'],
            $stats['skipped']
        ));

        return self::SUCCESS;
    }
}

<?php

namespace App\Services;

use App\Models\NewsItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ArticleContentExtractionService
{
    public const BATCH_LIMIT = 10;

    public function pendingArticles(int $limit = self::BATCH_LIMIT): Collection
    {
        return NewsItem::query()
            ->where('is_visible', true)
            ->where(function ($query): void {
                $query->where(function ($pendingQuery): void {
                    $pendingQuery->whereNull('extracted_at')
                        ->where('extraction_status', 'pending');
                })
                    ->orWhere(function ($retryQuery): void {
                        $retryQuery->where('extraction_status', 'retry')
                            ->where(function ($backoffQuery): void {
                                $backoffQuery->whereNull('extraction_retry_after')
                                    ->orWhere('extraction_retry_after', '<=', now());
                            });
                    })
                    ->orWhere(function ($retryQuery): void {
                        $retryQuery->where('extraction_status', 'failed')
                            ->whereNotNull('extraction_retry_after')
                            ->where('extraction_retry_after', '<=', now());
                    });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get();
    }

    public function processPending(int $limit = self::BATCH_LIMIT): array
    {
        $stats = [
            'processed' => 0,
            'extracted' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($this->pendingArticles($limit) as $article) {
            $stats['processed']++;

            $result = $this->extractForArticle($article);

            if ($result['status'] === 'extracted') {
                $stats['extracted']++;
            } elseif ($result['status'] === 'skipped') {
                $stats['skipped']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    public function extractForArticle(NewsItem $article): array
    {
        $targetUrl = trim((string) ($article->canonical_url ?: $article->url));

        if ($targetUrl === '' || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            $this->markFailure($article, 'Missing or invalid source URL.', false);

            return ['status' => 'failed'];
        }

        try {
            $response = Http::timeout(12)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept' => 'text/html,application/xhtml+xml',
                    'User-Agent' => 'Mozilla/5.0 SignalzNewsBot/1.0',
                ])
                ->get($targetUrl);

            if (!$response->successful() || trim($response->body()) === '') {
                $this->markFailure($article, 'Source fetch failed with HTTP ' . $response->status() . '.', true);

                return ['status' => 'failed'];
            }

            $payload = $this->extractPayloadFromHtml($response->body(), $targetUrl, $article);

            if (($payload['paragraphs'] ?? []) === []) {
                $this->markFailure($article, 'Readable source paragraphs were not found.', true);

                return ['status' => 'failed'];
            }

            $article->forceFill([
                'canonical_url' => $payload['canonical_url'] ?: $targetUrl,
                'source_domain' => $payload['source_domain'],
                'source_courtesy' => $payload['source_courtesy'],
                'extracted_body' => $payload['paragraphs'],
                'extracted_author' => $payload['author'],
                'extracted_image_url' => $payload['image_url'] ?: $article->extracted_image_url,
                'slug' => $article->slug ?: $article->makeSlug(),
                'extraction_status' => 'extracted',
                'extracted_at' => now(),
                'extraction_error' => null,
                'extraction_retry_after' => null,
            ])->save();

            return ['status' => 'extracted'];
        } catch (\Throwable $exception) {
            $this->markFailure($article, Str::limit($exception->getMessage(), 300), true);

            return ['status' => 'failed'];
        }
    }

    public function extractPayloadFromHtml(string $html, string $url, ?NewsItem $article = null): array
    {
        libxml_use_internal_errors(true);

        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($document);

        $canonicalUrl = $this->metaContent($xpath, '//link[@rel="canonical"]/@href')
            ?: $this->metaContent($xpath, '//meta[@property="og:url"]/@content')
            ?: $url;

        $host = parse_url($canonicalUrl, PHP_URL_HOST) ?: parse_url($url, PHP_URL_HOST);
        $sourceDomain = is_string($host) ? strtolower($host) : null;
        $sourceCourtesy = $sourceDomain ? preg_replace('/^www\./', '', $sourceDomain) : ($article?->source_name ?: null);

        $author = $this->metaContent($xpath, '//meta[@name="author"]/@content')
            ?: $this->metaContent($xpath, '//meta[@property="article:author"]/@content')
            ?: $this->firstNodeText($xpath, [
                "//*[contains(@class,'author')]",
                "//*[contains(@class,'byline')]",
                "//*[@itemprop='author']",
            ]);

        $imageUrl = $this->metaContent($xpath, '//meta[@property="og:image"]/@content')
            ?: $this->metaContent($xpath, '//meta[@name="twitter:image"]/@content');

        $articleRoot = $this->firstNode($xpath, [
            "//*[@itemprop='articleBody']",
            '//article',
            "//*[contains(@class,'article-body')]",
            "//*[contains(@class,'story-body')]",
            "//*[contains(@class,'entry-content')]",
            "//*[contains(@class,'post-content')]",
            '//main',
        ]);

        $paragraphNodes = $articleRoot
            ? $xpath->query('.//p', $articleRoot)
            : $xpath->query('//p');

        $paragraphs = [];
        $totalChars = 0;

        foreach ($paragraphNodes ?: [] as $paragraphNode) {
            $text = trim(preg_replace('/\s+/', ' ', html_entity_decode($paragraphNode->textContent ?: '')));

            if ($text === '' || mb_strlen($text) < 60) {
                continue;
            }

            if (preg_match('/^(subscribe|sign up|read more|advertisement)/i', $text)) {
                continue;
            }

            $paragraphs[] = $text;
            $totalChars += mb_strlen($text);

            if (count($paragraphs) >= 6 || $totalChars >= 2200) {
                break;
            }
        }

        return [
            'canonical_url' => $canonicalUrl,
            'source_domain' => $sourceDomain,
            'source_courtesy' => $sourceCourtesy,
            'author' => $author ? Str::limit(trim($author), 255, '') : null,
            'image_url' => $imageUrl,
            'paragraphs' => $paragraphs,
        ];
    }

    protected function markFailure(NewsItem $article, string $message, bool $shouldRetry): void
    {
        $article->forceFill([
            'extraction_status' => $shouldRetry ? 'retry' : 'failed',
            'extraction_error' => $message,
            'extraction_retry_after' => $shouldRetry ? now()->addHours(3) : null,
        ])->save();
    }

    protected function firstNode(\DOMXPath $xpath, array $queries): ?\DOMNode
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);

            if ($nodes && $nodes->length > 0) {
                return $nodes->item(0);
            }
        }

        return null;
    }

    protected function firstNodeText(\DOMXPath $xpath, array $queries): ?string
    {
        $node = $this->firstNode($xpath, $queries);

        return $node ? trim(preg_replace('/\s+/', ' ', $node->textContent ?: '')) : null;
    }

    protected function metaContent(\DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);

        if (!$nodes || $nodes->length === 0) {
            return null;
        }

        $value = trim((string) $nodes->item(0)?->nodeValue);

        return $value !== '' ? $value : null;
    }
}

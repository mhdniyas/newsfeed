<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

class FifaOfficialNewsService
{
    protected string $scoresUrl = 'https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/scores-fixtures';

    public function __construct(protected HeadlessPageRenderer $renderer)
    {
    }

    public function latestArticles(int $limit = 10): array
    {
        $html = $this->renderer->render($this->scoresUrl, 600);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $links = $xpath->query("//h3[contains(., 'Latest FIFA World Cup 2026')]/following::a[contains(@href, '/en/articles/')]");
        $articles = [];

        foreach ($links as $link) {
            $title = trim(preg_replace('/\s+/', ' ', $link->textContent ?? ''));
            $href = $link->getAttribute('href');

            if (!$title || !$href) {
                continue;
            }

            $imgNode = $xpath->query(".//img", $link)->item(0);
            $imageUrl = $imgNode?->getAttribute('src') ?: null;

            if (!$imageUrl && $imgNode?->getAttribute('srcset')) {
                $srcSet = trim($imgNode->getAttribute('srcset'));
                $firstSource = Str::before($srcSet, ',');
                $imageUrl = trim(Str::beforeLast($firstSource, ' '));
            }

            if ($imageUrl && !Str::startsWith($imageUrl, 'http')) {
                $imageUrl = 'https://www.fifa.com' . $imageUrl;
            }

            $articles[] = [
                'title' => $this->normalizeTitle($title),
                'url' => Str::startsWith($href, 'http') ? $href : 'https://www.fifa.com' . $href,
                'image_url' => $imageUrl,
                'source_name' => 'FIFA.com',
                'description' => 'Official FIFA World Cup 2026 coverage from FIFA.com.',
            ];
        }

        return collect($articles)
            ->unique('url')
            ->take($limit)
            ->values()
            ->all();
    }

    protected function normalizeTitle(string $title): string
    {
        $title = trim($title);
        $half = (int) (mb_strlen($title) / 2);

        if ($half > 0) {
            $firstHalf = mb_substr($title, 0, $half);
            $secondHalf = mb_substr($title, $half);

            if ($firstHalf === $secondHalf) {
                return $firstHalf;
            }
        }

        return $title;
    }
}

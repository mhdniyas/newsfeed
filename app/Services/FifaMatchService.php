<?php

namespace App\Services;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

class FifaMatchService
{
    protected string $scoresUrl = 'https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/scores-fixtures';

    public function __construct(protected HeadlessPageRenderer $renderer)
    {
    }

    public function getScoreboard(): array
    {
        $html = $this->renderer->render($this->scoresUrl, 600);

        if (!$html) {
            return [
                'recent' => [],
                'upcoming' => [],
                'source_url' => $this->scoresUrl,
                'synced_at' => null,
            ];
        }

        $matches = $this->parseMatches($html);
        $nowUtc = now('UTC');

        $recent = collect($matches)
            ->filter(fn (array $match) => $match['status'] === 'FT')
            ->sortByDesc(fn (array $match) => optional($match['kickoff_at'])->timestamp ?? optional($match['date'])->timestamp ?? 0)
            ->take(6)
            ->values()
            ->all();

        $upcoming = collect($matches)
            ->filter(fn (array $match) => $match['status'] === 'UPCOMING')
            ->filter(fn (array $match) => $match['kickoff_at'] === null || $match['kickoff_at']->gte($nowUtc))
            ->sortBy(fn (array $match) => optional($match['kickoff_at'])->timestamp ?? PHP_INT_MAX)
            ->take(8)
            ->values()
            ->all();

        return [
            'recent' => $recent,
            'upcoming' => $upcoming,
            'source_url' => $this->scoresUrl,
            'synced_at' => now(),
        ];
    }

    protected function parseMatches(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $links = $xpath->query("//a[contains(@href, '/en/match-centre/match/')]");
        $matches = [];

        foreach ($links as $link) {
            $dateText = $this->nearestDateText($xpath, $link);
            $matches[] = $this->buildMatchFromNode($xpath, $link, $dateText);
        }

        return array_values(array_filter($matches, fn (?array $match) => $match !== null));
    }

    protected function buildMatchFromNode(DOMXPath $xpath, \DOMNode $link, ?string $dateText): ?array
    {
        $href = $link->attributes?->getNamedItem('href')?->nodeValue;
        $teams = $xpath->query(".//div[contains(@class, 'match-row_team__')]//span[contains(@class, 'd-none d-md-block')]", $link);
        $scores = $xpath->query(".//span[contains(@class, 'match-row_score__')]", $link);
        $statusNode = $xpath->query(".//span[contains(@class, 'match-row_statusLabel__') or contains(@class, 'match-row_matchTime__')]", $link)->item(0);
        $bottomLabels = $xpath->query(".//span[contains(@class, 'match-row_bottomLabel__')]", $link);
        $stadiumLabels = $xpath->query(".//div[contains(@class, 'match-row_stadiumCityLabels__')]//span", $link);

        if (!$href || $teams->length < 2 || !$statusNode || $bottomLabels->length < 2 || $stadiumLabels->length < 2) {
            return null;
        }

        $homeTeam = trim($teams->item(0)->textContent ?? '');
        $awayTeam = trim($teams->item(1)->textContent ?? '');
        $marker = trim($statusNode->textContent ?? '');
        $stage = trim($bottomLabels->item(0)->textContent ?? '');
        $group = trim($bottomLabels->item(1)->textContent ?? '');
        $stadium = trim($stadiumLabels->item(0)->textContent ?? '');
        $city = trim(trim($stadiumLabels->item(1)->textContent ?? ''), '() ');

        $date = $this->inferDate($dateText);
        $kickoffAt = $marker !== 'FT' ? $this->buildKickoffAt($date, $marker) : null;

        if ($marker === 'FT' && $scores->length >= 2) {
            $homeScore = (int) trim($scores->item(0)->textContent ?? '0');
            $awayScore = (int) trim($scores->item(1)->textContent ?? '0');

            return [
                'home_team' => $homeTeam,
                'away_team' => $awayTeam,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'status' => 'FT',
                'kickoff' => null,
                'kickoff_at' => $kickoffAt,
                'stage' => $stage,
                'group' => $group,
                'stadium' => $stadium,
                'city' => $city,
                'date' => $date,
                'match_url' => Str::startsWith($href, 'http') ? $href : 'https://www.fifa.com' . $href,
            ];
        }

        return [
            'home_team' => $homeTeam,
            'away_team' => $awayTeam,
            'home_score' => null,
            'away_score' => null,
            'status' => 'UPCOMING',
            'kickoff' => $marker,
            'kickoff_at' => $kickoffAt,
            'stage' => $stage,
            'group' => $group,
            'stadium' => $stadium,
            'city' => $city,
            'date' => $date,
            'match_url' => Str::startsWith($href, 'http') ? $href : 'https://www.fifa.com' . $href,
        ];
    }

    protected function nearestDateText(DOMXPath $xpath, \DOMNode $link): ?string
    {
        $node = $xpath->query("preceding::*[contains(normalize-space(), 'June 2026') or contains(normalize-space(), 'July 2026')][1]", $link)?->item(0);

        if (!$node) {
            return null;
        }

        $text = trim(preg_replace('/\s+/', ' ', $node->textContent ?? ''));

        return preg_match('/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)\s+\d{2}\s+(June|July)\s+2026$/', $text)
            ? $text
            : null;
    }

    protected function inferDate(?string $dateText): ?Carbon
    {
        if (!$dateText) {
            return null;
        }

        try {
            return Carbon::createFromFormat('l d F Y', $dateText, 'UTC')->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function buildKickoffAt(?Carbon $date, string $kickoff): ?Carbon
    {
        if (!$date || !preg_match('/^\d{2}:\d{2}$/', $kickoff)) {
            return null;
        }

        [$hours, $minutes] = array_map('intval', explode(':', $kickoff));

        return $date->copy()->setTime($hours, $minutes, 0);
    }
}

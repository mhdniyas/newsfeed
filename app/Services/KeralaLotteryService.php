<?php

namespace App\Services;

use App\Models\LotteryResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KeralaLotteryService
{
    public const LISTING_URL = 'https://result.keralalotteries.com/';
    public const STORAGE_DISK = 'local';
    public const STORAGE_DIR = 'lottery-results';
    public const TIMEZONE = 'Asia/Kolkata';
    public const FIRST_FETCH_HOUR = 16;
    public const FIRST_FETCH_MINUTE = 10;

    public function syncLatest(int $limit = 10): array
    {
        $this->recordAttempt('started');
        $windowOpenAt = $this->firstFetchAt();

        if (!$this->fetchWindowOpened()) {
            $this->recordAttempt('waiting_for_window', [
                'message' => 'Kerala lottery sync skipped before 4:10 PM IST.',
                'next_attempt_at' => $windowOpenAt->toIso8601String(),
            ]);

            return [
                'saved' => 0,
                'rows' => 0,
                'status' => 'waiting_for_window',
                'message' => 'Waiting for 4:10 PM IST.',
                'next_attempt_at' => $windowOpenAt,
            ];
        }

        if ($this->hasTodayResult()) {
            $this->recordAttempt('already_available', [
                'message' => 'Today\'s Kerala lottery result is already available.',
                'next_attempt_at' => $this->nextDayFirstFetchAt()->toIso8601String(),
            ]);

            return [
                'saved' => 0,
                'rows' => 0,
                'status' => 'already_available',
                'message' => 'Today\'s result already exists.',
                'next_attempt_at' => $this->nextDayFirstFetchAt(),
            ];
        }

        $rows = $this->fetchListingRows($limit);
        $saved = 0;

        foreach ($rows as $row) {
            $this->saveResultFromRow($row);
            $saved++;
        }

        $status = $saved > 0 ? 'saved_today_result' : 'today_result_not_found';
        $nextAttemptAt = $saved > 0 ? $this->nextDayFirstFetchAt() : $this->nextRetryAt();
        $message = $saved > 0
            ? 'Today\'s Kerala lottery result fetched successfully.'
            : 'Today\'s Kerala lottery result is not published yet. Retry after 30 minutes.';

        $this->recordAttempt($status, [
            'message' => $message,
            'next_attempt_at' => $nextAttemptAt->toIso8601String(),
            'saved' => (string) $saved,
        ]);

        return [
            'saved' => $saved,
            'rows' => count($rows),
            'status' => $status,
            'message' => $message,
            'next_attempt_at' => $nextAttemptAt,
        ];
    }

    public function fetchListingRows(int $limit = 10): array
    {
        $response = Http::timeout(20)
            ->withoutVerifying()
            ->retry(2, 1000)
            ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
            ->get(self::LISTING_URL);

        $response->throw();

        preg_match_all(
            '/<tr><td[^>]*>([^<]+)<\/td><td[^>]*>(\d{2}\/\d{2}\/\d{4})<\/td><td[^>]*><a\s+href="([^"]+)"/i',
            $response->body(),
            $matches,
            PREG_SET_ORDER
        );

        $rows = [];

        $today = $this->todayInIndia()->toDateString();

        foreach ($matches as $match) {
            $label = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5));
            $date = Carbon::createFromFormat('d/m/Y', trim($match[2]))?->startOfDay();
            $relativePdfUrl = trim($match[3]);

            if (!$date) {
                continue;
            }

            if ($date->toDateString() !== $today) {
                continue;
            }

            preg_match('/^(.*?)\(([^)]+)\)$/', $label, $labelMatch);

            $lotteryName = trim($labelMatch[1] ?? $label);
            $drawNumber = trim($labelMatch[2] ?? '');
            $lotteryCode = trim((string) Str::before($drawNumber, '-'));
            $officialPdfUrl = Str::startsWith($relativePdfUrl, 'http')
                ? $relativePdfUrl
                : 'https://result.keralalotteries.com/' . ltrim($relativePdfUrl, '/');

            $rows[] = [
                'lottery_name' => Str::title(str_replace('-', ' ', $lotteryName)),
                'lottery_code' => $lotteryCode !== '' ? $lotteryCode : null,
                'draw_number' => $drawNumber !== '' ? $drawNumber : null,
                'result_date' => $date,
                'official_pdf_url' => $officialPdfUrl,
                'source_url' => self::LISTING_URL,
            ];

            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    public function saveResultFromRow(array $row): LotteryResult
    {
        $result = LotteryResult::query()->firstOrNew([
            'slug' => $this->makeSlug(
                $row['lottery_name'],
                $row['draw_number'] ?? null,
                $row['result_date'] ?? null,
            ),
        ]);

        $result->fill([
            'lottery_name' => $row['lottery_name'],
            'lottery_code' => $row['lottery_code'] ?? null,
            'draw_number' => $row['draw_number'] ?? null,
            'result_date' => $row['result_date'] ?? null,
            'official_pdf_url' => $row['official_pdf_url'] ?? null,
            'source_url' => $row['source_url'] ?? self::LISTING_URL,
            'status' => 'waiting',
            'last_fetch_at' => now(),
        ]);

        if (blank($result->official_pdf_url)) {
            $result->status = 'failed';
            $result->save();

            return $result;
        }

        try {
            $pdfResponse = Http::timeout(30)
                ->withoutVerifying()
                ->retry(2, 1000)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->get($result->official_pdf_url);

            $pdfResponse->throw();

            $relativePath = $this->relativePdfPath($result);
            Storage::disk(self::STORAGE_DISK)->put($relativePath, $pdfResponse->body());

            $result->local_pdf_path = $relativePath;
            $result->status = 'pdf_available';

            $rawText = $this->extractPdfText(Storage::disk(self::STORAGE_DISK)->path($relativePath));
            $result->raw_text = $rawText;

            if (filled($rawText)) {
                $parsed = $this->parseRawText($rawText, $row);
                $result->fill($parsed);
                $result->parsed_at = now();
                // Mark as parsed if ANY prize data was extracted (including other_prizes)
                $hasPrizes = $result->hasParsedPrizes()
                    || !empty($result->other_prizes)
                    || !empty($result->consolation_prizes);
                $result->status = $hasPrizes ? 'available' : 'parse_failed';
            } else {
                $result->status = 'parse_failed';
            }
        } catch (\Throwable) {
            $result->status = 'failed';
        }

        $result->save();

        return $result;
    }

    public function parseRawText(string $rawText, array $fallback = []): array
    {
        // Remove page footers that contain the date/time (which can match years like 2026 as ending numbers)
        $rawText = preg_replace('/Page\s+\d+\s*Modernization\s*&\s*IT\s*Software\s*Division\s*:\s*Department\s*of\s*State\s*Lotteries\s*\d{2}\/\d{2}\/\d{4}\s*\d{2}:\d{2}:\d{2}/i', ' ', $rawText) ?? $rawText;

        $normalized = preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' ', $rawText)) ?? $rawText;

        $parsed = [
            'lottery_name'        => $fallback['lottery_name'] ?? 'Kerala Lottery',
            'lottery_code'        => $fallback['lottery_code'] ?? null,
            'draw_number'         => $fallback['draw_number'] ?? null,
            'result_date'         => $fallback['result_date'] ?? null,
            'first_prize_ticket'  => null,
            'first_prize_amount'  => null,
            'second_prize_ticket' => null,
            'second_prize_amount' => null,
            'third_prize_ticket'  => null,
            'third_prize_amount'  => null,
            'consolation_prizes'  => null,
            'other_prizes'        => null,
        ];

        if (preg_match('/\b([A-Z][A-Z\-\s]+?)\s+LOTTERY NO\.?\s*([A-Z]{1,4}-\d+)/', $normalized, $match)) {
            $parsed['lottery_name'] = Str::title(strtolower(trim(str_replace('-', ' ', $match[1]))));
            $parsed['draw_number'] = strtoupper(trim($match[2]));
            $parsed['lottery_code'] = Str::before($parsed['draw_number'], '-');
        }

        if (preg_match('/held on:-\s*(\d{2}\/\d{2}\/\d{4})/i', $normalized, $dateMatch)) {
            $parsed['result_date'] = Carbon::createFromFormat('d/m/Y', $dateMatch[1])?->startOfDay();
        }

        // Parse 1st, 2nd, 3rd prizes (full ticket numbers)
        foreach ([
            'first'  => '1st',
            'second' => '2nd',
            'third'  => '3rd',
        ] as $key => $label) {
            $parsed["{$key}_prize_ticket"] = $this->extractPrizeTicket($normalized, $label);
            $parsed["{$key}_prize_amount"] = $this->extractPrizeAmount($normalized, $label);
        }

        // Parse consolation prizes (full ticket numbers)
        if (preg_match('/Cons(?:olation)?\s+Prize(?:-Rs\s*:?\s*[\d,\/-]+)?\s+(.*?)(?=\d{1,2}(?:st|nd|rd|th)\s+Prize|Agent Prize|FOR THE TICKETS|$)/i', $normalized, $consMatch)) {
            preg_match_all('/([A-Z]{1,3}\s?-?\s?\d{6})/', $consMatch[1], $ticketMatches);
            $consolation = collect($ticketMatches[1] ?? [])
                ->map(fn ($t) => $this->normalizeTicket($t))
                ->filter()
                ->values()
                ->all();
            $parsed['consolation_prizes'] = $consolation ?: null;
        }

        // Parse 4th-10th prizes (4-digit ending numbers)
        // Use section-splitting: find each prize block between ordinal labels
        $otherPrizes = [];
        $prizeOrdinals = [
            '4th' => 4, '5th' => 5, '6th' => 6, '7th' => 7, '8th' => 8, '9th' => 9, '10th' => 10,
        ];
        $nextOrdinals = ['5th', '6th', '7th', '8th', '9th', '10th', 'Agent Prize', 'The prize', 'prize winners'];
        foreach ($prizeOrdinals as $ordinal => $num) {
            // Build lookahead for "next section" boundary
            $lookahead = implode('|', array_map(fn ($n) => preg_quote($n, '/'), $nextOrdinals));
            // Extract the section for this prize
            $sectionPattern = sprintf(
                '/%s\\s+Prize(?:-Rs\\s*:?\\s*([\\d,]+)\\s*\\/-)?(.+?)(?=%s|$)/is',
                preg_quote($ordinal, '/'),
                $lookahead
            );
            if (!preg_match($sectionPattern, $normalized, $pm)) {
                // Remove current ordinal from the lookahead for next iteration
                array_shift($nextOrdinals);
                continue;
            }
            $amount = filled($pm[1] ?? '') ? $this->formatIndianAmount((int) str_replace(',', '', $pm[1])) : null;
            $section = $pm[2] ?? '';
            // Extract only standalone 4-digit numbers (not part of 5+ digit sequences)
            preg_match_all('/(?<!\d)(\d{4})(?!\d)/', $section, $numMatches);
            $numbers = array_values(array_unique($numMatches[1] ?? []));
            if (!empty($numbers)) {
                $otherPrizes[] = [
                    'label'   => $ordinal . ' Prize',
                    'amount'  => $amount,
                    'numbers' => $numbers,
                ];
            }
            array_shift($nextOrdinals);
        }
        $parsed['other_prizes'] = $otherPrizes ?: null;

        return $parsed;
    }

    protected function extractPrizeTicket(string $text, string $prizeLabel): ?string
    {
        $pattern = sprintf(
            '/%s\s+Prize.*?(?:\d+\)\s*)?([A-Z]{1,3}\s?-?\s?\d{6})/i',
            preg_quote($prizeLabel, '/')
        );

        if (!preg_match($pattern, $text, $match)) {
            return null;
        }

        return $this->normalizeTicket($match[1]);
    }

    protected function extractPrizeAmount(string $text, string $prizeLabel): ?string
    {
        $pattern = sprintf('/%s\s+Prize\s+Rs\s*:?\s*([0-9,]+)\s*\/-/i', preg_quote($prizeLabel, '/'));

        if (!preg_match($pattern, $text, $match)) {
            return null;
        }

        return $this->formatIndianAmount((int) str_replace(',', '', $match[1]));
    }

    protected function normalizeTicket(?string $ticket): ?string
    {
        if (!$ticket) {
            return null;
        }

        if (!preg_match('/([A-Z]{1,3})\s?-?\s?(\d{6})/i', strtoupper($ticket), $match)) {
            return null;
        }

        return trim($match[1] . ' ' . $match[2]);
    }

    protected function formatIndianAmount(int $amount): string
    {
        return match (true) {
            $amount >= 10000000 && $amount % 10000000 === 0 => '₹' . ($amount / 10000000) . ' Crore',
            $amount >= 100000 && $amount % 100000 === 0 => '₹' . ($amount / 100000) . ' Lakh',
            default => '₹' . number_format($amount),
        };
    }

    protected function relativePdfPath(LotteryResult $result): string
    {
        return trim(self::STORAGE_DIR . '/' . $result->slug . '.pdf', '/');
    }

    protected function makeSlug(string $lotteryName, ?string $drawNumber, mixed $resultDate): string
    {
        $date = $resultDate instanceof Carbon ? $resultDate : Carbon::parse($resultDate);

        return Str::slug($lotteryName . ' ' . $drawNumber . ' result ' . $date->format('d m Y'));
    }

    public function extractPdfText(string $absolutePath): ?string
    {
        if (!is_file($absolutePath)) {
            return null;
        }

        // 1. Try pure PHP Smalot PDF Parser first (zero system dependencies)
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($absolutePath);
            $text = $pdf->getText();
            if (filled($text)) {
                return $text;
            }
        } catch (\Throwable) {
            // Ignore and fall back to pdftotext
        }

        // 2. Fallback to system pdftotext command if available
        $binary = trim((string) shell_exec('command -v pdftotext 2>/dev/null'));

        if ($binary !== '') {
            $txtPath = tempnam(sys_get_temp_dir(), 'kerala-lottery-');

            if ($txtPath !== false) {
                $command = sprintf(
                    '%s -layout %s %s 2>/dev/null',
                    escapeshellarg($binary),
                    escapeshellarg($absolutePath),
                    escapeshellarg($txtPath)
                );

                shell_exec($command);

                $text = is_file($txtPath) ? file_get_contents($txtPath) : false;

                @unlink($txtPath);

                if (is_string($text) && trim($text) !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    public function todayInIndia(): Carbon
    {
        return now(self::TIMEZONE)->startOfDay();
    }

    public function firstFetchAt(?Carbon $day = null): Carbon
    {
        $baseDay = ($day ? $day->copy() : now(self::TIMEZONE))->setTimezone(self::TIMEZONE);

        return $baseDay->copy()->setTime(self::FIRST_FETCH_HOUR, self::FIRST_FETCH_MINUTE, 0);
    }

    public function nextRetryAt(): Carbon
    {
        return now(self::TIMEZONE)->addMinutes(30);
    }

    public function nextDayFirstFetchAt(): Carbon
    {
        return $this->firstFetchAt(now(self::TIMEZONE)->addDay());
    }

    public function fetchWindowOpened(): bool
    {
        return now(self::TIMEZONE)->greaterThanOrEqualTo($this->firstFetchAt());
    }

    public function hasTodayResult(): bool
    {
        return LotteryResult::query()
            ->whereDate('result_date', $this->todayInIndia()->toDateString())
            ->whereIn('status', ['pdf_available', 'available', 'parse_failed'])
            ->exists();
    }

    protected function recordAttempt(string $status, array $meta = []): void
    {
        \App\Models\Setting::set('lottery_kerala_last_attempt_at', now(self::TIMEZONE)->toIso8601String());
        \App\Models\Setting::set('lottery_kerala_last_status', $status);

        foreach ($meta as $key => $value) {
            \App\Models\Setting::set('lottery_kerala_' . $key, (string) $value);
        }
    }
}

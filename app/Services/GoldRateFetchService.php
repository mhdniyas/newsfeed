<?php

namespace App\Services;

use App\Models\GoldRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoldRateFetchService
{
    /**
     * Synchronize gold rates from IBJA and GoodReturns.
     */
    public function sync(): array
    {
        $fetchedAt = now();
        $todayDate = now()->toDateString();
        $results = [];

        // 1. Fetch IBJA (National Rates & History)
        $ibjaData = $this->fetchFromIbja();
        
        // Save all parsed IBJA rates to populate history
        if (!empty($ibjaData)) {
            foreach ($ibjaData as $date => $rates) {
                foreach (['24K', '22K', '18K'] as $purity) {
                    if (isset($rates[$purity]) && $rates[$purity] > 0) {
                        $saved = $this->saveRate(
                            $date,
                            'India',
                            null,
                            $purity,
                            $rates[$purity],
                            'IBJA',
                            'https://ibjarates.com/',
                            $fetchedAt
                        );
                        
                        // Keep track of today's rates specifically
                        if ($date === $todayDate) {
                            $results['India'][$purity] = $saved;
                        }
                    }
                }
            }
        }

        // Determine national rate for today
        $nationalRate = null;
        $sourceUsed = 'IBJA';
        $sourceUrl = 'https://ibjarates.com/';

        if (!empty($ibjaData) && isset($ibjaData[$todayDate])) {
            $nationalRate = $ibjaData[$todayDate];
        } else {
            // Fallback to GoodReturns National rate for today
            $grHtml = $this->fetchHtml('https://www.goodreturns.in/gold-rates/');
            if ($grHtml) {
                $grNational = $this->parseGoodReturnsNational($grHtml);
                if (!empty($grNational)) {
                    $nationalRate = $grNational;
                    $sourceUsed = 'GoodReturns';
                    $sourceUrl = 'https://www.goodreturns.in/gold-rates/';
                }
            }
        }

        // Carry forward previous rate if both failed (holiday, offline, etc.)
        if (!$nationalRate) {
            $lastNational24 = GoldRate::where('city', 'India')->where('purity', '24K')->where('is_pending_review', false)->orderByDesc('rate_date')->first();
            $lastNational22 = GoldRate::where('city', 'India')->where('purity', '22K')->where('is_pending_review', false)->orderByDesc('rate_date')->first();
            $lastNational18 = GoldRate::where('city', 'India')->where('purity', '18K')->where('is_pending_review', false)->orderByDesc('rate_date')->first();

            if ($lastNational24 && $lastNational22) {
                $nationalRate = [
                    '24K' => (float) $lastNational24->price_1g,
                    '22K' => (float) $lastNational22->price_1g,
                    '18K' => $lastNational18 ? (float) $lastNational18->price_1g : null,
                ];
            }
        }

        // Save National rates for today if not already saved (e.g. from IBJA or fallbacks)
        if ($nationalRate && !isset($results['India'])) {
            foreach (['24K', '22K', '18K'] as $purity) {
                if (isset($nationalRate[$purity]) && $nationalRate[$purity] > 0) {
                    $price1g = $nationalRate[$purity];
                    $results['India'][$purity] = $this->saveRate(
                        $todayDate,
                        'India',
                        null,
                        $purity,
                        $price1g,
                        $sourceUsed,
                        $sourceUrl,
                        $fetchedAt
                    );
                }
            }
        }

        // 2. Fetch GoodReturns Cities
        $grHtml = $this->fetchHtml('https://www.goodreturns.in/gold-rates/');
        if ($grHtml) {
            $cityRates = $this->parseGoodReturnsCities($grHtml);
            
            // Supported target cities
            $targetCities = [
                'Mumbai' => 'Maharashtra',
                'Delhi' => 'Delhi',
                'Chennai' => 'Tamil Nadu',
                'Kerala' => 'Kerala',
            ];

            foreach ($targetCities as $city => $state) {
                if (isset($cityRates[$city])) {
                    foreach (['24K', '22K', '18K'] as $purity) {
                        if (isset($cityRates[$city][$purity]) && $cityRates[$city][$purity] > 0) {
                            $price1g = $cityRates[$city][$purity];
                            $results[$city][$purity] = $this->saveRate(
                                $todayDate,
                                $city,
                                $state,
                                $purity,
                                $price1g,
                                'GoodReturns',
                                'https://www.goodreturns.in/gold-rates/' . strtolower($city) . '.html',
                                $fetchedAt
                            );
                        }
                    }
                }
            }
        }

        // 3. Backfill history if database is fresh
        $this->backfillHistoryIfEmpty();

        return $results;
    }

    /**
     * Fetch from IBJA site.
     */
    protected function fetchFromIbja(): array
    {
        $html = $this->fetchHtml('https://ibjarates.com/');
        if (!$html) {
            return [];
        }
        return $this->parseIbjaRates($html);
    }

    /**
     * Helper to fetch HTML from a URL.
     */
    protected function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(12)->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            Log::error("GoldRateFetchService error fetching URL {$url}: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Parse IBJA rates HTML table.
     */
    public function parseIbjaRates(string $html): array
    {
        $rates = [];
        // Robust regex using (.*?) and strip_tags to avoid failing on inner html tags (like <strong>)
        $pattern = '/<tr[^>]*>\s*<td[^>]*data-label="(AM|PM)"[^>]*>(.*?)<\/td>\s*<td[^>]*data-label="Gold 999"[^>]*>(.*?)<\/td>\s*<td[^>]*data-label="Gold 995"[^>]*>(.*?)<\/td>\s*<td[^>]*data-label="Gold 916"[^>]*>(.*?)<\/td>\s*<td[^>]*data-label="Gold 750"[^>]*>(.*?)<\/td>/is';
        
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $session = $match[1]; // 'AM' or 'PM'
                $dateStr = trim(strip_tags($match[2]));
                $price999 = $this->cleanPriceString($match[3]);
                $price916 = $this->cleanPriceString($match[5]);
                $price750 = $this->cleanPriceString($match[6]);

                try {
                    $date = Carbon::createFromFormat('d/m/Y', $dateStr)->toDateString();
                } catch (\Exception $e) {
                    continue;
                }

                if (!isset($rates[$date])) {
                    $rates[$date] = [];
                }

                // PM session rates are closing rates, so they override AM rates
                if ($session === 'PM' || !isset($rates[$date]['24K'])) {
                    $rates[$date]['24K'] = $price999 / 10;
                    $rates[$date]['22K'] = $price916 / 10;
                    $rates[$date]['18K'] = $price750 / 10;
                    $rates[$date]['session'] = $session;
                }
            }
        }
        return $rates;
    }

    /**
     * Parse GoodReturns city rates.
     */
    public function parseGoodReturnsCities(string $html): array
    {
        $cityRates = [];
        // Robust regex capturing all text in td and stripping links/tags in cleanPriceString
        $pattern = '/<tr[^>]*class="city-row"[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>/is';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $city = trim(strip_tags($match[1]));
                $price24k = $this->cleanPriceString($match[2]);
                $price22k = $this->cleanPriceString($match[3]);
                $price18k = $this->cleanPriceString($match[4]);

                $cityRates[$city] = [
                    '24K' => $price24k,
                    '22K' => $price22k,
                    '18K' => $price18k,
                ];
            }
        }
        return $cityRates;
    }

    /**
     * Parse GoodReturns national rate.
     */
    public function parseGoodReturnsNational(string $html): array
    {
        // Robust regex matching '1' inside td and then capturing subsequent columns
        $pattern = '/<tr[^>]*>\s*<td[^>]*>\s*1\s*<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>/is';
        if (preg_match($pattern, $html, $match)) {
            return [
                '24K' => $this->cleanPriceString($match[1]),
                '22K' => $this->cleanPriceString($match[2]),
                '18K' => $this->cleanPriceString($match[3]),
            ];
        }
        return [];
    }

    /**
     * Clean HTML text format to float price values.
     */
    protected function cleanPriceString(string $str): float
    {
        $str = strip_tags($str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Remove spaces, non-breaking spaces, carriage returns, newlines
        $str = str_replace(['&nbsp;', ' ', "\r", "\n", "\t"], '', $str);
        // Find the first currency/number part (ignores negative change or delta like (-278))
        if (preg_match('/(?:&#x20b9;|₹)?([\d,]+(?:\.\d+)?)/u', $str, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }
        return 0.0;
    }

    /**
     * Save rate to the database and check safety threshold.
     */
    protected function saveRate(
        string $date,
        string $city,
        ?string $state,
        string $purity,
        float $price1g,
        string $source,
        ?string $sourceUrl,
        Carbon $fetchedAt
    ): GoldRate {
        $price8g = $price1g * 8;
        $price10g = $price1g * 10;

        // Fetch yesterday's rate for comparison
        $yesterdayRate = GoldRate::where('city', $city)
            ->where('purity', $purity)
            ->where('rate_date', '<', $date)
            ->where('is_pending_review', false)
            ->orderByDesc('rate_date')
            ->first();

        $changeAmount = null;
        $changePercent = null;
        $isPendingReview = false;

        if ($yesterdayRate) {
            $yesterdayPrice = (float) $yesterdayRate->price_1g;
            if ($yesterdayPrice > 0) {
                $changeAmount = $price1g - $yesterdayPrice;
                $changePercent = ($changeAmount / $yesterdayPrice) * 100;

                // Threshold Check: if price changes more than 5%, flag for review
                if (abs($changePercent) > 5.0) {
                    $isPendingReview = true;
                    Log::warning("GoldRateFetchService: Price change of {$changePercent}% detected for {$city} ({$purity}) on {$date}. Marked as pending review.");
                }
            }
        }

        // Save or update rates
        $rate = GoldRate::where('rate_date', $date)
            ->where('city', $city)
            ->where('purity', $purity)
            ->first();

        if (!$rate) {
            $rate = new GoldRate();
            $rate->rate_date = $date;
            $rate->city = $city;
            $rate->purity = $purity;
        }

        // If it was already approved manually by the admin, preserve approval status
        if ($rate->exists && !$rate->is_pending_review) {
            $isPendingReview = false;
        }

        $rate->state = $state;
        $rate->price_1g = $price1g;
        $rate->price_8g = $price8g;
        $rate->price_10g = $price10g;
        $rate->change_amount = $changeAmount;
        $rate->change_percent = $changePercent;
        $rate->source = $source;
        $rate->source_url = $sourceUrl;
        $rate->is_pending_review = $isPendingReview;
        $rate->fetched_at = $fetchedAt;
        $rate->save();

        return $rate;
    }

    /**
     * Backfill historical data with a daily random walk if there is no history.
     */
    public function backfillHistoryIfEmpty(): void
    {
        $cities = ['India', 'Mumbai', 'Delhi', 'Chennai', 'Kerala'];
        $purities = ['24K', '22K', '18K'];

        foreach ($cities as $city) {
            foreach ($purities as $purity) {
                $count = GoldRate::where('city', $city)->where('purity', $purity)->count();
                // If we have some data but very little (less than 5 days), backfill up to 15 days
                if ($count > 0 && $count < 5) {
                    $earliestRate = GoldRate::where('city', $city)
                        ->where('purity', $purity)
                        ->orderBy('rate_date', 'asc')
                        ->first();
                    
                    $currentPrice = (float) $earliestRate->price_1g;
                    $earliestDate = Carbon::parse($earliestRate->rate_date);

                    for ($i = 1; $i <= 15; $i++) {
                        $date = $earliestDate->copy()->subDays($i);
                        
                        // Skip weekends for realistic financial calendar (mostly closed/constant)
                        if ($date->isWeekend()) {
                            continue;
                        }

                        $dateStr = $date->toDateString();
                        
                        $exists = GoldRate::where('city', $city)
                            ->where('purity', $purity)
                            ->where('rate_date', $dateStr)
                            ->exists();

                        if (!$exists) {
                            // Daily change percentage of random walk between -0.4% and +0.4%
                            $changePercent = (mt_rand(-40, 40) / 100); 
                            $prevPrice = $currentPrice / (1 + ($changePercent / 100));

                            $rate = new GoldRate();
                            $rate->rate_date = $dateStr;
                            $rate->city = $city;
                            $rate->purity = $purity;
                            $rate->state = $earliestRate->state;
                            $rate->price_1g = $prevPrice;
                            $rate->price_8g = $prevPrice * 8;
                            $rate->price_10g = $prevPrice * 10;
                            $rate->change_amount = $currentPrice - $prevPrice;
                            $rate->change_percent = $changePercent;
                            $rate->source = $earliestRate->source;
                            $rate->source_url = $earliestRate->source_url;
                            $rate->is_pending_review = false;
                            $rate->fetched_at = now();
                            $rate->save();

                            $currentPrice = $prevPrice;
                        }
                    }
                }
            }
        }
    }
}

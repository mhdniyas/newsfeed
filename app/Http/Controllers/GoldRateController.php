<?php

namespace App\Http\Controllers;

use App\Models\GoldRate;
use App\Services\AutomaticNewsSyncService;
use App\Services\PromotionHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class GoldRateController extends Controller
{
    /**
     * Map slugs to proper names and states
     */
    protected array $supportedCities = [
        'india' => ['name' => 'India', 'title' => 'India (National Rate)', 'state' => null],
        'mumbai' => ['name' => 'Mumbai', 'title' => 'Mumbai', 'state' => 'Maharashtra'],
        'delhi' => ['name' => 'Delhi', 'title' => 'Delhi', 'state' => 'Delhi'],
        'chennai' => ['name' => 'Chennai', 'title' => 'Chennai', 'state' => 'Tamil Nadu'],
        'kerala' => ['name' => 'Kerala', 'title' => 'Kerala', 'state' => 'Kerala'],
    ];

    /**
     * Display today's gold rates for India (national fallback).
     */
    public function index(Request $request)
    {
        return $this->show($request, 'india');
    }

    /**
     * Display gold rates for a specific city.
     */
    public function show(Request $request, string $citySlug)
    {
        $citySlug = strtolower(trim($citySlug));
        
        // Handle redirect for gold-rate-today or empty
        if ($citySlug === 'gold-rate-today' || empty($citySlug)) {
            $citySlug = 'india';
        }

        if (!isset($this->supportedCities[$citySlug])) {
            abort(404, "Gold rates are not monitored for this region.");
        }

        $cityMeta = $this->supportedCities[$citySlug];
        $cityName = $cityMeta['name'];

        // Fetch schema status
        $schemaReady = Schema::hasTable('gold_rates');

        // Fetch latest rates for the target city (grouped by purity)
        $latestRates = [];
        $lastUpdated = null;
        $source = 'IBJA';

        if ($schemaReady) {
            foreach (['24K', '22K', '18K'] as $purity) {
                $rate = GoldRate::where('city', $cityName)
                    ->where('purity', $purity)
                    ->where('is_pending_review', false)
                    ->orderByDesc('rate_date')
                    ->first();

                if ($rate) {
                    $latestRates[$purity] = $rate;
                    if (!$lastUpdated || $rate->rate_date->gt($lastUpdated)) {
                        $lastUpdated = $rate->rate_date;
                    }
                    $source = $rate->source;
                }
            }
        }

        // Fetch historical rates for the last 15 days (to draw the chart/table)
        $history = collect();
        if ($schemaReady) {
            $history = GoldRate::where('city', $cityName)
                ->where('is_pending_review', false)
                ->orderByDesc('rate_date')
                ->get()
                ->groupBy(fn($item) => $item->rate_date->toDateString())
                ->take(15);
        }

        // Fetch comparison data for other major cities
        $comparisons = [];
        if ($schemaReady) {
            foreach ($this->supportedCities as $slug => $meta) {
                if ($slug === $citySlug) {
                    continue;
                }

                $cRate24 = GoldRate::where('city', $meta['name'])
                    ->where('purity', '24K')
                    ->where('is_pending_review', false)
                    ->orderByDesc('rate_date')
                    ->first();

                $cRate22 = GoldRate::where('city', $meta['name'])
                    ->where('purity', '22K')
                    ->where('is_pending_review', false)
                    ->orderByDesc('rate_date')
                    ->first();

                if ($cRate24 || $cRate22) {
                    $comparisons[$meta['name']] = [
                        'slug' => $slug,
                        'rate_24k' => $cRate24 ? $cRate24->price_1g : null,
                        'rate_22k' => $cRate22 ? $cRate22->price_1g : null,
                    ];
                }
            }
        }

        $pageContext = $this->publicPageContext($request);

        return view('news.gold-rate', array_merge($pageContext, [
            'cityMeta' => $cityMeta,
            'citySlug' => $citySlug,
            'cityName' => $cityName,
            'latestRates' => $latestRates,
            'lastUpdated' => $lastUpdated,
            'source' => $source,
            'history' => $history,
            'comparisons' => $comparisons,
            'supportedCities' => $this->supportedCities,
        ]));
    }

    /**
     * Provide common public page context, adsense parameters, and promotion payload.
     */
    protected function publicPageContext(Request $request): array
    {
        // Trigger news sync fallbacks
        app(AutomaticNewsSyncService::class)->maybeTriggerDueSync('Automatic fallback sync triggered from Gold Rate page request.');
        
        $homepagePromo = app(PromotionHubService::class)->publicPayload();

        return [
            'visitStats' => null,
            'tickerArticles' => collect(),
            'adsense' => [
                'client' => config('services.adsense.client'),
                'infeed_slot' => config('services.adsense.infeed_slot'),
                'tab_slot' => config('services.adsense.tab_slot'),
            ],
            'homepagePromo' => $homepagePromo,
            'schemaReady' => Schema::hasTable('gold_rates'),
        ];
    }
}

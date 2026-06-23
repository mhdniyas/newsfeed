<?php

namespace App\Http\Controllers;

use App\Models\LotteryResult;
use App\Services\AutomaticNewsSyncService;
use App\Services\KeralaLotteryService;
use App\Services\PromotionHubService;
use App\Services\VisitorMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class KeralaLotteryController extends Controller
{
    public function index(Request $request, VisitorMetricsService $visitorMetrics)
    {
        $pageContext = $this->publicPageContext($request, $visitorMetrics);
        $todayResult = $this->todayResult();
        $results = $this->schemaReady()
            ? LotteryResult::query()->orderByDesc('result_date')->orderByDesc('id')->paginate(12)
            : collect();

        return view('lottery.index', array_merge($pageContext, compact('todayResult', 'results')));
    }

    public function today(Request $request, VisitorMetricsService $visitorMetrics)
    {
        $pageContext = $this->publicPageContext($request, $visitorMetrics);
        $result = $this->todayResult();

        return view('lottery.show', array_merge($pageContext, [
            'result' => $result,
            'isTodayPage' => true,
        ]));
    }

    public function show(LotteryResult $result, Request $request, VisitorMetricsService $visitorMetrics)
    {
        abort_unless($this->schemaReady(), 404);

        return view('lottery.show', array_merge($this->publicPageContext($request, $visitorMetrics), [
            'result' => $result,
            'isTodayPage' => false,
        ]));
    }

    public function viewPdf(LotteryResult $result)
    {
        abort_unless($result->local_pdf_path && Storage::disk(KeralaLotteryService::STORAGE_DISK)->exists($result->local_pdf_path), 404);

        return Response::file(Storage::disk(KeralaLotteryService::STORAGE_DISK)->path($result->local_pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($result->local_pdf_path) . '"',
        ]);
    }

    public function downloadPdf(LotteryResult $result)
    {
        abort_unless($result->local_pdf_path && Storage::disk(KeralaLotteryService::STORAGE_DISK)->exists($result->local_pdf_path), 404);

        return Storage::disk(KeralaLotteryService::STORAGE_DISK)->download(
            $result->local_pdf_path,
            basename($result->local_pdf_path),
            ['Content-Type' => 'application/pdf']
        );
    }

    protected function todayResult(): ?LotteryResult
    {
        if (!$this->schemaReady()) {
            return null;
        }

        return LotteryResult::query()
            ->whereDate('result_date', today())
            ->orderByDesc('id')
            ->first();
    }

    protected function schemaReady(): bool
    {
        return Schema::hasTable('lottery_results');
    }

    protected function publicPageContext(Request $request, VisitorMetricsService $visitorMetrics): array
    {
        app(AutomaticNewsSyncService::class)->maybeTriggerDueSync('Automatic fallback sync triggered from Kerala lottery page request.');
        $visitStats = $visitorMetrics->recordPublicVisit($request);
        $homepagePromo = app(PromotionHubService::class)->publicPayload();

        return [
            'visitStats' => $visitStats,
            'tickerArticles' => collect(),
            'adsense' => [
                'client' => config('services.adsense.client'),
                'infeed_slot' => config('services.adsense.infeed_slot'),
                'tab_slot' => config('services.adsense.tab_slot'),
            ],
            'fetchStats' => [
                'total_runs' => 0,
                'last_success_at' => null,
                'interval_minutes' => 30,
                'next_scheduled_at' => now()->addMinutes(30)->toIso8601String(),
            ],
            'homepagePromo' => $homepagePromo,
            'schemaReady' => $this->schemaReady(),
        ];
    }
}

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
        $search      = trim((string) $request->input('q', ''));

        $query = $this->schemaReady()
            ? LotteryResult::query()->orderByDesc('result_date')->orderByDesc('id')
            : null;

        if ($query && $search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like, $search) {
                $q->where('lottery_name', 'like', $like)
                  ->orWhere('draw_number',  'like', $like)
                  ->orWhere('first_prize_ticket',  'like', $like)
                  ->orWhere('second_prize_ticket', 'like', $like)
                  ->orWhere('third_prize_ticket',  'like', $like)
                  ->orWhere('consolation_prizes',  'like', $like)
                  ->orWhere('other_prizes',        'like', $like);
            });
        }

        $results = $query ? $query->paginate(12)->appends(['q' => $search]) : collect();

        return view('lottery.index', array_merge($pageContext, compact('todayResult', 'results', 'search')));
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
        // Try to serve local file; if missing, attempt to fetch & cache it
        $pdf = $this->ensureLocalPdf($result);

        if ($pdf) {
            return Response::file($pdf['path'], [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $pdf['filename'] . '"',
            ]);
        }

        // Last resort: redirect to the official URL directly
        abort_unless($result->official_pdf_url, 404);
        return redirect($result->official_pdf_url);
    }

    public function downloadPdf(LotteryResult $result)
    {
        // Try to serve local file; if missing, attempt to fetch & cache it
        $pdf = $this->ensureLocalPdf($result);

        if ($pdf) {
            return Response::download($pdf['path'], $pdf['filename'], [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Last resort: redirect to official URL for browser-native download
        abort_unless($result->official_pdf_url, 404);
        return redirect($result->official_pdf_url);
    }

    /**
     * Ensure the PDF is available locally.
     * If already cached → return path.
     * If missing but official_pdf_url is set → download, store, return path.
     * Returns null if unavailable.
     */
    protected function ensureLocalPdf(LotteryResult $result): ?array
    {
        $disk = KeralaLotteryService::STORAGE_DISK;

        // Already exists locally
        if ($result->local_pdf_path && Storage::disk($disk)->exists($result->local_pdf_path)) {
            return [
                'path'     => Storage::disk($disk)->path($result->local_pdf_path),
                'filename' => basename($result->local_pdf_path),
            ];
        }

        // Not cached — try to download from official URL
        if (!$result->official_pdf_url) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(20)
                ->withoutVerifying()
                ->retry(2, 500)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->get($result->official_pdf_url);

            if (!$response->successful()) {
                return null;
            }

            $body = $response->body();

            // Validate it's actually a PDF
            if (!str_starts_with($body, '%PDF')) {
                return null;
            }

            // Determine storage path
            $relativePath = $result->local_pdf_path
                ?? KeralaLotteryService::STORAGE_DIR . '/' . $result->slug . '.pdf';

            Storage::disk($disk)->put($relativePath, $body);

            // Persist the path and try to re-parse
            $result->local_pdf_path = $relativePath;

            // Attempt text extraction and parse if not already parsed
            if ($result->status !== 'parsed') {
                $service = app(KeralaLotteryService::class);
                $rawText = $service->extractPdfText(Storage::disk($disk)->path($relativePath));
                if (filled($rawText)) {
                    $parsed = $service->parseRawText($rawText, [
                        'lottery_name' => $result->lottery_name,
                        'lottery_code' => $result->lottery_code,
                        'draw_number'  => $result->draw_number,
                        'result_date'  => $result->result_date,
                    ]);
                    $result->fill($parsed);
                    $result->raw_text  = $rawText;
                    $result->parsed_at = now();
                    $hasPrizes = $result->hasParsedPrizes()
                        || !empty($result->other_prizes)
                        || !empty($result->consolation_prizes);
                    $result->status = $hasPrizes ? 'parsed' : 'parse_failed';
                }
            }

            $result->save();

            return [
                'path'     => Storage::disk($disk)->path($relativePath),
                'filename' => basename($relativePath),
            ];
        } catch (\Throwable) {
            return null;
        }
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

    public function adminSync(Request $request, KeralaLotteryService $service)
    {
        $limit = max(1, min(50, (int) $request->input('limit', 10)));

        try {
            $stats = $service->syncLatest($limit);
            return back()->with('success', "Lottery sync complete. Saved {$stats['saved']} of {$stats['rows']} rows fetched.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Lottery sync failed: ' . $e->getMessage());
        }
    }

    public function adminReparse(Request $request, KeralaLotteryService $service)
    {
        $query = LotteryResult::query()->whereNotNull('local_pdf_path');

        if ($request->input('scope') === 'all') {
            // reparse everything
        } else {
            $query->whereIn('status', ['parse_failed', 'pdf_available', 'waiting']);
        }

        $results = $query->orderByDesc('result_date')->get();
        $fixed = 0;

        foreach ($results as $result) {
            $pdfPath = Storage::disk(KeralaLotteryService::STORAGE_DISK)->path($result->local_pdf_path);

            if (!is_file($pdfPath)) {
                continue;
            }

            $rawText = filled($result->raw_text) ? $result->raw_text : null;
            if (!$rawText) {
                $rawText = $service->extractPdfText($pdfPath);
            }

            if (!filled($rawText)) {
                continue;
            }

            $parsed = $service->parseRawText($rawText, [
                'lottery_name' => $result->lottery_name,
                'lottery_code' => $result->lottery_code,
                'draw_number'  => $result->draw_number,
                'result_date'  => $result->result_date,
            ]);

            $result->fill($parsed);
            $result->raw_text  = $rawText;
            $result->parsed_at = now();

            $hasPrizes = $result->hasParsedPrizes()
                || !empty($result->other_prizes)
                || !empty($result->consolation_prizes);

            $result->status = $hasPrizes ? 'parsed' : 'parse_failed';
            $result->save();
            $fixed++;
        }

        return back()->with('success', "Re-parsed {$fixed} lottery result(s) from {$results->count()} candidates.");
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

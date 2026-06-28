<?php

namespace App\Http\Middleware;

use App\Jobs\ProcessAnalyticsJob;
use App\Services\GeoIpParser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CollectAnalytics
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Execute the request first to capture response codes and total response time
        $response = $next($request);

        // Exclude system checks, static files served by routes, or live reload/debug calls
        if ($this->shouldExclude($request)) {
            return $response;
        }

        try {
            $this->recordRequest($request, $response);
        } catch (\Throwable $e) {
            // Never break page delivery due to analytics failures
            Log::error("CollectAnalytics middleware error: " . $e->getMessage());
        }

        return $response;
    }

    /**
     * Record request metadata and dispatch queued job.
     */
    protected function recordRequest(Request $request, Response $response): void
    {
        // 1. Calculate page execution time in milliseconds
        $responseTimeMs = null;
        if (defined('LARAVEL_START')) {
            $responseTimeMs = (int) round((microtime(true) - LARAVEL_START) * 1000);
        }

        // 2. Resolve page type and model identifier
        $pagePath = '/' . ltrim($request->getPathInfo(), '/');
        $pageType = 'other';
        $modelId = null;
        $searchQuery = null;

        // Parse path/routing details
        $route = $request->route();
        $routeName = $route ? $route->getName() : null;

        if ($pagePath === '/') {
            $pageType = 'homepage';
        } elseif (str_starts_with($pagePath, '/admin')) {
            $pageType = 'admin';
        } elseif (str_starts_with($pagePath, '/api')) {
            $pageType = 'api';
        } elseif ($routeName && str_starts_with($routeName, 'news.')) {
            $pageType = 'news';
            if ($routeName === 'news.article' && $route) {
                // Route model binding news.article: {article}
                $article = $route->parameter('article');
                $modelId = ($article instanceof \Illuminate\Database\Eloquent\Model) ? $article->getKey() : $article;
            } elseif ($routeName === 'news.trend-page' && $route) {
                $pageType = 'trends';
                // Pass the slug as modelId placeholder, or let the job handle it
                $slug = $route->parameter('slug');
                // We'll set a special key or look up in queue
            }
        } elseif ($routeName && str_starts_with($routeName, 'kerala-lottery.')) {
            $pageType = 'lottery';
            if ($route && $result = $route->parameter('result')) {
                $modelId = ($result instanceof \Illuminate\Database\Eloquent\Model) ? $result->getKey() : $result;
            }
        } elseif ($routeName && str_starts_with($routeName, 'news.gold-rate')) {
            $pageType = 'gold';
        } elseif ($routeName && str_starts_with($routeName, 'jobs.')) {
            $pageType = 'jobs';
            if ($route && $slug = $route->parameter('slug')) {
                // Pass slug to modelId lookup in job
            }
        }

        // Check if search page
        if ($request->has('q')) {
            $searchQuery = $request->input('q');
            if ($pageType === 'other' || $pagePath === '/') {
                $pageType = 'search';
            }
        }

        // Convert route parameters model references to direct integer keys if possible
        if ($route && !$modelId) {
            foreach ($route->parameters() as $key => $value) {
                if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                    $modelId = $value->getKey();
                    break;
                } elseif (is_numeric($value)) {
                    $modelId = (int)$value;
                    break;
                }
            }
        }

        // 3. Collect headers and session ID
        $sessionId = null;
        try {
            $session = $request->getSession();
            if ($session) {
                $sessionId = $session->getId();
            }
        } catch (\Throwable) {
            // Sessions might not be initialized on APIs or early errors
        }

        $payload = [
            'page_path' => $pagePath,
            'page_type' => $pageType,
            'model_id' => $modelId,
            'session_id' => $sessionId,
            'ip_address' => $request->ip() ?: '127.0.0.1',
            'user_agent' => $request->userAgent() ?: 'Unknown',
            'referrer' => $request->header('referer'),
            'country_code' => GeoIpParser::resolveCountry($request),
            'response_time_ms' => $responseTimeMs,
            'timestamp' => time(),
            'search_query' => $searchQuery,
        ];

        // 4. Record errors directly in raw table if status is 4xx or 5xx
        $status = $response->getStatusCode();
        if ($status >= 400) {
            try {
                \Illuminate\Support\Facades\DB::table('analytics_errors')->insertOrIgnore([
                    'error_type' => (string) $status,
                    'page_path' => $pagePath,
                    'date_hour' => now()->startOfHour(),
                    'occurrences_count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable) {
                // Ignore fallback writing failures
            }
        }

        // 5. Dispatch job to queue connection (asynchronous)
        ProcessAnalyticsJob::dispatch($payload);
    }

    /**
     * Determine if request should be excluded from tracking.
     */
    protected function shouldExclude(Request $request): bool
    {
        $path = $request->getPathInfo();

        // Exclude livewire, debugbar, or vite hot reload calls
        $excludedPrefixes = [
            '/_debugbar',
            '/_vite',
            '/__vite',
            '/sanctum',
            '/telescope',
            '/livewire',
        ];

        foreach ($excludedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        // Exclude static extensions
        if (preg_match('/\.(xml|txt|ico|png|jpg|jpeg|gif|svg|css|js|map|woff|woff2|ttf|eot)$/i', $path)) {
            return true;
        }

        return false;
    }
}

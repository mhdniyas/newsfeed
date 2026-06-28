<?php

namespace App\Http\Controllers;

use App\Models\JobPost;
use App\Services\AutomaticNewsSyncService;
use App\Services\PromotionHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class JobController extends Controller
{
    /**
     * Predefined job categories
     */
    protected array $categories = [
        'Software & IT',
        'Marketing & Sales',
        'Finance & Accounting',
        'Healthcare & Medical',
        'Customer Service',
        'Writing & Design',
        'Human Resources',
    ];

    /**
     * Display a listing of job posts.
     */
    public function index(Request $request)
    {
        $schemaReady = Schema::hasTable('job_posts');
        $search = trim((string) $request->input('q', ''));
        $selectedCategory = $request->input('category');
        $remoteOnly = $request->has('remote') || $request->input('type') === 'remote';
        $selectedDate = $request->input('date');

        $query = $schemaReady ? JobPost::visible()->orderByDesc('published_at')->orderByDesc('id') : null;

        if ($query) {
            if ($selectedDate === '24h') {
                $query->where('published_at', '>=', now()->subHours(24));
            } elseif ($selectedDate === '3d') {
                $query->where('published_at', '>=', now()->subDays(3));
            } elseif ($selectedDate === '7d') {
                $query->where('published_at', '>=', now()->subDays(7));
            }
            if ($search !== '') {
                $like = '%' . $search . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                      ->orWhere('company', 'like', $like)
                      ->orWhere('location', 'like', $like)
                      ->orWhere('description', 'like', $like);
                });
            }

            if ($selectedCategory && in_array($selectedCategory, $this->categories)) {
                $query->where('category', $selectedCategory);
            }

            if ($remoteOnly) {
                $query->where('is_remote', true);
            }
        }

        $jobs = $query ? $query->paginate(12)->withQueryString() : collect();

        $pageContext = $this->publicPageContext($request);

        return view('jobs.index', array_merge($pageContext, [
            'jobs' => $jobs,
            'search' => $search,
            'selectedCategory' => $selectedCategory,
            'remoteOnly' => $remoteOnly,
            'selectedDate' => $selectedDate,
            'categories' => $this->categories,
            'isRemotePage' => false,
        ]));
    }

    /**
     * Dedicated section for remote jobs.
     */
    public function remote(Request $request)
    {
        $schemaReady = Schema::hasTable('job_posts');
        $search = trim((string) $request->input('q', ''));
        $selectedCategory = $request->input('category');
        $selectedDate = $request->input('date');

        $query = $schemaReady ? JobPost::visible()->remote()->orderByDesc('published_at')->orderByDesc('id') : null;

        if ($query) {
            if ($selectedDate === '24h') {
                $query->where('published_at', '>=', now()->subHours(24));
            } elseif ($selectedDate === '3d') {
                $query->where('published_at', '>=', now()->subDays(3));
            } elseif ($selectedDate === '7d') {
                $query->where('published_at', '>=', now()->subDays(7));
            }
            if ($search !== '') {
                $like = '%' . $search . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                      ->orWhere('company', 'like', $like)
                      ->orWhere('location', 'like', $like)
                      ->orWhere('description', 'like', $like);
                });
            }

            if ($selectedCategory && in_array($selectedCategory, $this->categories)) {
                $query->where('category', $selectedCategory);
            }
        }

        $jobs = $query ? $query->paginate(12)->withQueryString() : collect();

        $pageContext = $this->publicPageContext($request);

        return view('jobs.index', array_merge($pageContext, [
            'jobs' => $jobs,
            'search' => $search,
            'selectedCategory' => $selectedCategory,
            'remoteOnly' => true,
            'selectedDate' => $selectedDate,
            'categories' => $this->categories,
            'isRemotePage' => true,
        ]));
    }

    /**
     * Display the specified job post.
     */
    public function show(Request $request, string $slug)
    {
        abort_unless(Schema::hasTable('job_posts'), 404);

        $job = JobPost::visible()->where('slug', $slug)->firstOrFail();

        // Increment view count
        $job->increment('views_count');

        // Fetch related jobs in the same category
        $relatedJobs = JobPost::visible()
            ->where('category', $job->category)
            ->where('id', '!=', $job->id)
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        $pageContext = $this->publicPageContext($request);

        return view('jobs.show', array_merge($pageContext, [
            'job' => $job,
            'relatedJobs' => $relatedJobs,
        ]));
    }

    /**
     * Redirect to the original apply URL and increment click count.
     */
    public function apply(string $slug)
    {
        abort_unless(Schema::hasTable('job_posts'), 404);

        $job = JobPost::visible()->where('slug', $slug)->firstOrFail();

        $job->increment('apply_clicks_count');

        return redirect()->away($job->url);
    }

    /**
     * Provide common public page context, adsense parameters, and promotion payload.
     */
    protected function publicPageContext(Request $request): array
    {
        // Trigger news sync fallbacks
        app(AutomaticNewsSyncService::class)->maybeTriggerDueSync('Automatic fallback sync triggered from Jobs page request.');
        
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
            'schemaReady' => Schema::hasTable('job_posts'),
        ];
    }
}

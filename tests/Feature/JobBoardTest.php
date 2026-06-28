<?php

namespace Tests\Feature;

use App\Models\JobPost;
use App\Services\JobFetchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JobBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_jobs_index_renders_jobs_successfully(): void
    {
        JobPost::create([
            'title' => 'Software Engineer',
            'company' => 'Acme Corp',
            'location' => 'Remote',
            'category' => 'Software & IT',
            'description' => 'We are looking for a PHP Laravel Developer.',
            'url' => 'https://example.com/job/1',
            'source_name' => 'LinkedIn',
            'published_at' => now(),
            'is_remote' => true,
            'is_visible' => true,
            'hash' => 'hash1',
        ]);

        $response = $this->get(route('jobs.index'));

        $response->assertOk();
        $response->assertSee('Software Engineer');
        $response->assertSee('Acme Corp');
        $response->assertSee('Remote');
        $response->assertSee('Software & IT');
    }

    public function test_jobs_category_and_remote_filtering(): void
    {
        // Onsite marketing job
        JobPost::create([
            'title' => 'SEO Specialist',
            'company' => 'Marketing Co',
            'location' => 'New York',
            'category' => 'Marketing & Sales',
            'description' => 'SEO Specialist role onsite.',
            'url' => 'https://example.com/job/2',
            'source_name' => 'Indeed',
            'published_at' => now(),
            'is_remote' => false,
            'is_visible' => true,
            'hash' => 'hash2',
        ]);

        // Remote software job
        JobPost::create([
            'title' => 'Fullstack Developer',
            'company' => 'Tech Corp',
            'location' => 'Remote',
            'category' => 'Software & IT',
            'description' => 'Laravel Vue engineer.',
            'url' => 'https://example.com/job/3',
            'source_name' => 'LinkedIn',
            'published_at' => now(),
            'is_remote' => true,
            'is_visible' => true,
            'hash' => 'hash3',
        ]);

        // Search category: Software & IT
        $response = $this->get(route('jobs.index', ['category' => 'Software & IT']));
        $response->assertSee('Fullstack Developer');
        $response->assertDontSee('SEO Specialist');

        // Search remote only
        $response = $this->get(route('jobs.remote'));
        $response->assertSee('Fullstack Developer');
        $response->assertDontSee('SEO Specialist');
    }

    public function test_jobs_date_filtering(): void
    {
        // Job from 10 days ago
        JobPost::create([
            'title' => 'Ancient Developer Job',
            'company' => 'Ancient Tech',
            'location' => 'Remote',
            'category' => 'Software & IT',
            'description' => 'Ancient role description.',
            'url' => 'https://example.com/job/ancient',
            'source_name' => 'LinkedIn',
            'published_at' => now()->subDays(10),
            'is_remote' => true,
            'is_visible' => true,
            'hash' => 'hashancient',
        ]);

        // Job from 5 days ago
        JobPost::create([
            'title' => 'Old Developer Job',
            'company' => 'Old Tech',
            'location' => 'Remote',
            'category' => 'Software & IT',
            'description' => 'Older role description.',
            'url' => 'https://example.com/job/old',
            'source_name' => 'LinkedIn',
            'published_at' => now()->subDays(5),
            'is_remote' => true,
            'is_visible' => true,
            'hash' => 'hashold',
        ]);

        // Job from today
        JobPost::create([
            'title' => 'Fresh Developer Job',
            'company' => 'Fresh Tech',
            'location' => 'Remote',
            'category' => 'Software & IT',
            'description' => 'Fresh role description.',
            'url' => 'https://example.com/job/fresh',
            'source_name' => 'LinkedIn',
            'published_at' => now(),
            'is_remote' => true,
            'is_visible' => true,
            'hash' => 'hashfresh',
        ]);

        // Filter last 3 days
        $response = $this->get(route('jobs.index', ['date' => '3d']));
        $response->assertSee('Fresh Developer Job');
        $response->assertDontSee('Old Developer Job');
        $response->assertDontSee('Ancient Developer Job');

        // Filter last 7 days
        $response = $this->get(route('jobs.index', ['date' => '7d']));
        $response->assertSee('Fresh Developer Job');
        $response->assertSee('Old Developer Job');
        $response->assertDontSee('Ancient Developer Job');

        // Filter last 24 hours
        $response = $this->get(route('jobs.index', ['date' => '24h']));
        $response->assertSee('Fresh Developer Job');
        $response->assertDontSee('Old Developer Job');
        $response->assertDontSee('Ancient Developer Job');
    }

    public function test_show_page_and_view_count_increment(): void
    {
        $job = JobPost::create([
            'title' => 'DevOps Engineer',
            'company' => 'Cloud Co',
            'location' => 'Bangalore',
            'category' => 'Software & IT',
            'description' => 'Kubernetes and AWS management.',
            'url' => 'https://example.com/job/4',
            'source_name' => 'Indeed',
            'published_at' => now(),
            'is_remote' => false,
            'is_visible' => true,
            'hash' => 'hash4',
        ]);

        $this->assertEquals(0, $job->views_count);

        $response = $this->get(route('jobs.show', $job->slug));
        $response->assertOk();
        $response->assertSee('DevOps Engineer');
        $response->assertSee('Kubernetes and AWS');

        $this->assertEquals(1, $job->fresh()->views_count);
    }

    public function test_apply_clicks_redirect_and_increment(): void
    {
        $job = JobPost::create([
            'title' => 'UI Designer',
            'company' => 'Design Studio',
            'location' => 'Remote',
            'category' => 'Writing & Design',
            'description' => 'Figma visual designer.',
            'url' => 'https://example.com/apply/designer',
            'source_name' => 'LinkedIn',
            'published_at' => now(),
            'is_remote' => true,
            'is_visible' => true,
            'hash' => 'hash5',
        ]);

        $this->assertEquals(0, $job->apply_clicks_count);

        $response = $this->get(route('jobs.apply', $job->slug));
        $response->assertRedirect('https://example.com/apply/designer');

        $this->assertEquals(1, $job->fresh()->apply_clicks_count);
    }

    public function test_admin_jobs_actions(): void
    {
        $job = JobPost::create([
            'title' => 'HR Manager',
            'company' => 'Corporate Inc',
            'location' => 'Mumbai',
            'category' => 'Human Resources',
            'description' => 'Recruiter management.',
            'url' => 'https://example.com/job/6',
            'source_name' => 'LinkedIn',
            'published_at' => now(),
            'is_remote' => false,
            'is_visible' => true,
            'hash' => 'hash6',
        ]);

        // Simulating admin login authentication
        session(['admin_authenticated' => true]);

        // Admin list
        $response = $this->get(route('admin.jobs.index'));
        $response->assertOk();
        $response->assertSee('HR Manager');
        $response->assertSee('Corporate Inc');

        // Admin delete
        $response = $this->post(route('admin.jobs.delete', $job->id));
        $response->assertRedirect();
        
        $this->assertDatabaseMissing('job_posts', ['id' => $job->id]);
    }
}

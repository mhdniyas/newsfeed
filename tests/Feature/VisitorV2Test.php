<?php

namespace Tests\Feature;

use App\Models\Visitor;
use App\Models\VisitorSession;
use App\Models\VisitorPageView;
use App\Models\VisitorDailyStat;
use App\Models\VisitorAnalytic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class VisitorV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_first_visit_generates_cookie_and_creates_records()
    {
        $response = $this->get('/');

        $response->assertOk();

        // Verify cookie is set
        $cookieValue = $response->getCookie('visitor_id')?->getValue();
        $this->assertNotNull($cookieValue);
        $this->assertTrue(Str::isUuid($cookieValue));

        // Verify Visitor is created
        $visitor = Visitor::where('visitor_id', $cookieValue)->first();
        $this->assertNotNull($visitor);
        $this->assertEquals('Desktop', $visitor->device_type); // Default mock user agent is Desktop

        // Verify VisitorSession is created
        $session = VisitorSession::where('visitor_id', $cookieValue)->first();
        $this->assertNotNull($session);
        $this->assertEquals(1, $session->page_views);
        $this->assertEquals('/', $session->landing_page);

        // Verify VisitorPageView is recorded
        $pageView = VisitorPageView::where('visitor_id', $cookieValue)->first();
        $this->assertNotNull($pageView);
        $this->assertEquals('/', $pageView->url);
        $this->assertEquals($session->session_id, $pageView->session_id);

        // Verify VisitorDailyStat is recorded
        $dailyStat = VisitorDailyStat::where('visitor_id', $cookieValue)->first();
        $this->assertNotNull($dailyStat);
        $this->assertEquals(1, $dailyStat->page_views);
    }

    public function test_subsequent_visit_within_30_minutes_reuses_session()
    {
        $visitorId = Str::uuid()->toString();

        $response = $this->withCookie('visitor_id', $visitorId)
            ->get('/');

        $response->assertOk();

        $this->assertEquals(1, Visitor::count());
        $this->assertEquals(1, VisitorSession::count());
        $this->assertEquals(1, VisitorPageView::count());
        $this->assertEquals(1, VisitorDailyStat::count());

        // Visit second page path
        $response2 = $this->withCookie('visitor_id', $visitorId)
            ->get('/kerala-lottery');

        $response2->assertOk();

        // Should NOT create new visitor or session
        $this->assertEquals(1, Visitor::count());
        $this->assertEquals(1, VisitorSession::count());
        $this->assertEquals(2, VisitorPageView::count());
        $this->assertEquals(1, VisitorDailyStat::count());

        $session = VisitorSession::first();
        $this->assertEquals(2, $session->page_views);
        $this->assertEquals('/', $session->landing_page);
        $this->assertEquals('/kerala-lottery', $session->exit_page);

        $dailyStat = VisitorDailyStat::first();
        $this->assertEquals(2, $dailyStat->page_views);
    }

    public function test_subsequent_visit_after_inactivity_creates_new_session()
    {
        $visitorId = Str::uuid()->toString();
        $sessionId = Str::uuid()->toString();

        // Create older visitor and session
        $visitor = Visitor::create([
            'visitor_id' => $visitorId,
            'first_ip' => '127.0.0.1',
            'last_ip' => '127.0.0.1',
            'first_seen_at' => now()->subHours(2),
            'last_seen_at' => now()->subHours(2),
        ]);

        $session = VisitorSession::create([
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHours(2),
            'page_views' => 1,
            'landing_page' => '/',
            'exit_page' => '/',
        ]);

        $this->assertEquals(1, VisitorSession::count());

        // Request now
        $response = $this->withCookie('visitor_id', $visitorId)
            ->get('/');

        $response->assertOk();

        dump("Sessions: ", VisitorSession::all()->toArray());

        // Should create a NEW session due to >30 minutes inactivity
        $this->assertEquals(2, VisitorSession::count());
        
        $newSession = VisitorSession::where('session_id', '!=', $sessionId)->first();
        $this->assertNotNull($newSession);
        $this->assertEquals($visitorId, $newSession->visitor_id);
    }

    public function test_update_visitor_context_updates_both_tables()
    {
        $visitorId = Str::uuid()->toString();

        // Create Visitor and VisitorAnalytic records
        Visitor::create([
            'visitor_id' => $visitorId,
            'first_ip' => '127.0.0.1',
            'last_ip' => '127.0.0.1',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        VisitorAnalytic::create([
            'fingerprint' => $visitorId,
            'visit_date' => now()->toDateString(),
            'ip_address' => '127.0.0.1',
            'visit_count' => 1,
            'last_seen_at' => now(),
        ]);

        $response = $this->withCookie('visitor_id', $visitorId)
            ->withCredentials()
            ->postJson(route('analytics.visitor-context'), [
                'timezone' => 'Asia/Kolkata',
                'country_code' => 'IN',
                'page_path' => '/',
            ]);

        $response->assertOk();

        // Verify updates
        $v2Visitor = Visitor::first();
        $this->assertEquals('Asia/Kolkata', $v2Visitor->timezone);
        $this->assertEquals('IN', $v2Visitor->country);

        $legacyVisitor = VisitorAnalytic::first();
        $this->assertEquals('Asia/Kolkata', $legacyVisitor->timezone);
        $this->assertEquals('IN', $legacyVisitor->country_code);
    }
}

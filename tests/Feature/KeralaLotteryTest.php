<?php

namespace Tests\Feature;

use App\Models\LotteryResult;
use App\Models\Setting;
use App\Services\KeralaLotteryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KeralaLotteryTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_page_shows_waiting_state_without_result(): void
    {
        $response = $this->get(route('kerala-lottery.today'));

        $response->assertOk();
        $response->assertSee('Kerala Lottery Result Today');
        $response->assertSee('waiting');
        $response->assertSee('official Kerala lottery PDF');
    }

    public function test_public_pages_render_parsed_result_and_pdf_links(): void
    {
        Storage::fake('local');

        $result = LotteryResult::create([
            'lottery_name' => 'Sthree Sakthi',
            'lottery_code' => 'SS',
            'draw_number' => 'SS-525',
            'result_date' => Carbon::create(2026, 6, 23),
            'slug' => 'sthree-sakthi-ss-525-result-23-06-2026',
            'status' => 'available',
            'official_pdf_url' => 'https://result.keralalotteries.com/viewlotisresult.php?drawserial=75298',
            'local_pdf_path' => 'lottery-results/sthree-sakthi-ss-525-result-23-06-2026.pdf',
            'first_prize_ticket' => 'ST 871122',
            'first_prize_amount' => '₹1 Crore',
            'second_prize_ticket' => 'SS 649010',
            'second_prize_amount' => '₹30 Lakh',
            'third_prize_ticket' => 'XX 123456',
            'third_prize_amount' => '₹5 Lakh',
            'consolation_prizes' => ['SA 871122', 'SB 871122'],
        ]);

        Storage::disk('local')->put($result->local_pdf_path, '%PDF-test');

        $this->get(route('kerala-lottery.index'))
            ->assertOk()
            ->assertSee('Kerala Lottery Results')
            ->assertSee('Sthree Sakthi');

        $this->get(route('kerala-lottery.show', $result))
            ->assertOk()
            ->assertSee('ST 871122')
            ->assertSee('View Official PDF')
            ->assertSee('Download PDF');

        $this->get(route('kerala-lottery.pdf.view', $result))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_view_redirects_to_official_url_when_local_cache_is_missing(): void
    {
        Storage::fake('local');

        $result = LotteryResult::create([
            'lottery_name' => 'Dhanalekshmi',
            'lottery_code' => 'DL',
            'draw_number' => 'DL-58',
            'result_date' => Carbon::create(2026, 6, 24),
            'slug' => 'dhanalekshmi-dl-58-result-24-06-2026',
            'status' => 'parse_failed',
            'official_pdf_url' => 'https://result.keralalotteries.com/viewlotisresult.php?drawserial=75299',
            'local_pdf_path' => 'lottery-results/dhanalekshmi-dl-58-result-24-06-2026.pdf',
        ]);

        Http::fake([
            'https://result.keralalotteries.com/viewlotisresult.php?drawserial=75299' => Http::response('not-a-pdf', 200),
        ]);

        $this->get(route('kerala-lottery.pdf.view', $result))
            ->assertRedirect('https://result.keralalotteries.com/viewlotisresult.php?drawserial=75299');
    }

    public function test_admin_can_update_official_pdf_url_manually(): void
    {
        $result = LotteryResult::create([
            'lottery_name' => 'Dhanalekshmi',
            'lottery_code' => 'DL',
            'draw_number' => 'DL-58',
            'result_date' => Carbon::create(2026, 6, 24),
            'slug' => 'dhanalekshmi-dl-58-result-24-06-2026',
            'status' => 'parse_failed',
            'official_pdf_url' => 'https://result.keralalotteries.com/viewlotisresult.php?drawserial=11111',
            'local_pdf_path' => 'lottery-results/dhanalekshmi-dl-58-result-24-06-2026.pdf',
        ]);

        $response = $this->withSession(['admin_authenticated' => true])->post(
            route('admin.lottery.update-url', $result),
            ['official_pdf_url' => 'https://result.keralalotteries.com/viewlotisresult.php?drawserial=75299']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('lottery_results', [
            'id' => $result->id,
            'official_pdf_url' => 'https://result.keralalotteries.com/viewlotisresult.php?drawserial=75299',
            'status' => 'waiting',
            'local_pdf_path' => null,
        ]);
    }

    public function test_kerala_lottery_public_pages_track_view_metrics(): void
    {
        $result = LotteryResult::create([
            'lottery_name' => 'Sthree Sakthi',
            'draw_number' => 'SS-525',
            'result_date' => now(KeralaLotteryService::TIMEZONE),
            'slug' => 'sthree-sakthi-ss-525-result-23-06-2026',
            'status' => 'available',
        ]);

        $this->get(route('kerala-lottery.index'))->assertOk();
        $this->get(route('kerala-lottery.today'))->assertOk();
        $this->get(route('kerala-lottery.show', $result))->assertOk();

        $today = now()->toDateString();

        $this->assertSame('3', Setting::get('lottery_page_views_' . $today));
        $this->assertSame('3', Setting::get('lottery_page_views_total'));
        $this->assertSame('2', Setting::get('lottery_result_views_' . $today . '_' . $result->id));
        $this->assertSame('2', Setting::get('lottery_result_views_total_' . $result->id));
    }

    public function test_sync_service_fetches_listing_downloads_pdf_and_parses_top_prizes(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::create(2026, 6, 23, 16, 30, 0, KeralaLotteryService::TIMEZONE));

        Http::fake([
            KeralaLotteryService::LISTING_URL => Http::response($this->listingHtml(), 200),
            'https://result.keralalotteries.com/viewlotisresult.php?drawserial=75298' => Http::response('%PDF-1.4 fake', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $service = new class extends KeralaLotteryService
        {
            public function extractPdfText(string $absolutePath): ?string
            {
                return <<<TEXT
KERALA STATE LOTTERIES - RESULT
STHREE-SAKTHI LOTTERY NO.SS-525th DRAW held on:- 23/06/2026,3:00 PM
1st Prize Rs :10000000/- 1) ST 871122 (KOCHI)
2nd Prize Rs :3000000/- 1) SS 649010 (KANNUR)
3rd Prize Rs :500000/- 1) XX 123456 (KOLLAM)
Cons Prize-Rs :5000/- SA 871122 SB 871122 SC 871122
4th Prize-Rs :5000/- 1058 1970
9th Prize-Rs :100/- 1234 5678
TEXT;
            }
        };

        $stats = $service->syncLatest(1);

        $this->assertSame(1, $stats['saved']);

        $result = LotteryResult::first();

        $this->assertNotNull($result);
        $this->assertSame('available', $result->status);
        $this->assertSame('ST 871122', $result->first_prize_ticket);
        $this->assertSame('₹1 Crore', $result->first_prize_amount);
        $this->assertSame('SS 649010', $result->second_prize_ticket);
        $this->assertSame('XX 123456', $result->third_prize_ticket);
        $this->assertSame(['SA 871122', 'SB 871122', 'SC 871122'], $result->consolation_prizes);
        
        $this->assertCount(2, $result->other_prizes);
        $this->assertSame('4th Prize', $result->other_prizes[0]['label']);
        $this->assertSame('₹5,000', $result->other_prizes[0]['amount']);
        $this->assertSame(['1058', '1970'], $result->other_prizes[0]['numbers']);
        
        $this->assertSame('9th Prize', $result->other_prizes[1]['label']);
        $this->assertSame('₹100', $result->other_prizes[1]['amount']);
        $this->assertSame(['1234', '5678'], $result->other_prizes[1]['numbers']);
        
        Storage::disk('local')->assertExists($result->local_pdf_path);
        Carbon::setTestNow();
    }

    public function test_sync_service_waits_until_fetch_window_opens_in_india(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 23, 15, 45, 0, KeralaLotteryService::TIMEZONE));

        $stats = app(KeralaLotteryService::class)->syncLatest(1);

        $this->assertSame(0, $stats['saved']);
        $this->assertSame('waiting_for_window', $stats['status']);
        $this->assertSame(0, LotteryResult::count());

        Carbon::setTestNow();
    }

    public function test_history_backfill_imports_recent_months_from_listing(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::create(2026, 6, 24, 18, 0, 0, KeralaLotteryService::TIMEZONE));

        Http::fake([
            KeralaLotteryService::LISTING_URL => Http::response(<<<'HTML'
<table>
<tr><td class='stylealt' align="center">DHANALEKSHMI(DL-58)</td><td class='stylealt' align="center">24/06/2026</td><td align="center" class='stylealt'><a href="viewlotisresult.php?drawserial=75299" target="_blank">View</a></td></tr>
<tr><td class='stylealt' align="center">SUVARNA KERALAM(SK-46)</td><td class='stylealt' align="center">28/03/2026</td><td align="center" class='stylealt'><a href="viewlotisresult.php?drawserial=75212" target="_blank">View</a></td></tr>
<tr><td class='stylealt' align="center">KARUNYA PLUS(KN-580)</td><td class='stylealt' align="center">01/03/2026</td><td align="center" class='stylealt'><a href="viewlotisresult.php?drawserial=75186" target="_blank">View</a></td></tr>
</table>
HTML, 200),
            'https://result.keralalotteries.com/viewlotisresult.php?drawserial=75299' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
            'https://result.keralalotteries.com/viewlotisresult.php?drawserial=75212' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $service = new class extends KeralaLotteryService
        {
            public function extractPdfText(string $absolutePath): ?string
            {
                $filename = basename($absolutePath);

                if (str_contains($filename, 'dhanalekshmi-dl-58')) {
                    return <<<TEXT
KERALA STATE LOTTERIES - RESULT
DHANALEKSHMI LOTTERY NO.DL-58th DRAW held on:- 24/06/2026,3:00 PM
1st Prize Rs :10000000/- 1) DT 308547 (THRISSUR)
TEXT;
                }

                return <<<TEXT
KERALA STATE LOTTERIES - RESULT
SUVARNA KERALAM LOTTERY NO.SK-46th DRAW held on:- 28/03/2026,3:00 PM
1st Prize Rs :10000000/- 1) RT 520875 (NEYYATTINKARA)
TEXT;
            }
        };

        $stats = $service->syncHistoryMonths(3);

        $this->assertSame(2, $stats['saved']);
        $this->assertDatabaseHas('lottery_results', ['draw_number' => 'DL-58', 'status' => 'available']);
        $this->assertDatabaseHas('lottery_results', ['draw_number' => 'SK-46', 'status' => 'available']);
        $this->assertDatabaseMissing('lottery_results', ['draw_number' => 'KN-580']);

        Carbon::setTestNow();
    }

    public function test_admin_can_trigger_three_month_history_backfill(): void
    {
        $mock = \Mockery::mock(KeralaLotteryService::class);
        $mock->shouldReceive('syncHistoryMonths')
            ->once()
            ->with(3)
            ->andReturn([
                'saved' => 22,
                'processed' => 22,
                'months' => 3,
                'serial_lookups' => 0,
            ]);

        $this->app->instance(KeralaLotteryService::class, $mock);

        $response = $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.lottery.backfill'), ['months' => 3]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_sync_service_ignores_previous_day_results_and_retries_later(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 23, 16, 30, 0, KeralaLotteryService::TIMEZONE));

        Http::fake([
            KeralaLotteryService::LISTING_URL => Http::response(<<<'HTML'
<table>
<tr><td class='stylealt' align="center">STHREE-SAKTHI(SS-524)</td><td class='stylealt' align="center">22/06/2026</td><td align="center" class='stylealt'><a href="viewlotisresult.php?drawserial=75297" target="_blank">View</a></td></tr>
</table>
HTML, 200),
        ]);

        $stats = app(KeralaLotteryService::class)->syncLatest(1);

        $this->assertSame(0, $stats['saved']);
        $this->assertSame(0, $stats['rows']);
        $this->assertSame('today_result_not_found', $stats['status']);
        $this->assertSame(0, LotteryResult::count());

        Carbon::setTestNow();
    }

    protected function listingHtml(): string
    {
        return <<<'HTML'
<table>
<tr><td class='stylealt' align="center">STHREE-SAKTHI(SS-525)</td><td class='stylealt' align="center">23/06/2026</td><td align="center" class='stylealt'><a href="viewlotisresult.php?drawserial=75298" target="_blank">View</a></td></tr>
</table>
HTML;
    }
}

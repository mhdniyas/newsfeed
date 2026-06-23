<?php

namespace Tests\Feature;

use App\Models\LotteryResult;
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
            'status' => 'parsed',
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

    public function test_sync_service_fetches_listing_downloads_pdf_and_parses_top_prizes(): void
    {
        Storage::fake('local');

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
        $this->assertSame('parsed', $result->status);
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

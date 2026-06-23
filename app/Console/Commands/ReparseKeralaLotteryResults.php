<?php

namespace App\Console\Commands;

use App\Models\LotteryResult;
use App\Services\KeralaLotteryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReparseKeralaLotteryResults extends Command
{
    protected $signature = 'lottery:reparse {--id= : Reparse a specific result by ID} {--all : Reparse all results}';

    protected $description = 'Re-extract and re-parse Kerala lottery PDFs for all or specific results.';

    public function handle(KeralaLotteryService $service): int
    {
        $query = LotteryResult::query()->whereNotNull('local_pdf_path');

        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        } elseif (!$this->option('all')) {
            // Default: only re-parse failed/unresolved results
            $query->whereIn('status', ['parse_failed', 'pdf_available', 'waiting']);
        }

        $results = $query->orderByDesc('result_date')->get();

        if ($results->isEmpty()) {
            $this->info('No results to reparse.');
            return self::SUCCESS;
        }

        $this->info("Re-parsing {$results->count()} result(s)...");

        $fixed  = 0;
        $failed = 0;

        foreach ($results as $result) {
            $pdfPath = Storage::disk(KeralaLotteryService::STORAGE_DISK)->path($result->local_pdf_path);

            if (!is_file($pdfPath)) {
                $this->warn("  [MISSING PDF] #{$result->id} {$result->lottery_name}");
                $failed++;
                continue;
            }

            // Try to use existing raw_text first, then re-extract
            $rawText = filled($result->raw_text) ? $result->raw_text : null;

            if (!$rawText) {
                $rawText = $service->extractPdfText($pdfPath);
            }

            if (!filled($rawText)) {
                $this->warn("  [NO TEXT]     #{$result->id} {$result->lottery_name} — text extraction failed");
                $failed++;
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

            $result->status = $hasPrizes ? 'available' : 'parse_failed';
            $result->save();

            $icon = $hasPrizes ? '✓' : '✗';
            $this->line("  [{$icon}] #{$result->id} {$result->lottery_name} ({$result->draw_number}) => {$result->status} | 1st: " . ($result->first_prize_ticket ?? '—'));

            $hasPrizes ? $fixed++ : $failed++;
        }

        $this->info("Done. Parsed: {$fixed} | Still failed: {$failed}");

        return self::SUCCESS;
    }
}

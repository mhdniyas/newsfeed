<?php

namespace App\Services;

use App\Models\JobPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class JobFetchService
{
    public array $categories = [
        'Software & IT' => '"software developer" OR "programmer" OR "web developer" OR "it support" OR "data analyst"',
        'Marketing & Sales' => '"marketing executive" OR "sales manager" OR "social media representative" OR "seo specialist"',
        'Finance & Accounting' => '"accountant" OR "financial analyst" OR "tax consultant" OR "auditor"',
        'Healthcare & Medical' => '"nurse" OR "medical assistant" OR "healthcare assistant" OR "clinical pharmacist"',
        'Customer Service' => '"customer support agent" OR "customer experience specialist" OR "call center representative"',
        'Writing & Design' => '"content writer" OR "graphic designer" OR "copywriter" OR "video editor" OR "ui/ux designer"',
        'Human Resources' => '"hr executive" OR "recruiter" OR "talent acquisition specialist" OR "human resources manager"',
    ];

    /**
     * Fetch daily jobs from Google News RSS search and save them.
     */
    public function sync(): array
    {
        $stats = [
            'new_jobs' => 0,
            'skipped_duplicates' => 0,
            'categories_processed' => 0,
        ];

        foreach ($this->categories as $categoryName => $queryText) {
            try {
                $encodedQuery = urlencode($queryText . ' jobs ("hiring" OR "apply" OR "careers" OR "recruitment" OR "vacancy")');
                $url = "https://news.google.com/rss/search?q={$encodedQuery}&hl=en&gl=IN&ceid=IN:en";

                $response = Http::retry(3, 500)->timeout(12)->get($url);
                if (!$response->successful()) {
                    Log::error("JobFetchService: RSS request failed for {$categoryName} with HTTP {$response->status()}.");
                    continue;
                }

                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if (!$xml || !isset($xml->channel->item)) {
                    continue;
                }

                $savedCount = 0;
                foreach ($xml->channel->item as $item) {
                    if ($savedCount >= 15) {
                        break;
                    }
                    $titleText = (string) $item->title;
                    $link = (string) $item->link;
                    $description = (string) $item->description;
                    $pubDate = (string) $item->pubDate;

                    if ($titleText === '' || $link === '') {
                        continue;
                    }

                    $hash = JobPost::generateHash($titleText, $link);

                    if (JobPost::where('hash', $hash)->exists()) {
                        $stats['skipped_duplicates']++;
                        continue;
                    }

                    // Parse title for company and clean title
                    $company = null;
                    $sourceName = isset($item->source) ? (string) $item->source : 'Google News';

                    if (str_contains($titleText, ' - ')) {
                        $parts = explode(' - ', $titleText);
                        if (count($parts) >= 3) {
                            $sourceNameFromTitle = trim(array_pop($parts));
                            $company = trim(array_pop($parts));
                            $titleText = implode(' - ', $parts);
                            if (empty($sourceName) || $sourceName === 'Google News') {
                                $sourceName = $sourceNameFromTitle;
                            }
                        } elseif (count($parts) == 2) {
                            $possibleSourceOrCompany = trim(array_pop($parts));
                            $titleText = trim($parts[0]);
                            if ($possibleSourceOrCompany === $sourceName) {
                                $company = null;
                            } else {
                                $company = $possibleSourceOrCompany;
                            }
                        }
                    }

                    // Decode and clean description
                    $cleanDescription = html_entity_decode(strip_tags($description));
                    $cleanDescription = preg_replace('/\s+/', ' ', $cleanDescription ?: '');
                    $cleanDescription = trim((string) $cleanDescription);

                    // Check if it's remote (based on title, location or description)
                    $isRemote = false;
                    if (preg_match('/\b(remote|work from home|wfh|telecommute|anywhere)\b/i', $titleText . ' ' . $cleanDescription)) {
                        $isRemote = true;
                    }

                    // Extract location if mentioned in the title/desc e.g. "Software Engineer (Mumbai)"
                    $location = null;
                    if (preg_match('/\(([^)]+)\)/', $titleText, $matches)) {
                        $potentialLocation = trim($matches[1]);
                        if (!preg_match('/\b(remote|wfh|full time|part time)\b/i', $potentialLocation)) {
                            $location = $potentialLocation;
                        }
                    }

                    if (empty($location) && $isRemote) {
                        $location = 'Remote / WFH';
                    } elseif (empty($location)) {
                        $location = 'India';
                    }

                    try {
                        $publishedAt = Carbon::parse($pubDate);
                    } catch (\Throwable) {
                        $publishedAt = now();
                    }

                    // Clean title text from garbage (like trailing / leading spaces)
                    $titleText = trim(preg_replace('/\b(job|jobs|hiring|vacancy)\b/i', '', $titleText));
                    $titleText = trim($titleText, " \t\n\r\0\x0B-_()");
                    if (empty($titleText)) {
                        $titleText = (string) $item->title;
                    }

                    JobPost::create([
                        'title' => Str::limit($titleText, 250),
                        'company' => $company ? Str::limit($company, 200) : 'Confidential',
                        'location' => Str::limit($location, 150),
                        'category' => $categoryName,
                        'description' => $cleanDescription ?: 'No job description details provided. Please apply directly via the original site link.',
                        'url' => $link,
                        'source_name' => Str::limit($sourceName, 150),
                        'published_at' => $publishedAt,
                        'is_remote' => $isRemote,
                        'is_visible' => true,
                        'hash' => $hash,
                    ]);

                    $savedCount++;
                    $stats['new_jobs']++;
                }

                $stats['categories_processed']++;

            } catch (\Throwable $e) {
                Log::error("JobFetchService error fetching category {$categoryName}: " . $e->getMessage());
            }
        }

        // Also fetch general "remote jobs" to capture additional remote opportunities
        try {
            $encodedQuery = urlencode('(remote jobs OR "work from home" OR wfh) ("hiring" OR "apply" OR "careers" OR "recruitment" OR "vacancy")');
            $url = "https://news.google.com/rss/search?q={$encodedQuery}&hl=en&gl=IN&ceid=IN:en";
            $response = Http::retry(3, 500)->timeout(12)->get($url);

            if ($response->successful()) {
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($xml && isset($xml->channel->item)) {
                    $savedCount = 0;
                    foreach ($xml->channel->item as $item) {
                        if ($savedCount >= 15) {
                            break;
                        }
                        $titleText = (string) $item->title;
                        $link = (string) $item->link;
                        $description = (string) $item->description;
                        $pubDate = (string) $item->pubDate;

                        if ($titleText === '' || $link === '') {
                            continue;
                        }

                        $hash = JobPost::generateHash($titleText, $link);
                        if (JobPost::where('hash', $hash)->exists()) {
                            continue;
                        }

                        // Determine category based on title keyword matching
                        $assignedCategory = 'Software & IT'; // default
                        $titleLower = strtolower($titleText);
                        
                        if (str_contains($titleLower, 'mark') || str_contains($titleLower, 'sale') || str_contains($titleLower, 'seo')) {
                            $assignedCategory = 'Marketing & Sales';
                        } elseif (str_contains($titleLower, 'account') || str_contains($titleLower, 'financ') || str_contains($titleLower, 'tax')) {
                            $assignedCategory = 'Finance & Accounting';
                        } elseif (str_contains($titleLower, 'nurse') || str_contains($titleLower, 'health') || str_contains($titleLower, 'medic') || str_contains($titleLower, 'pharmac')) {
                            $assignedCategory = 'Healthcare & Medical';
                        } elseif (str_contains($titleLower, 'teach') || str_contains($titleLower, 'tutor') || str_contains($titleLower, 'educ')) {
                            $assignedCategory = 'Education & Training';
                        } elseif (str_contains($titleLower, 'support') || str_contains($titleLower, 'service') || str_contains($titleLower, 'customer')) {
                            $assignedCategory = 'Customer Service';
                        } elseif (str_contains($titleLower, 'writ') || str_contains($titleLower, 'design') || str_contains($titleLower, 'video') || str_contains($titleLower, 'edit') || str_contains($titleLower, 'designer')) {
                            $assignedCategory = 'Writing & Design';
                        } elseif (str_contains($titleLower, 'hr ') || str_contains($titleLower, 'human resource') || str_contains($titleLower, 'recruit')) {
                            $assignedCategory = 'Human Resources';
                        }

                        $company = null;
                        $sourceName = isset($item->source) ? (string) $item->source : 'Google News';

                        if (str_contains($titleText, ' - ')) {
                            $parts = explode(' - ', $titleText);
                            if (count($parts) >= 3) {
                                $sourceNameFromTitle = trim(array_pop($parts));
                                $company = trim(array_pop($parts));
                                $titleText = implode(' - ', $parts);
                                if (empty($sourceName) || $sourceName === 'Google News') {
                                    $sourceName = $sourceNameFromTitle;
                                }
                            } elseif (count($parts) == 2) {
                                $possibleSourceOrCompany = trim(array_pop($parts));
                                $titleText = trim($parts[0]);
                                if ($possibleSourceOrCompany === $sourceName) {
                                    $company = null;
                                } else {
                                    $company = $possibleSourceOrCompany;
                                }
                            }
                        }

                        $cleanDescription = html_entity_decode(strip_tags($description));
                        $cleanDescription = preg_replace('/\s+/', ' ', $cleanDescription ?: '');
                        $cleanDescription = trim((string) $cleanDescription);

                        $location = 'Remote / WFH';

                        try {
                            $publishedAt = Carbon::parse($pubDate);
                        } catch (\Throwable) {
                            $publishedAt = now();
                        }

                        $titleText = trim(preg_replace('/\b(job|jobs|hiring|vacancy)\b/i', '', $titleText));
                        $titleText = trim($titleText, " \t\n\r\0\x0B-_()");

                        JobPost::create([
                            'title' => Str::limit($titleText, 250),
                            'company' => $company ? Str::limit($company, 200) : 'Confidential',
                            'location' => Str::limit($location, 150),
                            'category' => $assignedCategory,
                            'description' => $cleanDescription ?: 'No job description details provided. Please apply directly via the original site link.',
                            'url' => $link,
                            'source_name' => Str::limit($sourceName, 150),
                            'published_at' => $publishedAt,
                            'is_remote' => true,
                            'is_visible' => true,
                            'hash' => $hash,
                        ]);

                        $savedCount++;
                        $stats['new_jobs']++;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("JobFetchService error fetching general remote category: " . $e->getMessage());
        }

        return $stats;
    }
}

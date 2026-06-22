<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class HeadlessPageRenderer
{
    public function render(string $url, int $cacheSeconds = 600, bool $forceRefresh = false): ?string
    {
        $cacheKey = 'headless-page:' . md5($url);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $cacheSeconds, function () use ($url) {
            $binary = $this->chromeBinary();

            if (!$binary) {
                return null;
            }

            $process = new Process([
                $binary,
                '--headless=new',
                '--disable-gpu',
                '--no-first-run',
                '--no-default-browser-check',
                '--virtual-time-budget=12000',
                '--dump-dom',
                $url,
            ]);

            $process->setTimeout(40);
            $process->setEnv(['TZ' => 'UTC']);
            $process->run();

            if (!$process->isSuccessful()) {
                return null;
            }

            $output = $process->getOutput();

            return str_contains($output, '<html') ? $output : null;
        });
    }

    public function diagnostics(): array
    {
        return [
            'chrome_available' => $this->chromeBinary() !== null,
            'chrome_binary' => $this->chromeBinary(),
        ];
    }

    protected function chromeBinary(): ?string
    {
        $configured = config('services.fifa.chrome_binary');

        if ($configured && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

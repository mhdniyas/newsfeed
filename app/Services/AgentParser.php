<?php

namespace App\Services;

class AgentParser
{
    /**
     * Parse User-Agent string to extract visitor dimensions.
     */
    public static function parse(string $userAgent): array
    {
        $userAgent = trim($userAgent);

        $isBot = false;
        $botType = null;
        $deviceType = 'desktop';
        $browserName = 'Other';
        $osName = 'Other';

        // 1. Identify bots first
        if ($userAgent === '') {
            $isBot = true;
            $botType = 'Unknown Bot';
        } else {
            $botRules = [
                // AI Bots
                'GPTBot' => '/gptbot|chatgpt/i',
                'ClaudeBot' => '/claudebot/i',
                'PerplexityBot' => '/perplexitybot/i',
                'Google-Extended' => '/google-extended/i',
                'Meta-AI' => '/meta-external-agent|meta-ai/i',
                'Applebot' => '/applebot/i',
                // Search Bots
                'Googlebot' => '/googlebot/i',
                'Bingbot' => '/bingbot|bingpreview/i',
                'Yandex' => '/yandex/i',
                'DuckDuckBot' => '/duckduckbot/i',
                // Monitoring
                'UptimeRobot' => '/uptimerobot/i',
                'Better Stack' => '/better\s*stack|statuspage/i',
                'Pingdom' => '/pingdom/i',
                // Scripting / Unknown Bots
                'cURL' => '/curl/i',
                'Python Requests' => '/python-requests|urllib/i',
                'Go HTTP Client' => '/go-http-client/i',
                'PHP Guzzle' => '/guzzlehttp/i',
            ];

            foreach ($botRules as $type => $regex) {
                if (preg_match($regex, $userAgent)) {
                    $isBot = true;
                    $botType = $type;
                    break;
                }
            }

            // Fallback generic bot detector
            if (!$isBot && preg_match('/bot|crawl|spider|slurp|tracker|scraper/i', $userAgent)) {
                $isBot = true;
                $botType = 'Unknown Bot';
            }
        }

        // If it's a bot, we skip full browser/OS resolution but set deviceType appropriately (mostly desktop/monitoring)
        if ($isBot) {
            return [
                'is_bot' => true,
                'bot_type' => $botType,
                'device_type' => 'desktop',
                'browser_name' => 'Bot',
                'os_name' => 'Bot',
            ];
        }

        // 2. Parse Operating System
        $osRules = [
            'macOS' => '/macintosh|mac\s*os\s*x/i',
            'iOS' => '/iphone|ipad|ipod/i',
            'Windows' => '/windows|win32/i',
            'Android' => '/android/i',
            'Linux' => '/linux/i',
        ];

        foreach ($osRules as $os => $regex) {
            if (preg_match($regex, $userAgent)) {
                $osName = $os;
                break;
            }
        }

        // 3. Parse Device Type
        if (preg_match('/ipad/i', $userAgent)) {
            $deviceType = 'tablet';
        } elseif (preg_match('/mobile|iphone|ipod|phone|android/i', $userAgent)) {
            $deviceType = 'mobile';
        } else {
            $deviceType = 'desktop';
        }

        // 4. Parse Browser Name
        $browserRules = [
            'Edge' => '/edg([ea])?/i',
            'Chrome' => '/chrome|crios/i',
            'Safari' => '/safari/i',
            'Firefox' => '/firefox|fxios/i',
            'Opera' => '/opera|opr/i',
        ];

        // Special order: Edge and Chrome contain 'Safari', Chrome contains Safari and Chrome, etc.
        if (preg_match($browserRules['Edge'], $userAgent)) {
            $browserName = 'Edge';
        } elseif (preg_match($browserRules['Opera'], $userAgent)) {
            $browserName = 'Opera';
        } elseif (preg_match($browserRules['Chrome'], $userAgent)) {
            $browserName = 'Chrome';
        } elseif (preg_match($browserRules['Safari'], $userAgent)) {
            $browserName = 'Safari';
        } elseif (preg_match($browserRules['Firefox'], $userAgent)) {
            $browserName = 'Firefox';
        }

        return [
            'is_bot' => false,
            'bot_type' => null,
            'device_type' => $deviceType,
            'browser_name' => $browserName,
            'os_name' => $osName,
        ];
    }
}

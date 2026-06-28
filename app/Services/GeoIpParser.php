<?php

namespace App\Services;

use Illuminate\Http\Request;

class GeoIpParser
{
    /**
     * Resolve the ISO 3166-1 alpha-2 country code from the request.
     */
    public static function resolveCountry(Request $request): string
    {
        // 1. Check Cloudflare header
        if ($cfCountry = $request->header('CF-IPCountry')) {
            return strtoupper($cfCountry);
        }

        // 2. Check general proxy country headers
        $headers = [
            'X-AppEngine-Country',
            'CloudFront-Viewer-Country',
            'X-Country-Code'
        ];

        foreach ($headers as $h) {
            if ($val = $request->header($h)) {
                return strtoupper($val);
            }
        }

        // 3. Fallback Accept-Language header checks
        if ($lang = $request->header('Accept-Language')) {
            $lang = strtolower($lang);
            if (str_contains($lang, 'in') || str_contains($lang, 'hi-')) {
                return 'IN';
            }
            if (str_contains($lang, 'us') || str_contains($lang, 'en-us')) {
                return 'US';
            }
            if (str_contains($lang, 'gb') || str_contains($lang, 'en-gb')) {
                return 'GB';
            }
            if (str_contains($lang, 'ca')) {
                return 'CA';
            }
            if (str_contains($lang, 'ae')) {
                return 'AE';
            }
        }

        // 4. Default fallback
        return 'IN';
    }
}

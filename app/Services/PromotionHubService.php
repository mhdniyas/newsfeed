<?php

namespace App\Services;

use App\Models\Setting;

class PromotionHubService
{
    public const SETTING_KEY = 'promotion_hub_cards';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function cards(): array
    {
        $stored = json_decode((string) Setting::get(self::SETTING_KEY, ''), true);
        $stored = is_array($stored) ? $stored : [];

        $cards = [];

        foreach ($this->defaults() as $key => $default) {
            $cards[$key] = array_merge($default, is_array($stored[$key] ?? null) ? $stored[$key] : []);
            $cards[$key]['enabled'] = (bool) ($cards[$key]['enabled'] ?? false);

            foreach (['badge', 'title', 'body', 'primary_label', 'primary_url', 'secondary_label', 'secondary_url', 'note'] as $field) {
                $cards[$key][$field] = trim((string) ($cards[$key][$field] ?? ''));
            }
        }

        return $cards;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string, mixed>>
     */
    public function sanitizeInput(array $input): array
    {
        $cards = [];

        foreach ($this->defaults() as $key => $default) {
            $payload = is_array($input[$key] ?? null) ? $input[$key] : [];
            $cards[$key] = [
                'enabled' => (bool) ($payload['enabled'] ?? false),
                'badge' => $this->cleanText($payload['badge'] ?? null),
                'title' => $this->cleanText($payload['title'] ?? null),
                'body' => $this->cleanTextarea($payload['body'] ?? null),
                'primary_label' => $this->cleanText($payload['primary_label'] ?? null),
                'primary_url' => $this->normalizeUrl($payload['primary_url'] ?? null),
                'secondary_label' => $this->cleanText($payload['secondary_label'] ?? null),
                'secondary_url' => $this->normalizeUrl($payload['secondary_url'] ?? null),
                'note' => $this->cleanText($payload['note'] ?? null),
            ];
        }

        return $cards;
    }

    /**
     * @param  array<string, array<string, mixed>>  $cards
     */
    public function save(array $cards, ?string $whatsAppMessage = null): void
    {
        Setting::set(self::SETTING_KEY, json_encode($cards, JSON_UNESCAPED_SLASHES));
        Setting::set('promo_whatsapp_message', $this->cleanTextarea($whatsAppMessage));

        $hero = $cards['hero'] ?? [];
        Setting::set('promo_quotex_url', $hero['primary_url'] ?? null);
        Setting::set('promo_signals_url', $hero['secondary_url'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public function publicPayload(): array
    {
        $cards = $this->cards();

        return [
            'cards' => $cards,
            'hero' => $cards['hero'],
            'desktop' => [
                'left' => $cards['desktop_left'],
                'right' => $cards['desktop_right'],
            ],
            'mobile' => array_values(array_filter([
                $cards['mobile_primary'],
                $cards['mobile_secondary'],
            ], fn (array $card) => $card['enabled'])),
            'whatsapp_message' => Setting::get('promo_whatsapp_message', config('services.promotions.whatsapp_message')),
            'quotex_url' => $cards['hero']['primary_url'] ?: config('services.promotions.quotex_url'),
            'signals_url' => $cards['hero']['secondary_url'] ?: config('services.promotions.signals_url'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return [
            'hero' => 'Homepage hero card',
            'desktop_left' => 'Desktop left rail',
            'desktop_right' => 'Desktop right rail',
            'mobile_primary' => 'Mobile card one',
            'mobile_secondary' => 'Mobile card two',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function defaults(): array
    {
        return [
            'hero' => [
                'enabled' => true,
                'badge' => 'Sponsored',
                'title' => 'Start with $10 on Quotex',
                'body' => 'Premium trading signals promotion placed between the homepage news sections, with direct referral actions for your audience.',
                'primary_label' => 'Start With $10',
                'primary_url' => $this->normalizeUrl(config('services.promotions.quotex_url')),
                'secondary_label' => 'Premium Signals',
                'secondary_url' => $this->normalizeUrl(config('services.promotions.signals_url')),
                'note' => 'Trading involves risk. Sponsored promotion.',
            ],
            'desktop_left' => [
                'enabled' => true,
                'badge' => 'Advertise Here',
                'title' => 'High-intent desktop traffic',
                'body' => 'Own the left rail beside the live news feed with a compact sponsored unit built for direct response.',
                'primary_label' => 'Book Placement',
                'primary_url' => '',
                'secondary_label' => 'View Media Kit',
                'secondary_url' => '',
                'note' => 'Desktop side placement visible during long-scroll reading.',
            ],
            'desktop_right' => [
                'enabled' => true,
                'badge' => 'Partner Slot',
                'title' => 'Reach daily football readers',
                'body' => 'Run a second desktop promotion on the opposite rail for stronger visibility across homepage and feed pages.',
                'primary_label' => 'Launch Campaign',
                'primary_url' => '',
                'secondary_label' => 'See Example',
                'secondary_url' => '',
                'note' => 'Best for product promos, affiliate offers, and newsletter growth.',
            ],
            'mobile_primary' => [
                'enabled' => true,
                'badge' => 'Mobile Ad',
                'title' => 'Sticky-style mobile visibility',
                'body' => 'Compact mobile card styled like a premium ad unit for fast taps without breaking the reading flow.',
                'primary_label' => 'Promote Now',
                'primary_url' => '',
                'secondary_label' => 'Ad Specs',
                'secondary_url' => '',
                'note' => 'Designed for small screens and thumb reach.',
            ],
            'mobile_secondary' => [
                'enabled' => true,
                'badge' => 'Sponsor Block',
                'title' => 'Add a second mobile slot',
                'body' => 'Stacked mobile placement adds another revenue lane between content sections and article grids.',
                'primary_label' => 'Get Quote',
                'primary_url' => '',
                'secondary_label' => 'Preview Layout',
                'secondary_url' => '',
                'note' => 'Secondary card supports extra campaigns without crowding the nav.',
            ],
        ];
    }

    protected function cleanText(mixed $value): string
    {
        return trim((string) $value);
    }

    protected function cleanTextarea(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function normalizeUrl(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        return $value;
    }
}

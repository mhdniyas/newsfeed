<?php

namespace App\Services;

class FifaPlaceholderImageService
{
    public const VARIANT_COUNT = 12;

    public function urlForSeed(string $seed): string
    {
        return '/media/fifa-placeholder/' . rawurlencode($seed) . '.svg';
    }

    public function variantForSeed(string $seed): int
    {
        return (hexdec(substr(md5($seed), 0, 6)) % self::VARIANT_COUNT) + 1;
    }

    public function svgForSeed(string $seed): string
    {
        $variant = $this->variantForSeed($seed);
        $themes = $this->themes();
        $theme = $themes[$variant - 1];
        $escapedTitle = e($theme['title']);
        $escapedSubtitle = e($theme['subtitle']);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="675" viewBox="0 0 1200 675" fill="none">
  <defs>
    <linearGradient id="bg{$variant}" x1="0" y1="0" x2="1200" y2="675" gradientUnits="userSpaceOnUse">
      <stop stop-color="{$theme['from']}"/>
      <stop offset="1" stop-color="{$theme['to']}"/>
    </linearGradient>
    <radialGradient id="glow{$variant}" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(940 120) rotate(146) scale(420 320)">
      <stop stop-color="rgba(255,255,255,0.38)"/>
      <stop offset="1" stop-color="rgba(255,255,255,0)"/>
    </radialGradient>
  </defs>
  <rect width="1200" height="675" rx="36" fill="url(#bg{$variant})"/>
  <circle cx="1030" cy="130" r="220" fill="url(#glow{$variant})"/>
  <circle cx="178" cy="520" r="162" fill="rgba(255,255,255,0.1)"/>
  <path d="M145 560C235 467 363 420 477 428C594 436 673 484 757 479C861 473 956 395 1039 311" stroke="rgba(255,255,255,0.16)" stroke-width="16" stroke-linecap="round"/>
  <rect x="72" y="76" width="220" height="42" rx="21" fill="rgba(255,255,255,0.18)"/>
  <text x="102" y="104" fill="#fff" font-family="Arial, sans-serif" font-size="24" font-weight="700" letter-spacing="3">FIFA 2026</text>
  <text x="72" y="332" fill="#fff" font-family="Arial, sans-serif" font-size="78" font-weight="800">{$escapedTitle}</text>
  <text x="72" y="394" fill="rgba(255,255,255,0.85)" font-family="Arial, sans-serif" font-size="32" font-weight="500">{$escapedSubtitle}</text>
  <g transform="translate(888 372)">
    <circle cx="0" cy="0" r="112" fill="rgba(255,255,255,0.18)"/>
    <circle cx="0" cy="0" r="68" fill="rgba(255,255,255,0.14)"/>
    <path d="M0 -86L37 -27L20 41H-20L-37 -27L0 -86Z" fill="rgba(255,255,255,0.9)"/>
    <path d="M-68 -22L-20 41L-74 18L-68 -22Z" fill="rgba(255,255,255,0.76)"/>
    <path d="M68 -22L20 41L74 18L68 -22Z" fill="rgba(255,255,255,0.76)"/>
    <path d="M-45 -68L0 -86L-68 -22L-45 -68Z" fill="rgba(255,255,255,0.72)"/>
    <path d="M45 -68L0 -86L68 -22L45 -68Z" fill="rgba(255,255,255,0.72)"/>
    <path d="M0 96L-20 41H20L0 96Z" fill="rgba(255,255,255,0.8)"/>
  </g>
  <text x="72" y="594" fill="rgba(255,255,255,0.75)" font-family="Arial, sans-serif" font-size="24">Live headlines, fixtures, scores and official World Cup coverage</text>
</svg>
SVG;
    }

    protected function themes(): array
    {
        return [
            ['from' => '#0F766E', 'to' => '#F59E0B', 'title' => 'Matchday Pulse', 'subtitle' => 'World Cup storylines'],
            ['from' => '#14532D', 'to' => '#16A34A', 'title' => 'Road To Glory', 'subtitle' => 'Host cities in focus'],
            ['from' => '#1D4ED8', 'to' => '#0F172A', 'title' => 'Scoreboard Live', 'subtitle' => 'Fixtures and final whistles'],
            ['from' => '#7C2D12', 'to' => '#EA580C', 'title' => 'Golden Moments', 'subtitle' => 'Big goals and big nights'],
            ['from' => '#312E81', 'to' => '#2563EB', 'title' => 'Stadium Stories', 'subtitle' => 'Atmosphere across 2026'],
            ['from' => '#831843', 'to' => '#E11D48', 'title' => 'Knockout Heat', 'subtitle' => 'Pressure on every touch'],
            ['from' => '#164E63', 'to' => '#06B6D4', 'title' => 'Tournament Watch', 'subtitle' => 'Latest shifts in form'],
            ['from' => '#3F3F46', 'to' => '#52525B', 'title' => 'Final Whistle', 'subtitle' => 'Recaps from the pitch'],
            ['from' => '#4C1D95', 'to' => '#7C3AED', 'title' => 'Group Stage Grind', 'subtitle' => 'Every point matters'],
            ['from' => '#166534', 'to' => '#84CC16', 'title' => 'Host Nation Energy', 'subtitle' => 'USA, Mexico, Canada'],
            ['from' => '#9A3412', 'to' => '#FB7185', 'title' => 'Star Player Radar', 'subtitle' => 'Icons chasing history'],
            ['from' => '#0F172A', 'to' => '#334155', 'title' => 'World Cup Briefing', 'subtitle' => 'Reliable updates all day'],
        ];
    }
}

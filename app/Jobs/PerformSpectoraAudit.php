<?php

namespace App\Jobs;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\TransferStats;

class PerformSpectoraAudit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Domain $domain)
    {
    }

    public function handle(): void
    {
        set_time_limit(120); // Allow 2 minutes for execution

        $url = $this->domain->url;
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $score = 100;
        $details = [];
        $ttfb = 0;
        $totalTime = 0;

        try {
            // 1. Download & Measure
            $response = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->withOptions([
                    'on_stats' => function (TransferStats $stats) use (&$ttfb, &$totalTime) {
                        // TTFB (starttransfer_time)
                        if (isset($stats->getHandlerStats()['starttransfer_time'])) {
                            $ttfb = $stats->getHandlerStats()['starttransfer_time'];
                        }
                        $totalTime = $stats->getTransferTime();
                    }
                ])
                ->timeout(15)
                ->get($url);

            if ($response->failed()) {
                Log::error("Spectora Audit failed for {$url}: HTTP " . $response->status());
                return;
            }

            $body = $response->body();
            $sizeBytes = strlen($body);
            $crawler = new Crawler($body);

            // 2. Analyze & Calculate Score

            // A) Server Speed (TTFB > 500ms)
            // TTFB is in seconds from Guzzle
            $ttfbMs = $ttfb * 1000;
            if ($ttfbMs > 500) {
                $score -= 10;
                $details[] = [
                    'category' => 'performance',
                    'label' => 'Server-Antwortzeit (TTFB)',
                    'status' => 'error',
                    'message' => 'Der Server antwortet langsam (' . round($ttfbMs) . 'ms). Ziel: < 500ms.'
                ];
            } else {
                $details[] = [
                    'category' => 'performance',
                    'label' => 'Server-Antwortzeit (TTFB)',
                    'status' => 'success',
                    'message' => 'Schnelle Antwortzeit (' . round($ttfbMs) . 'ms).'
                ];
            }

            // B) Größe (HTML Body > 1MB)
            if ($sizeBytes > 1024 * 1024) {
                $score -= 10;
                $details[] = [
                    'category' => 'performance',
                    'label' => 'Seitengröße',
                    'status' => 'error',
                    'message' => 'Die HTML-Größe ist sehr groß (' . round($sizeBytes / 1024 / 1024, 2) . ' MB).'
                ];
            } else {
                $details[] = [
                    'category' => 'performance',
                    'label' => 'Seitengröße',
                    'status' => 'success',
                    'message' => 'HTML-Größe ist optimal (' . round($sizeBytes / 1024) . ' KB).'
                ];
            }

            // C) SEO Checks
            // H1
            $h1Count = $crawler->filter('h1')->count();
            if ($h1Count === 0) {
                $score -= 10;
                $details[] = [
                    'category' => 'seo',
                    'label' => 'H1 Überschrift',
                    'status' => 'error',
                    'message' => 'Keine H1-Überschrift gefunden.'
                ];
            } else {
                $details[] = [
                    'category' => 'seo',
                    'label' => 'H1 Überschrift',
                    'status' => 'success',
                    'message' => "H1 gefunden ($h1Count)."
                ];
            }

            // Title
            $title = $crawler->filter('title')->count() > 0 ? $crawler->filter('title')->text() : '';
            if (empty($title)) {
                $score -= 10;
                $details[] = [
                    'category' => 'seo',
                    'label' => 'Seitentitel',
                    'status' => 'error',
                    'message' => 'Der <title> Tag fehlt oder ist leer.'
                ];
            } else {
                $details[] = [
                    'category' => 'seo',
                    'label' => 'Seitentitel',
                    'status' => 'success',
                    'message' => 'Titel vorhanden.'
                ];
            }

            // Meta Description
            $metaDesc = $crawler->filter('meta[name="description"]')->count() > 0 
                ? $crawler->filter('meta[name="description"]')->attr('content') 
                : '';
            
            if (empty($metaDesc)) {
                $score -= 5;
                $details[] = [
                    'category' => 'seo',
                    'label' => 'Meta-Description',
                    'status' => 'warning',
                    'message' => 'Keine Meta-Description gefunden.'
                ];
            } else {
                $details[] = [
                    'category' => 'seo',
                    'label' => 'Meta-Description',
                    'status' => 'success',
                    'message' => 'Meta-Description vorhanden.'
                ];
            }

            // D) Bilder (Alt-Attribute)
            $imagesWithoutAlt = $crawler->filter('img')->reduce(function (Crawler $node) {
                return empty($node->attr('alt'));
            })->count();

            if ($imagesWithoutAlt > 0) {
                $score -= 5;
                $details[] = [
                    'category' => 'accessibility',
                    'label' => 'Bilder-Alt-Texte',
                    'status' => 'warning',
                    'message' => "$imagesWithoutAlt Bilder haben kein Alt-Attribut."
                ];
            } else {
                $details[] = [
                    'category' => 'accessibility',
                    'label' => 'Bilder-Alt-Texte',
                    'status' => 'success',
                    'message' => 'Alle Bilder haben Alt-Attribute.'
                ];
            }

            // E) Sicherheit (HTTPS)
            $parsedUrl = parse_url($url);
            if (!isset($parsedUrl['scheme']) || $parsedUrl['scheme'] !== 'https') {
                $score -= 20;
                $details[] = [
                    'category' => 'security',
                    'label' => 'HTTPS Verschlüsselung',
                    'status' => 'error',
                    'message' => 'Die Seite nutzt kein HTTPS.'
                ];
            } else {
                $details[] = [
                    'category' => 'security',
                    'label' => 'HTTPS Verschlüsselung',
                    'status' => 'success',
                    'message' => 'HTTPS ist aktiv.'
                ];
            }

            // Clamp Score
            $score = max(0, min(100, $score));

            // 3. Save
            $this->domain->pagespeed_desktop = $score; // Using this column as "Spectora Score"
            $this->domain->last_pagespeed_details = $details;
            $this->domain->touch(); // Updates updated_at
            $this->domain->save();

            // Save to History
            \App\Models\ChecksHistory::create([
                'domain_id' => $this->domain->id,
                'pagespeed_score_desktop' => $score,
                'created_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Spectora Audit Exception for {$url}: " . $e->getMessage());
        }
    }
}

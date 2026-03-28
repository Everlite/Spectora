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
        $filterService = app(\App\Services\MonitoringFilterService::class);
        set_time_limit(120); // Allow 2 minutes for execution

        $url = $this->domain->url;
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        // 0. Pre-check Filter (Excludes, Robots.txt)
        $filter = $filterService->shouldCheck($this->domain, $url);
        if (!$filter['should_check']) {
            Log::info("Skipping audit for {$url}: {$filter['reason']}");
            return;
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

            // 1.5 Post-fetch Filter (noindex, auth)
            $postFilter = $filterService->shouldIgnoreResponse($this->domain, $response);
            if ($postFilter['ignore']) {
                Log::info("Ignoring audit response for {$url}: {$postFilter['reason']}");
                return;
            }

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
                    'label' => 'Server response time (TTFB)',
                    'status' => 'error',
                    'message' => 'The server is responding slowly (' . round($ttfbMs) . 'ms). Target: < 500ms.'
                ];
            } else {
                $details[] = [
                    'category' => 'performance',
                    'label' => 'Server response time (TTFB)',
                    'status' => 'success',
                    'message' => 'Fast response time (' . round($ttfbMs) . 'ms).'
                ];
            }

            // B) Größe (HTML Body > 1MB)
            if ($sizeBytes > 1024 * 1024) {
                $score -= 10;
                $details[] = [
                    'category' => 'performance',
                    'label' => 'Page size',
                    'status' => 'error',
                    'message' => 'The HTML size is very large (' . round($sizeBytes / 1024 / 1024, 2) . ' MB).'
                ];
            } else {
                $details[] = [
                    'category' => 'performance',
                    'label' => 'Page size',
                    'status' => 'success',
                    'message' => 'HTML size is optimal (' . round($sizeBytes / 1024) . ' KB).'
                ];
            }

            // C) SEO Checks
            // H1
            $h1Count = $crawler->filter('h1')->count();
            if ($h1Count === 0) {
                $score -= 10;
                $details[] = [
                    'category' => 'seo',
                    'label' => 'H1 heading',
                    'status' => 'error',
                    'message' => 'No H1 heading found.'
                ];
            } else {
                $details[] = [
                    'category' => 'seo',
                    'label' => 'H1 heading',
                    'status' => 'success',
                    'message' => "H1 found ($h1Count)."
                ];
            }

            // Title
            $title = $crawler->filter('title')->count() > 0 ? $crawler->filter('title')->text() : '';
            if (empty($title)) {
                $score -= 10;
                $details[] = [
                    'category' => 'seo',
                    'label' => 'Page title',
                    'status' => 'error',
                    'message' => 'The <title> tag is missing or empty.'
                ];
            } else {
                $details[] = [
                    'category' => 'seo',
                    'label' => 'Page title',
                    'status' => 'success',
                    'message' => 'Title present.'
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
                    'label' => 'Meta description',
                    'status' => 'warning',
                    'message' => 'No meta description found.'
                ];
            } else {
                $details[] = [
                    'category' => 'seo',
                    'label' => 'Meta description',
                    'status' => 'success',
                    'message' => 'Meta description present.'
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
                    'label' => 'Image alt texts',
                    'status' => 'warning',
                    'message' => "$imagesWithoutAlt images have no alt attribute."
                ];
            } else {
                $details[] = [
                    'category' => 'accessibility',
                    'label' => 'Image alt texts',
                    'status' => 'success',
                    'message' => 'All images have alt attributes.'
                ];
            }

            // E) Sicherheit (HTTPS)
            $parsedUrl = parse_url($url);
            if (!isset($parsedUrl['scheme']) || $parsedUrl['scheme'] !== 'https') {
                $score -= 20;
                $details[] = [
                    'category' => 'security',
                    'label' => 'HTTPS encryption',
                    'status' => 'error',
                    'message' => 'The page does not use HTTPS.'
                ];
            } else {
                $details[] = [
                    'category' => 'security',
                    'label' => 'HTTPS encryption',
                    'status' => 'success',
                    'message' => 'HTTPS is active.'
                ];
            }

            // Clamp Score
            $score = max(0, min(100, $score));

            // 3. Save
            $this->domain->pagespeed_score_desktop = $score; // Using this column as "Spectora Score"
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

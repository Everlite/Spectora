<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Models\ChecksHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RunPageSpeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $domain;
    public $strategy;

    /**
     * Create a new job instance.
     *
     * @param Domain $domain
     * @param string $strategy 'mobile' or 'desktop'
     */
    public function __construct(Domain $domain, string $strategy = 'mobile')
    {
        $this->domain = $domain;
        $this->strategy = $strategy;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = $this->domain->url;
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        try {
            $data = app(\App\Services\LighthouseService::class)->scan($url, $this->strategy);

            if (!$data) {
                Log::error("Local Lighthouse failed for {$url} ({$this->strategy})");
                return;
            }

            $score = isset($data['categories']['performance']['score']) 
                ? (int)round($data['categories']['performance']['score'] * 100) 
                : 0;

            // Extract Audits (Opportunities & Diagnostics)
            $audits = $data['audits'] ?? []; // Lighthouse JSON root has audits
            $categories = $data['categories'] ?? [];
            
            $details = [];
            
            // Helper to process a category
            $processCategory = function($catKey) use ($categories, $audits) {
                if (!isset($categories[$catKey])) return null;
                
                $cat = $categories[$catKey];
                $auditRefs = $cat['auditRefs'] ?? [];
                $relevantAudits = [];

                foreach ($auditRefs as $ref) {
                    $id = $ref['id'];
                    if (!isset($audits[$id])) continue;
                    $audit = $audits[$id];
                    
                    $scoreDisplayMode = $audit['scoreDisplayMode'] ?? '';
                    if ($scoreDisplayMode === 'manual' || $scoreDisplayMode === 'notApplicable') continue;
                    if (isset($audit['score']) && $audit['score'] >= 0.9) continue;

                    $relevantAudits[] = [
                        'id' => $id,
                        'title' => $audit['title'] ?? $id,
                        'description' => $audit['description'] ?? '',
                        'displayValue' => $audit['displayValue'] ?? null,
                        'score' => isset($audit['score']) ? (int)round($audit['score'] * 100) : null,
                    ];
                }
                
                return [
                    'score' => (int)round(($cat['score'] ?? 0) * 100),
                    'audits' => array_slice($relevantAudits, 0, 10)
                ];
            };

            $details['performance'] = $processCategory('performance');

            // Update Domain
            if ($this->strategy === 'desktop') {
                $this->domain->pagespeed_desktop = $score;
            } else {
                $this->domain->pagespeed_mobile = $score;
            }
            
            $currentDetails = $this->domain->last_pagespeed_details ?? [];
            if (is_string($currentDetails)) $currentDetails = json_decode($currentDetails, true) ?? [];
            
            $currentDetails[$this->strategy] = $details;
            $this->domain->last_pagespeed_details = $currentDetails;

            $this->domain->save();

            // Create History Entry
            ChecksHistory::create([
                'domain_id' => $this->domain->id,
                'created_at' => now(),
                'pagespeed_score' => $this->strategy === 'mobile' ? $score : null,
                'pagespeed_score_desktop' => $this->strategy === 'desktop' ? $score : null,
            ]);

        } catch (\Exception $e) {
            Log::error("Local Lighthouse Job Exception for {$url}: " . $e->getMessage());
        }
    }
}

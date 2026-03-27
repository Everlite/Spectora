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

class CheckPageSpeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $domain;

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    public function handle(): void
    {
        $url = $this->domain->url;
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $mobileScore = $this->getPageSpeedScore($url, 'mobile');
        $desktopScore = $this->getPageSpeedScore($url, 'desktop');

        if ($mobileScore !== null && $desktopScore !== null) {
            ChecksHistory::create([
                'domain_id' => $this->domain->id,
                'pagespeed_score' => $mobileScore,
                'pagespeed_score_desktop' => $desktopScore,
                'checked_at' => now(),
            ]);

            Log::info("CheckPageSpeedJob (Local): Checked $url - Mobile: $mobileScore, Desktop: $desktopScore");
        } else {
            Log::error("CheckPageSpeedJob (Local): Failed to retrieve scores for $url");
        }
    }

    private function getPageSpeedScore($url, $strategy)
    {
        $data = app(\App\Services\LighthouseService::class)->scan($url, $strategy);
        if (!$data) return null;

        $score = $data['categories']['performance']['score'] ?? 0;
        return (int)($score * 100);
    }
}

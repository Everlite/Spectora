<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Models\MonitoredUrl;
use App\Models\ChecksHistory;
use App\Services\MonitoringFilterService;
use App\Services\WatchdogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DomainWarningMail;

class CheckUrlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Domain $domain,
        public ?string $url = null,
        public ?MonitoredUrl $monitoredUrl = null
    ) {
        $this->url = $url ?? ($monitoredUrl ? $monitoredUrl->url : $domain->url);
    }

    public function handle(): void
    {
        $filterService = app(MonitoringFilterService::class);
        $url = $this->url;
        
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        // 0. Pre-check Filter (Excludes, Robots.txt)
        $filter = $filterService->shouldCheck($this->domain, $url);
        if (!$filter['should_check']) {
            Log::info("Skipping check for {$url}: {$filter['reason']}");
            return;
        }

        $startTime = microtime(true);
        $statusCode = 0;
        $sslDays = null;
        $issues = [];
        $safetyStatus = 'safe';
        $safetyDetails = [];

        try {
            // 1. Perform HTTP Check with SpectoraBot UA
            $response = Http::withUserAgent('SpectoraBot/1.0')
                ->timeout(15)
                ->get($url);
            
            // 1.5 Post-fetch Filter (noindex, auth)
            $postFilter = $filterService->shouldIgnoreResponse($this->domain, $response);
            if ($postFilter['ignore']) {
                Log::info("Ignoring response for {$url}: {$postFilter['reason']}");
                if ($this->monitoredUrl) {
                    $this->monitoredUrl->update(['last_safety_status' => 'ignored']);
                }
                return;
            }

            $statusCode = $response->status();
            $responseTime = microtime(true) - $startTime;
            $body = strtolower($response->body());

            // 2. SSL Check (Only for main domain or if url includes host)
            $sslDays = $this->getSSLDays($url);

            // 3. Keyword Check (Must NOT Contain)
            if ($this->domain->keyword_must_not_contain) {
                $forbiddenKeywords = array_map('trim', explode(',', $this->domain->keyword_must_not_contain));
                foreach ($forbiddenKeywords as $keyword) {
                    if (!empty($keyword) && str_contains($body, $keyword)) {
                        $issues[] = "❌ Fehlerwort gefunden: " . htmlspecialchars($keyword);
                        $safetyStatus = 'danger';
                        $safetyDetails['keywords_found'][] = $keyword;
                    }
                }
            }

            // 4. Watchdog Service (Security, Title, Links, etc.)
            try {
                $watchdog = new WatchdogService();
                // Watchdog currently expects Domain for settings, but we can pass URL if needed.
                // For now, let's just use it as is but be aware it scans the URL from the response.
                $scanResult = $watchdog->scan($this->domain); // This might repeat the request if not careful, but WatchdogService uses domain->url.
                // TODO: Refactor WatchdogService to accept a Crawler or Response body to avoid redundant requests.
                
                $safetyDetails['watchdog'] = $scanResult;
                if ($scanResult['status'] === 'danger') {
                    $safetyStatus = 'danger';
                    foreach ($scanResult['issues'] as $issue) {
                        if ($issue['severity'] === 'critical') {
                            $issues[] = "🚨 {$issue['title']}: {$issue['description']}";
                        }
                    }
                } elseif ($scanResult['status'] === 'warning' && $safetyStatus === 'safe') {
                    $safetyStatus = 'warning';
                }
            } catch (\Exception $e) {
                Log::error("Watchdog scan failed for {$url}: " . $e->getMessage());
            }

            if ($statusCode >= 400 || $statusCode === 0) {
                $issues[] = "❌ Nicht erreichbar (HTTP $statusCode)";
            }

        } catch (\Exception $e) {
            $responseTime = microtime(true) - $startTime;
            Log::error("Check failed for {$url}: " . $e->getMessage());
            $statusCode = 0;
            $issues[] = "❌ Check fehlgeschlagen: " . $e->getMessage();
            $safetyStatus = 'danger';
        }

        $responseTimeMs = round(($responseTime ?? 0) * 1000);

        // --- Update Records ---
        if ($this->monitoredUrl) {
            $this->monitoredUrl->update([
                'last_status_code' => $statusCode,
                'last_safety_status' => $safetyStatus,
                'last_response_time' => round(($responseTime ?? 0) * 1000),
                'last_checked' => now(),
            ]);
            
            // Still update the domain's last_checked to show activity on the dashboard
            $this->domain->update(['last_checked' => now()]);
        } else {
            // Updating the Domain model as the "Main URL" record
            Log::info("Updating domain {$this->domain->url} Status: {$statusCode}, SSL: {$sslDays}");
            try {
                $this->domain->update([
                    'status' => ($statusCode >= 200 && $statusCode < 400) ? 'online' : 'offline',
                    'status_code' => $statusCode,
                    'ssl_days_left' => $sslDays,
                    'response_time' => $responseTime ?? 0,
                    'safety_status' => $safetyStatus,
                    'safety_details' => $safetyDetails,
                    'last_checked' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to update domain record: " . $e->getMessage());
            }
        }

        // --- Create History ---
        ChecksHistory::create([
            'domain_id' => $this->domain->id,
            'monitored_url_id' => $this->monitoredUrl?->id,
            'status_code' => $statusCode > 0 ? $statusCode : null,
            'response_time' => $responseTime ?? 0,
            'safety_status' => $safetyStatus,
            'ssl_days_left' => $sslDays,
            'created_at' => now(),
        ]);

        // --- Handle Notifications (Only if it's the main domain for now to avoid spam) ---
        if (!$this->monitoredUrl && !empty($issues)) {
             // Existing notification logic...
             if (!$this->domain->notify_sent) {
                 try {
                     $user = $this->domain->user;
                     if ($user) {
                         Mail::to($user->email)->send(new DomainWarningMail($this->domain, $issues));
                         $this->domain->update(['notify_sent' => true]);
                     }
                 } catch (\Exception $e) {
                     Log::error("Failed to send mail: " . $e->getMessage());
                 }
             }
        }
    }

    private function getSSLDays($url) {
        try {
            $host = parse_url($url, PHP_URL_HOST);
            if (!$host) return null;

            $context = stream_context_create([
                "ssl" => [
                    "capture_peer_cert" => true, 
                    "verify_peer" => false, 
                    "verify_peer_name" => false
                ]
            ]);

            $client = @stream_socket_client(
                "ssl://{$host}:443", 
                $errno, 
                $errstr, 
                10, 
                STREAM_CLIENT_CONNECT, 
                $context
            );

            if (!$client) {
                Log::warning("SSL Socket failed for {$host}: {$errstr} ({$errno})");
                return null;
            }

            $params = stream_context_get_params($client);
            if (!isset($params["options"]["ssl"]["peer_certificate"])) {
                Log::warning("SSL Certificate capture failed for {$host}");
                fclose($client);
                return null;
            }

            $cert = openssl_x509_parse($params["options"]["ssl"]["peer_certificate"]);
            fclose($client);

            if (!$cert || !isset($cert['validTo_time_t'])) {
                Log::warning("SSL Certificate parsing failed for {$host}");
                return null;
            }

            $days = floor(($cert['validTo_time_t'] - time()) / 86400);
            return max(0, $days);
        } catch (\Exception $e) {
            Log::error("SSL check exception for {$url}: " . $e->getMessage());
            return null;
        }
    }
}

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

class CheckDomainJob implements ShouldQueue
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

        $startTime = microtime(true);
        $statusCode = 0;
        $sslDays = null;
        $issues = [];
        $safetyStatus = 'safe';
        $safetyDetails = [];

        try {
            // 1. Perform HTTP Check with SpectoraBot UA
            $response = Http::withUserAgent('SpectoraBot/1.0')
                ->timeout(10)
                ->get($url);
            
            $statusCode = $response->status();
            $responseTime = microtime(true) - $startTime;
            $body = strtolower($response->body());

            // 2. SSL Check
            $sslDays = $this->getSSLDays($url);

            // 3. Keyword Check (Must NOT Contain)
            if ($this->domain->keyword_must_not_contain) {
                $forbiddenKeywords = array_map('trim', explode(',', $this->domain->keyword_must_not_contain));
                foreach ($forbiddenKeywords as $keyword) {
                    if (!empty($keyword) && str_contains($body, strtolower($keyword))) {
                        $issues[] = "<span class='danger'>❌ Fehlerwort gefunden: <strong>" . htmlspecialchars($keyword) . "</strong></span>";
                        $safetyStatus = 'danger';
                        $safetyDetails['keywords_found'][] = $keyword;
                    }
                }
            }

            // 4. Watchdog Service (Security, Title, Links, Hidden Content, etc.)
            try {
                $watchdog = new \App\Services\WatchdogService();
                $scanResult = $watchdog->scan($this->domain);
                
                // Store all detailed issues from Watchdog
                $safetyDetails['watchdog'] = $scanResult;
                
                // Update status based on Watchdog findings
                if ($scanResult['status'] === 'danger') {
                    $safetyStatus = 'danger';
                    // Add critical issues to notification
                    foreach ($scanResult['issues'] as $issue) {
                        if ($issue['severity'] === 'critical') {
                            $issues[] = "<span class='danger'>🚨 {$issue['title']}: {$issue['description']}</span>";
                        }
                    }
                } elseif ($scanResult['status'] === 'warning' && $safetyStatus === 'safe') {
                    $safetyStatus = 'warning';
                }
            } catch (\Exception $e) {
                Log::error("Watchdog scan failed for {$url}: " . $e->getMessage());
            }


            // Check Status Code
            if ($statusCode >= 400 || $statusCode === 0) {
                $issues[] = "<span class='danger'>❌ Website ist nicht erreichbar (HTTP-Status: $statusCode)</span>";
            }

            // Check SSL Expiry
            if ($sslDays !== null && $sslDays < 7) {
                $issues[] = "<span class='warning'>⚠️ SSL-Zertifikat läuft bald ab (in {$sslDays} Tagen)</span>";
            }

            // Check Response Time
            if ($responseTime > 3.0) {
                $issues[] = "<span class='warning'>⚠️ Server-Antwortzeit ist sehr langsam: " . number_format($responseTime, 2) . "s</span>";
            }

        } catch (\Exception $e) {
            $responseTime = microtime(true) - $startTime;
            Log::error("Check failed for {$url}: " . $e->getMessage());
            $statusCode = 0; // Ensure 0 on failure
            $issues[] = "<span class='danger'>❌ Check fehlgeschlagen: " . htmlspecialchars($e->getMessage()) . "</span>";
        }

        // --- Update Domain Model ---
        $this->domain->status_code = $statusCode;
        $this->domain->ssl_days_left = $sslDays;
        $this->domain->response_time = $responseTime;
        $this->domain->safety_status = $safetyStatus;
        $this->domain->safety_details = $safetyDetails;
        $this->domain->last_checked = now();
        $this->domain->save();

        // --- Handle Notifications ---
        if (!empty($issues)) {
            if (!$this->domain->notify_sent) {
                // Send Mail
                try {
                    // Assuming user relation exists or we get email from user
                    $user = $this->domain->user; 
                    if ($user) {
                        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\DomainWarningMail($this->domain, $issues));
                        $this->domain->notify_sent = true;
                        $this->domain->save();
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send warning mail for {$url}: " . $e->getMessage());
                }
            }
        } else {
            // Reset if everything is fine
            if ($this->domain->notify_sent) {
                $this->domain->notify_sent = false;
                $this->domain->save();
            }
        }

        // --- Create History ---
        ChecksHistory::create([
            'domain_id' => $this->domain->id,
            'status_code' => $statusCode > 0 ? $statusCode : null,
            'response_time' => $responseTime,
            'created_at' => now(), // Explicitly set created_at as requested
            'safety_status' => $safetyStatus,
            'ssl_days_left' => $sslDays, 
        ]);
    }


    private function getSSLDays($url) {
        try {
            $domain = parse_url($url, PHP_URL_HOST);
            $context = stream_context_create(["ssl" => ["capture_peer_cert" => true, "verify_peer" => false, "verify_peer_name" => false]]);
            $client = @stream_socket_client("ssl://{$domain}:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
            if (!$client) return null;
            $params = stream_context_get_params($client);
            if (!isset($params["options"]["ssl"]["peer_certificate"])) return null;
            $cert = openssl_x509_parse($params["options"]["ssl"]["peer_certificate"]);
            if (!$cert) return null;
            $validTo = $cert['validTo_time_t'];
            return floor(($validTo - time()) / 86400);
        } catch (\Exception $e) {
            return null;
        }
    }
}

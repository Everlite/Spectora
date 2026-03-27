<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class WatchdogService
{
    /**
     * Kategorisierte Spam-Keywords
     */
    protected array $spamKeywords = [
        'pharma' => ['viagra', 'cialis', 'levitra', 'pharmacy', 'pills online', 'buy medication'],
        'gambling' => ['casino', 'poker online', 'betting', 'slots', 'jackpot', 'roulette'],
        'adult' => ['porn', 'xxx', 'adult content', 'sex video', 'webcam girls'],
        'counterfeit' => ['replica watches', 'cheap jerseys', 'louis vuitton', 'gucci outlet', 'fake rolex'],
        'crypto_scam' => ['buy bitcoin', 'crypto investment', 'guaranteed returns', 'double your bitcoin'],
    ];

    /**
     * Verdächtige externe Domains
     */
    protected array $suspiciousDomains = [
        'bit.ly', 'tinyurl.com', 'goo.gl', // URL-Shortener
        '.ru', '.cn', '.tk', '.ml', '.ga', // Bekannte Spam-TLDs
    ];

    /**
     * Scannt eine Domain auf Sicherheitsprobleme
     */
    public function scan(Domain $domain): array
    {
        $url = $domain->url;
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $issues = [];
        $summary = ['critical' => 0, 'warning' => 0, 'info' => 0];

        try {
            // SpectoraBot (Privacy First Scanner)
            $response = Http::withUserAgent('SpectoraBot/1.0 (+https://example.com/bot)')
                ->timeout(15)
                ->get($url);

            if ($response->failed()) {
                return [
                    'status' => 'error',
                    'issues' => [[
                        'type' => 'connection_error',
                        'severity' => 'critical',
                        'title' => 'Website nicht erreichbar',
                        'description' => 'Die Website konnte nicht geladen werden (HTTP ' . $response->status() . ').',
                        'explanation' => 'SpectoraBot kann die Seite nicht crawlen. Das schadet dem SEO-Ranking massiv.',
                        'recommendation' => 'Prüfe, ob die Website online ist und ob SpectoraBot nicht blockiert wird (robots.txt, .htaccess).',
                    ]],
                    'summary' => ['critical' => 1, 'warning' => 0, 'info' => 0]
                ];
            }

            $body = $response->body();
            $bodyLower = strtolower($body);
            $crawler = new Crawler($body);

            // ═══════════════════════════════════════════════════
            // CHECK 1: Title-Tag Analyse
            // ═══════════════════════════════════════════════════
            $titleIssue = $this->checkTitle($crawler);
            if ($titleIssue) {
                $issues[] = $titleIssue;
                $summary[$titleIssue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 2: Spam-Keyword-Scan
            // ═══════════════════════════════════════════════════
            $keywordIssues = $this->checkSpamKeywords($bodyLower);
            foreach ($keywordIssues as $issue) {
                $issues[] = $issue;
                $summary[$issue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 3: Verdächtige externe Links
            // ═══════════════════════════════════════════════════
            $linkIssues = $this->checkSuspiciousLinks($crawler, $url);
            foreach ($linkIssues as $issue) {
                $issues[] = $issue;
                $summary[$issue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 4: Hidden Content (display:none mit Text)
            // ═══════════════════════════════════════════════════
            $hiddenIssue = $this->checkHiddenContent($body);
            if ($hiddenIssue) {
                $issues[] = $hiddenIssue;
                $summary[$hiddenIssue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 5: Verdächtige Iframes
            // ═══════════════════════════════════════════════════
            $iframeIssues = $this->checkIframes($crawler);
            foreach ($iframeIssues as $issue) {
                $issues[] = $issue;
                $summary[$issue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 6: Meta-Refresh Redirect
            // ═══════════════════════════════════════════════════
            $redirectIssue = $this->checkMetaRedirect($crawler);
            if ($redirectIssue) {
                $issues[] = $redirectIssue;
                $summary[$redirectIssue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 7: Search Console Verification
            // ═══════════════════════════════════════════════════
            $verificationInfo = $this->checkSearchConsoleVerification($bodyLower);
            if ($verificationInfo) {
                $issues[] = $verificationInfo;
                $summary[$verificationInfo['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 8: Verdächtige externe Scripts
            // ═══════════════════════════════════════════════════
            $scriptIssues = $this->checkSuspiciousScripts($crawler);
            foreach ($scriptIssues as $issue) {
                $issues[] = $issue;
                $summary[$issue['severity']]++;
            }

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'issues' => [[
                    'type' => 'scan_error',
                    'severity' => 'warning',
                    'title' => 'Scan fehlgeschlagen',
                    'description' => 'Fehler beim Scannen: ' . $e->getMessage(),
                    'explanation' => 'Der Watchdog konnte die Seite nicht vollständig analysieren.',
                    'recommendation' => 'Überprüfe, ob die URL korrekt ist und die Seite erreichbar ist.',
                ]],
                'summary' => ['critical' => 0, 'warning' => 1, 'info' => 0]
            ];
        }

        // Gesamtstatus ermitteln
        $status = 'safe';
        if ($summary['critical'] > 0) {
            $status = 'danger';
        } elseif ($summary['warning'] > 0) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'issues' => $issues,
            'summary' => $summary
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // EINZELNE CHECK-METHODEN
    // ═══════════════════════════════════════════════════════════

    private function checkTitle(Crawler $crawler): ?array
    {
        $titleNode = $crawler->filter('title');
        
        if ($titleNode->count() === 0) {
            return [
                'type' => 'missing_title',
                'severity' => 'warning',
                'title' => 'Kein Title-Tag gefunden',
                'description' => 'Die Seite hat keinen <title>-Tag.',
                'explanation' => 'Der Title-Tag ist essentiell für SEO und wird in Suchergebnissen angezeigt.',
                'recommendation' => 'Füge einen aussagekräftigen <title>-Tag im <head>-Bereich hinzu.',
            ];
        }

        $title = trim($titleNode->text());

        if (empty($title)) {
            return [
                'type' => 'empty_title',
                'severity' => 'warning',
                'title' => 'Leerer Title-Tag',
                'description' => 'Der <title>-Tag ist leer.',
                'explanation' => 'Ein leerer Title schadet dem SEO-Ranking und sieht in Suchergebnissen unprofessionell aus.',
                'recommendation' => 'Füge einen beschreibenden Titel hinzu (50-60 Zeichen optimal).',
            ];
        }

        // Check für japanische/chinesische Zeichen (Hack-Indikator)
        if (preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $title)) {
            return [
                'type' => 'title_hijacked',
                'severity' => 'critical',
                'title' => 'Title möglicherweise gehackt',
                'description' => 'Fremdsprachige Zeichen im Title gefunden: "' . mb_substr($title, 0, 50) . '..."',
                'explanation' => 'Japanische oder chinesische Zeichen in einem deutschen Title deuten oft auf einen SEO-Spam-Hack hin.',
                'recommendation' => 'Überprüfe sofort die Website auf Malware. Ändere alle Passwörter (FTP, CMS, Datenbank).',
            ];
        }

        // Untitled oder Placeholder-Title
        if (in_array(strtolower($title), ['untitled', 'home', 'willkommen', 'startseite', 'index'])) {
            return [
                'type' => 'generic_title',
                'severity' => 'info',
                'title' => 'Generischer Title',
                'description' => 'Der Title "' . $title . '" ist nicht optimal.',
                'explanation' => 'Generische Titles wie "Home" verschwenden SEO-Potenzial.',
                'recommendation' => 'Verwende einen einzigartigen, beschreibenden Title mit relevanten Keywords.',
            ];
        }

        return null;
    }

    private function checkSpamKeywords(string $bodyLower): array
    {
        $issues = [];

        foreach ($this->spamKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($bodyLower, $keyword)) {
                    // Kontext extrahieren (50 Zeichen vor und nach)
                    $pos = strpos($bodyLower, $keyword);
                    $start = max(0, $pos - 40);
                    $context = substr($bodyLower, $start, 100);
                    $context = preg_replace('/\s+/', ' ', $context); // Whitespace normalisieren
                    
                    $categoryNames = [
                        'pharma' => 'Pharma-Spam',
                        'gambling' => 'Glücksspiel-Spam',
                        'adult' => 'Adult-Content',
                        'counterfeit' => 'Fälschungs-Spam',
                        'crypto_scam' => 'Krypto-Betrug',
                    ];

                    $issues[] = [
                        'type' => 'spam_keyword',
                        'severity' => 'critical',
                        'title' => $categoryNames[$category] . ' erkannt',
                        'description' => 'Verdächtiges Keyword gefunden: "' . $keyword . '"',
                        'context' => '..."' . trim($context) . '"...',
                        'explanation' => 'Solche Keywords deuten auf eine gehackte Website oder SEO-Spam hin. Suchmaschinen können die Seite abstrafen.',
                        'recommendation' => 'Durchsuche den Quellcode nach diesem Begriff. Prüfe, ob die Seite gehackt wurde. Scanne mit einem Malware-Scanner.',
                    ];
                }
            }
        }

        return $issues;
    }

    private function checkSuspiciousLinks(Crawler $crawler, string $ownUrl): array
    {
        $issues = [];
        $ownHost = parse_url($ownUrl, PHP_URL_HOST);

        foreach ($crawler->filter('a[href]') as $element) {
            $node = new Crawler($element);
            $href = $node->attr('href');
            if (!$href || str_starts_with($href, '#') || str_starts_with($href, '/')) {
                continue;
            }

            $linkHost = parse_url($href, PHP_URL_HOST);
            if (!$linkHost || $linkHost === $ownHost) {
                continue;
            }

            // URL-Shortener erkennen
            foreach (['bit.ly', 'tinyurl.com', 'goo.gl', 't.co', 'ow.ly'] as $shortener) {
                if (str_contains($linkHost, $shortener)) {
                    $issues[] = [
                        'type' => 'url_shortener',
                        'severity' => 'warning',
                        'title' => 'URL-Shortener gefunden',
                        'description' => 'Link zu: ' . mb_substr($href, 0, 60),
                        'explanation' => 'URL-Shortener verstecken das echte Ziel. Hacker nutzen sie, um verdächtige Links zu tarnen.',
                        'recommendation' => 'Ersetze den Shortener durch den direkten Link oder entferne den Link, falls unbekannt.',
                    ];
                    continue 2;
                }
            }

            // Verdächtige TLDs
            foreach (['.ru', '.cn', '.tk', '.ml', '.ga', '.cf'] as $tld) {
                if (str_ends_with($linkHost, $tld)) {
                    $issues[] = [
                        'type' => 'suspicious_tld',
                        'severity' => 'warning',
                        'title' => 'Link zu verdächtiger Domain',
                        'description' => 'Externer Link zu: ' . $linkHost,
                        'explanation' => 'Diese TLDs werden oft für Spam oder Phishing verwendet.',
                        'recommendation' => 'Überprüfe, ob dieser Link von dir gewollt ist. Wenn nicht, wurde die Seite möglicherweise gehackt.',
                    ];
                    continue 2;
                }
            }
        }

        return $issues;
    }


    private function checkHiddenContent(string $body): ?array
    {
        // Suche nach display:none oder visibility:hidden mit Textinhalt
        if (preg_match('/<[^>]+style=["\'][^"\']*(?:display:\s*none|visibility:\s*hidden)[^"\']*["\'][^>]*>([^<]{20,})/i', $body, $matches)) {
            $hiddenText = trim(strip_tags($matches[1]));
            $hiddenText = mb_substr($hiddenText, 0, 100);
            
            return [
                'type' => 'hidden_content',
                'severity' => 'critical',
                'title' => 'Versteckter Content gefunden',
                'description' => 'Text in verstecktem Element: "' . $hiddenText . '..."',
                'explanation' => 'Versteckter Text ist eine Black-Hat-SEO-Technik. Suchmaschinen bestrafen dies mit Ranking-Verlust.',
                'recommendation' => 'Entferne den versteckten Content. Prüfe, ob die Seite gehackt wurde.',
            ];
        }

        return null;
    }

    private function checkIframes(Crawler $crawler): array
    {
        $issues = [];

        foreach ($crawler->filter('iframe') as $element) {
            $node = new Crawler($element);
            $src = $node->attr('src');
            if (!$src) continue;

            // Bekannte Safe-Domains
            $safeDomains = ['vimeo.com', 'facebook.com', 'twitter.com'];
            $isSafe = false;
            foreach ($safeDomains as $safe) {
                if (str_contains($src, $safe)) {
                    $isSafe = true;
                    break;
                }
            }

            if (!$isSafe) {
                $issues[] = [
                    'type' => 'suspicious_iframe',
                    'severity' => 'warning',
                    'title' => 'Verdächtiger Iframe gefunden',
                    'description' => 'Iframe lädt: ' . mb_substr($src, 0, 80),
                    'explanation' => 'Unbekannte Iframes können Malware, Phishing-Seiten oder Tracking-Scripts laden.',
                    'recommendation' => 'Prüfe, ob dieser Iframe gewollt ist. Entferne ihn, wenn du die Quelle nicht kennst.',
                ];
            }
        }

        return $issues;
    }


    private function checkMetaRedirect(Crawler $crawler): ?array
    {
        $metaRefresh = $crawler->filter('meta[http-equiv="refresh"]');
        
        if ($metaRefresh->count() > 0) {
            $content = $metaRefresh->attr('content') ?? '';
            
            return [
                'type' => 'meta_redirect',
                'severity' => 'warning',
                'title' => 'Meta-Refresh Redirect gefunden',
                'description' => 'Redirect konfiguriert: ' . mb_substr($content, 0, 60),
                'explanation' => 'Meta-Refresh Redirects sind veraltet und können für Cloaking missbraucht werden.',
                'recommendation' => 'Ersetze Meta-Refresh durch einen serverseitigen 301-Redirect.',
            ];
        }

        return null;
    }

    private function checkSearchConsoleVerification(string $bodyLower): ?array
    {
        if (str_contains($bodyLower, 'search-engine-verification')) {
            // Nur Info – das ist gut
            return [
                'type' => 'search_console_verification',
                'severity' => 'info',
                'title' => 'Search Console Verifizierung vorhanden',
                'description' => 'Die Seite ist für die Search Console verifiziert.',
                'explanation' => 'Das ist positiv und zeigt, dass der Betreiber Zugang zur Search Console hat.',
                'recommendation' => 'Keine Aktion erforderlich.',
            ];
        }

        return null;
    }

    private function checkSuspiciousScripts(Crawler $crawler): array
    {
        $issues = [];

        foreach ($crawler->filter('script[src]') as $element) {
            $node = new Crawler($element);
            $src = $node->attr('src');
            if (!$src) continue;

            // Bekannte Safe-Domains (CDN/Social)
            $safeDomains = [
                'cloudflare.com', 'jquery.com', 'jsdelivr.net', 'unpkg.com',
                'facebook.net', 'twitter.com', 'stripe.com', 'paypal.com'
            ];
            
            $host = parse_url($src, PHP_URL_HOST);
            if (!$host) continue;

            $isSafe = false;
            foreach ($safeDomains as $safe) {
                if (str_contains($host, $safe)) {
                    $isSafe = true;
                    break;
                }
            }

            if (!$isSafe) {
                // Verdächtige TLDs
                foreach (['.ru', '.cn', '.tk', '.ml', '.ga', '.cf'] as $tld) {
                    if (str_ends_with($host, $tld)) {
                        $issues[] = [
                            'type' => 'suspicious_script',
                            'severity' => 'critical',
                            'title' => 'Verdächtiges externes Script',
                            'description' => 'Script lädt von: ' . $host,
                            'explanation' => 'Externe Scripts von verdächtigen Domains können Malware oder Cryptominer enthalten.',
                            'recommendation' => 'Entferne dieses Script sofort, wenn du es nicht selbst eingebaut hast. Prüfe die Seite auf Hacks.',
                        ];
                        break;
                    }
                }
            }
        }

        return $issues;
    }
}


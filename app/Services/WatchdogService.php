<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class WatchdogService
{
    /**
     * Categorized spam keywords
     */
    protected array $spamKeywords = [
        'pharma' => ['viagra', 'cialis', 'levitra', 'pharmacy', 'pills online', 'buy medication'],
        'gambling' => ['casino', 'poker online', 'betting', 'slots', 'jackpot', 'roulette'],
        'adult' => ['porn', 'xxx', 'adult content', 'sex video', 'webcam girls'],
        'counterfeit' => ['replica watches', 'cheap jerseys', 'louis vuitton', 'gucci outlet', 'fake rolex'],
        'crypto_scam' => ['buy bitcoin', 'crypto investment', 'guaranteed returns', 'double your bitcoin'],
    ];

    /**
     * Suspicious external domains
     */
    protected array $suspiciousDomains = [
        'bit.ly', 'tinyurl.com', 'goo.gl', // URL-Shortener
        '.ru', '.cn', '.tk', '.ml', '.ga', // Known spam TLDs
    ];

    /**
     * Scans a domain for security issues
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
                        'title' => 'Website unreachable',
                        'description' => 'The website could not be loaded (HTTP ' . $response->status() . ').',
                        'explanation' => 'SpectoraBot cannot crawl the page. This significantly harms SEO ranking.',
                        'recommendation' => 'Check if the website is online and if SpectoraBot is not being blocked (robots.txt, .htaccess).',
                    ]],
                    'summary' => ['critical' => 1, 'warning' => 0, 'info' => 0]
                ];
            }

            $body = $response->body();
            $bodyLower = strtolower($body);
            $crawler = new Crawler($body);

            // ═══════════════════════════════════════════════════
            // CHECK 1: Title tag analysis
            // ═══════════════════════════════════════════════════
            $titleIssue = $this->checkTitle($crawler);
            if ($titleIssue) {
                $issues[] = $titleIssue;
                $summary[$titleIssue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 2: Spam keyword scan
            // ═══════════════════════════════════════════════════
            $keywordIssues = $this->checkSpamKeywords($bodyLower);
            foreach ($keywordIssues as $issue) {
                $issues[] = $issue;
                $summary[$issue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 3: Suspicious external links
            // ═══════════════════════════════════════════════════
            $linkIssues = $this->checkSuspiciousLinks($crawler, $url);
            foreach ($linkIssues as $issue) {
                $issues[] = $issue;
                $summary[$issue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 4: Hidden content (display:none with text)
            // ═══════════════════════════════════════════════════
            $hiddenIssue = $this->checkHiddenContent($body);
            if ($hiddenIssue) {
                $issues[] = $hiddenIssue;
                $summary[$hiddenIssue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 5: Suspicious iframes
            // ═══════════════════════════════════════════════════
            $iframeIssues = $this->checkIframes($crawler);
            foreach ($iframeIssues as $issue) {
                $issues[] = $issue;
                $summary[$issue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 6: Meta refresh redirect
            // ═══════════════════════════════════════════════════
            $redirectIssue = $this->checkMetaRedirect($crawler);
            if ($redirectIssue) {
                $issues[] = $redirectIssue;
                $summary[$redirectIssue['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 7: Search Console verification
            // ═══════════════════════════════════════════════════
            $verificationInfo = $this->checkSearchConsoleVerification($bodyLower);
            if ($verificationInfo) {
                $issues[] = $verificationInfo;
                $summary[$verificationInfo['severity']]++;
            }

            // ═══════════════════════════════════════════════════
            // CHECK 8: Suspicious external scripts
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
                    'title' => 'Scan failed',
                    'description' => 'Error during scan: ' . $e->getMessage(),
                    'explanation' => 'The watchdog could not fully analyze the page.',
                    'recommendation' => 'Check if the URL is correct and the page is reachable.',
                ]],
                'summary' => ['critical' => 0, 'warning' => 1, 'info' => 0]
            ];
        }

        // Determine overall status
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
    // INDIVIDUAL CHECK METHODS
    // ═══════════════════════════════════════════════════════════

    private function checkTitle(Crawler $crawler): ?array
    {
        $titleNode = $crawler->filter('title');
        
        if ($titleNode->count() === 0) {
            return [
                'type' => 'missing_title',
                'severity' => 'warning',
                'title' => 'No title tag found',
                'description' => 'The page has no <title> tag.',
                'explanation' => 'The title tag is essential for SEO and is displayed in search results.',
                'recommendation' => 'Add a descriptive <title> tag in the <head> section.',
            ];
        }

        $title = trim($titleNode->text());

        if (empty($title)) {
            return [
                'type' => 'empty_title',
                'severity' => 'warning',
                'title' => 'Empty title tag',
                'description' => 'The <title> tag is empty.',
                'explanation' => 'An empty title harms SEO ranking and looks unprofessional in search results.',
                'recommendation' => 'Add a descriptive title (50-60 characters optimal).',
            ];
        }

        // Check for Japanese/Chinese characters (hack indicator)
        if (preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $title)) {
            return [
                'type' => 'title_hijacked',
                'severity' => 'critical',
                'title' => 'Title possibly hijacked',
                'description' => 'Foreign characters found in title: "' . mb_substr($title, 0, 50) . '..."',
                'explanation' => 'Japanese or Chinese characters in an English title often indicate an SEO spam hack.',
                'recommendation' => 'Check the website for malware immediately. Change all passwords (FTP, CMS, Database).',
            ];
        }

        // Untitled or placeholder title
        if (in_array(strtolower($title), ['untitled', 'home', 'welcome', 'startpage', 'index'])) {
            return [
                'type' => 'generic_title',
                'severity' => 'info',
                'title' => 'Generic title',
                'description' => 'The title "' . $title . '" is not optimal.',
                'explanation' => 'Generic titles like "Home" waste SEO potential.',
                'recommendation' => 'Use a unique, descriptive title with relevant keywords.',
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
                    // Extract context (50 characters before and after)
                    $pos = strpos($bodyLower, $keyword);
                    $start = max(0, $pos - 40);
                    $context = substr($bodyLower, $start, 100);
                    $context = preg_replace('/\s+/', ' ', $context); // Normalize whitespace
                    
                    $categoryNames = [
                        'pharma' => 'Pharma spam detected',
                        'gambling' => 'Gambling spam detected',
                        'adult' => 'Adult content detected',
                        'counterfeit' => 'Counterfeit spam detected',
                        'crypto_scam' => 'Crypto scam detected',
                    ];

                    $issues[] = [
                        'type' => 'spam_keyword',
                        'severity' => 'critical',
                        'title' => $categoryNames[$category],
                        'description' => 'Suspicious keyword found: "' . $keyword . '"',
                        'context' => '..."' . trim($context) . '"...',
                        'explanation' => 'Such keywords indicate a hijacked website or SEO spam. Search engines may penalize the page.',
                        'recommendation' => 'Search the source code for this term. Check if the page has been hijacked. Scan with a malware scanner.',
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

            // Detect URL shorteners
            foreach (['bit.ly', 'tinyurl.com', 'goo.gl', 't.co', 'ow.ly'] as $shortener) {
                if (str_contains($linkHost, $shortener)) {
                    $issues[] = [
                        'type' => 'url_shortener',
                        'severity' => 'warning',
                        'title' => 'URL shortener found',
                        'description' => 'Link to: ' . mb_substr($href, 0, 60),
                        'explanation' => 'URL shorteners hide the real destination. Hackers use them to disguise suspicious links.',
                        'recommendation' => 'Replace the shortener with the direct link or remove the link if unknown.',
                    ];
                    continue 2;
                }
            }

            // Suspicious TLDs
            foreach (['.ru', '.cn', '.tk', '.ml', '.ga', '.cf'] as $tld) {
                if (str_ends_with($linkHost, $tld)) {
                    $issues[] = [
                        'type' => 'suspicious_tld',
                        'severity' => 'warning',
                        'title' => 'Link to suspicious domain',
                        'description' => 'External link to: ' . $linkHost,
                        'explanation' => 'These TLDs are often used for spam or phishing.',
                        'recommendation' => 'Verify if this link is intentional. If not, the page may have been hijacked.',
                    ];
                    continue 2;
                }
            }
        }

        return $issues;
    }


    private function checkHiddenContent(string $body): ?array
    {
        // Search for display:none or visibility:hidden with text content
        if (preg_match('/<[^>]+style=["\'][^"\']*(?:display:\s*none|visibility:\s*hidden)[^"\']*["\'][^>]*>([^<]{20,})/i', $body, $matches)) {
            $hiddenText = trim(strip_tags($matches[1]));
            $hiddenText = mb_substr($hiddenText, 0, 100);
            
            return [
                'type' => 'hidden_content',
                'severity' => 'critical',
                'title' => 'Hidden content found',
                'description' => 'Text in hidden element: "' . $hiddenText . '..."',
                'explanation' => 'Hidden text is a black-hat SEO technique. Search engines penalize this with ranking loss.',
                'recommendation' => 'Remove the hidden content. Check if the page has been hijacked.',
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

            // Known safe domains
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
                    'title' => 'Suspicious iframe found',
                    'description' => 'Iframe loads: ' . mb_substr($src, 0, 80),
                    'explanation' => 'Unknown iframes can load malware, phishing pages, or tracking scripts.',
                    'recommendation' => 'Verify if this iframe is intentional. Remove it if you don\'t know the source.',
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
                'title' => 'Meta-refresh redirect found',
                'description' => 'Redirect configured: ' . mb_substr($content, 0, 60),
                'explanation' => 'Meta-refresh redirects are deprecated and can be abused for cloaking.',
                'recommendation' => 'Replace meta-refresh with a server-side 301 redirect.',
            ];
        }

        return null;
    }

    private function checkSearchConsoleVerification(string $bodyLower): ?array
    {
        if (str_contains($bodyLower, 'search-engine-verification')) {
            // Info only – this is good
            return [
                'type' => 'search_console_verification',
                'severity' => 'info',
                'title' => 'Search Console verification found',
                'description' => 'The page is verified for Search Console.',
                'explanation' => 'This is positive and indicates that the owner has access to Search Console.',
                'recommendation' => 'No action required.',
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

            // Known safe domains (CDN/Social)
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
                // Suspicious TLDs
                foreach (['.ru', '.cn', '.tk', '.ml', '.ga', '.cf'] as $tld) {
                    if (str_ends_with($host, $tld)) {
                        $issues[] = [
                            'type' => 'suspicious_script',
                            'severity' => 'critical',
                            'title' => 'Suspicious external script',
                            'description' => 'Script loads from: ' . $host,
                            'explanation' => 'External scripts from suspicious domains can contain malware or cryptominers.',
                            'recommendation' => 'Remove this script immediately if you didn\'t install it yourself. Check the page for hacks.',
                        ];
                        break;
                    }
                }
            }
        }

        return $issues;
    }
}


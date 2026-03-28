<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\MonitoredUrl;
use App\Services\SitemapService;
use App\Services\MonitoringFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class DomainController extends Controller
{
    public function updateSettings(Request $request, Domain $domain)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'only_check_public_pages' => 'boolean',
            'respect_robots_txt' => 'boolean',
            'respect_noindex' => 'boolean',
            'exclude_patterns' => 'nullable|string',
            'included_sitemaps' => 'nullable|array',
        ]);

        $domain->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Settings updated successfully']);
        }

        return back()->with('status', 'Settings saved.');
    }

    public function detectSitemaps(Domain $domain, SitemapService $sitemapService)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $sitemaps = $sitemapService->discover($domain->url);
        
        $domain->update([
            'sitemap_urls' => $sitemaps
        ]);

        return response()->json([
            'message' => count($sitemaps) . ' sitemaps found.',
            'sitemaps' => $sitemaps
        ]);
    }

    public function scanUrls(Domain $domain, SitemapService $sitemapService, MonitoringFilterService $filterService)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $allUrls = [];
        $domainHost = preg_replace('/^www\./', '', parse_url($domain->url, PHP_URL_HOST));

        // 1. Scan Homepage Links
        try {
            $response = Http::timeout(10)->withUserAgent('SpectoraBot/1.0')->get($domain->url);
            if ($response->successful()) {
                $crawler = new Crawler($response->body());
                $crawler->filter('a[href]')->each(function (Crawler $node) use (&$allUrls, $domainHost, $domain) {
                    $href = $node->attr('href');
                    if (!$href || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) return;
                    
                    // Normalize relative links
                    if (str_starts_with($href, '//')) {
                        $href = (parse_url($domain->url, PHP_URL_SCHEME) ?: 'https') . ':' . $href;
                    } elseif (str_starts_with($href, '/')) {
                        $href = rtrim($domain->url, '/') . $href;
                    }
                    
                    $urlHost = preg_replace('/^www\./', '', parse_url($href, PHP_URL_HOST));
                    if ($urlHost === $domainHost) {
                        $allUrls[] = rtrim($href, '/');
                    }
                });
            }
        } catch (\Exception $e) { 
            \Illuminate\Support\Facades\Log::warning("Homepage scan failed for {$domain->url}: " . $e->getMessage());
        }

        // 2. Scan Sitemaps
        $sitemapsToScan = $domain->included_sitemaps;
        
        // If no sitemaps selected, try auto-discovery to give user some results
        if (empty($sitemapsToScan)) {
            $sitemapsToScan = $sitemapService->discover($domain->url);
        }

        foreach ($sitemapsToScan as $sitemapUrl) {
            try {
                $parsed = $sitemapService->parse($sitemapUrl);
                if (!empty($parsed['items'])) {
                    foreach ($parsed['items'] as $item) {
                         $allUrls[] = rtrim($item, '/');
                    }
                }
            } catch (\Exception $e) { /* ignore single sitemap failure */ }
        }

        // 3. Unique & Clean
        $uniqueUrls = array_unique($allUrls);
        $results = [];

        foreach ($uniqueUrls as $url) {
            // Check if already monitored (normalize both for comparison)
            $existing = $domain->monitoredUrls()
                ->where('url', $url)
                ->orWhere('url', $url . '/')
                ->first();
            
            // Check if "public" per filter
            $filter = $filterService->shouldCheck($domain, $url);
            
            $results[] = [
                'url' => $url,
                'is_monitored' => $existing ? $existing->is_active : false,
                'is_public' => $filter['should_check'],
                'skip_reason' => $filter['should_check'] ? null : $filter['reason'],
            ];
        }

        return response()->json([
            'urls' => array_values($results)
        ]);
    }

    public function syncMonitoredUrls(Request $request, Domain $domain)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'urls' => 'required|array',
            'urls.*.url' => 'required|string',
            'urls.*.is_monitored' => 'required|boolean',
        ]);

        foreach ($validated['urls'] as $urlData) {
            $domain->monitoredUrls()->updateOrCreate(
                ['url' => rtrim($urlData['url'], '/')],
                ['is_active' => $urlData['is_monitored']]
            );
        }

        return response()->json(['message' => 'URLs synchronized.']);
    }

    public function history(Domain $domain)
    {
        // Ensure the user owns the domain
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $showOnlyErrors = request()->has('only_errors');
        $dateFilter = request()->input('date');

        $query = $domain->history()->orderBy('created_at', 'desc');

        if ($showOnlyErrors) {
            $query->where(function ($q) {
                $q->where('status_code', '>=', 400)
                  ->orWhere('status_code', 0)
                  ->orWhereNull('status_code');
            });
        }

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        $checks = $query->paginate(20);

        // Prepare data for Chart.js (Last 50 checks, chronological order)
        // If date filter is active, show chart for that day? Or keep global trend?
        // User asked to "filter history", usually implies the list. 
        // Let's filter the chart too if a date is selected, to show the trend OF THAT DAY.
        
        $chartQuery = $domain->history()->orderBy('created_at', 'desc');
        if ($dateFilter) {
            $chartQuery->whereDate('created_at', $dateFilter);
            $chartData = $chartQuery->get()->reverse(); // Get all for that day
        } else {
            $chartData = $chartQuery->take(50)->get()->reverse();
        }

        $labels = $chartData->map(fn($check) => $check->created_at->format('d.m. H:i'))->values();
        $dataPoints = $chartData->pluck('response_time')->values();

        return view('domains.history', compact('domain', 'checks', 'labels', 'dataPoints', 'showOnlyErrors', 'dateFilter'));
    }

    public function show(Domain $domain)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        // --- 1. Analytics Data (Last 30 Days) ---
        $days = 30;
        $startDate = now()->subDays($days);

        $analyticsData = \App\Models\AnalyticsVisit::where('domain_id', $domain->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, count(*) as pageviews, count(distinct visitor_hash) as visitors')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill missing dates for chart
        $chartData = [];
        $currentDate = clone $startDate;
        $now = now();
        
        $analyticsKeyed = $analyticsData->keyBy('date');

        while ($currentDate <= $now) {
            $dateStr = $currentDate->format('Y-m-d');
            $record = $analyticsKeyed->get($dateStr);
            $chartData[] = [
                'date' => $currentDate->format('d.m.'),
                'visitors' => $record ? $record->visitors : 0,
                'pageviews' => $record ? $record->pageviews : 0,
            ];
            $currentDate->addDay();
        }

        $chartLabels = array_column($chartData, 'date');
        $chartVisitors = array_column($chartData, 'visitors');
        $chartPageviews = array_column($chartData, 'pageviews');

        // Top Pages
        $topPages = \App\Models\AnalyticsVisit::where('domain_id', $domain->id)
            ->where('created_at', '>=', $startDate)
            ->select('url', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('url')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Top Sources
        $topSources = \App\Models\AnalyticsVisit::where('domain_id', $domain->id)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('referrer_domain')
            ->select('referrer_domain', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('referrer_domain')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Devices
        $devices = \App\Models\AnalyticsVisit::where('domain_id', $domain->id)
            ->where('created_at', '>=', $startDate)
            ->select('device', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('device')
            ->get();
        
        $deviceLabels = $devices->pluck('device');
        $deviceData = $devices->pluck('total');
        
        // Prepare keyed stats for legend (e.g. ['desktop' => 70, 'mobile' => 20, ...])
        $totalDeviceVisits = $deviceData->sum();
        $deviceStats = $devices->mapWithKeys(function($d) use ($totalDeviceVisits) {
            return [strtolower($d->device) => $totalDeviceVisits > 0 ? round(($d->total / $totalDeviceVisits) * 100) : 0];
        })->toArray();


        // --- 2. History & KPIs ---
        // Uptime (Last 30 days based on checks)
        $totalChecks = $domain->history()->where('created_at', '>=', $startDate)->count();
        $failedChecks = $domain->history()->where('created_at', '>=', $startDate)->where('status_code', '>=', 400)->count();
        $uptime = $totalChecks > 0 ? round((($totalChecks - $failedChecks) / $totalChecks) * 100, 2) : 100;

        // Avg Response Time (24h)
        $avgResponseTime = $domain->history()
            ->where('created_at', '>=', now()->subDay())
            ->avg('response_time');
        
        // Stored as seconds in history, so convert to ms for the dashboard
        $avgResponseTime = round(($avgResponseTime ?? 0) * 1000);

        // Recent Checks (Logbook)
        $recentChecks = $domain->history()->orderBy('created_at', 'desc')->paginate(20);
        
        // Monitored URLs for the Overview tab
        $monitoredUrls = $domain->monitoredUrls()->where('is_active', true)->get();

        // History Chart (Response Time) - Align with Analytics Chart if possible
        $historyChartData = $domain->history()->orderBy('created_at', 'desc')->take(50)->get()->reverse();
        $historyLabels = $historyChartData->map(fn($h) => $h->created_at->format('d.m. H:i'))->values();
        $historyResponseTimes = $historyChartData->map(fn($h) => round($h->response_time * 1000))->values();


        // --- 3. Performance & Security ---
        // SSL
        $sslDaysRemaining = $domain->ssl_days_left ?? 0;

        // PageSpeed History for Chart
        $psHistory = $domain->history()
            ->whereNotNull('pagespeed_score_desktop')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->reverse();
        $psHistoryLabels = $psHistory->map(fn($h) => $h->created_at->format('d.m.'))->values();
        $psHistoryScores = $psHistory->pluck('pagespeed_score_desktop')->values();

        // Main Performance Score
        $score = $domain->pagespeed_score_desktop ?? 0;
        $scoreColor = $score >= 90 ? 'emerald' : ($score >= 50 ? 'amber' : 'rose');

        // Watchdog / Security
        $watchdogData = $domain->safety_details['watchdog'] ?? [];

        // --- 4. Notes ---
        $notes = $domain->notes()->orderBy('created_at', 'desc')->get();

        // --- 5. Security & Audit Summary (Restored Fix) ---
        $auditDetails = $domain->last_pagespeed_details ?? [];
        $criticalCount = collect($auditDetails)->where('status', 'error')->count();
        $warningCount = collect($auditDetails)->where('status', 'warning')->count();

        return view('domains.dashboard', compact(
            'domain',
            // Analytics
            'chartLabels', 'chartVisitors', 'chartPageviews', 'topPages', 'topSources', 'deviceLabels', 'deviceData', 'deviceStats',
            // Technical
            'uptime', 'avgResponseTime', 'sslDaysRemaining', 'recentChecks', 'monitoredUrls',
            'historyLabels', 'historyResponseTimes', 'psHistoryLabels', 'psHistoryScores',
            // Security/Audit
            'criticalCount', 'warningCount', 'auditDetails', 'score', 'scoreColor', 'watchdogData',
            // Notes
            'notes'
        ));
    }

    public function analyze(Request $request, Domain $domain)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        // Dispatch Jobs Synchronously
        \App\Jobs\PerformSpectoraAudit::dispatchSync($domain);
        \App\Jobs\CheckDomainJob::dispatchSync($domain);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Analysis started']);
        }

        return back()->with('status', "Spectora audit started. Refresh in a few seconds.");
    }

    public function status(Domain $domain)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        // Fetch History for Chart
        $history = $domain->history()
            ->whereNotNull('pagespeed_score_desktop')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->reverse();

        return response()->json([
            'pagespeed_mobile' => $domain->pagespeed_score,
            'pagespeed_desktop' => $domain->pagespeed_score_desktop,
            'updated_at' => $domain->updated_at->toIso8601String(),
            'details' => $domain->last_pagespeed_details,
            'history_labels' => $history->map(fn($h) => $h->created_at->setTimezone('Europe/Berlin')->format('d.m. H:i'))->values(),
            'history_scores' => $history->pluck('pagespeed_score_desktop')->values(),
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|string',
            'keyword_must_contain' => 'nullable|string',
            'keyword_must_not_contain' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $url = trim($request->url);
        if (!preg_match('#^https?://#', $url)) {
            $url = 'https://' . $url;
        }

        // Check for duplicates
        if (Domain::where('user_id', $user->id)->where('url', $url)->exists()) {
             return back()->withErrors(['url' => 'You are already monitoring this domain.']);
        }

        $domain = Domain::create([
            'user_id' => $user->id,
            'url' => $url,
            'keyword_must_contain' => $request->keyword_must_contain,
            'keyword_must_not_contain' => $request->keyword_must_not_contain,
        ]);

        // Dispatch Job
        \App\Jobs\PerformSpectoraAudit::dispatchSync($domain);

        return redirect()->route('dashboard')->with('status', 'Domain successfully added!');
    }

    public function destroy(Domain $domain)
    {
        $domain->delete();

        return redirect()->route('dashboard')->with('status', 'Domain deleted.');
    }
}

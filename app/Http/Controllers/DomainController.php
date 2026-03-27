<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DomainController extends Controller
{
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


        // --- 2. History & KPIs ---
        // Uptime (Last 30 days based on checks)
        $totalChecks = $domain->history()->where('created_at', '>=', $startDate)->count();
        $failedChecks = $domain->history()->where('created_at', '>=', $startDate)->where('status_code', '>=', 400)->count();
        $uptime = $totalChecks > 0 ? round((($totalChecks - $failedChecks) / $totalChecks) * 100, 2) : 100;

        // Avg Response Time (24h)
        $avgResponseTime = $domain->history()
            ->where('created_at', '>=', now()->subDay())
            ->avg('response_time');
        
        // Assuming response_time is in seconds (e.g., 0.123), convert to ms
        // If it's already in ms (e.g. 123), this would make it huge, but previous dashboard showed 's', so likely seconds.
        $avgResponseTime = round(($avgResponseTime ?? 0) * 1000);

        // Recent Checks (Logbook)
        $recentChecks = $domain->history()->orderBy('created_at', 'desc')->paginate(20);

        // History Chart (Response Time) - Align with Analytics Chart if possible, or just last 50 checks
        // Let's use last 50 checks for the "Technical" tab graph
        $historyChartData = $domain->history()->orderBy('created_at', 'desc')->take(50)->get()->reverse();
        $historyLabels = $historyChartData->map(fn($h) => $h->created_at->format('d.m. H:i'))->values();
        $historyResponseTimes = $historyChartData->pluck('response_time')->values();


        // --- 3. Performance & Security ---
        // SSL
        $sslDaysRemaining = $domain->ssl_days_left ?? 0; // Assuming this attribute exists or is calculated

        // PageSpeed History for Chart
        $psHistory = $domain->history()
            ->whereNotNull('pagespeed_score_desktop')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->reverse();
        $psHistoryLabels = $psHistory->map(fn($h) => $h->created_at->format('d.m.'))->values();
        $psHistoryScores = $psHistory->pluck('pagespeed_score_desktop')->values();


        // --- 4. Notes ---
        $notes = $domain->notes()->orderBy('created_at', 'desc')->get();

        return view('domains.dashboard', compact(
            'domain',
            // Analytics
            'chartLabels', 'chartVisitors', 'chartPageviews',
            'topPages', 'topSources', 'deviceLabels', 'deviceData',
            // KPIs
            'uptime', 'avgResponseTime', 'sslDaysRemaining',
            // History
            'recentChecks', 'historyLabels', 'historyResponseTimes',
            // Performance
            'psHistoryLabels', 'psHistoryScores',
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

        return back()->with('status', "Spectora Audit gestartet. Aktualisiere in wenigen Sekunden.");
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
            'pagespeed_mobile' => $domain->pagespeed_mobile,
            'pagespeed_desktop' => $domain->pagespeed_desktop,
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
             return back()->withErrors(['url' => 'Du überwachst diese Domain bereits.']);
        }

        $domain = Domain::create([
            'user_id' => $user->id,
            'url' => $url,
            'keyword_must_contain' => $request->keyword_must_contain,
            'keyword_must_not_contain' => $request->keyword_must_not_contain,
        ]);

        // Dispatch Job
        \App\Jobs\PerformSpectoraAudit::dispatchSync($domain);

        return redirect()->route('dashboard')->with('status', 'Domain erfolgreich hinzugefügt!');
    }

    public function destroy(Domain $domain)
    {
        $domain->delete();

        return redirect()->route('dashboard')->with('status', 'Domain gelöscht.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Store a new analytics event.
     */
    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|uuid|exists:domains,uuid',
            'url' => 'required|url',
            'referrer' => 'nullable|string',
            'width' => 'nullable|integer',
        ]);

        $domain = \App\Models\Domain::where('uuid', $validated['domain'])->firstOrFail();

        // Anti-Spoofing / CORS Check
        $origin = $request->header('Origin');
        $referer = $request->header('Referer');
        $domainHost = parse_url($domain->url, PHP_URL_HOST);

        // Allow localhost for development
        $isLocal = str_contains($origin, 'localhost') || str_contains($origin, '127.0.0.1') || 
                   str_contains($referer, 'localhost') || str_contains($referer, '127.0.0.1');

        if (!$isLocal) {
            $originHost = $origin ? parse_url($origin, PHP_URL_HOST) : null;
            $refererHost = $referer ? parse_url($referer, PHP_URL_HOST) : null;

            if ($originHost && $originHost !== $domainHost) {
                abort(403, 'Origin mismatch');
            }
            
            if (!$originHost && $refererHost && $refererHost !== $domainHost) {
                abort(403, 'Referer mismatch');
            }
        }

        // 1. Privacy Hashing
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? 'Unknown';
        $date = now()->format('Y-m-d');
        $salt = config('app.key');
        
        $visitorHash = hash('sha256', $ip . $userAgent . $date . $salt);

        // 2. Parsing
        $urlPath = parse_url($validated['url'], PHP_URL_PATH) ?? '/';
        
        $referrerDomain = null;
        if (!empty($validated['referrer'])) {
            $referrerDomain = parse_url($validated['referrer'], PHP_URL_HOST);
        }

        // Device Detection
        $width = $request->input('width');
        $device = 'desktop';
        if ($width && $width < 768) {
            $device = 'mobile';
        } elseif ($width && $width < 1024) {
            $device = 'tablet';
        }

        // Simple Browser/OS Detection
        $browser = $this->getBrowser($userAgent);
        $os = $this->getOs($userAgent);

        // Country (Cloudflare support)
        $country = $request->header('CF-IPCountry');

        // 3. Save
        \App\Models\AnalyticsVisit::create([
            'domain_id' => $domain->id,
            'visitor_hash' => $visitorHash,
            'url' => $validated['url'],
            'path' => $urlPath,
            'referrer' => $validated['referrer'],
            'referrer_domain' => $referrerDomain,
            'browser' => $browser,
            'os' => $os,
            'device' => $device,
            'country' => $country,
        ]);

        return response()->noContent();
    }

    private function getBrowser($ua)
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'chrome')) return 'Chrome';
        if (str_contains($ua, 'firefox')) return 'Firefox';
        if (str_contains($ua, 'safari') && !str_contains($ua, 'chrome')) return 'Safari';
        if (str_contains($ua, 'edge')) return 'Edge';
        if (str_contains($ua, 'opera') || str_contains($ua, 'opr')) return 'Opera';
        return 'Other';
    }

    private function getOs($ua)
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'windows')) return 'Windows';
        if (str_contains($ua, 'mac os')) return 'macOS';
        if (str_contains($ua, 'android')) return 'Android';
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad')) return 'iOS';
        if (str_contains($ua, 'linux')) return 'Linux';
        return 'Other';
    }
    /**
     * Display the analytics dashboard for a domain.
     */
    public function show(\App\Models\Domain $domain)
    {
        if ($domain->user_id !== \Illuminate\Support\Facades\Auth::id()) {
            abort(403);
        }

        $days = 30;
        $startDate = now()->subDays($days);

        // 1. Visits per Day (Line Chart)
        $visitsPerDay = \App\Models\AnalyticsVisit::where('domain_id', $domain->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, count(*) as total, count(distinct visitor_hash) as visitors')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 2. Top Pages
        $topPages = \App\Models\AnalyticsVisit::where('domain_id', $domain->id)
            ->where('created_at', '>=', $startDate)
            ->select('url', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('url')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // 3. Top Sources
        $topSources = \App\Models\AnalyticsVisit::where('domain_id', $domain->id)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('referrer_domain')
            ->select('referrer_domain', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('referrer_domain')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // 4. Devices
        $devices = \App\Models\AnalyticsVisit::where('domain_id', $domain->id)
            ->where('created_at', '>=', $startDate)
            ->select('device', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('device')
            ->get();

        return view('domains.analytics', compact('domain', 'visitsPerDay', 'topPages', 'topSources', 'devices'));
    }
}

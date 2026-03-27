<?php

namespace App\Services;

use App\Models\Domain;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReportService
{
    public function generatePdf(Domain $domain)
    {
        $user = $domain->user;
        
        // Determine logo path and encode as Base64
        $logoBase64 = null;
        $logoPath = null;
        
        if ($user->agency_logo_path) {
            $logoPath = public_path('storage/' . $user->agency_logo_path);
        } else {
            $logoPath = public_path('images/logo.png');
        }

        if ($logoPath && file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // Gather Data
        // 1. Uptime (Mocked or calculated)
        $uptime = '100%'; 

        // 2. Avg Response Time
        $avgResponseTime = $domain->response_time ?? 0;

        // 3. Visitors (Last 30 Days)
        $visitors = $domain->analyticsVisits()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        
        // 4. Security Status
        $securityStatus = ucfirst($domain->safety_status ?? 'Unknown');

        $data = [
            'domain' => $domain,
            'logoBase64' => $logoBase64,
            'uptime' => $uptime,
            'avgResponseTime' => $avgResponseTime,
            'visitors' => $visitors,
            'securityStatus' => $securityStatus,
            'safetyDetails' => $domain->safety_details,
            'date' => now()->format('F Y'),
        ];

        // --- Real Data for Charts (Last 30 Days) ---
        $days = collect(range(29, 0))->map(fn($days) => now()->subDays($days)->format('Y-m-d'));
        $labels = $days->map(fn($date) => \Carbon\Carbon::parse($date)->format('d.m'));

        // Helper to fetch and encode image
        $fetchChart = function($config) {
            $url = 'https://quickchart.io/chart?c=' . urlencode(json_encode($config)) . '&w=400&h=200';
            try {
                $image = file_get_contents($url);
                if ($image !== false) {
                    return 'data:image/png;base64,' . base64_encode($image);
                }
            } catch (\Exception $e) {
                // Log error or ignore
            }
            return null;
        };

        // 1. Performance (Response Time) - Line Chart
        // Fetch from history
        $responseHistory = $domain->history()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, AVG(response_time) as avg_time')
            ->groupBy('date')
            ->pluck('avg_time', 'date');

        $performanceData = $days->map(fn($date) => $responseHistory->get($date) ?? null); // Null for gaps
        
        $chartConfig1 = [
            'type' => 'line',
            'data' => [
                'labels' => $labels->toArray(),
                'datasets' => [[
                    'label' => 'Response Time (s)',
                    'data' => $performanceData->toArray(),
                    'borderColor' => '#38BDF8', // Cyan
                    'backgroundColor' => 'rgba(56, 189, 248, 0.1)',
                    'fill' => true,
                    'pointRadius' => 2,
                    'spanGaps' => true, // Connect lines over gaps
                ]]
            ],
            'options' => [
                'legend' => ['display' => false],
                'scales' => [
                    'xAxes' => [['display' => false]],
                    'yAxes' => [['display' => true, 'ticks' => ['beginAtZero' => true]]]
                ]
            ]
        ];
        $data['chartResponse'] = $fetchChart($chartConfig1);

        // 2. Visitors - Bar Chart
        $visits = $domain->analyticsVisits()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT visitor_hash) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $visitorData = $days->map(fn($date) => $visits->get($date) ?? 0); // 0 for missing days
        
        $chartConfig2 = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels->toArray(),
                'datasets' => [[
                    'label' => 'Visitors',
                    'data' => $visitorData->toArray(),
                    'backgroundColor' => '#0F172A', // Dark Blue
                ]]
            ],
            'options' => [
                'legend' => ['display' => false],
                'scales' => [
                    'xAxes' => [['display' => false]],
                    'yAxes' => [['display' => false, 'ticks' => ['beginAtZero' => true]]]
                ]
            ]
        ];
        $data['chartVisitors'] = $fetchChart($chartConfig2);

        // 3. Score Trend - Line Chart (Replacing Gauge)
        $scoreHistory = $domain->history()
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('pagespeed_score_desktop')
            ->selectRaw('DATE(created_at) as date, AVG(pagespeed_score_desktop) as score')
            ->groupBy('date')
            ->pluck('score', 'date');

        $scoreData = $days->map(fn($date) => $scoreHistory->get($date) ?? null);

        $chartConfig3 = [
            'type' => 'line',
            'data' => [
                'labels' => $labels->toArray(),
                'datasets' => [[
                    'label' => 'Spectora Score',
                    'data' => $scoreData->toArray(),
                    'borderColor' => '#10B981', // Green
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'pointRadius' => 2,
                    'spanGaps' => true,
                ]]
            ],
            'options' => [
                'legend' => ['display' => false],
                'scales' => [
                    'xAxes' => [['display' => false]],
                    'yAxes' => [['display' => true, 'ticks' => ['min' => 0, 'max' => 100]]]
                ]
            ]
        ];
        $data['chartScore'] = $fetchChart($chartConfig3);

        // --- Mock Data for "Recent Checks" Table ---
        $data['recentChecks'] = [
            ['check' => 'SSL Certificate', 'status' => 'Valid', 'time' => now()->subMinutes(5)->format('H:i')],
            ['check' => 'Homepage Availability', 'status' => 'Online', 'time' => now()->subMinutes(10)->format('H:i')],
            ['check' => 'DNS Resolution', 'status' => 'Resolved', 'time' => now()->subMinutes(15)->format('H:i')],
            ['check' => 'Server Response', 'status' => $avgResponseTime . 's', 'time' => now()->subMinutes(20)->format('H:i')],
            ['check' => 'Malware Scan', 'status' => 'Clean', 'time' => now()->subHour()->format('H:i')],
        ];

        $pdf = Pdf::loadView('reports.monthly', $data);
        $pdf->setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'sans-serif']);
        
        return $pdf;
    }
}

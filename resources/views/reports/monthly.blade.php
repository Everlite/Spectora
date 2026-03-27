<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Monthly Report - {{ $domain->url }}</title>
    <style>
        @page {
            margin: 100px 40px 60px 40px; /* Top margin for header, Bottom for footer */
        }
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333;
            background: #fff;
            margin: 0;
        }
        
        /* Fixed Header */
        header {
            position: fixed;
            top: -80px;
            left: -40px;
            right: -40px;
            height: 80px;
            background-color: #0F172A; /* Slate 900 */
            color: white;
            padding: 0 40px;
            line-height: 80px;
        }
        .header-content {
            width: 100%;
            height: 100%;
        }
        .logo-container {
            background: white;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            vertical-align: middle;
            line-height: normal;
        }
        .logo {
            max-height: 30px;
            display: block;
        }
        .report-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            vertical-align: middle;
        }
        .report-meta {
            color: #94A3B8; /* Slate 400 */
            font-size: 12px;
            float: right;
        }

        /* Fixed Footer */
        footer {
            position: fixed;
            bottom: -40px;
            left: 0px;
            right: 0px;
            height: 30px;
            font-size: 10px;
            color: #94A3B8;
            text-align: center;
            border-top: 1px solid #E2E8F0;
            padding-top: 10px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #0F172A;
            border-bottom: 2px solid #E2E8F0;
            padding-bottom: 5px;
            margin-bottom: 15px;
            margin-top: 20px;
        }
        
        /* KPI Grid */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin: 0 -10px;
        }
        .kpi-card {
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .kpi-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748B;
            font-weight: bold;
            letter-spacing: 0.05em;
            margin-bottom: 5px;
        }
        .kpi-value {
            font-size: 20px;
            font-weight: bold;
            color: #0F172A;
        }
        
        /* KPI Backgrounds */
        .bg-green { background-color: #ECFDF5; border-color: #A7F3D0; }
        .bg-blue { background-color: #EFF6FF; border-color: #BFDBFE; }
        .bg-slate { background-color: #F8FAFC; border-color: #E2E8F0; }
        .bg-orange { background-color: #FFF7ED; border-color: #FED7AA; }

        /* Charts */
        .chart-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .chart-cell {
            width: 33.33%;
            padding: 5px;
            vertical-align: top;
        }
        .chart-box {
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            background: #fff;
        }
        .chart-img {
            width: 100%;
            height: auto;
            max-height: 150px;
            object-fit: contain;
        }

        /* Details Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .details-table th {
            text-align: left;
            background: #0F172A;
            color: white;
            padding: 8px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .details-table td {
            padding: 8px;
            border-bottom: 1px solid #E2E8F0;
            color: #334155;
        }
        .details-table tr:nth-child(even) {
            background-color: #F8FAFC;
        }
    </style>
</head>
<body>
    <header>
        <table class="header-content">
            <tr>
                <td style="vertical-align: middle;">
                    <span class="report-title">Monthly Report</span>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <span style="margin-right: 15px; font-size: 14px; font-weight: normal;">{{ $domain->url }}</span>
                    @if($logoBase64)
                        <div class="logo-container">
                            <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                        </div>
                    @else
                        <span style="font-weight: bold; color: #38BDF8;">Spectora</span>
                    @endif
                </td>
            </tr>
        </table>
    </header>

    <footer>
        Generated by Spectora Monitoring &bull; {{ now()->format('d.m.Y H:i') }} &bull; Page <span class="page-number"></span>
    </footer>

    <!-- KPIs -->
    <div class="section-title" style="margin-top: 0;">Overview</div>
    <table class="kpi-table">
        <tr>
            <td width="25%">
                <div class="kpi-card bg-green">
                    <div class="kpi-label">Uptime (30d)</div>
                    <div class="kpi-value" style="color: #059669;">{{ $uptime }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card bg-blue">
                    <div class="kpi-label">Avg Response</div>
                    <div class="kpi-value" style="color: #2563EB;">{{ number_format($avgResponseTime, 2) }}s</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card bg-slate">
                    <div class="kpi-label">Visitors (30 Days)</div>
                    <div class="kpi-value">{{ $visitors }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card bg-orange">
                    <div class="kpi-label">Security</div>
                    <div class="kpi-value" style="color: {{ $securityStatus == 'Safe' ? '#059669' : '#DC2626' }};">
                        {{ $securityStatus }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Charts -->
    <div class="section-title">Performance & Traffic</div>
    <table class="chart-table">
        <tr>
            <td class="chart-cell">
                <div class="chart-box">
                    <div class="kpi-label">Response Time</div>
                    @if($chartResponse)
                        <img src="{{ $chartResponse }}" class="chart-img">
                    @else
                        <div style="padding: 20px; color: #ccc;">No Data</div>
                    @endif
                </div>
            </td>
            <td class="chart-cell">
                <div class="chart-box">
                    <div class="kpi-label">Visitors</div>
                    @if($chartVisitors)
                        <img src="{{ $chartVisitors }}" class="chart-img">
                    @else
                        <div style="padding: 20px; color: #ccc;">No Data</div>
                    @endif
                </div>
            </td>
            <td class="chart-cell">
                <div class="chart-box">
                    <div class="kpi-label">Spectora Score</div>
                    @if($chartScore)
                        <img src="{{ $chartScore }}" class="chart-img">
                    @else
                        <div style="padding: 20px; color: #ccc;">No Data</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Security Issues -->
    @if(isset($safetyDetails) && count($safetyDetails) > 0)
        <div class="section-title" style="color: #DC2626;">Security Issues Detected</div>
        <table class="details-table">
            <thead>
                <tr>
                    <th style="background: #DC2626;">Issue Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($safetyDetails as $issue)
                <tr>
                    <td style="color: #DC2626; font-weight: bold;">{{ $issue }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Recent Checks -->
    <div class="section-title">Recent Activity</div>
    <table class="details-table">
        <thead>
            <tr>
                <th>Check Name</th>
                <th>Status</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentChecks as $check)
            <tr>
                <td>{{ $check['check'] }}</td>
                <td>
                    @if($check['status'] == 'Online' || $check['status'] == 'Valid' || $check['status'] == 'Clean' || $check['status'] == 'Resolved')
                        <span style="color: #10B981; font-weight: bold;">{{ $check['status'] }}</span>
                    @else
                        {{ $check['status'] }}
                    @endif
                </td>
                <td>{{ $check['time'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

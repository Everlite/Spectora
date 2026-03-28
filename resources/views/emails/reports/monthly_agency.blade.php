<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #38BDF8; text-decoration: none; }
        .summary-box { background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 25px; text-align: center; }
        .stat-row { display: flex; justify-content: space-around; margin-top: 15px; }
        .stat-item { text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; display: block; }
        .stat-label { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .text-green { color: #10B981; }
        .text-red { color: #EF4444; }
        .problem-list { margin-bottom: 25px; }
        .problem-item { padding: 10px; border-bottom: 1px solid #eee; font-size: 14px; }
        .problem-item:last-child { border-bottom: none; }
        .btn-container { text-align: center; margin-top: 30px; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #38BDF8; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body style="background-color: #f9fafb; margin: 0; padding: 0;">
    <div class="container" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 40px; margin-bottom: 40px; padding: 40px;">
        <div class="header">
            <a href="{{ route('dashboard') }}" class="logo" style="color: #0F172A; font-size: 28px;">Spectora</a>
        </div>

        <p style="font-size: 16px; color: #4B5563;">Hello {{ $user->first_name ?? 'Partner' }},</p>
        
        <p style="font-size: 16px; color: #4B5563;">Your monthly status report is here. Last month, we monitored <strong>{{ $stats['total'] }} domains</strong> for you.</p>
        
        <div class="summary-box" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;">
            <div style="font-weight: 600; color: #64748B; margin-bottom: 15px;">Overall Status</div>
            <div class="stat-row">
                <div class="stat-item" style="margin-right: 20px;">
                    <span class="stat-value text-green" style="color: #10B981; font-size: 32px;">{{ $stats['safe'] }}</span>
                    <span class="stat-label" style="color: #64748B;">All OK</span>
                </div>
                <div class="stat-item" style="margin-left: 20px;">
                    <span class="stat-value {{ $stats['issues'] > 0 ? 'text-red' : 'text-green' }}" style="font-size: 32px; color: {{ $stats['issues'] > 0 ? '#EF4444' : '#10B981' }};">{{ $stats['issues'] }}</span>
                    <span class="stat-label" style="color: #64748B;">Needs Attention</span>
                </div>
            </div>
        </div>

        @if(count($stats['problem_domains']) > 0)
            <div class="problem-list">
                <strong style="color: #EF4444; display: block; margin-bottom: 15px;">⚠️ The following domains need attention:</strong>
                @foreach($stats['problem_domains'] as $domain)
                    <div class="problem-item" style="padding: 15px 0;">
                        <a href="{{ route('domains.show', $domain['uuid']) }}" style="color: #0F172A; text-decoration: none; font-weight: bold; font-size: 16px;">
                            {{ $domain['url'] }}
                        </a>
                        <br>
                        <span style="color: #64748B; font-size: 14px; display: block; margin-top: 4px;">{{ $domain['reason'] }}</span>
                    </div>
                @endforeach
                @if($stats['issues'] > count($stats['problem_domains']))
                    <div class="problem-item" style="color: #94A3B8; font-style: italic; padding-top: 15px;">
                        ...and {{ $stats['issues'] - count($stats['problem_domains']) }} more.
                    </div>
                @endif
            </div>
        @else
            <div style="text-align: center; padding: 30px; background-color: #ECFDF5; border-radius: 8px; color: #065F46;">
                <span style="font-size: 24px;">🎉</span><br>
                <strong>Everything is looking good!</strong><br>
                No issues detected.
            </div>
        @endif
        
        <div class="btn-container">
            <a href="{{ route('dashboard') }}" class="btn" style="background-color: #0F172A; padding: 14px 28px; border-radius: 8px; font-size: 16px;">Go to Dashboard & View Reports</a>
        </div>
        
        <div class="footer" style="border-top: 1px solid #E2E8F0; padding-top: 20px; margin-top: 40px;">
            <p style="color: #94A3B8;">This report was automatically generated.<br>
            &copy; {{ date('Y') }} Spectora Monitoring</p>
        </div>
    </div>
</body>
</html>

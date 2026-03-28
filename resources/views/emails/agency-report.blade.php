<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #38BDF8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .stats {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hello {{ $domain->user->first_name ?? 'Partner' }},</h2>
        
        <p>The monthly security check for <strong>{{ $domain->url }}</strong> has been completed.</p>
        
        <div class="stats">
            <p><strong>Current Security Status:</strong> {{ ucfirst($domain->safety_status ?? 'Unknown') }}</p>
            <p><strong>Visitors last month:</strong> {{ $visitorsCount }}</p>
        </div>
        
        <p>Attached you will find the detailed PDF for your records or to forward to the client.</p>
        
        <p>Best regards,<br>Your Monitoring Team</p>
    </div>
</body>
</html>

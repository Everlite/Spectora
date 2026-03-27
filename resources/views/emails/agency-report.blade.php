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
        <h2>Hallo {{ $domain->user->first_name ?? 'Partner' }},</h2>
        
        <p>Der monatliche Sicherheits-Check für <strong>{{ $domain->url }}</strong> ist abgeschlossen.</p>
        
        <div class="stats">
            <p><strong>Aktueller Sicherheits-Status:</strong> {{ ucfirst($domain->safety_status ?? 'Unbekannt') }}</p>
            <p><strong>Besucher letzten Monat:</strong> {{ $visitorsCount }}</p>
        </div>
        
        <p>Anbei findest du das detaillierte PDF für deine Unterlagen oder zum Weiterleiten an den Kunden.</p>
        
        <p>Beste Grüße,<br>Dein Monitoring Team</p>
    </div>
</body>
</html>

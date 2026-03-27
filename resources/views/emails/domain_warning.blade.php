<!DOCTYPE html>
<html>
<head>
    <title>Domain Warning</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .issue { margin-bottom: 10px; }
        .danger { color: #ef4444; }
        .warning { color: #f59e0b; }
    </style>
</head>
<body>
    <p>Hallo,</p>
    <p>deine Website <strong>{{ $domain->url }}</strong> meldet folgende Warnung:</p>
    
    <ul>
        @foreach($issues as $issue)
            <li class="issue">{!! $issue !!}</li>
        @endforeach
    </ul>

    <p>Geprüft: {{ now()->format('d.m.Y H:i:s') }}</p>
    <hr>
    <p>Monitoring Service</p>
</body>
</html>

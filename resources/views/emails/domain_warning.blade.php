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
    <p>Hello,</p>
    <p>Your website <strong>{{ $domain->url }}</strong> is reporting the following warning:</p>
    
    <ul>
        @foreach($issues as $issue)
            <li class="issue">{!! $issue !!}</li>
        @endforeach
    </ul>

    <p>Checked: {{ now()->format('Y-m-d H:i:s') }}</p>
    <hr>
    <p>Monitoring Service</p>
</body>
</html>

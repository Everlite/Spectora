<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Spectora') }}</title>
    
    <!-- Fonts: System Stack (Privacy First) -->
    <style>
        .font-sans {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-white dark:bg-gray-900">
    <main class="w-full flex min-h-screen">
        <!-- Left Side: Branding & Testimonials -->
        <div class="relative flex-1 hidden items-center justify-center h-screen bg-gray-900 lg:flex overflow-hidden">
            <div class="relative z-10 w-full max-w-md p-8">
                <img src="/logo.png" width="150" alt="Spectora Logo" class="mb-8">
                <div class="space-y-6">
                    <h3 class="text-white text-3xl font-bold leading-tight">
                        Schützen Sie Ihre digitale Infrastruktur.
                    </h3>
                    <p class="text-gray-300 text-lg">
                        Starten Sie jetzt mit professionellem Monitoring für Uptime, SSL und Performance. 
                        Automatische Alerts, bevor Ihre Kunden etwas merken.
                    </p>
                    

                </div>
            </div>
            
            <!-- Background Decoration -->
            <div class="absolute inset-0 my-auto h-[500px]"
                style="background: linear-gradient(152.92deg, rgba(139, 92, 246, 0.2) 4.54%, rgba(6, 182, 212, 0.26) 34.2%, rgba(139, 92, 246, 0.1) 77.55%); filter: blur(118px);">
            </div>
        </div>

        <!-- Right Side: Form -->
        <div class="flex-1 flex items-center justify-center h-screen overflow-y-auto bg-white dark:bg-gray-900">
            <div class="w-full max-w-md space-y-8 px-4 sm:px-8 py-8">
                <div class="lg:hidden mb-8">
                    <img src="/logo.png" width="120" alt="Spectora Logo">
                </div>
                
                {{ $slot }}
                
            </div>
        </div>
    </main>
</body>
</html>

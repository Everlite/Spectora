<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Spectora</title>

        <!-- Fonts: System Stack (Privacy First) -->
    <style>
        .font-sans {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
        }
    </style>

        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#0f172a">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Spectora">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 dark:bg-gray-900 text-slate-800 dark:text-gray-100 transition-colors duration-300">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800/95 border-b border-slate-200/60 dark:border-gray-700/50">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js');
                });
            }

            // PWA Install Logic (Global)
            let deferredPrompt;
            const installBtnMobile = document.getElementById('installAppBtnMobile');
            const installBtnDesktop = document.getElementById('installAppBtnDesktop');

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                console.log('PWA: Native prompt ready');
            });

            const handleInstallClick = () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        deferredPrompt = null;
                    });
                } else {
                    // Fallback: Show manual instructions modal
                    window.dispatchEvent(new CustomEvent('open-install-modal'));
                }
            };

            // Attach listeners when DOM is ready
            document.addEventListener('DOMContentLoaded', () => {
                const btnMobile = document.getElementById('installAppBtnMobile');
                const btnDesktop = document.getElementById('installAppBtnDesktop');
                if(btnMobile) btnMobile.addEventListener('click', handleInstallClick);
                if(btnDesktop) btnDesktop.addEventListener('click', handleInstallClick);
            });
        </script>

        <!-- Theme Initialization (must be before Alpine) -->
        <script>
            (function() {
                const theme = localStorage.getItem('spectora-theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>


        <!-- Install Instructions Modal (Global) -->
        <div x-data="{ showInstallModal: false }" 
             @open-install-modal.window="showInstallModal = true"
             x-show="showInstallModal" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true"
             style="display: none;" x-show.important="showInstallModal">
            
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="showInstallModal = false"></div>

                <div class="inline-block align-bottom bg-gray-800 border border-gray-700 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full">
                    <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-900/50 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">Install App</h3>
                                <div class="mt-2 text-sm text-gray-300 space-y-4">
                                    <p>To install Spectora on your home screen:</p>
                                    
                                    <!-- iOS Instructions -->
                                    <div class="bg-gray-700/50 p-3 rounded-lg">
                                        <p class="font-bold text-white mb-1">iPhone / iPad (Safari)</p>
                                        <p>Tap <span class="inline-flex"><svg class="w-4 h-4 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg> Share</span> and select <br><strong>"Add to Home Screen"</strong>.</p>
                                    </div>

                                    <!-- Android Instructions -->
                                    <div class="bg-gray-700/50 p-3 rounded-lg">
                                        <p class="font-bold text-white mb-1">Android (Chrome)</p>
                                        <p>Tap the menu icon (⋮) and select <br><strong>"Install App"</strong> or <strong>"Add to Home Screen"</strong>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                        <button type="button" @click="showInstallModal = false" class="w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-white hover:bg-gray-600 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Got it</button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

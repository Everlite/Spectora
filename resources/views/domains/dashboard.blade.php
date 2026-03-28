<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4" x-data="headerActions()">
            <!-- Domain Info -->
            <div class="flex items-center gap-4">
                <!-- Domain Icon -->
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-violet-600 dark:from-cyan-500 dark:to-cyan-600 flex items-center justify-center shadow-lg shadow-violet-500/20 dark:shadow-cyan-500/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                </div>
                <div>
                    <h2 class="font-bold text-xl md:text-2xl text-primary leading-tight flex items-center gap-3">
                        <span class="accent-primary break-all">{{ $domain->url }}</span>
                        @if($domain->status_code >= 200 && $domain->status_code < 400)
                            <span class="badge-success text-[10px]">● Online</span>
                        @else
                            <span class="badge-error text-[10px]">● Offline</span>
                        @endif
                    </h2>
                    <p class="text-muted text-sm mt-0.5">Last Check: {{ $domain->updated_at->diffForHumans() }}</p>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-2 md:gap-3">
                <!-- Analyse Button (Primary) -->
                <button 
                    @click="runAnalysis()"
                    :disabled="isAnalyzing"
                    class="btn-primary"
                >
                    <template x-if="isAnalyzing">
                        <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="!isAnalyzing">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </template>
                    <span x-text="isAnalyzing ? 'Analyzing...' : 'Analyze'"></span>
                </button>
                
                <a href="{{ $domain->url }}" target="_blank"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-200 hover:bg-slate-200 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    Visit
                </a>

                <!-- Tracking Code -->
                <button @click="showTrackingModal = true"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-200 hover:bg-slate-200 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    Tracking
                </button>

                <a href="{{ route('domains.report', $domain) }}"
                    class="px-3 md:px-4 py-2 bg-spectora-violet hover:bg-violet-600 text-white text-sm font-bold rounded-lg transition shadow-lg shadow-violet-900/20 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <span class="hidden sm:inline">PDF</span>
                </a>
            </div>
            
            <!-- Analysis Feedback Toast -->
            <div x-show="analysisResult" x-cloak 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="fixed bottom-4 right-4 z-50">
                <div x-show="analysisResult === 'success'" class="bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="font-medium">Analysis successful! Reloading...</span>
                </div>
                <div x-show="analysisResult === 'error'" class="bg-red-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span class="font-medium" x-text="'Error: ' + analysisError"></span>
                </div>
            </div>


            <!-- Tracking Code Modal -->
            <div x-show="showTrackingModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="modal-title" role="dialog" aria-modal="true">

                <!-- Backdrop -->
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showTrackingModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"
                        @click="showTrackingModal = false"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <!-- Modal Panel -->
                    <div x-show="showTrackingModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="inline-block align-bottom bg-gray-800 border border-gray-700 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full">

                        <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="text-left">
                                <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">
                                    Tracking Code Installation
                                </h3>
                                <div class="mt-4" x-data="{ copied: false }">
                                    <p class="text-sm text-gray-400 mb-3">
                                        Copy and paste this code into the <code
                                            class="bg-gray-900 px-1 py-0.5 rounded text-gray-300">&lt;head&gt;</code>
                                        of your website.
                                    </p>
                                    <div class="relative group">
                                        <div class="flex items-start bg-gray-900 border border-gray-700 rounded p-2 h-24 focus-within:border-spectora-cyan transition-colors">
                                            <textarea readonly
                                                class="flex-1 bg-transparent border-none text-green-400 font-mono text-xs p-1 focus:ring-0 h-full resize-none leading-relaxed w-full"
                                                id="trackingCode"><script src="{{ url('/js/sp-core.js') }}" data-domain="{{ $domain->uuid }}"></script></textarea>
                                            
                                            <button
                                                @click="
                                                    navigator.clipboard.writeText(document.getElementById('trackingCode').value);
                                                    copied = true;
                                                    setTimeout(() => copied = false, 2000);
                                                "
                                                class="flex-none ml-2 text-xs font-bold px-3 py-1.5 rounded border transition-all duration-200"
                                                :class="copied ? 'bg-green-500/20 text-green-400 border-green-500/50' : 'bg-gray-800 hover:bg-gray-700 text-white border-gray-600'"
                                            >
                                                <span x-show="!copied">Copy</span>
                                                <span x-show="copied" x-cloak class="flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    Copied!
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                            <button type="button" @click="showTrackingModal = false"
                                class="w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <style>
        .custom-grid-4 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 768px) {
            .custom-grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1280px) {
            .custom-grid-4 {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .custom-grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        @media (min-width: 1024px) {
            .custom-grid-2 {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>

    <div class="py-10" x-data="dashboardData()">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('error'))
                <div class="mb-4 bg-red-900 border border-red-700 text-red-200 px-4 py-3 rounded-xl relative shadow-lg" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Tabs Navigation - Professional Style -->
            <div class="card-base p-1.5 flex w-full overflow-x-auto scrollbar-hide">
                <nav class="flex space-x-1 w-full" aria-label="Tabs">
                    <button
                        @click="tab = 'overview'"
                        :class="tab === 'overview' 
                            ? 'bg-gradient-to-r from-violet-500 to-violet-600 dark:from-cyan-500 dark:to-cyan-600 text-white shadow-lg dark:shadow-cyan-500/20' 
                            : 'text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-gray-700/50'"
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Overview
                    </button>

                    <button
                        @click="tab = 'analytics'"
                        :class="tab === 'analytics' 
                            ? 'bg-gradient-to-r from-violet-500 to-violet-600 dark:from-cyan-500 dark:to-cyan-600 text-white shadow-lg dark:shadow-cyan-500/20' 
                            : 'text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-gray-700/50'"
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Analytics
                    </button>

                    <button
                        @click="tab = 'history'"
                        :class="tab === 'history' 
                            ? 'bg-gradient-to-r from-violet-500 to-violet-600 dark:from-cyan-500 dark:to-cyan-600 text-white shadow-lg dark:shadow-cyan-500/20' 
                            : 'text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-gray-700/50'"
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        History
                    </button>

                    <button
                        @click="tab = 'notes'"
                        :class="tab === 'notes' 
                            ? 'bg-gradient-to-r from-violet-500 to-violet-600 dark:from-cyan-500 dark:to-cyan-600 text-white shadow-lg dark:shadow-cyan-500/20' 
                            : 'text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-gray-700/50'"
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Notes
                    </button>

                    <button
                        @click="tab = 'monitoring'"
                        :class="tab === 'monitoring' 
                            ? 'bg-gradient-to-r from-violet-500 to-violet-600 dark:from-cyan-500 dark:to-cyan-600 text-white shadow-lg dark:shadow-cyan-500/20' 
                            : 'text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-gray-700/50'"
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Monitoring
                    </button>

                </nav>
            </div>


            <!-- Tab Content: Overview -->
            <div x-show="tab === 'overview'"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-6">

                @php
                    $score = $domain->pagespeed_score_desktop ?? 0;
                    $scoreColor = $score >= 90 ? 'green' : ($score >= 50 ? 'orange' : 'red');
                    $watchdogData = $domain->safety_details['watchdog'] ?? null;
                    $securityIssues = $watchdogData['issues'] ?? [];
                    $criticalCount = count(array_filter($securityIssues, fn($i) => ($i['severity'] ?? '') === 'critical'));
                    $warningCount = count(array_filter($securityIssues, fn($i) => ($i['severity'] ?? '') === 'warning'));
                    $auditDetails = $domain->last_pagespeed_details ?? [];
                @endphp

                <!-- Row 1: 4 Stat Cards -->
                <div class="custom-grid-4">
                    
                    <!-- Performance Card -->
                    <div class="bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-xl shadow-sm p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs lg:text-sm font-medium text-slate-500 dark:text-gray-400">Performance</h3>
                            <span class="text-[10px] lg:text-xs px-2 py-1 rounded-full {{ $score >= 90 ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400' : ($score >= 50 ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400' : 'bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400') }}">
                                {{ $score >= 90 ? 'Excellent' : ($score >= 50 ? 'Average' : 'Critical') }}
                            </span>
                        </div>
                        <div class="flex items-baseline gap-1 mb-3">
                            <span class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white">{{ $score }}</span>
                            <span class="text-base lg:text-lg text-slate-400 dark:text-gray-500">/100</span>
                        </div>
                        <div class="border-t border-slate-100 dark:border-gray-700 pt-2">
                            <div class="h-12 lg:h-16">
                                <canvas id="performanceSparkline" class="w-full h-full"></canvas>
                            </div>
                            <div class="flex justify-between text-[9px] lg:text-[10px] text-slate-400 dark:text-gray-500 mt-1">
                                <span>7 Days</span>
                                <span>Today</span>
                            </div>
                        </div>
                    </div>

                    <!-- Uptime Card -->
                    <div class="bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-xl shadow-sm p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs lg:text-sm font-medium text-slate-500 dark:text-gray-400">Uptime</h3>
                            <span class="text-[10px] lg:text-xs px-2 py-1 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400">30 Days</span>
                        </div>
                        <div class="flex items-baseline gap-1 mb-3">
                            <span class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white">{{ $uptime }}</span>
                            <span class="text-base lg:text-lg text-slate-400 dark:text-gray-500">%</span>
                        </div>
                        <div class="border-t border-slate-100 dark:border-gray-700 pt-2">
                            <div class="h-12 lg:h-16">
                                <canvas id="uptimeSparkline" class="w-full h-full"></canvas>
                            </div>
                            <div class="flex justify-between text-[9px] lg:text-[10px] text-slate-400 dark:text-gray-500 mt-1">
                                <span>30 Days</span>
                                <span>Today</span>
                            </div>
                        </div>
                    </div>

                    <!-- Response Time Card -->
                    <div class="bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-xl shadow-sm p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs lg:text-sm font-medium text-slate-500 dark:text-gray-400">Response Time</h3>
                            <span class="text-[10px] lg:text-xs px-2 py-1 rounded-full {{ $avgResponseTime < 300 ? 'bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-400' : 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400' }}">
                                {{ $avgResponseTime < 300 ? 'Fast' : 'Acceptable' }}
                            </span>
                        </div>
                        <div class="flex items-baseline gap-1 mb-3">
                            <span class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white">
                                @if(isset($avgResponseTime))
                                    @if($avgResponseTime < 1000)
                                        {{ $avgResponseTime }}<span class="text-lg lg:text-xl text-slate-500 font-medium">ms</span>
                                    @else
                                        {{ number_format($avgResponseTime / 1000, 2) }}<span class="text-lg lg:text-xl text-slate-500 font-medium">s</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="border-t border-slate-100 dark:border-gray-700 pt-2">
                            <div class="h-12 lg:h-16">
                                <canvas id="responseSparkline" class="w-full h-full"></canvas>
                            </div>
                            <div class="flex justify-between text-[9px] lg:text-[10px] text-slate-400 dark:text-gray-500 mt-1">
                                <span>7 Days</span>
                                <span>Today</span>
                            </div>
                        </div>
                    </div>

                    <!-- SSL Certificate Card -->
                    <div class="bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-xl shadow-sm p-4">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-xs lg:text-sm font-medium text-slate-500 dark:text-gray-400">SSL Certificate</h3>
                                <svg class="w-4 h-4 lg:w-5 lg:h-5 {{ $sslDaysRemaining > 30 ? 'text-emerald-500' : ($sslDaysRemaining > 7 ? 'text-amber-500' : 'text-rose-500') }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex items-baseline gap-1 mb-3">
                                <span class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white">{{ $sslDaysRemaining }}</span>
                                <span class="text-base lg:text-lg text-slate-400 dark:text-gray-500">Days</span>
                            </div>
                        </div>
                        <div class="border-t border-slate-100 dark:border-gray-700 pt-2">
                            <div class="relative pt-1">
                                <div class="overflow-hidden h-3 text-xs flex rounded bg-slate-100 dark:bg-gray-700">
                                    <div style="width:{{ min(100, ($sslDaysRemaining / 90) * 100) }}%" 
                                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $sslDaysRemaining > 60 ? 'bg-emerald-500' : ($sslDaysRemaining > 30 ? 'bg-emerald-400' : ($sslDaysRemaining > 7 ? 'bg-amber-500' : 'bg-rose-500')) }}">
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between text-[9px] lg:text-[10px] text-slate-400 dark:text-gray-500 mt-2">
                                <span>0 Days</span>
                                <span>90 Days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Geräte + Traffic -->
                <div class="custom-grid-2">
                    
                    <!-- Device Pie Chart (Left) -->
                    <div class="min-w-0 bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-xl shadow-sm p-4 lg:p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-semibold text-slate-800 dark:text-gray-100">Devices</h3>
                            <span class="text-xs text-slate-400 dark:text-gray-500">Last 30 Days</span>
                        </div>
                        <div class="flex items-center justify-center">
                            <div class="relative w-48 h-48 max-w-full">
                                <canvas id="overviewDeviceChart"></canvas>
                            </div>
                        </div>
                        <!-- Legend -->
                        <div class="mt-4 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-violet-500"></div>
                                    <span class="text-slate-600 dark:text-gray-400">Desktop</span>
                                </div>
                                <span class="font-medium text-slate-800 dark:text-gray-200">{{ $deviceStats['desktop'] ?? 0 }}%</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-cyan-500"></div>
                                    <span class="text-slate-600 dark:text-gray-400">Mobile</span>
                                </div>
                                <span class="font-medium text-slate-800 dark:text-gray-200">{{ $deviceStats['mobile'] ?? 0 }}%</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                                    <span class="text-slate-600 dark:text-gray-400">Tablet</span>
                                </div>
                                <span class="font-medium text-slate-800 dark:text-gray-200">{{ $deviceStats['tablet'] ?? 0 }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Traffic Line Chart (Right) -->
                    <div class="min-w-0 bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-xl shadow-sm p-4 lg:p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-semibold text-slate-800 dark:text-gray-100">Traffic Overview</h3>
                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-violet-500"></div>
                                    <span class="text-slate-500 dark:text-gray-400">Visitors</span>
                                    <span class="font-semibold text-slate-800 dark:text-gray-200">{{ number_format(array_sum($chartVisitors)) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-cyan-500"></div>
                                    <span class="text-slate-500 dark:text-gray-400">Pageviews</span>
                                    <span class="font-semibold text-slate-800 dark:text-gray-200">{{ number_format(array_sum($chartPageviews)) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="relative h-64 w-full">
                            <canvas id="overviewChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Security Alert (nur wenn Issues) -->
                @if($criticalCount + $warningCount > 0)
                <div @click="showSecurityModal = true"
                     class="card-base p-4 flex items-center gap-3 cursor-pointer transition-all duration-200 bg-rose-50 dark:bg-red-500/10 border-rose-200 dark:border-red-500/30 hover:bg-rose-100 dark:hover:bg-red-500/20 hover:border-rose-300 dark:hover:border-red-500/50 hover:shadow-lg dark:hover:shadow-red-500/20 group">
                    <svg class="w-6 h-6 text-rose-600 dark:text-red-400 flex-shrink-0 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div class="flex-1">
                        <span class="font-bold text-rose-700 dark:text-red-400 group-hover:text-rose-800 dark:group-hover:text-red-300">{{ $criticalCount + $warningCount }} security issues found</span>
                        <span class="text-sm ml-2 text-slate-600 dark:text-gray-400 group-hover:underline">→ Click for details</span>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 dark:text-gray-500 group-hover:text-rose-600 dark:group-hover:text-red-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
                @endif

                @if(count($monitoredUrls) > 0)
                <!-- Monitored Paths Summary -->
                <div class="card-base p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-violet-100 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 dark:text-white">Monitored Paths</h3>
                                <p class="text-xs text-slate-500 dark:text-gray-400">Current status of your subpages</p>
                            </div>
                        </div>
                        <button @click="tab = 'monitoring'" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">Manage all →</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($monitoredUrls as $mUrl)
                        <div class="p-3 rounded-xl border border-slate-100 dark:border-gray-700/50 bg-slate-50/50 dark:bg-gray-800/30 flex items-center justify-between group hover:border-violet-200 dark:hover:border-violet-500/30 transition-all">
                            <div class="min-w-0 pr-4">
                                <p class="text-[10px] font-mono text-slate-400 uppercase tracking-tighter">{{ parse_url($mUrl->url, PHP_URL_PATH) ?: '/' }}</p>
                                <p class="text-xs font-semibold text-slate-700 dark:text-gray-200 truncate" title="{{ $mUrl->url }}">{{ $mUrl->url }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="text-[10px] font-bold {{ $mUrl->last_status_code < 400 ? 'text-emerald-500' : 'text-rose-500' }}">
                                        {{ $mUrl->last_status_code ?: 'PENDING' }}
                                    </p>
                                    <p class="text-[9px] text-slate-400 font-mono">
                                        @if(isset($mUrl->last_response_time))
                                            @if($mUrl->last_response_time < 1000)
                                                {{ $mUrl->last_response_time }}ms
                                            @else
                                                {{ number_format($mUrl->last_response_time / 1000, 2) }}s
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                                <div class="w-2 h-2 rounded-full {{ $mUrl->last_status_code < 400 ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Audit Details - Grouped by Category with Messages -->
                @if(!empty($auditDetails))
                @php
                    $categories = [
                        'performance' => ['name' => 'Performance', 'icon' => '⚡', 'color' => '#f97316'],
                        'seo' => ['name' => 'SEO', 'icon' => '🔍', 'color' => '#8b5cf6'],
                        'accessibility' => ['name' => 'Accessibility', 'icon' => '♿', 'color' => '#06b6d4'],
                        'security' => ['name' => 'Security', 'icon' => '🔒', 'color' => '#22c55e'],
                    ];
                    $groupedAudits = collect($auditDetails)->groupBy('category');
                    $passedCount = collect($auditDetails)->where('status', 'success')->count();
                    $failedCount = collect($auditDetails)->where('status', '!=', 'success')->count();
                @endphp
                
                <div class="card-base p-4 sm:p-6 group">
                        
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-primary font-bold text-lg flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-br from-violet-500 to-cyan-500 dark:from-violet-600 dark:to-cyan-400">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                Website Audit
                            </h3>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="px-3 py-1 rounded-full bg-emerald-100 dark:bg-green-500/15 text-emerald-700 dark:text-green-400">✓ {{ $passedCount }} passed</span>
                                @if($failedCount > 0)
                                    <span class="px-3 py-1 rounded-full bg-rose-100 dark:bg-red-500/15 text-rose-700 dark:text-red-400">✗ {{ $failedCount }} issues</span>
                                @endif
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="space-y-4">
                            @foreach($categories as $catKey => $cat)
                                @if(isset($groupedAudits[$catKey]))
                                    @php
                                        $items = $groupedAudits[$catKey];
                                        $catPassed = $items->where('status', 'success')->count();
                                        $catFailed = $items->where('status', '!=', 'success')->count();
                                    @endphp
                                    <div x-data="{ open: {{ $catFailed > 0 ? 'true' : 'false' }} }" class="rounded-lg overflow-hidden border border-slate-200 dark:border-gray-700/50">
                                        <!-- Category Header -->
                                        <button @click="open = !open" class="w-full flex items-center justify-between p-4 transition-all bg-slate-50 dark:bg-gray-800/50 hover:bg-slate-100 dark:hover:bg-gray-800">
                                            <div class="flex items-center gap-3">
                                                <span class="text-xl">{{ $cat['icon'] }}</span>
                                                <span class="font-bold text-primary">{{ $cat['name'] }}</span>
                                                <span class="text-xs px-2 py-0.5 rounded {{ $catFailed > 0 ? 'bg-rose-100 dark:bg-red-500/20 text-rose-600 dark:text-red-400' : 'bg-emerald-100 dark:bg-green-500/20 text-emerald-600 dark:text-green-400' }}">
                                                    {{ $catPassed }}/{{ $items->count() }}
                                                </span>
                                            </div>
                                            <svg class="w-5 h-5 text-slate-400 dark:text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>
                                        
                                        <!-- Category Items -->
                                        <div x-show="open" x-collapse class="divide-y divide-slate-200 dark:divide-gray-700/30">
                                            @foreach($items as $item)
                                                <div class="p-4 flex items-start gap-3 bg-slate-50/50 dark:bg-gray-900/30">
                                                    @if(($item['status'] ?? '') === 'success')
                                                        <div class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center bg-emerald-100 dark:bg-green-500/20">
                                                            <span class="text-emerald-600 dark:text-green-400 text-sm">✓</span>
                                                        </div>
                                                        <div>
                                                            <p class="text-primary font-medium">{{ $item['label'] ?? '' }}</p>
                                                            <p class="text-muted text-sm mt-0.5">{{ $item['message'] ?? '' }}</p>
                                                        </div>
                                                    @else
                                                        <div class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center bg-rose-100 dark:bg-red-500/20">
                                                            <span class="text-rose-600 dark:text-red-400 text-sm">!</span>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-rose-700 dark:text-red-300 font-medium">{{ $item['label'] ?? '' }}</p>
                                                            <p class="text-rose-600/80 dark:text-red-400/80 text-sm mt-0.5">{{ $item['message'] ?? '' }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Traffic Chart (full width, clickable) -->
                <div class="card-base p-6 cursor-pointer group transition-all hover:shadow-lg dark:hover:shadow-none hover:border-violet-200 dark:hover:border-cyan-500/30" @click="tab = 'analytics'">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-br from-violet-500 to-emerald-500 dark:from-cyan-500 dark:to-green-500">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-primary font-bold text-lg">Traffic Overview</h3>
                                    <p class="text-muted text-xs group-hover:text-violet-600 dark:group-hover:text-cyan-400 transition-colors">Click for details →</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-violet-500 dark:bg-cyan-400"></div>
                                    <span class="text-secondary">Besucher</span>
                                    <span class="text-primary font-bold text-lg">{{ number_format(array_sum($chartVisitors)) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-fuchsia-500 dark:bg-violet-400"></div>
                                    <span class="text-secondary">Aufrufe</span>
                                    <span class="text-primary font-bold text-lg">{{ number_format(array_sum($chartPageviews)) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="h-72 md:h-80 w-full">
                            <canvas id="overviewChart"></canvas>
                        </div>
                        <p class="text-muted text-xs text-center mt-4">Last 30 Days</p>
                </div>

            </div>

            <!-- Security Details Modal -->
            <div x-show="showSecurityModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="showSecurityModal = false"></div>
                    <div class="inline-block align-bottom bg-spectora-card border border-gray-700 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                        <div class="bg-spectora-card px-5 pt-5 pb-4 sm:p-6">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full {{ $domain->safety_status === 'safe' ? 'bg-green-500/20' : 'bg-red-500/20' }} sm:mx-0">
                                    @if($domain->safety_status === 'safe')
                                        <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    @else
                                        <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    @endif
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg font-bold text-white">Security Report</h3>
                                    
                                    @if($criticalCount > 0 || $warningCount > 0)
                                        <div class="flex gap-2 mt-2 text-xs">
                                            @if($criticalCount > 0)
                                                <span class="px-2 py-1 rounded bg-red-500/20 text-red-400 font-bold">{{ $criticalCount }} Critical</span>
                                            @endif
                                            @if($warningCount > 0)
                                                <span class="px-2 py-1 rounded bg-orange-500/20 text-orange-400 font-bold">{{ $warningCount }} Warnings</span>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="mt-4 max-h-[55vh] overflow-y-auto pr-2 space-y-3">
                                        @if(!empty($securityIssues))
                                            @foreach($securityIssues as $issue)
                                                @php
                                                    $severity = $issue['severity'] ?? 'warning';
                                                    $colors = match($severity) {
                                                        'critical' => ['bg' => 'bg-red-500/10', 'border' => 'border-red-500/30', 'badge' => 'bg-red-500/20 text-red-400'],
                                                        'warning' => ['bg' => 'bg-orange-500/10', 'border' => 'border-orange-500/30', 'badge' => 'bg-orange-500/20 text-orange-400'],
                                                        default => ['bg' => 'bg-blue-500/10', 'border' => 'border-blue-500/30', 'badge' => 'bg-blue-500/20 text-blue-400'],
                                                    };
                                                @endphp
                                                <div class="{{ $colors['bg'] }} {{ $colors['border'] }} border rounded-lg p-4">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h4 class="font-bold text-white text-sm">{{ $issue['title'] ?? 'Issue' }}</h4>
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded {{ $colors['badge'] }} uppercase font-bold">{{ $severity }}</span>
                                                    </div>
                                                    <p class="text-gray-300 text-sm">{{ $issue['description'] ?? '' }}</p>
                                                    @if(!empty($issue['recommendation']))
                                                        <div class="mt-2 flex items-start gap-2 text-xs">
                                                            <span class="text-green-500">✅</span>
                                                            <span class="text-green-400">{{ $issue['recommendation'] }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="text-center py-8">
                                                <svg class="w-12 h-12 mx-auto text-green-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                                <p class="text-green-400 font-bold">All safe!</p>
                                                <p class="text-gray-500 text-sm mt-1">No security issues found.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-800/50 px-5 py-3 sm:flex sm:flex-row-reverse border-t border-gray-700">
                            <button type="button" @click="showSecurityModal = false" class="w-full inline-flex justify-center rounded-lg px-4 py-2 bg-spectora-cyan text-gray-900 text-sm font-bold hover:bg-cyan-400 transition sm:w-auto">Close</button>
                        </div>
                    </div>
                </div>
            </div>




            <!-- Tab Content: Monitoring -->
            <div x-show="tab === 'monitoring'"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-6"
                 x-data="monitoringManager()">
                 
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Column 1 & 2: Main Settings -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Smart Filters Card -->
                        <div class="bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                Smart Monitoring Filters
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- Only Public Pages -->
                                <label class="flex items-center justify-between p-3 rounded-lg border border-slate-200 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-gray-200">Only public pages</p>
                                            <p class="text-xs text-slate-500 dark:text-gray-400">Automatically skips login areas and protected content.</p>
                                        </div>
                                    </div>
                                    <input type="checkbox" x-model="settings.only_check_public_pages" class="w-5 h-5 rounded border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-violet-600 focus:ring-violet-500">
                                </label>

                                <!-- Robots.txt -->
                                <label class="flex items-center justify-between p-3 rounded-lg border border-slate-200 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-gray-200">Respect robots.txt</p>
                                            <p class="text-xs text-slate-500 dark:text-gray-400">Follows the instructions in robots.txt (User-Agent: SpectoraBot).</p>
                                        </div>
                                    </div>
                                    <input type="checkbox" x-model="settings.respect_robots_txt" class="w-5 h-5 rounded border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-violet-600 focus:ring-violet-500">
                                </label>

                                <!-- Noindex -->
                                <label class="flex items-center justify-between p-3 rounded-lg border border-slate-200 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-gray-200">Respect noindex</p>
                                            <p class="text-xs text-slate-500 dark:text-gray-400">Ignores pages with "noindex" meta tag or HTTP header.</p>
                                        </div>
                                    </div>
                                    <input type="checkbox" x-model="settings.respect_noindex" class="w-5 h-5 rounded border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-violet-600 focus:ring-violet-500">
                                </label>
                            </div>
                        </div>

                        <!-- URL Patterns Card -->
                        <div class="bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                URL Exclusion Patterns
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-gray-400 mb-4">One pattern per line. Use * as a wildcard (e.g. <code>*/downloads/*</code>).</p>
                            <textarea 
                                x-model="settings.exclude_patterns"
                                rows="5"
                                class="w-full rounded-xl border-slate-300 dark:border-gray-600 bg-slate-50 dark:bg-gray-900/50 text-slate-900 dark:text-white focus:ring-violet-500 focus:border-violet-500 font-mono text-sm"
                                placeholder="*/private/*&#10;*/kran/*"></textarea>
                            
                            <div class="mt-6 flex justify-end">
                                <button @click="saveSettings()" class="btn-primary" :disabled="isSaving">
                                    <template x-if="isSaving">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </template>
                                    <span x-text="isSaving ? 'Saving...' : 'Save settings'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Sitemaps & URLs -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Sitemaps Card -->
                        <div class="bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>
                                Sitemaps
                            </h3>
                            <div class="mb-4">
                                <button @click="detectSitemaps()" class="w-full py-2 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 rounded-lg text-sm font-bold hover:bg-slate-200 dark:hover:bg-gray-600 transition flex items-center justify-center gap-2" :disabled="isDetecting">
                                    <template x-if="isDetecting">
                                        <svg class="animate-spin h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </template>
                                    <span x-text="isDetecting ? 'Searching...' : 'Search sitemaps automatically'"></span>
                                </button>
                            </div>
                            <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                <template x-for="sitemap in sitemap_urls" :key="sitemap">
                                    <label class="flex items-center gap-3 p-2 rounded bg-slate-50 dark:bg-gray-900/40 border border-slate-100 dark:border-gray-700 hover:bg-slate-100 dark:hover:bg-gray-700 transition cursor-pointer">
                                        <input type="checkbox" :value="sitemap" x-model="settings.included_sitemaps" class="w-4 h-4 rounded border-slate-300 dark:border-gray-600 text-cyan-500 focus:ring-cyan-500">
                                        <span class="text-xs font-mono text-slate-600 dark:text-gray-300 truncate" x-text="sitemap.split('/').pop() || sitemap"></span>
                                    </label>
                                </template>
                                <template x-if="sitemap_urls.length === 0">
                                    <p class="text-center py-4 text-xs text-slate-400 italic">No sitemaps found.</p>
                                </template>
                            </div>
                        </div>

                        <!-- URL Selection Card -->
                        <div class="bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                Monitored URLs
                            </h3>
                            
                            <div class="mb-4">
                                <button @click="openUrlSelector()" class="w-full py-2 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-lg text-sm font-bold hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    URLs auswählen & verwalten
                                </button>
                            </div>

                            <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                <template x-for="url in monitoredUrls" :key="url.id || url.url">
                                    <div class="flex items-center justify-between p-2 rounded bg-slate-50 dark:bg-gray-900/40 border border-slate-100 dark:border-gray-700/50">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[10px] font-mono text-slate-500 dark:text-gray-400 truncate" x-text="url.url"></p>
                                        </div>
                                        <div class="flex items-center gap-2 ml-4">
                                            <span x-show="url.is_active" class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                            <span x-text="url.is_active ? 'Active' : 'Inactive'" class="text-[10px] text-slate-500"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="monitoredUrls.length === 0">
                                    <p class="text-center py-4 text-xs text-slate-400 italic">No additional URLs selected yet.</p>
                                </template>
                            </div>
                        </div>
                    </div> <!-- End Column 3 -->
                    
                </div> <!-- End Grid -->

                <!-- URL Selection Modal (Now safely inside monitoringManager) -->
                <div x-show="showUrlModal" 
                     class="fixed inset-0 z-50 overflow-y-auto" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showUrlModal = false">
                            <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm"></div>
                        </div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200 dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-slate-100 dark:border-gray-700 flex justify-between items-center">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Select URLs to monitor</h3>
                                <button @click="showUrlModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg>
                                </button>
                            </div>

                            <div class="p-6">
                                <div class="mb-6 flex items-center justify-between">
                                    <div class="text-sm text-slate-500 dark:text-gray-400">
                                        Scanning sitemaps and homepage for links...
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="selectPublicOnly()" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">Select public only</button>
                                        <span class="text-slate-300 dark:text-gray-600">|</span>
                                        <button @click="toggleAllUrls()" class="text-xs font-bold text-slate-600 dark:text-slate-400 hover:underline" x-text="allSelected ? 'Deselect all' : 'Select all'"></button>
                                    </div>
                                </div>

                                <div class="space-y-2 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                                    <template x-if="isScanningUrls">
                                        <div class="py-12 text-center">
                                            <svg class="animate-spin h-8 w-8 text-violet-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <p class="text-slate-500">Analyzing domain structure...</p>
                                        </div>
                                    </template>

                                    <template x-for="item in discoveredUrls" :key="item.url">
                                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer group">
                                            <input type="checkbox" x-model="item.is_monitored" class="w-5 h-5 rounded border-slate-300 dark:border-gray-600 text-violet-600 focus:ring-violet-500">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-mono text-slate-700 dark:text-gray-300 truncate" x-text="item.url"></p>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <template x-if="item.is_public">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">PUBLIC</span>
                                                    </template>
                                                    <template x-if="!item.is_public">
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400" :title="item.skip_reason">PRIVATE/LOCKED</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </label>
                                    </template>

                                    <template x-if="discoveredUrls.length === 0 && !isScanningUrls">
                                        <div class="py-12 text-center text-slate-400 text-sm">
                                            <svg class="w-12 h-12 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            No URLs found. Try different sitemap settings.
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 dark:bg-gray-900/50 border-t border-slate-100 dark:border-gray-700 flex justify-end gap-3">
                                <button @click="showUrlModal = false" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition">Cancel</button>
                                <button @click="saveUrlSelection()" class="btn-primary" :disabled="isSyncingUrls">
                                    <template x-if="isSyncingUrls">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </template>
                                    <span x-text="isSyncingUrls ? 'Saving...' : 'Save selection'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div> <!-- End Modal -->

            </div> <!-- End Tab Content: Monitoring (monitoringManager) -->


            <!-- Tab Content: Analytics -->
            <div x-show="tab === 'analytics'" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="space-y-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Top Pages -->
                            <div class="bg-spectora-card border border-gray-700/50 rounded-xl p-6 shadow-xl">
                                <h3 class="text-lg font-bold text-white mb-4">Top Pages</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="text-gray-500 text-xs uppercase tracking-wider border-b border-gray-700">
                                                <th class="pb-3">URL</th>
                                                <th class="pb-3 text-right">Views</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-700/50">
                                            @foreach ($topPages as $page)
                                                <tr class="group hover:bg-gray-800/30 transition">
                                                    <td class="py-3 text-gray-300 font-mono text-sm truncate max-w-xs group-hover:text-white">
                                                        {{ $page->url }}</td>
                                                    <td class="py-3 text-right text-white font-bold">
                                                        {{ number_format($page->total) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Top Sources -->
                            <div class="bg-spectora-card border border-gray-700/50 rounded-xl p-6 shadow-xl">
                                <h3 class="text-lg font-bold text-white mb-4">Top Sources</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="text-gray-500 text-xs uppercase tracking-wider border-b border-gray-700">
                                                <th class="pb-3">Source</th>
                                                <th class="pb-3 text-right">Visitors</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-700/50">
                                            @foreach ($topSources as $source)
                                                <tr class="group hover:bg-gray-800/30 transition">
                                                    <td class="py-3 text-gray-300 font-mono text-sm truncate max-w-xs group-hover:text-white">
                                                        {{ $source->referrer_domain ?: 'Direct / Unknown' }}</td>
                                                    <td class="py-3 text-right text-white font-bold">
                                                        {{ number_format($source->total) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Devices -->
                            <div class="bg-spectora-card border border-gray-700/50 rounded-xl p-6 shadow-xl">
                                <h3 class="text-lg font-bold text-white mb-4">Devices</h3>
                                <div class="h-64 relative">
                                    <canvas id="analyticsDeviceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content: History -->
                    <div x-show="tab === 'history'"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="space-y-6"
                         x-data="historyManager()">
                        <div class="space-y-8">
                            <!-- Spectora Analysis Section -->
                            <div class="bg-spectora-card border border-gray-700/50 rounded-xl p-6 shadow-xl">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">Spectora Analysis</h3>
                                        <p class="text-sm text-gray-400">Performance, Security & SEO Check</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <!-- Last Analysis Time -->
                                        @if($domain->updated_at)
                                            <span class="text-xs text-gray-500">
                                                Last Analysis: {{ $domain->updated_at->diffForHumans() }}
                                            </span>
                                        @endif
                                        
                                        <!-- Analysis Button with Loading State -->
                                        <button 
                                            @click="runAnalysis()"
                                            :disabled="isAnalyzing"
                                            class="bg-spectora-violet hover:bg-violet-600 disabled:bg-violet-800 disabled:cursor-wait text-white text-sm font-bold py-2 px-4 rounded transition shadow-lg flex items-center gap-2"
                                        >
                                            <template x-if="isAnalyzing">
                                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </template>
                                            <span x-text="isAnalyzing ? 'Analyzing...' : 'Start analysis'"></span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-[10px] text-gray-500 italic bg-gray-800/30 p-2 rounded border border-gray-700/50 inline-block">
                                        <span class="text-spectora-cyan font-bold">Note:</span> Performance scan runs locally in a container – may take 30-60s.
                                    </p>
                                </div>
                                
                                <!-- Analysis Feedback -->
                                <div x-show="analysisResult" x-cloak class="mb-6">
                                    <div x-show="analysisResult === 'success'" class="bg-green-500/10 border border-green-500/30 rounded-lg p-4 flex items-center gap-3">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <div>
                                            <p class="text-green-400 font-bold text-sm">Analysis successful!</p>
                                            <p class="text-green-300/70 text-xs">The page will be updated shortly...</p>
                                        </div>
                                    </div>
                                    <div x-show="analysisResult === 'error'" class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 flex items-center gap-3">
                                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        <div>
                                            <p class="text-red-400 font-bold text-sm">Analysis failed</p>
                                            <p class="text-red-300/70 text-xs" x-text="analysisError"></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Details List -->
                                @if ($domain->last_pagespeed_details)
                                    <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                                        @foreach ($domain->last_pagespeed_details as $item)
                                            <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-4 flex items-start gap-4">
                                                <div class="flex-shrink-0 mt-1">
                                                    @php $status = $item['status'] ?? 'unknown'; @endphp
                                                    @if ($status === 'success')
                                                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    @elseif($status === 'warning')
                                                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h4 class="font-semibold text-gray-200 text-sm">{{ $item['label'] ?? 'Unknown Issue' }}</h4>
                                                    <p class="text-xs text-gray-400 mt-1">{{ $item['message'] ?? '' }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <svg class="w-12 h-12 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <p class="text-gray-500 italic">No analysis performed yet.</p>
                                        <p class="text-gray-600 text-sm mt-1">Click "Start analysis" for a detailed report.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Check History / Issues Log -->
                            <div class="bg-spectora-card border border-gray-700/50 rounded-xl p-6 shadow-xl">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">Event Log</h3>
                                        <p class="text-sm text-gray-400">Issues and status changes</p>
                                    </div>
                                    
                                    <!-- Filter Toggle -->
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="showAllLogs" class="sr-only peer">
                                            <div class="relative w-10 h-5 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-gray-400 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-spectora-cyan"></div>
                                            <span class="text-sm text-gray-400">Show all</span>
                                        </label>
                                    </div>
                                </div>
                                
                                @php
                                    // Filter only issues (Status >= 400 or 0)
                                    $issueChecks = $recentChecks->filter(function($check) {
                                        return $check->status_code >= 400 || $check->status_code === 0 || $check->status_code === null;
                                    });
                                @endphp
                                
                                <div class="overflow-x-auto">
                                    <!-- Issues Only View -->
                                    <div x-show="!showAllLogs">
                                        @if($issueChecks->count() > 0)
                                            <table class="w-full text-left">
                                                <thead>
                                                    <tr class="text-gray-500 text-xs uppercase tracking-wider border-b border-gray-700">
                                                        <th class="pb-3">Time</th>
                                                        <th class="pb-3">Status</th>
                                                        <th class="pb-3">Response time</th>
                                                        <th class="pb-3">Error message</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-700/50">
                                                    @foreach ($issueChecks as $check)
                                                        <tr class="hover:bg-gray-800/30 transition">
                                                            <td class="py-3 text-gray-300 text-sm">
                                                                {{ $check->created_at->format('d.m.Y H:i:s') }}</td>
                                                            <td class="py-3">
                                                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-500/20 text-red-400">
                                                                    {{ $check->status_code ?: 'ERROR' }}
                                                                </span>
                                                            </td>
                                                            <td class="py-3 text-gray-300 text-sm font-mono">
                                                                @if(isset($check->response_time))
                                                                    @if($check->response_time < 1)
                                                                        {{ round($check->response_time * 1000, 0) }}ms
                                                                    @else
                                                                        {{ number_format($check->response_time, 2) }}s
                                                                    @endif
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td class="py-3 text-red-400 text-xs truncate max-w-[100px] sm:max-w-xs">
                                                                {{ $check->error_message ?? 'Connection error' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <div class="text-center py-12">
                                                <svg class="w-16 h-16 mx-auto text-green-500/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <p class="text-green-400 font-bold text-lg">No issues!</p>
                                                <p class="text-gray-500 text-sm mt-1">No outages or errors recorded.</p>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- All Logs View -->
                                    <div x-show="showAllLogs" x-cloak>
                                        <table class="w-full text-left">
                                            <thead>
                                                <tr class="text-gray-500 text-xs uppercase tracking-wider border-b border-gray-700">
                                                    <th class="pb-3">Time</th>
                                                    <th class="pb-3">Status</th>
                                                    <th class="pb-3">Response time</th>
                                                    <th class="pb-3">Message</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-700/50">
                                                @foreach ($recentChecks as $check)
                                                    <tr class="hover:bg-gray-800/30 transition">
                                                        <td class="py-3 text-gray-300 text-sm">
                                                            {{ $check->created_at->format('d.m.Y H:i:s') }}</td>
                                                        <td class="py-3">
                                                            <span class="px-2 py-0.5 rounded text-xs font-bold {{ $check->status_code >= 200 && $check->status_code < 400 ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                                                {{ $check->status_code }}
                                                            </span>
                                                        </td>
                                                        <td class="py-3 text-gray-300 text-sm font-mono">
                                                            @if(isset($check->response_time))
                                                                @if($check->response_time < 1)
                                                                    {{ round($check->response_time * 1000, 0) }}ms
                                                                @else
                                                                    {{ number_format($check->response_time, 2) }}s
                                                                @endif
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td class="py-3 text-gray-400 text-xs truncate max-w-[100px] sm:max-w-xs">
                                                            {{ $check->error_message ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <div class="mt-4">
                                            {{ $recentChecks->links() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Content: Notes -->
                        <div x-show="tab === 'notes'" 
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="space-y-6">
                            <div class="bg-spectora-card border border-gray-700/50 rounded-xl p-6 shadow-xl"
                                x-data="notesManager('{{ $domain->uuid }}')">
                                <h3 class="text-lg font-bold text-white mb-4">Notes</h3>

                                <!-- Add Note -->
                                <div class="mb-6">
                                    <textarea x-model="newNote"
                                        class="w-full bg-gray-900 border border-gray-700 rounded text-white text-sm p-3 focus:border-spectora-cyan focus:ring-0"
                                        rows="3" placeholder="Add a new note..."></textarea>
                                    <div class="mt-2 flex justify-end">
                                        <button @click="addNote()" :disabled="!newNote.trim()"
                                            class="bg-spectora-cyan hover:bg-cyan-500 text-white text-sm font-bold py-2 px-4 rounded disabled:opacity-50 transition">
                                            Add Note
                                        </button>
                                    </div>
                                </div>

                                <!-- Notes List -->
                                <div class="space-y-4">
                                    <template x-for="note in notes" :key="note.id">
                                        <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-4 relative group">
                                            <p class="text-gray-300 text-sm whitespace-pre-wrap" x-text="note.content"></p>
                                            <div class="mt-2 flex justify-between items-center text-xs text-gray-500">
                                                <span x-text="new Date(note.created_at).toLocaleString()"></span>
                                                <div class="flex gap-2">
                                                    <button @click="editNote(note)"
                                                        class="text-blue-400 hover:text-blue-300 font-bold">Edit</button>
                                                    <button @click="confirmDelete(note.id)"
                                                        class="text-red-400 hover:text-red-300 font-bold">Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="notes.length === 0" class="text-center text-gray-500 py-8">
                                        No notes yet.
                                    </div>
                                </div>

                                <!-- Delete Confirmation Modal -->
                                <div x-show="isDeleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                                    aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeDeleteModal()"></div>
                                        <div class="inline-block align-bottom bg-gray-800 border border-gray-700 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                                            <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <div class="sm:flex sm:items-start">
                                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-900/50 sm:mx-0 sm:h-10 sm:w-10">
                                                        <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                    </div>
                                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                                        <h3 class="text-lg leading-6 font-medium text-white">Are you sure?</h3>
                                                        <div class="mt-2">
                                                            <p class="text-sm text-gray-400">Do you really want to delete this note? This process cannot be undone.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                                                <button type="button" @click="submitDelete()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Delete</button>
                                                <button type="button" @click="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-white hover:bg-gray-600 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Note Modal -->
                                <div x-show="isEditModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeEditModal()"></div>
                                        <div class="inline-block align-bottom bg-gray-800 border border-gray-700 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                                            <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <h3 class="text-lg leading-6 font-medium text-white mb-4">Edit Note</h3>
                                                <textarea x-model="editingContent" class="w-full bg-gray-900 border border-gray-700 rounded text-white text-sm p-3 focus:border-spectora-cyan focus:ring-0" rows="5"></textarea>
                                            </div>
                                            <div class="bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                                                <button type="button" @click="submitEdit()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-spectora-cyan text-base font-medium text-white hover:bg-cyan-500 sm:ml-3 sm:w-auto sm:text-sm">Save Changes</button>
                                                <button type="button" @click="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-white hover:bg-gray-600 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function headerActions() {
            return {
                showTrackingModal: false,
                isAnalyzing: false,
                analysisResult: null,
                analysisError: '',
                
                async runAnalysis() {
                    this.isAnalyzing = true;
                    this.analysisResult = null;
                    this.analysisError = '';
                    
                    try {
                        const response = await fetch('{{ route('domains.analyze', $domain) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (response.ok) {
                            this.analysisResult = 'success';
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            this.analysisResult = 'error';
                            const data = await response.json().catch(() => ({}));
                            this.analysisError = data.message || 'An error occurred.';
                            setTimeout(() => { this.analysisResult = null; }, 5000);
                        }
                    } catch (e) {
                        this.analysisResult = 'error';
                        this.analysisError = e.message;
                        setTimeout(() => { this.analysisResult = null; }, 5000);
                    } finally {
                        this.isAnalyzing = false;
                    }
                }
            }
        }

        function dashboardData() {
            return {
                tab: 'overview', // Default to overview
                showSecurityModal: false,
                showTrafficModal: false,
                init() {
                    this.initOverviewChart();
                    this.initDeviceChart('overviewDeviceChart');
                    this.initDeviceChart('analyticsDeviceChart');
                    this.initSparklines();
                },
                initSparklines() {
                    // Performance Sparkline
                    const perfCtx = document.getElementById('performanceSparkline');
                    if (perfCtx) {
                        new Chart(perfCtx, {
                            type: 'line',
                            data: {
                                labels: @json($psHistoryLabels),
                                datasets: [{
                                    data: @json($psHistoryScores),
                                    borderColor: '{{ $score >= 90 ? "#10b981" : ($score >= 50 ? "#f59e0b" : "#ef4444") }}',
                                    backgroundColor: '{{ $score >= 90 ? "rgba(16,185,129,0.1)" : ($score >= 50 ? "rgba(245,158,11,0.1)" : "rgba(239,68,68,0.1)") }}',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false }, tooltip: { enabled: true } },
                                scales: {
                                    x: { display: false },
                                    y: { display: false, min: 0, max: 100 }
                                }
                            }
                        });
                    }
                    // Uptime Sparkline
                    const uptimeCtx = document.getElementById('uptimeSparkline');
                    if (uptimeCtx) {
                        new Chart(uptimeCtx, {
                            type: 'line',
                            data: {
                                labels: ['', '', '', '', '', '', ''],
                                datasets: [{
                                    data: [100, 100, 100, 100, 100, 100, {{ $uptime }}],
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16,185,129,0.1)',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { display: false },
                                    y: { display: false, min: 95, max: 100.5 }
                                }
                            }
                        });
                    }
                    // Response Time Sparkline
                    const respCtx = document.getElementById('responseSparkline');
                    if (respCtx) {
                        new Chart(respCtx, {
                            type: 'line',
                            data: {
                                labels: @json($historyLabels),
                                datasets: [{
                                    data: @json($historyResponseTimes),
                                    borderColor: '{{ $avgResponseTime < 300 ? "#8b5cf6" : "#f59e0b" }}',
                                    backgroundColor: '{{ $avgResponseTime < 300 ? "rgba(139,92,246,0.1)" : "rgba(245,158,11,0.1)" }}',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false }, tooltip: { enabled: true } },
                                scales: {
                                    x: { display: false },
                                    y: { display: false }
                                }
                            }
                        });
                    }
                },
                initOverviewChart() {
                    const ctx = document.getElementById('overviewChart');
                    if (!ctx) return;
                    new Chart(ctx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: @json($chartLabels),
                            datasets: [{
                                    label: 'Visitors',
                                    data: @json($chartVisitors),
                                    borderColor: '#8b5cf6',
                                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 0,
                                    pointHoverRadius: 4
                                },
                                {
                                    label: 'Pageviews',
                                    data: @json($chartPageviews),
                                    borderColor: '#06b6d4',
                                    backgroundColor: 'rgba(6, 182, 212, 0.05)',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 0,
                                    pointHoverRadius: 4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(148, 163, 184, 0.1)' },
                                    ticks: { color: '#94a3b8', font: { size: 11 } }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { color: '#94a3b8', font: { size: 11 } }
                                }
                            },
                            interaction: {
                                mode: 'index',
                                intersect: false
                            }
                        }
                    });
                },
                initDeviceChart(chartId = 'overviewDeviceChart') {
                    const ctx = document.getElementById(chartId);
                    if (!ctx) return;
                    new Chart(ctx.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Desktop', 'Mobile', 'Tablet'],
                            datasets: [{
                                data: @json($deviceData),
                                backgroundColor: [
                                    '#8b5cf6', // Violet (Desktop)
                                    '#06b6d4', // Cyan (Mobile)
                                    '#f59e0b'  // Amber (Tablet)
                                ],
                                borderWidth: 0,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            cutout: '65%'
                        }
                    });
                }
            }
        }

        function historyManager() {
            return {
                isAnalyzing: false,
                analysisResult: null, // 'success' | 'error' | null
                analysisError: '',
                showAllLogs: false,
                
                async runAnalysis() {
                    this.isAnalyzing = true;
                    this.analysisResult = null;
                    this.analysisError = '';
                    
                    try {
                        const response = await fetch('{{ route('domains.analyze', $domain) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (response.ok) {
                            this.analysisResult = 'success';
                            // Reload after 2 seconds to show updated data
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            this.analysisResult = 'error';
                            const data = await response.json().catch(() => ({}));
                            this.analysisError = data.message || 'An unknown error occurred.';
                        }
                    } catch (e) {
                        this.analysisResult = 'error';
                        this.analysisError = 'Network error: ' + e.message;
                    } finally {
                        this.isAnalyzing = false;
                    }
                }
            }
        }

        function notesManager(domainUuid) {
            return {
                notes: @json($notes),
                newNote: '',
                isDeleteModalOpen: false,
                deleteNoteId: null,
                isEditModalOpen: false,
                editingNoteId: null,
                editingContent: '',

                async addNote() {
                    if (!this.newNote.trim()) return;

                    try {
                        const response = await fetch(`/domains/${domainUuid}/notes`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                content: this.newNote
                            })
                        });

                        if (response.ok) {
                            const note = await response.json();
                            this.notes.unshift(note);
                            this.newNote = '';
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                confirmDelete(noteId) {
                    this.deleteNoteId = noteId;
                    this.isDeleteModalOpen = true;
                },

                closeDeleteModal() {
                    this.isDeleteModalOpen = false;
                    this.deleteNoteId = null;
                },

                async submitDelete() {
                    if (!this.deleteNoteId) return;

                    try {
                        const response = await fetch(`/notes/${this.deleteNoteId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Accept': 'application/json'
                            }
                        });
                        if (response.ok) {
                            this.notes = this.notes.filter(n => n.id !== this.deleteNoteId);
                            this.closeDeleteModal();
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                editNote(note) {
                    this.editingNoteId = note.id;
                    this.editingContent = note.content;
                    this.isEditModalOpen = true;
                },

                closeEditModal() {
                    this.isEditModalOpen = false;
                    this.editingNoteId = null;
                    this.editingContent = '';
                },

                async submitEdit() {
                    if (!this.editingNoteId || !this.editingContent.trim()) return;

                    try {
                        const response = await fetch(`/notes/${this.editingNoteId}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                content: this.editingContent
                            })
                        });

                        if (response.ok) {
                            const updatedNote = await response.json();
                            const index = this.notes.findIndex(n => n.id === this.editingNoteId);
                            if (index !== -1) {
                                this.notes[index] = updatedNote;
                            }
                            this.closeEditModal();
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
            }
        }

        function monitoringManager() {
            return {
                isSaving: false,
                isDetecting: false,
                showUrlModal: false,
                isScanningUrls: false,
                isSyncingUrls: false,
                sitemap_urls: @json($domain->sitemap_urls ?? []),
                monitoredUrls: @json($domain->monitoredUrls),
                discoveredUrls: [],
                allSelected: false,
                settings: {
                    only_check_public_pages: {{ $domain->only_check_public_pages ? 'true' : 'false' }},
                    respect_robots_txt: {{ $domain->respect_robots_txt ? 'true' : 'false' }},
                    respect_noindex: {{ $domain->respect_noindex ? 'true' : 'false' }},
                    exclude_patterns: @json($domain->exclude_patterns ?? ''),
                    included_sitemaps: @json($domain->included_sitemaps ?? []),
                },

                async saveSettings() {
                    this.isSaving = true;
                    try {
                        const response = await fetch('{{ route('domains.settings.update', $domain) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.settings)
                        });
                        if (response.ok) {
                            // Show success state briefly then reload
                            const btn = event.target.closest('button');
                            const originalText = btn.innerText;
                            btn.innerText = 'Saved!';
                            btn.classList.add('bg-emerald-600');
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isSaving = false;
                    }
                },

                async detectSitemaps() {
                    this.isDetecting = true;
                    try {
                        const response = await fetch('{{ route('domains.sitemaps.detect', $domain) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        if (response.ok) {
                            const data = await response.json();
                            this.sitemap_urls = data.sitemaps;
                            this.settings.included_sitemaps = data.sitemaps; // Auto-include new ones?
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isDetecting = false;
                    }
                },

                async openUrlSelector() {
                    this.showUrlModal = true;
                    this.isScanningUrls = true;
                    try {
                        const response = await fetch('{{ route('domains.urls.scan', $domain) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        if (response.ok) {
                            const data = await response.json();
                            this.discoveredUrls = data.urls;
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isScanningUrls = false;
                    }
                },

                toggleAllUrls() {
                    this.allSelected = !this.allSelected;
                    this.discoveredUrls.forEach(u => u.is_monitored = this.allSelected);
                },

                selectPublicOnly() {
                    this.discoveredUrls.forEach(u => u.is_monitored = u.is_public);
                },

                async saveUrlSelection() {
                    this.isSyncingUrls = true;
                    try {
                        const response = await fetch('{{ route('domains.urls.sync', $domain) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ urls: this.discoveredUrls })
                        });
                        if (response.ok) {
                            window.location.reload();
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isSyncingUrls = false;
                    }
                }
            }
        }
    </script>

</x-app-layout>

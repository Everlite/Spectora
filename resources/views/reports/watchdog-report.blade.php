{{-- Watchdog Report Partial - uses parent dashboardManager() scope --}}
<div class="space-y-6 text-left w-full h-full max-h-[80vh] flex flex-col">

    <!-- Header / Summary -->
    <template x-if="wdHasWatchdog()">
        <div class="flex flex-wrap gap-3 flex-shrink-0">
            <template x-if="wdSummary.critical > 0">
                <div class="px-3 py-1 bg-red-900/50 border border-red-700 text-red-200 rounded-full text-sm font-semibold flex items-center shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span x-text="wdSummary.critical + ' Critical'"></span>
                </div>
            </template>
            <template x-if="wdSummary.warning > 0">
                <div class="px-3 py-1 bg-yellow-900/50 border border-yellow-700 text-yellow-200 rounded-full text-sm font-semibold flex items-center shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span x-text="wdSummary.warning + ' Warnings'"></span>
                </div>
            </template>
            <template x-if="wdSummary.info > 0">
                <div class="px-3 py-1 bg-blue-900/50 border border-blue-700 text-blue-200 rounded-full text-sm font-semibold flex items-center shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span x-text="wdSummary.info + ' Info'"></span>
                </div>
            </template>
        </div>
    </template>

    <div class="overflow-y-auto flex-1 pr-2 custom-scrollbar">
        <!-- Keywords Found (Manual Check) -->
        <template x-if="statusDetails && statusDetails.keywords_found && statusDetails.keywords_found.length > 0">
            <div class="mb-4 bg-red-900/20 border border-red-800 p-4 rounded-xl shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <h4 class="font-bold text-gray-100 text-lg">Gefundene Fehlerwörter</h4>
                    </div>
                </div>
                <p class="text-gray-300 text-sm mb-3">Die folgenden explizit als Fehler definierten Wörter wurden auf der Website gefunden:</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="keyword in statusDetails.keywords_found">
                        <span class="px-2 py-1 bg-gray-950 border border-red-700 text-red-300 rounded text-xs font-mono shadow-inner" x-text="keyword"></span>
                    </template>
                </div>
            </div>
        </template>

        <!-- Issues Loop (Watchdog) -->
        <template x-if="wdHasWatchdog() && wdIssues.length > 0">
            <div class="space-y-4">
                <template x-for="(issue, index) in wdIssues" :key="index">
                    <div x-show="!wdIsDismissed(issue)" 
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-95"
                        class="rounded-xl border p-5 shadow-sm relative overflow-hidden"
                        :class="{
                            'bg-red-900/20 border-red-800/60': issue.severity === 'critical',
                            'bg-yellow-900/10 border-yellow-800/60': issue.severity === 'warning',
                            'bg-blue-900/10 border-blue-800/60': issue.severity === 'info' || !['critical', 'warning'].includes(issue.severity)
                        }">
                        
                        <!-- Decorative top accent bar -->
                        <div class="absolute top-0 left-0 w-full h-1" 
                             :class="{
                                'bg-red-500': issue.severity === 'critical',
                                'bg-yellow-500': issue.severity === 'warning',
                                'bg-blue-500': issue.severity === 'info' || !['critical', 'warning'].includes(issue.severity)
                             }"></div>

                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2 mb-3 mt-1">
                                <!-- Critical Icon -->
                                <template x-if="issue.severity === 'critical'">
                                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </template>
                                <!-- Warning Icon -->
                                <template x-if="issue.severity === 'warning'">
                                    <svg class="w-5 h-5 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </template>
                                <!-- Info Icon -->
                                <template x-if="issue.severity !== 'critical' && issue.severity !== 'warning'">
                                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </template>
                                
                                <h4 class="font-bold text-gray-100 text-lg" x-text="issue.title"></h4>
                            </div>
                            
                            <!-- Dismiss Button -->
                            <button @click="wdDismiss(issue)" class="text-gray-500 hover:text-white bg-gray-800/50 hover:bg-gray-700 transition-colors p-1.5 rounded-lg border border-transparent hover:border-gray-600 focus:outline-none" title="Warnung ausblenden">
                                <span class="sr-only">Dismiss</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="mt-2 space-y-4 text-sm leading-relaxed">
                            <!-- Description -->
                            <template x-if="issue.description">
                                <div>
                                    <span class="font-semibold text-gray-400 block uppercase text-xs tracking-wider mb-1">Problem</span>
                                    <p class="text-gray-200" x-text="issue.description"></p>
                                </div>
                            </template>
                            
                            <!-- Explanation -->
                            <template x-if="issue.explanation">
                                <div>
                                    <span class="font-semibold text-gray-400 block uppercase text-xs tracking-wider mb-1">Auswirkung</span>
                                    <p class="text-gray-300" x-text="issue.explanation"></p>
                                </div>
                            </template>

                            <!-- Context (Snippets) -->
                            <template x-if="issue.context">
                                <div>
                                    <span class="font-semibold text-gray-400 block uppercase text-xs tracking-wider mb-1">Context / Code Snippet</span>
                                    <div class="bg-[#0b0f19] p-2.5 rounded border border-gray-700 font-mono text-xs text-green-400 overflow-x-auto shadow-inner">
                                        <span x-text="issue.context"></span>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Recommendation -->
                            <template x-if="issue.recommendation">
                                <div class="bg-gray-800/60 p-3 rounded-lg border-l-4 shadow-sm mt-4"
                                    :class="{
                                        'border-red-500': issue.severity === 'critical',
                                        'border-yellow-500': issue.severity === 'warning',
                                        'border-blue-500': issue.severity !== 'critical' && issue.severity !== 'warning'
                                    }">
                                    <span class="font-semibold text-gray-200 flex items-center gap-1.5 mb-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        Lösungsvorschlag
                                    </span>
                                    <p class="text-gray-300" x-text="issue.recommendation"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                
                <template x-if="wdIssues.length > 0 && wdVisibleCount === 0">
                    <div class="text-center py-8 border border-dashed border-gray-700 rounded-xl bg-gray-800/30">
                        <p class="text-spectora-cyan font-medium mb-3">✅ Alle Warnungen wurden als gelesen markiert.</p>
                        <button @click="wdRestoreAll()" class="text-xs text-gray-400 hover:text-white border border-gray-600 rounded px-3 py-1.5 hover:bg-gray-700 transition-colors focus:outline-none">
                            Alle wieder anzeigen
                        </button>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="(!wdHasWatchdog() || wdIssues.length === 0) && (!statusDetails?.keywords_found?.length)">
            <div class="text-gray-400 italic text-center py-10">Keine spezifischen Sicherheitswarnungen in den Details gefunden.</div>
        </template>
    </div> <!-- End scrollable area -->

    <!-- Developer Tools -->
    <div class="pt-4 border-t border-gray-700/80 mt-2 flex-shrink-0">
        <div class="flex items-center gap-3 mb-3">
            <button @click="showJson = !showJson" class="text-xs font-semibold uppercase tracking-wider text-spectora-cyan hover:text-cyan-400 flex items-center focus:outline-none transition-colors border-none bg-transparent p-0">
                <svg class="w-4 h-4 mr-1 transition-transform duration-200" :class="{'rotate-90': showJson}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                Developer JSON View
            </button>
            <template x-if="showJson">
                <button @click="wdCopyJson()" class="text-xs text-gray-300 hover:text-white flex items-center border border-gray-600 rounded px-2.5 py-1 hover:bg-gray-700 focus:outline-none transition-colors">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <span x-ref="wdCopyBtn">Copy</span>
                </button>
            </template>
        </div>
        
        <div x-show="showJson" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-[#0f1423] rounded-lg p-4 border border-gray-700 font-mono text-xs text-gray-300 overflow-x-auto max-h-64 shadow-inner custom-scrollbar relative">
             <div class="absolute top-2 right-2 text-[10px] text-gray-500 uppercase">RAW DATA</div>
            <pre x-text="JSON.stringify(statusDetails, null, 2)"></pre>
        </div>
    </div>
</div>

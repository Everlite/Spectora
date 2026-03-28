<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Monitored Domains') }}
            </h2>
            <div class="flex items-center gap-3">
                <!-- Notification UI Container -->
                <div id="notification-ui" class="flex items-center">
                    <!-- State: Enable Button -->
                    <button id="enable-notifications-btn" onclick="enableNotifications()" style="display: none;"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-lg transition shadow-lg flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                            </path>
                        </svg>
                        Enable Alerts
                    </button>

                    <!-- State: Active Badge -->
                    <span id="notifications-active-badge" style="display: none;"
                        class="px-4 py-2 bg-green-500/20 text-green-400 border border-green-500/30 text-sm font-bold rounded-lg flex items-center gap-2 cursor-default">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Alerts Active
                    </span>

                    <!-- State: Blocked Badge -->
                    <span id="notifications-blocked-badge" style="display: none;"
                        class="px-4 py-2 bg-red-500/20 text-red-400 border border-red-500/30 text-sm font-bold rounded-lg flex items-center gap-2 cursor-help"
                        title="Notifications are blocked in your browser settings.">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                        Alerts Blocked
                    </span>
                </div>

                <div x-data>
                    <button @click="$dispatch('open-add-domain')" class="bg-gray-700 hover:bg-gray-600 text-white text-sm font-bold py-2 px-4 rounded transition">
                        + Add Domain
                    </button>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="dashboardManager()">
        <div class="max-w-[98%] mx-auto sm:px-6 lg:px-8">
            
            @if (session('status'))
                <div class="mb-4 bg-green-900 border border-green-700 text-green-200 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-900 border border-red-700 text-red-200 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Whoops!</strong>
                    <span class="block sm:inline">
                        @foreach ($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                @foreach ($domains as $domain)
                    <div class="bg-[#1f2937] border border-gray-700 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 relative">
                        
                        <div class="absolute left-0 top-0 bottom-0 w-1 {{ $domain->status_code >= 200 && $domain->status_code < 400 ? 'bg-green-500' : 'bg-red-500' }}"></div>

                        <div class="p-6 pl-8">
                            <!-- Domain Name Row -->
                            <div class="mb-4">
                                <h3 class="text-lg font-bold text-white truncate w-full" title="{{ $domain->url }}">
                                    {{ $domain->url }}
                                </h3>
                            </div>

                            <!-- Status Badges Row -->
                            <div class="flex space-x-2 mb-6">
                                <!-- Watchdog Status -->
                                @if($domain->status_code >= 200 && $domain->status_code < 400)
                                    <div class="px-2 py-1 text-xs font-bold rounded bg-green-900 text-green-400 border border-green-700 uppercase w-28 h-8 flex items-center justify-center shrink-0" style="min-width: 112px; width: 112px;">
                                        Online
                                    </div>
                                @else
                                    <div class="px-2 py-1 text-xs font-bold rounded bg-red-900 text-red-400 border border-red-700 uppercase w-28 h-8 flex items-center justify-center shrink-0" style="min-width: 112px; width: 112px;">
                                        Offline
                                    </div>
                                @endif

                                <!-- Security Status -->
                                <div @click="openStatus({{ json_encode($domain->url) }}, {{ json_encode($domain->safety_details ?? []) }}, {{ json_encode($domain->safety_status) }})" 
                                     class="relative group flex items-center justify-center space-x-1 cursor-pointer px-2 py-1 rounded border text-xs font-bold uppercase w-28 h-8 shrink-0
                                    @if($domain->safety_status === 'safe') bg-green-900 text-green-400 border-green-700
                                    @elseif($domain->safety_status === 'danger') bg-red-900 text-red-400 border-red-700
                                    @elseif($domain->safety_status === 'warning') bg-yellow-900 text-yellow-400 border-yellow-700
                                    @else bg-gray-800 text-gray-400 border-gray-600
                                    @endif" 
                                    style="min-width: 112px; width: 112px;">
                                    
                                    @if($domain->safety_status === 'safe')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                        <span>Safe</span>
                                    @elseif($domain->safety_status === 'danger')
                                        <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <span>Danger</span>
                                    @elseif($domain->safety_status === 'warning')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <span>Warning</span>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                        <span>Unknown</span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                
                                <div>
                                    <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Uptime (30d)</div>
                                    <div class="flex items-center text-white font-mono">
                                        <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                        100%
                                    </div>
                                </div>

                                <div>
                                    <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">SSL Valid</div>
                                    <div class="flex items-center font-mono {{ ($domain->ssl_days_left ?? 0) > 30 ? 'text-green-400' : 'text-red-400' }}">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                        {{ $domain->ssl_days_left ?? '-' }} Days
                                    </div>
                                </div>

                                <div>
                                    <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Response</div>
                                    <div class="flex items-center text-cyan-400 font-mono">
                                        <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        @if(isset($domain->response_time))
                                            @if($domain->response_time < 1)
                                                {{ round($domain->response_time * 1000, 0) }}ms
                                            @else
                                                {{ number_format($domain->response_time, 2) }}s
                                            @endif
                                        @else
                                            0.00s
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Visitors (24h)</div>
                                    <div class="flex items-center font-mono {{ ($domain->visitors_today ?? 0) > 0 ? 'text-green-400' : 'text-gray-300' }}">
                                        <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        {{ $domain->visitors_today ?? 0 }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Last Check</div>
                                    <div class="flex items-center text-white font-mono text-sm" 
                                         x-data="{ 
                                            date: '{{ $domain->last_checked?->toIso8601String() }}',
                                            formatted() {
                                                if (!this.date) return '-';
                                                try {
                                                    const d = new Date(this.date);
                                                    return d.toLocaleDateString('de-DE', {day: '2-digit', month: '2-digit'}) + ' ' + d.toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'});
                                                } catch (e) {
                                                    return 'Date Error';
                                                }
                                            }
                                         }">
                                        <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <span x-text="formatted()"></span>
                                    </div>
                                </div>
                            </div>

                        </div>

                            <div class="bg-[#111827] px-6 py-4 flex flex-nowrap gap-3 justify-between items-center border-t border-gray-700 overflow-x-auto">
                                    <a href="{{ route('domains.show', $domain) }}" 
                                       class="px-4 py-2 bg-spectora-cyan hover:bg-cyan-500 text-white text-sm font-bold rounded transition flex items-center gap-2 shadow-lg shadow-cyan-500/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                        Dashboard
                                    </a>
                                <form method="POST" action="{{ route('domains.destroy', $domain) }}" id="delete-form-{{ $domain->id }}" class="shrink-0 ml-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" 
                                            @click="confirmDelete('domain', 'delete-form-{{ $domain->id }}', {{ json_encode($domain->url) }})"
                                            class="px-3 py-1.5 border border-red-900/50 rounded text-xs font-bold text-red-500 uppercase tracking-wider hover:bg-red-900/20 hover:text-red-400 hover:border-red-700 transition whitespace-nowrap">
                                        Delete
                                    </button>
                                </form>
                            </div>

                    </div>
                @endforeach

            </div> 
        <!-- End Main View -->

    <!-- Notes Modal -->
    <div x-show="isNotesOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop -->
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isNotesOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeNotes()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div x-show="isNotesOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-gray-800 border border-gray-700 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">
                                Notes for <span x-text="domainUrl" class="text-spectora-cyan"></span>
                            </h3>
                            
                            <!-- Add Note Form -->
                            <div class="mt-4">
                                <textarea x-model="newNote" class="w-full bg-gray-900 border border-gray-700 rounded text-white text-sm p-2 focus:ring-spectora-cyan focus:border-spectora-cyan" style="background-color: #111827; color: #ffffff;" rows="3" placeholder="Add a new note..."></textarea>
                                <div class="mt-2 flex justify-end">
                                    <button @click="addNote()" :disabled="!newNote.trim()" class="bg-spectora-cyan hover:bg-cyan-500 text-white text-xs font-bold py-2 px-4 rounded disabled:opacity-50">
                                        Add Note
                                    </button>
                                </div>
                            </div>

                            <!-- Notes List -->
                            <div class="mt-6 space-y-4 max-h-60 overflow-y-auto">
                                <template x-for="note in notes" :key="note.id">
                                    <div class="bg-gray-700 rounded p-3 border border-gray-600 relative group">
                                        <p class="text-sm text-gray-200 whitespace-pre-wrap" x-text="note.content"></p>
                                        <div class="mt-2 text-xs text-gray-400 flex justify-between items-center">
                                            <span x-text="formatDate(note.created_at)"></span>
                                            <div class="flex gap-2">
                                                <button @click="editNote(note)" class="text-blue-400 hover:text-blue-300 font-bold">Edit</button>
                                                <button @click="deleteNote(note.id)" class="text-red-400 hover:text-red-300 font-bold">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="notes.length === 0" class="text-center text-gray-500 text-sm py-4">
                                    No notes yet.
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                    <button type="button" @click="closeNotes()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-white hover:bg-gray-600 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Status Modal -->
    <div x-show="isStatusOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isStatusOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeStatus()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="isStatusOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-gray-800 border border-gray-700 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full">
                
                <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-white mb-2" id="modal-title">
                                Status Report: <span x-text="statusUrl" class="text-spectora-cyan"></span>
                            </h3>
                            
                            <div class="mt-4">
                                <template x-if="statusType === 'safe'">
                                    <div class="bg-green-900/50 border border-green-700 text-green-200 p-4 rounded-lg flex items-center">
                                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                        <div>
                                            <strong class="block font-bold text-lg">Alles sicher!</strong>
                                            <span class="text-sm">Es wurden keine Bedrohungen oder Probleme gefunden.</span>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="statusType !== 'safe' && statusDetails">
                                    <div class="w-full text-white mt-4">
                                        @include('reports.watchdog-report')
                                    </div>
                                </template>
                                
                                <template x-if="statusType !== 'safe' && !statusDetails">
                                    <div class="text-gray-400 italic">Keine Details verfügbar.</div>
                                </template>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                    <button type="button" @click="closeStatus()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-white hover:bg-gray-600 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Schließen
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div x-show="isDeleteModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop -->
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isDeleteModalOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeDeleteModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div x-show="isDeleteModalOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Are you sure?
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Do you really want to delete <span x-text="deleteLabel" class="font-bold text-gray-700"></span>? This process cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="submitDelete()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" @click="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Domain Modal -->
    <div x-data="addDomainManager()"
         @open-add-domain.window="openModal()"
         x-show="isOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="isOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-gray-800 border border-gray-700 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <form method="POST" action="{{ route('domains.store') }}" class="p-6">
                    @csrf
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">Add New Domain</h3>

                    <!-- Global Errors -->
                    @if ($errors->any())
                        <div class="mb-4 bg-red-900 border border-red-700 text-red-200 px-4 py-3 rounded relative" role="alert">
                            <strong class="font-bold">Error!</strong>
                            <span class="block sm:inline">
                                @foreach ($errors->all() as $error)
                                    {{ $error }}<br>
                                @endforeach
                            </span>
                        </div>
                    @endif
                    
                    <!-- URL -->
                    <div class="mb-4">
                        <label for="url" class="block text-sm font-medium text-gray-400">Domain / URL</label>
                        <input type="text" name="url" id="url" required placeholder="https://example.com"
                               style="background-color: #111827; color: #ffffff;"
                               class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-spectora-cyan focus:border-spectora-cyan sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Enter with or without https://</p>
                    </div>

                    <!-- Keyword Must Contain -->
                    <div class="mb-4">
                        <label for="keyword_must_contain" class="block text-sm font-medium text-gray-400">Keyword must contain (Optional)</label>
                        <input type="text" name="keyword_must_contain" id="keyword_must_contain" placeholder="e.g. Welcome"
                               style="background-color: #111827; color: #ffffff;"
                               class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-spectora-cyan focus:border-spectora-cyan sm:text-sm">
                    </div>

                    <!-- Keyword Must Not Contain -->
                    <div class="mb-4">
                        <label for="keyword_must_not_contain" class="block text-sm font-medium text-gray-400">Keyword must NOT contain (Optional)</label>
                        <input type="text" name="keyword_must_not_contain" id="keyword_must_not_contain" placeholder="e.g. Error 404"
                               style="background-color: #111827; color: #ffffff;"
                               class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-spectora-cyan focus:border-spectora-cyan sm:text-sm">
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-spectora-cyan text-base font-medium text-white hover:bg-cyan-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-spectora-cyan sm:col-start-2 sm:text-sm">
                            Add Domain
                        </button>
                        <button type="button" @click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div> <!-- Close dashboardManager (line 50) -->



    <script>
        function dashboardManager() {
            return {
                // Notes Logic
                isNotesOpen: false,
                domainId: null,
                domainUrl: '',
                notes: [],
                newNote: '',

                async openNotes(id, url) {
                    this.domainId = id;
                    this.domainUrl = url;
                    this.isNotesOpen = true;
                    this.newNote = '';
                    await this.fetchNotes();
                },

                closeNotes() {
                    this.isNotesOpen = false;
                    this.domainId = null;
                    this.notes = [];
                },

                async fetchNotes() {
                    const response = await fetch(`/domains/${this.domainId}/notes`);
                    this.notes = await response.json();
                },

                async addNote() {
                    if (!this.newNote.trim()) return;

                    const response = await fetch(`/domains/${this.domainId}/notes`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ content: this.newNote })
                    });

                    if (response.ok) {
                        this.newNote = '';
                        await this.fetchNotes();
                    }
                },



                async editNote(note) {
                    const newContent = prompt('Edit note:', note.content);
                    if (newContent === null || newContent === note.content) return;

                    const response = await fetch(`/notes/${note.id}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ content: newContent })
                    });

                    if (response.ok) {
                        await this.fetchNotes();
                    }
                },

                formatDate(dateString) {
                    return new Date(dateString).toLocaleString();
                },

                // Status Modal Logic
                isStatusOpen: false,
                statusUrl: '',
                statusDetails: null,
                statusType: '',

                // Watchdog Report Logic
                showJson: false,
                wdDismissed: [],
                wdStorageKey: '',

                openStatus(url, details, type) {
                    this.statusUrl = url;
                    this.statusDetails = details;
                    this.statusType = type;
                    this.isStatusOpen = true;
                    this.showJson = false;
                    // Load dismissed state from localStorage for this domain
                    this.wdStorageKey = 'spectora_dismissed_' + url.replace(/[^a-zA-Z0-9]/g, '_');
                    try {
                        this.wdDismissed = JSON.parse(localStorage.getItem(this.wdStorageKey) || '[]');
                    } catch(e) { this.wdDismissed = []; }
                },

                closeStatus() {
                    this.isStatusOpen = false;
                    this.statusDetails = null;
                },

                // Delete Modal Logic
                isDeleteModalOpen: false,
                deleteType: '', // 'domain' or 'note'
                deleteLabel: '',
                deleteTargetId: '',

                confirmDelete(type, id, label) {
                    // alert('confirmDelete called: ' + type + ' ' + id + ' ' + label);
                    this.deleteType = type;
                    this.deleteTargetId = id;
                    this.deleteLabel = label;
                    this.isDeleteModalOpen = true;
                },

                closeDeleteModal() {
                    this.isDeleteModalOpen = false;
                    this.deleteType = '';
                    this.deleteTargetId = '';
                    this.deleteLabel = '';
                },

                async submitDelete() {
                    if (this.deleteType === 'domain') {
                        document.getElementById(this.deleteTargetId).submit();
                    } else if (this.deleteType === 'note') {
                        await this.performDeleteNote(this.deleteTargetId);
                        this.closeDeleteModal();
                    }
                },

                deleteNote(noteId) {
                    this.confirmDelete('note', noteId, 'this note');
                },

                async performDeleteNote(noteId) {
                    const response = await fetch(`/notes/${noteId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        await this.fetchNotes();
                    }
                },

                // Watchdog Report Helpers
                wdHasWatchdog() { return !!(this.statusDetails && this.statusDetails.watchdog); },
                wdGetIssues() { return this.wdHasWatchdog() ? (this.statusDetails.watchdog.issues || []) : []; },
                wdGetSummary() { return this.wdHasWatchdog() ? (this.statusDetails.watchdog.summary || {critical:0, warning:0, info:0}) : {critical:0, warning:0, info:0}; },
                wdIssueKey(issue) { return (issue.type || '') + '::' + (issue.title || ''); },
                wdDismiss(issue) {
                    const key = this.wdIssueKey(issue);
                    if (!this.wdDismissed.includes(key)) {
                        this.wdDismissed.push(key);
                        localStorage.setItem(this.wdStorageKey, JSON.stringify(this.wdDismissed));
                    }
                },
                wdIsDismissed(issue) { return this.wdDismissed.includes(this.wdIssueKey(issue)); },
                wdVisibleCount() { return this.wdGetIssues().filter(i => !this.wdIsDismissed(i)).length; },
                wdRestoreAll() {
                    this.wdDismissed = [];
                    localStorage.removeItem(this.wdStorageKey);
                },
                wdCopyJson() {
                    navigator.clipboard.writeText(JSON.stringify(this.statusDetails, null, 2));
                    const btn = document.getElementById('wd-copy-btn');
                    if (btn) { btn.textContent = '✓ Copied!'; setTimeout(() => { btn.textContent = 'Copy'; }, 1500); }
                },
            };
        }

        function addDomainManager() {
            return {
                isOpen: {{ $errors->any() ? 'true' : 'false' }},
                openModal() {
                    this.isOpen = true;
                },
                closeModal() {
                    this.isOpen = false;
                }
            };
        }
    </script>

    <script>
        console.log('🚀 Script Loaded!');

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔔 Init Notification Logic...');

            // 1. Check Browser Support
            if (!('serviceWorker' in navigator)) {
                return;
            }

            if (!('PushManager' in window)) {
                return;
            }

            // 2. Check Permission
            const perm = Notification.permission;

            if (perm === 'denied') {
                const blockedBadge = document.getElementById('notifications-blocked-badge');
                if(blockedBadge) blockedBadge.style.display = 'inline-flex';
            }

            // 3. Check Registration
            navigator.serviceWorker.ready.then(function(registration) {
                console.log('✅ Service Worker ready:', registration);

                registration.pushManager.getSubscription().then(function(subscription) {
                    if (subscription) {
                        console.log('✅ User is already subscribed:', subscription);
                        const activeBadge = document.getElementById('notifications-active-badge');
                        if(activeBadge) activeBadge.style.display = 'inline-flex';
                    } else {
                        console.log('ℹ️ User is NOT subscribed. Showing button.');
                        const btn = document.getElementById('enable-notifications-btn');
                        if(btn) btn.style.display = 'inline-flex';
                    }
                }).catch(function(err) {
                    console.error('❌ Error getting subscription:', err);
                });
            }).catch(function(err) {
                console.error('❌ Service Worker ready failed:', err);
            });
        });

        function enableNotifications() {
            console.log('🖱️ Enable button clicked.');
            
            navigator.serviceWorker.ready.then(function(registration) {
                const vapidPublicKey = '{{ config('webpush.vapid.public_key') }}';
                
                if (!vapidPublicKey) {
                    alert('Error: VAPID Public Key is missing on server.');
                    return;
                }

                registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
                }).then(function(subscription) {
                    console.log('🎉 Subscribed!', subscription);

                    fetch('/subscriptions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(subscription)
                    }).then(function(response) {
                        if (response.ok) {
                            alert('Notifications enabled successfully!');
                            document.getElementById('enable-notifications-btn').style.display = 'none';
                            document.getElementById('notifications-active-badge').style.display = 'inline-flex';
                        } else {
                            response.json().then(data => {
                                console.error('❌ Backend save failed:', data);
                                alert('Failed to save subscription: ' + (data.error ? JSON.stringify(data.error) : 'Unknown error'));
                            }).catch(() => {
                                console.error('❌ Backend save failed (no JSON):', response);
                                alert('Failed to save subscription on server (Status ' + response.status + ').');
                            });
                        }
                    });

                }).catch(function(err) {
                    console.error('❌ Subscribe failed:', err);
                    if (Notification.permission === 'denied') {
                        document.getElementById('enable-notifications-btn').style.display = 'none';
                        document.getElementById('notifications-blocked-badge').style.display = 'inline-flex';
                        alert('You have blocked notifications. Please enable them in your browser settings.');
                    } else {
                        alert('Failed to enable notifications: ' + err.message);
                    }
                });
            });
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
    </script>
</x-app-layout>
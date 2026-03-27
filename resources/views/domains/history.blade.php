<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Check History') }}: <span class="text-spectora-cyan">{{ $domain->url }}</span>
            </h2>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-bold rounded transition">
                &larr; Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-spectora-card border border-gray-700 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    @if($checks->isEmpty())
                        <p class="text-gray-500 text-center py-4">No history data available yet.</p>
                    @else
                        <!-- Chart Container -->
                        <div class="mb-8 h-64 w-full">
                            <canvas id="historyChart"></canvas>
                        </div>

                        <!-- Controls & Filter -->
                        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4" x-data="{ showList: {{ $showOnlyErrors ? 'true' : 'false' }} }">
        <div class="flex gap-4 items-center">
            <button @click="showList = !showList" class="text-sm font-medium text-spectora-cyan hover:text-cyan-300 focus:outline-none flex items-center gap-1">
                <span x-text="showList ? 'Hide Detailed List' : 'Show Detailed List'"></span>
                <svg x-show="!showList" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                <svg x-show="showList" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
            </button>
        </div>

        <div class="flex items-center gap-4">
            <!-- Date Filter -->
            <form action="{{ route('domains.history', $domain) }}" method="GET" class="flex items-center gap-2">
                @if($showOnlyErrors)
                    <input type="hidden" name="only_errors" value="1">
                @endif
                <input type="date" name="date" value="{{ $dateFilter ?? '' }}" 
                       onchange="this.form.submit()"
                       class="bg-gray-800 border border-gray-700 text-white text-xs rounded-lg focus:ring-spectora-cyan focus:border-spectora-cyan block p-1.5">
                @if($dateFilter)
                    <a href="{{ route('domains.history', ['domain' => $domain, 'only_errors' => $showOnlyErrors ? 1 : null]) }}" class="text-gray-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </a>
                @endif
            </form>

            <!-- Error Filter -->
            <div>
                @if($showOnlyErrors)
                    <a href="{{ route('domains.history', ['domain' => $domain, 'date' => $dateFilter]) }}" class="text-xs font-medium text-gray-400 hover:text-white bg-gray-700 px-3 py-1 rounded-full transition">
                        Show All
                    </a>
                @else
                    <a href="{{ route('domains.history', ['domain' => $domain, 'only_errors' => 1, 'date' => $dateFilter]) }}" class="text-xs font-medium text-red-400 hover:text-red-300 border border-red-900/50 bg-red-900/10 px-3 py-1 rounded-full transition">
                        Show Errors Only
                    </a>
                @endif
            </div>
        </div>
    </div>

                        <div x-show="showList" x-cloak class="overflow-x-auto transition-all duration-300" x-data="{ showList: {{ $showOnlyErrors ? 'true' : 'false' }} }">
                            <table class="min-w-full divide-y divide-gray-700">
                                <thead class="bg-gray-800/50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Time</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Response Time</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">SSL</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-spectora-card divide-y divide-gray-700">
                                    @foreach($checks as $check)
                                        <tr class="hover:bg-gray-700/30 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                                {{ $check->created_at->format('d.m.Y H:i:s') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($check->status_code >= 200 && $check->status_code < 400)
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-900/50 text-green-400 border border-green-700">
                                                        {{ $check->status_code }} OK
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-900/50 text-red-400 border border-red-700">
                                                        {{ $check->status_code ?? 'ERR' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 font-mono">
                                                {{ number_format($check->response_time, 3) }}s
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                                {{-- Assuming SSL info might be stored or just generic OK if status is OK --}}
                                                {{-- Legacy didn't store SSL per check in history table explicitly in the dump I saw, 
                                                   but the user mentioned "SSL Status". 
                                                   The migration for checks_history has: domain_id, status_code, response_time, checked_at.
                                                   It DOES NOT have ssl_days_left. 
                                                   Wait, let me check the migration again to be sure. --}}
                                                -
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $checks->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('historyChart').getContext('2d');
            
            // Data from Controller
            const labels = @json($labels);
            const dataPoints = @json($dataPoints);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Response Time (s)',
                        data: dataPoints,
                        borderColor: '#38BDF8', // Spectora Cyan
                        backgroundColor: 'rgba(56, 189, 248, 0.1)',
                        borderWidth: 2,
                        tension: 0.3, // Smooth curves
                        pointRadius: 3,
                        pointBackgroundColor: '#0F172A', // Navy
                        pointBorderColor: '#38BDF8',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#9CA3AF' // Gray-400
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(75, 85, 99, 0.2)' // Gray-600 low opacity
                            },
                            ticks: {
                                color: '#9CA3AF'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#9CA3AF',
                                maxTicksLimit: 10
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>

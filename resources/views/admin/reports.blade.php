@php
    // TAB 04: fixtures live in resources/data/admin/reports.php so this view stays presentation only.
    $__fixtures = require base_path('resources/data/admin/reports.php');
    $reportTypes = $__fixtures['reportTypes'];

    $recent = $__fixtures['recent'];

    // Internal Forms — sourced from the shared config so this stays in sync
    // with the Internal Forms module's own "Recent Submissions" list.
    $internalFormsCfg   = config('esperanza_internal_forms');
    $internalSubmissions = $internalFormsCfg['submissions'];
    $officeColors = $__fixtures['officeColors'];
    $internalOfficeSplit = collect($internalSubmissions)
        ->groupBy('office')
        ->map(fn ($rows, $office) => ['label' => $office, 'value' => $rows->count(), 'color' => $officeColors[$office] ?? '#64748b'])
        ->values()->all();

    // Sakuna incidents — mirrors the current SWM-2026-07 active-period incident
    // log on the Sakuna Command Center (12 incidents), broken down by severity.
    $incidentSeveritySplit = $__fixtures['incidentSeveritySplit'];
    $incidentTotal = array_sum(array_column($incidentSeveritySplit, 'value'));

    $trendLabels = $__fixtures['trendLabels'];
    $trendDocuments = $__fixtures['trendDocuments'];
    $trendAssistance = $__fixtures['trendAssistance'];

    $channelSplit = $__fixtures['channelSplit'];
    $channelTotal = array_sum(array_column($channelSplit, 'value'));

    $topBarangays = $__fixtures['topBarangays'];

    $tab = $tab ?? 'reports';
@endphp

<x-layouts.admin title="Reports & Analytics" subtitle="Generate reports and explore municipal service insights." active="reports">
    <div x-data="{ tab: '{{ $tab }}' }" class="animate-fade-up">

        <div x-show="!$store.session.can('reports')" x-cloak>
            <x-admin.access-restricted module="Reports & Analytics" icon="chart-column" />
        </div>

        <div x-show="$store.session.can('reports')" x-cloak>

        <div x-show="$store.session.account && $store.session.account.scope !== 'Municipal'" x-cloak class="flex items-center gap-2.5 rounded-xl bg-purple-50 border border-purple-100 px-3.5 py-2.5 mb-4">
            <i data-lucide="map-pin" class="w-4 h-4 text-purple-500 shrink-0"></i>
            <p class="text-xs text-purple-700">Reports below are municipality-wide reference data. Barangay-level report generation for your assigned barangay is coming soon.</p>
        </div>

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-4">
            <button @click="tab = 'reports'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'reports' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="file-chart-column" class="w-3.5 h-3.5"></i>Reports</button>
            <button @click="tab = 'analytics'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'analytics' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="trending-up" class="w-3.5 h-3.5"></i>Analytics</button>
        </div>

        <!-- Reports -->
        <div x-show="tab === 'reports'">
            <h3 class="text-sm font-semibold text-navy-900 mb-3">Generate a Report</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 mb-4">
                @foreach($reportTypes as $i => $report)
                    {{-- Stagger only the first 3 cards (choreography rule: max 3 steps, rest appear together) --}}
                    <x-ui.card class="group cursor-pointer animate-fade-up" style="animation-delay: {{ min($i, 2) * 40 }}ms" @click="$dispatch('toast', { message: 'Generating {{ $report['title'] }}…', variant: 'info' })">
                        <div class="flex items-start gap-3.5">
                            <span class="w-11 h-11 rounded-xl {{ $report['color'] }} flex items-center justify-center shrink-0 transition-transform duration-[220ms] ease-standard group-hover:scale-110">
                                <i data-lucide="{{ $report['icon'] }}" class="w-5 h-5"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-navy-900">{{ $report['title'] }}</p>
                                <p class="text-xs text-slate-400 mt-1">{{ $report['desc'] }}</p>
                            </div>
                        </div>
                        <div class="mt-3.5 pt-3.5 border-t border-slate-100 flex items-center gap-1.5 text-xs font-medium text-brand-600">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>Generate Report
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            <h3 class="text-sm font-semibold text-navy-900 mb-3">Recently Generated</h3>
            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Report</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Generated By</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Date</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Format</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($recent as $r)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $r['name'] }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ $r['by'] }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ $r['generated'] }}</td>
                            <td class="px-4 py-3"><span class="text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">{{ $r['format'] }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <button @click="$dispatch('toast', { message: 'Report download is a frontend preview — no file is generated.', variant: 'info' })" class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="download" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </div>

        <!-- Analytics -->
        <div x-show="tab === 'analytics'" x-cloak>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card label="Avg. Satisfaction Score" value="4.6 / 5" icon="smile" color="green" trend="+0.2" :delay="0" />
                <x-ui.stat-card label="Digital Adoption Rate" value="63%" icon="smartphone" color="brand" trend="+11%" sublabel="Requests via portal" :delay="40" />
                <x-ui.stat-card label="Avg. Resolution Time" value="2.4 days" icon="timer" color="purple" trendDirection="down" trend="-0.6 days" :delay="80" />
                <x-ui.stat-card label="Repeat Requesters" value="38%" icon="repeat" color="gold" :delay="120" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                <x-ui.card class="lg:col-span-2">
                    <h3 class="text-sm font-semibold text-navy-900 mb-1">Requests Trend (YTD)</h3>
                    <div class="flex items-center gap-4 text-xs text-slate-400 mb-4">
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-500"></span>Dokyu</span>
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-purple-500"></span>Tulong</span>
                    </div>
                    <div class="h-64" x-data="lineChart({
                        labels: @js($trendLabels),
                        datasets: [
                            { label: 'Dokyu', data: @js($trendDocuments), color: '#2f62f5', fill: 'rgba(47,98,245,0.08)' },
                            { label: 'Tulong', data: @js($trendAssistance), color: '#a855f7', fill: 'rgba(168,85,247,0.06)' },
                        ],
                    })">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4">Requests by Channel</h3>
                    <div
                        class="relative w-36 h-36 mx-auto"
                        x-data="donutChart({ labels: @js(array_column($channelSplit, 'label')), values: @js(array_column($channelSplit, 'value')), colors: @js(array_column($channelSplit, 'color')) })"
                    >
                        <canvas x-ref="canvas"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-xl font-semibold text-navy-900 tabular-nums">{{ number_format($channelTotal) }}</span>
                            <span class="text-[11px] text-slate-400">Total</span>
                        </div>
                    </div>
                    <div class="mt-5 space-y-2.5">
                        @foreach($channelSplit as $row)
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-slate-500">
                                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $row['color'] }}"></span>
                                    {{ $row['label'] }}
                                </span>
                                <span class="font-medium text-navy-900 tabular-nums">{{ number_format($row['value']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            <x-ui.card>
                <h3 class="text-sm font-semibold text-navy-900 mb-4">Top Barangays by Digital Engagement</h3>
                <div class="space-y-4">
                    @foreach($topBarangays as $b)
                        <div x-show="$store.session.inScope('{{ $b['name'] }}')" x-cloak>
                            <div class="flex items-center justify-between text-sm mb-1.5">
                                <span class="text-slate-600 font-medium">Brgy. {{ $b['name'] }}</span>
                                <span class="text-slate-400">{{ $b['pct'] }}%</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-400 transition-all duration-[600ms] ease-standard" style="width: 0%" x-init="setTimeout(() => $el.style.width = '{{ $b['pct'] }}%', 150)"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        <h3 class="text-sm font-semibold text-navy-900 mb-3 mt-2">Internal Forms &amp; Incident Reports</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card label="Internal Forms Submitted" value="{{ count($internalSubmissions) }}" icon="clipboard-list" color="purple" :delay="0" />
            <x-ui.stat-card label="Offices Covered" value="{{ count($internalFormsCfg['offices']) }}" icon="building-2" color="brand" :delay="40" />
            <x-ui.stat-card label="Incidents Logged (SWM-2026-07)" value="{{ $incidentTotal }}" icon="triangle-alert" color="orange" :delay="80" />
            <x-ui.stat-card label="Critical / High Severity" value="{{ $incidentSeveritySplit[0]['value'] + $incidentSeveritySplit[1]['value'] }}" icon="siren" color="red" :delay="120" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <x-ui.card>
                <h3 class="text-sm font-semibold text-navy-900 mb-4">Internal Forms by Office</h3>
                <div
                    class="relative w-36 h-36 mx-auto"
                    x-data="donutChart({ labels: @js(array_column($internalOfficeSplit, 'label')), values: @js(array_column($internalOfficeSplit, 'value')), colors: @js(array_column($internalOfficeSplit, 'color')) })"
                >
                    <canvas x-ref="canvas"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-xl font-semibold text-navy-900 tabular-nums">{{ count($internalSubmissions) }}</span>
                        <span class="text-[11px] text-slate-400">Total</span>
                    </div>
                </div>
                <div class="mt-5 space-y-2.5">
                    @foreach($internalOfficeSplit as $row)
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 text-slate-500">
                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $row['color'] }}"></span>
                                {{ $row['label'] }}
                            </span>
                            <span class="font-medium text-navy-900 tabular-nums">{{ number_format($row['value']) }}</span>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card>
                <h3 class="text-sm font-semibold text-navy-900 mb-4">Incident Reports by Severity</h3>
                <div
                    class="relative w-36 h-36 mx-auto"
                    x-data="donutChart({ labels: @js(array_column($incidentSeveritySplit, 'label')), values: @js(array_column($incidentSeveritySplit, 'value')), colors: @js(array_column($incidentSeveritySplit, 'color')) })"
                >
                    <canvas x-ref="canvas"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-xl font-semibold text-navy-900 tabular-nums">{{ $incidentTotal }}</span>
                        <span class="text-[11px] text-slate-400">Total</span>
                    </div>
                </div>
                <div class="mt-5 space-y-2.5">
                    @foreach($incidentSeveritySplit as $row)
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 text-slate-500">
                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $row['color'] }}"></span>
                                {{ $row['label'] }}
                            </span>
                            <span class="font-medium text-navy-900 tabular-nums">{{ number_format($row['value']) }}</span>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        </div>
    </div>
</x-layouts.admin>

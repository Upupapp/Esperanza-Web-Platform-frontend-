{{-- tab-profiling.blade.php — Profiling Coverage Tab --}}
{{-- Rendered inside constituents.blade.php Alpine x-data scope --}}
{{-- GET /admin/coverage via loadRegistry() -> barangayCoverage[] + coverageMunicipal
     (CoverageCalculator; the mock fixture this replaces was, field for field, an
     almost exact match for this endpoint's shape -- likely its original source). --}}

<div class="space-y-6">

    {{-- ── SUMMARY STAT CARDS ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-ui.stat-card
            label="Barangays Complete"
            valueBind="barangayCoverage.filter(b => b.status === 'Complete').length + '/' + barangayCoverage.length"
            icon="map-pin"
            color="brand"
            :delay="0"
        />
        <x-ui.stat-card
            label="Households Recorded"
            valueBind="(coverageMunicipal?.households ?? barangayCoverage.reduce((s,b) => s + b.hh, 0)).toLocaleString()"
            icon="house"
            color="green"
            :delay="75"
        />
        <x-ui.stat-card
            label="Avg. Completion Rate"
            valueBind="(coverageMunicipal?.coveragePct ?? Math.round(barangayCoverage.reduce((s,b) => s + b.pct, 0) / (barangayCoverage.length || 1))) + '%'"
            icon="clipboard-check"
            color="purple"
            :delay="150"
        />
        <x-ui.stat-card
            label="Barangays Below 60%"
            valueBind="barangayCoverage.filter(b => b.pct < 60).length"
            icon="triangle-alert"
            color="red"
            sublabel="Needs field verification"
            :delay="225"
        />
    </div>

    {{-- ── COVERAGE STATUS DONUT + LEGEND ROW ────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Donut chart card --}}
        <x-ui.card class="flex flex-col items-center justify-center py-6 gap-4">
            <h3 class="text-sm font-semibold text-navy-900 tracking-tight self-start px-1">
                Status Breakdown
            </h3>

            <div
                x-data="{ chart: null }"
                x-init="$watch('barangayStatusBreakdown', async (rows) => {
                    const Chart = await window.loadChart();
                    if (chart) chart.destroy();
                    chart = new Chart($refs.canvas, {
                        type: 'doughnut',
                        data: {
                            labels: rows.map(r => r.label),
                            datasets: [{
                                data: rows.map(r => r.value),
                                backgroundColor: rows.map(r => r.color),
                                borderColor: '#ffffff',
                                borderWidth: 3,
                                hoverOffset: 6,
                            }],
                        },
                        options: {
                            cutout: '68%',
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: (ctx) => ' ' + ctx.label + ': ' + ctx.raw + ' barangay' + (ctx.raw > 1 ? 's' : '') } },
                            },
                        },
                    });
                }, { immediate: true })"
                class="relative w-40 h-40"
            >
                <canvas x-ref="canvas" class="w-full h-full"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-2xl font-bold text-navy-900" x-text="barangayCoverage.length"></span>
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider mt-0.5">Barangays</span>
                </div>
            </div>

            {{-- Legend --}}
            <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs w-full px-2">
                <template x-for="row in barangayStatusBreakdown" :key="row.label">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background-color:' + row.color"></span>
                        <span class="text-slate-600" x-text="row.label"></span>
                        <span class="font-semibold text-navy-900" x-text="'(' + row.value + ')'"></span>
                    </div>
                </template>
            </div>
        </x-ui.card>

        {{-- Coverage progress overview --}}
        <x-ui.card class="lg:col-span-2 flex flex-col gap-3 py-5 px-5">
            <h3 class="text-sm font-semibold text-navy-900 tracking-tight mb-1">
                Overall Municipal Coverage
            </h3>

            <div class="space-y-3">
                {{-- Resident profiling bar --}}
                <div x-data="{
                    get n() { return coverageMunicipal?.verified ?? barangayCoverage.reduce((s,b) => s + b.profiled, 0) },
                    get d() { return coverageMunicipal?.residents ?? barangayCoverage.reduce((s,b) => s + b.residents, 0) },
                    get pct() { return this.d === 0 ? 0 : Math.round((this.n / this.d) * 100) },
                }">
                    <div class="flex justify-between text-xs text-slate-600 mb-1">
                        <span>Residents Profiled</span>
                        <span class="font-semibold text-navy-900">
                            <span x-text="n.toLocaleString()"></span> / <span x-text="d.toLocaleString()"></span> &mdash;
                            <span class="text-emerald-600" x-text="pct + '%'"></span>
                        </span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full transition-all duration-[600ms] ease-standard" :style="'width:' + pct + '%'"></div>
                    </div>
                </div>
                {{-- Household profiling bar --}}
                <div x-data="{
                    get n() { return barangayCoverage.reduce((s,b) => s + b.hh_profiled, 0) },
                    get d() { return barangayCoverage.reduce((s,b) => s + b.hh, 0) },
                    get pct() { return this.d === 0 ? 0 : Math.round((this.n / this.d) * 100) },
                }">
                    <div class="flex justify-between text-xs text-slate-600 mb-1">
                        <span>Households Profiled</span>
                        <span class="font-semibold text-navy-900">
                            <span x-text="n.toLocaleString()"></span> / <span x-text="d.toLocaleString()"></span> &mdash;
                            <span class="text-blue-600" x-text="pct + '%'"></span>
                        </span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full transition-all duration-[600ms] ease-standard" :style="'width:' + pct + '%'"></div>
                    </div>
                </div>
                {{-- Barangays with ≥80% bar --}}
                <div x-data="{
                    get n() { return barangayCoverage.filter(b => b.pct >= 80).length },
                    get d() { return barangayCoverage.length },
                    get pct() { return this.d === 0 ? 0 : Math.round((this.n / this.d) * 100) },
                }">
                    <div class="flex justify-between text-xs text-slate-600 mb-1">
                        <span>Barangays &ge; 80% Complete</span>
                        <span class="font-semibold text-navy-900">
                            <span x-text="n"></span> / <span x-text="d"></span> &mdash;
                            <span class="text-purple-600" x-text="pct + '%'"></span>
                        </span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-500 rounded-full transition-all duration-[600ms] ease-standard" :style="'width:' + pct + '%'"></div>
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-slate-500 mt-auto pt-2 border-t border-slate-100" x-data="{ today: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }">
                <i data-lucide="info" class="w-3 h-3 inline -mt-0.5 mr-0.5"></i>
                Data as of <span x-text="today"></span>. Figures are computed live from resident, household and barangay-profiling records.
            </p>
        </x-ui.card>
    </div>

    {{-- ── BARANGAY COVERAGE TABLE ─────────────────────────────────────────── --}}
    <x-ui.card class="overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-semibold text-navy-900 tracking-tight">Barangay-Level Coverage</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    <span x-text="isMunicipalScope ? barangayCoverage.length + ' barangays of Esperanza' : 'Brgy. ' + $store.session.account?.scope"></span>
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button
                    @click="exportCSV(barangayCoverage, 'profiling-coverage-export.csv')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 hover:border-slate-300 transition"
                >
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
                </button>
            </div>
        </div>

        <x-ui.table>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Barangay</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Residents Profiled</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Households Profiled</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap w-32">Coverage %</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Last Visit</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Assigned Encoder</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    <template x-for="b in barangayCoverage" :key="b.name">
                        <tr x-show="$store.session.inScope(b.name)" class="hover:bg-slate-50/70 transition-colors group" x-cloak>
                            {{-- Barangay name --}}
                            <td class="px-4 py-3 font-medium text-navy-900 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                          :class="b.pct >= 90 ? 'bg-emerald-500' : (b.pct >= 70 ? 'bg-amber-400' : 'bg-rose-500')"></span>
                                    <span x-text="'Brgy. ' + b.name"></span>
                                </div>
                            </td>

                            {{-- Residents --}}
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap tabular-nums hidden xl:table-cell">
                                <span class="font-semibold text-navy-900" x-text="b.profiled.toLocaleString()"></span>
                                <span class="text-slate-500 text-xs" x-text="' / ' + b.residents.toLocaleString()"></span>
                            </td>

                            {{-- Households --}}
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap tabular-nums hidden 2xl:table-cell">
                                <span class="font-semibold text-navy-900" x-text="b.hh_profiled.toLocaleString()"></span>
                                <span class="text-slate-500 text-xs" x-text="' / ' + b.hh.toLocaleString()"></span>
                            </td>

                            {{-- Coverage % with progress bar --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 min-w-[90px]">
                                    <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full"
                                             :class="b.pct >= 90 ? 'bg-emerald-500' : (b.pct >= 70 ? 'bg-amber-400' : 'bg-rose-500')"
                                             :style="'width:' + b.pct + '%'"></div>
                                    </div>
                                    <span class="text-xs font-semibold tabular-nums w-8 text-right shrink-0"
                                          :class="b.pct >= 90 ? 'text-emerald-600' : (b.pct >= 70 ? 'text-amber-600' : 'text-rose-600')"
                                          x-text="b.pct + '%'"></span>
                                </div>
                            </td>

                            {{-- Last visit --}}
                            <td class="px-4 py-3 whitespace-nowrap hidden 2xl:table-cell">
                                <span class="text-slate-700 text-xs"
                                      x-text="b.last_visit ? new Date(b.last_visit).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : 'Not yet visited'"></span>
                            </td>

                            {{-- Encoder --}}
                            <td class="px-4 py-3 whitespace-nowrap hidden 2xl:table-cell">
                                <div class="flex items-center gap-2" x-show="b.encoder">
                                    <span class="w-6 h-6 rounded-full bg-brand-100 text-brand-700 text-[10px] font-bold flex items-center justify-center shrink-0 uppercase"
                                          x-text="b.encoder ? b.encoder.charAt(0) : '?'"></span>
                                    <span class="text-xs text-slate-700" x-text="b.encoder"></span>
                                </div>
                                <span class="text-xs text-slate-500" x-show="!b.encoder">Unassigned</span>
                            </td>

                            {{-- Status chip --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ring-1"
                                      :class="{
                                          'text-emerald-700 bg-emerald-50 ring-emerald-200': b.status === 'Complete',
                                          'text-blue-700 bg-blue-50 ring-blue-200': b.status === 'In Progress',
                                          'text-amber-700 bg-amber-50 ring-amber-200': b.status === 'Needs Follow-up',
                                          'text-rose-700 bg-rose-50 ring-rose-200': !b.status || b.status === 'Not Started (Re-visit Required)',
                                      }"
                                      x-text="b.status || 'Not Started (Re-visit Required)'"></span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button
                                    @click="goToBarangayResidents(b.name)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium text-brand-700 bg-brand-50 hover:bg-brand-100 ring-1 ring-brand-200 transition"
                                    :title="'View residents in Brgy. ' + b.name"
                                >
                                    <i data-lucide="users" class="w-3 h-3"></i> View Residents
                                </button>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty state --}}
                    <tr x-show="barangayCoverage.length === 0" x-cloak>
                        <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">No barangay coverage data available.</td>
                    </tr>
                </tbody>
            </table>
        </x-ui.table>

        {{-- Table footer --}}
        <div class="flex items-center justify-between gap-2 px-5 py-3 border-t border-slate-100 bg-slate-50/50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-navy-900" x-text="barangayCoverage.length"></span> barangay(s) &mdash; Municipality of Esperanza, Masbate
            </p>
        </div>
    </x-ui.card>

</div>

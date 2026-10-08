{{-- tab-profiling.blade.php — Profiling Coverage Tab --}}
{{-- Rendered inside constituents.blade.php Alpine x-data scope --}}
{{-- GET /admin/coverage via loadRegistry() -> barangayCoverage[] + coverageMunicipal
     (CoverageCalculator; the mock fixture this replaces was, field for field, an
     almost exact match for this endpoint's shape -- likely its original source). --}}

<div class="space-y-6">

    {{-- ── SUMMARY STAT CARDS ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-ui.stat-card layout="compact"
            label="Barangays Complete"
            valueBind="barangayCoverage.filter(b => b.status === 'Complete').length + '/' + barangayCoverage.length"
            icon="map-pin"
            color="brand"
            :delay="0"
        />
        <x-ui.stat-card layout="compact"
            label="Households Recorded"
            valueBind="(coverageMunicipal?.households ?? barangayCoverage.reduce((s,b) => s + b.hh, 0)).toLocaleString()"
            icon="house"
            color="green"
            :delay="75"
        />
        <x-ui.stat-card layout="compact"
            label="Avg. Completion Rate"
            valueBind="(coverageMunicipal?.coveragePct ?? Math.round(barangayCoverage.reduce((s,b) => s + b.pct, 0) / (barangayCoverage.length || 1))) + '%'"
            icon="clipboard-check"
            color="purple"
            :delay="150"
        />
        <x-ui.stat-card layout="compact"
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
        <x-ui.card class="flex flex-col items-center gap-4">
            <h3 class="text-lg font-extrabold text-ink self-start px-1">Status Breakdown</h3>

            {{-- The dashboard's clay doughnut; the status colours mean something, so they stay. --}}
            <x-ui.clay-donut rows="barangayStatusBreakdown" total-label="Barangays" :keep-colors="true" empty="No barangay coverage data yet." class="w-full" />
        </x-ui.card>

        {{-- Coverage progress overview --}}
        <x-ui.card class="lg:col-span-2 flex flex-col gap-3">
            <h3 class="text-lg font-extrabold text-ink mb-1">Overall Municipal Coverage</h3>

            <div class="space-y-3">
                {{-- Resident profiling bar --}}
                <div x-data="{
                    get n() { return coverageMunicipal?.verified ?? barangayCoverage.reduce((s,b) => s + b.profiled, 0) },
                    get d() { return coverageMunicipal?.residents ?? barangayCoverage.reduce((s,b) => s + b.residents, 0) },
                    get pct() { return this.d === 0 ? 0 : Math.round((this.n / this.d) * 100) },
                }">
                    <div class="flex justify-between gap-3 text-[13.5px] font-semibold text-ink-body mb-2">
                        <span>Residents Profiled</span>
                        <span class="font-semibold text-ink">
                            <span x-text="n.toLocaleString()"></span> / <span x-text="d.toLocaleString()"></span> &mdash;
                            <span class="text-brand-600" x-text="pct + '%'"></span>
                        </span>
                    </div>
                    <div class="h-3.5 rounded-full bg-track shadow-pressed-sm p-[2px]">
                        <div class="h-full rounded-full bg-gradient-to-r shadow-[inset_0_2px_2px_rgb(255_255_255/.45),inset_0_-2px_3px_rgb(11_23_48/.3)] transition-all duration-[600ms] ease-standard from-brand-400 to-brand-600" :style="'width:' + pct + '%'"></div>
                    </div>
                </div>
                {{-- Household profiling bar --}}
                <div x-data="{
                    get n() { return barangayCoverage.reduce((s,b) => s + b.hh_profiled, 0) },
                    get d() { return barangayCoverage.reduce((s,b) => s + b.hh, 0) },
                    get pct() { return this.d === 0 ? 0 : Math.round((this.n / this.d) * 100) },
                }">
                    <div class="flex justify-between gap-3 text-[13.5px] font-semibold text-ink-body mb-2">
                        <span>Households Profiled</span>
                        <span class="font-semibold text-ink">
                            <span x-text="n.toLocaleString()"></span> / <span x-text="d.toLocaleString()"></span> &mdash;
                            <span class="text-brand-600" x-text="pct + '%'"></span>
                        </span>
                    </div>
                    <div class="h-3.5 rounded-full bg-track shadow-pressed-sm p-[2px]">
                        <div class="h-full rounded-full bg-gradient-to-r shadow-[inset_0_2px_2px_rgb(255_255_255/.45),inset_0_-2px_3px_rgb(11_23_48/.3)] transition-all duration-[600ms] ease-standard from-brand-500 to-brand-700" :style="'width:' + pct + '%'"></div>
                    </div>
                </div>
                {{-- Barangays with ≥80% bar --}}
                <div x-data="{
                    get n() { return barangayCoverage.filter(b => b.pct >= 80).length },
                    get d() { return barangayCoverage.length },
                    get pct() { return this.d === 0 ? 0 : Math.round((this.n / this.d) * 100) },
                }">
                    <div class="flex justify-between gap-3 text-[13.5px] font-semibold text-ink-body mb-2">
                        <span>Barangays &ge; 80% Complete</span>
                        <span class="font-semibold text-ink">
                            <span x-text="n"></span> / <span x-text="d"></span> &mdash;
                            <span class="text-brand-600" x-text="pct + '%'"></span>
                        </span>
                    </div>
                    <div class="h-3.5 rounded-full bg-track shadow-pressed-sm p-[2px]">
                        <div class="h-full rounded-full bg-gradient-to-r shadow-[inset_0_2px_2px_rgb(255_255_255/.45),inset_0_-2px_3px_rgb(11_23_48/.3)] transition-all duration-[600ms] ease-standard from-gold-300 to-gold-500" :style="'width:' + pct + '%'"></div>
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-ink-muted mt-auto pt-2 border-t border-brand-600/10" x-data="{ today: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }">
                <i data-lucide="info" class="w-3 h-3 inline -mt-0.5 mr-0.5"></i>
                Data as of <span x-text="today"></span>. Figures are computed live from resident, household and barangay-profiling records.
            </p>
        </x-ui.card>
    </div>

    {{-- ── BARANGAY COVERAGE TABLE ─────────────────────────────────────────── --}}
    <x-ui.card class="overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-brand-600/10">
            <div>
                <h3 class="text-lg font-extrabold text-ink">Barangay-Level Coverage</h3>
                <p class="text-xs text-ink-muted mt-0.5">
                    <span x-text="isMunicipalScope ? barangayCoverage.length + ' barangays of Esperanza' : 'Brgy. ' + $store.session.account?.scope"></span>
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button
                    @click="exportCSV(barangayCoverage, 'profiling-coverage-export.csv')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-2xl surface-glass-sm text-ink-body hover:bg-white/90 transition"
                >
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
                </button>
            </div>
        </div>

        <x-ui.table>
            <table class="min-w-full divide-y divide-brand-600/[.08] text-sm">
                <thead class="bg-well/70">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Barangay</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden xl:table-cell">Residents Profiled</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Households Profiled</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap w-32">Coverage %</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Last Visit</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Assigned Encoder</th>
                        <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-right text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-600/[.08]">
                    <template x-for="b in barangayCoverage" :key="b.name">
                        <tr x-show="$store.session.inScope(b.name)" class="hover:bg-white/60 transition-colors group" x-cloak>
                            {{-- Barangay name --}}
                            <td class="px-4 py-3 font-medium text-ink whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                          :class="b.pct >= 90 ? 'bg-emerald-500' : (b.pct >= 70 ? 'bg-amber-400' : 'bg-rose-500')"></span>
                                    <span x-text="'Brgy. ' + b.name"></span>
                                </div>
                            </td>

                            {{-- Residents --}}
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap tabular-nums hidden xl:table-cell">
                                <span class="font-semibold text-ink" x-text="b.profiled.toLocaleString()"></span>
                                <span class="text-ink-muted text-xs" x-text="' / ' + b.residents.toLocaleString()"></span>
                            </td>

                            {{-- Households --}}
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap tabular-nums hidden 2xl:table-cell">
                                <span class="font-semibold text-ink" x-text="b.hh_profiled.toLocaleString()"></span>
                                <span class="text-ink-muted text-xs" x-text="' / ' + b.hh.toLocaleString()"></span>
                            </td>

                            {{-- Coverage % with progress bar --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 min-w-[90px]">
                                    <div class="flex-1 h-2.5 rounded-full bg-track shadow-pressed-sm p-[1.5px]">
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
                                <span class="text-xs text-ink-muted" x-show="!b.encoder">Unassigned</span>
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
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-medium text-brand-700 bg-brand-50 hover:bg-brand-100 ring-1 ring-brand-200 transition"
                                    :title="'View residents in Brgy. ' + b.name"
                                >
                                    <i data-lucide="users" class="w-3 h-3"></i> View Residents
                                </button>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty state --}}
                    <tr x-show="barangayCoverage.length === 0" x-cloak>
                        <td colspan="8" class="px-6 py-10 text-center text-sm text-ink-muted">No barangay coverage data available.</td>
                    </tr>
                </tbody>
            </table>
        </x-ui.table>

        {{-- Table footer --}}
        <div class="flex items-center justify-between gap-2 px-5 py-3 border-t border-brand-600/10 bg-well/50">
            <p class="text-xs text-ink-muted">
                Showing <span class="font-semibold text-ink" x-text="barangayCoverage.length"></span> barangay(s) &mdash; Municipality of Esperanza, Masbate
            </p>
        </div>
    </x-ui.card>

</div>

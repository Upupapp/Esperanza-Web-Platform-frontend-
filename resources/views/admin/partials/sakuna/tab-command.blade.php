{{--
    resources/views/admin/partials/sakuna/tab-command.blade.php
    Emergency Command Center tab — SAKUNA Disaster Management Module
    @include'd from admin/sakuna.blade.php — Alpine state inherited from parent x-data.
    DO NOT add x-data at the root level of this file.

    GET /sakuna/command-center (App\Domain\Sakuna\CommandCenter::board) is a
    read-only projection over everything else in Sakuna -- "BUILD IT AS A
    PROJECTION, NOT ANOTHER WRITE PATH" per its own docblock. Loaded via
    loadCommandCenter() in the parent x-data, alongside GET /sakuna/incidents
    for a true severity-ranked priority queue (the projection's own `markers`
    only cover incidents that carry lat/lng, so it isn't a complete ranking).

    Active Incident Locations is a real map (2026-10-26, sakunaCommandMap in
    resources/js/sakuna-map.js): the board's `markers` and `center_markers`
    at their saved coordinates. An incident without a pin is counted, not
    placed; it stays in the queue below and on the Incidents tab.

    Everything the mock's KPI row, map panel, "Operational Alerts", "Recent
    Activity Feed" and "Pending Decisions" showed was fabricated -- none of
    it (responder/vehicle counts, priority-needs counts, per-barangay
    population/pulse map states, supply-threshold/road-status/generator-fuel
    warnings, an EOC decision queue) exists anywhere in the schema. What's
    below is what the projection actually returns.
--}}

<div x-init="loadCommandCenter()">

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- A. LOADING / ERROR                                                           --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div x-show="commandLoading" class="space-y-5">
    {{-- KPI skeleton --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
        @for ($i = 0; $i < 6; $i++)
            <div class="surface-glass rounded-[22px] min-h-[136px] p-4 space-y-3 animate-pulse">
                <div class="h-3 bg-slate-100 rounded w-20"></div>
                <div class="h-7 bg-slate-100 rounded-lg w-12"></div>
                <div class="h-3 bg-slate-100 rounded w-16"></div>
            </div>
        @endfor
    </div>
    {{-- Map skeleton --}}
    <div class="surface-glass rounded-[26px] p-5 animate-pulse">
        <div class="h-5 bg-slate-100 rounded w-80 mb-4"></div>
        <div class="h-60 bg-slate-100 rounded-xl"></div>
    </div>
    {{-- Incident queue + evac overview skeleton --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
        <div class="lg:col-span-3 surface-glass rounded-[26px] p-5 space-y-3 animate-pulse">
            <div class="h-5 bg-slate-100 rounded w-48 mb-2"></div>
            @for ($i = 0; $i < 6; $i++)
                <div class="h-11 bg-slate-100 rounded-xl"></div>
            @endfor
        </div>
        <div class="lg:col-span-2 surface-glass rounded-[26px] p-5 space-y-3 animate-pulse">
            <div class="h-5 bg-slate-100 rounded w-48 mb-2"></div>
            @for ($i = 0; $i < 5; $i++)
                <div class="h-16 bg-slate-100 rounded-xl"></div>
            @endfor
        </div>
    </div>
</div>

<div x-show="commandFailed" x-cloak>
    <x-ui.error-state messageExpr="'Could not load the Command Center.'" retryExpr="loadCommandCenter()" :retryable="true" />
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- B. MAIN CONTENT                                                              --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div
    x-show="!commandLoading && !commandFailed"
    x-cloak
    x-transition:enter="transition duration-[300ms] ease-standard"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="space-y-5"
>

    {{-- ── B1. KPI Cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
        <x-ui.stat-card layout="compact" tone="rose"   object="flame"    label="Open Incidents"       valueBind="commandBoard.counts.incidents_open"     sublabelBind="commandBoard.counts.incidents_total + ' total'" @click="setTab('incidents')" :delay="0" />
        <x-ui.stat-card layout="compact" tone="teal"   object="building" label="Evac. Centers Open"   valueBind="commandBoard.counts.centers_open"       sublabelBind="commandBoard.centers.filter(c => c.pct >= 75).length + ' near/over capacity'" @click="setTab('evacuation-centers')" :delay="40" />
        <x-ui.stat-card layout="compact" tone="brand"  object="people"   label="Families Sheltered"   valueBind="commandBoard.counts.families_sheltered" sublabelBind="commandBoard.counts.individuals_sheltered + (commandBoard.counts.individuals_sheltered === 1 ? ' individual' : ' individuals') + ((commandBoard.counts.houses_totally_damaged || commandBoard.counts.houses_partially_damaged) ? ' · houses: ' + (commandBoard.counts.houses_totally_damaged || 0) + ' totally, ' + (commandBoard.counts.houses_partially_damaged || 0) + ' partly damaged' : '')" @click="setTab('evacuees')" :delay="80" />
        <x-ui.stat-card layout="compact" tone="gold"   object="box"      label="Relief Released"      valueBind="commandBoard.counts.relief_released"    @click="setTab('relief')" :delay="120" />
        <x-ui.stat-card layout="compact" tone="violet" object="document" label="Damage Reports"       valueBind="commandBoard.counts.damage_reports"     @click="setTab('damage-assessment')" :delay="160" />
        <x-ui.stat-card layout="compact" tone="amber"  object="bell"     label="Active Public Alerts" valueBind="commandBoard.counts.alerts_live"        @click="setTab('alerts')" :delay="200" />
    </div>{{-- /KPI Cards --}}


    {{-- ── B2. Active Incident Locations ───────────────────────────────────── --}}
    {{-- A real map (2026-10-26): incidents and evacuation centres at the
         coordinates saved on their records, nothing estimated. --}}
    <div class="surface-glass rounded-[26px] overflow-hidden" x-data="sakunaCommandMap" x-effect="draw()">
        <div class="px-5 py-4 border-b border-brand-600/10 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-ink-body">Active Incident Locations</h3>
                <p class="text-xs text-ink-muted mt-0.5">Incidents and evacuation centres at the places saved on their records</p>
            </div>
            <button type="button" @click="fit()" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 surface-glass-sm rounded-2xl px-3 py-1.5 hover:bg-white/90 transition-colors">
                <i data-lucide="scan" class="w-3.5 h-3.5"></i> Fit to records
            </button>
        </div>

        <div class="p-5 space-y-3">
            {{-- Filters --}}
            <div class="flex flex-wrap items-center gap-2">
                <select aria-label="Incident status on the map" x-model="statusFilter"
                    class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="open">Open incidents</option>
                    <option value="all">All on the map (incl. resolved)</option>
                    <template x-for="st in mapStatuses" :key="st"><option :value="st" x-text="'Only ' + incidentStatusLabel(st)"></option></template>
                </select>
                <select aria-label="Severity on the map" x-model="severityFilter"
                    class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">All severities</option>
                    <option>Critical</option><option>High</option><option>Medium</option><option>Low</option>
                </select>
                <select aria-label="Incident type on the map" x-model="typeFilter"
                    class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">All types</option>
                    <template x-for="ty in mapTypes" :key="ty"><option :value="ty" x-text="ty"></option></template>
                </select>
                <label class="inline-flex items-center gap-2 text-sm text-slate-600 surface-glass-sm rounded-2xl px-3 py-2 cursor-pointer select-none">
                    <input type="checkbox" x-model="showCentres" class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-400">
                    Evacuation centres
                </label>
            </div>

            {{-- The map --}}
            <div class="relative">
                <div x-ref="map" class="sakuna-map h-[360px] sm:h-[460px] w-full rounded-2xl overflow-hidden border border-brand-600/10"
                     role="application" aria-label="Map of incidents and evacuation centres. Select a marker for its summary."></div>
                <div x-show="mapState === 'loading'" class="pointer-events-none absolute inset-x-0 top-3 flex justify-center z-[500]">
                    <span class="text-[11px] font-semibold text-slate-600 bg-white/90 rounded-full px-2.5 py-1 shadow">Loading the map…</span>
                </div>
                <div x-show="mapState !== 'failed' && !commandLoading && nothingToPlot" x-cloak class="pointer-events-none absolute inset-x-0 bottom-8 flex justify-center px-4 z-[500]">
                    <p class="text-xs text-slate-700 bg-white/95 rounded-2xl px-3.5 py-2 shadow text-center max-w-md">
                        Nothing to plot with these filters: no incident or centre here has a saved map location.
                    </p>
                </div>
            </div>
            <div x-show="mapState === 'failed'" x-cloak role="alert" class="rounded-2xl bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-900">
                <p class="font-semibold">The map background could not load (no connection, or the map service is busy).</p>
                <p class="mt-0.5">The markers are still placed at their saved coordinates; the queue below lists every incident.</p>
                <button type="button" @click="retryMap()" class="mt-1 font-semibold underline underline-offset-2">Try again</button>
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 pt-1">
                <span class="flex items-center gap-1.5 text-xs text-ink-muted"><span class="w-3 h-3 rounded-full bg-rose-500 ring-2 ring-white shadow flex-shrink-0"></span>Critical</span>
                <span class="flex items-center gap-1.5 text-xs text-ink-muted"><span class="w-3 h-3 rounded-full bg-orange-500 ring-2 ring-white shadow flex-shrink-0"></span>High</span>
                <span class="flex items-center gap-1.5 text-xs text-ink-muted"><span class="w-3 h-3 rounded-full bg-amber-400 ring-2 ring-white shadow flex-shrink-0"></span>Medium</span>
                <span class="flex items-center gap-1.5 text-xs text-ink-muted"><span class="w-3 h-3 rounded-full bg-slate-400 ring-2 ring-white shadow flex-shrink-0"></span>Low</span>
                <span class="flex items-center gap-1.5 text-xs text-ink-muted"><span class="w-3.5 h-3.5 rounded bg-teal-600 ring-2 ring-white shadow flex-shrink-0"></span>Centre, open</span>
                <span class="flex items-center gap-1.5 text-xs text-ink-muted"><span class="w-3.5 h-3.5 rounded bg-slate-500 ring-2 ring-white shadow flex-shrink-0"></span>Centre, not open</span>
                <span class="text-[11px] text-ink-muted italic sm:ml-auto">A numbered circle groups nearby incidents (its colour is the most severe); zoom in or select it.</span>
            </div>

            {{-- Open incidents nobody pinned: counted, never placed at a guessed point. --}}
            <p x-show="unmapped > 0" x-cloak class="flex items-start gap-2 rounded-2xl surface-pressed-sm px-3.5 py-2.5 text-xs text-slate-700">
                <i data-lucide="map-pin-off" class="w-4 h-4 text-amber-600 flex-shrink-0 mt-px"></i>
                <span x-text="unmapped === 1
                    ? '1 open incident has no map location, so it is not on the map. It is in the queue below and on the Incidents tab, where staff can pin it.'
                    : unmapped + ' open incidents have no map location, so they are not on the map. They are in the queue below and on the Incidents tab, where staff can pin them.'"></span>
            </p>
        </div>
    </div>{{-- /Map Panel --}}


    {{-- ── B3. Priority Incident Queue + Evacuation Center Overview ─────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

        {{-- Left: Priority Incident Queue --}}
        <div class="lg:col-span-3 surface-glass rounded-[26px] overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-brand-600/10">
                <div>
                    <h3 class="text-sm font-semibold text-ink-body">Priority Incident Queue</h3>
                    <p class="text-xs text-ink-muted mt-0.5">Top 6 open incidents by severity</p>
                </div>
                <button @click="setTab('incidents')" class="text-xs text-brand-600 hover:text-brand-700 font-medium flex items-center gap-1 transition-colors">
                    View all <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="overflow-x-auto focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 rounded-xl" tabindex="0" role="region" aria-label="Priority incident queue (scrolls sideways)">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-brand-600/10">
                            <th class="text-left px-4 py-2.5 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]">Ref</th>
                            <th class="text-left px-4 py-2.5 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]">Type</th>
                            <th class="text-left px-4 py-2.5 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]">Barangay</th>
                            <th class="text-left px-4 py-2.5 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]">Severity</th>
                            <th class="text-left px-4 py-2.5 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]">Status</th>
                            <th class="text-right px-4 py-2.5 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-600/[.08]">
                        <template x-for="inc in priorityIncidents" :key="inc.ref">
                            <tr x-show="$store.session.inScope(inc.barangay)" class="hover:bg-white/60 transition-colors group">
                                <td class="px-4 py-3"><span class="text-xs font-mono text-ink-muted" x-text="inc.ref"></span></td>
                                <td class="px-4 py-3"><span class="text-xs font-medium text-slate-700" x-text="inc.type"></span></td>
                                <td class="px-4 py-3"><span class="text-xs text-slate-600" x-text="inc.barangay"></span></td>
                                <td class="px-4 py-3"><span :class="severityBadge(inc.severity) + ' px-2 py-0.5 rounded-full text-xs font-medium border'" x-text="inc.severity"></span></td>
                                <td class="px-4 py-3"><span :class="statusBadge(inc.status)" x-text="inc.status"></span></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openIncidentDetail(inc)" class="p-1.5 text-ink-muted hover:text-brand-600 hover:bg-brand-50 rounded-xl transition-colors" title="View Details">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <button x-show="inc.available_verbs.includes('validate')" @click="actIncidentVerb(inc, 'validate')" class="p-1.5 text-ink-muted hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-colors" title="Validate">
                                            <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="priorityIncidents.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-xs text-ink-muted">No open incidents.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Right: Evacuation Center Overview --}}
        <div class="lg:col-span-2 surface-glass rounded-[26px] overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-brand-600/10">
                <div>
                    <h3 class="text-sm font-semibold text-ink-body">Evacuation Center Status</h3>
                    <p class="text-xs text-ink-muted mt-0.5" x-text="commandBoard.centers.length + ' open centers'"></p>
                </div>
                <button @click="setTab('evacuation-centers')" class="text-xs text-brand-600 hover:text-brand-700 font-medium flex items-center gap-1 transition-colors">
                    Manage <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="divide-y divide-brand-600/[.08] max-h-[340px] overflow-y-auto">
                <template x-for="c in commandBoard.centers" :key="c.ref">
                    <div class="px-4 py-3 hover:bg-white/60 transition-colors">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-700 truncate" x-text="c.name"></p>
                                <p class="text-[11px] text-ink-muted truncate" x-text="c.barangay + ' · ' + c.families + ' families'"></p>
                            </div>
                            <span x-show="c.over_capacity" class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700">Over Capacity</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" :class="occupancyBar(c.pct)" :style="'width:' + c.pct + '%'"></div>
                            </div>
                            <span class="text-[11px] font-semibold text-ink-muted tabular-nums w-10 text-right" x-text="c.pct + '%'"></span>
                        </div>
                        <p class="text-[10px] text-ink-muted mt-0.5" x-text="c.individuals + ' / ' + c.capacity + ' occupants'"></p>
                    </div>
                </template>
                <div x-show="commandBoard.centers.length === 0" class="px-4 py-8 text-center text-xs text-ink-muted">No centers currently open.</div>
            </div>
        </div>

    </div>{{-- /Incident Queue + Evac Overview --}}


    {{-- ── B4. Active Public Alerts ─────────────────────────────────────────── --}}
    <div class="surface-glass rounded-[26px] overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-brand-600/10">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-ink-body">Active Public Alerts</h3>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700" x-text="commandBoard.alerts.length + ' active'"></span>
            </div>
            <button @click="setTab('alerts')" class="text-xs text-ink-muted hover:text-slate-700 flex items-center gap-1 transition-colors">
                Manage <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </button>
        </div>
        <div class="divide-y divide-brand-600/[.08]">
            <template x-for="a in commandBoard.alerts" :key="a.ref">
                <div class="flex items-start gap-3 px-5 py-3 hover:bg-white/60 transition-all">
                    <span :class="alertLevelBadge(a.level)" x-text="a.level" class="shrink-0 mt-0.5"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-700" x-text="a.title.en"></p>
                        <p class="text-[11px] text-ink-muted mt-0.5" x-text="a.type + ' · ' + a.ref"></p>
                    </div>
                    <span class="text-[10px] text-ink-muted whitespace-nowrap flex-shrink-0 mt-0.5" x-text="a.published_at ? new Date(a.published_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></span>
                </div>
            </template>
            <div x-show="commandBoard.alerts.length === 0" class="px-5 py-8 text-center text-xs text-ink-muted">No active public alerts.</div>
        </div>
    </div>{{-- /Active Public Alerts --}}


    {{-- ── B5. Recent Activity Feed + Resource Readiness ─────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

        {{-- Left: Activity Feed --}}
        <div class="lg:col-span-3 surface-glass rounded-[26px] overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-brand-600/10">
                <h3 class="text-sm font-semibold text-ink-body">Recent Activity Feed</h3>
                <span class="text-[11px] text-ink-muted">Last 25 incident actions</span>
            </div>
            <div class="px-5 py-4 space-y-0 max-h-[420px] overflow-y-auto">
                <template x-for="(item, idx) in commandBoard.feed" :key="item.incident + '-' + item.at + '-' + idx">
                    <div class="flex items-start gap-3 py-3" :class="idx < commandBoard.feed.length - 1 ? 'border-b border-slate-50' : ''">
                        <div class="w-7 h-7 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-slate-700 leading-relaxed">
                                <span class="font-mono font-medium" x-text="item.incident"></span>
                                <span x-text="' — ' + item.verb + 'd (' + item.from + (item.to && item.to !== item.from ? ' → ' + item.to : '') + ')'"></span>
                            </p>
                            <p class="text-[11px] text-ink-muted mt-0.5" x-show="item.note" x-text="item.note"></p>
                        </div>
                        <span class="text-[10px] text-ink-muted whitespace-nowrap flex-shrink-0 mt-0.5" x-text="item.at ? new Date(item.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></span>
                    </div>
                </template>
                <div x-show="commandBoard.feed.length === 0" class="text-center text-xs text-ink-muted py-8">No incident activity recorded yet.</div>
            </div>
        </div>

        {{-- Right: Resource Readiness --}}
        <div class="lg:col-span-2 surface-glass rounded-[26px] overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-brand-600/10">
                <h3 class="text-sm font-semibold text-ink-body">Resource Readiness</h3>
                <button @click="setTab('resources')" class="text-xs text-brand-600 hover:text-brand-700 font-medium flex items-center gap-1 transition-colors">
                    Manage <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="px-4 py-4 space-y-2.5 max-h-[420px] overflow-y-auto">
                <template x-for="r in commandBoard.resources" :key="r.ref">
                    <div class="p-3 rounded-xl border border-brand-600/10 bg-slate-50/40">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <p class="text-xs font-semibold text-slate-700 truncate" x-text="r.name"></p>
                            <span class="text-[10px] text-ink-muted" x-text="r.readiness || '—'"></span>
                        </div>
                        <p class="text-[11px] text-ink-muted" x-text="r.category"></p>
                        <p class="text-[11px] text-ink-muted mt-1" x-text="r.available + ' / ' + r.quantity + ' available'"></p>
                    </div>
                </template>
                <div x-show="commandBoard.resources.length === 0" class="text-center text-xs text-ink-muted py-8">No resources recorded.</div>
            </div>
        </div>

    </div>{{-- /Activity + Resources --}}

</div>{{-- /Main Content --}}


@include('admin.partials.sakuna._incident-detail-modal')

</div>{{-- /x-init wrapper --}}

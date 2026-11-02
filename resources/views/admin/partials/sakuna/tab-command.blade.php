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
            <div class="sk-card min-h-[136px] p-4 space-y-3 animate-pulse">
                <div class="h-3 bg-slate-100 rounded w-20"></div>
                <div class="h-7 bg-slate-100 rounded-lg w-12"></div>
                <div class="h-3 bg-slate-100 rounded w-16"></div>
            </div>
        @endfor
    </div>
    {{-- Map skeleton --}}
    <div class="sk-card p-5 animate-pulse">
        <div class="h-5 bg-slate-100 rounded w-80 mb-4"></div>
        <div class="h-60 bg-slate-100 rounded-xl"></div>
    </div>
    {{-- Incident queue + evac overview skeleton --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
        <div class="lg:col-span-3 sk-card p-5 space-y-3 animate-pulse">
            <div class="h-5 bg-slate-100 rounded w-48 mb-2"></div>
            @for ($i = 0; $i < 6; $i++)
                <div class="h-11 bg-slate-100 rounded-xl"></div>
            @endfor
        </div>
        <div class="lg:col-span-2 sk-card p-5 space-y-3 animate-pulse">
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
    class="space-y-3"
>

    {{-- Sakuna Redesign (2026-11-02): a KPI strip, the map beside the status
         column, then the queue beside the activity feed. --}}

    {{-- ── B1. KPI strip ──────────────────────────────────────────────────── --}}
    <div class="sk-stats" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
        <button type="button" class="sk-stat" @click="setTab('incidents')">
            <span class="sk-slab"><span class="sk-dot sk-t-red"></span>Open incidents</span>
            <span class="sk-sval tabular-nums" x-text="commandBoard.counts.incidents_open"></span>
            <span class="sk-ssub" x-text="commandBoard.counts.incidents_total + ' total'"></span>
        </button>
        <button type="button" class="sk-stat" @click="setTab('evacuation-centers')">
            <span class="sk-slab"><span class="sk-dot sk-t-teal"></span>Evac. centers open</span>
            <span class="sk-sval tabular-nums" x-text="commandBoard.counts.centers_open"></span>
            <span class="sk-ssub" x-text="commandBoard.centers.filter(c => c.pct >= 75).length + ' near/over capacity'"></span>
        </button>
        <button type="button" class="sk-stat" @click="setTab('evacuees')">
            <span class="sk-slab"><span class="sk-dot sk-t-blue"></span>Families sheltered</span>
            <span class="sk-sval tabular-nums" x-text="commandBoard.counts.families_sheltered"></span>
            <span class="sk-ssub" x-text="commandBoard.counts.individuals_sheltered + (commandBoard.counts.individuals_sheltered === 1 ? ' individual' : ' individuals') + ((commandBoard.counts.houses_totally_damaged || commandBoard.counts.houses_partially_damaged) ? ' · houses: ' + (commandBoard.counts.houses_totally_damaged || 0) + ' totally, ' + (commandBoard.counts.houses_partially_damaged || 0) + ' partly damaged' : '')"></span>
        </button>
        <button type="button" class="sk-stat" @click="setTab('relief')">
            <span class="sk-slab"><span class="sk-dot sk-t-amber"></span>Relief released</span>
            <span class="sk-sval tabular-nums" x-text="commandBoard.counts.relief_released"></span>
            <span class="sk-ssub">&nbsp;</span>
        </button>
        <button type="button" class="sk-stat" @click="setTab('damage-assessment')">
            <span class="sk-slab"><span class="sk-dot sk-t-indigo"></span>Damage reports</span>
            <span class="sk-sval tabular-nums" x-text="commandBoard.counts.damage_reports"></span>
            <span class="sk-ssub">&nbsp;</span>
        </button>
        <button type="button" class="sk-stat" @click="setTab('alerts')">
            <span class="sk-slab"><span class="sk-dot sk-t-orange"></span>Active public alerts</span>
            <span class="sk-sval tabular-nums" x-text="commandBoard.counts.alerts_live"></span>
            <span class="sk-ssub">&nbsp;</span>
        </button>
    </div>{{-- /KPI strip --}}

    {{-- ── B2. Map | status column ─────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-3 items-stretch">

        {{-- Active Incident Locations: a real map (2026-10-26), incidents and
             evacuation centres at the coordinates saved on their records,
             nothing estimated. --}}
        <section class="sk-card flex flex-col overflow-hidden" style="flex: 2 1 560px;" x-data="sakunaCommandMap" x-effect="draw()">
            <div class="sk-chead">
                <h3 class="sk-ctitle">Active incident locations</h3>
                <div class="flex flex-wrap items-center gap-1.5">
                    <select aria-label="Incident status on the map" x-model="statusFilter" class="sk-sel">
                        <option value="open">Open incidents</option>
                        <option value="all">All on the map (incl. resolved)</option>
                        <template x-for="st in mapStatuses" :key="st"><option :value="st" x-text="'Only ' + incidentStatusLabel(st)"></option></template>
                    </select>
                    <select aria-label="Severity on the map" x-model="severityFilter" class="sk-sel">
                        <option value="">All severities</option>
                        <option>Critical</option><option>High</option><option>Medium</option><option>Low</option>
                    </select>
                    <select aria-label="Incident type on the map" x-model="typeFilter" class="sk-sel">
                        <option value="">All types</option>
                        <template x-for="ty in mapTypes" :key="ty"><option :value="ty" x-text="ty"></option></template>
                    </select>
                    <label class="sk-chk cursor-pointer select-none">
                        <input type="checkbox" x-model="showCentres">
                        Evacuation centres
                    </label>
                    <button type="button" @click="fit()" class="sk-btn sk-btn-sm">
                        <i data-lucide="scan"></i> Fit to records
                    </button>
                </div>
            </div>

            {{-- The map --}}
            <div class="relative flex-1 min-h-[360px]">
                <div x-ref="map" class="sakuna-map" style="position: absolute; inset: 0;"
                     role="application" aria-label="Map of incidents and evacuation centres. Select a marker for its summary."></div>
                <div x-show="mapState === 'loading'" class="pointer-events-none absolute inset-x-0 top-3 flex justify-center z-[500]">
                    <span class="text-[11px] font-semibold text-slate-600 bg-white/90 rounded-full px-2.5 py-1 shadow">Loading the map…</span>
                </div>
                {{-- Open incidents nobody pinned: counted, never placed at a guessed point. --}}
                <p x-show="unmapped > 0" x-cloak class="absolute top-2.5 right-2.5 z-[500] max-w-[60%] flex items-start gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1.5 text-xs text-amber-900 shadow-sm">
                    <i data-lucide="map-pin-off" class="w-3.5 h-3.5 flex-shrink-0 mt-px"></i>
                    <span x-text="unmapped === 1
                        ? '1 open incident has no map location, so it is not on the map. It is in the queue below and on the Incidents tab, where staff can pin it.'
                        : unmapped + ' open incidents have no map location, so they are not on the map. They are in the queue below and on the Incidents tab, where staff can pin them.'"></span>
                </p>
                <div x-show="mapState !== 'failed' && !commandLoading && nothingToPlot" x-cloak class="pointer-events-none absolute inset-0 flex items-center justify-center px-4 z-[500]">
                    <p class="text-[12.5px] text-slate-700 bg-white rounded-[9px] px-3.5 py-2 shadow-lg text-center max-w-md">
                        Nothing to plot with these filters: no incident or centre here has a saved map location.
                    </p>
                </div>
            </div>
            <div x-show="mapState === 'failed'" x-cloak role="alert" class="border-t border-amber-200 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-900">
                <p class="font-semibold">The map background could not load (no connection, or the map service is busy).</p>
                <p class="mt-0.5">The markers are still placed at their saved coordinates; the queue below lists every incident.</p>
                <button type="button" @click="retryMap()" class="mt-1 font-semibold underline underline-offset-2">Try again</button>
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-3.5 py-2 text-[11.5px] text-slate-600">
                <div class="flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-1.5"><span class="sk-dot sk-t-red"></span>Critical</span>
                    <span class="inline-flex items-center gap-1.5"><span class="sk-dot" style="color: #EA580C;"></span>High</span>
                    <span class="inline-flex items-center gap-1.5"><span class="sk-dot" style="color: #EAB308;"></span>Medium</span>
                    <span class="inline-flex items-center gap-1.5"><span class="sk-dot" style="color: #94A3B8;"></span>Low</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm bg-teal-700"></span>Centre, open</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm bg-slate-600"></span>Centre, not open</span>
                </div>
                <span class="sk-muted">Numbered circles group nearby incidents (colour = most severe)</span>
            </div>
        </section>{{-- /Map --}}

        <div class="flex flex-col gap-3 min-w-0" style="flex: 1 1 300px;">

            {{-- Missing Persons (owner, 2026-10-30): counted as people, not
                 cases, from GET /sakuna/missing/summary; hidden where the role
                 may not read cases or the feature is not switched on. --}}
            <section class="sk-card"
                 x-data="{ mp: null }"
                 x-init="if (can('sakuna_missing','view')) $api.get('/admin/sakuna/missing/summary').then((r) => { mp = r.data; }).catch(() => { mp = null; })"
                 x-show="mp" x-cloak>
                <div class="sk-chead" style="border-bottom: 0; padding-bottom: 6px;">
                    <h3 class="sk-ctitle">Missing persons</h3>
                    <button type="button" @click="setTab('missing-persons')" class="sk-link">Open Missing Persons <i data-lucide="arrow-right"></i></button>
                </div>
                <dl class="grid grid-cols-5 gap-1 px-3.5 pb-3">
                    <div class="flex flex-col-reverse"><dt class="sk-ssub">Still missing</dt><dd class="sk-sval sk-t-red tabular-nums" style="font-size: 18px;" x-text="mp?.missing ?? 0"></dd></div>
                    <div class="flex flex-col-reverse"><dt class="sk-ssub">Unidentified</dt><dd class="sk-sval sk-t-orange tabular-nums" style="font-size: 18px;" x-text="mp?.unidentified ?? 0"></dd></div>
                    <div class="flex flex-col-reverse"><dt class="sk-ssub">This week</dt><dd class="sk-sval tabular-nums" style="font-size: 18px;" x-text="mp?.recent ?? 0"></dd></div>
                    <div class="flex flex-col-reverse"><dt class="sk-ssub">Found</dt><dd class="sk-sval sk-t-green tabular-nums" style="font-size: 18px;" x-text="mp?.found ?? 0"></dd></div>
                    <div class="flex flex-col-reverse"><dt class="sk-ssub">Closed</dt><dd class="sk-sval tabular-nums" style="font-size: 18px;" x-text="mp?.closed ?? 0"></dd></div>
                </dl>
                <p class="px-3.5 pb-3 -mt-1 text-[11px] sk-muted" x-show="mp && mp.cases === 0">No one has been recorded missing.</p>
            </section>

            {{-- Evacuation centres --}}
            <section class="sk-card overflow-hidden">
                <div class="sk-chead">
                    <h3 class="sk-ctitle">Evacuation centres <span class="sk-csub" x-text="'· ' + commandBoard.centers.length + ' open'"></span></h3>
                    <button type="button" @click="setTab('evacuation-centers')" class="sk-link">Manage <i data-lucide="arrow-right"></i></button>
                </div>
                <div class="max-h-[260px] overflow-y-auto">
                    <template x-for="c in commandBoard.centers" :key="c.ref">
                        <div class="px-3.5 py-2.5 flex flex-col gap-1.5 border-b border-[#EEF2F7] last:border-b-0">
                            <div class="flex justify-between gap-2 text-[12.5px]">
                                <span class="sk-b truncate" x-text="c.name"></span>
                                <span class="flex items-center gap-1.5 shrink-0">
                                    <span x-show="c.over_capacity" class="sk-pill sk-p-red">Over capacity</span>
                                    <span class="sk-muted tabular-nums" x-text="c.pct + '%'"></span>
                                </span>
                            </div>
                            <div class="h-[5px] rounded-full bg-[#E9EEF6] overflow-hidden">
                                <div class="h-full rounded-full transition-all" :class="occupancyBar(c.pct)" :style="'width:' + Math.max(c.pct, 1) + '%'"></div>
                            </div>
                            <div class="sk-ssub" x-text="c.barangay + ' · ' + c.families + ' families · ' + c.individuals + ' / ' + c.capacity + ' occupants'"></div>
                        </div>
                    </template>
                    <div x-show="commandBoard.centers.length === 0" class="sk-empty" style="padding: 14px;">No centers currently open.</div>
                </div>
            </section>

            {{-- Active public alerts --}}
            <section class="sk-card overflow-hidden">
                <div class="sk-chead">
                    <h3 class="sk-ctitle">Active public alerts <span class="sk-pill sk-p-red ml-1" x-text="commandBoard.alerts.length + ' active'"></span></h3>
                    <button type="button" @click="setTab('alerts')" class="sk-link">Manage <i data-lucide="arrow-right"></i></button>
                </div>
                <div class="max-h-[220px] overflow-y-auto">
                    <template x-for="a in commandBoard.alerts" :key="a.ref">
                        <div class="flex items-start gap-2.5 px-3.5 py-2.5 border-b border-[#EEF2F7] last:border-b-0">
                            <span :class="alertLevelBadge(a.level)" x-text="a.level" class="shrink-0 mt-0.5"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12.5px] sk-b" x-text="a.title.en"></p>
                                <p class="sk-sub" x-text="a.type + ' · ' + a.ref"></p>
                            </div>
                            <span class="sk-ssub shrink-0 mt-0.5" x-text="a.published_at ? new Date(a.published_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></span>
                        </div>
                    </template>
                    <div x-show="commandBoard.alerts.length === 0" class="sk-empty" style="padding: 14px;">No active public alerts.</div>
                </div>
            </section>

            {{-- Resource readiness --}}
            <section class="sk-card overflow-hidden flex-grow">
                <div class="sk-chead">
                    <h3 class="sk-ctitle">Resource readiness</h3>
                    <button type="button" @click="setTab('resources')" class="sk-link">Manage <i data-lucide="arrow-right"></i></button>
                </div>
                <div class="max-h-[260px] overflow-y-auto">
                    <template x-for="r in commandBoard.resources" :key="r.ref">
                        <div class="flex items-center justify-between gap-2 px-3.5 py-2.5 border-b border-[#EEF2F7] last:border-b-0">
                            <div class="min-w-0">
                                <p class="text-[12.5px] sk-b truncate" x-text="r.name"></p>
                                <p class="sk-sub" x-text="r.category + ' · ' + r.available + ' / ' + r.quantity + ' available'"></p>
                            </div>
                            <span class="sk-tag shrink-0" x-text="r.readiness || '—'"></span>
                        </div>
                    </template>
                    <div x-show="commandBoard.resources.length === 0" class="sk-empty" style="padding: 14px;">No resources recorded.</div>
                </div>
            </section>
        </div>
    </div>{{-- /Map | status --}}

    {{-- ── B3. Priority queue | recent activity ─────────────────────────────── --}}
    <div class="flex flex-wrap gap-3 items-stretch">

        <section class="sk-card overflow-hidden" style="flex: 2 1 560px;">
            <div class="sk-chead">
                <h3 class="sk-ctitle">Priority incident queue <span class="sk-csub">· top 6 open, by severity</span></h3>
                <button type="button" @click="setTab('incidents')" class="sk-link">View all <i data-lucide="arrow-right"></i></button>
            </div>
            <div class="sk-scroll" tabindex="0" role="region" aria-label="Priority incident queue (scrolls sideways)">
                <table class="sk-tbl min-w-[600px]">
                    <thead>
                        <tr><th>Ref</th><th>Type</th><th>Barangay</th><th>Severity</th><th>Status</th><th class="sk-r">Actions</th></tr>
                    </thead>
                    <tbody>
                        <template x-for="inc in priorityIncidents" :key="inc.ref">
                            <tr x-show="$store.session.inScope(inc.barangay)">
                                <td><span class="sk-mono" x-text="inc.ref"></span></td>
                                <td class="sk-b" x-text="inc.type"></td>
                                <td x-text="inc.barangay"></td>
                                <td><span :class="severityBadge(inc.severity)" class="sk-pill" x-text="inc.severity"></span></td>
                                <td><span :class="statusBadge(inc.status)" class="sk-pill" x-text="incidentStatusLabel(inc.status)"></span></td>
                                <td class="sk-r whitespace-nowrap">
                                    <button type="button" x-show="inc.available_verbs.includes('validate')" @click="actIncidentVerb(inc, 'validate')" class="sk-iconbtn sk-iconbtn-bare hover:!text-emerald-700" :aria-label="'Validate ' + inc.ref" title="Validate">
                                        <i data-lucide="circle-check"></i>
                                    </button>
                                    <button type="button" @click="openIncidentDetail(inc)" class="sk-iconbtn sk-iconbtn-bare" :aria-label="'View ' + inc.ref" title="View details">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="priorityIncidents.length === 0">
                            <td colspan="6"><div class="sk-empty">No open incidents.</div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="sk-card flex flex-col overflow-hidden" style="flex: 1 1 300px;">
            <div class="sk-chead">
                <h3 class="sk-ctitle">Recent activity</h3>
                <span class="sk-csub">Last 25 incident actions</span>
            </div>
            <ol class="px-3.5 overflow-y-auto max-h-[320px] flex-grow">
                <template x-for="(item, idx) in commandBoard.feed" :key="item.incident + '-' + item.at + '-' + idx">
                    <li class="flex gap-2.5 py-2 border-b border-[#EEF2F7] last:border-b-0">
                        <span class="w-1.5 h-1.5 mt-1.5 rounded-full bg-[#2563EB] shrink-0"></span>
                        <div class="flex-grow min-w-0 text-xs leading-relaxed">
                            <div>
                                <span class="sk-mono" style="color: #1D4ED8;" x-text="item.incident"></span>
                                <span class="sk-b" x-text="item.verb + 'd'"></span>
                                <span class="sk-muted" x-text="item.from + (item.to && item.to !== item.from ? ' → ' + item.to : '')"></span>
                            </div>
                            <div class="sk-sub" x-show="item.note" x-text="item.note"></div>
                        </div>
                        <span class="sk-ssub shrink-0" x-text="item.at ? new Date(item.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></span>
                    </li>
                </template>
                <li x-show="commandBoard.feed.length === 0" class="sk-empty">No incident activity recorded yet.</li>
            </ol>
        </section>
    </div>{{-- /Queue | activity --}}

</div>{{-- /Main Content --}}


@include('admin.partials.sakuna._incident-detail-modal')

</div>{{-- /x-init wrapper --}}

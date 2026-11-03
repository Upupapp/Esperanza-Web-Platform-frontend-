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
    placed; it stays in the priority queue and on the Incidents tab.

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
<div x-show="commandLoading" class="space-y-3" aria-busy="true" aria-label="Loading the Command Center">
    {{-- The loaded page's shape (fifth run): band, Needs action, queue beside the map, then the figures. --}}
    <div class="sk-card h-[68px] animate-pulse"></div>
    <div class="sk-card h-[52px] animate-pulse"></div>
    <div class="flex flex-wrap gap-3">
        <div class="sk-card p-4 space-y-2.5 animate-pulse" style="flex: 3 1 520px;">
            <div class="h-4 bg-slate-200/70 rounded w-48 mb-2"></div>
            @for ($i = 0; $i < 5; $i++)
                <div class="h-11 bg-slate-200/70 rounded-xl"></div>
            @endfor
        </div>
        <div class="sk-card p-4 animate-pulse" style="flex: 2 1 380px;">
            <div class="h-4 bg-slate-200/70 rounded w-40 mb-3"></div>
            <div class="h-64 bg-slate-200/70 rounded-xl"></div>
        </div>
    </div>
    <div class="grid grid-cols-3 gap-3">
        @for ($i = 0; $i < 3; $i++)
            <div class="sk-card h-[92px] p-3.5 space-y-2.5 animate-pulse">
                <div class="h-3 bg-slate-200/70 rounded w-24"></div>
                <div class="h-7 bg-slate-200/70 rounded-lg w-12"></div>
            </div>
        @endfor
    </div>
</div>

<div x-show="commandFailed" x-cloak class="space-y-3">
    <x-ui.error-state tone="neutral" icon="cloud-off" title="Not loaded" messageExpr="'Could not load the Command Center. ' + offlineWhy" retryExpr="loadCommandCenter()" :retryable="true" />
    {{-- Still useful offline (fifth run): the hotlines as last loaded. --}}
    <template x-if="lastCopy('hotlines')?.data?.length">
        <section class="sk-card max-w-xl mx-auto" aria-labelledby="offline-hotlines-title">
            <div class="sk-chead">
                <h2 id="offline-hotlines-title" class="sk-ctitle">Emergency hotlines</h2>
                <span class="sk-csub" x-text="'as last loaded ' + new Date(lastCopy('hotlines').at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })"></span>
            </div>
            <ul class="divide-y divide-[#EEF2F7]">
                <template x-for="h in lastCopy('hotlines').data" :key="h.id">
                    <li class="flex items-center justify-between gap-3 px-3.5 py-2.5">
                        <span class="min-w-0"><span class="sk-b block" x-text="h.office"></span><span class="sk-sub" x-text="h.position || ''"></span></span>
                        <a class="sk-link tabular-nums" :href="'tel:' + String(h.contact || '').replace(/[^0-9+]/g, '')" x-text="h.contact"></a>
                    </li>
                </template>
            </ul>
        </section>
    </template>
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

    {{-- ── The event (critique 2026-11-03, third run) ──────────────────────
         What is in effect, said in full and large enough to read across the
         EOC: the public alert with its level and places, or the open incidents. --}}
    <section class="sk-card sk-event" aria-label="Current event">
        <template x-if="commandBoard.alerts.length">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3">
                <span :class="alertLevelBadge(commandBoard.alerts[0].level)" class="!text-sm !font-bold !px-3 !py-1" x-text="commandBoard.alerts[0].level"></span>
                <div class="min-w-0 flex-1">
                    <h2 id="event-title" class="sk-event-title" x-text="commandBoard.alerts[0].title.en"></h2>
                    <p class="sk-sub" x-text="[commandBoard.alerts[0].type, commandBoard.alerts[0].published_at ? 'issued ' + new Date(commandBoard.alerts[0].published_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : null, commandBoard.alerts.length > 1 ? '+' + (commandBoard.alerts.length - 1) + ' more in effect' : null].filter(Boolean).join(' · ')"></p>
                </div>
                <button type="button" @click="setTab('alerts')" class="sk-btn sk-btn-sm">Manage alerts <i data-lucide="arrow-right"></i></button>
            </div>
        </template>
        <template x-if="!commandBoard.alerts.length">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3">
                <h2 id="event-title" class="sk-event-title" x-text="commandBoard.counts.incidents_open ? commandBoard.counts.incidents_open + (commandBoard.counts.incidents_open === 1 ? ' open incident' : ' open incidents') + ', no public alert in effect' : 'No public alert in effect and no open incidents'"></h2>
            </div>
        </template>
    </section>


    {{-- ── Needs action (critique 2026-11-03, second run) ─────────────────────
         What is waiting on someone, first: open incidents not yet validated,
         the one waiting longest, nobody assigned, centers filling up. --}}
    <section class="sk-card" aria-labelledby="needs-action-title">
        <div class="flex flex-wrap items-center gap-2 px-3.5 py-2.5">
            <h2 id="needs-action-title" class="sk-ctitle mr-1">Needs action</h2>
            <button type="button" x-show="missingSummary?.missing" @click="setTab('missing-persons')" class="sk-need sk-need-amber" :aria-label="(missingSummary?.missing || 0) + (missingSummary?.missing === 1 ? ' person' : ' people') + ' still missing'">
                <i data-lucide="user-search"></i>
                <span x-text="(missingSummary?.missing || 0) + ' still missing'"></span>
            </button>
            <button type="button" x-show="needsAction.toValidate" @click="openIncidentsFiltered({ filter_status: 'Submitted' })" class="sk-need sk-need-amber" :aria-label="needsAction.toValidate + ' open incidents waiting to be validated'">
                <i data-lucide="badge-alert"></i>
                <span x-text="needsAction.toValidate + ' to validate'"></span>
            </button>
            <button type="button" x-show="needsAction.oldest" @click="openIncidentDetail(needsAction.oldest)" class="sk-need" :class="waitTone(needsAction.oldest) === 'sk-wait-red' ? 'sk-need-red' : 'sk-need-neutral'">
                <i data-lucide="hourglass"></i>
                <span x-text="needsAction.oldest ? 'Longest wait: ' + needsAction.oldest.type + ', ' + needsAction.oldest.barangay + ', ' + waitedLabel(needsAction.oldest.reported) : ''"></span>
            </button>
            <button type="button" x-show="needsAction.unassigned" @click="openIncidentsFiltered({ filter_assigned: 'none' })" class="sk-need sk-need-amber" :aria-label="needsAction.unassigned + ' open incidents with nobody assigned'">
                <i data-lucide="user-x"></i>
                <span x-text="needsAction.unassigned + ' with nobody assigned'"></span>
            </button>
            <button type="button" x-show="needsAction.fullCentres.length" @click="setTab('evacuation-centers')" class="sk-need sk-need-amber">
                <i data-lucide="house"></i>
                <span x-text="needsAction.fullCentres.length === 1 ? needsAction.fullCentres[0].name + ' is ' + needsAction.fullCentres[0].pct + '% full' : needsAction.fullCentres.length + ' centers are 75% full or more'"></span>
            </button>
            {{-- "Nothing is waiting" only when every count is live (critique, eighth run). --}}
            <p x-show="!staleSince.board && commandIncidentsKnown && !missingUnknown && !missingSummary?.missing && !needsAction.toValidate && !needsAction.unassigned && !needsAction.fullCentres.length" class="text-sm text-[#166534] inline-flex items-center gap-1.5">
                <i data-lucide="circle-check" class="w-4 h-4"></i> Nothing is waiting: no one is missing, every open incident is validated and assigned, and no center is near full.
            </p>
            <p x-show="staleSince.board || !commandIncidentsKnown || missingUnknown" x-cloak class="text-sm text-ink-body inline-flex items-center gap-1.5">
                <i data-lucide="cloud-off" class="w-4 h-4 text-ink-muted"></i>
                <span x-text="[
                    !commandIncidentsKnown ? 'Incidents not known offline: the board had ' + (commandBoard.counts?.incidents_open ?? 0) + ' open as of ' + boardAsOf + '.' : null,
                    missingUnknown ? 'Missing persons not known offline.' : null,
                    commandIncidentsKnown && !missingUnknown && staleSince.board ? 'As of ' + boardAsOf + '; the board could not be refreshed.' : null,
                ].filter(Boolean).join(' ')"></span>
            </p>
        </div>
    </section>

    {{-- ── Queue | map (critique 2026-11-03, third run) ──────────────────────
         The work and where it is, side by side on the first screen. --}}
    <div class="flex flex-wrap gap-3 items-start">

        <section class="sk-card overflow-hidden" style="flex: 3 1 520px;">
            <div class="sk-chead">
                <h2 class="sk-ctitle">Priority incident queue <span class="sk-csub" x-text="commandIncidentsKnown ? '· ' + (commandOpenCount > priorityIncidents.length ? priorityIncidents.length + ' of ' + commandOpenCount : commandOpenCount) + ' open, most severe first' : '· not known offline'"></span></h2>
                <button type="button" @click="setTab('incidents')" class="sk-link">View all <i data-lucide="arrow-right"></i></button>
            </div>
            <div class="sk-scroll" tabindex="0" role="region" aria-label="Priority incident queue (scrolls sideways)">
                <table class="sk-tbl sk-tbl-act sk-tbl-tight">
                    <thead>
                        <tr><th>Incident</th><th>Severity</th><th>Waiting</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <template x-for="inc in priorityIncidents" :key="inc.ref">
                            <tr @click="openIncidentDetail(inc)">
                                <td><div class="sk-b" x-text="inc.type + ' · ' + inc.barangay"></div><div class="sk-mono" x-text="inc.ref"></div></td>
                                <td><span :class="severityBadge(inc.severity)" class="sk-pill" x-text="inc.severity"></span></td>
                                <td class="whitespace-nowrap tabular-nums"><div :class="waitTone(inc)"><span x-text="waitedLabel(inc.reported)"></span><span x-show="waitTone(inc) === 'sk-wait-red'" class="sk-longest">Longest</span></div><div class="sk-sub" x-text="inc.reported ? 'since ' + new Date(inc.reported).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></div></td>
                                <td><span :class="statusBadge(inc.status)" class="sk-pill" x-text="incidentStatusLabel(inc.status)"></span></td>
                                <td class="whitespace-nowrap" @click.stop>
                                    <button type="button" @click="openIncidentDetail(inc)" class="sk-btn sk-btn-sm sk-btn-soft" :aria-label="'View ' + inc.ref + ', ' + inc.type + ' in ' + inc.barangay + ', ' + inc.severity + ' severity'" title="Open the incident to validate, assign or dispatch">
                                        <i data-lucide="eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="priorityIncidents.length === 0">
                            <td colspan="5"><div class="sk-empty" x-text="commandIncidentsKnown ? 'No open incidents.' : 'Not known offline. Try again when the connection is back.'"></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="sk-tfoot"><span class="sk-wait-red">Red</span>: waited longest for someone to validate or take it. <span class="sk-wait-amber">Amber</span>: waiting over 4 hours.</p>
        </section>

        <section class="sk-card flex flex-col overflow-hidden" style="flex: 2 1 380px;" x-data="sakunaCommandMap" x-effect="draw()">
            <div class="sk-chead">
                <h2 class="sk-ctitle">Active incident locations</h2>
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
                        Evacuation centers
                    </label>
                    <button type="button" @click="fit()" class="sk-btn sk-btn-sm">
                        <i data-lucide="scan"></i> Fit to records
                    </button>
                </div>
            </div>

            {{-- The map --}}
            <div class="relative flex-1 min-h-[360px]">
                <div x-ref="map" class="sakuna-map" style="position: absolute; inset: 0;"
                     role="application" aria-label="Map of incidents and evacuation centers. Select a marker for its summary."></div>
                <div x-show="mapState === 'loading'" class="pointer-events-none absolute inset-x-0 top-3 flex justify-center z-[500]">
                    <span class="text-xs font-semibold text-slate-600 bg-white/90 rounded-full px-2.5 py-1 shadow">Loading the map…</span>
                </div>
                {{-- Open incidents nobody pinned: counted, never placed at a guessed point. --}}
                <p x-show="unmapped > 0" x-cloak class="absolute top-2.5 right-2.5 z-[500] max-w-[60%] flex items-start gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1.5 text-xs text-amber-900 shadow-sm">
                    <i data-lucide="map-pin-off" class="w-3.5 h-3.5 flex-shrink-0 mt-px"></i>
                    <span x-text="unmapped === 1
                        ? '1 open incident has no map location, so it is not on the map. It is in the priority queue and on the Incidents tab, where staff can pin it.'
                        : unmapped + ' open incidents have no map location, so they are not on the map. They are in the priority queue and on the Incidents tab, where staff can pin them.'"></span>
                </p>
                <div x-show="mapState !== 'failed' && !commandLoading && nothingToPlot" x-cloak class="pointer-events-none absolute inset-0 flex items-center justify-center px-4 z-[500]">
                    <p class="text-[12.5px] text-slate-700 bg-white rounded-[9px] px-3.5 py-2 shadow-lg text-center max-w-md">
                        Nothing to plot with these filters: no incident or center here has a saved map location.
                    </p>
                </div>
            </div>
            <div x-show="mapState === 'failed'" x-cloak role="alert" class="border-t border-amber-200 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-900">
                <p class="font-semibold">The map background could not load (no connection, or the map service is busy).</p>
                <p class="mt-0.5">The markers are still placed at their saved coordinates; the priority queue lists every incident.</p>
                <button type="button" @click="retryMap()" class="mt-1 font-semibold underline underline-offset-2">Try again</button>
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-3.5 py-2 text-xs text-slate-600">
                <div class="flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><span class="sk-dot" style="background: #E11D48; box-shadow: 0 0 0 1.5px #fff, 0 0 0 3px #BE123C;"></span>Critical (ringed)</span>
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><span class="sk-dot" style="background: #F97316;"></span>High</span>
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><span class="sk-dot sk-t-yellow"></span>Medium</span>
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><span class="sk-dot sk-t-slate"></span>Low</span>
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><span class="w-2 h-2 rounded-sm bg-teal-700"></span>Center, open</span>
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><span class="w-2 h-2 rounded-sm bg-slate-600"></span>Center, not open</span>
                </div>
                <span class="sk-muted">Numbered circles group nearby incidents (colour = most severe)</span>
            </div>
        </section>{{-- /Map --}}
    </div>{{-- /Queue | map --}}

    {{-- ── B1. Totals, after the work (fifth run): the first screen is the event, what needs action, the queue and the map. ──────────────────────────────────────────────────── --}}
    <p x-show="boardAsOf && !staleSince.board" x-cloak class="sk-asof -mb-1.5" x-text="'These figures are as of ' + boardAsOf + '. The live board could not be refreshed.'"></p>
    <div class="sk-stats" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <button type="button" class="sk-stat" @click="setTab('incidents')">
            <span class="sk-slab">Open incidents</span>
            <span class="sk-sval sk-sval-lg tabular-nums" x-text="commandBoard.counts.incidents_open"></span>
            <span class="sk-ssub" x-text="commandBoard.counts.incidents_total + ' total'"></span>
        </button>
        <button type="button" class="sk-stat" @click="setTab('evacuation-centers')">
            <span class="sk-slab">Evac. centers open</span>
            <span class="sk-sval sk-sval-lg tabular-nums" x-text="commandBoard.counts.centers_open"></span>
            <span class="sk-ssub" x-text="commandBoard.centers.filter(c => c.pct >= 75).length + ' near/over capacity'"></span>
        </button>
        <button type="button" class="sk-stat" @click="setTab('evacuees')">
            <span class="sk-slab">Families sheltered</span>
            <span class="sk-sval sk-sval-lg tabular-nums" x-text="commandBoard.counts.families_sheltered"></span>
            <span class="sk-ssub" x-text="commandBoard.counts.individuals_sheltered + (commandBoard.counts.individuals_sheltered === 1 ? ' individual' : ' individuals') + ((commandBoard.counts.houses_totally_damaged || commandBoard.counts.houses_partially_damaged) ? ' · houses: ' + (commandBoard.counts.houses_totally_damaged || 0) + ' totally, ' + (commandBoard.counts.houses_partially_damaged || 0) + ' partly damaged' : '')"></span>
        </button>
    </div>{{-- /KPI strip --}}

    {{-- ── Status cards | recent activity ───────────────────────────────── --}}
    <div class="flex flex-wrap gap-3 items-start">
        <div class="grid gap-3 min-w-0 content-start" style="flex: 3 1 520px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">

            {{-- Missing Persons (owner, 2026-10-30): counted as people, not
                 cases, from GET /sakuna/missing/summary; hidden where the role
                 may not read cases or the feature is not switched on. --}}
            <section class="sk-card"
                 x-data="{ get mp() { return missingSummary; } }"
                 x-init="if (can('sakuna_missing','view')) $api.get('/admin/sakuna/missing/summary').then((r) => { missingSummary = r.data; missingUnknown = false; saveCopy('missing-summary', r.data); }).catch((e) => { if (e?.status === 404 || e?.code === 'FEATURE_OFF') return; missingSummary = lastCopy('missing-summary')?.data || null; missingUnknown = !missingSummary; })"
                 x-show="mp" x-cloak>
                <div class="sk-chead" style="border-bottom: 0; padding-bottom: 6px;">
                    <h2 class="sk-ctitle">Missing persons</h2>
                    <button type="button" @click="setTab('missing-persons')" class="sk-link">Open Missing Persons <i data-lucide="arrow-right"></i></button>
                </div>
                <dl class="grid grid-cols-3 gap-x-3 gap-y-2.5 px-3.5 pb-3">
                    <div class="flex flex-col-reverse"><dt class="sk-ssub">Still missing</dt><dd class="sk-sval tabular-nums" style="font-size: 18px;" x-text="mp?.missing ?? 0"></dd></div>
                    <div class="flex flex-col-reverse"><dt class="sk-ssub">Unidentified</dt><dd class="sk-sval tabular-nums" style="font-size: 18px;" x-text="mp?.unidentified ?? 0"></dd></div>
                    <div class="flex flex-col-reverse"><dt class="sk-ssub">This week</dt><dd class="sk-sval tabular-nums" style="font-size: 18px;" x-text="mp?.recent ?? 0"></dd></div>
                    <div class="flex flex-col-reverse"><dt class="sk-ssub">Found</dt><dd class="sk-sval sk-t-green tabular-nums" style="font-size: 18px;" x-text="mp?.found ?? 0"></dd></div>
                    <div class="flex flex-col-reverse"><dt class="sk-ssub">Closed</dt><dd class="sk-sval tabular-nums" style="font-size: 18px;" x-text="mp?.closed ?? 0"></dd></div>
                </dl>
                <p class="px-3.5 pb-3 -mt-1 text-xs sk-muted" x-show="mp && mp.cases === 0">No one has been recorded missing.</p>
            </section>

            {{-- Evacuation centers --}}
            <section class="sk-card overflow-hidden">
                <div class="sk-chead">
                    <h2 class="sk-ctitle">Evacuation centers <span class="sk-csub" x-text="'· ' + commandBoard.centers.length + ' open'"></span></h2>
                    <button type="button" @click="setTab('evacuation-centers')" class="sk-link">Manage <i data-lucide="arrow-right"></i></button>
                </div>
                <div class="max-h-[260px] overflow-y-auto">
                    <template x-for="c in commandBoard.centers" :key="c.ref">
                        <div class="px-3.5 py-2.5 flex flex-col gap-1.5 border-b border-[#EEF2F7] last:border-b-0">
                            <div class="flex justify-between gap-2 text-[12.5px]">
                                <span class="sk-b truncate" x-text="c.name"></span>
                                <span class="flex items-center gap-1.5 shrink-0">
                                    <span x-show="c.over_capacity" class="sk-pill sk-p-amber">Over capacity</span>
                                    <span class="sk-muted tabular-nums" x-text="c.pct + '%'"></span>
                                </span>
                            </div>
                            <div class="h-[5px] rounded-full bg-[#E9EEF6] overflow-hidden">
                                <div class="h-full rounded-full transition-all" :class="occupancyBar(c.pct)" :style="'width:' + Math.max(c.pct, 1) + '%'"></div>
                            </div>
                            <div class="sk-ssub" x-text="c.barangay + ' · ' + c.families + (c.families === 1 ? ' family · ' : ' families · ') + c.individuals + ' of ' + c.capacity + ' people · ' + Math.max(0, c.capacity - c.individuals) + ' free'"></div>
                        </div>
                    </template>
                    <div x-show="commandBoard.centers.length === 0" class="sk-empty" style="padding: 14px;">No centers currently open.</div>
                </div>
            </section>

            {{-- Other public alerts: the first is the event band's (critique, seventh run). --}}
            <section class="sk-card overflow-hidden" x-show="commandBoard.alerts.length > 1" x-cloak>
                <div class="sk-chead">
                    <h2 class="sk-ctitle">Other public alerts in effect <span class="sk-csub" x-text="'· ' + (commandBoard.alerts.length - 1)"></span></h2>
                    <button type="button" @click="setTab('alerts')" class="sk-link">Manage <i data-lucide="arrow-right"></i></button>
                </div>
                <div class="max-h-[220px] overflow-y-auto">
                    <template x-for="a in commandBoard.alerts.slice(1)" :key="a.ref">
                        <div class="flex items-start gap-2.5 px-3.5 py-2.5 border-b border-[#EEF2F7] last:border-b-0">
                            <span :class="alertLevelBadge(a.level)" x-text="a.level" class="shrink-0 mt-0.5"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12.5px] sk-b" x-text="a.title.en"></p>
                                <p class="sk-sub" x-text="a.type + ' · ' + a.ref"></p>
                            </div>
                            <span class="sk-ssub shrink-0 mt-0.5" x-text="a.published_at ? new Date(a.published_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></span>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Resource readiness --}}
            <section class="sk-card overflow-hidden flex-grow">
                <div class="sk-chead">
                    <h2 class="sk-ctitle">Resource readiness</h2>
                    <button type="button" @click="setTab('resources')" class="sk-link">Manage <i data-lucide="arrow-right"></i></button>
                </div>
                <div class="max-h-[260px] overflow-y-auto">
                    <template x-for="r in commandBoard.resources" :key="r.ref">
                        <div class="flex items-center justify-between gap-2 px-3.5 py-2.5 border-b border-[#EEF2F7] last:border-b-0">
                            <div class="min-w-0">
                                <p class="text-[12.5px] sk-b truncate" x-text="r.name"></p>
                                <p class="sk-sub" x-text="r.category + ' · ' + r.available + ' / ' + r.quantity + ' available'"></p>
                            </div>
                            <span class="shrink-0" :class="r.readiness ? 'sk-pill sk-p-blue' : 'sk-muted'" x-text="r.readiness || '—'"></span>
                        </div>
                    </template>
                    <div x-show="commandBoard.resources.length === 0" class="sk-empty" style="padding: 14px;">No resources recorded.</div>
                </div>
            </section>
        </div>

        <section class="sk-card flex flex-col overflow-hidden" style="flex: 2 1 300px;">
            <div class="sk-chead">
                <h2 class="sk-ctitle">Recent activity</h2>
                <span class="sk-csub">Last 25 incident actions</span>
            </div>
            <ol class="px-3.5 overflow-y-auto max-h-[320px] flex-grow">
                <template x-for="(item, idx) in commandBoard.feed" :key="item.incident + '-' + item.at + '-' + idx">
                    <li class="flex gap-2.5 py-2 border-b border-[#EEF2F7] last:border-b-0">
                        <span class="w-1.5 h-1.5 mt-1.5 rounded-full bg-[#2563EB] shrink-0"></span>
                        <div class="flex-grow min-w-0 text-xs leading-relaxed">
                            <div>
                                <span class="sk-mono" style="color: #1D4ED8;" x-text="item.incident"></span>
                                <span class="sk-b" x-text="({ validate: 'validated', assign: 'assigned', dispatch: 'dispatched', escalate: 'escalated', resolve: 'resolved', close: 'closed' })[item.verb] || item.verb"></span>
                            </div>
                            <div class="sk-sub" x-show="item.note" x-text="item.note"></div>
                        </div>
                        <span class="sk-ssub shrink-0" x-text="item.at ? new Date(item.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></span>
                    </li>
                </template>
                <li x-show="commandBoard.feed.length === 0" class="sk-empty">No incident activity recorded yet.</li>
            </ol>
        </section>
    </div>{{-- /Status | activity --}}


</div>{{-- /Main Content --}}


@include('admin.partials.sakuna._incident-detail-modal')

</div>{{-- /x-init wrapper --}}

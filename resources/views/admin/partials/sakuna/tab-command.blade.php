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
            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 space-y-3 animate-pulse">
                <div class="w-10 h-10 bg-slate-100 rounded-xl"></div>
                <div class="h-6 bg-slate-100 rounded-lg w-14"></div>
                <div class="h-3 bg-slate-100 rounded w-20"></div>
                <div class="h-3 bg-slate-100 rounded w-16"></div>
            </div>
        @endfor
    </div>
    {{-- Map skeleton --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-5 animate-pulse">
        <div class="h-5 bg-slate-100 rounded w-80 mb-4"></div>
        <div class="h-60 bg-slate-100 rounded-xl"></div>
    </div>
    {{-- Incident queue + evac overview skeleton --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
        <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-card p-5 space-y-3 animate-pulse">
            <div class="h-5 bg-slate-100 rounded w-48 mb-2"></div>
            @for ($i = 0; $i < 6; $i++)
                <div class="h-11 bg-slate-100 rounded-xl"></div>
            @endfor
        </div>
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-card p-5 space-y-3 animate-pulse">
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
    x-data="{
        get groupedMarkers() {
            const byBarangay = {};
            (commandBoard?.markers || []).forEach(m => {
                const g = byBarangay[m.barangay] = byBarangay[m.barangay] || { name: m.barangay, incidents: [] };
                g.incidents.push(m);
            });
            const rank = { Critical: 0, High: 1, Medium: 2, Low: 3 };
            return Object.values(byBarangay).map(g => ({
                ...g,
                worst: [...g.incidents].sort((a, b) => (rank[a.severity] ?? 9) - (rank[b.severity] ?? 9))[0]?.severity,
            }));
        },
        dotColor(sev) {
            return { Critical: 'bg-rose-500', High: 'bg-orange-500', Medium: 'bg-amber-400', Low: 'bg-slate-300' }[sev] || 'bg-emerald-400';
        },
    }"
>

    {{-- ── B1. KPI Cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('incidents')">
            <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="flame" class="w-5 h-5 text-rose-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.incidents_open"></p>
            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mt-0.5">Open Incidents</p>
            <p class="text-xs text-rose-600 mt-0.5 font-medium" x-text="commandBoard.counts.incidents_total + ' total'"></p>
        </div>

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('evacuation-centers')">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="building-2" class="w-5 h-5 text-emerald-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.centers_open"></p>
            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mt-0.5">Evac. Centers Open</p>
            <p class="text-xs text-amber-600 mt-0.5 font-medium" x-text="commandBoard.centers.filter(c => c.pct >= 75).length + ' near/over capacity'"></p>
        </div>

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('evacuees')">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.families_sheltered"></p>
            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mt-0.5">Families Sheltered</p>
            <p class="text-xs text-blue-600 mt-0.5 font-medium" x-text="commandBoard.counts.individuals_sheltered + ' individuals'"></p>
        </div>

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('relief')">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="package" class="w-5 h-5 text-amber-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.relief_released"></p>
            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mt-0.5">Relief Released</p>
        </div>

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('damage-assessment')">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="clipboard-list" class="w-5 h-5 text-purple-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.damage_reports"></p>
            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mt-0.5">Damage Reports</p>
        </div>

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('alerts')">
            <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="bell-ring" class="w-5 h-5 text-orange-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.alerts_live"></p>
            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide mt-0.5">Active Public Alerts</p>
        </div>

    </div>{{-- /KPI Cards --}}


    {{-- ── B2. Active Incident Locations ───────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-800">Active Incident Locations</h3>
            <p class="text-xs text-slate-500 mt-0.5">Barangays with at least one open incident that carries coordinates</p>
        </div>

        <div class="p-5">
            <div class="relative bg-slate-50 rounded-2xl border border-slate-100 p-4 overflow-hidden">
                <div class="absolute inset-0 opacity-30" style="background-image: linear-gradient(rgba(203,213,225,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(203,213,225,0.4) 1px, transparent 1px); background-size: 40px 40px;"></div>

                <div class="relative z-10 grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 min-h-[120px]">
                    <template x-for="g in groupedMarkers" :key="g.name">
                        <div class="flex flex-col items-center gap-1.5 py-2 px-1 rounded-xl transition-all hover:bg-white/70 cursor-default" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <div class="relative">
                                <div class="w-4 h-4 rounded-full ring-2 ring-white ring-offset-1 shadow-sm" :class="dotColor(g.worst)"></div>
                                <span class="absolute -top-1.5 -right-1.5 w-3.5 h-3.5 bg-white rounded-full border border-slate-200 text-[8px] font-bold flex items-center justify-center text-slate-700" x-text="g.incidents.length"></span>
                            </div>
                            <span class="text-[9px] sm:text-[10px] text-center text-slate-600 font-medium leading-tight max-w-[70px]" x-text="g.name"></span>

                            <div x-show="open" x-cloak x-transition:enter="transition duration-[160ms] ease-standard" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                                 class="absolute z-30 bottom-full mb-2 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[11px] rounded-xl p-3 w-56 shadow-float pointer-events-none" style="white-space: normal;">
                                <p class="font-semibold text-xs mb-1.5" x-text="'Brgy. ' + g.name"></p>
                                <template x-for="inc in g.incidents" :key="inc.ref">
                                    <p class="text-slate-300 mb-0.5"><span class="text-white" x-text="inc.title"></span> — <span x-text="inc.severity"></span>, <span x-text="inc.status"></span></p>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div x-show="groupedMarkers.length === 0" class="col-span-full flex items-center justify-center text-xs text-slate-500 py-10">
                        No open incidents currently carry map coordinates.
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-200 flex flex-wrap gap-x-5 gap-y-1.5">
                    <span class="flex items-center gap-1.5 text-xs text-slate-500"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 flex-shrink-0"></span>Critical</span>
                    <span class="flex items-center gap-1.5 text-xs text-slate-500"><span class="w-2.5 h-2.5 rounded-full bg-orange-500 flex-shrink-0"></span>High</span>
                    <span class="flex items-center gap-1.5 text-xs text-slate-500"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 flex-shrink-0"></span>Medium</span>
                    <span class="flex items-center gap-1.5 text-xs text-slate-500"><span class="w-2.5 h-2.5 rounded-full bg-slate-300 flex-shrink-0"></span>Low</span>
                    <span class="ml-auto text-[10px] text-slate-500 italic">Dot badge = open incident count in that barangay</span>
                </div>
            </div>

            <p class="text-[11px] text-slate-500 mt-2 text-center italic">
                Note: this is a schematic panel, not a GIS map — evacuation centers have no stored coordinates yet, so only incidents are plotted.
            </p>
        </div>
    </div>{{-- /Map Panel --}}


    {{-- ── B3. Priority Incident Queue + Evacuation Center Overview ─────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

        {{-- Left: Priority Incident Queue --}}
        <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Priority Incident Queue</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Top 6 open incidents by severity</p>
                </div>
                <button @click="setTab('incidents')" class="text-xs text-brand-600 hover:text-brand-700 font-medium flex items-center gap-1 transition-colors">
                    View all <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Ref</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Barangay</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Severity</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="inc in priorityIncidents" :key="inc.ref">
                            <tr x-show="$store.session.inScope(inc.barangay)" class="hover:bg-slate-50/60 transition-colors group">
                                <td class="px-4 py-3"><span class="text-xs font-mono text-slate-500" x-text="inc.ref"></span></td>
                                <td class="px-4 py-3"><span class="text-xs font-medium text-slate-700" x-text="inc.type"></span></td>
                                <td class="px-4 py-3"><span class="text-xs text-slate-600" x-text="inc.barangay"></span></td>
                                <td class="px-4 py-3"><span :class="severityBadge(inc.severity) + ' px-2 py-0.5 rounded-full text-xs font-medium border'" x-text="inc.severity"></span></td>
                                <td class="px-4 py-3"><span :class="statusBadge(inc.status)" x-text="inc.status"></span></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openIncidentDetail(inc)" class="p-1.5 text-slate-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" title="View Details">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <button x-show="inc.available_verbs.includes('validate')" @click="actIncidentVerb(inc, 'validate')" class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Validate">
                                            <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="priorityIncidents.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-xs text-slate-500">No open incidents.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Right: Evacuation Center Overview --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Evacuation Center Status</h3>
                    <p class="text-xs text-slate-500 mt-0.5" x-text="commandBoard.centers.length + ' open centers'"></p>
                </div>
                <button @click="setTab('evacuation-centers')" class="text-xs text-brand-600 hover:text-brand-700 font-medium flex items-center gap-1 transition-colors">
                    Manage <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="divide-y divide-slate-50 max-h-[340px] overflow-y-auto">
                <template x-for="c in commandBoard.centers" :key="c.ref">
                    <div class="px-4 py-3 hover:bg-slate-50/60 transition-colors">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-700 truncate" x-text="c.name"></p>
                                <p class="text-[11px] text-slate-500 truncate" x-text="c.barangay + ' · ' + c.families + ' families'"></p>
                            </div>
                            <span x-show="c.over_capacity" class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700">Over Capacity</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" :class="occupancyBar(c.pct)" :style="'width:' + c.pct + '%'"></div>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-500 tabular-nums w-10 text-right" x-text="c.pct + '%'"></span>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-0.5" x-text="c.individuals + ' / ' + c.capacity + ' occupants'"></p>
                    </div>
                </template>
                <div x-show="commandBoard.centers.length === 0" class="px-4 py-8 text-center text-xs text-slate-500">No centers currently open.</div>
            </div>
        </div>

    </div>{{-- /Incident Queue + Evac Overview --}}


    {{-- ── B4. Active Public Alerts ─────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-slate-800">Active Public Alerts</h3>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700" x-text="commandBoard.alerts.length + ' active'"></span>
            </div>
            <button @click="setTab('alerts')" class="text-xs text-slate-500 hover:text-slate-700 flex items-center gap-1 transition-colors">
                Manage <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </button>
        </div>
        <div class="divide-y divide-slate-50">
            <template x-for="a in commandBoard.alerts" :key="a.ref">
                <div class="flex items-start gap-3 px-5 py-3 hover:bg-slate-50/60 transition-all">
                    <span :class="alertLevelBadge(a.level)" x-text="a.level" class="shrink-0 mt-0.5"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-700" x-text="a.title.en"></p>
                        <p class="text-[11px] text-slate-500 mt-0.5" x-text="a.type + ' · ' + a.ref"></p>
                    </div>
                    <span class="text-[10px] text-slate-500 whitespace-nowrap flex-shrink-0 mt-0.5" x-text="a.published_at ? new Date(a.published_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></span>
                </div>
            </template>
            <div x-show="commandBoard.alerts.length === 0" class="px-5 py-8 text-center text-xs text-slate-500">No active public alerts.</div>
        </div>
    </div>{{-- /Active Public Alerts --}}


    {{-- ── B5. Recent Activity Feed + Resource Readiness ─────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

        {{-- Left: Activity Feed --}}
        <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-800">Recent Activity Feed</h3>
                <span class="text-[11px] text-slate-500">Last 25 incident actions</span>
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
                            <p class="text-[11px] text-slate-500 mt-0.5" x-show="item.note" x-text="item.note"></p>
                        </div>
                        <span class="text-[10px] text-slate-500 whitespace-nowrap flex-shrink-0 mt-0.5" x-text="item.at ? new Date(item.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></span>
                    </div>
                </template>
                <div x-show="commandBoard.feed.length === 0" class="text-center text-xs text-slate-500 py-8">No incident activity recorded yet.</div>
            </div>
        </div>

        {{-- Right: Resource Readiness --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-800">Resource Readiness</h3>
                <button @click="setTab('resources')" class="text-xs text-brand-600 hover:text-brand-700 font-medium flex items-center gap-1 transition-colors">
                    Manage <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="px-4 py-4 space-y-2.5 max-h-[420px] overflow-y-auto">
                <template x-for="r in commandBoard.resources" :key="r.ref">
                    <div class="p-3 rounded-xl border border-slate-100 bg-slate-50/40">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <p class="text-xs font-semibold text-slate-700 truncate" x-text="r.name"></p>
                            <span class="text-[10px] text-slate-500" x-text="r.readiness || '—'"></span>
                        </div>
                        <p class="text-[11px] text-slate-500" x-text="r.category"></p>
                        <p class="text-[11px] text-slate-500 mt-1" x-text="r.available + ' / ' + r.quantity + ' available'"></p>
                    </div>
                </template>
                <div x-show="commandBoard.resources.length === 0" class="text-center text-xs text-slate-500 py-8">No resources recorded.</div>
            </div>
        </div>

    </div>{{-- /Activity + Resources --}}

</div>{{-- /Main Content --}}


@include('admin.partials.sakuna._incident-detail-modal')

</div>{{-- /x-init wrapper --}}

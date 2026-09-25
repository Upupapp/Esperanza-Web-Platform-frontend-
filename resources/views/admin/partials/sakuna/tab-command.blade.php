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
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mt-0.5">Open Incidents</p>
            <p class="text-xs text-rose-600 mt-0.5 font-medium" x-text="commandBoard.counts.incidents_total + ' total'"></p>
        </div>

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('evacuation-centers')">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="building-2" class="w-5 h-5 text-emerald-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.centers_open"></p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mt-0.5">Evac. Centers Open</p>
            <p class="text-xs text-amber-600 mt-0.5 font-medium" x-text="commandBoard.centers.filter(c => c.pct >= 75).length + ' near/over capacity'"></p>
        </div>

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('evacuees')">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.families_sheltered"></p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mt-0.5">Families Sheltered</p>
            <p class="text-xs text-blue-600 mt-0.5 font-medium" x-text="commandBoard.counts.individuals_sheltered + ' individuals'"></p>
        </div>

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('relief')">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="package" class="w-5 h-5 text-amber-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.relief_released"></p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mt-0.5">Relief Released</p>
        </div>

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('damage-assessment')">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="clipboard-list" class="w-5 h-5 text-purple-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.damage_reports"></p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mt-0.5">Damage Reports</p>
        </div>

        <div class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard p-4 cursor-pointer" @click="setTab('alerts')">
            <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center transition-transform duration-[220ms] ease-standard group-hover:scale-110 group-hover:-rotate-3">
                <i data-lucide="bell-ring" class="w-5 h-5 text-orange-600"></i>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-800 tabular-nums tracking-tight" x-text="commandBoard.counts.alerts_live"></p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mt-0.5">Active Public Alerts</p>
        </div>

    </div>{{-- /KPI Cards --}}


    {{-- ── B2. Active Incident Locations ───────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-800">Active Incident Locations</h3>
            <p class="text-xs text-slate-400 mt-0.5">Barangays with at least one open incident that carries coordinates</p>
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
                    <div x-show="groupedMarkers.length === 0" class="col-span-full flex items-center justify-center text-xs text-slate-400 py-10">
                        No open incidents currently carry map coordinates.
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-200 flex flex-wrap gap-x-5 gap-y-1.5">
                    <span class="flex items-center gap-1.5 text-xs text-slate-500"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 flex-shrink-0"></span>Critical</span>
                    <span class="flex items-center gap-1.5 text-xs text-slate-500"><span class="w-2.5 h-2.5 rounded-full bg-orange-500 flex-shrink-0"></span>High</span>
                    <span class="flex items-center gap-1.5 text-xs text-slate-500"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 flex-shrink-0"></span>Medium</span>
                    <span class="flex items-center gap-1.5 text-xs text-slate-500"><span class="w-2.5 h-2.5 rounded-full bg-slate-300 flex-shrink-0"></span>Low</span>
                    <span class="ml-auto text-[10px] text-slate-400 italic">Dot badge = open incident count in that barangay</span>
                </div>
            </div>

            <p class="text-[11px] text-slate-400 mt-2 text-center italic">
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
                    <p class="text-xs text-slate-400 mt-0.5">Top 6 open incidents by severity</p>
                </div>
                <button @click="setTab('incidents')" class="text-xs text-brand-600 hover:text-brand-700 font-medium flex items-center gap-1 transition-colors">
                    View all <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wide">Ref</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wide">Type</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wide">Barangay</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wide">Severity</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wide">Actions</th>
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
                                        <button @click="openIncidentDetail(inc)" class="p-1.5 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" title="View Details">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <button x-show="inc.available_verbs.includes('validate')" @click="actIncidentVerb(inc, 'validate')" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Validate">
                                            <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="priorityIncidents.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-xs text-slate-400">No open incidents.</td>
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
                    <p class="text-xs text-slate-400 mt-0.5" x-text="commandBoard.centers.length + ' open centers'"></p>
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
                                <p class="text-[11px] text-slate-400 truncate" x-text="c.barangay + ' · ' + c.families + ' families'"></p>
                            </div>
                            <span x-show="c.over_capacity" class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700">Over Capacity</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" :class="occupancyBar(c.pct)" :style="'width:' + c.pct + '%'"></div>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-500 tabular-nums w-10 text-right" x-text="c.pct + '%'"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-0.5" x-text="c.individuals + ' / ' + c.capacity + ' occupants'"></p>
                    </div>
                </template>
                <div x-show="commandBoard.centers.length === 0" class="px-4 py-8 text-center text-xs text-slate-400">No centers currently open.</div>
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
                        <p class="text-[11px] text-slate-400 mt-0.5" x-text="a.type + ' · ' + a.ref"></p>
                    </div>
                    <span class="text-[10px] text-slate-400 whitespace-nowrap flex-shrink-0 mt-0.5" x-text="a.published_at ? new Date(a.published_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></span>
                </div>
            </template>
            <div x-show="commandBoard.alerts.length === 0" class="px-5 py-8 text-center text-xs text-slate-400">No active public alerts.</div>
        </div>
    </div>{{-- /Active Public Alerts --}}


    {{-- ── B5. Recent Activity Feed + Resource Readiness ─────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

        {{-- Left: Activity Feed --}}
        <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-800">Recent Activity Feed</h3>
                <span class="text-[11px] text-slate-400">Last 25 incident actions</span>
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
                            <p class="text-[11px] text-slate-400 mt-0.5" x-show="item.note" x-text="item.note"></p>
                        </div>
                        <span class="text-[10px] text-slate-400 whitespace-nowrap flex-shrink-0 mt-0.5" x-text="item.at ? new Date(item.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></span>
                    </div>
                </template>
                <div x-show="commandBoard.feed.length === 0" class="text-center text-xs text-slate-400 py-8">No incident activity recorded yet.</div>
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
                            <span class="text-[10px] text-slate-400" x-text="r.readiness || '—'"></span>
                        </div>
                        <p class="text-[11px] text-slate-500" x-text="r.category"></p>
                        <p class="text-[11px] text-slate-400 mt-1" x-text="r.available + ' / ' + r.quantity + ' available'"></p>
                    </div>
                </template>
                <div x-show="commandBoard.resources.length === 0" class="text-center text-xs text-slate-400 py-8">No resources recorded.</div>
            </div>
        </div>

    </div>{{-- /Activity + Resources --}}

</div>{{-- /Main Content --}}


{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- INCIDENT DETAIL MODAL                                                        --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div
    x-show="showIncidentDetail"
    x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-[150ms] ease-exit"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showIncidentDetail = false"
    x-trap="showIncidentDetail"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="showIncidentDetail = false">
    <div
        x-transition:enter="transition duration-[200ms] ease-emphasized"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        class="bg-white rounded-2xl shadow-float w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden"
    >
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center">
                    <i data-lucide="flame" class="w-4.5 h-4.5 text-rose-600"></i>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-800" x-text="selectedIncident?.ref ?? '—'"></h2>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="(selectedIncident?.type ?? '') + ' — Brgy. ' + (selectedIncident?.barangay ?? '')"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span :class="statusBadge(selectedIncident?.status ?? '')" x-text="selectedIncident?.status ?? ''"></span>
                <button @click="showIncidentDetail = false" class="w-8 h-8 rounded-xl hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors ml-2">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Modal Body --}}
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Description</p>
                <p class="text-sm text-slate-700 leading-relaxed" x-text="selectedIncident?.description || '—'"></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="space-y-3">
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i> Incident Information
                    </h4>
                    <dl class="space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-slate-500 flex-shrink-0">Type</dt>
                            <dd class="text-xs font-medium text-slate-800 text-right" x-text="selectedIncident?.type ?? '—'"></dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-slate-500 flex-shrink-0">Severity</dt>
                            <dd><span :class="severityBadge(selectedIncident?.severity ?? '') + ' px-2 py-0.5 rounded-full text-xs font-medium border'" x-text="selectedIncident?.severity ?? '—'"></span></dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-slate-500 flex-shrink-0">Date Reported</dt>
                            <dd class="text-xs font-medium text-slate-800 text-right" x-text="selectedIncident?.reported ? new Date(selectedIncident.reported).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—'"></dd>
                        </div>
                    </dl>
                </div>

                <div class="space-y-3">
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Location & Reporter
                    </h4>
                    <dl class="space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-slate-500 flex-shrink-0">Barangay</dt>
                            <dd class="text-xs font-medium text-slate-800 text-right" x-text="selectedIncident?.barangay ?? '—'"></dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-slate-500 flex-shrink-0">Sitio</dt>
                            <dd class="text-xs font-medium text-slate-800 text-right" x-text="selectedIncident?.sitio || '—'"></dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-slate-500 flex-shrink-0">Reporter</dt>
                            <dd class="text-xs font-medium text-slate-800 text-right" x-text="selectedIncident?.reporter_name || '—'"></dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-slate-500 flex-shrink-0">Assigned To</dt>
                            <dd class="text-xs font-medium text-slate-800 text-right" x-text="selectedIncident?.assigned_name || 'Unassigned'"></dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Timeline --}}
            <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide flex items-center gap-1.5 mb-3">
                    <i data-lucide="history" class="w-3.5 h-3.5"></i> Incident Timeline
                </h4>
                <div class="space-y-0">
                    <template x-for="(entry, idx) in (selectedIncident?.timeline || [])" :key="idx">
                        <div class="flex items-start gap-3 py-2.5" :class="idx < (selectedIncident.timeline.length - 1) ? 'border-b border-slate-50' : ''">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <div class="w-2 h-2 rounded-full bg-brand-400 mt-1.5"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-semibold text-slate-700 capitalize" x-text="entry.verb"></span>
                                    <span class="text-[11px] text-slate-400" x-text="entry.from + (entry.to && entry.to !== entry.from ? ' → ' + entry.to : '')"></span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5" x-show="entry.note" x-text="entry.note"></p>
                            </div>
                            <span class="text-[11px] text-slate-400 whitespace-nowrap flex-shrink-0 mt-0.5" x-text="entry.at ? new Date(entry.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></span>
                        </div>
                    </template>
                    <p x-show="!(selectedIncident?.timeline || []).length" class="text-xs text-slate-400 py-2">No actions recorded yet — reported, awaiting validation.</p>
                </div>
            </div>

            {{-- Escalate mini-form --}}
            <div x-show="showEscalateForm" x-cloak class="p-4 bg-orange-50 border border-orange-200 rounded-xl space-y-2.5">
                <p class="text-xs font-semibold text-orange-800">Escalate — raise severity without rewinding progress</p>
                <div class="flex items-center gap-2">
                    <select aria-label="New severity" x-model="escalateSeverity" class="text-xs rounded-lg border border-orange-200 py-1.5 px-2">
                        <option>Critical</option><option>High</option><option>Medium</option><option>Low</option>
                    </select>
                    <input type="text" x-model="escalateNote" placeholder="Note (optional)" class="flex-1 text-xs rounded-lg border border-orange-200 py-1.5 px-2">
                </div>
                <div class="flex gap-2">
                    <button @click="actIncidentVerb(selectedIncident, 'escalate', { severity: escalateSeverity, note: escalateNote || null }); escalateNote = ''" class="px-3 py-1.5 text-xs font-semibold bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors">Confirm Escalation</button>
                    <button @click="showEscalateForm = false" class="px-3 py-1.5 text-xs font-medium bg-white text-slate-600 border border-slate-200 rounded-lg transition-colors">Cancel</button>
                </div>
            </div>

            {{-- Assign mini-form --}}
            <div x-show="showAssignForm" x-cloak class="p-4 bg-violet-50 border border-violet-200 rounded-xl space-y-2.5">
                <p class="text-xs font-semibold text-violet-800">Assign to personnel</p>
                <div class="flex items-center gap-2">
                    <select aria-label="Assign to" x-model="assignTo" class="flex-1 text-xs rounded-lg border border-violet-200 py-1.5 px-2">
                        <option value="">Select…</option>
                        <template x-for="p in personnel" :key="p.id"><option :value="p.id" x-text="p.name + ' (' + p.employee_id + ')'"></option></template>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button :disabled="!assignTo" @click="actIncidentVerb(selectedIncident, 'assign', { assigned_to: assignTo }); assignTo = ''" class="px-3 py-1.5 text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-colors disabled:opacity-40">Confirm Assignment</button>
                    <button @click="showAssignForm = false" class="px-3 py-1.5 text-xs font-medium bg-white text-slate-600 border border-slate-200 rounded-lg transition-colors">Cancel</button>
                </div>
            </div>

        </div>{{-- /Modal Body --}}

        {{-- Modal Footer Actions --}}
        <div class="flex flex-wrap items-center gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex-shrink-0">
            <button x-show="selectedIncident?.available_verbs?.includes('validate')" @click="actIncidentVerb(selectedIncident, 'validate')" class="px-3 py-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">Validate</button>
            <button x-show="selectedIncident?.available_verbs?.includes('assign')" @click="showAssignForm = !showAssignForm; showEscalateForm = false" class="px-3 py-1.5 text-xs font-medium bg-white hover:bg-slate-50 text-violet-700 border border-violet-200 rounded-lg transition-colors">Assign</button>
            <button x-show="selectedIncident?.available_verbs?.includes('dispatch')" @click="actIncidentVerb(selectedIncident, 'dispatch')" class="px-3 py-1.5 text-xs font-medium bg-white hover:bg-slate-50 text-purple-700 border border-purple-200 rounded-lg transition-colors">Dispatch</button>
            <button x-show="selectedIncident?.available_verbs?.includes('escalate')" @click="showEscalateForm = !showEscalateForm; showAssignForm = false" class="px-3 py-1.5 text-xs font-medium bg-white hover:bg-slate-50 text-orange-700 border border-orange-200 rounded-lg transition-colors">Escalate</button>
            <button x-show="selectedIncident?.available_verbs?.includes('resolve')" @click="actIncidentVerb(selectedIncident, 'resolve')" class="px-3 py-1.5 text-xs font-medium bg-white hover:bg-slate-50 text-emerald-700 border border-emerald-200 rounded-lg transition-colors">Resolve</button>
            <button x-show="selectedIncident?.available_verbs?.includes('close')" @click="actIncidentVerb(selectedIncident, 'close')" class="px-3 py-1.5 text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200 rounded-lg transition-colors">Close</button>
            <button @click="showIncidentDetail = false" class="ml-auto px-4 py-1.5 text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition-colors">Dismiss</button>
        </div>
    </div>
</div>{{-- /Incident Detail Modal --}}

</div>{{-- /x-init wrapper --}}

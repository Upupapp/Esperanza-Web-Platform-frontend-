{{--
    tab-incidents.blade.php
    Incident Management Workspace — SAKUNA Disaster Management Module
    Included by sakuna.blade.php — NO x-data at root.
    Parent Alpine state: incidentSearch/incidentTypeFilter/incidentSeverityFilter/
    incidentStatusFilter/incidentBarangayFilter, incidentsList/incidentsLoading/
    incidentsFailed, selectedIncident/showIncidentDetail, showCreateIncident/
    incidentForm/incidentSaving. Helpers: statusBadge(st), severityBadge(sev),
    toast(msg, variant), openIncidentDetail(row), actIncidentVerb(inc, verb, payload).

    GET/POST /sakuna/incidents (SakunaController) -- the real status chain is
    Submitted -> Under Verification -> Assigned -> Processing -> Completed ->
    Archived (IncidentLifecycle::CHAIN), not the mock's 10-value New/For
    Validation/Validated/Assigned/Dispatched/En Route/On Scene/Contained/
    Resolved/Closed chain. Which verbs a row may take next comes from the
    row's own `available_verbs`, not a hardcoded status switch.
--}}

<div x-data="{
    moreFilters: false,
    elapsed(iso) {
        if (!iso) return '—';
        const mins = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
        if (mins < 60) return mins + 'm';
        if (mins < 1440) return Math.round(mins / 60) + 'h';
        return Math.round(mins / 1440) + 'd';
    },
    // Triage (critique 2026-11-03, sixth run): the longest-waiting incidents
    // come first unless another column is chosen, and several waiting to be
    // validated can be validated together after one confirmation.
    sortKey: 'waiting',
    picked: [],
    bulkAsk: false,
    bulkBusy: false,
    sortBy(key) { this.sortKey = key; },
    ariaSort(key) { return this.sortKey === key ? (key === 'reported' || key === 'ref' ? 'descending' : 'ascending') : 'none'; },
    get validatable() { return this.filteredIncidents.filter(i => i.available_verbs?.includes('validate')); },
    togglePick(ref) { this.picked = this.picked.includes(ref) ? this.picked.filter(r => r !== ref) : [...this.picked, ref]; },
    toggleAll() { this.picked = this.picked.length === this.validatable.length ? [] : this.validatable.map(i => i.ref); },
    async validatePicked() {
        if (this.bulkBusy) return;
        this.bulkBusy = true;
        for (const ref of [...this.picked]) {
            const inc = incidentsList.find(i => i.ref === ref);
            if (inc && inc.available_verbs?.includes('validate')) await actIncidentVerb(inc, 'validate');
        }
        this.picked = []; this.bulkAsk = false; this.bulkBusy = false;
    },
    get filteredIncidents() {
        const q = incidentSearch.toLowerCase();
        const rank = { Critical: 0, High: 1, Medium: 2, Low: 3 };
        const at = (i) => new Date(i.reported || 0).getTime();
        const order = {
            waiting: (a, b) => (isWaiting(b) - isWaiting(a)) || (isWaiting(a) ? at(a) - at(b) : at(b) - at(a)),
            severity: (a, b) => ((rank[a.severity] ?? 9) - (rank[b.severity] ?? 9)) || at(a) - at(b),
            reported: (a, b) => at(b) - at(a),
            ref: (a, b) => String(b.ref).localeCompare(String(a.ref)),
        }[this.sortKey];
        return incidentsList.filter(inc =>
            $store.session.inScope(inc.barangay) &&
            (!q || inc.ref.toLowerCase().includes(q) || inc.type.toLowerCase().includes(q) || inc.barangay.toLowerCase().includes(q)) &&
            (!incidentTypeFilter || inc.type === incidentTypeFilter) &&
            (!incidentSeverityFilter || inc.severity === incidentSeverityFilter) &&
            (!incidentStatusFilter || inc.status === incidentStatusFilter) &&
            (!incidentBarangayFilter || inc.barangay === incidentBarangayFilter) &&
            (!incidentSourceFilter || (incidentSourceFilter === 'resident') === ['Citizen Portal', 'Mobile App'].includes(inc.source)) &&
            (!incidentAssignedFilter || (incidentAssignedFilter === 'none' ? !inc.assigned_name && !['Completed', 'Archived', 'Rejected'].includes(inc.status) : !!inc.assigned_name))
        ).sort(order);
    },
}" x-init="loadIncidentsList()" class="space-y-3">

{{-- ── Stats + actions (Sakuna Redesign, 2026-11-02) ───────────────────────────────────── --}}
<div class="sk-pagebar">
    <div class="sk-stats">
        <div class="sk-stat"><span class="sk-slab">Total</span><span class="sk-sval tabular-nums" x-text="fig(incidentsList.length, incidentsLoading || incidentsFailed)"></span></div>
        <div class="sk-stat"><span class="sk-slab"><span class="sk-dot sk-t-red" x-show="incidentsList.some(i => i.severity === 'Critical')"></span>Critical</span><span class="sk-sval tabular-nums" :class="incidentsList.some(i => i.severity === 'Critical') ? 'sk-t-red' : ''" x-text="fig(incidentsList.filter(i => i.severity === 'Critical').length, incidentsLoading || incidentsFailed)"></span></div>
        <div class="sk-stat"><span class="sk-slab">Active</span><span class="sk-sval tabular-nums" x-text="fig(incidentsList.filter(i => !['Completed','Archived','Rejected'].includes(i.status)).length, incidentsLoading || incidentsFailed)"></span></div>
        <div class="sk-stat"><span class="sk-slab">Resolved</span><span class="sk-sval tabular-nums" x-text="fig(incidentsList.filter(i => i.status === 'Completed').length, incidentsLoading || incidentsFailed)"></span></div>
    </div>
    <div class="sk-actions">
        <button type="button" data-info class="sk-iconbtn sk-iconbtn-bare" aria-label="About Incident Management" title="Incident Management: report, track, validate, and coordinate response to all active incidents.">
            <i data-lucide="info"></i>
        </button>
        <div class="sk-btngrp" role="group" aria-label="Export">
            <button type="button" @click="exportCSV(filteredIncidents, 'incidents-export.csv')" class="sk-btn">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button type="button" @click="exportPDF(filteredIncidents, 'incidents-export.csv')" class="sk-btn">
                <i data-lucide="file-text"></i> Export PDF
            </button>
        </div>
        <button type="button" x-show="can('sakuna_incidents', 'create')" @click="openCreateIncident()" :disabled="onLastCopy" :title="onLastCopy ? 'Waits for the connection' : ''" class="sk-btn sk-btn-p disabled:opacity-50">
            <i data-lucide="plus"></i> New incident
        </button>
    </div>
</div>

<div x-show="incidentsLoading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-64 opacity-80" /></div>
<div x-show="incidentsFailed" x-cloak><x-ui.error-state tone="neutral" icon="cloud-off" title="Not loaded" messageExpr="'Could not load incidents. ' + offlineWhy" retryExpr="loadIncidentsList()" :retryable="true" /></div>

<section x-show="!incidentsLoading && !incidentsFailed" x-cloak class="sk-card overflow-hidden">

{{-- ── Filter Bar ───────────────────────────────────────────────────────────────────────── --}}
<h2 class="sr-only">Incidents</h2>
<div class="sk-toolbar">
    <div class="sk-search">
        <i data-lucide="search"></i>
        <input aria-label="Search by ID, type, or barangay" type="search" x-model="incidentSearch"
            placeholder="Search by ID, type, or barangay…">
    </div>
    <select x-show="moreFilters || incidentTypeFilter" aria-label="Incident type filter" x-model="incidentTypeFilter" class="sk-sel">
        <option value="">All types</option>
        <option>Flood</option><option>Landslide</option><option>Fire</option><option>Earthquake</option>
        <option>Vehicular Accident</option><option>Medical Emergency</option><option>Missing Person</option>
        <option>Stranded Resident</option><option>Fallen Tree</option><option>Downed Power Line</option>
        <option>Road Obstruction</option><option>Damaged Bridge</option><option>Rescue Request</option>
        <option>Drowning or Water Rescue</option><option>Agricultural Damage</option><option>Structural Damage</option>
        <option>Water Interruption</option><option>Power Interruption</option><option>Public Health Emergency</option>
        <option>Other</option>
    </select>
    <select aria-label="Incident severity filter" x-model="incidentSeverityFilter" class="sk-sel">
        <option value="">All severities</option>
        <option>Critical</option><option>High</option><option>Medium</option><option>Low</option>
    </select>
    <select aria-label="Incident status filter" x-model="incidentStatusFilter" class="sk-sel">
        <option value="">All statuses</option>
        <option value="Submitted">Reported</option>
        <option value="Under Verification">Validated</option>
        <option value="Assigned">Assigned</option>
        <option value="Processing">Dispatched</option>
        <option value="Completed">Resolved</option>
        <option value="Archived">Closed</option>
        {{-- Reports marked Not valid can be listed too (QA 2026-11-18); the Active tile leaves them out. --}}
        <option value="Rejected">Not valid</option>
    </select>
    <select aria-label="Assigned to filter" x-model="incidentAssignedFilter" class="sk-sel">
        <option value="">Anyone or nobody</option>
        <option value="none">Nobody assigned (open)</option>
        <option value="someone">Someone assigned</option>
    </select>
    <select x-show="moreFilters || incidentBarangayFilter" aria-label="Incident barangay filter" x-model="incidentBarangayFilter" class="sk-sel">
        <option value="">All barangays</option>
        @foreach($barangays as $brgy)
            <option>{{ $brgy }}</option>
        @endforeach
    </select>
    {{-- Reports residents sent from their Sakuna page, apart from the ones staff entered (2026-10-17). --}}
    <select x-show="moreFilters || incidentSourceFilter" aria-label="Incident source filter" x-model="incidentSourceFilter" class="sk-sel">
        <option value="">All sources</option>
        <option value="resident">Reported by residents</option>
        <option value="staff">Entered by staff</option>
    </select>
    <button type="button" class="sk-btn sk-btn-sm" @click="moreFilters = !moreFilters" :aria-expanded="moreFilters ? 'true' : 'false'">
        <i data-lucide="sliders-horizontal"></i><span x-text="moreFilters ? 'Fewer filters' : 'More filters'"></span>
    </button>
    <button type="button" @click="incidentSearch=''; incidentTypeFilter=''; incidentSeverityFilter=''; incidentStatusFilter=''; incidentBarangayFilter=''; incidentSourceFilter=''; incidentAssignedFilter='';"
        class="sk-clear">Clear</button>
    <span class="sk-csub ml-auto whitespace-nowrap" x-text="filteredIncidents.length + (filteredIncidents.length === 1 ? ' incident' : ' incidents')"></span>
</div>
<div x-show="picked.length" x-cloak class="flex flex-wrap items-center gap-2 px-3 py-2 border-b border-[#EEF2F7] bg-brand-50/60" role="region" aria-label="Chosen incidents">
    <template x-if="!bulkAsk">
        <div class="flex flex-wrap items-center gap-2 w-full">
            <span class="text-sm text-ink-body" x-text="picked.length + (picked.length === 1 ? ' incident chosen' : ' incidents chosen')"></span>
            <button type="button" class="sk-btn sk-btn-sm sk-btn-p" @click="bulkAsk = true"><i data-lucide="check-check"></i> <span x-text="'Validate ' + picked.length + '…'"></span></button>
            <button type="button" class="sk-clear" @click="picked = []">Clear choice</button>
        </div>
    </template>
    <template x-if="bulkAsk">
        <div class="flex flex-wrap items-center gap-2 w-full" role="alertdialog" aria-labelledby="bulk-validate-text">
            <span id="bulk-validate-text" class="text-sm text-ink-body flex-1 min-w-[14rem]" x-text="'Validate ' + picked.length + (picked.length === 1 ? ' incident? It is' : ' incidents? Each is') + ' marked validated, as if done one by one.'"></span>
            <button type="button" class="sk-btn sk-btn-sm" @click="bulkAsk = false">Not yet</button>
            <button type="button" class="sk-btn sk-btn-sm sk-btn-p" :disabled="bulkBusy" @click="validatePicked()" x-text="bulkBusy ? 'Validating…' : 'Validate ' + picked.length"></button>
        </div>
    </template>
</div>

{{-- ── Incident Table ───────────────────────────────────────────────────────────────────── --}}
<div class="sk-scroll" tabindex="0" role="region" aria-label="Incidents table (scrolls sideways)">
    <table class="sk-tbl sk-tbl-act sk-tbl-tight">
        <thead>
            <tr>
                <th class="!pr-0 w-8"><input type="checkbox" class="accent-brand-600 w-4 h-4 align-middle" x-show="validatable.length" :checked="picked.length && picked.length === validatable.length" @change="toggleAll()" aria-label="Choose every incident waiting to be validated"></th>
                <th :aria-sort="ariaSort('ref')"><button type="button" class="sk-sort" :class="sortKey === 'ref' ? 'sk-sort-on' : ''" @click="sortBy('ref')">Ref</button></th>
                <th :aria-sort="ariaSort('reported')"><button type="button" class="sk-sort" :class="sortKey === 'reported' ? 'sk-sort-on' : ''" @click="sortBy('reported')">Type · reported</button></th>
                <th>Barangay / sitio</th>
                <th :aria-sort="ariaSort('severity')"><button type="button" class="sk-sort" :class="sortKey === 'severity' ? 'sk-sort-on' : ''" @click="sortBy('severity')">Severity</button></th>
                <th :aria-sort="ariaSort('waiting')"><button type="button" class="sk-sort" :class="sortKey === 'waiting' ? 'sk-sort-on' : ''" @click="sortBy('waiting')">Waiting</button></th>
                <th>Status · assigned to</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <template x-for="inc in filteredIncidents" :key="inc.ref">
                <tr @click="openIncidentDetail(inc)" tabindex="0" @keydown.enter.prevent="openIncidentDetail(inc)">
                    <td class="!pr-0" @click.stop><input type="checkbox" class="accent-brand-600 w-4 h-4 align-middle" x-show="inc.available_verbs.includes('validate')" :checked="picked.includes(inc.ref)" @change="togglePick(inc.ref)" :aria-label="'Choose ' + inc.ref + ' to validate'"></td>
                    <td class="whitespace-nowrap"><span class="sk-mono" x-text="inc.ref"></span></td>
                    <td class="whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <i :data-lucide="incidentTypeIcon(inc.type)" class="w-3.5 h-3.5 text-[#1D4ED8] flex-shrink-0"></i>
                            <span class="sk-b" x-text="inc.type"></span>
                            <span x-show="['Citizen Portal', 'Mobile App'].includes(inc.source)" class="sk-pill sk-p-blue ml-1" style="font-weight: 500;">Resident</span>
                        </div>
                        <div class="sk-sub" x-text="inc.reported ? new Date(inc.reported).toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit'}) : '—'"></div>
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5 flex-wrap"><span class="sk-b" x-text="inc.barangay"></span><span class="sk-muted" x-show="inc.sitio" x-text="'· ' + inc.sitio"></span></div>
                        {{-- On the Command Center's map, or not (no pin saved): never a guessed point. --}}
                        <div class="sk-sub inline-flex items-center gap-1" :class="hasMapPin(inc) ? '!text-emerald-700' : ''">
                            <i :data-lucide="hasMapPin(inc) ? 'map-pin-check' : 'map-pin-off'" class="w-3 h-3"></i>
                            <span x-text="hasMapPin(inc) ? 'On the map' : 'No map pin'"></span>
                        </div>
                    </td>
                    <td class="whitespace-nowrap"><span :class="severityBadge(inc.severity)" class="sk-pill" x-text="inc.severity"></span></td>
                    <td class="whitespace-nowrap tabular-nums" :class="waitTone(inc)"><span x-text="elapsed(inc.reported)"></span><span x-show="waitTone(inc) === 'sk-wait-red'" class="sk-longest">Longest</span></td>
                    <td class="whitespace-nowrap">
                        <span :class="statusBadge(inc.status)" class="sk-pill" x-text="incidentStatusLabel(inc.status)" :title="'Status on record: ' + inc.status"></span>
                        <div class="sk-sub" x-text="inc.assigned_name || 'Nobody assigned'"></div>
                    </td>
                    <td class="whitespace-nowrap" @click.stop>
                        <div class="inline-flex items-center gap-1.5">
                            <button type="button" x-show="inc.available_verbs.includes('validate')" @click="openIncidentToValidate(inc)" class="sk-btn sk-btn-sm sk-btn-soft" :aria-label="'Review and validate ' + inc.ref">
                                <i data-lucide="check"></i> Validate…
                            </button>
                            <button type="button" tabindex="-1" @click="openIncidentDetail(inc)" class="sk-btn sk-btn-sm sk-btn-soft">
                                <i data-lucide="eye"></i> View
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
            <tr x-show="filteredIncidents.length === 0">
                <td colspan="8">
                    <div class="sk-empty flex-col">
                        <i data-lucide="search-x"></i>
                        <span><b class="text-slate-700">No incidents match your filters.</b></span>
                        <button type="button" @click="incidentSearch=''; incidentTypeFilter=''; incidentSeverityFilter=''; incidentStatusFilter=''; incidentBarangayFilter=''; incidentSourceFilter=''; incidentAssignedFilter='';"
                            class="sk-clear">Clear filters</button>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

</section>{{-- /loaded content --}}

{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
{{-- INCIDENT DETAIL MODAL (shared)                                                         --}}
{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
@include('admin.partials.sakuna._incident-detail-modal')

{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
{{-- REPORT NEW INCIDENT MODAL                                                              --}}
{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
<div x-show="showCreateIncident" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showCreateIncident = false"
    x-transition:enter="transition duration-[200ms] ease-emphasized"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
        x-trap="showCreateIncident"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCreateIncident = false">

    <div x-transition:enter="transition duration-[200ms] ease-emphasized"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-xl max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-brand-600/10">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center">
                    <i data-lucide="siren" class="w-5 h-5 text-rose-600"></i>
                </span>
                <div>
                    <h3 class="text-base font-semibold text-ink-body">Report new incident</h3>
                    <p class="text-xs text-ink-muted">The reporting officer is recorded automatically from your session.</p>
                </div>
            </div>
            <button aria-label="Close window" @click="showCreateIncident = false" class="sk-mclose">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="incident-title">Title <span class="text-rose-500">*</span></label>
                <input id="incident-title" type="text" x-model="incidentForm.title" placeholder="e.g. Flooding along Sitio Lawod"
                    class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5" for="incident-type">Incident Type <span class="text-rose-500">*</span></label>
                    <select id="incident-type" x-model="incidentForm.type" class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
                        <option value="">Select type...</option>
                        <option>Flood</option><option>Landslide</option><option>Fire</option><option>Earthquake</option>
                        <option>Vehicular Accident</option><option>Medical Emergency</option><option>Missing Person</option>
                        <option>Stranded Resident</option><option>Fallen Tree</option><option>Downed Power Line</option>
                        <option>Road Obstruction</option><option>Damaged Bridge</option><option>Rescue Request</option>
                        <option>Drowning or Water Rescue</option><option>Agricultural Damage</option><option>Structural Damage</option>
                        <option>Water Interruption</option><option>Power Interruption</option><option>Public Health Emergency</option>
                        <option>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5" for="incident-barangay">Barangay <span class="text-rose-500">*</span></label>
                    <select id="incident-barangay" x-model="incidentForm.barangay" class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
                        <option value="">Select barangay...</option>
                        @foreach($barangays as $brgy)
                            <option>{{ $brgy }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1.5" for="incident-sitio">Sitio / Exact Location</label>
                    <input id="incident-sitio" type="text" x-model="incidentForm.sitio" placeholder="e.g. Near the old bridge"
                        class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
                </div>
                {{-- Where it is on the map (2026-10-26): optional, as the written place above is kept either way. --}}
                <div class="sm:col-span-2">
                    <x-sakuna.location-picker target="incidentForm" search subject="incident" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5" for="incident-severity">Severity <span class="text-rose-500">*</span></label>
                    <select id="incident-severity" x-model="incidentForm.severity" class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
                        <option value="">Select severity...</option>
                        <option>Critical</option><option>High</option><option>Medium</option><option>Low</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5" for="incident-source">Source / Channel</label>
                    <select id="incident-source" x-model="incidentForm.source" class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
                        <option value="">Select source...</option>
                        <option>Barangay</option>
                        <option>MDRRMO Hotline</option>
                        <option>Police</option>
                        <option>Fire</option>
                        <option>Health Office</option>
                        <option>Field Responder</option>
                        <option>Manual Encoding</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1.5" for="incident-description">Description</label>
                    <textarea id="incident-description" x-model="incidentForm.description" rows="4" placeholder="Describe the incident in detail — nature, current situation, immediate danger, etc."
                        class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 resize-none transition-colors"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 px-6 py-4 border-t border-brand-600/10 bg-slate-50/70 rounded-b-2xl">
            <button @click="showCreateIncident = false" class="sk-btn">Cancel</button>
            <button :disabled="incidentSaving || !incidentForm.title || !incidentForm.type || !incidentForm.barangay || !incidentForm.severity" @click="submitIncident()"
                class="sk-btn sk-btn-p ml-auto">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span x-text="incidentSaving ? 'Reporting…' : 'Submit incident'"></span>
            </button>
        </div>
    </div>
</div>{{-- /Report New Incident Modal --}}

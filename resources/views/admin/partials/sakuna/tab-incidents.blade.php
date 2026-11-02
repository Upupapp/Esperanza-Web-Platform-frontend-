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
    incidentTypeIcon(type) {
        const map = {
            'Flood': 'waves', 'Landslide': 'triangle-alert', 'Fire': 'flame', 'Earthquake': 'activity',
            'Vehicular Accident': 'car', 'Medical Emergency': 'heart-pulse', 'Missing Person': 'search',
            'Stranded Resident': 'anchor', 'Fallen Tree': 'tree-pine', 'Downed Power Line': 'zap',
            'Road Obstruction': 'cone', 'Damaged Bridge': 'construction', 'Rescue Request': 'life-buoy',
            'Drowning or Water Rescue': 'anchor', 'Agricultural Damage': 'wheat', 'Structural Damage': 'building-2',
            'Water Interruption': 'droplets', 'Power Interruption': 'zap-off', 'Public Health Emergency': 'shield-alert',
        };
        return map[type] || 'alert-circle';
    },
    elapsed(iso) {
        if (!iso) return '—';
        const mins = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
        if (mins < 60) return mins + 'm';
        if (mins < 1440) return Math.round(mins / 60) + 'h';
        return Math.round(mins / 1440) + 'd';
    },
    get filteredIncidents() {
        const q = incidentSearch.toLowerCase();
        return incidentsList.filter(inc =>
            $store.session.inScope(inc.barangay) &&
            (!q || inc.ref.toLowerCase().includes(q) || inc.type.toLowerCase().includes(q) || inc.barangay.toLowerCase().includes(q)) &&
            (!incidentTypeFilter || inc.type === incidentTypeFilter) &&
            (!incidentSeverityFilter || inc.severity === incidentSeverityFilter) &&
            (!incidentStatusFilter || inc.status === incidentStatusFilter) &&
            (!incidentBarangayFilter || inc.barangay === incidentBarangayFilter) &&
            (!incidentSourceFilter || (incidentSourceFilter === 'resident') === (inc.source === 'Citizen Portal'))
        );
    },
}" x-init="loadIncidentsList()" class="space-y-3">

{{-- ── Stats + actions (Sakuna Redesign, 2026-11-02) ───────────────────────────────────── --}}
<div class="sk-pagebar">
    <div class="sk-stats">
        <div class="sk-stat"><span class="sk-slab"><span class="sk-dot sk-t-navy"></span>Total</span><span class="sk-sval tabular-nums" x-text="incidentsList.length"></span></div>
        <div class="sk-stat"><span class="sk-slab"><span class="sk-dot sk-t-red"></span>Critical</span><span class="sk-sval sk-t-red tabular-nums" x-text="incidentsList.filter(i => i.severity === 'Critical').length"></span></div>
        <div class="sk-stat"><span class="sk-slab"><span class="sk-dot sk-t-orange"></span>Active</span><span class="sk-sval tabular-nums" x-text="incidentsList.filter(i => !['Completed','Archived'].includes(i.status)).length"></span></div>
        <div class="sk-stat"><span class="sk-slab"><span class="sk-dot sk-t-green"></span>Resolved</span><span class="sk-sval tabular-nums" x-text="incidentsList.filter(i => i.status === 'Completed').length"></span></div>
    </div>
    <div class="sk-actions">
        <button type="button" class="sk-iconbtn sk-iconbtn-bare" aria-label="About Incident Management: report, track, validate, and coordinate response to all active incidents." title="Incident Management: report, track, validate, and coordinate response to all active incidents.">
            <i data-lucide="info"></i>
        </button>
        <div class="sk-btngrp" role="group" aria-label="Export">
            <button type="button" @click="exportCSV(filteredIncidents, 'incidents-export.csv')" class="sk-btn">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button type="button" @click="exportPDF(filteredIncidents, 'incidents-export.csv')" class="sk-btn">
                Export PDF
            </button>
        </div>
        <button type="button" x-show="can('sakuna_incidents', 'create')" @click="openCreateIncident()" class="sk-btn sk-btn-p">
            <i data-lucide="plus"></i> New Incident
        </button>
    </div>
</div>

<div x-show="incidentsLoading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-64 opacity-80" /></div>
<div x-show="incidentsFailed" x-cloak><x-ui.error-state messageExpr="'Could not load incidents.'" retryExpr="loadIncidentsList()" :retryable="true" /></div>

<section x-show="!incidentsLoading && !incidentsFailed" x-cloak class="sk-card overflow-hidden">

{{-- ── Filter Bar ───────────────────────────────────────────────────────────────────────── --}}
<div class="sk-toolbar">
    <div class="sk-search">
        <i data-lucide="search"></i>
        <input aria-label="Search by ID, type, or barangay" type="search" x-model="incidentSearch"
            placeholder="Search by ID, type, or barangay…">
    </div>
    <select aria-label="Incident type filter" x-model="incidentTypeFilter" class="sk-sel">
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
    </select>
    <select aria-label="Incident barangay filter" x-model="incidentBarangayFilter" class="sk-sel">
        <option value="">All barangays</option>
        @foreach($barangays as $brgy)
            <option>{{ $brgy }}</option>
        @endforeach
    </select>
    {{-- Reports residents sent from their Sakuna page, apart from the ones staff entered (2026-10-17). --}}
    <select aria-label="Incident source filter" x-model="incidentSourceFilter" class="sk-sel">
        <option value="">All sources</option>
        <option value="resident">Reported by residents</option>
        <option value="staff">Entered by staff</option>
    </select>
    <button type="button" @click="incidentSearch=''; incidentTypeFilter=''; incidentSeverityFilter=''; incidentStatusFilter=''; incidentBarangayFilter=''; incidentSourceFilter='';"
        class="sk-clear">Clear</button>
    <span class="sk-spacer"></span>
    <span class="sk-csub" x-text="filteredIncidents.length + (filteredIncidents.length === 1 ? ' incident' : ' incidents')"></span>
</div>

{{-- ── Incident Table ───────────────────────────────────────────────────────────────────── --}}
<div class="sk-scroll" tabindex="0" role="region" aria-label="Incidents table (scrolls sideways)">
    <table class="sk-tbl sk-tbl-tight">
        <thead>
            <tr>
                <th>Ref</th>
                <th>Type</th>
                <th>Reported</th>
                <th>Barangay / sitio</th>
                <th>Severity</th>
                <th>Assigned to</th>
                <th>Status</th>
                <th>Elapsed</th>
                <th class="sk-r">Actions</th>
            </tr>
        </thead>
        <tbody>
            <template x-for="inc in filteredIncidents" :key="inc.ref">
                <tr>
                    <td class="whitespace-nowrap"><span class="sk-mono" x-text="inc.ref"></span></td>
                    <td class="whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <i :data-lucide="incidentTypeIcon(inc.type)" class="w-3.5 h-3.5 text-[#1D4ED8] flex-shrink-0"></i>
                            <span class="sk-b" x-text="inc.type"></span>
                            <span x-show="inc.source === 'Citizen Portal'" class="sk-pill sk-p-blue ml-1" style="font-weight: 500;">Resident</span>
                        </div>
                    </td>
                    <td class="whitespace-nowrap">
                        <span x-text="inc.reported ? new Date(inc.reported).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '—'"></span>
                        <span class="sk-muted" x-text="inc.reported ? new Date(inc.reported).toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit'}) : ''"></span>
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
                    <td class="whitespace-nowrap">
                        <span x-text="inc.assigned_name || '—'" x-show="inc.assigned_name"></span>
                        <span class="italic text-[#B45309]" x-show="!inc.assigned_name">Unassigned</span>
                    </td>
                    <td class="whitespace-nowrap"><span :class="statusBadge(inc.status)" class="sk-pill" x-text="incidentStatusLabel(inc.status)" :title="'Status on record: ' + inc.status"></span></td>
                    <td class="whitespace-nowrap sk-muted" x-text="elapsed(inc.reported)"></td>
                    <td class="whitespace-nowrap sk-r">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <button type="button" x-show="inc.available_verbs.includes('validate')" @click="actIncidentVerb(inc, 'validate')" class="sk-btn sk-btn-sm sk-btn-ok">
                                <i data-lucide="check"></i> Validate
                            </button>
                            <button type="button" @click="openIncidentDetail(inc)" class="sk-btn sk-btn-sm sk-btn-soft">
                                <i data-lucide="eye"></i> View
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
            <tr x-show="filteredIncidents.length === 0">
                <td colspan="9">
                    <div class="sk-empty flex-col">
                        <i data-lucide="search-x"></i>
                        <span><b class="text-slate-700">No incidents match your filters.</b></span>
                        <button type="button" @click="incidentSearch=''; incidentTypeFilter=''; incidentSeverityFilter=''; incidentStatusFilter=''; incidentBarangayFilter=''; incidentSourceFilter='';"
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
                    <h3 class="text-base font-semibold text-ink-body">Report New Incident</h3>
                    <p class="text-xs text-ink-muted">The reporting officer is recorded automatically from your session.</p>
                </div>
            </div>
            <button aria-label="Close" @click="showCreateIncident = false" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100 text-ink-muted transition-colors">
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
            <button @click="showCreateIncident = false" class="text-sm text-slate-600 bg-white border border-slate-200 hover:border-slate-300 px-4 py-2 rounded-2xl transition-colors">Cancel</button>
            <button :disabled="incidentSaving || !incidentForm.title || !incidentForm.type || !incidentForm.barangay || !incidentForm.severity" @click="submitIncident()"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 px-5 py-2 rounded-2xl transition-colors ml-auto disabled:opacity-50">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span x-text="incidentSaving ? 'Reporting…' : 'Submit Incident'"></span>
            </button>
        </div>
    </div>
</div>{{-- /Report New Incident Modal --}}

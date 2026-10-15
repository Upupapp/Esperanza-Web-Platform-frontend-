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
            (!incidentBarangayFilter || inc.barangay === incidentBarangayFilter)
        );
    },
}" x-init="loadIncidentsList()" class="space-y-5">

{{-- ── Header ──────────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-start justify-between gap-3">
    <div>
        <h2 class="text-lg font-semibold text-ink-body">Incident Management</h2>
        <p class="text-sm text-ink-muted mt-0.5">Report, track, validate, and coordinate response to all active incidents.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="exportCSV(filteredIncidents, 'incidents-export.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 surface-glass-sm rounded-2xl px-3.5 py-2 hover:bg-white/90 transition-colors">
            <i data-lucide="download" class="w-4 h-4 text-ink-muted"></i> Export CSV
        </button>
        <button @click="exportPDF(filteredIncidents, 'incidents-export.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 surface-glass-sm rounded-2xl px-3.5 py-2 hover:bg-white/90 transition-colors">
            <i data-lucide="file-down" class="w-4 h-4 text-ink-muted"></i> Export PDF
        </button>
        <button x-show="can('sakuna_incidents', 'create')" @click="openCreateIncident()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-3 py-2 shadow-card transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> New Incident
        </button>
    </div>
</div>

<div x-show="incidentsLoading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-64 opacity-80" /></div>
<div x-show="incidentsFailed" x-cloak><x-ui.error-state messageExpr="'Could not load incidents.'" retryExpr="loadIncidentsList()" :retryable="true" /></div>

<div x-show="!incidentsLoading && !incidentsFailed" x-cloak class="space-y-5">

{{-- ── Stats Summary ────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap gap-2">
    <x-ui.pill color="slate"><i data-lucide="list" class="w-3.5 h-3.5"></i> <span x-text="'Total: ' + incidentsList.length"></span></x-ui.pill>
    <x-ui.pill color="rose"><i data-lucide="flame" class="w-3.5 h-3.5"></i> <span x-text="'Critical: ' + incidentsList.filter(i => i.severity === 'Critical').length"></span></x-ui.pill>
    <x-ui.pill color="orange"><i data-lucide="truck" class="w-3.5 h-3.5"></i> <span x-text="'Active: ' + incidentsList.filter(i => !['Completed','Archived'].includes(i.status)).length"></span></x-ui.pill>
    <x-ui.pill color="emerald"><i data-lucide="circle-check" class="w-3.5 h-3.5"></i> <span x-text="'Resolved: ' + incidentsList.filter(i => i.status === 'Completed').length"></span></x-ui.pill>
</div>

{{-- ── Filter Bar ───────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap gap-2 items-center">
    <div class="relative">
        <i data-lucide="search" class="w-4 h-4 text-ink-muted absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        <input aria-label="Search by ID, type, or barangay" type="text" x-model="incidentSearch"
            placeholder="Search by ID, type, or barangay..."
            class="pl-9 pr-3 py-2 text-sm rounded-2xl focus:outline-none w-64 transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
    </div>
    <select aria-label="Incident type filter" x-model="incidentTypeFilter"
        class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
        <option value="">All Types</option>
        <option>Flood</option><option>Landslide</option><option>Fire</option><option>Earthquake</option>
        <option>Vehicular Accident</option><option>Medical Emergency</option><option>Missing Person</option>
        <option>Stranded Resident</option><option>Fallen Tree</option><option>Downed Power Line</option>
        <option>Road Obstruction</option><option>Damaged Bridge</option><option>Rescue Request</option>
        <option>Drowning or Water Rescue</option><option>Agricultural Damage</option><option>Structural Damage</option>
        <option>Water Interruption</option><option>Power Interruption</option><option>Public Health Emergency</option>
        <option>Other</option>
    </select>
    <select aria-label="Incident severity filter" x-model="incidentSeverityFilter"
        class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
        <option value="">All Severities</option>
        <option>Critical</option><option>High</option><option>Medium</option><option>Low</option>
    </select>
    <select aria-label="Incident status filter" x-model="incidentStatusFilter"
        class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
        <option value="">All Statuses</option>
        <option value="Submitted">Reported</option>
        <option value="Under Verification">Validated</option>
        <option value="Assigned">Assigned</option>
        <option value="Processing">Dispatched</option>
        <option value="Completed">Resolved</option>
        <option value="Archived">Closed</option>
    </select>
    <select aria-label="Incident barangay filter" x-model="incidentBarangayFilter"
        class="text-sm rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
        <option value="">All Barangays</option>
        @foreach($barangays as $brgy)
            <option>{{ $brgy }}</option>
        @endforeach
    </select>
    <button @click="incidentSearch=''; incidentTypeFilter=''; incidentSeverityFilter=''; incidentStatusFilter=''; incidentBarangayFilter='';"
        class="inline-flex items-center gap-1.5 text-sm text-ink-muted surface-glass-sm rounded-2xl px-3.5 py-2 hover:bg-white/90 transition-colors">
        <i data-lucide="x" class="w-3.5 h-3.5"></i> Clear
    </button>
</div>

{{-- ── Incident Table ───────────────────────────────────────────────────────────────────── --}}
<div class="surface-glass rounded-[26px] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-brand-600/[.08]">
            <thead>
                <tr class="bg-slate-50 text-left">
                    <th class="px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Ref</th>
                    <th class="px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Type</th>
                    <th class="px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Reported</th>
                    <th class="px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Barangay / Sitio</th>
                    <th class="px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Severity</th>
                    <th class="px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Assigned To</th>
                    <th class="px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Status</th>
                    <th class="px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Elapsed</th>
                    <th class="px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-600/[.08]">
                <template x-for="inc in filteredIncidents" :key="inc.ref">
                    <tr class="hover:bg-white/60 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap"><span class="font-mono text-xs text-ink-muted" x-text="inc.ref"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="flex-shrink-0 w-7 h-7 rounded-lg bg-brand-50 flex items-center justify-center">
                                    <i :data-lucide="incidentTypeIcon(inc.type)" class="w-3.5 h-3.5 text-brand-600"></i>
                                </span>
                                <span class="text-sm font-medium text-slate-700" x-text="inc.type"></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-xs text-slate-600" x-text="inc.reported ? new Date(inc.reported).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '—'"></span><br>
                            <span class="text-xs text-ink-muted" x-text="inc.reported ? new Date(inc.reported).toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit'}) : ''"></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm font-medium text-slate-700" x-text="inc.barangay"></span><br>
                            <span class="text-xs text-ink-muted" x-text="inc.sitio || '—'"></span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap"><span :class="severityBadge(inc.severity)" class="px-2.5 py-0.5 rounded-full text-xs font-medium" x-text="inc.severity"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm text-slate-700" x-text="inc.assigned_name || '—'" x-show="inc.assigned_name"></span>
                            <span class="text-xs italic text-amber-600" x-show="!inc.assigned_name">Unassigned</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap"><span :class="statusBadge(inc.status)" x-text="incidentStatusLabel(inc.status)" :title="'Status on record: ' + inc.status"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs text-ink-muted" x-text="elapsed(inc.reported)"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button @click="openIncidentDetail(inc)"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 px-2.5 py-1.5 rounded-xl transition-colors">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                                </button>
                                <button x-show="inc.available_verbs.includes('validate')" @click="actIncidentVerb(inc, 'validate')"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1.5 rounded-xl transition-colors">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i> Validate
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredIncidents.length === 0">
                    <td colspan="9" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mb-1">
                                <i data-lucide="search-x" class="w-5 h-5 text-ink-muted"></i>
                            </span>
                            <p class="text-sm text-ink-muted font-medium">No incidents match your filters.</p>
                            <button @click="incidentSearch=''; incidentTypeFilter=''; incidentSeverityFilter=''; incidentStatusFilter=''; incidentBarangayFilter='';"
                                class="text-sm text-brand-600 hover:underline mt-1">Clear filters</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

</div>{{-- /loaded content --}}

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

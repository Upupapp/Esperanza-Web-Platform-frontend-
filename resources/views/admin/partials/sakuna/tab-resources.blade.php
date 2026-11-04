{{--
    tab-resources.blade.php
    Response Resource & Deployment Management
    Included by sakuna.blade.php — NO x-data at root.
    Parent Alpine state: resourceSearch/resourceCategoryFilter, resourcesList/
    resourcesLoading/resourcesFailed, resourceIncidentsOpen, selectedResource/
    showResourceDetail, showDeployModal/deployForm/deploySaving, showCreateResource/
    resourceFormMode/resourceForm/resourceSaving. Helpers: can(submodule, action),
    exportCSV(rows, filename), toast(msg, variant), loadResources(), resourceCategories(),
    openCreateResource(), openEditResource(r), openResourceDetail(row), openDeploy(r),
    submitDeploy(verb), saveResource().

    GET/POST /sakuna/resources, GET/PUT /sakuna/resources/{ref} (create/edit/detail
    added in commit 26b66d6 -- sakuna_resources' own RBAC always declared create/edit
    but nothing implemented either, the same gap 66959ee closed for sakuna_centers),
    POST /sakuna/resources/{ref}/deploy|return.

    This is a BULK inventory model (quantity/deployed/available per resource row),
    not a per-unit asset with a lifecycle status. SakunaResource has no condition,
    fuel level, assigned personnel, office/station, serial/plate number or per-asset
    inspection history -- all of that, plus the "Team Readiness Board" of four named
    rescue teams and the fabricated per-status pill counts, was invented with nothing
    behind it. Deployment History is real now: sakuna_resource_movements has existed
    since the table was created but was never read back before GET /sakuna/resources/{ref}.
    Deploy only ever targets an incident (or nothing, for general standby) -- there is
    no evacuation-center deployment target in the schema, so the mock's "Assign To:
    Incident / Evacuation Center" toggle is dropped along with the fabricated priority/
    personnel/instructions/ETA fields the deploy form used to collect.
--}}

<div x-data="{
    get filteredResources() {
        const q = resourceSearch.toLowerCase();
        return resourcesList.filter(r =>
            $store.session.inScope(r.barangay) &&
            (!q || r.name.toLowerCase().includes(q) || r.ref.toLowerCase().includes(q) || r.category.toLowerCase().includes(q)) &&
            (!resourceCategoryFilter || r.category === resourceCategoryFilter)
        );
    },
}" x-init="loadResources()" class="space-y-3">

{{-- Sakuna Redesign (2026-11-02): stat strip and actions, then the list card. --}}
<div class="sk-pagebar">
    <div class="sk-stats" x-show="!resourcesFailed" :class="resourcesLoading ? 'animate-pulse opacity-60' : ''">
        <div class="sk-stat">
            <span class="sk-slab">Resource types</span>
            <span class="sk-sval tabular-nums" x-text="fig(resourcesList.length, resourcesLoading || resourcesFailed)"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-amber" x-show="resourcesList.some(r => r.quantity > 0 && r.available === 0)"></span>None left to deploy</span>
            <span class="sk-sval tabular-nums" x-text="fig(resourcesList.filter(r => r.quantity > 0 && r.available === 0).length, resourcesLoading || resourcesFailed)"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab">Units deployed</span>
            <span class="sk-sval tabular-nums" x-text="fig(resourcesList.reduce((s,r) => s + r.deployed, 0), resourcesLoading || resourcesFailed)"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab">Units available</span>
            <span class="sk-sval tabular-nums" x-text="fig(resourcesList.reduce((s,r) => s + r.available, 0), resourcesLoading || resourcesFailed)"></span>
        </div>
    </div>
    <div class="sk-actions">
        <button type="button" data-info class="sk-iconbtn sk-iconbtn-bare" aria-label="About response assets and resources" title="Response assets and resources: track inventory and deploy MDRRMO response resources to active incidents.">
            <i data-lucide="info"></i>
        </button>
        <div class="sk-btngrp" role="group" aria-label="Export">
            <button type="button" class="sk-btn disabled:opacity-50" :disabled="resourcesLoading || resourcesFailed" @click="exportCSV(filteredResources, 'resources-export.csv')">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button type="button" class="sk-btn disabled:opacity-50" :disabled="resourcesLoading || resourcesFailed" @click="exportPDF(filteredResources, 'resources-export.csv')">
                <i data-lucide="file-text"></i> Export PDF
            </button>
        </div>
        <button type="button" x-show="can('sakuna_resources', 'create')" @click="openCreateResource()" :disabled="resourcesLoading || resourcesFailed || onLastCopy" :title="resourcesFailed || onLastCopy ? 'Waits for the connection' : ''" class="sk-btn sk-btn-p disabled:opacity-50">
            <i data-lucide="plus"></i> Add resource
        </button>
    </div>
</div>

<div x-show="resourcesLoading" class="sk-card h-72 animate-pulse"></div>
<div x-show="resourcesFailed" x-cloak><x-ui.error-state tone="neutral" icon="cloud-off" title="Not loaded" messageExpr="'Could not load resources. ' + offlineWhy" retryExpr="loadResources()" :retryable="true" /></div>

<div x-show="!resourcesLoading && !resourcesFailed" x-cloak class="space-y-3">

<section class="sk-card overflow-hidden">
    {{-- ── Toolbar ──────────────────────────────────────────────────────────────────────── --}}
    <h2 class="sr-only">Resources</h2>
    <div class="sk-toolbar">
        <div class="sk-search" style="max-width: 340px;">
            <label for="resource-search" class="sr-only">Search by name, ref or category</label>
            <i data-lucide="search"></i>
            <input id="resource-search" aria-label="Search by name, ref or category" type="search" x-model="resourceSearch" placeholder="Search by name, ref or category…">
        </div>
        <select aria-label="Resource category filter" x-model="resourceCategoryFilter" class="sk-sel">
            <option value="">All categories</option>
            <template x-for="cat in resourceCategories()" :key="cat"><option :value="cat" x-text="cat"></option></template>
        </select>
        <button type="button" class="sk-clear" @click="resourceSearch = ''; resourceCategoryFilter = ''">Clear</button>
        <span class="sk-spacer"></span>
        <span class="sk-csub">Showing <strong x-text="filteredResources.length"></strong> of <strong x-text="resourcesList.length"></strong> resources</span>
    </div>

    {{-- ── Resources Table ──────────────────────────────────────────────────────────────── --}}
    <div class="sk-scroll" tabindex="0" role="region" aria-label="Resources table (scrolls sideways)">
        <table class="sk-tbl sk-tbl-act">
            <thead>
                <tr>
                    <th>Resource</th>
                    <th>Category · barangay</th>
                    <th>Available</th>
                    <th>Readiness</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="r in filteredResources" :key="r.ref">
                    <tr class="cursor-pointer group" @click="openResourceDetail(r)" tabindex="0" @keydown.enter.prevent="openResourceDetail(r)">
                        <td><div class="sk-b group-hover:text-[#1D4ED8] transition-colors" x-text="r.name"></div><div class="sk-mono" x-text="r.ref"></div></td>
                        <td><span class="sk-tag" x-text="r.category"></span><div class="sk-sub" x-text="r.barangay || 'Municipal stock'"></div></td>
                        <td class="whitespace-nowrap" :aria-label="r.available + ' of ' + r.quantity + ' available, ' + r.deployed + ' deployed'">
                            <div class="tabular-nums"><span class="font-semibold text-ink" x-text="r.available"></span> <span class="sk-muted" x-text="'of ' + r.quantity + (r.unit ? ' ' + r.unit : '')"></span></div>
                            <div class="w-24 h-[5px] rounded-full bg-[#E9EEF6] overflow-hidden mt-1" aria-hidden="true"><div class="h-full rounded-full bg-emerald-600" :style="'width:' + (r.quantity ? Math.round(r.available / r.quantity * 100) : 0) + '%'"></div></div>
                            <div class="sk-sub" x-show="r.deployed > 0" x-text="r.deployed + ' deployed'"></div>
                        </td>
                        <td class="whitespace-nowrap">
                            <span x-show="r.readiness" class="sk-pill sk-p-blue" x-text="r.readiness"></span>
                            <span x-show="!r.readiness" class="sk-muted">—</span>
                        </td>
                        <td class="whitespace-nowrap" @click.stop>
                            <div class="inline-flex items-center gap-1">
                                <button type="button" x-show="r.available > 0 || r.deployed > 0" @click="openDeploy(r)" class="sk-btn sk-btn-sm" :aria-label="(r.available > 0 ? 'Deploy or return ' : 'Record units coming back for ') + r.ref" :title="r.available > 0 ? 'Send units out, or record units coming back' : 'Every unit is out: record units coming back'">
                                    <span x-show="r.available > 0" class="inline-flex"><i data-lucide="send"></i></span><span x-show="!(r.available > 0)" class="inline-flex"><i data-lucide="undo-2"></i></span>
                                    <span x-text="r.available > 0 ? 'Deploy' : 'Return'"></span>
                                </button>
                                <button type="button" @click="openResourceDetail(r)" class="sk-btn sk-btn-sm sk-btn-soft" :aria-label="'View ' + r.ref">
                                    <i data-lucide="eye"></i> View
                                </button>
                                <button type="button" x-show="can('sakuna_resources','edit')" @click="openEditResource(r)" class="sk-btn sk-btn-sm" :aria-label="'Edit ' + r.ref">
                                    <i data-lucide="pencil"></i> Edit
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredResources.length === 0">
                    <td colspan="5">
                        <div class="sk-empty" style="padding: 36px 16px;">
                            <i data-lucide="package"></i>
                            <span>No resources match your filters.</span>
                            <button type="button" @click="resourceSearch = ''; resourceCategoryFilter = ''" class="sk-clear">Clear filters</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

</div>{{-- /loaded content --}}

{{-- ── Deploy / Return Modal ───────────────────────────────────────────────────────────── --}}
<div x-show="showDeployModal" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showDeployModal = false"
        x-trap="showDeployModal"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showDeployModal = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-md">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center"><i data-lucide="send" class="w-5 h-5 text-brand-600"></i></div>
                <div>
                    <h3 class="font-semibold text-ink-body">Deploy or Return</h3>
                    <p class="text-xs text-ink-muted" x-text="selectedResource?.name ?? '—'"></p>
                </div>
            </div>
            <button aria-label="Close window" @click="showDeployModal = false" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between text-xs bg-slate-50 rounded-xl p-3">
                <span class="text-ink-muted">Available: <strong class="text-emerald-700" x-text="selectedResource?.available"></strong></span>
                <span class="text-ink-muted">Deployed: <strong class="text-orange-700" x-text="selectedResource?.deployed"></strong></span>
            </div>

            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="deploy-quantity">Quantity</label>
                <input id="deploy-quantity" type="number" min="1" x-model="deployForm.quantity" placeholder="e.g. 2"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>

            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="deploy-incident">Incident (optional — only for deploy)</label>
                <select id="deploy-incident" x-model="deployForm.incident_id" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">— General standby, not tied to an incident —</option>
                    <template x-for="inc in resourceIncidentsOpen" :key="inc.id">
                        <option :value="inc.id" x-text="inc.ref + ' — ' + inc.type + ', ' + inc.barangay + ' (' + inc.status + ')'"></option>
                    </template>
                </select>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex justify-end gap-2">
            <button :disabled="deploySaving || !deployForm.quantity || Number(deployForm.quantity) > (selectedResource?.deployed ?? 0)" @click="submitDeploy('return')"
                class="flex-1 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-white/60 transition-colors disabled:opacity-40">
                <i data-lucide="undo-2" class="w-4 h-4 inline mr-1.5"></i> Return
            </button>
            <button :disabled="deploySaving || !deployForm.quantity || Number(deployForm.quantity) > (selectedResource?.available ?? 0)" @click="submitDeploy('deploy')"
                class="flex-1 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors disabled:opacity-40">
                <i data-lucide="send" class="w-4 h-4 inline mr-1.5"></i> Deploy
            </button>
        </div>
    </div>
</div>

{{-- ── Resource Detail Modal ───────────────────────────────────────────────────────────── --}}
<div x-show="showResourceDetail" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showResourceDetail = false"
        x-trap="showResourceDetail"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showResourceDetail = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-2xl my-auto">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center"><i data-lucide="truck" class="w-5 h-5 text-brand-600"></i></div>
                <div>
                    <h3 class="font-semibold text-ink-body" x-text="selectedResource?.name ?? '—'"></h3>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="font-mono text-xs text-ink-muted" x-text="selectedResource?.ref ?? ''"></span>
                        <span class="text-ink-faint">·</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-medium" x-text="selectedResource?.category"></span>
                    </div>
                </div>
            </div>
            <button aria-label="Close window" @click="showResourceDetail = false" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-5">

            <div>
                <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5"></i> Details</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Barangay</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedResource?.barangay || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Readiness</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedResource?.readiness || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Unit</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedResource?.unit || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-ink-muted">Total Quantity</p><p class="text-sm font-medium text-ink-body mt-0.5" x-text="selectedResource?.quantity"></p></div>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="boxes" class="w-3.5 h-3.5"></i> Inventory</h4>
                <div class="bg-slate-50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2 text-xs text-ink-muted">
                        <span>Deployed: <strong class="text-orange-700" x-text="selectedResource?.deployed"></strong></span>
                        <span>Available: <strong class="text-emerald-700" x-text="selectedResource?.available"></strong></span>
                    </div>
                    <div class="h-2.5 bg-emerald-200 rounded-full overflow-hidden">
                        <div class="h-full bg-orange-400" :style="'width: ' + (selectedResource?.quantity ? (selectedResource.deployed / selectedResource.quantity * 100) : 0) + '%'"></div>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-ink-muted uppercase tracking-[.07em] mb-3 flex items-center gap-1.5"><i data-lucide="history" class="w-3.5 h-3.5"></i> Deployment History</h4>
                <div class="space-y-0">
                    <template x-for="(mv, idx) in (selectedResource?.movements || [])" :key="idx">
                        <div class="flex items-start gap-3 py-2.5" :class="idx < (selectedResource.movements.length - 1) ? 'border-b border-slate-50' : ''">
                            <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center flex-shrink-0">
                                <i :data-lucide="mv.verb === 'deploy' ? 'send' : 'undo-2'" class="w-3.5 h-3.5 text-ink-muted"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-slate-700 capitalize" x-text="mv.verb + ' — ' + mv.quantity + (selectedResource?.unit ? ' ' + selectedResource.unit : ' unit(s)')"></p>
                                <p class="text-xs text-ink-muted" x-text="[mv.incident_ref ? mv.incident_ref + ' (' + mv.incident_title + ')' : null, mv.actor_name].filter(Boolean).join(' · ')"></p>
                            </div>
                            <span class="text-xs text-ink-muted whitespace-nowrap flex-shrink-0" x-text="mv.at ? new Date(mv.at).toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit'}) : ''"></span>
                        </div>
                    </template>
                    <p x-show="!(selectedResource?.movements || []).length" class="text-xs text-ink-muted py-2">No deployments or returns recorded yet.</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex flex-wrap gap-2 items-center">
            <button x-show="can('sakuna_resources','edit')" @click="showResourceDetail = false; openEditResource(selectedResource)"
                class="sk-btn">
                <i data-lucide="pencil" class="w-4 h-4"></i> Edit
            </button>
            <button x-show="selectedResource?.available > 0 || selectedResource?.deployed > 0" @click="showResourceDetail = false; openDeploy(selectedResource)"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-3 py-2 transition-colors ml-auto">
                <i data-lucide="send" class="w-4 h-4"></i> Deploy / return
            </button>
        </div>
    </div>
</div>

{{-- ── Add / Edit Resource Modal ───────────────────────────────────────────────────────── --}}
<div x-show="showCreateResource" x-cloak
    x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showCreateResource = false"
        x-trap="showCreateResource"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCreateResource = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-xl">

        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
            <h3 class="font-semibold text-ink-body" x-text="resourceFormMode === 'create' ? 'Add resource to inventory' : 'Edit resource'"></h3>
            <button aria-label="Close window" @click="showCreateResource = false" class="sk-mclose"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="resource-name">Resource Name <span class="text-rose-500">*</span></label>
                <input id="resource-name" type="text" x-model="resourceForm.name" placeholder="e.g. MDRRMO Rescue Boat C"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="resource-category">Category <span class="text-rose-500">*</span></label>
                <input id="resource-category" type="text" x-model="resourceForm.category" list="resource-category-suggestions" placeholder="e.g. Boats"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                <datalist id="resource-category-suggestions">
                    <template x-for="cat in resourceCategories()" :key="cat"><option :value="cat"></option></template>
                </datalist>
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="resource-quantity">Quantity <span class="text-rose-500">*</span></label>
                <input id="resource-quantity" type="number" min="0" x-model="resourceForm.quantity" placeholder="e.g. 2"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="resource-unit">Unit</label>
                <input id="resource-unit" type="text" x-model="resourceForm.unit" placeholder="e.g. units, tanks, boxes"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="resource-readiness">Readiness</label>
                <input id="resource-readiness" type="text" x-model="resourceForm.readiness" placeholder="e.g. Ready"
                    class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <div class="col-span-2">
                <label class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-1.5 block" for="resource-barangay">Barangay (optional — leave blank for a municipal-wide asset)</label>
                <select id="resource-barangay" x-model="resourceForm.barangay" class="w-full px-3 py-2.5 text-sm rounded-2xl focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="">— None —</option>
                    @foreach($barangays as $brgy)
                        <option>{{ $brgy }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex justify-end gap-2">
            <button @click="showCreateResource = false"
                class="sk-btn">
                Cancel
            </button>
            <button :disabled="resourceSaving || !resourceForm.name || !resourceForm.category || resourceForm.quantity === ''" @click="saveResource()"
                class="sk-btn sk-btn-p">
                <span x-text="resourceSaving ? 'Saving…' : (resourceFormMode === 'create' ? 'Add resource' : 'Save changes')"></span>
            </button>
        </div>
    </div>
</div>

</div>{{-- end x-data wrapper --}}

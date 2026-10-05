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
}" x-init="loadResources()" class="space-y-4">

{{-- ── Header ─────────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Response Assets &amp; Resources</h2>
        <p class="text-sm text-slate-500 mt-0.5">Track inventory and deploy MDRRMO response resources to active incidents.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="exportCSV(filteredResources, 'resources-export.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-card hover:border-slate-300 transition-colors">
            <i data-lucide="download" class="w-4 h-4 text-slate-500"></i> Export
        </button>
        <button x-show="can('sakuna_resources', 'create')" @click="openCreateResource()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-3 py-2 shadow-card transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Resource
        </button>
    </div>
</div>

<div x-show="resourcesLoading" class="space-y-2"><x-ui.skeleton class="h-16" /><x-ui.skeleton class="h-64 opacity-80" /></div>
<div x-show="resourcesFailed" x-cloak><x-ui.error-state messageExpr="'Could not load resources.'" retryExpr="loadResources()" :retryable="true" /></div>

<div x-show="!resourcesLoading && !resourcesFailed" x-cloak class="space-y-4">

{{-- ── Summary Cards ───────────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Resource Types</p>
        <p class="text-2xl font-bold text-slate-800 mt-1" x-text="resourcesList.length"></p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Total Units</p>
        <p class="text-2xl font-bold text-slate-800 mt-1" x-text="resourcesList.reduce((s,r) => s + r.quantity, 0)"></p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Deployed</p>
        <p class="text-2xl font-bold text-orange-600 mt-1" x-text="resourcesList.reduce((s,r) => s + r.deployed, 0)"></p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Available</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1" x-text="resourcesList.reduce((s,r) => s + r.available, 0)"></p>
    </div>
</div>

{{-- ── Filter Bar ──────────────────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-48">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
            <input aria-label="Search by name, ref or category" type="text" x-model="resourceSearch" placeholder="Search by name, ref or category…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
        </div>
        <select aria-label="Resource category filter" x-model="resourceCategoryFilter"
            class="text-sm border border-slate-200 rounded-xl px-3 py-2 bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
            <option value="">All Categories</option>
            <template x-for="cat in resourceCategories()" :key="cat"><option :value="cat" x-text="cat"></option></template>
        </select>
        <button @click="resourceSearch = ''; resourceCategoryFilter = ''"
            class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors">
            Clear filters
        </button>
    </div>
</div>

{{-- ── Resources Table ─────────────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap">Ref</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap">Resource Name</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap">Category</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap">Barangay</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap">Quantity</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap">Deployed</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap">Available</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap">Readiness</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="r in filteredResources" :key="r.ref">
                    <tr class="hover:bg-slate-50/60 transition-colors cursor-pointer group" @click="openResourceDetail(r)" tabindex="0" @keydown.enter.prevent="openResourceDetail(r)">
                        <td class="px-4 py-3 whitespace-nowrap"><span class="font-mono text-xs text-slate-500" x-text="r.ref"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap"><span class="font-medium text-slate-800 group-hover:text-brand-600 transition-colors" x-text="r.name"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-xs font-medium" x-text="r.category"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600" x-text="r.barangay || '—'"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-slate-700" x-text="r.quantity + (r.unit ? ' ' + r.unit : '')"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-right" :class="r.deployed > 0 ? 'text-orange-600 font-medium' : 'text-slate-500'" x-text="r.deployed"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-right" :class="r.available > 0 ? 'text-emerald-600 font-medium' : 'text-slate-500'" x-text="r.available"></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span x-show="r.readiness" class="bg-sky-50 text-sky-700 border border-sky-200 px-2 py-0.5 rounded-full text-xs font-medium" x-text="r.readiness"></span>
                            <span x-show="!r.readiness" class="text-slate-300 text-xs">—</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right" @click.stop>
                            <div class="flex items-center justify-end gap-1">
                                <button @click="openResourceDetail(r)" class="p-1.5 rounded-lg text-slate-500 hover:text-brand-600 hover:bg-brand-50 transition-colors" title="View Details">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                                <button x-show="can('sakuna_resources','edit')" @click="openEditResource(r)" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Edit">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button x-show="r.available > 0" @click="openDeploy(r)" class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Deploy / Return">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                </button>
                                <button x-show="r.available === 0 && r.deployed === 0" disabled title="Nothing to deploy"
                                    class="p-1.5 rounded-lg text-slate-300 cursor-not-allowed">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredResources.length === 0">
                    <td colspan="9" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3 text-slate-500">
                            <i data-lucide="package" class="w-10 h-10 opacity-30"></i>
                            <p class="text-sm font-medium text-slate-500">No resources match your filters.</p>
                            <button @click="resourceSearch = ''; resourceCategoryFilter = ''" class="text-sm text-brand-600 hover:underline">Clear Filters</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/40">
        <p class="text-xs text-slate-500">Showing <strong x-text="filteredResources.length"></strong> of <strong x-text="resourcesList.length"></strong> resources</p>
    </div>
</div>

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
        class="bg-white rounded-2xl shadow-float w-full max-w-md">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center"><i data-lucide="send" class="w-5 h-5 text-brand-600"></i></div>
                <div>
                    <h3 class="font-semibold text-slate-800">Deploy or Return</h3>
                    <p class="text-xs text-slate-500" x-text="selectedResource?.name ?? '—'"></p>
                </div>
            </div>
            <button aria-label="Close" @click="showDeployModal = false" class="p-2 rounded-xl text-slate-500 hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between text-xs bg-slate-50 rounded-xl p-3">
                <span class="text-slate-500">Available: <strong class="text-emerald-600" x-text="selectedResource?.available"></strong></span>
                <span class="text-slate-500">Deployed: <strong class="text-orange-600" x-text="selectedResource?.deployed"></strong></span>
            </div>

            <div>
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1.5 block" for="deploy-quantity">Quantity</label>
                <input id="deploy-quantity" type="number" min="1" x-model="deployForm.quantity" placeholder="e.g. 2"
                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
            </div>

            <div>
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1.5 block" for="deploy-incident">Incident (optional — only for deploy)</label>
                <select id="deploy-incident" x-model="deployForm.incident_id" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
                    <option value="">— General standby, not tied to an incident —</option>
                    <template x-for="inc in resourceIncidentsOpen" :key="inc.id">
                        <option :value="inc.id" x-text="inc.ref + ' — ' + inc.type + ', ' + inc.barangay + ' (' + inc.status + ')'"></option>
                    </template>
                </select>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl flex gap-3">
            <button :disabled="deploySaving || !deployForm.quantity || Number(deployForm.quantity) > (selectedResource?.deployed ?? 0)" @click="submitDeploy('return')"
                class="flex-1 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors disabled:opacity-40">
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
    class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-12 bg-navy-950/60 backdrop-blur-sm overflow-y-auto"
    @click.self="showResourceDetail = false"
        x-trap="showResourceDetail"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showResourceDetail = false">
    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-2xl shadow-float w-full max-w-2xl mb-8">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center"><i data-lucide="truck" class="w-5 h-5 text-brand-600"></i></div>
                <div>
                    <h3 class="font-semibold text-slate-800" x-text="selectedResource?.name ?? '—'"></h3>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="font-mono text-xs text-slate-500" x-text="selectedResource?.ref ?? ''"></span>
                        <span class="text-slate-300">·</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-medium" x-text="selectedResource?.category"></span>
                    </div>
                </div>
            </div>
            <button aria-label="Close" @click="showResourceDetail = false" class="p-2 rounded-xl text-slate-500 hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 space-y-5">

            <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5"></i> Details</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-slate-500">Barangay</p><p class="text-sm font-medium text-slate-800 mt-0.5" x-text="selectedResource?.barangay || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-slate-500">Readiness</p><p class="text-sm font-medium text-slate-800 mt-0.5" x-text="selectedResource?.readiness || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-slate-500">Unit</p><p class="text-sm font-medium text-slate-800 mt-0.5" x-text="selectedResource?.unit || '—'"></p></div>
                    <div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-slate-500">Total Quantity</p><p class="text-sm font-medium text-slate-800 mt-0.5" x-text="selectedResource?.quantity"></p></div>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="boxes" class="w-3.5 h-3.5"></i> Inventory</h4>
                <div class="bg-slate-50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2 text-xs text-slate-500">
                        <span>Deployed: <strong class="text-orange-600" x-text="selectedResource?.deployed"></strong></span>
                        <span>Available: <strong class="text-emerald-600" x-text="selectedResource?.available"></strong></span>
                    </div>
                    <div class="h-2.5 bg-emerald-200 rounded-full overflow-hidden">
                        <div class="h-full bg-orange-400" :style="'width: ' + (selectedResource?.quantity ? (selectedResource.deployed / selectedResource.quantity * 100) : 0) + '%'"></div>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3 flex items-center gap-1.5"><i data-lucide="history" class="w-3.5 h-3.5"></i> Deployment History</h4>
                <div class="space-y-0">
                    <template x-for="(mv, idx) in (selectedResource?.movements || [])" :key="idx">
                        <div class="flex items-start gap-3 py-2.5" :class="idx < (selectedResource.movements.length - 1) ? 'border-b border-slate-50' : ''">
                            <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center flex-shrink-0">
                                <i :data-lucide="mv.verb === 'deploy' ? 'send' : 'undo-2'" class="w-3.5 h-3.5 text-slate-500"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-slate-700 capitalize" x-text="mv.verb + ' — ' + mv.quantity + (selectedResource?.unit ? ' ' + selectedResource.unit : ' unit(s)')"></p>
                                <p class="text-xs text-slate-500" x-text="[mv.incident_ref ? mv.incident_ref + ' (' + mv.incident_title + ')' : null, mv.actor_name].filter(Boolean).join(' · ')"></p>
                            </div>
                            <span class="text-[11px] text-slate-500 whitespace-nowrap flex-shrink-0" x-text="mv.at ? new Date(mv.at).toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit'}) : ''"></span>
                        </div>
                    </template>
                    <p x-show="!(selectedResource?.movements || []).length" class="text-xs text-slate-500 py-2">No deployments or returns recorded yet.</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl flex flex-wrap gap-2 items-center">
            <button x-show="can('sakuna_resources','edit')" @click="showResourceDetail = false; openEditResource(selectedResource)"
                class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 hover:border-slate-300 transition-colors">
                <i data-lucide="pencil" class="w-4 h-4"></i> Edit
            </button>
            <button x-show="selectedResource?.available > 0 || selectedResource?.deployed > 0" @click="showResourceDetail = false; openDeploy(selectedResource)"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-3 py-2 transition-colors ml-auto">
                <i data-lucide="send" class="w-4 h-4"></i> Deploy / Return
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
        class="bg-white rounded-2xl shadow-float w-full max-w-xl">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800" x-text="resourceFormMode === 'create' ? 'Add Resource to Inventory' : 'Edit Resource'"></h3>
            <button aria-label="Close" @click="showCreateResource = false" class="p-2 rounded-xl text-slate-500 hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="p-6 grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1.5 block" for="resource-name">Resource Name <span class="text-rose-500">*</span></label>
                <input id="resource-name" type="text" x-model="resourceForm.name" placeholder="e.g. MDRRMO Rescue Boat C"
                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1.5 block" for="resource-category">Category <span class="text-rose-500">*</span></label>
                <input id="resource-category" type="text" x-model="resourceForm.category" list="resource-category-suggestions" placeholder="e.g. Boats"
                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
                <datalist id="resource-category-suggestions">
                    <template x-for="cat in resourceCategories()" :key="cat"><option :value="cat"></option></template>
                </datalist>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1.5 block" for="resource-quantity">Quantity <span class="text-rose-500">*</span></label>
                <input id="resource-quantity" type="number" min="0" x-model="resourceForm.quantity" placeholder="e.g. 2"
                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1.5 block" for="resource-unit">Unit</label>
                <input id="resource-unit" type="text" x-model="resourceForm.unit" placeholder="e.g. units, tanks, boxes"
                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1.5 block" for="resource-readiness">Readiness</label>
                <input id="resource-readiness" type="text" x-model="resourceForm.readiness" placeholder="e.g. Ready"
                    class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
            </div>
            <div class="col-span-2">
                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1.5 block" for="resource-barangay">Barangay (optional — leave blank for a municipal-wide asset)</label>
                <select id="resource-barangay" x-model="resourceForm.barangay" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none transition-colors">
                    <option value="">— None —</option>
                    @foreach($barangays as $brgy)
                        <option>{{ $brgy }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl flex gap-3">
            <button @click="showCreateResource = false"
                class="flex-1 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                Cancel
            </button>
            <button :disabled="resourceSaving || !resourceForm.name || !resourceForm.category || resourceForm.quantity === ''" @click="saveResource()"
                class="flex-1 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors disabled:opacity-50">
                <span x-text="resourceSaving ? 'Saving…' : (resourceFormMode === 'create' ? 'Add Resource' : 'Save Changes')"></span>
            </button>
        </div>
    </div>
</div>

</div>{{-- end x-data wrapper --}}

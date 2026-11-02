{{--
    tab-centers.blade.php
    Evacuation Center Management — SAKUNA Disaster Management Module
    Included by sakuna.blade.php — NO x-data at root.
    Parent Alpine state: centerSearch/centerStatusFilter, centersList/centersLoading/
    centersFailed, selectedCenter/showCenterDetail, showCreateCenter/centerForm/
    centerFormMode/centerSaving, showOpenCenterConfirm/showCloseCenterConfirm/
    pendingCenterAction, centerEvacuees. Helpers: statusBadge(st), occupancyBar(pct),
    toast(msg, variant), openCenterDetail(c), openCreateCenter(), openEditCenter(c),
    confirmOpenCenter(), confirmCloseCenter().

    GET/POST/PUT /sakuna/centers, POST /sakuna/centers/{ref}/{verb} (SakunaController;
    the create/edit endpoints were added in commit 66959ee -- neither existed before,
    despite sakuna_centers' own permission set declaring both).

    SakunaCenter has no lat/lng, no `type` column, no manager/contact fields and no
    personnel/room/inspection sub-model -- the mock's schematic-map pin, "Facility
    Type" picker, Manager/Contact display, the whole "Zone/Room Breakdown" table, the
    "Personnel" tab (3 named staff), "Structural Assessment" (safety rating, hazard
    tags), "History" tab (inspection log) and "Activity" tab (timeline) are all built
    around data that does not exist anywhere in the schema. The Occupants tab is real
    instead of fabricated demographics: it fetches this center's actual checked-in
    families from GET /sakuna/evacuees?center={ref}.
--}}

<div x-data="{
    centerBarangayFilter: '',
    closeCenterConfirmed: false,
    viewMode: 'grid',
    get filteredCenters() {
        const q = centerSearch.toLowerCase();
        return centersList.filter(c =>
            $store.session.inScope(c.barangay) &&
            (!q || c.name.toLowerCase().includes(q) || c.barangay.toLowerCase().includes(q)) &&
            (!centerStatusFilter || c.status === centerStatusFilter) &&
            (!this.centerBarangayFilter || c.barangay === this.centerBarangayFilter)
        );
    },
}" x-init="loadCenters()" class="space-y-3">

{{-- Sakuna Redesign (2026-11-02): stat strip and actions, then one card
     holding the toolbar and either the card grid or the table. --}}

{{-- ── Stats + actions ──────────────────────────────────────────────────────────────────── --}}
<div class="sk-pagebar">
    <div class="sk-stats" x-show="!centersFailed" :class="centersLoading ? 'animate-pulse opacity-60' : ''">
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-green"></span>Open</span>
            <span class="sk-sval tabular-nums" x-text="centersList.filter(c => c.status === 'Processing').length"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-orange"></span>Near/over capacity</span>
            <span class="sk-sval tabular-nums" x-text="centersList.filter(c => c.status === 'Processing' && c.pct >= 75).length"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-slate"></span>Not open</span>
            <span class="sk-sval tabular-nums" x-text="centersList.filter(c => c.status !== 'Processing').length"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-blue"></span>Total capacity (open)</span>
            <span class="sk-sval tabular-nums" x-text="centersList.filter(c => c.status === 'Processing').reduce((s,c) => s + c.capacity, 0).toLocaleString()"></span>
        </div>
        <div class="sk-stat">
            <span class="sk-slab"><span class="sk-dot sk-t-navy"></span>Current occupants</span>
            <span class="sk-sval tabular-nums" x-text="centersList.reduce((s,c) => s + c.individuals, 0).toLocaleString()"></span>
        </div>
    </div>
    <div class="sk-actions">
        <button type="button" class="sk-iconbtn sk-iconbtn-bare" aria-label="About evacuation centers: register facilities and manage occupancy and open/close status" title="Register facilities and manage occupancy and open/close status.">
            <i data-lucide="info"></i>
        </button>
        <div class="sk-btngrp" role="group" aria-label="Export">
            <button type="button" class="sk-btn" @click="exportCSV(filteredCenters, 'evacuation-centers-export.csv')">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button type="button" class="sk-btn" @click="exportPDF(filteredCenters, 'evacuation-centers-export.csv')">
                Export PDF
            </button>
        </div>
        <button type="button" x-show="can('sakuna_centers', 'create')" @click="openCreateCenter()" class="sk-btn sk-btn-p">
            <i data-lucide="plus"></i> Add Center
        </button>
    </div>
</div>

<div x-show="centersLoading" class="sk-card h-80 animate-pulse"></div>
<div x-show="centersFailed" x-cloak><x-ui.error-state messageExpr="'Could not load evacuation centers.'" retryExpr="loadCenters()" :retryable="true" /></div>

<div x-show="!centersLoading && !centersFailed" x-cloak class="space-y-3">

<section class="sk-card overflow-hidden">
    {{-- ── Toolbar ──────────────────────────────────────────────────────────────────────── --}}
    <div class="sk-toolbar">
        <div class="sk-search">
            <label for="center-search" class="sr-only">Search by name or barangay</label>
            <i data-lucide="search"></i>
            <input id="center-search" aria-label="Search by name or barangay" type="search" x-model="centerSearch" placeholder="Search by name or barangay…">
        </div>
        <select aria-label="Center status filter" x-model="centerStatusFilter" class="sk-sel">
            <option value="">All statuses</option>
            <option value="Draft">Registered (not open)</option>
            <option value="Processing">Open</option>
            <option value="Archived">Closed</option>
        </select>
        <select aria-label="Center barangay filter" x-model="centerBarangayFilter" class="sk-sel">
            <option value="">All barangays</option>
            @foreach($barangays as $brgy)
                <option>{{ $brgy }}</option>
            @endforeach
        </select>
        <button type="button" class="sk-clear" @click="centerSearch=''; centerStatusFilter=''; centerBarangayFilter='';">Clear</button>
        <span class="sk-spacer"></span>
        <span class="sk-csub" x-text="filteredCenters.length + (filteredCenters.length === 1 ? ' center' : ' centers')"></span>
        <div class="sk-btngrp" role="group" aria-label="View">
            <button type="button" aria-label="Card view" :aria-pressed="viewMode === 'grid'" @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'sk-iconbtn-on' : ''" class="sk-btn" style="padding: 0 9px;">
                <i data-lucide="layout-grid"></i>
            </button>
            <button type="button" aria-label="Table view" :aria-pressed="viewMode === 'table'" @click="viewMode = 'table'" :class="viewMode === 'table' ? 'sk-iconbtn-on' : ''" class="sk-btn" style="padding: 0 9px;">
                <i data-lucide="table"></i>
            </button>
        </div>
    </div>

    {{-- ── Centers Grid View ────────────────────────────────────────────────────────────── --}}
    <div x-show="viewMode === 'grid'" class="grid gap-3 p-3 bg-[#F8FAFD]" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
        <template x-for="c in filteredCenters" :key="c.ref">
            <article class="sk-card p-3 flex flex-col gap-2.5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h3 class="sk-b text-[13px] leading-snug" x-text="c.name"></h3>
                        <p class="sk-sub" x-text="c.barangay + ' · Capacity ' + c.capacity.toLocaleString()"></p>
                    </div>
                    <span :class="statusBadge(c.status)" class="sk-pill flex-shrink-0" x-text="c.status === 'Processing' ? 'Open' : (c.status === 'Archived' ? 'Closed' : 'Registered')"></span>
                </div>

                <div class="space-y-1" x-show="c.status === 'Processing'">
                    <div class="flex justify-between text-[11.5px] sk-muted">
                        <span>Occupancy</span>
                        <span :class="c.pct >= 90 ? 'text-rose-600 font-semibold' : (c.pct >= 75 ? 'text-amber-600 font-medium' : 'text-slate-600')" x-text="c.pct + '%'"></span>
                    </div>
                    <div class="h-[5px] rounded-full bg-[#E9EEF6] overflow-hidden">
                        <div class="h-full rounded-full transition-all" :class="occupancyBar(c.pct)" :style="'width:' + c.pct + '%'"></div>
                    </div>
                </div>

                <div class="grid grid-cols-3 text-center bg-[#F8FAFD] rounded-lg py-1.5">
                    <div><p class="sk-b text-[15px] tabular-nums" x-text="c.families"></p><p class="sk-ssub">Families</p></div>
                    <div><p class="sk-b text-[15px] tabular-nums" :class="(c.capacity - c.individuals) > 0 ? '!text-emerald-700' : '!text-rose-600'" x-text="(c.capacity - c.individuals).toLocaleString()"></p><p class="sk-ssub">Available</p></div>
                    <div><p class="sk-b text-[15px] tabular-nums" x-text="c.status === 'Processing' ? c.individuals.toLocaleString() + ' / ' + c.capacity.toLocaleString() : '—'"></p><p class="sk-ssub">Occupancy</p></div>
                </div>

                <div class="flex flex-col gap-1 text-[11.5px]">
                    <span class="sk-muted inline-flex items-center gap-1" x-show="c.address"><i data-lucide="map-pin" class="w-3 h-3 shrink-0"></i><span x-text="c.address"></span></span>
                    {{-- On the residents' map, or not yet (registered before the map, 2026-10-26). --}}
                    <span class="flex items-center justify-between gap-1.5">
                        <span class="inline-flex items-center gap-1 font-medium" :class="hasMapPin(c) ? 'text-emerald-700' : 'text-[#C2410C]'">
                            <i :data-lucide="hasMapPin(c) ? 'map-pin-check' : 'map-pin-off'" class="w-3 h-3"></i>
                            <span x-text="hasMapPin(c) ? 'On the map' : 'No map location yet'"></span>
                        </span>
                        <button type="button" x-show="hasMapPin(c)" @click="openCenterDetail(c)" class="sk-link" style="font-size: 11.5px;">View on map</button>
                        <button type="button" x-show="!hasMapPin(c) && can('sakuna_centers','edit')" @click="openEditCenter(c)" class="sk-link" style="font-size: 11.5px;">Add location</button>
                    </span>
                </div>

                <div class="flex gap-1.5 mt-auto">
                    <button type="button" @click="openCenterDetail(c)" class="sk-btn sk-btn-sm sk-btn-soft flex-1">
                        <i data-lucide="eye"></i> View details
                    </button>
                    <button type="button" x-show="can('sakuna_centers','open') && c.status !== 'Processing'" @click="pendingCenterAction = c; showOpenCenterConfirm = true; $nextTick(() => window.renderIcons?.())" class="sk-btn sk-btn-sm sk-btn-ok flex-1">
                        <i data-lucide="door-open"></i> Open
                    </button>
                    <button type="button" x-show="can('sakuna_centers','close') && c.status !== 'Archived'" @click="pendingCenterAction = c; closeCenterConfirmed = false; showCloseCenterConfirm = true; $nextTick(() => window.renderIcons?.())" class="sk-btn sk-btn-sm flex-1">
                        <i data-lucide="circle-x"></i> Close
                    </button>
                </div>
            </article>
        </template>
        <div x-show="filteredCenters.length === 0" class="sk-empty" style="grid-column: 1 / -1;">
            <i data-lucide="house"></i> No evacuation centers match your filters.
        </div>
    </div>

    {{-- ── Centers Table View ───────────────────────────────────────────────────────────── --}}
    <div x-show="viewMode === 'table'" x-cloak class="sk-scroll" tabindex="0" role="region" aria-label="Evacuation centres table (scrolls sideways)">
        <table class="sk-tbl min-w-[860px]">
            <thead>
                <tr>
                    <th>Center</th>
                    <th>Barangay</th>
                    <th>Status</th>
                    <th>Occupancy</th>
                    <th class="sk-r">Families</th>
                    <th class="sk-r">Available</th>
                    <th>Location</th>
                    <th class="sk-r">Actions</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="c in filteredCenters" :key="c.ref">
                    <tr>
                        <td>
                            <div class="sk-b" x-text="c.name"></div>
                            <div class="sk-sub" x-text="c.address || ''"></div>
                        </td>
                        <td class="whitespace-nowrap" x-text="c.barangay"></td>
                        <td class="whitespace-nowrap"><span :class="statusBadge(c.status)" class="sk-pill" x-text="c.status === 'Processing' ? 'Open' : (c.status === 'Archived' ? 'Closed' : 'Registered')"></span></td>
                        <td class="whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-20 h-[5px] rounded-full bg-[#E9EEF6] overflow-hidden">
                                    <div class="h-full rounded-full" :class="occupancyBar(c.pct)" :style="'width:' + c.pct + '%'"></div>
                                </div>
                                <span class="tabular-nums" x-text="c.individuals + '/' + c.capacity"></span>
                            </div>
                        </td>
                        <td class="sk-r tabular-nums" x-text="c.families"></td>
                        <td class="sk-r tabular-nums" :class="(c.capacity - c.individuals) > 0 ? 'text-emerald-700' : 'text-rose-600'" x-text="(c.capacity - c.individuals).toLocaleString()"></td>
                        <td class="whitespace-nowrap">
                            <span x-show="hasMapPin(c)" class="text-emerald-700">On the map</span>
                            <span x-show="!hasMapPin(c)"><span class="text-[#C2410C]">No map location yet</span><template x-if="can('sakuna_centers','edit')"><span> · <button type="button" @click="openEditCenter(c)" class="sk-link" style="font-size: 12px;">Add</button></span></template></span>
                        </td>
                        <td class="sk-r whitespace-nowrap">
                            <button type="button" @click="openCenterDetail(c)" class="sk-btn sk-btn-sm sk-btn-soft">
                                <i data-lucide="eye"></i> View
                            </button>
                            <button type="button" x-show="can('sakuna_centers','open') && c.status !== 'Processing'" @click="pendingCenterAction = c; showOpenCenterConfirm = true; $nextTick(() => window.renderIcons?.())" class="sk-btn sk-btn-sm sk-btn-ok">
                                <i data-lucide="door-open"></i> Open
                            </button>
                            <button type="button" x-show="can('sakuna_centers','close') && c.status !== 'Archived'" @click="pendingCenterAction = c; closeCenterConfirmed = false; showCloseCenterConfirm = true; $nextTick(() => window.renderIcons?.())" class="sk-btn sk-btn-sm">
                                <i data-lucide="circle-x"></i> Close
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredCenters.length === 0">
                    <td colspan="8"><div class="sk-empty"><i data-lucide="house"></i> No evacuation centers match your filters.</div></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

</div>{{-- /loaded content --}}

{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
{{-- CENTER DETAIL MODAL                                                                    --}}
{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
<div x-show="showCenterDetail" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showCenterDetail = false"
    x-transition:enter="transition duration-[200ms] ease-emphasized"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
        x-trap="showCenterDetail"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCenterDetail = false">

    <div x-data="{ centerTab: 'overview' }"
        x-transition:enter="transition duration-[200ms] ease-emphasized"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-3xl max-h-[90vh] overflow-y-auto">

        <div class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-brand-600/10 sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mt-0.5">
                    <i data-lucide="building-2" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-ink-body" x-text="selectedCenter?.name || '—'"></h3>
                    <p class="text-xs text-ink-muted mt-0.5" x-text="selectedCenter?.ref + ' · ' + (selectedCenter?.barangay || '')"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span :class="statusBadge(selectedCenter?.status ?? '')" x-text="selectedCenter?.status === 'Processing' ? 'Open' : (selectedCenter?.status === 'Archived' ? 'Closed' : 'Registered')"></span>
                <button aria-label="Close" @click="showCenterDetail = false" class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100 text-ink-muted transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <div class="flex overflow-x-auto border-b border-brand-600/10 px-6 scrollbar-none">
            @foreach(['overview' => 'Overview', 'services' => 'Services', 'occupants' => 'Occupants'] as $tab => $label)
            <button @click="centerTab = '{{ $tab }}'"
                :class="centerTab === '{{ $tab }}' ? 'border-b-2 border-brand-600 text-brand-600 font-medium' : 'text-ink-muted hover:text-slate-700'"
                class="text-sm px-4 py-3 whitespace-nowrap transition-colors flex-shrink-0">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Tab: Overview --}}
        <div x-show="centerTab === 'overview'" class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="bg-slate-50 rounded-xl p-4 space-y-2.5">
                    <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r">Facility</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-ink-muted">Ref</span><span class="font-mono font-medium text-ink-body" x-text="selectedCenter?.ref || '—'"></span></div>
                        <div class="flex justify-between"><span class="text-ink-muted">Barangay</span><span class="font-medium text-ink-body" x-text="selectedCenter?.barangay || '—'"></span></div>
                        <div class="flex justify-between gap-3"><span class="text-ink-muted shrink-0">Address</span><span class="font-medium text-ink-body text-right" x-text="selectedCenter?.address || '—'"></span></div>
                        <div class="flex justify-between gap-3"><span class="text-ink-muted shrink-0">Coordinates</span><span class="font-mono text-xs text-ink-body text-right" x-text="hasMapPin(selectedCenter) ? window.sakunaGeo.coordText(selectedCenter.lat, selectedCenter.lng) : 'Not pinned yet'"></span></div>
                    </div>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 space-y-2.5">
                    <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r">Capacity & Occupancy</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-ink-muted">Capacity</span><span class="font-medium text-ink-body" x-text="selectedCenter?.capacity ?? '—'"></span></div>
                        <div class="flex justify-between"><span class="text-ink-muted">Current Occupants</span><span class="font-medium text-ink-body" x-text="selectedCenter?.individuals ?? 0"></span></div>
                        <div class="flex justify-between"><span class="text-ink-muted">Families</span><span class="font-medium text-ink-body" x-text="selectedCenter?.families ?? 0"></span></div>
                        <div class="flex justify-between"><span class="text-ink-muted">Available</span>
                            <span class="font-medium text-emerald-600" x-text="selectedCenter ? (selectedCenter.capacity - selectedCenter.individuals) : '—'"></span>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Where it is (2026-10-26): what residents see on their map. --}}
            <div>
                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r mb-2">Map Location</h4>
                <div x-show="hasMapPin(selectedCenter)">
                    <div x-data="sakunaPinView({ point: () => ({ lat: selectedCenter?.lat, lng: selectedCenter?.lng }), tone: 'centre' })" x-effect="show()">
                        <div x-ref="map" class="sakuna-map h-56 w-full rounded-xl overflow-hidden border border-slate-200" role="img" aria-label="Map showing where the centre is"></div>
                        <p x-show="viewState === 'failed'" x-cloak class="text-[11px] text-amber-800 font-semibold mt-1.5">The map could not load. The coordinates above are still the saved ones.</p>
                    </div>
                    <div class="flex flex-wrap gap-x-4 mt-2 text-xs">
                        <a :href="hasMapPin(selectedCenter) ? window.sakunaGeo.osmUrl(selectedCenter.lat, selectedCenter.lng) : '#'" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">Open in OpenStreetMap</a>
                        <a :href="hasMapPin(selectedCenter) ? window.sakunaGeo.directionsUrl(selectedCenter.lat, selectedCenter.lng) : '#'" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">Directions</a>
                    </div>
                </div>
                <div x-show="!hasMapPin(selectedCenter)" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                    <p class="font-semibold">This centre is not on the map yet.</p>
                    <p class="mt-0.5">Residents still see it in their list, but not on the map or as a nearest centre. Edit it to pin where it is.</p>
                </div>
            </div>
        </div>

        {{-- Tab: Services --}}
        <div x-show="centerTab === 'services'" class="p-6">
            <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r mb-3">Services On File</h4>
            <div class="flex flex-wrap gap-1.5">
                <template x-for="s in (selectedCenter?.services || [])" :key="s">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200" x-text="s"></span>
                </template>
                <span x-show="!(selectedCenter?.services || []).length" class="text-xs text-ink-muted italic">No services recorded for this center.</span>
            </div>
        </div>

        {{-- Tab: Occupants (real, fetched from GET /sakuna/evacuees?center=) --}}
        <div x-show="centerTab === 'occupants'" class="p-6 space-y-3">
            <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r">Checked-In Families</h4>
            <div class="divide-y divide-brand-600/[.08] border border-brand-600/10 rounded-xl overflow-hidden">
                <template x-for="f in centerEvacuees" :key="f.ref">
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-700 truncate" x-text="f.head_name"></p>
                            <p class="text-xs text-ink-muted" x-text="f.sitio || f.barangay"></p>
                        </div>
                        <span class="text-xs text-ink-muted shrink-0" x-text="f.member_count + ' member(s)'"></span>
                    </div>
                </template>
                <div x-show="centerEvacuees.length === 0" class="px-4 py-8 text-center text-xs text-ink-muted">No families currently checked in here.</div>
            </div>
            <div class="flex justify-end">
                <button @click="setTab('evacuees')" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 px-4 py-2 rounded-2xl transition-colors">
                    <i data-lucide="users" class="w-4 h-4"></i> Manage Evacuees
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-t border-brand-600/10 bg-slate-50/70 rounded-b-2xl sticky bottom-0">
            <button x-show="can('sakuna_centers','edit')" @click="openEditCenter(selectedCenter); showCenterDetail = false"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:border-slate-300 px-3 py-1.5 rounded-xl transition-colors">
                <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
            </button>
            <button @click="showCenterDetail = false" class="text-sm text-ink-muted hover:text-slate-700 px-3 py-1.5 rounded-xl hover:bg-slate-100 transition-colors">Close</button>
        </div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
{{-- OPEN CENTER CONFIRMATION MODAL                                                         --}}
{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
<div x-show="showOpenCenterConfirm" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showOpenCenterConfirm = false"
    x-transition:enter="transition duration-[200ms] ease-emphasized"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
        x-trap="showOpenCenterConfirm"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showOpenCenterConfirm = false">

    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-md p-6">

        <div class="flex items-center gap-3 mb-4">
            <span class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0"><i data-lucide="door-open" class="w-5 h-5 text-emerald-600"></i></span>
            <h3 class="text-base font-semibold text-ink-body">Open Evacuation Center</h3>
        </div>

        <p class="text-sm text-slate-600 leading-relaxed mb-5">
            Open <strong class="text-ink-body" x-text="pendingCenterAction?.name || 'this center'"></strong> for check-ins.
        </p>

        <div class="flex items-center justify-end gap-3">
            <button @click="showOpenCenterConfirm = false" class="text-sm text-slate-600 bg-white border border-slate-200 hover:border-slate-300 px-4 py-2 rounded-2xl transition-colors">Cancel</button>
            <button @click="confirmOpenCenter()" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 px-5 py-2 rounded-2xl transition-colors">
                <i data-lucide="check" class="w-4 h-4"></i> Confirm Open
            </button>
        </div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
{{-- CLOSE CENTER CONFIRMATION MODAL                                                        --}}
{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
<div x-show="showCloseCenterConfirm" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showCloseCenterConfirm = false"
    x-transition:enter="transition duration-[200ms] ease-emphasized"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
        x-trap="showCloseCenterConfirm"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCloseCenterConfirm = false">

    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-md p-6">

        <div class="flex items-center gap-3 mb-4">
            <span class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center flex-shrink-0"><i data-lucide="circle-x" class="w-5 h-5 text-rose-600"></i></span>
            <h3 class="text-base font-semibold text-ink-body">Close Evacuation Center</h3>
        </div>

        <p class="text-sm text-slate-600 leading-relaxed mb-4">
            Closing <strong class="text-ink-body" x-text="pendingCenterAction?.name || 'this center'"></strong> removes it from active operations.
            The server refuses this while anyone is still checked in — transfer or check out all occupants first.
        </p>

        <label class="flex items-start gap-3 cursor-pointer mb-5 select-none">
            <input type="checkbox" x-model="closeCenterConfirmed" class="w-4 h-4 mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-400 flex-shrink-0">
            <span class="text-sm text-slate-700">I confirm this center is cleared for closure.</span>
        </label>

        <div class="flex items-center justify-end gap-3">
            <button @click="showCloseCenterConfirm = false; closeCenterConfirmed = false;" class="text-sm text-slate-600 bg-white border border-slate-200 hover:border-slate-300 px-4 py-2 rounded-2xl transition-colors">Cancel</button>
            <button :disabled="!closeCenterConfirmed" :class="closeCenterConfirmed ? 'bg-rose-600 hover:bg-rose-700 text-white cursor-pointer' : 'bg-slate-100 text-ink-muted cursor-not-allowed'"
                @click="if (closeCenterConfirmed) { confirmCloseCenter(); closeCenterConfirmed = false; }"
                class="inline-flex items-center gap-1.5 text-sm font-medium px-5 py-2 rounded-2xl transition-colors">
                <i data-lucide="circle-x" class="w-4 h-4"></i> Confirm Close
            </button>
        </div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
{{-- ADD / EDIT CENTER MODAL                                                                --}}
{{-- ────────────────────────────────────────────────────────────────────────────────────── --}}
<div x-show="showCreateCenter" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm"
    @click.self="showCreateCenter = false"
    x-transition:enter="transition duration-[200ms] ease-emphasized"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
        x-trap="showCreateCenter"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showCreateCenter = false">

    <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-[28px] shadow-float w-full max-w-xl max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-brand-600/10 sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center"><i data-lucide="building-2" class="w-5 h-5 text-brand-600"></i></span>
                <div>
                    <h3 class="text-base font-semibold text-ink-body" x-text="centerFormMode === 'create' ? 'Register Evacuation Center' : 'Edit Center — ' + centerForm.ref"></h3>
                    <p class="text-xs text-ink-muted">Facility name, barangay, capacity and its place on the map, plus optional services.</p>
                </div>
            </div>
            <button aria-label="Close" @click="showCreateCenter = false" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100 text-ink-muted transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>

        <div class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="center-name">Center Name <span class="text-rose-500">*</span></label>
                <input id="center-name" type="text" x-model="centerForm.name" placeholder="e.g. Poblacion Elementary School"
                    class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5" for="center-barangay">Barangay <span class="text-rose-500">*</span></label>
                    <select id="center-barangay" x-model="centerForm.barangay" class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
                        <option value="">Select barangay...</option>
                        @foreach($barangays as $brgy)
                            <option>{{ $brgy }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5" for="center-capacity">Capacity <span class="text-rose-500">*</span></label>
                    <input id="center-capacity" type="number" min="0" x-model="centerForm.capacity" placeholder="e.g. 200"
                        class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="center-address">Address</label>
                <input id="center-address" type="text" x-model="centerForm.address" placeholder="Street / Sitio, Barangay, Esperanza, Masbate"
                    class="w-full text-sm border border-slate-200 rounded-2xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
            </div>
            {{-- Where it stands (2026-10-26): required to register, so residents can find it. --}}
            <x-sakuna.location-picker target="centerForm" search subject="centre" required="centerFormMode === 'create'" />
            <div>
                <span class="block text-xs font-medium text-slate-700 mb-1.5">Services</span>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="s in ['Potable Water','Toilets','Electricity','Medical Station','Kitchen Facility','PWD-Accessible','Security Post','Relief Distribution Point']" :key="s">
                        <button type="button" @click="toggleCenterFormService(s)"
                            class="px-2.5 py-1 rounded-xl text-xs font-medium border transition-colors"
                            :class="centerForm.services.includes(s) ? 'bg-brand-50 border-brand-300 text-brand-700' : 'bg-white border-slate-200 text-ink-muted'"
                            x-text="s"></button>
                    </template>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 px-6 py-4 border-t border-brand-600/10 bg-slate-50/70 rounded-b-2xl">
            <button @click="showCreateCenter = false" class="text-sm text-slate-600 bg-white border border-slate-200 hover:border-slate-300 px-4 py-2 rounded-2xl transition-colors">Cancel</button>
            <p x-show="centerNeedsPin && centerForm.name && centerForm.barangay && centerForm.capacity !== ''" class="text-[11px] font-semibold text-rose-700 ml-auto text-right">Pin the centre on the map to register it.</p>
            <button :disabled="centerSaving || !centerForm.name || !centerForm.barangay || centerForm.capacity === '' || centerNeedsPin" @click="saveCenter()"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 px-5 py-2 rounded-2xl transition-colors ml-auto disabled:opacity-50">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span x-text="centerSaving ? 'Saving…' : (centerFormMode === 'create' ? 'Register Center' : 'Save Changes')"></span>
            </button>
        </div>
    </div>
</div>

</div>{{-- end local x-data --}}

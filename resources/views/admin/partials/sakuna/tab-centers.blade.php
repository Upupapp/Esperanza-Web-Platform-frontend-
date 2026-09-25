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
}" x-init="loadCenters()" class="space-y-5">

{{-- ── Header ──────────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-start justify-between gap-3">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Evacuation Centers</h2>
        <p class="text-sm text-slate-500 mt-0.5">Register facilities and manage occupancy and open/close status.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="exportCSV(filteredCenters, 'evacuation-centers-export.csv')"
            class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-card hover:border-slate-300 transition-colors">
            <i data-lucide="download" class="w-4 h-4 text-slate-400"></i> Export
        </button>
        <button x-show="can('sakuna_centers', 'create')" @click="openCreateCenter()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-3 py-2 shadow-card transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Center
        </button>
    </div>
</div>

<div x-show="centersLoading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
    @for ($i = 0; $i < 6; $i++)<div class="bg-white rounded-2xl border border-slate-100 shadow-card h-56 animate-pulse"></div>@endfor
</div>
<div x-show="centersFailed" x-cloak><x-ui.error-state messageExpr="'Could not load evacuation centers.'" retryExpr="loadCenters()" :retryable="true" /></div>

<div x-show="!centersLoading && !centersFailed" x-cloak class="space-y-5">

{{-- ── Status + Occupancy Summary ──────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap gap-2 items-center">
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
        <i data-lucide="door-open" class="w-3.5 h-3.5"></i> <span x-text="'Open: ' + centersList.filter(c => c.status === 'Processing').length"></span>
    </span>
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
        <i data-lucide="triangle-alert" class="w-3.5 h-3.5"></i> <span x-text="'Near/Over Capacity: ' + centersList.filter(c => c.status === 'Processing' && c.pct >= 75).length"></span>
    </span>
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
        <i data-lucide="circle-x" class="w-3.5 h-3.5"></i> <span x-text="'Not Open: ' + centersList.filter(c => c.status !== 'Processing').length"></span>
    </span>
    <span class="mx-1 text-slate-200 select-none">|</span>
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
        <i data-lucide="users" class="w-3.5 h-3.5"></i> <span x-text="'Total Capacity (open): ' + centersList.filter(c => c.status === 'Processing').reduce((s,c) => s + c.capacity, 0).toLocaleString()"></span>
    </span>
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
        <i data-lucide="user-check" class="w-3.5 h-3.5"></i> <span x-text="'Current: ' + centersList.reduce((s,c) => s + c.individuals, 0).toLocaleString()"></span>
    </span>
</div>

{{-- ── Filter Bar ───────────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap gap-2 items-center">
    <div class="relative">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        <input aria-label="Search by name or barangay" type="text" x-model="centerSearch"
            placeholder="Search by name or barangay..."
            class="pl-9 pr-3 py-2 text-sm bg-white border border-slate-200 rounded-xl shadow-card focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 w-60 transition-colors">
    </div>
    <select aria-label="Center status filter" x-model="centerStatusFilter"
        class="text-sm bg-white border border-slate-200 rounded-xl shadow-card px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
        <option value="">All Statuses</option>
        <option value="Draft">Registered (not open)</option>
        <option value="Processing">Open</option>
        <option value="Archived">Closed</option>
    </select>
    <select aria-label="Center barangay filter" x-model="centerBarangayFilter"
        class="text-sm bg-white border border-slate-200 rounded-xl shadow-card px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
        <option value="">All Barangays</option>
        @foreach($barangays as $brgy)
            <option>{{ $brgy }}</option>
        @endforeach
    </select>
    <button @click="centerSearch=''; centerStatusFilter=''; centerBarangayFilter='';"
        class="inline-flex items-center gap-1.5 text-sm text-slate-500 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-card hover:border-slate-300 transition-colors">
        <i data-lucide="x" class="w-3.5 h-3.5"></i> Clear
    </button>
    <div class="ml-auto flex items-center bg-white border border-slate-200 rounded-xl overflow-hidden shadow-card">
        <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-brand-50 text-brand-600' : 'text-slate-400 hover:text-slate-600'" class="px-3 py-2 transition-colors">
            <i data-lucide="layout-grid" class="w-4 h-4"></i>
        </button>
        <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-brand-50 text-brand-600' : 'text-slate-400 hover:text-slate-600'" class="px-3 py-2 border-l border-slate-200 transition-colors">
            <i data-lucide="table" class="w-4 h-4"></i>
        </button>
    </div>
</div>

{{-- ── Centers Grid View ────────────────────────────────────────────────────────────────── --}}
<div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
    <template x-for="c in filteredCenters" :key="c.ref">
        <div class="bg-white rounded-2xl shadow-card border border-slate-100 p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800 leading-tight" x-text="c.name"></h3>
                    <p class="text-xs text-slate-500 mt-0.5" x-text="c.barangay"></p>
                </div>
                <span :class="statusBadge(c.status)" class="flex-shrink-0" x-text="c.status === 'Processing' ? 'Open' : (c.status === 'Archived' ? 'Closed' : 'Registered')"></span>
            </div>

            <div class="space-y-1.5" x-show="c.status === 'Processing'">
                <div class="flex justify-between text-xs text-slate-500">
                    <span>Occupancy</span>
                    <span :class="c.pct >= 90 ? 'text-rose-600 font-semibold' : (c.pct >= 75 ? 'text-amber-600 font-medium' : 'text-slate-600')" x-text="c.pct + '%'"></span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-2 rounded-full transition-all" :class="occupancyBar(c.pct)" :style="'width:' + c.pct + '%'"></div>
                </div>
                <p class="text-xs text-slate-500" x-text="c.individuals.toLocaleString() + ' / ' + c.capacity.toLocaleString() + ' persons'"></p>
            </div>
            <p class="text-xs text-slate-400 italic" x-show="c.status !== 'Processing'" x-text="'Capacity: ' + c.capacity.toLocaleString()"></p>

            <div class="grid grid-cols-2 gap-2 border-t border-slate-50 pt-3">
                <div class="text-center">
                    <p class="text-sm font-semibold text-slate-800" x-text="c.families"></p>
                    <p class="text-xs text-slate-400">Families</p>
                </div>
                <div class="text-center border-l border-slate-100">
                    <p class="text-sm font-semibold" :class="(c.capacity - c.individuals) > 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="(c.capacity - c.individuals).toLocaleString()"></p>
                    <p class="text-xs text-slate-400">Available</p>
                </div>
            </div>

            <p class="text-xs text-slate-500" x-show="c.address"><i data-lucide="map-pin" class="w-3.5 h-3.5 inline -mt-0.5 mr-1"></i><span x-text="c.address"></span></p>

            <div class="flex items-center gap-2 border-t border-slate-50 pt-3 mt-auto">
                <button @click="openCenterDetail(c)" class="flex-1 inline-flex items-center justify-center gap-1.5 text-xs font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 px-3 py-2 rounded-xl transition-colors">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> View Details
                </button>
                <button x-show="can('sakuna_centers','open') && c.status !== 'Processing'" @click="pendingCenterAction = c; showOpenCenterConfirm = true; $nextTick(() => window.renderIcons?.())"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-2 rounded-xl transition-colors">
                    <i data-lucide="door-open" class="w-3.5 h-3.5"></i> Open
                </button>
                <button x-show="can('sakuna_centers','close') && c.status === 'Processing'" @click="pendingCenterAction = c; closeCenterConfirmed = false; showCloseCenterConfirm = true; $nextTick(() => window.renderIcons?.())"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 text-xs font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 px-3 py-2 rounded-xl transition-colors">
                    <i data-lucide="circle-x" class="w-3.5 h-3.5"></i> Close
                </button>
            </div>
        </div>
    </template>
    <div x-show="filteredCenters.length === 0" class="sm:col-span-2 xl:col-span-3 flex flex-col items-center py-14 text-center text-sm text-slate-500">
        No evacuation centers match your filters.
    </div>
</div>

{{-- ── Centers Table View ───────────────────────────────────────────────────────────────── --}}
<div x-show="viewMode === 'table'" x-cloak class="bg-white rounded-2xl shadow-card border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr class="bg-slate-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Center</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Barangay</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Occupancy</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Families</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <template x-for="c in filteredCenters" :key="c.ref">
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-slate-800" x-text="c.name"></p>
                            <p class="text-xs text-slate-400" x-text="c.address || ''"></p>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap" x-text="c.barangay"></td>
                        <td class="px-4 py-3 whitespace-nowrap"><span :class="statusBadge(c.status)" x-text="c.status === 'Processing' ? 'Open' : (c.status === 'Archived' ? 'Closed' : 'Registered')"></span></td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-1.5 rounded-full" :class="occupancyBar(c.pct)" :style="'width:' + c.pct + '%'"></div>
                                </div>
                                <span class="text-xs text-slate-600" x-text="c.individuals + '/' + c.capacity"></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600" x-text="c.families"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button @click="openCenterDetail(c)" class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 px-2.5 py-1.5 rounded-lg transition-colors">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                                </button>
                                <button x-show="can('sakuna_centers','open') && c.status !== 'Processing'" @click="pendingCenterAction = c; showOpenCenterConfirm = true; $nextTick(() => window.renderIcons?.())"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1.5 rounded-lg transition-colors">
                                    <i data-lucide="door-open" class="w-3.5 h-3.5"></i> Open
                                </button>
                                <button x-show="can('sakuna_centers','close') && c.status === 'Processing'" @click="pendingCenterAction = c; closeCenterConfirmed = false; showCloseCenterConfirm = true; $nextTick(() => window.renderIcons?.())"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 px-2.5 py-1.5 rounded-lg transition-colors">
                                    <i data-lucide="circle-x" class="w-3.5 h-3.5"></i> Close
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

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
        class="bg-white rounded-2xl shadow-float w-full max-w-3xl max-h-[90vh] overflow-y-auto">

        <div class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-slate-100 sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mt-0.5">
                    <i data-lucide="building-2" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-800" x-text="selectedCenter?.name || '—'"></h3>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="selectedCenter?.ref + ' · ' + (selectedCenter?.barangay || '')"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span :class="statusBadge(selectedCenter?.status ?? '')" x-text="selectedCenter?.status === 'Processing' ? 'Open' : (selectedCenter?.status === 'Archived' ? 'Closed' : 'Registered')"></span>
                <button @click="showCenterDetail = false" class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <div class="flex overflow-x-auto border-b border-slate-100 px-6 scrollbar-none">
            @foreach(['overview' => 'Overview', 'services' => 'Services', 'occupants' => 'Occupants'] as $tab => $label)
            <button @click="centerTab = '{{ $tab }}'"
                :class="centerTab === '{{ $tab }}' ? 'border-b-2 border-brand-600 text-brand-600 font-medium' : 'text-slate-500 hover:text-slate-700'"
                class="text-sm px-4 py-3 whitespace-nowrap transition-colors flex-shrink-0">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Tab: Overview --}}
        <div x-show="centerTab === 'overview'" class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="bg-slate-50 rounded-xl p-4 space-y-2.5">
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Facility</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Ref</span><span class="font-mono font-medium text-slate-800" x-text="selectedCenter?.ref || '—'"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Barangay</span><span class="font-medium text-slate-800" x-text="selectedCenter?.barangay || '—'"></span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500 shrink-0">Address</span><span class="font-medium text-slate-800 text-right" x-text="selectedCenter?.address || '—'"></span></div>
                    </div>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 space-y-2.5">
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Capacity & Occupancy</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Capacity</span><span class="font-medium text-slate-800" x-text="selectedCenter?.capacity ?? '—'"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Current Occupants</span><span class="font-medium text-slate-800" x-text="selectedCenter?.individuals ?? 0"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Families</span><span class="font-medium text-slate-800" x-text="selectedCenter?.families ?? 0"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Available</span>
                            <span class="font-medium text-emerald-600" x-text="selectedCenter ? (selectedCenter.capacity - selectedCenter.individuals) : '—'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Services --}}
        <div x-show="centerTab === 'services'" class="p-6">
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Services On File</h4>
            <div class="flex flex-wrap gap-1.5">
                <template x-for="s in (selectedCenter?.services || [])" :key="s">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200" x-text="s"></span>
                </template>
                <span x-show="!(selectedCenter?.services || []).length" class="text-xs text-slate-400 italic">No services recorded for this center.</span>
            </div>
        </div>

        {{-- Tab: Occupants (real, fetched from GET /sakuna/evacuees?center=) --}}
        <div x-show="centerTab === 'occupants'" class="p-6 space-y-3">
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Checked-In Families</h4>
            <div class="divide-y divide-slate-50 border border-slate-100 rounded-xl overflow-hidden">
                <template x-for="f in centerEvacuees" :key="f.ref">
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-700 truncate" x-text="f.head_name"></p>
                            <p class="text-xs text-slate-400" x-text="f.sitio || f.barangay"></p>
                        </div>
                        <span class="text-xs text-slate-500 shrink-0" x-text="f.member_count + ' member(s)'"></span>
                    </div>
                </template>
                <div x-show="centerEvacuees.length === 0" class="px-4 py-8 text-center text-xs text-slate-400">No families currently checked in here.</div>
            </div>
            <div class="flex justify-end">
                <button @click="setTab('evacuees')" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 px-4 py-2 rounded-xl transition-colors">
                    <i data-lucide="users" class="w-4 h-4"></i> Manage Evacuees
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/70 rounded-b-2xl sticky bottom-0">
            <button x-show="can('sakuna_centers','edit')" @click="openEditCenter(selectedCenter); showCenterDetail = false"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:border-slate-300 px-3 py-1.5 rounded-lg transition-colors">
                <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
            </button>
            <button @click="showCenterDetail = false" class="text-sm text-slate-500 hover:text-slate-700 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">Close</button>
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
        class="bg-white rounded-2xl shadow-float w-full max-w-md p-6">

        <div class="flex items-center gap-3 mb-4">
            <span class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0"><i data-lucide="door-open" class="w-5 h-5 text-emerald-600"></i></span>
            <h3 class="text-base font-semibold text-slate-800">Open Evacuation Center</h3>
        </div>

        <p class="text-sm text-slate-600 leading-relaxed mb-5">
            Open <strong class="text-slate-800" x-text="pendingCenterAction?.name || 'this center'"></strong> for check-ins.
        </p>

        <div class="flex items-center justify-end gap-3">
            <button @click="showOpenCenterConfirm = false" class="text-sm text-slate-600 bg-white border border-slate-200 hover:border-slate-300 px-4 py-2 rounded-xl transition-colors">Cancel</button>
            <button @click="confirmOpenCenter()" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 px-5 py-2 rounded-xl transition-colors">
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
        class="bg-white rounded-2xl shadow-float w-full max-w-md p-6">

        <div class="flex items-center gap-3 mb-4">
            <span class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center flex-shrink-0"><i data-lucide="circle-x" class="w-5 h-5 text-rose-600"></i></span>
            <h3 class="text-base font-semibold text-slate-800">Close Evacuation Center</h3>
        </div>

        <p class="text-sm text-slate-600 leading-relaxed mb-4">
            Closing <strong class="text-slate-800" x-text="pendingCenterAction?.name || 'this center'"></strong> removes it from active operations.
            The server refuses this while anyone is still checked in — transfer or check out all occupants first.
        </p>

        <label class="flex items-start gap-3 cursor-pointer mb-5 select-none">
            <input type="checkbox" x-model="closeCenterConfirmed" class="w-4 h-4 mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-400 flex-shrink-0">
            <span class="text-sm text-slate-700">I confirm this center is cleared for closure.</span>
        </label>

        <div class="flex items-center justify-end gap-3">
            <button @click="showCloseCenterConfirm = false; closeCenterConfirmed = false;" class="text-sm text-slate-600 bg-white border border-slate-200 hover:border-slate-300 px-4 py-2 rounded-xl transition-colors">Cancel</button>
            <button :disabled="!closeCenterConfirmed" :class="closeCenterConfirmed ? 'bg-rose-600 hover:bg-rose-700 text-white cursor-pointer' : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
                @click="if (closeCenterConfirmed) { confirmCloseCenter(); closeCenterConfirmed = false; }"
                class="inline-flex items-center gap-1.5 text-sm font-medium px-5 py-2 rounded-xl transition-colors">
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
        class="bg-white rounded-2xl shadow-float w-full max-w-xl max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-slate-100 sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center"><i data-lucide="building-2" class="w-5 h-5 text-brand-600"></i></span>
                <div>
                    <h3 class="text-base font-semibold text-slate-800" x-text="centerFormMode === 'create' ? 'Register Evacuation Center' : 'Edit Center — ' + centerForm.ref"></h3>
                    <p class="text-xs text-slate-400">Facility name, barangay and capacity, plus optional services.</p>
                </div>
            </div>
            <button @click="showCreateCenter = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>

        <div class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="center-name">Center Name <span class="text-rose-500">*</span></label>
                <input id="center-name" type="text" x-model="centerForm.name" placeholder="e.g. Poblacion Elementary School"
                    class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5" for="center-barangay">Barangay <span class="text-rose-500">*</span></label>
                    <select id="center-barangay" x-model="centerForm.barangay" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
                        <option value="">Select barangay...</option>
                        @foreach($barangays as $brgy)
                            <option>{{ $brgy }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5" for="center-capacity">Capacity <span class="text-rose-500">*</span></label>
                    <input id="center-capacity" type="number" min="0" x-model="centerForm.capacity" placeholder="e.g. 200"
                        class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="center-address">Address</label>
                <input id="center-address" type="text" x-model="centerForm.address" placeholder="Street / Sitio, Barangay, Esperanza, Masbate"
                    class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 transition-colors">
            </div>
            <div>
                <span class="block text-xs font-medium text-slate-700 mb-1.5">Services</span>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="s in ['Potable Water','Toilets','Electricity','Medical Station','Kitchen Facility','PWD-Accessible','Security Post','Relief Distribution Point']" :key="s">
                        <button type="button" @click="toggleCenterFormService(s)"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium border transition-colors"
                            :class="centerForm.services.includes(s) ? 'bg-brand-50 border-brand-300 text-brand-700' : 'bg-white border-slate-200 text-slate-500'"
                            x-text="s"></button>
                    </template>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/70 rounded-b-2xl">
            <button @click="showCreateCenter = false" class="text-sm text-slate-600 bg-white border border-slate-200 hover:border-slate-300 px-4 py-2 rounded-xl transition-colors">Cancel</button>
            <button :disabled="centerSaving || !centerForm.name || !centerForm.barangay || centerForm.capacity === ''" @click="saveCenter()"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 px-5 py-2 rounded-xl transition-colors ml-auto disabled:opacity-50">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span x-text="centerSaving ? 'Saving…' : (centerFormMode === 'create' ? 'Register Center' : 'Save Changes')"></span>
            </button>
        </div>
    </div>
</div>

</div>{{-- end local x-data --}}

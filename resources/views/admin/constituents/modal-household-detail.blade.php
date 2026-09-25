{{-- modal-household-detail.blade.php --}}
{{-- Household detail modal — 3 tabs: Overview | Members | Amenities --}}

<div x-data="{
    get open() { return showHouseholdDetail },
    set open(v) { showHouseholdDetail = v },
    editing: false,
    saving: false,
    edit: {},
    startEdit() {
        this.edit = {
            sitio: selectedHousehold.sitio, address: selectedHousehold.address, head_name: null,
            dwelling_type: selectedHousehold.dwelling_type, housing_tenure: selectedHousehold.housing_tenure,
            flood_risk: selectedHousehold.flood_risk, electricity: !!selectedHousehold.electricity,
            water: !!selectedHousehold.water, toilet: !!selectedHousehold.toilet,
        };
        this.editing = true;
    },
    async saveEdit() {
        if (this.saving) return;
        this.saving = true;
        // head_name only sent if actually changed -- leaving it blank must not
        // silently detach the current head.
        const payload = { ...this.edit };
        if (!payload.head_name) delete payload.head_name;
        this.saving = true;
        try {
            const res = await $api.put('/admin/households/' + selectedHousehold.ref, payload);
            Object.assign(selectedHousehold, res.data);
            const idx = this.households.findIndex(x => x.ref === selectedHousehold.ref);
            if (idx !== -1) this.households[idx] = { ...this.households[idx], ...res.data };
            this.editing = false;
            this.$dispatch('toast', { message: 'Household record updated.', variant: 'success' });
        } catch (err) {
            this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not save changes.', variant: 'error' });
        } finally { this.saving = false; }
    },
}">
<x-ui.modal maxWidth="2xl" :bare="true" :fullscreen="true">

    <template x-if="selectedHousehold">
        <div class="flex flex-col min-h-0 h-full">

            {{-- ════════════════════════════════════════════════════════
                 MODAL HEADER (dark hero — matches individual/family)
            ════════════════════════════════════════════════════════ --}}
            <div class="relative shrink-0 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 text-white px-6 py-5 rounded-t-2xl overflow-hidden">
                <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
                <div class="absolute -bottom-6 -left-6 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>

                {{-- Compact toggle + Close --}}
                <div class="absolute top-4 right-4 flex items-center gap-1.5 z-20">
                    <button type="button" @click="compact = !compact" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" :title="compact ? 'Expand to full screen' : 'Shrink to popup'">
                        <span x-show="compact"><i data-lucide="maximize-2" class="w-3.5 h-3.5"></i></span>
                        <span x-show="!compact"><i data-lucide="minimize-2" class="w-3.5 h-3.5"></i></span>
                    </button>
                    <button type="button" @click="showHouseholdDetail = false; editing = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" title="Close">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="flex items-start gap-5 relative z-10">
                    <div class="flex-shrink-0 w-20 h-20 rounded-full bg-white/10 flex items-center justify-center text-white font-bold text-xl uppercase">
                        <span x-text="(selectedHousehold?.label || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('')"></span>
                    </div>

                    {{-- Identity block --}}
                    <div class="flex-1 min-w-0 pt-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white">
                                <i data-lucide="waves-horizontal" class="w-3 h-3"></i>
                                <span x-text="(selectedHousehold?.flood_risk || 'Unknown') + ' Flood Risk'"></span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/10 text-white/80">
                                <i data-lucide="shield-check" class="w-3 h-3"></i>
                                <span x-text="verificationBadge(selectedHousehold?.verification).label"></span>
                            </span>
                            {{-- Verifying here (after actually reviewing the household's
                                 members and details below) rather than from the list row,
                                 where nothing has been reviewed yet. --}}
                            <button
                                type="button"
                                x-show="selectedHousehold?.verification !== 'Verified' && can('const_data_quality','verify')"
                                @click="verifyHousehold(selectedHousehold)"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/30 hover:bg-emerald-400/30 transition-colors"
                            >
                                <i data-lucide="check" class="w-3 h-3"></i> Mark as Verified
                            </button>
                        </div>

                        <h2 class="text-xl font-bold text-white leading-tight truncate" x-text="selectedHousehold?.label"></h2>
                        <p class="text-sm text-white/60 mt-0.5">
                            ID: <span class="font-mono font-medium text-white/80" x-text="selectedHousehold?.ref"></span>
                            &nbsp;·&nbsp;
                            <span x-text="(selectedHousehold?.member_count || 0) + ' members'"></span>
                        </p>

                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                                <i data-lucide="map-pin" class="w-3 h-3"></i>
                                <span x-text="'Brgy. ' + (selectedHousehold?.barangay || '—') + (selectedHousehold?.sitio ? ' · Sitio ' + selectedHousehold.sitio : '')"></span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                                <i data-lucide="user" class="w-3 h-3"></i>
                                <span x-text="selectedHousehold?.head_name || 'No head listed'"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════
                 BODY: side nav + scrollable content panel
            ════════════════════════════════════════════════════════ --}}
            <div class="flex-1 min-h-0 flex flex-col sm:flex-row overflow-hidden">

                {{-- ── Side nav ── --}}
                <div class="w-full sm:w-48 shrink-0 flex flex-row sm:flex-col gap-0.5 border-b sm:border-b-0 sm:border-r border-slate-100 bg-slate-50/60 p-2 sm:p-2.5 overflow-x-auto sm:overflow-x-visible sm:overflow-y-auto scrollbar-thin">
                    <template x-for="tab in [
                        {id:'overview',icon:'layout-dashboard',label:'Overview'},
                        {id:'members',icon:'users',label:'Members',count:selectedHousehold?.member_count},
                        {id:'amenities',icon:'plug-zap',label:'Amenities'}
                    ]" :key="tab.id">
                        <button
                            @click="householdTab = tab.id"
                            class="shrink-0 sm:w-full flex items-center gap-2 sm:gap-2.5 px-3 py-2 sm:py-2.5 rounded-lg text-[13px] font-medium transition-colors whitespace-nowrap sm:whitespace-normal text-left"
                            :class="householdTab === tab.id ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'"
                        >
                            <i :data-lucide="tab.icon" class="w-4 h-4 shrink-0" :class="householdTab === tab.id ? 'text-brand-600' : 'text-slate-400'"></i>
                            <span class="flex-1 min-w-0 sm:truncate" x-text="tab.label"></span>
                            <span
                                x-show="tab.count"
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0"
                                :class="householdTab === tab.id ? 'bg-brand-100 text-brand-700' : 'bg-slate-200 text-slate-500'"
                                x-text="tab.count"
                            ></span>
                        </button>
                    </template>
                </div>

                {{-- ── Content panel ── --}}
                <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin px-6 py-5 bg-slate-50/50 space-y-4">

                {{-- ── OVERVIEW TAB ── --}}
                <div x-show="householdTab === 'overview'" x-cloak>
                    <template x-if="!editing">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Left column --}}
                        <x-ui.card class="p-4">
                            <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Location & Identification</h4>
                            <dl class="space-y-2.5">
                                <div class="flex justify-between items-start gap-2">
                                    <dt class="text-xs text-slate-500">Address</dt>
                                    <dd class="text-xs font-semibold text-navy-900 text-right" x-text="selectedHousehold.address || '—'"></dd>
                                </div>
                                <div class="flex justify-between items-start gap-2">
                                    <dt class="text-xs text-slate-500">Household ID</dt>
                                    <dd class="text-xs font-mono font-semibold text-navy-900" x-text="selectedHousehold.ref"></dd>
                                </div>
                                <div class="flex justify-between items-start gap-2">
                                    <dt class="text-xs text-slate-500">Barangay</dt>
                                    <dd class="text-xs font-semibold text-navy-900" x-text="selectedHousehold.barangay"></dd>
                                </div>
                                <div class="flex justify-between items-start gap-2">
                                    <dt class="text-xs text-slate-500">Sitio / Purok</dt>
                                    <dd class="text-xs font-semibold text-navy-900" x-text="selectedHousehold.sitio || '—'"></dd>
                                </div>
                                <div class="flex justify-between items-start gap-2">
                                    <dt class="text-xs text-slate-500">Head of Household</dt>
                                    <dd class="text-xs font-semibold text-navy-900" x-text="selectedHousehold.head_name || '—'"></dd>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <dt class="text-xs text-slate-500">Total Members</dt>
                                    <dd class="text-xs font-bold text-navy-900" x-text="selectedHousehold.member_count"></dd>
                                </div>
                            </dl>
                        </x-ui.card>

                        {{-- Right column --}}
                        <div class="space-y-3">
                            <x-ui.card class="p-4">
                                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Dwelling & Tenure</h4>
                                <dl class="space-y-2.5">
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-slate-500">Dwelling Type</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedHousehold.dwelling_type || '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-slate-500">Housing Tenure</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedHousehold.housing_tenure || '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-center gap-2">
                                        <dt class="text-xs text-slate-500">Flood Risk</dt>
                                        <dd>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                                :class="{
                                                    'bg-emerald-100 text-emerald-700': selectedHousehold.flood_risk === 'Low',
                                                    'bg-amber-100 text-amber-700': selectedHousehold.flood_risk === 'Medium',
                                                    'bg-rose-100 text-rose-700': selectedHousehold.flood_risk === 'High'
                                                }"
                                                x-text="selectedHousehold.flood_risk || '—'"
                                            ></span>
                                        </dd>
                                    </div>
                                </dl>
                            </x-ui.card>

                            <x-ui.card class="p-4">
                                <dl class="space-y-1.5">
                                    <div class="flex justify-between items-center gap-2">
                                        <dt class="text-xs text-slate-500">Record Status</dt>
                                        <dd>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" :class="statusBadge(selectedHousehold.status)" x-text="selectedHousehold.status"></span>
                                        </dd>
                                    </div>
                                </dl>
                            </x-ui.card>
                        </div>
                    </div>
                    </template>

                    {{-- ── EDIT FORM ── --}}
                    <template x-if="editing">
                        <div class="max-w-xl space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="hh-edit-address">Address</label>
                                <input id="hh-edit-address" type="text" x-model="edit.address" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-800 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="hh-edit-sitio">Sitio / Purok</label>
                                <input id="hh-edit-sitio" type="text" x-model="edit.sitio" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-800 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="hh-edit-head">Head of Household <span class="text-slate-400 font-normal">(leave blank to keep current)</span></label>
                                <input id="hh-edit-head" type="text" x-model="edit.head_name" placeholder="Full name" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-800 placeholder-slate-400 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Dwelling Type</label>
                                    <select aria-label="Dwelling Type" x-model="edit.dwelling_type" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                                        <option value="Single Detached">Single Detached</option>
                                        <option value="Single Attached">Single Attached</option>
                                        <option value="Apartment">Apartment</option>
                                        <option value="Informal Settler">Informal Settler</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Housing Tenure</label>
                                    <select aria-label="Housing Tenure" x-model="edit.housing_tenure" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                                        <option value="Owner">Owner</option>
                                        <option value="Renter">Renter</option>
                                        <option value="Shared">Shared</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5">Flood Risk</label>
                                <select aria-label="Flood Risk" x-model="edit.flood_risk" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-700 focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" x-model="edit.electricity" class="rounded text-brand-600"> Electricity</label>
                                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" x-model="edit.water" class="rounded text-brand-600"> Water</label>
                                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" x-model="edit.toilet" class="rounded text-brand-600"> Toilet</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-ui.button variant="secondary" size="sm" @click="editing = false">Cancel</x-ui.button>
                                <x-ui.button size="sm" icon="check" x-bind:disabled="saving" @click="saveEdit()" x-text="saving ? 'Saving…' : 'Save Changes'"></x-ui.button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- ── MEMBERS TAB ── --}}
                <div x-show="householdTab === 'members'" x-cloak>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-navy-900">
                            All Household Members
                            <span
                                class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-700 font-semibold"
                                x-text="individuals.filter(r => r.household?.ref === selectedHousehold?.ref).length"
                            ></span>
                        </h3>
                    </div>

                    <x-ui.card class="overflow-hidden">
                        <template x-if="individuals.filter(r => r.household?.ref === selectedHousehold?.ref).length === 0">
                            <div class="py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <i data-lucide="user-x" class="w-8 h-8 text-slate-300"></i>
                                    <p class="text-sm text-slate-400">No individuals linked to this household.</p>
                                </div>
                            </div>
                        </template>

                        <ul class="divide-y divide-slate-100">
                            <template x-for="r in individuals.filter(r => r.household?.ref === selectedHousehold?.ref)" :key="r.ref">
                                <li
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-indigo-50/40 cursor-pointer transition-colors"
                                    @click="openIndividual(r)"

        role="button"
        tabindex="0"
        @keydown.enter.prevent="openIndividual(r)"
        @keydown.space.prevent="openIndividual(r)">
                                    <div class="w-8 h-8 text-xs rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0" x-text="(r.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-navy-900 truncate" x-text="r.name"></p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs text-slate-400" x-text="r.age ? (r.age + ' yrs') : ''"></span>
                                            <template x-if="r.sex"><span class="text-xs text-slate-400" x-text="'· ' + r.sex"></span></template>
                                            <template x-if="r.relation"><span class="text-xs text-slate-500 font-medium" x-text="'· ' + r.relation"></span></template>
                                        </div>
                                    </div>
                                    <template x-if="r.family?.label">
                                        <span class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full hidden sm:inline-flex" x-text="r.family.label"></span>
                                    </template>
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0"
                                        :class="verificationBadge(r.verification).class"
                                        x-text="verificationBadge(r.verification).label"
                                    ></span>
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 flex-shrink-0"></i>
                                </li>
                            </template>
                        </ul>
                    </x-ui.card>
                </div>

                {{-- ── AMENITIES TAB ── --}}
                <div x-show="householdTab === 'amenities'" x-cloak>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4">Household Amenities</h3>

                    {{-- 3 amenity cards --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        {{-- Water --}}
                        <x-ui.card class="p-5 flex flex-col items-center text-center gap-3">
                            <div
                                class="w-16 h-16 rounded-2xl flex items-center justify-center"
                                :class="selectedHousehold.water ? 'bg-blue-100' : 'bg-slate-100'"
                            >
                                <i data-lucide="droplets" class="w-8 h-8" :class="selectedHousehold.water ? 'text-blue-500' : 'text-slate-300'"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-navy-900">Water Source</p>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold mt-1.5"
                                    :class="selectedHousehold.water ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'"
                                >
                                    <i :data-lucide="selectedHousehold.water ? 'check-circle' : 'x-circle'" class="w-3.5 h-3.5"></i>
                                    <span x-text="selectedHousehold.water ? 'Available' : 'Not Available'"></span>
                                </span>
                            </div>
                        </x-ui.card>

                        {{-- Electricity --}}
                        <x-ui.card class="p-5 flex flex-col items-center text-center gap-3">
                            <div
                                class="w-16 h-16 rounded-2xl flex items-center justify-center"
                                :class="selectedHousehold.electricity ? 'bg-amber-100' : 'bg-slate-100'"
                            >
                                <i data-lucide="zap" class="w-8 h-8" :class="selectedHousehold.electricity ? 'text-amber-500' : 'text-slate-300'"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-navy-900">Electricity</p>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold mt-1.5"
                                    :class="selectedHousehold.electricity ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'"
                                >
                                    <i :data-lucide="selectedHousehold.electricity ? 'check-circle' : 'x-circle'" class="w-3.5 h-3.5"></i>
                                    <span x-text="selectedHousehold.electricity ? 'Available' : 'Not Available'"></span>
                                </span>
                            </div>
                        </x-ui.card>

                        {{-- Toilet --}}
                        <x-ui.card class="p-5 flex flex-col items-center text-center gap-3">
                            <div
                                class="w-16 h-16 rounded-2xl flex items-center justify-center"
                                :class="selectedHousehold.toilet ? 'bg-emerald-100' : 'bg-slate-100'"
                            >
                                <i data-lucide="toilet" class="w-8 h-8" :class="selectedHousehold.toilet ? 'text-emerald-500' : 'text-slate-300'"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-navy-900">Toilet Facility</p>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold mt-1.5"
                                    :class="selectedHousehold.toilet ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                >
                                    <i :data-lucide="selectedHousehold.toilet ? 'check-circle' : 'x-circle'" class="w-3.5 h-3.5"></i>
                                    <span x-text="selectedHousehold.toilet ? 'Available' : 'Not Available'"></span>
                                </span>
                            </div>
                        </x-ui.card>
                    </div>

                    {{-- Flood risk block --}}
                    <h3 class="text-sm font-semibold text-navy-900 mb-3">Disaster Risk Profile</h3>
                    <div
                        class="rounded-xl p-4 flex items-start gap-3"
                        :class="{
                            'bg-emerald-50 border border-emerald-200': selectedHousehold.flood_risk === 'Low',
                            'bg-amber-50 border border-amber-200': selectedHousehold.flood_risk === 'Medium',
                            'bg-rose-50 border border-rose-200': selectedHousehold.flood_risk === 'High'
                        }"
                    >
                        <div
                            class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                            :class="{
                                'bg-emerald-100': selectedHousehold.flood_risk === 'Low',
                                'bg-amber-100': selectedHousehold.flood_risk === 'Medium',
                                'bg-rose-100': selectedHousehold.flood_risk === 'High'
                            }"
                        >
                            <i data-lucide="waves-horizontal" class="w-5 h-5"
                                :class="{
                                    'text-emerald-600': selectedHousehold.flood_risk === 'Low',
                                    'text-amber-600': selectedHousehold.flood_risk === 'Medium',
                                    'text-rose-600': selectedHousehold.flood_risk === 'High'
                                }"
                            ></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Flood Risk Level</p>
                            <p class="text-sm font-bold mt-0.5"
                                :class="{
                                    'text-emerald-700': selectedHousehold.flood_risk === 'Low',
                                    'text-amber-700': selectedHousehold.flood_risk === 'Medium',
                                    'text-rose-700': selectedHousehold.flood_risk === 'High'
                                }"
                                x-text="selectedHousehold.flood_risk || '—'"
                            ></p>
                        </div>
                    </div>
                </div>

                </div>
            </div>

            {{-- ── Modal Footer ── --}}
            <div class="flex-shrink-0 px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3 rounded-b-2xl" x-show="!editing">
                <x-ui.button size="sm" icon="pencil" x-show="can('const_household','edit')" @click="startEdit()">Edit Household</x-ui.button>
            </div>

        </div>
    </template>

    {{-- Loading placeholder --}}
    <template x-if="!selectedHousehold">
        <div class="flex items-center justify-center h-48 text-slate-400 text-sm">
            <i data-lucide="loader-circle" class="w-6 h-6 animate-spin mr-2"></i>
            Loading household details…
        </div>
    </template>

</x-ui.modal>
</div>

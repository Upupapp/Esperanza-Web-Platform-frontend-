{{-- modal-household-detail.blade.php --}}
{{-- Household detail modal — 3 tabs: Overview | Members | Amenities --}}

<div x-data="{
    get open() { return showHouseholdDetail },
    set open(v) { showHouseholdDetail = v },
    editing: false,
    saving: false,
    edit: {},
    // A new head picked from the registry, as in Add Household: a typed name
    // alone made a second record of a resident already on file, often a
    // member of this very household (QA sweep 2026-11-18).
    heads: [],
    head: null,
    headSeq: 0,
    async lookHead() {
        const seq = ++this.headSeq;
        const rows = await headMatches(this.edit.head_name, selectedHousehold.barangay);
        if (seq === this.headSeq) this.heads = rows;
    },
    pickHead(r) { this.head = { ref: r.ref, name: r.name }; this.edit.head_name = r.name; this.heads = []; },
    get headPicked() { return !!this.head && this.head.name === (this.edit.head_name || '').trim(); },
    startEdit() {
        this.edit = {
            sitio: selectedHousehold.sitio, address: selectedHousehold.address, head_name: null,
            dwelling_type: selectedHousehold.dwelling_type, housing_tenure: selectedHousehold.housing_tenure,
            flood_risk: selectedHousehold.flood_risk, electricity: hasAmenity(selectedHousehold.electricity),
            water: hasAmenity(selectedHousehold.water), toilet: hasAmenity(selectedHousehold.toilet),
        };
        this.head = null;
        this.heads = [];
        this.editing = true;
    },
    async saveEdit() {
        if (this.saving) return;
        this.saving = true;
        // head_name only sent if actually changed -- leaving it blank (or only
        // spaces) must not silently detach the current head. One picked goes
        // by ref; a name not picked is made a resident of this household's
        // barangay (sent without it, the server failed).
        const payload = { ...this.edit };
        if (typeof payload.head_name === 'string') payload.head_name = payload.head_name.trim();
        if (this.headPicked) { delete payload.head_name; payload.head_ref = this.head.ref; }
        else if (!payload.head_name) delete payload.head_name; else payload.barangay = selectedHousehold.barangay;
        // The amenities are words on the server, never true/false (QA sweep
        // 2026-11-18: every save was refused). A box left as it was keeps the
        // household's own word (Solar, Deep Well); a changed one says Yes or No.
        for (const k of ['electricity', 'water', 'toilet']) {
            if (payload[k] === hasAmenity(selectedHousehold[k])) delete payload[k];
            else payload[k] = payload[k] ? 'Yes' : 'No';
        }
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
                            {{-- Only when the record carries its flood risk: the list rows have none. --}}
                            <span x-show="selectedHousehold?.flood_risk !== undefined" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white">
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
                                <span x-text="'Brgy. ' + (selectedHousehold?.barangay || '—') + (selectedHousehold?.sitio ? ' · ' + sitioLabel(selectedHousehold.sitio) : '')"></span>
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
                <div class="w-full sm:w-48 shrink-0 flex flex-row sm:flex-col gap-0.5 border-b sm:border-b-0 sm:border-r border-brand-600/10 bg-slate-50/60 p-2 sm:p-2.5 overflow-x-auto sm:overflow-x-visible sm:overflow-y-auto scrollbar-thin">
                    <template x-for="tab in [
                        {id:'overview',icon:'layout-dashboard',label:'Overview'},
                        {id:'members',icon:'users',label:'Members',count:selectedHousehold?.member_count},
                        {id:'amenities',icon:'plug-zap',label:'Amenities'}
                    ]" :key="tab.id">
                        <button
                            @click="householdTab = tab.id"
                            class="shrink-0 sm:w-full flex items-center gap-2 sm:gap-2.5 px-3 py-2 sm:py-2.5 rounded-xl text-[13px] font-medium transition-colors whitespace-nowrap sm:whitespace-normal text-left"
                            :class="householdTab === tab.id ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-ink-muted hover:bg-white/70 hover:text-slate-700'"
                        >
                            <i :data-lucide="tab.icon" class="w-4 h-4 shrink-0" :class="householdTab === tab.id ? 'text-brand-600' : 'text-ink-muted'"></i>
                            <span class="flex-1 min-w-0 sm:truncate" x-text="tab.label"></span>
                            <span
                                x-show="tab.count"
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0"
                                :class="householdTab === tab.id ? 'bg-brand-100 text-brand-700' : 'bg-slate-200 text-ink-muted'"
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
                            <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3">Location & Identification</h4>
                            <dl class="space-y-2.5">
                                <div class="flex justify-between items-start gap-2">
                                    <dt class="text-xs text-ink-muted">Address</dt>
                                    <dd class="text-xs font-semibold text-ink text-right" x-text="selectedHousehold.address || '—'"></dd>
                                </div>
                                <div class="flex justify-between items-start gap-2">
                                    <dt class="text-xs text-ink-muted">Household ID</dt>
                                    <dd class="text-xs font-mono font-semibold text-ink" x-text="selectedHousehold.ref"></dd>
                                </div>
                                <div class="flex justify-between items-start gap-2">
                                    <dt class="text-xs text-ink-muted">Barangay</dt>
                                    <dd class="text-xs font-semibold text-ink" x-text="selectedHousehold.barangay"></dd>
                                </div>
                                <div class="flex justify-between items-start gap-2">
                                    <dt class="text-xs text-ink-muted">Sitio / Purok</dt>
                                    <dd class="text-xs font-semibold text-ink" x-text="selectedHousehold.sitio || '—'"></dd>
                                </div>
                                <div class="flex justify-between items-start gap-2">
                                    <dt class="text-xs text-ink-muted">Head of Household</dt>
                                    <dd class="text-xs font-semibold text-ink" x-text="selectedHousehold.head_name || '—'"></dd>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <dt class="text-xs text-ink-muted">Total Members</dt>
                                    <dd class="text-xs font-bold text-ink" x-text="selectedHousehold.member_count"></dd>
                                </div>
                            </dl>
                        </x-ui.card>

                        {{-- Right column --}}
                        <div class="space-y-3">
                            <x-ui.card class="p-4">
                                <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-3">Dwelling & Tenure</h4>
                                <dl class="space-y-2.5">
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-ink-muted">Dwelling Type</dt>
                                        <dd class="text-xs font-semibold text-ink" x-text="selectedHousehold.dwelling_type || '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-ink-muted">Housing Tenure</dt>
                                        <dd class="text-xs font-semibold text-ink" x-text="selectedHousehold.housing_tenure || '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-center gap-2">
                                        <dt class="text-xs text-ink-muted">Flood Risk</dt>
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
                                        <dt class="text-xs text-ink-muted">Record Status</dt>
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
                                <input id="hh-edit-address" type="text" x-model="edit.address" class="w-full rounded-2xl py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="hh-edit-sitio">Sitio / Purok</label>
                                <input id="hh-edit-sitio" type="text" x-model="edit.sitio" class="w-full rounded-2xl py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5" for="hh-edit-head">Head of Household <span class="text-ink-muted font-normal">(leave blank to keep current)</span></label>
                                <input id="hh-edit-head" type="text" x-model="edit.head_name" @input.debounce.300ms="lookHead()" autocomplete="off" placeholder="Full name" class="w-full rounded-2xl py-2.5 px-4 text-sm text-ink-body placeholder-slate-400 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                                <div x-show="heads.length && !headPicked" x-cloak class="mt-1.5 rounded-xl border border-slate-200 bg-white divide-y divide-slate-100 max-h-48 overflow-y-auto">
                                    <p class="px-3 pt-2 pb-1 text-[10.5px] font-semibold text-ink-muted">Already on file — pick to link them</p>
                                    <template x-for="r in heads" :key="r.ref">
                                        <button type="button" @click="pickHead(r)" class="w-full text-left px-3 py-2 hover:bg-indigo-50 transition-colors">
                                            <span class="block text-xs font-semibold text-ink truncate" x-text="r.name"></span>
                                            <span class="block text-[10.5px] text-ink-muted font-mono truncate" x-text="r.ref + (r.age != null ? ' · ' + r.age + ' yrs' : '')"></span>
                                        </button>
                                    </template>
                                </div>
                                <p x-show="headPicked" x-cloak class="text-[10.5px] text-emerald-700 mt-1" x-text="headPicked ? ('Linked to ' + head.ref + ', already a resident on file.') : ''"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Dwelling Type</label>
                                    <select aria-label="Dwelling Type" x-model="edit.dwelling_type" class="w-full rounded-2xl py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                                        <option value="Single Detached">Single Detached</option>
                                        <option value="Single Attached">Single Attached</option>
                                        <option value="Apartment">Apartment</option>
                                        <option value="Informal Settler">Informal Settler</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Housing Tenure</label>
                                    <select aria-label="Housing Tenure" x-model="edit.housing_tenure" class="w-full rounded-2xl py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
                                        <option value="Owner">Owner</option>
                                        <option value="Renter">Renter</option>
                                        <option value="Shared">Shared</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1.5">Flood Risk</label>
                                <select aria-label="Flood Risk" x-model="edit.flood_risk" class="w-full rounded-2xl py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all border-0 surface-pressed-sm">
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
                                <x-ui.button size="sm" icon="check" x-bind:disabled="saving" @click="saveEdit()" x-text="saving ? 'Saving…' : 'Save changes'"></x-ui.button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- ── MEMBERS TAB ── --}}
                <div x-show="householdTab === 'members'" x-cloak>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-ink">
                            All Household Members
                            <span
                                class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-700 font-semibold"
                                x-text="members.loading && !members.rows.length ? (selectedHousehold?.member_count ?? '…') : members.rows.length"
                            ></span>
                        </h3>
                    </div>

                    <x-ui.card class="overflow-hidden">
                        <template x-if="members.rows.length === 0">
                            <div class="py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <span x-show="members.loading"><i data-lucide="loader-circle" class="w-8 h-8 text-ink-faint animate-spin"></i></span>
                                    <span x-show="!members.loading"><i data-lucide="user-x" class="w-8 h-8 text-ink-faint"></i></span>
                                    <p x-show="members.loading" class="text-sm text-ink-muted">Loading members…</p>
                                    <p x-show="!members.loading && members.failed" class="text-sm text-rose-700">Could not load the members. <button type="button" class="underline" @click="loadMembers('household', selectedHousehold.ref)">Try again</button></p>
                                    <p x-show="!members.loading && !members.failed" class="text-sm text-ink-muted">No individuals linked to this household.</p>
                                </div>
                            </div>
                        </template>

                        <ul class="divide-y divide-brand-600/[.08]">
                            <template x-for="r in members.rows" :key="r.ref">
                                <li
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-indigo-50/40 cursor-pointer transition-colors"
                                    @click="openIndividual(r)"

        role="button"
        tabindex="0"
        @keydown.enter.prevent="openIndividual(r)"
        @keydown.space.prevent="openIndividual(r)">
                                    <div class="relative overflow-hidden w-8 h-8 text-xs rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0"><span x-text="(r.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></span><img x-show="photoOf(r.ref)" x-cloak :src="photoOf(r.ref)" alt="" class="absolute inset-0 w-full h-full object-cover" x-on:error="$el.style.display='none'"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-ink truncate" x-text="r.name"></p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs text-ink-muted" x-text="r.age ? (r.age + ' yrs') : ''"></span>
                                            <template x-if="r.sex"><span class="text-xs text-ink-muted" x-text="'· ' + r.sex"></span></template>
                                            <template x-if="r.relation"><span class="text-xs text-ink-muted font-medium" x-text="'· ' + r.relation"></span></template>
                                        </div>
                                    </div>
                                    <template x-if="r.family?.label">
                                        <span class="text-xs text-ink-muted bg-slate-100 px-2 py-0.5 rounded-full hidden sm:inline-flex" x-text="r.family.label"></span>
                                    </template>
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0"
                                        :class="verificationBadge(r.verification).class"
                                        x-text="verificationBadge(r.verification).label"
                                    ></span>
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-ink-faint flex-shrink-0"></i>
                                </li>
                            </template>
                        </ul>
                    </x-ui.card>
                </div>

                {{-- ── AMENITIES TAB ── --}}
                <div x-show="householdTab === 'amenities'" x-cloak>
                    <h3 class="text-sm font-semibold text-ink mb-4">Household Amenities</h3>

                    {{-- 3 amenity cards: what the household reported, in its own words
                         (QA sweep 2026-11-18: any word, None too, read Available). --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        {{-- Water --}}
                        <x-ui.card class="p-5 flex flex-col items-center text-center gap-3">
                            <div
                                class="w-16 h-16 rounded-2xl flex items-center justify-center"
                                :class="hasAmenity(selectedHousehold.water) ? 'bg-blue-100' : 'bg-slate-100'"
                            >
                                <i data-lucide="droplets" class="w-8 h-8" :class="hasAmenity(selectedHousehold.water) ? 'text-blue-500' : 'text-ink-faint'"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-ink">Water Source</p>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold mt-1.5"
                                    :class="hasAmenity(selectedHousehold.water) ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-ink-muted'"
                                >
                                    <i :data-lucide="hasAmenity(selectedHousehold.water) ? 'check-circle' : 'x-circle'" class="w-3.5 h-3.5"></i>
                                    <span x-text="{ yes: 'Available', no: 'Not Available', unknown: 'Not Recorded' }[amenityState(selectedHousehold.water)]"></span>
                                </span>
                                <p x-show="amenityNote(selectedHousehold.water)" class="text-xs text-ink-muted mt-1.5" x-text="amenityNote(selectedHousehold.water)"></p>
                            </div>
                        </x-ui.card>

                        {{-- Electricity --}}
                        <x-ui.card class="p-5 flex flex-col items-center text-center gap-3">
                            <div
                                class="w-16 h-16 rounded-2xl flex items-center justify-center"
                                :class="hasAmenity(selectedHousehold.electricity) ? 'bg-amber-100' : 'bg-slate-100'"
                            >
                                <i data-lucide="zap" class="w-8 h-8" :class="hasAmenity(selectedHousehold.electricity) ? 'text-amber-500' : 'text-ink-faint'"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-ink">Electricity</p>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold mt-1.5"
                                    :class="hasAmenity(selectedHousehold.electricity) ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-ink-muted'"
                                >
                                    <i :data-lucide="hasAmenity(selectedHousehold.electricity) ? 'check-circle' : 'x-circle'" class="w-3.5 h-3.5"></i>
                                    <span x-text="{ yes: 'Available', no: 'Not Available', unknown: 'Not Recorded' }[amenityState(selectedHousehold.electricity)]"></span>
                                </span>
                                <p x-show="amenityNote(selectedHousehold.electricity)" class="text-xs text-ink-muted mt-1.5" x-text="amenityNote(selectedHousehold.electricity)"></p>
                            </div>
                        </x-ui.card>

                        {{-- Toilet --}}
                        <x-ui.card class="p-5 flex flex-col items-center text-center gap-3">
                            <div
                                class="w-16 h-16 rounded-2xl flex items-center justify-center"
                                :class="hasAmenity(selectedHousehold.toilet) ? 'bg-emerald-100' : 'bg-slate-100'"
                            >
                                <i data-lucide="toilet" class="w-8 h-8" :class="hasAmenity(selectedHousehold.toilet) ? 'text-emerald-500' : 'text-ink-faint'"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-ink">Toilet Facility</p>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold mt-1.5"
                                    :class="hasAmenity(selectedHousehold.toilet) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-ink-muted'"
                                >
                                    <i :data-lucide="hasAmenity(selectedHousehold.toilet) ? 'check-circle' : 'x-circle'" class="w-3.5 h-3.5"></i>
                                    <span x-text="{ yes: 'Available', no: 'Not Available', unknown: 'Not Recorded' }[amenityState(selectedHousehold.toilet)]"></span>
                                </span>
                                <p x-show="amenityNote(selectedHousehold.toilet)" class="text-xs text-ink-muted mt-1.5" x-text="amenityNote(selectedHousehold.toilet)"></p>
                            </div>
                        </x-ui.card>
                    </div>

                    {{-- Flood risk block --}}
                    <h3 class="text-sm font-semibold text-ink mb-3">Disaster Risk Profile</h3>
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
                            <p class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]">Flood Risk Level</p>
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
            <div class="flex-shrink-0 px-6 py-4 border-t border-brand-600/10 bg-slate-50/50 flex items-center justify-end gap-3 rounded-b-2xl" x-show="!editing">
                <x-ui.button size="sm" icon="pencil" x-show="can('const_household','edit')" @click="startEdit()">Edit household</x-ui.button>
            </div>

        </div>
    </template>

    {{-- Loading placeholder --}}
    <template x-if="!selectedHousehold">
        <div class="flex items-center justify-center h-48 text-ink-muted text-sm">
            <i data-lucide="loader-circle" class="w-6 h-6 animate-spin mr-2"></i>
            Loading household details…
        </div>
    </template>

</x-ui.modal>
</div>

{{-- modal-add-household.blade.php --}}
{{-- Add Household modal — 3 steps --}}

<div x-data="{
    get open() { return showAddHousehold },
    set open(v) { showAddHousehold = v },
    submitting: false,
    form: {
        barangay: '',
        sitio: '',
        address: '',
        head_name: '',
        dwelling_type: '',
        housing_tenure: '',
        flood_risk: '',
        electricity: false,
        water: false,
        toilet: false,
    },
    barangayOptions: @js(config('esperanza.barangays')),
    get canProceed() {
        if (addHouseholdStep === 1) {
            return this.form.barangay !== '' && this.form.address.trim() !== '' && this.form.head_name.trim() !== ''
        }
        if (addHouseholdStep === 2) {
            return this.form.dwelling_type !== '' && this.form.housing_tenure !== '' && this.form.flood_risk !== ''
        }
        return true
    },
    async saveHousehold() {
        if (this.submitting) return;
        this.submitting = true;
        try {
            const res = await $api.post('/admin/households', this.form);
            this.households.push(res.data);
            this.$dispatch('toast', { message: 'Household record saved successfully.', variant: 'success' });
            showAddHousehold = false;
            addHouseholdStep = 1;
            this.form = {
                barangay: '', sitio: '', address: '', head_name: '',
                dwelling_type: '', housing_tenure: '', flood_risk: '',
                electricity: false, water: false, toilet: false,
            };
        } catch (err) {
            this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not create this household.', variant: 'error' });
        } finally { this.submitting = false; }
    }
}" x-init="$watch('open', v => { if (!v) addHouseholdStep = 1; })">
<x-ui.modal maxWidth="xl" :fullscreen="true">

    <div class="flex flex-col h-full max-h-[90vh]">

        {{-- ── Modal Header ── --}}
        <div class="flex-shrink-0 px-6 pt-6 pb-4 border-b border-slate-200">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-ink">Register New Household</h2>
                    <p class="text-sm text-ink-muted mt-0.5">Municipality of Esperanza, Masbate</p>
                </div>
                <button aria-label="Close" @click="showAddHousehold = false; addHouseholdStep = 1" class="p-2 rounded-xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Step indicator — 3 steps --}}
            <div class="flex items-center gap-0 mt-5">
                <template x-for="(step, idx) in [
                    {n:1, label:'Location'},
                    {n:2, label:'Dwelling'},
                    {n:3, label:'Amenities'}
                ]" :key="step.n">
                    <div class="flex items-center" :class="idx < 2 ? 'flex-1' : ''">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <div
                                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 transition-all"
                                :class="addHouseholdStep > step.n
                                    ? 'bg-emerald-500 text-white'
                                    : addHouseholdStep === step.n
                                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200'
                                        : 'bg-slate-100 text-ink-muted'"
                            >
                                <template x-if="addHouseholdStep > step.n">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </template>
                                <template x-if="addHouseholdStep <= step.n">
                                    <span x-text="step.n"></span>
                                </template>
                            </div>
                            <span
                                class="text-xs font-medium hidden sm:block transition-colors"
                                :class="addHouseholdStep === step.n ? 'text-indigo-600' : addHouseholdStep > step.n ? 'text-emerald-600' : 'text-ink-muted'"
                                x-text="step.label"
                            ></span>
                        </div>
                        <template x-if="idx < 2">
                            <div class="flex-1 h-px mx-2 sm:mx-3" :class="addHouseholdStep > step.n ? 'bg-emerald-300' : 'bg-slate-200'"></div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── Form Body (scrollable) ── --}}
        <div class="flex-1 overflow-y-auto px-6 py-5">

            {{-- ── STEP 1: Location & Identity ── --}}
            <div x-show="addHouseholdStep === 1" x-cloak class="space-y-5">
                <p class="text-xs text-ink-muted bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 flex items-start gap-2">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-indigo-500 mt-0.5 flex-shrink-0"></i>
                    Enter the location and primary occupant information. Fields marked <span class="text-rose-500 font-bold mx-0.5">*</span> are required.
                </p>

                {{-- Barangay + Sitio --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="barangay-3">
                            Barangay <span class="text-rose-500">*</span>
                        </label>
                        <select id="barangay-3"
                            x-model="form.barangay"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-ink focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        >
                            <option value="">Select barangay…</option>
                            <template x-for="b in barangayOptions" :key="b">
                                <option :value="b" x-text="b"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="sitio-purok-2">Sitio / Purok</label>
                        <input id="sitio-purok-2"
                            type="text"
                            x-model="form.sitio"
                            placeholder="e.g. Sitio Bonbon"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-ink placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        />
                    </div>
                </div>

                {{-- Street address --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="street-address">
                        Street Address <span class="text-rose-500">*</span>
                    </label>
                    <input id="street-address"
                        type="text"
                        x-model="form.address"
                        placeholder="e.g. Purok 3, Brgy. Esperanza"
                        class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-ink placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                    />
                </div>

                {{-- Household head --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="household-head-name">
                        Household Head Name <span class="text-rose-500">*</span>
                    </label>
                    <input id="household-head-name"
                        type="text"
                        x-model="form.head_name"
                        placeholder="Full name of household head"
                        class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-ink placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                    />
                    <p class="text-xs text-ink-muted mt-1.5">This person will be listed as the primary occupant of this household. If they aren't already a resident on file, a new resident record is created for them.</p>
                </div>
            </div>

            {{-- ── STEP 2: Dwelling & Risk ── --}}
            <div x-show="addHouseholdStep === 2" x-cloak class="space-y-5">
                <p class="text-xs text-ink-muted bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 flex items-start gap-2">
                    <i data-lucide="building-2" class="w-3.5 h-3.5 text-ink-muted mt-0.5 flex-shrink-0"></i>
                    Describe the dwelling type, ownership, and disaster risk classification.
                </p>

                {{-- Dwelling type radios --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">
                        Dwelling Type <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="dt in [
                            {val:'Single Detached', icon:'home', desc:'Standalone structure'},
                            {val:'Single Attached', icon:'building', desc:'Shares one wall'},
                            {val:'Apartment', icon:'building-2', desc:'Multi-family building'},
                            {val:'Informal Settler', icon:'tent', desc:'Informal settlement'}
                        ]" :key="dt.val">
                            <label
                                class="relative flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
                                :class="form.dwelling_type === dt.val
                                    ? 'border-indigo-500 bg-indigo-50'
                                    : 'border-slate-200 bg-white hover:border-slate-300'"
                            >
                                <input type="radio" x-model="form.dwelling_type" :value="dt.val" class="sr-only" />
                                <i :data-lucide="dt.icon" class="w-4 h-4 flex-shrink-0" :class="form.dwelling_type === dt.val ? 'text-indigo-600' : 'text-ink-muted'"></i>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold" :class="form.dwelling_type === dt.val ? 'text-indigo-700' : 'text-slate-700'" x-text="dt.val"></p>
                                    <p class="text-xs text-ink-muted" x-text="dt.desc"></p>
                                </div>
                                <template x-if="form.dwelling_type === dt.val">
                                    <div class="absolute top-2 right-2 w-4 h-4 bg-indigo-600 rounded-full flex items-center justify-center">
                                        <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                    </div>
                                </template>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Housing tenure radios --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">
                        Housing Tenure <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="ht in [
                            {val:'Owner', icon:'key', desc:'Owns the property'},
                            {val:'Renter', icon:'banknote', desc:'Paying rent'},
                            {val:'Shared', icon:'handshake', desc:'Shared occupancy'}
                        ]" :key="ht.val">
                            <label
                                class="relative flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 cursor-pointer transition-all text-center"
                                :class="form.housing_tenure === ht.val
                                    ? 'border-indigo-500 bg-indigo-50'
                                    : 'border-slate-200 bg-white hover:border-slate-300'"
                            >
                                <input type="radio" x-model="form.housing_tenure" :value="ht.val" class="sr-only" />
                                <i :data-lucide="ht.icon" class="w-5 h-5" :class="form.housing_tenure === ht.val ? 'text-indigo-600' : 'text-ink-muted'"></i>
                                <span class="text-xs font-semibold" :class="form.housing_tenure === ht.val ? 'text-indigo-700' : 'text-slate-600'" x-text="ht.val"></span>
                                <template x-if="form.housing_tenure === ht.val">
                                    <div class="absolute top-1.5 right-1.5 w-3.5 h-3.5 bg-indigo-600 rounded-full flex items-center justify-center">
                                        <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                    </div>
                                </template>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Flood risk radios --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">
                        Flood Risk Level <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="fr in [
                            {val:'Low', activeCls:'border-emerald-500 bg-emerald-50', iconCls:'text-emerald-600', textCls:'text-emerald-700', dotCls:'bg-emerald-500'},
                            {val:'Medium', activeCls:'border-amber-500 bg-amber-50', iconCls:'text-amber-600', textCls:'text-amber-700', dotCls:'bg-amber-500'},
                            {val:'High', activeCls:'border-rose-500 bg-rose-50', iconCls:'text-rose-600', textCls:'text-rose-700', dotCls:'bg-rose-500'}
                        ]" :key="fr.val">
                            <label
                                class="relative flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 cursor-pointer transition-all text-center"
                                :class="form.flood_risk === fr.val ? fr.activeCls : 'border-slate-200 bg-white hover:border-slate-300'"
                            >
                                <input type="radio" x-model="form.flood_risk" :value="fr.val" class="sr-only" />
                                <i data-lucide="waves-horizontal" class="w-5 h-5" :class="form.flood_risk === fr.val ? fr.iconCls : 'text-ink-muted'"></i>
                                <span class="text-xs font-semibold" :class="form.flood_risk === fr.val ? fr.textCls : 'text-slate-600'" x-text="fr.val"></span>
                                <template x-if="form.flood_risk === fr.val">
                                    <div class="absolute top-1.5 right-1.5 w-3.5 h-3.5 rounded-full flex items-center justify-center" :class="fr.dotCls">
                                        <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                    </div>
                                </template>
                            </label>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ── STEP 3: Amenities ── --}}
            <div x-show="addHouseholdStep === 3" x-cloak class="space-y-5">
                <p class="text-xs text-ink-muted bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 flex items-start gap-2">
                    <i data-lucide="plug-zap" class="w-3.5 h-3.5 text-ink-muted mt-0.5 flex-shrink-0"></i>
                    Indicate available basic amenities.
                </p>

                {{-- Amenities checkboxes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Basic Amenities</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {{-- Electricity --}}
                        <label
                            class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all text-center"
                            :class="form.electricity ? 'border-amber-400 bg-amber-50' : 'border-slate-200 bg-white hover:border-slate-300'"
                        >
                            <input type="checkbox" x-model="form.electricity" class="sr-only" />
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="form.electricity ? 'bg-amber-100' : 'bg-slate-100'">
                                <i data-lucide="zap" class="w-5 h-5" :class="form.electricity ? 'text-amber-500' : 'text-ink-muted'"></i>
                            </div>
                            <span class="text-xs font-semibold" :class="form.electricity ? 'text-amber-700' : 'text-slate-600'">Has Electricity</span>
                            <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                :class="form.electricity ? 'bg-amber-500 border-amber-500' : 'border-slate-300'">
                                <i x-show="form.electricity" data-lucide="check" class="w-3 h-3 text-white"></i>
                            </div>
                        </label>

                        {{-- Water --}}
                        <label
                            class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all text-center"
                            :class="form.water ? 'border-blue-400 bg-blue-50' : 'border-slate-200 bg-white hover:border-slate-300'"
                        >
                            <input type="checkbox" x-model="form.water" class="sr-only" />
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="form.water ? 'bg-blue-100' : 'bg-slate-100'">
                                <i data-lucide="droplets" class="w-5 h-5" :class="form.water ? 'text-blue-500' : 'text-ink-muted'"></i>
                            </div>
                            <span class="text-xs font-semibold" :class="form.water ? 'text-blue-700' : 'text-slate-600'">Has Water Source</span>
                            <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                :class="form.water ? 'bg-blue-500 border-blue-500' : 'border-slate-300'">
                                <i x-show="form.water" data-lucide="check" class="w-3 h-3 text-white"></i>
                            </div>
                        </label>

                        {{-- Toilet --}}
                        <label
                            class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all text-center"
                            :class="form.toilet ? 'border-emerald-400 bg-emerald-50' : 'border-slate-200 bg-white hover:border-slate-300'"
                        >
                            <input type="checkbox" x-model="form.toilet" class="sr-only" />
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="form.toilet ? 'bg-emerald-100' : 'bg-slate-100'">
                                <i data-lucide="toilet" class="w-5 h-5" :class="form.toilet ? 'text-emerald-500' : 'text-ink-muted'"></i>
                            </div>
                            <span class="text-xs font-semibold" :class="form.toilet ? 'text-emerald-700' : 'text-slate-600'">Has Toilet Facility</span>
                            <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                :class="form.toilet ? 'bg-emerald-500 border-emerald-500' : 'border-slate-300'">
                                <i x-show="form.toilet" data-lucide="check" class="w-3 h-3 text-white"></i>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Summary preview --}}
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r mb-2.5">Household Summary</h4>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                        <div><dt class="text-ink-muted">Barangay</dt><dd class="font-semibold text-ink truncate" x-text="form.barangay || '—'"></dd></div>
                        <div><dt class="text-ink-muted">Head</dt><dd class="font-semibold text-ink truncate" x-text="form.head_name || '—'"></dd></div>
                        <div><dt class="text-ink-muted">Dwelling</dt><dd class="font-semibold text-ink" x-text="form.dwelling_type || '—'"></dd></div>
                        <div><dt class="text-ink-muted">Flood Risk</dt><dd class="font-semibold text-ink" x-text="form.flood_risk || '—'"></dd></div>
                        <div class="col-span-2"><dt class="text-ink-muted">Amenities</dt>
                            <dd class="font-semibold text-ink">
                                <span x-text="[form.electricity ? 'Elec' : null, form.water ? 'Water' : null, form.toilet ? 'Toilet' : null].filter(Boolean).join(', ') || 'None'"></span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

        </div>

        {{-- ── Modal Footer ── --}}
        <div class="flex-shrink-0 px-6 py-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between gap-3 rounded-b-2xl">
            {{-- Back / Cancel --}}
            <button
                @click="addHouseholdStep > 1 ? addHouseholdStep-- : (showAddHousehold = false)"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-white/60 transition-colors shadow-sm"
            >
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span x-text="addHouseholdStep > 1 ? 'Back' : 'Cancel'"></span>
            </button>

            {{-- Step counter --}}
            <span class="text-xs text-ink-muted font-medium hidden sm:block">
                Step <span x-text="addHouseholdStep"></span> of 3
            </span>

            {{-- Next (steps 1–2) --}}
            <button
                x-show="addHouseholdStep < 3"
                @click="canProceed && addHouseholdStep++"
                :disabled="!canProceed"
                class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-200 disabled:text-ink-faint disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl shadow-sm transition-colors"
            >
                Next
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>

            {{-- Save (step 3) --}}
            <button
                x-show="addHouseholdStep === 3"
                :disabled="submitting"
                @click="saveHousehold()"
                class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-sm font-medium rounded-xl shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            >
                <i data-lucide="loader-circle" class="w-4 h-4 animate-spin" x-show="submitting"></i>
                <i data-lucide="save" class="w-4 h-4" x-show="!submitting"></i>
                <span x-text="submitting ? 'Saving…' : 'Save Household'"></span>
            </button>
        </div>

    </div>

</x-ui.modal>
</div>

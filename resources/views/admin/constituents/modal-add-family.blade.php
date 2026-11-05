{{-- modal-add-family.blade.php --}}
{{-- Add Family modal — 2 steps --}}

<div x-data="{
    get open() { return showAddFamily },
    set open(v) { showAddFamily = v },
    submitting: false,
    form: {
        surname: '',
        family_type: 'Nuclear',
        barangay: '',
        sitio: '',
        head_name: '',
        household_ref: '',
        notes: '',
        programs: []
    },
    programOptions: ['4Ps (Pantawid Pamilyang Pilipino Program)', 'OSCA (Senior Citizen)', 'PWD Assistance', 'Solo Parent Welfare', 'Other Government Assistance'],
    barangayOptions: @js(config('esperanza.barangays')),
    get canProceed() {
        if (addFamilyStep === 1) {
            return this.form.surname.trim() !== '' && this.form.family_type !== '' && this.form.barangay !== ''
        }
        return true
    },
    async saveFamily() {
        if (this.submitting) return;
        this.submitting = true;
        try {
            const res = await $api.post('/admin/families', this.form);
            this.families.push(res.data);
            this.$dispatch('toast', { message: 'Family record saved successfully.', variant: 'success' });
            showAddFamily = false;
            addFamilyStep = 1;
            this.form = { surname: '', family_type: 'Nuclear', barangay: '', sitio: '', head_name: '', household_ref: '', notes: '', programs: [] };
        } catch (err) {
            this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not create this family.', variant: 'error' });
        } finally { this.submitting = false; }
    }
}" x-init="$watch('open', v => { if (!v) addFamilyStep = 1; })">
<x-ui.modal maxWidth="xl" :fullscreen="true">

    <div class="flex flex-col h-full max-h-[85vh]">

        {{-- ── Modal Header ── --}}
        <div class="flex-shrink-0 px-6 pt-6 pb-4 border-b border-slate-200">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-ink">Add New Family</h2>
                    <p class="text-sm text-ink-muted mt-0.5">Municipality of Esperanza, Masbate</p>
                </div>
                <button aria-label="Close" @click="showAddFamily = false; addFamilyStep = 1" class="p-2 rounded-xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Step indicator --}}
            <div class="flex items-center gap-0 mt-5">
                <template x-for="(step, idx) in [{n:1,label:'Family Info'},{n:2,label:'Programs & Notes'}]" :key="step.n">
                    <div class="flex items-center" :class="idx < 1 ? 'flex-1' : ''">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                                :class="addFamilyStep > step.n
                                    ? 'bg-emerald-500 text-white'
                                    : addFamilyStep === step.n
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-200'
                                        : 'bg-slate-100 text-ink-muted'"
                            >
                                <template x-if="addFamilyStep > step.n">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </template>
                                <template x-if="addFamilyStep <= step.n">
                                    <span x-text="step.n"></span>
                                </template>
                            </div>
                            <span
                                class="text-xs font-medium hidden sm:block transition-colors"
                                :class="addFamilyStep === step.n ? 'text-blue-600' : addFamilyStep > step.n ? 'text-emerald-600' : 'text-ink-muted'"
                                x-text="step.label"
                            ></span>
                        </div>
                        <template x-if="idx === 0">
                            <div class="flex-1 h-px mx-3" :class="addFamilyStep > 1 ? 'bg-emerald-300' : 'bg-slate-200'"></div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── Form Body (scrollable) ── --}}
        <div class="flex-1 overflow-y-auto px-6 py-5">

            {{-- ── STEP 1: Family Information ── --}}
            <div x-show="addFamilyStep === 1" x-cloak class="space-y-5">
                <p class="text-xs text-ink-muted bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 flex items-start gap-2">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-blue-500 mt-0.5 flex-shrink-0"></i>
                    Enter the core details for the new family record. Fields marked with <span class="text-rose-500 font-bold mx-0.5">*</span> are required.
                </p>

                {{-- Surname --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="family-surname">
                        Family Surname <span class="text-rose-500">*</span>
                    </label>
                    <input id="family-surname"
                        type="text"
                        x-model="form.surname"
                        placeholder="e.g. Santos"
                        class="w-full px-3 py-2.5 text-sm rounded-xl text-ink placeholder-slate-400 focus:outline-none focus:ring-blue-500 focus:border-transparent transition border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"
                    />
                </div>

                {{-- Family type --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">
                        Family Type <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="ft in [{val:'Nuclear',icon:'users',desc:'Parents & children'},{val:'Extended',icon:'users-round',desc:'Includes relatives'},{val:'Single Parent',icon:'user',desc:'One-parent household'}]" :key="ft.val">
                            <label
                                class="relative flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 cursor-pointer transition-all text-center"
                                :class="form.family_type === ft.val
                                    ? 'border-blue-500 bg-blue-50'
                                    : 'border-slate-200 bg-white hover:border-slate-300'"
                            >
                                <input type="radio" x-model="form.family_type" :value="ft.val" class="sr-only" />
                                <i :data-lucide="ft.icon" class="w-5 h-5" :class="form.family_type === ft.val ? 'text-blue-600' : 'text-ink-muted'"></i>
                                <span class="text-xs font-semibold" :class="form.family_type === ft.val ? 'text-blue-700' : 'text-slate-600'" x-text="ft.val"></span>
                                <span class="text-xs text-ink-muted leading-tight" x-text="ft.desc"></span>
                                <template x-if="form.family_type === ft.val">
                                    <div class="absolute top-1.5 right-1.5 w-3.5 h-3.5 bg-blue-600 rounded-full flex items-center justify-center">
                                        <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                    </div>
                                </template>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Barangay + Sitio --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="barangay-2">
                            Barangay <span class="text-rose-500">*</span>
                        </label>
                        <select id="barangay-2"
                            x-model="form.barangay"
                            class="w-full px-3 py-2.5 text-sm rounded-xl text-ink focus:outline-none focus:ring-blue-500 focus:border-transparent transition border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"
                        >
                            <option value="">Select barangay…</option>
                            <template x-for="b in barangayOptions" :key="b">
                                <option :value="b" x-text="b"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="sitio-purok">Sitio / Purok</label>
                        <input id="sitio-purok"
                            type="text"
                            x-model="form.sitio"
                            placeholder="e.g. Sitio Magsaysay"
                            class="w-full px-3 py-2.5 text-sm rounded-xl text-ink placeholder-slate-400 focus:outline-none focus:ring-blue-500 focus:border-transparent transition border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"
                        />
                    </div>
                </div>

                {{-- Head of Family + Household reference --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="head-of-family">Head of Family</label>
                        <input id="head-of-family"
                            type="text"
                            x-model="form.head_name"
                            placeholder="Full name of head"
                            class="w-full px-3 py-2.5 text-sm rounded-xl text-ink placeholder-slate-400 focus:outline-none focus:ring-blue-500 focus:border-transparent transition border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"
                        />
                        <p class="text-[10.5px] text-ink-muted mt-1">If they aren't already a resident on file, a new resident record is created for them.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="link-to-household-ref-optional">Link to Household <span class="text-ink-muted font-normal">(optional)</span></label>
                        <input id="link-to-household-ref-optional"
                            type="text"
                            x-model="form.household_ref"
                            placeholder="e.g. HH-2026-0001"
                            class="w-full px-3 py-2.5 text-sm rounded-xl text-ink placeholder-slate-400 focus:outline-none focus:ring-blue-500 focus:border-transparent transition border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"
                        />
                    </div>
                </div>
            </div>

            {{-- ── STEP 2: Programs & Notes ── --}}
            <div x-show="addFamilyStep === 2" x-cloak class="space-y-5">
                <p class="text-xs text-ink-muted bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 flex items-start gap-2">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-ink-muted mt-0.5 flex-shrink-0"></i>
                    Optionally enroll this family in government programs and add any relevant remarks.
                </p>

                {{-- Programs checkboxes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Government Programs</label>
                    <div class="space-y-2">
                        <template x-for="prog in programOptions" :key="prog">
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-white hover:bg-white/60 cursor-pointer transition-colors"
                                :class="form.programs.includes(prog) ? 'border-emerald-300 bg-emerald-50/50' : ''">
                                <input type="checkbox" :value="prog" x-model="form.programs"
                                    class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500" />
                                <span class="text-sm text-ink" x-text="prog"></span>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="notes-remarks">Notes / Remarks</label>
                    <textarea id="notes-remarks"
                        x-model="form.notes"
                        rows="4"
                        placeholder="Any additional notes about this family…"
                        class="w-full px-3 py-2.5 text-sm rounded-xl text-ink placeholder-slate-400 focus:outline-none focus:ring-blue-500 focus:border-transparent transition resize-none border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100"
                    ></textarea>
                </div>

                {{-- Summary preview --}}
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h4 class="text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] mb-2.5">Summary</h4>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                        <div><dt class="text-ink-muted">Surname</dt><dd class="font-semibold text-ink truncate" x-text="form.surname || '—'"></dd></div>
                        <div><dt class="text-ink-muted">Type</dt><dd class="font-semibold text-ink" x-text="form.family_type"></dd></div>
                        <div><dt class="text-ink-muted">Barangay</dt><dd class="font-semibold text-ink truncate" x-text="form.barangay || '—'"></dd></div>
                        <div><dt class="text-ink-muted">Head</dt><dd class="font-semibold text-ink truncate" x-text="form.head_name || '—'"></dd></div>
                        <div class="col-span-2"><dt class="text-ink-muted">Programs</dt><dd class="font-semibold text-ink" x-text="form.programs.length > 0 ? form.programs.length + ' selected' : 'None'"></dd></div>
                    </dl>
                </div>
            </div>

        </div>

        {{-- ── Modal Footer ── --}}
        <div class="flex-shrink-0 px-6 py-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between gap-3 rounded-b-2xl">
            {{-- Back / Cancel --}}
            <button
                @click="addFamilyStep > 1 ? addFamilyStep-- : (showAddFamily = false)"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-white/60 transition-colors shadow-sm"
            >
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span x-text="addFamilyStep > 1 ? 'Back' : 'Cancel'"></span>
            </button>

            {{-- Next / Save --}}
            <button
                x-show="addFamilyStep < 2"
                @click="canProceed && addFamilyStep++"
                :disabled="!canProceed"
                class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-200 disabled:text-ink-faint disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl shadow-sm transition-colors"
            >
                Next
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>

            <button
                x-show="addFamilyStep === 2"
                :disabled="submitting"
                @click="saveFamily()"
                class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-sm font-medium rounded-xl shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            >
                <i data-lucide="loader-circle" class="w-4 h-4 animate-spin" x-show="submitting"></i>
                <i data-lucide="save" class="w-4 h-4" x-show="!submitting"></i>
                <span x-text="submitting ? 'Saving…' : 'Save family'"></span>
            </button>
        </div>

    </div>

</x-ui.modal>
</div>

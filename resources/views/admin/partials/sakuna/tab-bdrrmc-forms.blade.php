{{--
    tab-bdrrmc-forms.blade.php
    Exact-replica BDRRMC capture forms supplied by Barangay Labangtaytay —
    Profile of Evacuees, Human & Housing Damages, Agriculture Damages,
    Fishing Damages, Business Damages, and the Relief Distribution Sheet (RDS).
    Included by sakuna.blade.php — local x-data scoped here, parent state untouched.
--}}

@php
    // TAB 04: fixtures live in resources/data/admin/partials/sakuna/tab-bdrrmc-forms.php so this view stays presentation only.
    $__fixtures = require base_path('resources/data/admin/partials/sakuna/tab-bdrrmc-forms.php');
    $bdrrmcForms = $__fixtures['bdrrmcForms'];
@endphp

<div
    x-data="{
        // The BDRRMC capture forms, stored on the server
        // (POST /admin/sakuna/bdrrmc-forms/{key}/submit). They opened filled
        // with sample families from one barangay, under hard-coded names, and
        // Save only showed a toast: nothing was stored.
        openForm: null,
        disasterType: '',
        purok: '',
        center: '',
        occurred: '',
        barangay: '',
        barangayLocked: false,
        barangays: [],
        signoff: { prepared: '', consolidated: '', noted: '' },
        saving: false,
        submissions: [],
        submissionsLoading: false,
        formNames: @js(collect($bdrrmcForms)->pluck('name', 'key')),
        rowsOf: { evacuees: 'evacueeRows', 'human-housing': 'housingRows', agriculture: 'agriRows', fishing: 'fishingRows', business: 'businessRows', rds: 'rdsRows' },

        async init() {
            const account = this.$store.session.account || {};
            this.barangayLocked = !!account.scope && account.scope !== 'Municipal';
            this.barangay = this.barangayLocked ? account.scope : '';
            this.signoff.prepared = account.name || '';
            Object.keys(this.rowsOf).forEach((key) => this.resetRows(key));
            try {
                this.barangays = (await $api.get('/barangays')).data || [];
            } catch { this.barangays = []; }
            this.fillOfficials();
            this.loadSubmissions();
        },
        // The barangay's own Punong Barangay and Secretary sign the form.
        fillOfficials() {
            const b = this.barangays.find((x) => x.name === this.barangay);
            this.signoff.noted = b?.officials?.punong_barangay || b?.punong_barangay || '';
            this.signoff.consolidated = b?.officials?.barangay_secretary || b?.barangay_secretary || '';
        },
        resetRows(key) {
            const name = this.rowsOf[key];
            this[name] = [];
            this.blankRow(key);
        },
        blankRow(key) {
            const blanks = {
                'evacuees': () => ({ purok:'', hh:'', head:'', pre03m:0,pre03f:0,pre46m:0,pre46f:0,ch712m:0,ch712f:0,yth1317m:0,yth1317f:0,ad1859m:0,ad1859f:0,sc60m:0,sc60f:0, assist:'' }),
                'human-housing': () => ({ hh:'', head:'', deps:0, cas_m:0,cas_f:0, miss_m:0,miss_f:0, resc_m:0,resc_f:0, roof_slight_nipa:0,roof_slight_gi:0, roof_partial_nipa:0,roof_partial_gi:0, roof_total_nipa:0,roof_total_gi:0, flood_height:'', est_partial:0, est_total:0 }),
                'agriculture': () => ({ hh:'', owner:'', livestock_type:'', heads:'', age:'', crop:'', stage:'', area_affected:'', product:'', loss_recovery:'', metric_tons:'' }),
                'fishing': () => ({ hh:'', owner:'', qty:'', gear:'', damage:'', est_damage:'' }),
                'business': () => ({ code:'', owner:'', establishment:'', type:'', workers:'', merch:'', equip:'', struct:'', flood_height:'', est_damage:'' }),
                'rds': () => ({ hh:'', beneficiary:'', deps:'', kind:'', qty:'', received:false }),
            };
            this[this.rowsOf[key]].push(blanks[key]());
        },
        evacueeRows: [],
        addEvacueeRow() { this.blankRow('evacuees'); },
        housingRows: [],
        addHousingRow() { this.blankRow('human-housing'); },
        agriRows: [],
        addAgriRow() { this.blankRow('agriculture'); },
        fishingRows: [],
        addFishingRow() { this.blankRow('fishing'); },
        businessRows: [],
        addBusinessRow() { this.blankRow('business'); },
        rdsRows: [],
        addRdsRow() { this.blankRow('rds'); },
        evacueeFamTotal(r) { return (r.pre03m+r.pre03f+r.pre46m+r.pre46f+r.ch712m+r.ch712f+r.yth1317m+r.yth1317f+r.ad1859m+r.ad1859f+r.sc60m+r.sc60f) || 0; },
        evacueeGrandTotal() { return this.evacueeRows.reduce((s,r) => s + this.evacueeFamTotal(r), 0); },

        // A row nobody touched is not sent; the server checks each sent row's
        // required columns and names the row and the column it lacks.
        isBlankRow(r) { return Object.values(r).every((v) => v === '' || v === 0 || v === false || v === null); },
        print() { window.print(); },
        async save() {
            const key = this.openForm;
            const rows = (this[this.rowsOf[key]] || []).filter((r) => !this.isBlankRow(r));
            const fail = (message) => this.$dispatch('toast', { message, variant: 'error' });
            if (!this.barangay) return fail('Choose the barangay first.');
            if (!rows.length) return fail('Fill in at least one row before saving.');
            if (!this.signoff.prepared.trim()) return fail('Type the name of the person who prepared the form.');
            this.saving = true;
            try {
                const res = await $api.post('/admin/sakuna/bdrrmc-forms/' + key + '/submit', {
                    barangay: this.barangay,
                    period: this.disasterType.trim() || null,
                    rows,
                    meta: {
                        prepared_by: this.signoff.prepared.trim(),
                        consolidated_by: this.signoff.consolidated.trim() || null,
                        noted_by: this.signoff.noted.trim() || null,
                        // Today in the officer's own time zone (toISOString is UTC: a
                        // form saved before 8 AM in Manila was dated the day before).
                        date: new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10),
                        disaster: this.disasterType.trim() || null,
                        purok: this.purok.trim() || null,
                        evacuation_center: this.center.trim() || null,
                        occurred_on: this.occurred || null,
                        region: 'Region V',
                    },
                });
                this.$dispatch('toast', { message: 'Saved as ' + res.data.ref + ' (' + rows.length + ' row' + (rows.length === 1 ? '' : 's') + '). It is listed under Submitted forms.', variant: 'success' });
                this.resetRows(key);
                this.openForm = null;
                this.loadSubmissions();
            } catch (err) {
                fail(err && err.explain ? err.explain('en') : 'Could not save the form.');
            } finally {
                this.saving = false;
            }
        },
        get canListSubmissions() { return this.$store.session.canPerm('sakuna_reports', 'view'); },
        get canApprove() { return this.$store.session.canPerm('sakuna_damage', 'approve'); },
        async loadSubmissions() {
            if (!this.canListSubmissions) return;
            this.submissionsLoading = true;
            try {
                this.submissions = (await $api.get('/admin/sakuna/bdrrmc-forms/submissions', { params: { sort: '-created_at' } })).data || [];
            } catch { this.submissions = []; }
            finally { this.submissionsLoading = false; this.$nextTick(() => window.renderIcons?.()); }
        },
        async approve(sub) {
            try {
                await $api.post('/admin/sakuna/bdrrmc-forms/submissions/' + encodeURIComponent(sub.ref) + '/approve');
                this.$dispatch('toast', { message: sub.ref + ' approved.', variant: 'success' });
                this.loadSubmissions();
            } catch (err) {
                this.$dispatch('toast', { message: err && err.explain ? err.explain('en') : 'Could not approve.', variant: 'error' });
            }
        },
    }"
    class="space-y-5"
>
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-lg font-bold text-ink-body">BDRRMC Capture Forms</h2>
            <p class="text-sm text-ink-muted mt-0.5">The barangay DRRM capture forms (the layout Barangay Labangtaytay files with the MDRRMO/PDRRMC): fill one out, save it to the system, and print it.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        @foreach($bdrrmcForms as $f)
            <div class="surface-glass rounded-[26px] p-4 hover:shadow-glass-hover transition-shadow">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl {{ $f['color'] }} flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $f['icon'] }}" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">{{ $f['code'] }}</p>
                        <p class="text-sm font-semibold text-ink-body leading-snug">{{ $f['name'] }}</p>
                    </div>
                </div>
                <p class="text-xs text-ink-muted leading-relaxed mb-3">{{ $f['desc'] }}</p>
                <button
                    @click="openForm = '{{ $f['key'] }}'; $nextTick(() => window.renderIcons?.())"
                    class="w-full text-xs font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-lg py-2 transition-colors"
                >
                    Open Capture Form
                </button>
            </div>
        @endforeach
    </div>

    <div x-show="canListSubmissions" x-cloak class="surface-glass rounded-[26px]">
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-brand-600/10">
            <div>
                <h3 class="text-sm font-semibold text-ink-body">Submitted forms</h3>
                <p class="text-xs text-ink-muted">Saved capture forms, newest first. The MDRRMO approves each one.</p>
            </div>
            <button type="button" @click="loadSubmissions()" class="inline-flex items-center gap-1.5 text-xs text-slate-600 bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 hover:border-slate-300"><i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Refresh</button>
        </div>
        <p x-show="submissionsLoading" class="px-4 py-3 text-xs text-ink-muted">Loading…</p>
        <p x-show="!submissionsLoading && !submissions.length" class="px-4 py-6 text-center text-xs text-ink-muted">No capture form has been saved yet.</p>
        <div x-show="submissions.length" class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-[10px] uppercase tracking-wide text-ink-muted border-b border-brand-600/10">
                        <th class="px-4 py-2 font-semibold">Reference</th>
                        <th class="px-4 py-2 font-semibold">Form</th>
                        <th class="px-4 py-2 font-semibold">Barangay</th>
                        <th class="px-4 py-2 font-semibold">Disaster</th>
                        <th class="px-4 py-2 font-semibold text-right">Rows</th>
                        <th class="px-4 py-2 font-semibold">Saved</th>
                        <th class="px-4 py-2 font-semibold">Status</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-600/[.08]">
                    <template x-for="sub in submissions" :key="sub.ref">
                        <tr>
                            <td class="px-4 py-2 font-mono text-slate-700" x-text="sub.ref"></td>
                            <td class="px-4 py-2 text-slate-700" x-text="formNames[sub.form] || sub.form"></td>
                            <td class="px-4 py-2 text-slate-700" x-text="'Brgy. ' + sub.barangay"></td>
                            <td class="px-4 py-2 text-ink-muted" x-text="sub.period || '—'"></td>
                            <td class="px-4 py-2 text-right tabular-nums text-slate-700" x-text="sub.row_count"></td>
                            <td class="px-4 py-2 text-ink-muted" x-text="(sub.submitted_by || '—') + ' · ' + (sub.submitted_at ? new Date(sub.submitted_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '')"></td>
                            <td class="px-4 py-2"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset" :class="sub.status === 'Approved' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-blue-50 text-blue-700 ring-blue-200'" x-text="sub.status"></span></td>
                            <td class="px-4 py-2 text-right"><button type="button" x-show="canApprove && sub.status === 'Submitted'" @click="approve(sub)" class="text-xs font-medium text-brand-600 hover:underline">Approve</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Print: only the open capture form, its whole table, without the buttons. --}}
    <style>
        @media print {
            body * { visibility: hidden !important; }
            [data-print-area], [data-print-area] * { visibility: visible !important; }
            [data-print-area] { position: absolute !important; inset: 0 !important; height: auto !important; box-shadow: none !important; }
            [data-print-area] .overflow-y-auto, [data-print-area] .overflow-x-auto { overflow: visible !important; height: auto !important; }
            [data-print-area] .no-print, [data-print-area] button { display: none !important; }
        }
    </style>

    {{-- ══════════════════════════════════════ 1. PROFILE OF EVACUEES ══════════════════════════════════════ --}}
    <template x-if="openForm === 'evacuees'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">Dromic Form 1.2</p>
                    <h3 class="font-semibold text-ink-body">Profile of Evacuees Inside the Evacuation Center</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Region</label><input aria-label="Region" type="text" value="Region V" readonly class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Type of Disaster</label><input aria-label="Type of Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Name of Evacuation Center</label><input aria-label="Name of Evacuation Center" type="text" x-model="center" placeholder="e.g. Barangay Hall" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-sky-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="2">Purok</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="2">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="2">Family Head</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Pre-Schooler 0-3</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Pre-Schooler 4-6</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Children 7-12</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Youth 13-17</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Adult 18-59</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Senior 60+</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted" rowspan="2">Total</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="2">Assistance Extended</th>
                                <th class="px-2 py-2" rowspan="2"></th>
                            </tr>
                            <tr class="bg-sky-50/70 border-b border-slate-200 text-[10px] text-sky-600">
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in evacueeRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input aria-label="Purok" type="text" x-model="r.purok" class="w-12 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="HH Code" type="text" x-model="r.hh" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Family Head" type="text" x-model="r.head" class="w-36 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Pre-Schooler 0-3 M" type="number" min="0" x-model.number="r.pre03m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Pre-Schooler 0-3 F" type="number" min="0" x-model.number="r.pre03f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Pre-Schooler 4-6 M" type="number" min="0" x-model.number="r.pre46m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Pre-Schooler 4-6 F" type="number" min="0" x-model.number="r.pre46f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Children 7-12 M" type="number" min="0" x-model.number="r.ch712m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Children 7-12 F" type="number" min="0" x-model.number="r.ch712f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Youth 13-17 M" type="number" min="0" x-model.number="r.yth1317m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Youth 13-17 F" type="number" min="0" x-model.number="r.yth1317f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Adult 18-59 M" type="number" min="0" x-model.number="r.ad1859m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Adult 18-59 F" type="number" min="0" x-model.number="r.ad1859f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Senior 60+ M" type="number" min="0" x-model.number="r.sc60m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Senior 60+ F" type="number" min="0" x-model.number="r.sc60f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-2 py-1 text-center font-semibold text-slate-700" x-text="evacueeFamTotal(r)"></td>
                                    <td class="px-1 py-1"><input aria-label="Assistance Extended" type="text" x-model="r.assist" class="w-32 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><button aria-label="Remove" @click="evacueeRows.splice(ri,1)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-700">
                                <td colspan="15" class="px-2 py-2 text-right">Grand Total</td>
                                <td class="px-2 py-2 text-center" x-text="evacueeGrandTotal()"></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button @click="addEvacueeRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Family Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Barangay Official (Evacuation Center)" consolidated-label="Barangay Secretary" noted-label="Punong Barangay" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" :disabled="saving" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-2xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> <span x-text="saving ? 'Saving…' : 'Save Form'"></span></button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════ 2. HUMAN, HOUSING & FLOODING ══════════════════════════════════ --}}
    <template x-if="openForm === 'human-housing'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-ink-body">"Human, Housing & Flooding" Calamity/Damages Capture and Assessment Form</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Purok</label><input aria-label="Purok" type="text" x-model="purok" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Type of Disaster</label><input aria-label="Type of Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-rose-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="2">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="2">Head of Family</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted" rowspan="2">No. of Dependents</th>
                                <th class="px-2 py-1 text-center font-semibold text-rose-700" colspan="2">Casualty</th>
                                <th class="px-2 py-1 text-center font-semibold text-rose-700" colspan="2">Missing</th>
                                <th class="px-2 py-1 text-center font-semibold text-rose-700" colspan="2">Rescued</th>
                                <th class="px-2 py-1 text-center font-semibold text-orange-700" colspan="6">Roof Damage (Nipa / GI Sheets)</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted" rowspan="2">Flood Height*</th>
                                <th class="px-2 py-1 text-center font-semibold text-emerald-700" colspan="2">Estimated Damage (₱)</th>
                                <th class="px-2 py-2" rowspan="2"></th>
                            </tr>
                            <tr class="bg-rose-50/70 border-b border-slate-200 text-[10px] text-rose-600">
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center text-orange-600">Slight-Nipa</th><th class="px-1 py-1 text-center text-orange-600">Slight-GI</th>
                                <th class="px-1 py-1 text-center text-orange-600">Partial-Nipa</th><th class="px-1 py-1 text-center text-orange-600">Partial-GI</th>
                                <th class="px-1 py-1 text-center text-orange-600">Total-Nipa</th><th class="px-1 py-1 text-center text-orange-600">Total-GI</th>
                                <th class="px-1 py-1 text-center text-emerald-600">Partial</th><th class="px-1 py-1 text-center text-emerald-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in housingRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input aria-label="HH Code" type="text" x-model="r.hh" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Head of Family" type="text" x-model="r.head" class="w-32 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="No. of Dependents" type="number" min="0" x-model.number="r.deps" class="w-12 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Casualty M" type="number" min="0" x-model.number="r.cas_m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Casualty F" type="number" min="0" x-model.number="r.cas_f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Missing M" type="number" min="0" x-model.number="r.miss_m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Missing F" type="number" min="0" x-model.number="r.miss_f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Rescued M" type="number" min="0" x-model.number="r.resc_m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Rescued F" type="number" min="0" x-model.number="r.resc_f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof Damage (Nipa / GI Sheets) Slight-Nipa" type="number" min="0" x-model.number="r.roof_slight_nipa" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof Damage (Nipa / GI Sheets) Slight-GI" type="number" min="0" x-model.number="r.roof_slight_gi" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof Damage (Nipa / GI Sheets) Partial-Nipa" type="number" min="0" x-model.number="r.roof_partial_nipa" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof Damage (Nipa / GI Sheets) Partial-GI" type="number" min="0" x-model.number="r.roof_partial_gi" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof Damage (Nipa / GI Sheets) Total-Nipa" type="number" min="0" x-model.number="r.roof_total_nipa" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof Damage (Nipa / GI Sheets) Total-GI" type="number" min="0" x-model.number="r.roof_total_gi" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1">
                                        <select aria-label="Flood Height" x-model="r.flood_height" class="w-24 rounded-xl py-1 px-1 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4">
                                            <option value="">—</option>
                                            <option value="1">(1) Ankle</option>
                                            <option value="2">(2) Knee</option>
                                            <option value="3">(3) Waist</option>
                                            <option value="4">(4) Chest</option>
                                            <option value="5">(5) Head</option>
                                        </select>
                                    </td>
                                    <td class="px-0.5 py-1"><input aria-label="Estimated Damage (₱) Partial" type="number" min="0" x-model.number="r.est_partial" class="w-16 rounded-xl py-1 px-1 text-xs text-right focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Estimated Damage (₱) Total" type="number" min="0" x-model.number="r.est_total" class="w-16 rounded-xl py-1 px-1 text-xs text-right focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><button aria-label="Remove" @click="housingRows.splice(ri,1)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="text-[10px] text-ink-muted">*Levels of flood height: (1) Ankle; (2) Knee; (3) Waist; (4) Chest; (5) Head</p>
                <button @click="addHousingRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Household Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-label="Barangay Secretary" noted-label="Punong Barangay" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" :disabled="saving" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-2xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> <span x-text="saving ? 'Saving…' : 'Save Form'"></span></button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════════ 3. AGRICULTURE DAMAGES ══════════════════════════════════════ --}}
    <template x-if="openForm === 'agriculture'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-ink-body">"Agricultural Crops & Livestock" Calamity Damages Captured Assessment Form</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Name of Disaster</label><input aria-label="Name of Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Date of Occurrence</label><input aria-label="Date of Occurrence" type="date" x-model="occurred" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-amber-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Affected Owner/Farmer</th>
                                <th class="px-2 py-2 text-left font-semibold text-amber-700">Type of Livestock</th>
                                <th class="px-2 py-2 text-center font-semibold text-amber-700">No. of Heads</th>
                                <th class="px-2 py-2 text-center font-semibold text-amber-700">Age</th>
                                <th class="px-2 py-2 text-left font-semibold text-emerald-700">Type of Crop Planted</th>
                                <th class="px-2 py-2 text-left font-semibold text-emerald-700">Stage of Crop</th>
                                <th class="px-2 py-2 text-left font-semibold text-emerald-700">Area Affected</th>
                                <th class="px-2 py-2 text-left font-semibold text-emerald-700">Type of Product</th>
                                <th class="px-2 py-2 text-left font-semibold text-emerald-700">Production Loss</th>
                                <th class="px-2 py-2 text-center font-semibold text-emerald-700">Metric Tons</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in agriRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input aria-label="HH Code" type="text" x-model="r.hh" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Affected Owner/Farmer" type="text" x-model="r.owner" class="w-32 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Type of Livestock" type="text" x-model="r.livestock_type" class="w-24 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="No. of Heads" type="number" min="0" x-model.number="r.heads" class="w-14 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Age" type="text" x-model="r.age" class="w-16 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Type of Crop Planted" type="text" x-model="r.crop" class="w-24 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1">
                                        <select aria-label="Stage of Crop" x-model="r.stage" class="w-28 rounded-xl py-1 px-1 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4">
                                            <option value="">—</option>
                                            <option>Vegetative</option>
                                            <option>Productive</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1"><input aria-label="Area Affected" type="text" x-model="r.area_affected" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Type of Product" type="text" x-model="r.product" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1">
                                        <select aria-label="Production Loss" x-model="r.loss_recovery" class="w-36 rounded-xl py-1 px-1 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4">
                                            <option value="">—</option>
                                            <option>With chance of recovery</option>
                                            <option>No chance of recovery</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1"><input aria-label="Metric Tons" type="number" step="0.1" min="0" x-model.number="r.metric_tons" class="w-16 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><button aria-label="Remove" @click="agriRows.splice(ri,1)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button @click="addAgriRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Farmer Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-label="Barangay Secretary" noted-label="Punong Barangay/BDRRMC Chairperson" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" :disabled="saving" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-2xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> <span x-text="saving ? 'Saving…' : 'Save Form'"></span></button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════════ 4. FISHING DAMAGES ══════════════════════════════════════ --}}
    <template x-if="openForm === 'fishing'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-ink-body">"Fishing" Calamity Damages Captured Assessment Form</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Purok</label><input aria-label="Purok" type="text" x-model="purok" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Name & Type of Disaster</label><input aria-label="Name & Type of Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Date of Occurrence</label><input aria-label="Date of Occurrence" type="date" x-model="occurred" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-cyan-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Owner</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted">Qty.</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Fishing Gear / Equipment</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Description of Damage</th>
                                <th class="px-2 py-2 text-right font-semibold text-ink-muted">Estimated Damage (₱)</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in fishingRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input aria-label="HH Code" type="text" x-model="r.hh" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Owner" type="text" x-model="r.owner" class="w-36 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Qty." type="number" min="0" x-model.number="r.qty" class="w-14 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Fishing Gear / Equipment" type="text" x-model="r.gear" class="w-40 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1">
                                        <select aria-label="Description of Damage" x-model="r.damage" class="w-28 rounded-xl py-1 px-1 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4">
                                            <option value="">—</option>
                                            <option>Partial</option>
                                            <option>Total</option>
                                            <option>Missing</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1"><input aria-label="Estimated Damage (₱)" type="number" min="0" x-model.number="r.est_damage" class="w-24 rounded-xl py-1 px-1 text-xs text-right focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><button aria-label="Remove" @click="fishingRows.splice(ri,1)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button @click="addFishingRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Owner Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-label="Barangay Secretary" noted-label="Punong Barangay" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" :disabled="saving" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-2xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> <span x-text="saving ? 'Saving…' : 'Save Form'"></span></button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════════ 5. BUSINESS DAMAGES ══════════════════════════════════════ --}}
    <template x-if="openForm === 'business'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-ink-body">"Business" Calamity Damages Captured Assessment Form</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Purok</label><input aria-label="Purok" type="text" x-model="purok" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Disaster</label><input aria-label="Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Date of Occurrence</label><input aria-label="Date of Occurrence" type="date" x-model="occurred" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-violet-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Business Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Full Name of Owner</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Establishment/Store</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Business Type</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted">No. of Workers</th>
                                <th class="px-2 py-2 text-center font-semibold text-violet-700">Merchandise (Total/Partial)</th>
                                <th class="px-2 py-2 text-center font-semibold text-violet-700">Equipment (Total/Partial)</th>
                                <th class="px-2 py-2 text-center font-semibold text-violet-700">Structures (Total/Partial)</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted">Flood Height*</th>
                                <th class="px-2 py-2 text-right font-semibold text-ink-muted">Estimated Damage (₱)</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in businessRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input aria-label="Business Code" type="text" x-model="r.code" class="w-24 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Full Name of Owner" type="text" x-model="r.owner" class="w-32 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Establishment/Store" type="text" x-model="r.establishment" class="w-32 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="store, products, food, rental" type="text" x-model="r.type" class="w-24 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" placeholder="store, products, food, rental"></td>
                                    <td class="px-1 py-1"><input aria-label="No. of Workers" type="number" min="0" x-model.number="r.workers" class="w-14 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1">
                                        <select aria-label="Merchandise (Total/Partial)" x-model="r.merch" class="w-24 rounded-xl py-1 px-1 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4">
                                            <option value="">—</option><option>Partial</option><option>Total</option><option>None</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1">
                                        <select aria-label="Equipment (Total/Partial)" x-model="r.equip" class="w-24 rounded-xl py-1 px-1 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4">
                                            <option value="">—</option><option>Partial</option><option>Total</option><option>None</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1">
                                        <select aria-label="Structures (Total/Partial)" x-model="r.struct" class="w-24 rounded-xl py-1 px-1 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4">
                                            <option value="">—</option><option>Partial</option><option>Total</option><option>None</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1">
                                        <select aria-label="Flood Height" x-model="r.flood_height" class="w-24 rounded-xl py-1 px-1 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4">
                                            <option value="">—</option>
                                            <option value="1">(1) Ankle</option><option value="2">(2) Knee</option><option value="3">(3) Waist</option><option value="4">(4) Chest</option><option value="5">(5) Head</option>
                                        </select>
                                    </td>
                                    <td class="px-1 py-1"><input aria-label="Estimated Damage (₱)" type="number" min="0" x-model.number="r.est_damage" class="w-24 rounded-xl py-1 px-1 text-xs text-right focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><button aria-label="Remove" @click="businessRows.splice(ri,1)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="text-[10px] text-ink-muted">*Levels of flood height: (1) Ankle; (2) Knee; (3) Waist; (4) Chest; (5) Head</p>
                <button @click="addBusinessRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Business Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-label="Barangay Secretary" noted-label="Punong Barangay/BDRRMC Chairperson" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" :disabled="saving" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-2xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> <span x-text="saving ? 'Saving…' : 'Save Form'"></span></button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════════ 6. RELIEF DISTRIBUTION SHEET ══════════════════════════════════════ --}}
    <template x-if="openForm === 'rds'">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">RDS</p>
                    <h3 class="font-semibold text-ink-body">Relief Distribution Sheet</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Name of Evacuation Center</label><input aria-label="Name of Evacuation Center" type="text" x-model="center" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Type of Disaster</label><input aria-label="Type of Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Date of Relief Distribution</label><input aria-label="Date of Relief Distribution" type="date" x-model="occurred" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-emerald-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Name of Beneficiary</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted">No. of Dependents</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Kind/Type</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">Qty/Unit</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted">Received in Good Condition</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted">Signature/Thumbmark</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in rdsRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1 py-1"><input aria-label="HH Code" type="text" x-model="r.hh" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Name of Beneficiary" type="text" x-model="r.beneficiary" class="w-36 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="No. of Dependents" type="number" min="0" x-model.number="r.deps" class="w-14 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Kind/Type" type="text" x-model="r.kind" class="w-32 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Qty/Unit" type="text" x-model="r.qty" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1 text-center"><input aria-label="Received in Good Condition" type="checkbox" x-model="r.received" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"></td>
                                    <td class="px-1 py-1 text-center text-ink-faint italic">signed on release</td>
                                    <td class="px-1 py-1"><button aria-label="Remove" @click="rdsRows.splice(ri,1)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button @click="addRdsRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Beneficiary Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Barangay Official" prepared-role="Distributed by" consolidated-label="" noted-label="Punong Barangay" noted-role="Certified True and Correct" :two-column="true" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print()" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" :disabled="saving" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-2xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> <span x-text="saving ? 'Saving…' : 'Save Form'"></span></button>
            </div>
        </div>
        </div>
    </div>
    </template>

</div>

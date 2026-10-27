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
        // Filling the Profile of Evacuees and the Relief Distribution Sheet
        // from records, and reopening saved forms: resources/js/sakuna-bdrrmc.js.
        ...(window.bdrrmcRecords ? bdrrmcRecords() : {}),
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
                'human-housing': () => ({ hh:'', head:'', deps:0, cas_m:0,cas_f:0, miss_m:0,miss_f:0, resc_m:0,resc_f:0, roof_slight_nipa:0,roof_slight_gi:0, roof_partial_nipa:0,roof_partial_gi:0, roof_total_nipa:0,roof_total_gi:0, wall_partial:0,wall_total:0, other_partial:0,other_total:0, flood_height:'', est_partial:0, est_total:0 }),
                'agriculture': () => ({ hh:'', owner:'', livestock_type:'', heads:'', age:'', crop:'', stage:'', area_recoverable:'', area_unrecoverable:'', product:'', metric_tons:'' }),
                'fishing': () => ({ hh:'', owner:'', qty:'', gear:'', damage:'', est_damage:'' }),
                'business': () => ({ code:'', owner:'', establishment:'', type:'', workers:'', merch:'', equip:'', struct:'', flood_height:'', est_damage:'' }),
                'rds': () => ({ hh:'', beneficiary:'', deps:'', kind:'', qty:'' }),
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
        // A row filled from records is never blank; its _src (where it came
        // from) is not a cell.
        isBlankRow(r) { return Object.entries(r).filter(([k]) => !k.startsWith('_')).every(([, v]) => v === '' || v === 0 || v === false || v === null); },
        // The open form alone, full width (resources/js/sakuna-bdrrmc.js); the
        // plain window.print() when that script is not loaded.
        print(event) { const area = event?.target?.closest('[data-print-area]'); window.bdrrmcPrint ? window.bdrrmcPrint(area) : window.print(); },
        async save() {
            const key = this.openForm;
            const rows = (this[this.rowsOf[key]] || []).filter((r) => !this.isBlankRow(r));
            const fail = (message) => this.$dispatch('toast', { message, variant: 'error' });
            if (!this.barangay) return fail('Choose the barangay first.');
            if (!rows.length) return fail('Fill in at least one row before saving.');
            if (!this.signoff.prepared.trim()) return fail('Type the name of the person who prepared the form.');
            const drafted = this.drafted ? this.drafted[key] : null;
            this.saving = true;
            try {
                const res = await $api.post('/admin/sakuna/bdrrmc-forms/' + key + '/submit', {
                    barangay: this.barangay,
                    period: this.disasterType.trim() || null,
                    rows: rows.map((r) => (this.cleanRow ? this.cleanRow(r) : r)),
                    // Where the rows came from; the server checks it against
                    // the records and writes the record itself.
                    source: this.sourcePayload ? this.sourcePayload(key, rows) : { kind: 'manual' },
                    meta: {
                        as_of: key === 'evacuees' ? (drafted ? drafted.params.as_of : (this.asOfTyped ? this.asOfTyped + ':00+08:00' : null)) : null,
                        address: key === 'evacuees' ? ((this.address || '').trim() || null) : null,
                        distribution_date: key === 'rds' ? (this.distributedOn || null) : null,
                        province: key === 'rds' ? 'Masbate' : null,
                        municipality: key === 'rds' ? 'Esperanza' : null,
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
                this.$dispatch('toast', { message: 'Saved as ' + res.data.ref + ' (' + rows.length + ' row' + (rows.length === 1 ? '' : 's') + '). It is listed under Submitted forms.'
                    + (res.data.meta && res.data.meta.provisional ? ' It is provisional: families of exactly five await the LGU.' : ''), variant: 'success' });
                if (this.drafted) this.drafted[key] = null;
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
                if (this.viewing && this.viewing.ref === sub.ref) this.viewSubmission(sub);
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
                    @click="openForm = '{{ $f['key'] }}'; fillOpen = false; fill.error = ''; $nextTick(() => window.renderIcons?.())"
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
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <button type="button" @click="viewSubmission(sub)" :disabled="viewLoading" class="text-xs font-medium text-brand-600 hover:underline mr-3">View</button>
                                {{-- Who filed a form cannot approve it; the server refuses it too (2026-10-27). --}}
                                <button type="button" x-show="sub.can_approve" @click="approve(sub)" class="text-xs font-medium text-brand-600 hover:underline">Approve</button>
                                <span x-show="sub.status === 'Submitted' && sub.submitted_by_me && canApprove" class="text-[11px] text-ink-muted">Filed by you</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Print: the open form alone. Print copies it into #bdrrmc-print-root and
         prints only that, full width, landscape as the paper forms are,
         flowing over pages, without the buttons or the on-screen notes. A
         browser's own Print (Ctrl+P) falls back to the window on top. --}}
    <style>
        #bdrrmc-print-root { display: none; }
        @media print {
            @page { size: landscape; margin: 10mm; }
            @page bdrrmc-portrait { size: portrait; margin: 10mm; }
            #bdrrmc-print-root[data-orientation="portrait"] { page: bdrrmc-portrait; }
            html.bdrrmc-printing body > *:not(#bdrrmc-print-root) { display: none !important; }
            html.bdrrmc-printing #bdrrmc-print-root { display: block !important; }
            #bdrrmc-print-root [data-print-area] { position: static !important; width: 100% !important; height: auto !important; overflow: visible !important; box-shadow: none !important; border-radius: 0 !important; display: block !important; }
            #bdrrmc-print-root .overflow-y-auto, #bdrrmc-print-root .overflow-x-auto, #bdrrmc-print-root .overflow-hidden { overflow: visible !important; height: auto !important; }
            #bdrrmc-print-root .no-print, #bdrrmc-print-root button { display: none !important; }
            #bdrrmc-print-root table { font-size: 9px !important; width: 100% !important; }
            #bdrrmc-print-root tr { break-inside: avoid; }
            #bdrrmc-print-root input, #bdrrmc-print-root select { border: 0 !important; box-shadow: none !important; background: transparent !important; appearance: none; }

            html:not(.bdrrmc-printing) body * { visibility: hidden !important; }
            html:not(.bdrrmc-printing) [data-print-area]:not([data-print-under]), html:not(.bdrrmc-printing) [data-print-area]:not([data-print-under]) * { visibility: visible !important; }
            html:not(.bdrrmc-printing) [data-print-area]:not([data-print-under]) { position: absolute !important; inset: 0 !important; height: auto !important; box-shadow: none !important; }
            html:not(.bdrrmc-printing) [data-print-area] .overflow-y-auto, html:not(.bdrrmc-printing) [data-print-area] .overflow-x-auto { overflow: visible !important; height: auto !important; }
            html:not(.bdrrmc-printing) [data-print-area] .no-print, html:not(.bdrrmc-printing) [data-print-area] button { display: none !important; }
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
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area :data-print-under="viewing ? true : null" class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">Dromic Form 1.2</p>
                    <h3 class="font-semibold text-ink-body">Profile of Evacuees Inside the Evacuation Center</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                {{-- Fill from records (2026-10-27): the families staying at a centre at the
                     time of the report, from check-ins, transfers and check-outs.
                     Nothing is saved until Save Form. --}}
                <div x-show="canFill && canFill('evacuees')" class="no-print rounded-2xl border border-brand-600/15 bg-brand-50/40 p-3 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-ink-body"><span class="font-semibold">Fill from records:</span> the families staying at an evacuation centre at the date and time of the report.</p>
                        <button type="button" @click="openFill()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-700 bg-white border border-brand-200 rounded-xl px-2.5 py-1.5 hover:border-brand-300"><i data-lucide="database" class="w-3.5 h-3.5"></i> <span x-text="fillOpen ? 'Close' : 'Fill from records'"></span></button>
                    </div>
                    <div x-show="fillOpen" x-cloak class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2 items-end">
                        <div><label for="fill-evac-center" class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Evacuation center</label>
                            <select id="fill-evac-center" x-model="fill.center" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard bg-white"><option value="">Choose a centre</option><template x-for="c in centers" :key="c.ref"><option :value="c.ref" x-text="c.name + ' · Brgy. ' + c.barangay + (c.status === 'Processing' ? ' · open' : '')"></option></template></select></div>
                        <div><label for="fill-evac-asof" class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">As of (Esperanza time)</label>
                            <input id="fill-evac-asof" type="datetime-local" x-model="fill.asOf" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard bg-white"></div>
                        <button type="button" @click="fillFromRecords('evacuees')" :disabled="fill.loading" class="inline-flex items-center justify-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-xl px-4 py-2"><span x-text="fill.loading ? 'Filling…' : 'Fill'"></span></button>
                    </div>
                    <p x-show="fillOpen && fill.error" x-cloak role="alert" class="text-xs text-rose-700" x-text="fill.error"></p>
                    <p x-show="fillOpen" x-cloak class="text-[11px] text-ink-muted">Nothing is saved until you choose Save Form. What the records do not hold stays blank and is flagged; type it if you know it.</p>
                </div>

                {{-- What the records said, and what is missing --}}
                <template x-if="drafted && drafted.evacuees">
                    <div class="no-print rounded-2xl border border-slate-200 bg-white p-3 space-y-1.5 text-xs">
                        <p class="text-ink-body"><span class="font-semibold">Filled from records</span> at <span x-text="drafted.evacuees.header.evacuation_center"></span>, as of <span x-text="viewWhen(drafted.evacuees.params.as_of)"></span>:
                            <span x-text="drafted.evacuees.summary.families + (drafted.evacuees.summary.families === 1 ? ' family; ' : ' families; ') + drafted.evacuees.summary.headcount + ' people recorded, ' + drafted.evacuees.summary.classified + ' in the age columns, ' + drafted.evacuees.summary.unclassified + ' without an age or sex.'"></span></p>
                        <p x-show="drafted.evacuees.summary.conflicts" class="text-rose-700 font-medium" x-text="drafted.evacuees.summary.conflicts + (drafted.evacuees.summary.conflicts === 1 ? ' family appears' : ' families appear') + ' in two places at once: see the rows marked in red. They are listed, not merged.'"></p>
                        <p x-show="drafted.evacuees.summary.rows_with_warnings" class="text-amber-800" x-text="drafted.evacuees.summary.rows_with_warnings + (drafted.evacuees.summary.rows_with_warnings === 1 ? ' row needs' : ' rows need') + ' a look: see the notes under each name.'"></p>
                        <template x-for="w in drafted.evacuees.warnings" :key="w.code"><p :class="w.level === 'manual' ? 'text-ink-muted' : 'text-amber-800'" x-text="w.message"></p></template>
                        <p class="text-ink-muted">Cells you change are outlined; the saved form records what the records said and what you changed. Removing a row asks for a reason.</p>
                    </div>
                </template>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Region</label><input aria-label="Region" type="text" value="Region V" readonly class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked || !!(drafted && drafted.evacuees)" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none cursor-not-allowed"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Type of Disaster</label><input aria-label="Type of Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Date of Occurrence</label><input aria-label="Date of Occurrence" type="date" x-model="occurred" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Name of Evacuation Center</label><input aria-label="Name of Evacuation Center" type="text" x-model="center" :readonly="!!(drafted && drafted.evacuees)" placeholder="e.g. Barangay Hall" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div class="md:col-span-2"><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Address</label><input aria-label="Address of the Evacuation Center" type="text" x-model="address" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div x-show="!(drafted && drafted.evacuees)"><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">As of (date and time)</label><input aria-label="As of (date and time)" type="datetime-local" x-model="asOfTyped" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div x-show="drafted && drafted.evacuees"><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">As of</label><input aria-label="As of" type="text" readonly :value="drafted && drafted.evacuees ? viewWhen(drafted.evacuees.params.as_of) : ''" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-sky-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted" rowspan="3">No.</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="3">Purok</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="3">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="3">Name of Family Head or Representative</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="4">Pre-Schooler (0-6)</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Older Children</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Youth</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Adult</th>
                                <th class="px-2 py-1 text-center font-semibold text-sky-700" colspan="2">Senior Citizen</th>
                                <th class="px-2 py-1 text-center font-semibold text-ink-muted" colspan="2" rowspan="2">Total</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted" rowspan="3">Grand Total</th>
                                {{-- Not on the paper form: the family's size as recorded at check-in,
                                     next to the people the age columns could count (2026-10-27). --}}
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted" rowspan="3">Recorded Headcount</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="3">Assistance Extended</th>
                                <th class="px-2 py-2 no-print" rowspan="3"></th>
                            </tr>
                            <tr class="bg-sky-50/70 border-b border-slate-200 text-[10px] text-sky-700">
                                <th class="px-1 py-1 text-center font-semibold" colspan="2">0 to 3</th><th class="px-1 py-1 text-center font-semibold" colspan="2">4 to 6</th>
                                <th class="px-1 py-1 text-center font-semibold" colspan="2">7 to 12</th><th class="px-1 py-1 text-center font-semibold" colspan="2">13 to 17</th>
                                <th class="px-1 py-1 text-center font-semibold" colspan="2">18 to 59</th><th class="px-1 py-1 text-center font-semibold" colspan="2">60 above</th>
                            </tr>
                            <tr class="bg-sky-50/70 border-b border-slate-200 text-[10px] text-sky-600">
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center">M</th><th class="px-1 py-1 text-center">F</th>
                                <th class="px-1 py-1 text-center text-ink-muted">M</th><th class="px-1 py-1 text-center text-ink-muted">F</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in evacueeRows" :key="rowKey ? rowKey('evacuees', r, ri) : ri">
                                <tr :class="rowClass ? rowClass('evacuees', r, ri) : (ri % 2 === 1 && 'bg-slate-50/50')">
                                    <td class="px-1.5 py-1 text-center text-ink-muted align-top" x-text="ri + 1"></td>
                                    <td class="px-1 py-1 align-top"><input aria-label="Purok" type="text" x-model="r.purok" class="w-12 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'purok')"></td>
                                    <td class="px-1 py-1 align-top"><input aria-label="HH Code" type="text" x-model="r.hh" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'hh')"></td>
                                    <td class="px-1 py-1 align-top">
                                        <input aria-label="Family Head or Representative" type="text" x-model="r.head" class="w-40 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'head')">
                                        <template x-if="rowSource('evacuees', r)">
                                            <div class="no-print mt-1 w-40 space-y-0.5">
                                                <p class="text-[10px] text-ink-muted font-mono" x-text="rowSource('evacuees', r).ref"></p>
                                                <template x-for="(w, wi) in rowWarnings('evacuees', r)" :key="wi"><p class="text-[10px] leading-snug" :class="w.level === 'conflict' ? 'text-rose-700 font-medium' : (w.level === 'warning' ? 'text-amber-800' : 'text-ink-muted')" x-text="w.message"></p></template>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-0.5 py-1"><input aria-label="Pre-Schooler 0-3 M" type="number" min="0" x-model.number="r.pre03m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'pre03m')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Pre-Schooler 0-3 F" type="number" min="0" x-model.number="r.pre03f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'pre03f')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Pre-Schooler 4-6 M" type="number" min="0" x-model.number="r.pre46m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'pre46m')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Pre-Schooler 4-6 F" type="number" min="0" x-model.number="r.pre46f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'pre46f')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Children 7-12 M" type="number" min="0" x-model.number="r.ch712m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'ch712m')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Children 7-12 F" type="number" min="0" x-model.number="r.ch712f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'ch712f')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Youth 13-17 M" type="number" min="0" x-model.number="r.yth1317m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'yth1317m')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Youth 13-17 F" type="number" min="0" x-model.number="r.yth1317f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'yth1317f')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Adult 18-59 M" type="number" min="0" x-model.number="r.ad1859m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'ad1859m')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Adult 18-59 F" type="number" min="0" x-model.number="r.ad1859f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'ad1859f')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Senior 60+ M" type="number" min="0" x-model.number="r.sc60m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'sc60m')"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Senior 60+ F" type="number" min="0" x-model.number="r.sc60f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'sc60f')"></td>
                                    <td class="px-2 py-1 text-center text-slate-700" x-text="sexTotal(r, 'm')"></td>
                                    <td class="px-2 py-1 text-center text-slate-700" x-text="sexTotal(r, 'f')"></td>
                                    <td class="px-2 py-1 text-center font-semibold text-slate-700" x-text="evacueeFamTotal(r)"></td>
                                    <td class="px-2 py-1 text-center text-slate-700" x-text="rowSource('evacuees', r) ? rowSource('evacuees', r).headcount : '—'" :title="rowSource('evacuees', r) ? 'The family size recorded at check-in' : 'Typed by hand: no recorded headcount'"></td>
                                    <td class="px-1 py-1 align-top"><input aria-label="Assistance Extended" type="text" x-model="r.assist" class="w-32 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('evacuees', r, 'assist')"></td>
                                    <td class="px-1 py-1 no-print"><button aria-label="Remove" @click="removeRow('evacuees', ri)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-700">
                                <td colspan="4" class="px-2 py-2 text-right">Grand Total</td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('pre03m')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('pre03f')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('pre46m')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('pre46f')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('ch712m')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('ch712f')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('yth1317m')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('yth1317f')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('ad1859m')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('ad1859f')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('sc60m')"></td>
                                <td class="px-1 py-2 text-center" x-text="columnTotal('sc60f')"></td>
                                <td class="px-1 py-2 text-center" x-text="sexColumnTotal('m')"></td>
                                <td class="px-1 py-2 text-center" x-text="sexColumnTotal('f')"></td>
                                <td class="px-2 py-2 text-center" x-text="evacueeGrandTotal()"></td>
                                <td class="px-2 py-2 text-center" x-text="recordedHeadcount()"></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button @click="addEvacueeRow(); $nextTick(() => window.renderIcons?.())" class="no-print w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Family Row
                </button>

                {{-- The paper form's family-size lines. Families of exactly five are
                     not put in either until the LGU confirms (2026-10-27). --}}
                <div class="rounded-xl border border-slate-200 p-3 text-xs space-y-1 text-slate-700">
                    <p>Total Number of Families with less than 5 members: <strong x-text="familySizes().lt5 + (fiveRule() === 'fewer' ? familySizes().eq5 : 0)"></strong></p>
                    <p>Total Number of Families with more than 5 members: <strong x-text="familySizes().gt5 + (fiveRule() === 'more' ? familySizes().eq5 : 0)"></strong></p>
                    <p x-show="fiveRule() === 'unresolved' && familySizes().eq5" class="text-amber-800">Families with exactly 5 members: <strong x-text="familySizes().eq5"></strong>, not counted in either line above until the LGU confirms where they belong. This report is provisional.</p>
                    <p x-show="recordedHeadcount() !== evacueeGrandTotal()" class="text-ink-muted">Recorded headcount <strong x-text="recordedHeadcount()"></strong>; people in the age columns <strong x-text="evacueeGrandTotal()"></strong>. The difference had no age or sex on record.</p>
                    <p class="text-[10px] text-ink-muted">Note: List should be by families (Family Head and Dependents).</p>
                </div>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Barangay Official (Evacuation Center)" consolidated-label="Barangay Secretary" noted-label="Punong Barangay" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print($event)" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
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
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area :data-print-under="viewing ? true : null" class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-ink-body">"Human, Housing & Flooding" Calamity/Damages Capture and Assessment Form</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="text-center leading-snug">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-body">Barangay Disaster Risk Reduction and Management Committee</p>
                    <p class="text-[11px] uppercase tracking-wide text-ink-muted" x-text="(barangay ? 'Brgy. ' + barangay + ', ' : '') + 'Esperanza, Masbate'"></p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Purok</label><input aria-label="Purok" type="text" x-model="purok" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Type of Disaster</label><input aria-label="Type of Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-rose-50/70"><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="4">No.</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="4">HH Code</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="4">Name of Head of the Family</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="4">No. of Dependents</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="6">Human</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="11">Housing Damages</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2" rowspan="3">Estimated Damage (PhP)</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle no-print" rowspan="4"></th></tr>
                            <tr class="bg-rose-50/70"><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="6">No. of Individuals</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="6">Roof</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2" rowspan="2">Outer Wall</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2" rowspan="2">Other Parts</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="3">Flood Height*</th></tr>
                            <tr class="bg-rose-50/70"><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2">Casualty</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2">Missing</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2">Rescued</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2">Slightly</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2">Partially</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2">Totally</th></tr>
                            <tr class="bg-rose-50/70"><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">M</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">F</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">M</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">F</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">M</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">F</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Nipa</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">GI Sheets</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Nipa</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">GI Sheets</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Nipa</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">GI Sheets</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Partial</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Total</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Partial</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Total</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Partial</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Total</th></tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in housingRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1.5 py-1 text-center text-ink-muted" x-text="ri + 1"></td>
                                    <td class="px-1 py-1"><input aria-label="HH Code" type="text" x-model="r.hh" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Name of Head of the Family" type="text" x-model="r.head" class="w-36 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="No. of Dependents" type="number" min="0" x-model.number="r.deps" class="w-12 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Casualty M" type="number" min="0" x-model.number="r.cas_m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Casualty F" type="number" min="0" x-model.number="r.cas_f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Missing M" type="number" min="0" x-model.number="r.miss_m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Missing F" type="number" min="0" x-model.number="r.miss_f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Rescued M" type="number" min="0" x-model.number="r.resc_m" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Rescued F" type="number" min="0" x-model.number="r.resc_f" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof slightly damaged, Nipa" type="number" min="0" x-model.number="r.roof_slight_nipa" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof slightly damaged, GI Sheets" type="number" min="0" x-model.number="r.roof_slight_gi" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof partially damaged, Nipa" type="number" min="0" x-model.number="r.roof_partial_nipa" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof partially damaged, GI Sheets" type="number" min="0" x-model.number="r.roof_partial_gi" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof totally damaged, Nipa" type="number" min="0" x-model.number="r.roof_total_nipa" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Roof totally damaged, GI Sheets" type="number" min="0" x-model.number="r.roof_total_gi" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Outer wall, partial" type="number" min="0" x-model.number="r.wall_partial" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Outer wall, total" type="number" min="0" x-model.number="r.wall_total" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Other parts, partial" type="number" min="0" x-model.number="r.other_partial" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Other parts, total" type="number" min="0" x-model.number="r.other_total" class="w-9 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><select aria-label="Flood Height" x-model="r.flood_height" class="w-24 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"><option value="">—</option><option value="1">(1) Ankle</option><option value="2">(2) Knee</option><option value="3">(3) Waist</option><option value="4">(4) Chest</option><option value="5">(5) Head</option></select></td>
                                    <td class="px-0.5 py-1"><input aria-label="Estimated Damage (PhP) Partial" type="number" min="0" x-model.number="r.est_partial" class="w-20 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Estimated Damage (PhP) Total" type="number" min="0" x-model.number="r.est_total" class="w-20 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1 no-print"><button aria-label="Remove" @click="housingRows.splice(ri,1)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-700">
                                <td class="px-1.5 py-2 text-center">TOTAL</td>
                                <td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'deps')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'cas_m')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'cas_f')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'miss_m')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'miss_f')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'resc_m')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'resc_f')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'roof_slight_nipa')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'roof_slight_gi')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'roof_partial_nipa')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'roof_partial_gi')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'roof_total_nipa')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'roof_total_gi')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'wall_partial')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'wall_total')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'other_partial')"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'other_total')"></td><td class="px-1 py-2"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'est_partial').toLocaleString('en-PH', { maximumFractionDigits: 2 })"></td><td class="px-1 py-2 text-center" x-text="formTotal('human-housing', 'est_total').toLocaleString('en-PH', { maximumFractionDigits: 2 })"></td>
                                <td class="no-print"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="text-[10px] text-ink-muted">*Levels of flood height: (1) Ankle; (2) Knee; (3) Waist; (4) Chest; (5) Head</p>
                <button @click="addHousingRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Household Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-label="Barangay Secretary" noted-label="Punong Barangay" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print($event)" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
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
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area :data-print-under="viewing ? true : null" class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-ink-body">"Agricultural Crops & Livestock" Calamity Damages Captured Assessment Form</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="text-center leading-snug">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-body">Barangay Disaster Risk Reduction and Management Committee</p>
                    <p class="text-[11px] uppercase tracking-wide text-ink-muted" x-text="(barangay ? 'Brgy. ' + barangay + ', ' : '') + 'Esperanza, Masbate'"></p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Name of Disaster</label><input aria-label="Name of Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Date of Occurrence</label><input aria-label="Date of Occurrence" type="date" x-model="occurred" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-amber-50/70"><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="3">No.</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="3">HH Code</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="3">HH Name of Affected Owner/Farmer</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="3">Livestock</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="4">Crops</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2">Production</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle no-print" rowspan="3"></th></tr>
                            <tr class="bg-amber-50/70"><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">Type of Livestock</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">No. of Heads</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">Age</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">Type of Crop Planted</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Stage of the Crop</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="2">Area Affected</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">Type of Product</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Production Loss</th></tr>
                            <tr class="bg-amber-50/70"><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Vegetative / Productive</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">w/ chance of recovery</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">NO chance of recovery</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">In metric tons</th></tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in agriRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1.5 py-1 text-center text-ink-muted" x-text="ri + 1"></td>
                                    <td class="px-1 py-1"><input aria-label="HH Code" type="text" x-model="r.hh" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="HH Name of Affected Owner/Farmer" type="text" x-model="r.owner" class="w-36 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Type of Livestock" type="text" x-model="r.livestock_type" class="w-24 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="No. of Heads" type="number" min="0" x-model.number="r.heads" class="w-14 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Age of Livestock" type="text" x-model="r.age" class="w-16 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Type of Crop Planted" type="text" x-model="r.crop" class="w-24 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><select aria-label="Stage of the Crop" x-model="r.stage" class="w-28 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"><option value="">—</option><option>Vegetative</option><option>Productive</option></select></td>
                                    <td class="px-1 py-1"><input aria-label="Area Affected, with chance of recovery" type="text" x-model="r.area_recoverable" placeholder="e.g. 0.5 ha" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Area Affected, no chance of recovery" type="text" x-model="r.area_unrecoverable" placeholder="e.g. 0.5 ha" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Type of Product" type="text" x-model="r.product" class="w-24 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Production Loss in metric tons" type="number" min="0" step="0.01" x-model.number="r.metric_tons" class="w-16 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1 no-print"><button aria-label="Remove" @click="agriRows.splice(ri,1)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-700">
                                <td class="px-1.5 py-2 text-center"></td>
                                <td class="px-1 py-2"></td><td class="px-1 py-2 text-right">TOTAL</td><td class="px-1 py-2"></td><td class="px-1 py-2 text-center" x-text="formTotal('agriculture', 'heads')"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2 text-center" x-text="formTotal('agriculture', 'metric_tons')"></td>
                                <td class="no-print"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button @click="addAgriRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Farmer Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-label="Barangay Secretary" noted-label="Punong Barangay/BDRRMC Chairperson" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print($event)" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
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
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-orientation="portrait" data-print-area :data-print-under="viewing ? true : null" class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-ink-body">"Fishing" Calamity Damages Captured Assessment Form</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="text-center leading-snug">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-body">Barangay Disaster Risk Reduction and Management Committee</p>
                    <p class="text-[11px] uppercase tracking-wide text-ink-muted" x-text="(barangay ? 'Brgy. ' + barangay + ', ' : '') + 'Esperanza, Masbate'"></p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Purok</label><input aria-label="Purok" type="text" x-model="purok" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Name & Type of Disaster</label><input aria-label="Name & Type of Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Date of Occurrence</label><input aria-label="Date of Occurrence" type="date" x-model="occurred" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-cyan-50/70"><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">No.</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">HH Code</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">HH Name of Owner</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Qty.</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Description of Fishing Gear/ Equipment</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Description of Damage (Partial/ Total/Missing)</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Estimated Damage (PhP)</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle no-print"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in fishingRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1.5 py-1 text-center text-ink-muted" x-text="ri + 1"></td>
                                    <td class="px-1 py-1"><input aria-label="HH Code" type="text" x-model="r.hh" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="HH Name of Owner" type="text" x-model="r.owner" class="w-36 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="Qty." type="number" min="0" x-model.number="r.qty" class="w-14 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Description of Fishing Gear/Equipment" type="text" x-model="r.gear" class="w-44 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><select aria-label="Description of Damage" x-model="r.damage" class="w-28 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"><option value="">—</option><option>Partial</option><option>Total</option><option>Missing</option></select></td>
                                    <td class="px-0.5 py-1"><input aria-label="Estimated Damage (PhP)" type="number" min="0" x-model.number="r.est_damage" class="w-24 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1 no-print"><button aria-label="Remove" @click="fishingRows.splice(ri,1)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-700">
                                <td class="px-1.5 py-2 text-center"></td>
                                <td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2 text-right">TOTAL</td><td class="px-1 py-2 text-center" x-text="formTotal('fishing', 'est_damage').toLocaleString('en-PH', { maximumFractionDigits: 2 })"></td>
                                <td class="no-print"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button @click="addFishingRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Owner Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-label="Barangay Secretary" noted-label="Punong Barangay" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print($event)" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
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
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area :data-print-under="viewing ? true : null" class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">BDRRMC Capture Form</p>
                    <h3 class="font-semibold text-ink-body">"Business" Calamity Damages Captured Assessment Form</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <div class="text-center leading-snug">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-body">Barangay Disaster Risk Reduction and Management Committee</p>
                    <p class="text-[11px] uppercase tracking-wide text-ink-muted" x-text="(barangay ? 'Brgy. ' + barangay + ', ' : '') + 'Esperanza, Masbate'"></p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted cursor-not-allowed border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Purok</label><input aria-label="Purok" type="text" x-model="purok" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Disaster</label><input aria-label="Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Date of Occurrence</label><input aria-label="Date of Occurrence" type="date" x-model="occurred" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-violet-50/70"><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">No.</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">Business Code</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">Full Name of Owner</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">Name of Establishment/ Store</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">Business Type (store, products, food, rental)</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">No. of Workers</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" colspan="4">Description, Type &amp; Qty. of Damage</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle" rowspan="2">Estimated Damage (PhP)</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle no-print" rowspan="2"></th></tr>
                            <tr class="bg-violet-50/70"><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Merchandise (Qty, Desc&#8217;n) (Total/Partial)</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Equipment (Qty, Desc&#8217;n) (Total/Partial)</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Structures (Qty, Desc&#8217;n) (Total/Partial)</th><th class="px-1.5 py-1 text-center font-semibold text-ink-muted border border-slate-200 align-middle">Flood Height*</th></tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in businessRows" :key="ri">
                                <tr :class="ri % 2 === 1 && 'bg-slate-50/50'">
                                    <td class="px-1.5 py-1 text-center text-ink-muted" x-text="ri + 1"></td>
                                    <td class="px-1 py-1"><input aria-label="Business Code" type="text" x-model="r.code" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Full Name of Owner" type="text" x-model="r.owner" class="w-32 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Name of Establishment/Store" type="text" x-model="r.establishment" class="w-32 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Business Type" type="text" x-model="r.type" class="w-24 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-0.5 py-1"><input aria-label="No. of Workers" type="number" min="0" x-model.number="r.workers" class="w-14 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Merchandise (Qty, Description) (Total/Partial)" type="text" x-model="r.merch" placeholder="e.g. 10 sacks rice, Total" class="w-36 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Equipment (Qty, Description) (Total/Partial)" type="text" x-model="r.equip" placeholder="e.g. 1 freezer, Partial" class="w-36 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><input aria-label="Structures (Qty, Description) (Total/Partial)" type="text" x-model="r.struct" placeholder="e.g. store roof, Partial" class="w-36 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1"><select aria-label="Flood Height" x-model="r.flood_height" class="w-24 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"><option value="">—</option><option value="1">(1) Ankle</option><option value="2">(2) Knee</option><option value="3">(3) Waist</option><option value="4">(4) Chest</option><option value="5">(5) Head</option></select></td>
                                    <td class="px-0.5 py-1"><input aria-label="Estimated Damage (PhP)" type="number" min="0" x-model.number="r.est_damage" class="w-24 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4"></td>
                                    <td class="px-1 py-1 no-print"><button aria-label="Remove" @click="businessRows.splice(ri,1)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-700">
                                <td class="px-1.5 py-2 text-center"></td>
                                <td class="px-1 py-2 text-right">TOTAL</td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2 text-center" x-text="formTotal('business', 'workers')"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2"></td><td class="px-1 py-2 text-center" x-text="formTotal('business', 'est_damage').toLocaleString('en-PH', { maximumFractionDigits: 2 })"></td>
                                <td class="no-print"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="text-[10px] text-ink-muted">*Levels of flood height: (1) Ankle; (2) Knee; (3) Waist; (4) Chest; (5) Head</p>
                <button @click="addBusinessRow(); $nextTick(() => window.renderIcons?.())" class="w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Business Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Assigned Kagawad (Purok)" consolidated-label="Barangay Secretary" noted-label="Punong Barangay/BDRRMC Chairperson" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print($event)" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
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
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-orientation="portrait" data-print-area :data-print-under="viewing ? true : null" class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide">RDS</p>
                    <h3 class="font-semibold text-ink-body">Relief Distribution Sheet</h3>
                </div>
                <button aria-label="Close" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                {{-- Fill from records (2026-10-27): the relief RELEASED in an operation;
                     recorded-but-unreleased relief is not counted as given. --}}
                <div x-show="canFill && canFill('rds')" class="no-print rounded-2xl border border-brand-600/15 bg-brand-50/40 p-3 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-ink-body"><span class="font-semibold">Fill from records:</span> the relief released in an operation in this barangay, one line per release.</p>
                        <button type="button" @click="openFill()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-700 bg-white border border-brand-200 rounded-xl px-2.5 py-1.5 hover:border-brand-300"><i data-lucide="database" class="w-3.5 h-3.5"></i> <span x-text="fillOpen ? 'Close' : 'Fill from records'"></span></button>
                    </div>
                    <div x-show="fillOpen" x-cloak class="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr_auto] gap-2 items-end">
                        <div><label for="fill-rds-operation" class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Relief operation</label>
                            <input id="fill-rds-operation" type="text" list="fill-rds-operations" x-model="fill.operation" placeholder="e.g. Typhoon Relief Round 1" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard bg-white">
                            <datalist id="fill-rds-operations"><template x-for="op in operations" :key="op"><option :value="op"></option></template></datalist></div>
                        <div><label for="fill-rds-date" class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Day (optional)</label>
                            <input id="fill-rds-date" type="date" x-model="fill.date" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard bg-white"></div>
                        <div><label for="fill-rds-center" class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Only at a centre (optional)</label>
                            <select id="fill-rds-center" x-model="fill.rdsCenter" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard bg-white"><option value="">Anywhere in the barangay</option><template x-for="c in centers" :key="c.ref"><option :value="c.ref" x-text="c.name"></option></template></select></div>
                        <button type="button" @click="fillFromRecords('rds')" :disabled="fill.loading" class="inline-flex items-center justify-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-xl px-4 py-2"><span x-text="fill.loading ? 'Filling…' : 'Fill'"></span></button>
                    </div>
                    <p x-show="fillOpen && fill.error" x-cloak role="alert" class="text-xs text-rose-700" x-text="fill.error"></p>
                    <p x-show="fillOpen" x-cloak class="text-[11px] text-ink-muted">Choose the barangay below first. Nothing is saved until you choose Save Form; the signature column stays blank for the beneficiaries to sign on paper.</p>
                </div>

                <template x-if="drafted && drafted.rds">
                    <div class="no-print rounded-2xl border border-slate-200 bg-white p-3 space-y-1.5 text-xs">
                        <p class="text-ink-body"><span class="font-semibold">Filled from records:</span> <span x-text="drafted.rds.params.operation"></span>,
                            <span x-text="drafted.rds.summary.rows + (drafted.rds.summary.rows === 1 ? ' release to ' : ' releases to ') + drafted.rds.summary.beneficiaries + (drafted.rds.summary.beneficiaries === 1 ? ' beneficiary.' : ' beneficiaries.')"></span></p>
                        <p x-show="drafted.rds.summary.rows_with_warnings" class="text-amber-800" x-text="drafted.rds.summary.rows_with_warnings + (drafted.rds.summary.rows_with_warnings === 1 ? ' row needs' : ' rows need') + ' a look: see the notes under each name.'"></p>
                        <template x-for="w in drafted.rds.warnings" :key="w.code"><p :class="w.level === 'manual' ? 'text-ink-muted' : 'text-amber-800'" x-text="w.message"></p></template>
                        <p class="text-ink-muted">Cells you change are outlined; the saved form records what the records said and what you changed. Removing a row asks for a reason.</p>
                    </div>
                </template>

                <div class="text-center leading-snug">
                    <p class="text-[11px] uppercase tracking-wide text-ink-muted">Republic of the Philippines</p>
                    <p class="text-[11px] uppercase tracking-wide text-ink-muted" x-text="(barangay ? 'Barangay ' + barangay + ', ' : '') + 'Esperanza, Masbate'"></p>
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-body">Relief Distribution Sheet (RDS)</p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Region</label><input aria-label="Region" type="text" value="V" readonly class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Province</label><input aria-label="Province" type="text" value="Masbate" readonly class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Municipality</label><input aria-label="Municipality" type="text" value="Esperanza" readonly class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none cursor-not-allowed"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Barangay</label><select aria-label="Barangay" x-model="barangay" :disabled="barangayLocked || !!(drafted && drafted.rds)" @change="fillOfficials()" class="w-full rounded-xl py-2 px-3 text-sm text-ink-muted border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none cursor-not-allowed"><option value="">Choose a barangay</option><template x-for="b in barangays" :key="b.name"><option :value="b.name" x-text="b.name"></option></template></select></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Name of Evacuation Center</label><input aria-label="Name of Evacuation Center" type="text" x-model="center" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Type of Disaster</label><input aria-label="Type of Disaster" type="text" x-model="disasterType" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Date of Occurrence</label><input aria-label="Date of Occurrence" type="date" x-model="occurred" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                    <div><label class="block text-[10px] font-medium text-ink-muted uppercase tracking-wide mb-1">Date of Relief Distribution</label><input aria-label="Date of Relief Distribution" type="date" x-model="distributedOn" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-emerald-50/70 border-b border-slate-200">
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted" rowspan="2">No.</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="2">HH Code</th>
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted" rowspan="2">Name of Beneficiary</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted" rowspan="2">No. of Dependents</th>
                                <th class="px-2 py-1 text-center font-semibold text-ink-muted" colspan="2">Assistance Received</th>
                                <th class="px-2 py-2 text-center font-semibold text-ink-muted" rowspan="2">Signature/ Thumbmark<br><span class="font-normal">Received Family Food Packs in Good Condition</span></th>
                                <th class="px-2 py-2 no-print" rowspan="2"></th>
                            </tr>
                            <tr class="bg-emerald-50/70 border-b border-slate-200">
                                <th class="px-2 py-1 text-left font-semibold text-ink-muted">Kind/ Type</th>
                                <th class="px-2 py-1 text-left font-semibold text-ink-muted">Qty/ Unit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(r, ri) in rdsRows" :key="rowKey ? rowKey('rds', r, ri) : ri">
                                <tr :class="rowClass ? rowClass('rds', r, ri) : (ri % 2 === 1 && 'bg-slate-50/50')">
                                    <td class="px-1.5 py-1 text-center text-ink-muted align-top" x-text="ri + 1"></td>
                                    <td class="px-1 py-1 align-top"><input aria-label="HH Code" type="text" x-model="r.hh" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('rds', r, 'hh')"></td>
                                    <td class="px-1 py-1 align-top">
                                        <input aria-label="Name of Beneficiary" type="text" x-model="r.beneficiary" class="w-40 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('rds', r, 'beneficiary')">
                                        <template x-if="rowSource('rds', r)">
                                            <div class="no-print mt-1 w-40 space-y-0.5">
                                                <p class="text-[10px] text-ink-muted font-mono" x-text="rowSource('rds', r).ref + (rowSource('rds', r).released_by ? ' · released by ' + rowSource('rds', r).released_by : '')"></p>
                                                <template x-for="(w, wi) in rowWarnings('rds', r)" :key="wi"><p class="text-[10px] leading-snug" :class="w.level === 'warning' ? 'text-amber-800' : 'text-ink-muted'" x-text="w.message"></p></template>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-1 py-1 align-top"><input aria-label="No. of Dependents" type="number" min="0" x-model.number="r.deps" class="w-14 rounded-xl py-1 px-1 text-xs text-center focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('rds', r, 'deps')"></td>
                                    <td class="px-1 py-1 align-top"><input aria-label="Kind/Type" type="text" x-model="r.kind" class="w-32 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('rds', r, 'kind')"></td>
                                    <td class="px-1 py-1 align-top"><input aria-label="Qty/Unit" type="text" x-model="r.qty" class="w-20 rounded-xl py-1 px-1.5 text-xs focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4" :class="editedClass('rds', r, 'qty')"></td>
                                    {{-- Blank: the beneficiary signs or thumbmarks the printed sheet. No
                                         acknowledgement is recorded in the system (2026-10-27). --}}
                                    <td class="px-1 py-1 text-center min-w-[7rem]"><span class="no-print text-[10px] text-ink-faint italic">signed on the printed sheet</span></td>
                                    <td class="px-1 py-1 no-print"><button aria-label="Remove" @click="removeRow('rds', ri)" class="text-ink-faint hover:text-rose-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button @click="addRdsRow(); $nextTick(() => window.renderIcons?.())" class="no-print w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Beneficiary Row
                </button>

                <x-admin.sakuna.bdrrmc-signoff prepared-label="Barangay Official" prepared-role="Distributed by" consolidated-label="" noted-label="Punong Barangay" noted-role="Certified True and Correct" :two-column="true" />
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <button @click="print($event)" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button @click="save()" :disabled="saving" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-2xl px-4 py-2 transition-colors"><i data-lucide="save" class="w-4 h-4"></i> <span x-text="saving ? 'Saving…' : 'Save Form'"></span></button>
            </div>
        </div>
        </div>
    </div>
    </template>

    {{-- ══════════════════════════════════════ A SAVED FORM, REOPENED ══════════════════════════════════════ --}}
    {{-- Exactly as it was saved (GET /admin/sakuna/bdrrmc-forms/submissions/{ref}),
         with the columns it was filed against. Never drafted again from live
         records (owner, 2026-10-27). --}}
    <template x-if="viewing">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="viewing = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="viewing = null"
        @keydown.space.prevent="viewing = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div data-print-area :data-print-orientation="viewPortrait() ? 'portrait' : null" role="dialog" aria-modal="true" :aria-label="'Saved form ' + viewing.ref" @keydown.escape.window="viewing = null" class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="flex items-start justify-between gap-3 px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div class="min-w-0">
                    <template x-for="line in viewHeading()" :key="line"><p class="text-[10px] uppercase tracking-wide text-ink-muted" x-text="line"></p></template>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide" x-text="viewCode()"></p>
                    <h3 class="font-semibold text-ink-body" x-text="viewName()"></h3>
                    <p class="text-xs text-ink-muted mt-0.5" x-text="viewing.ref + ' · ' + viewing.status + ' · saved by ' + (viewing.submitted_by || '—') + ', ' + viewWhen(viewing.submitted_at) + (viewing.approved_by ? ' · approved by ' + viewing.approved_by + ', ' + viewWhen(viewing.approved_at) : '')"></p>
                </div>
                <button aria-label="Close" @click="viewing = null" class="no-print p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <p x-show="viewing.meta && viewing.meta.provisional" class="rounded-xl bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-900"><strong>Provisional.</strong> <span x-text="viewing.meta ? viewing.meta.provisional_reason : ''"></span></p>

                <dl class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <template x-for="field in viewHeader()" :key="field[0]">
                        <div><dt class="text-[10px] font-medium text-ink-muted uppercase tracking-wide" x-text="field[0]"></dt><dd class="text-sm text-ink-body" x-text="field[1]"></dd></div>
                    </template>
                </dl>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-[10.5px]">
                                <th class="px-2 py-2 text-left font-semibold text-ink-muted">No.</th>
                                <template x-for="c in viewColumns()" :key="c.key"><th class="px-2 py-2 text-left font-semibold text-ink-muted align-bottom" x-text="c.label"></th></template>
                                <th x-show="viewing.form === 'evacuees'" class="px-2 py-2 text-center font-semibold text-ink-muted align-bottom">Total M</th>
                                <th x-show="viewing.form === 'evacuees'" class="px-2 py-2 text-center font-semibold text-ink-muted align-bottom">Total F</th>
                                <th x-show="viewing.form === 'evacuees'" class="px-2 py-2 text-center font-semibold text-ink-muted align-bottom">Grand Total</th>
                                <th x-show="viewing.form === 'evacuees'" class="px-2 py-2 text-center font-semibold text-ink-muted align-bottom">Recorded Headcount</th>
                                <th x-show="viewing.form === 'rds'" class="px-2 py-2 text-center font-semibold text-ink-muted align-bottom">Signature/Thumbmark</th>
                                <th class="no-print px-2 py-2 text-left font-semibold text-ink-muted align-bottom">Source</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-600/[.08]">
                            <template x-for="(row, i) in viewing.rows" :key="i">
                                <tr>
                                    <td class="px-2 py-1 text-ink-muted" x-text="i + 1"></td>
                                    <template x-for="c in viewColumns()" :key="c.key"><td class="px-2 py-1 text-slate-700" x-text="c.type === 'boolean' ? (row[c.key] ? 'Yes' : '') : (row[c.key] === null || row[c.key] === undefined ? '' : row[c.key])"></td></template>
                                    <td x-show="viewing.form === 'evacuees'" class="px-2 py-1 text-center" x-text="viewRowTotals(row).m"></td>
                                    <td x-show="viewing.form === 'evacuees'" class="px-2 py-1 text-center" x-text="viewRowTotals(row).f"></td>
                                    <td x-show="viewing.form === 'evacuees'" class="px-2 py-1 text-center font-semibold" x-text="viewRowTotals(row).m + viewRowTotals(row).f"></td>
                                    <td x-show="viewing.form === 'evacuees'" class="px-2 py-1 text-center" x-text="viewRowSource(i) && viewRowSource(i).headcount !== undefined ? viewRowSource(i).headcount : '—'"></td>
                                    <td x-show="viewing.form === 'rds'" class="px-2 py-1 min-w-[7rem]"></td>
                                    <td class="no-print px-2 py-1 text-[10px] text-ink-muted">
                                        <template x-if="viewRowSource(i) && viewRowSource(i).ref">
                                            <div>
                                                <p class="font-mono" x-text="viewRowSource(i).ref"></p>
                                                <p x-show="(viewRowSource(i).edited || []).length" class="text-amber-800" x-text="'Changed: ' + (viewRowSource(i).edited || []).map((e) => e.column).join(', ')"></p>
                                                <p x-show="(viewRowSource(i).warnings || []).some((w) => w.level !== 'manual')" class="text-amber-800" x-text="(viewRowSource(i).warnings || []).filter((w) => w.level !== 'manual').map((w) => w.message).join(' ')"></p>
                                            </div>
                                        </template>
                                        <span x-show="!(viewRowSource(i) && viewRowSource(i).ref)">Typed by hand</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot x-show="viewing.form !== 'rds'">
                            <tr class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-700">
                                <td class="px-2 py-2" x-text="viewing.form === 'evacuees' ? '' : 'TOTAL'"></td>
                                <template x-for="c in viewColumns()" :key="c.key"><td class="px-2 py-2" x-text="viewTotals(c) ? viewColumnTotal(c.key).toLocaleString('en-PH', { maximumFractionDigits: 2 }) : (c.key === 'head' && viewing.form === 'evacuees' ? 'Grand Total' : '')"></td></template>
                                <td x-show="viewing.form === 'evacuees'" class="px-2 py-2 text-center" x-text="viewing.rows.reduce((s, r) => s + viewRowTotals(r).m, 0)"></td>
                                <td x-show="viewing.form === 'evacuees'" class="px-2 py-2 text-center" x-text="viewing.rows.reduce((s, r) => s + viewRowTotals(r).f, 0)"></td>
                                <td x-show="viewing.form === 'evacuees'" class="px-2 py-2 text-center" x-text="viewing.rows.reduce((s, r) => s + viewRowTotals(r).m + viewRowTotals(r).f, 0)"></td>
                                <td x-show="viewing.form === 'evacuees'" class="px-2 py-2 text-center" x-text="viewing.meta && viewing.meta.summary ? viewing.meta.summary.headcount : ''"></td>
                                <td class="no-print"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- The family-size lines as the form was saved, with the rule then in force. --}}
                <div x-show="viewing.form === 'evacuees' && viewing.meta && viewing.meta.families_lt5 !== undefined" class="rounded-xl border border-slate-200 p-3 text-xs space-y-1 text-slate-700">
                    <p>Total Number of Families with less than 5 members: <strong x-text="viewing.meta ? viewing.meta.families_lt5 + (viewing.meta.five_member_rule === 'fewer' ? viewing.meta.families_eq5 : 0) : ''"></strong></p>
                    <p>Total Number of Families with more than 5 members: <strong x-text="viewing.meta ? viewing.meta.families_gt5 + (viewing.meta.five_member_rule === 'more' ? viewing.meta.families_eq5 : 0) : ''"></strong></p>
                    <p x-show="viewing.meta && viewing.meta.five_member_rule === 'unresolved' && viewing.meta.families_eq5" class="text-amber-800">Families with exactly 5 members: <strong x-text="viewing.meta ? viewing.meta.families_eq5 : ''"></strong>, not counted in either line above until the LGU confirms where they belong.</p>
                    <p x-show="viewing.meta && viewing.meta.summary && viewing.meta.summary.unclassified" class="text-ink-muted" x-text="viewing.meta && viewing.meta.summary ? 'Recorded headcount ' + viewing.meta.summary.headcount + '; people in the age columns ' + viewing.meta.summary.grand_total + '. The difference had no age or sex on record.' : ''"></p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 text-center">
                    <template x-for="s in [
                        { role: viewing.form === 'rds' ? 'Distributed by' : 'Prepared by', name: viewing.meta ? viewing.meta.prepared_by : '', label: viewSignoff().prepared },
                        { role: 'Consolidated by', name: viewing.form === 'rds' ? null : (viewing.meta ? viewing.meta.consolidated_by : ''), label: 'Barangay Secretary' },
                        { role: viewing.form === 'rds' ? 'Certified True and Correct' : 'Noted by', name: viewing.meta ? viewing.meta.noted_by : '', label: viewSignoff().noted },
                    ].filter((x) => x.name !== null)" :key="s.role">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide mb-4" x-text="s.role"></p>
                            <p class="text-sm font-semibold text-slate-700 border-b border-slate-300 pb-1 mb-1" x-text="s.name || ' '"></p>
                            <p class="text-xs text-ink-muted" x-text="s.label"></p>
                            <p class="text-[10px] text-ink-muted mt-1">Signature Over Printed Name</p>
                        </div>
                    </template>
                </div>

                {{-- Where the rows came from: on the screen, not on the paper. --}}
                <div x-show="viewing.meta && viewing.meta.source" class="no-print rounded-xl bg-slate-50 border border-slate-200 p-3 text-xs text-ink-muted space-y-1">
                    <template x-if="viewing.meta && viewing.meta.source && viewing.meta.source.kind === 'records'">
                        <div class="space-y-1">
                            <p><span class="font-semibold text-ink-body">Filled from records</span>, drafted <span x-text="viewWhen(viewing.meta.source.generated_at || viewing.meta.source.redrafted_at)"></span>, checked again and saved <span x-text="viewWhen(viewing.meta.source.saved_at)"></span>. Counting rules <span class="font-mono" x-text="viewing.meta.source.rules_version"></span>.</p>
                            <p x-text="viewing.meta.source.rows.filter((r) => r.ref).length + ' rows from the records, ' + viewing.meta.source.rows.filter((r) => (r.edited || []).length).length + ' of them changed; ' + viewing.meta.source.rows.filter((r) => !r.ref).length + ' typed by hand.'"></p>
                            <template x-for="r in viewing.meta.source.removed || []" :key="r.ref"><p x-text="'Left out ' + r.ref + ': ' + r.reason"></p></template>
                        </div>
                    </template>
                    <p x-show="viewing.meta && viewing.meta.source && viewing.meta.source.kind === 'manual'">Typed by hand.</p>
                </div>
            </div>
            <div class="no-print px-6 py-4 border-t border-brand-600/10 bg-slate-50/60 rounded-b-2xl flex items-center justify-end gap-2 shrink-0">
                <span x-show="viewing.status === 'Submitted' && viewing.submitted_by_me && canApprove" class="mr-auto text-xs text-ink-muted">You filed this form, so another approver signs it off.</span>
                <button @click="printSaved($event)" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                <button x-show="viewing.can_approve" @click="approve(viewing)" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-4 py-2 transition-colors"><i data-lucide="check" class="w-4 h-4"></i> Approve</button>
            </div>
        </div>
        </div>
    </div>
    </template>

</div>

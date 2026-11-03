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
        formCodes: @js(collect($bdrrmcForms)->pluck('code', 'key')),
        rowsOf: { evacuees: 'evacueeRows', 'human-housing': 'housingRows', agriculture: 'agriRows', fishing: 'fishingRows', business: 'businessRows', rds: 'rdsRows' },

        async init() {
            const account = this.$store.session.account || {};
            this.barangayLocked = !!account.scope && account.scope !== 'Municipal';
            this.barangay = this.barangayLocked ? account.scope : '';
            // Prepared by is written in by whoever prepares it, as on the paper
            // (owner, 2026-10-27): never the signed-in officer.
            this.signoff.prepared = '';
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
        // A blank form is a blank sheet: its numbered rows empty, as on paper.
        resetRows(key) {
            const name = this.rowsOf[key];
            this[name] = [];
            this.padRows(key);
        },
        blankRow(key) {
            const blanks = {
                'evacuees': () => ({ purok:'', hh:'', head:'', pre03m:'',pre03f:'',pre46m:'',pre46f:'',ch712m:'',ch712f:'',yth1317m:'',yth1317f:'',ad1859m:'',ad1859f:'',sc60m:'',sc60f:'', assist:'' }),
                'human-housing': () => ({ hh:'', head:'', deps:'', cas_m:'',cas_f:'', miss_m:'',miss_f:'', resc_m:'',resc_f:'', roof_slight_nipa:'',roof_slight_gi:'', roof_partial_nipa:'',roof_partial_gi:'', roof_total_nipa:'',roof_total_gi:'', wall_partial:'',wall_total:'', other_partial:'',other_total:'', flood_height:'', est_damage:'' }),
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
        // By value: a blank cell is nothing, not a string to join.
        evacueeFamTotal(r) { return this.sexTotal(r, 'm') + this.sexTotal(r, 'f'); },
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
        // The list's Approve opens the form first; the form asks once more.
        approveAsk: '',
        async approve(sub) {
            if (!this.viewing || this.viewing.ref !== sub.ref) { this.approveAsk = sub.ref; this.viewSubmission(sub); return; }
            if (this.approveAsk !== sub.ref) { this.approveAsk = sub.ref; return; }
            this.approveAsk = '';
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
    class="space-y-3"
>
    {{-- Sakuna Redesign (2026-11-02): the six forms as rows in one card,
         then the saved ones. --}}
    <section class="sk-card overflow-hidden">
        <div class="sk-chead">
            <h2 class="sk-ctitle inline-flex items-center gap-1.5">BDRRMC Capture Forms
                <button type="button" data-info class="sk-iconbtn sk-iconbtn-bare !w-6 !h-6"
                    aria-label="About capture forms"
                    title="The barangay DRRM capture forms (the layout Barangay Labangtaytay files with the MDRRMO/PDRRMC): fill one out, save it to the system, and print it.">
                    <i data-lucide="info" class="!w-3.5 !h-3.5"></i>
                </button>
            </h2>
            <span class="sk-csub">Fill out, save to the system, then print</span>
        </div>
        <p class="px-3.5 py-2 text-xs sk-muted border-b border-[#EEF2F7]">DROMIC: the DSWD's Disaster Response Operations Monitoring and Information Center report. RDS: Relief Distribution Sheet.</p>
        {{-- Each form its own card (owner, 2026-11-03): white tiles on the
             tinted well, equal heights per row, a hover edge because each is
             something to pick. --}}
        <div class="grid gap-2.5 p-3 bg-[#F4F7FC]" style="grid-template-columns: repeat(auto-fill, minmax(min(420px, 100%), 1fr));">
            @foreach($bdrrmcForms as $f)
                <div class="sk-tile sk-form-tile flex items-center gap-3 px-3.5 py-3">
                    <span class="w-[34px] h-[34px] rounded-[9px] {{ $f['color'] }} flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $f['icon'] }}" class="w-4 h-4"></i>
                    </span>
                    <div class="flex-grow min-w-0">
                        <p class="text-xs font-semibold tracking-[.06em] uppercase text-[#5F6B80]">{{ $f['code'] }}</p>
                        <p class="sk-b text-[13px] leading-snug text-pretty">{{ $f['name'] }}</p>
                        <p class="sk-sub text-pretty">{{ $f['desc'] }}</p>
                    </div>
                    <button
                        type="button"
                        @click="openForm = '{{ $f['key'] }}'; fillOpen = false; fill.error = ''; $nextTick(() => window.renderIcons?.())"
                        class="sk-btn sk-btn-sm sk-btn-soft shrink-0"
                    >
                        Open form <i data-lucide="arrow-right"></i>
                    </button>
                </div>
            @endforeach
        </div>
    </section>

    <section x-show="canListSubmissions" x-cloak class="sk-card overflow-hidden">
        <div class="sk-chead">
            <h2 class="sk-ctitle">Submitted forms <span class="sk-csub">· saved capture forms, newest first. The MDRRMO approves each one.</span></h2>
            <button type="button" @click="loadSubmissions()" class="sk-btn sk-btn-sm"><i data-lucide="refresh-cw"></i> Refresh</button>
        </div>
        <p x-show="submissionsLoading" class="sk-empty">Loading…</p>
        <p x-show="!submissionsLoading && !submissions.length" class="sk-empty">No capture form has been saved yet.</p>
        <div x-show="submissions.length" class="sk-scroll" tabindex="0" role="region" aria-label="Submitted forms table (scrolls sideways)">
            <table class="sk-tbl">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Form</th>
                        <th>Barangay</th>
                        <th>Disaster</th>
                        <th class="sk-r">Rows</th>
                        <th>Saved</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="sub in submissions" :key="sub.ref">
                        <tr>
                            <td><span class="sk-mono" x-text="sub.ref"></span></td>
                            <td class="sk-b min-w-[14rem]" x-text="formNames[sub.form] || sub.form"></td>
                            <td x-text="sub.barangay"></td>
                            <td class="sk-muted" x-text="sub.period || '—'"></td>
                            <td class="sk-r tabular-nums" x-text="sub.row_count"></td>
                            <td class="whitespace-nowrap"><div class="text-ink-body" x-text="sub.submitted_by || '—'"></div><div class="sk-sub" x-text="sub.submitted_at ? new Date(sub.submitted_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : ''"></div></td>
                            <td><span class="sk-pill" :class="statusBadge(sub.status)" x-text="sub.status"></span></td>
                            <td class="whitespace-nowrap">
                                <button type="button" @click="viewSubmission(sub)" :disabled="viewLoading" class="sk-btn sk-btn-sm sk-btn-soft">View</button>
                                {{-- Who filed a form cannot approve it; the server refuses it too (2026-10-27). --}}
                                <button type="button" x-show="sub.can_approve" @click="approve(sub)" class="sk-btn sk-btn-sm sk-btn-ok" :aria-label="'Review and approve ' + sub.ref">Review and approve…</button>
                                <span x-show="sub.status === 'Submitted' && sub.submitted_by_me && canApprove" class="sk-ssub">Filed by you</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    {{-- The paper forms (bdrrmc-paper.blade.php), on screen and in print, in the
         barangay files' own look: A4, Arial, ruled black lines, grey header
         cells, and the files' highlights (yellow HH Code, green Business Code,
         red title words and flood-height note), copied from the paper rather
         than the portal's palette. Print copies the open form into
         #bdrrmc-print-root and prints only its sheets, one A4 page each, in the
         file's orientation, without the buttons or the on-screen notes. --}}
    <style>
        .pp-paper { font-family: Arial, Helvetica, sans-serif; color: #000; overflow-x: auto; padding-bottom: 4px; }
        .pp-sheet { background: #fff; margin: 0 auto 16px; padding: 5mm 6mm; box-shadow: 0 1px 4px rgba(0,0,0,.18); box-sizing: border-box; }
        .pp-sheet.pp-landscape { width: 297mm; min-height: 210mm; }
        .pp-sheet.pp-portrait { width: 210mm; min-height: 297mm; }
        .pp-heading { margin-bottom: 4pt; line-height: 1.25; }
        .pp-heading p { margin: 0; }
        .pp-center { text-align: center; }
        .pp-b { font-weight: 700; }
        .pp-10 { font-size: 10pt; } .pp-11 { font-size: 11pt; } .pp-12 { font-size: 12pt; }
        .pp-red { color: #c00000; }
        .pp-upper { text-transform: uppercase; }
        .pp-two { display: grid; grid-template-columns: 1fr 1fr; gap: 0 24pt; margin-top: 3pt; }
        .pp-two-rds { grid-template-columns: 0.75fr 1fr; }
        .pp-lines { display: flex; flex-direction: column; gap: 0; margin-top: 1pt; }
        .pp-line { display: flex; flex-wrap: wrap; gap: 0 26pt; margin-top: 3pt; }
        .pp-field { display: inline-flex; align-items: flex-end; gap: 2pt; white-space: nowrap; line-height: 11pt; height: 12.5pt; }
        .pp-label { line-height: 11pt; }
        .pp-value { display: inline-block; min-width: 60pt; height: 11pt; border: 0; border-bottom: 1px solid #000; border-radius: 0; background: transparent; font: inherit; color: #000; padding: 0 2pt; line-height: 11pt; box-sizing: content-box; vertical-align: bottom; margin: 0; }
        .pp-value.pp-b { font-weight: 700; }
        .pp-filled { min-height: 1.2em; }
        .pp-w-60 .pp-value { min-width: 30pt; width: 30pt; } .pp-w-80 .pp-value { width: 80pt; } .pp-w-110 .pp-value { width: 110pt; }
        .pp-w-120 .pp-value { width: 120pt; } .pp-w-130 .pp-value { width: 130pt; } .pp-w-140 .pp-value { width: 140pt; }
        .pp-w-150 .pp-value { width: 150pt; } .pp-w-160 .pp-value { width: 160pt; } .pp-w-170 .pp-value { width: 170pt; }
        .pp-w-190 .pp-value { width: 190pt; } .pp-w-240 .pp-value { width: 240pt; }
        .pp-input:focus { outline: 2px solid #93c5fd; outline-offset: 1px; }
        select.pp-input { padding-right: 2pt; appearance: auto; }
        .pp-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8.5pt; margin-top: 2pt; }
        .pp-table th, .pp-table td { border: 1px solid #000; padding: 0 2pt; }
        .pp-table thead th { background: #d9d9d9; font-weight: 700; font-size: 9pt; text-align: center; vertical-align: middle; line-height: 1.05; padding: 1pt 1pt; hyphens: manual; }
        .pp-table thead th.pp-small { font-size: 7pt; }
        .pp-table thead th.pp-hl-yellow { background: #ffff00; }
        .pp-table thead th.pp-hl-green { background: #66ff33; }
        .pp-table th.pp-caption { background: transparent; border: 0; font-size: 9.5pt; padding: 0; }
        .pp-sub { display: block; font-size: 6.5pt; font-weight: 400; }
        .pp-sub-bold { font-weight: 700; font-size: 7pt; }
        .pp-table tbody td { height: var(--pp-row, 15pt); vertical-align: middle; }
        .pp-table td.pp-t-no { font-size: 9pt; position: relative; }
        .pp-table td.pp-t-int, .pp-table td.pp-t-num, .pp-table td.pp-t-sum-m, .pp-table td.pp-t-sum-f, .pp-table td.pp-t-sum-all, .pp-table td.pp-t-flood { text-align: center; }
        .pp-cell-input { width: 100%; border: 0; background: transparent; font: inherit; color: #000; padding: 0; }
        .pp-cell-input.pp-num { text-align: center; -moz-appearance: textfield; }
        .pp-cell-input::-webkit-outer-spin-button, .pp-cell-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .pp-cell-input:focus { outline: 2px solid #93c5fd; }
        .pp-total td { background: #d9d9d9; height: var(--pp-row, 15pt); font-weight: 700; }
        .pp-total td.pp-num { text-align: center; }
        .pp-total-label.pp-al-center { text-align: center; } .pp-total-label.pp-al-right { text-align: right; } .pp-total-label.pp-al-left { text-align: left; }
        .pp-note { font-style: italic; text-decoration: underline; font-size: 9pt; margin: 1pt 0 0; }
        .pp-flood { color: #c00000; font-weight: 700; font-style: italic; font-size: 9pt; margin: 2pt 0 0; }
        /* The portrait files print larger type than the landscape ones. */
        .pp-type-mid .pp-table thead th { font-size: 10.5pt; } .pp-type-mid .pp-table { font-size: 10pt; } .pp-type-mid .pp-table td.pp-t-no { font-size: 10pt; }
        .pp-type-large .pp-10 { font-size: 12pt; } .pp-type-large .pp-field, .pp-type-large .pp-label, .pp-type-large .pp-value { line-height: 13pt; height: auto; }
        .pp-type-large .pp-table thead th { font-size: 11.5pt; } .pp-type-large .pp-table thead th .pp-sub { font-size: 9pt; }
        .pp-type-large .pp-table { font-size: 11pt; } .pp-type-large .pp-table td.pp-t-no { font-size: 12pt; }
        .pp-type-large .pp-sign { font-size: 12pt; }
        .pp-notes-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 12pt; }
        .pp-digital { font-size: 7pt; margin-top: 1pt; line-height: 1.15; text-align: right; max-width: 62%; }
        .pp-digital p { margin: 0; }
        .pp-sign { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 20pt; margin-top: 3pt; font-size: 10pt; line-height: 1.15; }
        .pp-sign.pp-sign-two { grid-template-columns: repeat(2, 1fr); }
        .pp-signer p { margin: 0; }
        .pp-sign-name { display: block; width: 75%; height: 11pt; line-height: 11pt; margin-top: 9pt; border: 0; border-bottom: 1px solid #000; background: transparent; font: inherit; text-decoration: none; padding: 0; }
        .pp-remove { position: absolute; right: 1px; top: 50%; transform: translateY(-50%); color: #e11d48; font-size: 11pt; line-height: 1; background: #fff; border-radius: 4px; padding: 0 2px; }
        .pp-notes { font-size: 7pt; line-height: 1.2; padding: 1pt 0; }
        .pp-notes p { margin: 0; }
        .pp-mono { font-family: ui-monospace, monospace; color: #475569; }
        .pp-w-conflict { color: #be123c; font-weight: 600; } .pp-w-warning { color: #92400e; } .pp-w-manual { color: #64748b; }
        .pp-hint { font-size: 7pt; color: #94a3b8; font-style: italic; }
        .pp-table tr.pp-conflict td { background: #fff1f2; } .pp-table tr.pp-warning td { background: #fffbeb; }
        #bdrrmc-print-root { display: none; }
        @media print {
            @page { size: A4 landscape; margin: 0; }
            @page bdrrmc-portrait { size: A4 portrait; margin: 0; }
            #bdrrmc-print-root[data-orientation="portrait"] { page: bdrrmc-portrait; }
            html.bdrrmc-printing body > *:not(#bdrrmc-print-root) { display: none !important; }
            html.bdrrmc-printing #bdrrmc-print-root { display: block !important; }
            #bdrrmc-print-root [data-print-area] { position: static !important; width: auto !important; height: auto !important; overflow: visible !important; box-shadow: none !important; border-radius: 0 !important; display: block !important; background: #fff !important; }
            #bdrrmc-print-root .overflow-y-auto, #bdrrmc-print-root .overflow-x-auto, #bdrrmc-print-root .overflow-hidden { overflow: visible !important; height: auto !important; padding: 0 !important; }
            #bdrrmc-print-root .pp-paper, #bdrrmc-print-root .pp-paper ~ *, #bdrrmc-print-root [data-print-area] .space-y-4 > * { margin: 0 !important; }
            #bdrrmc-print-root .pp-paper, #bdrrmc-print-root .pp-wrap { overflow: visible !important; padding: 0 !important; margin: 0 !important; }
            #bdrrmc-print-root .no-print, #bdrrmc-print-root button { display: none !important; }
            #bdrrmc-print-root .pp-sheet { box-shadow: none !important; margin: 0 !important; break-after: page; page-break-after: always; }
            #bdrrmc-print-root .pp-sheet:last-of-type { break-after: auto; page-break-after: auto; }
            #bdrrmc-print-root .pp-sheet.pp-landscape { width: 297mm; min-height: 0; height: auto; }
            #bdrrmc-print-root .pp-sheet.pp-portrait { width: 210mm; min-height: 0; height: auto; }
            #bdrrmc-print-root .pp-table tr td { background: transparent; }
            #bdrrmc-print-root .pp-table .pp-total td { background: #d9d9d9; }
            #bdrrmc-print-root .pp-table thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            #bdrrmc-print-root .pp-total td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            #bdrrmc-print-root .pp-value, #bdrrmc-print-root .pp-sign-name { outline: 0 !important; }

            html:not(.bdrrmc-printing) body * { visibility: hidden !important; }
            html:not(.bdrrmc-printing) [data-print-area]:not([data-print-under]), html:not(.bdrrmc-printing) [data-print-area]:not([data-print-under]) * { visibility: visible !important; }
            html:not(.bdrrmc-printing) [data-print-area]:not([data-print-under]) { position: absolute !important; inset: 0 !important; height: auto !important; box-shadow: none !important; }
            html:not(.bdrrmc-printing) [data-print-area] .overflow-y-auto, html:not(.bdrrmc-printing) [data-print-area] .overflow-x-auto { overflow: visible !important; height: auto !important; }
            html:not(.bdrrmc-printing) [data-print-area] .no-print, html:not(.bdrrmc-printing) [data-print-area] button { display: none !important; }
        }
    </style>

    {{-- ══════════════════════════════ THE OPEN FORM, AS THE PAPER LAYS IT OUT ══════════════════════════════ --}}
    {{-- One window for all six forms (they were six hand-drawn tables): the
         paper form is drawn from resources/js/bdrrmc-paper.js, so filling it
         in, printing it and reprinting a saved copy look the same (owner,
         2026-10-27). --}}
    <template x-if="openForm">
    <div style="position: fixed; inset: 0; z-index: 50;">
        <div @click="openForm = null" style="position: absolute; inset: 0;" class="bg-navy-950/60 backdrop-blur-sm"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openForm = null"
        @keydown.space.prevent="openForm = null"></div>
        <div style="position: fixed; inset: 0.75rem;" class="sm:inset-4">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100" data-print-area :data-print-orientation="paperSpec('edit').orientation" :data-print-under="viewing ? true : null" class="relative bg-white rounded-[28px] shadow-float w-full h-full flex flex-col overflow-hidden">
            <div class="no-print flex items-center justify-between px-6 py-4 border-b border-brand-600/10 shrink-0">
                <div>
                    <p class="text-xs font-semibold text-ink-muted uppercase tracking-wide" x-text="formCodes[openForm]"></p>
                    <h3 class="font-semibold text-ink-body" x-text="formNames[openForm]"></h3>
                </div>
                <button aria-label="Close window" @click="openForm = null" class="p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4 bg-slate-100/70">
                <template x-if="openForm === 'evacuees'">
                <div class="space-y-4">
                    {{-- Fill from records (2026-10-27): the families staying at a center at the
                         time of the report, from check-ins, transfers and check-outs.
                         Nothing is saved until Save Form. --}}
                    <div x-show="canFill && canFill('evacuees')" class="no-print rounded-2xl border border-brand-600/15 bg-brand-50/40 p-3 space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs text-ink-body"><span class="font-semibold">Fill from records:</span> the families staying at an evacuation center at the date and time of the report.</p>
                            <button type="button" @click="openFill()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-700 bg-white border border-brand-200 rounded-xl px-2.5 py-1.5 hover:border-brand-300"><i data-lucide="database" class="w-3.5 h-3.5"></i> <span x-text="fillOpen ? 'Close' : 'Fill from records'"></span></button>
                        </div>
                        <div x-show="fillOpen" x-cloak class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2 items-end">
                            <div><label for="fill-evac-center" class="block text-xs font-medium text-ink-muted uppercase tracking-wide mb-1">Evacuation center</label>
                                <select id="fill-evac-center" x-model="fill.center" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard bg-white"><option value="">Choose a center</option><template x-for="c in centers" :key="c.ref"><option :value="c.ref" x-text="c.name + ' · Brgy. ' + c.barangay + (c.status === 'Processing' ? ' · open' : '')"></option></template></select></div>
                            <div><label for="fill-evac-asof" class="block text-xs font-medium text-ink-muted uppercase tracking-wide mb-1">As of (Esperanza time)</label>
                                <input id="fill-evac-asof" type="datetime-local" x-model="fill.asOf" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard bg-white"></div>
                            <button type="button" @click="fillFromRecords('evacuees')" :disabled="fill.loading" class="inline-flex items-center justify-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-xl px-4 py-2"><span x-text="fill.loading ? 'Filling…' : 'Fill'"></span></button>
                        </div>
                        <p x-show="fillOpen && fill.error" x-cloak role="alert" class="text-xs text-rose-700" x-text="fill.error"></p>
                        <p x-show="fillOpen" x-cloak class="text-xs text-ink-muted">Nothing is saved until you choose Save Form. What the records do not hold stays blank and is flagged; type it if you know it.</p>
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
                </div>
                </template>
                <template x-if="openForm === 'rds'">
                <div class="space-y-4">
                    {{-- Fill from records (2026-10-27): the relief RELEASED in an operation;
                         recorded-but-unreleased relief is not counted as given. --}}
                    <div x-show="canFill && canFill('rds')" class="no-print rounded-2xl border border-brand-600/15 bg-brand-50/40 p-3 space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs text-ink-body"><span class="font-semibold">Fill from records:</span> the relief released in an operation in this barangay, one line per release.</p>
                            <button type="button" @click="openFill()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-700 bg-white border border-brand-200 rounded-xl px-2.5 py-1.5 hover:border-brand-300"><i data-lucide="database" class="w-3.5 h-3.5"></i> <span x-text="fillOpen ? 'Close' : 'Fill from records'"></span></button>
                        </div>
                        <div x-show="fillOpen" x-cloak class="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr_auto] gap-2 items-end">
                            <div><label for="fill-rds-operation" class="block text-xs font-medium text-ink-muted uppercase tracking-wide mb-1">Relief operation</label>
                                <input id="fill-rds-operation" type="text" list="fill-rds-operations" x-model="fill.operation" placeholder="e.g. Typhoon Relief Round 1" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard bg-white">
                                <datalist id="fill-rds-operations"><template x-for="op in operations" :key="op"><option :value="op"></option></template></datalist></div>
                            <div><label for="fill-rds-date" class="block text-xs font-medium text-ink-muted uppercase tracking-wide mb-1">Day (optional)</label>
                                <input id="fill-rds-date" type="date" x-model="fill.date" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard bg-white"></div>
                            <div><label for="fill-rds-center" class="block text-xs font-medium text-ink-muted uppercase tracking-wide mb-1">Only at a center (optional)</label>
                                <select id="fill-rds-center" x-model="fill.rdsCenter" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard bg-white"><option value="">Anywhere in the barangay</option><template x-for="c in centers" :key="c.ref"><option :value="c.ref" x-text="c.name"></option></template></select></div>
                            <button type="button" @click="fillFromRecords('rds')" :disabled="fill.loading" class="inline-flex items-center justify-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-xl px-4 py-2"><span x-text="fill.loading ? 'Filling…' : 'Fill'"></span></button>
                        </div>
                        <p x-show="fillOpen && fill.error" x-cloak role="alert" class="text-xs text-rose-700" x-text="fill.error"></p>
                        <p x-show="fillOpen" x-cloak class="text-xs text-ink-muted">Choose the barangay below first. Nothing is saved until you choose Save Form; the signature column stays blank for the beneficiaries to sign on paper.</p>
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
                </div>
                </template>

                {{-- Registered owners (owner, 2026-10-30): pick a farmer, fisherfolk or
                     business owner from the registry so the name is not typed again;
                     anyone not registered is still written in on the form. --}}
                <div x-show="['agriculture', 'fishing', 'business'].includes(openForm) && can('sakuna_damage','create')" x-cloak class="no-print rounded-xl border border-slate-200 bg-white p-3 space-y-2"
                     x-data="{ q: '', found: [], kinds: { agriculture: 'farmer', fishing: 'fisherfolk', business: 'business' },
                        async search() {
                            if (this.q.trim().length < 2) { this.found = []; return; }
                            try { this.found = (await $api.get('/admin/sakuna/registered-people', { params: { kind: this.kinds[openForm], q: this.q.trim(), barangay: barangay || undefined } })).data || []; } catch { this.found = []; }
                        },
                        pick(p) {
                            const rows = $data[rowsOf[openForm]];
                            const blank = rows.find((r) => Object.entries(r).every(([k, v]) => k.startsWith('_') || v === '' || v === null));
                            const row = blank || (blankRow(openForm), rows[rows.length - 1]);
                            row.owner = p.name; row.owner_ref = p.ref;
                            this.q = ''; this.found = [];
                            this.$dispatch('toast', { message: p.name + ' added from the registry.', variant: 'success' });
                        } }">
                    <label class="text-xs font-semibold text-ink" :for="'reg-owner-' + openForm" x-text="{ agriculture: 'Add a registered farmer', fishing: 'Add registered fisherfolk', business: 'Add a registered business owner or vendor' }[openForm] || ''"></label>
                    <input :id="'reg-owner-' + openForm" type="search" x-model="q" @input.debounce.400ms="search()" placeholder="Name or RES number" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm">
                    <div class="flex flex-wrap gap-2">
                        <template x-for="p in found" :key="p.ref">
                            <button type="button" @click="pick(p)" class="text-xs px-2.5 py-1 rounded-full border border-slate-200 hover:border-brand-300" x-text="p.name + ' · ' + p.barangay + (p.sitio ? ', ' + p.sitio : '')"></button>
                        </template>
                    </div>
                    <p class="text-xs text-ink-muted">Only residents classified on the DRRM list appear. Not registered? Write the owner in on the form.</p>
                </div>

                @include('admin.partials.sakuna.bdrrmc-paper', ['mode' => 'edit'])

                <button type="button" @click="addPage(openForm); $nextTick(() => window.renderIcons?.())" class="no-print w-full flex items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-300 hover:border-brand-300 hover:bg-brand-50/40 text-xs font-medium text-ink-muted hover:text-brand-600 py-2 transition-all duration-[160ms] ease-standard">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> <span x-text="'Add another sheet (' + paperSpec('edit').rows + ' more rows)'"></span>
                </button>
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
            <div class="flex items-start justify-between gap-3 px-6 py-4 border-b border-brand-600/10 shrink-0" :class="paperFits() && 'no-print'">
                <div class="min-w-0">
                    <template x-for="line in viewHeading()" :key="line"><p class="text-[10px] uppercase tracking-wide text-ink-muted" x-text="line"></p></template>
                    <p class="text-[10px] font-semibold text-ink-muted uppercase tracking-wide" x-text="viewCode()"></p>
                    <h3 class="font-semibold text-ink-body" x-text="viewName()"></h3>
                    <p class="text-xs text-ink-muted mt-0.5" x-text="viewing.ref + ' · ' + viewing.status + ' · saved by ' + (viewing.submitted_by || '—') + ', ' + viewWhen(viewing.submitted_at) + (viewing.approved_by ? ' · approved by ' + viewing.approved_by + ', ' + viewWhen(viewing.approved_at) : '')"></p>
                </div>
                <button aria-label="Close window" @click="viewing = null" class="no-print p-2 rounded-2xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 space-y-4">
                <p x-show="viewing.meta && viewing.meta.provisional" class="no-print rounded-xl bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-900"><strong>Provisional.</strong> <span x-text="viewing.meta ? viewing.meta.provisional_reason : ''"></span></p>

                {{-- Drawn as the paper form when it was filed against the paper's
                     columns (paperFits); a form saved under older columns keeps the
                     plain table it was saved with, so nothing it holds is hidden. --}}
                <template x-if="paperFits()">
                    <div class="pp-wrap -mx-6 px-6 py-4 bg-slate-100/70">
                        @include('admin.partials.sakuna.bdrrmc-paper', ['mode' => 'view'])
                    </div>
                </template>
                <template x-if="paperFits()">
                    <div class="no-print rounded-xl bg-slate-50 border border-slate-200 p-3 text-xs text-ink-muted space-y-0.5" x-show="(viewing.meta && viewing.meta.source && viewing.meta.source.rows || []).some((r) => r.ref)">
                        <p class="font-semibold text-ink-body">Where each row came from</p>
                        <template x-for="(row, i) in viewing.rows" :key="i">
                            <p x-show="viewRowSource(i) && viewRowSource(i).ref" x-text="'Row ' + (i + 1) + ': ' + (viewRowSource(i) ? viewRowSource(i).ref : '') + ((viewRowSource(i) && (viewRowSource(i).edited || []).length) ? ' · changed: ' + viewRowSource(i).edited.map((e) => e.column).join(', ') : '') + ((viewRowSource(i) && (viewRowSource(i).warnings || []).some((w) => w.level !== 'manual')) ? ' · ' + viewRowSource(i).warnings.filter((w) => w.level !== 'manual').map((w) => w.message).join(' ') : '')"></p>
                        </template>
                    </div>
                </template>
                <template x-if="!paperFits()">
                <div class="space-y-4">
                    <dl class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <template x-for="field in viewHeader()" :key="field[0]">
                            <div><dt class="text-xs font-medium text-ink-muted uppercase tracking-wide" x-text="field[0]"></dt><dd class="text-sm text-ink-body" x-text="field[1]"></dd></div>
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
                                        <td class="no-print px-2 py-1 text-xs text-ink-muted">
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
    
                </div>
                </template>

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
                <span x-show="viewing.can_approve && approveAsk === viewing.ref" x-cloak class="mr-auto text-sm text-ink-body" role="status">Approve this form as checked? The paper signature still applies.</span>
                <button x-show="viewing.can_approve && approveAsk === viewing.ref" x-cloak @click="approveAsk = ''" class="inline-flex items-center text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-slate-300 transition-colors">Not yet</button>
                <button x-show="viewing.can_approve" @click="approve(viewing)" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-2xl px-4 py-2 transition-colors"><i data-lucide="check" class="w-4 h-4"></i> <span x-text="approveAsk === viewing.ref ? 'Yes, approve' : 'Approve…'">Approve…</span></button>
            </div>
        </div>
        </div>
    </div>
    </template>

</div>

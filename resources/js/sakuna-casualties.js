// Sakuna's Casualties tab (client request, 2026-11-14): the Missing Persons
// record (owner, 2026-10-30) grown into the casualty record the NDRRMC
// situation report counts - the dead, the injured and the missing - including
// people who are not from Esperanza and residents hurt or killed outside it.
//
//   The list and its figures   GET  /admin/sakuna/missing (+ /summary)
//   One case                   GET  /admin/sakuna/missing/{ref}
//   Record a person            POST /admin/sakuna/missing (incident_ref; none for a case outside Esperanza)
//   Correct the details        PUT  /admin/sakuna/missing/{ref}
//   Found, died, closed        POST /admin/sakuna/missing/{ref}/status
//   For validation, Validated  POST /admin/sakuna/missing/{ref}/validation
//   Written consent to post    POST /admin/sakuna/missing/{ref}/consent
//   A Balita draft             POST /admin/sakuna/missing/{ref}/announcement
//   The casualty list          GET  /admin/sakuna/missing/export (edit; audited)
//
// Used as x-data="sakunaCasualties" inside the Sakuna page, so `this` also
// reaches the page's can(), toast(), saveCopy()/lastCopy()/useCopy()
// and markMissingOff(). The casualty list's CSV is written here, with
// resident-import's writer (BOM, quotes doubled, formulas guarded).

import api from './api/client';
import { downloadCsv } from './resident-import';

const FILTERS = { q: '', category: '', validation: '', lives: '', happened: '', status: '', kind: '', barangay: '', incident: '', from: '', to: '' };

function blankForm() {
    return {
        kind: 'deceased', lives: 'esperanza', individual_ref: '', residentLabel: '',
        name: '', sex: '', age: '', barangay: '', home_address: '', home_town: '', home_province: '',
        place_scope: 'esperanza', incident: '', place_barangay: '', place_town: '', place_province: '', place_detail: '',
        occurred_at: '', cause: '', injury: '', treatment: '',
        remains_location: '', remains_ref: '', released_to: '', released_at: '', death_registry_no: '',
        last_seen_place: '', last_seen_at: '', description: '',
        kin_name: '', kin_relation: '', kin_contact: '', source: '',
    };
}

/** "2026-11-14T10:30" in the officer's own time, for a datetime-local input. */
function toLocalInput(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    const p = (n) => String(n).padStart(2, '0');
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' + p(d.getHours()) + ':' + p(d.getMinutes());
}

function toIso(local) {
    if (!local) return null;
    const d = new Date(local);
    return Number.isNaN(d.getTime()) ? null : d.toISOString();
}

function sakunaCasualties() {
    return {
        rows: [], page: 1, lastPage: 1, total: 0, loading: false, failed: false, off: false, summary: null, more: false,
        f: { ...FILTERS },
        open: null, saving: false, move: { status: '', found_place: '', note: '' },
        validBasis: '', validNote: '', consentBy: '',
        adding: false, editing: '', incidents: [], residents: [], residentQuery: '', form: blankForm(),

        categories: [['', 'All'], ['dead', 'Dead'], ['injured', 'Injured'], ['missing', 'Missing'], ['safe', 'Found safe']],
        categoryLabel: { dead: 'Dead', injured: 'Injured', missing: 'Missing', safe: 'Found safe' },
        categoryTone: { dead: 'sk-p-navy', injured: 'sk-p-orange', missing: 'sk-p-amber', safe: 'sk-p-green' },
        kinds: { missing: 'Missing person', injured: 'Injured person', deceased: 'Deceased person', unidentified: 'Unidentified body' },
        kindChoices: [
            ['deceased', 'Dead', 'The person died'],
            ['injured', 'Injured', 'Hurt or fell ill'],
            ['missing', 'Missing', 'Not found yet'],
            ['unidentified', 'Body not identified', 'No name yet'],
        ],
        // Causes as the situation reports write them; free text is fine too.
        causes: ['Drowning', 'Landslide', 'Flooding', 'Storm surge', 'Electrocution', 'Falling debris', 'Falling tree', 'Collapsed structure', 'Lightning', 'Blunt trauma', 'Vehicular accident', 'Fire or burns', 'Hypothermia'],
        sources: ['MHO (death certificate)', 'PNP report', 'Hospital', 'Rural Health Unit', 'Barangay', 'BFP', 'Family', 'MDRRMO'],

        init() {
            this.f.incident = new URLSearchParams(window.location.search).get('incident') || '';
            this.load(1);
            this.loadSummary();
            // Live updates (2026-11-15): a change to Sakuna anywhere reloads this
            // page of the list and the counts quietly; they wait while a case,
            // the form or a dialog is open, or a field is in use.
            // They also wait for a load the officer asked for (QA 2026-11-18).
            this.$store.live.on(this.$el, 'sakuna', () => Promise.all([this.load(this.page, true), this.loadSummary(true)]), { busy: () => !!this.open || this.adding || !!this.editing || this.saving || this.loading });
        },

        when(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'; },
        filters() { return Object.fromEntries(Object.entries(this.f).filter(([, v]) => v !== '' && v !== null)); },
        params() { return { ...this.filters(), page: this.page, per_page: 25 }; },

        // quiet (a live update): the rows on screen stay until the new ones
        // come, and a failed reload keeps them.
        // Only the latest call's answer is used (QA 2026-11-18): a quiet reload
        // with the old filters that answered after a pill or barangay change
        // put every category back under the highlighted 'Dead'.
        loadSeq: 0, summarySeq: 0,
        async load(page = 1, quiet = false) {
            this.page = page;
            const seq = ++this.loadSeq;
            const params = this.params();
            if (!quiet) { this.loading = true; this.failed = false; }
            try {
                const res = await api.get('/admin/sakuna/missing', { params });
                if (seq !== this.loadSeq) return;
                this.rows = res.data || []; this.total = res.meta?.total ?? this.rows.length; this.lastPage = res.meta?.last_page || 1;
                this.failed = false;
                this.saveCopy?.('missing', { params: JSON.stringify(params), rows: this.rows, total: this.total, lastPage: this.lastPage });
            } catch (err) {
                if (quiet || seq !== this.loadSeq) return;
                const copy = this.lastCopy?.('missing');
                if (err?.code === 'FEATURE_OFF' || err?.status === 404) { this.off = true; if (err?.code === 'FEATURE_OFF') this.markMissingOff?.(); }
                else if (copy && copy.data.params === JSON.stringify(params)) { this.useCopy?.('missing'); this.rows = copy.data.rows || []; this.total = copy.data.total || 0; this.lastPage = copy.data.lastPage || 1; }
                else this.failed = true;
            } finally { if (seq === this.loadSeq) this.loading = false; }
        },
        // The stat strip: people, not cases; for the incident, barangay and dates chosen.
        // quiet: a failed reload keeps the counts on screen.
        loadSummary(quiet = false) {
            const seq = ++this.summarySeq;
            const scope = Object.fromEntries(['incident', 'barangay', 'from', 'to'].filter((k) => this.f[k]).map((k) => [k, this.f[k]]));
            return api.get('/admin/sakuna/missing/summary', { params: scope })
                .then((r) => { if (seq !== this.summarySeq) return; this.summary = r.data; this.saveCopy?.('missing-summary', r.data); })
                .catch(() => { if (!quiet && seq === this.summarySeq) this.summary = this.lastCopy?.('missing-summary')?.data || null; });
        },
        setCategory(c) { this.f.category = c; this.load(1); },
        filtered() { this.load(1); this.loadSummary(); },
        clear() { this.f = { ...FILTERS }; this.filtered(); },
        cat(key) { return this.summary?.casualties?.[key] || null; },
        /** Residents hurt, killed or missing outside Esperanza: kept, not counted (2026-11-16). */
        elsewhereLine() {
            const e = this.summary?.elsewhere || {};
            const parts = [['dead', 'dead'], ['injured', 'injured'], ['missing', 'missing']].filter(([k]) => e[k]).map(([k, label]) => e[k] + ' ' + label);
            return (e.total === 1 ? '1 Esperanza resident' : e.total + ' Esperanza residents') + ' hurt, killed or missing outside Esperanza (' + parts.join(', ') + ') '
                + (e.total === 1 ? 'is' : 'are') + ' not in these figures: the town where it happened counts them. They are kept here for welfare follow-up.';
        },

        // How a row reads.
        personName(r) { return r.name || (r.kind === 'unidentified' ? 'Unidentified body' : 'No name yet'); },
        livesLine(r) {
            const l = r.lives || {};
            if (l.scope === 'outside') return 'Not from Esperanza · ' + ([l.town, l.province].filter(Boolean).join(', ') || 'town not given');
            // Non-breaking hyphens: a RES- number never splits across two lines.
            if (l.scope === 'esperanza') return 'Esperanza' + (r.barangay ? ' · Brgy. ' + r.barangay : '') + (r.registry_ref ? ' · ' + r.registry_ref.replace(/-/g, '‑') : '');
            return 'Home not known';
        },
        /** Where it happened; `full` (the detail window) adds the exact place outside Esperanza too. */
        placeLine(r, full = false) {
            const h = r.happened || {};
            if (h.scope === 'outside') return 'Outside Esperanza · ' + ([h.town, h.province].filter(Boolean).join(', ') || '—') + (full && h.detail ? ' · ' + h.detail : '');
            return (h.barangay ? 'Brgy. ' + h.barangay : '—') + (h.detail ? ' · ' + h.detail : '');
        },
        whenShort(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }) : ''; },
        ageSex(r) { return [r.sex, r.age !== null && r.age !== undefined ? r.age + ' yrs' : null].filter(Boolean).join(' · ') || '—'; },
        /** The status under the category pill only when it says more ("Found deceased", "Unidentified"). */
        statusDiffers(r) { return !['Missing', 'Injured', 'Deceased', this.categoryLabel[r.category]].includes(r.status); },
        /**
         * What validates a case, by category (decided 2026-11-16 from NDRRMC MC 19
         * s.2016): the MDRRMO marks it once the document is on file. A police
         * report alone no longer validates a death: the MHO's death certificate does.
         */
        validationHelp(o) {
            return {
                dead: 'For validation. A death is validated once the death certificate is in: the Municipal Health Officer issues it (or a hospital or the DOH).',
                injured: 'For validation. An injury is validated once a Rural Health Unit, hospital or DOH record confirms it.',
                missing: 'For validation. A missing person is validated once the family has reported it to the police (PNP report) or the MDM missing persons form is filled in.',
                safe: 'Found safe is not counted. Record who confirmed the person is safe.',
            }[o.category] || 'For validation.';
        },
        validationExample(o) {
            return { dead: 'Certificate no., e.g. 2026-114', injured: 'Record no., e.g. RHU 2026-031', missing: 'Report or blotter no., e.g. 2026-07', safe: 'Who confirmed it, e.g. Brgy. Captain' }[o.category] || 'Document number';
        },
        validatedLine(o) {
            return 'Validated ' + this.when(o.validated_at) + (o.validated_by ? ' by ' + o.validated_by : '')
                + [o.validation_basis, o.validation_note].filter(Boolean).map((s) => ' · ' + s).join('');
        },
        whenOf(r) { return r.occurred_at || r.last_seen_at; },

        async show(ref) {
            try {
                this.open = (await api.get('/admin/sakuna/missing/' + encodeURIComponent(ref))).data;
                this.move = { status: '', found_place: '', note: '' }; this.validBasis = ''; this.validNote = ''; this.consentBy = '';
                this.$nextTick(() => window.renderIcons?.());
            } catch { this.$dispatch('toast', { message: 'Could not open this case.', variant: 'error' }); }
        },
        async refresh() { if (this.open) await this.show(this.open.ref); await this.load(this.page); this.loadSummary(); },
        failedWith(err, fallback) { this.$dispatch('toast', { message: err?.localised ? err.localised('en') : fallback, variant: 'error' }); },

        async moveTo() {
            if (this.saving || !this.open || !this.move.status) return;
            this.saving = true;
            try {
                await api.post('/admin/sakuna/missing/' + encodeURIComponent(this.open.ref) + '/status', { status: this.move.status, found_place: this.move.found_place || null, note: this.move.note || null });
                this.$dispatch('toast', { message: this.open.ref + ' is now ' + this.move.status + '.', variant: 'success' });
                await this.refresh();
            } catch (err) { this.failedWith(err, 'Could not change this case.'); } finally { this.saving = false; }
        },
        async setValidation(to) {
            if (this.saving || !this.open) return;
            this.saving = true;
            try {
                const validated = to === 'Validated';
                await api.post('/admin/sakuna/missing/' + encodeURIComponent(this.open.ref) + '/validation', {
                    validation: to,
                    validation_basis: validated ? this.validBasis || null : null,
                    validation_note: validated ? this.validNote.trim() || null : null,
                });
                this.$dispatch('toast', { message: this.open.ref + (to === 'Validated' ? ' is validated.' : ' is back to For validation.'), variant: 'success' });
                await this.refresh();
            } catch (err) { this.failedWith(err, 'Could not change the validation.'); } finally { this.saving = false; }
        },
        async giveConsent() {
            if (this.saving || !this.open || !this.consentBy.trim()) return;
            this.saving = true;
            try {
                await api.post('/admin/sakuna/missing/' + encodeURIComponent(this.open.ref) + '/consent', { by: this.consentBy.trim() });
                this.$dispatch('toast', { message: 'Written consent recorded. A Balita post can be prepared.', variant: 'success' });
                await this.refresh();
            } catch (err) { this.failedWith(err, 'Could not record the consent.'); } finally { this.saving = false; }
        },
        // A Balita draft from the case (owner, 2026-10-30): it goes through
        // Balita's review; the MIO, the Super Administrator or the MDRRMO Head
        // approves it, never its writer. Only with written consent on record.
        async draftPost(balitaUrl) {
            if (this.saving || !this.open) return;
            this.saving = true;
            try {
                const res = await api.post('/admin/sakuna/missing/' + encodeURIComponent(this.open.ref) + '/announcement');
                this.$dispatch('toast', { message: 'Draft ready in Balita. Check it and send it for review.', variant: 'success' });
                window.open(balitaUrl + '?tab=balita&post=' + res.data.id, '_self');
            } catch (err) { this.failedWith(err, 'Could not prepare the post.'); } finally { this.saving = false; }
        },

        // ------------------------------------------------ Record or correct a person
        // Every incident, not the newest hundred (a page's cap; QA 2026-11-18):
        // last quarter's typhoon must still be choosable. The one the link
        // asked for (?incident=REF) is fetched on its own if the list lacks it.
        async loadIncidents(want = '') {
            let list = [];
            try { list = (await api.getAll('/admin/sakuna/incidents')).data || []; } catch { list = []; }
            if (want && !list.some((i) => i.ref === want)) {
                try {
                    const one = (await api.get('/admin/sakuna/incidents/' + encodeURIComponent(want))).data;
                    if (one?.ref) list = [one, ...list];
                } catch { /* not found, or outside the officer's scope */ }
            }
            this.incidents = list;
        },
        async startAdd() {
            this.editing = ''; this.form = blankForm(); this.residents = []; this.residentQuery = '';
            this.adding = true;
            const want = this.f.incident || '';
            await this.loadIncidents(want);
            if (want && this.incidents.some((i) => i.ref === want)) this.$nextTick(() => { this.form.incident = want; this.incidentChosen(); });
            this.$nextTick(() => window.renderIcons?.());
        },
        startEdit() {
            const o = this.open;
            if (!o) return;
            const l = o.lives || {}; const h = o.happened || {};
            this.form = {
                ...blankForm(),
                kind: o.kind, lives: l.scope || 'unknown', individual_ref: '', residentLabel: o.registry_ref ? o.name + ' (' + o.registry_ref + ')' : '',
                name: o.name || '', sex: o.sex || '', age: o.age ?? '', barangay: l.scope === 'esperanza' ? (o.barangay || '') : '',
                home_address: l.address || '', home_town: l.town || '', home_province: l.province || '',
                place_scope: h.scope || 'esperanza', incident: o.incident?.ref || '', place_barangay: h.barangay || '',
                place_town: h.town || '', place_province: h.province || '', place_detail: h.detail || '',
                occurred_at: toLocalInput(o.occurred_at), cause: o.cause || '', injury: o.injury || '', treatment: o.treatment || '',
                remains_location: o.remains_location || '', remains_ref: o.remains_ref || '', released_to: o.released_to || '',
                released_at: toLocalInput(o.released_at), death_registry_no: o.death_registry_no || '',
                last_seen_place: o.last_seen_place || '', last_seen_at: toLocalInput(o.last_seen_at), description: o.description || '',
                kin_name: o.kin_name || '', kin_relation: o.kin_relation || '', kin_contact: o.kin_contact || '', source: o.source || '',
            };
            this.editing = o.ref; this.adding = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        async findResident() {
            const q = this.residentQuery.trim();
            if (q.length < 2) { this.residents = []; return; }
            try { this.residents = (await api.get('/admin/residents', { params: { q, per_page: 10 } })).data || []; } catch { this.residents = []; }
        },
        pickResident(p) {
            this.form.individual_ref = p.ref; this.form.name = ''; this.form.barangay = p.barangay || this.form.barangay;
            this.form.residentLabel = p.name + ' (' + p.ref + ')'; this.residents = []; this.residentQuery = '';
        },
        unpickResident() { this.form.individual_ref = ''; this.form.residentLabel = ''; },
        incidentChosen() {
            const i = this.incidents.find((x) => x.ref === this.form.incident);
            if (i && this.form.place_scope === 'esperanza' && !this.form.place_barangay) this.form.place_barangay = i.barangay || '';
        },
        get dead() { return ['deceased', 'unidentified'].includes(this.form.kind); },
        /** What is still needed before Record, in words; empty when ready. */
        get missingBits() {
            const f = this.form; const need = [];
            if (!this.editing && f.kind !== 'unidentified' && !f.name.trim() && !f.individual_ref) need.push('the name');
            if (f.lives === 'outside' && !f.home_town.trim()) need.push('the home town');
            if (f.place_scope === 'esperanza' && !f.incident && !this.editing) need.push('the incident');
            if (f.place_scope === 'outside' && !f.place_town.trim()) need.push('the town where it happened');
            return need;
        },
        body() {
            const f = this.form; const b = {};
            const put = (k, v) => { b[k] = v === '' || v === undefined ? null : v; };
            if (!this.editing) { put('kind', f.kind); put('incident_ref', f.incident); }
            if (f.individual_ref) put('individual_ref', f.individual_ref);
            if (!f.individual_ref || f.name) put('name', f.name.trim());
            put('sex', f.sex); put('age', f.age === '' ? '' : Number(f.age));
            put('home_scope', f.lives === 'unknown' ? '' : f.lives);
            if (f.lives === 'esperanza') put('barangay', f.barangay);
            put('home_address', f.lives === 'unknown' ? '' : f.home_address);
            put('home_town', f.lives === 'outside' ? f.home_town : ''); put('home_province', f.lives === 'outside' ? f.home_province : '');
            put('place_scope', f.place_scope);
            put('place_barangay', f.place_scope === 'esperanza' ? f.place_barangay : '');
            put('place_town', f.place_scope === 'outside' ? f.place_town : ''); put('place_province', f.place_scope === 'outside' ? f.place_province : '');
            put('place_detail', f.place_detail); put('occurred_at', toIso(f.occurred_at)); put('cause', f.cause);
            put('injury', f.kind === 'injured' ? f.injury : ''); put('treatment', f.kind === 'injured' ? f.treatment : '');
            const d = this.dead;
            put('remains_location', d ? f.remains_location : ''); put('remains_ref', d ? f.remains_ref : '');
            put('released_to', d ? f.released_to : ''); put('released_at', d ? toIso(f.released_at) : ''); put('death_registry_no', d ? f.death_registry_no : '');
            put('last_seen_place', f.kind === 'missing' ? f.last_seen_place : ''); put('last_seen_at', f.kind === 'missing' ? toIso(f.last_seen_at) : '');
            put('description', f.description);
            put('kin_name', f.kin_name); put('kin_relation', f.kin_relation); put('kin_contact', f.kin_contact); put('source', f.source);
            // A new record leaves out what was never filled in.
            return this.editing ? b : Object.fromEntries(Object.entries(b).filter(([, v]) => v !== null));
        },
        async save() {
            if (this.saving || this.missingBits.length) return;
            this.saving = true;
            try {
                const res = this.editing
                    ? await api.put('/admin/sakuna/missing/' + encodeURIComponent(this.editing), this.body())
                    : await api.post('/admin/sakuna/missing', this.body());
                this.$dispatch('toast', { message: res.data.ref + (this.editing ? ' updated.' : ' recorded.'), variant: 'success' });
                this.adding = false; this.editing = '';
                await this.load(1); this.loadSummary(); await this.show(res.data.ref);
            } catch (err) { this.failedWith(err, 'Could not save this person.'); } finally { this.saving = false; }
        },

        // ------------------------------------------------ The casualty list
        // The NDRRMC name list's columns (CSV or PDF), dead first. Audited by
        // the server by count, never by name. Counted where it happened
        // (2026-11-16): residents hurt, killed or missing outside Esperanza come
        // last, apart; the PDF ends with the signatures NDRRMC MC 19 s.2016
        // §7.4.1 asks of the LGU's report (the Mayor, countersigned by the MHO).
        async exportList(as) {
            try {
                const res = await api.get('/admin/sakuna/missing/export', { params: this.filters() });
                const data = res.data || {};
                const COUNTED = "In Esperanza's count";
                // The NDRRMC name list's order (... Cause, Remarks, Source of data, Validated), then ours.
                const rows = (data.rows || []).map((r) => ({
                    Category: this.categoryLabel[r.category] || r.category,
                    Province: r.province || '',
                    'City/Municipality': r.municipality || '',
                    Barangay: r.barangay || '',
                    Name: r.name || 'Unidentified',
                    Age: r.age ?? '',
                    Sex: r.sex || '',
                    Address: r.address || (r.lives === 'outside' ? 'Not from Esperanza' : ''),
                    Cause: r.cause || '',
                    Remarks: r.remarks || '',
                    'Source of data': r.source || '',
                    // Found safe is not counted, so not validated either way.
                    Validated: r.category === 'safe' ? '—' : (r.validated ? 'Yes' : 'No'),
                    'Date and time': this.whenShort(r.when),
                    Details: r.details || '',
                    [COUNTED]: r.counted === false ? 'No' : 'Yes',
                }));
                if (!rows.length) { this.toast?.('No records to export.', 'warning'); return; }
                // The province's name list (QA 2026-11-18): a BOM so Excel reads
                // Peña and Niño, quotes doubled, and a name or remark that starts
                // like a formula (= + - @) opened as text, never run.
                if (as === 'csv') {
                    const keys = Object.keys(rows[0]);
                    downloadCsv('casualty-list.csv', [keys, ...rows.map((r) => keys.map((k) => r[k]))]);
                    this.toast?.('Exported successfully.', 'success');
                    return;
                }
                const c = this.summary?.casualties || {};
                const line = (k, label) => label + ': ' + (c[k]?.total ?? 0) + ' (' + (c[k]?.validated ?? 0) + ' validated, ' + (c[k]?.for_validation ?? 0) + ' for validation)';
                const here = rows.filter((r) => r[COUNTED] === 'Yes');
                const away = rows.filter((r) => r[COUNTED] === 'No');
                const awayTitle = 'Esperanza residents hurt, killed or missing outside Esperanza';
                const awayNote = 'Not in the count above: the town where it happened counts them. Kept for welfare follow-up, such as burial assistance and benefit claims.';
                await window.tablePdf(here.length ? here : away, {
                    filename: 'casualty-list.pdf',
                    title: 'Sakuna · Casualty list',
                    subtitle: [data.municipality, data.province].filter(Boolean).join(', ') + ' · ' + (this.f.incident ? 'Incident ' + this.f.incident : 'All incidents'),
                    // A space after the slash lets the narrow heading break there, not mid-word.
                    columns: Object.keys(rows[0]).filter((key) => key !== COUNTED).map((key) => ({ key, label: key === 'City/Municipality' ? 'City/ Municipality' : key })),
                    summary: here.length
                        ? [[line('dead', 'Dead'), line('injured', 'Injured'), line('missing', 'Missing'), 'Found safe: ' + (c.safe ?? 0)].join('   ·   ') + '. Counted where it happened.']
                        : [awayTitle + '. ' + awayNote],
                    parts: here.length && away.length ? [{ title: awayTitle, note: awayNote, rows: away }] : [],
                    signatures: [
                        { label: 'Prepared by:', position: 'MDRRM Officer' },
                        { label: 'Countersigned by:', position: 'Municipal Health Officer' },
                        { label: 'Approved by:', position: 'Municipal Mayor' },
                    ],
                });
                this.toast?.('PDF downloaded.', 'success');
            } catch (err) { this.failedWith(err, 'Could not make the casualty list.'); }
        },
    };
}

export function register(Alpine) {
    Alpine.data('sakunaCasualties', sakunaCasualties);
}

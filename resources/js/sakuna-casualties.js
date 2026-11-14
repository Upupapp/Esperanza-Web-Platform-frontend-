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
// reaches the page's can(), toast(), saveCopy()/lastCopy()/useCopy(),
// exportCSV() and markMissingOff().

import api from './api/client';

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
        validNote: '', consentBy: '',
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
            this.$store.live.on(this.$el, 'sakuna', () => Promise.all([this.load(this.page, true), this.loadSummary(true)]), { busy: () => !!this.open || this.adding || !!this.editing || this.saving });
        },

        when(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—'; },
        filters() { return Object.fromEntries(Object.entries(this.f).filter(([, v]) => v !== '' && v !== null)); },
        params() { return { ...this.filters(), page: this.page, per_page: 25 }; },

        // quiet (a live update): the rows on screen stay until the new ones
        // come, and a failed reload keeps them.
        async load(page = 1, quiet = false) {
            this.page = page;
            if (!quiet) { this.loading = true; this.failed = false; }
            try {
                const res = await api.get('/admin/sakuna/missing', { params: this.params() });
                this.rows = res.data || []; this.total = res.meta?.total ?? this.rows.length; this.lastPage = res.meta?.last_page || 1;
                this.failed = false;
                this.saveCopy?.('missing', { params: JSON.stringify(this.params()), rows: this.rows, total: this.total, lastPage: this.lastPage });
            } catch (err) {
                if (quiet) return;
                const copy = this.lastCopy?.('missing');
                if (err?.code === 'FEATURE_OFF' || err?.status === 404) { this.off = true; if (err?.code === 'FEATURE_OFF') this.markMissingOff?.(); }
                else if (copy && copy.data.params === JSON.stringify(this.params())) { this.useCopy?.('missing'); this.rows = copy.data.rows || []; this.total = copy.data.total || 0; this.lastPage = copy.data.lastPage || 1; }
                else this.failed = true;
            } finally { this.loading = false; }
        },
        // The stat strip: people, not cases; for the incident, barangay and dates chosen.
        // quiet: a failed reload keeps the counts on screen.
        loadSummary(quiet = false) {
            const scope = Object.fromEntries(['incident', 'barangay', 'from', 'to'].filter((k) => this.f[k]).map((k) => [k, this.f[k]]));
            return api.get('/admin/sakuna/missing/summary', { params: scope })
                .then((r) => { this.summary = r.data; this.saveCopy?.('missing-summary', r.data); })
                .catch(() => { if (!quiet) this.summary = this.lastCopy?.('missing-summary')?.data || null; });
        },
        setCategory(c) { this.f.category = c; this.load(1); },
        filtered() { this.load(1); this.loadSummary(); },
        clear() { this.f = { ...FILTERS }; this.filtered(); },
        cat(key) { return this.summary?.casualties?.[key] || null; },

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
        /** What validates a case, by category (a death certificate is only for a death). */
        validationHelp(o) {
            return {
                dead: 'For validation. A death counts as validated once a death certificate shows it was caused by the disaster (MHO), or a police report confirms it.',
                injured: 'For validation. An injury counts as validated once a hospital or Rural Health Unit record confirms it.',
                missing: 'For validation. A missing person counts as validated once a police (PNP) or barangay report confirms it.',
                safe: 'For validation. Validated once the family or the barangay confirms the person is safe.',
            }[o.category] || 'For validation.';
        },
        validationExample(o) {
            return { dead: 'e.g. Death certificate no. 2026-114', injured: 'e.g. RHU record no. 2026-031', missing: 'e.g. PNP blotter no. 2026-07', safe: 'e.g. Barangay report' }[o.category] || 'Note';
        },
        whenOf(r) { return r.occurred_at || r.last_seen_at; },

        async show(ref) {
            try {
                this.open = (await api.get('/admin/sakuna/missing/' + encodeURIComponent(ref))).data;
                this.move = { status: '', found_place: '', note: '' }; this.validNote = ''; this.consentBy = '';
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
                await api.post('/admin/sakuna/missing/' + encodeURIComponent(this.open.ref) + '/validation', { validation: to, validation_note: this.validNote || null });
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
        async loadIncidents() {
            try { this.incidents = (await api.get('/admin/sakuna/incidents', { params: { per_page: 100 } })).data || []; } catch { this.incidents = []; }
        },
        async startAdd() {
            this.editing = ''; this.form = blankForm(); this.residents = []; this.residentQuery = '';
            this.adding = true;
            await this.loadIncidents();
            const want = this.f.incident || '';
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
        // the server by count, never by name.
        async exportList(as) {
            try {
                const res = await api.get('/admin/sakuna/missing/export', { params: this.filters() });
                const data = res.data || {};
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
                }));
                if (!rows.length) { this.toast?.('No records to export.', 'warning'); return; }
                if (as === 'csv') { this.exportCSV(rows, 'casualty-list.csv'); return; }
                const c = this.summary?.casualties || {};
                const line = (k, label) => label + ': ' + (c[k]?.total ?? 0) + ' (' + (c[k]?.validated ?? 0) + ' validated, ' + (c[k]?.for_validation ?? 0) + ' for validation)';
                await window.tablePdf(rows, {
                    filename: 'casualty-list.pdf',
                    title: 'Sakuna · Casualty list',
                    subtitle: [data.municipality, data.province].filter(Boolean).join(', ') + ' · ' + (this.f.incident ? 'Incident ' + this.f.incident : 'All incidents'),
                    // A space after the slash lets the narrow heading break there, not mid-word.
                    columns: Object.keys(rows[0]).map((key) => ({ key, label: key === 'City/Municipality' ? 'City/ Municipality' : key })),
                    summary: [[line('dead', 'Dead'), line('injured', 'Injured'), line('missing', 'Missing'), 'Found safe: ' + (c.safe ?? 0)].join('   ·   ')],
                });
                this.toast?.('PDF downloaded.', 'success');
            } catch (err) { this.failedWith(err, 'Could not make the casualty list.'); }
        },
    };
}

export function register(Alpine) {
    Alpine.data('sakunaCasualties', sakunaCasualties);
}

// Dokyu's Document types and Tulong's Tulong types (round 2, 2026-10-30):
// the editor that was Settings > Service Catalog (scope round 2026-10-15;
// workflow, questions and new services 2026-10-17), on a page of each tab,
// for one kind at a time. The owner: "give the option to modify the dokyu and
// tulong in their respective tabs, in dokyu screen they can edit, add, modify
// the dokyu offered in the system".
//
// Loaded only on /admin/document-requests/types and
// /admin/assistance-requests/types (see the area loader at the end of
// app.js), so neither the shared bundle nor the Dokyu and Tulong pages carry
// it; register() runs before Alpine starts.
//
// A Department Head holds set_service_catalog too since round 2: the server
// lists only their own office's types, refuses another office's
// (OUT_OF_SCOPE) and adds a new type to their own office, so for them the
// office is shown fixed here.

import api from './api/client';

// ------------------------------------------------------------------ helpers

const TZ = 'Asia/Manila';

const session = () => window.Alpine?.store('session');
/** canPerm at the moment it is asked (the Super Administrator passes every check). */
const may = (submodule, action) => Boolean(session()?.canPerm?.(submodule, action));
/** Resolves once canPerm() can be trusted. */
const sessionReady = () => Promise.resolve(session()?.whenReady?.()).catch(() => null);

/** On window: the button that asked may be gone by the time the answer comes. */
function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}
const errText = (err, fallback) => (err && typeof err.localised === 'function' ? err.localised('en') : fallback);
/** A field's own reason (422) when there is one, else the error's message. */
const errExplain = (err, fallback) => (err && typeof err.explain === 'function' ? err.explain('en') : errText(err, fallback));

const BARE_TIME = /^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?$/;

/** A timestamp from the API; a bare "Y-m-d H:i:s" is UTC, the server's own zone. */
function toDate(value) {
    if (!value) return null;
    if (value instanceof Date) return value;
    let s = String(value).trim();
    if (BARE_TIME.test(s)) s = s.replace(' ', 'T') + 'Z';
    const d = new Date(s);
    return Number.isNaN(d.getTime()) ? null : d;
}

/** "Oct 15, 2026, 9:30 AM", in Esperanza's time. */
function fmtWhen(value) {
    const d = toDate(value);
    return d ? d.toLocaleString('en-US', { timeZone: TZ, month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '';
}

const num = (n) => Number(n || 0).toLocaleString('en-US');
const plural = (n, one, many) => num(n) + ' ' + (Number(n) === 1 ? one : many);

const FIELD_LABELS = { name: 'Name', office: 'Office', category: 'Category', active: 'Active / offered' };
const fieldLabel = (key) => FIELD_LABELS[key] || String(key).replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());

function showValue(v) {
    if (v === undefined) return '—';
    if (v === null || v === '') return '(empty)';
    if (typeof v === 'boolean') return v ? 'Yes' : 'No';
    if (Array.isArray(v)) return v.length ? v.map((x) => (x !== null && typeof x === 'object' ? JSON.stringify(x) : String(x))).join(' · ') : '(none)';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
}

/** Before and after, field by field, and whether each changed. */
function diffFields(before, after) {
    const asObject = (v) => (v !== null && typeof v === 'object' && !Array.isArray(v) ? v : (v === null || v === undefined ? {} : { value: v }));
    const b = asObject(before);
    const a = asObject(after);
    const has = (o, k) => Object.prototype.hasOwnProperty.call(o, k);
    return [...new Set([...Object.keys(b), ...Object.keys(a)])].map((k) => {
        const from = has(b, k) ? b[k] : undefined;
        const to = has(a, k) ? a[k] : undefined;
        return { field: k, label: fieldLabel(k), from: showValue(from), to: showValue(to), changed: JSON.stringify(from) !== JSON.stringify(to) };
    });
}

// ------------------------------------------------------------- the catalog

// The words for each kind: never "service" on these screens (owner, round 2:
// "they are names services in the system" only in his prompts).
const WORDS = {
    dokyu: { one: 'document type', many: 'document types', One: 'Document type', example: 'Certificate of Good Moral Character' },
    tulong: { one: 'Tulong type', many: 'Tulong types', One: 'Tulong type', example: 'Educational Assistance' },
};
const BARANGAY_OFFICE = 'Barangay Office';
const FIELD_KINDS = {
    text: 'Text', textarea: 'Long text', number: 'Number', date: 'Date', select: 'One choice', multiselect: 'Several choices',
    checkbox: 'Tick box', derivedAge: 'Age, worked out from the birth date', table: 'Table', tel: 'Phone number', email: 'Email', file: 'File',
};

/**
 * Whether the server can read a fee as written. It turns the display text
 * into the rule it charges (FeeRuleParser) and answers a shape it does not
 * know with a server error, so the shapes it knows are checked here first.
 */
function feeProblem(fee) {
    const raw = String(fee ?? '').trim();
    if (!raw) return 'Write the fee, or Free.';
    if (raw.length > 120) return 'The fee is at most 120 characters.';
    const s = raw.replace(/₱|PHP|Php| /g, ' ').replace(/\s+/g, ' ').trim();
    if (s === '' || /^free$/i.test(s)) return '';
    if (/^[\d.,]+\s*base\s*\+\s*[\d.,]+\s*per\s*[\d.,]+\s*\w+/i.test(s)) return '';
    if (/^[\d.,]+\s*and up\s*\(?based on [^)]+\)?/i.test(s)) return '';
    if (/^based on .+$/i.test(s)) return '';
    if (/^[\d.,]+$/.test(s)) return '';
    return 'The system cannot charge this fee as written. Use Free, an amount (₱155.00), “₱5.00 base + ₱1.00 per ₱1,000 income”, “₱500.00 and up (based on capital)” or “Based on prior year gross receipts”.';
}

// A type's workflow steps (2026-10-17): what each kind means in the editor.
const STEP_KINDS = [
    ['submit', 'Filing', 'The resident files the request. Done by itself.'],
    ['office', 'Office step', 'Work the office does: an officer marks it done.'],
    ['payment', 'Payment', 'Done once the fee is paid (or there is none).'],
    ['approval', 'Approval', 'Done by Approve. Waits for the office steps before it.'],
    ['release', 'Release', 'Done by Release / Complete. Waits for the office steps before it.'],
];
// The questions staff can add; special kinds the forms came with are kept as they are.
const QUESTION_KINDS = [
    ['text', 'Short answer'], ['textarea', 'Long answer'], ['number', 'Number'], ['date', 'Date'],
    ['tel', 'Mobile number'], ['select', 'Choose one'], ['checkbox-group', 'Tick all that apply'],
];
const KEPT_KINDS = { derived_age: 'Age, worked out from the birth date', table: 'Table (rows of family members or items)', checkbox: 'Single tick box', multiselect: 'Choose several' };

/** @param {'dokyu'|'tulong'} kind the page's kind; there is no choosing it here. */
function catalogEditor(kind = 'dokyu') {
    const words = WORDS[kind] || WORDS.dokyu;
    return {
        fixedType: kind, words,
        started: false, loading: false, loaded: false, failed: false, denied: false,
        rows: [], offices: [],
        stepKinds: STEP_KINDS, questionKinds: QUESTION_KINDS,
        // Adding a type (2026-10-17).
        adding: false, creating: false, createError: '',
        // The type just added, opened by the editor itself: called from
        // inside the "Add" window, `this.open` would be that window's own
        // (2026-10-17).
        pendingEdit: null,
        draft: { type: kind, name: '', office: '', category: '', fee: 'Free', days: '1-3 working days', permissions_like: '' },
        search: '', office: '', offered: '',

        open: false, section: 'details', key: null,
        svc: null, spec: null, specFailed: false,
        detailLoading: false, detailFailed: false,
        details: { name: '', office: '', category: '', active: true },
        savingDetails: false, detailsError: '',
        rules: { fee: '', days: '', requirements: [], workflow: [], fields: [] },
        rulesBase: '', rulesError: '', publishing: false,
        newReq: '', newStep: '',
        log: [], logLoaded: false, logLoading: false, logFailed: false,

        when: fmtWhen,
        plural,
        get canEdit() { return may('set_service_catalog', 'edit'); },

        init() {
            this.$watch('pendingEdit', (key) => {
                if (!key) return;
                this.pendingEdit = null;
                const row = this.rows.find((r) => r.key === key);
                if (row) this.edit(row);
            });
            // A message about what was typed goes once it is changed.
            this.$watch('details', () => { this.detailsError = ''; });
            this.$watch('rules', () => { this.rulesError = ''; });
        },
        async start() {
            if (this.started) return;
            this.started = true;
            await sessionReady();
            if (!may('set_service_catalog', 'view')) { this.denied = true; return; }
            await this.load();
        },
        /** GET /admin/settings/catalog: every type, offered or not; this page keeps its own kind. */
        async load() {
            this.loading = true; this.failed = false;
            try {
                const res = await api.get('/admin/settings/catalog');
                this.rows = (res.data || []).filter((r) => r.type === this.fixedType);
                this.offices = Array.isArray(res.meta?.offices) ? res.meta.offices : [];
                this.loaded = true;
            } catch { this.failed = true; } finally { this.loading = false; }
        },

        /**
         * A Department Head's (or Staff's) own office, as the Departments list
         * names it, or '' for an officer who is not held to one.
         */
        get ownOffice() {
            const s = session();
            if (!s?.deptRestricted?.()) return '';
            const dept = String(s.account?.dept || '').trim();
            const low = dept.toLowerCase();
            const o = this.offices.find((x) => [x.name, x.short].some((n) => n && String(n).toLowerCase() === low));
            return o ? o.name : dept;
        },
        get officeFilterChoices() { return [...new Set(this.rows.map((r) => r.office).filter(Boolean))].sort(); },
        get categoryChoices() { return [...new Set(this.rows.map((r) => r.category).filter(Boolean))].sort(); },
        get shown() {
            const q = this.search.trim().toLowerCase();
            return this.rows.filter((r) => (!this.office || r.office === this.office)
                && (this.offered === '' || (this.offered === 'on' ? r.active : !r.active))
                && (!q || [r.name, r.key, r.category, r.office].some((v) => String(v || '').toLowerCase().includes(q))));
        },
        get summary() {
            const all = this.rows.length;
            const n = this.shown.length;
            const off = this.rows.filter((r) => !r.active).length;
            return (n === all ? plural(all, words.one, words.many) : num(n) + ' of ' + plural(all, words.one, words.many))
                + (off ? ' · ' + num(off) + ' switched off' : '');
        },

        // ---- the editor
        async edit(row) {
            // Reopening the type whose edits were not saved keeps them: a
            // click outside the window closes it.
            if (this.key === row.key && this.svc && (this.detailsDirty || this.rulesDirty)) { this.open = true; return; }
            this.key = row.key;
            this.section = 'details';
            this.svc = null; this.spec = null; this.specFailed = false;
            this.detailsError = ''; this.rulesError = '';
            this.newReq = ''; this.newStep = '';
            this.log = []; this.logLoaded = false; this.logFailed = false;
            this.open = true;
            await this.loadDetail();
        },
        async loadDetail() {
            const key = this.key;
            if (!key) return;
            this.detailLoading = true; this.detailFailed = false;
            this.loadSpec();
            try {
                const res = await api.get('/admin/settings/catalog/' + encodeURIComponent(key));
                if (this.key === key) this.applyDetail(res.data);
            } catch {
                if (this.key === key) this.detailFailed = true;
            } finally {
                if (this.key === key) this.detailLoading = false;
            }
        },
        /** The form a resident fills in comes with the public record. */
        async loadSpec() {
            const key = this.key;
            if (!key) return;
            this.spec = null; this.specFailed = false;
            try {
                const res = await api.get('/services/' + encodeURIComponent(key));
                if (this.key === key) this.spec = res.data || {};
            } catch {
                if (this.key === key) this.specFailed = true;
            }
        },
        /** keep.details / keep.rules: leave unsaved edits of that half alone. */
        applyDetail(d, keep = {}) {
            const keepDetails = keep.details && this.detailsDirty;
            const keepRules = keep.rules && this.rulesDirty;
            this.svc = d;
            if (!keepDetails) this.details = { name: d.name || '', office: d.office || '', category: d.category || '', active: !!d.active };
            if (!keepRules) {
                const c = d.current || {};
                this.rules = {
                    fee: c.fee ?? '',
                    days: c.days ?? '',
                    // A requirement asked only for some answers ({label, showIf},
                    // the Solo Parent ID's, 2026-10-26) stays an object: its
                    // wording is editable, its condition kept as it is.
                    requirements: Array.isArray(c.requirements) ? c.requirements.map((r) => (r && typeof r === 'object' ? { ...r } : r)) : [],
                    // The workflow and the form's questions (2026-10-17): copies,
                    // so editing them leaves the loaded record alone.
                    workflow: (Array.isArray(c.workflow) ? c.workflow : []).map((st) => ({ name: st.name || '', kind: st.kind || 'office', responsible: st.responsible || '', days: st.days ?? '' })),
                    fields: (Array.isArray(c.fields) ? c.fields : []).map((f) => ({ ...f, optionsText: Array.isArray(f.options) ? f.options.join('\n') : '' })),
                };
                this.rulesBase = JSON.stringify(this.rules);
            }
            const i = this.rows.findIndex((r) => r.key === d.key);
            if (i >= 0) {
                const c = d.current || {};
                this.rows[i] = {
                    ...this.rows[i], name: d.name, office: d.office, category: d.category, active: !!d.active, policy_blocked: !!d.policy_blocked,
                    version: c.version, fee: c.fee, days: c.days, requirements: (c.requirements || []).length, steps: (c.process || []).length,
                };
            }
        },
        showSection(key) {
            this.section = key;
            if (key === 'history') this.loadLog();
        },

        // details: name, office, category, offered (not versioned)
        get isBarangayService() { return this.svc?.office === BARANGAY_OFFICE; },
        // A Department Head's types stay in their office: the server refuses a move.
        get officeLocked() { return !!this.ownOffice; },
        get officeOptions() {
            const own = this.svc?.office;
            if (this.officeLocked) return own ? [own] : [];
            const list = this.offices.filter((o) => o.active !== false).map((o) => o.name);
            if (own && !this.isBarangayService && !list.includes(own)) list.unshift(own);
            return list;
        },
        get detailsDirty() {
            const s = this.svc;
            if (!s) return false;
            const d = this.details;
            return d.name.trim() !== (s.name || '') || d.office !== (s.office || '')
                || (d.category || '').trim() !== (s.category || '') || d.active !== !!s.active;
        },
        detailsProblem() {
            const name = this.details.name.trim();
            if (name.length < 3) return 'The name needs at least 3 characters.';
            if (name.length > 160) return 'The name is at most 160 characters.';
            if ((this.details.category || '').trim().length > 80) return 'The category is at most 80 characters.';
            if (!this.details.office) return 'Choose the office that handles this ' + words.one + '.';
            return '';
        },
        async saveDetails() {
            if (this.savingDetails || !this.svc || !this.canEdit) return;
            const problem = this.detailsProblem();
            if (problem) { this.detailsError = problem; return; }
            const s = this.svc;
            const d = this.details;
            const body = {};
            if (d.name.trim() !== s.name) body.name = d.name.trim();
            if (d.office !== s.office) body.office = d.office;
            if ((d.category || '').trim() !== (s.category || '')) body.category = (d.category || '').trim() || null;
            if (d.active !== !!s.active) body.active = d.active;
            if (!Object.keys(body).length) { this.detailsError = ''; return; }
            const key = this.key;
            this.savingDetails = true; this.detailsError = '';
            try {
                const res = await api.put('/admin/settings/catalog/' + encodeURIComponent(key) + '/details', body);
                if (this.key === key) this.applyDetail(res.data, { rules: true });
                this.logLoaded = false;
                toast(body.active === false
                    ? 'Saved. ' + d.name.trim() + ' is switched off: residents can no longer file it.'
                    : (body.active === true ? 'Saved. ' + d.name.trim() + ' is offered to residents now.' : 'Details saved.'));
            } catch (err) {
                this.detailsError = errExplain(err, 'Could not save the details.');
                // Renamed or added meanwhile under Departments: offer the current list.
                if (err?.code === 'UNKNOWN_OFFICE') {
                    api.get('/admin/settings/catalog').then((res) => {
                        if (Array.isArray(res.meta?.offices)) this.offices = res.meta.offices;
                    }).catch(() => {});
                }
            } finally { this.savingDetails = false; }
        },

        // rules: fee, time, required documents, steps - published as a version
        get rulesShapeOk() {
            const c = this.svc?.current || {};
            return c.requirements == null || (Array.isArray(c.requirements) && c.requirements.every((x) => typeof x === 'string' || (x && typeof x === 'object' && typeof x.label === 'string')));
        },
        reqLabel(r) { return r && typeof r === 'object' ? String(r.label ?? '') : String(r ?? ''); },
        // "Only when Application type is Renewal" - the condition in words.
        reqCondition(r) {
            if (!r || typeof r !== 'object' || !r.showIf) return '';
            const name = (key) => {
                const f = (this.rules.fields || []).find((x) => x.key === key);
                return f?.label || String(key).replace(/_/g, ' ');
            };
            const one = (rule) => name(rule.field) + ' is ' + (Array.isArray(rule.contains) ? rule.contains.join(' or ') : rule.contains);
            return 'Only when ' + (Array.isArray(r.showIf) ? r.showIf.map(one).join(' and ') : one(r.showIf));
        },
        // Whether the published version already tracks its steps on requests.
        get tracked() { return !!this.svc?.current?.tracked; },
        get rulesLocked() { return !this.canEdit || !!this.svc?.policy_blocked || !this.rulesShapeOk; },
        get rulesDirty() { return !!this.svc && JSON.stringify(this.rules) !== this.rulesBase; },
        get nextVersion() { return (Number(this.svc?.current?.version) || 0) + 1; },
        get feeChanged() { return !!this.svc && String(this.rules.fee ?? '') !== String(this.svc.current?.fee ?? ''); },
        get feeError() { return feeProblem(this.rules.fee); },
        addRequirement() {
            const v = this.newReq.trim();
            if (!v) return;
            this.rules.requirements.push(v);
            this.newReq = '';
        },
        addStep() {
            const v = this.newStep.trim();
            if (!v) return;
            // A new step goes before the release step, if there is one.
            const at = this.rules.workflow.findIndex((st) => st.kind === 'release');
            const step = { name: v, kind: 'office', responsible: '', days: '' };
            if (at >= 0) this.rules.workflow.splice(at, 0, step); else this.rules.workflow.push(step);
            this.newStep = '';
        },
        stepKindHelp(k) { return (STEP_KINDS.find((x) => x[0] === k) || [])[2] || ''; },
        get responsibleChoices() {
            return [...new Set(['Resident', 'Barangay Secretary', 'Punong Barangay', 'Department Head', 'Department Staff', 'Front Desk', 'Records Officer', 'Municipal Treasurer',
                ...this.offices.map((o) => o.short || o.name).filter(Boolean)])];
        },
        // ---- the request form's questions
        isKept(f) { return !!KEPT_KINDS[f?.type]; },
        keptLabel(f) { return KEPT_KINDS[f?.type] || f?.type; },
        hasOptions(f) { return ['select', 'checkbox-group'].includes(f?.type); },
        addQuestion() { this.rules.fields.push({ key: '', label: '', type: 'text', required: false, optionsText: '' }); },
        moveQuestion(i, dir) { this.move('fields', i, dir); },
        move(list, i, dir) {
            const a = this.rules[list];
            const j = i + dir;
            if (j < 0 || j >= a.length) return;
            const tmp = a[i];
            a[i] = a[j];
            a[j] = tmp;
        },
        remove(list, i) { this.rules[list].splice(i, 1); },
        rulesProblem() {
            const fee = feeProblem(this.rules.fee);
            if (fee) return fee;
            const days = String(this.rules.days ?? '').trim();
            if (!days) return 'Write the processing time, for example “1-2 working days”.';
            if (days.length > 120) return 'The processing time is at most 120 characters.';
            if (this.rules.requirements.some((r) => !this.reqLabel(r).trim())) return 'A required document is empty: write it or remove it.';
            const wf = this.rules.workflow;
            if (!wf.length) return 'Add at least one processing step.';
            if (wf.some((st) => !String(st.name).trim())) return 'A processing step has no name: write it or remove it.';
            const kinds = wf.map((st) => st.kind);
            for (const k of ['submit', 'approval', 'release']) if (kinds.filter((x) => x === k).length > 1) return 'Only one step can be the ' + (STEP_KINDS.find((x) => x[0] === k) || [k, k])[1].toLowerCase() + ' step.';
            if (kinds.includes('submit') && kinds[0] !== 'submit') return 'The filing step comes first.';
            if (kinds.includes('release') && kinds[kinds.length - 1] !== 'release') return 'The release step comes last.';
            if (kinds.includes('approval') && kinds.includes('release') && kinds.indexOf('approval') > kinds.indexOf('release')) return 'The approval step comes before the release step.';
            if (wf.some((st) => st.days !== '' && st.days !== null && (!/^\d+$/.test(String(st.days)) || Number(st.days) > 365))) return 'Days are a whole number from 0 to 365.';
            for (const [i, f] of this.rules.fields.entries()) {
                if (!String(f.label || '').trim()) return 'Question ' + (i + 1) + ' has no wording: write it or remove it.';
                if (this.hasOptions(f) && this.optionsOf(f).length < 2) return 'Question ' + (i + 1) + ' (' + f.label.trim() + ') needs at least 2 choices, one per line.';
            }
            return '';
        },
        optionsOf(f) { return String(f.optionsText || '').split('\n').map((o) => o.trim()).filter(Boolean); },
        async publish() {
            if (this.publishing || !this.svc || this.rulesLocked || !this.rulesDirty) return;
            const problem = this.rulesProblem();
            if (problem) { this.rulesError = problem; return; }
            const key = this.key;
            const body = {
                fee: String(this.rules.fee).trim(),
                days: String(this.rules.days).trim(),
                requirements: this.rules.requirements.map((r) => (r && typeof r === 'object' ? { ...r, label: this.reqLabel(r).trim() } : String(r).trim())),
                workflow: this.rules.workflow.map((st) => ({
                    name: String(st.name).trim(), kind: st.kind, responsible: String(st.responsible || '').trim() || null,
                    days: st.days === '' || st.days === null ? null : Number(st.days),
                })),
                fields: this.rules.fields.map((f) => {
                    const { optionsText, ...rest } = f;
                    const out = { ...rest, label: String(f.label).trim() };
                    if (this.hasOptions(f)) out.options = this.optionsOf(f); else if (!this.isKept(f)) delete out.options;
                    return out;
                }),
            };
            this.publishing = true; this.rulesError = '';
            try {
                const res = await api.post('/admin/settings/catalog/' + encodeURIComponent(key), body);
                toast('Version ' + (res.data?.version ?? this.nextVersion) + ' published. New requests follow it; requests already filed keep the version they were filed under.');
                try {
                    const fresh = await api.get('/admin/settings/catalog/' + encodeURIComponent(key));
                    if (this.key === key) this.applyDetail(fresh.data, { details: true });
                } catch {
                    // Published; only the history could not be read again.
                    if (this.key === key) this.rulesBase = JSON.stringify(this.rules);
                }
            } catch (err) {
                this.rulesError = errExplain(err, 'Could not publish the new version.');
            } finally { this.publishing = false; }
        },

        // ---- a new type (2026-10-17)
        get likeChoices() { return this.rows.filter((r) => r.type === this.fixedType).sort((a, b) => a.name.localeCompare(b.name)); },
        // Barangay certificates are Dokyu's; a Department Head adds to their own office only.
        get draftOffices() {
            if (this.ownOffice) return [this.ownOffice];
            return [...(this.fixedType === 'dokyu' ? [BARANGAY_OFFICE] : []), ...this.offices.filter((o) => o.active !== false).map((o) => o.name)];
        },
        startAdding() {
            this.draft = { type: this.fixedType, name: '', office: this.ownOffice || '', category: '', fee: 'Free', days: '1-3 working days', permissions_like: '' };
            this.createError = '';
            if (this.draft.office) this.suggestLike();
            this.adding = true;
        },
        // The officers who handle a type of the same office, by default.
        suggestLike() {
            const same = this.likeChoices.find((r) => r.office === this.draft.office);
            if (same) this.draft.permissions_like = same.key;
        },
        async createService() {
            if (this.creating || !this.canEdit) return;
            const d = this.draft;
            if (d.name.trim().length < 3) { this.createError = 'The name needs at least 3 characters.'; return; }
            if (!d.office) { this.createError = 'Choose the office that handles it.'; return; }
            if (!d.permissions_like) { this.createError = 'Choose whose officers will handle it.'; return; }
            const fee = feeProblem(d.fee);
            if (fee) { this.createError = fee; return; }
            this.creating = true; this.createError = '';
            try {
                const res = await api.post('/admin/settings/catalog', {
                    type: this.fixedType, name: d.name.trim(), office: d.office, category: d.category.trim() || null,
                    fee: String(d.fee).trim() || 'Free', days: String(d.days).trim() || null, permissions_like: d.permissions_like,
                });
                this.adding = false;
                await this.load();
                toast(words.One + ' added, switched off. Finish its form, documents and steps, then switch it on under Details.');
                this.pendingEdit = res.data?.key || null;
            } catch (err) {
                this.createError = errExplain(err, 'Could not add the ' + words.one + '.');
            } finally { this.creating = false; }
        },

        // the request form as the public record shows it
        get formSteps() {
            const spec = this.spec;
            if (!spec) return [];
            const steps = Array.isArray(spec.form_spec?.steps) ? spec.form_spec.steps : [];
            if (steps.length) return steps.map((s) => ({ label: s.label || s.title || '', fields: Array.isArray(s.fields) ? s.fields : [] }));
            const fields = Array.isArray(spec.fields) ? spec.fields : [];
            return fields.length ? [{ label: '', fields }] : [];
        },
        get entitlement() { return this.spec?.form_spec?.entitlement || ''; },
        fieldKind(f) {
            let label = FIELD_KINDS[f?.type] || (f?.type ? String(f.type) : 'Text');
            const options = Array.isArray(f?.options) ? f.options.map((o) => (o !== null && typeof o === 'object' ? (o.label ?? o.value) : o)) : [];
            if (options.length) label += ': ' + options.slice(0, 4).join(', ') + (options.length > 4 ? ', …' : '');
            if (f?.required === true) label += ' · required';
            else if (f?.required === false) label += ' · optional';
            return label;
        },

        // history: the published versions, and the detail changes in the audit log
        get history() {
            const list = Array.isArray(this.svc?.versions) ? this.svc.versions : [];
            const current = this.svc?.current?.version;
            return list.map((v, i) => {
                const prev = list[i + 1];
                const changes = [];
                if (prev) {
                    if ((v.fee || '') !== (prev.fee || '')) changes.push('Fee: ' + (prev.fee || '—') + ' → ' + (v.fee || '—'));
                    if ((v.days || '') !== (prev.days || '')) changes.push('Processing time: ' + (prev.days || '—') + ' → ' + (v.days || '—'));
                    if (v.requirements !== prev.requirements) changes.push('Required documents: ' + prev.requirements + ' → ' + v.requirements);
                    if (v.steps !== prev.steps) changes.push('Processing steps: ' + prev.steps + ' → ' + v.steps);
                    if (!changes.length) changes.push('Same fee, time and counts: the wording of a document or step changed.');
                } else {
                    changes.push('First version: ' + (v.fee || 'no fee') + ' · ' + (v.days || 'no time given') + ' · ' + plural(v.requirements, 'document', 'documents') + ' · ' + plural(v.steps, 'step', 'steps'));
                }
                return {
                    ...v,
                    changes,
                    current: v.version === current,
                    by: v.published_by && v.published_by !== 'seed' ? 'by ' + v.published_by : 'from the original catalog',
                };
            });
        },
        get canSeeLog() { return may('um_audit', 'view'); },
        async loadLog() {
            if (this.logLoaded || this.logLoading || !this.key || !this.canSeeLog) return;
            const key = this.key;
            this.logLoading = true; this.logFailed = false;
            try {
                const res = await api.get('/admin/audit-logs', { params: { module: 'settings', action: 'catalog.service_updated', target: key, per_page: 50 } });
                if (this.key !== key) return;
                this.log = (res.data || []).filter((l) => l.target === key).map((l) => ({
                    id: l.id, user: l.user || 'System', time: l.time, fields: diffFields(l.before, l.after).filter((f) => f.changed),
                }));
                this.logLoaded = true;
            } catch {
                if (this.key === key) this.logFailed = true;
            } finally { this.logLoading = false; }
        },
    };
}

export function register(Alpine) {
    Alpine.data('catalogEditor', catalogEditor);
}

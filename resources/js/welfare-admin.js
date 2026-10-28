// The welfare round's staff screens (2026-10-29), on the Tulong page and the
// Constituents individual detail:
//
// - tulongApply: "Apply for New Tulong" - an officer files a case for a
//   walk-in or a referral (person, service, its form, requirements seen at
//   the office and consent), or records assistance already given.
// - servicesRendered, serviceRecordForm, caseServiceRecords,
//   residentServices: the services given to a person (section C) - the
//   Tulong page's "Services rendered" view, the "Record a service" window, a
//   counseling / referral / session case's "Service records", and the
//   Constituents individual detail's latest ten.
//
// Loaded only where it is used (the area loader at the end of app.js), so
// neither the shared bundle nor the Tulong page's HTML carries it; register()
// runs before Alpine starts. The components sit inside the Tulong page's own
// x-data and read its tulongTypesByName, barangays, requests, fromApi(),
// openRequest() through Alpine's scope chain; every name they define is
// prefixed (ap*, ...) so none shadows the page's.
import api from './api/client';

const TZ = 'Asia/Manila';

/** A toast on window: after an await, the button that asked may be gone. */
function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}

/** The server's own words for a failure (English: the staff site), else the fallback. */
const says = (err, fallback) => (err && err.localised ? err.localised('en') : fallback);

/** Today in Esperanza, as Y-m-d. */
const manilaToday = () => new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());

/** 'Oct 15, 2026' for a Y-m-d date or a timestamp, as the day it is in Esperanza. */
function day(value) {
    if (!value) return '';
    const s = String(value);
    const d = /^\d{4}-\d{2}-\d{2}$/.test(s) ? new Date(s + 'T00:00:00+08:00') : new Date(s);
    return Number.isNaN(d.getTime()) ? s : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: TZ });
}

/** '13:30' -> '1:30 PM'. */
function time(hm) {
    const m = /^(\d{1,2}):(\d{2})/.exec(String(hm || ''));
    if (!m) return hm || '';
    const h = Number(m[1]);
    return (h % 12 || 12) + ':' + m[2] + (h < 12 ? ' AM' : ' PM');
}

function peso(value) {
    if (value === null || value === undefined || value === '') return '';
    const n = Number(value);
    return Number.isNaN(n) ? String(value) : '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/** Age in whole years on a day (Y-m-d), or null. */
function ageOn(birth, onDay = manilaToday()) {
    const b = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(birth || ''));
    const o = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(onDay || ''));
    if (!b || !o) return null;
    let age = Number(o[1]) - Number(b[1]);
    if (Number(o[2]) < Number(b[2]) || (o[2] === b[2] && Number(o[3]) < Number(b[3]))) age -= 1;
    return age;
}

const can = (submodule, action) => !!window.Alpine?.store('session')?.canPerm(submodule, action);
const icons = () => window.Alpine?.nextTick(() => window.renderIcons?.());

// ----------------------------------------------------------------- Catalog

// The Tulong catalog as the page has it (config/esperanza_tulong_types.php,
// staff-only services included), laid over by what the server publishes now
// (GET /services?type=tulong): staff edit a service's requirements and form
// in the Service Catalog, and the request's requirement rows (req_1, req_2...)
// follow the server's list. The public list leaves staff-only services out,
// so those keep the page's own.
let livePromise = null;
function liveServices() {
    if (!livePromise) {
        livePromise = api.get('/services', { params: { type: 'tulong' } })
            .then((res) => {
                const by = {};
                (res?.data || []).forEach((sv) => { if (sv && sv.key) by[sv.key] = sv; });
                return by;
            })
            .catch(() => { livePromise = null; return {}; });
    }
    return livePromise;
}

function catalogEntry(type, live) {
    const t = JSON.parse(JSON.stringify(type || {}));
    const sv = live && live[t.key];
    if (sv && sv.version !== null && sv.version !== undefined) {
        if (sv.name) t.name = sv.name;
        if (sv.category) t.category = sv.category;
        if (Array.isArray(sv.requirements)) t.requirements = Array.isArray(sv.requirement_rules) ? sv.requirement_rules : sv.requirements;
        if (Array.isArray(sv.fields)) t.fields = JSON.parse(JSON.stringify(sv.fields));
    }
    t.rules = window.requirementRules ? window.requirementRules(t.requirements) : (t.requirements || []).map((r) => ({ label: String(r), showIf: null }));
    return t;
}

/** How a service is worked, as a services-rendered record's kind. */
export function recordKind(processingKind) {
    return { medical: 'assistance', cash: 'assistance', goods: 'assistance', registration: 'registration', counseling: 'counseling', session: 'session', referral: 'referral' }[processingKind] || 'other';
}

// ------------------------------------------------------------ Person search

/**
 * The person a case or a record is for: GET /admin/people/search?q= (two
 * characters or more; residents of the registry and citizen accounts, merged,
 * at most 20), or a person not in the list. Spread into a component: methods
 * only, no getters (a getter in a spread object is read once and frozen).
 */
export function personPicker() {
    return {
        pq: '',
        pResults: [],
        pLoading: false,
        pFailed: false,
        pSearched: false,
        pSeq: 0,
        person: null,
        pMode: 'search',
        pNew: { name: '', sex: '', birth_date: '', barangay: '', contact: '', add_to_registry: true },
        pReset() {
            this.pq = '';
            this.pResults = [];
            this.pLoading = false;
            this.pFailed = false;
            this.pSearched = false;
            this.person = null;
            this.pMode = 'search';
            this.pNew = { name: '', sex: '', birth_date: '', barangay: '', contact: '', add_to_registry: true };
        },
        async pSearch() {
            const q = this.pq.trim();
            const seq = ++this.pSeq;
            if (q.length < 2) {
                this.pResults = [];
                this.pSearched = false;
                this.pLoading = false;
                return;
            }
            this.pLoading = true;
            this.pFailed = false;
            try {
                const res = await api.get('/admin/people/search', { params: { q } });
                if (seq !== this.pSeq) return;
                this.pResults = (Array.isArray(res?.data) ? res.data : []).slice(0, 20);
                this.pSearched = true;
            } catch {
                if (seq === this.pSeq) { this.pResults = []; this.pFailed = true; }
            } finally {
                if (seq === this.pSeq) this.pLoading = false;
                icons();
            }
        },
        pPick(p) {
            this.person = p;
            this.pMode = 'search';
        },
        pLine(p) {
            if (!p) return '';
            return [p.sex, p.age !== null && p.age !== undefined ? p.age + ' yrs' : (p.birth_date ? day(p.birth_date) : ''), p.barangay ? 'Brgy. ' + p.barangay : '', p.individual_ref, p.account_no].filter(Boolean).join(' · ');
        },
        /** The person as the API takes it. */
        pPayload() {
            if (this.pMode === 'new') {
                const n = this.pNew;
                return { name: n.name.trim(), sex: n.sex || null, birth_date: n.birth_date || null, barangay: n.barangay || null, contact: n.contact.trim() || null, add_to_registry: !!n.add_to_registry };
            }
            const p = this.person || {};
            if (p.kind === 'account' && p.account_no) return { account_no: p.account_no };
            if (p.individual_ref) return { individual_ref: p.individual_ref };
            return p.account_no ? { account_no: p.account_no } : null;
        },
        pName() {
            return this.pMode === 'new' ? this.pNew.name.trim() : (this.person?.name || '');
        },
        /** '' when the person is enough to go on, else what is missing. */
        pProblem() {
            if (this.pMode === 'new') {
                if (this.pNew.name.trim().length < 3) return 'Write the person\'s full name.';
                if (!this.pNew.barangay) return 'Choose the person\'s barangay.';
                const phone = window.phMobile?.error(this.pNew.contact, false, 'en');
                return phone || '';
            }
            return this.person ? '' : 'Find the person, or choose "Not in the list".';
        },
    };
}

// --------------------------------------------------- Apply for New Tulong

/**
 * Filing on behalf of a resident (section B): POST /admin/tulong/requests,
 * or, for a walk-in at a group session, POST
 * /admin/tulong/sessions/{ref}/participants/walk-in with the same body.
 * Opened by the page's button, or by a 'tulong-apply' window event
 * ({ sessionRef, serviceKey, sessionTitle } for a walk-in).
 */
export function tulongApply() {
    return {
        ...personPicker(),
        apOpen: false,
        apStep: 1,
        apBusy: false,
        apError: '',
        apSessionRef: null,
        apSessionTitle: '',
        apLive: {},
        apSearch: '',
        apKey: '',
        apType: null,
        apForm: {},
        apReqs: [],
        apConsent: false,
        apChannel: 'walk_in',
        apReferredBy: '',
        apRecordOnly: false,
        apRecord: { completed_on: '', amount: '', released_to: '', note: '' },
        apSteps: ['Person', 'Service', 'Form', 'Requirements & consent'],
        day,

        /** Every Tulong service in the page's catalog, the server's wording over it. */
        apCatalog() {
            const byName = this.tulongTypesByName || {};
            return Object.values(byName).filter((t) => t && t.key).map((t) => catalogEntry(t, this.apLive));
        },
        /** The services this officer may file (each service's own create). */
        apCreatable() {
            return this.apCatalog().filter((t) => can(t.key, 'create'));
        },
        apCanApply() {
            return Object.values(this.tulongTypesByName || {}).some((t) => t && t.key && can(t.key, 'create'));
        },
        apGroups() {
            const q = this.apSearch.trim().toLowerCase();
            const groups = {};
            this.apCreatable()
                .filter((t) => !q || [t.name, t.category, t.office_short].some((v) => String(v || '').toLowerCase().includes(q)))
                .forEach((t) => { (groups[t.category || 'Other'] ||= []).push(t); });
            return Object.keys(groups).sort().map((category) => ({ category, items: groups[category] }));
        },
        openApply(opts = {}) {
            this.pReset();
            this.apStep = 1;
            this.apBusy = false;
            this.apError = '';
            this.apSearch = '';
            this.apKey = '';
            this.apType = null;
            this.apForm = {};
            this.apReqs = [];
            this.apConsent = false;
            this.apChannel = 'walk_in';
            this.apReferredBy = '';
            this.apRecordOnly = false;
            this.apRecord = { completed_on: manilaToday(), amount: '', released_to: '', note: '' };
            this.apSessionRef = opts.sessionRef || null;
            this.apSessionTitle = opts.sessionTitle || '';
            this.apOpen = true;
            liveServices().then((live) => {
                this.apLive = live;
                if (opts.serviceKey) this.apChoose(opts.serviceKey);
            });
            icons();
        },
        apChoose(key) {
            const t = this.apCatalog().find((x) => x.key === key);
            if (!t) return;
            this.apKey = key;
            this.apType = t;
            const form = {};
            (t.fields || []).forEach((f) => {
                form[f.key] = (f.type === 'checkbox-group' || f.type === 'multiselect' || f.type === 'table') ? [] : (f.default ?? '');
            });
            if (!(t.fields || []).length) form.purpose = '';
            this.apForm = form;
            // Each requirement keeps its place in the server's list: req_1, req_2...
            this.apReqs = t.rules.map((r, i) => ({ key: 'req_' + (i + 1), label: r.label, showIf: r.showIf || null, presented: false }));
            this.apRecordOnly = false;
            this.apError = '';
            icons();
        },
        apShown(f) {
            return window.showIfMet ? window.showIfMet(f?.showIf, this.apForm) : true;
        },
        apShownReqs() {
            return this.apReqs.filter((r) => (window.showIfMet ? window.showIfMet(r.showIf, this.apForm) : true));
        },
        apToggle(key, option) {
            const list = Array.isArray(this.apForm[key]) ? this.apForm[key] : [];
            const i = list.indexOf(option);
            if (i === -1) list.push(option); else list.splice(i, 1);
            this.apForm[key] = list;
        },
        apAddRow(f) {
            if (!Array.isArray(this.apForm[f.key])) this.apForm[f.key] = [];
            const row = {};
            (f.columns || []).forEach((c) => { row[c.key] = ''; });
            this.apForm[f.key].push(row);
        },
        apAge(f) {
            return ageOn(this.apForm[f.derived_from]);
        },
        apMayRecord() {
            return !!this.apKey && !this.apSessionRef && can(this.apKey, 'release');
        },
        apShowsAmount() {
            return !['goods', 'registration', 'counseling', 'session', 'referral'].includes(this.apType?.processingKind);
        },
        /** Step by step, '' when the step is complete. */
        apProblem(step = this.apStep) {
            if (step === 1) return this.pProblem();
            if (step === 2) return this.apKey ? '' : 'Choose the service.';
            if (step === 3) {
                const missing = (this.apType?.fields || []).filter((f) => {
                    if (!f.required || f.type === 'derived_age' || !this.apShown(f)) return false;
                    const v = this.apForm[f.key];
                    return Array.isArray(v) ? v.length === 0 : String(v ?? '').trim() === '';
                }).map((f) => f.label);
                if (missing.length) return 'Fill in: ' + missing.join(', ');
                for (const f of (this.apType?.fields || []).filter((x) => x.type === 'tel' && this.apShown(x))) {
                    const err = window.phMobile?.error(this.apForm[f.key], !!f.required, 'en');
                    if (err) return f.label + ': ' + err;
                }
                return '';
            }
            if (step === 4) {
                if (this.apChannel === 'referral' && !this.apReferredBy.trim()) return 'Say who referred the person.';
                if (this.apRecordOnly) {
                    if (!this.apRecord.completed_on) return 'Give the date the assistance was given.';
                    if (!this.apRecord.released_to.trim()) return 'Write who received it.';
                }
                return this.apConsent ? '' : 'The person must agree to the privacy notice first.';
            }
            return '';
        },
        apNext() {
            const problem = this.apProblem();
            this.apError = problem;
            if (problem) return;
            if (this.apStep === 1 && this.apSessionRef && this.apKey) this.apStep = 3;
            else this.apStep += 1;
            if (this.apStep === 4 && this.apRecordOnly && !this.apRecord.released_to) this.apRecord.released_to = this.pName();
            icons();
            this.$nextTick(() => document.getElementById('ap-step-title')?.focus({ preventScroll: true }));
        },
        apBack() {
            this.apError = '';
            if (this.apStep === 3 && this.apSessionRef) this.apStep = 1;
            else if (this.apStep > 1) this.apStep -= 1;
            icons();
        },
        apPayload() {
            const form = JSON.parse(JSON.stringify(this.apForm || {}));
            for (const f of (this.apType?.fields || [])) {
                if (!this.apShown(f)) delete form[f.key];
                if (!this.apShown(f) || !/^others?$/i.test(form[f.key] || '') || !String(form[f.key + '_other'] || '').trim()) delete form[f.key + '_other'];
            }
            const body = {
                service_key: this.apKey,
                person: this.pPayload(),
                form_data: form,
                channel: this.apChannel,
                referred_by: this.apChannel === 'referral' ? this.apReferredBy.trim() : null,
                requirements_presented: this.apShownReqs().filter((r) => r.presented).map((r) => r.key),
                consent: !!this.apConsent,
                record_only: null,
            };
            if (this.apRecordOnly && this.apMayRecord()) {
                body.record_only = {
                    completed_on: this.apRecord.completed_on,
                    amount: this.apShowsAmount() && this.apRecord.amount !== '' ? Number(this.apRecord.amount) : null,
                    released_to: this.apRecord.released_to.trim(),
                    note: this.apRecord.note.trim() || null,
                };
            }
            return body;
        },
        async apSubmit() {
            if (this.apBusy) return;
            for (const step of [1, 2, 3, 4]) {
                const problem = this.apProblem(step);
                if (problem) { this.apError = problem; this.apStep = step === 2 && this.apSessionRef ? 1 : step; return; }
            }
            this.apBusy = true;
            this.apError = '';
            const sessionRef = this.apSessionRef;
            try {
                const url = sessionRef
                    ? '/admin/tulong/sessions/' + encodeURIComponent(sessionRef) + '/participants/walk-in'
                    : '/admin/tulong/requests';
                const res = await api.post(url, this.apPayload());
                const data = res?.data || {};
                this.apOpen = false;
                if (sessionRef) {
                    toast(this.pName() + ' was added to the session.');
                    window.dispatchEvent(new CustomEvent('welfare-session-changed', { detail: { ref: sessionRef, session: data.participants ? data : null } }));
                } else {
                    toast((data.ref ? data.ref + ' filed' : 'Filed') + ' for ' + (data.applicant || this.pName()) + '.');
                    if (typeof this.openFiledCase === 'function') this.openFiledCase(data);
                }
            } catch (err) {
                this.apError = says(err, 'Could not file the request.');
                toast(this.apError, 'error');
            } finally {
                this.apBusy = false;
            }
        },
    };
}

// ------------------------------------------------------ Services rendered

const RECORD_KINDS = [['assistance', 'Assistance'], ['counseling', 'Counseling'], ['session', 'Group session'], ['referral', 'Referral'], ['registration', 'Registration'], ['other', 'Other']];
const RECORD_MODES = [['office', 'At the office'], ['home_visit', 'Home visit'], ['field', 'In the field'], ['online', 'Online or by phone']];
const RECORD_SOURCES = [['walk-in', 'Walk-in'], ['PNP-WCPD', 'PNP-WCPD'], ['barangay', 'Barangay'], ['BCPC', 'BCPC'], ['court', 'Court'], ['school', 'School'], ['DSWD', 'DSWD'], ['RHU', 'RHU'], ['other', 'Other']];
const SOURCE_LABELS = { manual: 'Recorded', tulong: 'Tulong case', session: 'Session', record: 'Filed as given', backfill: 'Past case' };
const kindLabel = (k) => (RECORD_KINDS.find((x) => x[0] === k) || [k, k || ''])[1];
const modeLabel = (m) => (RECORD_MODES.find((x) => x[0] === m) || [m, m || ''])[1];
const sourceLabel = (s) => (RECORD_SOURCES.find((x) => x[0] === s) || [s, s || ''])[1];

/** A record row with the per-row state its Void form needs (kept on the row). */
function recordRow(r) {
    return { ...r, person: r.person || {}, _voiding: false, _reason: '', _busy: false };
}

/** POST /admin/services-rendered/{ref}/void { reason }: the row comes back voided. */
async function voidRecord(r) {
    const reason = String(r._reason || '').trim();
    if (reason.length < 5 || r._busy) return false;
    r._busy = true;
    try {
        const res = await api.post('/admin/services-rendered/' + encodeURIComponent(r.ref) + '/void', { reason });
        const back = res?.data || {};
        r.voided_at = back.voided_at || new Date().toISOString();
        r.void_reason = back.void_reason || reason;
        r._voiding = false;
        toast(r.ref + ' is voided.', 'warning');
        return true;
    } catch (err) {
        toast(says(err, 'Could not void ' + r.ref + '.'), 'error');
        return false;
    } finally {
        r._busy = false;
    }
}

/**
 * The Tulong page's "Services rendered" view: GET /admin/services-rendered
 * with the filters, a page at a time. Arriving with
 * ?view=services&individual_ref=...|account_no=...&person=Name (the
 * Constituents detail's "See all") shows that person's records.
 */
export function servicesRendered() {
    return {
        srRows: [],
        srMeta: { total: 0, page: 1, per_page: 25 },
        srLoading: false,
        srFailed: false,
        srLoaded: false,
        srSeq: 0,
        srF: { q: '', service_key: '', kind: '', from: '', to: '', barangay: '', include_voided: false },
        srPerson: null,
        srKinds: RECORD_KINDS,
        day,
        peso,
        kindLabel,
        modeLabel,
        sourceLabel,
        sourceOf: (s) => SOURCE_LABELS[s] || s || '',
        srInit() {
            const params = new URLSearchParams(window.location.search);
            const ind = params.get('individual_ref');
            const acc = params.get('account_no');
            if (ind || acc) this.srPerson = { individual_ref: ind || null, account_no: acc || null, name: params.get('person') || ind || acc };
        },
        srServices() {
            return Object.values(this.tulongTypesByName || {}).filter((t) => t && t.key).sort((a, b) => String(a.name).localeCompare(String(b.name)));
        },
        srCanCreate() { return can('tulong_records', 'create'); },
        srCanVoid() { return can('tulong_records', 'edit'); },
        srParams(page) {
            const p = { page, per_page: this.srMeta.per_page || 25 };
            ['q', 'service_key', 'kind', 'from', 'to', 'barangay'].forEach((k) => { const v = String(this.srF[k] || '').trim(); if (v) p[k] = v; });
            p.include_voided = this.srF.include_voided ? 1 : 0;
            if (this.srPerson?.individual_ref) p.individual_ref = this.srPerson.individual_ref;
            else if (this.srPerson?.account_no) p.account_no = this.srPerson.account_no;
            return p;
        },
        async srLoad(page = 1) {
            const seq = ++this.srSeq;
            this.srLoading = true;
            this.srFailed = false;
            try {
                const res = await api.get('/admin/services-rendered', { params: this.srParams(page) });
                if (seq !== this.srSeq) return;
                this.srRows = (res?.data || []).map(recordRow);
                const m = res?.meta || {};
                this.srMeta = { total: m.total ?? this.srRows.length, page: m.page || m.current_page || page, per_page: m.per_page || this.srMeta.per_page || 25 };
                this.srLoaded = true;
            } catch {
                if (seq === this.srSeq) this.srFailed = true;
            } finally {
                if (seq === this.srSeq) this.srLoading = false;
                icons();
            }
        },
        srPages() { return Math.max(1, Math.ceil((this.srMeta.total || 0) / (this.srMeta.per_page || 25))); },
        srClear() {
            this.srF = { q: '', service_key: '', kind: '', from: '', to: '', barangay: '', include_voided: false };
            this.srLoad(1);
        },
        srClearPerson() {
            this.srPerson = null;
            const url = new URL(window.location.href);
            ['individual_ref', 'account_no', 'person'].forEach((k) => url.searchParams.delete(k));
            window.history.replaceState(null, '', url);
            this.srLoad(1);
        },
        srRecord() {
            window.dispatchEvent(new CustomEvent('welfare-record-service', { detail: {} }));
        },
        srVoid(r) { return voidRecord(r); },
        srCase(r) {
            if (r.request_ref && typeof this.openByRef === 'function') this.openByRef(r.request_ref);
        },
    };
}

/**
 * "Record a service" (section C): POST /admin/services-rendered. Opened by a
 * 'welfare-record-service' window event; from a case it carries the case's
 * ref (the person then comes from the request) and its service. A sensitive
 * service's record is always confidential.
 */
export function serviceRecordForm() {
    return {
        ...personPicker(),
        rfOpen: false,
        rfBusy: false,
        rfError: '',
        rfRequestRef: null,
        rfFixedPerson: '',
        rfKey: '',
        rfOther: '',
        rfKind: 'counseling',
        rfDate: '',
        rfMode: 'office',
        rfSource: '',
        rfReferredTo: '',
        rfDetails: '',
        rfAmount: '',
        rfConfidential: false,
        rfKinds: RECORD_KINDS,
        rfModes: RECORD_MODES,
        rfSources: RECORD_SOURCES,
        rfToday: manilaToday,
        rfServices() {
            return Object.values(this.tulongTypesByName || {}).filter((t) => t && t.key).sort((a, b) => String(a.name).localeCompare(String(b.name)));
        },
        rfType() {
            return this.rfKey && this.rfKey !== '__other' ? this.rfServices().find((t) => t.key === this.rfKey) || null : null;
        },
        rfSensitive() { return !!this.rfType()?.sensitive; },
        openRecord(opts = {}) {
            this.pReset();
            this.rfBusy = false;
            this.rfError = '';
            this.rfRequestRef = opts.request_ref || null;
            this.rfFixedPerson = opts.person_name || '';
            this.rfKey = '';
            this.rfOther = '';
            this.rfKind = 'counseling';
            this.rfDate = manilaToday();
            this.rfMode = 'office';
            this.rfSource = '';
            this.rfReferredTo = '';
            this.rfDetails = '';
            this.rfAmount = '';
            this.rfConfidential = false;
            if (opts.service_key) {
                this.rfKey = opts.service_key;
                this.rfChosen();
            }
            this.rfOpen = true;
            icons();
        },
        rfChosen() {
            const t = this.rfType();
            if (t) this.rfKind = recordKind(t.processingKind);
            else if (this.rfKey === '__other') this.rfKind = 'other';
            if (this.rfSensitive()) this.rfConfidential = true;
        },
        rfProblem() {
            if (!this.rfRequestRef) {
                const who = this.pProblem();
                if (who) return who;
            }
            if (!this.rfKey) return 'Choose the service given, or Other.';
            if (this.rfKey === '__other' && this.rfOther.trim().length < 3) return 'Name the service given.';
            if (!this.rfDate) return 'Give the date the service was given.';
            if (this.rfDate > manilaToday()) return 'The date cannot be in the future.';
            if (this.rfDetails.length > 2000) return 'The details are at most 2,000 characters.';
            if (this.rfAmount !== '' && !(Number(this.rfAmount) >= 0)) return 'The amount must be zero or more.';
            return '';
        },
        async rfSave() {
            if (this.rfBusy) return;
            const problem = this.rfProblem();
            this.rfError = problem;
            if (problem) return;
            const body = {
                kind: this.rfKind,
                rendered_on: this.rfDate,
                mode: this.rfMode || null,
                referral_source: this.rfSource || null,
                referred_to: this.rfReferredTo.trim() || null,
                details: this.rfDetails.trim() || null,
                amount: this.rfAmount === '' ? null : Number(this.rfAmount),
                confidential: this.rfSensitive() ? true : !!this.rfConfidential,
            };
            if (this.rfKey === '__other') body.service_name = this.rfOther.trim();
            else body.service_key = this.rfKey;
            if (this.rfRequestRef) body.request_ref = this.rfRequestRef;
            else body.person = this.pPayload();
            this.rfBusy = true;
            try {
                const res = await api.post('/admin/services-rendered', body);
                const row = res?.data || null;
                this.rfOpen = false;
                toast((row?.ref ? row.ref + ' recorded' : 'Recorded') + (this.rfRequestRef ? ' on ' + this.rfRequestRef : '') + '.');
                window.dispatchEvent(new CustomEvent('welfare-records-changed', { detail: { request_ref: this.rfRequestRef, row } }));
            } catch (err) {
                this.rfError = says(err, 'Could not save the record.');
            } finally {
                this.rfBusy = false;
            }
        },
    };
}

/**
 * A counseling, referral or session case's "Service records": its rows
 * (GET /admin/services-rendered?request_ref=), "Add a record", and - for
 * counseling and referral, from Assessment, once a record is on it - "Close
 * the case" (POST /admin/tulong/requests/{ref}/complete-service { note }).
 * A session's case closes only when its session is finished.
 */
export function caseServiceRecords() {
    return {
        crRows: [],
        crLoading: false,
        crFailed: false,
        crFor: null,
        crClosing: false,
        crNote: '',
        crBusy: false,
        crError: '',
        day,
        peso,
        kindLabel,
        modeLabel,
        crShown() {
            return !!this.selected && ['counseling', 'referral', 'session'].includes(this.processingKind);
        },
        crSync() {
            const ref = this.selected?.ref || null;
            if (!this.crShown() || !ref) return;
            if (ref === this.crFor) return;
            this.crFor = ref;
            this.crRows = [];
            this.crClosing = false;
            this.crNote = '';
            this.crError = '';
            this.crLoad();
        },
        async crLoad() {
            const ref = this.crFor;
            if (!ref) return;
            this.crLoading = true;
            this.crFailed = false;
            try {
                const res = await api.get('/admin/services-rendered', { params: { request_ref: ref, include_voided: 1, per_page: 50 } });
                if (ref === this.crFor) this.crRows = (res?.data || []).map(recordRow);
            } catch {
                if (ref === this.crFor) this.crFailed = true;
            } finally {
                if (ref === this.crFor) this.crLoading = false;
                icons();
            }
        },
        crLive() { return this.crRows.filter((r) => !r.voided_at).length; },
        crCanAdd() { return can('tulong_records', 'create') && !['Rejected', 'Cancelled'].includes(this.selected?.status); },
        crCanVoid() { return can('tulong_records', 'edit'); },
        crMayClose() {
            return ['counseling', 'referral'].includes(this.processingKind) && this.selected?.status === 'Assessment' && !this.detailLoading;
        },
        crAdd() {
            window.dispatchEvent(new CustomEvent('welfare-record-service', { detail: { request_ref: this.selected.ref, person_name: this.selected.citizen, service_key: this.selected.service_key } }));
        },
        crVoid(r) { return voidRecord(r); },
        async crClose() {
            const r = this.selected;
            if (!r || this.crBusy) return;
            if (this.crNote.trim().length < 5) { this.crError = 'Write a short closing summary (at least 5 characters).'; return; }
            this.crBusy = true;
            this.crError = '';
            try {
                const res = await api.post('/admin/tulong/requests/' + encodeURIComponent(r.ref) + '/complete-service', { note: this.crNote.trim() });
                if (res?.data && typeof this.applyDetail === 'function') this.applyDetail(r, res.data);
                const row = (this.requests || []).find((x) => x.ref === r.ref);
                if (row && res?.data?.status) row.status = res.data.status;
                this.crClosing = false;
                this.crNote = '';
                toast(r.ref + ' is closed. The service is on record.');
                this.crLoad();
            } catch (err) {
                this.crError = says(err, 'Could not close ' + r.ref + '.');
            } finally {
                this.crBusy = false;
                icons();
            }
        },
    };
}

/**
 * Constituents > individual detail: the latest ten services given to the
 * resident (GET /admin/services-rendered?individual_ref=&per_page=10), and
 * "See all" in the Tulong page's Services rendered view.
 */
export function residentServices() {
    return {
        rsRows: [],
        rsTotal: 0,
        rsLoading: false,
        rsFailed: false,
        rsFor: null,
        day,
        peso,
        kindLabel,
        rsAllowed() { return can('tulong_records', 'view'); },
        rsSync(ref, open) {
            if (!open) { this.rsFor = null; return; }
            if (!ref || !this.rsAllowed() || ref === this.rsFor) return;
            this.rsFor = ref;
            this.rsRows = [];
            this.rsLoad();
        },
        async rsLoad() {
            const ref = this.rsFor;
            if (!ref) return;
            this.rsLoading = true;
            this.rsFailed = false;
            try {
                const res = await api.get('/admin/services-rendered', { params: { individual_ref: ref, per_page: 10, page: 1 } });
                if (ref !== this.rsFor) return;
                this.rsRows = (res?.data || []).slice(0, 10);
                this.rsTotal = res?.meta?.total ?? this.rsRows.length;
            } catch {
                if (ref === this.rsFor) this.rsFailed = true;
            } finally {
                if (ref === this.rsFor) this.rsLoading = false;
                icons();
            }
        },
        rsSeeAll(ref, name) {
            return '/admin/assistance-requests?view=services&individual_ref=' + encodeURIComponent(ref || '') + '&person=' + encodeURIComponent(name || '');
        },
    };
}

export function register(Alpine) {
    Alpine.data('tulongApply', tulongApply);
    Alpine.data('servicesRendered', servicesRendered);
    Alpine.data('serviceRecordForm', serviceRecordForm);
    Alpine.data('caseServiceRecords', caseServiceRecords);
    Alpine.data('residentServices', residentServices);
}

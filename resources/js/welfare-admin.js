// The welfare round's staff screens (2026-10-29), on the Tulong page and the
// Constituents individual detail:
//
// - tulongApply: "Apply for New Tulong" - an officer files a case for a
//   walk-in or a referral (person, service, its form, requirements seen at
//   the office and consent), or records assistance already given.
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

export function register(Alpine) {
    Alpine.data('tulongApply', tulongApply);
}

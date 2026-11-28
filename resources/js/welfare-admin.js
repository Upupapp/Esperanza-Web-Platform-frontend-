// The welfare round's staff screens (2026-10-29), on the Tulong page:
//
// - tulongApply: "Apply for New Tulong" - an officer files a case for a
//   walk-in or a referral (person, service, its form, the requirements'
//   documents uploaded and consent), or records assistance already given.
// - caseNotes: a counseling / referral / session case's "Case notes" (round
//   2, 2026-10-30). The Services rendered view, the "Record a service"
//   window and the Constituents detail's list of them were removed then: the
//   owner's "services rendered just means the tulong".
// - sessionList, sessionDesk, caseSession: group sessions and their
//   certificates (section D) - the Sessions view, the session window (its
//   participants, attendance, and "Finish and issue certificates") with the
//   New / Edit session window, and a session case's own Session panel.
//
// Loaded only where it is used (the area loader at the end of app.js), so
// neither the shared bundle nor the Tulong page's HTML carries it; register()
// runs before Alpine starts. The components sit inside the Tulong page's own
// x-data and read its tulongTypesByName, barangays, requests, fromApi(),
// openRequest() through Alpine's scope chain; every name they define is
// prefixed (ap*, ...) so none shadows the page's.
import api from './api/client';
import { registerCertificate, openWelfareCertificate } from './welfare-certificate';
import { uploadProblem, fileSize, attachAtOffice } from './requirement-upload';

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
const canStep = (submodule, step) => !!window.Alpine?.store('session')?.canStep?.(submodule, step);
const icons = () => window.Alpine?.nextTick(() => window.renderIcons?.());

// ----------------------------------------------------------------- Catalog

// The Tulong catalog as the page has it (config/esperanza_tulong_types.php,
// staff-only services included), laid over by what the server publishes now
// (GET /services?type=tulong): staff edit a type's requirements and form
// under Tulong types, and the request's requirement rows (req_1, req_2...)
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
    if (sv && sv.submodule_key) t.submodule = sv.submodule_key;
    t.rules = window.requirementRules ? window.requirementRules(t.requirements) : (t.requirements || []).map((r) => ({ label: String(r), showIf: null }));
    return t;
}

/**
 * A Tulong type made under Tulong types after the page was built: in the
 * server's list only, never in the page's catalog (sweep 2026-11-18). In the
 * page catalog's shape; catalogEntry() then lays its version over it.
 */
function liveOnlyEntry(sv) {
    return {
        key: sv.key, name: sv.name || sv.key, category: sv.category || null,
        office: sv.office || null, office_short: sv.office_short || null, level: sv.level || null,
        requirements: [], fields: [],
        processingKind: sv.processing_kind || null, sessionCouple: !!sv.session_couple,
        sensitive: !!sv.sensitive, staffOnly: !!sv.staff_only, certificate: sv.certificate || null,
    };
}

/**
 * The permission a type is filed and released under: its RBAC submodule.
 * A type made under Tulong types shares the one of the type it was made
 * like (submodule_key); the page's own types are their submodule.
 */
const typePerm = (t) => (t && (t.submodule || t.key)) || '';

// ------------------------------------------------------------ Person search

/**
 * The person a case is for: GET /admin/people/search?q= (two
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
 * ({ sessionRef, serviceKey, sessionTitle, knownRefs } for a walk-in).
 *
 * The requirements' documents are uploaded, not ticked as seen (owner, round
 * 2, 2026-10-30: "uploaded and is recorded by the system not just presented
 * at the office"): a file per requirement, kept on its row (file, state,
 * progress, error), each sent to the new case once it is filed. A file that
 * does not go up leaves the window open on what was filed and which document
 * to attach from the case.
 */
export function tulongApply() {
    return {
        ...personPicker(),
        apOpen: false,
        apStep: 1,
        apBusy: false,
        apError: '',
        // The case once filed ({ ref, detail }): nothing is filed twice.
        apFiled: null,
        apSessionRef: null,
        apSessionTitle: '',
        apKnownRefs: [],
        apFixedKey: '',
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
        // The steps' names (round 2: the owner's "Tulong Type", never "Service").
        apSteps: ['Person', 'Tulong Type', 'Form', 'Requirements & consent'],
        day,
        fileSize,

        init() {
            // Closed (Escape, outside it) on a filed case whose documents did
            // not all go up: the case still opens on the page.
            this.$watch('apOpen', (open) => { if (!open && this.apFiled && !this.apBusy) this.apFinish(); });
        },

        /**
         * Every Tulong service in the page's catalog, the server's wording
         * over it, and those the server lists that the page's catalog does
         * not have (made under Tulong types since the page was built).
         */
        apCatalog() {
            const byName = this.tulongTypesByName || {};
            const page = Object.values(byName).filter((t) => t && t.key);
            const known = new Set(page.map((t) => t.key));
            const added = Object.values(this.apLive || {})
                .filter((sv) => sv && sv.key && !known.has(sv.key) && (!sv.type || sv.type === 'tulong'))
                .map(liveOnlyEntry);
            return [...page, ...added].map((t) => catalogEntry(t, this.apLive));
        },
        /** The services this officer may file (each service's own create). */
        apCreatable() {
            return this.apCatalog().filter((t) => can(typePerm(t), 'create'));
        },
        apGroups() {
            const q = this.apSearch.trim().toLowerCase();
            const groups = {};
            (this.apFixedKey ? this.apCatalog().filter((t) => t.key === this.apFixedKey) : this.apCreatable())
                .filter((t) => !q || [t.name, t.category, t.office_short].some((v) => String(v || '').toLowerCase().includes(q)))
                .forEach((t) => { (groups[t.category || 'Other'] ||= []).push(t); });
            return Object.keys(groups).sort().map((category) => ({ category, items: groups[category] }));
        },
        openApply(opts = {}) {
            this.pReset();
            this.apStep = 1;
            this.apBusy = false;
            this.apError = '';
            this.apFiled = null;
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
            // A walk-in files the session's own service.
            this.apFixedKey = opts.sessionRef ? (opts.serviceKey || '') : '';
            this.apSessionTitle = opts.sessionTitle || '';
            // The session's cases before this one: the new case is the one not among them.
            this.apKnownRefs = Array.isArray(opts.knownRefs) ? opts.knownRefs : [];
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
            // Each requirement keeps its place in the server's list: req_1,
            // req_2... Its chosen file and how its upload went stay on it.
            this.apReqs = t.rules.map((r, i) => ({ key: 'req_' + (i + 1), label: r.label, showIf: r.showIf || null, file: null, state: '', progress: 0, error: '' }));
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
        /** A file chosen for a requirement (from the files, or the camera): checked, then kept on the row. */
        apPickFile(r, event) {
            const input = event?.target;
            const file = input?.files?.[0] || null;
            if (input) input.value = '';
            if (!file) return;
            const problem = uploadProblem(file);
            r.error = problem ? file.name + ': ' + problem : '';
            if (!problem) r.file = file;
            r.state = '';
            r.progress = 0;
        },
        apDropFile(r) {
            r.file = null;
            r.error = '';
            r.state = '';
            r.progress = 0;
        },
        apChosenCount() {
            return this.apShownReqs().filter((r) => r.file).length;
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
            // Help already given is the import step, as OfficeFiling checks it (bug hunt 25 Nov, A3).
            return !!this.apKey && !this.apSessionRef && canStep(typePerm(this.apType || { key: this.apKey }), 'import');
        },
        apShowsAmount() {
            return !['registration', 'counseling', 'session', 'referral'].includes(this.apType?.processingKind);
        },
        // Money or goods: the amount is required (owner, 2026-11-25; the
        // server's WelfareCatalog::AMOUNT_KINDS).
        apNeedsAmount() {
            return ['cash', 'goods', 'medical'].includes(this.apType?.processingKind);
        },
        /** Step by step, '' when the step is complete. */
        apProblem(step = this.apStep) {
            if (step === 1) return this.pProblem();
            if (step === 2) return this.apKey ? '' : 'Choose the Tulong type.';
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
                // No negative counts, ages or amounts (manual test 2026-11-06):
                // the inputs say min 0, which a typed '-5' still gets past.
                const negative = (v) => String(v ?? '').trim() !== '' && Number(v) < 0;
                // A projected net income may be a loss, as the server allows (QA 2026-11-06).
                const mayBeNegative = (key) => String(key || '').endsWith('net_income');
                for (const f of (this.apType?.fields || []).filter((x) => this.apShown(x))) {
                    if (f.type === 'number' && !mayBeNegative(f.key) && negative(this.apForm[f.key])) return f.label + ' must be 0 or more.';
                    if (f.type === 'table' && Array.isArray(this.apForm[f.key])) {
                        for (const col of (f.columns || []).filter((c) => c.type === 'number' && !mayBeNegative(c.key))) {
                            if (this.apForm[f.key].some((row) => negative(row?.[col.key]))) return f.label + ', ' + col.label + ' must be 0 or more.';
                        }
                    }
                }
                return '';
            }
            if (step === 4) {
                if (this.apChannel === 'referral' && !this.apReferredBy.trim()) return 'Say who referred the person.';
                if (this.apRecordOnly) {
                    if (!this.apRecord.completed_on) return 'Give the date the assistance was given.';
                    if (!this.apRecord.released_to.trim()) return 'Write who received it.';
                    if (this.apNeedsAmount() && !(Number(this.apRecord.amount) > 0)) return 'Enter the amount (₱) given, more than ₱0.';
                    if (this.apShowsAmount() && String(this.apRecord.amount ?? '').trim() !== '' && Number(this.apRecord.amount) < 0) return 'The amount must be 0 or more.';
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
                // No requirements_presented any more (round 2): the documents
                // are uploaded to the case once it is filed.
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
            if (this.apBusy || this.apFiled) return;
            for (const step of [1, 2, 3, 4]) {
                const problem = this.apProblem(step);
                if (problem) { this.apError = problem; this.apStep = step === 2 && this.apSessionRef ? 1 : step; return; }
            }
            this.apBusy = true;
            this.apError = '';
            const sessionRef = this.apSessionRef;
            let data;
            try {
                const url = sessionRef
                    ? '/admin/tulong/sessions/' + encodeURIComponent(sessionRef) + '/participants/walk-in'
                    : '/admin/tulong/requests';
                const res = await api.post(url, this.apPayload());
                data = res?.data || {};
            } catch (err) {
                this.apError = says(err, 'Could not file the request.');
                toast(this.apError, 'error');
                this.apBusy = false;
                return;
            }
            // Filed: from here on nothing files it again, whatever the uploads do.
            // A walk-in's answer is the session; its case is the one new in it.
            const fresh = sessionRef ? [...new Set((data.participants || []).map((p) => p.request_ref).filter((ref) => ref && !this.apKnownRefs.includes(ref)))] : [];
            const caseRef = sessionRef ? (fresh.length === 1 ? fresh[0] : null) : (data.ref || null);
            const filed = { ref: caseRef, data, sessionRef };
            this.apFiled = filed;
            const failed = await this.apUpload(filed);
            this.apBusy = false;
            // Closed while the files were going up: what happened is said in toasts.
            if (!this.apOpen) { this.apFinish(true); return; }
            if (failed.length) {
                this.apError = 'Not uploaded: ' + failed.join(', ') + '. ' + (failed.length === 1 ? 'It' : 'They') + ' can be attached from the case (Upload file).';
                return;
            }
            this.apFinish();
        },
        /**
         * Each chosen file, one after another, to the case just filed. Returns
         * the names of the documents that did not go up; the newest detail
         * the server answered with replaces the filed one.
         */
        async apUpload(filed) {
            const failed = [];
            const caseRef = filed.ref;
            const chosen = this.apShownReqs().filter((r) => r.file);
            const rows = filed.sessionRef ? null : (Array.isArray(filed.data?.requirements) ? filed.data.requirements : null);
            for (const r of chosen) {
                // The case's own row for it: by its key, else by its wording.
                const target = rows ? (rows.find((q) => q.key === r.key) || rows.find((q) => q.label === r.label)) : { key: r.key };
                r.error = '';
                if (!caseRef || !target) {
                    r.state = 'failed';
                    r.error = 'Not uploaded: the case could not be matched. Attach it from the case.';
                    failed.push(r.label);
                    continue;
                }
                r.state = 'uploading';
                r.progress = 0;
                try {
                    const back = await attachAtOffice('tulong', caseRef, target.key, r.file, (p) => { r.progress = p; });
                    r.state = 'done';
                    if (back && back.ref && !filed.sessionRef) filed.data = back;
                } catch (err) {
                    r.state = 'failed';
                    r.error = 'Not uploaded: ' + says(err, 'the upload did not go through') + ' Attach it from the case.';
                    failed.push(r.label);
                }
                icons();
            }
            return failed;
        },
        /**
         * Close and show what was filed: the case opens on the page, or the
         * session updates. warn: also name, in a toast, the documents that
         * did not go up (the window was closed before it could say so).
         */
        apFinish(warn = false) {
            const filed = this.apFiled;
            this.apFiled = null;
            if (!filed) { this.apOpen = false; return; }
            const done = this.apShownReqs().filter((r) => r.state === 'done').length;
            const failed = this.apShownReqs().filter((r) => r.state === 'failed').map((r) => r.label);
            const uploaded = done ? ' ' + done + ' document' + (done === 1 ? '' : 's') + ' uploaded.' : '';
            this.apOpen = false;
            if (filed.sessionRef) {
                toast(this.pName() + ' was added to the session.' + uploaded);
                window.dispatchEvent(new CustomEvent('welfare-session-changed', { detail: { ref: filed.sessionRef, session: filed.data.participants ? filed.data : null } }));
            } else {
                toast((filed.ref ? filed.ref + ' filed' : 'Filed') + ' for ' + (filed.data.applicant || this.pName()) + '.' + uploaded);
                // To the page by an event, never through this window's scope:
                // there the case window's `this.open = true` would land on
                // this window's own open setter and open it again.
                window.dispatchEvent(new CustomEvent('tulong-case-filed', { detail: filed.data }));
            }
            if (warn && failed.length) toast('Not uploaded: ' + failed.join(', ') + '. Attach ' + (failed.length === 1 ? 'it' : 'them') + ' from the case with Upload file.', 'warning');
        },
    };
}

// ---------------------------------------------------------------- Case notes

// How a session or visit was held (services-rendered `mode`).
const NOTE_MODES = [['office', 'At the office'], ['home_visit', 'Home visit'], ['field', 'In the field'], ['online', 'Online or by phone']];
const modeLabel = (m) => (NOTE_MODES.find((x) => x[0] === m) || [m, m || ''])[1];

/** A note with the per-row state its Void form needs (kept on the row). */
function noteRow(r) {
    return { ...r, person: r.person || {}, _voiding: false, _reason: '', _busy: false };
}

/** POST /admin/services-rendered/{ref}/void { reason }: the note comes back voided. */
async function voidNote(r) {
    const reason = String(r._reason || '').trim();
    if (reason.length < 5 || r._busy) return false;
    r._busy = true;
    try {
        const res = await api.post('/admin/services-rendered/' + encodeURIComponent(r.ref) + '/void', { reason });
        const back = res?.data || {};
        r.voided_at = back.voided_at || new Date().toISOString();
        r.void_reason = back.void_reason || reason;
        r._voiding = false;
        toast('The note is voided.', 'warning');
        return true;
    } catch (err) {
        toast(says(err, 'Could not void the note.'), 'error');
        return false;
    } finally {
        r._busy = false;
    }
}

/**
 * A Tulong case's "Case notes" (round 2, 2026-10-30; the welfare round's
 * "Service records"). On every Tulong type since 2026-11-06 (owner): a cash,
 * medical, goods or registration case gets them too, not only counseling,
 * referral and session cases; the server already takes a note on any type.
 * Staff only: the resident never sees them. The owner folded the
 * separate Services rendered view, the "Record a service" window and the
 * resident's list of them back into Tulong ("the services rendered just
 * means the tulong"): what is written now is a note on a case, still a
 * services-rendered row with the case's request_ref, which the server keeps.
 *   GET /admin/services-rendered?request_ref=&include_voided=1 - the notes;
 *   POST /admin/services-rendered { request_ref, rendered_on, mode, details }
 *     - "Add a note": the person and the Tulong type are the case's, and a
 *     confidential type's notes stay with its office (the server makes them so);
 *   POST /admin/services-rendered/{ref}/void { reason };
 *   POST /admin/tulong/requests/{ref}/complete-service { note } - counseling
 *     and referral close from Assessment once a note is on them. A session's
 *     case closes only when its session is finished.
 */
export function caseNotes() {
    return {
        cnRows: [],
        cnLoading: false,
        cnFailed: false,
        cnFor: null,
        cnAdding: false,
        cnDate: '',
        cnMode: 'office',
        cnDetails: '',
        cnSaving: false,
        cnAddError: '',
        cnClosing: false,
        cnNote: '',
        cnBusy: false,
        cnError: '',
        cnModes: NOTE_MODES,
        cnToday: manilaToday,
        day,
        peso,
        modeLabel,
        cnShown() {
            return !!this.selected;
        },
        /** The panel's one-line help, in the words of this case's kind of work. */
        cnHint() {
            if (this.processingKind === 'session') return 'The session is noted here for each participant when it is finished. Add a note for anything else done on the case.';
            if (['counseling', 'referral'].includes(this.processingKind)) return "Note each session held, home visit or referral as it happens: the notes are the case's work.";
            return 'Note anything done on the case as it happens: an interview, a home visit, a call, a referral.';
        },
        cnSync() {
            // The case window closed: its notes are read again when it next
            // opens, on the same case too. `selected` stays set behind the
            // closed window, so a colleague's note, or the one a finished
            // session added, was missing on reopening (sweep 2026-11-18).
            if (this.open === false) { this.cnFor = null; return; }
            const ref = this.selected?.ref || null;
            if (!this.cnShown() || !ref) return;
            if (ref === this.cnFor) return;
            this.cnFor = ref;
            this.cnRows = [];
            this.cnAdding = false;
            this.cnClosing = false;
            this.cnNote = '';
            this.cnError = '';
            this.cnLoad();
        },
        async cnLoad() {
            const ref = this.cnFor;
            if (!ref) return;
            this.cnLoading = true;
            this.cnFailed = false;
            try {
                const res = await api.get('/admin/services-rendered', { params: { request_ref: ref, include_voided: 1, per_page: 50 } });
                if (ref === this.cnFor) this.cnRows = (res?.data || []).map(noteRow);
            } catch {
                if (ref === this.cnFor) this.cnFailed = true;
            } finally {
                if (ref === this.cnFor) this.cnLoading = false;
                icons();
            }
        },
        cnLive() { return this.cnRows.filter((r) => !r.voided_at).length; },
        cnCanAdd() { return can('tulong_records', 'create') && !['Rejected', 'Cancelled'].includes(this.selected?.status); },
        cnCanVoid() { return can('tulong_records', 'edit'); },
        cnMayClose() {
            return ['counseling', 'referral'].includes(this.processingKind) && this.selected?.status === 'Assessment' && !this.detailLoading;
        },
        /** A confidential Tulong type: its notes are shown only to its own office. */
        cnSensitive() {
            const t = Object.values(this.tulongTypesByName || {}).find((x) => x && x.key === this.selected?.service_key);
            return !!t?.sensitive;
        },
        cnStartAdding() {
            this.cnDate = manilaToday();
            this.cnMode = 'office';
            this.cnDetails = '';
            this.cnAddError = '';
            this.cnAdding = true;
            this.$nextTick(() => document.getElementById('cn-details')?.focus());
            icons();
        },
        cnProblem() {
            if (!this.cnDate) return 'Give the date.';
            if (this.cnDate > manilaToday()) return 'The date cannot be in the future.';
            if (this.cnDetails.trim().length < 3) return 'Write what was done.';
            if (this.cnDetails.length > 2000) return 'The note is at most 2,000 characters.';
            return '';
        },
        async cnSave() {
            const ref = this.selected?.ref;
            if (this.cnSaving || !ref) return;
            const problem = this.cnProblem();
            this.cnAddError = problem;
            if (problem) return;
            this.cnSaving = true;
            try {
                await api.post('/admin/services-rendered', {
                    request_ref: ref,
                    rendered_on: this.cnDate,
                    mode: this.cnMode || null,
                    details: this.cnDetails.trim(),
                    confidential: this.cnSensitive(),
                });
                this.cnAdding = false;
                toast('Note added to ' + ref + '.');
                if (ref === this.cnFor) this.cnLoad();
            } catch (err) {
                this.cnAddError = says(err, 'Could not save the note.');
            } finally {
                this.cnSaving = false;
            }
        },
        cnVoid(r) { return voidNote(r); },
        async cnClose() {
            const r = this.selected;
            if (!r || this.cnBusy) return;
            if (this.cnNote.trim().length < 5) { this.cnError = 'Write a short closing summary (at least 5 characters).'; return; }
            this.cnBusy = true;
            this.cnError = '';
            try {
                const res = await api.post('/admin/tulong/requests/' + encodeURIComponent(r.ref) + '/complete-service', { note: this.cnNote.trim() });
                if (res?.data && typeof this.applyDetail === 'function') this.applyDetail(r, res.data);
                const row = (this.requests || []).find((x) => x.ref === r.ref);
                if (row && res?.data?.status) row.status = res.data.status;
                this.cnClosing = false;
                this.cnNote = '';
                toast(r.ref + ' is closed. Its notes stay with the office.');
                this.cnLoad();
            } catch (err) {
                this.cnError = says(err, 'Could not close ' + r.ref + '.');
            } finally {
                this.cnBusy = false;
                icons();
            }
        },
    };
}

// ------------------------------------------------- Sessions and certificates

const SESSION_TABS = [['Scheduled', 'Upcoming'], ['Done', 'Done'], ['Cancelled', 'Cancelled']];
const FACILITATOR_ROLES = [['team_leader', 'Team leader'], ['member', 'Member'], ['counselor', 'Counselor']];
const PARTICIPANT_ROLES = { applicant: 'Applicant', partner: 'Partner', participant: 'Participant' };

/** The catalog's session services (processingKind 'session'). */
function sessionServices(byName) {
    return Object.values(byName || {}).filter((t) => t && t.key && t.processingKind === 'session');
}

// 'desk' marks a change the session window made itself, so it does not reload for it.
const changed = (ref, session = null, source = null) => window.dispatchEvent(new CustomEvent('welfare-session-changed', { detail: { ref, session, source } }));

/**
 * The Sessions view: GET /admin/tulong/sessions?status=&service_key=, as
 * Upcoming / Done / Cancelled. A row opens the session window
 * ('tulong-open-session'); New session opens its form ('tulong-new-session').
 */
export function sessionList() {
    return {
        slTab: 'Scheduled',
        slService: '',
        slRows: [],
        slLoading: false,
        slFailed: false,
        slLoaded: false,
        slSeq: 0,
        slTabs: SESSION_TABS,
        day,
        time,
        slServices() { return sessionServices(this.tulongTypesByName); },
        slCanCreate() { return can('tulong_sessions', 'create'); },
        async slLoad() {
            const seq = ++this.slSeq;
            this.slLoading = true;
            this.slFailed = false;
            try {
                const params = { status: this.slTab };
                if (this.slService) params.service_key = this.slService;
                // Every page: the list is 25 a page, and a tab of more
                // showed only its first 25 (sweep 2026-11-18).
                const res = await api.getAll('/admin/tulong/sessions', { params });
                if (seq !== this.slSeq) return;
                const rows = Array.isArray(res?.data) ? res.data : [];
                // Upcoming soonest first; done and cancelled newest first.
                rows.sort((a, b) => (String(a.date) + String(a.start_time || '')).localeCompare(String(b.date) + String(b.start_time || '')) * (this.slTab === 'Scheduled' ? 1 : -1));
                this.slRows = rows;
                this.slLoaded = true;
            } catch {
                if (seq === this.slSeq) this.slFailed = true;
            } finally {
                if (seq === this.slSeq) this.slLoading = false;
                icons();
            }
        },
        slSetTab(t) {
            this.slTab = t;
            this.slLoad();
        },
        slOpen(s) { window.dispatchEvent(new CustomEvent('tulong-open-session', { detail: { ref: s.ref } })); },
        slNew() { window.dispatchEvent(new CustomEvent('tulong-new-session', { detail: {} })); },
        slCount(s) {
            const c = s.counts || {};
            const n = c.participants ?? (s.participants || []).length;
            return n + (s.capacity ? ' of ' + s.capacity : '') + ' participant' + (n === 1 ? '' : 's') + (s.status === 'Done' && c.certificates !== undefined ? ' · ' + c.certificates + ' certificate' + (c.certificates === 1 ? '' : 's') : '');
        },
    };
}

/**
 * The session window and its form (section D):
 *   GET /admin/tulong/sessions/{ref}; POST /admin/tulong/sessions and
 *   PUT .../{ref} (the form); POST .../cancel { reason };
 *   GET /admin/tulong/sessions/waiting?service_key= and
 *   POST .../participants { request_ref } (Add waiting cases);
 *   DELETE .../participants/{id}; POST .../attendance { participants: [{ id, attended }] };
 *   POST .../finish (perm tulong_sessions release).
 * A walk-in is filed through Apply for New Tulong ('tulong-apply' with the
 * session). A refusal (ACCREDITATION_REQUIRED, SESSION_FULL...) is shown in
 * the server's own words.
 */
export function sessionDesk() {
    return {
        sdOpen: false,
        ses: null,
        sdLoading: false,
        sdBusy: false,
        sdError: '',
        sdPanel: null,
        sdWaiting: [],
        sdWaitingLoading: false,
        sdWaitingFailed: false,
        sdCancelReason: '',
        sdResult: '',
        sfOpen: false,
        sfRef: null,
        sf: null,
        sfBusy: false,
        sfError: '',
        sfRoles: FACILITATOR_ROLES,
        day,
        time,
        roleLabel: (r) => PARTICIPANT_ROLES[r] || r || '',
        facRole: (r) => (FACILITATOR_ROLES.find((x) => x[0] === r) || [r, r || ''])[1],
        sdServices() { return sessionServices(this.tulongTypesByName); },
        sdType(key) { return this.sdServices().find((t) => t.key === key) || null; },
        sdCan(action) { return can('tulong_sessions', action); },
        sdScheduled() { return this.ses?.status === 'Scheduled'; },

        // ---- the window
        sdFrom(d) {
            const s = { ...d };
            s.facilitators = Array.isArray(d.facilitators) ? d.facilitators : [];
            s.participants = (d.participants || []).map((p) => ({ ...p, _attended: p.attended === true, _confirmRemove: false, _busy: false }));
            s.counts = d.counts || {};
            return s;
        },
        async sdShow(ref) {
            if (!ref) return;
            this.sdOpen = true;
            this.sdPanel = null;
            this.sdError = '';
            this.sdResult = '';
            if (this.ses?.ref !== ref) this.ses = null;
            this.sdLoading = true;
            try {
                const res = await api.get('/admin/tulong/sessions/' + encodeURIComponent(ref));
                this.ses = this.sdFrom(res?.data || {});
            } catch (err) {
                this.sdError = says(err, 'Could not open the session.');
            } finally {
                this.sdLoading = false;
                icons();
            }
        },
        /** After a change made elsewhere (a walk-in filed), or here. */
        sdTake(data) {
            if (data && data.ref) this.ses = this.sdFrom(data);
            if (this.ses?.ref) changed(this.ses.ref, null, 'desk');
            icons();
        },
        sdUrl(tail = '') { return '/admin/tulong/sessions/' + encodeURIComponent(this.ses.ref) + tail; },
        async sdRun(fn, okMessage) {
            if (this.sdBusy) return false;
            this.sdBusy = true;
            this.sdError = '';
            try {
                const res = await fn();
                if (res?.data?.ref) this.sdTake(res.data); else await this.sdShow(this.ses.ref).then(() => changed(this.ses?.ref, null, 'desk'));
                if (okMessage) toast(typeof okMessage === 'function' ? okMessage(res?.data || {}) : okMessage);
                return true;
            } catch (err) {
                this.sdError = says(err, 'The session could not be changed.');
                toast(this.sdError, 'error');
                return false;
            } finally {
                this.sdBusy = false;
                icons();
            }
        },

        // ---- participants
        sdTotals() {
            const list = this.ses?.participants || [];
            return { all: list.length, here: list.filter((p) => p._attended).length };
        },
        sdDirty() { return (this.ses?.participants || []).some((p) => p._attended !== (p.attended === true)); },
        /** The session's cases, each with whether everyone in it attended. */
        sdCases() {
            const by = {};
            (this.ses?.participants || []).forEach((p) => {
                const key = p.request_ref || 'p' + p.id;
                (by[key] ||= { ref: p.request_ref, names: [], complete: true }).names.push(p.name);
                if (!p._attended) by[key].complete = false;
            });
            return Object.values(by);
        },
        async sdLoadWaiting() {
            this.sdPanel = 'waiting';
            this.sdWaitingLoading = true;
            this.sdWaitingFailed = false;
            try {
                const res = await api.get('/admin/tulong/sessions/waiting', { params: { service_key: this.ses.service_key } });
                const inThis = new Set((this.ses.participants || []).map((p) => p.request_ref).filter(Boolean));
                this.sdWaiting = (res?.data || []).filter((c) => !inThis.has(c.ref)).map((c) => ({ ...c, _pick: false }));
            } catch {
                this.sdWaitingFailed = true;
            } finally {
                this.sdWaitingLoading = false;
                icons();
            }
        },
        sdPicked() { return this.sdWaiting.filter((c) => c._pick); },
        /** One at a time, in order; a refusal (the session is full) stops the rest. */
        async sdAddWaiting() {
            const picked = this.sdPicked();
            if (!picked.length || this.sdBusy) return;
            this.sdBusy = true;
            this.sdError = '';
            let added = 0;
            let last = null;
            try {
                for (const c of picked) {
                    const res = await api.post(this.sdUrl('/participants'), { request_ref: c.ref });
                    last = res?.data || last;
                    added += 1;
                }
            } catch (err) {
                this.sdError = says(err, 'A case could not be added.') + (added ? ' (' + added + ' added before this.)' : '');
            } finally {
                this.sdBusy = false;
            }
            if (added) {
                toast(added + ' case' + (added === 1 ? '' : 's') + ' added. The residents are told the date, time and venue.');
                if (last?.ref) this.sdTake(last); else await this.sdShow(this.ses.ref).then(() => changed(this.ses?.ref, null, 'desk'));
            }
            if (!this.sdError) this.sdPanel = null;
            icons();
        },
        sdWalkIn() {
            const type = this.sdType(this.ses.service_key);
            // knownRefs: the session's cases now, so Apply can tell which one it filed (its documents go there).
            const knownRefs = (this.ses.participants || []).map((p) => p.request_ref).filter(Boolean);
            window.dispatchEvent(new CustomEvent('tulong-apply', { detail: { sessionRef: this.ses.ref, serviceKey: this.ses.service_key, sessionTitle: this.ses.title || type?.name || '', knownRefs } }));
        },
        sdRemove(p) {
            return this.sdRun(() => api.delete(this.sdUrl('/participants/' + encodeURIComponent(p.id))), p.name + ' was taken off the session. The case waits for a session again.');
        },
        sdSaveAttendance() {
            const participants = (this.ses?.participants || []).map((p) => ({ id: p.id, attended: !!p._attended }));
            return this.sdRun(() => api.post(this.sdUrl('/attendance'), { participants }), 'Attendance saved.');
        },
        sdCancel() {
            const reason = this.sdCancelReason.trim();
            if (reason.length < 5) { this.sdError = 'Write why the session is cancelled (at least 5 characters).'; return false; }
            return this.sdRun(() => api.post(this.sdUrl('/cancel'), { reason }), 'The session is cancelled. Its cases wait for a new schedule.').then((ok) => { if (ok) this.sdPanel = null; return ok; });
        },
        /** The attendance on screen is saved first, then the session is finished. */
        async sdFinish() {
            if (this.sdBusy) return;
            if (this.sdDirty()) {
                const saved = await this.sdSaveAttendance();
                if (!saved) return;
            }
            const ok = await this.sdRun(() => api.post(this.sdUrl('/finish')), (d) => {
                const n = d.counts?.certificates;
                return 'The session is finished' + (n !== undefined ? ': ' + n + ' certificate' + (n === 1 ? '' : 's') + ' issued.' : '.');
            });
            if (ok) {
                this.sdPanel = null;
                const c = this.ses?.counts || {};
                this.sdResult = c.certificates !== undefined ? c.certificates + ' certificate' + (c.certificates === 1 ? '' : 's') + ' issued; ' + (c.attended ?? 0) + ' of ' + (c.participants ?? 0) + ' attended.' : '';
            }
        },
        sdOpenCase(p) {
            if (!p.request_ref) return;
            this.sdOpen = false;
            if (typeof this.openByRef === 'function') this.$nextTick(() => this.openByRef(p.request_ref));
        },
        /** A participant's certificate, drawn from its case's issued document. */
        async sdCert(p) {
            const meta = { service_key: this.ses.service_key, session: { date: this.ses.date, venue: this.ses.venue } };
            let doc = { control_number: p.certificate?.control_number, verify_url: p.certificate?.verify_url };
            if (p.request_ref) {
                try {
                    const res = await api.get('/admin/tulong/requests/' + encodeURIComponent(p.request_ref));
                    doc = (res?.data?.issued_documents || []).find((d) => d.control_number === p.certificate?.control_number) || doc;
                } catch {
                    // The minimal copy still shows the number and the QR.
                }
            }
            openWelfareCertificate(doc, meta);
        },

        // ---- the form
        sfBlank() {
            return { service_key: '', title: '', date: '', start_time: '09:00', end_time: '', venue: '', capacity: '', notes: '', facilitators: [{ name: '', position: '', role: 'team_leader', accreditation_no: '', accreditation_valid_until: '' }] };
        },
        sfNew() {
            this.sfRef = null;
            this.sf = this.sfBlank();
            const only = this.sdServices();
            if (only.length === 1) this.sfService(only[0].key);
            this.sfError = '';
            this.sfOpen = true;
            icons();
        },
        sfEdit() {
            const s = this.ses;
            if (!s) return;
            this.sfRef = s.ref;
            this.sf = {
                service_key: s.service_key || '', title: s.title || '', date: s.date || '', start_time: (s.start_time || '').slice(0, 5), end_time: (s.end_time || '').slice(0, 5),
                venue: s.venue || '', capacity: s.capacity ?? '', notes: s.notes || '',
                facilitators: (s.facilitators || []).map((f) => ({ name: f.name || '', position: f.position || '', role: f.role || 'member', accreditation_no: f.accreditation_no || '', accreditation_valid_until: f.accreditation_valid_until || '' })),
            };
            if (!this.sf.facilitators.length) this.sf.facilitators = this.sfBlank().facilitators;
            this.sfError = '';
            this.sfOpen = true;
            icons();
        },
        sfService(key) {
            this.sf.service_key = key;
            const t = this.sdType(key);
            if (t && !this.sf.title.trim()) this.sf.title = t.name;
            if (t?.certificate?.signedBy === 'counselor' && !this.sf.facilitators.some((f) => f.role === 'counselor')) this.sf.facilitators[0].role = 'counselor';
        },
        sfAdd() { this.sf.facilitators.push({ name: '', position: '', role: 'member', accreditation_no: '', accreditation_valid_until: '' }); },
        sfDrop(i) { this.sf.facilitators.splice(i, 1); },
        /** A counselor accredited on the session day, as the certificate needs (DSWD MC 01 s. 2019). */
        sfAccredited() {
            return this.sf.facilitators.some((f) => f.role === 'counselor' && f.accreditation_no.trim() && f.accreditation_valid_until && (!this.sf.date || f.accreditation_valid_until >= this.sf.date));
        },
        sfNeedsAccreditation() { return !!this.sdType(this.sf?.service_key)?.certificate?.requiresAccreditation; },
        sfProblem() {
            const f = this.sf;
            if (!f.service_key) return 'Choose the Tulong type.';
            if (f.title.trim().length < 3) return 'Give the session a title.';
            if (!f.date) return 'Give the date.';
            if (!f.start_time) return 'Give the start time.';
            if (f.end_time && f.end_time <= f.start_time) return 'The end time must be after the start time.';
            if (f.venue.trim().length < 3) return 'Give the venue.';
            if (f.capacity !== '' && !(Number(f.capacity) >= 1)) return 'The capacity is a number of 1 or more, or empty for no limit.';
            const named = f.facilitators.filter((x) => x.name.trim());
            if (!named.length) return 'Name at least one facilitator.';
            if (named.some((x) => !x.position.trim())) return 'Give each facilitator\'s position (it is printed under their name).';
            if (named.some((x) => x.role === 'counselor' && x.accreditation_no.trim() && !x.accreditation_valid_until)) return 'Give the date the counselor\'s accreditation is valid until.';
            return '';
        },
        async sfSave() {
            if (this.sfBusy) return;
            const problem = this.sfProblem();
            this.sfError = problem;
            if (problem) return;
            const f = this.sf;
            const body = {
                service_key: f.service_key, title: f.title.trim(), date: f.date, start_time: f.start_time, end_time: f.end_time || null,
                venue: f.venue.trim(), capacity: f.capacity === '' ? null : Number(f.capacity), notes: f.notes.trim() || null,
                facilitators: f.facilitators.filter((x) => x.name.trim()).map((x) => ({
                    name: x.name.trim(), position: x.position.trim(), role: x.role,
                    accreditation_no: x.role === 'counselor' ? (x.accreditation_no.trim() || null) : null,
                    accreditation_valid_until: x.role === 'counselor' ? (x.accreditation_valid_until || null) : null,
                })),
            };
            this.sfBusy = true;
            try {
                const res = this.sfRef
                    ? await api.put('/admin/tulong/sessions/' + encodeURIComponent(this.sfRef), body)
                    : await api.post('/admin/tulong/sessions', body);
                const data = res?.data || {};
                this.sfOpen = false;
                toast(this.sfRef ? 'The session is updated. Its participants are told of a new date, time or venue.' : 'The session is scheduled.');
                if (data.ref) {
                    this.ses = this.sdFrom(data);
                    this.sdOpen = true;
                    changed(data.ref, null, 'desk');
                }
            } catch (err) {
                this.sfError = says(err, 'Could not save the session.');
            } finally {
                this.sfBusy = false;
                icons();
            }
        },
    };
}

/**
 * A session case's Session panel: its session (from the case detail's
 * session { ref, date, start_time, venue, status }) or "Waiting for a
 * session" with Add to a session (the service's scheduled sessions, then
 * POST /admin/tulong/sessions/{ref}/participants { request_ref }); for
 * pre-marriage counseling whether Family Code art. 16 requires it; and the
 * certificate once issued.
 */
export function caseSession() {
    return {
        csPicking: false,
        csOptions: [],
        csLoading: false,
        csBusy: false,
        csError: '',
        day,
        time,
        csShown() { return !!this.selected && this.processingKind === 'session'; },
        csCanAdd() {
            return can('tulong_sessions', 'edit') && !this.selected?.session && !['Completed', 'Rejected', 'Cancelled'].includes(this.selected?.status);
        },
        async csPick() {
            this.csPicking = true;
            this.csLoading = true;
            this.csError = '';
            try {
                // Every scheduled session of the type, not the first page of 25.
                const res = await api.getAll('/admin/tulong/sessions', { params: { status: 'Scheduled', service_key: this.selected.service_key } });
                this.csOptions = (res?.data || []).slice().sort((a, b) => String(a.date).localeCompare(String(b.date)));
            } catch (err) {
                this.csError = says(err, 'Could not load the sessions.');
            } finally {
                this.csLoading = false;
                icons();
            }
        },
        async csAdd(s) {
            if (this.csBusy) return;
            this.csBusy = true;
            this.csError = '';
            try {
                await api.post('/admin/tulong/sessions/' + encodeURIComponent(s.ref) + '/participants', { request_ref: this.selected.ref });
                this.csPicking = false;
                toast(this.selected.ref + ' is in the session of ' + day(s.date) + '. The resident is told the date, time and venue.');
                if (typeof this.refreshSelected === 'function') await this.refreshSelected();
                changed(s.ref);
            } catch (err) {
                this.csError = says(err, 'Could not add the case to the session.');
            } finally {
                this.csBusy = false;
                icons();
            }
        },
        csOpen() {
            const ref = this.selected?.session?.ref;
            if (ref) window.dispatchEvent(new CustomEvent('tulong-open-session', { detail: { ref } }));
        },
        /** Family Code art. 16: counseling is required when either partner is 18 to 25. */
        csArt16() {
            if (this.selected?.service_key !== 'tulong_pmc') return null;
            const f = this.selected.form_data || {};
            const onDay = this.selected.session?.date || manilaToday();
            const ages = [ageOn(this.selected.applicant_birth_date || f.applicant_birth_date || f.birth_date || f.dateOfBirth, onDay), ageOn(f.partner_birth_date, onDay)];
            if (ages.some((a) => a !== null && a >= 18 && a <= 25)) return { required: true, text: 'Required (Art. 16): a partner is 18 to 25 years old on the session day.' };
            if (ages.every((a) => a !== null)) return { required: false, text: 'Optional: both partners are over 25.' };
            return { required: null, text: 'Required (Art. 16) when either partner is 18 to 25. Check the ages.' };
        },
        csCerts() {
            const t = Object.values(this.tulongTypesByName || {}).find((x) => x && x.key === this.selected?.service_key);
            return t?.certificate ? (this.selected.issued_documents || []) : [];
        },
        csCert(d) {
            openWelfareCertificate(d, { service_key: this.selected.service_key, session: this.selected.session || null });
        },
    };
}

export function register(Alpine) {
    registerCertificate(Alpine);
    Alpine.data('tulongApply', tulongApply);
    Alpine.data('caseNotes', caseNotes);
    Alpine.data('sessionList', sessionList);
    Alpine.data('sessionDesk', sessionDesk);
    Alpine.data('caseSession', caseSession);
}

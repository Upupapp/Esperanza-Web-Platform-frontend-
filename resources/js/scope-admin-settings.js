// Scope round 2026-10-15 - Settings and User Management: help content,
// document templates, system status, audit log, departments. (The service
// catalog moved to Dokyu's and Tulong's own pages in round 2, 2026-10-30:
// resources/js/catalog-editor.js.)
//
// Loaded only on the pages that use it (see the area loader at the end of
// app.js), so the shared bundle stays within its budget. register() runs
// before Alpine starts.
//
// Every tab here loads the first time it is shown, and only after the
// session can say whether the role may read it: a 403 pops a red toast, and
// these tabs used to load at page open whether shown or not.

import api from './api/client';

// ------------------------------------------------------------------ helpers

const TZ = 'Asia/Manila';
let uidSeq = 0;

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

/**
 * A timestamp from the API. ISO strings carry their zone; a bare
 * "Y-m-d H:i:s" (read straight from a table, as template versions are) is
 * UTC, the server's own zone.
 */
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

/** "Oct 15, 2026" for a calendar date ("2026-10-15") or a timestamp. */
function fmtDay(value) {
    if (!value) return '';
    const m = String(value).match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) return new Date(Date.UTC(+m[1], +m[2] - 1, +m[3], 12)).toLocaleDateString('en-US', { timeZone: 'UTC', month: 'short', day: 'numeric', year: 'numeric' });
    const d = toDate(value);
    return d ? d.toLocaleDateString('en-US', { timeZone: TZ, month: 'short', day: 'numeric', year: 'numeric' }) : '';
}

/** "just now", "4 minutes ago", "3 hours ago", "2 days ago". */
function fmtAgo(value) {
    const d = toDate(value);
    if (!d) return '';
    const s = Math.round((Date.now() - d.getTime()) / 1000);
    if (s < 45) return 'just now';
    const [n, unit] = s < 3600 ? [Math.round(s / 60), 'minute'] : s < 86400 ? [Math.round(s / 3600), 'hour'] : [Math.round(s / 86400), 'day'];
    return n + ' ' + unit + (n === 1 ? '' : 's') + ' ago';
}

/** Today in Esperanza, Y-m-d. */
function today() {
    return new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
}

const num = (n) => Number(n || 0).toLocaleString('en-US');
const plural = (n, one, many) => num(n) + ' ' + (Number(n) === 1 ? one : many);

/** 1536 -> "1.5 KB". */
function fmtBytes(n) {
    const v = Number(n);
    if (!Number.isFinite(v) || v < 0) return '';
    const units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    let x = v;
    while (x >= 1024 && i < units.length - 1) { x /= 1024; i += 1; }
    return (i === 0 ? String(Math.round(x)) : x.toFixed(x >= 100 ? 0 : 1)) + ' ' + units[i];
}

const FIELD_LABELS = {
    name: 'Name', office: 'Office', office_short: 'Office short name', category: 'Category', active: 'Active / offered', short: 'Short name',
    cert_title: 'Title', cert_subtitle: 'Subtitle', note: 'Note', validity_months: 'Valid for (months)', version: 'Version',
    fee: 'Fee', days: 'Processing time', requirements: 'Required documents', process: 'Processing steps',
    effective_date: 'Effective date', role_key: 'Role', dept: 'Department', scope: 'Scope', email: 'Email', status: 'Status',
};
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

/** "office.created" -> "Office created". */
const actionLabel = (a) => (a ? String(a).replace(/[._]+/g, ' ').trim().replace(/^./, (c) => c.toUpperCase()) : '');

// ------------------------------------------------------ FAQs and tutorials

const HELP_DOCS = {
    faqs: {
        fields: ['q', 'a', 'category', 'q_fil', 'a_fil'],
        required: ['q', 'a', 'category'],
        max: { q: 300, a: 4000, category: 60, q_fil: 300, a_fil: 4000 },
        limit: 200,
        title: 'Frequently Asked Questions',
        noun: ['question', 'questions'],
    },
    tutorials: {
        fields: ['title', 'category', 'duration', 'icon', 'url', 'description'],
        required: ['title'],
        max: { title: 200, category: 60, duration: 10, icon: 40, url: 500, description: 1000 },
        limit: 100,
        title: 'Video Tutorials',
        noun: ['tutorial', 'tutorials'],
    },
};

const TUTORIAL_ICONS = [
    ['circle-play', 'Play button'], ['file-text', 'Document'], ['user-round-check', 'Verified person'], ['hand-heart', 'Assistance'],
    ['search', 'Search'], ['credit-card', 'Payment'], ['bell', 'Notification'], ['calendar-clock', 'Appointment'],
    ['life-buoy', 'Help'], ['map-pin', 'Place'], ['shield-check', 'Security'], ['smartphone', 'Phone'],
];

/**
 * The FAQs and video tutorials residents read on Help (site content docs
 * `faqs` and `tutorials`), edited as a list and published as a new version
 * the way privacy, terms and support are. Each item shows how residents rated
 * it; a rating is kept against the wording it was given for.
 */
function siteHelpEditor(docKey) {
    const spec = HELP_DOCS[docKey] || HELP_DOCS.faqs;
    const isFaq = docKey === 'faqs';
    return {
        docKey, isFaq,
        started: false, loading: false, loaded: false, failed: false, denied: false, saving: false,
        title: '', intro: '', version: null, effectiveDate: '', updatedBy: '',
        items: [], base: '', helpfulness: {},
        expanded: null, search: '', category: '',
        errors: {}, error: '',
        icons: TUTORIAL_ICONS,

        get canEdit() { return may('set_site_content', 'edit'); },
        async start() {
            if (this.started) return;
            this.started = true;
            await sessionReady();
            if (!may('set_site_content', 'view')) { this.denied = true; return; }
            await this.load();
        },
        async load() {
            this.loading = true; this.failed = false;
            try {
                const res = await api.get('/admin/settings/site-content/' + docKey);
                this.apply(res.data || {});
            } catch (err) {
                // Never published: start an empty list rather than an error.
                if (err?.status === 404) this.apply({});
                else this.failed = true;
            } finally { this.loading = false; }
        },
        apply(d) {
            this.title = d.title || spec.title;
            this.intro = d.intro || '';
            this.version = d.version ?? null;
            this.effectiveDate = d.effective_date || '';
            this.updatedBy = d.last_updated_by || '';
            this.helpfulness = d.helpfulness && typeof d.helpfulness === 'object' ? d.helpfulness : {};
            this.items = (Array.isArray(d.sections) ? d.sections : []).map((s) => this.blank(s, true));
            this.base = this.snapshot();
            this.errors = {}; this.error = ''; this.expanded = null;
            this.loaded = true;
        },
        blank(source = {}, saved = false) {
            const item = { uid: ++uidSeq };
            spec.fields.forEach((f) => { item[f] = source[f] == null ? '' : String(source[f]); });
            // The wording residents rated, so editing it does not hide the count.
            item.rated = saved ? (isFaq ? item.q : item.title) : '';
            return item;
        },
        snapshot() {
            return JSON.stringify({ t: this.title, i: this.items.map((it) => spec.fields.map((f) => it[f])) });
        },
        get dirty() { return this.loaded && this.snapshot() !== this.base; },
        get versionLine() {
            if (!this.version) return 'Not published yet';
            return 'Version ' + this.version + (this.updatedBy ? ' · by ' + this.updatedBy : '') + (this.effectiveDate ? ' · in force since ' + fmtDay(this.effectiveDate) : '');
        },
        get categories() {
            return [...new Set(this.items.map((it) => (it.category || '').trim()).filter(Boolean))].sort();
        },
        get filtering() { return this.search.trim() !== '' || this.category !== ''; },
        get shown() {
            const q = this.search.trim().toLowerCase();
            return this.items.filter((it) => (!this.category || (it.category || '').trim() === this.category)
                && (!q || spec.fields.some((f) => String(it[f] || '').toLowerCase().includes(q))));
        },
        get countLine() {
            const n = this.items.length;
            const shown = this.shown.length;
            return (shown === n ? plural(n, spec.noun[0], spec.noun[1]) : num(shown) + ' of ' + plural(n, spec.noun[0], spec.noun[1]));
        },
        indexOf(item) { return this.items.findIndex((it) => it.uid === item.uid); },
        toggle(item) { this.expanded = this.expanded === item.uid ? null : item.uid; },
        add() {
            if (this.items.length >= spec.limit) { this.error = 'At most ' + spec.limit + ' ' + spec.noun[1] + '.'; return; }
            const item = this.blank(isFaq ? { category: this.category } : { icon: 'circle-play', category: this.category });
            this.items.push(item);
            this.search = '';
            this.expanded = item.uid;
            // x-show reveals the new fields on a timer, so focus after it.
            this.$nextTick(() => setTimeout(() => document.getElementById(docKey + '-item-' + item.uid)?.querySelector('input, textarea')?.focus(), 0));
        },
        remove(item) {
            const i = this.indexOf(item);
            if (i >= 0) this.items.splice(i, 1);
            if (this.expanded === item.uid) this.expanded = null;
            delete this.errors[item.uid];
        },
        move(item, dir) {
            const i = this.indexOf(item);
            const j = i + dir;
            if (i < 0 || j < 0 || j >= this.items.length) return;
            const a = this.items;
            const tmp = a[i];
            a[i] = a[j];
            a[j] = tmp;
        },
        /**
         * The server keeps a rating under the first 160 characters of the
         * trimmed question (FAQs) or title (tutorials), counted as characters.
         */
        rating(item) {
            const key = Array.from(String(item.rated || '').trim()).slice(0, 160).join('');
            const r = key ? this.helpfulness[key] : null;
            const yes = Number(r?.helpful) || 0;
            const no = Number(r?.not_helpful) || 0;
            return { yes, no, any: yes + no > 0 };
        },
        get ratingSummary() {
            let yes = 0;
            let no = 0;
            this.items.forEach((it) => { const r = this.rating(it); yes += r.yes; no += r.no; });
            if (!yes && !no) return 'Residents have not rated these yet.';
            return 'Residents’ ratings of the ' + spec.noun[1] + ' listed: ' + num(yes) + ' helpful · ' + num(no) + ' not helpful. A rating stays with the wording it was given for.';
        },
        iconChoices(item) {
            const list = [...TUTORIAL_ICONS];
            if (item.icon && !list.some(([v]) => v === item.icon)) list.unshift([item.icon, item.icon]);
            return list;
        },
        err(item, field) { return this.errors[item.uid]?.[field] || ''; },
        hasErrors(item) { return !!this.errors[item.uid]; },
        /** A field's message goes once it is edited; the summary once none is left. */
        clearErr(item, field) {
            const e = this.errors[item.uid];
            if (!e || !e[field]) return;
            const rest = { ...e };
            delete rest[field];
            const next = { ...this.errors };
            if (Object.keys(rest).length) next[item.uid] = rest;
            else delete next[item.uid];
            this.errors = next;
            if (!Object.keys(next).length) this.error = '';
        },
        validate() {
            const errors = {};
            this.items.forEach((it) => {
                const e = {};
                spec.required.forEach((f) => { if (!String(it[f] || '').trim()) e[f] = 'Required.'; });
                Object.entries(spec.max).forEach(([f, n]) => { if (String(it[f] || '').trim().length > n) e[f] = 'At most ' + n + ' characters.'; });
                if (!isFaq && String(it.url || '').trim()) {
                    let ok = false;
                    try { ok = new URL(it.url.trim()).protocol === 'https:'; } catch { ok = false; }
                    if (!ok) e.url = 'A full address that starts with https://';
                }
                if (Object.keys(e).length) errors[it.uid] = e;
            });
            this.errors = errors;
            return Object.keys(errors).length === 0;
        },
        async publish() {
            if (this.saving || !this.canEdit) return;
            this.error = '';
            if (!this.title.trim()) { this.error = 'Give the page a title.'; return; }
            if (this.title.trim().length > 255) { this.error = 'The title is at most 255 characters.'; return; }
            if (!this.items.length) { this.error = 'Keep at least one ' + spec.noun[0] + '.'; return; }
            if (this.items.length > spec.limit) { this.error = 'At most ' + spec.limit + ' ' + spec.noun[1] + '.'; return; }
            if (!this.validate()) {
                this.search = ''; this.category = '';
                this.expanded = Number(Object.keys(this.errors)[0]);
                this.error = 'Some ' + spec.noun[1] + ' need fixing: they are marked below.';
                return;
            }
            const sections = this.items.map((it) => {
                const out = {};
                spec.fields.forEach((f) => {
                    const v = String(it[f] || '').trim();
                    if (v !== '') out[f] = v;
                });
                return out;
            });
            const items = this.items;
            this.saving = true;
            try {
                // In force today, so residents see it at once.
                const res = await api.post('/admin/settings/site-content/' + docKey, {
                    title: this.title.trim(), effective_date: today(), intro: this.intro || null, sections,
                });
                toast((isFaq ? 'FAQs' : 'Tutorials') + ' published as version ' + (res.data?.version ?? '') + '. Residents see them now.');
                await this.load();
            } catch (err) {
                // "sections.3.url" -> the fourth item's link.
                const errors = {};
                Object.entries(err?.fields || {}).forEach(([path, messages]) => {
                    const m = path.match(/^sections\.(\d+)\.(\w+)$/);
                    const it = m ? items[Number(m[1])] : null;
                    if (it) errors[it.uid] = { ...(errors[it.uid] || {}), [m[2]]: Array.isArray(messages) ? messages[0] : String(messages) };
                });
                if (Object.keys(errors).length) {
                    this.errors = errors;
                    this.expanded = Number(Object.keys(errors)[0]);
                }
                this.error = errExplain(err, 'Could not publish.');
            } finally { this.saving = false; }
        },
    };
}

// ------------------------------------------------------- document templates

/**
 * Each document type's printed wording - title, subtitle, a note, and how
 * many months it stays valid - published as a new version, with who
 * published each (TemplatesController; set_service_catalog view / edit).
 */
function templatesTab() {
    return {
        started: false, loading: false, loaded: false, failed: false, denied: false,
        rows: [], search: '', type: '',
        open: false, sel: null,
        form: { cert_title: '', cert_subtitle: '', note: '', validity_months: '' },
        base: '', history: [], historyLoading: false, historyFailed: false,
        saving: false, error: '', fieldErrors: {},

        when: fmtWhen,
        get canEdit() { return may('set_service_catalog', 'edit'); },
        init() {
            this.$watch('form', () => { this.error = ''; this.fieldErrors = {}; });
        },
        async start() {
            if (this.started) return;
            this.started = true;
            await sessionReady();
            if (!may('set_service_catalog', 'view')) { this.denied = true; return; }
            await this.load();
        },
        async load() {
            this.loading = true; this.failed = false;
            try {
                const res = await api.get('/admin/settings/templates');
                this.rows = res.data || [];
                this.loaded = true;
            } catch { this.failed = true; } finally { this.loading = false; }
        },
        typeLabel(t) { return t === 'barangay' ? 'Barangay certificate' : (t === 'municipal' ? 'Municipal document' : (t || 'Document')); },
        get types() { return [...new Set(this.rows.map((r) => r.preview_type).filter(Boolean))].sort(); },
        get shown() {
            const q = this.search.trim().toLowerCase();
            return this.rows.filter((r) => (!this.type || r.preview_type === this.type)
                && (!q || [r.doc_key, r.service, r.cert_title, r.cert_subtitle, r.note].some((v) => String(v || '').toLowerCase().includes(q))));
        },
        validityText(months) {
            const n = Number(months);
            return n > 0 ? plural(n, 'month', 'months') : '6 months (standard)';
        },
        async edit(row) {
            this.sel = row;
            this.form = {
                cert_title: row.cert_title || '',
                cert_subtitle: row.cert_subtitle || '',
                note: row.note || '',
                validity_months: row.validity_months == null ? '' : String(row.validity_months),
            };
            this.base = JSON.stringify(this.form);
            this.error = ''; this.fieldErrors = {};
            this.history = [];
            this.open = true;
            await this.loadHistory();
        },
        async loadHistory() {
            const docKey = this.sel?.doc_key;
            if (!docKey) return;
            this.historyLoading = true; this.historyFailed = false;
            try {
                const res = await api.get('/admin/settings/templates/' + encodeURIComponent(docKey));
                if (this.sel?.doc_key === docKey) this.history = res.data?.versions || [];
            } catch {
                if (this.sel?.doc_key === docKey) this.historyFailed = true;
            } finally { this.historyLoading = false; }
        },
        get dirty() { return JSON.stringify(this.form) !== this.base; },
        get preview() {
            const n = Number(this.form.validity_months);
            return {
                title: this.form.cert_title.trim() || 'Document title',
                subtitle: this.form.cert_subtitle.trim(),
                note: this.form.note.trim(),
                validity: 'Valid for ' + (n > 0 ? plural(n, 'month', 'months') : '6 months') + ' from the date of issue.',
            };
        },
        problems() {
            const e = {};
            const t = this.form.cert_title.trim();
            if (t.length < 3) e.cert_title = 'The title needs at least 3 characters.';
            else if (t.length > 160) e.cert_title = 'The title is at most 160 characters.';
            if (this.form.cert_subtitle.trim().length > 200) e.cert_subtitle = 'The subtitle is at most 200 characters.';
            if (this.form.note.trim().length > 600) e.note = 'The note is at most 600 characters.';
            const v = String(this.form.validity_months ?? '').trim();
            if (v !== '' && !(/^\d+$/.test(v) && Number(v) >= 1 && Number(v) <= 60)) e.validity_months = 'A whole number of months from 1 to 60, or empty for the standard 6.';
            return e;
        },
        async save() {
            if (this.saving || !this.sel || !this.canEdit || !this.dirty) return;
            const problems = this.problems();
            this.fieldErrors = problems;
            if (Object.keys(problems).length) { this.error = Object.values(problems)[0]; return; }
            const docKey = this.sel.doc_key;
            const v = String(this.form.validity_months ?? '').trim();
            const body = {
                cert_title: this.form.cert_title.trim(),
                cert_subtitle: this.form.cert_subtitle.trim() || null,
                note: this.form.note.trim() || null,
                validity_months: v === '' ? null : Number(v),
            };
            this.saving = true; this.error = '';
            try {
                const res = await api.put('/admin/settings/templates/' + encodeURIComponent(docKey), body);
                const d = res.data || {};
                const i = this.rows.findIndex((r) => r.doc_key === docKey);
                if (i >= 0) {
                    this.rows[i] = {
                        ...this.rows[i], cert_title: d.cert_title, cert_subtitle: d.cert_subtitle, note: d.note,
                        validity_months: d.validity_months, version: d.version, versions: (d.versions || []).length || this.rows[i].versions,
                    };
                }
                if (this.sel?.doc_key === docKey) {
                    this.sel = i >= 0 ? this.rows[i] : this.sel;
                    this.history = d.versions || this.history;
                    this.form = {
                        cert_title: d.cert_title || '', cert_subtitle: d.cert_subtitle || '', note: d.note || '',
                        validity_months: d.validity_months == null ? '' : String(d.validity_months),
                    };
                    this.base = JSON.stringify(this.form);
                }
                toast('Version ' + (d.version ?? '') + ' published. The portals print the new wording on documents from now on.');
            } catch (err) {
                const fields = err?.fields || {};
                this.fieldErrors = Object.fromEntries(Object.entries(fields).map(([k, m]) => [k, Array.isArray(m) ? m[0] : String(m)]));
                this.error = errExplain(err, 'Could not save the template.');
            } finally { this.saving = false; }
        },
    };
}

// ------------------------------------------------------------ system status

const CHECK_LABELS = { database: 'Database', migrations: 'Database migrations', storage: 'File storage', cache: 'Cache', contract: 'Service contract' };
const CHANNEL_LABELS = { email: 'Email', sms: 'SMS', in_app: 'In the bell', push: 'Phone push' };
const DELIVERY_LABELS = {
    Released: ['Sent', 'emerald'], 'Pending Review': ['Waiting or retrying', 'amber'], Cancelled: ['Not sent (no address or no gateway)', 'slate'], Rejected: ['Failed', 'rose'],
};

// The System and Integrations tabs read the same status: one request serves
// both for half a minute; Refresh asks again.
let statusCache = null;
function fetchStatus(fresh = false) {
    if (!fresh && statusCache && Date.now() - statusCache.at < 30000) return statusCache.promise;
    const promise = api.get('/admin/system/status').then((res) => res.data || {});
    statusCache = { at: Date.now(), promise };
    promise.catch(() => { if (statusCache?.promise === promise) statusCache = null; });
    return promise;
}

/**
 * The integrations as the server is configured (never a secret), for the
 * System and Integrations tabs. Online payments are listed as not connected:
 * the backend has no payment gateway (fees are paid at the cashier, who
 * issues the official receipt).
 */
function integrationCards(data) {
    // Nothing is said before the server has answered.
    if (!data) return [];
    const i = data.integrations || {};
    const mail = i.mail || {};
    const sms = i.sms || {};
    const id = i.identity_checks || {};
    const scan = i.upload_scanning || {};
    const scanning = !!scan.mode && scan.mode !== 'stub';
    const identityOk = id.driver !== 'simulate' && !!id.key_set;
    return [
        {
            key: 'mail', name: 'Email', ok: !!mail.sends, tone: mail.sends ? 'emerald' : 'amber',
            state: mail.sends ? 'Configured' : 'Not sending',
            lines: [
                'Driver: ' + (mail.driver || 'none'),
                mail.from ? 'Sends as ' + mail.from : '',
                mail.sends ? '' : 'Emails are written to the server log, not delivered.',
            ],
        },
        {
            // No SMS gateway exists: the server says sends=false and explains in
            // its note, also when ESPERANZA_SMS_DRIVER is set (then a warning).
            key: 'sms', name: 'SMS', ok: !!sms.sends, tone: sms.sends ? 'emerald' : (sms.driver ? 'amber' : 'slate'),
            state: sms.sends ? 'Connected' : 'Not connected',
            lines: [sms.note || (sms.sends && sms.driver ? 'Gateway: ' + sms.driver : '')],
        },
        {
            key: 'identity', name: 'Didit identity checks', ok: identityOk, tone: identityOk ? 'emerald' : 'amber',
            state: id.driver === 'simulate' ? 'Simulated (no real checks)' : (id.key_set ? 'Configured' : 'No API key'),
            lines: [
                'Driver: ' + (id.driver || 'none') + (id.environment ? ' · ' + id.environment : ''),
                id.environment === 'sandbox' ? 'A sandbox key approves without checking, so it never verifies an identity.' : '',
            ],
        },
        {
            key: 'scan', name: 'Upload scanning', ok: scanning, tone: scanning ? 'emerald' : 'amber',
            state: scanning ? 'Scanning uploads' : 'Not scanning',
            lines: ['Mode: ' + (scan.mode || 'none'), scan.note || '', scan.stub_allowed ? 'The stub scanner is allowed on this server.' : ''],
        },
        {
            key: 'payments', name: 'Online payments (GCash and others)', ok: false, tone: 'slate',
            state: 'Not connected',
            lines: ['No payment gateway is built in: fees are paid at the cashier, and staff issue the official receipt.'],
        },
    ].map((c) => ({ ...c, lines: c.lines.filter(Boolean) }));
}

/**
 * Settings > Integrations: the real integrations for technical
 * administrators (set_system view), and for anyone else only where to find
 * them. It was a static list that called GCash and an SMS gateway
 * "Approved" (docs review, scope round 2026-10-15).
 */
function integrationsTab() {
    return {
        started: false, loading: false, loaded: false, failed: false, allowed: false,
        data: null,
        async start() {
            if (this.started) return;
            this.started = true;
            await sessionReady();
            this.allowed = may('set_system', 'view');
            if (this.allowed) await this.load();
        },
        async load() {
            if (this.loading) return;
            this.loading = true; this.failed = false;
            try {
                this.data = await fetchStatus();
                this.loaded = true;
            } catch { this.failed = true; } finally { this.loading = false; }
        },
        get cards() { return integrationCards(this.data); },
        get webUrl() { return this.data?.integrations?.web_url || ''; },
    };
}

/**
 * The Facebook Page card on Integrations (welfare round 2026-10-29): Balita
 * posts copied to the LGU's Page when published. Seen and changed by anyone
 * who can post in Balita (comm_news create; owner, 2026-10-29: "should apply
 * to anyone who can post") and by technical administrators (set_system view;
 * changed with set_system edit).
 * GET /admin/integrations/facebook sends the connection without its token:
 * {connected, page_id, page_name, page_link, connected_by_name, connected_at,
 * auto_post, default_share, status: ok|token_problem, last_error,
 * last_checked_at}. The token typed here goes up once (PUT) and is dropped
 * from the page as soon as the server answers.
 */
function facebookConnection(d) {
    const data = d || {};
    return {
        connected: data.connected ?? Boolean(data.page_id),
        page_id: data.page_id || '',
        page_name: data.page_name || '',
        page_link: data.page_link || '',
        connected_by_name: data.connected_by_name || data.connected_by?.name || '',
        connected_at: data.connected_at || null,
        auto_post: data.auto_post !== false,
        default_share: ['municipal', 'all', 'none'].includes(data.default_share) ? data.default_share : 'municipal',
        status: data.status || 'ok',
        last_error: data.last_error || '',
        last_checked_at: data.last_checked_at || null,
    };
}

function facebookPage() {
    return {
        started: false, loading: false, loaded: false, failed: false,
        fb: facebookConnection(null),
        formOpen: false,
        form: { page_id: '', access_token: '' },
        showToken: false,
        connecting: false,
        connectError: '',
        fieldErrors: {},
        patching: false,
        checking: false,
        checkNote: '',
        checkOk: true,
        confirmDisconnect: false,
        disconnecting: false,

        when: fmtWhen,
        get visible() { return may('comm_news', 'create') || may('set_system', 'view'); },
        get canManage() { return may('comm_news', 'create') || may('set_system', 'edit'); },
        async start() {
            if (this.started) return;
            this.started = true;
            await sessionReady();
            if (this.visible) await this.load();
        },
        async load() {
            if (this.loading) return;
            this.loading = true; this.failed = false;
            try {
                const res = await api.get('/admin/integrations/facebook');
                this.fb = facebookConnection(res.data);
                this.loaded = true;
            } catch { this.failed = true; } finally { this.loading = false; }
        },
        get state() {
            if (!this.fb.connected) return 'none';
            return this.fb.status === 'token_problem' ? 'problem' : 'ok';
        },
        get stateLabel() { return { none: 'Not connected', ok: 'Connected', problem: 'Token problem' }[this.state]; },
        get stateTone() { return { none: 'slate', ok: 'emerald', problem: 'rose' }[this.state]; },
        get connectedLine() {
            const by = this.fb.connected_by_name;
            const at = this.fb.connected_at ? fmtWhen(this.fb.connected_at) : '';
            if (!by && !at) return '';
            return 'Connected' + (by ? ' by ' + by : '') + (at ? ' on ' + at : '');
        },
        fieldError(key) {
            const v = this.fieldErrors?.[key];
            return Array.isArray(v) ? (v[0] || '') : (v || '');
        },
        get formReady() { return /^\d{5,}$/.test(this.form.page_id.trim()) && this.form.access_token.trim().length >= 20; },
        openForm() {
            this.form = { page_id: this.fb.page_id || '', access_token: '' };
            this.showToken = false;
            this.connectError = '';
            this.fieldErrors = {};
            this.checkNote = '';
            this.formOpen = true;
            this.$nextTick(() => document.getElementById(this.form.page_id ? 'fb-token' : 'fb-page-id')?.focus());
        },
        closeForm() {
            this.formOpen = false;
            this.form = { page_id: '', access_token: '' };
        },
        async connect() {
            if (this.connecting || !this.formReady || !this.canManage) return;
            this.connecting = true;
            this.connectError = '';
            this.fieldErrors = {};
            try {
                const res = await api.put('/admin/integrations/facebook', { page_id: this.form.page_id.trim(), access_token: this.form.access_token.trim() });
                this.fb = facebookConnection(res.data);
                this.closeForm();
                this.checkNote = '';
                toast('Connected to ' + (this.fb.page_name || 'the Facebook Page') + '.');
            } catch (err) {
                if (err?.status === 422) this.fieldErrors = err.fields || {};
                if (err?.status !== 403) this.connectError = errExplain(err, 'Could not connect the Page. Check the Page ID and the token.');
            } finally {
                // The token leaves the page whatever the answer; it is pasted again to retry.
                this.form.access_token = '';
                this.showToken = false;
                this.connecting = false;
            }
        },
        async patch(changes, revert) {
            if (this.patching || !this.canManage) return;
            this.patching = true;
            try {
                const res = await api.patch('/admin/integrations/facebook', changes);
                if (res?.data) this.fb = facebookConnection(res.data);
                toast('Saved.');
            } catch (err) {
                revert();
                if (err?.status !== 403) toast(errText(err, 'Could not save the change.'), 'error');
            } finally {
                this.patching = false;
            }
        },
        setAutoPost(on) {
            const before = this.fb.auto_post;
            this.fb.auto_post = on;
            this.patch({ auto_post: on }, () => { this.fb.auto_post = before; });
        },
        setDefaultShare(value) {
            const before = this.fb.default_share;
            this.fb.default_share = value;
            this.patch({ default_share: value }, () => { this.fb.default_share = before; });
        },
        async check() {
            if (this.checking) return;
            this.checking = true;
            this.checkNote = '';
            try {
                const res = await api.post('/admin/integrations/facebook/check');
                if (res?.data) this.fb = facebookConnection(res.data);
                this.checkOk = this.fb.status !== 'token_problem';
                this.checkNote = this.checkOk
                    ? 'The connection works: Facebook answered as ' + (this.fb.page_name || 'the Page') + '.'
                    : 'Facebook refused the token' + (this.fb.last_error ? ': ' + this.fb.last_error : '.') + ' Reconnect with a new token.';
            } catch (err) {
                this.checkOk = false;
                if (err?.status !== 403) this.checkNote = errText(err, 'Could not reach Facebook to check. Try again in a moment.');
            } finally {
                this.checking = false;
            }
        },
        async disconnect() {
            if (this.disconnecting || !this.canManage) return;
            this.disconnecting = true;
            try {
                await api.delete('/admin/integrations/facebook');
                this.fb = facebookConnection(null);
                this.confirmDisconnect = false;
                this.checkNote = '';
                this.closeForm();
                toast('The Facebook Page is disconnected.');
            } catch (err) {
                if (err?.status !== 403) toast(errText(err, 'Could not disconnect the Page.'), 'error');
            } finally {
                this.disconnecting = false;
            }
        },
    };
}

/** Status for technical administrators (SystemController; set_system view / edit). */
function systemTab() {
    return {
        started: false, loading: false, loaded: false, failed: false, denied: false,
        data: null, sending: false, lastTest: null,

        when: fmtWhen,
        ago: fmtAgo,
        get canEdit() { return may('set_system', 'edit'); },
        async start() {
            if (this.started) return;
            this.started = true;
            await sessionReady();
            if (!may('set_system', 'view')) { this.denied = true; return; }
            await this.load();
        },
        /** fresh: Refresh asks the server again rather than reuse a recent answer. */
        async load(fresh = false) {
            if (this.loading) return;
            this.loading = true; this.failed = false;
            try {
                this.data = await fetchStatus(fresh);
                this.loaded = true;
            } catch { this.failed = true; } finally { this.loading = false; }
        },
        get overall() {
            const s = this.data?.health?.status;
            if (s === 'ok') return { label: 'All checks pass', tone: 'emerald', icon: 'circle-check' };
            if (s === 'degraded') return { label: 'Degraded: something non-critical is failing', tone: 'amber', icon: 'triangle-alert' };
            if (s === 'down') return { label: 'Down: a critical check is failing', tone: 'rose', icon: 'circle-x' };
            return { label: 'Unknown', tone: 'slate', icon: 'circle-help' };
        },
        get checks() {
            return Object.entries(this.data?.health?.checks || {}).map(([name, c]) => ({
                name, label: CHECK_LABELS[name] || fieldLabel(name), ok: c?.status === 'ok',
                latency: c?.latency_ms == null ? '' : Number(c.latency_ms).toFixed(c.latency_ms < 10 ? 1 : 0) + ' ms', detail: c?.detail || '',
            }));
        },
        get versions() {
            const v = this.data?.versions || {};
            return [
                ['Release', v.release || 'Not set'], ['Contract', v.contract || 'Not set'], ['Environment', v.environment || ''],
                ['PHP', v.php || ''], ['Laravel', v.laravel || ''], ['Database', v.database || ''], ['Time zone', v.timezone || ''],
            ].filter(([, value]) => value !== '');
        },
        get scheduler() {
            const b = this.data?.background || {};
            const last = b.scheduler_last_run || null;
            const d = toDate(last);
            const minutes = d ? (Date.now() - d.getTime()) / 60000 : null;
            // The heartbeat is written every minute; five quiet minutes is a stopped scheduler.
            const ok = minutes !== null && minutes <= 5;
            return {
                ok, last,
                label: ok ? 'Running' : (d ? 'Stale' : 'No heartbeat'),
                text: d ? 'Last heartbeat ' + fmtAgo(last) + ' (' + fmtWhen(last) + ')' : 'No heartbeat recorded: the scheduler may not be running.',
                queued: b.queued_jobs, failed: b.failed_jobs,
            };
        },
        // What the scheduler runs, as the scheduler last recorded it
        // (background.scheduled, 2026-10-15); the plain list when it has not.
        get scheduledJobs() {
            const jobs = this.data?.background?.scheduled;
            if (!Array.isArray(jobs) || !jobs.length) return SCHEDULE_FALLBACK;
            return jobs.map((j) => ({
                key: j.task + j.expression,
                when: SCHEDULE_WHEN[j.expression] || j.expression,
                label: SCHEDULE_TASKS[j.task] || j.task,
                next: j.next_due ? fmtWhen(j.next_due) : '',
            }));
        },
        get deliveries() {
            const n = this.data?.notifications_24h;
            if (!n || typeof n !== 'object') return [];
            return Object.entries(n).map(([channel, statuses]) => {
                const counts = Object.entries(statuses || {}).map(([status, count]) => ({
                    status, count: Number(count) || 0, label: (DELIVERY_LABELS[status] || [status])[0], tone: (DELIVERY_LABELS[status] || [null, 'slate'])[1],
                }));
                return { channel, label: CHANNEL_LABELS[channel] || fieldLabel(channel), total: counts.reduce((t, c) => t + c.count, 0), counts };
            });
        },
        get integrations() { return integrationCards(this.data); },
        get webUrl() { return this.data?.integrations?.web_url || ''; },
        get storage() {
            const s = this.data?.storage || {};
            const free = Number(s.free_bytes);
            const total = Number(s.total_bytes);
            if (!(total > 0) || !Number.isFinite(free)) return null;
            const used = Math.min(100, Math.max(0, Math.round(((total - free) / total) * 100)));
            return { free: fmtBytes(free), total: fmtBytes(total), used, low: used >= 90 };
        },
        /** The host's backup job writes this file; read whatever it recorded. */
        get backup() {
            const b = this.data?.backup;
            if (!b || typeof b !== 'object') return null;
            const at = b.finished_at || b.completed_at || b.time || b.at || b.timestamp || b.date || b.created_at || b.started_at || null;
            const size = b.size_bytes ?? b.bytes ?? b.size ?? null;
            const status = b.status != null ? String(b.status) : (b.ok === true || b.success === true ? 'ok' : (b.ok === false || b.success === false ? 'failed' : ''));
            const worked = /^(ok|success|succeeded|completed|done|passed)$/i.test(status) || (!status && !b.error);
            // The job runs nightly: a last run over 26 hours old means it stopped.
            const d = toDate(at);
            const stale = !d || (Date.now() - d.getTime()) > 26 * 3600 * 1000;
            const ok = worked && !stale;
            return {
                ok,
                status: !worked ? (status || 'failed') : (stale ? 'stale' : 'ok'),
                when: at ? fmtWhen(at) + ' (' + fmtAgo(at) + ')' : 'Time not recorded',
                size: size == null ? '' : (Number.isFinite(Number(size)) ? fmtBytes(Number(size)) : String(size)),
                file: b.file || b.name || b.path || b.archive || '',
                where: b.destination || b.target || b.location || '',
                message: [b.message || b.error || b.note || '', worked && stale ? 'No backup in the last 26 hours: the nightly job may have stopped.' : ''].filter(Boolean).join(' '),
            };
        },
        async testEmail() {
            if (this.sending || !this.canEdit) return;
            this.sending = true;
            try {
                const res = await api.post('/admin/system/test-email');
                const d = res.data || {};
                const nowhere = ['log', 'array'].includes(d.driver);
                this.lastTest = { ok: !nowhere, at: new Date().toISOString(), text: nowhere ? 'Accepted by the ' + d.driver + ' driver, which delivers nothing.' : 'Sent to ' + d.to + '.' };
                toast(nowhere
                    ? 'The “' + d.driver + '” mail driver accepted the test email but delivers nothing: it does not reach ' + d.to + '.'
                    : 'Test email sent to ' + d.to + '. Check that inbox, and its spam folder.', nowhere ? 'warning' : 'success');
            } catch (err) {
                // A refused send answers 502 with the transport's reason.
                const payload = err?.cause?.response?.data?.data;
                if (err?.status === 502 && payload) {
                    this.lastTest = { ok: false, at: new Date().toISOString(), text: 'Refused: ' + (payload.error || 'no reason given') };
                    toast('The mail server refused the test email: ' + (payload.error || 'no reason given') + '.', 'error');
                } else {
                    toast(errText(err, 'Could not send the test email.'), 'error');
                }
            } finally { this.sending = false; }
        },
    };
}

// ----------------------------------------------------------------- audit log

const AUDIT_MODULE_LABELS = {
    dokyu: 'Dokyu', tulong: 'Tulong', sakuna: 'Sakuna', constituents: 'Constituents', communications: 'Communications', payments: 'Payments',
    papeles: 'Papeles', appointments: 'Appointments', user_management: 'User Management', users: 'Test accounts', identity: 'Sign-in & Accounts',
    support: 'Support', reports: 'Reports', privacy: 'Privacy', settings: 'Settings',
};

/** The audit trail, filtered and paged on the server (ModuleController::auditLogs). */
function auditLogTab() {
    return {
        started: false, loading: false, loaded: false, failed: false, denied: false,
        rows: [], total: 0, page: 1, lastPage: 1, pageSize: 50, seq: 0,
        f: { actor: '', target: '', target_type: '', target_id: '', module: '', action: '', from: '', to: '' },
        more: false,
        actions: [], types: [],
        open: false, sel: null,
        exporting: false,
        exportingPdf: false,

        when: fmtWhen,
        actionLabel,
        moduleLabel(m) { return AUDIT_MODULE_LABELS[m] || (m ? fieldLabel(m) : '—'); },
        get canExport() { return may('um_audit', 'export'); },
        async start() {
            if (this.started) return;
            this.started = true;
            await sessionReady();
            if (!may('um_audit', 'view')) { this.denied = true; return; }
            await this.load();
        },
        params() {
            const p = {};
            Object.entries(this.f).forEach(([k, v]) => {
                const s = String(v ?? '').trim();
                if (s !== '') p[k] = s;
            });
            return p;
        },
        get filtered() { return Object.keys(this.params()).length > 0; },
        get dateProblem() { return this.f.from && this.f.to && this.f.from > this.f.to ? 'The start date is after the end date.' : ''; },
        apply() { this.page = 1; this.load(); },
        clear() {
            Object.keys(this.f).forEach((k) => { this.f[k] = ''; });
            this.apply();
        },
        async load() {
            if (this.dateProblem) return;
            const seq = ++this.seq;
            this.loading = true; this.failed = false;
            try {
                const res = await api.get('/admin/audit-logs', { params: { ...this.params(), per_page: this.pageSize, page: this.page } });
                if (seq !== this.seq) return;
                this.rows = res.data || [];
                this.total = res.meta?.total ?? this.rows.length;
                this.lastPage = Math.max(1, res.meta?.last_page || 1);
                this.page = res.meta?.page || this.page;
                this.remember(this.rows);
                this.loaded = true;
            } catch {
                if (seq === this.seq) this.failed = true;
            } finally {
                if (seq === this.seq) this.loading = false;
            }
        },
        /** Actions and record types seen so far, offered in the filters. */
        remember(rows) {
            const actions = new Set(this.actions);
            const types = new Set(this.types);
            rows.forEach((r) => { if (r.action) actions.add(r.action); if (r.target_type) types.add(r.target_type); });
            this.actions = [...actions].sort().slice(0, 300);
            this.types = [...types].sort().slice(0, 100);
        },
        // x-admin.pager reads a list's length and turns pages; here the pages
        // are the server's, so the "list" is the total it reported.
        get pageInfo() { return { length: this.total }; },
        pageCount() { return this.lastPage; },
        pageOf() { return this.page; },
        turnPage(key, step) {
            const next = Math.min(Math.max(1, this.page + step), this.lastPage);
            if (next === this.page) return;
            this.page = next;
            this.load();
        },
        get countLine() {
            if (!this.loaded) return '';
            return plural(this.total, 'entry', 'entries') + (this.filtered ? ' match these filters' : ' in the log') + ', newest first.';
        },

        show(row) { this.sel = row; this.open = true; },
        get changes() { return this.sel ? diffFields(this.sel.before, this.sel.after) : []; },
        get changedCount() { return this.changes.filter((c) => c.changed).length; },
        get contextRows() {
            const c = this.sel?.context;
            if (!c || typeof c !== 'object') return [];
            return Object.entries(c).map(([k, v]) => ({ key: k, label: fieldLabel(k), value: showValue(v) }));
        },
        actorLine(row) {
            if (!row) return '';
            const who = row.user || (row.actor_type === 'system' ? 'System' : (row.actor_type === 'citizen' ? 'A resident' : 'Unknown'));
            return who + (row.role ? ' · ' + fieldLabel(row.role) : '') + (row.scope ? ' · ' + (row.scope === 'Municipal' ? 'Municipality-wide' : row.scope) : '');
        },
        targetLine(row) {
            if (!row) return '';
            const t = row.target == null || row.target === '' ? '' : String(row.target);
            return [row.target_type ? fieldLabel(row.target_type) : '', t].filter(Boolean).join(' · ') || '—';
        },
        async exportCsv() {
            if (this.exporting || !this.canExport || this.dateProblem) return;
            this.exporting = true;
            try {
                const blob = await api.get('/admin/audit-logs/export', { params: this.params(), responseType: 'blob' });
                const url = URL.createObjectURL(blob instanceof Blob ? blob : new Blob([blob], { type: 'text/csv' }));
                const a = Object.assign(document.createElement('a'), { href: url, download: 'audit-log-' + today() + '.csv' });
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(url), 2000);
                toast(this.total > 10000
                    ? 'Exported the newest 10,000 entries. Narrow the filters to export the rest.'
                    : 'Audit log exported. The export itself is recorded in the log.', this.total > 10000 ? 'warning' : 'success');
            } catch (err) {
                toast(errText(err, 'Could not export the audit log.'), 'error');
            } finally { this.exporting = false; }
        },
        // The same export as a PDF (owner, 2026-10-15), readable as the screen
        // is: Manila time, people, modules and actions in words, and only the
        // fields that changed. The export is recorded as the CSV one is (the
        // same request, the same filters); the rows come from the list itself.
        async exportPdf() {
            if (this.exportingPdf || !this.canExport || this.dateProblem) return;
            this.exportingPdf = true;
            try {
                await api.get('/admin/audit-logs/export', { params: this.params(), responseType: 'blob' });
                const res = await api.getAll('/admin/audit-logs', { params: this.params(), maxPages: 20 });
                const rows = (res.data || []).map((r) => ({
                    When: fmtWhen(r.time),
                    Person: this.actorLine(r),
                    Module: this.moduleLabel(r.module),
                    Action: actionLabel(r.action),
                    Record: this.targetLine(r),
                    Changes: diffFields(r.before, r.after).filter((c) => c.changed).map((c) => c.label + ': ' + c.from + ' -> ' + c.to).join('; '),
                }));
                const total = res.meta?.total ?? rows.length;
                await window.tablePdf(rows, {
                    filename: 'audit-log-' + today() + '.pdf',
                    title: 'Audit log',
                    subtitle: total > rows.length ? 'The newest ' + rows.length.toLocaleString('en-PH') + ' of ' + total.toLocaleString('en-PH') + ' entries: narrow the filters, or use the CSV, for the rest' : '',
                });
                toast('Audit log exported as a PDF. The export itself is recorded in the log.');
            } catch (err) {
                toast(errText(err, 'Could not export the audit log.'), 'error');
            } finally { this.exportingPdf = false; }
        },
    };
}

// ------------------------------------------------------------- departments

/**
 * The municipal offices staff belong to and services are handled by
 * (DepartmentsController; um_personnel view / create / edit). A rename is
 * carried into everything that names the office, in one transaction.
 */
function departmentsTab() {
    return {
        started: false, loading: false, loaded: false, failed: false, denied: false,
        rows: [], search: '', show: '',
        open: false, mode: 'create', sel: null,
        form: { name: '', short: '', active: true },
        saving: false, error: '', fieldErrors: {},
        confirmToggle: null, toggling: null,

        plural,
        get canCreate() { return may('um_personnel', 'create'); },
        get canEdit() { return may('um_personnel', 'edit'); },
        init() {
            this.$watch('form', () => { this.error = ''; this.fieldErrors = {}; });
        },
        async start() {
            if (this.started) return;
            this.started = true;
            await sessionReady();
            if (!may('um_personnel', 'view')) { this.denied = true; return; }
            await this.load();
        },
        async load() {
            this.loading = true; this.failed = false;
            try {
                const res = await api.get('/admin/offices');
                this.rows = res.data || [];
                this.loaded = true;
            } catch { this.failed = true; } finally { this.loading = false; }
        },
        get activeCount() { return this.rows.filter((r) => r.active).length; },
        get offCount() { return this.rows.filter((r) => !r.active).length; },
        get shown() {
            const q = this.search.trim().toLowerCase();
            return this.rows.filter((r) => (this.show === '' || (this.show === 'on' ? r.active : !r.active))
                && (!q || [r.name, r.short, r.head, r.head_position].some((v) => String(v || '').toLowerCase().includes(q))));
        },
        headLine(r) { return r.head ? r.head + (r.head_position ? ' · ' + r.head_position : '') : 'Not in Direktoryo'; },
        create() {
            if (!this.canCreate) return;
            this.mode = 'create'; this.sel = null;
            this.form = { name: '', short: '', active: true };
            this.error = ''; this.fieldErrors = {};
            this.open = true;
        },
        edit(row) {
            this.mode = 'edit'; this.sel = row;
            this.form = { name: row.name || '', short: row.short || '', active: !!row.active };
            this.error = ''; this.fieldErrors = {};
            this.open = true;
        },
        get renaming() { return this.mode === 'edit' && !!this.sel && this.form.name.trim() !== this.sel.name; },
        get switchingOff() { return this.mode === 'edit' && !!this.sel && this.sel.active && !this.form.active; },
        get dirty() {
            if (this.mode === 'create') return this.form.name.trim() !== '';
            const s = this.sel;
            return !!s && (this.form.name.trim() !== s.name || this.form.short.trim() !== (s.short || '') || this.form.active !== !!s.active);
        },
        problems() {
            const e = {};
            const n = this.form.name.trim();
            if (n.length < 3) e.name = 'The office name needs at least 3 characters.';
            else if (n.length > 160) e.name = 'The office name is at most 160 characters.';
            else if (this.rows.some((r) => r.name.toLowerCase() === n.toLowerCase() && r.id !== this.sel?.id)) e.name = 'Another office already has this name.';
            if (this.form.short.trim().length > 40) e.short = 'The short name is at most 40 characters.';
            return e;
        },
        async save() {
            if (this.saving) return;
            if (this.mode === 'create' ? !this.canCreate : !this.canEdit) return;
            const problems = this.problems();
            this.fieldErrors = problems;
            if (Object.keys(problems).length) { this.error = Object.values(problems)[0]; return; }
            this.saving = true; this.error = '';
            try {
                if (this.mode === 'create') {
                    const res = await api.post('/admin/offices', { name: this.form.name.trim(), short: this.form.short.trim() || null });
                    toast((res.data?.name || 'The office') + ' added. It can be chosen for staff and services now.');
                } else {
                    const s = this.sel;
                    const body = {};
                    if (this.form.name.trim() !== s.name) body.name = this.form.name.trim();
                    if (this.form.short.trim() !== (s.short || '')) body.short = this.form.short.trim() || null;
                    if (this.form.active !== !!s.active) body.active = this.form.active;
                    if (!Object.keys(body).length) { this.open = false; return; }
                    await api.put('/admin/offices/' + s.id, body);
                    toast(body.name
                        ? 'Renamed to ' + body.name + ' everywhere the office was named.'
                        : (body.active === false ? s.name + ' is switched off.' : (body.active === true ? s.name + ' is active again.' : 'Office saved.')));
                }
                this.open = false;
                window.dispatchEvent(new CustomEvent('offices-changed'));
                await this.load();
            } catch (err) {
                const fields = err?.fields || {};
                this.fieldErrors = Object.fromEntries(Object.entries(fields).map(([k, m]) => [k, Array.isArray(m) ? m[0] : String(m)]));
                this.error = errExplain(err, 'Could not save the office.');
            } finally { this.saving = false; }
        },
        /** Two clicks from the list: the first asks, the second switches. */
        async toggle(row) {
            if (!this.canEdit || this.toggling) return;
            if (this.confirmToggle !== row.id) { this.confirmToggle = row.id; return; }
            this.confirmToggle = null;
            this.toggling = row.id;
            try {
                await api.put('/admin/offices/' + row.id, { active: !row.active });
                toast(row.active ? row.name + ' is switched off.' : row.name + ' is active again.');
                window.dispatchEvent(new CustomEvent('offices-changed'));
                await this.load();
            } catch (err) {
                toast(errText(err, 'Could not change the office.'), 'error');
            } finally { this.toggling = null; }
        },
    };
}

/**
 * Department pickers on the personnel forms: the active offices from the
 * Departments list. null when the role cannot read it or it failed; the form
 * then falls back to the directory's office names.
 */
function officesStore() {
    return {
        names: null,
        pending: null,
        activeNames() {
            if (this.names) return Promise.resolve(this.names);
            if (this.pending) return this.pending;
            this.pending = sessionReady()
                .then(() => (may('um_personnel', 'view') ? api.get('/admin/offices') : null))
                .then((res) => {
                    const names = (res?.data || []).filter((o) => o.active).map((o) => o.name);
                    return names.length ? names : null;
                })
                .catch(() => null)
                .then((names) => {
                    this.names = names;
                    this.pending = null;
                    return names;
                });
            return this.pending;
        },
        forget() { this.names = null; },
    };
}

// ============================================================ Direktoryo

/**
 * Settings › Direktoryo (owner, 2026-10-21): the directory residents read,
 * kept here and laid out like the residents' Direktoryo: a search, Municipal
 * Offices | Barangays, a count, the cards. It was Balita's Offices and
 * Barangays tabs. Each half is for whoever holds it: the offices for
 * comm_directory (the Municipal Information Office and the administrators),
 * the barangays for comm_barangay_directory (also each Barangay Secretary,
 * who sees and edits their own barangay only).
 */
function directoryTab() {
    const blankOffice = () => ({ id: null, office: '', official_name: '', position: '', contact: '', email: '' });
    return {
        view: 'offices',
        search: '',
        offices: [],
        barangays: [],
        loading: true,
        failed: false,
        processing: false,
        officeOpen: false,
        officeMode: 'add',
        officeForm: blankOffice(),
        removing: null,
        barangayOpen: false,
        selected: null,
        may,
        get canOffices() { return may('comm_directory', 'view'); },
        get canBarangays() { return may('comm_barangay_directory', 'view'); },
        async init() {
            await sessionReady();
            const asked = new URLSearchParams(window.location.search).get('view');
            this.view = (asked === 'barangays' && this.canBarangays) || !this.canOffices ? 'barangays' : 'offices';
            await this.load();
        },
        async load() {
            if (!this.canOffices && !this.canBarangays) return;
            this.loading = true;
            this.failed = false;
            try {
                const [offices, barangays] = await Promise.all([
                    this.canOffices ? api.get('/admin/directory', { params: { scope: 'government' } }) : { data: [] },
                    this.canBarangays ? api.get('/barangays') : { data: [] },
                ]);
                this.offices = offices.data || [];
                // seal_path names one of this site's own bundled pictures.
                this.barangays = (barangays.data || [])
                    .map((b) => ({ ...b, seal_path: window.staticAssetUrl ? window.staticAssetUrl(b.seal_path) : b.seal_path }))
                    .sort((a, b) => a.name.localeCompare(b.name));
            } catch {
                this.failed = true;
            } finally {
                this.loading = false;
            }
        },
        get filteredOffices() {
            const q = this.search.trim().toLowerCase();
            return this.offices.filter((o) => !q || (o.office || '').toLowerCase().includes(q) || (o.official_name || '').toLowerCase().includes(q));
        },
        // A Barangay Secretary sees their own barangay only.
        get myBarangays() { return this.barangays.filter((b) => session()?.inScope(b.name)); },
        get filteredBarangays() {
            const q = this.search.trim().toLowerCase();
            return this.myBarangays.filter((b) => !q || b.name.toLowerCase().includes(q) || (b.punong_barangay || '').toLowerCase().includes(q));
        },
        get countLine() {
            return this.view === 'offices'
                ? this.filteredOffices.length + ' of ' + this.offices.length + ' offices'
                : this.filteredBarangays.length + ' of ' + this.myBarangays.length + (this.myBarangays.length === 1 ? ' barangay' : ' barangays');
        },
        initials(name) { return (name || '').split(/\s+/).filter(Boolean).map((w) => w[0]).slice(0, 2).join('').toUpperCase(); },

        openAddOffice() { this.officeForm = blankOffice(); this.officeMode = 'add'; this.officeOpen = true; },
        openEditOffice(o) { this.officeForm = { ...blankOffice(), ...o }; this.officeMode = 'edit'; this.officeOpen = true; },
        async saveOffice() {
            const f = this.officeForm;
            if (this.processing || !f.office.trim()) return;
            this.processing = true;
            try {
                const payload = { office: f.office.trim(), official_name: f.official_name || null, position: f.position || null, contact: f.contact || null, email: f.email || null };
                if (this.officeMode === 'add') await api.post('/admin/directory', payload);
                else await api.put('/admin/directory/' + f.id, payload);
                this.officeOpen = false;
                toast(this.officeMode === 'add' ? 'Office added. Residents see it in Direktoryo.' : 'Office updated.');
                await this.load();
            } catch (err) {
                toast(errText(err, 'Could not save the office.'), 'error');
            } finally {
                this.processing = false;
            }
        },
        async confirmRemove() {
            const o = this.removing;
            if (this.processing || !o) return;
            this.processing = true;
            try {
                await api.delete('/admin/directory/' + o.id);
                this.removing = null;
                toast(o.office + ' was removed from the directory.');
                await this.load();
            } catch (err) {
                toast(errText(err, 'Could not remove the office.'), 'error');
            } finally {
                this.processing = false;
            }
        },

        editBarangay(b) { this.selected = { ...b }; this.barangayOpen = true; },
        async saveBarangay() {
            const b = this.selected;
            if (this.processing || !b) return;
            this.processing = true;
            try {
                await api.put('/admin/directory/barangay/' + encodeURIComponent(b.name), {
                    punong_barangay: b.punong_barangay, barangay_secretary: b.barangay_secretary, contact: b.contact,
                });
                this.barangayOpen = false;
                toast('Brgy. ' + b.name + ' updated.');
                await this.load();
            } catch (err) {
                toast(errText(err, 'Could not save.'), 'error');
            } finally {
                this.processing = false;
            }
        },
    };
}

// The scheduler's jobs in plain words (routes/console.php in the backend).
const SCHEDULE_TASKS = {
    'esperanza:notifications deliver': 'Sends waiting email and SMS notifications',
    'scheduler-heartbeat': 'Writes the heartbeat above',
    'esperanza:identity-checks': 'Retries identity checks Didit could not run',
    'esperanza:notifications overdue': 'The overdue-requests digest',
    'esperanza:appointment-reminders': "Tomorrow's appointment reminders",
    'esperanza:notifications weekly': 'The weekly summary',
    'esperanza:event-reminders': 'Reminds residents of the events they asked about, the day before',
};
const SCHEDULE_WHEN = {
    '* * * * *': 'Every minute',
    '*/5 * * * *': 'Every 5 minutes',
    '0 8 * * *': 'Daily, 8:00 AM',
    '0 8 * * 1': 'Mondays, 8:00 AM',
    '0 * * * *': 'Every hour',
};
const SCHEDULE_FALLBACK = [
    { key: 'a', when: 'Every minute', label: 'Sends waiting email and SMS notifications, and writes the heartbeat above', next: '' },
    { key: 'b', when: 'Every 5 minutes', label: 'Retries identity checks Didit could not run', next: '' },
    { key: 'c', when: 'Daily, 8:00 AM', label: "The overdue-requests digest, and tomorrow's appointment reminders", next: '' },
    { key: 'd', when: 'Mondays, 8:00 AM', label: 'The weekly summary', next: '' },
];

export function register(Alpine) {
    Alpine.data('directoryTab', directoryTab);
    Alpine.data('siteHelpEditor', siteHelpEditor);
    Alpine.data('templatesTab', templatesTab);
    Alpine.data('systemTab', systemTab);
    Alpine.data('integrationsTab', integrationsTab);
    Alpine.data('facebookPage', facebookPage);
    Alpine.data('auditLogTab', auditLogTab);
    Alpine.data('departmentsTab', departmentsTab);
    Alpine.store('offices', officesStore());
    window.addEventListener('offices-changed', () => Alpine.store('offices').forget());
}

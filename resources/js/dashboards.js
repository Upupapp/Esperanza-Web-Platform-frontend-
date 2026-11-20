import api from './api/client';
import { chartSvg } from './report-charts';

// Office and barangay dashboards, and the standard printed reports (owner,
// 2026-10-14; the MSWDO first). The numbers are the backend's
// (GET /admin/dashboard/department, /admin/dashboard/barangay and
// /admin/reports/{key}); this file only picks the period, draws them and
// prints them in the LGU's own report layout.

const TZ = 'Asia/Manila';
const pad = (n) => String(n).padStart(2, '0');
const ymd = (y, m, d) => `${y}-${pad(m)}-${pad(d)}`;
const lastDay = (y, m) => new Date(Date.UTC(y, m, 0)).getUTCDate();

function today() {
    const s = new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
    const [y, m, d] = s.split('-').map(Number);

    return { y, m, d, iso: ymd(y, m, d) };
}

export const PERIODS = [
    { key: 'this_month', label: 'This month' },
    { key: 'last_month', label: 'Last month' },
    { key: 'this_quarter', label: 'This quarter' },
    { key: 'last_quarter', label: 'Last quarter' },
    { key: 'this_year', label: 'This year' },
    // Imported past records are mostly last year's (2026-11-20).
    { key: 'last_year', label: 'Last year' },
    { key: 'custom', label: 'Custom' },
];

/** From and to (Y-m-d, days in Esperanza) for a period choice. */
export function periodRange(key) {
    const t = today();
    if (key === 'last_month') {
        const y = t.m === 1 ? t.y - 1 : t.y;
        const m = t.m === 1 ? 12 : t.m - 1;
        return { from: ymd(y, m, 1), to: ymd(y, m, lastDay(y, m)) };
    }
    if (key === 'this_quarter') {
        const q = Math.floor((t.m - 1) / 3);
        return { from: ymd(t.y, q * 3 + 1, 1), to: t.iso };
    }
    if (key === 'last_quarter') {
        let q = Math.floor((t.m - 1) / 3) - 1;
        let y = t.y;
        if (q < 0) { q = 3; y -= 1; }
        return { from: ymd(y, q * 3 + 1, 1), to: ymd(y, q * 3 + 3, lastDay(y, q * 3 + 3)) };
    }
    if (key === 'this_year') return { from: ymd(t.y, 1, 1), to: t.iso };
    if (key === 'last_year') return { from: ymd(t.y - 1, 1, 1), to: ymd(t.y - 1, 12, 31) };

    return { from: ymd(t.y, t.m, 1), to: t.iso };
}

export const peso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
export const num = (n) => Number(n || 0).toLocaleString('en-PH');
/** A peso amount short enough for a tile: ₱56,500, ₱292K, ₱1.4M. */
export const pesoTile = (n) => {
    const v = Number(n || 0);
    if (v >= 1e6) return '₱' + (v / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
    if (v >= 1e5) return '₱' + Math.round(v / 1e3) + 'K';
    return '₱' + Math.round(v).toLocaleString('en-PH');
};
/** "1 account", "2 accounts". */
export const plural = (n, one, many) => num(n) + ' ' + (Number(n) === 1 ? one : many);
const shortDate = (d) => (d ? new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '');

/** Opens the report preview (the one <x-admin.report-sheet> on the page). On window: the button may be gone by then. */
export function openReport(detail) {
    window.dispatchEvent(new CustomEvent('open-report', { detail }));
}

/** What each printed report is, for the cards that open it. */
export const REPORT_INFO = {
    dept_summary: { icon: 'chart-column', desc: 'The dashboard on paper: the figures, then a graph over each table - by month, program, barangay, sex, age group, sector.' },
    brgy_summary: { icon: 'chart-column', desc: 'The barangay on paper: the figures, then graphs of its residents, certificates and the assistance they received.' },
    dept_services_extended: { icon: 'hand-heart', desc: 'Clients served by type of assistance or document, by sex, with the sectors.' },
    dept_by_barangay: { icon: 'map-pinned', desc: 'All 20 barangays: cases served, persons, amount released, still pending.' },
    dept_masterlist: { icon: 'users', desc: 'Every person served, with the date, amount and who received it. Contains names; each copy is recorded.' },
    dept_pending: { icon: 'hourglass', desc: 'Open cases as of today: who waits, on whom, and what is overdue.' },
    dept_accomplishment: { icon: 'clipboard-list', desc: 'The LGU Accomplishment Report, written from what was released each day.' },
    brgy_monthly: { icon: 'file-text', desc: 'Certificates issued and fees, residents and records, assistance received, disasters.' },
    brgy_collections: { icon: 'receipt-text', desc: 'Official receipts issued, and the fees in the barangay logbook.' },
    brgy_population: { icon: 'users-round', desc: 'Residents by age group and sex, the sectors, households and families.' },
    brgy_assistance: { icon: 'heart-handshake', desc: 'Assistance the residents received from every office, by program.' },
};
const info = (key) => REPORT_INFO[key] || { icon: 'file-text', desc: '' };

const AGE_LABELS = { child: 'Children (0-17)', adult: 'Adults (18-59)', senior: 'Seniors (60+)' };
const SECTOR_LABELS = { senior: 'Senior citizens (60+)', pwd: 'Persons with disability', solo_parent: 'Solo parents', '4ps': '4Ps beneficiaries', ip: 'Indigenous peoples' };
const SEX_LABELS = { female: 'Female', male: 'Male' };
// Where a record came from (flows review, 2026-11-20): records imported from
// the office's own past files count everywhere, by their real date, unless
// one kind is chosen here (the backend's ReportFilters::SOURCES).
const SOURCE_LABELS = { filed: 'Filed in Esperanza', imported: 'Imported past records' };
const asOptions = (labels) => Object.entries(labels).map(([value, label]) => ({ value, label }));

/**
 * What an office's dashboard and its reports narrow to (2026-10-28: "filters
 * that they can modify"): one program, and who was helped - sex, age group,
 * sector, each person as on the day counted (the backend's ReportFilters).
 * Shared by the office dashboard and the Reports page.
 */
function reportFilters() {
    return {
        service: '',
        sex: '',
        age: '',
        sector: '',
        source: '',
        sexOptions: asOptions(SEX_LABELS),
        sourceOptions: asOptions(SOURCE_LABELS),
        ageOptions: asOptions(AGE_LABELS),
        sectorOptions: asOptions(SECTOR_LABELS),
        /** The chosen ones, as the API takes them; nothing for "everyone". */
        filterParams() {
            const out = {};
            ['service', 'sex', 'age', 'sector', 'source'].forEach((k) => { if (this[k]) out[k] = this[k]; });
            return out;
        },
        // Methods, not getters: a getter spread into the component is read once.
        anyFilter() { return !!(this.service || this.sex || this.age || this.sector || this.source); },
        personFilter() { return !!(this.sex || this.age || this.sector); },
        /** Picks up a choice from the address, if it is one of the choices. */
        readFilters(q) {
            if (SEX_LABELS[q.get('sex')]) this.sex = q.get('sex');
            if (AGE_LABELS[q.get('age')]) this.age = q.get('age');
            if (SECTOR_LABELS[q.get('sector')]) this.sector = q.get('sector');
            if (SOURCE_LABELS[q.get('source')]) this.source = q.get('source');
            this.service = q.get('service') || '';
        },
        clearFilters() {
            this.service = '';
            this.sex = '';
            this.age = '';
            this.sector = '';
            this.source = '';
            this.changed();
        },
    };
}

/** Bars relative to the largest, for the pressed-track bar lists. */
function bars(rows) {
    const max = Math.max(1, ...rows.map((r) => r.value));
    return rows.map((r) => ({ ...r, pct: Math.round((r.value / max) * 100) }));
}

/** Copies getters as getters: a spread reads each one once and keeps the value. */
function mix(target, ...sources) {
    sources.forEach((s) => Object.defineProperties(target, Object.getOwnPropertyDescriptors(s)));
    return target;
}

/** "Nov 3" for a day (2026-11-03); a month (2026-11) is left to the trend chart. */
const dayLabel = (d) => (/^\d{4}-\d{2}-\d{2}/.test(String(d)) ? new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : d);
const sum = (o) => Object.values(o || {}).reduce((n, v) => n + (Number(v) || 0), 0);

/** How a request came in (GET /admin/dashboard/department and summary?area=desk by_channel). */
const CHANNELS = {
    online: { label: 'Online', color: '#2f62f5' },
    walk_in: { label: 'Walk-in', color: '#e8c200' },
    referral: { label: 'Referral', color: '#a855f7' },
    record: { label: 'Recorded after the fact', color: '#94a3b8' },
    // Brought in from the office's own past files (2026-11-20), never "Online".
    import: { label: 'Imported past records', color: '#64748b' },
};
const channelRows = (rows) => (rows || []).filter((r) => r.count > 0).map((r) => ({ label: CHANNELS[r.channel]?.label || r.channel, value: r.count, color: CHANNELS[r.channel]?.color || '#14b8a6' }));

/** Who was helped: sex for the doughnut, age groups and sectors as bars. */
function demographics(d) {
    if (!d) return { sex: [], ages: [], sectors: [], unknownAge: 0, people: 0 };
    return {
        people: d.people || 0,
        sex: [
            { label: 'Female', value: d.female || 0, color: '#e85d9b' },
            { label: 'Male', value: d.male || 0, color: '#2f62f5' },
            { label: 'Not recorded', value: d.unknown_sex || 0, color: '#94a3b8' },
        ].filter((r) => r.value > 0),
        ages: bars(Object.entries(AGE_LABELS).map(([k, label]) => ({ label, value: d.ages?.[k] || 0 }))),
        unknownAge: d.unknown_age || 0,
        sectors: bars(Object.entries(SECTOR_LABELS).map(([k, label]) => ({ label, value: d.sectors?.[k] || 0 }))),
    };
}

/** Period picking shared by both dashboards and the Reports page. */
function periodPicker() {
    return {
        periods: PERIODS,
        period: 'this_month',
        from: '',
        to: '',
        setPeriod(key) {
            this.period = key;
            if (key !== 'custom') Object.assign(this, periodRange(key));
            this.changed();
        },
        customChanged() {
            if (this.from && this.to) this.changed();
        },
        changed() {},
    };
}

const query = () => new URLSearchParams(window.location.search);

/** Keeps the dashboard's view in the address, so a reload or a shared link opens the same one. */
function remember(params) {
    const q = query();
    Object.entries(params).forEach(([k, v]) => (v ? q.set(k, v) : q.delete(k)));
    const s = q.toString();
    window.history.replaceState(null, '', window.location.pathname + (s ? '?' + s : ''));
}

// ---------------------------------------------------------------- Host

/** The Dashboard page's views, in the words and icons of its tabs. */
const VIEW_TABS = {
    overview: { label: 'Overview', icon: 'layout-dashboard' },
    department: { label: 'Departments', icon: 'building-2' },
    barangay: { label: 'Barangays', icon: 'map-pinned' },
    desk: { label: 'Front desk', icon: 'concierge-bell' },
    records: { label: 'Records', icon: 'folder-search' },
    balita: { label: 'Balita', icon: 'newspaper' },
    sakuna: { label: 'Sakuna', icon: 'siren' },
};

/** One icon per "Needs your action" item (GET /admin/dashboard/inbox keys). */
const INBOX_ICONS = {
    dokyu_ready: 'file-check', tulong_ready: 'hand-heart', assigned_to_me: 'user-round-check', waiting_on_staff: 'inbox',
    ready_to_claim: 'package-check', overdue: 'timer', payments_to_verify: 'wallet', accounts_to_verify: 'badge-check',
    identity_to_review: 'scan-face', papeles_to_verify: 'folder-open', records_to_review: 'user-round-search',
    dq_mine: 'triangle-alert', dq_unassigned: 'list-todo', balita_second_look: 'eye', balita_review: 'newspaper',
    events_review: 'calendar-days', banners_review: 'gallery-horizontal', balita_sent_back: 'undo-2',
    tickets_open: 'message-square', appointments_today: 'calendar-clock', sessions_week: 'users-round',
    sakuna_to_validate: 'clipboard-check', incidents_mine: 'siren', sakuna_alert_drafts: 'bell-ring',
    relief_to_release: 'package', forms_to_approve: 'clipboard-list', casualties_to_validate: 'heart-pulse', ids_lapsing: 'id-card',
};
/** The items of each area, for its tab when it is not the account's home (the home shows all). */
const AREA_INBOX = {
    balita: ['balita_second_look', 'balita_review', 'events_review', 'banners_review', 'balita_sent_back', 'tickets_open'],
    sakuna: ['sakuna_to_validate', 'incidents_mine', 'sakuna_alert_drafts', 'relief_to_release', 'forms_to_approve', 'casualties_to_validate'],
    records: ['records_to_review', 'dq_mine', 'dq_unassigned', 'papeles_to_verify', 'accounts_to_verify', 'identity_to_review'],
    desk: ['waiting_on_staff', 'ready_to_claim', 'overdue', 'assigned_to_me', 'accounts_to_verify', 'identity_to_review', 'appointments_today', 'tickets_open'],
};
const INBOX_TONES = {
    brand: { icon: 'bg-brand-50 text-brand-600', pill: 'bg-brand-50 text-brand-700' },
    warning: { icon: 'bg-amber-50 text-amber-600', pill: 'bg-amber-100 text-amber-800' },
    danger: { icon: 'bg-rose-50 text-rose-600', pill: 'bg-rose-100 text-rose-700' },
    success: { icon: 'bg-emerald-50 text-emerald-600', pill: 'bg-emerald-100 text-emerald-700' },
};

/**
 * The Dashboard page: one view per kind of work, as tabs only when an account
 * has more than one (owner, 18 Nov). The server says which views an account
 * has and the home first (GET /admin/dashboard/subjects views); "Needs your
 * action" (GET /admin/dashboard/inbox) is loaded here once for every view.
 */
export function dashboardHost() {
    return {
        view: 'overview',
        subjects: { home: 'overview', views: [], offices: [], barangays: [], filter_barangays: [] },
        catalog: { reports: [], offices: [], barangays: [] },
        subjectsReady: false,
        inbox: [],
        inboxReady: false,
        inboxSeq: 0,
        async init() {
            try {
                const [subjects, catalog] = await Promise.all([
                    api.get('/admin/dashboard/subjects'),
                    this.$store.session.can('reports') ? api.get('/admin/reports').catch(() => null) : Promise.resolve(null),
                ]);
                this.subjects = { ...this.subjects, ...subjects.data };
                this.catalog = catalog?.meta?.printable || this.catalog;
            } catch {
                // The overview still works; the other tabs simply do not show.
            }
            const asked = query().get('view');
            this.view = this.views.includes(asked) ? asked : this.views[0];
            this.subjectsReady = true;
            this.loadInbox();
            this.$store.live.on(this.$el, 'any', () => this.loadInbox(true));
            this.$nextTick(() => window.renderIcons?.());
        },
        /** The views, the home first. A server without views: the overview and the tabs it had. */
        get views() {
            const listed = (this.subjects.views || []).filter((v) => VIEW_TABS[v]);
            if (listed.length) return listed;
            const old = ['overview', this.subjects.offices.length ? 'department' : null, this.subjects.barangays.length ? 'barangay' : null].filter(Boolean);
            return old.includes(this.subjects.home) ? [this.subjects.home, ...old.filter((v) => v !== this.subjects.home)] : old;
        },
        get showTabs() { return this.subjectsReady && this.views.length > 1; },
        get isHome() { return this.view === this.views[0]; },
        tabLabel(v) {
            if (v === 'department' && this.subjects.offices.length === 1) return 'My office';
            if (v === 'barangay' && this.subjects.barangays.length === 1) return 'My barangay';
            return VIEW_TABS[v]?.label || v;
        },
        tabIcon(v) { return VIEW_TABS[v]?.icon || 'layout-dashboard'; },
        show(view) {
            this.view = view;
            remember({ view: view === this.views[0] ? null : view, office: null, barangay: null });
            this.$nextTick(() => window.renderIcons?.());
        },
        // Every item is counted and scoped by the server; a live change
        // anywhere recounts it quietly, and only the latest answer is kept.
        async loadInbox(quiet = false) {
            const mine = ++this.inboxSeq;
            try {
                const res = await api.get('/admin/dashboard/inbox');
                if (mine !== this.inboxSeq) return;
                this.inbox = (res.data?.items || []).filter((i) => i.count > 0);
            } catch {
                if (mine !== this.inboxSeq || quiet) return;
                this.inbox = [];
            } finally {
                if (mine === this.inboxSeq) {
                    this.inboxReady = true;
                    this.$nextTick(() => window.renderIcons?.());
                }
            }
        },
        inboxIcon(item) { return INBOX_ICONS[item.key] || 'circle-dot'; },
        inboxTone(item) { return INBOX_TONES[item.tone] || INBOX_TONES.brand; },
        /** One item's count (0 when absent), for a tile that shows it. */
        inboxCount(key) { return this.inbox.find((i) => i.key === key)?.count || 0; },
        get shownInbox() {
            const keys = AREA_INBOX[this.view];
            return this.isHome || !keys ? this.inbox : this.inbox.filter((i) => keys.includes(i.key));
        },
        get inboxTotal() { return this.shownInbox.reduce((n, i) => n + (Number(i.count) || 0), 0); },
        /** The printable reports of one kind this account may open. */
        reportsOf(kind) {
            return (this.catalog.reports || []).filter((r) => r.kind === kind);
        },
    };
}

// ----------------------------------------------------- Office dashboard

export function deptDashboard() {
    return {
        ...periodPicker(),
        ...reportFilters(),
        office: null,
        barangay: '',
        data: null,
        loading: true,
        failed: false,
        error: '',
        seq: 0,
        liveAfter: false,
        peso,
        pesoTile,
        num,
        plural,
        shortDate,
        info,
        init() {
            Object.assign(this, periodRange(this.period));
            const offices = this.subjects?.offices || [];
            const asked = query().get('office');
            // Only when the address names one: PESO has no short name, and null matched it.
            const found = asked ? offices.find((o) => o.name === asked || o.short === asked) : null;
            this.office = found?.name || (offices.find((o) => o.short === 'MSWDO') || offices[0])?.name || null;
            this.readFilters(query());
            if (this.service && !this.services.some((x) => x.key === this.service)) this.service = '';
            this.load();
            // Live updates (2026-11-15): any change anywhere reloads it quietly.
            this.$store.live.on(this.$el, 'any', () => this.load(true));
        },
        changed() { this.load(); },
        /** Another office: its own programs, so the program chosen goes. */
        officeChanged() {
            this.barangay = '';
            this.service = '';
            this.load();
        },
        // quiet (a live update): the figures on screen stay, not dimmed, until
        // the new ones come, and a failed reload keeps them.
        async load(quiet = false) {
            if (!this.office) { this.loading = false; return; }
            // A live reload never replaces a load the officer asked for (QA
            // 2026-11-18): it dropped the new period's answer, and when it then
            // failed the old figures stayed under the new period's name. It
            // runs once that load is done.
            if (quiet && this.loading) { this.liveAfter = true; return; }
            // Only the latest answer is shown: an earlier period's, arriving late, is dropped.
            const mine = ++this.seq;
            if (!quiet) {
                this.loading = true;
                this.failed = false;
            }
            try {
                const res = await api.get('/admin/dashboard/department', {
                    params: { office: this.office, barangay: this.barangay || '', from: this.from, to: this.to, ...this.filterParams() },
                });
                if (mine !== this.seq) return;
                this.data = res.data;
                this.failed = false;
                if (quiet) return;
                const entry = (this.subjects?.offices || []).find((o) => o.name === this.office);
                remember({ view: 'department', office: entry?.short || this.office, service: this.service, sex: this.sex, age: this.age, sector: this.sector, source: this.source });
            } catch (e) {
                if (mine !== this.seq || quiet) return;
                this.failed = true;
                this.error = e?.localised ? e.localised('en') : 'Could not load the dashboard.';
            } finally {
                if (mine === this.seq) {
                    this.loading = false;
                    this.$nextTick(() => window.renderIcons?.());
                    if (this.liveAfter) { this.liveAfter = false; this.load(true); }
                }
            }
        },
        get entry() { return (this.subjects?.offices || []).find((o) => o.name === this.office) || null; },
        get choosesOffice() { return (this.subjects?.offices || []).length > 1; },
        /** The office's programs and documents, for the Program filter. */
        get services() { return this.entry?.services || []; },
        get hasTulong() { return (this.data?.types || this.entry?.types || []).includes('tulong'); },
        get hasDokyu() { return (this.data?.types || this.entry?.types || []).includes('dokyu'); },
        get k() { return this.data?.kpis || {}; },
        get servedWord() { return this.hasTulong ? 'Clients served' : 'Documents released'; },
        // A head (one who approves) gets the approval count, staff their own
        // cases: kpis.ready_to_approve is sent only to an approver (18 Nov).
        get isHead() { return !!this.k.ready_to_approve; },
        get readyTotal() { return sum(this.k.ready_to_approve); },
        get readySub() {
            const r = this.k.ready_to_approve || {};
            return [this.hasDokyu ? 'Dokyu ' + num(r.dokyu) : null, this.hasTulong ? 'Tulong ' + num(r.tulong) : null].filter(Boolean).join(' · ');
        },
        get payments() { return this.data?.payments || null; },
        // Payments to verify take Received's place for an office that collects
        // fees (Dokyu only, the Treasurer), or when any wait or came in.
        get showPayments() {
            const p = this.payments;
            return !!p && (!this.hasTulong || p.awaiting_verification > 0 || p.collected > 0);
        },
        /** The list a head approves from, and an officer's own cases (the inbox's links). */
        get approvalUrl() {
            const r = this.k.ready_to_approve || {};
            return this.hasDokyu && (r.dokyu || !this.hasTulong)
                ? '/admin/document-requests?status=Under%20Verification%2CUnder%20Review%2CResubmitted'
                : '/admin/assistance-requests?status=For%20Approval';
        },
        get mineUrl() { return (this.hasTulong ? '/admin/assistance-requests' : '/admin/document-requests') + '?assigned=me'; },
        // "Where the open cases are": all of the office's, or the account's own.
        pipeMine: false,
        get hasMine() { return Array.isArray(this.data?.pipeline_mine); },
        get pipeline() {
            const src = this.pipeMine && this.hasMine ? this.data.pipeline_mine : (this.data?.pipeline || []);
            const both = this.hasTulong && this.hasDokyu;
            return bars(src.filter((r) => r.count > 0).map((r) => ({
                key: r.type + r.status, label: r.status, value: r.count, tag: both ? r.type : '',
                tone: r.needs_action ? 'orange' : 'brand', dot: r.needs_action ? 'orange' : 'slate',
            })));
        },
        /** Open cases per officer, with the overdue ones (heads: by_assignee). */
        get team() {
            return bars((this.data?.by_assignee || []).map((r) => ({
                key: String(r.officer_id ?? 'none'), label: r.name, value: r.open,
                sub: r.overdue ? plural(r.overdue, 'overdue case', 'overdue cases') : '', tone: r.officer_id ? 'brand' : 'gold',
            })));
        },
        get channels() { return channelRows(this.data?.by_channel); },
        get byBarangay() {
            return bars((this.data?.by_barangay || []).map((r) => ({
                label: 'Brgy. ' + r.barangay, value: r.served, text: num(r.served) + ' served',
                sub: [this.hasTulong && r.amount_released ? peso(r.amount_released) + ' released' : null, r.open ? r.open + ' still open' : null].filter(Boolean).join(' · '),
            }))).slice(0, 10);
        },
        get demo() { return demographics(this.data?.demographics); },
        get trend() {
            const t = this.data?.trend;
            if (!t) return { labels: [], series: [] };
            return {
                labels: t.months || [],
                series: [
                    { label: 'Received', values: t.received || [], color: '#2f62f5', fill: '#2f62f5', fillOpacity: 0.3 },
                    { label: this.hasTulong ? 'Served' : 'Released', values: t.served || [], color: '#e8c200', fill: '#f5cf00', fillOpacity: 0.42 },
                ],
            };
        },
        get barangays() { return this.subjects?.barangays?.length ? this.subjects.barangays : (this.data?.by_barangay || []).map((r) => r.barangay).sort(); },
        reports() {
            return (this.catalog?.reports || []).filter((r) => r.kind === 'department');
        },
        get may() {
            const o = (this.catalog?.offices || []).find((x) => x.name === this.office);
            return { view: !!o?.can_view, print: !!o?.can_print, export: !!o?.can_export };
        },
        mayOpen(r) { return r.with_names ? this.may.export : this.may.view; },
        /** The Summary Report, with graphs, as the dashboard is narrowed now. */
        get summary() { return this.reports().find((r) => r.key === 'dept_summary' && this.mayOpen(r)) || null; },
        print(key) {
            openReport({ key, office: this.office, barangay: this.barangay || '', from: this.from, to: this.to, ...this.filterParams() });
        },
        requestsUrl(r) {
            return (r.type === 'tulong' ? '/admin/assistance-requests' : '/admin/document-requests') + '?ref=' + encodeURIComponent(r.ref);
        },
    };
}

// --------------------------------------------------- The Tulong page's report

/**
 * Download Report on the Tulong page (owner, 2026-11-20: "sa tulong tab pde i
 * export ung report, un na ung standard template"): the office's Summary
 * Report (dept_summary, with its graphs and executive summary), narrowed to
 * Tulong, for a period, one program and the records' source. Imported past
 * records count by the day the office gave the assistance unless Source
 * leaves them out. Opens in the page's <x-admin.report-sheet>, where it is
 * previewed, then saved as PDF or Excel, or printed.
 */
export function tulongReport() {
    return {
        ...periodPicker(),
        open: false,
        catalog: null,
        failed: false,
        office: '',
        service: '',
        source: '',
        sourceOptions: asOptions(SOURCE_LABELS),
        init() {
            this.period = 'this_year';
            Object.assign(this, periodRange(this.period));
            this.load();
        },
        changed() {},
        async load() {
            // Only for an account with Reports, as the dashboards ask.
            if (!this.$store.session?.can?.('reports')) return;
            try {
                const res = await api.get('/admin/reports');
                this.catalog = res.meta?.printable || { reports: [], offices: [] };
                this.failed = false;
                if (!this.offices.some((o) => o.name === this.office)) {
                    this.office = (this.offices.find((o) => o.short === 'MSWDO') || this.offices[0])?.name || '';
                }
            } catch (e) {
                this.failed = true;
            }
        },
        /** Offices with Tulong programs whose reports this account may open. */
        get offices() {
            return (this.catalog?.offices || []).filter((o) => o.can_view && (o.types || []).includes('tulong'));
        },
        get services() {
            return ((this.offices.find((o) => o.name === this.office) || {}).services || []).filter((s) => s.type === 'tulong');
        },
        get allowed() {
            return (this.catalog?.reports || []).some((r) => r.key === 'dept_summary') && this.offices.length > 0;
        },
        officeChanged() { this.service = ''; },
        start() {
            this.open = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        preview() {
            if (!this.office || !this.from || !this.to) return;
            this.open = false;
            openReport({ key: 'dept_summary', office: this.office, from: this.from, to: this.to, type: 'tulong', service: this.service || '', source: this.source || '' });
        },
    };
}

// --------------------------------------------------- Barangay dashboard

export function brgyDashboard() {
    return {
        ...periodPicker(),
        barangay: null,
        data: null,
        loading: true,
        failed: false,
        error: '',
        seq: 0,
        liveAfter: false,
        peso,
        pesoTile,
        num,
        plural,
        info,
        init() {
            Object.assign(this, periodRange(this.period));
            const list = this.subjects?.barangays || [];
            const asked = query().get('barangay');
            this.barangay = list.find((b) => b.toLowerCase() === String(asked || '').toLowerCase()) || (list.includes('Poblacion') && list.length > 1 ? 'Poblacion' : list[0]) || null;
            this.load();
            // Live updates (2026-11-15): any change anywhere reloads it quietly.
            this.$store.live.on(this.$el, 'any', () => this.load(true));
        },
        changed() { this.load(); },
        // quiet: as the office dashboard's.
        async load(quiet = false) {
            if (!this.barangay) { this.loading = false; return; }
            if (quiet && this.loading) { this.liveAfter = true; return; }
            const mine = ++this.seq;
            if (!quiet) {
                this.loading = true;
                this.failed = false;
            }
            try {
                const res = await api.get('/admin/dashboard/barangay', { params: { barangay: this.barangay, from: this.from, to: this.to } });
                if (mine !== this.seq) return;
                this.data = res.data;
                this.failed = false;
                if (!quiet) remember({ view: 'barangay', barangay: (this.subjects?.barangays || []).length > 1 ? this.barangay : null });
            } catch (e) {
                if (mine !== this.seq || quiet) return;
                this.failed = true;
                this.error = e?.localised ? e.localised('en') : 'Could not load the dashboard.';
            } finally {
                if (mine === this.seq) {
                    this.loading = false;
                    this.$nextTick(() => window.renderIcons?.());
                    if (this.liveAfter) { this.liveAfter = false; this.load(true); }
                }
            }
        },
        get choosesBarangay() { return (this.subjects?.barangays || []).length > 1; },
        /** A barangay's own account (the Barangay Secretary): its own tiles. */
        get own() { return !this.choosesBarangay && !!this.barangay && this.$store.session.account?.scope === this.barangay; },
        get people() { return this.data?.people || null; },
        get docs() { return this.data?.documents || null; },
        get aid() { return this.data?.assistance || null; },
        get sakuna() { return this.data?.sakuna || null; },
        get docRows() { return bars((this.docs?.by_document || []).map((r) => ({ label: r.document, value: r.released }))); },
        /** Certificate requests by stage; orange where it waits on the barangay. */
        get stages() {
            return bars((this.docs?.pipeline || []).filter((r) => r.count > 0).map((r) => ({
                key: r.status, label: r.status, value: r.count, tone: r.needs_action ? 'orange' : 'brand', dot: r.needs_action ? 'orange' : 'slate',
            })));
        },
        get residentsDemo() { return demographics(this.people?.demographics); },
        get aidDemo() { return demographics(this.aid?.demographics); },
        // Each series only when the account may read its module (18 Nov).
        get trend() {
            const t = this.data?.trend;
            if (!t) return { labels: [], series: [] };
            return {
                labels: t.months || [],
                series: [
                    t.documents ? { label: 'Certificates released', values: t.documents, color: '#2f62f5', fill: '#2f62f5', fillOpacity: 0.3 } : null,
                    t.assistance ? { label: 'Assistance completed', values: t.assistance, color: '#e8c200', fill: '#f5cf00', fillOpacity: 0.42 } : null,
                ].filter(Boolean),
            };
        },
        reports() {
            return (this.catalog?.reports || []).filter((r) => r.kind === 'barangay' && r.can_view);
        },
        get summary() { return this.reports().find((r) => r.key === 'brgy_summary') || null; },
        print(key) {
            openReport({ key, barangay: this.barangay, from: this.from, to: this.to });
        },
    };
}

// ------------------------------------------------------ Area dashboards

// Balita, Sakuna, Records and the front desk (owner, 18 Nov): one scoped
// answer each, GET /admin/dashboard/summary?area=. A block the account may not
// read is absent from kpis, so its tile hides instead of showing a false 0.

const AREA_TOPICS = { balita: 'balita', sakuna: 'sakuna', records: 'residents', desk: ['requests', 'appointments', 'residents'] };

const POST_COLORS = { Draft: '#94a3b8', 'Pending Review': '#f59e0b', Scheduled: '#6366f1', Approved: '#10b981', Released: '#2f62f5', Rejected: '#f43f5e', Archived: '#cbd5e1' };
const LADDER_COLORS = { Unverified: '#94a3b8', 'For Validation': '#f59e0b', 'Partially Verified': '#6366f1', Verified: '#10b981' };
const KIND_WORDS = { post: 'Post', event: 'Event', banner: 'Banner' };
const twoSeries = (labels, a, b) => ({
    labels,
    series: [
        { ...a, color: '#2f62f5', fill: '#2f62f5', fillOpacity: 0.3 },
        b ? { ...b, color: '#e8c200', fill: '#f5cf00', fillOpacity: 0.42 } : null,
    ].filter((s) => s && Array.isArray(s.values)),
});
const daysWord = (d) => (Number(d) < 1 ? 'Today' : plural(d, 'day', 'days'));

const AREAS = {
    balita: {
        get editor() { return this.data?.mode === 'editor'; },
        get partsText() {
            const p = this.k.waiting_review_parts;
            if (!p) return '';
            // Only the kinds that wait: "1 post", not "1 post · 0 events · 0 banners".
            return [['posts', 'post', 'posts'], ['events', 'event', 'events'], ['banners', 'banner', 'banners']]
                .filter(([key]) => p[key] > 0).map(([key, one, many]) => plural(p[key], one, many)).join(' · ');
        },
        get statusRows() { return (this.charts.by_status || []).filter((r) => r.count > 0).map((r) => ({ label: r.label, value: r.count, color: POST_COLORS[r.status] || '#94a3b8' })); },
        get trend() {
            const t = this.charts.trend;
            return t ? twoSeries(t.months || [], { label: 'Written', values: t.written }, { label: 'Published', values: t.published }) : { labels: [], series: [] };
        },
        get hasTrend() { return this.trend.series.some((s) => s.values.some((v) => v > 0)); },
        get officeRows() { return bars((this.charts.by_office || []).map((r) => ({ label: r.office, value: r.count }))); },
        get categoryRows() { return bars((this.charts.by_category || []).map((r) => ({ label: r.category, value: r.count }))); },
        get topPosts() { return this.charts.top_posts || []; },
        get queue() { return this.lists.review_queue || []; },
        get sentBack() { return this.lists.sent_back || []; },
        get myWaiting() { return this.lists.my_waiting || []; },
        kindWord(r) { return KIND_WORDS[r.kind] || 'Post'; },
        /** Where an item opens: a post opens itself (its review, for a reviewer); events and banners their lists. */
        itemUrl(r, status = 'Pending Review') {
            if (r.kind === 'event') return '/admin/communications?tab=events&status=' + encodeURIComponent(status);
            if (r.kind === 'banner') return '/admin/communications/banners';
            return '/admin/communications?tab=balita&post=' + r.id;
        },
        waited: daysWord,
    },
    sakuna: {
        // Head wording for one who assigns incidents and publishes alerts;
        // staff see their own incidents and draft alerts for the head.
        get assigns() { return this.$store.session.canPerm('sakuna_incidents', 'assign'); },
        get publishes() { return this.$store.session.canPerm('sakuna_alerts', 'publish'); },
        get typeRows() { return (this.charts.by_type || []).filter((r) => r.count > 0).map((r) => ({ label: r.type, value: r.count })); },
        get trend() {
            const t = this.charts.trend || [];
            return { labels: t.map((r) => dayLabel(r.label)), bars: true, series: [{ label: 'Incidents', values: t.map((r) => r.count), color: '#f43f5e' }] };
        },
        get barangayRows() { return bars((this.charts.by_barangay || []).filter((r) => r.count > 0).map((r) => ({ label: 'Brgy. ' + r.barangay, value: r.count, tone: 'rose' }))).slice(0, 10); },
        get centerRows() {
            return (this.charts.centers || []).map((r) => ({
                key: r.name + r.barangay, label: r.name, value: r.families, pct: Math.min(100, Math.max(0, Math.round(r.pct || 0))),
                text: r.capacity ? Math.round(r.pct || 0) + '% full' : plural(r.individuals, 'person', 'persons'),
                sub: ['Brgy. ' + r.barangay, plural(r.families, 'family', 'families'), plural(r.individuals, 'person', 'persons'), r.capacity ? 'room for ' + num(r.capacity) : null, r.over_capacity ? 'Over capacity' : null].filter(Boolean).join(' · '),
                tone: r.over_capacity ? 'rose' : 'teal',
            }));
        },
        get sectorRows() { return bars((this.charts.damage_by_sector || []).map((r) => ({ label: r.sector, value: Number(r.estimated_cost) || 0, text: pesoTile(r.estimated_cost), sub: plural(r.reports, 'report', 'reports') }))); },
        get feed() { return this.lists.feed || []; },
        get alerts() { return this.lists.alerts || []; },
        feedText(f) { return f.to ? (f.from ? f.from + ' \u2192 ' + f.to : f.to) : String(f.verb || '').replace(/_/g, ' '); },
    },
    records: {
        get ladderRows() { return (this.charts.ladder || []).map((r) => ({ label: r.label, value: r.count, color: LADDER_COLORS[r.label] || '#94a3b8' })).filter((r) => r.value > 0); },
        get coverageRows() {
            return (this.charts.coverage_by_barangay || []).filter((r) => r.residents > 0).slice(0, 8).map((r) => ({
                key: r.name, label: 'Brgy. ' + r.name, value: r.pct, pct: Math.round(r.pct || 0), text: Math.round(r.pct || 0) + '%',
                sub: plural(r.residents, 'resident', 'residents'), tone: r.pct < 50 ? 'orange' : 'teal',
            }));
        },
        get trend() {
            const t = this.charts.trend;
            return t ? twoSeries(t.months || [], { label: 'Residents added', values: t.residents_added }, t.accounts_registered ? { label: 'Accounts registered', values: t.accounts_registered } : null) : { labels: [], series: [] };
        },
        get dqRows() { return bars((this.charts.dq_by_type || []).filter((r) => r.count > 0).map((r) => ({ label: r.type, value: r.count, tone: 'orange' }))); },
        get demo() { return demographics(this.charts.demographics); },
        get toReview() { return this.lists.to_review || []; },
        get dqMine() { return this.lists.dq_mine || []; },
        ladder(key) { return (this.k.ladder || {})[key] || 0; },
    },
    desk: {
        brgyTab: 'all',
        get waitingTotal() { return sum(this.k.waiting); },
        get waitingText() { return this.modules(this.k.waiting); },
        get readyTotal() { return sum(this.k.ready_to_claim); },
        /** "Dokyu 2 · Tulong 1", for the modules the account holds. */
        modules(o) {
            if (!o) return '';
            return [this.$store.session.can('dokyu') && 'dokyu' in o ? 'Dokyu ' + num(o.dokyu) : null, this.$store.session.can('tulong') && 'tulong' in o ? 'Tulong ' + num(o.tulong) : null].filter(Boolean).join(' · ');
        },
        apptCount(status) { return this.k.appointments_today?.by_status?.[status] || 0; },
        get channels() { return channelRows(this.charts.by_channel); },
        get daily() {
            const d = this.charts.daily;
            if (!d) return { labels: [], series: [] };
            const t = twoSeries((d.labels || []).map(dayLabel), this.$store.session.can('dokyu') ? { label: 'Dokyu', values: d.dokyu } : null, this.$store.session.can('tulong') ? { label: 'Tulong', values: d.tulong } : null);
            if (t.series.length === 1 && t.series[0].label === 'Tulong') Object.assign(t.series[0], { color: '#e8c200', fill: '#f5cf00', fillOpacity: 0.42 });
            return t;
        },
        get officeRows() { return bars((this.charts.open_by_office || []).filter((r) => r.open > 0).map((r) => ({ label: r.office, value: r.open }))); },
        get barangayRows() {
            const key = this.brgyTab;
            return bars((this.charts.open_by_barangay || []).map((r) => ({ label: 'Brgy. ' + r.barangay, value: (key === 'dokyu' ? r.dokyu : key === 'tulong' ? r.tulong : r.open) || 0 })).filter((r) => r.value > 0));
        },
        get readyList() { return this.lists.ready_to_claim || []; },
        get appointments() { return this.lists.appointments_today || []; },
        requestUrl(r) { return (r.type === 'tulong' ? '/admin/assistance-requests' : '/admin/document-requests') + '?ref=' + encodeURIComponent(r.ref); },
        since: shortDate,
    },
};

export function areaDashboard(area) {
    const board = {
        ...periodPicker(),
        area,
        barangay: '',
        data: null,
        loading: true,
        failed: false,
        error: '',
        seq: 0,
        liveAfter: false,
        peso,
        pesoTile,
        num,
        plural,
        shortDate,
        init() {
            Object.assign(this, periodRange(this.period));
            const asked = query().get('barangay');
            if (asked && this.choosesBarangay && this.barangayChoices.includes(asked)) this.barangay = asked;
            if (this.brgyTab !== undefined) this.brgyTab = this.$store.session.can('dokyu') ? 'dokyu' : (this.$store.session.can('tulong') ? 'tulong' : 'all');
            this.load();
            // Live updates: the area's own topics reload it quietly.
            this.$store.live.on(this.$el, AREA_TOPICS[area], () => this.load(true));
        },
        changed() { this.load(); },
        // quiet: as the office dashboard's (a live reload waits for the load the officer asked for).
        async load(quiet = false) {
            if (quiet && this.loading) { this.liveAfter = true; return; }
            const mine = ++this.seq;
            if (!quiet) {
                this.loading = true;
                this.failed = false;
            }
            try {
                const params = { area, from: this.from, to: this.to };
                if (this.barangay) params.barangay = this.barangay;
                const res = await api.get('/admin/dashboard/summary', { params });
                if (mine !== this.seq) return;
                this.data = res.data;
                this.failed = false;
                if (!quiet) remember({ barangay: this.barangay || null });
            } catch (e) {
                if (mine !== this.seq || quiet) return;
                this.failed = true;
                this.error = e?.localised ? e.localised('en') : 'Could not load the dashboard.';
            } finally {
                if (mine === this.seq) {
                    this.loading = false;
                    this.$nextTick(() => window.renderIcons?.());
                    if (this.liveAfter) { this.liveAfter = false; this.load(true); }
                }
            }
        },
        get k() { return this.data?.kpis || {}; },
        get charts() { return this.data?.charts || {}; },
        get lists() { return this.data?.lists || {}; },
        /** Whether the server sent a figure (absent: the account may not read it). */
        has(key) { return !!this.data && this.k[key] !== undefined && this.k[key] !== null; },
        get barangayChoices() { return this.subjects?.filter_barangays?.length ? this.subjects.filter_barangays : (this.subjects?.barangays || []); },
        /** A barangay account's own barangay: no picker, its name in the title. */
        get fixedBarangay() { return this.barangayChoices.length === 1 ? this.barangayChoices[0] : null; },
        // A writer's own posts are rarely for one barangay: no picker there (plan 6.8).
        get choosesBarangay() { return this.barangayChoices.length > 1 && !(area === 'balita' && this.data?.mode === 'writer'); },
        get scopeTitle() {
            const b = this.data?.barangay || this.fixedBarangay || this.barangay;
            return b ? 'Barangay ' + b : 'All barangays';
        },
        get periodText() { return this.data ? 'Period: ' + this.data.period.label : ''; },
    };

    return mix(board, AREAS[area] || {});
}

// ------------------------------------------------- The Reports page part

/**
 * The Reports page's "Printable Reports": pick the office or barangay and the
 * period, then preview, print or save to Excel.
 */
export function printableReports() {
    return {
        ...periodPicker(),
        ...reportFilters(),
        catalog: { reports: [], offices: [], barangays: [] },
        office: null,
        barangay: null,
        ready: false,
        info,
        init() {
            Object.assign(this, periodRange(this.period));
        },
        setCatalog(c) {
            this.catalog = c || this.catalog;
            const offices = this.catalog.offices || [];
            this.office = (offices.find((o) => o.short === 'MSWDO') || offices[0])?.name || null;
            const brgys = this.catalog.barangays || [];
            this.barangay = brgys.length > 1 && brgys.includes('Poblacion') ? 'Poblacion' : (brgys[0] || null);
            this.ready = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        get departmentReports() { return (this.catalog.reports || []).filter((r) => r.kind === 'department'); },
        get barangayReports() { return (this.catalog.reports || []).filter((r) => r.kind === 'barangay'); },
        get officeEntry() { return (this.catalog.offices || []).find((o) => o.name === this.office) || null; },
        get services() { return this.officeEntry?.services || []; },
        mayOpen(r) {
            if (r.kind === 'barangay') return !!r.can_view;
            return r.with_names ? !!this.officeEntry?.can_export : !!this.officeEntry?.can_view;
        },
        titleOf(r) {
            if (r.title) return r.title;
            return (this.officeEntry?.types || []).includes('tulong') ? 'Report of Assistance Extended' : 'Report of Documents Issued';
        },
        open(r) {
            const dept = r.kind === 'department';
            openReport({ key: r.key, office: dept ? this.office : undefined, barangay: dept ? undefined : this.barangay, from: this.from, to: this.to, ...(dept ? this.filterParams() : {}) });
        },
    };
}

// ----------------------------------------------------- The report sheet

const LANDSCAPE = ['dept_masterlist', 'dept_pending'];

/**
 * The preview of one printed report: the LGU letterhead, the title, the
 * period covered, its tables with their totals, and who signs. The names on
 * the signature lines can be changed before printing (an officer-in-charge
 * signs for an absent head); nothing typed there is saved.
 */
export function reportSheet() {
    return {
        open: false,
        loading: false,
        failed: false,
        error: '',
        busy: null,
        params: null,
        report: null,
        signatories: [],
        seq: 0,
        chartSvg,
        // Only the latest report asked for is shown (QA 2026-11-18): a slower
        // earlier one arriving last replaced it, and Print, PDF and Excel then
        // sent that report's key with the other one's filters.
        async show(detail) {
            const mine = ++this.seq;
            this.params = detail;
            this.open = true;
            this.loading = true;
            this.failed = false;
            this.report = null;
            try {
                const res = await api.get('/admin/reports/' + detail.key, { params: this.query() });
                if (mine !== this.seq) return;
                this.report = res.data;
                this.signatories = (res.data.signatories || []).map((s) => ({ ...s, name: s.name || '', position: s.position || '' }));
            } catch (e) {
                if (mine !== this.seq) return;
                this.failed = true;
                this.error = e?.localised ? e.localised('en') : 'Could not open the report.';
            } finally {
                if (mine === this.seq) {
                    this.loading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            }
        },
        query() {
            const p = this.params || {};
            const out = { from: p.from, to: p.to };
            ['office', 'barangay', 'service', 'type', 'sex', 'age', 'sector', 'source'].forEach((k) => { if (p[k]) out[k] = p[k]; });
            return out;
        },
        /** "Medical Assistance (AICS) · Female", when an office report is narrowed. */
        get filterText() { return this.report?.filters?.label || ''; },
        get landscape() { return LANDSCAPE.includes(this.report?.key); },
        get isBarangay() { return this.report?.kind === 'barangay'; },
        get officeLine() {
            const s = this.report?.subject || {};
            if (this.isBarangay) return 'Barangay ' + (s.barangay || '');
            return s.office || '';
        },
        get subOfficeLine() {
            const s = this.report?.subject || {};
            if (this.isBarangay) return 'Office of the Punong Barangay';
            return s.barangay ? 'Barangay ' + s.barangay + ' only' : '';
        },
        get periodText() {
            if (!this.report) return '';
            return this.report.as_of ? 'As of ' + this.report.as_of : this.report.period?.label;
        },
        get generatedText() {
            if (!this.report) return '';
            const at = new Date(this.report.generated_at).toLocaleString('en-US', { timeZone: TZ, month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
            return 'Generated from the Esperanza eGov Platform on ' + at + ' by ' + (this.report.generated_by || '') + '.';
        },
        align(c) {
            if (['int', 'money', 'text_right'].includes(c.format)) return 'rep-num';
            return c.format === 'date' || ['ref', 'or_no', 'no', 'sex', 'status', 'waiting_on', 'overdue'].includes(c.key) ? 'rep-nowrap' : '';
        },
        cell(v, c) {
            if (v === null || v === undefined || v === '') return '';
            if (c.format === 'money') return typeof v === 'number' ? Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : v;
            if (c.format === 'int') return typeof v === 'number' ? v.toLocaleString('en-PH') : v;
            // A totals cell under a date column holds a word ("Total"), not a date.
            if (c.format === 'date') return /^\d{4}-\d{2}-\d{2}/.test(String(v)) ? shortDate(v) : v;
            return v;
        },
        total(s, c) {
            if (!s.totals || s.totals[c.key] === undefined || s.totals[c.key] === null) return '';
            return this.cell(s.totals[c.key], c);
        },
        /** Logged first (who printed what, for whom, how many rows), then the browser prints. */
        async print() {
            if (this.busy || !this.report?.can_print) return;
            this.busy = 'print';
            try {
                await api.post('/admin/reports/' + this.report.key + '/print', this.query());
                document.body.classList.add('dp-printing');
                await this.$nextTick();
                window.print();
            } catch (e) {
                this.$dispatch('toast', { message: e?.localised ? e.localised('en') : 'Could not print the report.', variant: 'error' });
            } finally {
                this.busy = null;
            }
        },
        /**
         * The printed copy as a PDF file (owner, 2026-10-15: PDF as well as
         * Excel). Recorded like a print; drawn as the Dokyu document download
         * draws its paper (html2pdf on a copy, oklch() colours made rgb()).
         */
        async pdf() {
            if (this.busy || !this.report?.can_print) return;
            this.busy = 'pdf';
            const editor = window.DokyuEditor || {};
            let restoreColors = () => {};
            try {
                await api.post('/admin/reports/' + this.report.key + '/print', this.query());
                const html2pdf = await window.loadHtml2Pdf();
                const paper = document.querySelector('#dp-print-area .rep-paper') || document.querySelector('.rep-paper');
                if (!paper) throw new Error('no report on screen');
                if (editor.waitForImages) await editor.waitForImages(paper);
                if (editor.sanitizeExportColors) restoreColors = editor.sanitizeExportColors(document.body);
                // Every page gets its own top and bottom margin, as the printed
                // copy does (2026-11-03: page two began at the very top edge, the
                // margin having been the paper's own padding, on page one only).
                // The paper keeps its side padding; each page is numbered.
                const copy = editor.exportClone ? editor.exportClone(paper) : paper.cloneNode(true);
                Object.assign(copy.style, { paddingTop: '0', paddingBottom: '0', boxShadow: 'none', borderRadius: '0' });
                await html2pdf().set({
                    margin: this.landscape ? [10, 0, 12, 0] : [12, 0, 14, 0],
                    filename: (this.report.key || 'report') + '-' + new Date().toISOString().slice(0, 10) + '.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    // Text sat on each cell's bottom border (2026-11-20): html2canvas
                    // places text too low while images are display:block (Tailwind's
                    // preflight); inline-block in its copy puts it where the browser does.
                    html2canvas: { scale: 2, useCORS: true, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff', onclone: (doc) => { const st = doc.createElement('style'); st.textContent = 'img { display: inline-block; }'; doc.head.appendChild(st); } },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: this.landscape ? 'landscape' : 'portrait', compress: true },
                    pagebreak: { mode: ['css', 'legacy'] },
                }).from(copy).toPdf().get('pdf').then((doc) => {
                    const pages = doc.internal.getNumberOfPages();
                    const w = doc.internal.pageSize.getWidth();
                    const h = doc.internal.pageSize.getHeight();
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(8);
                    doc.setTextColor(85, 85, 85);
                    for (let i = 1; i <= pages; i += 1) {
                        doc.setPage(i);
                        doc.text('Page ' + i + ' of ' + pages, w - 14, h - 6, { align: 'right' });
                    }
                }).save();
            } catch (e) {
                this.$dispatch('toast', { message: e?.localised ? e.localised('en') : 'Could not make the PDF. Use Print and choose Save as PDF.', variant: 'error' });
            } finally {
                restoreColors();
                this.busy = null;
            }
        },
        /** The same report as an Excel workbook, laid out like the printed copy. Logged as an export. */
        async excel() {
            if (this.busy || !this.report?.can_export) return;
            this.busy = 'excel';
            try {
                const res = await api.post('/admin/reports/' + this.report.key + '/export', { ...this.query(), format: 'XLSX' });
                const { S, colName, downloadXlsx } = await import('./xlsx-report');
                const r = res.data;
                const aoa = [];
                const money = [];
                const paras = [];
                const head = ['Republic of the Philippines', 'Province of Masbate', 'Municipality of Esperanza', this.officeLine];
                if (this.subOfficeLine) head.push(this.subOfficeLine);
                head.forEach((h) => aoa.push([h]));
                aoa.push([]);
                aoa.push([String(r.title || '').toUpperCase()]);
                aoa.push([r.as_of ? 'As of ' + r.as_of : 'Period Covered: ' + (r.period?.label || '')]);
                if (r.filters?.label) aoa.push(['Narrowed to: ' + r.filters.label]);
                if (r.filters?.note) aoa.push([r.filters.note]);
                aoa.push([]);
                // The executive summary, each paragraph one cell merged across the
                // table's width and wrapped (2026-11-20: a line to a row broke
                // sentences across rows).
                if ((r.summary || []).length) {
                    aoa.push(['Executive Summary']);
                    r.summary.forEach((p) => {
                        paras.push(aoa.length);
                        aoa.push([{ v: String(p), s: S.para }]);
                        aoa.push([]);
                    });
                }
                (r.sections || []).forEach((s) => {
                    if (s.title) aoa.push([s.title]);
                    aoa.push(s.columns.map((c) => c.label));
                    (s.rows || []).forEach((row) => {
                        s.columns.forEach((c, ci) => { if (c.format === 'money') money.push([aoa.length, ci]); });
                        aoa.push(s.columns.map((c) => (c.format === 'date' ? row[c.key] || '' : row[c.key] ?? '')));
                    });
                    if (s.totals) {
                        s.columns.forEach((c, ci) => { if (c.format === 'money') money.push([aoa.length, ci]); });
                        aoa.push(s.columns.map((c) => s.totals[c.key] ?? ''));
                    }
                    if (s.note) aoa.push([s.note]);
                    aoa.push([]);
                });
                aoa.push([]);
                aoa.push(this.signatories.map((s) => s.role + ':'));
                aoa.push([]);
                aoa.push(this.signatories.map((s) => String(s.name || '').toUpperCase()));
                aoa.push(this.signatories.map((s) => s.position || ''));
                // Every amount a number in the peso format (the months showed plain numbers).
                money.forEach(([row, col]) => {
                    const v = aoa[row][col];
                    if (v !== '' && v !== null && Number.isFinite(Number(v))) aoa[row][col] = { v: Number(v), s: S.peso };
                });
                const widest = Math.max(...(r.sections || []).map((s) => s.columns.length), 3);
                const cols = Array.from({ length: widest }, (_, i) => (i === 0 ? 34 : 18));
                const across = cols.reduce((a, b) => a + b, 0) - 4;
                const heights = {};
                paras.forEach((row) => { heights[row] = Math.max(18, Math.ceil(aoa[row][0].v.length / across) * 15 + 6); });
                const merges = paras.map((row) => 'A' + (row + 1) + ':' + colName(widest - 1) + (row + 1));
                const who = (r.subject?.short || r.subject?.barangay || 'report').replace(/[^a-z0-9]+/gi, '-');
                await downloadXlsx([{ name: 'Report', rows: aoa, cols, merges, heights }], [r.key, who, r.period?.from, r.period?.to].filter(Boolean).join('_') + '.xlsx', { title: r.title });
                this.$dispatch('toast', { message: 'Saved to Excel. The copy is recorded under your name.', variant: 'success' });
            } catch (e) {
                this.$dispatch('toast', { message: e?.localised ? e.localised('en') : 'Could not save the report.', variant: 'error' });
            } finally {
                this.busy = null;
            }
        },
    };
}

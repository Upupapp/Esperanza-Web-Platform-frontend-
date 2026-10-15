import api from './api/client';
import { loadXlsx } from './resident-import';

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

/** Bars relative to the largest, for the pressed-track bar lists. */
function bars(rows) {
    const max = Math.max(1, ...rows.map((r) => r.value));
    return rows.map((r) => ({ ...r, pct: Math.round((r.value / max) * 100) }));
}

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

/**
 * The Dashboard page's tabs: the overview every account has, the office
 * dashboards and the barangay dashboards. A department account lands on its
 * office, a barangay account on its barangay (GET /admin/dashboard/subjects).
 */
export function dashboardHost() {
    return {
        view: 'overview',
        subjects: { home: 'overview', offices: [], barangays: [] },
        catalog: { reports: [], offices: [], barangays: [] },
        subjectsReady: false,
        async init() {
            try {
                const [subjects, catalog] = await Promise.all([
                    api.get('/admin/dashboard/subjects'),
                    this.$store.session.can('reports') ? api.get('/admin/reports').catch(() => null) : Promise.resolve(null),
                ]);
                this.subjects = subjects.data;
                this.catalog = catalog?.meta?.printable || this.catalog;
            } catch {
                // The overview still works; the other tabs simply do not show.
            }
            const asked = query().get('view');
            const allowed = ['overview', this.subjects.offices.length ? 'department' : null, this.subjects.barangays.length ? 'barangay' : null].filter(Boolean);
            this.view = allowed.includes(asked) ? asked : (allowed.includes(this.subjects.home) ? this.subjects.home : 'overview');
            this.subjectsReady = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        get officeTabLabel() {
            return this.subjects.home === 'department' ? 'My Office' : 'Departments';
        },
        get barangayTabLabel() {
            return this.subjects.barangays.length === 1 ? 'My Barangay' : 'Barangays';
        },
        show(view) {
            this.view = view;
            remember({ view: view === 'overview' ? null : view, office: null, barangay: null });
            this.$nextTick(() => window.renderIcons?.());
        },
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
        office: null,
        barangay: '',
        data: null,
        loading: true,
        failed: false,
        error: '',
        seq: 0,
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
            this.load();
        },
        changed() { this.load(); },
        async load() {
            if (!this.office) { this.loading = false; return; }
            // Only the latest answer is shown: an earlier period's, arriving late, is dropped.
            const mine = ++this.seq;
            this.loading = true;
            this.failed = false;
            try {
                const res = await api.get('/admin/dashboard/department', {
                    params: { office: this.office, barangay: this.barangay || '', from: this.from, to: this.to },
                });
                if (mine !== this.seq) return;
                this.data = res.data;
                const entry = (this.subjects?.offices || []).find((o) => o.name === this.office);
                remember({ view: 'department', office: entry?.short || this.office });
            } catch (e) {
                if (mine !== this.seq) return;
                this.failed = true;
                this.error = e?.localised ? e.localised('en') : 'Could not load the dashboard.';
            } finally {
                if (mine === this.seq) {
                    this.loading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            }
        },
        get entry() { return (this.subjects?.offices || []).find((o) => o.name === this.office) || null; },
        get choosesOffice() { return (this.subjects?.offices || []).length > 1; },
        get hasTulong() { return (this.data?.types || this.entry?.types || []).includes('tulong'); },
        get hasDokyu() { return (this.data?.types || this.entry?.types || []).includes('dokyu'); },
        get k() { return this.data?.kpis || {}; },
        get servedWord() { return this.hasTulong ? 'Clients served' : 'Documents released'; },
        get pipeline() {
            const rows = (this.data?.pipeline || []).filter((r) => r.count > 0);
            const max = Math.max(1, ...rows.map((r) => r.count));
            return rows.map((r) => ({ ...r, pct: Math.round((r.count / max) * 100) }));
        },
        get byBarangay() {
            return bars((this.data?.by_barangay || []).map((r) => ({ label: r.barangay, value: r.served, open: r.open, amount: r.amount_released }))).slice(0, 10);
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
        print(key) {
            openReport({ key, office: this.office, barangay: this.barangay || '', from: this.from, to: this.to });
        },
        requestsUrl(r) {
            return (r.type === 'tulong' ? '/admin/assistance-requests' : '/admin/document-requests') + '?ref=' + encodeURIComponent(r.ref);
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
        },
        changed() { this.load(); },
        async load() {
            if (!this.barangay) { this.loading = false; return; }
            const mine = ++this.seq;
            this.loading = true;
            this.failed = false;
            try {
                const res = await api.get('/admin/dashboard/barangay', { params: { barangay: this.barangay, from: this.from, to: this.to } });
                if (mine !== this.seq) return;
                this.data = res.data;
                remember({ view: 'barangay', barangay: (this.subjects?.barangays || []).length > 1 ? this.barangay : null });
            } catch (e) {
                if (mine !== this.seq) return;
                this.failed = true;
                this.error = e?.localised ? e.localised('en') : 'Could not load the dashboard.';
            } finally {
                if (mine === this.seq) {
                    this.loading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            }
        },
        get choosesBarangay() { return (this.subjects?.barangays || []).length > 1; },
        get people() { return this.data?.people || null; },
        get docs() { return this.data?.documents || null; },
        get aid() { return this.data?.assistance || null; },
        get sakuna() { return this.data?.sakuna || null; },
        get docRows() { return bars((this.docs?.by_document || []).map((r) => ({ label: r.document, value: r.released }))); },
        get residentsDemo() { return demographics(this.people?.demographics); },
        get aidDemo() { return demographics(this.aid?.demographics); },
        get trend() {
            const t = this.data?.trend;
            if (!t) return { labels: [], series: [] };
            return {
                labels: t.months || [],
                series: [
                    { label: 'Certificates released', values: t.documents || [], color: '#2f62f5', fill: '#2f62f5', fillOpacity: 0.3 },
                    { label: 'Assistance completed', values: t.assistance || [], color: '#e8c200', fill: '#f5cf00', fillOpacity: 0.42 },
                ],
            };
        },
        reports() {
            return (this.catalog?.reports || []).filter((r) => r.kind === 'barangay' && r.can_view);
        },
        print(key) {
            openReport({ key, barangay: this.barangay, from: this.from, to: this.to });
        },
    };
}

// ------------------------------------------------- The Reports page part

/**
 * The Reports page's "Printable Reports": pick the office or barangay and the
 * period, then preview, print or save to Excel.
 */
export function printableReports() {
    return {
        ...periodPicker(),
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
        mayOpen(r) {
            if (r.kind === 'barangay') return !!r.can_view;
            return r.with_names ? !!this.officeEntry?.can_export : !!this.officeEntry?.can_view;
        },
        titleOf(r) {
            if (r.title) return r.title;
            return (this.officeEntry?.types || []).includes('tulong') ? 'Report of Assistance Extended' : 'Report of Documents Issued';
        },
        open(r) {
            openReport({ key: r.key, office: r.kind === 'department' ? this.office : undefined, barangay: r.kind === 'barangay' ? this.barangay : undefined, from: this.from, to: this.to });
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
        async show(detail) {
            this.params = detail;
            this.open = true;
            this.loading = true;
            this.failed = false;
            this.report = null;
            try {
                const res = await api.get('/admin/reports/' + detail.key, { params: this.query() });
                this.report = res.data;
                this.signatories = (res.data.signatories || []).map((s) => ({ ...s, name: s.name || '', position: s.position || '' }));
            } catch (e) {
                this.failed = true;
                this.error = e?.localised ? e.localised('en') : 'Could not open the report.';
            } finally {
                this.loading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },
        query() {
            const p = this.params || {};
            const out = { from: p.from, to: p.to };
            if (p.office) out.office = p.office;
            if (p.barangay) out.barangay = p.barangay;
            return out;
        },
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
                await html2pdf().set({
                    margin: 0,
                    filename: (this.report.key || 'report') + '-' + new Date().toISOString().slice(0, 10) + '.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff' },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: this.landscape ? 'landscape' : 'portrait', compress: true },
                    pagebreak: { mode: ['css', 'legacy'] },
                }).from(editor.exportClone ? editor.exportClone(paper) : paper).save();
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
                const XLSX = await loadXlsx();
                const r = res.data;
                const aoa = [];
                const money = [];
                const head = ['Republic of the Philippines', 'Province of Masbate', 'Municipality of Esperanza', this.officeLine];
                if (this.subOfficeLine) head.push(this.subOfficeLine);
                head.forEach((h) => aoa.push([h]));
                aoa.push([]);
                aoa.push([String(r.title || '').toUpperCase()]);
                aoa.push([r.as_of ? 'As of ' + r.as_of : 'Period Covered: ' + (r.period?.label || '')]);
                aoa.push([]);
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
                const ws = XLSX.utils.aoa_to_sheet(aoa);
                money.forEach(([row, col]) => {
                    const ref = XLSX.utils.encode_cell({ r: row, c: col });
                    if (ws[ref] && typeof ws[ref].v === 'number') ws[ref].z = '#,##0.00';
                });
                const widest = Math.max(...(r.sections || []).map((s) => s.columns.length), 3);
                ws['!cols'] = Array.from({ length: widest }, (_, i) => ({ wch: i === 0 ? 34 : 18 }));
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Report');
                const who = (r.subject?.short || r.subject?.barangay || 'report').replace(/[^a-z0-9]+/gi, '-');
                XLSX.writeFile(wb, [r.key, who, r.period?.from, r.period?.to].filter(Boolean).join('_') + '.xlsx');
                this.$dispatch('toast', { message: 'Saved to Excel. The copy is recorded under your name.', variant: 'success' });
            } catch (e) {
                this.$dispatch('toast', { message: e?.localised ? e.localised('en') : 'Could not save the report.', variant: 'error' });
            } finally {
                this.busy = null;
            }
        },
    };
}

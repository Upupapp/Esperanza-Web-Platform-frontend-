// Sakuna's Reports tab (owner, 2026-10-28/30). Every figure, chart and table
// row comes from the server's one calculation (GET /admin/sakuna/reports/
// summary and /incidents) under the same filters, so the cards, the charts,
// the table and the exports agree. Export is its own permission, checked and
// logged on the server (POST /admin/sakuna/reports/export); the file is built
// here from what that returns, as the other exports are.
import api from './api/client';
// The export's summary, charts and workbook writer load only when an export is asked for.
const loadInsights = () => import('./sakuna-insights');
const loadWorkbook = () => import('./xlsx-report');

// Incidents speak their own words for the universal statuses (sakuna.blade.php incidentStatusLabel).
const incidentWords = { Submitted: 'Reported', 'Under Verification': 'Validated', Processing: 'Dispatched', Completed: 'Resolved', Archived: 'Closed' };
// case_kind (sheet item 13, owner 2026-11-05): the Missing Persons tab's kinds of case.
const caseKinds = { missing: 'Missing persons', deceased: 'Deceased persons', unidentified: 'Unidentified bodies' };
const blankFilters = () => ({ barangay: '', from: '', to: '', type: '', status: '', case_kind: '', livelihood_group: '', livelihood_code: '' });
// The person figures (missing, found, deceased, unidentified) come only to
// whoever may see Missing Persons while it is on (2026-11-05): without them the
// summary has no persons block (or null) and the rows no person columns, and
// every tile, column, chart, table and export line about them is left out.
export const hasPersons = (summary) => !!summary?.persons && typeof summary.persons === 'object';
const toast = (message, variant = 'success') => window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));

export function sakunaReport() {
    return {
        f: blankFilters(),
        summary: null,
        rows: [],
        page: 1,
        lastPage: 1,
        total: 0,
        loading: false,
        failed: false,
        exporting: '',

        init() {
            this.$store.livelihood?.load();
            this.load();
            // Missing Persons learned off elsewhere on the page: drop a case kind already chosen.
            window.addEventListener('sakuna-missing-off', () => { if (this.f.case_kind) { this.f.case_kind = ''; this.load(1); } });
        },
        /** A case kind sent while Missing Persons is off: the server says FEATURE_OFF. Cleared, not an error. */
        caseKindOff(err) {
            if (err?.code !== 'FEATURE_OFF' || !this.f.case_kind) return false;
            this.f.case_kind = '';
            this.markMissingOff?.();
            return true;
        },
        params(extra = {}) {
            return Object.fromEntries(Object.entries({ ...this.f, ...extra }).filter(([, v]) => v !== '' && v !== null));
        },
        get rangeError() { return this.f.from && this.f.to && this.f.to < this.f.from ? 'The end date is before the start date.' : ''; },
        async load(page = 1) {
            if (this.rangeError) return;
            this.loading = true;
            this.failed = false;
            this.page = page;
            let again = false;
            try {
                const [s, r] = await Promise.all([
                    api.get('/admin/sakuna/reports/summary', { params: this.params() }),
                    api.get('/admin/sakuna/reports/incidents', { params: this.params({ page, per_page: 25 }) }),
                ]);
                this.summary = s.data;
                this.rows = r.data || [];
                this.total = r.meta?.total ?? this.rows.length;
                this.lastPage = r.meta?.last_page || 1;
            } catch (err) {
                if (this.caseKindOff(err)) again = true; else this.failed = true;
            } finally {
                this.loading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
            if (again) this.load(1);
        },
        clear() { this.f = blankFilters(); this.rangeOpen = false; this.load(1); },

        // The date range (Sakuna Redesign, 2026-11-02): one button in the
        // filter row opens From/To and the presets; nothing loads until Apply,
        // and Cancel puts the dates back as they were.
        rangeOpen: false,
        rangeSnap: null,
        rangePos: '',
        get rangeLabel() {
            const fmt = (d, year) => new Date(d + 'T00:00:00').toLocaleDateString('en-US', year ? { month: 'short', day: 'numeric', year: 'numeric' } : { month: 'short', day: 'numeric' });
            const { from, to } = this.f;
            if (from && to) return fmt(from, from.slice(0, 4) !== to.slice(0, 4)) + ' – ' + fmt(to, true);
            if (from) return 'From ' + fmt(from, true);
            if (to) return 'Until ' + fmt(to, true);
            return 'All dates';
        },
        openRange(btn) {
            if (this.rangeOpen) return this.cancelRange();
            this.rangeSnap = { from: this.f.from, to: this.f.to };
            // Placed under the button, in the card, so the row's scroll cannot clip it.
            this.rangePos = 'left:' + Math.max(0, btn.offsetLeft - btn.parentElement.scrollLeft) + 'px;top:' + (btn.offsetTop + btn.offsetHeight + 6) + 'px';
            this.rangeOpen = true;
        },
        cancelRange() {
            if (!this.rangeOpen) return;
            if (this.rangeSnap) Object.assign(this.f, this.rangeSnap);
            this.rangeOpen = false;
        },
        applyRange() {
            if (this.rangeError) return;
            this.rangeOpen = false;
            this.load(1);
        },
        presetRange(kind) {
            const iso = (d) => [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-');
            const today = new Date();
            const back = (days) => { const d = new Date(today); d.setDate(d.getDate() - days); return iso(d); };
            const ranges = {
                week: [back(6), iso(today)],
                month30: [back(29), iso(today)],
                thisMonth: [iso(new Date(today.getFullYear(), today.getMonth(), 1)), iso(today)],
                all: ['', ''],
            };
            [this.f.from, this.f.to] = ranges[kind];
            this.applyRange();
        },
        // A group change keeps the item only if it belongs to the group.
        groupChanged() { if (this.f.livelihood_code && !this.f.livelihood_code.startsWith(this.f.livelihood_group)) this.f.livelihood_code = ''; this.load(1); },

        // Charts, in the site's one look (clay-donut, clay-trend), from the
        // server's figures: nothing is counted in the browser.
        // A disaster type's own colour, so Fire reads as fire on every chart.
        incidentTypeColor(type) {
            return ({ Fire: '#EA580C', Flood: '#2563EB', Landslide: '#92400E', Earthquake: '#7C3AED', 'Vehicular Accident': '#475569',
                'Medical Emergency': '#DB2777', 'Missing Person': '#C2410C', 'Rescue Request': '#0E7490', 'Drowning or Water Rescue': '#0369A1',
                'Structural Damage': '#78716C', 'Agricultural Damage': '#15803D' })[type] || '#64748B';
        },
        reportTypeBreakdown() { return Object.entries(this.summary?.incidents.by_type || {}); },
        reportTrendLabels() { return (this.summary?.incidents.trend || []).map((t) => new Date(t.label + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })); },
        reportTrendData() { return (this.summary?.incidents.trend || []).map((t) => t.count); },
        // The chart names each status as the Incidents tab does (critique 2026-11-03).
        statusRows() {
            const tone = { Submitted: '#2563EB', 'Under Verification': '#4F46E5', Assigned: '#7C3AED', Processing: '#EA580C', Completed: '#059669', Archived: '#64748B', Rejected: '#E11D48' };
            return Object.entries(this.summary?.incidents.by_status || {}).map(([label, value]) => ({ label: incidentWords[label] || label, value, color: tone[label] || '#94A3B8' }));
        },
        livelihoodRows() { return (this.summary?.livelihood.groups || []).filter((g) => g.people).map((g) => ({ label: g.code + ' · ' + g.label, value: g.people })); },
        barangayRows() {
            const rows = Object.entries(this.summary?.incidents.by_barangay || {});
            const max = Math.max(1, ...rows.map((r) => r[1]));
            return rows.map(([label, count]) => ({ label, count, pct: Math.round((count / max) * 100) }));
        },

        async exportAs(format) {
            if (this.exporting || this.rangeError) return;
            this.exporting = format;
            try {
                const res = await api.post('/admin/sakuna/reports/export', { ...this.params(), format });
                const d = res.data;
                if (format === 'XLSX') await this.writeXlsx(d); else await this.writePdf(d);
                toast(format + ' downloaded.');
            } catch (err) {
                if (this.caseKindOff(err)) {
                    toast('Missing Persons is not switched on here, so the case kind filter is gone. Export again for the whole report.', 'info');
                    this.load(1);
                } else toast(err?.localised ? err.localised('en') : 'Could not export this report.', 'error');
            } finally {
                this.exporting = '';
            }
        },
        filterLines(d) {
            const f = d.filters || {};
            return [
                ['Report', d.title],
                ['Generated', new Date(d.generated_at).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) + ' by ' + d.generated_by],
                ['Period', this.periodText(d.summary.period)],
                ['Barangay', f.barangay || 'All barangays'],
                ['Disaster type', f.type || 'All types'],
                ['Incident status', f.status ? (incidentWords[f.status] || f.status) : 'All statuses'],
                // Only for whoever sees the Missing Persons tab, as the filter is.
                ...(f.case_kind || (this.permsReady && this.tabShown('missing-persons')) ? [['Case kind', caseKinds[f.case_kind] || 'All cases']] : []),
                ['Livelihood / occupation', f.livelihood_code ? this.$store.livelihood.label(f.livelihood_code) : (f.livelihood_group ? this.$store.livelihood.label(f.livelihood_group) || f.livelihood_group : 'All')],
            ];
        },
        periodText(p) {
            const fmt = (iso) => (iso ? new Date(iso + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '');
            return p.from && p.to ? fmt(p.from) + ' – ' + fmt(p.to) : (fmt(p.from) || fmt(p.to) || 'All dates');
        },
        // The situation report's key figures (2026-10-30): every section, not
        // incidents alone. "Now" figures say so; the rest count the period.
        keyFigures(s) {
            const e = s.evacuation, r = s.relief.totals, res = s.resources.now;
            const p = s.persons || {};
            return [
                ['Incidents', 'Incidents reported', s.incidents.total],
                ['Evacuation (right now)', 'Open evacuation centres', e.now.open_centres],
                ['Evacuation (right now)', 'Families inside centres', e.now.families_inside],
                ['Evacuation (right now)', 'Persons inside centres', e.now.persons_inside],
                ['Evacuation (right now)', 'Capacity of open centres', e.now.capacity],
                ['Evacuation', 'Families checked in', e.period.families_checked_in],
                ['Evacuation', 'Individuals evacuated', s.evacuated.individuals],
                ['Evacuation', 'Families checked out', e.period.families_checked_out],
                ...(hasPersons(s) ? [['Missing persons', 'Still missing', p.missing], ['Missing persons', 'Found', p.found], ['Missing persons', 'Deceased', p.deceased], ['Missing persons', 'Unidentified', p.unidentified]] : []),
                ['Relief', 'Families served', r.families],
                ['Relief', 'Items released', r.released],
                ['Relief', 'Awaiting release', r.awaiting],
                ['Relief', 'Cost of assistance (PHP)', this.money(r.cost)],
                ['Resources (right now)', 'Units deployed', res.deployed],
                ['Resources (right now)', 'Units available', res.available],
                ['Damage', 'Damage reports', s.damage.reports],
                ['Damage', 'Units affected', s.damage.units_affected],
                ['Damage', 'Estimated cost (PHP)', this.money(s.damage.estimated_cost)],
                ['Alerts', 'Alerts issued', s.alerts.issued],
                ['Alerts', 'Alerts cancelled', s.alerts.cancelled],
            ];
        },
        money(n) { return Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        cell(v) { return v === null || v === undefined || v === '' ? '—' : (typeof v === 'number' ? v.toLocaleString('en-PH') : v); },

        // Every table of the situation report, by section: the screen shows a
        // section's tables, the XLSX one sheet per section, the PDF all of
        // them in order. Counts only; no names. `all` (the export) adds the
        // per-incident missing-person table, from every incident row.
        reportTables(d, all = false) {
            const s = d.summary;
            const n = (key, label) => ({ key, label, num: true });
            const t = (key, label) => ({ key, label });
            const pairs = (obj) => Object.entries(obj || {}).map(([label, count]) => ({ label, count }));
            const age = s.evacuees.by_age, sex = s.evacuees.by_sex;
            const tables = [
                { group: 'evacuation', heading: 'Evacuation centres (right now)', note: 'Occupancy when the report was made, not over the period.',
                    columns: [t('name', 'Centre'), t('barangay', 'Barangay'), t('status', 'Status'), n('capacity', 'Capacity'), n('families', 'Families'), n('persons', 'Persons'), n('available', 'Space left'), n('percent_full', '% full')],
                    rows: s.evacuation.centres },
                { group: 'evacuation', heading: 'Evacuated people by sex', note: 'People checked in during the period: ' + s.evacuees.persons.toLocaleString('en-PH') + ' in all.',
                    columns: [t('label', 'Sex'), n('count', 'People')], rows: pairs(sex) },
                { group: 'evacuation', heading: 'Evacuated people by age group', note: 'Age when checked in; from the birth date when no age was typed.',
                    columns: [t('label', 'Age group'), n('count', 'People')], rows: pairs(age) },
                { group: 'evacuation', heading: 'Vulnerable sectors among evacuated people', columns: [t('label', 'Sector'), n('count', 'People')], rows: pairs(s.evacuees.by_sector) },
                { group: 'evacuation', heading: 'Evacuated families (FACED details)', note: 'Families checked in during the period, from the details recorded on their FACED card.',
                    columns: [t('label', 'Detail'), n('count', 'Families')],
                    rows: [{ label: 'Families', count: s.evacuees.families }, { label: '4Ps beneficiaries', count: s.evacuees.four_ps }, { label: 'Indigenous Peoples', count: s.evacuees.indigenous },
                        ...pairs(s.evacuees.shelter_damage).map((r) => ({ ...r, label: 'House: ' + r.label })), ...pairs(s.evacuees.house_ownership).map((r) => ({ ...r, label: 'Ownership: ' + r.label }))] },
                { group: 'relief', heading: 'Relief by item', columns: [t('item', 'Item'), t('unit', 'Unit'), n('quantity_released', 'Quantity released'), n('released', 'Records released'), n('awaiting', 'Awaiting release'), n('families', 'Families'), { ...n('cost', 'Cost (PHP)'), money: true }], rows: s.relief.by_item },
                { group: 'relief', heading: 'Relief by operation', columns: [t('operation', 'Operation'), n('records', 'Records'), n('families', 'Families'), n('released', 'Released'), n('awaiting', 'Awaiting'), { ...n('cost', 'Cost (PHP)'), money: true }], rows: s.relief.by_operation },
                { group: 'relief', heading: 'Relief by barangay', note: "The beneficiary's home barangay.", columns: [t('barangay', 'Barangay'), n('records', 'Records'), n('families', 'Families'), n('released', 'Released'), n('awaiting', 'Awaiting'), { ...n('cost', 'Cost (PHP)'), money: true }], rows: s.relief.by_barangay },
                { group: 'relief', heading: 'What families said in the app', columns: [t('label', 'Answer'), n('count', 'Items')],
                    rows: [{ label: 'Received', count: s.relief.totals.received }, { label: 'Not received', count: s.relief.totals.not_received }] },
                { group: 'resources', heading: 'Resources (right now)', note: 'The stock when the report was made.', columns: [t('name', 'Resource'), t('category', 'Category'), t('barangay', 'Kept by'), n('quantity', 'Total'), n('deployed', 'Deployed'), n('available', 'Available'), t('unit', 'Unit'), t('readiness', 'Readiness')], rows: s.resources.items },
                { group: 'resources', heading: 'Deployed and returned during the period', columns: [t('resource', 'Resource'), t('incident', 'Incident'), n('deployed', 'Deployed'), n('returned', 'Returned'), t('unit', 'Unit')], rows: s.resources.deployments },
                { group: 'damage', heading: 'Damage by sector', columns: [t('sector', 'Sector'), n('reports', 'Reports'), n('units_affected', 'Units affected'), { ...n('estimated_cost', 'Estimated cost (PHP)'), money: true }], rows: s.damage.by_sector },
                { group: 'damage', heading: 'Damage by barangay', columns: [t('barangay', 'Barangay'), n('reports', 'Reports'), n('units_affected', 'Units affected'), { ...n('estimated_cost', 'Estimated cost (PHP)'), money: true }], rows: s.damage.by_barangay },
                ...(hasPersons(s) ? [{ group: 'missing', heading: 'Missing, found, deceased and unidentified persons', note: 'Counts only. Names stay in the Missing Persons tab.',
                    columns: [t('label', 'Status'), n('count', 'People')],
                    rows: [{ label: 'Still missing', count: s.persons.missing }, { label: 'Found', count: s.persons.found }, { label: 'Deceased', count: s.persons.deceased }, { label: 'Unidentified', count: s.persons.unidentified }] }] : []),
                { group: 'alerts', heading: 'Public alerts', columns: [t('ref', 'Alert'), t('level', 'Level'), t('type', 'Type'), t('title', 'Title'), t('barangays', 'Barangays'), t('status', 'Status'), t('published', 'Published'), n('reached', 'Reached')],
                    rows: s.alerts.list.map((a) => ({ ...a, published: a.published_at ? new Date(a.published_at).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '' })) },
            ];
            if (all && hasPersons(s)) {
                tables.splice(tables.findIndex((x) => x.group === 'missing') + 1, 0, { group: 'missing', heading: 'Persons by incident', columns: [t('ref', 'Incident'), t('title', 'Summary'), n('missing', 'Missing'), n('found', 'Found'), n('deceased', 'Deceased')],
                    rows: d.rows.filter((r) => r.missing || r.found || r.deceased) });
            }
            return tables;
        },
        section: 'overview',
        sectionTabs: [['overview', 'Overview'], ['evacuation', 'Evacuation'], ['relief', 'Relief'], ['resources', 'Resources'], ['damage', 'Damage'], ['missing', 'Missing persons'], ['alerts', 'Alerts']],
        tablesFor(group) { return this.summary ? this.reportTables({ summary: this.summary, rows: this.rows }).filter((x) => x.group === group) : []; },
        hasPersons() { return hasPersons(this.summary); },
        // The section tabs; Missing persons only with its figures.
        shownSectionTabs() { return this.sectionTabs.filter((tab) => tab[0] !== 'missing' || this.hasPersons()); },
        // The figure tiles; the person ones only with their figures.
        figureCards() {
            const s = this.summary;
            if (!s) return [];
            const p = hasPersons(s) ? s.persons : null;
            return [
                ['Incidents', s.incidents.total, ''],
                ['Evacuated individuals', s.evacuated.individuals, ''],
                ['Evacuated families', s.evacuated.families, ''],
                ['Inside centers now', s.evacuation.now.persons_inside, ''],
                ...(p ? [['Still missing', p.missing, 'sk-t-amber'], ['Found', p.found, ''], ['Deceased', p.deceased, ''], ['Unidentified', p.unidentified, 'sk-t-amber']] : []),
                ['Relief released', s.relief.totals.released, ''],
                ['Resources deployed now', s.resources.now.deployed, ''],
                ['Damage reports', s.damage.reports, ''],
                ['Alerts issued', s.alerts.issued, ''],
            ].map((c) => [c[0], Number(c[1] || 0), c[2]]);
        },
        // A table's rows as the PDF prints them: money with two decimals.
        printable(tbl) {
            const money = tbl.columns.filter((c) => c.money).map((c) => c.key);
            return money.length ? tbl.rows.map((r) => ({ ...r, ...Object.fromEntries(money.map((k) => [k, this.money(r[k])])) })) : tbl.rows;
        },
        columns(s) {
            return [
                { key: 'ref', label: 'Incident' }, { key: 'reported', label: 'Reported' }, { key: 'type', label: 'Disaster type' },
                { key: 'barangay', label: 'Barangay' }, { key: 'title', label: 'Summary' }, { key: 'status', label: 'Status' },
                ...(hasPersons(s) ? [{ key: 'missing', label: 'Missing' }, { key: 'found', label: 'Found' }, { key: 'deceased', label: 'Deceased' }] : []),
                { key: 'damage_reports', label: 'Damage reports' }, { key: 'units_affected', label: 'Units affected' },
            ];
        },
        livelihoodTable(s) {
            return { heading: 'Evacuated people by livelihood', note: 'The occupation recorded at check-in.', columns: [{ key: 'code', label: 'Code' }, { key: 'label', label: 'Livelihood' }, { key: 'people', label: 'People', num: true }],
                rows: [...s.livelihood.items, { code: '', label: 'Not yet classified', people: s.livelihood.unclassified }] };
        },
        // The workbook (owner, 2026-11-03): the Executive Summary first, then
        // Analytics (each chart's table with a native Excel chart beside it),
        // then the detailed data, one sheet per section. Every sheet is read
        // from the same export as the PDF.
        async writeXlsx(d) {
            const [{ executiveSummary, charts: reportCharts }, { S, downloadXlsx }] = await Promise.all([loadInsights(), loadWorkbook()]);
            const width = (rows) => {
                const w = [];
                rows.forEach((r) => (r || []).forEach((c, i) => {
                    const v = c && typeof c === 'object' ? c.v : c;
                    const len = v === null || v === undefined ? 0 : (typeof v === 'number' ? v.toLocaleString('en-PH').length + 2 : String(v).length);
                    if (!(c && typeof c === 'object' && [S.title, S.subtitle, S.section, S.note, S.para].includes(c.s))) w[i] = Math.max(w[i] || 8, Math.min(48, len + 2));
                }));
                return w.map((x) => x || 10);
            };
            const head = (labels) => labels.map((v) => ({ v, s: S.header }));
            const val = (col, v) => (typeof v === 'number' ? { v, s: col.money ? S.money : S.num } : v ?? '');
            const paraHeight = (text, chars = 118) => Math.max(18, Math.ceil(String(text).length / chars) * 15 + 6);

            // 1. Executive Summary
            const sum = { name: 'Executive Summary', gridlines: false, cols: [26, 44, 16, 14, 14, 14], rows: [], merges: [], heights: {} };
            const push = (row, h) => { sum.rows.push(row); if (h) sum.heights[sum.rows.length - 1] = h; return sum.rows.length - 1; };
            const span = (r, from = 'A') => sum.merges.push(from + (r + 1) + ':F' + (r + 1));
            span(push([{ v: d.title, s: S.title }], 26));
            span(push([{ v: 'Municipality of Esperanza  ·  Municipal Disaster Risk Reduction and Management Office', s: S.subtitle }]));
            push([]);
            [...this.filterLines(d).slice(1), ['Data as of', new Date(d.summary.as_of).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' })]].forEach(([k, v]) => span(push([{ v: k, s: S.label }, v]), 'B'));
            push([]);
            push([{ v: 'Executive summary', s: S.section }]);
            executiveSummary(d).forEach((ptext) => span(push([{ v: ptext, s: S.para }], paraHeight(ptext))));
            push([]);
            push([{ v: 'Key figures', s: S.section }]);
            push(head(['Section', 'Figure', 'Value']));
            this.keyFigures(d.summary).forEach(([sec, k, v]) => {
                const n = typeof v === 'string' ? Number(v.replace(/,/g, '')) : v;
                push([sec, k, { v: n, s: /PHP/.test(k) ? S.money : S.num }]);
            });
            push([]);
            push([{ v: 'What is in this workbook', s: S.section }]);
            const contents = [['Analytics', 'Each chart with the table it reads; change a number or restyle a chart in Excel.'], ['Incidents', 'Every incident in the period, one row each.'],
                ['Evacuation', 'Centres now, evacuated people by sex, age and sector, FACED details.'], ['Relief', 'Relief by item, operation and barangay; what families said in the app.'],
                ['Resources', 'Stock now; deployed and returned in the period.'], ['Damage', 'Damage by sector and barangay.'], ['Missing persons', 'Counts by status and by incident (no names).'],
                ['Alerts', 'Public alerts issued.'], ['Livelihood', 'Evacuated people by occupation.']].filter(([k]) => k !== 'Missing persons' || hasPersons(d.summary));
            contents.forEach(([k, v]) => span(push([{ v: k, s: S.label }, v]), 'B'));
            push([]);
            push([{ v: 'Notes', s: S.section }]);
            d.summary.notes.forEach((note) => span(push([{ v: note, s: S.note }], paraHeight(note, 140))));

            // 2. Analytics: each chart's table, its chart to the right.
            const an = { name: 'Analytics', gridlines: false, cols: [36, 15, 15, 3], rows: [], merges: [], heights: {}, charts: [] };
            an.rows.push([{ v: 'Analytics', s: S.title }]);
            an.heights[0] = 24;
            an.rows.push([{ v: 'Charts read their numbers from the tables beside them, under the same filters as the Executive Summary.', s: S.subtitle }]);
            an.rows.push([]);
            reportCharts(d).forEach((c) => {
                const r = an.rows.length;
                const n = c.categories.length;
                an.rows.push([{ v: c.title, s: S.section }]);
                if (c.note) an.rows[r].push({ v: c.note, s: S.note });
                an.rows.push(head(['Category', ...c.series.map((sr) => sr.name)]));
                c.categories.forEach((cat, i) => an.rows.push([cat, ...c.series.map((sr) => ({ v: Number(sr.values[i] || 0), s: c.format === 'money' ? S.money : S.num }))]));
                const tall = c.kind === 'bar' ? Math.max(15, n * c.series.length + 6) : 16;
                an.charts.push({ title: c.title, kind: c.kind, money: c.format === 'money', categories: c.categories, catRange: [0, r + 2, r + 1 + n],
                    series: c.series.map((sr, k) => ({ name: sr.name, color: sr.color, colors: sr.colors, values: sr.values, col: 1 + k, nameCell: [1 + k, r + 1] })),
                    anchor: { col: 4, row: r, toCol: 13, toRow: r + tall } });
                while (an.rows.length < r + Math.max(n + 3, tall + 1)) an.rows.push([]);
                an.rows.push([]);
            });
            if (!an.charts.length) an.rows.push([{ v: 'No figures in this period to chart.', s: S.note }]);

            // 3. Detailed data.
            const cols = this.columns(d.summary);
            const incRows = [head(cols.map((c) => c.label)), ...d.rows.map((r) => cols.map((c) => (c.key === 'reported' ? new Date(r.reported).toLocaleString('en-PH') : c.key === 'status' ? (incidentWords[r.status] || r.status) : val({ money: false }, r[c.key]))))];
            if (!d.rows.length) incRows.push([{ v: 'No incidents in this period under these filters.', s: S.note }]);
            const incidents = { name: 'Incidents', rows: incRows, freeze: 1 };
            incidents.cols = width(incRows);
            const tables = this.reportTables(d, true);
            const block = (tbl) => [[{ v: tbl.heading, s: S.section }], ...(tbl.note ? [[{ v: tbl.note, s: S.note }]] : []), head(tbl.columns.map((c) => c.label)),
                ...(tbl.rows.length ? tbl.rows.map((r) => tbl.columns.map((c) => val(c, r[c.key]))) : [[{ v: 'None recorded.', s: S.note }]]), []];
            const sectionSheets = [['evacuation', 'Evacuation'], ['relief', 'Relief'], ['resources', 'Resources'], ['damage', 'Damage'], ['missing', 'Missing persons'], ['alerts', 'Alerts']]
                .filter(([group]) => group !== 'missing' || hasPersons(d.summary))
                .map(([group, name]) => { const rows = tables.filter((x) => x.group === group).flatMap(block); return { name, rows, cols: width(rows) }; });
            const livRows = block(this.livelihoodTable(d.summary));
            await downloadXlsx([sum, an, incidents, ...sectionSheets, { name: 'Livelihood', rows: livRows, cols: width(livRows) }],
                'sakuna-report_' + d.summary.period.from + '_' + d.summary.period.to + '.xlsx', { title: d.title + ', ' + this.periodText(d.summary.period), creator: d.generated_by });
        },
        // The management report as one PDF (owner, 2026-11-03): the period and
        // filters, the executive summary, the key figures and the charts, then
        // every detailed table on its own pages, numbered "Page x of y".
        async writePdf(d) {
            const { executiveSummary, kpis: reportKpis, charts: reportCharts } = await loadInsights();
            const incidents = d.rows.map((r) => ({ ...r, status: incidentWords[r.status] || r.status, reported: new Date(r.reported).toLocaleDateString('en-PH') }));
            const sections = [
                { heading: 'Key figures in full', note: 'As of ' + new Date(d.summary.as_of).toLocaleString('en-PH') + '. "Right now" figures show that moment; the rest count the period.',
                    columns: [{ key: 's', label: 'Section' }, { key: 'k', label: 'Figure' }, { key: 'v', label: 'Value' }],
                    rows: this.keyFigures(d.summary).map(([s, k, v]) => ({ s, k, v: typeof v === 'number' ? v.toLocaleString('en-PH') : v })) },
                { heading: 'Incidents', columns: this.columns(d.summary), rows: incidents },
                ...this.reportTables(d, true).map((tbl) => ({ ...tbl, rows: this.printable(tbl) })),
                this.livelihoodTable(d.summary),
                { heading: 'Notes', columns: [{ key: 'note', label: 'What the records can and cannot say' }], rows: d.summary.notes.map((note) => ({ note })) },
            ];
            await window.reportPdf(sections, {
                filename: 'sakuna-report_' + d.summary.period.from + '_' + d.summary.period.to + '.pdf',
                title: d.title,
                office: 'Municipal Disaster Risk Reduction and Management Office',
                subtitle: 'Generated by ' + d.generated_by,
                filters: [...this.filterLines(d).slice(2), ['Data as of', new Date(d.summary.as_of).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' })]],
                summary: executiveSummary(d),
                kpis: reportKpis(d),
                charts: reportCharts(d),
            });
        },
    };
}

// ------------------------------------------------ One incident's printed report

const TZ = 'Asia/Manila';
/** 'Nov 5, 2026, 3:04 PM', as the time it is in Esperanza. */
const manilaWhen = (iso) => (iso ? new Date(iso).toLocaleString('en-US', { timeZone: TZ, dateStyle: 'medium', timeStyle: 'short' }) : '');
const stepWords = { report: 'Reported', validate: 'Validated', assign: 'Assigned', dispatch: 'Dispatched', escalate: 'Escalated', resolve: 'Resolved', close: 'Closed', deploy: 'Deployed', return: 'Returned' };
const personKinds = { missing: 'Missing person', deceased: 'Deceased person', unidentified: 'Unidentified body' };

/**
 * A printable report of one incident (Sprint 2, owner 2026-11-05), from the
 * incident window's Print report: GET /admin/sakuna/reports/incident/{ref}
 * (sakuna_reports.print; the server logs each one). Shown and printed as the
 * portal's printed reports are (components/admin/report-sheet.blade.php: the
 * rep-paper sheet on A4 portrait pages, body.dp-printing isolating
 * #dp-print-area). persons is null where the viewer may not read Missing
 * Persons or it is off; the section is then left out.
 */
export function incidentReportPrint() {
    return {
        open: false,
        loading: false,
        failed: false,
        error: '',
        ref: '',
        report: null,
        async show(ref) {
            if (!ref) return;
            this.ref = ref;
            this.report = null;
            this.failed = false;
            this.error = '';
            this.loading = true;
            this.open = true;
            try {
                const res = await api.get('/admin/sakuna/reports/incident/' + encodeURIComponent(ref));
                if (this.ref === ref) this.report = res.data;
            } catch (err) {
                if (this.ref !== ref) return;
                this.failed = true;
                this.error = err?.status === 404 ? 'This incident was not found, or it is outside your barangay.' : (err?.localised ? err.localised('en') : 'Could not load the report.');
            } finally {
                if (this.ref === ref) this.loading = false;
            }
        },
        print() {
            if (!this.report) return;
            document.body.classList.add('dp-printing');
            this.$nextTick(() => window.print());
        },
        when: manilaWhen,
        word(v) { return stepWords[v] || (v ? String(v).charAt(0).toUpperCase() + String(v).slice(1).replace(/_/g, ' ') : ''); },
        status(s) { return incidentWords[s] || s || ''; },
        kind(k) { return personKinds[k] || k || ''; },
        num(n) { return Number(n || 0).toLocaleString('en-PH'); },
        peso(n) { return '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        // The incident's details, two to a row; what the record leaves blank is left out.
        get detailRows() {
            const i = this.report?.incident || {};
            const pairs = [
                ['Incident', i.ref], ['Disaster type', i.type],
                ['Severity', i.severity], ['Status', this.status(i.status)],
                ['Barangay', i.barangay], ['Sitio / Purok', i.sitio],
                ['Reported', manilaWhen(i.reported)], ['Source', this.word(i.source)],
                ['Reported by', i.reporter_name || i.reporter?.name], ['Assigned to', i.assigned_name],
                ['Coordinates', i.lat !== null && i.lat !== undefined && i.lng !== null && i.lng !== undefined ? i.lat + ', ' + i.lng : ''],
                ['Photos', this.photoCount(i) ? this.photoCount(i) + ' on file' : ''],
            ].filter((p) => p[1] !== '' && p[1] !== null && p[1] !== undefined);
            const rows = [];
            for (let k = 0; k < pairs.length; k += 2) rows.push(pairs.slice(k, k + 2));
            return rows;
        },
        /** photo_count from the server; an older payload's photos list counted instead. */
        photoCount(i) {
            if (i?.photo_count !== null && i?.photo_count !== undefined && Number.isFinite(Number(i.photo_count))) return Number(i.photo_count);
            return Array.isArray(i?.photos) ? i.photos.length : 0;
        },
        get generatedText() {
            const r = this.report;
            return r ? 'Generated ' + manilaWhen(r.generated_at) + ' by ' + (r.generated_by || '') : '';
        },
    };
}

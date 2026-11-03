// Sakuna's Reports tab (owner, 2026-10-28/30). Every figure, chart and table
// row comes from the server's one calculation (GET /admin/sakuna/reports/
// summary and /incidents) under the same filters, so the cards, the charts,
// the table and the exports agree. Export is its own permission, checked and
// logged on the server (POST /admin/sakuna/reports/export); the file is built
// here from what that returns, as the other exports are.
import api from './api/client';
import { loadXlsx } from './resident-import';

// Incidents speak their own words for the universal statuses (sakuna.blade.php incidentStatusLabel).
const incidentWords = { Submitted: 'Reported', 'Under Verification': 'Validated', Processing: 'Dispatched', Completed: 'Resolved', Archived: 'Closed' };
const blankFilters = () => ({ barangay: '', from: '', to: '', type: '', status: '', livelihood_group: '', livelihood_code: '' });
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
            try {
                const [s, r] = await Promise.all([
                    api.get('/admin/sakuna/reports/summary', { params: this.params() }),
                    api.get('/admin/sakuna/reports/incidents', { params: this.params({ page, per_page: 25 }) }),
                ]);
                this.summary = s.data;
                this.rows = r.data || [];
                this.total = r.meta?.total ?? this.rows.length;
                this.lastPage = r.meta?.last_page || 1;
            } catch {
                this.failed = true;
            } finally {
                this.loading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
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
                toast(err?.localised ? err.localised('en') : 'Could not export this report.', 'error');
            } finally {
                this.exporting = '';
            }
        },
        filterLines(d) {
            const f = d.filters || {};
            return [
                ['Report', d.title],
                ['Generated', new Date(d.generated_at).toLocaleString('en-PH') + ' by ' + d.generated_by],
                ['Period', d.summary.period.from + ' to ' + d.summary.period.to],
                ['Barangay', f.barangay || 'All barangays'],
                ['Disaster type', f.type || 'All types'],
                ['Incident status', f.status || 'All statuses'],
                ['Livelihood', f.livelihood_code ? this.$store.livelihood.label(f.livelihood_code) : (f.livelihood_group || 'All')],
            ];
        },
        // The situation report's key figures (2026-10-30): every section, not
        // incidents alone. "Now" figures say so; the rest count the period.
        keyFigures(s) {
            const e = s.evacuation, r = s.relief.totals, res = s.resources.now;
            return [
                ['Incidents', 'Incidents reported', s.incidents.total],
                ['Evacuation (right now)', 'Open evacuation centres', e.now.open_centres],
                ['Evacuation (right now)', 'Families inside centres', e.now.families_inside],
                ['Evacuation (right now)', 'Persons inside centres', e.now.persons_inside],
                ['Evacuation (right now)', 'Capacity of open centres', e.now.capacity],
                ['Evacuation', 'Families checked in', e.period.families_checked_in],
                ['Evacuation', 'Individuals evacuated', s.evacuated.individuals],
                ['Evacuation', 'Families checked out', e.period.families_checked_out],
                ['Missing persons', 'Still missing', s.persons.missing],
                ['Missing persons', 'Found', s.persons.found],
                ['Missing persons', 'Deceased', s.persons.deceased],
                ['Missing persons', 'Unidentified', s.persons.unidentified],
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
                { group: 'missing', heading: 'Missing, found, deceased and unidentified persons', note: 'Counts only. Names stay in the Missing Persons tab.',
                    columns: [t('label', 'Status'), n('count', 'People')],
                    rows: [{ label: 'Still missing', count: s.persons.missing }, { label: 'Found', count: s.persons.found }, { label: 'Deceased', count: s.persons.deceased }, { label: 'Unidentified', count: s.persons.unidentified }] },
                { group: 'alerts', heading: 'Public alerts', columns: [t('ref', 'Alert'), t('level', 'Level'), t('type', 'Type'), t('title', 'Title'), t('barangays', 'Barangays'), t('status', 'Status'), t('published', 'Published'), n('reached', 'Reached')],
                    rows: s.alerts.list.map((a) => ({ ...a, published: a.published_at ? new Date(a.published_at).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '' })) },
            ];
            if (all) {
                tables.splice(tables.findIndex((x) => x.group === 'missing') + 1, 0, { group: 'missing', heading: 'Persons by incident', columns: [t('ref', 'Incident'), t('title', 'Summary'), n('missing', 'Missing'), n('found', 'Found'), n('deceased', 'Deceased')],
                    rows: d.rows.filter((r) => r.missing || r.found || r.deceased) });
            }
            return tables;
        },
        section: 'overview',
        sectionTabs: [['overview', 'Overview'], ['evacuation', 'Evacuation'], ['relief', 'Relief'], ['resources', 'Resources'], ['damage', 'Damage'], ['missing', 'Missing persons'], ['alerts', 'Alerts']],
        tablesFor(group) { return this.summary ? this.reportTables({ summary: this.summary, rows: this.rows }).filter((x) => x.group === group) : []; },
        // A table's rows as the PDF prints them: money with two decimals.
        printable(tbl) {
            const money = tbl.columns.filter((c) => c.money).map((c) => c.key);
            return money.length ? tbl.rows.map((r) => ({ ...r, ...Object.fromEntries(money.map((k) => [k, this.money(r[k])])) })) : tbl.rows;
        },
        columns() {
            return [
                { key: 'ref', label: 'Incident' }, { key: 'reported', label: 'Reported' }, { key: 'type', label: 'Disaster type' },
                { key: 'barangay', label: 'Barangay' }, { key: 'title', label: 'Summary' }, { key: 'status', label: 'Status' },
                { key: 'missing', label: 'Missing' }, { key: 'found', label: 'Found' }, { key: 'deceased', label: 'Deceased' },
                { key: 'damage_reports', label: 'Damage reports' }, { key: 'units_affected', label: 'Units affected' },
            ];
        },
        livelihoodTable(s) {
            return { heading: 'Evacuated people by livelihood', note: 'The occupation recorded at check-in.', columns: [{ key: 'code', label: 'Code' }, { key: 'label', label: 'Livelihood' }, { key: 'people', label: 'People', num: true }],
                rows: [...s.livelihood.items, { code: '', label: 'Not yet classified', people: s.livelihood.unclassified }] };
        },
        // One sheet per section after the Summary and the Incidents: each of
        // its tables stacked, a heading and note above the column heads.
        async writeXlsx(d) {
            const XLSX = await loadXlsx();
            const wb = XLSX.utils.book_new();
            const cover = [...this.filterLines(d), ['As of', new Date(d.summary.as_of).toLocaleString('en-PH')], [], ['Section', 'Figure', 'Value'],
                ...this.keyFigures(d.summary).map(([sec, k, v]) => [sec, k, typeof v === 'string' ? Number(v.replace(/,/g, '')) : v]), [], ['Notes'], ...d.summary.notes.map((x) => [x])];
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(cover), 'Summary');
            const cols = this.columns();
            const rows = d.rows.map((r) => cols.map((c) => (c.key === 'reported' ? new Date(r.reported).toLocaleString('en-PH') : r[c.key])));
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet([cols.map((c) => c.label), ...rows]), 'Incidents');
            const tables = this.reportTables(d, true);
            const sheets = [['evacuation', 'Evacuation'], ['relief', 'Relief'], ['resources', 'Resources'], ['damage', 'Damage'], ['missing', 'Missing persons'], ['alerts', 'Alerts']];
            const block = (tbl) => [[tbl.heading], ...(tbl.note ? [[tbl.note]] : []), tbl.columns.map((c) => c.label),
                ...(tbl.rows.length ? tbl.rows.map((r) => tbl.columns.map((c) => r[c.key] ?? '')) : [['None recorded.']]), []];
            for (const [group, name] of sheets) {
                XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(tables.filter((x) => x.group === group).flatMap(block)), name);
            }
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(block(this.livelihoodTable(d.summary))), 'Livelihood');
            XLSX.writeFile(wb, 'sakuna-report_' + d.summary.period.from + '_' + d.summary.period.to + '.xlsx');
        },
        // The situation report as one PDF: the filters, the key figures, the
        // incidents, then every section's tables and the notes.
        async writePdf(d) {
            const subtitle = this.filterLines(d).slice(2).map(([k, v]) => k + ': ' + v).join(' · ');
            const incidents = d.rows.map((r) => ({ ...r, reported: new Date(r.reported).toLocaleDateString('en-PH') }));
            const sections = [
                { heading: 'Key figures', note: 'As of ' + new Date(d.summary.as_of).toLocaleString('en-PH') + '. "Right now" figures show that moment; the rest count the period.',
                    columns: [{ key: 's', label: 'Section' }, { key: 'k', label: 'Figure' }, { key: 'v', label: 'Value' }],
                    rows: this.keyFigures(d.summary).map(([s, k, v]) => ({ s, k, v: typeof v === 'number' ? v.toLocaleString('en-PH') : v })) },
                { heading: 'Incidents', columns: this.columns(), rows: incidents },
                ...this.reportTables(d, true).map((tbl) => ({ ...tbl, rows: this.printable(tbl) })),
                this.livelihoodTable(d.summary),
                { heading: 'Notes', columns: [{ key: 'note', label: 'What the records can and cannot say' }], rows: d.summary.notes.map((note) => ({ note })) },
            ];
            await window.reportPdf(sections, { filename: 'sakuna-report_' + d.summary.period.from + '_' + d.summary.period.to + '.pdf', title: d.title, subtitle });
        },
    };
}
